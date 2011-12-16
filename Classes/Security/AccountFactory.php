<?php
namespace TYPO3\FLOW3\Security;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A factory for conveniently creating new accounts
 *
 * @FLOW3\Scope("singleton")
 */
class AccountFactory {

	/**
	 * @var \TYPO3\FLOW3\Security\Cryptography\HashService
	 * @FLOW3\Inject
	 */
	protected $hashService;

	/**
	 * Creates a new account and sets the given password and roles
	 *
	 * @param string $identifier Identifier of the account, must be unique
	 * @param string $password The clear text password
	 * @param array $roleIdentifiers Optionally an array of role identifiers to assign to the new account
	 * @param string $authenticationProviderName Optinally the name of the authentication provider the account is affiliated with
	 * @return \TYPO3\FLOW3\Security\Account A new account, not yet added to the account repository
	 */
	public function createAccountWithPassword($identifier, $password, $roleIdentifiers = array(),
                                              $authenticationProviderName = 'DefaultProvider') {
		$roles = array();
		foreach ($roleIdentifiers as $roleIdentifier) {
			$roles[] = new \TYPO3\FLOW3\Security\Policy\Role($roleIdentifier);
		}

		$account = new \TYPO3\FLOW3\Security\Account();
		$account->setAccountIdentifier($identifier);
		$account->setCredentialsSource($this->hashService->hashPassword($password));
		$account->setAuthenticationProviderName($authenticationProviderName);
		$account->setRoles($roles);

		return $account;
	}
}

?>