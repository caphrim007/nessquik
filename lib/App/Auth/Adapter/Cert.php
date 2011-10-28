<?php

/**
* @author Tim Rupp
*/
class App_Auth_Adapter_Cert implements Zend_Auth_Adapter_Interface {
	protected $params = array();
	protected $certificate = null;

	/**
	* Hold messages for logging
	*
	* @var array
	*/
	protected $_messages = array();

	const IDENT = __CLASS__;

	/**
	* Constructor for Array Container
	*
	* @return void
	*/
	public function __construct($params, $certificate = null) {
		if ($params instanceof Zend_Config) {
			$this->params = $params->toArray();
		} elseif (is_array($params)) {
			$this->params = $params;
		} else {
			throw new Zend_Auth_Adapter_Exception(sprintf('The options for %s must be an array', __CLASS__));
		}

		$this->certificate = $certificate;

		$this->_messages = array();
		$this->_messages[0] = ''; // reserved
		$this->_messages[1] = ''; // reserved
	}

	/**
	* @return Zend_Auth_Result
	*/
	public function authenticate() {
		$log = App_Log::getInstance(self::IDENT);

		$log->debug('"authenticate" method called');

		if (empty($this->certificate)) {
			$log->err('Client certificate information was not supplied');
			throw new Zend_Auth_Adapter_Exception('Client certificate information was not supplied');
		}

		if (!file_exists($this->params['cafile'])) {
			$log->err('The CA file does not exist');
			throw new Zend_Auth_Adapter_Exception('The CA file does not exist');
		}

		if (!is_readable($this->params['cafile'])) {
			$log->err('The CA file cannot be read');
			throw new Zend_Auth_Adapter_Exception('The CA file cannot be read');
		}

		$clientCert = openssl_x509_parse($_SERVER['SSL_CLIENT_CERT']);
		$tempFile = tempnam(_ABSPATH.'/tmp/', 'cert_');

		file_put_contents($tempFile, $this->certificate);

		$cmd = sprintf($this->params['openssl'],
			$this->params['cafile'],
			$tempFile
		);

		$output = shell_exec($cmd);

		$output = preg_split('/(: )|[\r\n]/', $output);
		array_shift($output);
		array_pop($output);

		unlink($tempFile);

		if (@$output[0] == 'OK') {
			$log->debug(sprintf('Authentication successful for subject "%s"', $clientCert['name']));
			return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $clientCert['name'], $this->_messages);
		} else {
			$log->debug(sprintf('Authentication failed for subject "%s"', $clientCert['name']));
			return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, $clientCert['name'], $this->_messages);
		}
	}
}

?>
