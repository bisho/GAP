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

	public function testCheckValidHOTPCode() {
		$base32 = new GAP_Base32Conversor();
		$key = 'TESTTESTTESTTEST';
		$rawKey = $base32->base32decode($key);
		$challenge = (int) (time()/GAP_Authenticator::TIMESTAMP_GRANULARITY);

		$hotpGenerator = $this->getMock('GAP_HOTPGenerator');
		$hotpGenerator->expects($this->once())
						->method('getHOTPCode')
						->with($this->equalTo($rawKey), $this->equalTo($challenge, 1))
						->will($this->returnValue(123));
		$auth = new GAP_Authenticator(NULL, $hotpGenerator);
		$this->assertEquals($auth->checkHOTPCode(123, $key), TRUE);
	}

	public function testCheckInvalidHOTPCode() {
		$base32 = new GAP_Base32Conversor();
		$key = 'TESTTESTTESTTEST';
		$rawKey = $base32->base32decode($key);
		$timestamp = 30000;
		$challenge = (int) ($timestamp / GAP_Authenticator::TIMESTAMP_GRANULARITY);

		$hotpGenerator = $this->getMock('GAP_HOTPGenerator');
		$hotpGenerator->expects($this->at(0))
						->method('getHOTPCode')
						->with($this->equalTo($rawKey), $this->equalTo($challenge-1))
						->will($this->returnValue(123));
		$hotpGenerator->expects($this->at(1))
						->method('getHOTPCode')
						->with($this->equalTo($rawKey), $this->equalTo($challenge))
						->will($this->returnValue(123));
		$hotpGenerator->expects($this->at(2))
						->method('getHOTPCode')
						->with($this->equalTo($rawKey), $this->equalTo($challenge+1))
						->will($this->returnValue(123));
		$auth = new GAP_Authenticator(NULL, $hotpGenerator);
		$this->assertEquals($auth->checkHOTPCode(321, $key, $timestamp), FALSE);
	}

	public function testCheckHOTPCodeWithInvalidKey() {
		$timestamp = 30000;

		$auth = new GAP_Authenticator();

		try {
			$key = 'Wrong key 999';
			$auth->checkHOTPCode(321, $key, $timestamp);
			var_dump($auth->checkHOTPCode(321, $key, $timestamp));
			$this->fail('checkHOTP should throw exception with wrong key');
		} catch (GAP_InvalidSecretKey $e) {
			$this->assertEquals($e->getMessage(), 'Invalid secret key: '.$key);
		}

		try {
			$key = 'TEST';
			$auth->checkHOTPCode(321, $key, $timestamp);
			var_dump($auth->checkHOTPCode(321, $key, $timestamp));
			$this->fail('checkHOTP should throw exception with wrong key');
		} catch (GAP_InvalidSecretKey $e) {
			$this->assertEquals($e->getMessage(), 'Secret key is too short: '.$key);
		}
	}

	public function testGetHOTPCode() {
		$base32 = new GAP_Base32Conversor();
		$key = 'TEST TEST TEST TEST';
		$rawKey = $base32->base32decode($key);
		$challenge = (int) (time()/GAP_Authenticator::TIMESTAMP_GRANULARITY);

		$hotpGenerator = $this->getMock('GAP_HOTPGenerator');
		$hotpGenerator->expects($this->once())
						->method('getHOTPCode')
						->with($this->equalTo($rawKey), $this->equalTo($challenge, 1))
						->will($this->returnValue(123));
		$auth = new GAP_Authenticator(NULL, $hotpGenerator);
		$this->assertEquals($auth->getHOTPCode($key), 123);
	}
}
