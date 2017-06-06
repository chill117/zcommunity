<?php

if (!defined('zc'))
	die('Hacking attempt...');

function zc_who_viewing_zc_action($actions)
{
	global $txt, $context;
	global $zcFunc, $zc;
	
	// 0 => $article_ids[(int) $actions['article']][$k]
	// 1 => $blog_ids[(int) $actions['blog']][$k]
	// 2 => $data[$k]
	$context['zc']['temp_return'] = array('', '', '');
	$context['zc']['temp_actions'] = $actions;
	
	if (!function_exists('zcFormatTxtString'))
	{
		if (file_exists($zc['sources_dir'] . '/Subs.php'))
			require_once($zc['sources_dir'] . '/Subs.php');
	}
		
	// prepares $zcFunc function array
	if (empty($zcFunc['db_query']) && function_exists('zc_prepare_func_names'))
		zc_prepare_func_names();
	
	// load zcomm permissions if necessary
	if (!isset($context['can_view_zcommunity']))
	{
		if (!function_exists('zcLoadPermissions') && file_exists($zc['sources_dir'] . '/Load.php'))
			require_once($zc['sources_dir'] . '/Load.php');
			
		if (function_exists('zcLoadPermissions'))
			zcLoadPermissions();
			
		// create $context['can_'] variables of permissions....
		if (!empty($context['zc']['permissions']))
			foreach ($context['zc']['permissions'] as $permission => $dummy)
				$context['can_' . $permission] = in_array($permission, $context['user']['zc_permissions']);
	}
	
	// load Who language file if necessary...
	if (!isset($txt['b570']))
	{
		if (!function_exists('zcLoadLanguage') && file_exists($zc['sources_dir'] . '/Load.php'))
			require_once($zc['sources_dir'] . '/Load.php');
		
		if (function_exists('zcLoadLanguage'))
			zcLoadLanguage('Who');
	}
	
	// load zc_settings if necessary...
	if (!isset($zc['settings']['community_total_blogs']))
	{
		if (!function_exists('zcLoadGlobalSettings') && file_exists($zc['sources_dir'] . '/Load.php'))
			require_once($zc['sources_dir'] . '/Load.php');
		
		if (function_exists('zcLoadGlobalSettings'))
			zcLoadGlobalSettings();
	}
	
	$zc_actions = array(
		// 'action' => array('txt_var_for_article', 'txt_var_for_blog', 'text_for_any', must_return_true)
		'approvearticle' => array('b580', '', '', true),
		'approvecomment' => array('b581', '', '', true),
		'deletecomment' => array('b594', '', '', true),
		'deletearticle' => array('', 'b592', '', true),
		'deletepoll' => array('', 'b593', '', true),
		'lockarticle' => array('b596', '', '', true),
		'lockpoll' => array('', 'b597', '', true),
		'pollvote' => array('', 'b572', '', $context['can_vote_in_polls']),
		'printpage' => array('b577', '', '', true),
		'reporttm' => array('b589', 'b588', '', $context['can_report_to_moderator']),
		'deletedraft' => array('', '', 'b595', $context['can_save_drafts']),
		'help' => array('', '', 'b602', true),
	);
	
	if (!empty($actions['article']))
	{
		// subscribing to an article...
		if ($context['can_mark_notify'] && $actions['zc'] == 'notify' && isset($actions['sa']) && $actions['sa'] == 'on')
			$context['zc']['temp_return'][0] = 'b598';
		// unsubscribing from an article...
		elseif ($context['can_mark_notify'] && $actions['zc'] == 'notify')
			$context['zc']['temp_return'][0] = 'b599';
	}
	elseif (!empty($actions['blog']))
	{
		// subscribing to a blog...
		if ($context['can_mark_notify'] && $actions['zc'] == 'notify' && isset($actions['sa']) && $actions['sa'] == 'on')
			$context['zc']['temp_return'][1] = 'b600';
		// unsubscribing from a blog...
		elseif ($context['can_mark_notify'] && $actions['zc'] == 'notify')
			$context['zc']['temp_return'][1] = 'b601';
		// viewing a blog's xml feed?
		elseif ($actions['zc'] == '.xml' && !empty($zc['settings']['blog_xml_enable']))
			$context['zc']['temp_return'][1] = 'b603';
	}
	// community news xml feed
	elseif ($actions['zc'] == '.xml' && !empty($zc['settings']['blog_xml_enable']) && isset($actions['news']))
		$context['zc']['temp_return'][2] = 'b604';
	// blogging community xml site map
	elseif ($actions['zc'] == '.xml' && !empty($zc['settings']['blog_xml_enable']) && isset($actions['type']) && $actions['type'] == 'sitemap')
		$context['zc']['temp_return'][2] = 'b605';
	// xml feed of entire blogging community
	elseif ($actions['zc'] == '.xml' && !empty($zc['settings']['blog_xml_enable']))
		$context['zc']['temp_return'][2] = 'b606';
	
	// doing something more specific than posting?
	if (isset($actions['zc']) && $actions['zc'] == 'post')
	{
		// something with an article....
		if (isset($actions['article']) && !isset($actions['comment']))
		{
			// editing an article
			if (!empty($actions['article']))
				$context['zc']['temp_return'][0] = 'b582';
			// new article
			else
				$context['zc']['temp_return'][1] = 'b585';
		}
		// something with a comment...
		elseif (isset($actions['comment']))
		{
			// editing a comment
			if (!empty($actions['comment']))
				$context['zc']['temp_return'][0] = 'b583';
			// new comment
			else
				$context['zc']['temp_return'][0] = 'b576';
		}
		// something with a poll...
		elseif (isset($actions['poll']))
		{
			// editing an poll
			if (!empty($actions['poll']))
				$context['zc']['temp_return'][0] = 'b573';
			// new poll
			else
				$context['zc']['temp_return'][1] = 'b586';
		}
	}
	// what page are they viewing in the blog control panel?
	elseif (isset($actions['zc']) && $actions['zc'] == 'bcp' && !$context['user']['is_guest'])
	{
		$bcp_sub_pages = array(
			// sub action => array(txt, lang_file, must_return_true)
			'preferences' => array('b90', 'Blog', true),
			'notifications' => array('b257', 'Blog', true),
			'globalSettings' => array('b218', 'ControlPanel', $context['can_access_global_settings_tab']),
			'permissions' => array('b235', 'Blog', $context['can_access_permissions_tab']),
			'communityPage' => array('b302', 'ControlPanel', $context['can_access_blog_index_tab']),
			'plugins' => array('b217', 'ControlPanel', $context['can_access_plugins_tab']),
			'themes' => array('b527', 'ControlPanel', $context['can_access_themes_tab'] || (!empty($actions['blog']) && $context['can_use_blog_themes'])),
			'maintenance' => array('b88', 'Blog', $context['can_access_maintenance_tab']),
			'accessRestrictions' => array('b308', 'ControlPanel', $context['can_restrict_access_blogs']),
			'postingRestrictions' => array('b215', 'ControlPanel', $context['can_set_posting_restrictions']),
			'customWindows' => array('b507', 'ControlPanel', true),
			'categories' => array('b16a', 'Blog', true),
			'tags' => array('b220', 'Blog', true),
		);
		
		if (isset($actions['sa']) && isset($bcp_sub_pages[$actions['sa']]) && !empty($bcp_sub_pages[$actions['sa']][0]) && isset($bcp_sub_pages[$actions['sa']][2]) && $bcp_sub_pages[$actions['sa']][2] === true)
		{
			if (!isset($txt[$bcp_sub_pages[$actions['sa']][0]]))
				// load the language file
				zcLoadLanguage($bcp_sub_pages[$actions['sa']][1]);
		
			if (function_exists('zcFormatTxtString'))
			{
				if (!empty($actions['blog']))
					$context['zc']['temp_return'][1] = sprintf($txt['b608'], zcFormatTxtString($bcp_sub_pages[$actions['sa']][0]));
				else
					$context['zc']['temp_return'][2] = sprintf($txt['b607'], zcFormatTxtString($bcp_sub_pages[$actions['sa']][0]));
			}
			elseif (!empty($actions['blog']))
				$context['zc']['temp_return'][1] = sprintf($txt['b608'], $bcp_sub_pages[$actions['sa']][0]);
			else
				$context['zc']['temp_return'][2] = sprintf($txt['b607'], $bcp_sub_pages[$actions['sa']][0]);
		}
		elseif (!empty($actions['blog']))
			$context['zc']['temp_return'][1] = 'b609';
		else
			$context['zc']['temp_return'][2] = 'b590';
	}
	
	// we still haven't figured out what they're viewing... try the zc_actions array
	if (empty($context['zc']['temp_return'][0]) && empty($context['zc']['temp_return'][1]) && empty($context['zc']['temp_return'][2]) && isset($zc_actions) && isset($actions['zc']) && isset($zc_actions[$actions['zc']]) && isset($zc_actions[$actions['zc']][3]) && $zc_actions[$actions['zc']][3] === true)
	{
		if (!empty($zc_actions[$actions['zc']][0]) && !empty($actions['article']))
			$context['zc']['temp_return'][0] = $zc_actions[$actions['zc']][0];
		elseif (!empty($zc_actions[$actions['zc']][1]) && !empty($actions['blog']))
			$context['zc']['temp_return'][1] = $zc_actions[$actions['zc']][1];
		elseif (!empty($zc_actions[$actions['zc']][2]))
			$context['zc']['temp_return'][2] = $zc_actions[$actions['zc']][2];
	}
	
	// plug-in slot #5
	zc_plugin_slot(5);
	
	// Nothing or nothing the user is allowed to see....
	if (empty($context['zc']['temp_return'][0]) && empty($context['zc']['temp_return'][1]) && empty($context['zc']['temp_return'][2]))
		$context['zc']['temp_return'][2] = 'b570';
		
	for ($n = 0; $n <= 2; $n++)
		if (!empty($context['zc']['temp_return'][$n]) && isset($txt[$context['zc']['temp_return'][$n]]))
			$context['zc']['temp_return'][$n] = $txt[$context['zc']['temp_return'][$n]];
		
	$return = $context['zc']['temp_return'];
	
	// don't need these anymore...
	unset($context['zc']['temp_return']);
	unset($context['zc']['temp_actions']);
	
	return $return;
}

?>