<?php

require_once 'PHPUnit/Framework.php';
require_once dirname(dirname(__FILE__)).'/src/GAP_Authenticator.php';


/**
 * Test for GAP_Authenticator
 *
 * @author Guillermo Pérez <bishillo@gmail.com>
 */
class GAP_AuthenticatorTest extends PHPUnit_Framework_TestCase {
	public function testGenerateSecret() {
		$base32conversor = $this->getMock('GAP_Base32Conversor');
		$base32conversor->expects($this->exactly(2))
						->method('randomBase32String')
						->with($this->equalTo(GAP_Authenticator::KEY_BYTE_LENGTH))
						->will($this->returnValue('TESTTESTTESTTEST'));
		$auth = new GAP_Authenticator($base32conversor);
		$this->assertEquals($auth->generateSecret(), 'TESTTESTTESTTEST');
		$this->assertEquals($auth->generateSecret(TRUE), 'TEST TEST TEST TEST ');
	}

	public function testCheckValidTOTPCode() {
		$base32 = new GAP_Base32Conversor();
		$key = 'TESTTESTTESTTEST';
		$rawKey = $base32->base32decode($key);
		$challenge = (int) (time()/GAP_Authenticator::TIMESTAMP_GRANULARITY);

		$hotpGenerator = $this->getMock('GAP_TOTPGenerator');
		$hotpGenerator->expects($this->once())
						->method('getTOTPCode')
						->with($this->equalTo($rawKey), $this->equalTo($challenge, 1))
						->will($this->returnValue(123));
		$auth = new GAP_Authenticator(NULL, $hotpGenerator);
		$this->assertEquals($auth->checkTOTPCode(123, $key), TRUE);
	}

	public function testCheckInvalidTOTPCode() {
		$base32 = new GAP_Base32Conversor();
		$key = 'TESTTESTTESTTEST';
		$rawKey = $base32->base32decode($key);
		$timestamp = 30000;
		$challenge = (int) ($timestamp / GAP_Authenticator::TIMESTAMP_GRANULARITY);

		$hotpGenerator = $this->getMock('GAP_TOTPGenerator');
		$hotpGenerator->expects($this->at(0))
						->method('getTOTPCode')
						->with($this->equalTo($rawKey), $this->equalTo($challenge-1))
						->will($this->returnValue(123));
		$hotpGenerator->expects($this->at(1))
						->method('getTOTPCode')
						->with($this->equalTo($rawKey), $this->equalTo($challenge))
						->will($this->returnValue(123));
		$hotpGenerator->expects($this->at(2))
						->method('getTOTPCode')
						->with($this->equalTo($rawKey), $this->equalTo($challenge+1))
						->will($this->returnValue(123));
		$auth = new GAP_Authenticator(NULL, $hotpGenerator);
		$this->assertEquals($auth->checkTOTPCode(321, $key, $timestamp), FALSE);
	}

	public function testCheckTOTPCodeWithInvalidKey() {
		$timestamp = 30000;

		$auth = new GAP_Authenticator();

		try {
			$key = 'Wrong key 999';
			$auth->checkTOTPCode(321, $key, $timestamp);
			var_dump($auth->checkTOTPCode(321, $key, $timestamp));
			$this->fail('checkTOTP should throw exception with wrong key');
		} catch (GAP_InvalidSecretKey $e) {
			$this->assertEquals($e->getMessage(), 'Invalid secret key: '.$key);
		}

		try {
			$key = 'TEST';
			$auth->checkTOTPCode(321, $key, $timestamp);
			var_dump($auth->checkTOTPCode(321, $key, $timestamp));
			$this->fail('checkTOTP should throw exception with wrong key');
		} catch (GAP_InvalidSecretKey $e) {
			$this->assertEquals($e->getMessage(), 'Secret key is too short: '.$key);
		}
	}

	public function testGetTOTPCode() {
		$base32 = new GAP_Base32Conversor();
		$key = 'TEST TEST TEST TEST';
		$rawKey = $base32->base32decode($key);
		$challenge = (int) (time()/GAP_Authenticator::TIMESTAMP_GRANULARITY);

		$hotpGenerator = $this->getMock('GAP_TOTPGenerator');
		$hotpGenerator->expects($this->once())
						->method('getTOTPCode')
						->with($this->equalTo($rawKey), $this->equalTo($challenge, 1))
						->will($this->returnValue(123));
		$auth = new GAP_Authenticator(NULL, $hotpGenerator);
		$this->assertEquals($auth->getTOTPCode($key), 123);
	}
}
