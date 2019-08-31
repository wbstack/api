-- Just update the expiry timestamp of the object cache entry we create the DB with.

REPLACE INTO `<<prefix>>_objectcache`
(`keyname`, `value`, `exptime`)
VALUES (UNHEX('77696B693A6D657373616765733A656E'),	'��\n�@�e�`w=o�]e!h�������-�����0�zO�<�vh:@��԰S��ܴϑ��u��Źp_k�`|w4}^���E�@YU�V��',	'2040-01-19 03:14:07');
