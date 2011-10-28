<?php
/**
* Storage driver for using multiple storage drivers in a fall through fashion
*
* LICENSE: This source file is subject to version 3.01 of the PHP license
* that is available through the world-wide-web at the following URI:
* http://www.php.net/license/3_01.txt.  If you did not receive a copy of
* the PHP License and are unable to obtain it through the web, please
* send a note to license@php.net so we can mail you a copy immediately.
*
* @category   Authentication
* @package    Auth
* @author     Adam Ashley <aashley@php.net>
* @copyright  2001-2006 The PHP Group
* @license    http://www.php.net/license/3_01.txt  PHP License 3.01
* @version    CVS: $Id: Multiple.php,v 1.4 2007/06/12 03:11:26 aashley Exp $
* @since      File available since Release 1.5.0
*/

/**
* Storage driver for using multiple storage drivers in a fall through fashion
*
* This storage driver provides a mechanism for working through multiple
* storage drivers until either one allows successful login or the list is
* exhausted.
*
* This container takes an array of options of the following form:
*
* array(
*   array(
*     'type'    => <standard container type name>,
*     'options' => <normal array of options for container>,
*   ),
* );
*
* Full example:
*
* $options = array(
*   array(
*     'type'    => 'DB',
*     'options' => array(
*       'dsn' => "mysql://user:password@localhost/database",
*     ),
*   ),
*   array(
*     'type'    => 'Array',
*     'options' => array(
*       'cryptType' => 'md5',
*       'users'     => array(
*         'admin' => md5('password'),
*       ),
*     ),
*   ),
* );
*
* $auth = new Auth('Multiple', $options);
*
* @category   Authentication
* @package    Auth
* @author     Adam Ashley <aashley@php.net>
* @copyright  2001-2006 The PHP Group
* @license    http://www.php.net/license/3_01.txt  PHP License 3.01
* @version    Release: 1.6.1  File: $Revision: 1.4 $
* @since      File available since Release 1.5.0
*/
class App_Auth_Adapter_Multiple implements Zend_Auth_Adapter_Interface {
	/**
	* The options for each container
	*
	* @var array
	*/
	protected $options = array();

	/**
	* The instanciated containers
	*
	* @var array
	*/
	protected $containers = array();

	/**
	* Hold messages for logging
	*
	* @var array
	*/
	protected $_messages = array();

	/**
	* The username of the account being authenticated.
	*
	* @var string
	*/
	protected $_username = null;

	/**
	* The password of the account being authenticated.
	*
	* @var string
	*/
	protected $_password = null;

	protected $_loaders = array();

	const IDENT = __CLASS__;

