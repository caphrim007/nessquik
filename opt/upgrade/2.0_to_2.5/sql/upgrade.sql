CREATE TABLE `division_group_list` (
	`group_id` int(11) NOT NULL auto_increment,
	`group_name` varchar(255) default NULL,
	PRIMARY KEY  (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `special_plugin_profile` (
	`profile_id` int(11) NOT NULL auto_increment,
	`profile_name` char(128) default NULL,
	PRIMARY KEY  (`profile_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `special_plugin_profile_items` (
	`row_id` int(11) NOT NULL auto_increment,
	`profile_id` int(11) default NULL,
	`plugin_type` char(3) default NULL,
	`plugin` varchar(255) default NULL,
	PRIMARY KEY  (`row_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `special_plugin_profile_groups` (
	`row_id` int(11) NOT NULL auto_increment,
	`group_id` int(11) default NULL,
	`profile_id` int(11) default NULL,
	PRIMARY KEY  (`row_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `scanners` (
	`scanner_id` int(11) NOT NULL auto_increment,
	`name` varchar(255) default NULL,
	`client_key` varchar(32) default NULL,
	`privileged` enum('0','1') default '0',
	`online` enum('0','1') default '0',
	PRIMARY KEY  (`scanner_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `metrics` (
	`metric_id` int(11) NOT NULL auto_increment,
	`type` varchar(32) default NULL,
	`name` varchar(255) default NULL,
	`display_name` varchar(255) default NULL,
	`description` varchar(255) NOT NULL default '',
	PRIMARY KEY  (`metric_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `scanners_groups` (
	`row_id` int(11) NOT NULL auto_increment,
	`group_id` int(11) default NULL,
	`scanner_id` int(11) default NULL,
	PRIMARY KEY  (`row_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `help` (
	`help_id` int(11) NOT NULL auto_increment,
	`category_id` int(11) default NULL,
	`question` longtext,
	`answer` longtext,
	PRIMARY KEY  (`help_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `help_categories` (
	`category_id` int(11) NOT NULL auto_increment,
	`type` enum('A','G') default 'G',
	`category` varchar(255) default NULL,
	PRIMARY KEY  (`category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `historic_saved_scan_results` (
	`results_id` int(11) NOT NULL auto_increment,
	`profile_id` varchar(32) NOT NULL default '',
	`username` varchar(32) default NULL,
	`saved_on` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
	`scan_results` longtext,
	PRIMARY KEY  (`results_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- The following SQL MUST be run in the order listed
ALTER TABLE profile_list DROP COLUMN parent_profile_id;
ALTER TABLE profile_list ADD COLUMN cancel enum('N','Y') default 'N' AFTER status;
ALTER TABLE profile_list DROP COLUMN save_the_scan;
ALTER TABLE user_settings DROP COLUMN tmp_email_list;
ALTER TABLE user_settings DROP COLUMN tmp_cgibin_list;
ALTER TABLE user_settings DROP COLUMN cumulative_report;
ALTER TABLE user_settings CHANGE COLUMN report_format report_format char(8) default 'txt';
UPDATE user_settings SET report_format='txt' WHERE report_format='0';
UPDATE user_settings SET report_format='html' WHERE report_format='1';
ALTER TABLE user_settings CHANGE COLUMN report_format report_format enum('txt','html','nbe') default 'txt';
ALTER TABLE user_settings ADD COLUMN scanner_id int(11) default NULL;
ALTER TABLE user_settings CHANGE COLUMN save_scan_report save_scan_report enum('0','1') default '1';
ALTER TABLE saved_scan_results CHANGE COLUMN scan_results scan_results longtext;
ALTER TABLE scan_progress CHANGE COLUMN percent_done attack_percent int(11) default '0';
ALTER TABLE scan_progress ADD COLUMN portscan_percent int(11) default '0' AFTER profile_id;
ALTER TABLE recurrence DROP COLUMN username;
ALTER TABLE recurrence CHANGE COLUMN recur_type recur_type enum('D','W','M') default 'W';
ALTER TABLE saved_scan_results DROP COLUMN username;

RENAME TABLE user_settings TO profile_settings;
DROP TABLE scans;
