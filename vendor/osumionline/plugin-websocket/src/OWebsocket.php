<?php

declare(strict_types=1);

namespace Osumi\OsumiFramework\Plugins\OWebsocket;

use Closure;
use Osumi\OsumiFramework\Web\ORequest;
use Ratchet\App;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use RuntimeException;
use Throwable;

/**
 * OWebsocket - WebSocket server handler for Osumi Framework
 */
final class OWebsocket {
	/**
	 * @var string Default websocket host
	 */
	private const DEFAULT_HOST = 'localhost';

	/**
	 * @var int Default websocket port
	 */
	private const DEFAULT_PORT = 8080;

	/**
	 * @var string Default websocket path
	 */
	private const DEFAULT_PATH = '/';

	/**
	 * @var ?Closure Token validation method
	 */
	private static ?Closure $validate_method = null;

	/**
	 * @var array<int|string, array{
	 *   id:int|string,
	 *   user_id:int|string|null
	 * }>
	 */
	private static array $connections = [];

	/**
	 * @var array<int|string, ConnectionInterface>
	 */
	private static array $connection_objects = [];

	/**
	 * @var array<int|string, array{
	 *   id:int|string,
	 *   token:string,
	 *   connections:array<int|string>,
	 *   data:array<string, mixed>
	 * }>
	 */
	private static array $users = [];

	/**
	 * @var int|string|null Current connection id being processed
	 */
	private static int|string|null $current_connection_id = null;

	/**
	 * Set token validation method
	 *
	 * Expected signature:
	 * function (string $token): ?array
	 *
	 * @param callable $validate_method Token validation method
	 *
	 * @return void
	 */
	public static function setValidateMethod(callable $validate_method): void {
		self::$validate_method = Closure::fromCallable($validate_method);
	}

	/**
	 * Load websocket actions file
	 *
	 * @return void
	 */
	public static function loadActions(): void {
		global $core;

		$actions_file = $core->config->getDir('app') . 'Websocket/actions.php';

		if (!file_exists($actions_file)) {
			throw new RuntimeException(sprintf('Websocket actions file not found: %s', $actions_file));
		}

		require_once $actions_file;
	}

	/**
	 * Start websocket server
	 *
	 * @return void
	 */
	public static function start(): void {
		$config = self::getPluginConfig();

		$host = self::DEFAULT_HOST;
		if (array_key_exists('host', $config) && is_string($config['host']) && trim($config['host']) !== '') {
			$host = trim($config['host']);
		}

		$port = self::DEFAULT_PORT;
		if (array_key_exists('port', $config) && is_numeric($config['port'])) {
			$port = intval($config['port']);
		}

		$path = self::DEFAULT_PATH;
		if (array_key_exists('path', $config) && is_string($config['path']) && trim($config['path']) !== '') {
			$path = self::normalizePath($config['path']);
		}

		$ws_handler = new class implements MessageComponentInterface {
			/**
			 * Handle a new opened connection
			 *
			 * @param ConnectionInterface $conn Opened connection
			 *
			 * @return void
			 */
			public function onOpen(ConnectionInterface $conn): void {
				OWebsocket::addConnection($conn);
			}

			/**
			 * Handle an incoming message
			 *
			 * @param ConnectionInterface $from Source connection
			 *
			 * @param string $msg Received message
			 *
			 * @return void
			 */
			public function onMessage(ConnectionInterface $from, $msg): void {
				OWebsocket::setCurrentConnectionId($from->resourceId);
				OWebsocket::handleMessage(strval($msg));
			}

			/**
			 * Handle a closed connection
			 *
			 * @param ConnectionInterface $conn Closed connection
			 *
			 * @return void
			 */
			public function onClose(ConnectionInterface $conn): void {
				OWebsocket::setCurrentConnectionId($conn->resourceId);
				OWebsocket::removeConnection();
			}

			/**
			 * Handle a connection error
			 *
			 * @param ConnectionInterface $conn Connection with error
			 *
			 * @param \Exception $e Thrown exception
			 *
			 * @return void
			 */
			public function onError(ConnectionInterface $conn, \Exception $e): void {
				OWebsocket::setCurrentConnectionId($conn->resourceId);
				$conn->close();
				OWebsocket::removeConnection();
			}
		};

		$app = new App($host, $port);
		$app->route($path, $ws_handler, ['*']);
		$app->run();
	}

