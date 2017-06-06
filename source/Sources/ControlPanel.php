<?php

if (!defined('zc'))
	die('Hacking attempt...');

function blogControlPanel($memID = null)
{
	global $txt, $context, $scripturl, $zcFunc, $blog, $blog_info, $zc;
	
	// load control panel lang file...
	zcLoadLanguage('ControlPanel');
	
	$context['secondary_bcp_greeting'] = '';
	
	// just in case they got this far... and show them a message telling them they should register ;)
	if ($context['user']['is_guest'])
		zc_fatal_error('b131', false, true);
	
	$context['zc']['base_bcp_url'] = $scripturl . '?zc=bcp';
	$context['zc']['zc_request'] = 'zc' . (!empty($_REQUEST['zc']) ? '=' . $_REQUEST['zc'] : '');
	
	// viewing a blog in the blog control panel..... must be either blog owner or admin...
	if (!empty($blog) && !$context['is_blog_owner'] && !$context['can_access_any_blog_control_panel'])
		zc_fatal_error(array('zc_error_17', 'b207'));

	// the u request variable is the owner of the blog control panel
	if (!empty($_REQUEST['u']))
		$memID = (int) $_REQUEST['u'];
		
	// $memID was never set... so show this user their own blog control panel
	if (empty($memID))
		$memID = $context['user']['id'];
		
	// verify that this is a real user...
	$request = $zcFunc['db_query']("
		SELECT {tbl:members::column:id_member} AS id_member, {tbl:members::column:real_name} AS real_name
		FROM {db_prefix}{table:members}
		WHERE id_member = {int:member_id}
		LIMIT 1", __FILE__, __LINE__,
		array(
			'member_id' => $memID
		)
	);
	if ($zcFunc['db_num_rows']($request) == 0)
		zc_fatal_error('zc_error_26');
	else
	{
		$row = $zcFunc['db_fetch_assoc']($request);
		$cp_owner_name = $row['real_name'];
	}
	$zcFunc['db_free_result']($request);
	
	// u_request
	$context['zc']['u_request'] = ';u=' . $memID;
		
	// only the blog control panel owner and admins are allowed to view
	if (($memID != $context['user']['id']) && !$context['can_access_any_blog_control_panel'])
		zc_fatal_error('zc_error_4');
	
	$context['blog_control_panel'] = true;
	$context['show_control_panel_home'] = empty($blog);
	
	$context['zc']['cp_owner']['id'] = $memID;
	$context['zc']['cp_owner']['name'] = !empty($cp_owner_name) ? $cp_owner_name : '';
	$context['is_cp_owner'] = $context['zc']['cp_owner']['id'] == $context['user']['id'];
	
	$context['page_title'] = (!$context['is_cp_owner'] && !empty($context['zc']['cp_owner']['name']) ? $context['zc']['cp_owner']['name'] . '\'s ' : '') . $txt['b3002'];
	
	$context['zc']['link_tree']['bcp'] = '<a href="' . $scripturl . '?zc=bcp;u='. $memID .'">'. $context['page_title'] .'</a>';
	
	// count the number of blogs they have
	$request = $zcFunc['db_query']("
		SELECT COUNT(blog_id)
		FROM {db_prefix}blogs
		WHERE blog_owner = {int:member_id}", __FILE__, __LINE__,
		array(
			'member_id' => $memID
		)
	);
	list($context['zc']['cp_owner']['num_blogs_owned']) = $zcFunc['db_fetch_row']($request);
	$zcFunc['db_free_result']($request);
	
	if (empty($context['zc']['cp_owner']['num_blogs_owned']))
		$context['zc']['cp_owner']['num_blogs_owned'] = 0;

	// handle sub actions
	if (!empty($_REQUEST['sa']))
	{
		// 'action' => array('File', 'Function', MUST_RETURN_TRUE)
		$subActions = array(
			'deleteBlog' => array('Subs-Blogs.php', 'zcDeleteBlog', true),
			'deletePlugIns' => array('Subs-Plugins.php', 'zc_delete_plugins', true),
			'edcw' => array('Subs-CP.php', 'zcEnableDisableCustomWindow', true),
			'edp' => array('Subs-Plugins.php', 'zcEnableDisablePlugin', $context['can_access_plugins_tab']),
			'importsmfboards' => array('Import-smf.php', 'zc_import_smf_boards', $context['can_access_global_settings_tab']),
			'initBlog' => array('Subs-Blogs.php', 'zcInitBlog', true),
			'maintainblogcategories' => array('Subs-Maintenance.php', 'zcMaintainBlogCategories', $context['can_access_other_tab']),
			'maintaintags' => array('Subs-Maintenance.php', 'zcMaintainTags', $context['can_access_other_tab']),
			'pcw' => array('ControlPanel.php', 'zc_preview_custom_window', true),
			'prunedrafts' => array('Subs-Maintenance.php', 'zcPruneDrafts', $context['can_access_other_tab']),
			'recountblogtotals' => array('Subs-Maintenance.php', 'zcRecountBlogTotals', $context['can_access_other_tab']),
		);
			
		if (!empty($context['zc']['bcp_subActions']) && is_array($context['zc']['bcp_subActions']))
			$subActions = array_merge($subActions, $context['zc']['bcp_subActions']);
			
		// don't need this anymore....
		if (isset($context['zc']['bcp_subActions']))
			unset($context['zc']['bcp_subActions']);
		
		if (!empty($subActions[$_REQUEST['sa']]) && file_exists($zc['sources_dir'] . '/' . $subActions[$_REQUEST['sa']][0]) && $subActions[$_REQUEST['sa']][2] === true)
		{
			$context['zc']['sa_request'] = ';sa=' . $_REQUEST['sa'];
			require_once($zc['sources_dir'] . '/' . $subActions[$_REQUEST['sa']][0]);
			if (function_exists($subActions[$_REQUEST['sa']][1]))
				return $subActions[$_REQUEST['sa']][1]($memID);
		}
		elseif (!empty($subActions[$_REQUEST['sa']]))
			// they didn't return a subAction function AND the subAction function exists... redirect them...
			zc_redirect_exit(zcRequestVarsToString('sa'));
	}
		
	// has to go after the above sub actions....
	zcLoadTemplate('ControlPanel');
	
	$context['zc']['primary_bcp_menu'] = array(
		'_info_' => array(
			'location' => 'bcp1',
		),
		'main' => array(
			'label' => $txt['b28'],
			'is_active' => empty($blog),
			'can_see' => true,
			'attributes' => array(
				'href' => $scripturl . '?zc=bcp;u=' . $memID,
			),
		),
	);
	
	if (!empty($blog))
		$context['zc']['tertiary_bcp_menu'] = array(
			'_info_' => array(
				'location' => 'bcp2',
			),
			'write_article' => array(
				'label' => $txt['b76'],
				'is_active' => false,
				'can_see' => $context['can_post_articles'],
				'attributes' => array(
					'href' => $scripturl . '?zc=post;blog=' . $blog . '.0;article',
				),
			),
			'go_to_blog' => array(
				'label' => $txt['b3001'],
				'is_active' => false,
				'can_see' => true,
				'attributes' => array(
					'href' => $scripturl . '?blog=' . $blog . '.0',
				),
			),
		);
	
	/*
		for $context['zc']['subMenu']...
			subaction => array(tab text, must_return_true for the user to use/see the tab, info text)
	*/
	
	if (!empty($blog) || !empty($context['zc']['cp_owner']['num_blogs_owned']))
		$blog_subMenu = array(
			'blogSettings' => array($txt['b214'], true),
			'themes' => array($txt['b527'], $context['can_use_blog_themes']),
			'accessRestrictions' => array($txt['b308'], $context['can_restrict_access_blogs']),
			'postingRestrictions' => array($txt['b215'], $context['can_set_posting_restrictions']),
			'customWindows' => array($txt['b221'], true),
			'categories' => array($txt['b16a'], true),
			'tags' => array($txt['b220'], true),
		);
		
	$main_subMenu = array(
		'manageBlogs' => array($txt['b216'], true),
		'preferences' => array($txt['b90'], true),
		'notifications' => array($txt['b257'], $context['can_mark_notify']),
		'globalSettings' => array($txt['b218'], $context['can_access_global_settings_tab'] && $context['is_cp_owner']),
		'permissions' => array($txt['b235'], $context['can_access_permissions_tab'] && $context['is_cp_owner']),
		'communityPage' => array($txt['b302'], $zc['settings']['zc_mode'] == 3 && $context['can_access_blog_index_tab'] && $context['is_cp_owner']),
		'plugins' => array($txt['b217'], $context['can_access_plugins_tab'] && $context['is_cp_owner']),
		'themes' => array($txt['b527'], $context['can_access_themes_tab'] && $context['is_cp_owner']),
		'other' => array($txt['b5'], $context['can_access_other_tab'] && $context['is_cp_owner']),
	);

	// sub menu when viewing a blog in the blog control panel...
	if (!empty($blog))
		$subMenu = $blog_subMenu;
	// $blog isn't set?  must be the Main tab then
	else
		$subMenu = $main_subMenu;
		
	if (!empty($context['zc']['bcp_subMenu']) && is_array($context['zc']['bcp_subMenu']))
		$context['zc']['subMenu'] = array_merge($subMenu, $context['zc']['bcp_subMenu']);
	else
		$context['zc']['subMenu'] = $subMenu;
		
	// if condition is not true... unset the subMenu tab...
	foreach ($context['zc']['subMenu'] as $key => $array)
		if ($array[1] !== true)
			unset($context['zc']['subMenu'][$key]);
	
	if (!isset($_REQUEST['sa']))
		$_REQUEST['sa'] = !empty($blog) ? 'blogSettings' : 'manageBlogs';
		
	$valid_sub_actions = array_keys($context['zc']['subMenu']);
	
	// sub actions and sub menus... fun ;)
	$_REQUEST['sa'] = isset($_REQUEST['sa']) && !in_array($_REQUEST['sa'], array('blogSettings')) ? $_REQUEST['sa'] : '';
	if (in_array($_REQUEST['sa'], $valid_sub_actions))
		$context['zc']['current_subMenu'] = $_REQUEST['sa'];
	else
		$context['zc']['current_subMenu'] = !empty($blog) ? 'blogSettings' : 'manageBlogs';
		
	$context['page_title'] = $txt['b279'] . ' - ' . $context['zc']['subMenu'][$context['zc']['current_subMenu']][0];
	
	$context['zc']['secondary_bcp_menu'] = array('_info_' => array('location' => 'bcp2'));
		
	if (!empty($blog_subMenu))
		foreach ($blog_subMenu as $sa => $array)
			$blog_subMenu[$sa] = array(
				'label' => $array[0],
				'is_active' => $sa == $context['zc']['current_subMenu'] && !empty($blog),
				'can_see' => $array[1],
				'attributes' => array(
					'href' => $context['zc']['base_bcp_url'] . $context['zc']['u_request'] . $context['zc']['blog_request'] . $context['zc']['blogStart_request'] . ';sa='. $sa,
				),
			);
			
	foreach ($main_subMenu as $sa => $array)
		$main_subMenu[$sa] = array(
			'label' => $array[0],
			'is_active' => $sa == $context['zc']['current_subMenu'] && empty($blog),
			'can_see' => $array[1],
			'attributes' => array(
				'href' => $context['zc']['base_bcp_url'] . $context['zc']['u_request'] . $context['zc']['blogStart_request'] . ';sa='. $sa,
			),
		);
		
	$context['zc']['secondary_bcp_menu'] += (!empty($blog) ? $blog_subMenu : $main_subMenu);
	$context['zc']['primary_bcp_menu']['main']['sub_menu'] = $main_subMenu;
	
	$secondary_bcp_menu_subMenu = array(
		'communityPage' => array(
			'settings' => array('label' => $txt['b442']),
			'windows' => array('label' => $txt['b221']),
		),
		'other' => array(
			'error_log' => array('label' => $txt['b3040']),
			'maintenance' => array('label' => $txt['b88']),
		),
	);
	
	foreach ($secondary_bcp_menu_subMenu as $sa => $array)
		if (isset($context['zc']['secondary_bcp_menu'][$sa]))
		{
			foreach ($array as $ssa => $dummy)
				$array[$ssa] += array(
					'can_see' => true,
					'attributes' => array(
						'href' => $context['zc']['base_bcp_url'] . $context['zc']['u_request'] . $context['zc']['blogStart_request'] . ';sa='. $sa . ';ssa=' . $ssa
					),
					'is_active' => isset($_REQUEST['ssa']) && $_REQUEST['ssa'] == $ssa
				);
			$context['zc']['secondary_bcp_menu'][$sa]['sub_menu'] = $array;
		}
		else
			unset($secondary_bcp_menu_subMenu[$sa]);
	
	// info text for current page....
	if (!empty($context['zc']['subMenu'][$context['zc']['current_subMenu']][2]))
		$context['zc']['bcp_page_info'] = $context['zc']['subMenu'][$context['zc']['current_subMenu']][2];
	else
		$context['zc']['bcp_page_info'] = $txt['b478'];
		
	// sa_request
	$context['zc']['sa_request'] = ';sa=' . $context['zc']['current_subMenu'];
	
	// do we need to load the array of member groups?
	if (in_array($context['zc']['current_subMenu'], array('globalSettings', 'accessRestrictions', 'postingRestrictions')) && ($context['can_restrict_access_blogs'] || $context['can_set_posting_restrictions'] || $context['can_access_global_settings_tab']))
	{
		// get all the non-post-count-based member groups
		$request = $zcFunc['db_query']("
			SELECT {tbl:membergroups::column:id_group} AS id_group, {tbl:membergroups::column:group_name} AS group_name
			FROM {db_prefix}{table:membergroups}
			WHERE {tbl:membergroups::column:min_posts} < 0
				AND {tbl:membergroups::column:id_group} > 3", __FILE__, __LINE__);
		
		// guests and regular members
		$context['zc']['membergroups'] = array(
			0 => $txt['b134'],
			-1 => $txt['b133'],
		);
		
		// now for additional groups
		while ($row = $zcFunc['db_fetch_assoc']($request))
			$context['zc']['membergroups'][$row['id_group']] = $row['group_name'];
			
		$zcFunc['db_free_result']($request);
	}
				
	// "This user has X blog(s)." or "You have X blog(s)."
	if (!empty($context['zc']['cp_owner']['num_blogs_owned']))
		$part1 = sprintf((!$context['is_cp_owner'] && !empty($context['zc']['cp_owner']['name']) ? $txt['b3'] : $txt['b2']), $context['zc']['cp_owner']['num_blogs_owned'], ($context['zc']['cp_owner']['num_blogs_owned'] == 1 ? $txt['b3003a'] : $txt['b1']));
	else
		$part1 = !$context['is_cp_owner'] && !empty($context['zc']['cp_owner']['name']) ? $txt['b443'] : $txt['b443a'];
	
	// they aren't allowed to create a blog... just show a message how many they have
	if (!$context['can_create_blog'])
		$context['secondary_bcp_greeting'] = $part1;
	// message saying how many blogs they have and a link to create another (if they can make more)
	elseif ($context['zc']['cp_owner']['num_blogs_owned'] < $zc['settings']['max_num_blogs'] || (!$context['can_multiple_blogs'] && !empty($context['zc']['cp_owner']['num_blogs_owned'])) || $context['user']['is_admin'] || empty($zc['settings']['max_num_blogs']))
	{
		// link for creating new blog
		$part2 = '<a href="' . $context['zc']['base_bcp_url'] . $context['zc']['u_request'] . ';sa=initBlog;sesc=' . $context['session_id'] . '" rel="nofollow" onclick="return confirm(\'' . $txt['b445'] . '\');">' . sprintf((!$context['is_cp_owner'] && !empty($context['zc']['cp_owner']['name']) ? $txt['b4'] : $txt['b4a']), $txt['b3003a']) . '</a>';
		
		$context['secondary_bcp_greeting'] = $part1 . ' ' . $part2;
	}
	// message saying they have the maximum # of blogs
	else
		$context['secondary_bcp_greeting'] = $part1 . ' ' . (!$context['is_cp_owner'] && !empty($context['zc']['cp_owner']['name']) ? $txt['b444'] : $txt['b444a']);
		
	$maxindex = 5;
	$blogStart = isset($_REQUEST['blogStart']) ? (int) $_REQUEST['blogStart'] : 0;
	$context['show_blog_page_index'] = $context['zc']['cp_owner']['num_blogs_owned'] > $maxindex;
	
	$context['blog_page_index'] = zcConstructPageIndex($scripturl . '?blogStart=%1$d' . zcRequestVarsToString('blogStart', ';'), $blogStart, $context['zc']['cp_owner']['num_blogs_owned'], $maxindex, true);
	
	// now we want to get the blogs that we are going to display as tabs
	$request = $zcFunc['db_query']("
		SELECT blog_id, name
		FROM {db_prefix}blogs
		WHERE blog_owner = {int:member_id}
		ORDER BY blog_id ASC
		LIMIT {int:start}, {int:maxindex}", __FILE__, __LINE__,
		array(
			'member_id' => $memID,
			'start' => $blogStart,
			'maxindex' => $maxindex
		)
	);
	while ($row = $zcFunc['db_fetch_assoc']($request))
	{
		$submenu = $blog_subMenu;
		
		// fix some things in the submenu array
		foreach ($submenu as $sa => $dummy)
		{
			$submenu[$sa]['attributes']['href'] = $context['zc']['base_bcp_url'] . $context['zc']['u_request'] . ';blog=' . $row['blog_id'] . '.0' . $context['zc']['blogStart_request'] . ';sa=' . $sa;
			$submenu[$sa]['is_active'] = $sa == $context['zc']['current_subMenu'] && $blog == $row['blog_id'];
		}
		
		$context['zc']['primary_bcp_menu'][$row['blog_id']] = array(
			'label' => $zcFunc['un_htmlspecialchars']($row['name']),
			'is_active' => $blog == $row['blog_id'],
			'can_see' => true,
			'attributes' => array(
				'href' => $context['zc']['base_bcp_url'] . $context['zc']['u_request'] . $context['zc']['blogStart_request'] .';blog=' . $row['blog_id'] . '.0',
			),
			'sub_menu' => $submenu,
		);
	}
	$zcFunc['db_free_result']($request);
	
	if (!empty($blog) && $context['zc']['current_subMenu'] != 'blogSettings')
		zc_bcp_blogSettings($memID);
	
	$context['zc']['sub_sub_template'] = $context['zc']['current_subMenu'];
	
	$context['zc']['link_tree']['bcp_subMenu'] = '<a href="' . $scripturl . '?zc=bcp' . $context['zc']['u_request'] . $context['zc']['blog_request'] . $context['zc']['sa_request'] . '">' . $context['zc']['subMenu'][$context['zc']['current_subMenu']][0] . '</a>';
	
	// now load the subaction function (if there is one)
	if (!empty($context['zc']['current_subMenu']) && isset($context['zc']['subMenu'][$context['zc']['current_subMenu']]))
		if (function_exists('zc_bcp_' . $context['zc']['current_subMenu']))
		{
			$sub_menu_function = 'zc_bcp_' . $context['zc']['current_subMenu'];
			$sub_menu_function($memID);
		}
		else
			zc_redirect_exit(zcRequestVarsToString('sa'));
}

function zc_bcp_manageBlogs($memID)
{
	global $context, $zc;
	
	$context['can_delete_blogs'] = ($context['can_delete_own_blogs'] && $context['is_cp_owner']) || $context['user']['is_admin'];
	
	require_once($zc['sources_dir'] . '/Subs-Blogs.php');
	
	$context['my_blogs'] = array();
	if (($array = zc_get_list_of_blogs(null, 'b.blog_owner = ' . $memID, $context['zc']['cp_owner']['num_blogs_owned'])) != false)
		list($context['my_blogs'], $context['zc']['list1']) = $array;
}

function zc_bcp_blogSettings($memID)
{
	global $context, $txt;
	global $zcFunc, $blog, $blog_info, $zc;
	
	if ($context['zc']['current_subMenu'] == 'blogSettings' && isset($_REQUEST['delete_blog_avatar']))
	{
		checkSession('get');
	
		if (!empty($context['zc']['blog_settings']['blog_avatar']) && file_exists($context['zc']['defaultSettings']['blog_avatar']['dir'] . '/' . $context['zc']['blog_settings']['blog_avatar']))
			unlink($context['zc']['defaultSettings']['blog_avatar']['dir'] . '/' . $context['zc']['blog_settings']['blog_avatar']);
			
		// update the blog settings...
		$zcFunc['db_update']('{db_prefix}settings', array('blog_avatar' => 'string', 'blog_id' => 'int'), array('blog_avatar' => ''), array('blog_id' => $blog));
		$_SESSION['zc_success_msg'] = 'zc_success_8';
		zc_redirect_exit(zcRequestVarsToString('delete_blog_avatar'));
	}
	
	// are they allowed to mess with blog ownership?
	if ($context['can_change_blog_ownership'])
	{
		// changing blog ownership?
		if (isset($_POST['change_blog_owner']))
		{
			checkSession('get');
		
			require_once($zc['sources_dir'] . '/Subs-Blogs.php');
			$new_owner = (string) $_POST['change_blog_owner'];
			zc_change_blog_owner($blog, $new_owner);
			
			if (empty($context['zc']['errors']))
				zc_redirect_exit(zcRequestVarsToString());
		}
		
		// get the blog owner's name
		$context['zc']['this_blog_owner_name'] = '';
		if (!empty($blog_info['blog_owner']))
		{
			$request = $zcFunc['db_query']("
				SELECT {tbl:members::column:real_name} AS real_name
				FROM {db_prefix}{table:members}
				WHERE {tbl:members::column:id_member} = {int:blog_owner_id}
				LIMIT 1", __FILE__, __LINE__,
				array(
					'blog_owner_id' => $blog_info['blog_owner']
				)
			);
			while ($row = $zcFunc['db_fetch_assoc']($request))
				$context['zc']['this_blog_owner_name'] = $row['real_name'];
			$zcFunc['db_free_result']($request);
		}
	}
	
	// this blog has no settings in the blog_settings table?  ... we should set some...
	if (empty($context['zc']['blog_settings_row_exists']))
	{
		// get some info from the blogs table about this blog
		$request = $zcFunc['db_query']("
			SELECT member_groups, description, name
			FROM {db_prefix}blogs
			WHERE blog_id = {int:blog_id}
			LIMIT 1", __FILE__, __LINE__,
			array(
				'blog_id' => $blog
			)
		);
		
		if ($zcFunc['db_num_rows']($request) > 0)
			$blog_info = $zcFunc['db_fetch_assoc']($request);
			
		$zcFunc['db_free_result']($request);
		
		if (empty($blog_info['name']))
		{
			$blog_info['name'] = addslashes($zcFunc['htmlspecialchars'](stripslashes(zcFormatTextSpecialMeanings($zc['settings']['base_new_blogs_name'])), ENT_QUOTES));
			for ($i = 1; $i < 20; $i++)
				if ($zcFunc['db_num_rows']($zcFunc['db_query']("
					SELECT blog_id
					FROM {db_prefix}blogs
					WHERE blog_owner = {int:member_id}
						AND name = {string:blog_name}", __FILE__, __LINE__, array('member_id' => $memID, 'blog_name' => $blog_info['name']))) == 0)
					break;
				else
					$blog_info['name'] = addslashes($zcFunc['htmlspecialchars'](stripslashes(zcFormatTextSpecialMeanings($zc['settings']['base_new_blogs_name']) . ' ' . $i), ENT_QUOTES));
					
			// update the blogs table
			$zcFunc['db_update']('{db_prefix}blogs', array('blog_id' => 'int', 'name' => 'string'), array('name' => $blog_info['name']), array('blog_id' => $blog));
		}
		
		require_once($zc['sources_dir'] . '/Subs-Blogs.php');
		zc_init_blog_settings($blog, 'settings');
		
		zc_redirect_exit(zcRequestVarsToString());
	}
	
	zc_prepare_side_window_arrays($memID);
	$current_orders = array_keys($context['zc']['side_windows']);

	// this bit will handle setting side window orders to defaults
	if (!empty($context['zc']['side_windows']))
	{
		$updates = array();
		foreach ($context['zc']['side_windows'] as $win_order => $array)
			if (empty($context['zc']['blog_settings'][$array['type'] . 'WindowOrder']))
			{
				if (isset($context['zc']['defaultSettings'][$array['type'] . 'WindowOrder']['value']) && !in_array($context['zc']['defaultSettings'][$array['type'] . 'WindowOrder']['value'], $current_orders))
					$adjustedValue = $context['zc']['defaultSettings'][$array['type'] . 'WindowOrder']['value'];
				else
					for ($i = 0; $i <= count($context['zc']['side_windows']); $i++)
						if (!in_array($i, $current_orders))
						{
							$adjustedValue = $i;
							break 1;
						}
					
				$updates[$array['type'] . 'WindowOrder'] = $adjustedValue;
				$context['zc']['blog_settings'][$array['type'] . 'WindowOrder'] = $adjustedValue;
			}
		
		if (!empty($updates))
			zcUpdateBlogSettings($updates, $blog);
	}

	// save those blog settings :)
	if (isset($_POST['save_blogSettings']))
	{
		checkSession('get');
			
		if (!isset($context['zc']['defaultSettings']))
			$context['zc']['defaultSettings'] = zc_prepare_blog_settings_array();
			
		list($processed, $context['zc']['errors']) = zcProcessForm($context['zc']['defaultSettings']);
		
		$context['zc']['setting_changed'] = array();
		if (empty($context['zc']['errors']))
		{
			$request = $zcFunc['db_query']("
				SELECT blog_id
				FROM {db_prefix}blogs
				WHERE name = {string:blog_name}
					AND blog_id != {int:blog_id}
				LIMIT 1", __FILE__, __LINE__,
				array(
					'blog_name' => $processed['blogName'],
					'blog_id' => $blog
				)
			);
			if ($zcFunc['db_num_rows']($request) > 0)
				$context['zc']['errors']['blogName'] = array('zc_error_8', 'b391');
			$zcFunc['db_free_result']($request);
				
			// if still no errors... continue
			if (empty($context['zc']['errors']))
			{
				// update this blog's name and description
				$zcFunc['db_update']('
					{db_prefix}blogs',
					array('blog_id' => 'int', 'name' => 'string', 'description' => 'string'),
					array('name' => $processed['blogName'], 'description' => $processed['blogDescription']),
					array('blog_id' => $blog));
				
				// these are not stored in the blog_settings table!
				unset($processed['blogName'], $processed['blogDescription']);
				
				// if we changed the blog avatar... delete the old avatar from the server
				if (!empty($context['zc']['blog_settings']['blog_avatar']) && !empty($processed['blog_avatar']) && $context['zc']['blog_settings']['blog_avatar'] != $processed['blog_avatar'] && file_exists($context['zc']['defaultSettings']['blog_avatar']['dir'] . '/' . $context['zc']['blog_settings']['blog_avatar']))
					unlink($context['zc']['defaultSettings']['blog_avatar']['dir'] . '/' . $context['zc']['blog_settings']['blog_avatar']);
				
				// update the blog_settings table...
				zcUpdateBlogSettings($processed, $blog);
				$_SESSION['zc_success_msg'] = 'zc_success_2';
				zc_redirect_exit(zcRequestVarsToString());
			}
		}
	}
			
	$context['zc']['plugin_settings_info'] = zc_prepare_plugin_settings_array();
	// needs a row in the plugin_settings table...
	if (empty($context['zc']['plugin_settings_row_exists']) && !empty($context['zc']['plugin_settings_info']))
	{
		require_once($zc['sources_dir'] . '/Subs-Blogs.php');
		zc_init_blog_settings($blog, 'plugin_settings');	
		zc_redirect_exit(zcRequestVarsToString());
	}
	
	// let's load language files for plugins
	if (!empty($context['zc']['plugins']))
		foreach ($context['zc']['plugins'] as $id => $plugin)
		{
			// plugins must be enabled... and there should be a lang file specified...
			if (empty($zc['settings']['zcp_' . $id . '_enabled']) || empty($plugin['lngfile']))
				continue;
		
			// load it up
			zcLoadLanguage($plugin['lngfile'], '', false, $zc['plugins_lang_dir']);
		}
		
	// save plug-in settings for this blog...
	if (isset($_POST['save_blogPlugInSettings']))
	{
		checkSession('get');
		
		list($processed, $context['zc']['errors']) = zcProcessForm($context['zc']['plugin_settings_info']);
		
		if (empty($context['zc']['errors']))
		{
			zcUpdatePlugInSettings($processed, $blog);
			$_SESSION['zc_success_msg'] = 'zc_success_2';
			zc_redirect_exit(zcRequestVarsToString());
		}
	}
	
	if ($context['zc']['current_subMenu'] == 'blogSettings')
	{
		global $scripturl;
		
		// populate $context['zc']['defaultSettings']['blog_avatar']['show_above_field']
		if (!empty($context['zc']['blog_settings']['blog_avatar']))
		{
			list($avatar_width, $avatar_height) = zcResizeImage($zc['settings']['attachments_url'] . '/' . $context['zc']['blog_settings']['blog_avatar'], ($zc['settings']['blog_index_max_avatar_width'] > 65 ? 65 : $zc['settings']['blog_index_max_avatar_width']), ($zc['settings']['blog_index_max_avatar_height'] > 50 ? 50 : $zc['settings']['blog_index_max_avatar_height']));
			$avatar_width = ' width="' . $avatar_width . '"';
			$avatar_height = ' height="' . $avatar_height . '"';
			$context['zc']['defaultSettings']['blog_avatar']['show_above_field'] = '<img src="' . $zc['settings']['attachments_url'] . '/' . $zcFunc['htmlspecialchars']($context['zc']['blog_settings']['blog_avatar']) . '"' . $avatar_width . $avatar_height . ' alt="" /><a href="' . $scripturl . '?delete_blog_avatar' . zcRequestVarsToString(null, ';') . ';sesc=' . $context['session_id'] . '" title="' . $txt['b127'] . '" style="margin-left:3px;"><img src="' . $context['zc']['default_images_url'] . '/icons/disable_icon.gif" alt="' . $txt['b127'] . '" /></a>';
		}
		else
		{
			if (!empty($zc['settings']['default_blog_avatar']))
				$default_avatar_url = $zc['settings']['attachments_url'] . '/' . $zc['settings']['default_blog_avatar'];
			else
				$default_avatar_url = $context['zc']['default_images_url'] . '/defaultAvatar.png';
							
			list($avatar_width, $avatar_height) = zcResizeImage($default_avatar_url, ($zc['settings']['blog_index_max_avatar_width'] > 65 ? 65 : $zc['settings']['blog_index_max_avatar_width']), ($zc['settings']['blog_index_max_avatar_height'] > 50 ? 50 : $zc['settings']['blog_index_max_avatar_height']));
			$avatar_width = ' width="' . $avatar_width . '"';
			$avatar_height = ' height="' . $avatar_height . '"';
			$context['zc']['defaultSettings']['blog_avatar']['show_above_field'] = '<img src="' . $default_avatar_url . '"' . $avatar_width . $avatar_height . ' alt="" />';
		}
	}
}

function zc_bcp_categories($memID)
{
	global $context, $scripturl, $txt, $blog, $zcFunc, $zc;
	
	require_once($zc['sources_dir'] . '/Subs-CP.php');
	zc_prepare_blog_category_attributes();
	$context['zc']['form_info'] = $context['zc']['attributes']['categories'];
	$context['zc']['current_info'] = array();
		
	// are we doing something with blog categories?
	if (isset($_REQUEST['categories']))
	{
		// check session before we do anything here
		checkSession('get');
	
		// we are adding/editing a category huh?
		if (($_REQUEST['categories'] == 'add') || ($_REQUEST['categories'] == 'edit'))
		{
			if (isset($context['zc']['attributes']['categories']))
			{
				list($processed, $context['zc']['errors']) = zcProcessForm($context['zc']['attributes']['categories']);
	
				if (empty($processed))
					zc_fatal_error();
		
				if (empty($context['zc']['errors']))
				{
					$id = !empty($_POST['id']) ? (int) $_POST['id'] : 0;
					$columns = array('blog_id' => 'int');
						
					if (empty($id))
					{
						$request = $zcFunc['db_query']("
							SELECT cat_order
							FROM {db_prefix}categories
							WHERE blog_id = {int:blog_id}
							ORDER BY cat_order DESC
							LIMIT 1", __FILE__, __LINE__,
							array(
								'blog_id' => $blog
							)
						);
						if ($zcFunc['db_num_rows']($request) > 0)
						{
							list($order) = $zcFunc['db_fetch_row']($request);
							$order++;
						}
						else
							$order = 1;
						$zcFunc['db_free_result']($request);
						
						$columns['name'] = 'string';
						$columns['cat_order'] = 'int';
						$processed += array('blog_id' => $blog, 'cat_order' => $order);
					}
					else
						$columns['blog_category_id'] = 'int';
						
					if (empty($id))
						$zcFunc['db_insert']('insert', '{db_prefix}categories', $columns, $processed);
					else
						$zcFunc['db_update']('{db_prefix}categories', $columns, $processed, array('blog_category_id' => $id, 'blog_id' => $blog));
				}
			}
		}
		// so you wanna move a category?
		elseif ($_REQUEST['categories'] == 'move')
		{
			$new_order = (int) $_REQUEST['order'];
			
			// get this item's current order
			$request = $zcFunc['db_query']("
				SELECT cat_order AS item_order
				FROM {db_prefix}categories
				WHERE blog_category_id = {int:id}
					AND blog_id = {int:blog_id}
				LIMIT 1", __FILE__, __LINE__,
				array(
					'blog_id' => $blog,
					'id' => (int) $_REQUEST['id']
				)
			);
			
			if ($zcFunc['db_num_rows']($request) > 0)
				$row = $zcFunc['db_fetch_assoc']($request);
				
			$zcFunc['db_free_result']($request);
			
			// is the item's current order different from the new order?
			if (($new_order != $row['item_order']) && !empty($new_order))
				$old_order = $row['item_order'];
				
			// now to update the other items in table...
			if (isset($old_order))
				$zcFunc['db_update'](
					'{db_prefix}categories',
					array('cat_order' => 'int', 'blog_id' => 'int'),
					array('cat_order' => array($new_order < $old_order ? '+' : '-', 1)),
					array('cat_order' => array(array($new_order < $old_order ? '<' : '>', $new_order), array($new_order < $old_order ? '>=' : '<=', $old_order)), 'blog_id' => $blog),
					null
				);
			
			// finally we can change the order of the item we originally wanted to move
			if (!empty($new_order))
				$zcFunc['db_update']('{db_prefix}categories', array('cat_order' => 'int', 'blog_category_id' => 'int', 'blog_id' => 'int'), array('cat_order' => $new_order), array('blog_id' => $blog, 'blog_category_id' => (int) $_REQUEST['id']));
		}
		// deleting a category?
		elseif ($_REQUEST['categories'] == 'delete')
		{
			if (!empty($_POST['items']))
			{
				$items = array();
				foreach ($_POST['items'] as $item)
					$items[] = (int) $item;
					
				$num_items = count($items);
			
				// we will need the current orders
				$request = $zcFunc['db_query']("
					SELECT cat_order
					FROM {db_prefix}categories
					WHERE blog_category_id IN ({array_int:items})
						AND blog_id = {int:blog_id}
						ORDER BY cat_order DESC
					LIMIT {int:limit}", __FILE__, __LINE__,
					array(
						'items' => $items,
						'limit' => $num_items,
						'blog_id' => $blog
					)
				);
				$missing_orders = array();
				while ($row = $zcFunc['db_fetch_row']($request))
					$missing_orders[] = $row[0];
				$zcFunc['db_free_result']($request);
				
				// clear the blog_category_id column for articles using this/these category/categories
				$zcFunc['db_update'](
					'{db_prefix}articles',
					array('blog_category_id' => 'int'),
					array('blog_category_id' => 0),
					array('blog_category_id' => array('IN', $items)),
					$num_items);
				
				// delete the category/categories
				$zcFunc['db_query']("
					DELETE FROM {db_prefix}categories
					WHERE blog_category_id IN ({array_int:items})
						AND blog_id = {int:blog_id}
					LIMIT {int:limit}", __FILE__, __LINE__,
					array(
						'limit' => $num_items,
						'items' => $items,
						'blog_id' => $blog
					)
				);
				
				// decrease the orders of categories above the order of the category/categories we deleted
				if (!empty($missing_orders))
					foreach ($missing_orders as $order)
						$zcFunc['db_update'](
							'{db_prefix}categories',
							array('cat_order' => 'int', 'blog_id' => 'int'),
							array('cat_order' => array('-', 1)),
							array('blog_id' => $blog, 'cat_order' => array('>', $order)),
							null);
			}
		}
		
		// make sure there were no errors before redirecting the user
		if (empty($context['zc']['errors']))
			zc_redirect_exit(zcRequestVarsToString());
	}
	
	// count number of blog categories
	$request = $zcFunc['db_query']("
		SELECT COUNT(blog_category_id)
		FROM {db_prefix}categories
		WHERE blog_id = {int:blog_id}", __FILE__, __LINE__,
		array(
			'blog_id' => $blog
		)
	);
	list($num_categories) = $zcFunc['db_fetch_row']($request);
	$zcFunc['db_free_result']($request);
		
	$maxindex = isset($_REQUEST['all']) && !empty($zc['settings']['allow_show_all_link']) ? 99999 : 15;
	$start = isset($_REQUEST['listStart']) ? (int) $_REQUEST['listStart'] : 0;
	
	$context['zc']['list1']['show_page_index'] = !empty($num_categories) && $num_categories > $maxindex;
		
	if ($context['zc']['list1']['show_page_index'])
	{
		$context['zc']['list1']['page_index'] = zcConstructPageIndex($scripturl . '?listStart=%d' . zcRequestVarsToString('all,listStart', ';'), $start, $num_categories, $maxindex, true);
		
		if (!empty($zc['settings']['allow_show_all_link']))
			$context['zc']['list1']['show_all_link'] = '<a href="' . $scripturl . zcRequestVarsToString('all,listStart', '?') .';all">' . $txt['b81'] . '</a>';
	}
			
	// Default sort methods.
	$sort_methods = array(
		'name' => 'name',
	);

	if (!isset($_REQUEST['sort']) || !isset($sort_methods[$_REQUEST['sort']]))
	{
		$sort_by = 'name';
		$sort = 'name';
		$ascending = isset($_REQUEST['asc']);
	}
	else
	{
		$sort_by = $_REQUEST['sort'];
		$sort = $sort_methods[$_REQUEST['sort']];
		$ascending = isset($_REQUEST['asc']);
	}
		
	// make array of table header info
	$tableHeaders = array(
		'url_requests' => zcRequestVarsToString('sort,asc,desc', '?'),
		'headers' => array(
			'name' => array('label' => $txt['b509']),
		),
		'sort_direction' => $ascending ? 'up' : 'down',
		'sort_by' => $sort_by,
	);
	
	// create the table headers
	$context['zc']['list1']['table_headers'] = zcCreateTableHeaders($tableHeaders);
	$context['zc']['list1']['table_headers']['checkbox'] = '<input type="checkbox" onclick="invertAll(this, this.form, \'items[]\');" class="check" />';
	
	// let's get all of this user's blog categories
	$request = $zcFunc['db_query']("
		SELECT blog_category_id, cat_order, name
		FROM {db_prefix}categories
		WHERE blog_id = {int:blog_id}
		ORDER BY {raw:sort}" . ($ascending ? '' : ' DESC') . "
		LIMIT {int:start}, {int:maxindex}", __FILE__, __LINE__,
		array(
			'sort' => $sort,
			'blog_id' => $blog,
			'start' => $start,
			'maxindex' => $maxindex
		)
	);
	
	$context['zc']['list1']['submit_button_txt'] = $txt['b3006'];
	$context['zc']['list1']['confirm_submit_txt'] = sprintf($txt['b71'], $txt['b73']);
	$context['zc']['list1']['form_url'] = $scripturl . zcRequestVarsToString(null, '?') . ';categories=delete;sesc='. $context['session_id'];
	$context['zc']['list1']['list_empty_txt'] = sprintf($txt['b470'], $txt['b73']);
	$context['zc']['categories'] = array();
	while ($row = $zcFunc['db_fetch_assoc']($request))
	{
		$row['name'] = $zcFunc['un_htmlspecialchars']($row['name']);
		$context['zc']['categories'][$row['blog_category_id']] = array(
			'name' => $row['name'],
			'order' => $row['cat_order'],
			'edit_link' => '<a href="' . $scripturl . zcRequestVarsToString(null, '?') . ';edit=' . $row['blog_category_id'] . '#add" title="' . $txt['b17'] . '" rel="nofollow"><span class="edit_icon">&nbsp;</span></a>',
			'move_link' => '<a href="' . $scripturl . zcRequestVarsToString(null, '?') . ';move=' . $row['blog_category_id'] . '#categories" title="' . $txt['b19'] . '" rel="nofollow"><span class="move_icon">&nbsp;</span></a>',
			'checkbox' => '<input type="checkbox" name="items[]" value="' . $row['blog_category_id'] . '" />',
		);
	}
	$zcFunc['db_free_result']($request);
	
	if (!empty($_REQUEST['edit']) && !empty($context['zc']['categories'][$_REQUEST['edit']]))
		$context['zc']['current_info'] = $context['zc']['categories'][$_REQUEST['edit']];
		
	$context['zc']['form_info']['_info_']['title'] = (!empty($context['zc']['current_info']) ? $txt['b17'] : $txt['b18']) . ' ' . $txt['b16'];
		
	$c = false;
	if (!empty($context['zc']['categories']))
		foreach ($context['zc']['categories'] as $k => $category)
		{
			if (empty($c) && !empty($_REQUEST['move']) && $_REQUEST['move'] == $k)
			{
				$c = true;
				$context['zc']['categories'][$k]['move_link'] = '';
				$context['zc']['categories'][$k]['move_link'] .= '
						<input type="hidden" name="id" value="' . $k . '" />
						<select name="order" style="width:50px;">
							<option value="' . $category['order'] . '">' . $category['order'] . '</option>';
							
				for ($i = 1; $i <= $num_categories; $i++)
					if ($i != $category['order'])
						$context['zc']['categories'][$k]['move_link'] .= '
							<option value="' . $i . '">' . $i . '</option>';
							
				$context['zc']['categories'][$k]['move_link'] .= '
						</select>
						<input type="submit" value="' . $txt['b19'] . '" />';
						
				// we will need a different form url...
				$context['zc']['list1']['form_url'] = $scripturl . zcRequestVarsToString(null, '?') . ';categories=move' . ';sesc=' . $context['session_id'] . ';move=' . $k;
				
				$context['zc']['list1']['hide_primary_submit'] = true;
				
				// unset the checkbox variable for each category, so that it doesn't show on the list...
				foreach ($context['zc']['categories'] as $n => $dummy)
					unset($context['zc']['categories'][$n]['checkbox']);
					
				// finally unset the checkbox header...
				unset($context['zc']['list1']['table_headers']['checkbox']);
			}
			unset($context['zc']['categories'][$k]['order']);
		}
}

function zc_bcp_customWindows($memID)
{
	global $context, $scripturl, $txt, $zcFunc, $blog, $zc;
	
	require_once($zc['sources_dir'] . '/Subs-CP.php');
	
	/*$possible_ssa = array('settings', 'custom_windows');
	$_REQUEST['ssa'] = !isset($_REQUEST['ssa']) || !in_array($_REQUEST['ssa'], $possible_ssa) ? 'settings' : $_REQUEST['ssa'];
	$context['zc']['ssa'] = $_REQUEST['ssa'];
		
	if (!empty($context['zc']['secondary_bcp_menu'][$context['zc']['current_subMenu']]['sub_menu'][$context['zc']['ssa']]['label']))
		$context['page_title'] = $txt['b279'] . ' - ' . $context['zc']['secondary_bcp_menu'][$context['zc']['current_subMenu']]['sub_menu'][$context['zc']['ssa']]['label'];
	
	if ($_REQUEST['ssa'] == 'settings')
	{*/
	zc_prepare_sw_settings_array();
	
	if (isset($_POST['save_customWindows']))
	{
		checkSession('get');
		list($processed, $context['zc']['errors']) = zcProcessForm($context['zc']['sw_settings']);
		if (empty($context['zc']['errors']))
		{
			$updates = array();
			foreach ($processed as $key => $value)
				$updates[$key] = $value;
			
			if (!empty($updates))
				zcUpdateBlogSettings($updates, $blog);
				
			$_SESSION['zc_success_msg'] = 'zc_success_2';
			zc_redirect_exit(zcRequestVarsToString());
		}
	}
	
	zc_prepare_custom_window_attributes();
	$context['zc']['form_info'] = $context['zc']['attributes']['customWindows'];
	$context['zc']['current_info'] = array();
	$context['zc']['list1']['submit_button_txt'] = $txt['b3006'];
	$context['zc']['list1']['confirm_submit_txt'] = sprintf($txt['b71'], $txt['b74']);
	$context['zc']['list1']['form_url'] = $scripturl . zcRequestVarsToString(null, '?') . ';customWindows=delete;sesc='. $context['session_id'];
	$context['zc']['list1']['list_empty_txt'] = sprintf($txt['b470'], $txt['b472']);
	
	$context['zc']['list1']['table_headers'] = array(
		'name' => $txt['b180'],
		'title' => $txt['b3026'],
		'content' => $txt['b508'],
		'content_type' => $txt['b673'],
		'checkbox' => '<input type="checkbox" onclick="invertAll(this, this.form, \'items[]\');" class="check" />',
	);
	
	// do we want to display a move window form?
	$context['zc']['display_move_window_form'] = isset($_REQUEST['moveWindow']) ? $_REQUEST['moveWindow'] : '';
	
	// doing something with custom windows?
	if (isset($_REQUEST['customWindows']))
	{
		checkSession('get');
	
		// deleting?
		if ($_REQUEST['customWindows'] == 'delete')
			zc_delete_custom_windows();
		// creating/editing a custom window?
		elseif (($_REQUEST['customWindows'] == 'add') || ($_REQUEST['customWindows'] == 'edit'))
			zc_add_edit_custom_window();
				
		if (empty($context['zc']['errors']))
			zc_redirect_exit(zcRequestVarsToString());
	}

	// reordering custom windows?
	if (isset($_POST['changeWinOrder']))
		zc_reorder_custom_windows();
	
	// load extra links and the checkbox...
	if (!empty($context['zc']['custom_windows']))
		foreach ($context['zc']['custom_windows'] as $id => $window)
			if (!empty($id))
				$context['zc']['custom_windows'][$id] = array(
					'title' => $zcFunc['un_htmlspecialchars']($window['title']),
					'content' => empty($window['content_type']) ? $zcFunc['un_htmlspecialchars']($window['content']) : $window['content'],
					'content_type' => $window['content_type'],
					'edit_link' => '<a href="' . $scripturl . zcRequestVarsToString(null, '?') .';edit='. $id .'#add" title="'. $txt['b17'] .'" rel="nofollow"><span class="edit_icon">&nbsp;</span></a>',
					'enable_disable_link' => '<a href="' . $scripturl . zcRequestVarsToString('sa', '?') . ';sa=edcw;id=' . $id .';enable_disable='. (!empty($window['enabled']) ? 0 : 1) . ';sesc=' . $context['session_id'] .'" title="' . sprintf($txt['b254'], (!empty($window['enabled']) ? $txt['b114'] : $txt['b113'])) . '">' . (!empty($window['enabled']) ? '<img src="'. $context['zc']['default_images_url'] .'/icons/chk_on.png" alt="' . $txt['b86'] . '" />' : '<img src="' . $context['zc']['default_images_url'] . '/icons/chk_off.png" alt="' . $txt['b11'] . '" />') . '</a>',
					'preview_link' => '<a href="' . $scripturl . zcRequestVarsToString('sa', '?') . ';sa=pcw;window='. $id . '" title="' . $txt['b159'] . '" onclick="return reqWin(this.href);" rel="nofollow"><span class="preview_icon">&nbsp;</span></a>',
					'checkbox' => '<input type="checkbox" name="items[]" value="' . $id . '" />',
				);
	
	if (!empty($_REQUEST['edit']) && !empty($context['zc']['custom_windows'][$_REQUEST['edit']]))
		$context['zc']['current_info'] = $context['zc']['custom_windows'][$_REQUEST['edit']];
		
	$context['zc']['form_info']['_info_']['title'] = (!empty($context['zc']['current_info']) ? $txt['b17'] : $txt['b18']) . ' ' . $txt['b506'];
		
	$content_type = array($txt['b674'], $txt['b675'], $txt['b676']);
		
	if (!empty($context['zc']['custom_windows']))
		foreach ($context['zc']['custom_windows'] as $id => $window)
			if (!empty($id))
				$context['zc']['custom_windows'][$id]['content_type'] = $content_type[$window['content_type']];
}

function zc_bcp_communityPage($memID)
{
	global $context, $scripturl, $txt, $blog, $zcFunc, $zc;
	
	// just make sure they have access...
	if (!zc_check_permissions('access_blog_index_tab'))
		zc_fatal_error('zc_error_40');
		
	$possible_ssa = array('settings', 'windows');
	$_REQUEST['ssa'] = !isset($_REQUEST['ssa']) || !in_array($_REQUEST['ssa'], $possible_ssa) ? 'settings' : $_REQUEST['ssa'];
	$context['zc']['ssa'] = $_REQUEST['ssa'];
	
	require_once($zc['sources_dir'] . '/Subs-CP.php');
	
	if ($_REQUEST['ssa'] == 'settings')
	{
		$context['zc']['secondary_bcp_menu']['communityPage']['sub_menu']['settings']['is_active'] = true;
		zc_prepare_blog_index_settings_array();
		
		if (isset($_POST['save_communityPage']))
		{
			checkSession('get');
			list($processed, $context['zc']['errors']) = zcProcessForm($context['zc']['bi_settings']);
			if (empty($context['zc']['errors']))
			{
				zcUpdateGlobalSettings($processed);
				$_SESSION['zc_success_msg'] = 'zc_success_2';
				zc_redirect_exit(zcRequestVarsToString());
			}
		}
	}
	elseif ($_REQUEST['ssa'] == 'windows')
	{
		$context['zc']['bi_settings'] = zc_prepare_community_page_sw_array();
		
		if (isset($_POST['save_communityPage']))
		{
			checkSession('get');
			list($processed, $context['zc']['errors']) = zcProcessForm($context['zc']['bi_settings']);
			if (empty($context['zc']['errors']))
			{
				zcUpdateGlobalSettings($processed);
				$_SESSION['zc_success_msg'] = 'zc_success_2';
				zc_redirect_exit(zcRequestVarsToString());
			}
		}
	
		zc_prepare_side_window_arrays($memID);
		zc_prepare_custom_window_attributes();
		$context['zc']['form_info'] = $context['zc']['attributes']['customWindows'];
		$context['zc']['current_info'] = array();
		$context['zc']['list1']['title'] = $txt['b507'];
		$context['zc']['list1']['submit_button_txt'] = $txt['b3006'];
		$context['zc']['list1']['confirm_submit_txt'] = sprintf($txt['b71'], $txt['b74']);
		$context['zc']['list1']['form_url'] = $scripturl . zcRequestVarsToString(null, '?') . ';customWindows=delete;sesc='. $context['session_id'];
		$context['zc']['list1']['list_empty_txt'] = $txt['b477'];
		
		$context['zc']['list1']['table_headers'] = array(
			'name' => $txt['b180'],
			'title' => $txt['b3026'],
			'content' => $txt['b508'],
			'content_type' => $txt['b673'],
			'checkbox' => '<input type="checkbox" onclick="invertAll(this, this.form, \'items[]\');" class="check" />',
		);
		
		$context['zc']['list1']['title'] = $txt['b507'];
		
		// do we want to display a move window form?
		$context['zc']['display_move_window_form'] = isset($_REQUEST['moveWindow']) ? $_REQUEST['moveWindow'] : '';
		
		// doing something with custom windows?
		if (isset($_REQUEST['customWindows']))
		{
			checkSession('get');
		
			// deleting?
			if ($_REQUEST['customWindows'] == 'delete')
				zc_delete_custom_windows();
			// creating/editing a custom window?
			elseif (($_REQUEST['customWindows'] == 'add') || ($_REQUEST['customWindows'] == 'edit'))
				zc_add_edit_custom_window();
					
			if (empty($context['zc']['errors']))
				zc_redirect_exit(zcRequestVarsToString());
		}
	
		// reordering custom windows?
		if (isset($_POST['changeWinOrder']))
			zc_reorder_custom_windows();
		
		// load delete and edit links for custom windows
		if (!empty($context['zc']['custom_windows']))
			foreach ($context['zc']['custom_windows'] as $id => $window)
				if (!empty($id))
					$context['zc']['custom_windows'][$id] = array(
						'title' => $zcFunc['un_htmlspecialchars']($window['title']),
						'content' => empty($window['content_type']) ? $zcFunc['un_htmlspecialchars']($window['content']) : $window['content'],
						'content_type' => $window['content_type'],
						'edit_link' => '<a href="' . $scripturl . zcRequestVarsToString(null, '?') . ';edit=' . $id . '#add" title="' . $txt['b17'] . '" rel="nofollow"><span class="edit_icon">&nbsp;</span></a>',
						'enable_disable_link' => '<a href="' . $scripturl . zcRequestVarsToString('sa', '?') .';sa=edcw;id=' . $id . ';enable_disable=' . (!empty($window['enabled']) ? 0 : 1) . ';sesc=' . $context['session_id'] . '" title="' . sprintf($txt['b254'], (!empty($window['enabled']) ? $txt['b114'] : $txt['b113'])) . '">' . (!empty($window['enabled']) ? '<img src="' . $context['zc']['default_images_url'] . '/icons/chk_on.png" alt="' . $txt['b86']. '" />' : '<img src="' . $context['zc']['default_images_url'] . '/icons/chk_off.png" alt="' . $txt['b11'] .'" />') . '</a>',
						'preview_link' => '<a href="' . $scripturl . zcRequestVarsToString('sa', '?') . ';sa=pcw;window=' . $id . '" title="' . $txt['b159'] . '" onclick="return reqWin(this.href);" rel="nofollow"><span class="preview_icon">&nbsp;</span></a>',
						'checkbox' => '<input type="checkbox" name="items[]" value="' . $id . '" />',
					);
				
		if (!empty($_REQUEST['edit']) && !empty($context['zc']['custom_windows'][$_REQUEST['edit']]))
			$context['zc']['current_info'] = $context['zc']['custom_windows'][$_REQUEST['edit']];
			
		$content_type = array($txt['b674'], $txt['b675'], $txt['b676']);
			
		if (!empty($context['zc']['custom_windows']))
			foreach ($context['zc']['custom_windows'] as $id => $window)
				if (!empty($id))
					$context['zc']['custom_windows'][$id]['content_type'] = $content_type[$window['content_type']];
	}
}

function zc_bcp_globalSettings($memID)
{
	global $context, $txt, $scripturl, $zcFunc, $zc;
	
	// just make sure they have access...
	if (!zc_check_permissions('access_global_settings_tab'))
		zc_fatal_error('zc_error_40');
	
	$context['zc']['zc_settings']['default_blog_avatar'] += array(
		'dir' => $zc['settings']['attachments_dir'],
		'max_dir_size' => $zc['settings']['max_size_attachments_dir'],
		'max_width_image' => $zc['settings']['blog_index_max_avatar_width'],
		'max_height_image' => $zc['settings']['blog_index_max_avatar_height']
	);
	
	if ($context['zc']['current_subMenu'] == 'globalSettings' && isset($_REQUEST['delete_blog_avatar']))
	{
		checkSession('get');
	
		if (!empty($zc['settings']['default_blog_avatar']) && file_exists($context['zc']['zc_settings']['default_blog_avatar']['dir'] . '/' . $zc['settings']['default_blog_avatar']))
			unlink($context['zc']['zc_settings']['default_blog_avatar']['dir'] . '/' . $zc['settings']['default_blog_avatar']);
			
		// update the global settings...
		zcUpdateGlobalSettings(array('default_blog_avatar' => ''));
		$_SESSION['zc_success_msg'] = 'zc_success_9';
		zc_redirect_exit(zcRequestVarsToString('delete_blog_avatar'));
	}
	
	// let's load language files for plugins
	if (!empty($context['zc']['plugins']))
	{
		global $zc;
		$plugins_lang_dir = $zc['plugins_dir'] . '/Languages';
		foreach ($context['zc']['plugins'] as $plugin)
		{
			// no language file specified
			if (empty($plugin['lngfile']))
				continue;
		
			// load it up
			zcLoadLanguage($plugin['lngfile'], '', false, $plugins_lang_dir);
		}
	}
			
	if (!empty($context['zc']['themes']))
		foreach ($context['zc']['themes'] as $theme_id => $array)
			$context['zc']['zc_settings']['default_blog_theme']['options'][$theme_id] = $array['name'];
		
	// get all of the blogs in the blogging community
	$request = $zcFunc['db_query']("
		SELECT name, blog_id
		FROM {db_prefix}blogs
		ORDER BY name ASC", __FILE__, __LINE__);
	
	$context['zc_blogs'] = array();
	if ($zcFunc['db_num_rows']($request) > 0)
		while ($row = $zcFunc['db_fetch_assoc']($request))
			$context['zc_blogs'][$row['blog_id']] = $zcFunc['un_htmlspecialchars']($row['name']);
	
	$zcFunc['db_free_result']($request);
		
	if (!isset($context['zc']['zc_settings']))
		zc_prepare_global_settings_array();
		
	if (!empty($context['zc']['admin_plugin_settings']))
		$context['zc']['zc_settings'] = array_merge($context['zc']['zc_settings'], $context['zc']['admin_plugin_settings']);
		
	// we'll need this for the ( More Options )
	$context['zc']['raw_javascript'][] = '
					function addField(key, inputType)
					{
						setOuterHTML(document.getElementById("more_" + key), \'<br /><input type="\' + inputType + \'" name="\' + key + \'[]" value="" /><span id="more_\' + key + \'"></span>\');
					}';
	
	// populate the options array of defaultAllowedGroups
	if (!empty($context['zc']['membergroups']))
		foreach ($context['zc']['membergroups'] as $id => $name)
			$context['zc']['zc_settings']['defaultAllowedGroups']['options'][$id] = $name;
	
	// populate the options array of blogBoard
	if (!empty($context['zc_blogs']))
		foreach ($context['zc_blogs'] as $id => $name)
			$context['zc']['zc_settings']['blogBoard']['options'][$id] = $name;
	
	// we're saving admin-only blog settings... 
	if (isset($_POST['save_globalSettings']))
	{
		checkSession('get');
			
		list($processed, $context['zc']['errors']) = zcProcessForm($context['zc']['zc_settings']);
		
		// continue on to saving the settings if no errors occurred...
		if (empty($context['zc']['errors']))
		{
			// if we're using a valid forum version, then zc_mode is stored in the forum's settings table
			if (in_array($zc['with_software']['version'], $zc['all_software_versions']))
			{
				$zc_mode = $processed['zc_mode'];
				unset($processed['zc_mode']);
				zc_update_forum_settings(array('zc_mode' => $zc_mode));
			}
			
			zcUpdateGlobalSettings($processed);
			$_SESSION['zc_success_msg'] = 'zc_success_2';
			zc_redirect_exit(zcRequestVarsToString());
		}
	}
		
	// get all of this forum's boards
	$request = $zcFunc['db_query']("
		SELECT {tbl:boards::column:name} AS name, {tbl:boards::column:id_board} AS id_board
		FROM {db_prefix}{table:boards}
		ORDER BY {tbl:boards::column:name} ASC", __FILE__, __LINE__);
	
	$context['zc']['forum_boards'] = array();
	while ($row = $zcFunc['db_fetch_assoc']($request))
		$context['zc']['forum_boards'][$row['id_board']] = strip_tags($zcFunc['un_htmlspecialchars']($row['name']));
	$zcFunc['db_free_result']($request);
	
	// populate $context['zc']['zc_settings']['default_blog_avatar']['show_above_field']
	if (!empty($zc['settings']['default_blog_avatar']))
	{
		list($avatar_width, $avatar_height) = zcResizeImage($zc['settings']['attachments_url'] . '/' . $zc['settings']['default_blog_avatar'], ($zc['settings']['blog_index_max_avatar_width'] > 65 ? 65 : $zc['settings']['blog_index_max_avatar_width']), ($zc['settings']['blog_index_max_avatar_height'] > 50 ? 50 : $zc['settings']['blog_index_max_avatar_height']));
		$avatar_width = ' width="' . $avatar_width . '"';
		$avatar_height = ' height="' . $avatar_height . '"';
		$context['zc']['zc_settings']['default_blog_avatar']['show_above_field'] = '<img src="' . $zc['settings']['attachments_url'] . '/' . $zcFunc['htmlspecialchars']($zc['settings']['default_blog_avatar']) . '"' . $avatar_width . $avatar_height . ' alt="" /><a href="' . $scripturl . '?delete_blog_avatar' . zcRequestVarsToString(null, ';') . ';sesc=' . $context['session_id'] . '" title="' . $txt['b127'] . '" style="margin-left:3px;"><img src="' . $context['zc']['default_images_url'] . '/icons/disable_icon.gif" alt="' . $txt['b127'] . '" /></a>';
	}
	else
	{
		list($avatar_width, $avatar_height) = zcResizeImage($context['zc']['default_images_url'] . '/defaultAvatar.png', ($zc['settings']['blog_index_max_avatar_width'] > 65 ? 65 : $zc['settings']['blog_index_max_avatar_width']), ($zc['settings']['blog_index_max_avatar_height'] > 50 ? 50 : $zc['settings']['blog_index_max_avatar_height']));
		$avatar_width = ' width="' . $avatar_width . '"';
		$avatar_height = ' height="' . $avatar_height . '"';
		$context['zc']['zc_settings']['default_blog_avatar']['show_above_field'] = '<img src="' . $context['zc']['default_images_url'] . '/defaultAvatar.png"' . $avatar_width . $avatar_height . ' alt="" />';
	}
}

function zc_bcp_accessRestrictions($memID)
{
	global $context, $txt, $scripturl, $blog, $zc, $zcFunc;
	
	// are they allowed to use this tab?
	if (!$context['can_restrict_access_blogs'])
		zc_redirect_exit(zcRequestVarsToString('sa'));
	
	// array of info for the template and form processing...
	$context['zc']['access_restrictions'] = array(
		'_info_' => array(
			'title' => $txt['b310'],
			'hidden_form_values' => array('save_' . (!empty($_REQUEST['sa']) ? $_REQUEST['sa'] : '') => 1),
			'form_url' => $scripturl . zcRequestVarsToString(null, '?') .';sesc='. $context['session_id'],
		),
		'allowedGroups' => array(
			'type' => 'text',
			'value' => isset($zc['settings']['defaultAllowedGroups']) ? $zc['settings']['defaultAllowedGroups'] : '-1,0,2',
			'custom' => 'multi_check',
			'always_include' => array('2'),
			'options' => array(),
			'needs_explode' => true,
		),
	);
	
	// populate the options array
	if (!empty($context['zc']['membergroups']))
		foreach ($context['zc']['membergroups'] as $id => $name)
			$context['zc']['access_restrictions']['allowedGroups']['options'][$id] = $name;
	
	// current values....
	$context['zc']['current_info'] = array(
		'allowedGroups' => $context['zc']['blog_settings']['allowedGroups'],
	);
	
	// save access restriction settings?
	if (isset($_POST['save_accessRestrictions']))
	{
		list($processed, $context['zc']['errors']) = zcProcessForm($context['zc']['access_restrictions']);
		
		if (empty($context['zc']['errors']))
		{
			// update blog...
			$zcFunc['db_update'](
				'{db_prefix}blogs',
				array('blog_id' => 'int', 'member_groups' => 'string'),
				array('member_groups' => $processed['allowedGroups']),
				array('blog_id' => $blog));
		
			// redirect after successfully saving...
			zc_redirect_exit(zcRequestVarsToString());
		}
	}
	
	$context['zc']['form_info'] = array(
		'_info_' => array(
			'form_url' => $scripturl . zcRequestVarsToString(null, '?') . ';usersAllowedAccess=add;sesc=' . $context['session_id'],
			'title' => sprintf($txt['b311'], $txt['b309']),
		),
		'user' => array(
			'type' => 'text',
			'value' => '',
			'label' => $txt['b312'],
		),
	);
	
	if (!empty($_REQUEST['usersAllowedAccess']))
	{
		// delete user(s) from the list?
		if ($_REQUEST['usersAllowedAccess'] == 'delete')
		{
			checkSession('get');
			$users = array();
			if (!empty($_POST['items']))
				foreach ($_POST['items'] as $id)
					$users[] = (int) $id;
					
			$users_allowed_access = array_diff($context['zc']['blog_settings']['users_allowed_access'], $users);
			$users_allowed_access = !empty($users_allowed_access) ? implode(',', $users_allowed_access) : '';
			
			// update the blog_settings table...
			zcUpdateBlogSettings(array('users_allowed_access' => $users_allowed_access), $blog);
				
			// redirect after...
			zc_redirect_exit(zcRequestVarsToString());
		}
		// let's add member(s) to users_allowed_access
		elseif ($_REQUEST['usersAllowedAccess'] == 'add')
		{
			checkSession('get');
			
			list($processed, $context['zc']['errors']) = zcProcessForm($context['zc']['form_info']);
				
			if (empty($context['zc']['errors']))
			{
				$request = $zcFunc['db_query']("
					SELECT {tbl:members::column:id_member} AS id_member
					FROM {db_prefix}{table:members}
					WHERE {tbl:members::column:member_name} = {string:user_name}
						OR {tbl:members::column:real_name} = {string:user_name}
					LIMIT 1", __FILE__, __LINE__,
					array(
						'user_name' => $processed['user'],
					)
				);
					
				if ($zcFunc['db_num_rows']($request) > 0)
					$row = $zcFunc['db_fetch_assoc']($request);
					
				if (empty($row['id_member']))
					$context['zc']['errors'][] = 'zc_error_62';
					
				if (!empty($row['id_member']))
					if (in_array($row['id_member'], $context['zc']['blog_settings']['users_allowed_access']))
						$context['zc']['errors'][] = 'zc_error_63';
					
				// still no errors?  That's super...
				if (empty($context['zc']['errors']))
				{
					$context['zc']['blog_settings']['users_allowed_access'][] = $row['id_member'];
					
					// update the blog_settings table
					zcUpdateBlogSettings(array('users_allowed_access' => implode(',', $context['zc']['blog_settings']['users_allowed_access'])), $blog);
						
					zc_redirect_exit(zcRequestVarsToString());
				}
			} 
		}
	}
	$context['zc']['list1']['list_empty_txt'] = array('b706', 'b309');
	
	// load info about users on the allowed access list....
	$context['zc']['users_allowed_access'] = array();
	if (!empty($context['zc']['blog_settings']['users_allowed_access']))
	{
		$context['zc']['list1']['submit_button_txt'] = $txt['b3053'];
		$context['zc']['list1']['confirm_submit_txt'] = sprintf($txt['b313'], $txt['b309']);
		$context['zc']['list1']['form_url'] = $scripturl . zcRequestVarsToString(null, '?') . ';usersAllowedAccess=delete;sesc='. $context['session_id'];
	
		$maxindex = isset($_REQUEST['all']) && !empty($zc['settings']['allow_show_all_link']) ? 99999 : 15;
		$start = isset($_REQUEST['listStart']) ? (int) $_REQUEST['listStart'] : 0;
		$num_users = count($context['zc']['blog_settings']['users_allowed_access']);
		
		$context['zc']['list1']['show_page_index'] = !empty($num_users) && $num_users > $maxindex;
		
		if ($context['zc']['list1']['show_page_index'])
		{
			$context['zc']['list1']['page_index'] = zcConstructPageIndex($scripturl . '?listStart=%d' . zcRequestVarsToString('all,listStart', ';'), $start, $num_users, $maxindex, true);
			
			if (!empty($zc['settings']['allow_show_all_link']))
				$context['zc']['list1']['show_all_link'] = '<a href="' . $scripturl . ';all' . zcRequestVarsToString('all,listStart', ';') . '">' . $txt['b81'] . '</a>';
		}
				
		// Default sort methods.
		$sort_methods = array(
			'name' => 'real_name',
		);
	
		if (!isset($_REQUEST['sort']) || !isset($sort_methods[$_REQUEST['sort']]))
		{
			$sort_by = 'name';
			$sort = 'real_name';
			$ascending = isset($_REQUEST['asc']);
		}
		else
		{
			$sort_by = $_REQUEST['sort'];
			$sort = $sort_methods[$_REQUEST['sort']];
			$ascending = isset($_REQUEST['asc']);
		}
			
		// make array of table header info
		$tableHeaders = array(
			'url_requests' => zcRequestVarsToString('sort,asc,desc', '?'),
			'headers' => array(
				'name' => array('label' => $txt['b180']),
			),
			'sort_direction' => $ascending ? 'up' : 'down',
			'sort_by' => $sort_by,
		);
		
		// create the table headers
		$context['zc']['list1']['table_headers'] = zcCreateTableHeaders($tableHeaders);
		$context['zc']['list1']['table_headers']['checkbox'] = '<input type="checkbox" onclick="invertAll(this, this.form, \'items[]\');" class="check" />';
	
		$request = $zcFunc['db_query']("
			SELECT {tbl:members::column:real_name} AS real_name, {tbl:members::column:id_member} AS id_member
			FROM {db_prefix}{table:members}
			WHERE {tbl:members::column:id_member} IN ({array_int:users_allowed_access}) 
			ORDER BY {raw:sort}" . ($ascending ? '' : ' DESC') . "
			LIMIT {int:start}, {int:maxindex}", __FILE__, __LINE__,
			array(
				'sort' => $sort,
				'start' => $start,
				'maxindex' => $maxindex,
				'users_allowed_access' => $context['zc']['blog_settings']['users_allowed_access']
			)
		);
			
		while ($row = $zcFunc['db_fetch_assoc']($request))
		{
			$row['real_name'] = $row['real_name'];
			$context['zc']['users_allowed_access'][$row['id_member']] = array(
				'name' => !empty($row['id_member']) ? sprintf($context['zc']['link_templates']['user_profile'], $row['id_member'], $row['real_name'], ' title="' . $txt['b41'] . '"') : $row['real_name'],
				'checkbox' => '<input type="checkbox" name="items[]" value="' . $row['id_member'] . '" />',
			);
		}
		$zcFunc['db_free_result']($request);
	}
}

function zc_bcp_postingRestrictions($memID)
{
	global $context, $scripturl, $txt;
	global $blog, $zcFunc, $zc;
	
	// are they allowed to use this tab?
	if (!$context['can_set_posting_restrictions'])
		zc_redirect_exit(zcRequestVarsToString('sa'));
	
	// array of info for the template and form processing...
	$context['zc']['posting_restrictions'] = array(
		'_info_' => array(
			'title' => $txt['b512'],
			'hidden_form_values' => array('save_' . (!empty($_REQUEST['sa']) ? $_REQUEST['sa'] : '') => 1),
			'form_url' => $scripturl . zcRequestVarsToString(null, '?') .';sesc='. $context['session_id'],
		),
		'groupsAllowedToBlog' => array(
			'type' => 'text',
			'value' => '',
			'custom' => 'multi_check',
			'options' => array(),
			'must_return_true' => $context['can_set_posting_restrictions'],
		),
	);
	
	// populate the options array
	if (!empty($context['zc']['membergroups']))
		foreach ($context['zc']['membergroups'] as $id => $name)
			$context['zc']['posting_restrictions']['groupsAllowedToBlog']['options'][$id] = $name;
	
	// current values....
	$context['zc']['current_info'] = array(
		'groupsAllowedToBlog' => $context['zc']['blog_settings']['groupsAllowedToBlog'],
	);
			
	if (isset($_POST['save_postingRestrictions']))
	{
		checkSession('get');
		$groupsAllowedToBlog = array();
		// add each checked group... making sure that they are actual choices
		if (!empty($_POST['groupsAllowedToBlog']) && is_array($_POST['groupsAllowedToBlog']))
			foreach ($_POST['groupsAllowedToBlog'] as $checked_group)
				if (!empty($context['zc']['posting_restrictions']['groupsAllowedToBlog']['options'][$checked_group]))
					$groupsAllowedToBlog[] = $checked_group;
					
		zcUpdateBlogSettings(array('groupsAllowedToBlog' => implode(',', $groupsAllowedToBlog)), $blog);
		
		zc_redirect_exit(zcRequestVarsToString());
	}
	
	$context['zc']['form_info'] = array(
		'_info_' => array(
			'form_url' => $scripturl . zcRequestVarsToString(null, '?') . ';usersAllowedToBlog=add;sesc=' . $context['session_id'],
			'title' => sprintf($txt['b311'], $txt['b513']),
		),
		'blogger' => array(
			'type' => 'text',
			'value' => '',
			'label' => $txt['b312'],
		),
	);
	
	if (!empty($_REQUEST['usersAllowedToBlog']))
	{
		checkSession('get');
			
		// let's add member(s) to usersAllowedToBlog
		if ($_REQUEST['usersAllowedToBlog'] == 'add')
		{
			list($processed, $context['zc']['errors']) = zcProcessForm($context['zc']['form_info']);
				
			if (empty($context['zc']['errors']))
			{
				$request = $zcFunc['db_query']("
					SELECT {tbl:members::column:id_member} AS id_member
					FROM {db_prefix}{table:members}
					WHERE {tbl:members::column:member_name} = {string:blogger_name}
						OR {tbl:members::column:real_name} = {string:blogger_name}
					LIMIT 1", __FILE__, __LINE__,
					array(
						'blogger_name' => $processed['blogger']
					)
				);
					
				if ($zcFunc['db_num_rows']($request) > 0)
					$row = $zcFunc['db_fetch_assoc']($request);
					
				if (empty($row['id_member']))
					$context['zc']['errors'][] = 'zc_error_62';
					
				if (!empty($row['id_member']))
					if (in_array($row['id_member'], $context['zc']['blog_settings']['usersAllowedToBlog']))
						$context['zc']['errors'][] = 'zc_error_63';
					
				// still no errors?  That's super...
				if (empty($context['zc']['errors']))
				{
					$context['zc']['blog_settings']['usersAllowedToBlog'][] = $row['id_member'];
					
					// update the blog_settings table
					zcUpdateBlogSettings(array('usersAllowedToBlog' => implode(',', $context['zc']['blog_settings']['usersAllowedToBlog'])), $blog);
						
					zc_redirect_exit(zcRequestVarsToString());
				}
			} 
		}
		elseif ($_REQUEST['usersAllowedToBlog'] == 'delete')
		{
			// let's remove a member or members from the usersAllowedToBlog list
			if (!empty($_POST['items']))
			{
				checkSession('get');
				
				$items = array();
				foreach ($_POST['items'] as $item)
					$items[] = (int) $item;
					
				$context['zc']['blog_settings']['usersAllowedToBlog'] = array_diff($context['zc']['blog_settings']['usersAllowedToBlog'], $items);
							
				// update usersAllowedToBlog in blog_settings table
				zcUpdateBlogSettings(array('usersAllowedToBlog' => implode(',', $context['zc']['blog_settings']['usersAllowedToBlog'])), $blog);
			}
			zc_redirect_exit(zcRequestVarsToString());
		}
	}
	$context['zc']['list1']['list_empty_txt'] = array('b706', 'b513');
	
	// load info about all the users in usersAllowedToBlog list
	$context['zc']['users_allowed_to_blog'] = array();
	if (!empty($context['zc']['blog_settings']['usersAllowedToBlog']))
	{
		$context['zc']['list1']['submit_button_txt'] = $txt['b3053'];
		$context['zc']['list1']['confirm_submit_txt'] = sprintf($txt['b313'], $txt['b513']);
		$context['zc']['list1']['form_url'] = $scripturl . zcRequestVarsToString(null, '?') . ';usersAllowedToBlog=delete;sesc='. $context['session_id'];
	
		$maxindex = isset($_REQUEST['all']) && !empty($zc['settings']['allow_show_all_link']) ? 99999 : 15;
		$start = isset($_REQUEST['listStart']) ? (int) $_REQUEST['listStart'] : 0;
		$num_users = count($context['zc']['blog_settings']['usersAllowedToBlog']);
		
		$context['zc']['list1']['show_page_index'] = !empty($num_users) && $num_users > $maxindex;
		
		if ($context['zc']['list1']['show_page_index'])
		{
			$context['zc']['list1']['page_index'] = zcConstructPageIndex($scripturl . '?listStart=%d' . zcRequestVarsToString('all,listStart', ';'), $start, $num_users, $maxindex, true);
			
			if (!empty($zc['settings']['allow_show_all_link']))
				$context['zc']['list1']['show_all_link'] = '<a href="' . $scripturl . zcRequestVarsToString('all,listStart', '?') .';all">' . $txt['b81'] . '</a>';
		}
				
		// Default sort methods.
		$sort_methods = array(
			'name' => 'real_name',
		);
	
		if (!isset($_REQUEST['sort']) || !isset($sort_methods[$_REQUEST['sort']]))
		{
			$sort_by = 'name';
			$sort = 'real_name';
			$ascending = isset($_REQUEST['asc']);
		}
		else
		{
			$sort_by = $_REQUEST['sort'];
			$sort = $sort_methods[$_REQUEST['sort']];
			$ascending = isset($_REQUEST['asc']);
		}
			
		// make array of table header info
		$tableHeaders = array(
			'url_requests' => zcRequestVarsToString('sort,asc,desc', '?'),
			'headers' => array(
				'name' => array('label' => $txt['b180']),
			),
			'sort_direction' => $ascending ? 'up' : 'down',
			'sort_by' => $sort_by,
		);
		
		// create the table headers
		$context['zc']['list1']['table_headers'] = zcCreateTableHeaders($tableHeaders);
		$context['zc']['list1']['table_headers']['checkbox'] = '<input type="checkbox" onclick="invertAll(this, this.form, \'items[]\');" class="check" />';
	
		$request = $zcFunc['db_query']("
			SELECT {tbl:members::column:real_name} AS real_name, {tbl:members::column:id_member} AS id_member
			FROM {db_prefix}{table:members}
			WHERE {tbl:members::column:id_member} IN ({array_int:users_allowed_to_blog}) 
			ORDER BY {raw:sort}" . ($ascending ? '' : ' DESC') . "
			LIMIT {int:start}, {int:maxindex}", __FILE__, __LINE__,
			array(
				'sort' => $sort,
				'start' => $start,
				'maxindex' => $maxindex,
				'users_allowed_to_blog' => $context['zc']['blog_settings']['usersAllowedToBlog']
			)
		);
			
		while ($row = $zcFunc['db_fetch_assoc']($request))
		{
			$row['real_name'] = $row['real_name'];
			$context['zc']['users_allowed_to_blog'][$row['id_member']] = array(
				'name' => !empty($row['id_member']) ? sprintf($context['zc']['link_templates']['user_profile'], $row['id_member'], $row['real_name'], ' title="' . $txt['b41'] . '"') : $row['real_name'],
				'checkbox' => '<input type="checkbox" name="items[]" value="' . $row['id_member'] . '" />',
			);
		}
		$zcFunc['db_free_result']($request);
	}
	$gatb = $context['zc']['blog_settings']['groupsAllowedToBlog'];
	// it has to be an array...
	if (!empty($gatb))
	{
		if (!is_array($gatb))
			$gatb = explode(',', $gatb);
	}
	else
		$gatb = array();
	$context['zc']['blog_settings']['groupsAllowedToBlog'] = $gatb;
}

function zc_bcp_tags($memID)
{
	global $context, $txt, $scripturl;
	global $blog, $zcFunc, $zc;
	
	$context['zc']['list1']['form_url'] = $scripturl . zcRequestVarsToString(null, '?') . ';tags=delete;sesc='. $context['session_id'];
	$context['zc']['list1']['list_empty_txt'] = sprintf($txt['b470'], $txt['b77']);
	$context['zc']['list1']['confirm_submit_txt'] = sprintf($txt['b71'], $txt['b77']);
	
	// count number of tags
	$request = $zcFunc['db_query']("
		SELECT COUNT(tag)
		FROM {db_prefix}tags
		WHERE blog_id = {int:blog_id}", __FILE__, __LINE__,
		array(
			'blog_id' => $blog
		)
	);
	list($numTags) = $zcFunc['db_fetch_row']($request);
	$zcFunc['db_free_result']($request);
		
	$maxindex = isset($_REQUEST['all']) && !empty($zc['settings']['allow_show_all_link']) ? 99999 : 15;
	$start = isset($_REQUEST['listStart']) ? (int) $_REQUEST['listStart'] : 0;
	
	$context['zc']['list1']['show_page_index'] = !empty($numTags) && $numTags > $maxindex;
	
	if ($context['zc']['list1']['show_page_index'])
		{
			$context['zc']['list1']['page_index'] = zcConstructPageIndex($scripturl . '?listStart=%d' . zcRequestVarsToString('all,listStart', ';'), $start, $numTags, $maxindex, true);
			
			if (!empty($zc['settings']['allow_show_all_link']))
				$context['zc']['list1']['show_all_link'] = '<a href="' . $scripturl . zcRequestVarsToString('all,listStart', '?') .';all">' . $txt['b81'] . '</a>';
		}
			
	// Default sort methods.
	$sort_methods = array(
		'tag' => 'tag',
		'num_articles' => 'num_articles',
	);

	if (!isset($_REQUEST['sort']) || !isset($sort_methods[$_REQUEST['sort']]))
	{
		$sort_by = 'tag';
		$sort = 'tag';
		$ascending = isset($_REQUEST['asc']);
	}
	else
	{
		$sort_by = $_REQUEST['sort'];
		$sort = $sort_methods[$_REQUEST['sort']];
		$ascending = isset($_REQUEST['asc']);
	}
		
	// make array of table header info
	$tableHeaders = array(
		'url_requests' => zcRequestVarsToString('sort,asc,desc', '?'),
		'headers' => array(
			'tag' => array('label' => $txt['b26a']),
			'num_articles' => array('label' => $txt['b250']),
		),
		'sort_direction' => $ascending ? 'up' : 'down',
		'sort_by' => $sort_by,
	);
	
	// create the table headers
	$context['zc']['list1']['table_headers'] = zcCreateTableHeaders($tableHeaders);
	$context['zc']['list1']['table_headers']['checkbox'] = '<input type="checkbox" onclick="invertAll(this, this.form, \'items[]\');" class="check" />';

	// let's get the tags for this page
	$request = $zcFunc['db_query']("
		SELECT tag, num_articles
		FROM {db_prefix}tags
		WHERE blog_id = {int:blog_id}
		ORDER BY {raw:sort}" . ($ascending ? '' : ' DESC') . "
		LIMIT {int:start}, {int:maxindex}", __FILE__, __LINE__,
		array(
			'sort' => $sort,
			'start' => $start,
			'maxindex' => $maxindex,
			'blog_id' => $blog
		)
	);
		
	$context['zc']['tags'] = array();
	while ($row = $zcFunc['db_fetch_assoc']($request))
	{
		$row['tag'] = $row['tag'];
		$context['zc']['tags'][$row['tag']] = array(
			'tag' => $row['tag'],
			'num_articles' => $txt['b78'] . ' <b>' . $row['num_articles'] . '</b> ' . ($row['num_articles'] == 1 ? $txt['b66'] : $txt['b66a']),
			'checkbox' => '<input type="checkbox" name="items[]" value="' . $row['tag'] . '" />',
		);
	}
	
	$zcFunc['db_free_result']($request);
	
	if (empty($context['zc']['tags']))
		$context['zc']['list1']['list_help_link'] = '<a href="' . $scripturl . '?zc=help;txt=zc_help_7" onclick="return reqWin(this.href);" class="help" rel="nofollow"><img src="' . $context['zc']['default_images_url'] . '/icons/question_icon.png" alt="(?)" title="' . $txt['b79'] . '" /></a>';
	
	// doing something with blog_tags?
	if (!empty($_REQUEST['tags']))
	{
		checkSession('get');
		// deleting?
		if ($_REQUEST['tags'] == 'delete')
		{
			$tags = array();
			if (!empty($_POST['items']))
				foreach ($_POST['items'] as $tag)
					$tags[] = addslashes($zcFunc['htmlspecialchars']((string)$tag, ENT_QUOTES));
			
			if (!empty($tags))
			{
				$info = array('limit' => count($tags), 'blog_id' => $blog);
				$conditions = array();
				foreach ($tags as $k => $tag)
				{
					$info[$k] = $tag;
					$conditions[] = 'tag = {string:' . $k . '}';
				
					// let's get all the articles (and their current blog_tags) that use this tag
					$request = $zcFunc['db_query']("
						SELECT article_id, blog_tags
						FROM {db_prefix}articles
						WHERE ((blog_tags LIKE {string:tag0}) OR (blog_tags LIKE {string:tag1}) OR (blog_tags LIKE {string:tag2}) OR (blog_tags = {string:tag3}))
							AND blog_tags NOT LIKE {string:tag4}
							AND blog_tags NOT LIKE {string:tag5}
							AND blog_tags NOT LIKE {string:tag6}", __FILE__, __LINE__,
							array(
								'tag0' => '%,' . $tag . ',%',
								'tag1' => $tag . ',%',
								'tag2' => '%,' . $tag,
								'tag3' => $tag,
								'tag4' => '% ' . $tag . ',%',
								'tag5' => '% ' . $tag . ' %',
								'tag6' => '%,' . $tag . ' %'
							)
						);
						
					while ($row = $zcFunc['db_fetch_assoc']($request))
					{
						$re_blog_tags = implode(',', array_diff(explode(',', $row['blog_tags']), array($tag)));
						// update the article with its revised blog_tags
						if ($re_blog_tags != $row['blog_tags'])
							$zcFunc['db_update'](
								'{db_prefix}articles',
								array('article_id' => 'int', 'blog_tags' => 'string'),
								array('blog_tags' => $re_blog_tags),
								array('article_id' => $row['article_id']));
					}
				}
				
				// delete the tags from the blog_tags table
				$zcFunc['db_query']("
					DELETE FROM {db_prefix}tags
					WHERE blog_id = {int:blog_id}
						AND (" . implode("
						OR ", $conditions) . ")
					LIMIT {int:limit}", __FILE__, __LINE__, $info);
			}
		}
		zc_redirect_exit(zcRequestVarsToString());
	}
}

function zc_bcp_preferences($memID)
{
	global $context, $txt, $zcFunc;
	
	if (!isset($context['zc']['preferences']))
		$context['zc']['preferences'] = zc_prepare_preferences_array();
		
	// make sure we have the control panel owner's blog_preferences...
	$context['zc']['cp_owner']['blog_preferences'] = $memID != $context['user']['id'] ? zcLoadUserPreferences($memID) : $context['user']['blog_preferences'];
	
	if (isset($_POST['save_preferences']))
	{
		checkSession('get');
		
		list($processed, $context['zc']['errors']) = zcProcessForm($context['zc']['preferences']);
		
		// if no errors occurred, update the database with this user's preferences
		if (empty($context['zc']['errors']))
		{
			$columns = array('member_id' => 'int');
			foreach ($processed as $k => $v)
				$columns[$k] = isset($context['zc']['preferences'][$k]['type']) ? $context['zc']['preferences'][$k]['type'] : 'string';
			
			// update the preferences table
			$zcFunc['db_update']('{db_prefix}preferences', $columns, $processed, array('member_id' => $memID));
				
			$_SESSION['zc_success_msg'] = 'zc_success_3';
				
			zc_redirect_exit(zcRequestVarsToString());
		}
	}
}

function zc_bcp_notifications($memID)
{
	global $context, $txt, $scripturl;
	global $zcFunc, $zc;
	
	if (!$context['can_mark_notify'])
		zc_fatal_error('zc_error_40');
	
	zc_bcp_preferences($memID);
	
	require_once($zc['sources_dir'] . '/Subs-CP.php');
	
	// prepares $context['zc']['n_preferences']
	zc_prepare_n_preferences_array();
	
	if (isset($_POST['save_notifications']))
	{
		checkSession('get');
		
		list($processed, $context['zc']['errors']) = zcProcessForm($context['zc']['n_preferences']);
		
		// update this user's notification preferences...
		if (empty($context['zc']['errors']))
		{
			$columns = array('member_id' => 'int');
			foreach ($processed as $k => $v)
				$columns[$k] = isset($context['zc']['n_preferences'][$k]['type']) ? $context['zc']['n_preferences'][$k]['type'] : 'string';
			
			// update the preferences table
			$zcFunc['db_update']('{db_prefix}preferences', $columns, $processed, array('member_id' => $memID));
			$_SESSION['zc_success_msg'] = 'zc_success_2';
			zc_redirect_exit(zcRequestVarsToString());
		}
	}
	
	// do we want to unsubscribe from some articles?
	if (!empty($_POST['unsubscribe_articles']))
	{
		// clean the post variable...
		$articles = array();
		foreach ($_POST['unsubscribe_articles'] as $id)
			if (!empty($id))
				$articles[] = (int) $id;
		
		// unsubscribe them...
		if (!empty($articles))
			$zcFunc['db_query']("
				DELETE FROM {db_prefix}log_notify
				WHERE member_id = {int:member_id}
					AND article_id IN ({array_int:articles})
				LIMIT {int:limit}", __FILE__, __LINE__,
				array(
					'limit' => count($articles),
					'articles' => $articles,
					'member_id' => $memID
				)
			);
	}
	// maybe we want to unsubscribe from some blogs?
	elseif (!empty($_POST['unsubscribe_blogs']))
	{
		// clean the post variable...
		$blogs = array();
		foreach ($_POST['unsubscribe_blogs'] as $id)
			if (!empty($id))
				$blogs[] = (int) $id;
		
		// unsubscribe them...
		if (!empty($blogs))
			$zcFunc['db_query']("
				DELETE FROM {db_prefix}log_notify
				WHERE member_id = {int:member_id}
					AND blog_id IN ({array_int:blogs})
				LIMIT {int:limit}", __FILE__, __LINE__,
				array(
					'limit' => count($blogs),
					'blogs' => $blogs,
					'member_id' => $memID
				)
			);
	}
	
	// list1 is for subscribed articles
	$context['zc']['list1']['title'] = sprintf($txt['b270'], $txt['b66a']);
	$context['zc']['list1']['list_empty_txt'] = sprintf($txt['b271'], $txt['b129']);
	$context['zc']['list1']['submit_button_txt'] = $txt['b276'];
	$context['zc']['list1']['checkbox_name'] = 'unsubscribe_articles';
	$context['zc']['list1']['confirm_submit_txt'] = sprintf($txt['b274'], $txt['b129']);
	$context['zc']['list1']['form_url'] = $scripturl . zcRequestVarsToString(null, '?') . ';sesc='. $context['session_id'];
	
	// list2 is for subscribed blogs
	$context['zc']['list2']['title'] = sprintf($txt['b270'], $txt['b1a']);
	$context['zc']['list2']['list_empty_txt'] = sprintf($txt['b271'], $txt['b1']);
	$context['zc']['list2']['submit_button_txt'] = $txt['b276'];
	$context['zc']['list2']['checkbox_name'] = 'unsubscribe_blogs';
	$context['zc']['list2']['confirm_submit_txt'] = sprintf($txt['b274'], $txt['b1']);
	$context['zc']['list2']['form_url'] = $context['zc']['list1']['form_url'];
	
	// count number of article notifications
	$request = $zcFunc['db_query']("
		SELECT COUNT(article_id)
		FROM {db_prefix}log_notify
		WHERE member_id = {int:member_id}
			AND article_id != 0", __FILE__, __LINE__,
		array(
			'member_id' => $memID
		)
	);
	list($num_article_notifications) = $zcFunc['db_fetch_row']($request);
	$zcFunc['db_free_result']($request);
		
	$maxindex = isset($_REQUEST['all']) && !empty($zc['settings']['allow_show_all_link']) ? 99999 : 15;
	$start = isset($_REQUEST['listStart']) ? (int) $_REQUEST['listStart'] : 0;
	$num_article_notifications = !empty($row['num_article_notifications']) ? $row['num_article_notifications'] : 0;
	
	$context['zc']['list1']['show_page_index'] = !empty($num_article_notifications) && $num_article_notifications > $maxindex;
	
	if ($context['zc']['list1']['show_page_index'])
	{
		$context['zc']['list1']['page_index'] = zcConstructPageIndex($scripturl . '?listStart=%d' . zcRequestVarsToString('all,listStart', ';'), $start, $num_article_notifications, $maxindex, true);
		
		if (!empty($zc['settings']['allow_show_all_link']))
			$context['zc']['list1']['show_all_link'] = '<a href="' . $scripturl . zcRequestVarsToString('all,listStart', '?') .';all">' . $txt['b81'] . '</a>';
	}
			
	// Default sort methods.
	$sort_methods = array(
		'subject' => 't.subject',
		'author' => 't.poster_name',
		'blog' => 'b.blog_id',
	);

	if (!isset($_REQUEST['sort']) || !isset($sort_methods[$_REQUEST['sort']]))
	{
		$sort_by = 'subject';
		$sort = 't.subject';
		$ascending = isset($_REQUEST['asc']);
	}
	else
	{
		$sort_by = $_REQUEST['sort'];
		$sort = $sort_methods[$_REQUEST['sort']];
		$ascending = isset($_REQUEST['asc']);
	}
		
	// make array of table header info
	$tableHeaders = array(
		'url_requests' => zcRequestVarsToString('sort,asc,desc', '?'),
		'headers' => array(
			'subject' => array('label' => $txt['b227']),
			'author' => array('label' => $txt['b269']),
			'blog' => array('label' => $txt['b3003']),
		),
		'sort_direction' => $ascending ? 'up' : 'down',
		'sort_by' => $sort_by,
	);
	
	// create the table headers
	$context['zc']['list1']['table_headers'] = zcCreateTableHeaders($tableHeaders);
	$context['zc']['list1']['table_headers']['checkbox'] = '<input type="checkbox" onclick="invertAll(this, this.form, \'unsubscribe_articles[]\');" class="check" />';
	
	// get all the article notifications this user has active...
	$request = $zcFunc['db_query']("
		SELECT 
			t.article_id, t.subject, t.poster_id, IFNULL(mem.{tbl:members::column:real_name}, t.poster_name) AS poster_name,
			b.blog_id, b.name AS blog_name
		FROM {db_prefix}log_notify AS ln
			LEFT JOIN {db_prefix}articles AS t ON (t.article_id = ln.article_id)
			LEFT JOIN {db_prefix}blogs AS b ON (b.blog_id = t.blog_id)
			LEFT JOIN {db_prefix}{table:members} AS mem ON (mem.{tbl:members::column:id_member} = t.poster_id)
		WHERE ln.member_id = {int:member_id}
			AND ln.article_id != 0
		ORDER BY {raw:sort}" . ($ascending ? '' : ' DESC') . "
		LIMIT {int:start}, {int:maxindex}", __FILE__, __LINE__,
		array(
			'sort' => $sort,
			'start' => $start,
			'maxindex' => $maxindex,
			'member_id' => $memID
		)
	);
	$context['zc']['article_notifications'] = array();
	while ($row = $zcFunc['db_fetch_assoc']($request))
	{
		$row['subject'] = strip_tags($zcFunc['un_htmlspecialchars']($row['subject']));
		zc_censor_text($row['subject']);
		
		if (!empty($row['blog_name']))
			$row['blog_name'] = strip_tags($zcFunc['un_htmlspecialchars']($row['blog_name']));
		
		$context['zc']['article_notifications'][$row['article_id']] = array(
			'subject' => '<a href="' . $scripturl . '?article=' . $row['article_id'] . '.0">' . $row['subject'] . '</a>',
			'author' => !empty($row['poster_id']) ? sprintf($context['zc']['link_templates']['user_profile'], $row['poster_id'], $row['poster_name'], ' title="' . $txt['b41'] . '"') : $row['poster_name'],
			'blog' => !empty($row['blog_id']) ? '<a href="' . $scripturl . '?blog=' . $row['blog_id'] . '.0">' . $row['blog_name'] . '</a>' : $txt['b338'],
			'checkbox' => '<input type="checkbox" name="unsubscribe_articles[]" value="' . $row['article_id'] . '" />',
		);
	}
	$zcFunc['db_free_result']($request);
	
	// count number of blog notifications
	$request = $zcFunc['db_query']("
		SELECT COUNT(blog_id)
		FROM {db_prefix}log_notify
		WHERE member_id = {int:member_id}
			AND blog_id != 0", __FILE__, __LINE__,
		array(
			'member_id' => $memID
		)
	);
	list($num_blog_notifications) = $zcFunc['db_fetch_row']($request);
	$zcFunc['db_free_result']($request);
		
	$maxindex = isset($_REQUEST['all2']) && !empty($zc['settings']['allow_show_all_link']) ? 99999 : 15;
	$start = isset($_REQUEST['listStart2']) ? (int) $_REQUEST['listStart2'] : 0;
	$num_blog_notifications = !empty($row['num_blog_notifications']) ? $row['num_blog_notifications'] : 0;
	
	$context['zc']['list2']['show_page_index'] = !empty($num_blog_notifications) && $num_blog_notifications > $maxindex;
	
	if ($context['zc']['list2']['show_page_index'])
	{
		$context['zc']['list1']['page_index'] = zcConstructPageIndex($scripturl . '?listStart=%d' . zcRequestVarsToString('all,listStart', ';'), $start, $num_blog_notifications, $maxindex, true);
		
		if (!empty($zc['settings']['allow_show_all_link']))
			$context['zc']['list1']['show_all_link'] = '<a href="' . $scripturl . zcRequestVarsToString('all,listStart', '?') . ';all">' . $txt['b81'] . '</a>';
	}
			
	// Default sort methods.
	$sort_methods = array(
		'blog' => 'b.name',
	);

	if (!isset($_REQUEST['sort2']) || !isset($sort_methods[$_REQUEST['sort2']]))
	{
		$sort_by = 'blog';
		$sort2 = 'b.name';
		$ascending = isset($_REQUEST['asc']);
	}
	else
	{
		$sort_by = $_REQUEST['sort2'];
		$sort2 = $sort_methods[$_REQUEST['sort']];
		$ascending = isset($_REQUEST['asc']);
	}
		
	// make array of table header info
	$tableHeaders = array(
		'url_requests' => zcRequestVarsToString('sort2,asc2,desc2', '?'),
		'headers' => array(
			'blog' => array('label' => $txt['b3003']),
		),
		'sort_direction' => $ascending ? 'up' : 'down',
		'sort_by' => $sort_by,
	);
	
	// create the table headers
	$context['zc']['list2']['table_headers'] = zcCreateTableHeaders($tableHeaders);
	$context['zc']['list2']['table_headers']['checkbox'] = '<input type="checkbox" onclick="invertAll(this, this.form, \'unsubscribe_blogs[]\');" class="check" />';
	
	// get all the blogs notifications this user has active...
	$request = $zcFunc['db_query']("
		SELECT b.blog_id, b.name AS blog_name
		FROM {db_prefix}log_notify AS ln
			LEFT JOIN {db_prefix}blogs AS b ON (b.blog_id = ln.blog_id)
		WHERE ln.member_id = {int:member_id}
			AND ln.blog_id != 0
		ORDER BY {raw:sort}" . ($ascending ? '' : ' DESC') . "
		LIMIT {int:start}, {int:maxindex}", __FILE__, __LINE__,
		array(
			'sort' => $sort2,
			'start' => $start,
			'maxindex' => $maxindex,
			'member_id' => $memID
		)
	);
	$context['zc']['blog_notifications'] = array();
	while ($row = $zcFunc['db_fetch_assoc']($request))
	{
		$row['blog_name'] = strip_tags($zcFunc['un_htmlspecialchars']($row['blog_name']));
		
		$context['zc']['blog_notifications'][$row['blog_id']] = array(
			'blog' => '<a href="' . $scripturl . '?blog=' . $row['blog_id'] . '.0">' . $row['blog_name'] . '</a>',
			'checkbox' => '<input type="checkbox" name="unsubscribe_blogs[]" value="' . $row['blog_id'] . '" />',
		);
	}
	$zcFunc['db_free_result']($request);
}

function zc_bcp_plugins($memID)
{
	global $context, $txt, $scripturl;
	global $zc;
	
	// just make sure they have access...
	if (!zc_check_permissions('access_plugins_tab'))
		zc_fatal_error('zc_error_40');
		
	/*$context['zc']['install_plugin_form'] = array(
		'install_plugin' => array(
			'type' => 'file',
			'allowed_file_extensions' => array('php'),
			'value' => '',
			'dir' => $zc['plugins_dir'],
			'max_file_size' => 7200000,
			'label' => 'b535',
			'extract_archives' => true,
		),
	);
	
	if (isset($_POST['save_installPlugIn']))
	{
		checkSession('get');
		list($processed, $context['zc']['errors']) = zcProcessForm($context['zc']['install_plugin_form']);
		
		if (empty($context['zc']['errors']))
			zc_redirect_exit(zcRequestVarsToString());
	}*/
	
	// !!!
	
	$context['zc']['list1']['list_empty_txt'] = $txt['b437'];
	$context['zc']['list1']['title'] = $txt['b217'];
	
	$sort_methods = array(
		'name' => 'name',
		'version' => 'version',
		'author_name' => 'author_name',
		'author_email' => 'author_email',
		'author_url' => 'author_url',
	);
	
	// use default sorting method...
	if (!isset($_REQUEST['sort']) || !isset($sort_methods[$_REQUEST['sort']]))
	{
		$sort_by = 'name';
		$sort = 'name';
		$ascending = isset($_REQUEST['asc']);
	}
	// they wanna sort another way?
	else
	{
		$sort_by = $_REQUEST['sort'];
		$sort = $sort_methods[$_REQUEST['sort']];
		$ascending = isset($_REQUEST['asc']);
		$context['zc']['die'] = true;
	}

	// make array of table header info
	$tableHeaders = array(
		'url_requests' => zcRequestVarsToString('sort,asc,desc', '?'),
		'headers' => array(
			'name' => array('label' => $txt['b180']),
			'version' => array('label' => $txt['b560']),
			'author_name' => array('label' => $txt['b269']),
			'author_email' => array('label' => $txt['b611']),
			'author_url' => array('label' => $txt['b667']),
		),
		'sort_direction' => $ascending ? 'up' : 'down',
		'sort_by' => $sort_by,
	);
	
	$context['zc']['list1']['table_headers'] = zcCreateTableHeaders($tableHeaders);
	$context['zc']['list1']['table_headers']['enable_disable_link'] = '<span style="white-space:nowrap;"><a href="' . $scripturl . zcRequestVarsToString('sa', '?') . ';sa=edp;id=all;enable_disable=0;sesc=' . $context['session_id'] . '" title="' . $txt['b681'] . '"><img src="' . $context['zc']['default_images_url'] . '/icons/chk_off.png" alt="' . $txt['b681'] . '" /></a>&nbsp;&nbsp;<a href="' . $scripturl . zcRequestVarsToString('sa', '?') . ';sa=edp;id=all;enable_disable=1;sesc=' . $context['session_id'] . '" title="' . $txt['b680'] . '"><img src="' . $context['zc']['default_images_url'] . '/icons/chk_on.png" alt="' . $txt['b680'] . '" /></a></span>';
	$context['zc']['list1']['table_headers']['checkbox'] = '<input type="checkbox" onclick="invertAll(this, this.form, \'items[]\');" class="check" />';
	$context['zc']['list1']['alignment'] = array('enable_disable_link' => 'center', 'description' => 'left');
	
	$context['zc']['list1']['submit_button_txt'] = $txt['b3006'];
	$context['zc']['list1']['confirm_submit_txt'] = sprintf($txt['b71'], $txt['b217a']);
	$context['zc']['list1']['form_url'] = $scripturl . '?zc=bcp;u=' . $memID . ';sa=deletePlugIns' . zcRequestVarsToString('zc,u,sa', ';');
	
	// format all currently installed plug-ins for display on this page...
	$context['zc']['list_plugins'] = array();
	$i = 0;
	if (!empty($context['zc']['plugins']))
		foreach ($context['zc']['plugins'] as $id => $plugin)
		{
			// is there a language file specified?
			if (!empty($plugin['lngfile']))
				// load it up
				zcLoadLanguage($plugin['lngfile'], '', false, $zc['plugins_lang_dir']);
			
			$temp = array(
				'name' => !empty($plugin['name']) ? $plugin['name'] : '',
				'version' => !empty($plugin['version']) ? $plugin['version'] : '',
				'author_name' => !empty($plugin['author']['name']) ? $plugin['author']['name'] : '',
				'author_email' => !empty($plugin['author']['email']) ? $plugin['author']['email'] : '',
				'author_url' => !empty($plugin['author']['url']) ? $plugin['author']['url'] : '',
			);
			
			$key = isset($temp[$sort_by]) ? $temp[$sort_by] : $temp['name'];
			unset($temp);
			
			// make sure it's a unique key...
			if (isset($context['zc']['list_plugins'][$key]))
			{
				if (!isset($num_plugins))
					$num_plugins = count($context['zc']['plugins']);
					
				for ($n = $i; $n <= count($num_plugins); $n++)
					if (!isset($context['zc']['list_plugins'][$key . $n]))
					{
						$key .= $n;
						break 1;
					}
			}
				
			$context['zc']['list_plugins'][$key] = array(
				'name' => !empty($plugin['name']) ? '<span style="white-space:nowrap;">' . $plugin['name'] . '</span>' : '',
				'version' => !empty($plugin['version']) ? '<span style="white-space:nowrap;">' . $plugin['version'] . '</span>' : '',
				'author_name' => !empty($plugin['author']['url']) || !empty($plugin['author']['email']) ? '<a href="' . (!empty($plugin['author']['email']) ? 'mailto:' . $plugin['author']['email'] : $plugin['author']['url']) . '" style="white-space:nowrap;">' . $plugin['author']['name'] . '</a>' : $plugin['author']['name'],
				'author_email' => !empty($plugin['author']['email']) ? $plugin['author']['email'] : '-',
				'author_url' => !empty($plugin['author']['url']) ? $plugin['author']['url'] : '-',
				'description' => !empty($plugin['description']) ? zcFormatTxtString($plugin['description']) : '',
				'enable_disable_link' => '<a href="' . $scripturl . zcRequestVarsToString('sa', '?') . ';sa=edp;id=' . urlencode($id) . ';enable_disable=' . (!empty($zc['settings']['zcp_' . $id . '_enabled']) ? 0 : 1) . ';sesc=' . $context['session_id'] . '" title="' . sprintf($txt['b254'], (!empty($zc['settings']['zcp_' . $id . '_enabled']) ? $txt['b114'] : $txt['b113'])) . '">' . (!empty($zc['settings']['zcp_' . $id . '_enabled']) ? '<img src="' . $context['zc']['default_images_url'] . '/icons/chk_on.png" alt="' . $txt['b86'] . '" />' : '<img src="' . $context['zc']['default_images_url'] . '/icons/chk_off.png" alt="' . $txt['b11'] . '" />') . '</a>',
				'checkbox' => '<input type="checkbox" name="items[]" value="' . $id . '" />',
			);
		}
		
	// let's sort $context['zc']['list_plugins'] now...
	if ($ascending)
		rsort($context['zc']['list_plugins']);
	else
		sort($context['zc']['list_plugins']);
}

function zc_bcp_themes($memID)
{
	global $context, $txt, $scripturl;
	global $blog, $zc, $blog_info, $zcFunc;
	
	// admin Themes tab...
	if (empty($blog))
	{
		// just make sure they have access...
		if (!zc_check_permissions('access_themes_tab'))
			zc_fatal_error('zc_error_40');
			
		// do we want to choose a theme from the detailed theme page?
		if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'choose')
		{
			$context['zc']['pick_a_theme'] = true;
			
			// no need to view this page....
			if (empty($context['zc']['themes']))
				zc_redirect_exit(zcRequestVarsToString('do,theme'));
				
			$context['zc']['available_themes'] = $context['zc']['themes'];
			
			// let's make thumbnails of preview images
			foreach ($context['zc']['available_themes'] as $theme_id => $info)
				if (!empty($info['preview_images']))
				{
					$context['zc']['available_themes'][$theme_id]['thumbnails'] = array();
					foreach ($info['preview_images'] as $href)
					{
						if (($array = zcResizeImage($href, 240, 120)) !== false)
						{
							list($width, $height) = $array;
							$context['zc']['available_themes'][$theme_id]['thumbnails'][] = '<a href="'. $href .'"><img src="'. $href .'" alt="" width="'. $width .'" height="'. $height .'" /></a>';
						}
					}
				}
			
			if (isset($_REQUEST['theme']))
			{
				if (isset($context['zc']['themes'][$_REQUEST['theme']]))
					zcUpdateThemeSettings(array('blog_community_theme' => $_REQUEST['theme']));
			
				zc_redirect_exit(zcRequestVarsToString('do,theme'));
			}
		}
		// otherwise show the page as normal...
		else
		{
			// prepares $context['zc']['admin_theme_settings']
			zc_prepare_admin_theme_settings_array();
			
			/*$context['zc']['install_theme_form'] = array(
				'install_new_theme' => array(
					'type' => 'file',
					'allowed_file_extensions' => array('zip'),
					'value' => '',
					'dir' => $zc['themes_dir'],
					'max_file_size' => 7200000,
					'label' => 'b495',
					'extract_archives' => true,
				),
			);*/
			
			$context['zc']['form_theme_settings'] = $context['zc']['admin_theme_settings'];
			$context['zc']['form_data'] = $zc['settings'];
			
			if (isset($_POST['save_themes']))
			{
				checkSession('get');
					
				list($processed, $context['zc']['errors']) = zcProcessForm($context['zc']['form_theme_settings']);
				
				// no errors, right?
				if (empty($context['zc']['errors']))
				{
					zcUpdateThemeSettings($processed);
					$_SESSION['zc_success_msg'] = 'zc_success_2';
					zc_redirect_exit(zcRequestVarsToString());
				}
			}/*
			elseif (isset($_POST['save_installTheme']))
			{
				checkSession('get');
				list($processed, $context['zc']['errors']) = zcProcessForm($context['zc']['install_theme_form']);
				
				if (empty($context['zc']['errors']))
					zc_redirect_exit(zcRequestVarsToString());
			}*/
		}
	}
	// blog Themes tab...
	elseif (!empty($blog))
	{
		if (!$context['can_use_blog_themes'])
			zc_fatal_error('zc_error_40');
			
		// do we want to choose a theme from the detailed theme page?
		if (isset($_REQUEST['do']) && $_REQUEST['do'] == 'choose')
		{
			$context['zc']['pick_a_theme'] = true;
				
			$context['zc']['available_themes'] = array();
			// let's make thumbnails of preview images
			foreach ($context['zc']['themes'] as $theme_id => $info)
				if (in_array($theme_id, $zc['settings']['enabled_themes']))
				{
					$context['zc']['available_themes'][$theme_id] = $info;
					if (!empty($info['preview_images']))
					{
						$context['zc']['available_themes'][$theme_id]['thumbnails'] = array();
						foreach ($info['preview_images'] as $href)
						{
							if (($array = zcResizeImage($href, 240, 120)) !== false)
							{
								list($width, $height) = $array;
								$context['zc']['available_themes'][$theme_id]['thumbnails'][] = '<a href="'. $href .'"><img src="'. $href .'" alt="" width="'. $width .'" height="'. $height .'" /></a>';
							}
						}
					}
				}
			
			// no need to view this page....
			if (empty($context['zc']['available_themes']))
				zc_redirect_exit(zcRequestVarsToString('do,theme'));
			
			if (isset($_REQUEST['theme']))
			{
				if (isset($context['zc']['themes'][$_REQUEST['theme']]) && in_array($_REQUEST['theme'], $zc['settings']['enabled_themes']))
					zcUpdateThemeSettings(array('blog_theme' => $_REQUEST['theme']), $blog);
			
				zc_redirect_exit(zcRequestVarsToString('do,theme'));
			}
		}
		// otherwise show the page as normal...
		else
		{
			// needs a row in the theme_settings table...
			if (empty($context['zc']['theme_settings_row_exists']) && !empty($context['zc']['theme_settings_info']))
			{
				require_once($zc['sources_dir'] . '/Subs-Blogs.php');
				zc_init_blog_settings($blog, 'theme_settings');
				zc_redirect_exit(zcRequestVarsToString());
			}
			
			$context['zc']['form_data'] = $context['zc']['theme_settings'];
			
			// start the $context['zc']['theme_settings_info'] array over again...
			$context['zc']['theme_settings_info'] = zc_prepare_theme_settings_array();
			if (!empty($context['zc']['blog_theme']) && !empty($context['zc']['themes'][$context['zc']['blog_theme']]) && in_array($context['zc']['blog_theme'], $zc['settings']['enabled_themes']) && !empty($context['zc']['themes'][$context['zc']['blog_theme']]['settings']))
				foreach ($context['zc']['themes'][$context['zc']['blog_theme']]['settings'] as $setting => $array)
					$context['zc']['theme_settings_info'][$setting] = $array;
			
			$context['zc']['form_theme_settings'] = $context['zc']['theme_settings_info'];
			
			if (isset($_POST['save_themes']))
			{
				checkSession('get');
					
				list($processed, $context['zc']['errors']) = zcProcessForm($context['zc']['form_theme_settings']);
				
				// no errors, right?
				if (empty($context['zc']['errors']))
				{
					zcUpdateThemeSettings($processed, $blog);
					$_SESSION['zc_success_msg'] = 'zc_success_2';
					zc_redirect_exit(zcRequestVarsToString());
				}
			}
		}
	}
}

function zc_bcp_permissions()
{
	global $context, $txt, $zc;
	
	// just make sure they have access...
	if (!zc_check_permissions('access_permissions_tab'))
		zc_fatal_error('zc_error_40');
	
	// load permissions lang file...
	zcLoadLanguage('Permissions');
		
	// we'll need this
	require_once($zc['sources_dir'] . '/Subs-Permissions.php');
	
	$context['zc']['group_request'] = !empty($_REQUEST['group']) ? ';group=' . $_REQUEST['group'] : '';
	
	// updating permissions?
	if ((isset($_POST['add_deny']) && isset($_POST['groups']) && isset($_POST['permission'])) || isset($_POST['group']))
		zcUpdatePermissions();
	else
		zc_load_member_groups_permissions();
}

function zc_bcp_other($memID)
{
	global $context, $txt, $scripturl, $zc, $zcFunc;
	
	// just make sure they have access...
	if (!zc_check_permissions('access_other_tab'))
		zc_fatal_error('zc_error_40');
	
	$possible_ssa = array('maintenance', 'error_log');
	$_REQUEST['ssa'] = !isset($_REQUEST['ssa']) || !in_array($_REQUEST['ssa'], $possible_ssa) ? 'error_log' : $_REQUEST['ssa'];
	$context['zc']['ssa'] = $_REQUEST['ssa'];
	$context['zc']['ssa_request'] = !empty($_REQUEST['ssa']) ? ';ssa=' . $_REQUEST['ssa'] : '';
		
	if (!empty($context['zc']['secondary_bcp_menu'][$context['zc']['current_subMenu']]['sub_menu'][$context['zc']['ssa']]['label']))
		$context['page_title'] = $txt['b279'] . ' - ' . $context['zc']['secondary_bcp_menu'][$context['zc']['current_subMenu']]['sub_menu'][$context['zc']['ssa']]['label'];
	
	if ($_REQUEST['ssa'] == 'maintenance')
	{
		$maintenanceTasks = array(
			'recountblogtotals' => array('Subs-Maintenance.php', 'zcRecountBlogTotals', $txt['b3056']),
			'maintainblogcategories' => array('Subs-Maintenance.php', 'zcMaintainBlogCategories', $txt['b3052']),
			'maintaintags' => array('Subs-Maintenance.php', 'zcMaintainTags', $txt['b522']),
			'pruneDrafts' => array('Subs-Maintenance.php', 'zcPruneDrafts', $txt['b525'] . '&nbsp;<input type="text" name="pruneDrafts" style="width:30px;" />&nbsp;' . $txt['b526'] . '&nbsp;&nbsp;<input type="submit" value="' . $txt['b3006'] . '" onclick="return confirm(\'' . $txt['b278'] . '\');" />', 'form'),
		);
		
		$context['zc']['maintenance_links'] = array('<a href="' . $scripturl . '?zc=bcp;u=' . $memID . $context['zc']['sa_request'] . $context['zc']['ssa_request'] . ';task=check_tables;sesc=' . $context['session_id'] . '">' . $txt['b480'] . '</a>');
		if (!empty($maintenanceTasks))
			foreach ($maintenanceTasks as $task => $stuff)
				if (is_array($stuff))
					if (empty($stuff[3]))
						$context['zc']['maintenance_links'][] = '<a href="' . $scripturl . '?zc=bcp;u=' . $memID . $context['zc']['sa_request'] . $context['zc']['ssa_request'] . ';task=' . $task . ';sesc=' . $context['session_id'] . '">' . $stuff[2] . '</a>';
					elseif ($stuff[3] == 'form')
						$context['zc']['maintenance_links'][] = '<form method="post" action="' . $scripturl . '?zc=bcp;u=' . $memID . $context['zc']['sa_request'] . $context['zc']['ssa_request'] . ';task=' . $task . ';sesc=' . $context['session_id'] . '">' . $stuff[2] . '</form>';
		
		$need_extra_info = array(
			'settings' => array('Settings.php', 'zc_prepare_blog_settings_array'),
			'preferences' => array('Settings.php', 'zc_prepare_preferences_array'),
			'theme_settings' => array('Settings.php', 'zc_prepare_theme_settings_array'),
			'plugin_settings' => array('Settings.php', 'zc_prepare_plugin_settings_array'),
			'articles' => array('Subs-Post.php', 'zc_prepare_article_form_array'),
			'comments' => array('Subs-Post.php', 'zc_prepare_comment_form_array'),
			'polls' => array('Subs-Post.php', 'zc_prepare_poll_form_array'),
		);
		
		// run a check on all tables...
		if (!empty($_REQUEST['task']) && $_REQUEST['task'] == 'check_tables')
		{
			require_once($zc['sources_dir'] . '/Subs-Maintenance.php');
			require_once($zc['sources_dir'] . '/db-info-zc.php');
			
			$tables = zc_prepare_db_table_info();
			
			// check for missing columns in these tables... add issues found to known_issues array...
			$tables_with_issues = zc_db_check_for_missing_columns($tables);
			
			$check_again = array();
			foreach ($need_extra_info as $table_name => $array)
				if (file_exists($zc['sources_dir'] . '/' . $array[0]))
				{
					require_once($zc['sources_dir'] . '/' . $array[0]);
					if (!function_exists($array[1]))
						continue;
						
					$check_again[$table_name] = $array[1]();
				}
				
			$temp = zc_db_check_for_missing_columns($check_again);
			if (!empty($temp))
				$tables_with_issues = array_unique(array_merge($tables_with_issues, $temp));
			unset($temp);
			
			$context['zc']['known_issues'] = array();
			if (!empty($tables_with_issues))
			{
				// fix all tables link
				if (count($tables_with_issues) > 1)
					$context['zc']['known_issues'][] = '<a class="zcButtonLink" href="' . $context['zc']['base_bcp_url'] . $context['zc']['u_request'] . $context['zc']['sa_request'] . $context['zc']['ssa_request'] . ';fix_table=all;sesc=' . $context['session_id'] . '" title="' . $txt['b97'] . '">' . $txt['b6'] . '</a>';
				
				foreach ($tables_with_issues as $table_name)
					$context['zc']['known_issues'][] = sprintf($txt['b481'], $table_name) . ' <a class="zcButtonLink" href="' . $context['zc']['base_bcp_url'] . $context['zc']['u_request'] . $context['zc']['sa_request'] . $context['zc']['ssa_request'] . ';fix_table=' . $table_name . ';sesc=' . $context['session_id'] . '" title="' . $txt['b97'] . '">' . $txt['b96'] . '</a>';
			}
			
			if (empty($context['zc']['known_issues']))
			{
				$_SESSION['zc_success_msg'] = 'zc_success_10';
				zc_redirect_exit(zcRequestVarsToString('task'));
			}
		}
		// return a maintenance task function if that's what they are trying to do (and one exists)
		elseif (!empty($_REQUEST['task']) && !empty($maintenanceTasks[$_REQUEST['task']]))
		{
			// definitely check session first
			checkSession('get');
			
			if (is_array($maintenanceTasks[$_REQUEST['task']]) && file_exists($zc['sources_dir'] . '/' . $maintenanceTasks[$_REQUEST['task']][0]))
			{
				require_once($zc['sources_dir'] . '/' . $maintenanceTasks[$_REQUEST['task']][0]);
				
				if (function_exists($maintenanceTasks[$_REQUEST['task']][1]))
					$maintenanceTasks[$_REQUEST['task']][1]();
			}
			$_SESSION['zc_success_msg'] = 'zc_success_7';
			zc_redirect_exit(zcRequestVarsToString('task'));
		}
		
		if (!empty($_REQUEST['fix_table']))
		{
			checkSession('get');
			
			require_once($zc['sources_dir'] . '/Subs-Maintenance.php');
			require_once($zc['sources_dir'] . '/db-info-zc.php');
			
			$tables = zc_prepare_db_table_info();
			
			$check_again = array();
			foreach ($need_extra_info as $table_name => $array)
				if (file_exists($zc['sources_dir'] . '/' . $array[0]))
				{
					require_once($zc['sources_dir'] . '/' . $array[0]);
					if (!function_exists($array[1]))
						continue;
						
					$check_again[$table_name] = $array[1]();
				}
			
			// is it a valid table or are we repairing all tables?
			if ($_REQUEST['fix_table'] == 'all' || isset($tables[$_REQUEST['fix_table']]))
			{
				$repair_queue = $_REQUEST['fix_table'] == 'all' ? array_keys($tables) : array($_REQUEST['fix_table']);
				
				$failed_tables = array();
				foreach ($repair_queue as $table_name)
				{
					$request = $zcFunc['db_query']("
						DESCRIBE {db_prefix}{raw:table_name}", __FILE__, __LINE__,
						array(
							'table_name' => $table_name
						)
					);
					
					// the table doesn't exist.... let's try to make it...
					if ($zcFunc['db_num_rows']($request) == 0)
					{
						require_once($zc['sources_dir'] . '/Subs-Database.php');
						$result = zcCreateDatabaseTable($tables[$table_name]);
						if (!$result)
						{
							$failed_tables[] = $table_name;
							continue;
						}
					}
					else
						$result = zc_repair_db_table($table_name, $tables[$table_name], null, false);
						
					if (!$result)
					{
						$failed_tables[] = $table_name;
						continue;
					}
					
					if (isset($check_again[$table_name]))
						if (($result = zc_repair_db_table($table_name, $check_again[$table_name], null, false)) == false)
						{
							$failed_tables[] = $table_name;
							continue;
						}
						
					$zcFunc['db_free_result']($request);
				}
				
				if (!empty($failed_tables))
					$context['zc']['errors'][] = array('zc_error_99', '<br />' . implode('<br />', $failed_tables));
				else
				{
					$_SESSION['zc_success_msg'] = 'zc_success_7';
					zc_redirect_exit(zcRequestVarsToString('fix_table'));
				}
			}
			else
				$context['zc']['errors'][] = array('zc_error_98', $_REQUEST['fix_table']);
		}
	}
	elseif ($_REQUEST['ssa'] == 'error_log')
	{
		$context['zc']['secondary_bcp_menu']['other']['sub_menu']['error_log']['is_active'] = true;
		
		require_once($zc['sources_dir'] . '/Subs-Errors.php');
		$context['zc']['error_settings_array'] = zc_prepare_error_settings_array();
		
		// clear the error log?
		if (isset($_REQUEST['clear_error_log']))
		{
			checkSession('get');
			
			$zcFunc['db_query']("
				DELETE FROM {db_prefix}log_errors", __FILE__, __LINE__);
		}
		// deleting errors?
		elseif (!empty($_POST['errors']))
		{
			global $zcFunc;
			
			checkSession('post');
			
			$errors = array();
			foreach ($_POST['errors'] as $error_id)
				$errors[] = (int) $error_id;
				
			$errors = array_unique($errors);
			if (!empty($errors))
				$zcFunc['db_query']("
					DELETE FROM {db_prefix}log_errors
					WHERE error_id IN ({array_int:errors})
					LIMIT {int:limit}", __FILE__, __LINE__,
					array(
						'errors' => $errors,
						'limit' => count($errors)
					)
				);
		}
		// saving error logging settings?
		elseif (!empty($_POST['save_errorSettings']))
		{
			checkSession('post');
			
			list($processed, $context['zc']['errors']) = zcProcessForm($context['zc']['error_settings_array']);
			
			// no errors from processing?
			if (empty($context['zc']['errors']))
			{
				zcUpdateGlobalSettings($processed);
				$_SESSION['zc_success_msg'] = 'zc_success_2';
				zc_redirect_exit(zcRequestVarsToString());
			}
		}
		
		list($context['zc']['list_of_errors'], $context['zc']['list1']) = zc_get_list_of_errors();
		
		$context['zc']['list1']['special_links'] = array('<a href="' . $scripturl . zcRequestVarsToString(null, '?') . ';clear_error_log;sesc=' . $context['session_id'] . '" onclick="return confirm(\'' . $txt['b482'] . '\');">' . $txt['b483'] . '</a>');
	}
}

function zc_preview_custom_window()
{
	global $context, $txt, $zcFunc, $blog, $zc;
	
	$context['blog_control_panel'] = false;
	$context['zc']['layerless'] = true;
	$context['zc']['template_layers'] = array('html' => array());
	$context['zc']['no_change_template_layers'] = true;
	
	// are they allowed?
	if ((!empty($blog) && !$context['is_blog_owner'] && !$context['can_access_any_blog_control_panel']) || (empty($blog) && !$context['can_access_blog_index_tab']))
		zc_fatal_error('zc_error_40');
		
	zcLoadTemplate('Generic-index');
	
	$request = $zcFunc['db_query']("
		SELECT window_id, title, content, content_type
		FROM {db_prefix}custom_windows
		WHERE window_id = {int:window_id}
			AND blog_id = {int:blog_id}
		LIMIT 1", __FILE__, __LINE__,
		array(
			'window_id' => !empty($_REQUEST['window']) ? (int) $_REQUEST['window'] : 0,
			'blog_id' => $blog
		)
	);
	
	if ($zcFunc['db_num_rows']($request) > 0)
	{
		$context['zc']['side_windows'] = array(1 => '');
		$context['zc']['max_window_order'] = 1;
		
		$row = $zcFunc['db_fetch_assoc']($request);
		
		$row['content'] = $zcFunc['un_htmlspecialchars']($row['content']);
			
		$context['zc']['side_windows'] = array();
		$context['zc']['side_windows'][1] = array(
			'title' => $zcFunc['un_htmlspecialchars']($row['title']),
			'is_php' => !empty($row['content_type']) && $row['content_type'] == 2,
			'content_type' => $row['content_type'],
			'content' => empty($row['content_type']) ? $zcFunc['parse_bbc']($row['content']) : $row['content'],
			'type' => 'custom',
		);
		$context['zc']['sub_template'] = 'simple_popup';
		$context['zc']['sub_sub_template'] = 'side_windows';
	}
	// could not find the window...
	else
		zc_fatal_error('zc_error_76');
}

?>