<?php

class ClassGeneratorHelper {
	const MOCK_PREFIX = '__phockito_';

	private $_reflection;
	private $_doubleType;

	/**
	 * @param ReflectionClass $reflection
	 * @param string $doubleType A string describing the type of test double: Mock, Spy, or TypeBridge
	 */
	function __construct(ReflectionClass $reflection, $doubleType) {
		$this->_reflection = $reflection;
		$this->_doubleType = $doubleType;
	}

	function getDoubleFullName() {
		if (Phockito::_has_namespaces()) {
			return "{$this->_reflection->getNamespaceName()}\\{$this->getDoubleShortName()}";
		} else {
			return $this->getDoubleShortName();
		}
	}

	function getDoubleShortName() {
		return self::MOCK_PREFIX.$this->_reflection->getShortName().'_'.$this->_doubleType;
	}

	function getMockedShortName() {
		return $this->_reflection->getShortName();
	}

	function getNamespaceDeclaration() {
		if (Phockito::_has_namespaces()) {
			$namespace = $this->_reflection->getNamespaceName();
			return $namespace ? "namespace {$this->_reflection->getNamespaceName()};" : '';
		} else {
			return '';
		}
	}

	function raiseErrorIfMockedClassIsFinal() {
		if ($this->_reflection->isFinal()) {
			user_error("Cannot bridge final type {$this->getMockedShortName()}", E_USER_ERROR);
		}
	}
}