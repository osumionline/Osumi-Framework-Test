<?php declare(strict_types=1);

namespace OsumiFramework\App\Module;

use OsumiFramework\OFW\Core\OModuleAction;
use OsumiFramework\OFW\Core\OAction;
use OsumiFramework\OFW\Web\ORequest;

#[OModuleAction(
	url: '/getDate',
	services: 'user'
)]
class getDateAction extends OAction {
	/**
	 * Function used to obtain current date
	 *
	 * @param ORequest $req Request object with method, headers, parameters and filters used
	 * @return void
	 */
	public function run(ORequest $req):void {
		$this->getTemplate()->add('date', $this->user_service->getLastUpdate());
	}
}