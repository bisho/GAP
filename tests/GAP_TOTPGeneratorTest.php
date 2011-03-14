<?php

require_once 'PHPUnit/Framework.php';
require_once dirname(dirname(__FILE__)).'/src/GAP_Authenticator.php';


/**
 * Test for TOTP Generator
 *
 * @author Guillermo Pérez <bishillo@gmail.com>
 */
class GAP_TOTPGeneratorTest extends PHPUnit_Framework_TestCase {
	public function testGetTOTPCode() {
		$base32 = new GAP_Base32Conversor();
		$rawKey = $base32->base32decode('2SH3V3GDW7ZNMGYE');
		$timeChallenge = 10000;
		$expectedCode = 50548;
		$expectedNextCode = 478726;

		$hotp = new GAP_TOTPGenerator();
		$this->assertEquals($hotp->getTOTPCode($rawKey, $timeChallenge), $expectedCode);
		$this->assertEquals($hotp->getTOTPCode($rawKey, $timeChallenge+1), $expectedNextCode);
	}

	public function testHmacHashReal() {
		$base32 = new GAP_Base32Conversor();
		$rawKey = $base32->base32decode('2SH3V3GDW7ZNMGYE');
		$algo = 'sha1';
		$data = 'test';
		$expectedHash = '7021a23ea60aa4438472079a19254e2ce531afc6';

		$class = new ReflectionClass('GAP_TOTPGenerator');
		$method = $class->getMethod('hmacHashReal');
		$method->setAccessible(true);
		$this->assertEquals($method->invoke(new GAP_TOTPGenerator(), $algo, $data, $rawKey), $expectedHash);
		$this->assertEquals(
				bin2hex($method->invoke(new GAP_TOTPGenerator(), $algo, $data, $rawKey, TRUE)),
				$expectedHash
			);
	}

	public function testHmacHashRealBigKey() {
		$base32 = new GAP_Base32Conversor();
		$rawKey = $base32->base32decode(
				'2SH3V3GDW7ZNMGYE2SH3V3GDW7ZNMGYE2SH3V3GDW7ZNMGYE'.
				'2SH3V3GDW7ZNMGYE2SH3V3GDW7ZNMGYE2SH3V3GDW7ZNMGYE'.
				'2SH3V3GDW7ZNMGYE2SH3V3GDW7ZNMGYE2SH3V3GDW7ZNMGYE'.
				'2SH3V3GDW7ZNMGYE2SH3V3GDW7ZNMGYE2SH3V3GDW7ZNMGYE'
			);
		$algo = 'sha1';
		$data = 'test';
		$expectedHash = '4be9feb6cf041e067145d93cd4bbea90a71cfc25';

		$class = new ReflectionClass('GAP_TOTPGenerator');
		$method = $class->getMethod('hmacHashReal');
		$method->setAccessible(true);
		$this->assertEquals($method->invoke(new GAP_TOTPGenerator(), $algo, $data, $rawKey), $expectedHash);
	}
}
