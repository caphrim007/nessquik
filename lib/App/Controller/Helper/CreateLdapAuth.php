<?php

/**
* @author Tim Rupp
*/
class App_Controller_Helper_CreateLdapAuth extends Zend_Controller_Action_Helper_Abstract {
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

		$params['accountFilterFormat'] = preg_replace('/[^A-Za-z0-9%()=!&-]/', '', $params['accountFilterFormat']);
		$params['baseDn'] = preg_replace('/[^A-Za-z0-9,=-]/', '', $params['baseDn']);

		if (isset($params['useSsl']) && empty($params['port'])) {
			$params['port'] = 636;
		}

		if (empty($params['port'])) {
			$params['port'] = 389;
		}

		$config['auth'] = array(
			$id => array(
				'name' => $params['auth-name'],
				'priority' => count($keys) + 1,
				'adapter' => $params['auth-type'],
				'params' => array(
					'host' => $params['host'],
					'port' => $params['port'],
					'baseDn' => $params['baseDn'],
					'accountFilterFormat' => $params['accountFilterFormat'],
				)
			)
		);

		if (isset($params['auth-type'])) {
			$haystack = array('Fnal_Ldap');

			if (in_array($params['auth-type'], $haystack)) {
				$config['auth'][$id]['params']['create'] = true;
			}
		}

		if (isset($params['use-encryption'])) {
			switch ($params['encryption-type']) {
				case 'useSsl':
					$config['auth'][$id]['params']['useSsl'] = true;
					break;
				case 'useStartTls':
					$config['auth'][$id]['params']['useStartTls'] = true;
					break;
			}
		}

		if (isset($params['bindRequiresDn'])) {
			$config['auth'][$id]['params']['bindRequiresDn'] = true;
		}

		if (!empty($params['username'])) {
			$config['auth'][$id]['params']['username'] = $params['username'];
		}

		if (!empty($params['password'])) {
			$config['auth'][$id]['params']['password'] = $params['password'];
		}

		if (!empty($params['accountDomainName'])) {
			$config['auth'][$id]['params']['accountDomainName'] = $params['accountDomainName'];
		}

		return $config;
	}
}

?>
