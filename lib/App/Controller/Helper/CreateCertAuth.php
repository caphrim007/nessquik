<?php

/**
* @author Tim Rupp
*/
class App_Controller_Helper_CreateCertAuth extends Zend_Controller_Action_Helper_Abstract {
	public function direct($params) {
		$config = array(
			'auth' => array()
		);

		$auth = Ini_Authentication::getInstance()->toArray();

		$id = $params['id'];

		if (isset($auth['auth'])) {
			$keys = array_keys($auth['auth']);
		} else {
			$keys = array();
		}

		$config['auth'] = array(
			$id => array(
				'name' => $params['auth-name'],
				'priority' => count($keys) + 1,
				'adapter' => $params['auth-type'],
				'params' => array(
					'cafile' => $params['ca-file'],
					'openssl' => $params['openssl']
				)
			)
		);

		if (isset($params['auth-type'])) {
			$haystack = array('Fnal_Cert');

			if (in_array($params['auth-type'], $haystack)) {
				$config['auth'][$id]['params']['create'] = true;
			}
		}

		return $config;
	}
}

?>
