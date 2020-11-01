DROP INDEX ar_usertext_timestamp ON `prefix_archive`;
DROP INDEX usertext_timestamp ON `prefix_archive`;
ALTER TABLE `prefix_archive`
 DROP COLUMN ar_user,
 DROP COLUMN ar_user_text,
 ALTER COLUMN ar_actor DROP DEFAULT;
ALTER TABLE `prefix_image`
 DROP INDEX img_user_timestamp,
 DROP INDEX img_usertext_timestamp,
 DROP COLUMN img_user,
 DROP COLUMN img_user_text,
 ALTER COLUMN img_actor DROP DEFAULT;
ALTER TABLE `prefix_oldimage`
 DROP INDEX oi_usertext_timestamp,
 DROP COLUMN oi_user,
 DROP COLUMN oi_user_text,
 ALTER COLUMN oi_actor DROP DEFAULT;
ALTER TABLE `prefix_filearchive`
 DROP INDEX fa_user_timestamp,
 DROP COLUMN fa_user,
 DROP COLUMN fa_user_text,
 ALTER COLUMN fa_actor DROP DEFAULT;
ALTER TABLE `prefix_recentchanges`
 DROP INDEX rc_ns_usertext,
 DROP INDEX rc_user_text,
 DROP COLUMN rc_user,
 DROP COLUMN rc_user_text,
 ALTER COLUMN rc_actor DROP DEFAULT;
ALTER TABLE `prefix_logging`
 DROP INDEX user_time,
 DROP INDEX log_user_type_time,
 DROP INDEX log_user_text_type_time,
 DROP INDEX log_user_text_time,
 DROP COLUMN log_user,
 DROP COLUMN log_user_text,
 ALTER COLUMN log_actor DROP DEFAULT;

ALTER TABLE `prefix_account_requests` MODIFY acr_email VARCHAR(255) binary NOT NULL;

ALTER TABLE `prefix_wb_terms`
 MODIFY term_row_id BIGINT unsigned NOT NULL auto_increment;
ALTER TABLE `prefix_wb_items_per_site`
 MODIFY ips_row_id BIGINT unsigned NOT NULL auto_increment;
ALTER TABLE `prefix_wb_items_per_site`
 MODIFY ips_site_page VARCHAR(310) NOT NULL;
ALTER TABLE `prefix_wb_changes`
 MODIFY change_info MEDIUMBLOB NOT NULL;
ALTER TABLE `prefix_wbc_entity_usage`
 MODIFY eu_aspect VARBINARY(37) NOT NULL;

UPDATE `prefix_page`
 SET page_namespace = 640 + (page_namespace - 12300)
 WHERE page_namespace IN (12300, 12301);
UPDATE `prefix_archive`
 SET ar_namespace = 640 + (ar_namespace - 12300)
 WHERE ar_namespace IN (12300, 12301);
UPDATE `prefix_pagelinks`
 SET pl_namespace = 640 + (pl_namespace - 12300)
 WHERE pl_namespace IN (12300, 12301);
UPDATE `prefix_pagelinks`
 SET pl_from_namespace = 640 + (pl_from_namespace - 12300)
 WHERE pl_from_namespace IN (12300, 12301);
UPDATE `prefix_templatelinks`
 SET tl_namespace = 640 + (tl_namespace - 12300)
 WHERE tl_namespace IN (12300, 12301);
UPDATE `prefix_templatelinks`
 SET tl_from_namespace = 640 + (tl_from_namespace - 12300)
 WHERE tl_from_namespace IN (12300, 12301);
UPDATE `prefix_imagelinks`
 SET il_from_namespace = 640 + (il_from_namespace - 12300)
 WHERE il_from_namespace IN (12300, 12301);
UPDATE `prefix_recentchanges`
 SET rc_namespace = 640 + (rc_namespace - 12300)
 WHERE rc_namespace IN (12300, 12301);
UPDATE `prefix_watchlist`
 SET wl_namespace = 640 + (wl_namespace - 12300)
 WHERE wl_namespace IN (12300, 12301);
UPDATE `prefix_querycache`
 SET qc_namespace = 640 + (qc_namespace - 12300)
 WHERE qc_namespace IN (12300, 12301);
UPDATE `prefix_logging`
 SET log_namespace = 640 + (log_namespace - 12300)
 WHERE log_namespace IN (12300, 12301);
UPDATE `prefix_job`
 SET job_namespace = 640 + (job_namespace - 12300)
 WHERE job_namespace IN (12300, 12301);
UPDATE `prefix_redirect`
 SET rd_namespace = 640 + (rd_namespace - 12300)
 WHERE rd_namespace IN (12300, 12301);
UPDATE `prefix_querycachetwo`
 SET qcc_namespace = 640 + (qcc_namespace - 12300)
 WHERE qcc_namespace IN (12300, 12301);
UPDATE `prefix_querycachetwo`
 SET qcc_namespacetwo = 640 + (qcc_namespacetwo - 12300)
 WHERE qcc_namespacetwo IN (12300, 12301);
UPDATE `prefix_protected_titles`
 SET pt_namespace = 640 + (pt_namespace - 12300)
 WHERE pt_namespace IN (12300, 12301);

ALTER TABLE `prefix_echo_event` CHANGE COLUMN event_variant event_variant varchar(64) binary null;
alter table `prefix_echo_event` change column event_extra event_extra BLOB NULL;
ALTER TABLE `prefix_echo_event` CHANGE COLUMN event_agent_ip event_agent_ip varchar(39) binary NULL;