<?php

// If SSI.php is in the same place as this file, and SMF isn't defined, this is being run standalone.
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
// Hmm... no SSI.php and no SMF?
elseif (!defined('SMF'))
	die('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');
	
global $context, $modSettings, $zc, $zcFunc;
	
define('zc', 1);

$zc = array();
$zc['version'] = '0.8.1 Beta';
$zc['db_prefix'] = 'blog_';
$zc['smf_versions'] = array('SMF 2.0', 'SMF 1.1.x');
$zc['all_software_versions'] = $zc['smf_versions'];
$zc['with_software'] = array();

// detect forum version....
if (!empty($modSettings['smfVersion']) && substr($modSettings['smfVersion'], 0, 3) == '2.0')
	$zc['with_software']['version'] = 'SMF 2.0';
elseif (!empty($modSettings['smfVersion']) && substr($modSettings['smfVersion'], 0, 3) == '1.1')
	$zc['with_software']['version'] = 'SMF 1.1.x';
else
	$zc['with_software']['version'] = false;
			
// zCommunity is standing on its own...
if (!$zc['with_software']['version'])
{
	if (!file_exists(dirname(__FILE__) . '/settings.php'))
		die('Failed to load critical zCommunity settings file.');

	// load up basic settings
	require_once(dirname(__FILE__) . '/settings.php');
	
	$zc['time_start'] = array_sum(explode(' ', microtime()));
	$zc['db_query_count'] = 0;
	
	if (WIRELESS)
		die('zCommunity does not support wireless protocals yet.  Sorry.');
}
// populate $zc['with_software'] array with more info about the forum zCommunity is integrated with...
elseif (in_array($zc['with_software']['version'], $zc['smf_versions']))
{
	global $sc, $sourcedir, $boarddir, $boardurl, $db_count, $time_start, $language, $db_prefix, $db_connection;
	
	$zc['with_software']['sources_dir'] = $sourcedir;
	$zc['with_software']['main_dir'] = $boarddir;
	$zc['with_software']['main_url'] = $boardurl;
	$zc['with_software']['db_prefix'] = $db_prefix;
	
	// store some stuff in $zc
	$zc['time_start'] = $time_start;
	$zc['db_query_count'] = $db_count;
	$zc['language'] = $language;
	$zc['db_connection'] = $db_connection;
	$zc['site_name'] = $context['forum_name'];
	$zc['session_id'] = $sc;
	
	// file/url paths to zCommunity's main directory
	$zc['main_dir'] = $zc['with_software']['main_dir'] . '/zCommunity';
	$zc['main_url'] = $zc['with_software']['main_url'] . '/zCommunity';
	$zc['sources_dir'] = $zc['main_dir'] . '/Sources';
	$zc['cache_dir'] = $zc['main_dir'] . '/cache';
}

// let's set some more file/url paths for zCommunity
$zc['themes_url'] = $zc['main_url'] . '/Themes';
$zc['themes_dir'] = $zc['main_dir'] . '/Themes';
$zc['default_theme_url'] = $zc['themes_url'] . '/default';
$zc['default_images_url'] = $zc['themes_url'] . '/default/images';
$zc['plugins_dir'] = $zc['main_dir'] . '/Plugins';
$zc['plugins_lang_dir'] = $zc['plugins_dir'] . '/Languages';

// has to exist...
if (!file_exists($zc['sources_dir'] . '/Subs.php'))
	die('Could not find the necessary zCommunity sources files.  Database edits failed!');
	
$zcFunc = array();

// load up the database functions
require_once($zc['sources_dir'] . '/Subs-db-mysql.php');
$connection = zc_db_init();
$zc['db_connection'] = isset($zc['db_connection']) ? $zc['db_connection'] : $connection;
	
if (!isset($context) || !is_array($context))
	$context = array();

// this helps prevent compatibility problems with other scripts that use $context
$context['zc'] = array();

if (isset($modSettings['zc_mode']))
	$modSettings['zc_mode'] = (int) $modSettings['zc_mode'];
// for backwards compatibility...
elseif (isset($modSettings['blogMode']))
	$modSettings['zc_mode'] = (int) $modSettings['blogMode'];

if (isset($modSettings['zc_version']))
	$modSettings['zc_version'] = (string) $modSettings['zc_version'];
// for backwards compatibility...
elseif (isset($modSettings['zcommunity_version']))
	$modSettings['zc_version'] = (string) $modSettings['zcommunity_version'];

require_once($zc['sources_dir'] . '/Subs.php');

zc_prepare_func_names();

require_once($zc['sources_dir'] . '/db-info-zc.php');
require_once($zc['sources_dir'] . '/Subs-Database.php');
require_once($zc['sources_dir'] . '/Load.php');
require_once($zc['sources_dir'] . '/Settings.php');
require_once($zc['sources_dir'] . '/Subs-Maintenance.php');

$tables = zc_prepare_db_table_info();

// assume BC 2.0.2 if version not set
if (!isset($modSettings['zc_version']))
	zc_do_db_updates('BC 2.0.2');
else
	zc_do_db_updates($modSettings['zc_version']);

foreach ($tables as $table_info)
{
	zcCreateDatabaseTable($table_info);
	
	// check for and *fix* missing columns
	zc_repair_db_table($table_info['table_name'], $table_info, null, false);
}

zcLoadGlobalSettings();

zc_repair_db_table('settings', zc_prepare_blog_settings_array(), array('allowedGroups', 'blogName', 'blogDescription'), false);
zc_repair_db_table('preferences', zc_prepare_preferences_array(), null, false);

require_once($zc['sources_dir'] . '/Subs-smf.php');
zc_update_forum_settings(array('zc_version' => (string) $zc['version'], 'zc_mode' => isset($modSettings['zc_mode']) ? (int) $modSettings['zc_mode'] : 0));

echo '<div style="width:50%; margin-top:30px; padding:10px; border: 1px solid #F3F3F3; background-color:#FBFBFF; line-height:135%;">
		Database updated successfully!<br />
		<a href="' . $scripturl . '?zc=bcp" style="color:#7590D6; text-decoration: none;">Click here to view your control panel</a>
	</div>';

?>