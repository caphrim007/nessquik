<?php

/**
* @author Tim Rupp
*/
class Queue_Adapter_Nessquik extends Zend_Queue_Adapter_Db {
	/**
	* Send a message to the queue
	*
	* @param  string     $message Message to send to the active queue
	* @param  Zend_Queue $queue
	* @return Zend_Queue_Message
	* @throws Zend_Queue_Exception - database error
	*/
	public function send($message, Zend_Queue $queue = null) {
		if ($queue === null) {
			$queue = $this->_queue;
		}

		if (is_scalar($message)) {
			$message = (string) $message;
		}

		if (is_string($message)) {
			$message = trim($message);
		}

		if (!$this->isExists($queue->getName())) {
			throw new Zend_Queue_Exception('Queue does not exist:' . $queue->getName());
		}

		$created = new Zend_Date;
		$msg           = $this->_messageTable->createRow();
		$msg->queue_id = $this->getQueueId($queue->getName());
		$msg->created  = $created->get(Zend_Date::W3C);
		$msg->body     = $message;
		$msg->md5      = md5($message);

		try {
			$msg->save();
		} catch (Exception $error) {
			throw new Zend_Queue_Exception($error->getMessage(), $error->getCode());
		}

		$options = array(
			'queue' => $queue,
			'data'  => $msg->toArray(),
		);

		$classname = $queue->getMessageClass();
		if (!class_exists($classname)) {
			Zend_Loader::loadClass($classname);
		}

		return new $classname($options);
	}
}

?>
