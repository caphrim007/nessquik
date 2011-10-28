<?php
/**
 * Simple gravatar view helper
 *
 * @package
 * @category
 * @author      Dave Marshall <dave.marshall@atstsolutions.co.uk>
 * @author      $Author: $
 * @version     $Rev: $
 * @since       $Date: $
 * @link        $URL: $
 */
class App_View_Helper_Gravatar extends Zend_View_Helper_Abstract {
	protected $_url = 'http://www.gravatar.com/avatar.php';

	public function gravatar($email, $rating = 'G', $size = 48) {
		$params = array(
			'gravatar_id' => md5(strtolower($email)),
			'rating'      => $rating,
			'size'        => $size,
		);

		return $this->_url . '?' . http_build_query($params, '', '&amp;');
	}
}

?>
