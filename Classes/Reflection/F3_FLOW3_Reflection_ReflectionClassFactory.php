<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Reflection;

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
 * @subpackage Reflection
 * @version $Id:F3::FLOW3::Reflection::ReflectionClass.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * A factory for class reflections which uses a cache to store them
 *
 * @package FLOW3
 * @subpackage Reflection
 * @version $Id:F3::FLOW3::Reflection::ReflectionClass.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ReflectionClassFactory {

	/**
	 * The cached class reflections
	 *
	 * @var array
	 */
	protected $reflections = array();

	/**
	 * Fills the reflection cache with the specified class reflections.
	 *
	 * @param array $reflections An array of F3::FLOW3::Reflection::ReflectionClass
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setReflections(array $reflections) {
		$this->reflections = array();
		foreach ($reflections as $reflection) {
			if (!$reflection instanceof F3::FLOW3::Reflection::ReflectionClass) throw new InvalidArgumentException('The specified reflection is not a F3::FLOW3::Reflection::ReflectionClass', 1213627749);
			$this->reflections[$reflection->getName()] = $reflection;
		}
	}

	/**
	 * Returns all cached reflections
	 *
	 * @return array An array of F3::FLOW3::Reflection::ReflectionClass
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getReflections() {
		return $this->reflections;
	}

	/**
	 * Returns a new or - if available - cached reflection of the specified class
	 *
	 * @return F3::FLOW3::Reflection::ReflectionClass
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function reflect($className) {
		if (key_exists($className, $this->reflections)) {
			return $this->reflections[$className];
		} else {
			$this->reflections[$className] = new F3::FLOW3::Reflection::ReflectionClass($className);
			return $this->reflections[$className];
		}
	}
}

?>