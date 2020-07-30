<?php declare(strict_types=1);
class OPostInstall {
	private ?OColors $colors = null;
	private ?OConfig $config = null;
	private array    $messages = [
		'es' => [
					'TITLE'                  => "\nPOST INSTALL 6.1.0\n\n",
					'MOVE_LAYOUT_FOLDER'     => "  Carpeta layout reubicada: \"%s\" -> \"%s\".\n",
					'NEW_COMPONENT_FOLDER'   => "  Nueva carpeta para componentes: \"%s\".\n",
					'MOVE_PARTIAL_FOLDER'    => "  Nueva carpeta de componentes: \"%s\".\n",
					'MOVE_PARTIAL'           => "    Partial movido a componente: \"%s\" -> \"%s\".\n",
					'DELETE_PARTIAL_FOLDER'  => "    Carpeta de partials borrada: \"%s\".\n",
					'DELETE_PARTIALS_FOLDER' => "  Carpeta partials borrada: \"%s\".\n",
					'DELETE_TEMPLATE_FOLDER' => "  Carpeta template borrada: \"%s\".\n",
					'END_TITLE'              => "\nPOST INSTALL 6.1.0 finalizado.\n\n"
				],
		'en' => [
					'TITLE'                  => "\n\nPOST INSTALL 6.1.0\n\n",
					'MOVE_LAYOUT_FOLDER'     => "  Layout folder moved: \"%s\" -> \"%s\".\n",
					'NEW_COMPONENT_FOLDER'   => "  New component folder: \"%s\".\n",
					'MOVE_PARTIAL_FOLDER'    => "  New component folder: \"%s\".\n",
					'MOVE_PARTIAL'           => "    Partial moved to component: \"%s\" -> \"%s\".\n",
					'DELETE_PARTIAL_FOLDER'  => "    Partials folder deleted: \"%s\".\n",
					'DELETE_PARTIALS_FOLDER' => "  Partials folder deleted: \"%s\".\n",
					'DELETE_TEMPLATE_FOLDER' => "  Template folder deleted: \"%s\".\n",
					'END_TITLE' => "\nPOST INSTALL 6.1.0 finished.\n\n"
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
	 * Move al partials in a folder to the new component structure
	 *
	 * @param string $partial_folder
	 *
	 * @return string Returns information messages
	 */
	private function movePartialFolder(string $partial_folder): string {
		$ret = sprintf($this->messages[$this->config->getLang()]['MOVE_PARTIAL_FOLDER'],
			$this->colors->getColoredString($partial_folder, 'light_green')
		);
		$component_path = $this->config->getDir('app').'component/'.$partial_folder;
		mkdir($component_path);

		$partial_path = $this->config->getDir('app').'template/partials/'.$partial_folder;
		if ($model = opendir($partial_path)) {
			while (false !== ($entry = readdir($model))) {
				if ($entry != '.' && $entry != '..') {
					$name = str_ireplace('.php', '', $entry);
					$one_component_path = $component_path.'/'.$name;
					mkdir($one_component_path);
					rename($partial_path.'/'.$entry, $one_component_path.'/'.$entry);

					$ret .= sprintf($this->messages[$this->config->getLang()]['MOVE_PARTIAL'],
						$this->colors->getColoredString($partial_path.'/'.$entry, 'light_green'),
						$this->colors->getColoredString($one_component_path.'/'.$entry, 'light_green')
					);
				}
			}
			closedir($model);
		}

		$ret = sprintf($this->messages[$this->config->getLang()]['DELETE_PARTIAL_FOLDER'],
			$this->colors->getColoredString($partial_path, 'light_green')
		);
		rmdir($partial_path);

		return $ret;
	}

	/**
	 * Runs the v6.1.0 update post-installation tasks
	 *
	 * @return string
	 */
	public function run(): string {
		$ret = '';
		$ret .= $this->messages[$this->config->getLang()]['TITLE'];

		// Move cache folder from app to ofw
		$source = $this->config->getDir('app').'template/layout';
		$destination = $this->config->getDir('app').'layout';
		rename($source, $destination);
		$ret .= sprintf($this->messages[$this->config->getLang()]['MOVE_LAYOUT_FOLDER'],
			$this->colors->getColoredString($source, 'light_green'),
			$this->colors->getColoredString($destination, 'light_green')
		);

		// Create new component folder
		$component_path = $this->config->getDir('app').'component';
		mkdir($component_path);
		$ret .= sprintf($this->messages[$this->config->getLang()]['NEW_COMPONENT_FOLDER'],
			$this->colors->getColoredString($component_path, 'light_green')
		);

		// Move partials to components
		$partial_path = $this->config->getDir('app').'template/partials/';
		if (file_exists($partial_path)) {
			if ($model = opendir($partial_path)) {
				while (false !== ($entry = readdir($model))) {
					if ($entry != '.' && $entry != '..') {
						$ret .= $this->movePartialFolder($entry);
					}
				}
				closedir($model);
			}
		}

		// Delete partials folder
		rmdir($partial_path);
		$ret .= sprintf($this->messages[$this->config->getLang()]['DELETE_PARTIALS_FOLDER'],
			$this->colors->getColoredString($partial_path, 'light_green')
		);

		// Delete template folder
		$template_path = $this->config->getDir('app').'template';
		rmdir($template_path);
		$ret .= sprintf($this->messages[$this->config->getLang()]['DELETE_TEMPLATE_FOLDER'],
			$this->colors->getColoredString($template_path, 'light_green')
		);

		$ret .= $this->messages[$this->config->getLang()]['END_TITLE'];

		return $ret;
	}
}