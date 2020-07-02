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
	 * Generate documentation block for a module
	 *
	 * @param array $options List of options needed in the documentation block
	 *
	 * @return string Properly formatted documentation block
	 */
	private function generateModuleDoc(array $options): string {
		unset($options['module']);
		$str = "/**\n";
		foreach ($options as $key => $value) {
			$str .= " * @".$key." ".$value."\n";
		}
		$str .= "*/\n";
		return $str;
	}

	/**
	 * Generate documentation block for an action
	 *
	 * @param array $options List of options needed in the documentation block
	 *
	 * @return string Properly formatted documentation block
	 */
	private function generateActionDoc(array $options): string {
		$str = "/**\n";
		$str .= " * ".$options['comment']."\n";
		unset($options['comment']);
		$str .= " *\n";
		foreach ($options as $key => $value) {
			$str .= " * @".$key." ".$value."\n";
		}
		$str .= " * @param ORequest $"."req Request object with method, headers, parameters and filters used\n";
		$str .= " * @return void\n";
		$str .= " */\n";
		return $str;
	}

	private function processUrl(array $url): void {
$l = new OLog();
		$module_file = $this->config->getDir('app_module').$url['module'].'/'.$url['module'].'.php';
		$module_content = file_get_contents($module_file);
		$module_options = [];
$l->debug('URL: '.$url);
		foreach ($url as $option => $value) {
			if ($option!='id' && $option!='urls') {
				$module_options[$option] = $value;
			}
		}
$l->debug(var_export($module_options, true));
		if (count($module_options)>0) {
$l->debug("DOC MODULE");
			$docblock = $this->generateModuleDoc($module_options);
			$ind = stripos($module_content, 'class '.$url['module']);
$l->debug("IND: ".$ind);
			$module_content = substr($module_content, 0, $ind) . $docblock . substr($module_content, $ind);
		}

		foreach ($url['urls'] as $action_options) {
			$action = $action_options['action'];
$l->debug('ACTION: '.$action);
			unset($action_options['id']);
			unset($action_options['action']);
$l->debug(var_export($action_options, true));
			$docblock = $this->generateActionDoc($action_options);
			$ind = stripos($module_content, 'public function '.$action);
$l->debug('IND: '.$ind);
			$partial_content = substr($module_content, 0, $ind);
			$doc_ind = strripos($module_content, '/**');
$l->debug('DOC IND: '.$doc_ind);
			$module_content = substr($module_content, 0, $doc_ind) . $docblock . substr($module_content, $ind);
		}

		file_put_contents($module_file, $module_content);
	}

	/**
	 * Runs the v6.0.0 update post-installation tasks
	 *
	 * @return string
	 */
	public function run(): string {
		$ret = 'ok';

		// Move cache folder from app to ofw
		$source = $this->config->getDir('app').'cache';
		$destination = $this->config->getDir('base').'ofw/cache';
		rename($source, $destination);

		$urls_file = $this->config->getDir('app_config').'urls.json';
		$urls = json_decode( file_get_contents($urls_file), true);

		foreach ($urls['urls'] as $url) {
			$this->processUrl($url);
		}

		return $ret;
	}
}