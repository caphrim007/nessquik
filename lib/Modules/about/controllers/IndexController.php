<?php

/**
* @author Tim Rupp
*/
class About_IndexController extends Zend_Controller_Action {
	const IDENT = __CLASS__;

	public function init() {
		parent::init();

		$config = Ini_Config::getInstance();
		$auth = Zend_Auth::getInstance();

		$sessionUser = $auth->getIdentity();
		$sessionId = Account_Util::getId($sessionUser);

		if ($sessionId == 0) {
			$session = null;
		} else {
			$session = new Account($sessionId);
		}

		$this->view->assign(array(
			'action' => $this->_request->getActionName(),
			'config' => $config,
			'controller' => $this->_request->getControllerName(),
			'module' => $this->_request->getModuleName(),
			'session' => $session
		));
	}

	public function indexAction() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		$this->view->assign(array(

		));
	}
}

?>
