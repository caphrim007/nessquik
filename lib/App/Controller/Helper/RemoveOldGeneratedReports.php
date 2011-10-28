<?php

/**
* @author Tim Rupp
*/
class App_Controller_Helper_RemoveOldGeneratedReports extends Zend_Controller_Action_Helper_Abstract {
	public function direct($accountId) {
		try {
			$tmpDir = sprintf('%s/tmp/priv', _ABSPATH);
			$dir = new DirectoryIterator($tmpDir);
			foreach($dir as $fileInfo) {
				if ($fileInfo->isDot()) {
					continue;
				}

				$filename = $fileInfo->getPathname();
				$file = sprintf("/%s.report", $accountId);

				if (strpos($filename, $file) !== false) {
					unlink($filename);
				}
			}

			return true;
		} catch (Exception $error) {
			return false;
		}
	}
}

?>
