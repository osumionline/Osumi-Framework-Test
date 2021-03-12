<?php declare(strict_types=1);
class OPostInstall {
	private ?OColors $colors = null;
	private ?OConfig $config = null;
	private array    $messages = [
		'es' => [
			'TITLE'                    => "\nPOST INSTALL 7.0.0\n\n",
			'ADD_NAMESPACE_TO_MODEL'   => "  Archivo de modelo actualizado: \"%s\"\n",
			'ADD_NAMESPACE_TO_MODULE'  => "  Archivo de módulo actualizado: \"%s\"\n",
			'ADD_NAMESPACE_TO_SERVICE' => "  Archivo de servicio actualizado: \"%s\"\n",
			'ADD_NAMESPACE_TO_TASK'    => "  Archivo de tarea actualizado: \"%s\"\n",
			'CORE_FOLDER_DELETE'       => "  La carpeta \"%s\" no se puede eliminar automaticamente. Por favor, ejecuta el siguiente comando:\n\n",
			'POST_UPDATE'              => "  El archivo \"%s\" no se puede actualizar automaticamente. Por favor, ejecuta los siguientes comandos:\n\n",
			'UPDATE_URLS'              => "  Recuerda que ahora debes actualizar las URLs manualmente para usar el nuevo sistema de enrutamientos:\n\n",
			'END_TITLE'                => "\nPOST INSTALL 7.0.0 finalizado.\n\n"
		],
		'en' => [
			'TITLE'                    => "\n\nPOST INSTALL 7.0.0\n\n",
			'ADD_NAMESPACE_TO_MODEL'   => "  Model file updated: \"%s\"\n",
			'ADD_NAMESPACE_TO_MODULE'  => "  Module file updated: \"%s\"\n",
			'ADD_NAMESPACE_TO_SERVICE' => "  Service file updated: \"%s\"\n",
			'ADD_NAMESPACE_TO_TASK'    => "  Task file updated: \"%s\"\n",
			'CORE_FOLDER_DELETE'       => "  Folder \"%s\" cannot be automatically deleted. Please, run the following command:\n\n",
			'POST_UPDATE'              => "  File \"%s\" could not be updated automatically. Please, run the following commands:\n\n",
			'UPDATE_URLS'              => "  Remember that you have to update the URLs manually to use the new routing system:\n\n",
			'END_TITLE'                => "\nPOST INSTALL 7.0.0 finished.\n\n"
		]
	];
	private array $models_list = [];
	private array $services_list = [];

	/**
	 * Store global configuration locally
	 */
	public function __construct() {
		global $core;
		$this->config = $core->config;
		$this->colors = new OColors();
	}

	/**
	 * Add namespace support to model file
	 *
	 * @param string $model Name of the model file
	 *
	 * @return string Returns information messages
	 */
	private function addNamespaceToModel(string $model): string {
		$ret = sprintf($this->messages[$this->config->getLang()]['ADD_NAMESPACE_TO_MODEL'],
			$this->colors->getColoredString($model, 'light_green')
		);

		$model_path = $this->config->getDir('app_model').$model.'.php';
		$to_be_added = [
			"\nnamespace OsumiFramework\\App\\Model;\n",
			"use OsumiFramework\\OFW\\Core\\OModel;"
		];
		$model_content = $this->updateContent($model_path, $to_be_added);

		// Update OCore:: -> OModel::
		$model_content = str_ireplace("OCore::", "OModel::", $model_content);

		file_put_contents($model_path, $model_content);

		return $ret;
	}

	/**
	 * Add namespace support to service file
	 *
	 * @param string $service Name of the service file
	 *
	 * @return string Returns information messages
	 */
	private function addNamespaceToService(string $service): string {
		$ret = sprintf($this->messages[$this->config->getLang()]['ADD_NAMESPACE_TO_SERVICE'],
			$this->colors->getColoredString($service, 'light_green')
		);

		$service_path = $this->config->getDir('app_service').$service.'.php';
		$to_be_added = [
			"\nnamespace OsumiFramework\\App\\Service;\n",
			"use OsumiFramework\\OFW\\Core\\OService;"
		];
		$service_content = $this->updateContent($service_path, $to_be_added);
		file_put_contents($service_path, $service_content);

		return $ret;
	}

	/**
	 * Add namespace support to module file
	 *
	 * @param string $module Name of the module file
	 *
	 * @return string Returns information messages
	 */
	private function addNamespaceToModule(string $module): string {
		$ret = sprintf($this->messages[$this->config->getLang()]['ADD_NAMESPACE_TO_MODULE'],
			$this->colors->getColoredString($module, 'light_green')
		);

		$module_path = $this->config->getDir('app_module').$module.'/'.$module.'.php';
		$to_be_added = [
			"\nnamespace OsumiFramework\\App\\Module;\n",
			"use OsumiFramework\\OFW\\Core\\OModule;",
			"use OsumiFramework\\OFW\\Web\\ORequest;",
			"use OsumiFramework\\OFW\\Routing\\ORoute;"
		];
		$module_content = $this->updateContent($module_path, $to_be_added);
		file_put_contents($module_path, $module_content);

		return $ret;
	}

	/**
	 * Add namespace support to task file
	 *
	 * @param string $task Name of the task file
	 *
	 * @return string Returns information messages
	 */
	private function addNamespaceToTask(string $task): string {
		$ret = sprintf($this->messages[$this->config->getLang()]['ADD_NAMESPACE_TO_TASK'],
			$this->colors->getColoredString($task, 'light_green')
		);

		$task_path = $this->config->getDir('app_task').$task.'.php';
		$to_be_added = [
			"\nnamespace OsumiFramework\\App\\Task;\n",
			"use OsumiFramework\\OFW\\Core\\OTask;"
		];
		$task_content = $this->updateContent($task_path, $to_be_added);
		file_put_contents($task_path, $task_content);

		return $ret;
	}

