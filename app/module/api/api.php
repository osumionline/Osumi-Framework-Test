<?php declare(strict_types=1);
/**
 * Módulo API de prueba
 *
 * @type json
 * @prefix /api
 */
class api extends OModule {
	private ?userService $user_service;

	function __construct() {
		$this->user_service  = new userService();
	}

	/**
	 * Función para obtener la fecha
	 *
	 * @url /getDate
	 * @param ORequest $req Request object with method, headers, parameters and filters used
	 * @return void
	 */
	public function getDate(ORequest $req): void {
		$this->getTemplate()->add('date', $this->user_service->getLastUpdate());
	}
	
	/**
	 * Función para obtener la lista de usuarios
	 *
	 * @url /getUsers
	 * @param ORequest $req Request object with method, headers, parameters and filters used
	 * @return void
	 */
	public function getUsers(ORequest $req): void {
		$this->getTemplate()->addModelComponentList('list', $this->user_service->getUsers(), ['pass']);
	}
	
	/**
	 * Función para obtener los datos de un usuario
	 *
	 * @url /getUser/:id
	 * @param ORequest $req Request object with method, headers, parameters and filters used
	 * @return void
	 */
	public function getUser(ORequest $req): void {
		$status = 'ok';
		$user = $this->user_service->getUser($req->getParamInt('id'));

		if (is_null($user)) {
			$status = 'error';
		}

		$this->getTemplate()->add('status', $status);
		$this->getTemplate()->addModelComponent('user', $user, ['pass'], ['score']);
	}
}