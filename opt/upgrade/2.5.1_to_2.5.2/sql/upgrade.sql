CREATE TABLE IF NOT EXISTS `accounts` (
  `id` int(11) NOT NULL auto_increment,
  `username` varchar(255) default NULL,
  `password` varchar(32) default NULL,
  `auth_type` enum('pass','ldap','kca') default 'pass',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `accounts_roles` (
  `id` int(11) NOT NULL auto_increment,
  `account_id` int(11) default NULL,
  `role_id` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

ALTER TABLE `profile_list` ADD COLUMN account_id int(11) AFTER profile_id;
ALTER TABLE `profile_settings` ADD COLUMN account_id int(11) AFTER setting_id;
ALTER TABLE `saved_scan_results` ADD COLUMN account_id int(11) AFTER profile_id;
ALTER TABLE `whitelist` ADD COLUMN account_id int(11) AFTER whitelist_id;

ALTER TABLE `scanners` ADD COLUMN type enum('N','NP','S','P') DEFAULT 'N';
ALTER TABLE `recurrence` DROP COLUMN recurrence_id;

ALTER TABLE `recurrence` ADD COLUMN enabled enum('0','1') DEFAULT '0';
UPDATE recurrence SET enabled='1';

ALTER TABLE profile_list DROP COLUMN account_id;
