<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
/*
	zc_init_blog_settings($blogs, $tables = null)
		- inserts a row into the 3 settings tables for blogs (blog_settings, blog_plugin_settings, blog_theme_settings)
		- if $tables is not empty, rows will be inserted for only the tables in $tables
		
	zcInitBlog($memID)
		- import a board from a forum
		- create a new blog
		
	zcDeleteBlog($memID = null)
		- delete a blog or blogs
		
	zc_change_blog_owner($blog, $new_owner)
		- change the owner of a blog
		
	zc_get_list_of_blogs($blogs = null, $query = null, $num_blogs = null)
		- get a list of blogs
*/
	
function zc_init_blog_settings($blogs, $tables = null)
{
	global $zcFunc, $zc, $context;
	
	if (empty($blogs))
		return;
	elseif (!is_array($blogs))
		$blogs = array($blogs);
		
	if ($tables !== null)
		if (!empty($tables) && !is_array($tables))
			$tables = explode(',', $tables);
	
	// we only use $tables if we are starting rows for a pre-existing blog...
	if (empty($tables))
		zcUpdateGlobalSettings(array('community_total_blogs' => $zc['settings']['community_total_blogs'] + count($blogs)));

	$settings_arrays = array();
	if (empty($tables) || in_array('blog_settings', $tables))
		$settings_arrays['settings'] = zc_prepare_blog_settings_array();
		
	if (empty($tables) || in_array('theme_settings', $tables))
		$settings_arrays['theme_settings'] = zc_prepare_theme_settings_array();
		
	if (empty($tables) || in_array('plugin_settings', $tables))
		$settings_arrays['plugin_settings'] = zc_prepare_plugin_settings_array();
	
	foreach ($settings_arrays as $table => $array)
	{
		$columns = array('blog_id' => 'int');
		$temp_data = array();
		foreach ($array as $k => $a)
			if (!in_array($k, array('_info_')) && (!isset($array['_info_']['exclude_from_table']) || !in_array($k, $array['_info_']['exclude_from_table'])))
			{
				$columns[$k] = isset($a['type']) ? $a['type'] : 'string';
				$temp_data[$k] = $a['value'];
			}
	
		$data = array();
		foreach ($blogs as $blog)
			$data[] = array_merge(array('blog_id' => $blog), $temp_data);
		
		$zcFunc['db_insert']('replace', '{db_prefix}' . $table, $columns, $data);
	}
}

function zcInitBlog($memID)
{
	global $context, $txt;
	global $zc, $zcFunc;
	
	// make sure they are allowed to create blogs....
	if (!zc_check_permissions('create_blog') || (!empty($context['zc']['cp_owner']['num_blogs_owned']) && !zc_check_permissions('multiple_blogs')))
		zc_fatal_error('zc_error_52');
	
	// if this user has the maximum number of blogs allowed... they can't make anymore
	// ...... unless, of course, the user doing this is an admin
	if (!$context['user']['is_admin'] && !empty($zc['settings']['max_num_blogs']) && $context['zc']['cp_owner']['num_blogs_owned'] >= $zc['settings']['max_num_blogs'])
		zc_fatal_error(array('b444', (!$context['is_cp_owner'] && !empty($context['zc']['cp_owner']['name']) ? $txt['b6'] . ' ' . $txt['b5'] . ' ' . $txt['b4'] : $txt['b2'] . ' ' . $txt['b3'])));
	
	// convert an existing board to a blog
	if (isset($_POST['convertBoard']))
	{
		checkSession('post');
		
		$board = (int) $_POST['convertBoard'];
		$blog = 0;
		
		// need this...
		require_once($zc['sources_dir'] . '/Import-smf.php');
		
		// imports all topics, messages, polls, poll_choices, moderators associated with a blog or blogs
		$blogs = zc_import_smf_boards($board);
		if ($blogs !== false)
			foreach ($blogs as $id)
				if (!empty($id))
				{
					$blog = $id;
					break;
				}
			
		$_SESSION['zc_success_msg'] = 'zc_success_6';
		//zc_redirect_exit('zc=bcp;u=' . $context['user']['id'] . ';sa=globalSettings');
		
		// we want to make sure we redirect this user to the last page of their blog_page_index
		$blogStart = ';blogStart=' . $context['zc']['cp_owner']['num_blogs_owned'] > 5 ? floor($context['zc']['cp_owner']['num_blogs_owned'] / 5) * 5 : '';
		zc_redirect_exit('zc=bcp;u=' . $memID . ';blog='. $blog . '.0' . $blogStart);
	}
	// we're creating a fresh blog then...
	else
	{
		checkSession('get');
		
		// this is for default naming purposes
		if (!empty($context['zc']['cp_owner']['num_blogs_owned']))
			$num_of_blogs = ' ' . ($context['zc']['cp_owner']['num_blogs_owned'] + 1);
		else
			$num_of_blogs = '';
			
		$blog_info = array(
			'name' => addslashes($zcFunc['htmlspecialchars'](stripslashes(zcFormatTextSpecialMeanings($zc['settings']['base_new_blogs_name']) . $num_of_blogs), ENT_QUOTES)),
			'member_groups' => isset($zc['settings']['defaultAllowedGroups']) ? addslashes($zcFunc['htmlspecialchars']($zc['settings']['defaultAllowedGroups'], ENT_QUOTES)) : '-1,0',
		);
		
		// insert blog into blogs table
		$result = $zcFunc['db_insert']('insert', '{db_prefix}blogs', array('member_groups' => 'string', 'time_created' => 'int', 'name' => 'string', 'blog_owner' => 'int'), array('member_groups' => $blog_info['member_groups'], 'time_created' => time(), 'name' => $blog_info['name'], 'blog_owner' => $memID));
			
		if ($result != false)
			$blog = $zcFunc['db_insert_id']();
			
		if (!empty($blog))
		{
			zc_init_blog_settings($blog);
		
			$_SESSION['zc_success_msg'] = 'zc_success_5';
			
			// we want to make sure we redirect this user to the last page of their blog_page_index
			$blogStart = $context['zc']['cp_owner']['num_blogs_owned'] > 5 ? ';blogStart=' . (floor($context['zc']['cp_owner']['num_blogs_owned'] / 5) * 5) : '';
				
			zc_redirect_exit('zc=bcp;u=' . $memID . ';blog='. $blog . '.0' . $blogStart);
		}
		else
			zc_fatal_error('zc_error_64');
	}
}

