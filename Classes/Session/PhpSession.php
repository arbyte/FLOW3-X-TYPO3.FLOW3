<?php
namespace TYPO3\FLOW3\Session;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \TYPO3\FLOW3\Object\Configuration\Configuration as ObjectConfiguration;
use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A simple session based on PHP session functions.
 *
 * @FLOW3\Scope("singleton")
 */
class PhpSession implements \TYPO3\FLOW3\Session\SessionInterface {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Session\Aspect\LazyLoadingAspect
	 */
	protected $lazyLoadingAspect;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var array
	 */
	protected $settings = NULL;

	/**
	 * The session Id
	 *
	 * @var string
	 */
	protected $sessionId;

	/**
	 * If this session has been started
	 *
	 * @var boolean
	 */
	protected $started = FALSE;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		if (ini_get('session.auto_start') != 0) throw new \TYPO3\FLOW3\Session\Exception\SessionAutostartIsEnabledException('PHP\'s session.auto_start must be disabled.', 1219848292);
	}

	/**
	 * Injects the FLOW3 settings, only the session settings are kept.
	 *
	 * @param array $settings Settings of the FLOW3 package
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Initializes the PHP session according to the settings provided.
	 *
	 * @return void
	 */
	public function initializeObject() {
		if (!empty($this->settings['session']['PHPSession']['name'])) {
			session_name($this->settings['session']['PHPSession']['name']);
		}

		$cookieParameters = session_get_cookie_params();

		if (!empty($this->settings['session']['PHPSession']['cookie']['domain'])) {
			$cookieParameters['domain'] = $this->settings['session']['PHPSession']['cookie']['domain'];
		}
		if (!empty($this->settings['session']['PHPSession']['cookie']['lifetime'])) {
			$cookieParameters['lifetime'] = $this->settings['session']['PHPSession']['cookie']['lifetime'];
		}
		if (!empty($this->settings['session']['PHPSession']['cookie']['path'])) {
			$cookieParameters['path'] = $this->settings['session']['PHPSession']['cookie']['path'];
		}
		if (!empty($this->settings['session']['PHPSession']['cookie']['secure'])) {
			$cookieParameters['secure'] = $this->settings['session']['PHPSession']['cookie']['secure'];
		}
		if (!empty($this->settings['session']['PHPSession']['cookie']['httponly'])) {
			$cookieParameters['httponly'] = $this->settings['session']['PHPSession']['cookie']['httponly'];
		}

		session_set_cookie_params(
			$cookieParameters['lifetime'],
			$cookieParameters['path'],
			$cookieParameters['domain'],
			$cookieParameters['secure'],
			$cookieParameters['httponly']
		);

		if (empty($this->settings['session']['PHPSession']['savePath'])) {
			$sessionsPath = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array($this->environment->getPathToTemporaryDirectory(), 'Sessions'));
		} else {
			$sessionsPath = $this->settings['session']['PHPSession']['savePath'];
		}

		if (!file_exists($sessionsPath)) {
			\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($sessionsPath);
		}
		session_save_path($sessionsPath);
	}

	/**
	 * Tells if the session has been started already.
	 *
	 * @return boolean
	 */
	public function isStarted() {
		return $this->started;
	}

	/**
	 * Starts the session, if it has not been already started
	 *
	 * @return void
	 */
	public function start() {
		if ($this->started === FALSE) {
			$this->startOrResume();
		}
	}

	/**
	 * Resumes an existing session, if any.
	 *
	 * @return void
	 */
	public function resume() {
		if ($this->started === FALSE && isset($_COOKIE[session_name()])) {
			$this->startOrResume();
		}
	}

	/**
	 * Returns the current session ID.
	 *
	 * @return string The current session ID
	 * @throws \TYPO3\FLOW3\Session\Exception\SessionNotStartedException
	 */
	public function getId() {
		if ($this->started !== TRUE) throw new \TYPO3\FLOW3\Session\Exception\SessionNotStartedException('The session has not been started yet.', 1218043307);
		return $this->sessionId;
	}

	/**
	 * Generates and propagates a new session ID and transfers all existing data
	 * to the new session.
	 *
	 * @return string The new session ID
	 */
	public function renewId() {
		session_regenerate_id(TRUE);
		$this->sessionId = session_id();
		return $this->sessionId;
	}

	/**
	 * Returns the data associated with the given key.
	 *
	 * @param string $key An identifier for the content stored in the session.
	 * @return mixed The contents associated with the given key
	 * @throws \TYPO3\FLOW3\Session\Exception\SessionNotStartedException
	 */
	public function getData($key) {
		if ($this->started !== TRUE) throw new \TYPO3\FLOW3\Session\Exception\SessionNotStartedException('The session has not been started yet.', 1218043308);
		return (array_key_exists($key, $_SESSION)) ? $_SESSION[$key] : NULL;
	}

	/**
	 * Returns TRUE if $key is available.
	 *
	 * @param string $key
	 * @return boolean
	 */
	public function hasKey($key) {
		return array_key_exists($key, $_SESSION);
	}

	/**
	 * Stores the given data under the given key in the session
	 *
	 * @param string $key The key under which the data should be stored
	 * @param mixed $data The data to be stored
	 * @return void
	 * @throws \TYPO3\FLOW3\Session\Exception\SessionNotStartedException
	 */
	public function putData($key, $data) {
		if ($this->started !== TRUE) throw new \TYPO3\FLOW3\Session\Exception\SessionNotStartedException('The session has not been started yet.', 1218043309);
		if (is_resource($data)) throw new \TYPO3\FLOW3\Session\Exception\DataNotSerializeableException('The given data cannot be stored in a session, because it is of type "' . gettype($data) . '".', 1218475324);
		$_SESSION[$key] = $data;
	}

	/**
	 * Explicitly writes and closes the session
	 *
	 * @return void
	 * @throws \TYPO3\FLOW3\Session\Exception\SessionNotStartedException
	 */
	public function close() {
		if ($this->started !== TRUE) throw new \TYPO3\FLOW3\Session\Exception\SessionNotStartedException('The session has not been started yet.', 1218043310);
		try {
			session_write_close();
		} catch (\Exception $exception) {
			throw new \TYPO3\FLOW3\Session\Exception('The PHP session handler issued an error: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ' in line ' . $exception->getLine() . '.', 1218474911);
		}
		unset($_SESSION);
	}

	/**
	 * Explicitly destroys all session data
	 *
	 * @return void
	 * @throws \TYPO3\FLOW3\Session\Exception\SessionNotStartedException
	 */
	public function destroy() {
		if ($this->started !== TRUE) return; //throw new \TYPO3\FLOW3\Session\Exception\SessionNotStartedException('The session has not been started yet.', 1218043311);
		try {
			$cookieInfo = session_get_cookie_params();
			if ((empty($cookieInfo['domain'])) && (empty($cookieInfo['secure']))) {
				setcookie(session_name(), '', time() - 3600, $cookieInfo['path']);
			} elseif (empty($cookieInfo['secure'])) {
				setcookie(session_name(), '', time() - 3600, $cookieInfo['path'], $cookieInfo['domain']);
			} else {
				setcookie(session_name(), '', time() - 3600, $cookieInfo['path'], $cookieInfo['domain'], $cookieInfo['secure']);
			}
			session_destroy();
		} catch (\Exception $exception) {
			throw new \TYPO3\FLOW3\Session\Exception('The PHP session handler issued an error: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ' in line ' . $exception->getLine() . '.', 1218474912);
		}
		unset($_SESSION);
	}

	/**
	 * Shuts down this session
	 *
	 * @return void
	 */
	public function shutdownObject() {
		if ($this->started === TRUE) {
			$this->putData('TYPO3_FLOW3_Object_ObjectManager', $this->objectManager->getSessionInstances());
			$this->close();
		}
	}

	/**
	 * Starts or resumes a session
	 *
	 * @return void
	 */
	protected function startOrResume() {
		session_start();
		$this->sessionId = session_id();
		$this->started = TRUE;

		if ($this->hasKey('TYPO3_FLOW3_Object_ObjectManager') === TRUE) {
			$sessionObjects = $this->getData('TYPO3_FLOW3_Object_ObjectManager');
			if (is_array($sessionObjects)) {
				foreach ($sessionObjects as $object) {
					if ($object instanceof \TYPO3\FLOW3\Object\Proxy\ProxyInterface) {
						$objectName = $this->objectManager->getObjectNameByClassName(get_class($object));
						if ($this->objectManager->getScope($objectName) === ObjectConfiguration::SCOPE_SESSION) {
							$this->objectManager->setInstance($objectName, $object);
							$this->lazyLoadingAspect->registerSessionInstance($objectName, $object);
							$object->__wakeup();
						}
					}
				}
			} else {
					// Fallback for some malformed session data, if it is no array but something else.
					// In this case, we reset all session objects (graceful degradation).
				$this->putData('TYPO3_FLOW3_Object_ObjectManager', array());
			}
		}
	}
}

?>
