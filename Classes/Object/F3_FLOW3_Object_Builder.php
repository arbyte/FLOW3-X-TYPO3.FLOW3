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
 * @version $Id:F3::FLOW3::Object::Builder.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * The Object Object Builder takes care of the whole building (instantiation) process of an
 * object. It resolves dependencies, instantiates other objects if necessary, instantiates
 * the specified object, injects constructor and setter arguments and calls lifecycle methods.
 *
 * @package FLOW3
 * @subpackage Object
 * @version $Id:F3::FLOW3::Object::Builder.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Builder {

	/**
	 * @var F3::FLOW3::Object::ManagerInterfac A reference to the object manager - used for fetching other objects while solving dependencies
	 */
	protected $objectManager;

	/**
	 * @var F3::FLOW3::Reflection::Service A reference to the reflection service
	 */
	protected $reflectionService;

	/**
	 * @var array A little registry of object names which are currently being built. Used to prevent endless loops due to circular dependencies.
	 */
	protected $objectsBeingBuilt = array();

	/**
	 * @var array
	 */
	protected $debugMessages = array();

	/**
	 * Injects the Reflection Service
	 *
	 * @param F3::FLOW3::Reflection::Service $reflectionService The Reflection Service
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectReflectionService(F3::FLOW3::Reflection::Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Injects the object manager
	 *
	 * @param F3::FLOW3::Object::Manager $objectManager The object manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectManager(F3::FLOW3::Object::Manager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the object factory
	 * Note that the object builder and object registry must have been injected before the object factory
	 * can be injected.
	 *
	 * @param F3::FLOW3::Object::FactoryInterface $objectFactory The object factory
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectFactory(F3::FLOW3::Object::FactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Creates and returns a ready-to-use object of the specified type.
	 * During the building process all depencencies are resolved and injected.
	 *
	 * @param string $objectName: Name of the object to create an object for
	 * @param F3::FLOW3::Object::Configuration $objectConfiguration: The object configuration
	 * @param array $overridingConstructorArguments: An array of F3::FLOW3::Object::Argument which override possible autowired arguments. Numbering starts with 1! Index == 1 is the first argument, index == 2 to the second etc.
	 * @return object
	 * @throws F3::FLOW3::Object::Exception::CannotBuildObject
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObject($objectName, F3::FLOW3::Object::Configuration $objectConfiguration, array $overridingConstructorArguments) {
		if (isset ($this->objectsBeingBuilt[$objectName])) throw new F3::FLOW3::Object::Exception::CannotBuildObject('Circular object dependency for object "' . $objectName . '".', 1168505928);
		try {
			$this->objectsBeingBuilt[$objectName] = TRUE;
			$className = $objectConfiguration->getClassName();
			if (!class_exists($className, TRUE)) throw new F3::FLOW3::Object::Exception::CannotBuildObject('No valid implementation class for object "' . $objectName . '" found while building the object (Class "' . $className . '" does not exist).', 1173184871);

			$constructorArguments = $objectConfiguration->getConstructorArguments();
			foreach ($overridingConstructorArguments as $index => $value) {
				$constructorArguments[$index] = $value;
			}

			$setterProperties = $objectConfiguration->getProperties();

			$class = new F3::FLOW3::Reflection::ClassReflection($className);
			if ($objectConfiguration->getAutoWiringMode() == F3::FLOW3::Object::Configuration::AUTOWIRING_MODE_ON) {
				$constructorArguments = $this->autoWireConstructorArguments($constructorArguments, $class);
				$setterProperties = $this->autoWireSetterProperties($setterProperties, $class);
			}

			$preparedArguments = array();
			$this->injectConstructorArguments($constructorArguments, $preparedArguments);

			$object = (count($preparedArguments) > 0) ? $class->newInstanceArgs($preparedArguments) : $class->newInstance();

			if (!is_object($object)) {
				$errorMessage = error_get_last();
				throw new F3::FLOW3::Object::Exception::CannotBuildObject('A parse error ocurred while trying to build a new object of type ' . $className . ' (' . $errorMessage['message'] . ').', 1187164523);
			}

			$this->injectSetterProperties($setterProperties, $object);
			$this->callLifecycleInitializationMethod($object, $objectConfiguration);
		} catch (::Exception $exception) {
			unset ($this->objectsBeingBuilt[$objectName]);
			throw $exception;
		}
		unset ($this->objectsBeingBuilt[$objectName]);
		return $object;
	}

	/**
	 * Reconstitutes the specified object and fills it with the given properties.
	 *
	 * @param string $objectName Name of the object to reconstitute
	 * @param F3::FLOW3::Object::Configuration $objectConfiguration The object configuration
	 * @param array $properties The names of properties and their values which should be set during the reconstitution
	 * @return object The reconstituted object
	 * @throws F3::FLOW3::Object::Exception::CannotReconstituteObject if the class cannot be reconstituted or a circular dependency ocurred.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function reconstituteObject($objectName, F3::FLOW3::Object::Configuration $objectConfiguration, array $properties) {
		if (isset ($this->objectsBeingBuilt[$objectName])) throw new F3::FLOW3::Object::Exception::CannotReconstituteObject('Circular object dependency for object "' . $objectName . '".', 1216742543);
		$this->objectsBeingBuilt[$objectName] = TRUE;

		$className = $objectConfiguration->getClassName();
		if (!in_array('F3::FLOW3::AOP::ProxyInterface', class_implements($className))) throw new F3::FLOW3::Object::Exception::CannotReconstituteObject('Cannot reconstitute the class "' . $className . '" because it does not implement the AOP Proxy Interface.', 1216738485);

			// those objects will be fetched from within the __wakeup() method of the object...
		$GLOBALS['reconstituteObject']['objectFactory'] = $this->objectFactory;
		$GLOBALS['reconstituteObject']['objectManager'] = $this->objectManager;
		$GLOBALS['reconstituteObject']['properties'] = $properties;
		$object = unserialize('O:' . strlen($className) . ':"' . $className . '":0:{};');
		unset($GLOBALS['reconstituteObject']);

		unset ($this->objectsBeingBuilt[$objectName]);
		return $object;
	}

	/**
	 * If mandatory constructor arguments have not been defined yet, this function tries to autowire
	 * them if possible.
	 *
	 * @param array $constructorArguments: Array of F3::FLOW3::Object::ConfigurationArgument for the current object
	 * @param F3::FLOW3::Reflection::ClassReflection $class: The object class which contains the methods supposed to be analyzed
	 * @return array The modified array of F3::FLOW3::Object::ConfigurationArgument
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function autoWireConstructorArguments(array $constructorArguments, F3::FLOW3::Reflection::ClassReflection $class) {
		$className = $class->getName();
		$constructor = $class->getConstructor();
		if ($constructor !== NULL) {
			foreach ($constructor->getParameters() as $parameterIndex => $parameter) {
				$index = $parameterIndex + 1;
				if (!isset($constructorArguments[$index])) {
					try {
						if ($parameter->isOptional()) {
							$defaultValue = ($parameter->isDefaultValueAvailable()) ? $parameter->getDefaultValue() : NULL;
							$constructorArguments[$index] = new F3::FLOW3::Object::ConfigurationArgument($index, $defaultValue, F3::FLOW3::Object::ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);
						} elseif ($parameter->getClass() !== NULL) {
							$constructorArguments[$index] = new F3::FLOW3::Object::ConfigurationArgument($index, $parameter->getClass()->getName(), F3::FLOW3::Object::ConfigurationArgument::ARGUMENT_TYPES_REFERENCE);
						} elseif ($parameter->allowsNull()) {
							$constructorArguments[$index] = new F3::FLOW3::Object::ConfigurationArgument($index, NULL, F3::FLOW3::Object::ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);
						} else {
							$this->debugMessages[] = 'Tried everything to autowire parameter $' . $parameter->getName() . ' in ' . $className . '::' . $constructor->getName() . '() but I saw no way.';
						}
					} catch (ReflectionException $exception) {
						throw new F3::FLOW3::Object::Exception::CannotBuildObject('While trying to autowire the parameter $' . $parameter->getName() . ' of the method ' . $className . '::' . $constructor->getName() . '() a ReflectionException was thrown. Please verify the definition of your constructor method in ' . $constructor->getFileName() . ' line ' . $constructor->getStartLine() . '. Original message: ' . $exception->getMessage(), 1176467813);
					}
				} else {
					$this->debugMessages[] = 'Did not try to autowire parameter $' . $parameter->getName() . ' in ' . $className . '::' . $constructor->getName() . '() because it was already set.';
				}
			}
		} else {
			$this->debugMessages[] = 'Autowiring for class ' . $className . ' disabled because no constructor was found.';
		}
		return $constructorArguments;
	}


	/**
	 * This function tries to find yet unmatched dependencies which need to be injected via "inject*" setter methods.
	 *
	 * @param array $setterProperties: Array of F3::FLOW3::Object::ConfigurationProperty for the current object
	 * @param F3::FLOW3::Reflection::ClassReflection $class: The object class which contains the methods supposed to be analyzed
	 * @return array The modified array of F3::FLOW3::Object::ConfigurationProperty
	 * @throws F3::FLOW3::Object::Exception::CannotBuildObject if a required property could not be autowired.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function autoWireSetterProperties(array $setterProperties, F3::FLOW3::Reflection::ClassReflection $class) {
		$className = $class->getName();
		foreach ($class->getMethods(F3::FLOW3::Reflection::MethodReflection::IS_PUBLIC) as $method) {
			$methodName = $method->getName();
			if (F3::PHP6::Functions::substr($methodName, 0, 6) == 'inject') {
				$propertyName = F3::PHP6::Functions::strtolower(F3::PHP6::Functions::substr($methodName, 6, 1)) . F3::PHP6::Functions::substr($methodName, 7);
				if (array_key_exists($propertyName, $setterProperties)) {
					$this->debugMessages[] = 'Did not try to autowire property $' . $propertyName . ' in ' . $className .  ' because it was already set.';
					continue;
				}
				if ($method->getNumberOfParameters() != 1) {
					$this->debugMessages[] = 'Could not autowire property $' . $propertyName . ' in ' . $className .  ' because it had not exactly one parameter.';
					continue;
				}
				$methodParameters = $method->getParameters();
				$methodParameter = array_pop($methodParameters);
				try {
					$dependencyClass = $methodParameter->getClass();
				} catch (ReflectionException $exception) {
					$dependencyClass = NULL;
				}
				if ($dependencyClass === NULL) {
					$this->debugMessages[] = 'Could not autowire property $' . $propertyName . ' in ' . $className .  ' because I could not determine the class of the setter\'s parameter.';
					if (!$method->isTaggedWith('optional')) {
						throw new F3::FLOW3::Object::Exception::CannotBuildObject('While trying to autowire the required property $' . $propertyName . ' in class ' . $className . ' a ReflectionException was thrown. Please verify the definition of your setter method in ' . $method->getFileName() . ' line ' . $method->getStartLine() . '.', 1203413346);
					}
					continue;
				}
				$setterProperties[$propertyName] = new F3::FLOW3::Object::ConfigurationProperty($propertyName, $dependencyClass->getName(), F3::FLOW3::Object::ConfigurationProperty::PROPERTY_TYPES_REFERENCE);
			}
		}
		return $setterProperties;
	}

	/**
	 * Checks and resolves dependencies of the constructor arguments (objects) and prepares an array of constructor
	 * arguments (strings) which can be used in a "new" statement to instantiate the object.
	 *
	 * @param array $constructorArguments Array of F3::FLOW3::Object::ConfigurationArgument for the current object
	 * @param array &$preparedArguments An empty array passed by reference: Will contain constructor parameters as strings to be used in a new statement
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function injectConstructorArguments($constructorArguments, &$preparedArguments) {
		foreach ($constructorArguments as $constructorArgument) {
			if (is_object($constructorArgument)) {
				if (gettype($constructorArgument->getValue()) == 'integer') {
					$preparedArguments[] = $constructorArgument->getValue();
				} else {
					if ($constructorArgument->getType() === F3::FLOW3::Object::configurationArgument::ARGUMENT_TYPES_REFERENCE) {
						$value = $this->objectManager->getObject($constructorArgument->getValue());
					} else {
						$value = $constructorArgument->getValue();
					}
					$preparedArguments[] = $value;
				}
			} else {
				$preparedArguments[] = NULL;
			}
		}
	}

	/**
	 * Checks, resolves and injects dependencies through calling the setter method of the registered properties.
	 *
	 * @param array $setterProperties: Array of F3::FLOW3::Object::ConfigurationProperty for the current object
	 * @param object $object: The recently created instance of the current object. Dependencies will be injected to it.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function injectSetterProperties($setterProperties, $object) {
		foreach ($setterProperties as $propertyName => $property) {
			switch ($property->getType()) {
				case F3::FLOW3::Object::ConfigurationProperty::PROPERTY_TYPES_REFERENCE:
					$propertyValue = $this->objectManager->getObject($property->getValue());
				break;
				case F3::FLOW3::Object::ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE:
					$propertyValue = $property->getValue();
				break;
			}
			$setterMethodName = 'inject' . F3::PHP6::Functions::ucfirst($propertyName);
			if (method_exists($object, $setterMethodName)) {
				$object->$setterMethodName($propertyValue);
			} else {
				$setterMethodName = 'set' . F3::PHP6::Functions::ucfirst($propertyName);
				if (method_exists($object, $setterMethodName)) {
					$object->$setterMethodName($propertyValue);
				}
			}
		}
	}

	/**
	 * Calls the lifecycle initialization method (if any) of the object
	 *
	 * @param object $object: The instance of the recently created object.
	 * @param F3::FLOW3::Object::Configuration $objectConfiguration: The object configuration
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function callLifecycleInitializationMethod($object, F3::FLOW3::Object::Configuration $objectConfiguration) {
		$lifecycleInitializationMethod = $objectConfiguration->getLifecycleInitializationMethod();
		if (method_exists($object, $lifecycleInitializationMethod)) {
			$object->$lifecycleInitializationMethod();
		}
	}
}
?>