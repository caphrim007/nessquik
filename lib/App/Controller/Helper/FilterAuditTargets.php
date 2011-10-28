<?php

/**
* @author Tim Rupp
*/
class App_Controller_Helper_FilterAuditTargets extends Zend_Controller_Action_Helper_Abstract {
	public function direct($auditTargets, $targets) {
		if (count($targets) == 0) {
			throw new Zend_Controller_Action_Exception('You do not have permission to scan this target');
		} else {
			foreach($targets as $target) {
				switch($target['type']) {
					case 'hostname':
					case 'cluster':
					case 'range':
					case 'network':
						if (!isset($auditTargets[$target['type']])) {
							$auditTargets[$target['type']][] = $target['target'];
						} else {
							if (!in_array($target['target'], $auditTargets[$target['type']])) {
								$auditTargets[$target['type']][] = $target['target'];
							}
						}
				}
			}

			return $auditTargets;
		}
	}
}

?>
