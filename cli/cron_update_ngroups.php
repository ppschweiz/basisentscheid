#!/usr/bin/php
<?
/**
 * to be called by a cron job about once per day
 *
 * crontab example:
 * 0 0  * * *  <path>/cli/cron_update_ngroups.php
 *
 * @author Magnus Rosenbaum <dev@cmr.cx>
 * @package Basisentscheid
 */


if ( $dir = dirname($_SERVER['argv'][0]) ) chdir($dir);
define('DOCROOT', "../");
require DOCROOT."inc/common_cli.php";

update_ngroups();