	/**
	 * Register a new open connection
	 *
	 * @param ConnectionInterface $connection Connection object
	 *
	 * @return void
	 */
	public static function addConnection(ConnectionInterface $connection): void {
		$connection_id = $connection->resourceId;

		self::$connections[$connection_id] = [
			'id' => $connection_id,
			'user_id' => null
		];

		self::$connection_objects[$connection_id] = $connection;
	}

	/**
	 * Set current connection id
	 *
	 * @param int|string|null $connection_id Current connection identifier
	 *
	 * @return void
	 */
	public static function setCurrentConnectionId(int|string|null $connection_id): void {
		self::$current_connection_id = $connection_id;
	}

	/**
	 * Get current connection id
	 *
	 * @return int|string|null Current connection identifier
	 */
	public static function getCurrentConnectionId(): int|string|null {
		return self::$current_connection_id;
	}

	/**
	 * Remove a connection from memory
	 *
	 * If the removed connection is the last one for its user,
	 * user data will also be removed.
	 *
	 * @return void
	 */
	public static function removeConnection(): void {
		$current_connection_id = self::getCurrentConnectionId();

		if (is_null($current_connection_id)) {
			return;
		}

		if (!array_key_exists($current_connection_id, self::$connections)) {
			return;
		}

		$user_id = self::$connections[$current_connection_id]['user_id'];

		if (!is_null($user_id) && array_key_exists($user_id, self::$users)) {
			$user_connections = self::$users[$user_id]['connections'];
			$filtered_connections = array_values(
				array_filter(
					$user_connections,
					fn (int|string $connection_id): bool => $connection_id !== $current_connection_id
				)
			);

			self::$users[$user_id]['connections'] = $filtered_connections;

			if (count(self::$users[$user_id]['connections']) === 0) {
				unset(self::$users[$user_id]);
			}
		}

		unset(self::$connections[$current_connection_id]);

		if (array_key_exists($current_connection_id, self::$connection_objects)) {
			unset(self::$connection_objects[$current_connection_id]);
		}

		self::$current_connection_id = null;
	}

	/**
	 * Associate current connection with a user
	 *
	 * @param int|string $id User identifier
	 *
	 * @param string $token User token
	 *
	 * @param array<string, mixed> $data Extra user data
	 *
	 * @return void
	 */
	public static function setUserData(int|string $id, string $token, array $data = []): void {
		$current_connection_id = self::getCurrentConnectionId();

		if (is_null($current_connection_id)) {
			return;
		}

		if (!array_key_exists($current_connection_id, self::$connections)) {
			return;
		}

		if (array_key_exists('id', $data)) {
			unset($data['id']);
		}
		if (array_key_exists('token', $data)) {
			unset($data['token']);
		}

		$previous_user_id = self::$connections[$current_connection_id]['user_id'];

		if (!is_null($previous_user_id) && $previous_user_id !== $id && array_key_exists($previous_user_id, self::$users)) {
			$previous_user_connections = self::$users[$previous_user_id]['connections'];
			$previous_user_connections = array_values(
				array_filter(
					$previous_user_connections,
					fn (int|string $connection_id): bool => $connection_id !== $current_connection_id
				)
			);

			self::$users[$previous_user_id]['connections'] = $previous_user_connections;

			if (count(self::$users[$previous_user_id]['connections']) === 0) {
				unset(self::$users[$previous_user_id]);
			}
		}

		if (!array_key_exists($id, self::$users)) {
			self::$users[$id] = [
				'id' => $id,
				'token' => $token,
				'connections' => [],
				'data' => $data
			];
		}
		else {
			self::$users[$id]['token'] = $token;
			self::$users[$id]['data'] = array_merge(self::$users[$id]['data'], $data);
		}

		if (!in_array($current_connection_id, self::$users[$id]['connections'], true)) {
			self::$users[$id]['connections'][] = $current_connection_id;
		}

		self::$connections[$current_connection_id]['user_id'] = $id;
	}

