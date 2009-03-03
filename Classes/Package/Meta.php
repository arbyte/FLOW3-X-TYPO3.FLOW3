<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Package;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Package
 * @version $Id$
 */

/**
 * The default TYPO3 Package Meta implementation
 *
 * @package FLOW3
 * @subpackage Package
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Meta implements \F3\FLOW3\Package\MetaInterface {

	const CONSTRAINT_TYPE_DEPENDS = 'depends';
	const CONSTRAINT_TYPE_CONFLICTS = 'conflicts';
	const CONSTRAINT_TYPE_SUGGESTS = 'suggests';

	const PARTY_TYPE_PERSON = 'person';
	const PARTY_TYPE_COMPANY = 'company';

	const CONSTRAINT_SCOPE_PACKAGE = 'package';
	const CONSTRAINT_SCOPE_SYSTEM = 'system';

	const STATE_DEVELOPMENT = 'Development';
	const STATE_ALPHA = 'Alpha';
	const STATE_BETA = 'Beta';
	const STATE_RELEASE_CANDIDATE = 'ReleaseCandidate';
	const STATE_FINAL = 'Final';
	const STATE_OBSOLETE = 'Obsolete';

	/**
	 * @var array
	 */
	protected static $STATES = array(self::STATE_DEVELOPMENT, self::STATE_ALPHA, self::STATE_BETA, self::STATE_RELEASE_CANDIDATE, self::STATE_FINAL, self::STATE_OBSOLETE);

	/**
	 * @var array
	 */
	protected static $CONSTRAINT_TYPES = array(self::CONSTRAINT_TYPE_DEPENDS, self::CONSTRAINT_TYPE_CONFLICTS, self::CONSTRAINT_TYPE_SUGGESTS);

	/**
	 * @var string
	 */
	protected $packageKey;

	/**
	 * The version number
	 * @var \F3\FLOW3\Package\Version
	 */
	protected $version;

	/**
	 * Package title
	 * @var string
	 */
	protected $title;

	/**
	 * Package description
	 * @var string
	 */
	protected $description;

	/*
	 * Package state
	 * @var string
	 */
	protected $state;

	/**
	 * Package categories as string
	 * @var array
	 */
	protected $categories = array();

	/**
	 * Package parties (person, company)
	 * @var array
	 */
	protected $parties = array();

	/**
	 * constraints by constraint type (depends, conflicts, suggests)
	 * @var array
	 */
	protected $constraints = array();

	/**
	 * Get all available constraint types
	 *
	 * @return array All constraint types
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getConstraintTypes() {
		return self::$CONSTRAINT_TYPES;
	}

	/**
	 * Return all possible package states
	 *
	 * @return array The package states
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getStates() {
		return self::$STATES;
	}

	/**
	 * Package metadata constructor
	 *
-	 * @param string The package key
-	 * @param \SimpleXMLElement If specified, the XML data (which must be valid package meta XML) will be used to set the meta properties
	 * @param string $packageKey The package key
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function __construct($packageKey) {
		$this->packageKey = $packageKey;
	}

	/**
	 * @return string The package key
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getPackageKey() {
		return $this->packageKey;
	}

	/**
	 * @return string The package title
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @param string $title: The package title
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * @return string The package version
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getVersion() {
		return $this->version;
	}

	/**
	 * @param string $version: The package version to set
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function setVersion($version) {
		$this->version = $version;
	}

	/**
	 * @return string The package description
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @param string $description: The package description to set
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function setDescription($description) {
		$this->description = $description;
	}

	/**
	 * The package state can be one of:
	 * Development, Alpha, Beta, ReleaseCandidate, Final or Obsolete
	 *
	 * @return string The package state
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getState() {
		return $this->state;
	}

	/**
	 * @param string $state: The package state to set (Alpha, Beta, ...)
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @todo Only accept valid states
	 */
	public function setState($state) {
		if(!in_array($state, self::$STATES)) {
			throw new \InvalidArgumentException('"' . $state . '" is not a valid package state.', 1222810884);
		}
		$this->state = $state;
	}

	/**
	 * @return Array of string The package categories
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getCategories() {
		return $this->categories;
	}

	/**
	 * Adds a package category
	 *
	 * @param string $category
	 * @return void
	 */
	public function addCategory($category) {
		$this->categories[] = $category;
	}

	/**
	 * @return Array of F3\FLOW3\Package\Meta\AbstractParty The package parties
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getParties() {
		return $this->parties;
	}

	/**
	 * Add a party
	 *
	 * @param F3\FLOW3\Package\Meta\AbstractParty $party
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function addParty(\F3\FLOW3\Package\Meta\AbstractParty $party) {
		$this->parties[] = $party;
	}

	/**
	 * Get all constraints
	 *
	 * @return array Package constraints
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getConstraints() {
		return $this->constraints;
	}

	/**
	 * Get the constraints by type
	 *
	 * @param string $constraintType Type of the constraints to get: depends, conflicts, suggests
	 * @return array Package constraints
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getConstraintsByType($constraintType) {
		if (!isset($this->constraints[$constraintType])) return array();
		return $this->constraints[$constraintType];
	}

	/**
	 * Add a constraint
	 *
	 * @param F3\FLOW3\Package\Meta\AbstractConstraint $constraint The constraint to add
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function addConstraint(\F3\FLOW3\Package\Meta\AbstractConstraint $constraint) {
		$this->constraints[$constraint->getConstraintType()][] = $constraint;
	}
}
?>