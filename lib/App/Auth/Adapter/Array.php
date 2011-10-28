<?php

/**
* Storage driver for use against a PHP Array
*
* PHP versions 4 and 5
*
* LICENSE: This source file is subject to version 3.01 of the PHP license
* that is available through the world-wide-web at the following URI:
* http://www.php.net/license/3_01.txt.  If you did not receive a copy of
* the PHP License and are unable to obtain it through the web, please
* send a note to license@php.net so we can mail you a copy immediately.
*
* @category	Authentication
* @package	Auth
* @author	georg_1 at have2 dot com
* @author	Adam Ashley <aashley@php.net>
* @author	Tim Rupp <caphrim007@gmail.com>
* @copyright	2001-2006 The PHP Group
* @license	http://www.php.net/license/3_01.txt  PHP License 3.01
* @version	CVS: $Id: Array.php,v 1.5 2007/06/12 03:11:26 aashley Exp $
*/

/**
* Storage driver for fetching authentication data from a PHP Array
*
* This container takes two options when configuring:
*
* cryptType:   The crypt used to store the password. Currently recognised
*              are: none, md5 and crypt. default: none
* users:       A named array of usernames and passwords.
*              Ex:
*              array(
*                  'guest' => '084e0343a0486ff05530df6c705c8bb4', // password guest
*                  'georg' => 'fc77dba827fcc88e0243404572c51325'  // password georg
*              )
*
* Usage Example:
* <?php
* $AuthOptions = array(
*      'users' => array(
*          'guest' => '084e0343a0486ff05530df6c705c8bb4', // password guest
*          'georg' => 'fc77dba827fcc88e0243404572c51325'  // password georg
*      ),
*      'cryptType'=>'md5',
*  );
*
* $auth = new Auth("Array", $AuthOptions);
* ?>
*
* @category	Authentication
* @package	Auth
* @author	georg_1 at have2 dot com
* @author	Adam Ashley <aashley@php.net>
* @author	Tim Rupp <caphrim007@gmail.com>
* @copyright	2001-2006 The PHP Group
* @license	http://www.php.net/license/3_01.txt  PHP License 3.01
* @version	Release: 1.6.1  File: $Revision: 1.5 $
*/
class App_Auth_Adapter_Array implements Zend_Auth_Adapter_Interface {
	/**
	* The users and their password to authenticate against
	*
	* @var array
	*/
	protected $users;

	/**
	* The hashType used on the passwords
	*
	* @var string
	*/
	protected $hashType = 'md5';

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

	/**
	* Hold messages for logging
	*
	* @var array
	*/
	protected $_messages = array();

	/**
	* Constructor for Array Container
	*
	* @param array $data Options for the container
	* @return void
	*/
	public function __construct($data, $username = null, $password = null) {
		if (!is_array($data)) {
			throw new Zend_Auth_Adapter_Exception(sprintf('The options for %s must be an array', __CLASS__));
		}

		if (isset($data['users']) && is_array($data['users'])) {
			$this->users = $data['users'];
		} else if (is_array($data)) {
			$this->users = $data;
		} else {
			$this->users = array();
			throw new Zend_Auth_Adapter_Exception(sprintf('%s: no user data found in options array', __CLASS__));
		}

		if (isset($data['hashType'])) {
			$this->hashType = $data['hashType'];
		}

		if ($username !== null) {
			$this->setUsername($username);
		}

		if ($password !== null) {
			$this->setPassword($password);
		}

		$this->_messages = array();
		$this->_messages[0] = ''; // reserved
		$this->_messages[1] = ''; // reserved
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
	* @return Zend_Auth_Adapter_Ldap Provides a fluent interface
	*/
	public function setUsername($username) {
		$this->_username = (string)$username;

		return $this;
	}

	/**
	* Sets the passwort for the account
	*
	* @param  string $password The password of the account being authenticated
	* @return Zend_Auth_Adapter_Ldap Provides a fluent interface
	*/
	public function setPassword($password) {
		$this->_password = (string)$password;
		return $this;
	}

	/**
	* Crypt and verify the entered password
	*
	* @param  string Entered password
	* @param  string Password from the data container (usually this password
	*                is already encrypted.
	* @param  string Type of algorithm with which the password from
	*                the container has been crypted. (md5, crypt etc.)
	*                Defaults to "md5".
	* @return bool   True, if the passwords match
	*/
	protected function verifyPassword($password1, $password2, $hashType = "md5") {
		$this->_messages[] = 'Trying to verify given password with hashed password';
		$this->_messages[] = sprintf('Using "%s" hashing algorithm', $hashType);

		switch ($hashType) {
			case "crypt" :
				return ((string)crypt($password1, $password2) === (string)$password2);
				break;
			case "none" :
				return ((string)$password1 === (string)$password2);
				break;
			default :
			case "md5" :
				return ((string)md5($password1) === (string)$password2);
				break;
		}
	}

	/**
	* Get user information from array
	*
	* This function uses the given username to fetch the corresponding
	* login data from the array. If an account that matches the passed
	* username and password is found, the function returns true.
	* Otherwise it returns false.
	*
	* @param  string Username
	* @param  string Password
	* @return boolean|PEAR_Error Error object or boolean
	*/
	public function authenticate() {
		$this->_messages[] = sprintf('authenticate method called in "%s"', __CLASS__);

		$user = $this->getUsername();
		$password = $this->getPassword();

		if (!isset($this->users[$user])) {
			$this->_messages[0] = 'Authentication failed';
			return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, $user, $this->_messages);
		}

		$verifyPassword = $this->verifyPassword($password, $this->users[$user], $this->hashType);

		if (isset($this->users[$user]) && $verifyPassword) {
			$this->_messages[] = 'Authentication successful';
			return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $user, $this->_messages);
		} else {
			$this->_messages[0] = 'Authentication failed';
			return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, $user, $this->_messages);
		}
	}
}

?>
