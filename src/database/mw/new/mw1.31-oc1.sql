-- Origionally an Adminer 4.6.3 MySQL dump

CREATE TABLE `<<prefix>>_actor` (
  `actor_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `actor_user` int(10) unsigned DEFAULT NULL,
  `actor_name` varbinary(255) NOT NULL,
  PRIMARY KEY (`actor_id`),
  UNIQUE KEY `actor_name` (`actor_name`),
  UNIQUE KEY `actor_user` (`actor_user`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_archive` (
  `ar_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ar_namespace` int(11) NOT NULL DEFAULT 0,
  `ar_title` varbinary(255) NOT NULL DEFAULT '',
  `ar_comment` varbinary(767) NOT NULL DEFAULT '',
  `ar_comment_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `ar_user` int(10) unsigned NOT NULL DEFAULT 0,
  `ar_user_text` varbinary(255) NOT NULL DEFAULT '',
  `ar_actor` bigint(20) unsigned NOT NULL DEFAULT 0,
  `ar_timestamp` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `ar_minor_edit` tinyint(4) NOT NULL DEFAULT 0,
  `ar_rev_id` int(10) unsigned NOT NULL,
  `ar_text_id` int(10) unsigned NOT NULL DEFAULT 0,
  `ar_deleted` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `ar_len` int(10) unsigned DEFAULT NULL,
  `ar_page_id` int(10) unsigned DEFAULT NULL,
  `ar_parent_id` int(10) unsigned DEFAULT NULL,
  `ar_sha1` varbinary(32) NOT NULL DEFAULT '',
  `ar_content_model` varbinary(32) DEFAULT NULL,
  `ar_content_format` varbinary(64) DEFAULT NULL,
  PRIMARY KEY (`ar_id`),
  KEY `name_title_timestamp` (`ar_namespace`,`ar_title`,`ar_timestamp`),
  KEY `ar_usertext_timestamp` (`ar_user_text`,`ar_timestamp`),
  KEY `ar_actor_timestamp` (`ar_actor`,`ar_timestamp`),
  KEY `ar_revid` (`ar_rev_id`),
  KEY `usertext_timestamp` (`ar_user_text`,`ar_timestamp`)
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
  `ct_rc_id` int(11) DEFAULT NULL,
  `ct_log_id` int(10) unsigned DEFAULT NULL,
  `ct_rev_id` int(10) unsigned DEFAULT NULL,
  `ct_tag` varbinary(255) NOT NULL,
  `ct_params` blob DEFAULT NULL,
  PRIMARY KEY (`ct_id`),
  UNIQUE KEY `change_tag_rc_tag` (`ct_rc_id`,`ct_tag`),
  UNIQUE KEY `change_tag_log_tag` (`ct_log_id`,`ct_tag`),
  UNIQUE KEY `change_tag_rev_tag` (`ct_rev_id`,`ct_tag`),
  KEY `change_tag_tag_id` (`ct_tag`,`ct_rc_id`,`ct_rev_id`,`ct_log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_comment` (
  `comment_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_hash` int(11) NOT NULL,
  `comment_text` blob NOT NULL,
  `comment_data` blob DEFAULT NULL,
  PRIMARY KEY (`comment_id`),
  KEY `comment_hash` (`comment_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_content` (
  `content_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `content_size` int(10) unsigned NOT NULL,
  `content_sha1` varbinary(32) NOT NULL,
  `content_model` smallint(5) unsigned NOT NULL,
  `content_address` varbinary(255) NOT NULL,
  PRIMARY KEY (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_content_models` (
  `model_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `model_name` varbinary(64) NOT NULL,
  PRIMARY KEY (`model_id`),
  UNIQUE KEY `model_name` (`model_name`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_externallinks` (
  `el_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `el_from` int(10) unsigned NOT NULL DEFAULT 0,
  `el_to` blob NOT NULL,
  `el_index` blob NOT NULL,
  `el_index_60` varbinary(60) NOT NULL DEFAULT '',
  PRIMARY KEY (`el_id`),
  KEY `el_from` (`el_from`,`el_to`(40)),
  KEY `el_to` (`el_to`(60),`el_from`),
  KEY `el_index` (`el_index`(60)),
  KEY `el_index_60` (`el_index_60`,`el_id`),
  KEY `el_from_index_60` (`el_from`,`el_index_60`,`el_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

INSERT INTO `<<prefix>>_externallinks` (`el_id`, `el_from`, `el_to`, `el_index`, `el_index_60`) VALUES
(1,	1,	'https://www.mediawiki.org/wiki/Special:MyLanguage/Help:Contents',	'https://org.mediawiki.www./wiki/Special:MyLanguage/Help:Contents',	UNHEX('')),
(2,	1,	'https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:Configuration_settings',	'https://org.mediawiki.www./wiki/Special:MyLanguage/Manual:Configuration_settings',	UNHEX('')),
(3,	1,	'https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:FAQ',	'https://org.mediawiki.www./wiki/Special:MyLanguage/Manual:FAQ',	UNHEX('')),
(4,	1,	'https://lists.wikimedia.org/mailman/listinfo/mediawiki-announce',	'https://org.wikimedia.lists./mailman/listinfo/mediawiki-announce',	UNHEX('')),
(5,	1,	'https://www.mediawiki.org/wiki/Special:MyLanguage/Localisation#Translation_resources',	'https://org.mediawiki.www./wiki/Special:MyLanguage/Localisation#Translation_resources',	UNHEX('')),
(6,	1,	'https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:Combating_spam',	'https://org.mediawiki.www./wiki/Special:MyLanguage/Manual:Combating_spam',	UNHEX(''));

CREATE TABLE `<<prefix>>_filearchive` (
  `fa_id` int(11) NOT NULL AUTO_INCREMENT,
  `fa_name` varbinary(255) NOT NULL DEFAULT '',
  `fa_archive_name` varbinary(255) DEFAULT '',
  `fa_storage_group` varbinary(16) DEFAULT NULL,
  `fa_storage_key` varbinary(64) DEFAULT '',
  `fa_deleted_user` int(11) DEFAULT NULL,
  `fa_deleted_timestamp` binary(14) DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `fa_deleted_reason` varbinary(767) DEFAULT '',
  `fa_deleted_reason_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `fa_size` int(10) unsigned DEFAULT 0,
  `fa_width` int(11) DEFAULT 0,
  `fa_height` int(11) DEFAULT 0,
  `fa_metadata` mediumblob DEFAULT NULL,
  `fa_bits` int(11) DEFAULT 0,
  `fa_media_type` enum('UNKNOWN','BITMAP','DRAWING','AUDIO','VIDEO','MULTIMEDIA','OFFICE','TEXT','EXECUTABLE','ARCHIVE','3D') DEFAULT NULL,
  `fa_major_mime` enum('unknown','application','audio','image','text','video','message','model','multipart','chemical') DEFAULT 'unknown',
  `fa_minor_mime` varbinary(100) DEFAULT 'unknown',
  `fa_description` varbinary(767) DEFAULT '',
  `fa_description_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `fa_user` int(10) unsigned DEFAULT 0,
  `fa_user_text` varbinary(255) DEFAULT '',
  `fa_actor` bigint(20) unsigned NOT NULL DEFAULT 0,
  `fa_timestamp` binary(14) DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `fa_deleted` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `fa_sha1` varbinary(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`fa_id`),
  KEY `fa_name` (`fa_name`,`fa_timestamp`),
  KEY `fa_storage_group` (`fa_storage_group`,`fa_storage_key`),
  KEY `fa_deleted_timestamp` (`fa_deleted_timestamp`),
  KEY `fa_user_timestamp` (`fa_user_text`,`fa_timestamp`),
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
  `img_description` varbinary(767) NOT NULL DEFAULT '',
  `img_description_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `img_user` int(10) unsigned NOT NULL DEFAULT 0,
  `img_user_text` varbinary(255) NOT NULL DEFAULT '',
  `img_actor` bigint(20) unsigned NOT NULL DEFAULT 0,
  `img_timestamp` varbinary(14) NOT NULL DEFAULT '',
  `img_sha1` varbinary(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`img_name`),
  KEY `img_user_timestamp` (`img_user`,`img_timestamp`),
  KEY `img_usertext_timestamp` (`img_user_text`,`img_timestamp`),
  KEY `img_actor_timestamp` (`img_actor`,`img_timestamp`),
  KEY `img_size` (`img_size`),
  KEY `img_timestamp` (`img_timestamp`),
  KEY `img_sha1` (`img_sha1`(10)),
  KEY `img_media_mime` (`img_media_type`,`img_major_mime`,`img_minor_mime`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_imagelinks` (
  `il_from` int(10) unsigned NOT NULL DEFAULT 0,
  `il_from_namespace` int(11) NOT NULL DEFAULT 0,
  `il_to` varbinary(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`il_from`,`il_to`),
  KEY `il_to` (`il_to`,`il_from`),
  KEY `il_backlinks_namespace` (`il_from_namespace`,`il_to`,`il_from`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_image_comment_temp` (
  `imgcomment_name` varbinary(255) NOT NULL,
  `imgcomment_description_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`imgcomment_name`,`imgcomment_description_id`),
  UNIQUE KEY `imgcomment_name` (`imgcomment_name`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_interwiki` (
  `iw_prefix` varbinary(32) NOT NULL,
  `iw_url` blob NOT NULL,
  `iw_api` blob NOT NULL,
  `iw_wikiid` varbinary(64) NOT NULL,
  `iw_local` tinyint(1) NOT NULL,
  `iw_trans` tinyint(4) NOT NULL DEFAULT 0,
  UNIQUE KEY `iw_prefix` (`iw_prefix`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

INSERT INTO `<<prefix>>_interwiki` (`iw_prefix`, `iw_url`, `iw_api`, `iw_wikiid`, `iw_local`, `iw_trans`) VALUES
(UNHEX('6163726F6E796D'),	'https://www.acronymfinder.com/~/search/af.aspx?string=exact&Acronym=$1',	'',	UNHEX(''),	0,	0),
(UNHEX('6164766F6761746F'),	'http://www.advogato.org/$1',	'',	UNHEX(''),	0,	0),
(UNHEX('6172786976'),	'https://www.arxiv.org/abs/$1',	'',	UNHEX(''),	0,	0),
(UNHEX('633266696E64'),	'http://c2.com/cgi/wiki?FindPage&value=$1',	'',	UNHEX(''),	0,	0),
(UNHEX('6361636865'),	'https://www.google.com/search?q=cache:$1',	'',	UNHEX(''),	0,	0),
(UNHEX('636F6D6D6F6E73'),	'https://commons.wikimedia.org/wiki/$1',	'https://commons.wikimedia.org/w/api.php',	UNHEX(''),	0,	0),
(UNHEX('64696374696F6E617279'),	'http://www.dict.org/bin/Dict?Database=*&Form=Dict1&Strategy=*&Query=$1',	'',	UNHEX(''),	0,	0),
(UNHEX('646F69'),	'https://dx.doi.org/$1',	'',	UNHEX(''),	0,	0),
(UNHEX('6472756D636F72707377696B69'),	'http://www.drumcorpswiki.com/$1',	'http://drumcorpswiki.com/api.php',	UNHEX(''),	0,	0),
(UNHEX('64776A77696B69'),	'http://www.suberic.net/cgi-bin/dwj/wiki.cgi?$1',	'',	UNHEX(''),	0,	0),
(UNHEX('656C69627265'),	'http://enciclopedia.us.es/index.php/$1',	'http://enciclopedia.us.es/api.php',	UNHEX(''),	0,	0),
(UNHEX('656D61637377696B69'),	'https://www.emacswiki.org/cgi-bin/wiki.pl?$1',	'',	UNHEX(''),	0,	0),
(UNHEX('666F6C646F63'),	'https://foldoc.org/?$1',	'',	UNHEX(''),	0,	0),
(UNHEX('666F7877696B69'),	'https://fox.wikis.com/wc.dll?Wiki~$1',	'',	UNHEX(''),	0,	0),
(UNHEX('667265656273646D616E'),	'https://www.FreeBSD.org/cgi/man.cgi?apropos=1&query=$1',	'',	UNHEX(''),	0,	0),
(UNHEX('67656E746F6F2D77696B69'),	'http://gentoo-wiki.com/$1',	'',	UNHEX(''),	0,	0),
(UNHEX('676F6F676C65'),	'https://www.google.com/search?q=$1',	'',	UNHEX(''),	0,	0),
(UNHEX('676F6F676C6567726F757073'),	'https://groups.google.com/groups?q=$1',	'',	UNHEX(''),	0,	0),
(UNHEX('68616D6D6F6E6477696B69'),	'http://www.dairiki.org/HammondWiki/$1',	'',	UNHEX(''),	0,	0),
(UNHEX('687277696B69'),	'http://www.hrwiki.org/wiki/$1',	'http://www.hrwiki.org/w/api.php',	UNHEX(''),	0,	0),
(UNHEX('696D6462'),	'http://www.imdb.com/find?q=$1&tt=on',	'',	UNHEX(''),	0,	0),
(UNHEX('6B6D77696B69'),	'https://kmwiki.wikispaces.com/$1',	'',	UNHEX(''),	0,	0),
(UNHEX('6C696E757877696B69'),	'http://linuxwiki.de/$1',	'',	UNHEX(''),	0,	0),
(UNHEX('6C6F6A62616E'),	'https://mw.lojban.org/papri/$1',	'',	UNHEX(''),	0,	0),
(UNHEX('6C7177696B69'),	'http://wiki.linuxquestions.org/wiki/$1',	'',	UNHEX(''),	0,	0),
(UNHEX('6D65617462616C6C'),	'http://www.usemod.com/cgi-bin/mb.pl?$1',	'',	UNHEX(''),	0,	0),
(UNHEX('6D6564696177696B6977696B69'),	'https://www.mediawiki.org/wiki/$1',	'https://www.mediawiki.org/w/api.php',	UNHEX(''),	0,	0),
(UNHEX('6D656D6F7279616C706861'),	'http://en.memory-alpha.org/wiki/$1',	'http://en.memory-alpha.org/api.php',	UNHEX(''),	0,	0),
(UNHEX('6D65746177696B69'),	'http://sunir.org/apps/meta.pl?$1',	'',	UNHEX(''),	0,	0),
(UNHEX('6D65746177696B696D65646961'),	'https://meta.wikimedia.org/wiki/$1',	'https://meta.wikimedia.org/w/api.php',	UNHEX(''),	0,	0),
(UNHEX('6D6F7A696C6C6177696B69'),	'https://wiki.mozilla.org/$1',	'https://wiki.mozilla.org/api.php',	UNHEX(''),	0,	0),
(UNHEX('6D77'),	'https://www.mediawiki.org/wiki/$1',	'https://www.mediawiki.org/w/api.php',	UNHEX(''),	0,	0),
(UNHEX('6F656973'),	'https://oeis.org/$1',	'',	UNHEX(''),	0,	0),
(UNHEX('6F70656E77696B69'),	'http://openwiki.com/ow.asp?$1',	'',	UNHEX(''),	0,	0),
(UNHEX('706D6964'),	'https://www.ncbi.nlm.nih.gov/pubmed/$1?dopt=Abstract',	'',	UNHEX(''),	0,	0),
(UNHEX('707974686F6E696E666F'),	'https://wiki.python.org/moin/$1',	'',	UNHEX(''),	0,	0),
(UNHEX('726663'),	'https://tools.ietf.org/html/rfc$1',	'',	UNHEX(''),	0,	0),
(UNHEX('73323377696B69'),	'http://s23.org/wiki/$1',	'http://s23.org/w/api.php',	UNHEX(''),	0,	0),
(UNHEX('73656174746C65776972656C657373'),	'http://seattlewireless.net/$1',	'',	UNHEX(''),	0,	0),
(UNHEX('73656E736569736C696272617279'),	'https://senseis.xmp.net/?$1',	'',	UNHEX(''),	0,	0),
(UNHEX('73686F757477696B69'),	'http://www.shoutwiki.com/wiki/$1',	'http://www.shoutwiki.com/w/api.php',	UNHEX(''),	0,	0),
(UNHEX('73717565616B'),	'http://wiki.squeak.org/squeak/$1',	'',	UNHEX(''),	0,	0),
(UNHEX('7468656F7065646961'),	'https://www.theopedia.com/$1',	'',	UNHEX(''),	0,	0),
(UNHEX('746D6277'),	'http://www.tmbw.net/wiki/$1',	'http://tmbw.net/wiki/api.php',	UNHEX(''),	0,	0),
(UNHEX('746D6E6574'),	'http://www.technomanifestos.net/?$1',	'',	UNHEX(''),	0,	0),
(UNHEX('7477696B69'),	'http://twiki.org/cgi-bin/view/$1',	'',	UNHEX(''),	0,	0),
(UNHEX('756E6379636C6F7065646961'),	'https://en.uncyclopedia.co/wiki/$1',	'https://en.uncyclopedia.co/w/api.php',	UNHEX(''),	0,	0),
(UNHEX('756E7265616C'),	'https://wiki.beyondunreal.com/$1',	'https://wiki.beyondunreal.com/w/api.php',	UNHEX(''),	0,	0),
(UNHEX('7573656D6F64'),	'http://www.usemod.com/cgi-bin/wiki.pl?$1',	'',	UNHEX(''),	0,	0),
(UNHEX('77696B69'),	'http://c2.com/cgi/wiki?$1',	'',	UNHEX(''),	0,	0),
(UNHEX('77696B6961'),	'http://www.wikia.com/wiki/$1',	'',	UNHEX(''),	0,	0),
(UNHEX('77696B69626F6F6B73'),	'https://en.wikibooks.org/wiki/$1',	'https://en.wikibooks.org/w/api.php',	UNHEX(''),	0,	0),
(UNHEX('77696B6964617461'),	'https://www.wikidata.org/wiki/$1',	'https://www.wikidata.org/w/api.php',	UNHEX(''),	0,	0),
(UNHEX('77696B696631'),	'http://www.wikif1.org/$1',	'',	UNHEX(''),	0,	0),
(UNHEX('77696B69686F77'),	'https://www.wikihow.com/$1',	'https://www.wikihow.com/api.php',	UNHEX(''),	0,	0),
(UNHEX('77696B696D65646961'),	'https://wikimediafoundation.org/wiki/$1',	'https://wikimediafoundation.org/w/api.php',	UNHEX(''),	0,	0),
(UNHEX('77696B696E657773'),	'https://en.wikinews.org/wiki/$1',	'https://en.wikinews.org/w/api.php',	UNHEX(''),	0,	0),
(UNHEX('77696B696E666F'),	'http://wikinfo.co/English/index.php/$1',	'',	UNHEX(''),	0,	0),
(UNHEX('77696B697065646961'),	'https://en.wikipedia.org/wiki/$1',	'https://en.wikipedia.org/w/api.php',	UNHEX(''),	0,	0),
(UNHEX('77696B6971756F7465'),	'https://en.wikiquote.org/wiki/$1',	'https://en.wikiquote.org/w/api.php',	UNHEX(''),	0,	0),
(UNHEX('77696B69736F75726365'),	'https://wikisource.org/wiki/$1',	'https://wikisource.org/w/api.php',	UNHEX(''),	0,	0),
(UNHEX('77696B6973706563696573'),	'https://species.wikimedia.org/wiki/$1',	'https://species.wikimedia.org/w/api.php',	UNHEX(''),	0,	0),
(UNHEX('77696B6976657273697479'),	'https://en.wikiversity.org/wiki/$1',	'https://en.wikiversity.org/w/api.php',	UNHEX(''),	0,	0),
(UNHEX('77696B69766F79616765'),	'https://en.wikivoyage.org/wiki/$1',	'https://en.wikivoyage.org/w/api.php',	UNHEX(''),	0,	0),
(UNHEX('77696B74'),	'https://en.wiktionary.org/wiki/$1',	'https://en.wiktionary.org/w/api.php',	UNHEX(''),	0,	0),
(UNHEX('77696B74696F6E617279'),	'https://en.wiktionary.org/wiki/$1',	'https://en.wiktionary.org/w/api.php',	UNHEX(''),	0,	0);

CREATE TABLE `<<prefix>>_ipblocks` (
  `ipb_id` int(11) NOT NULL AUTO_INCREMENT,
  `ipb_address` tinyblob NOT NULL,
  `ipb_user` int(10) unsigned NOT NULL DEFAULT 0,
  `ipb_by` int(10) unsigned NOT NULL DEFAULT 0,
  `ipb_by_text` varbinary(255) NOT NULL DEFAULT '',
  `ipb_by_actor` bigint(20) unsigned NOT NULL DEFAULT 0,
  `ipb_reason` varbinary(767) NOT NULL DEFAULT '',
  `ipb_reason_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `ipb_timestamp` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `ipb_auto` tinyint(1) NOT NULL DEFAULT 0,
  `ipb_anon_only` tinyint(1) NOT NULL DEFAULT 0,
  `ipb_create_account` tinyint(1) NOT NULL DEFAULT 1,
  `ipb_enable_autoblock` tinyint(1) NOT NULL DEFAULT 1,
  `ipb_expiry` varbinary(14) NOT NULL DEFAULT '',
  `ipb_range_start` tinyblob NOT NULL,
  `ipb_range_end` tinyblob NOT NULL,
  `ipb_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `ipb_block_email` tinyint(1) NOT NULL DEFAULT 0,
  `ipb_allow_usertalk` tinyint(1) NOT NULL DEFAULT 0,
  `ipb_parent_block_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`ipb_id`),
  UNIQUE KEY `ipb_address` (`ipb_address`(255),`ipb_user`,`ipb_auto`,`ipb_anon_only`),
  KEY `ipb_user` (`ipb_user`),
  KEY `ipb_range` (`ipb_range_start`(8),`ipb_range_end`(8)),
  KEY `ipb_timestamp` (`ipb_timestamp`),
  KEY `ipb_expiry` (`ipb_expiry`),
  KEY `ipb_parent_block_id` (`ipb_parent_block_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_ip_changes` (
  `ipc_rev_id` int(10) unsigned NOT NULL DEFAULT 0,
  `ipc_rev_timestamp` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `ipc_hex` varbinary(35) NOT NULL DEFAULT '',
  PRIMARY KEY (`ipc_rev_id`),
  KEY `ipc_rev_timestamp` (`ipc_rev_timestamp`),
  KEY `ipc_hex_time` (`ipc_hex`,`ipc_rev_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_iwlinks` (
  `iwl_from` int(10) unsigned NOT NULL DEFAULT 0,
  `iwl_prefix` varbinary(20) NOT NULL DEFAULT '',
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
  `job_timestamp` varbinary(14) DEFAULT NULL,
  `job_params` blob NOT NULL,
  `job_random` int(10) unsigned NOT NULL DEFAULT 0,
  `job_attempts` int(10) unsigned NOT NULL DEFAULT 0,
  `job_token` varbinary(32) NOT NULL DEFAULT '',
  `job_token_timestamp` varbinary(14) DEFAULT NULL,
  `job_sha1` varbinary(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`job_id`),
  KEY `job_sha1` (`job_sha1`),
  KEY `job_cmd_token` (`job_cmd`,`job_token`,`job_random`),
  KEY `job_cmd_token_id` (`job_cmd`,`job_token`,`job_id`),
  KEY `job_cmd` (`job_cmd`,`job_namespace`,`job_title`,`job_params`(128)),
  KEY `job_timestamp` (`job_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

INSERT INTO `<<prefix>>_job` (`job_id`, `job_cmd`, `job_namespace`, `job_title`, `job_timestamp`, `job_params`, `job_random`, `job_attempts`, `job_token`, `job_token_timestamp`, `job_sha1`) VALUES
(1,	UNHEX('7573657247726F7570457870697279'),	0,	UNHEX('4D61696E5F50616765'),	UNHEX('3230313831313231313234333531'),	'a:1:{s:9:\"requestId\";s:24:\"2254576c0de5e9cc39d0fd8e\";}',	2116596865,	0,	UNHEX(''),	NULL,	UNHEX('706A30366F67356F7631326C64617665676262303669626978797A34703130')),
(2,	UNHEX('68746D6C4361636865557064617465'),	0,	UNHEX('4D61696E5F50616765'),	UNHEX('3230313831313231313234333532'),	'a:8:{s:5:\"table\";s:9:\"pagelinks\";s:9:\"recursive\";b:1;s:13:\"rootJobIsSelf\";b:1;s:16:\"rootJobSignature\";s:40:\"a61c71eb6e6b2d48a20167f06eb94a7c0cd27d43\";s:16:\"rootJobTimestamp\";s:14:\"20181121124352\";s:11:\"causeAction\";s:10:\"page-touch\";s:10:\"causeAgent\";s:7:\"unknown\";s:9:\"requestId\";s:24:\"2254576c0de5e9cc39d0fd8e\";}',	951724426,	0,	UNHEX(''),	NULL,	UNHEX('653566356475696D677A35357472616C736A757A73313269786F3275396732')),
(3,	UNHEX('68746D6C4361636865557064617465'),	0,	UNHEX('4D61696E5F50616765'),	UNHEX('3230313831313231313234333532'),	'a:8:{s:5:\"table\";s:13:\"templatelinks\";s:9:\"recursive\";b:1;s:13:\"rootJobIsSelf\";b:1;s:16:\"rootJobSignature\";s:40:\"74ce50dcd6ec47a5d174f89562dd93805541fc6e\";s:16:\"rootJobTimestamp\";s:14:\"20181121124352\";s:11:\"causeAction\";s:11:\"page-create\";s:10:\"causeAgent\";s:7:\"unknown\";s:9:\"requestId\";s:24:\"2254576c0de5e9cc39d0fd8e\";}',	527866552,	0,	UNHEX(''),	NULL,	UNHEX('66356C3071333979333433626F6B6B31636B616F66346761796D6B36776966'));

CREATE TABLE `<<prefix>>_l10n_cache` (
  `lc_lang` varbinary(32) NOT NULL,
  `lc_key` varbinary(255) NOT NULL,
  `lc_value` mediumblob NOT NULL,
  PRIMARY KEY (`lc_lang`,`lc_key`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_langlinks` (
  `ll_from` int(10) unsigned NOT NULL DEFAULT 0,
  `ll_lang` varbinary(20) NOT NULL DEFAULT '',
  `ll_title` varbinary(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ll_from`,`ll_lang`),
  KEY `ll_lang` (`ll_lang`,`ll_title`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_logging` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `log_type` varbinary(32) NOT NULL DEFAULT '',
  `log_action` varbinary(32) NOT NULL DEFAULT '',
  `log_timestamp` binary(14) NOT NULL DEFAULT '19700101000000',
  `log_user` int(10) unsigned NOT NULL DEFAULT 0,
  `log_user_text` varbinary(255) NOT NULL DEFAULT '',
  `log_actor` bigint(20) unsigned NOT NULL DEFAULT 0,
  `log_namespace` int(11) NOT NULL DEFAULT 0,
  `log_title` varbinary(255) NOT NULL DEFAULT '',
  `log_page` int(10) unsigned DEFAULT NULL,
  `log_comment` varbinary(767) NOT NULL DEFAULT '',
  `log_comment_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `log_params` blob NOT NULL,
  `log_deleted` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`log_id`),
  KEY `type_time` (`log_type`,`log_timestamp`),
  KEY `user_time` (`log_user`,`log_timestamp`),
  KEY `actor_time` (`log_actor`,`log_timestamp`),
  KEY `page_time` (`log_namespace`,`log_title`,`log_timestamp`),
  KEY `times` (`log_timestamp`),
  KEY `log_user_type_time` (`log_user`,`log_type`,`log_timestamp`),
  KEY `log_actor_type_time` (`log_actor`,`log_type`,`log_timestamp`),
  KEY `log_page_id_time` (`log_page`,`log_timestamp`),
  KEY `type_action` (`log_type`,`log_action`,`log_timestamp`),
  KEY `log_user_text_type_time` (`log_user_text`,`log_type`,`log_timestamp`),
  KEY `log_user_text_time` (`log_user_text`,`log_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_log_search` (
  `ls_field` varbinary(32) NOT NULL,
  `ls_value` varbinary(255) NOT NULL,
  `ls_log_id` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`ls_field`,`ls_value`,`ls_log_id`),
  KEY `ls_log_id` (`ls_log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_module_deps` (
  `md_module` varbinary(255) NOT NULL,
  `md_skin` varbinary(32) NOT NULL,
  `md_deps` mediumblob NOT NULL,
  PRIMARY KEY (`md_module`,`md_skin`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_objectcache` (
  `keyname` varbinary(255) NOT NULL DEFAULT '',
  `value` mediumblob DEFAULT NULL,
  `exptime` datetime DEFAULT NULL,
  PRIMARY KEY (`keyname`),
  KEY `exptime` (`exptime`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

INSERT INTO `<<prefix>>_objectcache` (`keyname`, `value`, `exptime`) VALUES
(UNHEX('77696B693A6D657373616765733A656E'),	'��\n�@�e�`w=o�]e!h�������-�����0�zO�<�vh:@��԰S��ܴϑ��u��Źp_k�`|w4}^���E�@YU�V��',	'2038-01-19 03:14:07');

CREATE TABLE `<<prefix>>_oldimage` (
  `oi_name` varbinary(255) NOT NULL DEFAULT '',
  `oi_archive_name` varbinary(255) NOT NULL DEFAULT '',
  `oi_size` int(10) unsigned NOT NULL DEFAULT 0,
  `oi_width` int(11) NOT NULL DEFAULT 0,
  `oi_height` int(11) NOT NULL DEFAULT 0,
  `oi_bits` int(11) NOT NULL DEFAULT 0,
  `oi_description` varbinary(767) NOT NULL DEFAULT '',
  `oi_description_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `oi_user` int(10) unsigned NOT NULL DEFAULT 0,
  `oi_user_text` varbinary(255) NOT NULL DEFAULT '',
  `oi_actor` bigint(20) unsigned NOT NULL DEFAULT 0,
  `oi_timestamp` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `oi_metadata` mediumblob NOT NULL,
  `oi_media_type` enum('UNKNOWN','BITMAP','DRAWING','AUDIO','VIDEO','MULTIMEDIA','OFFICE','TEXT','EXECUTABLE','ARCHIVE','3D') DEFAULT NULL,
  `oi_major_mime` enum('unknown','application','audio','image','text','video','message','model','multipart','chemical') NOT NULL DEFAULT 'unknown',
  `oi_minor_mime` varbinary(100) NOT NULL DEFAULT 'unknown',
  `oi_deleted` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `oi_sha1` varbinary(32) NOT NULL DEFAULT '',
  KEY `oi_usertext_timestamp` (`oi_user_text`,`oi_timestamp`),
  KEY `oi_actor_timestamp` (`oi_actor`,`oi_timestamp`),
  KEY `oi_name_timestamp` (`oi_name`,`oi_timestamp`),
  KEY `oi_name_archive_name` (`oi_name`,`oi_archive_name`(14)),
  KEY `oi_sha1` (`oi_sha1`(10))
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_page` (
  `page_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `page_namespace` int(11) NOT NULL,
  `page_title` varbinary(255) NOT NULL,
  `page_restrictions` tinyblob NOT NULL,
  `page_is_redirect` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `page_is_new` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `page_random` double unsigned NOT NULL,
  `page_touched` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `page_links_updated` varbinary(14) DEFAULT NULL,
  `page_latest` int(10) unsigned NOT NULL,
  `page_len` int(10) unsigned NOT NULL,
  `page_content_model` varbinary(32) DEFAULT NULL,
  `page_lang` varbinary(35) DEFAULT NULL,
  PRIMARY KEY (`page_id`),
  UNIQUE KEY `name_title` (`page_namespace`,`page_title`),
  KEY `page_random` (`page_random`),
  KEY `page_len` (`page_len`),
  KEY `page_redirect_namespace_len` (`page_is_redirect`,`page_namespace`,`page_len`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

INSERT INTO `<<prefix>>_page` (`page_id`, `page_namespace`, `page_title`, `page_restrictions`, `page_is_redirect`, `page_is_new`, `page_random`, `page_touched`, `page_links_updated`, `page_latest`, `page_len`, `page_content_model`, `page_lang`) VALUES
(1,	0,	UNHEX('4D61696E5F50616765'),	'',	0,	1,	0.499397461217,	UNHEX('3230313831313231313234333532'),	UNHEX('3230313831313231313234333532'),	1,	735,	UNHEX('77696B6974657874'),	NULL);

CREATE TABLE `<<prefix>>_pagelinks` (
  `pl_from` int(10) unsigned NOT NULL DEFAULT 0,
  `pl_from_namespace` int(11) NOT NULL DEFAULT 0,
  `pl_namespace` int(11) NOT NULL DEFAULT 0,
  `pl_title` varbinary(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`pl_from`,`pl_namespace`,`pl_title`),
  KEY `pl_namespace` (`pl_namespace`,`pl_title`,`pl_from`),
  KEY `pl_backlinks_namespace` (`pl_from_namespace`,`pl_namespace`,`pl_title`,`pl_from`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_page_props` (
  `pp_page` int(11) NOT NULL,
  `pp_propname` varbinary(60) NOT NULL,
  `pp_value` blob NOT NULL,
  `pp_sortkey` float DEFAULT NULL,
  UNIQUE KEY `pp_page_propname` (`pp_page`,`pp_propname`),
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
  `pt_reason` varbinary(767) DEFAULT '',
  `pt_reason_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `pt_timestamp` binary(14) NOT NULL,
  `pt_expiry` varbinary(14) NOT NULL DEFAULT '',
  `pt_create_perm` varbinary(60) NOT NULL,
  UNIQUE KEY `pt_namespace_title` (`pt_namespace`,`pt_title`),
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
  `rc_id` int(11) NOT NULL AUTO_INCREMENT,
  `rc_timestamp` varbinary(14) NOT NULL DEFAULT '',
  `rc_user` int(10) unsigned NOT NULL DEFAULT 0,
  `rc_user_text` varbinary(255) NOT NULL DEFAULT '',
  `rc_actor` bigint(20) unsigned NOT NULL DEFAULT 0,
  `rc_namespace` int(11) NOT NULL DEFAULT 0,
  `rc_title` varbinary(255) NOT NULL DEFAULT '',
  `rc_comment` varbinary(767) NOT NULL DEFAULT '',
  `rc_comment_id` bigint(20) unsigned NOT NULL DEFAULT 0,
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
  KEY `new_name_timestamp` (`rc_new`,`rc_namespace`,`rc_timestamp`),
  KEY `rc_ip` (`rc_ip`),
  KEY `rc_ns_usertext` (`rc_namespace`,`rc_user_text`),
  KEY `rc_ns_actor` (`rc_namespace`,`rc_actor`),
  KEY `rc_user_text` (`rc_user_text`,`rc_timestamp`),
  KEY `rc_actor` (`rc_actor`,`rc_timestamp`),
  KEY `rc_name_type_patrolled_timestamp` (`rc_namespace`,`rc_type`,`rc_patrolled`,`rc_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

INSERT INTO `<<prefix>>_recentchanges` (`rc_id`, `rc_timestamp`, `rc_user`, `rc_user_text`, `rc_actor`, `rc_namespace`, `rc_title`, `rc_comment`, `rc_comment_id`, `rc_minor`, `rc_bot`, `rc_new`, `rc_cur_id`, `rc_this_oldid`, `rc_last_oldid`, `rc_type`, `rc_source`, `rc_patrolled`, `rc_ip`, `rc_old_len`, `rc_new_len`, `rc_deleted`, `rc_logid`, `rc_log_type`, `rc_log_action`, `rc_params`) VALUES
(1,	UNHEX('3230313831313231313234333532'),	0,	UNHEX('4D6564696157696B692064656661756C74'),	0,	0,	UNHEX('4D61696E5F50616765'),	UNHEX(''),	0,	0,	0,	1,	1,	1,	0,	1,	UNHEX('6D772E6E6577'),	0,	UNHEX('3132372E302E302E31'),	0,	735,	0,	0,	NULL,	UNHEX(''),	'');

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
  `rev_text_id` int(10) unsigned NOT NULL DEFAULT 0,
  `rev_comment` varbinary(767) NOT NULL DEFAULT '',
  `rev_user` int(10) unsigned NOT NULL DEFAULT 0,
  `rev_user_text` varbinary(255) NOT NULL DEFAULT '',
  `rev_timestamp` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `rev_minor_edit` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `rev_deleted` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `rev_len` int(10) unsigned DEFAULT NULL,
  `rev_parent_id` int(10) unsigned DEFAULT NULL,
  `rev_sha1` varbinary(32) NOT NULL DEFAULT '',
  `rev_content_model` varbinary(32) DEFAULT NULL,
  `rev_content_format` varbinary(64) DEFAULT NULL,
  PRIMARY KEY (`rev_id`),
  KEY `rev_page_id` (`rev_page`,`rev_id`),
  KEY `rev_timestamp` (`rev_timestamp`),
  KEY `page_timestamp` (`rev_page`,`rev_timestamp`),
  KEY `user_timestamp` (`rev_user`,`rev_timestamp`),
  KEY `usertext_timestamp` (`rev_user_text`,`rev_timestamp`),
  KEY `page_user_timestamp` (`rev_page`,`rev_user`,`rev_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary MAX_ROWS=10000000 AVG_ROW_LENGTH=1024;

INSERT INTO `<<prefix>>_revision` (`rev_id`, `rev_page`, `rev_text_id`, `rev_comment`, `rev_user`, `rev_user_text`, `rev_timestamp`, `rev_minor_edit`, `rev_deleted`, `rev_len`, `rev_parent_id`, `rev_sha1`, `rev_content_model`, `rev_content_format`) VALUES
(1,	1,	1,	UNHEX(''),	0,	UNHEX('4D6564696157696B692064656661756C74'),	UNHEX('3230313831313231313234333532'),	0,	0,	735,	0,	UNHEX('6135776568756C646430676F32756E69616777767836366E36633830697271'),	NULL,	NULL);

CREATE TABLE `<<prefix>>_revision_actor_temp` (
  `revactor_rev` int(10) unsigned NOT NULL,
  `revactor_actor` bigint(20) unsigned NOT NULL,
  `revactor_timestamp` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `revactor_page` int(10) unsigned NOT NULL,
  PRIMARY KEY (`revactor_rev`,`revactor_actor`),
  UNIQUE KEY `revactor_rev` (`revactor_rev`),
  KEY `actor_timestamp` (`revactor_actor`,`revactor_timestamp`),
  KEY `page_actor_timestamp` (`revactor_page`,`revactor_actor`,`revactor_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_revision_comment_temp` (
  `revcomment_rev` int(10) unsigned NOT NULL,
  `revcomment_comment_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`revcomment_rev`,`revcomment_comment_id`),
  UNIQUE KEY `revcomment_rev` (`revcomment_rev`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_searchindex` (
  `si_page` int(10) unsigned NOT NULL,
  `si_title` varchar(255) NOT NULL DEFAULT '',
  `si_text` mediumtext NOT NULL,
  UNIQUE KEY `si_page` (`si_page`),
  FULLTEXT KEY `si_title` (`si_title`),
  FULLTEXT KEY `si_text` (`si_text`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `<<prefix>>_searchindex` (`si_page`, `si_title`, `si_text`) VALUES
(1,	'main page',	' mediawiki hasu800 been installed. consult theu800 user user\'su800 guide foru800 information onu800 using theu800 wiki software. getting started getting started getting started * configuration settings list * mediawiki faqu800 * mediawiki release mailing list * localise mediawiki foru800 your language * learn howu800 tou800 combat spam onu800 your wiki ');

CREATE TABLE `<<prefix>>_sites` (
  `site_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_global_key` varbinary(32) NOT NULL,
  `site_type` varbinary(32) NOT NULL,
  `site_group` varbinary(32) NOT NULL,
  `site_source` varbinary(32) NOT NULL,
  `site_language` varbinary(32) NOT NULL,
  `site_protocol` varbinary(32) NOT NULL,
  `site_domain` varbinary(255) NOT NULL,
  `site_data` blob NOT NULL,
  `site_forward` tinyint(1) NOT NULL,
  `site_config` blob NOT NULL,
  PRIMARY KEY (`site_id`),
  UNIQUE KEY `sites_global_key` (`site_global_key`),
  KEY `sites_type` (`site_type`),
  KEY `sites_group` (`site_group`),
  KEY `sites_source` (`site_source`),
  KEY `sites_language` (`site_language`),
  KEY `sites_protocol` (`site_protocol`),
  KEY `sites_domain` (`site_domain`),
  KEY `sites_forward` (`site_forward`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_site_identifiers` (
  `si_site` int(10) unsigned NOT NULL,
  `si_type` varbinary(32) NOT NULL,
  `si_key` varbinary(32) NOT NULL,
  UNIQUE KEY `site_ids_type` (`si_type`,`si_key`),
  KEY `site_ids_site` (`si_site`),
  KEY `site_ids_key` (`si_key`)
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

CREATE TABLE `<<prefix>>_slot_roles` (
  `role_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `role_name` varbinary(64) NOT NULL,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_tag_summary` (
  `ts_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ts_rc_id` int(11) DEFAULT NULL,
  `ts_log_id` int(10) unsigned DEFAULT NULL,
  `ts_rev_id` int(10) unsigned DEFAULT NULL,
  `ts_tags` blob NOT NULL,
  PRIMARY KEY (`ts_id`),
  UNIQUE KEY `tag_summary_rc_id` (`ts_rc_id`),
  UNIQUE KEY `tag_summary_log_id` (`ts_log_id`),
  UNIQUE KEY `tag_summary_rev_id` (`ts_rev_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_templatelinks` (
  `tl_from` int(10) unsigned NOT NULL DEFAULT 0,
  `tl_from_namespace` int(11) NOT NULL DEFAULT 0,
  `tl_namespace` int(11) NOT NULL DEFAULT 0,
  `tl_title` varbinary(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`tl_from`,`tl_namespace`,`tl_title`),
  KEY `tl_namespace` (`tl_namespace`,`tl_title`,`tl_from`),
  KEY `tl_backlinks_namespace` (`tl_from_namespace`,`tl_namespace`,`tl_title`,`tl_from`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_text` (
  `old_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `old_text` mediumblob NOT NULL,
  `old_flags` tinyblob NOT NULL,
  PRIMARY KEY (`old_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary MAX_ROWS=10000000 AVG_ROW_LENGTH=10240;

INSERT INTO `<<prefix>>_text` (`old_id`, `old_text`, `old_flags`) VALUES
(1,	'<strong>OpenCura MediaWiki has been installed.</strong>\n\nConsult the [https://www.mediawiki.org/wiki/Special:MyLanguage/Help:Contents User\'s Guide] for information on using the wiki software.\n\n== Getting started ==\n* [https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:Configuration_settings Configuration settings list]\n* [https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:FAQ MediaWiki FAQ]\n* [https://lists.wikimedia.org/mailman/listinfo/mediawiki-announce MediaWiki release mailing list]\n* [https://www.mediawiki.org/wiki/Special:MyLanguage/Localisation#Translation_resources Localise MediaWiki for your language]\n* [https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:Combating_spam Learn how to combat spam on your wiki]',	'utf-8');

CREATE TABLE `<<prefix>>_transcache` (
  `tc_url` varbinary(255) NOT NULL,
  `tc_contents` blob DEFAULT NULL,
  `tc_time` binary(14) DEFAULT NULL,
  PRIMARY KEY (`tc_url`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_updatelog` (
  `ul_key` varbinary(255) NOT NULL,
  `ul_value` blob DEFAULT NULL,
  PRIMARY KEY (`ul_key`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

INSERT INTO `<<prefix>>_updatelog` (`ul_key`, `ul_value`) VALUES
(UNHEX('416464524643416E64504D4944496E74657277696B69'),	NULL),
(UNHEX('4368616E67654368616E67654F626A65637449642E73716C'),	NULL),
(UNHEX('44656C65746544656661756C744D65737361676573'),	NULL),
(UNHEX('46697844656661756C744A736F6E436F6E74656E745061676573'),	NULL),
(UNHEX('57696B69626173655C5265706F5C4D61696E74656E616E63655C506F70756C6174655465726D46756C6C456E746974794964'),	NULL),
(UNHEX('636C5F6669656C64735F757064617465'),	NULL),
(UNHEX('636C65616E757020656D7074792063617465676F72696573'),	NULL),
(UNHEX('636F6E76657274207472616E736361636865206669656C64'),	NULL),
(UNHEX('66696C65617263686976652D66615F6D616A6F725F6D696D652D70617463682D66615F6D616A6F725F6D696D652D6368656D6963616C2E73716C'),	NULL),
(UNHEX('6669782070726F746F636F6C2D72656C61746976652055524C7320696E2065787465726E616C6C696E6B73'),	NULL),
(UNHEX('696D6167652D696D675F6D616A6F725F6D696D652D70617463682D696D675F6D616A6F725F6D696D652D6368656D6963616C2E73716C'),	NULL),
(UNHEX('696D6167652D696D675F6D656469615F747970652D70617463682D6164642D33642E73716C'),	NULL),
(UNHEX('6D696D655F6D696E6F725F6C656E677468'),	NULL),
(UNHEX('6F6C64696D6167652D6F695F6D616A6F725F6D696D652D70617463682D6F695F6D616A6F725F6D696D652D6368656D6963616C2E73716C'),	NULL),
(UNHEX('706F70756C617465202A5F66726F6D5F6E616D657370616365'),	NULL),
(UNHEX('706F70756C6174652063617465676F7279'),	NULL),
(UNHEX('706F70756C6174652066615F73686131'),	NULL),
(UNHEX('706F70756C61746520696D675F73686131'),	NULL),
(UNHEX('706F70756C6174652069705F6368616E676573'),	NULL),
(UNHEX('706F70756C617465206C6F675F736561726368'),	NULL),
(UNHEX('706F70756C617465206C6F675F7573657274657874'),	NULL),
(UNHEX('706F70756C617465207265765F6C656E20616E642061725F6C656E'),	NULL),
(UNHEX('706F70756C617465207265765F706172656E745F6964'),	NULL),
(UNHEX('706F70756C617465207265765F73686131'),	NULL),
(UNHEX('726563656E746368616E6765732D72635F69702D70617463682D72635F69705F6D6F646966792E73716C'),	NULL),
(UNHEX('7265766973696F6E2D7265765F746578745F69642D70617463682D7265765F746578745F69642D64656661756C742E73716C'),	NULL),
(UNHEX('736974655F73746174732D70617463682D736974655F73746174732D6D6F646966792E73716C'),	NULL),
(UNHEX('757365725F666F726D65725F67726F7570732D7566675F67726F75702D70617463682D7566675F67726F75702D6C656E6774682D696E6372656173652D3235352E73716C'),	NULL),
(UNHEX('757365725F67726F7570732D75675F67726F75702D70617463682D75675F67726F75702D6C656E6774682D696E6372656173652D3235352E73716C'),	NULL),
(UNHEX('757365725F70726F706572746965732D75705F70726F70657274792D70617463682D75705F70726F70657274792E73716C'),	NULL),
(UNHEX('77625F6368616E6765732D6368616E67655F696E666F2D2F7661722F7777772F68746D6C2F657874656E73696F6E732F57696B69626173652F7265706F2F696E636C756465732F53746F72652F53716C2F2E2E2F2E2E2F2E2E2F73716C2F4D616B654368616E6765496E666F4C61726765722E73716C'),	NULL),
(UNHEX('77625F6974656D735F7065725F736974652D6970735F736974655F706167652D2F7661722F7777772F68746D6C2F657874656E73696F6E732F57696B69626173652F7265706F2F696E636C756465732F53746F72652F53716C2F2E2E2F2E2E2F2E2E2F73716C2F4D616B6549707353697465506167654C61726765722E73716C'),	NULL),
(UNHEX('77625F7465726D732D7465726D5F726F775F69642D2F7661722F7777772F68746D6C2F657874656E73696F6E732F57696B69626173652F7265706F2F696E636C756465732F53746F72652F53716C2F2E2E2F2E2E2F2E2E2F73716C2F4D616B65526F774944734269672E73716C'),	NULL),
(UNHEX('7762635F656E746974795F75736167652D65755F6173706563742D2F7661722F7777772F68746D6C2F657874656E73696F6E732F57696B69626173652F636C69656E742F696E636C756465732F55736167652F53716C2F2E2E2F2E2E2F2E2E2F73716C2F656E746974795F75736167652D616C7465722D6173706563742D76617262696E6172792D33372E73716C'),	NULL);

CREATE TABLE `<<prefix>>_uploadstash` (
  `us_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `us_user` int(10) unsigned NOT NULL,
  `us_key` varbinary(255) NOT NULL,
  `us_orig_path` varbinary(255) NOT NULL,
  `us_path` varbinary(255) NOT NULL,
  `us_source_type` varbinary(50) DEFAULT NULL,
  `us_timestamp` varbinary(14) NOT NULL,
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
  `user_touched` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
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
(1,	UNHEX('41646D696E4E616D65'),	UNHEX(''),	':pbkdf2:sha512:30000:64:Hpc3KKdgcb0SWVb+cNWLnA==:1oWnXlwt9Ca7VPqPLzFPtG12zsU27dziF4X4du18SwLX6lzvUVjmSljlVBEMVPiPcsbJLWtccnWF9eps+DMXLA==',	'',	NULL,	'',	UNHEX('3230313831313231313234333538'),	UNHEX('3836353137306664303135343139373961633766393764636234323630303164'),	NULL,	UNHEX('0000000000000000000000000000000000000000000000000000000000000000'),	NULL,	UNHEX('3230313831313231313234333531'),	0,	NULL);

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
(1,	UNHEX('7379736F70'),	NULL);

CREATE TABLE `<<prefix>>_user_newtalk` (
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `user_ip` varbinary(40) NOT NULL DEFAULT '',
  `user_last_timestamp` varbinary(14) DEFAULT NULL,
  KEY `un_user_id` (`user_id`),
  KEY `un_user_ip` (`user_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_user_properties` (
  `up_user` int(10) unsigned NOT NULL,
  `up_property` varbinary(255) NOT NULL,
  `up_value` blob DEFAULT NULL,
  PRIMARY KEY (`up_user`,`up_property`),
  KEY `user_properties_property` (`up_property`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_valid_tag` (
  `vt_tag` varbinary(255) NOT NULL,
  PRIMARY KEY (`vt_tag`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_watchlist` (
  `wl_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wl_user` int(10) unsigned NOT NULL,
  `wl_namespace` int(11) NOT NULL DEFAULT 0,
  `wl_title` varbinary(255) NOT NULL DEFAULT '',
  `wl_notificationtimestamp` varbinary(14) DEFAULT NULL,
  PRIMARY KEY (`wl_id`),
  UNIQUE KEY `wl_user` (`wl_user`,`wl_namespace`,`wl_title`),
  KEY `namespace_title` (`wl_namespace`,`wl_title`),
  KEY `wl_user_notificationtimestamp` (`wl_user`,`wl_notificationtimestamp`)
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

CREATE TABLE `<<prefix>>_wb_changes` (
  `change_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `change_type` varbinary(25) NOT NULL,
  `change_time` varbinary(14) NOT NULL,
  `change_object_id` varbinary(14) NOT NULL,
  `change_revision_id` int(10) unsigned NOT NULL,
  `change_user_id` int(10) unsigned NOT NULL,
  `change_info` mediumblob NOT NULL,
  PRIMARY KEY (`change_id`),
  KEY `wb_changes_change_type` (`change_type`),
  KEY `wb_changes_change_time` (`change_time`),
  KEY `wb_changes_change_object_id` (`change_object_id`),
  KEY `wb_changes_change_user_id` (`change_user_id`),
  KEY `wb_changes_change_revision_id` (`change_revision_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_wb_changes_dispatch` (
  `chd_site` varbinary(32) NOT NULL,
  `chd_db` varbinary(32) NOT NULL,
  `chd_seen` int(11) NOT NULL DEFAULT 0,
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

CREATE TABLE `<<prefix>>_wb_entity_per_page` (
  `epp_entity_id` int(10) unsigned NOT NULL,
  `epp_entity_type` varbinary(32) NOT NULL,
  `epp_page_id` int(10) unsigned NOT NULL,
  `epp_redirect_target` varbinary(255) DEFAULT NULL,
  UNIQUE KEY `wb_epp_entity` (`epp_entity_id`,`epp_entity_type`),
  UNIQUE KEY `wb_epp_page` (`epp_page_id`),
  KEY `epp_redirect_target` (`epp_redirect_target`)
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
  KEY `wb_ips_site_page` (`ips_site_page`),
  KEY `wb_ips_item_id` (`ips_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_wb_property_info` (
  `pi_property_id` int(10) unsigned NOT NULL,
  `pi_type` varbinary(32) NOT NULL,
  `pi_info` blob NOT NULL,
  PRIMARY KEY (`pi_property_id`),
  KEY `pi_type` (`pi_type`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `<<prefix>>_wb_terms` (
  `term_row_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `term_entity_id` int(10) unsigned NOT NULL,
  `term_full_entity_id` varbinary(32) DEFAULT NULL,
  `term_entity_type` varbinary(32) NOT NULL,
  `term_language` varbinary(32) NOT NULL,
  `term_type` varbinary(32) NOT NULL,
  `term_text` varbinary(255) NOT NULL,
  `term_search_key` varbinary(255) NOT NULL,
  `term_weight` float unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`term_row_id`),
  KEY `term_entity` (`term_entity_id`),
  KEY `term_full_entity` (`term_full_entity_id`),
  KEY `term_text` (`term_text`,`term_language`),
  KEY `term_search_key` (`term_search_key`,`term_language`),
  KEY `term_search` (`term_language`,`term_entity_id`,`term_type`,`term_search_key`(16)),
  KEY `term_search_full` (`term_language`,`term_full_entity_id`,`term_type`,`term_search_key`(16))
) ENGINE=InnoDB DEFAULT CHARSET=binary;
-- 2018-11-21 12:47:21
