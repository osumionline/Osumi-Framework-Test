<?php
/**
 * Update Framework files to a newer version
 */
class updateTask {
	/**
	 * Returns description of the task
	 *
	 * @return Description of the task
	 */
	public function __toString() {
		return $this->colors->getColoredString("update", "light_green").": ".OTools::getMessage('TASK_UPDATE');
	}

	private $colors = null;

	/**
	 * Loads class used to colorize messages
	 *
	 * @return void
	 */
	function __construct() {
		$this->colors = new OColors();
	}

	private $repo_url = 'https://raw.githubusercontent.com/igorosabel/Osumi-Framework/';
	private $version_file = null;

	/**
	 * Get file of version updates
	 *
	 * @return array Available updates list array
	 */
	private function getVersionFile() {
		if (is_null($this->version_file)) {
			$this->version_file = json_decode( file_get_contents($this->repo_url.'master/ofw/core/version.json'), true );
		}
		return $this->version_file;
	}

	/**
	 * Get current version from the repository
	 *
	 * @return string Current version number (eg 5.0.0)
	 */
	private function getRepoVersion() {
		$version = $this->getVersionFile();
		return $version['version'];
	}

	/**
	 * Perform the update
	 *
	 * @return string Messages returned while performing the update
	 */
	function doUpdate($current_version) {
		global $core;
		$version = $this->getVersionFile();
		$updates = $version['updates'];

		$to_be_updated = [];
		foreach ($updates as $update_version => $update) {
			if (version_compare($current_version, $update_version)==-1) {
				array_push($to_be_updated, $update_version);
			}
		}
		asort($to_be_updated);
		echo "  ".$this->colors->getColoredString(OTools::getMessage('TASK_UPDATE_AVAILABLE', [count($to_be_updated)]), "light_green")."\n\n";

		foreach ($to_be_updated as $repo_version) {
			$backups = [];
			$result = true;
			echo "  ".$this->colors->getColoredString($updates[$repo_version]['message'], "black", "yellow")."\n";
			echo "==============================================================================================================\n";

			if (array_key_exists('deletes', $updates[$repo_version]) && count($updates[$repo_version]['deletes'])>0) {
				foreach ($updates[$repo_version]['deletes'] as $delete) {
					$local_delete = $core->config->getDir('base').$delete;
					if (file_exists($local_delete)) {
						echo OTools::getMessage('TASK_UPDATE_FILE_DELETE', [$delete]);
						$backup_file = $local_delete.'_backup';
						rename($local_delete, $backup_file);
						array_push($backups, ['new_file'=>$local_delete, 'backup'=>$backup_file]);
					}
				}
				echo "\n";
			}
			if (array_key_exists('files', $updates[$repo_version]) && count($updates[$repo_version]['files'])>0) {
				foreach ($updates[$repo_version]['files'] as $file) {
					$file_url = $this->repo_url.'v'.$repo_version.'/'.$file;
					echo OTools::getMessage('TASK_UPDATE_DOWNLOADING', [$file_url]);
					$file_content = file_get_contents($file_url);

					$local_file = $core->config->getDir('base').$file;
					if (file_exists($local_file)) {
						echo OTools::getMessage('TASK_UPDATE_FILE_EXISTS');
						$backup_file = $local_file.'_backup';
						rename($local_file, $backup_file);
						array_push($backups, ['new_file'=>$local_file, 'backup'=>$backup_file]);
					}
					else {
						echo OTools::getMessage('TASK_UPDATE_NEW_FILE');
					}

					$dir = dirname($local_file);
					if (!file_exists($dir)) {
						mkdir($dir, 0777, true);
					}

					$result_file = file_put_contents($local_file, $file_content);
					if ($result_file===false) {
						$result = false;
						break;
					}
				}
			}
			echo "==============================================================================================================\n";

			if ($result) {
				echo "\n  ".$this->colors->getColoredString(OTools::getMessage('TASK_UPDATE_ALL_UPDATED', [$repo_version]), "light_green")."\n";
				if (count($backups)>0) {
					echo OTools::getMessage('TASK_UPDATE_DELETE_BACKUPS');
					foreach ($backups as $backup) {
						unlink($backup['backup']);
					}
				}
			}
			else {
				echo "  ".$this->colors->getColoredString(OTools::getMessage('TASK_UPDATE_UPDATE_ERROR'), "white", "red")."\n";
				foreach ($backups as $backup) {
					if (file_exists($backup['new_file'])) {
						unlink($backup['new_file']);
					}
					rename($backup['backup'], $backup['new_file']);
				}
			}
			echo "\n";
		}
	}

	/**
	 * Run the task
	 *
	 * @return string Returns messages generated while performing the update
	 */
	public function run() {
		$current_version = trim( OTools::getVersion() );
		$repo_version = $this->getRepoVersion();

		echo "\n";
		echo "  ".$this->colors->getColoredString("Osumi Framework", "white", "blue")."\n\n";
		echo OTools::getMessage('TASK_UPDATE_INSTALLED_VERSION', [$current_version]);
		echo OTools::getMessage('TASK_UPDATE_CURRENT_VERSION', [$repo_version]);

		$compare = version_compare($current_version, $repo_version);

		switch ($compare) {
			case -1: {
				echo OTools::getMessage('TASK_UPDATE_UPDATING');
				$this->doUpdate($current_version);
			}
			break;
			case 0: {
				echo "  ".$this->colors->getColoredString(OTools::getMessage('TASK_UPDATE_UPDATED'), "light_green")."\n\n";
			}
			break;
			case 1: {
				echo "  ".$this->colors->getColoredString(OTools::getMessage('TASK_UPDATE_NEWER'), "white", "red")."\n\n";
			}
			break;
		}
	}
}