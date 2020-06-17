<?php declare(strict_types=1);
class OPostInstall {
	private ?OConfig $config = null;
	private ?string  $controller_path = null;
	private ?string  $module_path = null;
	private ?string  $template_path = null;
	private ?array   $urls = null;
	private array    $messages = [
		'es' => [
					'TITLE'                             => "\n\nPOST INSTALL 5.8.0\n\n",
					'NEW_MODULE_FOLDER'                 => "  Nueva carpeta para módulos.\n",
					'DELETE_CONTROLLER_FOLDER'          => "  Carpeta controllers borrada.\n",
					'MODULES_UPDATING'                  => "  Actualizando módulos...\n",
					'CONTROLLER_UPDATED'                => "    Controller actualizado para que use OModule.\n",
					'NEW_MODULE_FOLDER'                 => "    Nueva carpeta para el módulo: \"%s\".\n",
					'NEW_MODULE_TEMPLATE_FOLDER'        => "    Nueva carpeta para templates en el módulo: \"%s\".\n",
					'MOVE_MODULE_CONTROLLER'            => "    Controller movido a carpeta de módulo: \"%s\" -> \"%s\".\n",
					'MOVE_TEMPLATE_FILE'                => "    Archivo de template movido a carpeta de módulo: \"%s\" -> \"%s\".\n",
					'DELETE_CONTROLLER_TEMPLATE_FOLDER' => "    Carpeta de template antigua borrada: \"%s\".\n\n",
					'END_TITLE'                         => "\n\nPOST INSTALL 5.8.0 finalizado.\n\n"
				],
		'en' => [
					'TITLE'                             => "\n\nPOST INSTALL 5.8.0\n\n",
					'NEW_MODULE_FOLDER'                 => "  New module folder.\n",
					'DELETE_CONTROLLER_FOLDER'          => "  Controllers folder deleted.\n",
					'MODULES_UPDATING'                  => "  Updating modules...\n",
					'CONTROLLER_UPDATED'                => "    Controller updated to use OModule.\n",
					'NEW_MODULE_FOLDER'                 => "    Nueva carpeta para el módulo: \"%s\".\n",
					'NEW_MODULE_TEMPLATE_FOLDER'        => "    Nueva carpeta para templates en el módulo: \"%s\".\n",
					'MOVE_MODULE_CONTROLLER'            => "    Controller movido a carpeta de módulo: \"%s\" -> \"%s\".\n",
					'MOVE_TEMPLATE_FILE'                => "    Archivo de template movido a carpeta de módulo: \"%s\" -> \"%s\".\n",
					'DELETE_CONTROLLER_TEMPLATE_FOLDER' => "    Carpeta de template antigua borrada: \"%s\".\n\n",
					'END_TITLE'                         => "\n\nPOST INSTALL 5.8.0 finished.\n\n"
				]
	];

	/**
	 * Store global configuration locally
	 */
	public function __construct() {
		global $core;
		$this->config = $core->config;
		$this->controller_path = $this->config->getDir('app').'controller';
		$this->module_path = $this->config->getDir('app').'module';
		$this->template_path = $this->config->getDir('app_template');

		$this->urls = json_decode( file_get_contents( $this->config->getDir('app_cache').'urls.cache.json' ), true );
	}

	private function getType(string $module, string $action): string {
		$type = 'html';

		foreach ($this->urls as $url) {
			if ($url['module']==$module && $url['action']==$action) {
				if (array_key_exists('type', $url)) {
					$type = $url['type'];
				}
				break;
			}
		}

		return $type;
	}

	/**
	 * Updates a controller and moves required files to new folders
	 *
	 * @param string $controller Name of the controller file to be updated
	 *
	 * @return string Information about the process
	 */
	private function updateController(string $controller): string {
		$ret = '';
		$path = $this->controller_path.'/'.$controller.'.php';

		// Actualizo para que use OModule en vez de OController
		$content = file_get_contents($path);
		$content = str_ireplace(' extends OController {', ' extends OModule {', $content);
		file_put_contents($path, $content);

		$ret .= sprintf($this->messages[$this->config->getLang()]['CONTROLLER_UPDATED'], $controller);

		// Creo carpeta app/module/controller
		$new_module_path = $this->module_path.'/'.$controller;
		$ret .= sprintf($this->messages[$this->config->getLang()]['NEW_MODULE_FOLDER'], $new_module_path);
		mkdir($new_module_path);

		// Creo carpeta app/module/controller/template
		$new_module_template_path = $new_module_path.'/template';
		$ret .= sprintf($this->messages[$this->config->getLang()]['NEW_MODULE_TEMPLATE_FOLDER'], $new_module_template_path);
		mkdir($new_module_template_path);

		// Muevo app/controller/controller.php a app/module/controller/controller.php
		$ret .= sprintf($this->messages[$this->config->getLang()]['MOVE_MODULE_CONTROLLER'], $path, $new_module_path.'/'.$controller.'.php');
		rename($path, $new_module_path.'/'.$controller.'.php');

		// Muevo cada archivo de template que tuviese en app/template
		$controller_template_path = $this->template_path.'/'.$controller;
		if (file_exists($controller_template_path)) {
			if ($model = opendir($controller_template_path)) {
				while (false !== ($entry = readdir($model))) {
					if ($entry != '.' && $entry != '..') {
						$action = str_ireplace('.php', '', $entry);
						$type = $this->getType($controller, $action);
						$ret .= sprintf($this->messages[$this->config->getLang()]['MOVE_TEMPLATE_FILE'], $controller_template_path.'/'.$entry, $new_module_template_path.'/'.$entry);
						rename($controller_template_path.'/'.$entry, $new_module_template_path.'/'.$action.'.'.$type);
					}
				}
				closedir($model);
			}
			$ret .= sprintf($this->messages[$this->config->getLang()]['DELETE_CONTROLLER_TEMPLATE_FOLDER'], $controller_template_path);
			rmdir($controller_template_path);
		}

		return $ret;
	}

	/**
	 * Runs the v5.8.0 update post-installation tasks
	 *
	 * @return string
	 */
	public function run(): void {
		$ret = '';
		$ret .= $this->messages[$this->config->getLang()]['TITLE'];

		$ret .= $this->messages[$this->config->getLang()]['NEW_MODULE_FOLDER'];
		mkdir($this->module_path);

		$ret .= $this->messages[$this->config->getLang()]['MODULES_UPDATING'];
		if (file_exists($this->controller_path)) {
			if ($model = opendir($this->controller_path)) {
				while (false !== ($entry = readdir($model))) {
					if ($entry != '.' && $entry != '..') {
						$controller = str_ireplace('.php', '', $entry);
						$ret .= $this->updateController($controller);
					}
				}
				closedir($model);
			}
		}

		$ret .= $this->messages[$this->config->getLang()]['DELETE_CONTROLLER_FOLDER'];
		rmdir($this->controller_path);

		$ret .= $this->messages[$this->config->getLang()]['END_TITLE'];

		return $ret;
	}
}