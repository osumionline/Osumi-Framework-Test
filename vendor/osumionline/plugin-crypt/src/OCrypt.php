<?php declare(strict_types=1);

namespace Osumi\OsumiFramework\Plugins;

/**
 * Utility class to encrypt/decrypt text strings using OpenSSL
 */
class OCrypt {
	private ?string $key = null;
	private string $method = 'aes-256-cbc';

	/**
	 * Sets encryption key on startup if given
	 *
	 * @param string | null $key Encryption string key
	 */
	function __construct(string | null $key = null) {
		if (!is_null($key)) {
			$this->setKey($key);
		}
	}

	/**
	 * Sets encryption string key
	 *
	 * @param string $key Encryption string key
	 *
	 * @return void
	 */
	public function setKey(string $key): void {
		$this->key = $key;
	}

	/**
	 * Sets encryption method
	 *
	 * @param string $method Encryption method
	 *
	 * @return void
	 */
	public function setMethod(string $method): void {
		$this->method = $method;
	}

	/**
	 * Generates a random string key and returns it
	 *
	 * @return string Returns generated key
	 */
	public function generateKey(): string {
		$this->key = base64_encode(openssl_random_pseudo_bytes(32));
		return $this->key;
	}

	/**
	 * Encrypts given string using a key
	 *
	 * @param string $data String data to be encrypted
	 *
	 * @param string | null $key Key string to be used to encrypt data
	 *
	 * @return string Encrypted string
	 */
	function encrypt(string $data, string | null $key = null): string {
		if (is_null($key)) {
			$key = $this->key;
		}
		$encryption_key = base64_decode($key);
		$iv             = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->method));
		$encrypted      = openssl_encrypt($data, $this->method, $encryption_key, 0, $iv);
		return base64_encode($encrypted . '::' . $iv);
	}

	/**
	 * Decrypts given string using a key
	 *
	 * @param string $data String data to be decrypted
	 *
	 * @param string | null $key Key string to be used to decrypt data
	 *
	 * @return string Decrypted string
	 */
	public function decrypt(string $data, string | null $key = null): string {
		if (is_null($key)) {
			$key = $this->key;
		}
		$encryption_key            = base64_decode($key);
		list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
		return openssl_decrypt($encrypted_data, $this->method, $encryption_key, 0, $iv);
	}
}
