<?php declare(strict_types=1);
class OPostInstall {
	private ?OConfig $config = null;
	
	/**
	 * Store global configuration locally
	 */
	public function __construct() {
		global $core;
		$this->config = $core->config;
	}

	/**
	 * Runs the v6.0.0 update post-installation tasks
	 *
	 * @return string
	 */
	public function run(): string {
		// Move cache folder from app to ofw
		if (file_exists($this->config->getDir('app_service'))) {
			if ($model = opendir($this->config->getDir('app_service'))) {
				while (false !== ($entry = readdir($model))) {
					if ($entry != '.' && $entry != '..') {
						require $this->config->getDir('app_service').$entry;
					}
				}
				closedir($model);
			}
		}
	}
}