<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Object;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Object
 * @version $Id:F3::FLOW3::Object::Configuration.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */

/**
 * FLOW3 Object Configuration
 *
 * @package FLOW3
 * @subpackage Object
 * @version $Id:F3::FLOW3::Object::Configuration.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Configuration {

	const AUTOWIRING_MODE_OFF = 0;
	const AUTOWIRING_MODE_ON = 1;

	const SCOPE_PROTOTYPE = 'prototype';
	const SCOPE_SINGLETON = 'singleton';
	const SCOPE_SESSION = 'session';

	/**
	 * @var string $objectName: Unique identifier of the object
	 */
	protected $objectName;

	/**
	 * @var string $className: Name of the class the object is based on
	 */
	protected $className;

	/**
	 * @var string $scope: Instantiation scope for this object - overrides value set via annotation in the implementation class. Options supported by FLOW3 are are "prototype", "singleton" and "session"
	 */
	protected $scope = 'singleton';

	/**
	 * @var array $constructorArguments: Arguments of the constructor detected by reflection
	 */
	protected $constructorArguments = array();

	/**
	 * @var array $properties: Array of properties which are injected into the object
	 */
	protected $properties = array();

	/**
	 * @var integer $autoWiringMode: Mode of the autowiring feature. One of the AUTOWIRING_MODE_* constants
	 */
	protected $autoWiringMode = self::AUTOWIRING_MODE_ON;

	/**
	 * @var string $lifecycleInitializationMethod: Name of the method to call during the initialization of the object (after dependencies are injected)
	 */
	protected $lifecycleInitializationMethod = 'initializeObject';

	/**
	 * @var string Information about where this configuration has been created. Used in error messages to make debugging easier.
	 */
	protected $configurationSourceHint = '< unknown >';

	/**
	 * The constructor
	 *
	 * @param string $objectName: The unique identifier of the object
	 * @param string $className: Name of the class which provides the functionality of this object
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($objectName, $className = NULL) {
		$backtrace = debug_backtrace();
		if (isset($backtrace[1]['object'])) {
			$this->configurationSourceHint = get_class($backtrace[1]['object']);
		} elseif (isset($backtrace[1]['class'])) {
			$this->configurationSourceHint = $backtrace[1]['class'];
		}

		$this->objectName = $objectName;
		$this->className = ($className == NULL ? $objectName : $className);
	}

	/**
	 * Returns the object name
	 *
	 * @return string object name
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObjectName() {
		return $this->objectName;
	}

	/**
	 * Setter function for property "className"
	 *
	 * @param string $className: Name of the class which provides the functionality for this object
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setClassName($className) {
		$this->className = $className;
	}

	/**
	 * Returns the class name
	 *
	 * @return string Name of the implementing class of this object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassName() {
		return $this->className;
	}

	/**
	 * Setter function for property "scope"
	 *
	 * @param string $scope: Name of the scope
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setScope($scope) {
		if (!is_string($scope))  throw new InvalidArgumentException('Scope must be a string value.', 1167820928);
		$this->scope = $scope;
	}

	/**
	 * Returns the scope for this object
	 *
	 * @return string The scope ("prototype", "singleton" ...)
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getScope() {
		return $this->scope;
	}

	/**
	 * Setter function for property "autoWiringMode"
	 *
	 * @param integer $autoWiringMode: One of the AUTOWIRING_MODE_* constants
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setAutoWiringMode($autoWiringMode) {
		if ($autoWiringMode < 0 || $autoWiringMode > 1)  throw new RuntimeException('Invalid auto wiring mode', 1167824101);
		$this->autoWiringMode = $autoWiringMode;
	}

	/**
	 * Returns the injection arguments / properties autoWiringMode for this object
	 *
	 * @return integer Value of one of the AUTOWIRING_MODE_* constants
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAutoWiringMode() {
		return $this->autoWiringMode;
	}

	/**
	 * Setter function for property "lifecycleInitializationMethod"
	 *
	 * @param string $lifecycleInitializationMethod: Name of the method to call after setter injection
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setLifecycleInitializationMethod($lifecycleInitializationMethod) {
		if (!is_string($lifecycleInitializationMethod))  throw new RuntimeException('Invalid lifecycle initialization method name.', 1172047877);
		$this->lifecycleInitializationMethod = $lifecycleInitializationMethod;
	}

	/**
	 * Returns the name of the lifecycle initialization method for this object
	 *
	 * @return string The name of the intialization method
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getLifecycleInitializationMethod() {
		return $this->lifecycleInitializationMethod;
	}

	/**
	 * Setter function for injection properties
	 *
	 * @param  array $properties: Array of F3::FLOW3::Object::ConfigurationProperty
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setProperties(array $properties) {
		foreach ($properties as $name => $value) {
			if (!$value instanceof F3::FLOW3::Object::ConfigurationProperty) throw new RuntimeException('Properties must be of type F3::FLOW3ObjectConfigurationProperty', 1167935337);
		}
		$this->properties = $properties;
	}

	/**
	 * Returns the currently set injection properties of the object
	 *
	 * @return array Array of F3::FLOW3::Object::ConfigurationProperty
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getProperties() {
		return $this->properties;
	}

	/**
	 * Setter function for a single injection property
	 *
	 * @param  array	$property: A F3::FLOW3::Object::ConfigurationProperty
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setProperty(F3::FLOW3::Object::ConfigurationProperty $property) {
		$this->properties[$property->getName()] = $property;
	}

	/**
	 * Setter function for injection constructor arguments. If an empty array is passed to this
	 * method, all (possibly) defined constructor arguments are removed from the configuration.
	 *
	 * @param  array	$constructorArguments: Array of F3::FLOW3::Object::ConfigurationArgument
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setConstructorArguments(array $constructorArguments) {
		if ($constructorArguments === array()) {
			$this->constructorArguments = array();
		} else {
			foreach ($constructorArguments as $constructorArgument) {
				if (!$constructorArgument instanceof F3::FLOW3::Object::ConfigurationArgument) throw new RuntimeException('Properties must be of type F3::FLOW3ObjectConfigurationProperty', 1168004160);
				$this->constructorArguments[$constructorArgument->getIndex()] = $constructorArgument;
			}
		}
	}

	/**
	 * Setter function for a single constructor argument
	 *
	 * @param  array	$constructorArgument: A F3::FLOW3::Object::ConfigurationArgument
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setConstructorArgument(F3::FLOW3::Object::ConfigurationArgument $constructorArgument) {
		$this->constructorArguments[$constructorArgument->getIndex()] = $constructorArgument;
	}

	/**
	 * Returns a sorted array of constructor arguments indexed by position (starting with "1")
	 *
	 * @return array	A sorted array of F3::FLOW3::Object::ConfigurationArgument objects with the argument position as index
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getConstructorArguments() {
		if (count($this->constructorArguments) < 1 ) return array();

		asort($this->constructorArguments);
		$lastConstructorArgument = end($this->constructorArguments);
		$argumentsCount = $lastConstructorArgument->getIndex();
		$sortedConstructorArguments = array();
		for ($index = 1; $index <= $argumentsCount; $index++) {
			$sortedConstructorArguments[$index] = isset($this->constructorArguments[$index]) ? $this->constructorArguments[$index] : NULL;
		}
		return $sortedConstructorArguments;
	}

	/**
	 * Sets some information (hint) about where this configuration has been created.
	 *
	 * @param string $hint: The hint - e.g. the file name of the configuration file
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setConfigurationSourceHint($hint) {
		$this->configurationSourceHint = $hint;
	}

	/**
	 * Returns some information (if any) about where this configuration has been created.
	 *
	 * @return string The hint - e.g. the file name of the configuration file
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getConfigurationSourceHint() {
		return $this->configurationSourceHint;
	}
}

?>