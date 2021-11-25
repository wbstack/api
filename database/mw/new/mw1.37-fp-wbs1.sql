-- Adminer 4.6.3 MySQL dump

CREATE TABLE `<<prefix>>_account_credentials` (
  `acd_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `acd_user_id` int(10) unsigned NOT NULL,
  `acd_real_name` varbinary(255) NOT NULL DEFAULT '',
  `acd_email` tinyblob NOT NULL,
  `acd_email_authenticated` varbinary(14) DEFAULT NULL,
  `acd_bio` mediumblob NOT NULL,
  `acd_notes` mediumblob NOT NULL,
  `acd_urls` mediumblob NOT NULL,
  `acd_ip` varbinary(255) DEFAULT '',
  `acd_xff` varbinary(255) DEFAULT '',
  `acd_agent` varbinary(255) DEFAULT '',
  `acd_filename` varbinary(255) DEFAULT NULL,
  `acd_storage_key` varbinary(64) DEFAULT NULL,
  `acd_areas` mediumblob NOT NULL,
  `acd_registration` varbinary(14) NOT NULL,
  `acd_accepted` varbinary(14) DEFAULT NULL,
  `acd_user` int(10) unsigned NOT NULL DEFAULT 0,
  `acd_comment` varbinary(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`acd_id`),
  UNIQUE KEY `acd_user_id` (`acd_user_id`,`acd_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_account_requests` (
  `acr_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `acr_name` varbinary(255) NOT NULL DEFAULT '',
  `acr_real_name` varbinary(255) NOT NULL DEFAULT '',
  `acr_email` varbinary(255) NOT NULL,
  `acr_email_authenticated` varbinary(14) DEFAULT NULL,
  `acr_email_token` binary(32) DEFAULT NULL,
  `acr_email_token_expires` varbinary(14) DEFAULT NULL,
  `acr_bio` mediumblob NOT NULL,
  `acr_notes` mediumblob NOT NULL,
  `acr_urls` mediumblob NOT NULL,
  `acr_ip` varbinary(255) DEFAULT '',
  `acr_xff` varbinary(255) DEFAULT '',
  `acr_agent` varbinary(255) DEFAULT '',
  `acr_filename` varbinary(255) DEFAULT NULL,
  `acr_storage_key` varbinary(64) DEFAULT NULL,
  `acr_type` tinyint(255) unsigned NOT NULL DEFAULT 0,
  `acr_areas` mediumblob NOT NULL,
  `acr_registration` varbinary(14) NOT NULL,
  `acr_deleted` tinyint(1) NOT NULL,
  `acr_rejected` varbinary(14) DEFAULT NULL,
  `acr_held` varbinary(14) DEFAULT NULL,
  `acr_user` int(10) unsigned NOT NULL DEFAULT 0,
  `acr_comment` varbinary(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`acr_id`),
  UNIQUE KEY `acr_name` (`acr_name`),
  KEY `acr_email` (`acr_email`),
  KEY `acr_email_token` (`acr_email_token`),
  KEY `acr_type_del_reg` (`acr_type`,`acr_deleted`,`acr_registration`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_actor` (
  `actor_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `actor_user` int(10) unsigned DEFAULT NULL,
  `actor_name` varbinary(255) NOT NULL,
  PRIMARY KEY (`actor_id`),
  UNIQUE KEY `actor_name` (`actor_name`),
  UNIQUE KEY `actor_user` (`actor_user`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

INSERT INTO `<<prefix>>_actor` (`actor_id`, `actor_user`, `actor_name`) VALUES
(1,	1,	UNHEX('41646D696E4E616D65')),
(2,	2,	UNHEX('4D6564696157696B692064656661756C74'));

CREATE TABLE `<<prefix>>_archive` (
  `ar_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ar_namespace` int(11) NOT NULL DEFAULT 0,
  `ar_title` varbinary(255) NOT NULL DEFAULT '',
  `ar_comment_id` bigint(20) unsigned NOT NULL,
  `ar_actor` bigint(20) unsigned NOT NULL,
  `ar_timestamp` binary(14) NOT NULL,
  `ar_minor_edit` tinyint(4) NOT NULL DEFAULT 0,
  `ar_rev_id` int(10) unsigned NOT NULL,
  `ar_deleted` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `ar_len` int(10) unsigned DEFAULT NULL,
  `ar_page_id` int(10) unsigned DEFAULT NULL,
  `ar_parent_id` int(10) unsigned DEFAULT NULL,
  `ar_sha1` varbinary(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`ar_id`),
  UNIQUE KEY `ar_revid_uniq` (`ar_rev_id`),
  KEY `ar_name_title_timestamp` (`ar_namespace`,`ar_title`,`ar_timestamp`),
  KEY `ar_actor_timestamp` (`ar_actor`,`ar_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_bot_passwords` (
  `bp_user` int(10) unsigned NOT NULL,
  `bp_app_id` varbinary(32) NOT NULL,
  `bp_password` tinyblob NOT NULL,
  `bp_token` binary(32) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `bp_restrictions` blob NOT NULL,
  `bp_grants` blob NOT NULL,
  PRIMARY KEY (`bp_user`,`bp_app_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_category` (
  `cat_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cat_title` varbinary(255) NOT NULL,
  `cat_pages` int(11) NOT NULL DEFAULT 0,
  `cat_subcats` int(11) NOT NULL DEFAULT 0,
  `cat_files` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`cat_id`),
  UNIQUE KEY `cat_title` (`cat_title`),
  KEY `cat_pages` (`cat_pages`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_categorylinks` (
  `cl_from` int(10) unsigned NOT NULL DEFAULT 0,
  `cl_to` varbinary(255) NOT NULL DEFAULT '',
  `cl_sortkey` varbinary(230) NOT NULL DEFAULT '',
  `cl_sortkey_prefix` varbinary(255) NOT NULL DEFAULT '',
  `cl_timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cl_collation` varbinary(32) NOT NULL DEFAULT '',
  `cl_type` enum('page','subcat','file') NOT NULL DEFAULT 'page',
  PRIMARY KEY (`cl_from`,`cl_to`),
  KEY `cl_sortkey` (`cl_to`,`cl_type`,`cl_sortkey`,`cl_from`),
  KEY `cl_timestamp` (`cl_to`,`cl_timestamp`),
  KEY `cl_collation_ext` (`cl_collation`,`cl_to`,`cl_type`,`cl_from`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_change_tag` (
  `ct_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ct_rc_id` int(10) unsigned DEFAULT NULL,
  `ct_log_id` int(10) unsigned DEFAULT NULL,
  `ct_rev_id` int(10) unsigned DEFAULT NULL,
  `ct_params` blob DEFAULT NULL,
  `ct_tag_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`ct_id`),
  UNIQUE KEY `ct_rc_tag_id` (`ct_rc_id`,`ct_tag_id`),
  UNIQUE KEY `ct_log_tag_id` (`ct_log_id`,`ct_tag_id`),
  UNIQUE KEY `ct_rev_tag_id` (`ct_rev_id`,`ct_tag_id`),
  KEY `ct_tag_id_id` (`ct_tag_id`,`ct_rc_id`,`ct_rev_id`,`ct_log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_change_tag_def` (
  `ctd_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ctd_name` varbinary(255) NOT NULL,
  `ctd_user_defined` tinyint(1) NOT NULL,
  `ctd_count` bigint(20) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`ctd_id`),
  UNIQUE KEY `ctd_name` (`ctd_name`),
  KEY `ctd_count` (`ctd_count`),
  KEY `ctd_user_defined` (`ctd_user_defined`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_comment` (
  `comment_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_hash` int(11) NOT NULL,
  `comment_text` blob NOT NULL,
  `comment_data` blob DEFAULT NULL,
  PRIMARY KEY (`comment_id`),
  KEY `comment_hash` (`comment_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

INSERT INTO `<<prefix>>_comment` (`comment_id`, `comment_hash`, `comment_text`, `comment_data`) VALUES
(1,	0,	'',	NULL);

CREATE TABLE `<<prefix>>_content` (
  `content_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `content_size` int(10) unsigned NOT NULL,
  `content_sha1` varbinary(32) NOT NULL,
  `content_model` smallint(5) unsigned NOT NULL,
  `content_address` varbinary(255) NOT NULL,
  PRIMARY KEY (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

INSERT INTO `<<prefix>>_content` (`content_id`, `content_size`, `content_sha1`, `content_model`, `content_address`) VALUES
(1,	755,	UNHEX('3232767A357A6C7861327A63746577696D61756D3262663164756538686B6C'),	1,	UNHEX('74743A31'));

CREATE TABLE `<<prefix>>_content_models` (
  `model_id` int(11) NOT NULL AUTO_INCREMENT,
  `model_name` varbinary(64) NOT NULL,
  PRIMARY KEY (`model_id`),
  UNIQUE KEY `model_name` (`model_name`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

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

CREATE TABLE `<<prefix>>_echo_push_provider` (
  `epp_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `epp_name` tinyblob NOT NULL,
  PRIMARY KEY (`epp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_echo_push_subscription` (
  `eps_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `eps_user` int(10) unsigned NOT NULL,
  `eps_token` blob NOT NULL,
  `eps_token_sha256` binary(64) NOT NULL,
  `eps_provider` tinyint(3) unsigned NOT NULL,
  `eps_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `eps_data` blob DEFAULT NULL,
  `eps_topic` tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (`eps_id`),
  UNIQUE KEY `eps_token_sha256` (`eps_token_sha256`),
  KEY `eps_provider` (`eps_provider`),
  KEY `eps_topic` (`eps_topic`),
  KEY `echo_push_subscription_user_id` (`eps_user`),
  KEY `echo_push_subscription_token` (`eps_token`(10)),
  CONSTRAINT `echo_push_subscription_ibfk_1` FOREIGN KEY (`eps_provider`) REFERENCES `<<prefix>>_echo_push_provider` (`epp_id`),
  CONSTRAINT `echo_push_subscription_ibfk_2` FOREIGN KEY (`eps_topic`) REFERENCES `<<prefix>>_echo_push_topic` (`ept_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_echo_push_topic` (
  `ept_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `ept_text` tinyblob NOT NULL,
  PRIMARY KEY (`ept_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_echo_target_page` (
  `etp_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `etp_page` int(10) unsigned NOT NULL DEFAULT 0,
  `etp_event` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`etp_id`),
  KEY `echo_target_page_event` (`etp_event`),
  KEY `echo_target_page_page_event` (`etp_page`,`etp_event`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_entityschema_id_counter` (
  `id_value` int(10) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_externallinks` (
  `el_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `el_from` int(10) unsigned NOT NULL DEFAULT 0,
  `el_to` blob NOT NULL,
  `el_index` blob NOT NULL,
  `el_index_60` varbinary(60) NOT NULL,
  PRIMARY KEY (`el_id`),
  KEY `el_from` (`el_from`,`el_to`(40)),
  KEY `el_to` (`el_to`(60),`el_from`),
  KEY `el_index` (`el_index`(60)),
  KEY `el_index_60` (`el_index_60`,`el_id`),
  KEY `el_from_index_60` (`el_from`,`el_index_60`,`el_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

INSERT INTO `<<prefix>>_externallinks` (`el_id`, `el_from`, `el_to`, `el_index`, `el_index_60`) VALUES
(1,	1,	'https://www.mediawiki.org/wiki/Special:MyLanguage/Help:Contents',	'https://org.mediawiki.www./wiki/Special:MyLanguage/Help:Contents',	UNHEX('68747470733A2F2F6F72672E6D6564696177696B692E7777772E2F77696B692F5370656369616C3A4D794C616E67756167652F48656C703A436F6E74')),
(2,	1,	'https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:Configuration_settings',	'https://org.mediawiki.www./wiki/Special:MyLanguage/Manual:Configuration_settings',	UNHEX('68747470733A2F2F6F72672E6D6564696177696B692E7777772E2F77696B692F5370656369616C3A4D794C616E67756167652F4D616E75616C3A436F')),
(3,	1,	'https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:FAQ',	'https://org.mediawiki.www./wiki/Special:MyLanguage/Manual:FAQ',	UNHEX('68747470733A2F2F6F72672E6D6564696177696B692E7777772E2F77696B692F5370656369616C3A4D794C616E67756167652F4D616E75616C3A4641')),
(4,	1,	'https://lists.wikimedia.org/postorius/lists/mediawiki-announce.lists.wikimedia.org/',	'https://org.wikimedia.lists./postorius/lists/mediawiki-announce.lists.wikimedia.org/',	UNHEX('68747470733A2F2F6F72672E77696B696D656469612E6C697374732E2F706F73746F726975732F6C697374732F6D6564696177696B692D616E6E6F75')),
(5,	1,	'https://www.mediawiki.org/wiki/Special:MyLanguage/Localisation#Translation_resources',	'https://org.mediawiki.www./wiki/Special:MyLanguage/Localisation#Translation_resources',	UNHEX('68747470733A2F2F6F72672E6D6564696177696B692E7777772E2F77696B692F5370656369616C3A4D794C616E67756167652F4C6F63616C69736174')),
(6,	1,	'https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:Combating_spam',	'https://org.mediawiki.www./wiki/Special:MyLanguage/Manual:Combating_spam',	UNHEX('68747470733A2F2F6F72672E6D6564696177696B692E7777772E2F77696B692F5370656369616C3A4D794C616E67756167652F4D616E75616C3A436F'));

CREATE TABLE `<<prefix>>_filearchive` (
  `fa_id` int(11) NOT NULL AUTO_INCREMENT,
  `fa_name` varbinary(255) NOT NULL DEFAULT '',
  `fa_archive_name` varbinary(255) DEFAULT '',
  `fa_storage_group` varbinary(16) DEFAULT NULL,
  `fa_storage_key` varbinary(64) DEFAULT '',
  `fa_deleted_user` int(11) DEFAULT NULL,
  `fa_deleted_timestamp` binary(14),
  `fa_deleted_reason_id` bigint(20) unsigned NOT NULL,
  `fa_size` int(10) unsigned DEFAULT 0,
  `fa_width` int(11) DEFAULT 0,
  `fa_height` int(11) DEFAULT 0,
  `fa_metadata` mediumblob DEFAULT NULL,
  `fa_bits` int(11) DEFAULT 0,
  `fa_media_type` enum('UNKNOWN','BITMAP','DRAWING','AUDIO','VIDEO','MULTIMEDIA','OFFICE','TEXT','EXECUTABLE','ARCHIVE','3D') DEFAULT NULL,
  `fa_major_mime` enum('unknown','application','audio','image','text','video','message','model','multipart','chemical') DEFAULT 'unknown',
  `fa_minor_mime` varbinary(100) DEFAULT 'unknown',
  `fa_description_id` bigint(20) unsigned NOT NULL,
  `fa_actor` bigint(20) unsigned NOT NULL,
  `fa_timestamp` binary(14),
  `fa_deleted` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `fa_sha1` varbinary(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`fa_id`),
  KEY `fa_name` (`fa_name`,`fa_timestamp`),
  KEY `fa_storage_group` (`fa_storage_group`,`fa_storage_key`),
  KEY `fa_deleted_timestamp` (`fa_deleted_timestamp`),
  KEY `fa_actor_timestamp` (`fa_actor`,`fa_timestamp`),
  KEY `fa_sha1` (`fa_sha1`(10))
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_image` (
  `img_name` varbinary(255) NOT NULL DEFAULT '',
  `img_size` int(10) unsigned NOT NULL DEFAULT 0,
  `img_width` int(11) NOT NULL DEFAULT 0,
  `img_height` int(11) NOT NULL DEFAULT 0,
  `img_metadata` mediumblob NOT NULL,
  `img_bits` int(11) NOT NULL DEFAULT 0,
  `img_media_type` enum('UNKNOWN','BITMAP','DRAWING','AUDIO','VIDEO','MULTIMEDIA','OFFICE','TEXT','EXECUTABLE','ARCHIVE','3D') DEFAULT NULL,
  `img_major_mime` enum('unknown','application','audio','image','text','video','message','model','multipart','chemical') NOT NULL DEFAULT 'unknown',
  `img_minor_mime` varbinary(100) NOT NULL DEFAULT 'unknown',
  `img_description_id` bigint(20) unsigned NOT NULL,
  `img_actor` bigint(20) unsigned NOT NULL,
  `img_timestamp` binary(14) NOT NULL,
  `img_sha1` varbinary(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`img_name`),
  KEY `img_actor_timestamp` (`img_actor`,`img_timestamp`),
  KEY `img_size` (`img_size`),
  KEY `img_timestamp` (`img_timestamp`),
  KEY `img_sha1` (`img_sha1`(10)),
  KEY `img_media_mime` (`img_media_type`,`img_major_mime`,`img_minor_mime`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_imagelinks` (
  `il_from` int(10) unsigned NOT NULL DEFAULT 0,
  `il_to` varbinary(255) NOT NULL DEFAULT '',
  `il_from_namespace` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`il_from`,`il_to`),
  KEY `il_to` (`il_to`,`il_from`),
  KEY `il_backlinks_namespace` (`il_from_namespace`,`il_to`,`il_from`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_interwiki` (
  `iw_prefix` varbinary(32) NOT NULL,
  `iw_url` blob NOT NULL,
  `iw_api` blob NOT NULL,
  `iw_wikiid` varbinary(64) NOT NULL,
  `iw_local` tinyint(1) NOT NULL,
  `iw_trans` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`iw_prefix`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_invitesignup` (
  `is_inviter` int(10) unsigned NOT NULL,
  `is_invitee` int(10) unsigned DEFAULT NULL,
  `is_email` varbinary(255) NOT NULL,
  `is_when` varbinary(14) NOT NULL,
  `is_used` varbinary(14) DEFAULT NULL,
  `is_hash` varbinary(40) NOT NULL,
  `is_groups` mediumblob DEFAULT NULL,
  PRIMARY KEY (`is_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_ipblocks` (
  `ipb_id` int(11) NOT NULL AUTO_INCREMENT,
  `ipb_address` tinyblob NOT NULL,
  `ipb_user` int(10) unsigned NOT NULL DEFAULT 0,
  `ipb_by_actor` bigint(20) unsigned NOT NULL,
  `ipb_reason_id` bigint(20) unsigned NOT NULL,
  `ipb_timestamp` binary(14) NOT NULL,
  `ipb_auto` tinyint(1) NOT NULL DEFAULT 0,
  `ipb_anon_only` tinyint(1) NOT NULL DEFAULT 0,
  `ipb_create_account` tinyint(1) NOT NULL DEFAULT 1,
  `ipb_enable_autoblock` tinyint(1) NOT NULL DEFAULT 1,
  `ipb_expiry` varbinary(14) NOT NULL,
  `ipb_range_start` tinyblob NOT NULL,
  `ipb_range_end` tinyblob NOT NULL,
  `ipb_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `ipb_block_email` tinyint(1) NOT NULL DEFAULT 0,
  `ipb_allow_usertalk` tinyint(1) NOT NULL DEFAULT 0,
  `ipb_parent_block_id` int(11) DEFAULT NULL,
  `ipb_sitewide` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`ipb_id`),
  UNIQUE KEY `ipb_address_unique` (`ipb_address`(255),`ipb_user`,`ipb_auto`),
  KEY `ipb_user` (`ipb_user`),
  KEY `ipb_range` (`ipb_range_start`(8),`ipb_range_end`(8)),
  KEY `ipb_timestamp` (`ipb_timestamp`),
  KEY `ipb_expiry` (`ipb_expiry`),
  KEY `ipb_parent_block_id` (`ipb_parent_block_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_ipblocks_restrictions` (
  `ir_ipb_id` int(11) NOT NULL,
  `ir_type` tinyint(4) NOT NULL,
  `ir_value` int(11) NOT NULL,
  PRIMARY KEY (`ir_ipb_id`,`ir_type`,`ir_value`),
  KEY `ir_type_value` (`ir_type`,`ir_value`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_ip_changes` (
  `ipc_rev_id` int(10) unsigned NOT NULL DEFAULT 0,
  `ipc_rev_timestamp` binary(14) NOT NULL,
  `ipc_hex` varbinary(35) NOT NULL DEFAULT '',
  PRIMARY KEY (`ipc_rev_id`),
  KEY `ipc_rev_timestamp` (`ipc_rev_timestamp`),
  KEY `ipc_hex_time` (`ipc_hex`,`ipc_rev_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_iwlinks` (
  `iwl_from` int(10) unsigned NOT NULL DEFAULT 0,
  `iwl_prefix` varbinary(32) NOT NULL DEFAULT '',
  `iwl_title` varbinary(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`iwl_from`,`iwl_prefix`,`iwl_title`),
  KEY `iwl_prefix_title_from` (`iwl_prefix`,`iwl_title`,`iwl_from`),
  KEY `iwl_prefix_from_title` (`iwl_prefix`,`iwl_from`,`iwl_title`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_job` (
  `job_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `job_cmd` varbinary(60) NOT NULL DEFAULT '',
  `job_namespace` int(11) NOT NULL,
  `job_title` varbinary(255) NOT NULL,
  `job_timestamp` binary(14) DEFAULT NULL,
  `job_params` mediumblob NOT NULL,
  `job_random` int(10) unsigned NOT NULL DEFAULT 0,
  `job_attempts` int(10) unsigned NOT NULL DEFAULT 0,
  `job_token` varbinary(32) NOT NULL DEFAULT '',
  `job_token_timestamp` binary(14) DEFAULT NULL,
  `job_sha1` varbinary(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`job_id`),
  KEY `job_sha1` (`job_sha1`),
  KEY `job_cmd_token` (`job_cmd`,`job_token`,`job_random`),
  KEY `job_cmd_token_id` (`job_cmd`,`job_token`,`job_id`),
  KEY `job_cmd` (`job_cmd`,`job_namespace`,`job_title`,`job_params`(128)),
  KEY `job_timestamp` (`job_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

INSERT INTO `<<prefix>>_job` (`job_id`, `job_cmd`, `job_namespace`, `job_title`, `job_timestamp`, `job_params`, `job_random`, `job_attempts`, `job_token`, `job_token_timestamp`, `job_sha1`) VALUES
(1,	UNHEX('726563656E744368616E676573557064617465'),	-1,	UNHEX('526563656E744368616E676573'),	UNHEX('3230323131313235313630373036'),	'a:4:{s:4:\"type\";s:11:\"cacheUpdate\";s:9:\"namespace\";i:-1;s:5:\"title\";s:13:\"RecentChanges\";s:9:\"requestId\";s:24:\"6d7c0739098315fa2d5aa816\";}',	279329004,	0,	UNHEX(''),	NULL,	UNHEX('703432676C72736C373833776A3535696F376C78686D687866306535646235'));

CREATE TABLE `<<prefix>>_l10n_cache` (
  `lc_lang` varbinary(35) NOT NULL,
  `lc_key` varbinary(255) NOT NULL,
  `lc_value` mediumblob NOT NULL,
  PRIMARY KEY (`lc_lang`,`lc_key`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_langlinks` (
  `ll_from` int(10) unsigned NOT NULL DEFAULT 0,
  `ll_lang` varbinary(35) NOT NULL DEFAULT '',
  `ll_title` varbinary(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ll_from`,`ll_lang`),
  KEY `ll_lang` (`ll_lang`,`ll_title`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_logging` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `log_type` varbinary(32) NOT NULL DEFAULT '',
  `log_action` varbinary(32) NOT NULL DEFAULT '',
  `log_timestamp` binary(14) NOT NULL DEFAULT '19700101000000',
  `log_actor` bigint(20) unsigned NOT NULL,
  `log_namespace` int(11) NOT NULL DEFAULT 0,
  `log_title` varbinary(255) NOT NULL DEFAULT '',
  `log_page` int(10) unsigned DEFAULT NULL,
  `log_comment_id` bigint(20) unsigned NOT NULL,
  `log_params` blob NOT NULL,
  `log_deleted` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`log_id`),
  KEY `log_type_time` (`log_type`,`log_timestamp`),
  KEY `log_actor_time` (`log_actor`,`log_timestamp`),
  KEY `log_page_time` (`log_namespace`,`log_title`,`log_timestamp`),
  KEY `log_times` (`log_timestamp`),
  KEY `log_actor_type_time` (`log_actor`,`log_type`,`log_timestamp`),
  KEY `log_page_id_time` (`log_page`,`log_timestamp`),
  KEY `log_type_action` (`log_type`,`log_action`,`log_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

INSERT INTO `<<prefix>>_logging` (`log_id`, `log_type`, `log_action`, `log_timestamp`, `log_actor`, `log_namespace`, `log_title`, `log_page`, `log_comment_id`, `log_params`, `log_deleted`) VALUES
(1,	UNHEX('637265617465'),	UNHEX('637265617465'),	UNHEX('3230323131313235313630373036'),	2,	0,	UNHEX('4D61696E5F50616765'),	1,	1,	'a:1:{s:17:\"associated_rev_id\";i:1;}',	0);

CREATE TABLE `<<prefix>>_log_search` (
  `ls_field` varbinary(32) NOT NULL,
  `ls_value` varbinary(255) NOT NULL,
  `ls_log_id` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`ls_field`,`ls_value`,`ls_log_id`),
  KEY `ls_log_id` (`ls_log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

INSERT INTO `<<prefix>>_log_search` (`ls_field`, `ls_value`, `ls_log_id`) VALUES
(UNHEX('6173736F6369617465645F7265765F6964'),	UNHEX('31'),	1);

CREATE TABLE `<<prefix>>_mathoid` (
  `math_inputhash` varbinary(16) NOT NULL,
  `math_input` blob NOT NULL,
  `math_tex` blob DEFAULT NULL,
  `math_mathml` blob DEFAULT NULL,
  `math_svg` blob DEFAULT NULL,
  `math_style` tinyint(4) DEFAULT NULL,
  `math_input_type` tinyint(4) DEFAULT NULL,
  `math_png` mediumblob DEFAULT NULL,
  PRIMARY KEY (`math_inputhash`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_module_deps` (
  `md_module` varbinary(255) NOT NULL,
  `md_skin` varbinary(32) NOT NULL,
  `md_deps` mediumblob NOT NULL,
  PRIMARY KEY (`md_module`,`md_skin`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_oauth2_access_tokens` (
  `oaat_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `oaat_identifier` varbinary(255) NOT NULL,
  `oaat_expires` varbinary(14) NOT NULL,
  `oaat_acceptance_id` int(10) unsigned NOT NULL,
  `oaat_revoked` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`oaat_id`),
  UNIQUE KEY `oaat_identifier` (`oaat_identifier`),
  KEY `oaat_acceptance_id` (`oaat_acceptance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_oauth_accepted_consumer` (
  `oaac_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `oaac_wiki` varbinary(255) NOT NULL,
  `oaac_user_id` int(10) unsigned NOT NULL,
  `oaac_consumer_id` int(10) unsigned NOT NULL,
  `oaac_access_token` varbinary(32) NOT NULL,
  `oaac_access_secret` varbinary(32) NOT NULL,
  `oaac_grants` blob NOT NULL,
  `oaac_accepted` varbinary(14) NOT NULL,
  `oaac_oauth_version` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`oaac_id`),
  UNIQUE KEY `oaac_access_token` (`oaac_access_token`),
  UNIQUE KEY `oaac_user_consumer_wiki` (`oaac_user_id`,`oaac_consumer_id`,`oaac_wiki`),
  KEY `oaac_consumer_user` (`oaac_consumer_id`,`oaac_user_id`),
  KEY `oaac_user_id` (`oaac_user_id`,`oaac_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_oauth_registered_consumer` (
  `oarc_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `oarc_consumer_key` varbinary(32) NOT NULL,
  `oarc_name` varbinary(128) NOT NULL,
  `oarc_user_id` int(10) unsigned NOT NULL,
  `oarc_version` varbinary(32) NOT NULL,
  `oarc_callback_url` blob NOT NULL,
  `oarc_callback_is_prefix` tinyblob DEFAULT NULL,
  `oarc_description` blob NOT NULL,
  `oarc_email` varbinary(255) NOT NULL,
  `oarc_email_authenticated` varbinary(14) DEFAULT NULL,
  `oarc_developer_agreement` tinyint(4) NOT NULL DEFAULT 0,
  `oarc_owner_only` tinyint(4) NOT NULL DEFAULT 0,
  `oarc_wiki` varbinary(32) NOT NULL,
  `oarc_grants` blob NOT NULL,
  `oarc_registration` varbinary(14) NOT NULL,
  `oarc_secret_key` varbinary(32) DEFAULT NULL,
  `oarc_rsa_key` blob DEFAULT NULL,
  `oarc_restrictions` blob NOT NULL,
  `oarc_stage` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `oarc_stage_timestamp` varbinary(14) NOT NULL,
  `oarc_deleted` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `oarc_oauth_version` tinyint(4) NOT NULL DEFAULT 1,
  `oarc_oauth2_allowed_grants` blob DEFAULT NULL,
  `oarc_oauth2_is_confidential` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`oarc_id`),
  UNIQUE KEY `oarc_consumer_key` (`oarc_consumer_key`),
  UNIQUE KEY `oarc_name_version_user` (`oarc_name`,`oarc_user_id`,`oarc_version`),
  KEY `oarc_user_id` (`oarc_user_id`),
  KEY `oarc_stage_timestamp` (`oarc_stage`,`oarc_stage_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_objectcache` (
  `keyname` varbinary(255) NOT NULL DEFAULT '',
  `value` mediumblob DEFAULT NULL,
  `exptime` binary(14) NOT NULL,
  `modtoken` varbinary(17) NOT NULL DEFAULT '00000000000000000',
  `flags` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`keyname`),
  KEY `exptime` (`exptime`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

INSERT INTO `<<prefix>>_objectcache` (`keyname`, `value`, `exptime`, `modtoken`, `flags`) VALUES
(UNHEX('77696B693A6D657373616765733A656E'),	'��\n�0�E_ Ɋ\\���diK�]cA猡��d���*�\r��u�ߨ|�e����:сIA��\"�P��\'�%*���C����4���>E������x2V*�?',	UNHEX('3939393931323331323335393539'),	UNHEX('3030303030303030303030303030303030'),	NULL);

CREATE TABLE `<<prefix>>_oldimage` (
  `oi_name` varbinary(255) NOT NULL DEFAULT '',
  `oi_archive_name` varbinary(255) NOT NULL DEFAULT '',
  `oi_size` int(10) unsigned NOT NULL DEFAULT 0,
  `oi_width` int(11) NOT NULL DEFAULT 0,
  `oi_height` int(11) NOT NULL DEFAULT 0,
  `oi_bits` int(11) NOT NULL DEFAULT 0,
  `oi_description_id` bigint(20) unsigned NOT NULL,
  `oi_actor` bigint(20) unsigned NOT NULL,
  `oi_timestamp` binary(14) NOT NULL,
  `oi_metadata` mediumblob NOT NULL,
  `oi_media_type` enum('UNKNOWN','BITMAP','DRAWING','AUDIO','VIDEO','MULTIMEDIA','OFFICE','TEXT','EXECUTABLE','ARCHIVE','3D') DEFAULT NULL,
  `oi_major_mime` enum('unknown','application','audio','image','text','video','message','model','multipart','chemical') NOT NULL DEFAULT 'unknown',
  `oi_minor_mime` varbinary(100) NOT NULL DEFAULT 'unknown',
  `oi_deleted` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `oi_sha1` varbinary(32) NOT NULL DEFAULT '',
  KEY `oi_actor_timestamp` (`oi_actor`,`oi_timestamp`),
  KEY `oi_name_timestamp` (`oi_name`,`oi_timestamp`),
  KEY `oi_name_archive_name` (`oi_name`,`oi_archive_name`(14)),
  KEY `oi_sha1` (`oi_sha1`(10)),
  KEY `oi_timestamp` (`oi_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_page` (
  `page_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `page_namespace` int(11) NOT NULL,
  `page_title` varbinary(255) NOT NULL,
  `page_restrictions` tinyblob DEFAULT NULL,
  `page_is_redirect` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `page_is_new` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `page_random` double unsigned NOT NULL,
  `page_touched` binary(14) NOT NULL,
  `page_links_updated` varbinary(14) DEFAULT NULL,
  `page_latest` int(10) unsigned NOT NULL,
  `page_len` int(10) unsigned NOT NULL,
  `page_content_model` varbinary(32) DEFAULT NULL,
  `page_lang` varbinary(35) DEFAULT NULL,
  PRIMARY KEY (`page_id`),
  UNIQUE KEY `page_name_title` (`page_namespace`,`page_title`),
  KEY `page_random` (`page_random`),
  KEY `page_len` (`page_len`),
  KEY `page_redirect_namespace_len` (`page_is_redirect`,`page_namespace`,`page_len`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

INSERT INTO `<<prefix>>_page` (`page_id`, `page_namespace`, `page_title`, `page_restrictions`, `page_is_redirect`, `page_is_new`, `page_random`, `page_touched`, `page_links_updated`, `page_latest`, `page_len`, `page_content_model`, `page_lang`) VALUES
(1,	0,	UNHEX('4D61696E5F50616765'),	'',	0,	1,	0.808109170437,	UNHEX('3230323131313235313630373036'),	UNHEX('3230323131313235313630373036'),	1,	755,	UNHEX('77696B6974657874'),	NULL);

CREATE TABLE `<<prefix>>_pagelinks` (
  `pl_from` int(10) unsigned NOT NULL DEFAULT 0,
  `pl_namespace` int(11) NOT NULL DEFAULT 0,
  `pl_title` varbinary(255) NOT NULL DEFAULT '',
  `pl_from_namespace` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`pl_from`,`pl_namespace`,`pl_title`),
  KEY `pl_namespace` (`pl_namespace`,`pl_title`,`pl_from`),
  KEY `pl_backlinks_namespace` (`pl_from_namespace`,`pl_namespace`,`pl_title`,`pl_from`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_page_props` (
  `pp_page` int(11) NOT NULL,
  `pp_propname` varbinary(60) NOT NULL,
  `pp_value` blob NOT NULL,
  `pp_sortkey` float DEFAULT NULL,
  PRIMARY KEY (`pp_page`,`pp_propname`),
  UNIQUE KEY `pp_propname_page` (`pp_propname`,`pp_page`),
  UNIQUE KEY `pp_propname_sortkey_page` (`pp_propname`,`pp_sortkey`,`pp_page`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_page_restrictions` (
  `pr_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pr_page` int(11) NOT NULL,
  `pr_type` varbinary(60) NOT NULL,
  `pr_level` varbinary(60) NOT NULL,
  `pr_cascade` tinyint(4) NOT NULL,
  `pr_user` int(10) unsigned DEFAULT NULL,
  `pr_expiry` varbinary(14) DEFAULT NULL,
  PRIMARY KEY (`pr_id`),
  UNIQUE KEY `pr_pagetype` (`pr_page`,`pr_type`),
  KEY `pr_typelevel` (`pr_type`,`pr_level`),
  KEY `pr_level` (`pr_level`),
  KEY `pr_cascade` (`pr_cascade`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_protected_titles` (
  `pt_namespace` int(11) NOT NULL,
  `pt_title` varbinary(255) NOT NULL,
  `pt_user` int(10) unsigned NOT NULL,
  `pt_reason_id` bigint(20) unsigned NOT NULL,
  `pt_timestamp` binary(14) NOT NULL,
  `pt_expiry` varbinary(14) NOT NULL,
  `pt_create_perm` varbinary(60) NOT NULL,
  PRIMARY KEY (`pt_namespace`,`pt_title`),
  KEY `pt_timestamp` (`pt_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_querycache` (
  `qc_type` varbinary(32) NOT NULL,
  `qc_value` int(10) unsigned NOT NULL DEFAULT 0,
  `qc_namespace` int(11) NOT NULL DEFAULT 0,
  `qc_title` varbinary(255) NOT NULL DEFAULT '',
  KEY `qc_type` (`qc_type`,`qc_value`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_querycachetwo` (
  `qcc_type` varbinary(32) NOT NULL,
  `qcc_value` int(10) unsigned NOT NULL DEFAULT 0,
  `qcc_namespace` int(11) NOT NULL DEFAULT 0,
  `qcc_title` varbinary(255) NOT NULL DEFAULT '',
  `qcc_namespacetwo` int(11) NOT NULL DEFAULT 0,
  `qcc_titletwo` varbinary(255) NOT NULL DEFAULT '',
  KEY `qcc_type` (`qcc_type`,`qcc_value`),
  KEY `qcc_title` (`qcc_type`,`qcc_namespace`,`qcc_title`),
  KEY `qcc_titletwo` (`qcc_type`,`qcc_namespacetwo`,`qcc_titletwo`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_querycache_info` (
  `qci_type` varbinary(32) NOT NULL DEFAULT '',
  `qci_timestamp` binary(14) NOT NULL DEFAULT '19700101000000',
  PRIMARY KEY (`qci_type`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_recentchanges` (
  `rc_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rc_timestamp` binary(14) NOT NULL,
  `rc_actor` bigint(20) unsigned NOT NULL,
  `rc_namespace` int(11) NOT NULL DEFAULT 0,
  `rc_title` varbinary(255) NOT NULL DEFAULT '',
  `rc_comment_id` bigint(20) unsigned NOT NULL,
  `rc_minor` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `rc_bot` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `rc_new` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `rc_cur_id` int(10) unsigned NOT NULL DEFAULT 0,
  `rc_this_oldid` int(10) unsigned NOT NULL DEFAULT 0,
  `rc_last_oldid` int(10) unsigned NOT NULL DEFAULT 0,
  `rc_type` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `rc_source` varbinary(16) NOT NULL DEFAULT '',
  `rc_patrolled` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `rc_ip` varbinary(40) NOT NULL DEFAULT '',
  `rc_old_len` int(11) DEFAULT NULL,
  `rc_new_len` int(11) DEFAULT NULL,
  `rc_deleted` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `rc_logid` int(10) unsigned NOT NULL DEFAULT 0,
  `rc_log_type` varbinary(255) DEFAULT NULL,
  `rc_log_action` varbinary(255) DEFAULT NULL,
  `rc_params` blob DEFAULT NULL,
  PRIMARY KEY (`rc_id`),
  KEY `rc_timestamp` (`rc_timestamp`),
  KEY `rc_namespace_title_timestamp` (`rc_namespace`,`rc_title`,`rc_timestamp`),
  KEY `rc_cur_id` (`rc_cur_id`),
  KEY `rc_new_name_timestamp` (`rc_new`,`rc_namespace`,`rc_timestamp`),
  KEY `rc_ip` (`rc_ip`),
  KEY `rc_ns_actor` (`rc_namespace`,`rc_actor`),
  KEY `rc_actor` (`rc_actor`,`rc_timestamp`),
  KEY `rc_name_type_patrolled_timestamp` (`rc_namespace`,`rc_type`,`rc_patrolled`,`rc_timestamp`),
  KEY `rc_this_oldid` (`rc_this_oldid`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

INSERT INTO `<<prefix>>_recentchanges` (`rc_id`, `rc_timestamp`, `rc_actor`, `rc_namespace`, `rc_title`, `rc_comment_id`, `rc_minor`, `rc_bot`, `rc_new`, `rc_cur_id`, `rc_this_oldid`, `rc_last_oldid`, `rc_type`, `rc_source`, `rc_patrolled`, `rc_ip`, `rc_old_len`, `rc_new_len`, `rc_deleted`, `rc_logid`, `rc_log_type`, `rc_log_action`, `rc_params`) VALUES
(1,	UNHEX('3230323131313235313630373036'),	2,	0,	UNHEX('4D61696E5F50616765'),	1,	0,	0,	1,	1,	1,	0,	1,	UNHEX('6D772E6E6577'),	0,	UNHEX('3132372E302E302E31'),	0,	755,	0,	0,	NULL,	UNHEX(''),	'');

CREATE TABLE `<<prefix>>_redirect` (
  `rd_from` int(10) unsigned NOT NULL DEFAULT 0,
  `rd_namespace` int(11) NOT NULL DEFAULT 0,
  `rd_title` varbinary(255) NOT NULL DEFAULT '',
  `rd_interwiki` varbinary(32) DEFAULT NULL,
  `rd_fragment` varbinary(255) DEFAULT NULL,
  PRIMARY KEY (`rd_from`),
  KEY `rd_ns_title` (`rd_namespace`,`rd_title`,`rd_from`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_revision` (
  `rev_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rev_page` int(10) unsigned NOT NULL,
  `rev_comment_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `rev_actor` bigint(20) unsigned NOT NULL DEFAULT 0,
  `rev_timestamp` binary(14) NOT NULL,
  `rev_minor_edit` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `rev_deleted` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `rev_len` int(10) unsigned DEFAULT NULL,
  `rev_parent_id` int(10) unsigned DEFAULT NULL,
  `rev_sha1` varbinary(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`rev_id`),
  KEY `rev_page_id` (`rev_page`,`rev_id`),
  KEY `rev_timestamp` (`rev_timestamp`),
  KEY `rev_page_timestamp` (`rev_page`,`rev_timestamp`),
  KEY `rev_actor_timestamp` (`rev_actor`,`rev_timestamp`,`rev_id`),
  KEY `rev_page_actor_timestamp` (`rev_page`,`rev_actor`,`rev_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

INSERT INTO `<<prefix>>_revision` (`rev_id`, `rev_page`, `rev_comment_id`, `rev_actor`, `rev_timestamp`, `rev_minor_edit`, `rev_deleted`, `rev_len`, `rev_parent_id`, `rev_sha1`) VALUES
(1,	1,	0,	0,	UNHEX('3230323131313235313630373036'),	0,	0,	755,	0,	UNHEX('3232767A357A6C7861327A63746577696D61756D3262663164756538686B6C'));

CREATE TABLE `<<prefix>>_revision_actor_temp` (
  `revactor_rev` int(10) unsigned NOT NULL,
  `revactor_actor` bigint(20) unsigned NOT NULL,
  `revactor_timestamp` binary(14) NOT NULL,
  `revactor_page` int(10) unsigned NOT NULL,
  PRIMARY KEY (`revactor_rev`,`revactor_actor`),
  UNIQUE KEY `revactor_rev` (`revactor_rev`),
  KEY `actor_timestamp` (`revactor_actor`,`revactor_timestamp`),
  KEY `page_actor_timestamp` (`revactor_page`,`revactor_actor`,`revactor_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

INSERT INTO `<<prefix>>_revision_actor_temp` (`revactor_rev`, `revactor_actor`, `revactor_timestamp`, `revactor_page`) VALUES
(1,	2,	UNHEX('3230323131313235313630373036'),	1);

CREATE TABLE `<<prefix>>_revision_comment_temp` (
  `revcomment_rev` int(10) unsigned NOT NULL,
  `revcomment_comment_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`revcomment_rev`,`revcomment_comment_id`),
  UNIQUE KEY `revcomment_rev` (`revcomment_rev`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

INSERT INTO `<<prefix>>_revision_comment_temp` (`revcomment_rev`, `revcomment_comment_id`) VALUES
(1,	1);

CREATE TABLE `<<prefix>>_searchindex` (
  `si_page` int(10) unsigned NOT NULL,
  `si_title` varchar(255) NOT NULL DEFAULT '',
  `si_text` mediumtext NOT NULL,
  UNIQUE KEY `si_page` (`si_page`),
  FULLTEXT KEY `si_title` (`si_title`),
  FULLTEXT KEY `si_text` (`si_text`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

INSERT INTO `<<prefix>>_searchindex` (`si_page`, `si_title`, `si_text`) VALUES
(1,	'main page',	' mediawiki hasu800 been installed. consult theu800 user user\'su800 guide foru800 information onu800 using theu800 wiki software. getting started getting started getting started * configuration settings list * mediawiki faqu800 * mediawiki release mailing list * localise mediawiki foru800 your language * learn howu800 tou800 combat spam onu800 your wiki ');

CREATE TABLE `<<prefix>>_sites` (
  `site_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_global_key` varbinary(64) NOT NULL,
  `site_type` varbinary(32) NOT NULL,
  `site_group` varbinary(32) NOT NULL,
  `site_source` varbinary(32) NOT NULL,
  `site_language` varbinary(35) NOT NULL,
  `site_protocol` varbinary(32) NOT NULL,
  `site_domain` varbinary(255) NOT NULL,
  `site_data` blob NOT NULL,
  `site_forward` tinyint(1) NOT NULL,
  `site_config` blob NOT NULL,
  PRIMARY KEY (`site_id`),
  UNIQUE KEY `site_global_key` (`site_global_key`),
  KEY `site_type` (`site_type`),
  KEY `site_group` (`site_group`),
  KEY `site_source` (`site_source`),
  KEY `site_language` (`site_language`),
  KEY `site_protocol` (`site_protocol`),
  KEY `site_domain` (`site_domain`),
  KEY `site_forward` (`site_forward`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_site_identifiers` (
  `si_type` varbinary(32) NOT NULL,
  `si_key` varbinary(32) NOT NULL,
  `si_site` int(10) unsigned NOT NULL,
  PRIMARY KEY (`si_type`,`si_key`),
  KEY `si_site` (`si_site`),
  KEY `si_key` (`si_key`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_site_stats` (
  `ss_row_id` int(10) unsigned NOT NULL,
  `ss_total_edits` bigint(20) unsigned DEFAULT NULL,
  `ss_good_articles` bigint(20) unsigned DEFAULT NULL,
  `ss_total_pages` bigint(20) unsigned DEFAULT NULL,
  `ss_users` bigint(20) unsigned DEFAULT NULL,
  `ss_active_users` bigint(20) unsigned DEFAULT NULL,
  `ss_images` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`ss_row_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

INSERT INTO `<<prefix>>_site_stats` (`ss_row_id`, `ss_total_edits`, `ss_good_articles`, `ss_total_pages`, `ss_users`, `ss_active_users`, `ss_images`) VALUES
(1,	1,	0,	1,	1,	0,	0);

CREATE TABLE `<<prefix>>_slots` (
  `slot_revision_id` bigint(20) unsigned NOT NULL,
  `slot_role_id` smallint(5) unsigned NOT NULL,
  `slot_content_id` bigint(20) unsigned NOT NULL,
  `slot_origin` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`slot_revision_id`,`slot_role_id`),
  KEY `slot_revision_origin_role` (`slot_revision_id`,`slot_origin`,`slot_role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

INSERT INTO `<<prefix>>_slots` (`slot_revision_id`, `slot_role_id`, `slot_content_id`, `slot_origin`) VALUES
(1,	1,	1,	1);

CREATE TABLE `<<prefix>>_slot_roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varbinary(64) NOT NULL,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_templatelinks` (
  `tl_from` int(10) unsigned NOT NULL DEFAULT 0,
  `tl_namespace` int(11) NOT NULL DEFAULT 0,
  `tl_title` varbinary(255) NOT NULL DEFAULT '',
  `tl_from_namespace` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`tl_from`,`tl_namespace`,`tl_title`),
  KEY `tl_namespace` (`tl_namespace`,`tl_title`,`tl_from`),
  KEY `tl_backlinks_namespace` (`tl_from_namespace`,`tl_namespace`,`tl_title`,`tl_from`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_text` (
  `old_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `old_text` mediumblob NOT NULL,
  `old_flags` tinyblob NOT NULL,
  PRIMARY KEY (`old_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

INSERT INTO `<<prefix>>_text` (`old_id`, `old_text`, `old_flags`) VALUES
(1,	'<strong>MediaWiki has been installed.</strong>\n\nConsult the [https://www.mediawiki.org/wiki/Special:MyLanguage/Help:Contents User\'s Guide] for information on using the wiki software.\n\n== Getting started ==\n* [https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:Configuration_settings Configuration settings list]\n* [https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:FAQ MediaWiki FAQ]\n* [https://lists.wikimedia.org/postorius/lists/mediawiki-announce.lists.wikimedia.org/ MediaWiki release mailing list]\n* [https://www.mediawiki.org/wiki/Special:MyLanguage/Localisation#Translation_resources Localise MediaWiki for your language]\n* [https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:Combating_spam Learn how to combat spam on your wiki]',	'utf-8');

CREATE TABLE `<<prefix>>_updatelog` (
  `ul_key` varbinary(255) NOT NULL,
  `ul_value` blob DEFAULT NULL,
  PRIMARY KEY (`ul_key`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_uploadstash` (
  `us_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `us_user` int(10) unsigned NOT NULL,
  `us_key` varbinary(255) NOT NULL,
  `us_orig_path` varbinary(255) NOT NULL,
  `us_path` varbinary(255) NOT NULL,
  `us_source_type` varbinary(50) DEFAULT NULL,
  `us_timestamp` binary(14) NOT NULL,
  `us_status` varbinary(50) NOT NULL,
  `us_chunk_inx` int(10) unsigned DEFAULT NULL,
  `us_props` blob DEFAULT NULL,
  `us_size` int(10) unsigned NOT NULL,
  `us_sha1` varbinary(31) NOT NULL,
  `us_mime` varbinary(255) DEFAULT NULL,
  `us_media_type` enum('UNKNOWN','BITMAP','DRAWING','AUDIO','VIDEO','MULTIMEDIA','OFFICE','TEXT','EXECUTABLE','ARCHIVE','3D') DEFAULT NULL,
  `us_image_width` int(10) unsigned DEFAULT NULL,
  `us_image_height` int(10) unsigned DEFAULT NULL,
  `us_image_bits` smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`us_id`),
  UNIQUE KEY `us_key` (`us_key`),
  KEY `us_user` (`us_user`),
  KEY `us_timestamp` (`us_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_user` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_name` varbinary(255) NOT NULL DEFAULT '',
  `user_real_name` varbinary(255) NOT NULL DEFAULT '',
  `user_password` tinyblob NOT NULL,
  `user_newpassword` tinyblob NOT NULL,
  `user_newpass_time` binary(14) DEFAULT NULL,
  `user_email` tinyblob NOT NULL,
  `user_touched` binary(14) NOT NULL,
  `user_token` binary(32) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `user_email_authenticated` binary(14) DEFAULT NULL,
  `user_email_token` binary(32) DEFAULT NULL,
  `user_email_token_expires` binary(14) DEFAULT NULL,
  `user_registration` binary(14) DEFAULT NULL,
  `user_editcount` int(11) DEFAULT NULL,
  `user_password_expires` varbinary(14) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `user_email_token` (`user_email_token`),
  KEY `user_email` (`user_email`(50))
) ENGINE=InnoDB DEFAULT CHARSET=binary;

INSERT INTO `<<prefix>>_user` (`user_id`, `user_name`, `user_real_name`, `user_password`, `user_newpassword`, `user_newpass_time`, `user_email`, `user_touched`, `user_token`, `user_email_authenticated`, `user_email_token`, `user_email_token_expires`, `user_registration`, `user_editcount`, `user_password_expires`) VALUES
(1,	UNHEX('41646D696E4E616D65'),	UNHEX(''),	':pbkdf2:sha512:30000:64:oUlQcDeCS4r46Po6D5oxIA==:YtpXq4b1DSOfA9hm3xxTa5L/rdXRoK+wAnUNWOF7/EOG47uUR0VEfyCmyEMPcE1qvtBJP5lEUtcxowwU8+tm0Q==',	'',	NULL,	'',	UNHEX('3230323131313235313630373036'),	UNHEX('3638343733366138636635623039363634653963346133396565636232356231'),	NULL,	UNHEX('0000000000000000000000000000000000000000000000000000000000000000'),	NULL,	UNHEX('3230323131313235313630373035'),	0,	NULL),
(2,	UNHEX('4D6564696157696B692064656661756C74'),	UNHEX(''),	'',	'',	NULL,	'',	UNHEX('3230323131313235313630373036'),	UNHEX('2A2A2A20494E56414C4944202A2A2A0000000000000000000000000000000000'),	NULL,	NULL,	NULL,	UNHEX('3230323131313235313630373036'),	1,	NULL);

CREATE TABLE `<<prefix>>_user_former_groups` (
  `ufg_user` int(10) unsigned NOT NULL DEFAULT 0,
  `ufg_group` varbinary(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ufg_user`,`ufg_group`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_user_groups` (
  `ug_user` int(10) unsigned NOT NULL DEFAULT 0,
  `ug_group` varbinary(255) NOT NULL DEFAULT '',
  `ug_expiry` varbinary(14) DEFAULT NULL,
  PRIMARY KEY (`ug_user`,`ug_group`),
  KEY `ug_group` (`ug_group`),
  KEY `ug_expiry` (`ug_expiry`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

INSERT INTO `<<prefix>>_user_groups` (`ug_user`, `ug_group`, `ug_expiry`) VALUES
(1,	UNHEX('62757265617563726174'),	NULL),
(1,	UNHEX('696E746572666163652D61646D696E'),	NULL),
(1,	UNHEX('7379736F70'),	NULL);

CREATE TABLE `<<prefix>>_user_newtalk` (
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `user_ip` varbinary(40) NOT NULL DEFAULT '',
  `user_last_timestamp` binary(14) DEFAULT NULL,
  KEY `un_user_id` (`user_id`),
  KEY `un_user_ip` (`user_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_user_properties` (
  `up_user` int(10) unsigned NOT NULL,
  `up_property` varbinary(255) NOT NULL,
  `up_value` blob DEFAULT NULL,
  PRIMARY KEY (`up_user`,`up_property`),
  KEY `up_property` (`up_property`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_watchlist` (
  `wl_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wl_user` int(10) unsigned NOT NULL,
  `wl_namespace` int(11) NOT NULL DEFAULT 0,
  `wl_title` varbinary(255) NOT NULL DEFAULT '',
  `wl_notificationtimestamp` binary(14) DEFAULT NULL,
  PRIMARY KEY (`wl_id`),
  UNIQUE KEY `wl_user` (`wl_user`,`wl_namespace`,`wl_title`),
  KEY `wl_namespace_title` (`wl_namespace`,`wl_title`),
  KEY `wl_user_notificationtimestamp` (`wl_user`,`wl_notificationtimestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_watchlist_expiry` (
  `we_item` int(10) unsigned NOT NULL,
  `we_expiry` binary(14) NOT NULL,
  PRIMARY KEY (`we_item`),
  KEY `we_expiry` (`we_expiry`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_wbc_entity_usage` (
  `eu_row_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `eu_entity_id` varbinary(255) NOT NULL,
  `eu_aspect` varbinary(37) NOT NULL,
  `eu_page_id` int(11) NOT NULL,
  PRIMARY KEY (`eu_row_id`),
  UNIQUE KEY `eu_entity_id` (`eu_entity_id`,`eu_aspect`,`eu_page_id`),
  KEY `eu_page_id` (`eu_page_id`,`eu_entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_wbt_item_terms` (
  `wbit_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `wbit_item_id` int(10) unsigned NOT NULL,
  `wbit_term_in_lang_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`wbit_id`),
  UNIQUE KEY `wbt_item_terms_term_in_lang_id_item_id` (`wbit_term_in_lang_id`,`wbit_item_id`),
  KEY `wbt_item_terms_item_id` (`wbit_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_wbt_property_terms` (
  `wbpt_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wbpt_property_id` int(10) unsigned NOT NULL,
  `wbpt_term_in_lang_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`wbpt_id`),
  UNIQUE KEY `wbt_property_terms_term_in_lang_id_property_id` (`wbpt_term_in_lang_id`,`wbpt_property_id`),
  KEY `wbt_property_terms_property_id` (`wbpt_property_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_wbt_term_in_lang` (
  `wbtl_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wbtl_type_id` int(10) unsigned NOT NULL,
  `wbtl_text_in_lang_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`wbtl_id`),
  UNIQUE KEY `wbt_term_in_lang_text_in_lang_id_lang_id` (`wbtl_text_in_lang_id`,`wbtl_type_id`),
  KEY `wbt_term_in_lang_type_id_text_in` (`wbtl_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_wbt_text` (
  `wbx_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wbx_text` varbinary(255) NOT NULL,
  PRIMARY KEY (`wbx_id`),
  UNIQUE KEY `wbt_text_text` (`wbx_text`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_wbt_text_in_lang` (
  `wbxl_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wbxl_language` varbinary(20) NOT NULL,
  `wbxl_text_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`wbxl_id`),
  UNIQUE KEY `wbt_text_in_lang_text_id_text_id` (`wbxl_text_id`,`wbxl_language`),
  KEY `wbt_text_in_lang_language` (`wbxl_language`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_wbt_type` (
  `wby_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wby_name` varbinary(45) NOT NULL,
  PRIMARY KEY (`wby_id`),
  UNIQUE KEY `wbt_type_name` (`wby_name`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_wb_changes` (
  `change_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `change_type` varbinary(25) NOT NULL,
  `change_time` varbinary(14) NOT NULL,
  `change_object_id` varbinary(14) NOT NULL,
  `change_revision_id` int(10) unsigned NOT NULL,
  `change_user_id` int(10) unsigned NOT NULL,
  `change_info` mediumblob NOT NULL,
  PRIMARY KEY (`change_id`),
  KEY `wb_changes_change_time` (`change_time`),
  KEY `wb_changes_change_revision_id` (`change_revision_id`),
  KEY `change_object_id` (`change_object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_wb_changes_dispatch` (
  `chd_site` varbinary(32) NOT NULL,
  `chd_db` varbinary(32) NOT NULL,
  `chd_seen` int(10) unsigned NOT NULL DEFAULT 0,
  `chd_touched` varbinary(14) NOT NULL DEFAULT '00000000000000',
  `chd_lock` varbinary(64) DEFAULT NULL,
  `chd_disabled` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`chd_site`),
  KEY `wb_changes_dispatch_chd_seen` (`chd_seen`),
  KEY `wb_changes_dispatch_chd_touched` (`chd_touched`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_wb_changes_subscription` (
  `cs_row_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `cs_entity_id` varbinary(255) NOT NULL,
  `cs_subscriber_id` varbinary(255) NOT NULL,
  PRIMARY KEY (`cs_row_id`),
  UNIQUE KEY `cs_entity_id` (`cs_entity_id`,`cs_subscriber_id`),
  KEY `cs_subscriber_id` (`cs_subscriber_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_wb_id_counters` (
  `id_value` int(10) unsigned NOT NULL,
  `id_type` varbinary(32) NOT NULL,
  UNIQUE KEY `wb_id_counters_type` (`id_type`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_wb_items_per_site` (
  `ips_row_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ips_item_id` int(10) unsigned NOT NULL,
  `ips_site_id` varbinary(32) NOT NULL,
  `ips_site_page` varbinary(310) NOT NULL,
  PRIMARY KEY (`ips_row_id`),
  UNIQUE KEY `wb_ips_item_site_page` (`ips_site_id`,`ips_site_page`),
  KEY `wb_ips_item_id` (`ips_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_wb_property_info` (
  `pi_property_id` int(10) unsigned NOT NULL,
  `pi_type` varbinary(32) NOT NULL,
  `pi_info` blob NOT NULL,
  PRIMARY KEY (`pi_property_id`),
  KEY `pi_type` (`pi_type`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
-- 2021-11-25 16:35:20
