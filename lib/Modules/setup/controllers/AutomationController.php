<?php

/**
* @author Tim Rupp
*/
class Setup_AutomationController extends Zend_Controller_Action {
	const IDENT = __CLASS__;

	public function init() {
		parent::init();

		$request = $this->getRequest();
		$config = Ini_Config::getInstance();

		if ($config->misc->firstboot == 0) {
			$redirector = $this->_helper->getHelper('Redirector');
			$redirector->gotoSimple('index', 'index', 'default');
		}

		$this->view->assign(array(
			'action' => $request->getActionName(),
			'config' => $config,
			'controller' => $request->getControllerName(),
			'module' => $request->getModuleName()
		));
	}

	public function indexAction() {
		$count = 0;
		$dir = new DirectoryIterator(_ABSPATH.'/opt/setup/automation/');
		foreach($dir as $accounts ) {
			$count++;
		}

		if ($count == 0) {
			$redirector = $this->_helper->getHelper('Redirector');
			$redirector->gotoUrl('/setup/authentication');
			exit;
		}
	}

	public function createAction() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$permissions = new Permissions;
		$results = array();
		$addAutomations = array();

		try {
			$dir = new DirectoryIterator(_ABSPATH.'/opt/setup/automation/');
			foreach($dir as $automation ) {
				if(!$automation->isDot()) {
					$ini = new Zend_Config_Ini($automation->getPathname());
					$data = $ini->toArray();

					if (empty($data['auto'])) {
						$log->info('No automations were found');
						continue;
					}

					foreach($data['auto'] as $regexInfo) {
						$addAutomations = array();
						$updateData = array();
						$id = null;
						$perms = array();

						if (Regex_Util::exists($regexInfo['pattern'], $regexInfo['application'])) {
							$log->info('The specified automation already exists in the database');
						} else {
							$id = Regex_Util::create();
							$regex = new Regex($id);

							$updateData = array(
								'pattern' => $regexInfo['pattern'],
								'application' => $regexInfo['application'],
								'type' => $regexInfo['type'],
								'desc' => $regexInfo['description']
							);

							$regex->update($updateData);

							switch($regexInfo['type']) {
								case 'tag':
									if ($regexInfo['tags'] == 'all') {
										$tags = Tag_Util::get(1,0);
										foreach($tags as $tagId) {
											$tagName = Tag_Util::getName($tagId);
											$perms = $permissions->getPattern('Tag', $tagName, null, 1, 0);
											$addAutomations[] = $perms[0]['permission_id'];
										}
									} else if (is_array($regexInfo['tags'])) {
										foreach($regexInfo['tags'] as $tag) {
											if (is_numeric($tag)) {
												$tag = Tag_Util::getName($tag);
												$perms = $permissions->getPattern('Tag', $tag, null, 1, 0);
												$addAutomations[] = $perms[0]['permission_id'];
											} else {
												$perms = $permissions->getPattern('Tag', $tag, null, 1, 0);
												$addAutomations[] = $perms[0]['permission_id'];
											}
										}
									} else if (is_numeric($regexInfo['tags'])) {
										$tagName = Tag_Util::getName($regexInfo['tags']);
										$perms = $permissions->getPattern('Tag', $tagName, null, 1, 0);
										$addAutomations[] = $perms[0]['permission_id'];
									} else {
										$perms = $permissions->getPattern('Tag', $regexInfo['tags'], null, 1, 0);
										$addAutomations[] = $perms[0]['permission_id'];
									}
									break;
								case 'queue':
									if ($regexInfo['queues'] == 'all') {
										$queues = Queue_Util::get(1,0);
										foreach($queues as $queue) {
											$perms = $permissions->getPattern('Queue', $queue['queue_name'], null, 1, 0);
											$addAutomations[] = $perms[0]['permission_id'];
										}
									} else if (is_array($regexInfo['queues'])) {
										foreach($regexInfo['queues'] as $queue) {
											if (is_numeric($queue)) {
												$queueName = Queue_Util::getName($regexInfo['queues']);
												$perms = $permissions->getPattern('Queue', $queueName, null, 1, 0);
												$addAutomations[] = $perms[0]['permission_id'];
											} else {
												$perms = $permissions->getPattern('Queue', $queue, null, 1, 0);
												$addAutomations[] = $perms[0]['permission_id'];
											}
										}
									} else if (is_numeric($regexInfo['queues'])) {
										$queueName = Queue_Util::getName($regexInfo['queues']);
										$perms = $permissions->getPattern('Queue', $queueName, null, 1, 0);
										$addAutomations[] = $perms[0]['permission_id'];
									} else {
										$perms = $permissions->getPattern('Queue', $regexInfo['queues'], null, 1, 0);
										$addAutomations[] = $perms[0]['permission_id'];
									}
									break;
							}

							if (!empty($addAutomations)) {
								foreach($addAutomations as $automation) {
									$regex->addAutomation($automation);
								}
							}

							$results[] = $regex;
						}
					}
				}
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
		}

		$this->view->regex = $results;
	}
}

?>
