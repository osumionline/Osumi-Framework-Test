<?php declare(strict_types=1);
class home extends OModule {
	private ?userService  $user_service;
	private ?photoService $photo_service;

	function __construct() {
		$this->user_service  = new userService();
		$this->photo_service = new photoService();
	}

	/**
	 * Página de inicio
	 *
	 * @url /
	 * @param ORequest $req Request object with method, headers, parameters and filters used
	 * @return void
	 */
	public function start(ORequest $req): void {
		$users = $this->user_service->getUsers();

		$this->getTemplate()->add('date', $this->user_service->getLastUpdate());
		$this->getTemplate()->addComponent('users', 'home/users', ['users' => $users]);
	}

	/**
	 * Página de un usuario
	 *
	 * @url /user/:id
	 * @param ORequest $req Request object with method, headers, parameters and filters used
	 * @return void
	 */
	public function user(ORequest $req): void {
		$user = $this->user_service->getUser($req->getParamInt('id'));
		$list = $this->photo_service->getPhotos($user->get('id'));

		$this->getTemplate()->add('name', $user->get('user'));
		$this->getTemplate()->addComponent('photo_list', 'home/photo_list', ['list'=>$list]);
	}

	/**
	 * Página de pruebas para filtros
	 *
	 * @url /filter
	 * @filter testFilter
	 * @param ORequest $req Request object with method, headers, parameters and filters used
	 * @return void
	 */
	public function filter(ORequest $req): void {
		echo '<pre>';
		var_dump($req);
		echo '</pre>';
	}
}