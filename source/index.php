<?php

// if SMF is not defined, but SSI.php or ssi_examples.php exists in the upper level directory... die
if (!defined('SMF') && (file_exists(dirname(dirname(__FILE__)) . '/SSI.php') || file_exists(dirname(dirname(__FILE__)) . '/ssi_examples.php')))
{
	// send to upper left index.php
	if (file_exists(dirname(dirname(__FILE__)) . '/index.php'))
		include (dirname(dirname(__FILE__)) . '/index.php');
	// or just stop execution...
	else
		exit;
}
	
/*
	zC_START()
		- more stuff is loaded
		- User's blog permissions and preferences are loaded
*/
		
define('zc', 1);

$zc = array();
$zc['version'] = '0.8.1 Beta';
$zc['db_prefix'] = 'blog_';
$zc['smf_versions'] = array('SMF 2.0', 'SMF 1.1.x');
$zc['all_software_versions'] = $zc['smf_versions'];
$zc['with_software'] = array();

// detect integrated software version....
if (!empty($modSettings['smfVersion']) && substr($modSettings['smfVersion'], 0, 3) == '2.0')
	$zc['with_software']['version'] = 'SMF 2.0';
elseif (!empty($modSettings['smfVersion']) && substr($modSettings['smfVersion'], 0, 3) == '1.1')
	$zc['with_software']['version'] = 'SMF 1.1.x';
else
	$zc['with_software']['version'] = false;
	
zc_init();
	
function zc_init()
{
	global $modSettings;
	global $context, $zc, $zcFunc, $blog_info, $blog, $article, $comment, $poll, $draft;
			
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
	{
		if (!$zc['with_software']['version'])
			die('Could not find the necessary zCommunity sources files.');
		elseif (in_array($zc['with_software']['version'], $zc['smf_versions']))
			zc_redirect_exit();
	}
	
	$zcFunc = array();
	
	// load up the database functions
	require_once($zc['sources_dir'] . '/Subs-db-mysql.php');
	$connection = zc_db_init();
	$zc['db_connection'] = isset($zc['db_connection']) ? $zc['db_connection'] : $connection;
		
	if (!isset($context) || !is_array($context))
		$context = array();
	
	// this helps prevent compatibility problems with other scripts that use $context
	$context['zc'] = array();
	
	$context['zc']['zCommunity_is_home'] = false;
	$context['zc']['layerless'] = false;
	$context['zc']['site_name'] = $zc['site_name'];
	$context['zc']['default_images_url'] = $zc['default_images_url'];
	$context['zc']['meta'] = array();
	$context['zc']['container_alignment'] = '';
	$context['zc']['container_width'] = '';
	$context['zc']['right_side_bar_width'] = 22;
	$context['zc']['left_side_bar_width'] = 22;
	$context['zc']['main_guts_width'] = 100;
	
	// start errors array if not already started...
	if (!isset($context['zc']['errors']))
		$context['zc']['errors'] = array();
	
	// very important file...
	require_once($zc['sources_dir'] . '/Subs.php');
	
	zc_prepare_func_names();
	
	// cleans zC specific request variables... and makes a few globals
	require_once($zc['sources_dir'] . '/QueryString.php');
	zc_clean_request();
	
	// other important files...
	require_once($zc['sources_dir'] . '/Load.php');
	require_once($zc['sources_dir'] . '/Errors.php');
	
	// let's try to get the index lang file for zCommunity....
	zcLoadLanguage('index');
	
	// prepares $zc['settings'] array
	zcLoadGlobalSettings();
	
	// we don't reset $blog if viewing blog control panel or previewing a custom window
	if (!isset($_REQUEST['zc']) || !in_array($_REQUEST['zc'], array('bcp', 'pcw')))
		// modes 1 and 2 are NON-community modes... always use $zc['settings']['blogBoard'] as $blog
		if (in_array($zc['settings']['zc_mode'], array(1,2)))
			$blog = $zc['settings']['blogBoard'];
	
	// redirects?
	if (in_array($zc['settings']['zc_mode'], array(1,2)))
	{
		// ... can only view one blog if in these modes....
		if (!empty($blog) && !empty($zc['settings']['blogBoard']) && $blog != $zc['settings']['blogBoard'])
			zc_redirect_exit('blog=' . $zc['settings']['blogBoard'] . '.' . $_REQUEST['start']);
		elseif (!empty($blog) && $blog != $zc['settings']['blogBoard'])
			zc_redirect_exit('zc');
	}
			
	// more files...
	require_once($zc['sources_dir'] . '/Security.php');
	require_once($zc['sources_dir'] . '/Settings.php');
	require_once($zc['sources_dir'] . '/Permissions.php');
		
	// only load here if viewing an article or blog...
	if (!empty($blog) || !empty($article))
	{
		// prepare plug-ins ...
		require_once($zc['sources_dir'] . '/Plugins.php');
		load_zc_plugins();
		
		// prepare themes ...
		require_once($zc['sources_dir'] . '/Themes.php');
		load_zc_themes();
		
		$context['zc']['plugins_and_themes_loaded'] = true;
	}
	
	zcLoadBlog();
	
	if (in_array($zc['with_software']['version'], $zc['smf_versions']))
	{
		require_once($zc['sources_dir'] . '/Subs-smf.php');
		
		// this is helpful in that it populates the description and keywords meta tags with info when viewing a board
		zc_get_meta_for_smf();
	
		// zCommunity is your site's home page?
		$context['zc']['zCommunity_is_home'] = false;
	
		// should we show the nav menu link for zCommunity?  This is important if integrating zCommunity with another script
		$context['zc']['show_zc_nav'] = in_array($zc['settings']['zc_mode'], array(1,3)) || $context['user']['is_admin'];
	}
	
	if (!$zc['with_software'])
		zc_main();
}

