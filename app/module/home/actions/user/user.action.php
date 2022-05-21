<?php declare(strict_types=1);

namespace OsumiFramework\App\Module;

use OsumiFramework\OFW\Core\OModuleAction;
use OsumiFramework\OFW\Core\OAction;
use OsumiFramework\OFW\Web\ORequest;
use OsumiFramework\App\DTO\UserDTO;

#[OModuleAction(
	url: '/user/:id',
	services: 'user, photo'
)]
class userAction extends OAction {
	/**
	 * User's page
	 *
	 * @param UserDTO $req Data Transfer Object with "isValid" method and methods for this functions parameters
	 * @return void
	 */
	public function run(UserDTO $req):void {
		if (!$req->isValid()) {
			echo "ERROR!";
			exit;
		}
		$id_user = $req->getIdUser();
		$user = $this->user_service->getUser($id_user);
		$list = $this->photo_service->getPhotos($user->get('id'));

		$this->getTemplate()->add('name', $user->get('user'));
		$this->getTemplate()->addComponent('photo_list', 'home/photo_list', ['list'=>$list]);
	}
}