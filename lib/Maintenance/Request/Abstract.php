<?php

/**
* @author Tim Rupp
*/
abstract class Maintenance_Request_Abstract {
	/**
	* Message content
	*
	* @var array
	*/
	protected $_messages = array();

	/**
	* Set messages content
	*
	* If $name is not passed, or is not a string, resets the entire body and
	* sets the 'default' key to $content.
	*
	* If $name is a string, sets the named segment in the body array to
	* $content.
	*
	* @param string $content
	* @return Maintenance_Request_Abstract
	*/
	public function setMessages($content) {
		if (is_array($content)) {
			$this->_messages = $content;
		} else {
			$this->appendMessage($content);
		}

		return $this;
	}

	/**
	* Append content to the messages content
	*
	* @param string $content
	* @param null|string $name
	* @return Maintenance_Request_Abstract
	*/
	public function appendMessage($content) {
		if (is_array($content)) {
			foreach($content as $key => $val) {
				$this->appendMessage($val);
			}
		} else {
			$this->_messages[] = $content;
		}

		return $this;
	}

	/**
	* Clear messages array
	*
	* @param  string $name Named segment to clear
	* @return boolean
	*/
	public function clearMessages() {
		$this->_messages = array();
		return true;
	}

	/**
	* Return the messages content
	*
	* @param boolean $spec
	* @return string|array|null
	*/
	public function getMessages() {
		return $this->_messages;
	}
}

?>
