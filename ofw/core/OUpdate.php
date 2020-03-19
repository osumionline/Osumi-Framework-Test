<?php
/**
 * Utilities for the update process
 */
class OUpdate {
	private $colors          = null;
	private $base_dir        = null;
	//private $repo_url        = 'https://raw.githubusercontent.com/igorosabel/Osumi-Framework/master/';
	private $repo_url        = 'https://osumi.es/';
	private $version_file    = null;
	private $current_version = null;
	private $repo_version    = null;
	private $version_check   = null;
	private $new_updates     = [];

	/**
	 * Loads on start up current version and repo version and checks both
	 *
	 * @return void
	 */
	function __construct() {
		global $core;
		$this->colors          = new OColors();
		$this->base_dir        = $core->config->getDir('base');
		$this->current_version = trim( OTools::getVersion() );
		$this->repo_version    = $this->getVersion();
		$this->version_check   = version_compare($this->current_version, $this->repo_version);
	}

	/**
	 * Get currently installed version
	 *
	 * @return string Current version number
	 */
	public function getCurrentVersion() {
		return $this->current_version;
	}

	/**
	 * Get repository version
	 *
	 * @return string Repository version number
	 */
	public function getRepoVersion() {
		return $this->repo_version;
	}

	/**
	 * Get version check
	 *
	 * @return integer Get version check (-1 to be updated 0 current 1 newer)
	 */
	public function getVersionCheck() {
		return $this->version_check;
	}

	/**
	 * Get file of version updates
	 *
	 * @return array Available updates list array
	 */
	private function getVersionFile() {
		if (is_null($this->version_file)) {
			$this->version_file = json_decode( file_get_contents($this->repo_url.'ofw/base/version.json'), true );
		}
		return $this->version_file;
	}

	/**
	 * Get current version from the repository
	 *
	 * @return string Current version number (eg 5.0.0)
	 */
	private function getVersion() {
		$version = $this->getVersionFile();
		return $version['version'];
	}

	/**
	 * Perform the update check
	 *
	 * @return array Array of "to be updated" versions. Includes version number, message and array of files with their status
	 */
	public function doUpdateCheck() {
		$version = $this->getVersionFile();
		$updates = $version['updates'];

		$to_be_updated = [];
		foreach ($updates as $update_version => $update) {
			if (version_compare($this->current_version, $update_version)==-1) {
				$to_be_updated[$update_version] = [
					'message' => $update['message'],
					'postinstall' => (array_key_exists('postinstall', $update) && $update['postinstall']===true),
					'files' => []
				];
			}
		}
		asort($to_be_updated);

		foreach (array_keys($to_be_updated) as $version) {
			if (array_key_exists('deletes', $updates[$version])) {
				foreach ($updates[$version]['deletes'] as $delete) {
					$local_delete = $this->base_dir.$delete;
					$status = 2; // delete
					if (!file_exists($local_delete)) {
						$status = 3; // delete not found
					}
					array_push($to_be_updated[$version]['files'], ['file' => $local_delete, 'status' => $status]);
				}
			}
			if (array_key_exists('files', $updates[$version])) {
				foreach ($updates[$version]['files'] as $file) {
					$local_file = $this->base_dir.$file;
					$status = 0; // new
					if (file_exists($local_file)) {
						$status = 1; // update
					}
					array_push($to_be_updated[$version]['files'], ['file' => $local_file, 'status' => $status]);
				}
			}
		}

		$this->new_updates = $to_be_updated;
		return $this->new_updates;
	}

	/**
	 * Returns status message about a file
	 *
	 * @param array Array of information about a file to be updated
	 *
	 * @return string Information about the file
	 */
	private function getStatusMessage($file, $end='') {
		$ret = "    ";
		switch ($file['status']) {
			case 0: {
				$ret .= "[ ".$this->colors->getColoredString("NEW   ", "light_green")." ]";
			}
			break;
			case 1: {
				$ret .= "[ ".$this->colors->getColoredString("UPDATE", "light_blue")." ]";
			}
			break;
			case 2: {
				$ret .= "[ ".$this->colors->getColoredString("DELETE", "light_red")." ]";
			}
			break;
			case 3: {
				$ret .= "[ ".$this->colors->getColoredString("DELETE (NOT FOUND)", "light_purple")." ]";
			}
			break;
		}
		$ret .= " - ".str_ireplace($this->base_dir, '', $file['file']);
		
		if ($end=='ok') {
			$ret = str_pad($ret, 120, ' ');
			$ret .= "[ ".$this->colors->getColoredString("OK", "light_green")." ]";
		}
		if ($end=='error') {
			$ret = str_pad($ret, 120, ' ');
			$ret .= "[ ".$this->colors->getColoredString("ERROR", "light_red")." ]";
		}
		
		$ret .= "\n";

		return $ret;
	}

	/**
	 * Show information about available updates
	 *
	 * @return string Prints information about updates
	 */
	public function showUpdates() {
		$to_be_updated = $this->doUpdateCheck();
		echo "\n";

		foreach ($to_be_updated as $version => $update) {
			echo str_pad("==[ ".$update['message']." ]", 110, "=")."\n\n";
			foreach ($update['files'] as $file) {
				echo $this->getStatusMessage($file);
			}
			echo "\n".str_pad('', 109, '=')."\n\n";
		}
	}

	/**
	 * Perform the updates and print update information messages
	 *
	 * @return string Prints information about updates
	 */
	public function doUpdate() {
		$to_be_updated = $this->doUpdateCheck();
		echo "\n";

		$result = true;
		foreach ($to_be_updated as $version => $update) {
			echo str_pad("==[ ".$update['message']." ]", 110, "=")."\n\n";
			$backups = [];
			foreach ($update['files'] as $file) {
				// Update or delete -> make backup
				if ($file['status']==1 || $file['status']==2) {
					$backup_file = $file['file'].'_backup';
					rename($file['file'], $backup_file);
					array_push($backups, ['new_file'=>$file['file'], 'backup'=>$backup_file]);
				}
				// New or update -> download
				if ($file['status']==0 || $file['file']==1) {
					$file_url = $this->repo_url.'v'.$version.'/'.$file['file'];
					$file_content = file_get_contents($file_url);

					$dir = dirname($file['file']);
					if (!file_exists($dir)) {
						mkdir($dir, 0777, true);
					}

					$result_file = file_put_contents($file['file'], $file_content);
					if ($result_file===false) {
						echo $this->getStatusMessage($file, 'error');
						$result = false;
						break;
					}
				}
				
				echo $this->getStatusMessage($file, 'ok');
			}
			echo "\n".str_pad('', 109, '=')."\n\n";
		}
	}
}