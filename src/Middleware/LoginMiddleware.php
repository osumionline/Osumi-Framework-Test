<?php declare(strict_types=1);

namespace Osumi\OsumiFramework\App\Middleware;

//use Osumi\OsumiFramework\Plugins\OToken;

class LoginMiddleware {
	/**
	 * Security middleware for clients
	 *
	 * @param string $phase Current phase ('before'|'afterRender'|'afterResponse')
	 *
	 * @param array<string, mixed> $data Pipeline payload (route/headers/params/body...)
	 *
	 * @return array<string, mixed> Middleware result (context/stop/status_code/message...)
	 */
	public static function handle(string $phase, array $data): array {
		global $core;
		if ($phase !== 'before') {
			return [];
		}
		/*$headers = (array)($data['headers'] ?? []);
		$auth    = $headers['Authorization'] ?? null;

		if (!is_null($auth) && $auth !== '') {
			$tk = new OToken($core->config->getExtra('secret'));
			if ($tk->checkToken($auth)) {
				return [
					'context' => [
						'id' => intval($tk->getParam('id'))
					]
				];
			}
		}

		return [
			'stop' => true,
			'status_code' => 401,
			'message' => 'Unauthorized'
		];
		*/
		return [
			'context' => [
				'id' => 1
			]
		];
	}
}
