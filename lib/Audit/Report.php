<?php

/**
* @author Tim Rupp
*/
class Audit_Report {

	protected $_id;
	protected $_reports;

	const IDENT = __CLASS__;

	public function __construct($id) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$this->_id = $id;
		$this->_reports = array();

		$sql = $db->select()
			->from('audits_reports')
			->where('audit_id = ?', $this->_id);

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			$result = $stmt->fetchAll();
			if (!empty($result)) {
				$this->_reports = $result[0];
			}
		} catch (Exception $error) {
			throw new Audit_Exception($error->getMessage());
		}
	}

	public function __get($key) {
		switch($key) {
			case 'id':
				return $this->_id;
			default:
				break;
		}

		if (isset($this->_data[$key])) {
			return $this->_data[$key];
		} else {
			return false;
		}
	}

	public function __set($key, $val) {
		switch($key) {
			case 'id':
				return false;
		}

		$this->_data[$key] = $val;
	}

	public function hasReports() {
		if (empty($this->_reports)) {
			return false;
		} else {
			return true;
		}
	}

	/**
	* @throws Audit_Exception
	*/
	public function getReports($page = 1, $limit = 15) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('audits_reports')
			->where('audit_id = ?', $this->id)
			->order('created ASC');

		if (!empty($limit)) {
			$sql->limitPage($page, $limit);
		}

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			return $stmt->fetchAll();
		} catch (Exception $error) {
			throw new Policy_Exception($error->getMessage());
		}
	}

	public function create($reportId, $name) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$date = new Zend_Date;

		$data = array(
			'name' => $name,
			'audit_id' => $this->id,
			'created' => $date->get(Zend_Date::W3C),
			'id' => $reportId
		);

		$result = $db->insert('audits_reports', $data);
	}

	/**
	* @throws Audit_Exception
	*/
	public function delete($reportId) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		try {
			$where[] = $db->quoteInto('audit_id = ?', $this->_id);
			$where[] = $db->quoteInto('id = ?', $reportId);
			$result = $db->delete('audits_reports', $where);

			return true;
		} catch (Exception $error) {
			throw new Audit_Exception($error->getMessage());
		}
	}

	public function get($reportId) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance(self::IDENT);

		$key = array_search($reportId, $this->_reports);
		if ($key === false) {
			return $key;
		} else {
			return $this->_reports[$key];
		}
	}

	public function sendReport($reportId, $params = array()) {
		$config = Ini_Config::getInstance();
		$mail = new Zend_Mail();
		$filter = new Zend_Filter_Boolean(Zend_Filter_Boolean::ALL);

		$default = array(
			'reportFormat' => 'text',
			'subject' => 'nessquik scan report',
			'compressAttachment' => 'yes',
			'sendToMe' => 'yes',
			'sendToOthers' => 'no',
			'recipients' => array(),
			'sendFromAddr' => $config->mail->smtp->from,
			'sendFromName' => $config->mail->smtp->fromName
		);

		$audit = new Audit($this->id);
		$report = new Audit_Report_Object($reportId);
		$view = new Audit_Report_View($report);

		$params = array_merge($default, $params);

		$options = $config->mail->smtp->params->toArray();
		$transport = new Zend_Mail_Transport_Smtp($config->mail->smtp->server, $options);
		Zend_Mail::setDefaultTransport($transport);

		$bodyText = sprintf('Attached are the results of the scan "%s"', $audit->name);
		$bodyHtml = $bodyText;

		$output = $view->render($params['reportFormat']);
		$filename = sprintf('report-%s', $report->id);

		$mail->setSubject($params['subject']);
		$mail->setBodyText($bodyText);
		$mail->setBodyHtml($bodyHtml);

		if ($filter->filter($params['compressAttachment'])) {
			// create object
			$zip = new ZipArchive;

			// open archive
			$fullPath = sprintf('%s/tmp/%s.zip', _ABSPATH, $filename);
			$zipName = basename($fullPath);

			$result = $zip->open($fullPath, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
			if ($result !== true) {
				throw new Zend_Controller_Action_Exception(sprintf('Could not open archive: %s', $fullPath));
			}

			$filename = sprintf('%s.%s', $filename, $params['reportFormat']);
			$result = $zip->addFromString($filename, $output);
			if ($result === false) {
				throw new Zend_Controller_Action_Exception(sprintf('Could not add file: %s', $filename));
			}

			$zip->close();

			$zip = file_get_contents($fullPath);
			$attachment = $mail->createAttachment($zip);
			$attachment->filename = $zipName;
		} else {
			$filename = sprintf('%s.%s', $filename, $params['reportFormat']);
			$fullPath = sprintf('%s/tmp/%s', _ABSPATH, $filename);

			$attachment = $mail->createAttachment($output);
			$attachment->filename = $filename;
		}

		if ($filter->filter($params['sendToMe'])) {
			if (empty($params['sendFromAddr'])) {
				throw new Audit_Report_Exception('The specified From address was empty');
			}

			$mail->setFrom($config->mail->smtp->from, $config->mail->smtp->fromName);
			$mail->addTo($params['sendFromAddr'], $params['sendFromName']);
			$mail->send();
		}

		if ($filter->filter($params['sendToOthers'])) {
			if (empty($params['recipients'])) {
				throw new Audit_Report_Exception('Recipients list was empty');
			}

			$mail->clearRecipients();
			$mail->clearFrom();
			$mail->setFrom($params['sendFromAddr'], $params['sendFromName']);

			foreach($params['recipients'] as $recipient) {
				if (empty($recipient)) {
					continue;
				} else {
					$mail->addBcc($recipient);
				}
			}

			$mail->send();
		}

		return true;
	}

	/**
	* @throws Audit_Exception
	*/
	public function getMostRecentReport() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('audits_reports')
			->where('audit_id = ?', $this->id)
			->order('created DESC')
			->limit(1);

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			$result = $stmt->fetchAll();
			return $result[0]['id'];
		} catch (Exception $error) {
			throw new Policy_Exception($error->getMessage());
		}
	}
}

?>
