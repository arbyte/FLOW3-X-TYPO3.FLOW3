<?php
namespace TYPO3\FLOW3\Validation\Validator;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * Validator for string length
 *
 * @api
 */
class StringLengthValidator extends \TYPO3\FLOW3\Validation\Validator\AbstractValidator {

	/**
	 * Returns TRUE, if the given property ($value) is a valid string and its length
	 * is between 'minimum' (defaults to 0 if not specified) and 'maximum' (defaults to infinite if not specified)
	 * to be specified in the validation options.
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @throws TYPO3\FLOW3\Validation\Exception\InvalidSubjectException
	 * @api
	 */
	protected function isValid($value) {
		if (isset($this->options['minimum']) && isset($this->options['maximum'])
			&& $this->options['maximum'] < $this->options['minimum']) {
			throw new \TYPO3\FLOW3\Validation\Exception\InvalidValidationOptionsException('The \'maximum\' is shorter than the \'minimum\' in the StringLengthValidator.', 1238107096);
		}

        if (empty($value)) {
            return;
        }

		if (is_object($value)) {
			if (!method_exists($value, '__toString')) {
				throw new \TYPO3\FLOW3\Validation\Exception\InvalidSubjectException('The given object could not be converted to a string.', 1238110957);
			}
		} elseif (!is_string($value)) {
			$this->addError('The given value was not a valid string.', 1269883975);
		}

		$stringLength = strlen($value);
		$isValid = TRUE;
		if (isset($this->options['minimum']) && $stringLength < $this->options['minimum']) $isValid = FALSE;
		if (isset($this->options['maximum']) && $stringLength > $this->options['maximum']) $isValid = FALSE;

		if ($isValid === FALSE) {
			if (isset($this->options['minimum']) && isset($this->options['maximum'])) {
				$this->addError('The length of this text must be between %1$d and %2$d characters.', 1238108067, array($this->options['minimum'], $this->options['maximum']));
			} elseif (isset($this->options['minimum'])) {
				$this->addError('This field must contain at least %1$d characters.', 1238108068, array($this->options['minimum']));
			} else {
				$this->addError('This text may not exceed %1$d characters.', 1238108069, array($this->options['maximum']));
			}
		}
	}
}

?>