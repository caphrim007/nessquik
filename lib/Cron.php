<?php

/**
* @author Tim Rupp
*/
class Cron {
	public static $pid;

	protected $_date;
	protected $_bits;
	protected $_plugin;

	const IDENT = __CLASS__;

	public function __construct($schedule, $date = null, $plugin = null) {
		$schedule = trim($schedule);

		$this->setPlugin($plugin);
		$this->setDate($date);
		$this->setSchedule($schedule);
	}

	public function setDate($date = null) {
		if ($date === null) {
			$this->_date = new Zend_Date;
		} else if ($date instanceof Zend_Date) {
			$this->_date = $date;
		} else if (is_numeric($date)) {
			$this->_date = new Zend_Date($date, Zend_Date::TIMESTAMP);
		} else {
			$this->_date = new Zend_Date($date, Zend_Date::ISO_8601);
		}
	}

	public function setPlugin($plugin) {
		$this->_plugin = $plugin;
	}

	public function setSchedule($schedule) {
		switch($schedule) {
			case '@annually':
			case '@yearly':
				$this->_bits = array('0','0','1','1','*');
				break;
			case '@monthly':
				$this->_bits = array('0','0','1','*','*');
				break;
			case '@weekly':
				$this->_bits = array('0','0','*','*','0');
				break;
			case '@daily':
			case '@midnight':
				$this->_bits = array('0','0','1','1','*');
				break;
			case '@hourly':
				$this->_bits = array('0','*','*','*','*');
				break;
			default:
				$this->_bits = explode(' ', $schedule);
				break;
		}
	}

	/**
	* Returns boolean true if the current (or, if specified) date
	* matches the all the fields.
	*/
	public function schedule() {
		$log = App_Log::getInstance(self::IDENT);

		$log->debug(sprintf('Considering minute for %s', $this->_plugin));
		if (!$this->interpretMinute($this->_bits[0])) {
			$log->debug('Minute does not match current time');
			return false;
		}

		$log->debug(sprintf('Considering hour for %s', $this->_plugin));
		if (!$this->interpretHour($this->_bits[1])) {
			$log->debug('Hour does not match current time');
			return false;
		}

		$log->debug(sprintf('Considering day for %s', $this->_plugin));
		if (!$this->interpretDay($this->_bits[2])) {
			$log->debug('Day does not match current time');
			return false;
		}

		$log->debug(sprintf('Considering month for %s', $this->_plugin));
		if (!$this->interpretMonth($this->_bits[3])) {
			$log->debug('Month does not match current time');
			return false;
		}

		$log->debug(sprintf('Considering day of week for %s', $this->_plugin));
		if (!$this->interpretDayOfWeek($this->_bits[4])) {
			$log->debug('Day of week does not match current time');
			return false;
		}

		return true;
	}

	protected function interpretMinute($bit) {
		if ($bit == '*') {
			return true;
		} else if (strpos($bit, ',')) {
			$tmp = explode(',', $bit);
			foreach($tmp as $item) {
				if ($this->_date->get(Zend_Date::MINUTE_SHORT) == $item) {
					return true;
				}
			}
		} else if (strpos($bit, '/')) {
			$tmp = explode('/', $bit);
			if (($this->_date->get(Zend_Date::MINUTE_SHORT) % $tmp[1]) == 0) {
				return true;
			}
		} else if (strpos($bit, '-')) {
			list($low, $high) = explode('-', $bit);
			$tmp = range($low, $high);
			foreach($tmp as $item) {
				if ($this->_date->get(Zend_Date::MINUTE_SHORT) == $item) {
					return true;
				}
			}
		} else if ($this->_date->get(Zend_Date::MINUTE_SHORT) == $bit) {
			return true;
		}

		return false;
	}

	protected function interpretHour($bit) {
		if ($bit == '*') {
			return true;
		} else if (strpos($bit, ',')) {
			$tmp = explode(',', $bit);
			foreach($tmp as $item) {
				if ($this->_date->get(Zend_Date::HOUR_SHORT) == $item) {
					return true;
				}
			}
		} else if (strpos($bit, '/')) {
			$tmp = explode('/', $bit);
			if (($this->_date->get(Zend_Date::HOUR_SHORT) % $tmp[1]) == 0) {
				return true;
			}
		} else if (strpos($bit, '-')) {
			list($low, $high) = explode('-', $bit);
			$tmp = range($low, $high);
			foreach($tmp as $item) {
				if ($this->_date->get(Zend_Date::HOUR_SHORT) == $item) {
					return true;
				}
			}
		} else if ($this->_date->get(Zend_Date::HOUR_SHORT) == $bit) {
			return true;
		}

		return false;
	}

