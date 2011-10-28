<?php

/**
* @author Tim Rupp
*/
class App_Controller_Helper_RandomPassword extends Zend_Controller_Action_Helper_Abstract {
	protected $_characters = array();
	protected $_noSim = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrsuvwxyz23456789~!@#$%^&*_+=-?:';
	protected $_similar = '0olt01';

	public function direct($length = 8, $quantity = 1, $options = array()) {
		$results = array();

		if (empty($options)) {
			$this->_includeLetters();
			$this->_includeMixedCase();
			$this->_includeNumbers();
			$this->_noSimilarCharacters();
		} else {
			foreach($options as $option) {
				switch($option) {
					case 'letters':
						$this->_includeLetters();
						break;
					case 'mixedcase':
						$this->_includeMixedCase();
						break;
					case 'numbers':
						$this->_includeNumbers();
						break;
					case 'punct':
						$this->_includePunctuation();
						break;
					case 'all':
						$this->_includeLetters();
						$this->_includeMixedCase();
						$this->_includeNumbers();
						$this->_includePunctuation();
						$this->_noSimilarCharacters();
						break;
				}
			}
		}

		$chars = array_unique($this->_characters);

		if (!empty($options)) {
			if (array_search('nosim', $options) !== false) {
				$this->_noSimilarCharacters();
			}
		}

		$chars = array_unique($this->_characters);
		$chars = array_values($chars);
		$max = count($chars) - 1;

		$i = 0;
		$j = 0;

		while($j < $quantity) {
			$pass = '' ;

			while ($i <= $length) {
				$num = mt_rand(0, $max);
				$pass = $pass . $chars[$num];
				$i++;
			}

			$results[] = $pass;
			$i = 0;
			$j++;
		}

		return $results;
	}

	protected function _includeLetters() {
		$chars = 'abcdefghijklmnopqrstuvwxyz';
		$this->_characters = array_merge($this->_characters, str_split($chars));
	}

	protected function _includeMixedCase() {
		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
		$this->_characters = array_merge($this->_characters, str_split($chars));
	}

	protected function _includeNumbers() {
		$chars = '0123456789';
		$this->_characters = array_merge($this->_characters, str_split($chars));
	}

	protected function _includePunctuation() {
		// I'm deliberately not including all possible punctuation here
		$chars = '~!@#$%^&*_+=-?:';
		$this->_characters = array_merge($this->_characters, str_split($chars));
	}

	protected function _noSimilarCharacters() {
		$similar = str_split($this->_similar);
		$noSim = str_split($this->_noSim);
		$max = count($noSim) - 1;

		foreach($this->_characters as $key => $char) {
			if (in_array($char, $similar)) {
				$num = mt_rand(0, $max);
				$this->_characters[$key] = $noSim[$num];
			}
		}
	}
}

?>