function zc_main()
{
	call_user_func(zC_START());
	zc_ob_exit(null, null, true);
}

function zC_START()
{
	global $context, $zc, $blog, $article, $zcFunc, $blog_info, $txt, $scripturl;

	// no wireless support yet...
	if (WIRELESS && in_array($zc['with_software']['version'], $zc['smf_versions']))
		zc_redirect_exit();
	
	// well we are...
	$context['zc']['in_zcommunity'] = true;
	
	// prefetch makes things slower for the server...
	if (isset($_SERVER['HTTP_X_MOZ']) && $_SERVER['HTTP_X_MOZ'] == 'prefetch')
	{
		ob_end_clean();
		header('HTTP/1.1 403 Prefetch Forbidden');
		die;
	}
	
	// request variables that should carry a non-empty value...
	$notEmptyRequests = array('blogStart', 'listStart', 'listStart2', 'sort', 'sort2');
	
	// request variables that should carry no value...
	$issetRequests = array('all', 'all2');
	
	if (!empty($notEmptyRequests))
		foreach ($notEmptyRequests as $req)
			$context['zc'][$req . '_request'] = !empty($_REQUEST[$req]) ? ';' . $req . '=' . $_REQUEST[$req] : '';
	
	if (!empty($issetRequests))
		foreach ($issetRequests as $req)
			$context['zc'][$req . '_request'] = isset($_REQUEST[$req]) ? ';' . $req : '';
	
	$context['zc']['blog_request'] = '';
	$context['zc']['article_request'] = '';
	$context['zc']['zc_blog_article_request'] = 'zc';
	
	// Load the language file
	zcLoadLanguage('Blog');
	
	// zCommunity is disabled!
	if (empty($zc['settings']['zc_mode']) && (!isset($_REQUEST['zc']) || !in_array($_REQUEST['zc'], array('bcp', 'help')) || !$context['user']['is_admin']))
		zc_fatal_error($txt['b519'] . ' ' . ($context['user']['is_admin'] ? $txt['b520'] : ''));
	
	// hidden mode means only admins are allowed
	if (($zc['settings']['zc_mode'] == 2) && !$context['user']['is_admin'])
		zc_fatal_error('zc_error_66');
	
	$context['zc']['cookie_never_expire'] = $zc['settings']['cookie_time'] == 525600 || $zc['settings']['cookie_time'] == 3153600;
	
	// start with this as false so that we are sure the bots don't index only the ones we say not to...
	$context['robot_no_index'] = false;
	$context['zc']['link_tree'] = array();
	$context['zc']['extra_links'] = array();
	
	// Blog Control Panel link...
	if (!$context['user']['is_guest'])
		$context['zc']['extra_links'][] = '<a href="' . $scripturl . '?zc=bcp" rel="nofollow">' . $txt['b3002'] . '</a>';
	
	// start the link tree with the Home link
	$context['zc']['link_tree']['home'] = '<a href="' . $scripturl . '">' . $txt['b3000'] . '</a>';
	
	// Community link?
	if ($zc['settings']['zc_mode'] == 3 && empty($context['zc']['zCommunity_is_home']))
		$context['zc']['link_tree']['community'] = '<a href="' . $scripturl . '?zc">' . $txt['b213'] . '</a>';
			
	$context['zc']['extra_above_side_windows'] = array(
		'options' => array(
			'title' => $txt['b191a'],
			'links' => array(),
		),
		'buttons' => array(),
	);
		
	$context['zc']['extra_below_side_windows'] = array(
		'buttons' => array(),
		'other' => array('links' => array()),
	);
	
	$context['zc']['templates_other_copyrights'] = array();
	$context['zc']['template_layers'] = array();
	$context['zc']['link_templates'] = array();
	$context['zc']['raw_javascript'] = array();
	
	if (!isset($context['zc']['load_js_files']))
		$context['zc']['load_js_files'] = array();
	
	if (!isset($context['zc']['load_css_stylesheets']))
		$context['zc']['load_css_stylesheets'] = array();
		
	if (!isset($context['zc']['page_relative_links']))
		$context['zc']['page_relative_links'] = array();
		
	// integrated with some version of SMF ...
	if (in_array($zc['with_software']['version'], $zc['smf_versions']))
		zc_load_extra_smf();
		
	// if not already loaded
	if (empty($context['zc']['plugins_and_themes_loaded']))
	{
		// prepare plug-ins ...
		require_once($zc['sources_dir'] . '/Plugins.php');
		load_zc_plugins();
		
		// prepare themes ...
		require_once($zc['sources_dir'] . '/Themes.php');
		load_zc_themes();
		
		$context['zc']['plugins_and_themes_loaded'] = true;
	}
	
	// load this user's permissions in the blog community
	zcLoadPermissions();
	
	// just in case banned people got this far... removes their permissions
	zcBanPermissions();
	
	// there's a problem here if this isn't set or if it's not an array....
	if (!isset($context['user']['zc_permissions']) || !is_array($context['user']['zc_permissions']))
		zc_fatal_error();
	
	// they have to be able to view zcommunity....
	if (!in_array('view_zcommunity', $context['user']['zc_permissions']))
		zc_fatal_error('zc_error_25');
		
	// verify / process changes made to the database (plugins)
	if (function_exists('zcPluginDatabaseChanges'))
		zcPluginDatabaseChanges();
	
	if (!isset($context['zc']['permissions']))
		zcPreparePermissionsArray();
	
	// create $context['can_'] variables of permissions....
	if (!empty($context['zc']['permissions']))
		foreach ($context['zc']['permissions'] as $permission => $dummy)
			$context['can_' . $permission] = in_array($permission, $context['user']['zc_permissions']);
	
	// don't need this anymore...
	unset($context['zc']['permissions']);
	
	// load this user's current blog preferences
	zcLoadUserPreferences();
	
	// just start these here... easier this way... if we need more we'll make them later
	$context['zc']['list1'] = array();
	$context['zc']['list2'] = array();
	
	if (!empty($blog) || !empty($article))
		zc_load_blog_extra();
	
	// plug-in slot #8
	zc_plugin_slot(8);

	// handle zc actions
	if (!empty($_REQUEST['zc']))
	{
		// 'action' => array('File', 'Function', true_for_no_index, MUST_RETURN_TRUE, load_page_context)
		$zcActions = array(
			'approvearticle' => array('Subs-Articles.php', 'zcApproveArticle', true, true),
			'approvecomment' => array('Subs-Comments.php', 'zcApproveComment', true, true),
			'bcp' => array('ControlPanel.php', 'blogControlPanel', true, !$context['user']['is_guest']),
			'deletecomment' => array('Subs-Comments.php', 'zcDeleteComment', true, true),
			'deletearticle' => array('Subs-Articles.php', 'zcDeleteArticle', true, true),
			'deletepoll' => array('Subs-Polls.php', 'zcDeletePoll', true, true),
			'deletedraft' => array('Subs-Drafts.php', 'zcDeleteDraft', true, true),
			'lockarticle' => array('Subs-Articles.php', 'zcLockUnlockArticle', true, true),
			'lockpoll' => array('Subs-Polls.php', 'zcLockUnlockPoll', true, true),
			'notify' => array('Notify.php', 'zcNotifyOnOff', true, $context['can_mark_notify']),
			'pollvote' => array('Subs-Polls.php', 'zcCastVotePoll', true, $context['can_vote_in_polls']),
			'post' => array('Post.php', 'zcPost', true, true),
			'printpage' => array('PrintPage.php', 'zcPrintPage', true, true),
			'reporttm' => array('Notify.php', 'zcReportToModerators', true, $context['can_report_to_moderator']),
			'help' => array('Help.php', 'zc_help_popup', true, true),
			'.xml' => array('XML.php', 'BlogCommunityXmlFeed', true, !empty($zc['settings']['blog_xml_enable'])),
		);
		
		if (!empty($context['zc']['zcActions']) && is_array($context['zc']['zcActions']))
			$zcActions = array_merge($zcActions, $context['zc']['zcActions']);
			
		if (isset($context['zc']['zcActions']))
			unset($context['zc']['zcActions']);
		
		if (!empty($zcActions[$_REQUEST['zc']]) && (file_exists($zc['sources_dir'] . '/' . $zcActions[$_REQUEST['zc']][0]) || file_exists($zcActions[$_REQUEST['zc']][0])) && $zcActions[$_REQUEST['zc']][3] === true)
		{
			// we check the sources dir first....
			if (file_exists($zc['sources_dir'] . '/' . $zcActions[$_REQUEST['zc']][0]))
				require_once($zc['sources_dir'] . '/' . $zcActions[$_REQUEST['zc']][0]);
			// we gave the full path already....
			else
				require_once($zcActions[$_REQUEST['zc']][0]);
				
			if (function_exists($zcActions[$_REQUEST['zc']][1]))
			{
				// tell robots to not index?
				if ($zcActions[$_REQUEST['zc']][2] === true)
					$context['robot_no_index'] = true;
					
				return $zcActions[$_REQUEST['zc']][1];
			}
		}
		// they didn't return a zcAction function... so redirect them...
		zcReturnToOrigin();
	}
	
	// have gotten this far without returning anything and we want to view a single blog?
	if (!empty($article) || !empty($blog))
	{
		require_once($zc['sources_dir'] . '/Blog.php');
		return 'zcBlog';
	}
	
	// Community Page then?
	if ($zc['settings']['zc_mode'] == 3)
	{
		require_once($zc['sources_dir'] . '/Community.php');
		return 'zcCommunityPage';
	}
	// dunno how we got this far... but send them to blogBoard
	elseif (!empty($zc['settings']['blogBoard']))
		zc_redirect_exit('blog=' . $zc['settings']['blogBoard'] . '.' . $_REQUEST['start']);
	else
		zc_fatal_error(($context['user']['is_admin'] ? 'zc_error_51' : 'zc_error_47'));
}

?>