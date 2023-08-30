# api

## 8x.21.3 - 30 August 2023
- Add migration for creating batches table
- Make batched job batchable

## 8x.21.2 - 29 August 2023
- Also run PlatformStatsSummaryJob if preceding jobs failed

## 8x.21.1 - 29 August 2023
- Continue collecting platform stats when single job failed

## 8x.21.0 - 24 August 2023
- Allow for trusted proxies to be configured

## 8x.20.0 - 23 August 2023
- Add public /wiki endpoint

## 8x.19.0 - 23 August 2023
- Add daily job for updating MediaWiki stats on wikis

## 8x.18.0 - 22 August 2023
- Add WikiSiteStats model for persisting MediaWiki stats for a wiki

## 8x.17.3 - 15 August 2023
- Respond with proper HTTP status code on backend wiki lookup

## 8x.17.2 - 11 August 2023
- Update CHANGELOG.md file

## 8x.17.1 - 11 August 2023
- Reschedule stats collection job to 7AM

## 8x.17.0 - 01 August 2023
- Support arbitrary number of ElasticSearch clusters

## 8x.16.0 - 27 July 2023
- Added calling `report()` on for failed jobs to be able to gather them in Stackdriver (#622)
- Removed `fail()` calls for `MediawikiInit` Job, so it gets retried by the queue (#623)
- Added configurable backoff setting for failed jobs (#621) 

## 8x.15.1 - 24 July 2023
- Report Exception instead of logging when throttling account creation

## 8x.15.0 - 13 July 2023
- Throttle user signup

## 8x.14.0 - 04 July 2023
- Collect signup and wiki creation rate metrics with platform summary job

## 8x.13.0 - 22 June 2023
- Force lowercase domain names on wiki creation

## 8x.12.4 - 19 June 2023
- Reenable work done in polling job

## 8x.12.3 - 19 June 2023
- Temporary fix: make polling job a no-op so the queue can empty
- Run polling job less often

## 8x.12.2 - 13 June 2023
- Make sure job for polling wikis does queue properly

## 8x.12.1 - 12 June 2023
- Raise timeout values for polling wikis for pending jobs

## 8x.12.0 - 01 June 2023
- Poll wikis for pending MediaWiki jobs and create Kubernetes jobs to process them if needed

## 8x.11.1 - 18 April 2023
- Do not disable elastic search on wikis after a failure

## 8x.11.0 - 28 March 2023
- Use DB schemas from MediaWiki 1.39

## 8x.10.1 - 23 March 2023
- Set the timeout for MediaWiki updates to 1hr

## 8x.10.0 - 20 March 2023
- DB updates 1.38

## 8x.9.12 - 13 March 2023
- Respond 200 for any email on password reset requests

## 8x.9.11 - 16 December 2022
- Add /contact/sendMessage endpoint
- Add `WBSTACK_CONTACT_MAIL_RECIPIENT` env var
- Add `WBSTACK_CONTACT_MAIL_SENDER` env var

## 8x.9.10 - 21 November 2022
- Use Generally Available Kubernetes ingress object rather than beta

## 8x.9.8 - 01 September 2022
- Add command to create invitation codes in bulk `wbs-invitation:create-bulk`

## 8x.9.7 - 19 August 2022
- Refactor /mine endpoint to include limit, count and wikis

## 8x.9.6 - 18 August 2022
- Disable Elasticsearch on wiki creation by default. Make it configurable.

## 8x.9.5 - 15 August 2022
- Add active users to PlatformStatsSummaryJob

## 8x.9.4 - 12 August 2022

- Add SiteStatsUpdateJob

## 8x.9.3 - 08 August 2022

- Refactor stats summary job

## 8x.9.2 - 18 July 2022

- Fix reset password bug of task-specific emails

## 8x.9.1 - 22 June 2022

- Bump vimeo/psalm from 4.18 to 4.23.0

## 8x.9.0 - 22 June 2022

- Move elasticsearch host usage to config

## 8x.8.18 - 21 June 2022

- Slim down /main endpoint response

## 8x.8.17 - 20 June 2022

- Bump laravel/passport from 10.3.0 to 10.3.3

## 8x.8.16 - 20 June 2022

- Bump doctrine/dbal from 3.1.3 to 3.3.7

## 8x.8.15 - 17 June 2022

- Bump laravel/ui from 3.4.1 to 3.4.6

## 8x.8.14 - 17 June 2022

- Bump laravel/framework from 8.80.0 to 8.83.16

## 8x.8.13 - 17 June 2022

- Bump guzzlehttp/guzzle from 7.3.0 to 7.4.4

## 8x.8.12 - 17 June 2022

- Bump intervention/image from 2.7.1 to 2.7.2

## 8x.8.11 - 17 June 2022

- Bump `composer` from 2.2 to 2.3

## 8x.8.10 - 14 June 2022

- Bump `phpunit/phpunit` from 9.5.13 to 9.5.20

## 8x.8.9 - 13 June

- Bump `fakerphp/faker` from 1.18.0 to 1.19.0

## 8x.8.8 - 8 June 2022

- Stop seeding dev data in tests
- Add and schedule job for platform summary
- Disable maximum wiki functionality

## 8x.8.7 - 2 June 2022

- Add the functionality to limit the number of wikis a user can have

## 8x.8.6 - 3 May 2022

- [Allow forcesearchindex to fail](https://github.com/wbstack/api/pull/427)

## 8x.8.5 - 29 April 2022

- [Allow forcesearchindex to fail](https://github.com/wbstack/api/pull/418)

## 8x.8.4 - 8 April 2022

- Fix contact link in mail templates

## 8x.8.3 - 7 April 2022

- Update Email texts

## 8x.8.2 - 21 Febuary 2022

- Ensure certain wiki subdomains are forbidden for users.

## 8x.8.1 - 8 February 2022

- Ensure users created by the migration script are verified

## 8x.8.0 - 4 February 2022

 - Fix custom domain ingress creation (revert maclof/kubernetes-client from 0.21.0 to 0.23.0)
 - Add migrate custom domain script

## 8x.7.0 - 31 January 2022

 - Fix logo uploading job

## 8x.6.1 - 25 January 2022

 - Specifically require http/socket-client:2.0.2

## 8x.6.0 - 25 January 2022

 - REVERT Bump guzzlehttp/guzzle from 7.3.0 to 7.4.1
 - Fix MigrationWikiCreate Job when user missing

## 8x.5.0 - 24 January 2022

 - Add CreateEmptyWikiDb Job
 - Add MigrationWikiCreate Job
 - Add CirrusSearch Jobs
 - Bump guzzlehttp/guzzle from 7.3.0 to 7.4.1
 - Bump maclof/kubernetes-client from 0.21.0 to 0.23.0
 - Bump predis/predis from 1.1.9 to 1.1.10

 ## 8x.4.1 - 20 January 2022

 - Add DeleteWikiDispatcherJob

## 8x.4.0 - 20 January 2022

 - Introduce SetWikiLogo Job
 - Fix bug in provisioning wikidbs and queryservice namespaces
 - Replace custom artisan job execution with mxl/laravel-job
 - Enable setting a read only setting for a wiki

## 8x.3.0 - 3 December 2021

 - DB updates 1.37 (with fed props)
 - Add laravel/tinker
 - Add artisan command to soft delete a wiki
 - laravel/framework from 8.68.1 to 8.70.1

## 8x.2.0 - 29 October 2021

- Add 1.36 dump and make new wikis/wikidb use the new schema
- Add DeleteWikiFinalizeJob - currently not set to be run
- Give BINLOG MONITOR to new provisioned wiki-dbs - MariaDB was updated
- Bump some dependencies and github actions

## 8x.1.9 - 25 October 2021

- Package updates, including laravel/framework from 8.64.0 to 8.67.0
- Use new wbstack uiurl for email notification links

## 8x.1.8 - 18 October 2021

- [Add a Job (not called yet) to delete elasticsearch indexes](https://github.com/wbstack/api/pull/152)
- [Add a Job (not called yet) to delete blazegraph namespaces](https://github.com/wbstack/api/pull/155)
- [Configurable subdomain for use on wiki creation (was hardcoded to *.wiki.opencura.com)](https://github.com/wbstack/api/pull/217)
- [Adjust to work with MariaDB 10.5.9+ (and 10.5.2-). There is a gap where wiki creation will not correctly work!](https://github.com/wbstack/api/pull/216)

## 8x.1.7.1  - 17 January 2022

- BACKPORT [Enable setting a read only setting for a wiki](https://github.com/wbstack/api/pull/301)

## 8x.1.7 - 2 September 2021

- Revert ElasticSearchIndexInit error checking tabs

**8x.1.6** - 2 September 2021, broken in some way

- [Fix ApiWbStackElasticSearchInit does not contain success](https://github.com/wbstack/api/pull/185)

**8x.1.5** - 2 September 2021, broken in some way

- [Fix ElasticSearchIndexInit error checking tabs](https://github.com/wbstack/api/pull/184)

**8x.1.4** - 2 September 2021, broken in some way

- [Add CURLOPT_TIMEOUT_ELASTICSEARCH_INIT](https://github.com/wbstack/api/pull/183)

**8x.1.3** - 2 September 2021, broken in some way

- [MediawikiInit job timeout increase (10-60 seconds)](https://github.com/wbstack/api/pull/180)
- FIX [ElasticSearchIndexInit job curl issues](https://github.com/wbstack/api/pull/182)

**8x.1.2** - 2 September 2021, Broken https://github.com/wbstack/api/issues/181

- [Laravel 8.5.1 to 8.5.2](https://github.com/wbstack/api/pull/140)
- [Enable elastic search on new wikis (calling `wbstackElasticSearchInit` on creation)](https://github.com/wbstack/api/pull/147)
- [Add K8s ingress deletion job (not called yet)](https://github.com/wbstack/api/pull/161)

## 8x.1.1 - 15 July 2021

- Minor laravel bump
- Add job to add elastic indexes

## 8x.1.0 - 30 June 2021

- First 8.0 release

## 7x-1.1 - 23 June 2021

- [Fix Undefined index: wbstackInit in Jobs/MediawikiInit.php](https://github.com/wbstack/api/commit/c091b3c2a66766b1762024c9f74c6278df888c7c)

## 7x-1.0 - 28 March 2021

Has migrations to run!

- Update PHP from 7.3 to 7.4
- First version using Laravel 7:
  - Env var `MAIL_DRIVER` changes to `MAIL_MAILER`
- Updated code handling auth (`laravel/passport`)
- Added `wwExtEnableWikibaseLexeme` as a public setting
- Removed legacy wiki setting names

## 6x-1.9 - 27 March 2021

- Fix k8s ingress creation for custom domains

## 6x-1.8 - 26 March 2021

- Add wgLogo to public settings

## 6x-1.7 - 25 March 2021

Has migrations to run!

- [Update `maclof/kubernetes-client` and thus `app/Jobs/KubernetesIngressCreate.php` (without testing)](https://github.com/wbstack/api/pull/28)
  - If something is wrong here this could have an impact on new wikis created with custom domains.
- Alter throttling for api and sandbox routes
- [Get public wiki settings from wiki/details endpoint](https://github.com/wbstack/api/pull/15)
- [Migrate wiki_settings value column from string to text](https://github.com/wbstack/api/pull/16)
- [Wiki settings API can now input either internal or external setting names](https://github.com/wbstack/api/pull/20)
- [Add settings for Wikibase Manifest and FederatedProperties](https://github.com/wbstack/api/pull/23)

## 6x-1.6 - 11 December 2020

- [Add](https://github.com/wbstack/api/commit/8c3d6b29bd4711972da7f7c0207fc263421e1a54) and [Schedule](https://github.com/wbstack/api/commit/63c14492eea671549a72ef925fdcbfd039ba27a6) job to soft delete sandboxes
- [Fix](https://github.com/wbstack/api/commit/bb667ca88faf1aee61d1a7dc237f6c585de3df3b) mwinit job syntax error with ;s (broken in 6x-1.5)
- [Sandboxes can have 1 preset dataset loaded now](https://github.com/wbstack/api/commit/6e95a5075527835b2695f2bf23ba352316ef720c)

## 6x-1.5 - 10 December 2020

- [Add more words to sandbox wordlist](https://github.com/wbstack/api/commit/587a892dea94ffa9cc0df7b6ad5c7153fa8a7446)
- [Opportunistic storage pool population](https://github.com/wbstack/api/commit/a0e65dd63dd63bd80a244b88d8fa23cb25565269)
- [Ensure sandbox domains are not taken](https://github.com/wbstack/api/commit/8e9db079ff398f602966a7431552c7652c33ddd9), this would have just caused errors seen by the users and not actual bad things...
- [Recaptcha for sandbox API](https://github.com/wbstack/api/commit/39e650a313759f071042a7ce10002d1ac0a56e0d)
- [All Jobs now fail rather than throwing exceptions](https://github.com/wbstack/api/commit/ea6f924d7f82a77082fa4f39ac82955ed5e43401)
- [Number of page updates cleaned per job increased](https://github.com/wbstack/api/commit/c26de7b871e18dd2edb0640a8ecc87573cdb11bc)
- [getBatches API now works when there are 0 batches](https://github.com/wbstack/api/commit/73c3cb75a9cc1e964687da4590ce19e6382278e9)

## 6x-1.4 - 10 December 2020

- Fix word list for sandbox endpoint.

## 6x-1.3 - 10 December 2020

- Fix loading heathcheck when using sandbox only (broken in 6x-1.2)
- Fix accidentally bumped composer.lock (broken in 6x-1.2)

## 6x-1.2 - 10 December 2020

- Added sandbox related endpoints (hidden behind an ENV var)
- Fix migration script call name (broken in 6x-1.1)

## 6x-1.1 - 8 December 2020

- Removed unused stuff:
  - [admin endpoints](https://github.com/wbstack/api/commit/389ce0cfee4aa5f71a405b838c51b29c80ebc922)
  - [interest concept](https://github.com/wbstack/api/commit/2bbde16cf2d7764ff88d4fdcdbe0aa23f63d9b12)
  - [wiki count](https://github.com/wbstack/api/commit/41e7564aad05630064a386560ba7a4bc3ec16a19)
- [Changed names of the 2 MediaWiki update jobs for clarity](https://github.com/wbstack/api/commit/81d6482fbf72780bea89a88535051bde0340cb52)
- Reworked "storage pool provisioning" jobs [pt1](https://github.com/wbstack/api/commit/c259db095641660c27e5c8ef1a5c7a528e9b4a3e) [pt2](https://github.com/wbstack/api/commit/1a4c08b759b76372f997cdda6dcf350af387d05e)

## 6x-1.0 - Early December 2020

- Build moved to Github, but other than that everything remains the same.

## Laravel 6.x, Version 0 (Built on GCE)

### November 2020

- 6x-0.37 - mw135, FIX db creation... (heh i never tested it...)
- 6x-0.36 - mw135, Stop making new DBs....
- 6x-0.34 - mw135, make 1.35 dbs now (with version selection throughout)
- 6x-0.33 - mw135, make 1.35 dbs now
- 6x-0.32 - More aggressive api db pruneing
- 6x-0.31 - mw134, fix for updating wiki_dbs version when updating
- 6x-0.30 - mw134, skip deleted wikis in update.php calling script
- 6x-0.28 - mw134, make 1.34 dbs now
- 6x-0.27 - mw134, now has an mw update api calling job

### October 2020

- 6x-0.23 - PHP 7.3 & Fix for registration issue https://github.com/addshore/wbstack/issues/120

### June 2020

- 6x-0.22 - Multilang (term) length options
- 6x-0.21 - PHP Security fixes June 3 2020

### May 2020

- 6x-0.20 - API, settings can be configured for wikibase string lengths and also ConfirmAccount extension
- 6x-0.19 - CLI add set setting command
- 6x-0.18 - Require users to have verified accounts for lots of routes.
- 6x-0.17 - Better user verification flow
- 6x-0.16 - CLI, user verify command
- 6x-0.15 - CLI, commands for invitation interation (and move existing commands around)
- 6x-0.14 - backend: event/pageUpdateBatch endpoint
- 6x-0.13 - Simple API health check
- 6x-0.12 - Custom default mediawiki skin
- 6x-0.11 - Add write connection to the read pool (+resilience)
- 6x-0.10 - Separate read and write connections (blergh this is just config, why am I rebuilding and image for it....)
- 6x-0.9 - No longer create "Quickstatements" user on wiki creation

### April 2020

- 6x-0.8 - simple kubernetes ingress -> platform-nginx
- 6x-0.7 - Allow turning Stackdriver batching off with env var & Stackdriver cast enabled vars to bool
- 6x-0.5 - Stop using Input facade (removed in 6.x of laravel) (LogoUpdater)
- 6x-0.3 - Stackdriver integration
- 6x-0.2 - Markdownify account creation email, and fix password reset one? (no Translator::getFromJson)
- 6x-0.1 - Update to Laravel 6.x

## Laravel 5.x, Version 0

### April 2020

- 0.70 - Expire tokens after 30 days
- 0.67 - Wiki logo upload functionality, Also add a favicon (and use imagick not gd)
- 0.63 - Wiki logo upload functionality, Add ts to URL to purge the bucket cache
- 0.62 - Wiki logo upload functionality Step 3 - Delete before re upload
- 0.61 - Wiki logo upload functionality Step 2 - write to wiki settings
- 0.60 - Wiki logo upload functionality Step 1
- 0.57 - Tiny tweaks
- 0.56 - Re enable db prune jobs with a fix
- 0.55 - Custom domain handeling in the API (fix issuer for k8s ingresses created)
- 0.54 - Custom domain handeling in the API
- 0.53 - stop broken Prune jobs...
- 0.52 - Email verification nice if done & Job for k8s ingress creation & autoclean some db tables
- 0.51 - 1.33-wbs5 SQL (but correctly spaced??) - God this is brittle....
- 0.50 - Create dbs using 1.33-wbs5 SQL, (Echo extension)
- 0.49 - Create dbs using 1.33-wbs4 SQL & enable upgrade
- 0.48 - Delete wiki functionality
- 0.47 - Reset password functionality 1

### January 2020

- 0.46 - QS, batch up lexeme changes too

### December 2019

- 0.45 - Allow wiki creation again
- 0.44 - PAUSE wiki creation HARD - https://github.com/addshore/wbstack/issues/39

### November 2019

- 0.43 - Send UA to query service for NS creation - https://github.com/addshore/wbstack/issues/34
- 0.42 - Don't output if a user is registered when logging in
- 0.41 - SQL to truncate l10n & start using db version wbs3
- 0.40 - Includes ScheduleMediawikiManualDbUpdates job
- 0.39 - MediawikiManualDbUpdate Job, use correct DB
- 0.38 - Default creation DB is now wbs2 db... (with EntitySchema)
- 0.37 - Disallow _s in domains
- 0.36 - line instead of info for getWiki cli

### October 2019

- 0.35 - mw init api calls, log raw responses
- 0.34 - WORKSHOP, updater poking FIX 4
- 0.33 - WORKSHOP, updater poking FIX 3
- 0.32 - WORKSHOP, updater poking FIX 2
- 0.31 - WORKSHOP, updater poking FIX 1
- 0.30 - WORKSHOP, updater poking
- 0.29 - create wiki domain name length requirements
- 0.28 - registration fixes
- 0.27 - password requirements
- 0.26 - SLAVE CLIENT fix job grant (missed ON)
- 0.25 - SLAVE CLIENT access for new mediawiki users
- 0.24 - Friday Fix verification email from..
- 0.23 - Thursday morning, ingress for real certificate...
- 0.20 - Thursday morning, More logging for mw init job errors..
- 0.19 - Thursday morning, Config snippet on one line per https://github.com/Akirix/gluu/blob/0a4ae5b1044332e0ba9e3c5803e08d4200a01ab0/templates/ingress.yaml#L71
- 0.18 - Thursday morning, poke as code snippet didn't work with leading |
- 0.17 - Thursday morning, maybe fix ingresses that are created (after some testing)
- 0.16 - Fix mw and quickstatements service names in ingress
- 0.15 - k8s job, use string for id label :D
- 0.14 - post wiki create jobs use PLATFORM_MW_BACKEND_HOST
- 0.13 - Includes KubernetesIngressCreate Job on wiki creation
- 0.12 - Includes KubernetesIngressCreate Job fix, https and port ints and : keys
- 0.11 - Includes KubernetesIngressCreate Job
- 0.10 - Wednesday before Wikidatacon
- 0.6 - Stuff? And receiving backend mediawiki events (/backend/event/pageUpdate)
- 0.5 - mw db provisioning works :) (locally)
- 0.4 - mw db provisioning, flush privs after user creation
- 0.3 - mw db user creation with mwu_ prefix
- 0.2 - mw db creation job creates correct sql...

### August 2019

- 0.1 - Initial version
