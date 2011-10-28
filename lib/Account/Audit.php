<?php
/** vim: set ts=4:sw=4:sts=4smarttab:expandtab:autoindent **/

/**
* @author Tim Rupp
*/
class Account_Audit {
	private $report;

	/**
	* @var integer
	*/
	protected $accountId;

	protected $_status;

	const IDENT = __CLASS__;

	public function __construct($accountId) {
		$this->_status = array();

		if (is_numeric($accountId)) {
			$this->accountId = $accountId;
		} else {
			$this->accountId = 0;
		}
	}

	public function hasAudits() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('accounts_audits', array('account_id'))
			->where(sprintf('%s = %s',
				$db->quoteIdentifier('account_id'),
				$db->quote($this->accountId)
			))
			->limit(1);

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();

			if ($stmt->rowCount() > 0) {
				return true;
			} else {
				return false;
			}
		} catch (Exception $error) {
			throw new Account_Exception($error->getMessage());
		}
	}

	/**
	* @throws Account_Exception
	*/
	public function getAudits($page = 1, $limit = 15, $status = null) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$results = array();
		$newResults = array();

		$sql = $db->select()
			->from('accounts_audits')
			->where(sprintf('%s = %s',
				$db->quoteIdentifier('account_id'),
				$db->quote($this->accountId)
			));

		if (!empty($status)) {
			$sql->where(sprintf('%s = %s',
				$db->quoteIdentifier('audit_status'),
				$db->quote($status)
			));
		}

		if (!empty($limit)) {
			$sql->limitPage($page, $limit);
		}

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();

			return $stmt->fetchAll();
		} catch (Exception $error) {
			throw new Account_Exception($error->getMessage());
		}
	}

	public function getUpcomingAudits(Zend_Date $start, Zend_Date $end) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$now = new Zend_Date;
		$results = array();

		if ($start->compare($end) > -1) {
			throw new Account_Exception(sprintf('The specified end time, "%s", comes before the start time, "%s"', $end->get(Zend_Date::W3C), $start->get(Zend_Date::W3C)));
		}

		try {
			$audits = $this->getAudits(1,0,null);

			foreach($audits as $row) {
				$audit = new Audit($row['id']);

				if ($audit->scheduling) {
					if ($start->isEarlier($audit->date_scheduled) && $end->isLater($audit->date_scheduled)) {
						$results[] = array(
							'date_scheduled' => $audit->date_scheduled->get(Zend_Date::W3C),
							'status' => $audit->status,
							'id' => $audit->id,
							'name' => $audit->name
						);
					}

					$schedules = $audit->schedule->enumerateSchedules();

					if (!empty($schedules)) {
						foreach($schedules as $schedule) {
							$date = new Zend_Date($schedule, Zend_Date::W3C);
							$status = $audit->status;

							if ($date->isEarlier($now)) {
								$status = 'F';
							} else if ($date->isLater($now)) {
								$status = 'P';
							}

							if ($start->isEarlier($date) && $end->isLater($date)) {
								$results[] = array(
									'date_scheduled' => $schedule,
									'status' => $status,
									'id' => $audit->id,
									'name' => $audit->name
								);
							}
						}
					}
				} else {
					if ($start->isEarlier($audit->date_scheduled) && $end->isLater($audit->date_scheduled)) {
						$results[] = array(
							'date_scheduled' => $audit->date_scheduled->get(Zend_Date::W3C),
							'status' => $audit->status,
							'id' => $audit->id,
							'name' => $audit->name
						);
					}
				}
			}

			return $results;
		} catch (Exception $error) {
			throw new Account_Exception($error->getMessage());
		}
	}

	public function hasAudit($auditId) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('accounts_audits', array('audit_id'))
			->where(sprintf('%s = %s',
				$db->quoteIdentifier('account_id'),
				$db->quote($this->accountId)
			))
			->where(sprintf('%s = %s',
				$db->quoteIdentifier('audit_id'),
				$db->quote($auditId)
			))
			->limit(1);

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			$result = $stmt->fetchAll();

			if (count($result) == 0) {
				return false;
			} else {
				return true;
			}
		} catch (Exception $error) {
			throw new Account_Exception($error->getMessage());
		}
	}

	public function stop($auditId) {
		$audit = new Audit($auditId);
		$oldStatus = trim($audit->status);

		if ($oldStatus == 'P') {
			$audit->stop();
		} else {
			$audit->cancel();
		}
		return true;
	}

	public function delete($auditId) {
		$audit = new Audit($auditId);
		$oldStatus = $audit->status;

		try {
			$result = $audit->delete();
			return $result;
		} catch (Audit_Exception $error) {
			throw new Account_Exception($error->getMessage());
		}
	}

	public function get($status = 'P', $page = 1, $limit = 15) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$helper = new App_Controller_Helper_InterpretStatus;
		$status = $helper->direct($status, true);

		$sql = $db->select()
			->from('accounts_audits')
			->where(sprintf('%s = %s',
				$db->quoteIdentifier('account_id'),
				$db->quote($this->accountId)
			));

		if ($status !== null) {
			$sql->where(sprintf('%s = %s',
				$db->quoteIdentifier('audit_status'),
				$db->quote($status)
			));
		}

		if (!empty($limit)) {
			$sql->limitPage($page, $limit);
		}

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			return $stmt->fetchAll();
		} catch (Exception $error) {
			throw new Account_Exception($error->getMessage());
		}
	}

	public function count($status = 'P') {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$helper = new App_Controller_Helper_InterpretStatus;
		$status = $helper->direct($status, true);

		$sql = $db->select()
			->from('accounts_audits')
			->where(sprintf('%s = %s',
				$db->quoteIdentifier('account_id'),
				$db->quote($this->accountId)
			));

		if ($status !== null) {
			$sql->where(sprintf('%s = %s',
				$db->quoteIdentifier('audit_status'),
				$db->quote($status)
			));
		}

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			return $stmt->rowCount();
		} catch (Exception $error) {
			throw new Account_Exception($error->getMessage());
		}
	}
}

?>
