<?php declare(strict_types=1);
class OPostInstall {
	private ?OColors $colors = null;
	private ?OConfig $config = null;
	private array    $messages = [
		'es' => [
      'TITLE'            => "\nPOST INSTALL 8.0.0\n\n",
      'URL_CACHE_DELETE' => "  Archivo cache de URLs borrado: \"%s\"\n",
			'END_TITLE'        => "\nPOST INSTALL 8.0.0 finalizado.\n\n"
    ],
    'en' => [
      'TITLE'            => "\nPOST INSTALL 8.0.0\n\n",
      'URL_CACHE_DELETE' => "  URL cache file deleted: \"%s\"\n",
			'END_TITLE'        => "\nPOST INSTALL 8.0.0 finished.\n\n"
    ],
    'eu' => [
      'TITLE'            => "\nPOST INSTALL 8.0.0\n\n",
      'URL_CACHE_DELETE' => "  URLen cache-fitxategia ezabatu da: \"%s\"\n",
			'END_TITLE'        => "\nPOST INSTALL 8.0.0 bukatu du.\n\n"
    ]
  ];

  /**
	 * Store global configuration locally
	 */
	public function __construct() {
		global $core;
		$this->config = $core->config;
		$this->colors = new OColors();
	}

  /**
	 * Runs the v8.0.0 update post-installation tasks
	 *
	 * @return string
	 */
	public function run(): string {
    $ret = '';
    // Start
    $ret .= $this->messages[$this->config->getLang()]['TITLE'];

    // Update components to the new OComponent

    // Update the filters to the new naming convention
    $filters_path = $this->config->getDir('app_filter');
		if (file_exists($filters_path)) {
			if ($folder = opendir($filters_path)) {
				while (false !== ($file = readdir($folder))) {
					if ($file != '.' && $file != '..') {
            $name_data = explode('.', $file);
            $ext = array_pop($name_data);
            array_push('filter');
            array_push($ext);
						rename($filters_path.$file, $filters_path.implode('.', $name_data));
					}
				}
				closedir($model);
			}
		}

    // Update the layouts to the new naming convention

    // Update the models to the new naming convention, and remove $table_name

    // Update the modules to the new structure system

    // Update the services to the new naming convention

    // Update the tasks to the new naming convention

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
