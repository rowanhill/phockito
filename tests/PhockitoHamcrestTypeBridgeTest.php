<?php

// Include Phockito
require_once(dirname(dirname(__FILE__)) . '/Phockito.php');
Phockito::include_hamcrest();

class PhockitoHamcrestTypeBridgeTest_MockMe {
    function Foo(PhockitoHamcrestTypeBridgeTest_PassMe $a) { throw new Exception('Base method Foo was called'); }
}

class PhockitoHamcrestTypeBridgeTest_MockMe_Constructor {
	function __construct(PhockitoHamcrestTypeBridgeTest_PassMe $passMe) { throw new Exception('Base constructor was called'); }
	function Foo(PhockitoHamcrestTypeBridgeTest_PassMe $a) { throw new Exception('Base method Foo was called'); }
}

class PhockitoHamcrestTypeBridgeTest_PassMe {}

class PhockitoHamcrestTypeBridgeTest_PassMe_MatcherMethods {
	public function matches($item) { throw new Exception('Base method matches was called'); }
}

class PhockitoHamcrestTypeBridgeTest extends PHPUnit_Framework_TestCase {
    function testCanStubUsingMatchersForTypeHintedArguments() {
        $mock = Phockito::mock('PhockitoHamcrestTypeBridgeTest_MockMe');

        Phockito::when($mock->Baz(
			HamcrestTypeBridge::argOfTypeThat('PhockitoHamcrestTypeBridgeTest_PassMe',
				anInstanceOf('PhockitoHamcrestTypeBridgeTest_PassMe'))))
			->return('PassMe');

        $this->assertEquals($mock->Baz(new PhockitoHamcrestTypeBridgeTest_PassMe()), 'PassMe');
    }

	function testCanBridgeTypeWithTypeHintedConstructor() {
		$mock = Phockito::mock('PhockitoHamcrestTypeBridgeTest_MockMe_Constructor');

		Phockito::when($mock->Baz(
			HamcrestTypeBridge::argOfTypeThat('PhockitoHamcrestTypeBridgeTest_PassMe',
				anInstanceOf('PhockitoHamcrestTypeBridgeTest_PassMe'))))
			->return('PassMe');

		$this->assertEquals($mock->Baz(new PhockitoHamcrestTypeBridgeTest_PassMe()), 'PassMe');
	}

	/**
	 * @expectedException PHPUnit_Framework_Error
	 * @expectedExceptionCode E_USER_ERROR
	 */
	function testCannotBridgeTypeWithHamcrestMatcherMethods() {
		$mock = Phockito::mock('PhockitoHamcrestTypeBridgeTest_MockMe');

		Phockito::when($mock->Baz(
			HamcrestTypeBridge::argOfTypeThat('PhockitoHamcrestTypeBridgeTest_PassMe_MatcherMethods',
				anInstanceOf('PhockitoHamcrestTypeBridgeTest_PassMe_MatcherMethods'))))
			->return('PassMe');
	}
}
