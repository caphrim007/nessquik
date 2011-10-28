<?php

/**
* @author Tim Rupp
*/
class App_Controller_Helper_FilterTargetModifications extends Zend_Controller_Action_Helper_Abstract {
	public function direct($modifications, $results) {
		if (isset($modifications['add'])) {
			if (empty($modifications['add'])) {
				$modifications['add'] = array();
			}

			foreach($modifications['add'] as $type => $targets) {
				if (isset($results[$type])) {
					$results[$type] = array_merge($results[$type], $targets);
					$results[$type] = array_unique($results[$type]);
				} else {
					$results[$type] = $targets;
				}
			}
		}

		if (isset($modifications['remove'])) {
			if (empty($modifications['remove'])) {
				$modifications['remove'] = array();
			}

			foreach($modifications['remove'] as $type => $targets) {
				if (isset($results[$type])) {
					$results[$type] = array_diff($results[$type], $targets);
				}
			}
		}

		return $results;
	}
}

?>