	/**
	 * Get user data
	 *
	 * If id is null, current connection user data will be returned.
	 *
	 * @param int|string|null $id User identifier or null for current user
	 *
	 * @return ?array<string, mixed> User data or null if not found
	 */
	public static function getUserData(int|string|null $id = null): ?array {
		if (is_null($id)) {
			$current_connection_id = self::getCurrentConnectionId();

			if (is_null($current_connection_id)) {
				return null;
			}

			if (!array_key_exists($current_connection_id, self::$connections)) {
				return null;
			}

			$id = self::$connections[$current_connection_id]['user_id'];

			if (is_null($id)) {
				return null;
			}
		}

		if (!array_key_exists($id, self::$users)) {
			return null;
		}

		return array_merge(
			[
				'id' => self::$users[$id]['id'],
				'token' => self::$users[$id]['token']
			],
			self::$users[$id]['data']
		);
	}

	/**
	 * Clear user data and close user connections
	 *
	 * If id is null, current connection user will be cleared.
	 *
	 * @param int|string|null $id User identifier or null for current user
	 *
	 * @return void
	 */
	public static function clearUserData(int|string|null $id = null): void {
		if (is_null($id)) {
			$current_user_data = self::getUserData();

			if (is_null($current_user_data) || !array_key_exists('id', $current_user_data)) {
				return;
			}

			$id = $current_user_data['id'];
		}

		if (!array_key_exists($id, self::$users)) {
			return;
		}

		$user_connections = self::$users[$id]['connections'];

		foreach ($user_connections as $connection_id) {
			if (array_key_exists($connection_id, self::$connection_objects)) {
				self::$connection_objects[$connection_id]->close();
				unset(self::$connection_objects[$connection_id]);
			}

			if (array_key_exists($connection_id, self::$connections)) {
				self::$connections[$connection_id]['user_id'] = null;
				unset(self::$connections[$connection_id]);
			}
		}

		unset(self::$users[$id]);

		if (!is_null(self::$current_connection_id) && !array_key_exists(self::$current_connection_id, self::$connections)) {
			self::$current_connection_id = null;
		}
	}

	/**
	 * Check if current connection has user data
	 *
	 * @return bool Indicates if current connection has user data
	 */
	public static function hasUserData(): bool {
		$current_connection_id = self::getCurrentConnectionId();

		if (is_null($current_connection_id)) {
			return false;
		}

		if (!array_key_exists($current_connection_id, self::$connections)) {
			return false;
		}

		return !is_null(self::$connections[$current_connection_id]['user_id']);
	}

	/**
	 * Check if current connection is authenticated
	 *
	 * A connection is considered authenticated if it has user data,
	 * a token and the token validation method confirms it is valid.
	 *
	 * @return bool Indicates if current connection is authenticated
	 */
	public static function isAuthenticated(): bool {
		$user_data = self::getUserData();

		if (is_null($user_data)) {
			return false;
		}

		if (!array_key_exists('token', $user_data) || !is_string($user_data['token'])) {
			return false;
		}

		return !is_null(self::validateToken($user_data['token']));
	}

	/**
	 * Send a JSON message to current connection
	 *
	 * @param string $json JSON string to be sent
	 *
	 * @return void
	 */
	public static function send(string $json): void {
		$current_connection_id = self::getCurrentConnectionId();

		if (is_null($current_connection_id)) {
			return;
		}

		if (!self::isValidJson($json)) {
			return;
		}

		if (!array_key_exists($current_connection_id, self::$connection_objects)) {
			return;
		}

		self::$connection_objects[$current_connection_id]->send($json);
	}

