CREATE TABLE `<<prefix>>_echo_email_batch` (
  `eeb_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `eeb_user_id` int(10) unsigned NOT NULL,
  `eeb_event_priority` tinyint(3) unsigned NOT NULL DEFAULT 10,
  `eeb_event_id` int(10) unsigned NOT NULL,
  `eeb_event_hash` varbinary(32) NOT NULL,
  PRIMARY KEY (`eeb_id`),
  UNIQUE KEY `echo_email_batch_user_event` (`eeb_user_id`,`eeb_event_id`),
  KEY `echo_email_batch_user_hash_priority` (`eeb_user_id`,`eeb_event_hash`,`eeb_event_priority`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_echo_event` (
  `event_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `event_type` varbinary(64) NOT NULL,
  `event_variant` varbinary(64) DEFAULT NULL,
  `event_agent_id` int(10) unsigned DEFAULT NULL,
  `event_agent_ip` varbinary(39) DEFAULT NULL,
  `event_extra` blob DEFAULT NULL,
  `event_page_id` int(10) unsigned DEFAULT NULL,
  `event_deleted` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`event_id`),
  KEY `echo_event_type` (`event_type`),
  KEY `echo_event_page_id` (`event_page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_echo_notification` (
  `notification_event` int(10) unsigned NOT NULL,
  `notification_user` int(10) unsigned NOT NULL,
  `notification_timestamp` binary(14) NOT NULL,
  `notification_read_timestamp` binary(14) DEFAULT NULL,
  `notification_bundle_hash` varbinary(32) NOT NULL,
  PRIMARY KEY (`notification_user`,`notification_event`),
  KEY `echo_user_timestamp` (`notification_user`,`notification_timestamp`),
  KEY `echo_notification_event` (`notification_event`),
  KEY `echo_notification_user_read_timestamp` (`notification_user`,`notification_read_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_echo_target_page` (
  `etp_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `etp_page` int(10) unsigned NOT NULL DEFAULT 0,
  `etp_event` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`etp_id`),
  KEY `echo_target_page_event` (`etp_event`),
  KEY `echo_target_page_page_event` (`etp_page`,`etp_event`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

INSERT INTO `<<prefix>>_updatelog` (`ul_key`, `ul_value`) VALUES
(UNHEX('52656D6F76654F727068616E65644576656E7473'),	NULL),
(UNHEX('5570646174654563686F536368656D61466F725375707072657373696F6E'),	NULL),
(UNHEX('6563686F5F6576656E742D6576656E745F6167656E745F69702D2F7661722F7777772F68746D6C2F657874656E73696F6E732F4563686F2F64625F706174636865732F70617463682D6576656E745F6167656E745F69702D73697A652E73716C'),	NULL),
(UNHEX('6563686F5F6576656E742D6576656E745F65787472612D2F7661722F7777772F68746D6C2F657874656E73696F6E732F4563686F2F64625F706174636865732F70617463682D6576656E745F65787472612D73697A652E73716C'),	NULL),
(UNHEX('6563686F5F6576656E742D6576656E745F76617269616E742D2F7661722F7777772F68746D6C2F657874656E73696F6E732F4563686F2F64625F706174636865732F70617463682D6576656E745F76617269616E745F6E756C6C6162696C6974792E73716C'),	NULL);