function zcDeleteBlog($memID = null)
{
	global $context, $txt, $scripturl, $zcFunc;
	
	// definitely should check the session
	checkSession('get');
	
	// are they allowed to delete blogs?
	if (!$context['user']['is_admin'] && !$context['can_delete_own_blogs'])
		zc_fatal_error(array('zc_error_17', 'b208'));
	
	// assemble the array of blogs that we want to delete
	$blogs = array();
	if (isset($_REQUEST['zcDeleteBlog']))
	{
		$temps = explode(',', urldecode($_REQUEST['zcDeleteBlog']));
		foreach ($temps as $temp)
			$blogs[] = (int) $temp;
	}
	elseif (!empty($_POST['items']))
	{
		if (is_array($_POST['items']))
			foreach ($_POST['items'] as $post_blog)
				$blogs[] = (int) $post_blog;
	}
	elseif (!empty($_POST['blogs']))
		if (is_array($_POST['blogs']))
			foreach ($_POST['blogs'] as $post_blog)
				$blogs[] = (int) $post_blog;
				
	if (empty($blogs))
		return false;
		
	// verify this is the blog owner of each blog... (if they aren't an admin)
	if (!$context['user']['is_admin'])
	{
		$request = $zcFunc['db_query']("
			SELECT blog_owner, blog_id
			FROM {db_prefix}blogs AS b
			WHERE blog_id IN ({array_int:blogs})
			LIMIT {int:limit}", __FILE__, __LINE__,
			array(
				'limit' => count($blogs),
				'blogs' => $blogs
			)
		);
		$ownership_confirmed = array();
		while ($row = $zcFunc['db_fetch_assoc']($request))
			if ($row['blog_owner'] == $context['user']['id'])
				$ownership_confirmed[] = $row['blog_id'];
		$zcFunc['db_free_result']($request);
		
		// continue on with the blogs that have been confirmed as belonging to this user...
		$blogs = $ownership_confirmed;
	}
	
	// check each blog to see if it has articles in it
	$request = $zcFunc['db_query']("
		SELECT b.blog_id, b.name
		FROM {db_prefix}articles AS t
			LEFT JOIN {db_prefix}blogs AS b ON (b.blog_id = t.blog_id)
		WHERE t.blog_id IN ({array_int:blogs})", __FILE__, __LINE__,
		array(
			'blogs' => $blogs
		)
	);
	$use_safe_guard = array();
	$blog_names = array();
	while ($row = $zcFunc['db_fetch_assoc']($request))
	{
		$use_safe_guard[$row['blog_id']] = true;
		$blog_names[$row['blog_id']] = $zcFunc['un_htmlspecialchars']($row['name']);
	}
	$zcFunc['db_free_result']($request);
	
	// now check delete confirmation for each of the blogs we are trying to delete
	$requires_confirmation = array();
	foreach ($blogs as $blog)
	{
		if (empty($use_safe_guard[$blog]))
		{
			// doesn't require confirmation
		}
		elseif (!empty($use_safe_guard[$blog]) && isset($_REQUEST['safeGuard']))
		{
			// it requires confirmation and safeGuard has been set... has it been confirmed already?
			// if the safeGuard isn't set properly, it still requires confirmation
			if (!isset($_SESSION['zc_safe_guard']) || $_REQUEST['safeGuard'] != $_SESSION['zc_safe_guard'])
				$requires_confirmation[] = $blog;
		}
		else
			$requires_confirmation[] = $blog;
	}
			
	// exclude the blogs that still require confirmation
	$blogs = array_diff($blogs, $requires_confirmation);
	
	// are there blogs we can delete right now?
	if (!empty($blogs))
	{
		// nice function that handles everything we need here
		zcDeleteBlogs($blogs);
		
		// if there weren't any blogs that still required confirmation... we can redirect to Main
		if (empty($requires_confirmation))
			zc_redirect_exit($context['zc']['zc_request'] . $context['zc']['u_request']);
	}
	
	if (!empty($requires_confirmation))
	{
		$context['zc']['requires_confirmation'] = $requires_confirmation;
		$context['blog_control_panel'] = false;
	
		$this_or_these = count($context['zc']['requires_confirmation']) == 1 ? $txt['b68'] : $txt['b69'];

		$context['page_title'] = $txt['b278'];
		$context['zc']['blog_names'] = $blog_names;
		$context['zc']['confirm_text'] = sprintf($txt['b106'], $this_or_these, $this_or_these, $this_or_these);
		$context['zc']['sub_sub_template'] = 'sandwich';
		$context['zc']['sandwich_inner_template'] = 'confirmation_page';
		
		$_SESSION['zc_safe_guard'] = md5(mt_rand() . time());
		$context['zc']['confirm_href'] = $context['zc']['base_bcp_url'] . $context['zc']['u_request'] .';sa=deleteBlog;zcDeleteBlog=' . urlencode(implode(',', $context['zc']['requires_confirmation'])) . ';safeGuard=' . $_SESSION['zc_safe_guard'] . ';sesc=' . $context['session_id'];
		
		$context['zc']['cancel_href'] = $scripturl . '?zc=bcp;u='. $context['zc']['cp_owner']['id'];
	}
}

