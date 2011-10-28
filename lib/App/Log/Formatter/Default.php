<?php

/**
* @author Tim Rupp
*/
class App_Log_Formatter_Default implements Zend_Log_Formatter_Interface {
	/**
	* @var string
	*/
	protected $_ident;

	// Aug 15 09:08:51 Accounts [debug] Checking to see if captive accounts dump exists
	const DEFAULT_FORMAT = '%timestamp% %ident% [%priorityName%] %message%';
	const DEFAULT_IDENT = 'App';

	/**
	* Class constructor
	*
	* @throws Zend_Log_Exception
	*/
	public function __construct($ident = null) {
		if ($ident === null) {
			$ident = self::DEFAULT_IDENT;
		}

		if (! is_string($ident)) {
			throw new Zend_Log_Exception('Ident must be a string');
		}

		$this->_ident = $ident;
	}

	/**
	* Formats data into a single line to be written by the writer.
	*
	* @param  array    $event    event data
	* @return string
	*/
	public function format($event) {
		$output = self::DEFAULT_FORMAT . PHP_EOL;
		$event['ident'] = $this->_ident;

		foreach ($event as $name => $value) {
			switch($name) {
				case 'priorityName':
					$value = strtolower($value);
					break;
				case 'message':
					$value = $this->removeWhitespace($value);
					break;
				default:
					break;
			}

			$output = str_replace("%$name%", $value, $output);
		}

		return $output;
	}

	/**
	* Formats data into a single line to be written by the writer.
	*
	* Returns the string $value, stripping whitespace from the strings.
	* This includes strings like XML that may be many lines
	*
	* @param  array    $event    event data
	* @return string             formatted line to write to the log
	*/
	public function removeWhitespace($message) {
		$message = str_replace(array('\r\n',"\r\n",'\r','\n', "\n", '\t',"\t"), ' ', $message);
		$message = stripslashes($message);
		$message = preg_quote($message, '|');
		$message = preg_replace('|  +|', ' ', $message);

		// replaces whitespace between XML tags
		$message = preg_replace('/\\\>\s+\\\</', '><', $message);

		$message = trim($message);

		// preg_quote adds slashes back. This removes them
		$message = stripslashes($message);

		return $message;
	}
}

?>
