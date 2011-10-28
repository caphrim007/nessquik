<?php

// Used for including files
if (!defined("_ABSPATH")) {
	define("_ABSPATH", dirname(dirname(dirname(dirname(__FILE__)))));
}

require _ABSPATH.'/lib/Autoload.php';

class Standards_JsLintTest extends PHPUnit_Framework_TestCase {
	/**
	* Runs the test methods of this class.
	*/
	public static function main() {
		require_once "PHPUnit/TextUI/TestRunner.php";

		$suite  = new PHPUnit_Framework_TestSuite("Standards_JsLintTest");
		$result = PHPUnit_TextUI_TestRunner::run($suite);
	}

	protected function setUp() {

	}

	protected function tearDown() {

	}

	/**
	* @dataProvider providerJsIncludes
	*/
	public function testLintScriptFiles($jsFile) {
		$config = Ini_Config::getInstance();
		$javaBin = $config->java->path;

		$command = sprintf('%s -jar %s/opt/rhino/js.jar %s/opt/jslint.js %s', $javaBin, _ABSPATH, _ABSPATH, $jsFile);
		exec($command, $output, $returnVar);
		$this->assertContains('jslint: No problems found in', $output[0]);
	}

	public function providerJsIncludes() {
		$result = array();
		$toTest = array(
			'account',
			'admin',
			'audit',
			'config',
			'dashboard',
			'default',
			'metrx',
			'policy',
			'queues',
			'roles',
			'scanners',
			'settings',
			'setup',
			'site',
			'site-audit'
		);

		$jsIncludePath = sprintf('%s/usr/html/javascript', _ABSPATH);

		$dir = new RecursiveDirectoryIterator($jsIncludePath);
		foreach(new RecursiveIteratorIterator($dir) as $file ) {
			$filename = $file->getFilename();

			if ($file->isDir()) {
				continue;
			}

			$extension = substr($filename, -3);

			if ($extension == '.js') {
				foreach($toTest as $module) {
					$output = null;
					$jsDir = sprintf('%s/%s', $jsIncludePath, $module);

					if (strpos($file->getPathname(), $jsDir) !== false) {
						$result[] = array($file->getPathname());
					}
				}
			}
		}

		return $result;
	}
}

if (PHPUnit_MAIN_METHOD == "Standards_JsLintTest::main") {
	Standards_JsLintTest::main();
}

?>
