<?php

/**
* @author Tim Rupp
*/
class Setup_IndexController extends Zend_Controller_Action {
	public $session;

	const IDENT = __CLASS__;

	public function init() {
		parent::init();

		$config = Ini_Config::getInstance();

		if ($config->misc->firstboot == 0) {
			$redirector = $this->_helper->getHelper('Redirector');
			$redirector->gotoSimple('index', 'index', 'default');
		}

		$this->view->assign(array(
			'action' => $this->_request->getActionName(),
			'config' => $config,
			'controller' => $this->_request->getControllerName(),
			'module' => $this->_request->getModuleName()
		));
	}

	public function indexAction() {
		$writable = array();
		$toCheck = array(
			'%s/tmp',
			'%s/etc/local/',
			'%s/var/log/',
			'%s/var/cache/',
			'%s/var/lib/'
		);

		foreach($toCheck as $check) {
			$path = sprintf($check, _ABSPATH);

			if (!is_writable($path)) {
				$writable[] = $path;
			}
		}

		$this->view->assign(array(
			'writable' => $writable
		));
	}
}

?>
