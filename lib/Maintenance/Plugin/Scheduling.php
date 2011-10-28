<?php

/**
* Starts any audits whose date_scheduled has been
* reached and whose status is pending. This is used
* to start audits that were specified to run.
*
* @author Tim Rupp
*/
class Maintenance_Plugin_Scheduling extends Maintenance_Plugin_Abstract {
	const IDENT = __CLASS__;

	/**
	* @throws Maintenance_Plugin_Exception
	*/
	public function dispatch(Maintenance_Request_Abstract $request) {
		$log = App_Log::getInstance(self::IDENT);

		$audits = $this->_fetchAuditsToSchedule();

		$log->debug(sprintf('Fetched "%s" audits that will be scheduled', count($audits)));

		try {
			foreach($audits as $auditId) {
				$audit = new Audit($auditId['id']);
				$started = false;
				$iterations = 0;
				$exceptionOccurred = false;

				while($started === false) {
					$iterations = $iterations + 1;
					if ($iterations >= 10) {
						throw new Exception_ExcessiveLooping('Hit one too many iterations while automatically scheduling audits');
					}

					try {
						$scannerId = $audit->scanner_id;
						$account = new Account($audit->doc->accountId);

						$hasServerAcct = $account->scanner->hasAccount($audit->scanner_id);
						if ($hasServerAcct) {
							$username = (string)$account->doc->serverUsername;
							$password = (string)$account->doc->serverPassword;

							if (isset($account->doc->serverCookie)) {
								$cookies = $account->doc->serverCookie;
							} else {
								$cookies = array();
							}

							if ($cookies == 'reset' || empty($cookies)) {
								$cookies = array();
								$log->debug('Cookie was reset or empty. Asking for cookie from adapter');
								$cookie = $this->_getAuditServerCookie($scannerId, $username, $password);
							} else if (!isset($cookies[$scannerId])) {
								$log->debug('Cookie for scanner was empty. Asking for cookie from adapter');
								$cookie = $this->_getAuditServerCookie($scannerId, $username, $password);
							} else if ($cookies[$scannerId] == 'reset' ) {
								$log->debug('Cookie for scanner was reset. Asking for cookie from adapter');
								$cookie = $this->_getAuditServerCookie($scannerId, $username, $password);
							} else {
								$cookie = unserialize($cookies[$scannerId]);
								$cookieName = null;

								if ($cookie instanceof Zend_Http_Cookie) {
									$cookieName = $cookie->getName();
								} else {
									$cookieName = null;
								}

								if ($cookieName == 'token') {
									$log->debug('Cookie appears to be valid');
								} else {
									$log->debug('Cookie may be invalid. Regenerating cookie.');
									$cookie = $this->_getAuditServerCookie($scannerId, $username, $password);
								}
							}

							$cookies[$scannerId] = serialize($cookie);
							$account->doc->serverCookie = $cookies;

							$log->debug('Saving updated account details back to the database');
							$account = $account->update();
						} else {
							$log->debug(sprintf('No account was found for user "%s" on the audit server', $account->username));
						}

						$audit->account = $account;
						$audit->start();

						$started = true;
					} catch (Exception_Unauthorized $error) {
						$log->err($error->getMessage());
						$exceptionOccurred = true;
					} catch (Exception_UnknownCookie $error) {
						$log->err($error->getMessage());
						$exceptionOccurred = true;
					}

					if ($exceptionOccurred === true) {
						$log->debug('An exception occurred. Resetting server cookie details');
						$cookies = $account->doc->serverCookie;

						if (is_array($cookies)) {
							$cookies[$scannerId] = 'reset';
						} else {
							$cookies = 'reset';
						}

						$account->doc->serverCookie = $cookies;
						$account = $account->update();

						$audit->account = null;
						$audit->account = $account;
						$started = false;
					}
				}
			}

			return true;
		} catch (Exception $error) {
			throw new Maintenance_Plugin_Exception($error->getMessage());
		}
	}

	/**
	* @return array Array of audits whose status is pending and
	*	date_scheduled value is in the past.
	* @throws Maintenance_Plugin_Exception
	*/
	protected function _fetchAuditsToSchedule() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$date = new Zend_Date;

		$sql = $db->select()
			->from('audits')
			->where('status = ?', 'P')
			->where('date_scheduled <= ?', $date->get(Zend_Date::W3C));

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			return $stmt->fetchAll();
		} catch (Exception $error) {
			throw new Maintenance_Plugin_Exception($error->getMessage());
		}
	}

	protected function _getAuditServerCookie($scannerId, $username, $password) {
		$helper = new App_Controller_Helper_GetAuditServerCookie;
		return $helper->direct($scannerId, $username, $password);
	}
}

?>
