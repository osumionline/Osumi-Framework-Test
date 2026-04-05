<?php declare(strict_types=1);

namespace Osumi\OsumiFramework\Plugins;

/**
 * Utility class to access remote FTP servers
 */
class OFTP {
	private ?string   $lang            = null;
	private ?string   $server          = null;
	private ?string   $user_name       = null;
	private ?string   $user_pass       = null;
	private mixed     $conn            = null;
	private bool      $connected       = false;
	private bool      $logged          = false;
	private int       $mode            = FTP_ASCII;
	private bool      $auto_disconnect = true;
	private array     $errors          = [
		'es' => [
			'CONNECTION' => 'Error de conexión: "%s"',
			'LOGIN'      => 'Error al iniciar sesión: "%s"'
		],
		'en' => [
			'CONNECTION' => 'Connection error: "%s"',
			'LOGIN'      => 'Login error: "%s"'
		]
	];

	/**
	 * Set up server name, user, password and application language on startup
	 *
	 * @param string $server Host name where to connect
	 *
	 * @param string $user User name to be connected with
	 *
	 * @param string $pass Password of the user
	 */
	function __construct(string $server, string $user, string $pass) {
		global $core;
		$this->lang      = $core->config->getLang();
		$this->server    = $server;
		$this->user_name = $user;
		$this->user_pass = $pass;
	}

	/**
	 * Open a connection to the server
	 *
	 * @return bool Returns if connection was successful or not
	 */
	public function connect(): bool {
		$this->conn = ftp_connect($this->server);
		if ($this->conn) {
			$this->connected = true;
		}
		return $this->connected;
	}

	/**
	 * Closes connection to the server
	 *
	 * @return void
	 */
	public function disconnect(): void {
		ftp_close($this->conn);
		$this->connected = false;
		$this->logged = false;
	}

	/**
	 * Logs into connected server
	 *
	 * @return bool Returns if log in was successful or not
	 */
	public function login(): bool {
		$this->logged = ftp_login($this->conn, $this->user_name, $this->user_pass);
		return $this->logged;
	}

	/**
	 * Sets up if the connection has to be stablished on a passive mode
	 *
	 * @param bool $pasv Passive mode or not
	 *
	 * @return void
	 */
	public function passive(bool $pasv = true): void {
		ftp_pasv($this->conn, $pasv);
	}

	/**
	 * Autodisconnect after a command is executed
	 *
	 * @param bool $auto Set if connection should be closed after a command is executed
	 *
	 * @return void
	 */
	public function autoDisconnect(bool $auto): void {
		$this->auto_disconnect = $auto;
	}

	/**
	 * Set connection mode ASCII or BINARY
	 *
	 * @param string $mode Connection mode
	 *
	 * @return void
	 */
	public function mode(string $mode): void {
		switch ($mode) {
			case 'ascii': {
				$this->mode = FTP_ASCII;
			}
			break;
			case 'bin': {
				$this->mode = FTP_BINARY;
			}
			break;
		}
	}

	/**
	 * Check if connection is opened and if not try to connect
	 *
	 * @return void
	 */
	private function checkConnection(): void {
		if (!$this->connected && !$this->connect()) {
			throw new \Exception( sprintf($this->errors[$this->lang]['CONNECTION'], $this->server) );
		}
		if (!$this->logged && !$this->login()) {
			throw new \Exception( sprintf($this->errors[$this->lang]['LOGIN'], $this->user_name) );
		}
	}

	/**
	 * Put (or upload) a file into the server
	 *
	 * @param string $local Local path of the file to be uploaded
	 *
	 * @param string $remote Remote path of the file to be uploaded
	 *
	 * @return bool Returns if the operation was successfully performed
	 */
	public function put(string $local, string $remote): bool {
		$this->checkConnection();

		$result = ftp_put($this->conn, $remote, $local, $this->mode);

		if ($this->auto_disconnect) {
			$this->disconnect();
		}

		return $result;
	}

	/**
	 * Get (or download) a file from the server
	 *
	 * @param string $remote Remote path of the file to be downloaded
	 *
	 * @param string $local Local path where the downloaded file should be stored
	 *
	 * @return bool Returns if the operation was successfully performed
	 */
	public function get(string $remote, string $local): bool {
		$this->checkConnection();

		$result = ftp_get($this->conn, $local, $remote, $this->mode);

		if ($this->auto_disconnect) {
			$this->disconnect();
		}

		return $result;
	}

	/**
	 * Delete a file from the server
	 *
	 * @param string $remote Remote path of the file to be deleted
	 *
	 * @return bool Returns if the operation was successfully performed
	 */
	public function delete(string $remote): bool {
		$this->checkConnection();

		$result = ftp_delete($this->conn, $remote);

		if ($this->auto_disconnect) {
			$this->disconnect();
		}

		return $result;
	}

	/**
	 * Change path on the server
	 *
	 * @param string $dir Path on the server
	 *
	 * @return bool Returns if the operation was successfully performed
	 */
	public function chdir(string $dir): bool {
		$this->checkConnection();

		$result = ftp_chdir($this->conn, $dir);

		if ($this->auto_disconnect) {
			$this->disconnect();
		}

		return $result;
	}

	/**
	 * Create a directory on the server
	 *
	 * @param string $dir Path on the server
	 *
	 * @return bool Returns if the operation was successfully performed
	 */
	public function mkdir(string $dir): bool {
		$this->checkConnection();

		$result = ftp_mkdir($this->conn, $dir);

		if ($this->auto_disconnect) {
			$this->disconnect();
		}

		return $result;
	}

	/**
	 * Disconnect automatically when class is destroyed
	 *
	 * @return void
	 */
	public function __destruct() {
        $this->disconnect();
    }
}
