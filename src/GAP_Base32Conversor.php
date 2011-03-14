<?php

/**
 * Some basic Base32 operations
 *
 * @author Guillermo Pérez <bishillo@gmail.com>
 */
class GAP_Base32Conversor {
	/**
	 * Generates a random base32 string with the requested bytes of entropy.
	 *
	 * @param integer $bytes Bytes of entropy.
	 * @return string Base32 string
	 */
	public function randomBase32String($bytes) {
		$base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
		$randomString = '';
		$digits = ceil($bytes*8/5);
		for ($i = $digits; $i > 0; $i--) {
			$randomString .= $base32chars[mt_rand(0, 31)];
		}
		return $randomString;
	}

	/**
	 * Converts a base32 digit into decimal value.
	 *
	 * @param string $char
	 * @return integer|FALSE Decimal value or FALSE if not valid char
	 */
	protected function base32ToDec($char) {
		$dec = ord($char[0]);

		if ($dec >= 65 && $dec <= 90) {
			$dec -= 65; // A..Z -> 0..25
		} elseif ($dec >= 97 && $dec <= 122) {
			$dec -= 97; // a..z -> 0..25
		} elseif ($dec >= 50 && $dec <= 55) {
			$dec -= 24; // 2..7 -> 26..31
		} else {
			$dec = false;
		}
		return $dec;
	}

	/**
	 * Decodes a base32 string
	 *
	 * @param string $string Base32 string
	 * @param boolean $fromUserInput If the base32 string comes from user input,
	 * ignores spaces and tries to fix common mistypes.
	 * @return string Decoded string
	 */
	public function base32decode($string, $fromUserInput = TRUE) {
		if ($fromUserInput) {
			$string = strtr($string, array(
					// Strip whitespace
					' ' => '', "\t" => '', "\r" => '', "\n" => '',
					// Fix commonly mistyped; 0, 1 and 8 don't belong to base32
					// on purpose, to avoid confusions:
					'0' => 'O', '1' => 'L', '8' => 'B',
				));
		}

		$buffer = $bits = 0;
		$output = '';
		$length = strlen($string);

		for ($i = 0; $i < $length; $i++) {
			$char = $string[$i];

			// If we have reach padding, we are finish
			if ($char == '=') { break; }

			// Calculate bits
			$chrval = $this->base32ToDec($char);
			if ($chrval === FALSE) {
				// Wrong encoding
				return FALSE;
			}
			$buffer <<= 5;
			$buffer |= $chrval;
			$bits += 5;
			if ($bits >= 8) {
				$bits -= 8;
				$byte = ($buffer >> $bits) & 0xff;
				$output .= chr($byte);
			}
		}
		return $output;
	}
}
