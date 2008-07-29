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
 * @subpackage Tests
 * @version $Id:$
 */

/**
 * Testcase for username/password authentication token
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_Authentication_Token_UsernamePasswordTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function credentialsAreSetCorrectlyFromPOSTArguments() {
		$mockEnvironment = $this->getMock('F3_FLOW3_Utility_Environment', array(), array(), '', FALSE);

		$POSTArguments = array(
			'F3_FLOW3_Security_Authentication_Token_UsernamePassword::username' => 'FLOW3',
			'F3_FLOW3_Security_Authentication_Token_UsernamePassword::password' => 'verysecurepassword'
		);

		$mockEnvironment->expects($this->once())->method('getPOSTArguments')->will($this->returnValue($POSTArguments));

		$token = new F3_FLOW3_Security_Authentication_Token_UsernamePassword($mockEnvironment);
		$token->updateCredentials();

		$expectedCredentials = array ('username' => 'FLOW3', 'password' => 'verysecurepassword');
		$this->assertEquals($expectedCredentials, $token->getCredentials(), 'The credentials have not been extracted correctly from the POST arguments');
	}
}
?>