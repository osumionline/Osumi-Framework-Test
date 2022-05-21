<?php declare(strict_types=1);

namespace OsumiFramework\App\Module;

use OsumiFramework\OFW\Core\OModuleAction;
use OsumiFramework\OFW\Core\OAction;
use OsumiFramework\OFW\Web\ORequest;

#[OModuleAction(
	url: '/',
	services: 'user',
	css: 'start'
)]
class startAction extends OAction {
	/**
	 * Start page
	 *
	 * @param ORequest $req Request object with method, headers, parameters and filters used
	 * @return void
	 */
	public function run(ORequest $req):void {
		$users = $this->user_service->getUsers();

		$this->getTemplate()->add('date', $this->user_service->getLastUpdate());
		$this->getTemplate()->addComponent('users', 'home/users', ['users' => $users]);
	}
}