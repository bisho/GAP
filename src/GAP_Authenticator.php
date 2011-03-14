<?php
require_once dirname(__FILE__).'/GAP_Base32Conversor.php';
require_once dirname(__FILE__).'/GAP_HOTPGenerator.php';

/**
 * This class generates HOTP codes, ready to be used with Google Authenticator
 * mobile clients. Use it in your websites to offer 2-step authentication.
 * 
 * See http://goo.gl/dfszH
 *
 * @author Guillermo Pérez <bishillo@gmail.com>
 */
class GAP_Authenticator {
	const KEY_BYTE_LENGTH = 10;
	const TIMESTAMP_GRANULARITY = 30;

	/**
	 * @var GAP_Base32Conversor
	 */
	private $base32conversor = NULL;

	/**
	 * @var GAP_HOTPGenerator
	 */
	private $hotpGenerator = NULL;

	/**
	 * Builds a google authenticator service
	 *
	 * Optionally receives the external components (for dependency injection
	 * in tests) or builds the defaults.
	 *
	 * @param GAP_Base32Conversor $base32conversor
	 * @param GAP_HOTPGenerator $hotpGenerator
	 */
	public function __construct($base32conversor = NULL, $hotpGenerator = NULL) {
		if ($base32conversor === NULL) {
			$base32conversor = new GAP_Base32Conversor();
		}
		$this->base32conversor = $base32conversor;
		if ($hotpGenerator === NULL) {
			$hotpGenerator = new GAP_HOTPGenerator();
		}
		$this->hotpGenerator = $hotpGenerator;
	}

	/**
	 * Checks if a HOTP code is correct
	 *
	 * The client provides the code by using some mobile app like:
	 * http://goo.gl/dfszH
	 *
	 * A shared secret key should have been exchanged before. Once
	 * exchanged, the server should keep the secret safe, and avoid
	 * displaying it again anymore.
	 *
	 * @param integer $code Code provided by the user
	 * @param string $key Base32 string containing the secret key
	 * @param integer $timestamp Timestamp to use for HOTP code, current time by default
	 * @return boolean
	 * @throws GAP_InvalidSecretKey if the key is not valid
	 */
	public function checkHOTPCode($code, $key, $timestamp = NULL) {
		$rawKey = $this->getRawKey($key);
		$challenge = $this->getChallenge($timestamp);

		for ($i = -1; $i <= 1; $i++) {
			if ($code == $this->getHOTPGenerator()->getHOTPCode($rawKey, $challenge + $i)) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Generates a HOTP code
	 *
	 * Tipically this is done at the client, never at the server. This
	 * is mainly for demo purposes, or if you want to use PHP as client
	 * program to generate HOTP codes.
	 *
	 * @param string $key Base32 string containing the secret key
	 * @param integer $timestamp Timestamp to use for HOTP code, current time by default
	 * @return integer HOTP Code
	 * @throws GAP_InvalidSecretKey if the key is not valid
	 */
	public function getHOTPCode($key, $timestamp = NULL) {
		$rawKey = $this->getRawKey($key);
		$challenge = $this->getChallenge($timestamp);

		return $this->getHOTPGenerator()->getHOTPCode($rawKey, $challenge);
	}

	/**
	 * Generates a 80 bit random key in base32 (16 chars)
	 *
	 * This code is provided to the client once, to be configured in
	 * a mobile application. Once exchanged, it should not be displayed
	 * anymore, or anyone knowing the code might be able to generate
	 * HTOP codes.
	 *
	 * After giving the secret to the user, it's recommended to make
	 * a test to ensure it was properly configured, and no misstypes
	 * had ocurred.
	 *
	 * Use $prettyPrinting to show it to the user in easier-to-type
	 * chunks of 4 chars. Spaces are ignored in all Google Authenticator
	 * applications.
	 *
	 * @return string
	 */
	public function generateSecret($prettyPrinting = FALSE) {
		$key = $this->getBase32Conversor()->randomBase32String(self::KEY_BYTE_LENGTH);
		if ($prettyPrinting) {
			$key = chunk_split($key, 4, ' ');
		}
		return $key;
	}

	/**
	 * Returns the secret key in raw binary format
	 *
	 * @param string $key
	 * @return string Raw key
	 * @throws GAP_InvalidSecretKey if the key is not valid
	 */
	private function getRawKey($key) {
		$rawKey = $this->getBase32Conversor()->base32decode($key);
		if ($rawKey === FALSE) {
			throw new GAP_InvalidSecretKey('Invalid secret key: '.$key);
		} elseif (strlen($rawKey) < self::KEY_BYTE_LENGTH) {
			throw new GAP_InvalidSecretKey('Secret key is too short: '.$key);
		}
		return $rawKey;
	}

	/**
	 * Returns the challenge from the timestamp
	 *
	 * @param integer $timestamp
	 * @return int Challenge
	 */
	private function getChallenge($timestamp) {
		if ($timestamp === NULL) {
			$timestamp = time();
		}
		return (int) ($timestamp / self::TIMESTAMP_GRANULARITY);
	}

	/**
	 * @return GAP_Base32Conversor
	 */
	private function getBase32Conversor() {
		return $this->base32conversor;
	}

	/**
	 * @return GAP_HOTPGenerator
	 */
	private function getHOTPGenerator() {
		return $this->hotpGenerator;
	}
}

class GAP_InvalidSecretKey extends Exception { }
