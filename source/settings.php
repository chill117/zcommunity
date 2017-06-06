<?php

// This file is used only when zCommunity is stand-alone

// maintenance ...
// if set to 1, the site will be inaccessible to non-administrators. A page with an informational message will be displayed
$zc['maintenance_mode'] = 0;
$zc['maintenance_page_title'] = 'Maintenance Mode';
$zc['maintenance_message'] = 'We are currently doing maintenance.  The site will be back up soon.  We apologize for any inconvenience.';

// site info
$zc['site_name'] = '';
$zc['language'] = 'english';
$zc['site_url'] = '';
$zc['webmaster_email'] = '';
$zc['cookie_name'] = '';

// directory info
$zc['main_dir'] = dirname(__FILE__); // absolute path to zCommunity's main directory
$zc['sources_dir'] = $zc['main_dir'] . '/Sources'; // absolute path to zCommunity's Sources directory
$zc['cache_dir'] = $zc['main_dir'] . '/cache'; // absolute path to zCommunity's cache directory

// database info
$zc['db_server'] = '';
$zc['db_name'] = '';
$zc['db_user'] = '';
$zc['db_passwd'] = '';
$zc['db_prefix'] = '';
$zc['db_persist'] = 0;
$zc['db_error_send'] = 1;

// for db error caching... shouldn't touch this
$zc['db_last_error'] = 0;

?>