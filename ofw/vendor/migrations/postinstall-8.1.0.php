<?php declare(strict_types=1);

namespace OsumiFramework\OFW\Migrations;

use OsumiFramework\OFW\Core\OConfig;
use OsumiFramework\OFW\Tools\OColors;

class OPostInstall {
	private ?OColors $colors = null;
	private ?OConfig $config = null;
	private array    $messages = [
		'es' => [
			'TITLE'               => "\nPOST INSTALL 8.1.0\n\n",
			'UPDATING_COMPONENTS' => "  Actualizando componentes...\n",
			'COMPONENTS_UPDATED'  => "  Componentes actualizados.\n",
			'UPDATING_MODULES'    => "  Actualizando módulos...\n",
			'MODULES_UPDATED'     => "  Módulos actualizados.\n",
      'URL_CACHE_DELETE'    => "  Archivo cache de URLs borrado: \"%s\"\n",
			'END_TITLE'           => "\nPOST INSTALL 8.1.0 finalizado.\n\n"
		],
		'en' => [
			'TITLE'               => "\nPOST INSTALL 8.1.0\n\n",
			'UPDATING_COMPONENTS' => "  Updating components...\n",
			'COMPONENTS_UPDATED'  => "  Components updated.\n",
			'UPDATING_MODULES'    => "  Updating modules...\n",
			'MODULES_UPDATED'     => "  Modules updated.\n",
      'URL_CACHE_DELETE'    => "  URL cache file deleted: \"%s\"\n",
			'END_TITLE'           => "\nPOST INSTALL 8.1.0 finished.\n\n"
		],
		'eu' => [
			'TITLE'               => "\nPOST INSTALL 8.1.0\n\n",
			'UPDATING_COMPONENTS' => "  Eguneratzen konponenteak...\n",
			'COMPONENTS_UPDATED'  => "  Konponenteak eguneratu dira.\n",
			'UPDATING_MODULES'    => "  Eguneratzen moduluak...\n",
			'MODULES_UPDATED'     => "  Moduluak eguneratu dira.\n",
      'URL_CACHE_DELETE'    => "  URLen cache-fitxategia ezabatu da: \"%s\"\n",
			'END_TITLE'           => "\nPOST INSTALL 8.1.0 bukatu du.\n\n"
		]
	];

	private array $replaces = [];

	/**
	 * Store global configuration locally
	 */
	public function __construct() {
		global $core;
		$this->config = $core->config;
		$this->colors = new OColors();
	}

	/**
	 * Update components and save update history to update modules after this job
	 *
	 * @param string $path Path from where to look upon searching for components
	 */
	private function updateComponents(string $path): void {
		if ($folder = opendir($path)) {
			// Recorrer path
			while (false !== ($file = readdir($folder))) {
				if ($file != '.' && $file != '..') {
					$check_path = $path.$file.'/'.$file.'.component.php'; // ejemplo: /var/www/vhosts/osumi.es/dev.osumi.es/app/component/home/photo_list/photo_list.php
					// Si existe file/file.component.php es un componente
					if (file_exists($check_path)) {
						$partial_path = str_ireplace($this->config->getDir('app_component'), '', $check_path); // Quito el principio, ejemplo: home/photo_list/photo_list.php
						$partial_path = str_ireplace($file.'/'.$file.'.component.php', '', $partial_path); // Quito el final, ejemplo: home
						// Exploto por / y a cada pieza underscoresToCamelCase, vuelvo a juntar piezas con \
						$partial_path_parts = explode('/', $partial_path);
						for ($i = 0; $i < count($partial_path_parts); $i++) {
							$partial_path_parts[$i] = OTools::underscoresToCamelCase($partial_path_parts[$i], true);
						}
						$partial_path = implode('\\', $partial_path_parts);
						// Leo archivo ruta_completa
						$component_content = file_get_contents($check_path);
						$component_content = str_ireplace(
							'namespace OsumiFramework\\App\\Component;',
							'namespace OsumiFramework\\App\\Component\\' . $partial_path . ';',
							$component_content
						);
						file_put_contents($check_path, $component_content);
						// Obtengo nombre de la clase
						preg_match("/^class (.*?) extends OComponent/m", $component_content, $name_match);
						// Guardo reemplazo
						$this->replaces['use OsumiFramework\\App\\Component\\'.$name_match[1].';'] = 'use OsumiFramework\\App\\Component\\'.$partial_path.'\\'.$name_match[1].';';
					}
					else {
						$this->updateComponents($path . '/' . $file . '/');
					}
				}
			}
		}
	}

	/**
	 * Function to update all modules: update components "use" lines and remove "components" section from OModuleAction declaration
	 *
	 * @return void
	 */
	private function updateModules(): void {
		if ($folder = opendir($this->config->getDir('app_module'))) {
			// Recorrer módulos
			while (false !== ($module = readdir($folder))) {
				if ($module != '.' && $module != '..') {
					$actions_path = $this->config->getDir('app_module').$module.'/actions/';
					if ($actions_folder = opendir($actions_path)) {
						// Recorrer acciones
						while (false !== ($action = readdir($actions_folder))) {
							if ($action != '.' && $action != '..') {
								$action_path = $actions_path.$action.'/'.$action.'.action.php';
								$action_content = file_get_contents($action_path);
								$result = preg_match("/,\n\scomponents: \[(.*?)]/m", $action_content, $component_match);

								if ($result === 1) {
									foreach ($this->replaces as $old => $new) {
										$action_content = str_ireplace($old, $new, $action_content);
									}
									$action_content = str_ireplace(",\n\scomponents: [".$component_match[1]."]", "", $action_content);
									file_put_contents($action_path, $action_content);
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Runs the v8.1.0 update post-installation tasks
	 *
	 * @return string
	 */
	public function run(): string {
		$ret = '';
		// Start
		$ret .= $this->messages[$this->config->getLang()]['TITLE'];

		// Update components
		$ret .= $this->messages[$this->config->getLang()]['UPDATING_COMPONENTS'];

		$this->updateComponents($this->config->getDir('app_component'));

		$ret .= $this->messages[$this->config->getLang()]['COMPONENTS_UPDATED'];

		// Update modules

		$ret .= $this->messages[$this->config->getLang()]['UPDATING_MODULES'];

		$this->updateModules();

		$ret .= $this->messages[$this->config->getLang()]['MODULES_UPDATED'];

    // Delete the URL cache file
		$url_cache_file = $this->config->getDir('ofw_cache').'urls.cache.json';
		if (file_exists($url_cache_file)) {
			unlink($url_cache_file);
			$ret .= sprintf($this->messages[$this->config->getLang()]['URL_CACHE_DELETE'],
				$this->colors->getColoredString($url_cache_file, 'light_green')
			);
		}

		// End
		$ret .= $this->messages[$this->config->getLang()]['END_TITLE'];

		return $ret;
	}
}
/*
1814.66   80.80
x         100

2245.86 x 12 = 26950.39

1674

16.1 € / hora

30000 / 1800 = 16.66
32000 / 1800 = 17.77

16.1    100
16.66   103.47

16.1    100
17.77   110.37



35000 / 1800 = 19.44

16.1    100
19.44   120.74

jon.m@educaedu.com
*/
