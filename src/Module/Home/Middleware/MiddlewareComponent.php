<?php declare(strict_types=1);

namespace Osumi\OsumiFramework\App\Module\Home\Middleware;

use Osumi\OsumiFramework\Core\OComponent;
use Osumi\OsumiFramework\Web\ORequest;

class MiddlewareComponent extends OComponent {
	/**
	 * Test page for middlewares
	 *
	 * @param ORequest $req Request object with method, headers, parameters and middlewares used
	 * @return void
	 */
	public function run(ORequest $req):void {
		echo '<pre>';
		var_dump($req);
		echo '</pre>';
	}
}