	/**
	 * Send a JSON message to all connections of a user
	 *
	 * @param int|string $id User identifier
	 *
	 * @param string $json JSON string to be sent
	 *
	 * @return void
	 */
	public static function sendToUser(int|string $id, string $json): void {
		if (!self::isValidJson($json)) {
			return;
		}

		if (!array_key_exists($id, self::$users)) {
			return;
		}

		foreach (self::$users[$id]['connections'] as $connection_id) {
			if (array_key_exists($connection_id, self::$connection_objects)) {
				self::$connection_objects[$connection_id]->send($json);
			}
		}
	}

	/**
	 * Send a JSON message to all active connections
	 *
	 * @param string $json JSON string to be sent
	 *
	 * @return void
	 */
	public static function broadcast(string $json): void {
		if (!self::isValidJson($json)) {
			return;
		}

		foreach (self::$connection_objects as $connection) {
			$connection->send($json);
		}
	}

	/**
	 * Send a JSON message to all authenticated users
	 *
	 * @param string $json JSON string to be sent
	 *
	 * @return void
	 */
	public static function broadcastAuthenticated(string $json): void {
		if (!self::isValidJson($json)) {
			return;
		}

		foreach (self::$users as $user) {
			if ($user['token'] === '') {
				continue;
			}

			foreach ($user['connections'] as $connection_id) {
				if (array_key_exists($connection_id, self::$connection_objects)) {
					self::$connection_objects[$connection_id]->send($json);
				}
			}
		}
	}

	/**
	 * Check if a string contains valid JSON
	 *
	 * @param string $json String to be validated
	 *
	 * @return bool Indicates if the string is valid JSON
	 */
	public static function isValidJson(string $json): bool {
		json_decode($json);
		return json_last_error() === JSON_ERROR_NONE;
	}

	/**
	 * Validate a token using configured validation method
	 *
	 * @param string $token Token to be validated
	 *
	 * @return ?array<string, mixed> User data if token is valid, null otherwise
	 */
	public static function validateToken(string $token): ?array {
		if (is_null(self::$validate_method)) {
			return null;
		}

		$result = call_user_func(self::$validate_method, $token);

		if (is_null($result)) {
			return null;
		}

		if (!is_array($result)) {
			return null;
		}

		if (!array_key_exists('id', $result)) {
			return null;
		}

		return $result;
	}

	/**
	 * Process a websocket message
	 *
	 * @param string $message Received websocket message
	 *
	 * @return void
	 */
	public static function handleMessage(string $message): void {
		if (!self::isValidJson($message)) {
			self::send(self::getErrorJson(OWebsocketError::InvalidJson));
			return;
		}

		$message_data = json_decode($message, true);

		if (!is_array($message_data)) {
			self::send(self::getErrorJson(OWebsocketError::BadRequest));
			return;
		}

		if (!array_key_exists('action', $message_data) || !is_string($message_data['action'])) {
			self::send(self::getErrorJson(OWebsocketError::BadRequest));
			return;
		}

		if (!array_key_exists('data', $message_data) || !is_array($message_data['data']) || !self::isAssociativeArray($message_data['data'])) {
			self::send(self::getErrorJson(OWebsocketError::BadRequest));
			return;
		}

		$action = trim($message_data['action']);
		$data = $message_data['data'];

		if ($action === '') {
			self::send(self::getErrorJson(OWebsocketError::BadRequest));
			return;
		}

		$action_definition = OWebsocketAction::get($action);

		if (is_null($action_definition)) {
			self::send(self::getErrorJson(OWebsocketError::UnknownAction));
			return;
		}

		if ($action_definition['protected']) {
			if (!self::isAuthenticated()) {
				self::send(self::getErrorJson(OWebsocketError::Unauthorized));
				return;
			}

			$current_user_data = self::getUserData();

			if (
				is_null($current_user_data) ||
				!array_key_exists('token', $current_user_data) ||
				!is_string($current_user_data['token'])
			) {
				self::send(self::getErrorJson(OWebsocketError::Unauthorized));
				return;
			}

			$validated_user_data = self::validateToken($current_user_data['token']);

			if (is_null($validated_user_data)) {
				self::send(self::getErrorJson(OWebsocketError::Unauthorized));
				return;
			}

			$validated_user_id = $validated_user_data['id'];
			unset($validated_user_data['id']);

			self::setUserData($validated_user_id, $current_user_data['token'], $validated_user_data);
		}

		try {
			$request = self::createRequest($data);
			$response = self::executeComponent($action_definition['component_class'], $request);
		}
		catch (Throwable) {
			self::send(self::getErrorJson(OWebsocketError::ServerError));
			return;
		}

		if (!self::isValidJson($response)) {
			self::send(self::getErrorJson(OWebsocketError::InvalidResponse));
			return;
		}

		self::send($response);
	}

