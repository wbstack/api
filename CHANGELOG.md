# api

## 7x-1.0

- First version using Laravel 7

## 6x-1.8 - 26 March 2021

- Add wgLogo to public settings

## 6x-1.7 - 25 March 2021

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
