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
			'ADD_NAMESPACE_TO_FILTER'  => "  Archivo de filtro actualizado: \"%s\"\n",
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
			'ADD_NAMESPACE_TO_FILTER'  => "  Filter file updated: \"%s\"\n",
			'CORE_FOLDER_DELETE'       => "  Folder \"%s\" cannot be automatically deleted. Please, run the following command:\n\n",
			'POST_UPDATE'              => "  File \"%s\" could not be updated automatically. Please, run the following commands:\n\n",
			'UPDATE_URLS'              => "  Remember that you have to update the URLs manually to use the new routing system:\n\n",
			'END_TITLE'                => "\nPOST INSTALL 7.0.0 finished.\n\n"
		]
	];
	private array $models_list = [];
	private array $services_list = [];
	private array $plugins_list = [];

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
			"use OsumiFramework\\OFW\\DB\\OModel;"
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
	 * Add namespace support to filter file
	 *
	 * @param string $filter Name of the filter file
	 *
	 * @return string Returns information messages
	 */
	private function addNamespaceToFilter(string $filter): string {
		$ret = sprintf($this->messages[$this->config->getLang()]['ADD_NAMESPACE_TO_FILTER'],
			$this->colors->getColoredString($filter, 'light_green')
		);

		$filter_path = $this->config->getDir('app_filter').$filter.'.php';
		$to_be_added = [
			"\nnamespace OsumiFramework\\App\\Filter;\n"
		];
		$filter_content = $this->updateContent($filter_path, $to_be_added);
		file_put_contents($filter_path, $filter_content);

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
				array_push($ret, "use OsumiFramework\\App\\Model\\".ucfirst($model).";");
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
				array_push($ret, "use OsumiFramework\\App\\Service\\".$service."Service;");
			}
		}

		return $ret;
	}

	/**
	 * Add used plugin namespaces
	 *
	 * @param string $content Content of the code file to be checked
	 *
	 * @return array New namespaces to be added
	 */
	private function addPlugins(string $content): array {
		$ret = [];

		// Check if plugin classes are used and add them if necessary
		foreach ($this->plugins_list as $plugin) {
			if (stripos($content, "new ".$plugin)!==false) {
				array_push($ret, "use OsumiFramework\\OFW\\Plugins\\".$plugin.";");
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
		$plugins_content = $this->addPlugins($content);
		if (count($plugins_content) > 0) {
			$to_be_added = array_merge($to_be_added, $plugins_content);
		}
		return "<?php declare(strict_types=1);\n" . implode("\n", $to_be_added) . "\n\n". $content;
	}

	/**
	 * Get modules PHPDoc block
	 *
	 * @param string $module Module name
	 *
	 * @return string Modules PHPDoc block, if any
	 */
	public static function getModuleDocumentation(string $module): ?string {
		$class = new ReflectionClass($module);
		$class_doc = $class->getDocComment();
		if ($class_doc !== false) {
			return $class_doc;
		}

		return null;
	}

	/**
	 * Get module methods phpDoc information
	 *
	 * @param string $inspectclass Module name
	 *
	 * @return array List of items with module name, method name and associated phpDoc information
	 */
	public static function getDocumentation(string $inspectclass): array {
		$class = new ReflectionClass($inspectclass);

		$class_params = [
			'module' => $inspectclass,
			'action' => null,
			'type'   => 'html',
			'prefix' => null,
			'filter' => null,
			'doc'    => null
		];
		$class_doc = $this->getModuleDocumentation($inspectclass);
		if (!is_null($class_doc)) {
			$class_params['doc'] = $class_doc;
			$class_params = $this->parseAnnotations($class_params);
		}

		$methods = [];
		foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
			if ($method->class == $class->getName() && $method->name != '__construct') {
				 array_push($methods, $method->name);
			}
		}

		$arr = [];
		foreach($methods as $method) {
			$ref = new ReflectionMethod($inspectclass, $method);
			array_push($arr, $this->parseAnnotations([
				'module' => $class_params['module'],
				'action' => $method,
				'type'   => $class_params['type'],
				'prefix' => $class_params['prefix'],
				'filter' => $class_params['filter'],
				'doc' => $ref->getDocComment()
			]));
		}
		return $arr;
	}

	/**
	 * Get OFW annotations from a method's phpDoc information block
	 *
	 * @param array $item getDocumentation return element with name of the module, name of the method and associated phpDoc information
	 *
	 * @return array Received method information and new information gathered from the phpDoc block
	 */
	function parseAnnotations(array $item): array {
		$docs = explode("\n", $item['doc']);
		$info = [
			'module'  => $item['module'],
			'action'  => $item['action'],
			'type'    => $item['type'],
			'prefix'  => $item['prefix'],
			'filter'  => $item['filter'],
			'comment' => null,
			'url'     => null,
			'doc'     => $item['doc']
		];
		foreach ($docs as $line) {
			$line = trim($line);
			if ($line!='/**' && $line!='*' && $line!='*/') {
				if (substr($line, 0, 2)=='* ') {
					$line = substr($line, 2);
				}
				if (substr($line, 0, 1)!='@') {
					$info['comment'] = $line;
				}
				else {
					$words = explode(' ', $line);
					$command = substr(array_shift($words), 1);
					$command_list = ['url', 'type', 'prefix', 'filter'];
					if (in_array($command, $command_list)) {
						$info[$command] = implode(' ', $words);
					}
				}
			}
		}

		return $info;
	}

	/**
	 * Runs the v6.1.0 update post-installation tasks
	 *
	 * @return string
	 */
	public function run(): string {
		$ret = '';
		$ret .= $this->messages[$this->config->getLang()]['TITLE'];

		// Find installed plugins
		foreach ($this->config->getPlugins() as $p) {
			$plugin = new OPlugin($p);
			$plugin->loadConfig();
			$plugin_name = str_ireplace(".php", "", $plugin->getFileName());
			array_push($this->plugins_list, $plugin_name);
		}

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

		// Filters
		$filters_path = $this->config->getDir('app_filter');
		if (file_exists($filters_path)) {
			if ($filter = opendir($filters_path)) {
				while (false !== ($entry = readdir($filter))) {
					if ($entry != '.' && $entry != '..') {
						$entry = str_ireplace(".php", "", $entry);
						$ret .= $this->addNamespaceToFilter($entry);
					}
				}
				closedir($filter);
			}
		}

		// Modules
		$modules_path = $this->config->getDir('app_module');
		if (file_exists($modules_path)) {
			if ($module = opendir($modules_path)) {
				while (false !== ($entry = readdir($module))) {
					if ($entry != '.' && $entry != '..') {
						require_once $this->config->getDir('app_module').$entry.'/'.$entry.'.php';
						$ret .= $this->addNamespaceToModule($entry);
						var_dump($this->getDocumentation($entry));
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
		$ret .= "   * @type json                             type: 'json'\n";
		$ret .= "   */                                    )]\n\n";

		$ret .= "  /**                                    #[ORoute(\n";
		$ret .= "   * @url /getUser             ->           '/getUser',\n";
		$ret .= "   * @filter loginFilter                    filter: 'loginFilter'\n";
		$ret .= "   */                                    )]\n\n";

		$ret .= $this->messages[$this->config->getLang()]['END_TITLE'];

		return $ret;
	}
}