// be aware that this does NOT verify ownership of each blog... should do that before calling this function
function zcDeleteBlogs($blogs)
{
	global $txt, $context;
	global $zcFunc, $zc;
	
	// definitely should check the session
	checkSession('get');
	
	// are they allowed to delete blogs?
	if (!$context['user']['is_admin'] && !$context['can_delete_own_blogs'])
		zc_fatal_error(array('zc_error_17', 'b208'));
	
	// if empty, there's nothing to do...
	if (empty($blogs))
		return;
		
	// must be an array!
	if (!is_array($blogs))
		$blogs = array($blogs);
	
	// get all polls associated with this blog...
	$request = $zcFunc['db_query']("
		SELECT poll_id
		FROM {db_prefix}polls
		WHERE blog_id IN ({array_int:blogs})", __FILE__, __LINE__,
		array(
			'blogs' => $blogs
		)
	);
	$polls = array();
	while ($row = $zcFunc['db_fetch_assoc']($request))
		$polls[] = $row['poll_id'];
	$zcFunc['db_free_result']($request);
	
	// delete polls
	$zcFunc['db_query']("
		DELETE FROM {db_prefix}polls
		WHERE blog_id IN ({array_int:blogs})", __FILE__, __LINE__,
		array(
			'blogs' => $blogs
		)
	);
	
	// delete poll choices
	if (!empty($polls))
		$zcFunc['db_query']("
			DELETE FROM {db_prefix}poll_choices
			WHERE poll_id IN ({array_int:polls})", __FILE__, __LINE__,
			array(
				'polls' => $polls
			)
		);
	
	// delete poll logs
	if (!empty($polls))
		$zcFunc['db_query']("
			DELETE FROM {db_prefix}log_polls
			WHERE poll_id IN ({array_int:polls})", __FILE__, __LINE__,
			array(
				'polls' => $polls
			)
		);
	
	// delete notify logs...
	$zcFunc['db_query']("
		DELETE FROM {db_prefix}log_notify
		WHERE blog_id IN ({array_int:blogs})", __FILE__, __LINE__,
		array(
			'blogs' => $blogs
		)
	);
		
	// delete blog logs...
	$zcFunc['db_query']("
		DELETE FROM {db_prefix}log_blogs
		WHERE blog_id IN ({array_int:blogs})", __FILE__, __LINE__,
		array(
			'blogs' => $blogs
		)
	);
		
	// get articles in these blogs...
	$request = $zcFunc['db_query']("
		SELECT article_id
		FROM {db_prefix}articles
		WHERE blog_id IN ({array_int:blogs})", __FILE__, __LINE__,
		array(
			'blogs' => $blogs
		)
	);
	$articles = array();
	while ($row = $zcFunc['db_fetch_assoc']($request))
		$articles[] = $row['article_id'];
	$zcFunc['db_free_result']($request);
		
	// delete these articles' logs...
	if (!empty($articles))
		$zcFunc['db_query']("
			DELETE FROM {db_prefix}log_articles
			WHERE article_id IN ({array_int:articles})", __FILE__, __LINE__,
			array(
				'articles' => $articles
			)
		);
	
	// delete articles
	$zcFunc['db_query']("
		DELETE FROM {db_prefix}articles
		WHERE blog_id IN ({array_int:blogs})", __FILE__, __LINE__,
		array(
			'blogs' => $blogs
		)
	);
	
	// delete comments
	$zcFunc['db_query']("
		DELETE FROM {db_prefix}comments
		WHERE blog_id IN ({array_int:blogs})", __FILE__, __LINE__,
		array(
			'blogs' => $blogs
		)
	);
	
	// delete tags
	$zcFunc['db_query']("
		DELETE FROM {db_prefix}tags
		WHERE blog_id IN ({array_int:blogs})", __FILE__, __LINE__,
		array(
			'blogs' => $blogs
		)
	);
	
	// delete categories
	$zcFunc['db_query']("
		DELETE FROM {db_prefix}categories
		WHERE blog_id IN ({array_int:blogs})", __FILE__, __LINE__,
		array(
			'blogs' => $blogs
		)
	);
	
	// delete settings
	$zcFunc['db_query']("
		DELETE FROM {db_prefix}settings
		WHERE blog_id IN ({array_int:blogs})", __FILE__, __LINE__,
		array(
			'blogs' => $blogs
		)
	);
	
	// delete plugin settings
	$zcFunc['db_query']("
		DELETE FROM {db_prefix}plugin_settings
		WHERE blog_id IN ({array_int:blogs})", __FILE__, __LINE__,
		array(
			'blogs' => $blogs
		)
	);
	
	// delete theme settings
	$zcFunc['db_query']("
		DELETE FROM {db_prefix}theme_settings
		WHERE blog_id IN ({array_int:blogs})", __FILE__, __LINE__,
		array(
			'blogs' => $blogs
		)
	);
	
	// delete custom windows
	$zcFunc['db_query']("
		DELETE FROM {db_prefix}custom_windows
		WHERE blog_id IN ({array_int:blogs})", __FILE__, __LINE__,
		array(
			'blogs' => $blogs
		)
	);
	
	// delete blogs
	$zcFunc['db_query']("
		DELETE FROM {db_prefix}blogs
		WHERE blog_id IN ({array_int:blogs})", __FILE__, __LINE__,
		array(
			'blogs' => $blogs
		)
	);
	
	$zc['settings']['community_total_blogs'] = $zc['settings']['community_total_blogs'] > count($blogs) ? $zc['settings']['community_total_blogs'] - count($blogs) : 0;
	zcUpdateGlobalSettings(array('community_total_blogs' => $zc['settings']['community_total_blogs']));
}
/*
function LockBlogs()
{
	global $context, $txt;
	global $blog, $zcFunc;
	
	checkSession('get');
	
	// can they even do this?
	if (!$context['can_lock_blogs'])
		zc_fatal_error(array('zc_error_17', 'b330'));
	
	$blogs = array();
	if (!empty($blog))
		$blogs = array($blog);
	elseif (!empty($_POST['blogs']))
	{
		foreach ($_POST['blogs'] as $id)
			if (!empty($id))
				$blogs[] = (int) $id;
	}
	
	// nothing to do...
	if (empty($blogs))
		if (empty($context['zc']['using_adv_moderation']))
			zcReturnToOrigin();
		else
			return false;
			
	// alrighty... let's do some locking
	$zcFunc['db_query']("
		UPDATE {db_prefix}blogs
		SET locked = 1
		WHERE blog_id IN ({array_int:blogs})
			AND locked = 0
		LIMIT {int:limit}", __FILE__, __LINE__,
		array(
			'limit' => count($blogs),
			'blogs' => $blogs
		)
	);
			
	// redirect back to wherever they came from...
	if (empty($context['zc']['using_adv_moderation']))
		zcReturnToOrigin();
	else
		return true;
}

function UnLockBlogs()
{
	global $context, $txt;
	global $blog, $zcFunc;
	
	checkSession('get');
	
	// can they even do this?
	if (!$context['can_lock_blogs'])
		zc_fatal_error(array('zc_error_17', 'b330'));
	
	$blogs = array();
	if (!empty($blog))
		$blogs = array($blog);
	elseif (!empty($_POST['blogs']))
	{
		foreach ($_POST['blogs'] as $id)
			if (!empty($id))
				$blogs[] = (int) $id;
	}
	
	// nothing to do...
	if (empty($blogs))
		if (empty($context['zc']['using_adv_moderation']))
			zcReturnToOrigin();
		else
			return false;
		
	// now for the unlocking
	$zcFunc['db_query']("
		UPDATE {db_prefix}blogs
		SET locked = 0
		WHERE blog_id IN ({array_int:blogs})
			AND locked = 1
		LIMIT {int:limit}", __FILE__, __LINE__,
		array(
			'limit' => count($blogs),
			'blogs' => $blogs
		)
	);
			
	// redirect back to wherever they came from...
	if (empty($context['zc']['using_adv_moderation']))
		zcReturnToOrigin();
	else
		return true;
}*/

