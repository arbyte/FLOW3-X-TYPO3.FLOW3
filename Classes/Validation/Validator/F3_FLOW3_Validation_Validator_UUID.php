<?php
declare(ENCODING = 'utf-8');

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
 * @subpackage Validation
 * @version $Id$
 */

/**
 * Validator for Universally Unique Identifiers
 *
 * @package FLOW3
 * @subpackage Validation
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Validation_Validator_UUID implements F3_FLOW3_Validation_ValidatorInterface {

	/**
	 * Returns TRUE, if the given propterty ($proptertyValue) is a formally valid UUID.
	 * Any errors will be stored in the given errors object.
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param  object $propertyValue: The value that should be validated
	 * @return boolean TRUE if the value could be validated. FALSE if an error occured
	 * @throws F3_FLOW3_Validation_Exception_InvalidSubject if this validator cannot validate the given subject or the subject is not an object.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isValidProperty($propertyValue, F3_FLOW3_Validation_Errors &$errors) {
		return (boolean)preg_match('/([a-f0-9]){8}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){12}/', $propertyValue);
	}
}

?>