	protected function interpretDay($bit) {
		if ($bit == '*') {
			return true;
		} else if (strpos($bit, ',')) {
			$tmp = explode(',', $bit);
			foreach($tmp as $item) {
				if ($this->_date->get(Zend_Date::DAY) == $item) {
					return true;
				}
			}
		} else if (strpos($bit, '/')) {
			$tmp = explode('/', $bit);
			if (($this->_date->get(Zend_Date::DAY) % $tmp[1]) == 0) {
				return true;
			}
		} else if (strpos($bit, '-')) {
			list($low, $high) = explode('-', $bit);
			$tmp = range($low, $high);
			foreach($tmp as $item) {
				if ($this->_date->get(Zend_Date::DAY) == $item) {
					return true;
				}
			}
		} else if ($this->_date->get(Zend_Date::DAY) == $bit) {
			return true;
		}

		return false;
	}

	protected function interpretMonth($bit) {
		if ($bit == '*') {
			return true;
		} else if (strpos($bit, ',')) {
			$tmp = explode(',', $bit);
			foreach($tmp as $item) {
				if ($this->_date->get(Zend_Date::MONTH_SHORT) == $item) {
					return true;
				}
			}
		} else if (strpos($bit, '/')) {
			$tmp = explode('/', $bit);
			if (($this->_date->get(Zend_Date::MONTH_SHORT) % $tmp[1]) == 0) {
				return true;
			}
		} else if (strpos($bit, '-')) {
			list($low, $high) = explode('-', $bit);
			$tmp = range($low, $high);
			foreach($tmp as $item) {
				if ($this->_date->get(Zend_Date::MONTH_SHORT) == $item) {
					return true;
				}
			}
		} else if ($this->_date->get(Zend_Date::MONTH_SHORT) == $bit) {
			return true;
		}

		return false;
	}

	protected function interpretDayOfWeek($bit) {
		if ($bit == '*') {
			return true;
		} else if (strpos($bit, ',')) {
			$tmp = explode(',', $bit);
			foreach($tmp as $item) {
				if ($this->_date->get(Zend_Date::WEEKDAY_DIGIT) == $item) {
					return true;
				}
			}
		} else if (strpos($bit, '/')) {
			$tmp = explode('/', $bit);
			if (($this->_date->get(Zend_Date::WEEKDAY_DIGIT) % $tmp[1]) == 0) {
				return true;
			}
		} else if (strpos($bit, '-')) {
			list($low, $high) = explode('-', $bit);
			$tmp = range($low, $high);
			foreach($tmp as $item) {
				if ($this->_date->get(Zend_Date::WEEKDAY_DIGIT) == $item) {
					return true;
				}
			}
		} else if ($this->_date->get(Zend_Date::WEEKDAY_DIGIT) == $bit) {
			return true;
		}

		return false;
	}

	public static function isRunning() {
		$pids = explode(PHP_EOL, `ps -e | awk '{print $1}'`);
		if(in_array(self::$pid, $pids)) {
			return true;
		} else {
			return false;
		}
	}

	public static function lock($file = 'maintenance', $suffix = null) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		if ($suffix === null) {
			if ($config->cron->lock->suffix == '') {
				$suffix = 'lock';
			} else {
				$suffix = $config->cron->lock->suffix;
			}
		}

		$lock_file = sprintf("%s/%s.%s", $config->cron->lock->dir, $file, $suffix);

		if(file_exists($lock_file)) {
			// Is running?
			self::$pid = file_get_contents($lock_file);
			if(self::isrunning()) {
				$log->debug(sprintf('%s Already in progress.', self::$pid));
				return false;
			} else {
				$log->debug(sprintf('%s Previous job died abruptly.', self::$pid));
				$log->debug('Deleting old lock file');
				unlink($lock_file);
			}
		}

		self::$pid = getmypid();
		file_put_contents($lock_file, self::$pid);
		$log->debug(sprintf('%s Lock acquired, processing the job.', self::$pid));
		return self::$pid;
	}

	public static function unlock($file = 'maintenance', $suffix = null) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		if ($suffix === null) {
			if ($config->cron->lock->suffix == '') {
				$suffix = 'lock';
			} else {
				$suffix = $config->cron->lock->suffix;
			}
		}

		$lock_file = sprintf("%s/%s.%s", $config->cron->lock->dir, $file, $suffix);

		if(file_exists($lock_file)) {
			unlink($lock_file);
		}

		$log->debug(sprintf('%s Releasing lock.', self::$pid));
		return true;
	}
}

?>
