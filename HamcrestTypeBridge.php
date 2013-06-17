<?php

class HamcrestTypeBridge {
	/**
	 * Creates a subclass of $type which implements Hamcrest_Matcher and passes through calls to the given $matcher.
	 *
	 * @param string $type Name of the class to subtype
	 * @param Hamcrest_Matcher $matcher The matcher to proxy
	 */
	public static function argOfTypeThat($type, Hamcrest_Matcher $matcher) {
		$bridgeClass = self::createBridgeType($type);

		$bridge = new $bridgeClass($matcher);

		return $bridge;
	}

	private static function createBridgeType($type) {
		$reflect = new ReflectionClass($type);
		$classGeneratorHelper = new ClassGeneratorHelper($reflect, 'TypeBridge');

		// We can't bridge a final class (as we need to extend it), so raise an error if we're trying
		$classGeneratorHelper->raiseErrorIfMockedClassIsFinal();

		$bridgeClass = $classGeneratorHelper->getDoubleShortName();

		// If we've already built this bridge, just return it
		if (class_exists($bridgeClass, false)) return $bridgeClass;

		$namespace = $classGeneratorHelper->getNamespaceDeclaration();
		$hamcrestMatcher = Phockito::_has_namespaces() ? '\\Hamcrest_Matcher' : 'Hamcrest_Matcher';

		self::raiseErrorIfTypeHasMatcherMethods($type);

		$php = <<<PHP
$namespace
class $bridgeClass extends $type implements $hamcrestMatcher {
    private \$_matcher;
    public function __construct(\$matcher) {
        \$this->_matcher = \$matcher;
    }
    public function matches(\$item) {
        return \$this->_matcher->matches(\$item);
    }
    public function describeMismatch(\$item, Hamcrest_Description \$description) {
        return \$this->_matcher->describeMismatch(\$item, \$description);
    }
    public function describeTo(Hamcrest_Description \$description) {
        return \$this->_matcher->describeTo(\$description);
    }
}
PHP;
		eval($php);
		return $classGeneratorHelper->getDoubleFullName();
	}

	private static function raiseErrorIfTypeHasMatcherMethods($type) {
		$reflectType = new ReflectionClass($type);
		$reflectMatcher = new ReflectionClass('Hamcrest_Matcher');

		$typeMethods = $reflectType->getMethods();
		$matcherMethods = $reflectMatcher->getMethods();

		foreach ($typeMethods as $typeMethod) {
			foreach ($matcherMethods as $matcherMethod) {
				if ($typeMethod->getName() === $matcherMethod->getName()) {
					if (self::paramsMatch($typeMethod->getParameters(), $matcherMethod->getParameters())) {
						user_error("Cannot bridge types which have methods which collide with Hamcrest_Matcher", E_USER_ERROR);
					}
				}
			}
		}
	}

	/**
	 * @param $typeParams
	 * @param $matcherParams
	 * @return bool
	 */
	private static function paramsMatch($typeParams, $matcherParams) {
		if (count($typeParams) !== count($matcherParams)) {
			return false;
		}
		/** @var ReflectionParameter $typeParam */
		foreach ($typeParams as $typeParam) {
			$foundParam = false;
			/** @var ReflectionParameter $matcherParam */
			foreach ($matcherParams as $matcherParam) {
				if ($typeParam->getClass() === $matcherParam->getClass()) {
					$foundParam = true;
					break;
				}
			}
			if (!$foundParam) {
				return false;
			}
		}
		return true;
	}
}