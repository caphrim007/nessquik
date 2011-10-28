<?php

/**
* @author Tim Rupp
*/
class Table_Regex extends Zend_Db_Table_Abstract {
	protected $_name = 'regex';
	protected $_primary = 'id';
	protected $_sequence = 'regex_id_seq';
}

?>
