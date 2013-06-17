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
		$bridgeClass = "{$type}_Phockito_HamcrestTypeBridge";

		// If we've already built this bridge, just return it
		if (class_exists($bridgeClass, false)) return $bridgeClass;

		self::throwExceptionIfTypeHasMatcherMethods($type);

		$php = array();
		$php[] = "class $bridgeClass extends $type implements Hamcrest_Matcher {";
		$php[] = "    private \$_matcher;";
		$php[] = "    public function __construct(\$matcher) {";
		$php[] = "        \$this->_matcher = \$matcher;";
		$php[] = "    }";
		$php[] = "    public function matches(\$item) {";
		$php[] = "        return \$this->_matcher->matches(\$item);";
		$php[] = "    }";
		$php[] = "    public function describeMismatch(\$item, Hamcrest_Description \$description) {";
		$php[] = "        return \$this->_matcher->describeMismatch(\$item, \$description);";
		$php[] = "    }";
		$php[] = "    public function describeTo(Hamcrest_Description \$description) {";
		$php[] = "        return \$this->_matcher->describeTo(\$description);";
		$php[] = "    }";
		$php[] = "}";

		eval(implode("\n\n", $php));

		return $bridgeClass;
	}

	private static function throwExceptionIfTypeHasMatcherMethods($type) {
		$reflectType = new ReflectionClass($type);
		$reflectMatcher = new ReflectionClass('Hamcrest_Matcher');

		$typeMethods = $reflectType->getMethods();
		$matcherMethods = $reflectType->getMethods();

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