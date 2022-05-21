<?php declare(strict_types=1);

namespace OsumiFramework\App\Module;

use OsumiFramework\OFW\Core\OModuleAction;
use OsumiFramework\OFW\Core\OAction;
use OsumiFramework\OFW\Web\ORequest;

#[OModuleAction(
	url: '/filter',
	filter: 'testFilter'
)]
class filterAction extends OAction {
	/**
	 * Test page for filters
	 *
	 * @param ORequest $req Request object with method, headers, parameters and filters used
	 * @return void
	 */
	public function run(ORequest $req):void {
		echo '<pre>';
		var_dump($req);
		echo '</pre>';
	}
}