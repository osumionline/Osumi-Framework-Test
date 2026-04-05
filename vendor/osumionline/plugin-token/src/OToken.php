<?php declare(strict_types=1);

namespace Osumi\OsumiFramework\Plugins;

use Osumi\OsumiFramework\Tools\OTools;

/**
 * Utility class with tools to create JWT tokens, check if they are valid and get information from them
 */
class OToken {
	private string | null $secret = null;
	private array $params = [];
	private string | null $token = null;
	private int | null $iat = null;
	private int | null $exp = null;

	/**
	 * Sets signature secret string on startup
	 *
	 * @param string $secret String used to sign the resulting token or check a tokens validity
	 */
	function __construct(string | null $secret) {
		$this->secret = $secret;
	}

	/**
	 * Set parameter array to be added to the token
	 *
	 * @param array $params Parameter array to be added to the token
	 *
	 * @return void
	 */
	public function setParams(array $params): void {
		$this->params = $params;
	}

	/**
	 * Adds a parameter to the tokens parameters
	 *
	 * @param string $key Key code of the parameter
	 *
	 * @param string|int|float|bool $value Value of the parameter to be added
	 *
	 * @return void
	 */
	public function addParam(string $key, $value): void {
		$this->params[$key] = $value;
	}

	/**
	 * Gets list of tokens parameters
	 *
	 * @return array Array of tokens parameters
	 */
	public function getParams(): array {
		return $this->params;
	}

	/**
	 * Gets a single parameter of the token
	 *
	 * @param string $key Key code of the parameter to be retrieved
	 *
	 * @return string|int|float|bool Value of the requested parameter or null if not found
	 */
	public function getParam(string $key) {
		return array_key_exists($key, $this->params) ? $this->params[$key] : null;
	}

	/**
	 * Set IssuedAT value (int timestamp)
	 *
	 * @param int $iat Int timestamp value of the IssuedAT
	 *
	 * @return void
	 */
	public function setIAT(int $iat): void {
		$this->iat = $iat;
	}

	/**
	 * Get IssuedAT value
	 *
	 * @return IssuedAT value if pressent
	 */
	public function getIAT(): int | null {
		return $this->iat;
	}

	/**
	 * Set EXPiration value (int timestamp)
	 *
	 * @param int $exp Int timestamp value of the EXPiration
	 *
	 * @return void
	 */
	public function setEXP(int $exp): void {
		$this->exp = $exp;
		// If IssuedAT is not set, set it automatically
		if (is_null($this->getIAT())) {
			$this->setIAT(time());
		}
	}

	/**
	 * Get EXPiration value
	 *
	 * @return EXPiration value if pressent
	 */
	public function getEXP(): int | null {
		return $this->exp;
	}

	/**
	 * Generates a new JWT token based on given parameters and signed with the given secret key
	 *
	 * @return string Generated JWT token
	 */
	public function getToken(): string {
		if (!is_null($this->token)) {
			return $this->token;
		}
		if (is_null($this->secret)) {
			throw new Exception('Error: Secret cannot be null.');
		}
		$header = ['alg'=> 'HS256', 'typ'=>'JWT'];
		$header_64 = OTools::base64urlEncode(json_encode($header));
		$payload = $this->params;
		// If IAT value is set, add it to the payload
		if (!is_null($this->getIAT())) {
			$payload['iat'] = $this->getIAT();
		}
		// If EXP value is set, add it to the payload
		if (!is_null($this->getEXP())) {
			$payload['exp'] = $this->getEXP();
		}
		$payload_64 = OTools::base64urlEncode(json_encode($payload));

		$signature = hash_hmac('sha256', $header_64.'.'.$payload_64, $this->secret);

		$this->token = $header_64.'.'.$payload_64.'.'.$signature;

		return $this->token;
	}

	/**
	 * Checks if a given token is valid
	 *
	 * @param string $token Token to be checked
	 *
	 * @return bool Token is valid or not
	 */
	public function checkToken(string $token): bool {
		if (is_null($this->secret)) {
			throw new Exception('Error: Secret cannot be null.');
		}
		$pieces = explode('.', $token);
		$header_64  = $pieces[0];
		$payload_64 = $pieces[1];
		$signature  = $pieces[2];

		$signature_check = hash_hmac('sha256', $header_64.'.'.$payload_64, $this->secret);

		if ($signature === $signature_check) {
			$this->params = json_decode(OTools::base64urlDecode($payload_64), true);
			// Check for IAT
			if (array_key_exists('iat', $this->params)) {
				$this->setIAT($this->params['iat']);
				unset($this->params['iat']);
			}
			// Check for EXP
			if (array_key_exists('exp', $this->params)) {
				$this->setEXP($this->params['exp']);
				unset($this->params['exp']);
				if ($this->getEXP() < time()) {
					// Expiration date passed, so is not a valid token
					return false;
				}
			}
			return true;
		}
		return false;
	}
}
