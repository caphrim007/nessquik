<?php

/**
* @author Tim Rupp
*/
class App_Controller_Helper_CreateArrayAuth extends Zend_Controller_Action_Helper_Abstract {
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
				'adapter' => 'Array',
				'params' => array(
					'users' => array(
						$params['username'] => md5($params['password'])
					),
					'hashType' => 'md5'
				)
			)
		);

		return $config;
	}
}

?>