	/**
	* Constructor for Array Container
	*
	* @param array $data Options for the container
	* @return void
	*/
	public function __construct($options, $username, $password) {
		$this->_messages = array();
		$this->_messages[0] = ''; // reserved
		$this->_messages[1] = ''; // reserved

		$this->_loaders = new Zend_Loader_PluginLoader;
		$this->_loaders->addPrefixPath('Zend_Auth_Adapter', 'Zend/Auth/Adapter/');
		$this->_loaders->addPrefixPath('App_Auth_Adapter', 'App/Auth/Adapter');

		$this->setUsername($username);
		$this->setPassword($password);

		$log = App_Log::getInstance(self::IDENT);

		if ($options instanceof Zend_Config) {
			$options = $options->toArray();
		} else {
			throw new Zend_Auth_Adapter_Exception(sprintf('%s: The options for Auth_Container_Multiple must be an instance of Zend_Config', __CLASS__));
		}

		uasort($options['auth'], array($this,'sortPriority'));

		if (empty($options['auth'])) {
			throw new Zend_Auth_Adapter_Exception('No authentication types are specified');
		} else {
			foreach($options['auth'] as $key => $method) {
				try {
					$adapter = null;
					$class = $this->_loaders->load($method['adapter']);
					$params = $method['params'];

					switch ($method['adapter']) {
						case 'Cert':
						case 'Fnal_Cert':
							if (isset($_SERVER['SSL_CLIENT_CERT'])) {
								$clientCert = openssl_x509_parse($_SERVER['SSL_CLIENT_CERT']);

								if (!empty($clientCert['name'])) {
									$this->setUsername($clientCert['name']);
								}

								@$adapter = new $class($params, $_SERVER['SSL_CLIENT_CERT']);
							}
							break;
						case 'Fnal_Ldap':
							$adapter = new $class(array($params), $username, $password);
							break;
						case 'Ldap':
							$adapter = new App_Auth_Adapter_Ldap(array($params), $username, $password);
							break;
						case 'DbTable':
							$db = App_Db::getInstance($params['adapter']);
							$adapter = new $class($db, 
								$params['tableName'],
								$params['identityColumn'],
								$params['credentialColumn'],
								$params['credentialTreatment']
							);

							$adapter->setIdentity($username);
							$adapter->setCredential($password);
							break;
						case 'Array':
							$adapter = new $class($params, $username, $password);
							break;
						default:
							break;
					}

					if ($adapter !== null) {
						$this->containers[] = $adapter;
					}
				} catch (Zend_Auth_Adapter_Exception $error) {
					$log->err($error->getMessage());
				}
			}
		}

		if (empty($this->containers)) {
			throw new Zend_Auth_Adapter_Exception('No authentication adapters were created');
		}
	}

	public function sortPriority($a, $b) {
		@$aPriority = $a['priority'];
		@$bPriority = $b['priority'];

		if($aPriority > $bPriority) {
			return 1;
		} else if ($aPriority < $bPriority) {
			return -1;
		} else {
			/**
			* for my purposes, if they're equal, then consider
			* the first one to have priority because that's how
			* it will be written in the config file
			*/
			return 1;
		}
	}

	/**
	* Returns the username of the account being authenticated, or
	* NULL if none is set.
	*
	* @return string|null
	*/
	public function getUsername() {
		return $this->_username;
	}

	/**
	* Returns the password of the account being authenticated, or
	* NULL if none is set.
	*
	* @return string|null
	*/
	public function getPassword() {
		return $this->_password;
	}

	/**
	* Sets the username for binding
	*
	* @param  string $username The username for binding
	* @return Zend_Auth_Adapter_Multiple
	*/
	public function setUsername($username) {
		$this->_username = (string)$username;

		return $this;
	}

	/**
	* Sets the passwort for the account
	*
	* @param  string $password The password of the account being authenticated
	* @return Zend_Auth_Adapter_Multiple
	*/
	public function setPassword($password) {
		$this->_password = (string)$password;
		return $this;
	}

	/**
	*
	*/
	public function authenticate() {
		$log = App_Log::getInstance(self::IDENT);
		$log->debug(sprintf('"authenticate" method called'));

		foreach ($this->containers as $key => $container) {
			$containerClass = get_class($container);
			$log->debug(sprintf('Using Container "%s"', $containerClass));

			try {
				$result = $container->authenticate();
				$messages = $result->getMessages();
				foreach($messages as $key => $val) {
					if ($val == '') {
						continue;
					} else {
						$log->debug($val);
					}
				}
			} catch(Zend_Auth_Adapter_Exception $error) {
				$log->err($error->getMessage());
				continue;
			}

			if ($result->isValid()) {
				$log->debug(sprintf('Container "%s": Authentication succeeded.', $containerClass));
				$log->debug('Authentication successful');
				return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $result->getIdentity(), $this->_messages);
			} else {
				$log->debug(sprintf('Container "%s": Authentication failed.', $containerClass));
				$log->debug('Authentication failed');
			}

			unset($result);
		}

		$log->info('All containers rejected user credentials');
		$log->debug('Authentication failed');
		return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, $this->getUsername(), $this->_messages);
	}
}

?>
