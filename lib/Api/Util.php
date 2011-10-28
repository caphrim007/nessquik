<?php

/**
* @author Tim Rupp
*/
class Api_Util {
	const IDENT = __CLASS__;

	public static function getAccount($tokenId) {
		$log = App_Log::getInstance(self::IDENT);
		$token = new Token($tokenId);

		if ($token->isProxy()) {
			$log->debug('Token is a proxy token');
			$accountId = $token->getProxyId();
		} else {
			$log->debug('Token is not a proxy token');
			$accountId = $token->getAccountId();
		}

		if (empty($accountId)) {
			return null;
		} else {
			return new Account($accountId);
		}
	}

	public static function isAllowed($account) {
		$request = new Zend_XmlRpc_Request_Http;
		$method = $request->getMethod();

		if ($account->acl->isAllowed('Api', $method) || $account->acl->isAllowed('Capability', 'use_any_api')) {
			return true;
		} else {
			return false;
		}
	}
}

?>
