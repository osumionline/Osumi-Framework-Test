<?php declare(strict_types=1);

namespace OsumiFramework\OFW\Plugins;

use OsumiFramework\OFW\Core\OModule;
use OsumiFramework\OFW\Web\ORequest;
use OsumiFramework\OFW\Tools\OTools;

class OPanel extends OModule {
	private function getComponent(string $name, array $values=[]): string {
		$file = $this->getConfig()->getDir('ofw_plugins').'panel/component/'.$name.'.php';
		return OTools::getPartial($file, $values);
	}

	public function checkPass(ORequest $req): void {
		$status = 'ok';
		$file = $this->getConfig()->getDir('ofw_plugins').'panel/pass.json';
		if (!file_exists($file)) {
			$status = 'error';
		}
		$this->getTemplate()->add('status', $status);
	}

	public function setPass(ORequest $req): void {
		$status = 'ok';
		$pass   = $req->getParamString('pass');

		if (is_null($pass)) {
			$status = 'error';
		}

		if ($status=='ok') {
			$file = $this->getConfig()->getDir('ofw_plugins').'panel/pass.json';
			if (!file_exists($file)) {
				$obj = ['pass' => password_hash($pass, PASSWORD_BCRYPT)];
				file_put_contents($file, json_encode($obj));
			}
			else {
				$status = 'error';
			}
		}

		$this->getTemplate()->add('status', $status);
	}

	public function login(ORequest $req): void {
		$status = 'ok';
		$pass   = $req->getParamString('pass');
		$token  = '';

		if (is_null($pass)) {
			$status = 'error';
		}

		if ($status=='ok') {
			$file = $this->getConfig()->getDir('ofw_plugins').'panel/pass.json';
			if (file_exists($file)) {
				$obj = json_decode(file_get_contents($file), true);

				if ($obj != null) {
					if (password_verify($pass, $obj['pass'])) {
						$tk = new OToken($this->getConfig()->getExtra('secret'));
						$tk->addParam('login', 'ok');
						$token = $tk->getToken();
					}
					else {
						$status = 'error';
					}
				}
				else {
					$status = 'error';
				}
			}
			else {
				$status = 'error';
			}
		}

		$this->getTemplate()->add('status', $status);
		$this->getTemplate()->add('token',  $token);
	}

	public function getModules(ORequest $req): void {
		$status = 'ok';
		$list = [];

		$modules = OTools::getModuleUrls(true);
		foreach ($modules as $module) {
			if ($module['mode']=='module') {
				if (!array_key_exists($module['module'], $list)) {
					$list[$module['module']] = [
						'name' => $module['module'],
						'prefix' => $module['prefix'],
						'methods' => []
					];
				}

				$method = [
					'name' => $module['action'],
					'url' => $module['url'],
					'type' => $module['type'],
					'filter' => $module['filter'],
					'layout' => $module['layout']
				];

				array_push($list[$module['module']]['methods'], $method);
			}
		}
		$list = array_values($list);

		$this->getTemplate()->add('status', $status);
		$this->getTemplate()->add('list',  $this->getComponent('modules', $list), 'nourlencode');
	}
}