	/**
	 * Create a request object from websocket data
	 *
	 * @param array<string, mixed> $data Websocket message data
	 *
	 * @return ORequest Request object
	 */
	public static function createRequest(array $data): ORequest {
		return new ORequest(
			[
				'method' => 'WS',
				'headers' => [],
				'params' => $data
			],
			[]
		);
	}

	/**
	 * Get debug information from current websocket state
	 *
	 * @return string JSON string with debug information
	 */
	public static function getDebugInfo(): string {
		$debug_connections = [];

		foreach (self::$connections as $connection_id => $connection_data) {
			$debug_connections[$connection_id] = [
				'id' => $connection_data['id'],
				'user_id' => $connection_data['user_id'],
				'is_open' => array_key_exists($connection_id, self::$connection_objects)
			];
		}

		$debug_info = [
			'current_connection_id' => self::$current_connection_id,
			'connections' => $debug_connections,
			'users' => self::$users,
			'actions' => OWebsocketAction::getAll()
		];

		return json_encode($debug_info, JSON_PRETTY_PRINT) ?: '{}';
	}

	/**
	 * Execute a component and return its response
	 *
	 * @param string $component_class Component class name
	 *
	 * @param ORequest $request Request object
	 *
	 * @throws RuntimeException If component does not exist or returns invalid data
	 *
	 * @return string Component response
	 */
	private static function executeComponent(string $component_class, ORequest $request): string {
		if (!class_exists($component_class)) {
			throw new RuntimeException(sprintf('Websocket component class not found: %s', $component_class));
		}

		$component = new $component_class();

		if (!method_exists($component, 'render')) {
			throw new RuntimeException(sprintf('Websocket component render method not found: %s', $component_class));
		}

		$response = $component->render($request);

		if (!is_string($response)) {
			throw new RuntimeException(sprintf('Websocket component response must be a string: %s', $component_class));
		}

		return $response;
	}

	/**
	 * Get plugin configuration
	 *
	 * @return array<string, mixed> Plugin configuration
	 */
	private static function getPluginConfig(): array {
		global $core;

		$config = $core->config->getPluginConfig('websocket');

		return is_array($config) ? $config : [];
	}

	/**
	 * Normalize websocket path
	 *
	 * @param string $path Websocket path
	 *
	 * @return string Normalized websocket path
	 */
	private static function normalizePath(string $path): string {
		$path = trim($path);

		if ($path === '') {
			return self::DEFAULT_PATH;
		}

		if ($path[0] !== '/') {
			$path = '/' . $path;
		}

		if (strlen($path) > 1) {
			$path = rtrim($path, '/');
		}

		return $path;
	}

	/**
	 * Check if an array is associative
	 *
	 * @param array<mixed> $data Array to be checked
	 *
	 * @return bool Indicates if the array is associative
	 */
	private static function isAssociativeArray(array $data): bool {
		if ($data === []) {
			return true;
		}

		return array_keys($data) !== range(0, count($data) - 1);
	}

	/**
	 * Generate a standard error JSON response
	 *
	 * @param OWebsocketError $error Error code
	 *
	 * @return string JSON error response
	 */
	private static function getErrorJson(OWebsocketError $error): string {
		return json_encode(
			[
				'status' => 'error',
				'error' => $error->value
			],
			JSON_UNESCAPED_UNICODE
		) ?: '{"status":"error","error":"server_error"}';
	}
}