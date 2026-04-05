<?php

declare(strict_types=1);

namespace Osumi\OsumiFramework\Plugins\Websocket;

use InvalidArgumentException;

/**
 * OWebsocketAction - Static registry for websocket actions
 */
final class OWebsocketAction {
	/**
	 * @var array<string, array{action:string, component_class:string, protected:bool}>
	 */
	private static array $actions = [];

	/**
	 * Register a websocket action
	 *
	 * @param string $action Action name received in websocket messages
	 *
	 * @param string $component_class Component class to be executed
	 *
	 * @param bool $protected Indicates if the action requires authentication
	 *
	 * @throws InvalidArgumentException If action name or component class are empty, or action already exists
	 *
	 * @return void
	 */
	public static function register(string $action, string $component_class, bool $protected = false): void {
		$action = trim($action);
		$component_class = trim($component_class);

		if ($action === '') {
			throw new InvalidArgumentException('Websocket action name cannot be empty.');
		}

		if ($component_class === '') {
			throw new InvalidArgumentException('Websocket action component class cannot be empty.');
		}

		if (self::exists($action)) {
			throw new InvalidArgumentException(sprintf('Websocket action "%s" is already registered.', $action));
		}

		self::$actions[$action] = [
			'action' => $action,
			'component_class' => $component_class,
			'protected' => $protected
		];
	}

	/**
	 * Check if a websocket action exists
	 *
	 * @param string $action Action name to be checked
	 *
	 * @return bool Indicates if the action exists
	 */
	public static function exists(string $action): bool {
		return array_key_exists($action, self::$actions);
	}

	/**
	 * Get a websocket action definition
	 *
	 * @param string $action Action name to be retrieved
	 *
	 * @return ?array{action:string, component_class:string, protected:bool} Action definition or null if not found
	 */
	public static function get(string $action): ?array {
		return self::exists($action) ? self::$actions[$action] : null;
	}

	/**
	 * Get all registered websocket actions
	 *
	 * @return array<string, array{action:string, component_class:string, protected:bool}> List of registered actions
	 */
	public static function getAll(): array {
		return self::$actions;
	}

	/**
	 * Remove all registered websocket actions
	 *
	 * @return void
	 */
	public static function clear(): void {
		self::$actions = [];
	}
}