function zc_change_blog_owner($blog, $new_owner)
{
	global $context;
	global $zcFunc;
	
	// can they change blog ownership?
	if (!zc_check_permissions('change_blog_ownership'))
		zc_fatal_error('zc_error_52');
	
	if (empty($blog) || empty($new_owner))
		return false;
		
	$new_owner = addslashes($zcFunc['htmlspecialchars']((string) $new_owner, ENT_QUOTES));
	
	// make sure this is a real member....
	$request = $zcFunc['db_query']("
		SELECT {tbl:members::column:id_member} AS id_member
		FROM {db_prefix}{table:members}
		WHERE {tbl:members::column:real_name} = {string:new_owner_name}
			OR {tbl:members::column:member_name} = {string:new_owner_name}
		LIMIT 1", __FILE__, __LINE__,
		array(
			'new_owner_name' => $new_owner
		)
	);
		
	// not a real member...
	if ($zcFunc['db_num_rows']($request) == 0)
		$context['zc']['errors']['change_owner'] = 'zc_error_62';
	// aight we found 'em...
	else
	{
		$row = $zcFunc['db_fetch_assoc']($request);
		if (!empty($row['id_member']))
			$zcFunc['db_query']("
				UPDATE {db_prefix}blogs
				SET blog_owner = {int:blog_owner_id}
				WHERE blog_id = {int:blog_id}
				LIMIT 1", __FILE__, __LINE__,
				array(
					'blog_id' => (int) $blog,
					'blog_owner_id' => $row['id_member']
				)
			);
		else
			$context['zc']['errors']['change_owner'] = 'zc_error_62';
	}
	
	// redirect to new owner's blog control panel if there were no errors
	if (empty($context['zc']['errors']))
	{
		$_SESSION['zc_success_msg'] = 'zc_success_4';
		zc_redirect_exit('zc=bcp;u=' . $row['id_member'] . ';blog=' . $blog . '.0');
	}
}

