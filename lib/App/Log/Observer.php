<?php

class App_Log_Observer extends Log_observer {
	const IDENT = __CLASS__;
	public function notify($event) {
		$log = App_Log::getInstance(self::IDENT);
		$log->debug($event['message']);
	}
}

?>
