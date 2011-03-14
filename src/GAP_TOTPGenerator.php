<?php

/**
 * This class generates TOTP codes.
 * See RFC 4226: http://tools.ietf.org/html/rfc4226
 *
 * @author Guillermo Pérez <bishillo@gmail.com>
 */
class GAP_TOTPGenerator {
	const BLOCKSIZE = 64;

	/**
	 * Returns the TOTP code for the given shared secret key and
	 * time challenge (tipically unix time/30)
	 *
	 * See RFC 4226: http://tools.ietf.org/html/rfc4226
	 *
	 * @param type $rawKey
	 * @param type $timeChallenge
	 * @return int
	 */
	public function getTOTPCode($rawKey, $timeChallenge) {
		$data = str_pad(pack('N', $timeChallenge), 8, chr(0), STR_PAD_LEFT);
		$hash = $this->hmacHash('sha1', $data, $rawKey, TRUE);

		$offset = ord(substr($hash, -1)) & 0xF;
		$code = 0;
		for ($j = 0; $j < 4; $j++) {
			$code <<= 8;
			$code |= ord($hash[$offset + $j]);
		}

		$code &= 0x7FFFFFFF;
		$code %= 1000000;

		return $code;
	}

	/**
	 * Returns the HMAC hash for the given data and key.
	 *
	 * This method just wraps HMAC Hash real implementation. Uses the
	 * internally implemented hash_hmac function available from PHP
	 * > 5.1.2 with hash extension compiled in.
	 *
	 * If hash extension is not available will use the hmacHashReal
	 * method, a PHP implementation of HMAC hash.
	 *
	 * @param string $algo Algorithm to use in the hashes
	 * @param string $data Data to hash
	 * @param string $key Key to use for the HMAX
	 * @param boolean $raw_output Whether to return raw data of hex string
	 * @return string HMAC Hash
	 */
	protected function hmacHash($algo, $data, $key, $raw_output = FALSE) {
		return function_exists('hash_hmac') ?
				hash_hmac($algo, $data, $key, $raw_output) : self::hmacHashReal($algo, $data, $key, $raw_output);
	}

	/**
	 * A PHP implementation of HMAC hash, can be used as replacement of
	 * hmac_hash().
	 *
	 * @param string $algo Algorithm to use in the hashes
	 * @param string $data Data to hash
	 * @param string $key Key to use for the HMAX
	 * @param boolean $raw_output Whether to return raw data of hex string
	 * @return string HMAC Hash
	 */
	protected function hmacHashReal($algo, $data, $key, $raw_output = FALSE) {
		if (strlen($key) > self::BLOCKSIZE) {
			$key = $algo($key, TRUE);
		}
		$key = str_pad($key, self::BLOCKSIZE, chr(0x00));

		$innerKey = $key ^ str_repeat(chr(0x36), self::BLOCKSIZE);
		$outerKey = $key ^ str_repeat(chr(0x5c), self::BLOCKSIZE);

		$hash = $algo($innerKey . $data, TRUE);
		$hash = $algo($outerKey . $hash, TRUE);

		if (! $raw_output) {
			$hash = bin2hex($hash);
		}

		return $hash;
	}
}
