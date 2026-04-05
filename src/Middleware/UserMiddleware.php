<?php declare(strict_types=1);

namespace Osumi\OsumiFramework\App\Middleware;

//use Osumi\OsumiFramework\Plugins\OToken;

class UserMiddleware {
	/**
	 * Gets user from the token
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
						'name' => intval($tk->getParam('name'))
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
				'name' => 'User name'
			]
		];
	}
}