	/**
	 * Add database support, both ODB and model classes
	 *
	 * @param string $content Content of the code file to be checked
	 *
	 * @return array New namespaces to be added
	 */
	private function addDB(string $content): array {
		$ret = [];

		// Check if ODB is used and add it if necessary
		if (stripos($content, "new ODB")!==false) {
			array_push($ret, "use OsumiFramework\\OFW\\DB\\ODB;");
		}
		// Check if model classes are used and add them if necessary
		foreach ($this->models_list as $model) {
			if (stripos($content, "new ".ucfirst($model))!==false) {
				array_push($ret, "use OsumiFramework\\App\Model\\".ucfirst($model).";");
			}
		}

		return $ret;
	}

	/**
	 * Add used service namespaces
	 *
	 * @param string $content Content of the code file to be checked
	 *
	 * @return array New namespaces to be added
	 */
	private function addServices(string $content): array {
		$ret = [];

		// Check if service classes are used and add them if necessary
		foreach ($this->services_list as $service) {
			if (stripos($content, "new ".$service."Service")!==false) {
				array_push($ret, "use OsumiFramework\\App\Service\\".$service."Service;");
			}
		}

		return $ret;
	}

	/**
	 * Builds a file with the given namespaces to be added
	 *
	 * @param string $path Path of the code file
	 *
	 * @param array $to_be_added List of namespaces to be added to the file
	 *
	 * @return string Content of the code file with the new namespaces added
	 */
	private function updateContent(string $path, array $to_be_added): string {
		$content = file_get_contents($path);
		$content = str_ireplace("\r\n", "\n", $content);
		$content = str_ireplace("<?php declare(strict_types=1);\n", "", $content);

		$db_content = $this->addDB($content);
		if (count($db_content) > 0) {
			$to_be_added = array_merge($to_be_added, $db_content);
		}
		$services_content = $this->addServices($content);
		if (count($services_content) > 0) {
			$to_be_added = array_merge($to_be_added, $services_content);
		}
		return "<?php declare(strict_types=1);\n" . implode("\n", $to_be_added) . "\n\n". $content;
	}

	/**
	 * Runs the v6.1.0 update post-installation tasks
	 *
	 * @return string
	 */
	public function run(): string {
		$ret = '';
		$ret .= $this->messages[$this->config->getLang()]['TITLE'];

		// Add namespaces

		// Models
		$models_path = $this->config->getDir('app_model');
		if (file_exists($models_path)) {
			if ($model = opendir($models_path)) {
				while (false !== ($entry = readdir($model))) {
					if ($entry != '.' && $entry != '..') {
						$entry = str_ireplace(".php", "", $entry);
						$ret .= $this->addNamespaceToModel($entry);
						array_push($this->models_list, $entry);
					}
				}
				closedir($model);
			}
		}

		// Services
		$services_path = $this->config->getDir('app_service');
		if (file_exists($services_path)) {
			if ($service = opendir($services_path)) {
				while (false !== ($entry = readdir($service))) {
					if ($entry != '.' && $entry != '..') {
						$entry = str_ireplace(".php", "", $entry);
						$ret .= $this->addNamespaceToService($entry);
						array_push($this->services_list, $entry);
					}
				}
				closedir($service);
			}
		}

		// Modules
		$modules_path = $this->config->getDir('app_module');
		if (file_exists($modules_path)) {
			if ($module = opendir($modules_path)) {
				while (false !== ($entry = readdir($module))) {
					if ($entry != '.' && $entry != '..') {
						$ret .= $this->addNamespaceToModule($entry);
					}
				}
				closedir($module);
			}
		}

		// Tasks
		$tasks_path = $this->config->getDir('app_task');
		if (file_exists($tasks_path)) {
			if ($task = opendir($tasks_path)) {
				while (false !== ($entry = readdir($task))) {
					if ($entry != '.' && $entry != '..') {
						$entry = str_ireplace(".php", "", $entry);
						$ret .= $this->addNamespaceToTask($entry);
					}
				}
				closedir($task);
			}
		}

		$ret .= sprintf($this->messages[$this->config->getLang()]['CORE_FOLDER_DELETE'],
			$this->colors->getColoredString("ofw/core", 'light_green')
		);

		$ret .= "    ".$this->colors->getColoredString("rmdir ofw/core", 'light_green')."\n\n";

		$ret .= sprintf($this->messages[$this->config->getLang()]['POST_UPDATE'],
			$this->colors->getColoredString("ofw/template/update/update.php", 'light_green')
		);

		$ret .= "    ".$this->colors->getColoredString("rm ofw/template/update/update.php", 'light_green')."\n";
		$ret .= "    ".$this->colors->getColoredString("mv ofw/template/update/update_7.php ofw/template/update/update.php", 'light_green')."\n\n";

		$ret .= $this->messages[$this->config->getLang()]['UPDATE_URLS'];

		$ret .= "  /**                                    #[ORoute(\n";
		$ret .= "   * @prefix /api             ->            prefix: 'api',\n";
		$ret .= "   * @type json                             type: 'json',\n";
		$ret .= "   */                                    )]\n\n";

		$ret .= "  /**                                    #[ORoute(\n";
		$ret .= "   * @url /getUser             ->           '/getUser',\n";
		$ret .= "   * @filter loginFilter                    filter: 'loginFilter',\n";
		$ret .= "   */                                    )]\n\n";

		$ret .= $this->messages[$this->config->getLang()]['END_TITLE'];

		return $ret;
	}
}