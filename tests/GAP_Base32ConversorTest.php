<?php

require_once 'PHPUnit/Framework.php';
require_once dirname(dirname(__FILE__)).'/src/GAP_Base32Conversor.php';


/**
 * Tests for GAP_Base32Conversor
 *
 * @author Guillermo Pérez <bishillo@gmail.com>
 */
class GAP_Base32ConversorTest extends PHPUnit_Framework_TestCase {
	public function testRandomBase32String() {
		$base32 = new GAP_Base32Conversor();
		$randomString = $base32->randomBase32String(10);
		$this->assertEquals(strlen($randomString), ceil(10*8/5));
		$this->assertEquals(strlen($base32->base32decode($randomString)), 10);
		$this->assertTrue($base32->base32decode($randomString) !== FALSE);
	}

	public function testBase32ToDec() {
		$class = new ReflectionClass('GAP_Base32Conversor');
		$method = $class->getMethod('base32ToDec');
		$method->setAccessible(true);

		$base32 = new GAP_Base32Conversor();
		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567abcdefghijklmnopqrstuvwxyz';
		$length = strlen($chars);

		for ($i = 0; $i < $length; $i++) {
			$this->assertEquals($method->invoke($base32, $chars[$i]), $i%32);
		}

		$this->assertEquals($method->invoke($base32, 0), FALSE);
	}

	public function testBase32decode() {
		$base32 = new GAP_Base32Conversor();
		$base32text = 'JBSWY3DPEB3W64TMMQXC4LQ=';
		// Manual entry could contain spaces and errors
		$base32textManualEntry = "\tJBSWY3  DPE83W\n64T MMQXC41Q=";
		$expectedString = 'Hello world...';
		
		$this->assertEquals($base32->base32decode($base32text, FALSE), $expectedString);
		$this->assertEquals($base32->base32decode($base32textManualEntry), $expectedString);
		$this->assertEquals($base32->base32decode($base32textManualEntry, FALSE), FALSE);
	}
}