function zc_get_list_of_blogs($blogs = null, $query = null, $num_blogs = null)
{
	global $zcFunc, $zc, $context, $scripturl, $modSettings, $txt;
	
	if ((empty($blogs) || !is_array($blogs)) && empty($query))
		return false;
	elseif (!empty($query) && !empty($num_blogs))
		$query = (string) $query;
	elseif (!empty($blogs))
		$blogs = array_unique($blogs);
		
	$num_blogs = !empty($blogs) ? count($blogs) : $num_blogs;
	$list_of_blogs = array();
	$list_info = array();
	
	if (!empty($context['blog_control_panel']))
	{
		$list_info['list_empty_txt'] = ($context['user']['is_admin'] && !$context['is_cp_owner'] ? $txt['b54'] : $txt['b53']);
		$list_info['title'] = !$context['is_cp_owner'] ? $context['zc']['cp_owner']['name'] . '\'s ' . $txt['b1a'] : $txt['b516'];
		
		if (!empty($context['can_delete_blogs']))
		{
			$list_info['form_url'] = $scripturl . zcRequestVarsToString(null, '?') .';sesc='. $context['session_id'] .';sa=deleteBlog';
			$list_info['confirm_submit_txt'] = sprintf($txt['b71'], $txt['b1']);
		}
	}
	
	$zcRequests = '';
	if (empty($context['zc']['zCommunity_is_home']))
		$zcRequests .= '?zc';
		
	if (!empty($context['blog_control_panel']))
		$zcRequests .= '?zc=bcp' . $context['zc']['u_request'] . $context['zc']['sa_request'] . $context['zc']['blogStart_request'];
		
	if (!empty($_REQUEST['listStart']))
		$zcRequests .= (!empty($zcRequests) ? '?' : ';') . 'listStart=' . $_REQUEST['listStart'];

	$maxindex = isset($_REQUEST['all']) && !empty($zc['settings']['allow_show_all_link']) ? 99999 : (!empty($zc['settings']['maxIndexOnBlogIndex']) ? $zc['settings']['maxIndexOnBlogIndex'] : 20);
	
	$start = isset($_REQUEST['listStart']) ? (int) $_REQUEST['listStart'] : 0;
	$list_info['show_page_index'] = !empty($num_blogs) && $num_blogs > $maxindex;
	
	if ($list_info['show_page_index'])
		$list_info['page_index'] = zcConstructPageIndex($scripturl . '?listStart=%d' . zcRequestVarsToString('all,listStart', ';'), $start, $num_blogs, $maxindex, true);

	// Default sort methods.
	$sort_methods = array(
		'name' => 'b.name',
		'articles' => 'b.num_articles',
		'comments' => 'b.num_comments',
		'views' => 'b.num_views',
		'last_article' => 'b.last_article_id',
	);
	
	if (empty($context['blog_control_panel']))
		unset($sort_methods['comments']);

	if (!isset($_REQUEST['sort']) || !isset($sort_methods[$_REQUEST['sort']]))
	{
		$sort_by = !empty($zc['settings']['blogIndexDefaultSort']) ? $zc['settings']['blogIndexDefaultSort'] : 'views';
		$sort = !empty($zc['settings']['blogIndexDefaultSort']) && !empty($sort_methods[$zc['settings']['blogIndexDefaultSort']]) ? $sort_methods[$zc['settings']['blogIndexDefaultSort']] : 'b.num_views';
		$ascending = !isset($_REQUEST['asc']) && !isset($_REQUEST['desc']) ? !empty($zc['settings']['blogIndexSortAscending']) : isset($_REQUEST['asc']);
	}
	else
	{
		$sort_by = $_REQUEST['sort'];
		$sort = $sort_methods[$_REQUEST['sort']];
		$ascending = !isset($_REQUEST['asc']) && !isset($_REQUEST['desc']) ? !empty($zc['settings']['blogIndexSortAscending']) : isset($_REQUEST['asc']);
	}

	// make array of table header info
	$tableHeaders = array(
		'url_requests' => $zcRequests,
		'headers' => array(
			'avatar' => array('label' => ''),
			'name' => array('label' => $txt['b3025']),
			'articles' => array('label' => $txt['b66a']),
			'comments' => array('label' => $txt['b15a']),
			'views' => array('label' => $txt['b3027']),
			'last_article' => array('label' => $txt['b233']),
		),
		'sort_direction' => $ascending ? 'up' : 'down',
		'sort_by' => $sort_by,
	);

	$list_info += array(
		'alignment' => array(
			'avatar' => 'center',
			'name' => 'left',
			'articles' => 'center',
			'comments' => 'center',
			'views' => 'center',
			'last_article' => 'left',
		),
	);
	
	if (empty($context['blog_control_panel']))
		unset($list_info['alignment']['comments'], $tableHeaders['headers']['comments']);
	
	if (!empty($zc['settings']['showRSSFeedsOnBlogIndex']) && empty($context['blog_control_panel']))
		$tableHeaders['headers']['rss_link'] = array('label' => '');
	
	// create the table headers
	$list_info['table_headers'] = zcCreateTableHeaders($tableHeaders);
	
	if (!empty($context['blog_control_panel']) && !empty($context['can_delete_blogs']))
		$list_info['table_headers']['checkbox'] = '<input type="checkbox" onclick="invertAll(this, this.form, \'items[]\');" class="check" />';
		
	/*" . (!empty($zc['settings']['showAvatarsOnBlogIndex']) ? ", 
			IFNULL(a.{tbl:attachments::column:id_attach}, 0) AS id_attach, a.{tbl:attachments::column:filename} AS filename, a.{tbl:attachments::column:attachment_type} AS attachment_type, a.{tbl:attachments::column:width} AS avatar_width, a.{tbl:attachments::column:height} AS avatar_height" : '') . "
			" . (!empty($zc['settings']['showAvatarsOnBlogIndex']) ? "
			LEFT JOIN {db_prefix}{table:attachments} AS a ON (a.{tbl:attachments::column:id_member} = b.blog_owner)" : '') . "*/

	// get information about all of the blogs that this user can see
	$request = $zcFunc['db_query']("
		SELECT 
			b.num_comments, b.num_articles, b.num_unapproved_articles, b.num_unapproved_comments, b.blog_id, b.blog_owner, b.description, b.name AS blog_name, b.num_views, b.last_article_id,
			mem1.{tbl:members::column:id_member} AS owner_id, mem1.{tbl:members::column:real_name} AS owner_name, mem1.{tbl:members::column:avatar} AS avatar, IFNULL(mem2.{tbl:members::column:real_name}, t.poster_name) AS poster_name,
			t.poster_id, t.article_id, t.subject" . (!empty($zc['settings']['show_vpreview_on_lists']) ? ", t.body" : '') . ", t.smileys_enabled, t.posted_time" . (!$context['user']['is_admin'] ? ", t.access_restrict, t.users_allowed" : '') . ", t.approved,
			bs.articles_require_approval, bs.comments_require_approval, bs.blog_avatar
		FROM {db_prefix}blogs AS b
			LEFT JOIN {db_prefix}{table:members} AS mem1 ON (mem1.{tbl:members::column:id_member} = b.blog_owner)
			LEFT JOIN {db_prefix}articles AS t ON (t.article_id = b.last_article_id)
			LEFT JOIN {db_prefix}settings AS bs ON (bs.blog_id = b.blog_id)
			LEFT JOIN {db_prefix}{table:members} AS mem2 ON (mem2.{tbl:members::column:id_member} = t.poster_id)
		WHERE " . (!empty($blogs) ? "b.blog_id IN ({array_int:blogs})" : $query) . "
		ORDER BY {raw:sort}" . ($ascending ? '' : ' DESC') . "
		LIMIT {int:start}, {int:maxindex}", __FILE__, __LINE__,
		array(
			'sort' => $sort,
			'blogs' => $blogs,
			'start' => $start,
			'maxindex' => $maxindex
		)
	);
	
	if ($zcFunc['db_num_rows']($request) > 0)
	{
		$avatars = array();
		while ($row = $zcFunc['db_fetch_assoc']($request))
		{
			$row['blog_name'] = strip_tags($zcFunc['un_htmlspecialchars']($row['blog_name']));
			$row['description'] = strip_tags($zcFunc['un_htmlspecialchars']($row['description']));
			
			if (empty($row['last_article_id']))
			{
				$row['poster_id'] = 0;
				$row['poster_name'] = '';
				$row['posted_time'] = 0;
			}
			
			$user_started = $row['poster_id'] == $context['user']['id'] && !$context['user']['is_guest'];
		
			// is the last article access restricted?
			if (!empty($row['access_restrict']) && !$context['user']['is_admin'] && !$user_started)
			{
				$access_info = array(
					'access_restrict' => $row['access_restrict'],
					'users_allowed' => $row['users_allowed'],
					'poster_id' => $row['poster_id']
				);
				
				$can_see = zc_can_see_article($access_info);
					
				// they cannot see the last article... so let's find the last one that they CAN see
				if (!$can_see)
				{
					$max_attempts = 5;
					for ($m = 1; $m <= $max_attempts; $m++)
					{
						$request2 = $zcFunc['db_query']("
							SELECT t.article_id, t.subject, t.posted_time, t.poster_id, IFNULL(mem.{tbl:members::column:real_name}, t.poster_name) AS poster_name, t.approved, t.access_restrict, t.users_allowed" . (!empty($zc['settings']['show_vpreview_on_lists']) ? ", t.body" : '') . "
							FROM {db_prefix}articles AS t
								LEFT JOIN {db_prefix}{table:members} AS mem ON (mem.{tbl:members::column:id_member} = t.poster_id)
							WHERE t.blog_id = {int:blog_id}
								AND (t.access_restrict != 1" . (!$context['user']['is_guest'] ? " OR t.poster_id = {int:user_id}" : '') . ")" . (!empty($row['articles_require_approval']) ? "
								AND t.approved = 1" : '') . "
							ORDER BY t.article_id DESC
							LIMIT 1", __FILE__, __LINE__,
							array(
								'blog_id' => $row['blog_id'],
								'user_id' => $context['user']['id']
							)
						);
						if ($zcFunc['db_num_rows']($request2) > 0)
						{
							$row2 = $zcFunc['db_fetch_assoc']($request2);
							$access_info = array(
								'access_restrict' => $row2['access_restrict'],
								'users_allowed' => $row2['users_allowed'],
								'poster_id' => $row2['poster_id']
							);
							
							$can_see = zc_can_see_article($access_info);
							
							if ($can_see)
								$m = $max_attempts + 1;
						}
						// stop looking....
						else
						{
							$m = $max_attempts + 1;
							if (isset($row2))
								unset($row2);
						}
						$zcFunc['db_free_result']($request2);
					}
			
					// redo the last article related info in $row
					$row['body'] = !empty($row2['body']) ? $row2['body'] : '';
					$row['subject'] = !empty($row2['subject']) ? $row2['subject'] : '';
					$row['posted_time'] = !empty($row2['posted_time']) ? $row2['posted_time'] : '';
					$row['poster_id'] = !empty($row2['poster_id']) ? $row2['poster_id'] : '';
					$row['poster_name'] = !empty($row2['subject']) ? $row2['poster_name'] : '';
					$row['article_id'] = !empty($row2['article_id']) ? $row2['article_id'] : '';
					$row['approved'] = !empty($row2['approved']) ? $row2['approved'] : '';
				}
			}
			
			// does this blog have "articles require approval" enabled and is the last article not approved?
			if (!empty($row['articles_require_approval']) && empty($row['approved']))
			{
				// get the last APPROVED article...
				$request2 = $zcFunc['db_query']("
					SELECT t.article_id, t.subject, t.posted_time, t.poster_id, IFNULL(mem.{tbl:members::column:real_name}, t.poster_name) AS poster_name" . (!empty($zc['settings']['show_vpreview_on_lists']) ? ", t.body" : '') . "
					FROM {db_prefix}articles AS t
						LEFT JOIN {db_prefix}{table:members} AS mem ON (mem.{tbl:members::column:id_member} = t.poster_id)
					WHERE t.approved = 1
						AND t.blog_id = {int:blog_id}
					ORDER BY t.article_id DESC
					LIMIT 1", __FILE__, __LINE__,
					array(
						'blog_id' => $row['blog_id']
					)
				);
				if ($zcFunc['db_num_rows']($request2) > 0)
					$row2 = $zcFunc['db_fetch_assoc']($request2);
				$zcFunc['db_free_result']($request2);
			
				// redo the last article related info in $row
				$row['body'] = !empty($row2['body']) ? $row2['body'] : '';
				$row['subject'] = !empty($row2['subject']) ? $row2['subject'] : '';
				$row['posted_time'] = !empty($row2['posted_time']) ? $row2['posted_time'] : '';
				$row['poster_id'] = !empty($row2['poster_id']) ? $row2['poster_id'] : '';
				$row['poster_name'] = !empty($row2['subject']) ? $row2['poster_name'] : '';
				$row['article_id'] = !empty($row2['article_id']) ? $row2['article_id'] : '';
				$row['approved'] = !empty($row2['approved']) ? $row2['approved'] : '';
			}
			
			if (!empty($row['subject']))
			{
				$row['subject'] = strip_tags($zcFunc['un_htmlspecialchars']($row['subject']));
				zc_censor_text($row['subject']);
			}
			
			if (!empty($row['body']))
			{
				$row['body'] = $zcFunc['un_htmlspecialchars']($row['body']);
				$row['body'] = $zcFunc['parse_bbc']($row['body'], $row['smileys_enabled']);
				zc_censor_text($row['body']);
			}
			// load the blog owner's avatar if it hasn't already been loaded
			if (!empty($zc['settings']['showAvatarsOnBlogIndex']))
			{
				// figure out the filename
				/*if ($row['avatar'] == '')
				{
					if ($row['id_attach'] > 0)
					{
						if (empty($row['attachment_type']))
							$filename = $scripturl . '?action=dlattach;attach=' . $row['id_attach'] . ';type=avatar';
						else
							$filename = $modSettings['custom_avatar_url'] . '/' . $row['filename'];
					}
					else
						$filename = '';
				}
				elseif (stristr($row['avatar'], 'http://'))
					$filename = $row['avatar'];
				else
					$filename = $modSettings['avatar_url'] . '/' . $zcFunc['htmlspecialchars']($row['avatar']);*/
						
				//$current_width = $row['avatar_width'];
				//$current_height = $row['avatar_height'];
					
				if (!empty($row['blog_avatar']))
				{
					list($avatar_width, $avatar_height) = zcResizeImage($zc['settings']['attachments_url'] . '/' . $row['blog_avatar'], ($zc['settings']['blog_index_max_avatar_width'] > 65 ? 65 : $zc['settings']['blog_index_max_avatar_width']), ($zc['settings']['blog_index_max_avatar_height'] > 50 ? 50 : $zc['settings']['blog_index_max_avatar_height']));
					
					$avatar_width = ' width="' . $avatar_width . '"';
					$avatar_height = ' height="' . $avatar_height . '"';
					
					$avatar = '<img src="' . $zc['settings']['attachments_url'] . '/' . $zcFunc['htmlspecialchars']($row['blog_avatar']) . '"' . $avatar_width . $avatar_height . ' alt="" />';
				}
				else
				{
					if (empty($default_blog_avatar))
					{
						if (!empty($zc['settings']['default_blog_avatar']))
							$default_avatar_url = $zc['settings']['attachments_url'] . '/' . $zc['settings']['default_blog_avatar'];
						else
							$default_avatar_url = $context['zc']['default_images_url'] . '/defaultAvatar.png';
							
						list($avatar_width, $avatar_height) = zcResizeImage($default_avatar_url, ($zc['settings']['blog_index_max_avatar_width'] > 65 ? 65 : $zc['settings']['blog_index_max_avatar_width']), ($zc['settings']['blog_index_max_avatar_height'] > 50 ? 50 : $zc['settings']['blog_index_max_avatar_height']));
						
						$avatar_width = ' width="' . $avatar_width . '"';
						$avatar_height = ' height="' . $avatar_height . '"';
						$default_blog_avatar = '<img src="' . $default_avatar_url . '"' . $avatar_width . $avatar_height . ' alt="" />';
					}
					$avatar = $default_blog_avatar;
				}
			}
			
			$num_articles = !empty($row['articles_require_approval']) ? $row['num_articles'] - $row['num_unapproved_articles'] : $row['num_articles'];
			$num_comments = !empty($row['comments_require_approval']) ? $row['num_comments'] - $row['num_unapproved_comments'] : $row['num_comments'];
			
			$list_of_blogs[$row['blog_id']] = array();
			$list_of_blogs[$row['blog_id']]['avatar'] = !empty($zc['settings']['showAvatarsOnBlogIndex']) && !empty($avatar) ? $avatar : '';
					
			$list_of_blogs[$row['blog_id']] += array(
				'name' => '<a href="' . $scripturl . '?blog=' . $row['blog_id'] . '.0"' . (!empty($row['description']) ? ' title="' . $row['description'] . '"' : '') . '>' . $row['blog_name'] . '</a>',
				'articles' => comma_format($num_articles)
			);
			
			if (!empty($context['blog_control_panel']))
			{
				if (!empty($row['articles_require_approval']))
					$list_of_blogs[$row['blog_id']]['articles'] .= ' <span class="light_text" title="' . sprintf($txt['b668'], $row['num_unapproved_articles'], ($row['num_unapproved_articles'] == 1 ? $txt['b170'] : $txt['b129'])) . '">(' . $row['num_unapproved_articles'] . ')</span>';
			
				$list_of_blogs[$row['blog_id']]['comments'] = comma_format($num_comments);
					
				if (!empty($row['comments_require_approval']))
					$list_of_blogs[$row['blog_id']]['comments'] .= ' <span class="light_text" title="' . sprintf($txt['b668'], $row['num_unapproved_comments'], ($row['num_unapproved_comments'] == 1 ? $txt['b171'] : $txt['b130'])) . '">(' . $row['num_unapproved_comments'] . ')</span>';
			}
			
			$list_of_blogs[$row['blog_id']] += array(
				'views' => comma_format($row['num_views']),
				'last_article' => !empty($row['subject']) ? '<b><a href="' . $scripturl . '?article=' . $row['article_id'] . '.0">' . $row['subject'] . '</a></b><br /><span class="smalltext">' . $txt['b3045'] . ' ' . (!empty($row['poster_id']) ? sprintf($context['zc']['link_templates']['user_profile'], $row['poster_id'], $row['poster_name'], ' title="' . $txt['b41'] . '"') : $row['poster_name']) . '</span><br /><span class="smalltext">' . $txt['b3034'] . ' ' . timeformat($row['posted_time'], false) . '</span>' : ''
			);
			
			if (!empty($zc['settings']['showRSSFeedsOnBlogIndex']) && empty($context['blog_control_panel']))
				$list_of_blogs[$row['blog_id']]['rss_link'] = '<a href="' . $scripturl . '?zc=.xml;blog=' . $row['blog_id'] . '.0;type=rss" title="' . sprintf($txt['b277'], ' ') . '"><span class="small_rss_icon">&nbsp;</span></a>';
				
			if (!empty($context['blog_control_panel']) && !empty($context['can_delete_blogs']))
				$list_of_blogs[$row['blog_id']]['checkbox'] = '<input type="checkbox" name="items[]" value="'. $row['blog_id'] .'" class="check" />';
			
			if (!empty($zc['settings']['show_vpreview_on_lists']) && !empty($list_of_blogs[$row['blog_id']]['last_article']))
				$list_of_blogs[$row['blog_id']]['last_article'] .= '<br /><span class="hoverBoxActivator" onclick="document.getElementById(\'preview_' . $row['article_id'] . '\').style.display = \'block\';">' . $txt['b306'] . '</span><div class="hoverBox" id="preview_' . $row['article_id'] . '" style="display:none; margin-top:3px;"><div class="hoverBoxHeader"><span class="hoverBoxClose" onmouseup="document.getElementById(\'preview_' . $row['article_id'] .'\').style.display = \'none\';" title="' . $txt['b305'] . '">X</span>&nbsp;&nbsp;<span class="hoverBoxTitle">' . $txt['b159'] . '</span></div><div class="hoverBoxBody" style="line-height:135%;"><table width="100%" cellspacing="0" cellpadding="0" border="0" style="table-layout:fixed;"><tr class="noPadding"><td><div style="width:100%; overflow:auto;">' . (zcTruncateText(strip_tags($row['body'], '<br><a>'), $zc['settings']['max_length_preview_popups'], ' ', 40, $txt['b31a'], $scripturl . '?article='. $row['article_id'] .'.0', $txt['b31'])) . '</div></td></tr></table></div></div>';
		}
		$zcFunc['db_free_result']($request);
	}

	return array($list_of_blogs, $list_info);
}

?>