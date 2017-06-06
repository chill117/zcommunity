<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
function zc_prepare_community_page_sw_array()
{
	global $scripturl, $context;
	return array(
		'_info_' => array(
			'hidden_form_values' => array('save_communityPage' => 1),
			'form_url' => $scripturl . zcRequestVarsToString(null, '?') . ';sesc=' . $context['session_id'],
		),
		'community_page_side_bar' => array(
			'type' => 'int',
			'value' => 0,
			'custom' => 'select',
			'options' => array(
				'b84',
				'b10',
				'b9',
			),
			'label' => 'b301',
			'header_above' => 'b301',
		),
		'windows_inner_alignment' => array(
			'value' => 'center',
			'type' => 'text',
			'label' => 'b547',
			'custom' => 'select',
			'options' => array(
				'left' => 'b10',
				'center' => 'b82',
				'right' => 'b9',
			),
		),
		'enableGlobalTagsWindow' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b501', 'b26a'),
		),
		'max_tags_in_globaltag_window' => array(
			'type' => 'int',
			'value' => 48,
			'label' => 'b262',
			'subtext' => 'b263',
		),
		'enableGlobalMostRecentWindow' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b501', 'b503'),
		),
		'enableGlobalArchivesWindow' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b501', 'b22'),
		),
	);
}

function zc_prepare_blog_index_settings_array()
{
	global $context, $txt, $scripturl;
	$context['zc']['bi_settings'] = array(
		'_info_' => array(
			'hidden_form_values' => array('save_communityPage' => 1),
			'form_url' => $scripturl . zcRequestVarsToString(null, '?') .';sesc='. $context['session_id'],
		),
		'globalArticleListDefaultSort' => array(
			'type' => 'text',
			'value' => 'date',
			'custom' => 'select',
			'options' => array(
				'subject' => 'b3032',
				'blog' => 'b3003',
				'comments' => 'b15a',
				'views' => 'b3027',
				'author' => 'b3033',
				'date' => 'b30',
			),
			'label' => 'b397',
		),
		'globalArticleListSortAscending' => array(
			'type' => 'int',
			'value' => 0,
			'custom' => 'select',
			'options' => array(
				'b94',
				'b95',
			),
			'label' => 'b398',
		),
		'community_page_without_layers' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b325',
		),
		'community_page_width' => array(
			'type' => 'text',
			'value' => '80%',
			'subtext' => 'b327',
			'instructions' => 'b328',
			'label' => 'b326',
		),
		'community_page_alignment' => array(
			'type' => 'text',
			'custom' => 'select',
			'value' => 'center',
			'options' => array(
				'center' => 'b82',
				'left' => 'b10',
				'right' => 'b9',
			),
			'label' => 'b329',
		),
		'community_page_meta_keywords' => array(
			'value' => '',
			'type' => 'text',
			'label' => 'b418',
			'custom' => 'textarea',
			'ta_rows' => 5,
			'helptext' => 'zc_help_9',
			'header_above' => 'b552',
		),
		'community_page_meta_description' => array(
			'value' => '',
			'type' => 'text',
			'label' => 'b553',
			'custom' => 'textarea',
			'ta_rows' => 5,
		),
		'enable_blog_index_block' => array(
			'type' => 'check',
			'value' => 1,
			'label' => 'b348',
			'header_above' => 'b347',
		),
		'blogIndexDefaultSort' => array(
			'type' => 'text',
			'value' => 'views',
			'custom' => 'select',
			'options' => array(
				'name' => 'b3030',
				'articles' => 'b66a',
				'comments' => 'b15a',
				'views' => 'b3027',
				'last_article' => 'b233',
			),
			'label' => 'b394',
		),
		'blogIndexSortAscending' => array(
			'type' => 'int',
			'value' => 0,
			'custom' => 'select',
			'options' => array(
				'b94',
				'b95',
			),
			'label' => 'b393',
		),
		'showAvatarsOnBlogIndex' => array(
			'type' => 'check',
			'value' => 1,
			'label' => 'b461',
		),
		'blog_index_max_avatar_width' => array(
			'type' => 'int',
			'value' => 48,
			'label' => array('b258', 'b260'),
			'subtext' => 'b354',
		),
		'blog_index_max_avatar_height' => array(
			'type' => 'int',
			'value' => 48,
			'label' => array('b258', 'b261'),
			'subtext' => $txt['b354'],
		),
		'showRSSFeedsOnBlogIndex' => array(
			'type' => 'check',
			'value' => 0,
			'subtext' => 'b463',
			'label' => 'b462',
		),
		'maxIndexOnBlogIndex' => array(
			'type' => 'int',
			'value' => 20,
			'subtext' => 'b429',
			'label' => 'b402',
		),
		'enable_news_block' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b349',
		),
		'news_block_max_num' => array(
			'type' => 'int',
			'value' => 1,
			'label' => 'b356',
			'subtext' => 'b429',
		),
		'news_block_max_length' => array(
			'type' => 'int',
			'value' => 400,
			'label' => 'b355',
			'subtext' => 'b429',
		),
		'news_block_show_last_edit' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b425', 'b350'),
		),
		'show_socialbookmarks_news' => array(
			'type' => 'check',
			'value' => 1,
			'label' => 'b360',
		),
		'news_socialbookmarks_multicheck' => array(
			'value' => 'digg,delicious,furl,stumbleupon,technorati',
			'type' => 'text',
			'custom' => 'multi_check',
			'needs_explode' => true,
			'instructions' => 'b432',
			'options' => array(),
		),
	);
	
	// get info about the social bookmarks we have available...
	zc_prepare_bookmarking_options_array();
	
	// populate options array of socialbookmarks_multicheck
	if (!empty($context['blog_control_panel']) && !empty($context['zc']['bookmarking_options']))
		foreach ($context['zc']['bookmarking_options'] as $site => $array)
			$context['zc']['bi_settings']['news_socialbookmarks_multicheck']['options'][$site] = '<a href="' . $array['site_home'] . '" rel="nofollow" target="_blank">&raquo;</a>&nbsp;' . $array['name'];
}

function zc_prepare_n_preferences_array()
{
	global $context, $txt, $scripturl;
	$context['zc']['n_preferences'] = array(
		'_info_' => array(
			'hidden_form_values' => array('save_notifications' => 1),
			'form_url' => $scripturl . zcRequestVarsToString(null, '?') . ';sesc=' . $context['session_id'],
		),
		'send_body_with_notifications' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b255',
		),
		'notify_once' => array(
			'type' => 'check',
			'value' => 1,
			'label' => 'b256',
		),
		'report_to_mod_notices' => array(
			'type' => 'int',
			'value' => 2,
			'custom' => 'select',
			'options' => array(
				'b283',
				'b284',
				'b285',
			),
			'label' => 'b282',
		),
	);
}

function zcEnableDisableCustomWindow($memID)
{
	global $context;
	global $blog, $zcFunc;
	
	checkSession('get');
	$window_id = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : '';
	
	if (empty($window_id))
		zc_fatal_error();
	
	$zcFunc['db_update']('{db_prefix}custom_windows', array('enabled' => 'int', 'window_id' => 'int', 'blog_id' => 'int'), array('enabled' => !empty($_REQUEST['enable_disable']) ? 1 : 0), array('window_id' => $window_id, 'blog_id' => $blog));
		
	zc_redirect_exit('sa=' . (!empty($blog) ? 'customWindows' : 'communityPage') . zcRequestVarsToString('sa', ';') . '#customWindows');
}

function zc_prepare_blog_category_attributes()
{
	global $context, $txt, $blog, $scripturl;
	
	if (!isset($context['zc']['attributes']))
		$context['zc']['attributes'] = array();
		
	$context['zc']['attributes']['categories'] = array(
		'_info_' => array(
			'table_info' => array(
				'unprefixed_name' => 'categories',
			),
			'form_url' => $scripturl . zcRequestVarsToString(null, '?') . ';categories=' . (!empty($context['zc']['current_info']) ? 'edit;edit=' . $_REQUEST['edit'] : 'add') . ';sesc=' . $context['session_id'] . '#err',
			'form_id' => 'add',
		),
		'name' => array(
			'type' => 'text',
			'label' => 'b509',
			'max_length' => 100,
			'required' => true,
			'must_be_unique' => true,
			'additional_prevent_duplicates' => array(
				'blog_id' => $blog,
			),
		),
	);
}

function zc_prepare_custom_window_attributes()
{
	global $context, $txt, $scripturl;
	
	if (!isset($context['zc']['attributes']))
		$context['zc']['attributes'] = array();
		
	$context['zc']['attributes']['customWindows'] = array(
		'_info_' => array(
			'table_info' => array(
				'unprefixed_name' => 'custom_windows',
			),
			'form_url' => $scripturl . zcRequestVarsToString(null, '?') . ';customWindows=' . (!empty($context['zc']['current_info']) ? 'edit;edit=' . $_REQUEST['edit'] : 'add') . ';sesc=' . $context['session_id'] . '#err',
			'form_id' => 'add',
		),
		'title' => array(
			'type' => 'text',
			'max_length' => 100,
			'label' => 196,
			'required' => true,
		),
		'content' => array(
			'type' => 'text',
			'custom' => 'textarea',
			'label' => 'b508',
			'subtext' => 'b109',
			'required' => true,
			// should be string of tags (not array) ... for example: <br><a><img>
			// if is_raw_html and is_php are false, all html tags are stripped anyways
			'allowed_html_tags' => '',
			'is_php' => !empty($context['can_use_php_in_custom_windows']) && !empty($_POST['content_type']) && $_POST['content_type'] == 2,
			'is_raw_html' => !empty($context['can_use_raw_html_in_custom_windows']) && !empty($_POST['content_type']) && $_POST['content_type'] == 1,
		),
	);
	
	if (!empty($context['can_use_raw_html_in_custom_windows']) || !empty($context['can_use_php_in_custom_windows']))
	{
		$context['zc']['attributes']['customWindows']['content_type'] = array(
			'type' => 'int',
			'custom' => 'select',
			// 0 normal text, 1 raw html, 2 php
			'value' => 0,
			'label' => 'b673',
			'options' => array(
				'b674',
			),
		);
		
		if (!empty($context['can_use_raw_html_in_custom_windows']))
			$context['zc']['attributes']['customWindows']['content_type']['options'][1] = 'b675';
		
		if (!empty($context['can_use_php_in_custom_windows']))
			$context['zc']['attributes']['customWindows']['content_type']['options'][2] = 'b676';
	}
}

function zc_add_edit_custom_window()
{
	global $context, $blog, $zcFunc;
	
	if (!isset($context['zc']['attributes']['customWindows']))
		return false;
		
	list($processed, $context['zc']['errors']) = zcProcessForm($context['zc']['attributes']['customWindows']);

	if (empty($processed))
		zc_fatal_error();

	if (empty($context['zc']['errors']))
	{
		$id = !empty($_POST['id']) ? (int) $_POST['id'] : 0;
		
		$columns = array('blog_id' => 'int');
		if (!empty($id))
		{
			$where = array('blog_id' => $blog, 'window_id' => $id);
			$columns['window_id'] = 'int';
		}
		else
		{
			$data = array('blog_id' => $blog, 'win_order' => $context['zc']['max_side_window_order'] + 1);
			$columns['win_order'] = 'int';
		}
			
		foreach ($processed as $k => $v)
		{
			$columns[$k] = isset($context['zc']['attributes']['customWindows'][$k]['type']) ? $context['zc']['attributes']['customWindows'][$k]['type'] : 'string';
			$data[$k] = $v;
		}
			
		if (empty($id))
			$zcFunc['db_insert']('insert', '{db_prefix}custom_windows', $columns, $data);
		elseif (!empty($updateColumns))
			$zcFunc['db_update']('{db_prefix}custom_windows', $columns, $data, $where);
	}
}

function zc_delete_custom_windows()
{
	global $context, $blog, $zcFunc;
	
	if (empty($_POST['items']))
		return;

	$items = array();
	foreach ($_POST['items'] as $item)
		$items[] = (int) $item;
			
	$current_orders = array();
	foreach ($items as $item)
		$current_orders[$item] = $context['zc']['custom_windows'][$item]['win_order'];
		
	$updates = array();
	// figure out which non-custom-window orders we need to decrease
	if (!empty($current_orders))
		foreach ($current_orders as $order)
			foreach($context['zc']['side_windows'] as $win_order => $array)
				if ((strpos($array['type'], 'custom') === false) && !empty($win_order) && $win_order > $order)
					$updates[$array['type'] . 'WindowOrder'] = $win_order - 1;
					
	if (empty($blog))
		zcUpdateGlobalSettings($updates);
	else
		zcUpdateBlogSettings($updates, $blog);
			
	// decrease the orders of custom windows that had higher window orders
	if (!empty($current_orders))
		foreach ($current_orders as $order)
			$zcFunc['db_update'](
				'{db_prefix}custom_windows',
				array('win_order' => 'int', 'blog_id' => 'int'),
				array('win_order' => array('-', 1)),
				array('win_order' => array('>', $order), 'blog_id' => $blog),
				null);
			
	// delete the custom windows...
	$zcFunc['db_query']("
		DELETE FROM {db_prefix}custom_windows
		WHERE window_id IN ({array_int:items})
			AND blog_id = {int:blog_id}
		LIMIT {int:limit}", __FILE__, __LINE__,
		array(
			'limit' => count($items),
			'items' => $items,
			'blog_id' => $blog
		)
	);
}

function zc_reorder_custom_windows()
{
	global $context;
	global $blog, $zc, $zcFunc;

	checkSession('get');
	
	$custom_temp = array();
	if (!empty($context['zc']['custom_windows']))
		foreach ($context['zc']['custom_windows'] as $window)
			if (!empty($window['id']))
				$custom_temp[$window['var_name']] = $window['id'];
	
	// which settings array we use depends on $blog
	$current_settings = !empty($blog) ? $context['zc']['blog_settings'] : $zc['settings'];
	
	// clean the post variable and save the old order
	$originalVariable = $_REQUEST['moveWindow'] . 'WindowOrder';
	$new_order = !empty($_POST[$originalVariable]) ? (int) $_POST[$originalVariable] : 0;
	$old_order = !empty($current_settings[$originalVariable]) ? $current_settings[$originalVariable] : 0;
		
	if (($new_order != $old_order) && !empty($new_order))
	{
		$current_orders = array_keys($context['zc']['side_windows']);
		$updates = array();
		// custom_reorder is for custom windows
		$custom_reorder = array();
		
		// greater than or equal to the new order but less than the old order gets +1
		if (!empty($old_order) && ($new_order < $old_order) && in_array($new_order, $current_orders))
		{
			foreach ($context['zc']['side_windows'] as $win_order => $array)
				if (($win_order >= $new_order) && ($win_order < $old_order))
					if (isset($custom_temp[$array['type']]))
						$custom_reorder[$array['type']] = $win_order + 1;
					elseif (isset($current_settings[$array['type'] . 'WindowOrder']))
						$updates[$array['type'] . 'WindowOrder'] = $win_order + 1;
		}
		// less than or equal to the new order but less than the old order gets -1
		elseif (!empty($old_order) && ($new_order > $old_order) && in_array($new_order, $current_orders))
		{
			foreach ($context['zc']['side_windows'] as $win_order => $array)
				if (($win_order <= $new_order) && ($win_order > $old_order))
					if (isset($custom_temp[$array['type']]))
						$custom_reorder[$array['type']] = $win_order - 1;
					elseif (isset($current_settings[$array['type'] . 'WindowOrder']))
						$updates[$array['type'] . 'WindowOrder'] = $win_order - 1;
		}
		
		if (!empty($updates))
		{
			// update this blog's row in the blog_settings table
			if (!empty($blog))
				zcUpdateBlogSettings($updates, $blog);
			else
				zcUpdateGlobalSettings($updates);
		}
		
		// update each custom window
		if (!empty($custom_reorder))
			foreach ($custom_reorder as $variable => $value)
				$zcFunc['db_update'](
					'{db_prefix}custom_windows',
					array('win_order' => 'int', 'blog_id' => 'int', 'window_id' => 'int'),
					array('win_order' => $value),
					array('window_id' => $custom_temp[$variable], 'blog_id' => $blog));
					
		// finally update the window we originally wanted to move (if custom window)....
		if (!empty($custom_temp[$_REQUEST['moveWindow']]))
			$zcFunc['db_update'](
				'{db_prefix}custom_windows',
				array('win_order' => 'int', 'blog_id' => 'int', 'window_id' => 'int'),
				array('win_order' => $new_order),
				array('window_id' => $custom_temp[$_REQUEST['moveWindow']], 'blog_id' => $blog));
		// if not a custom window....
		else
		{
			if (!empty($blog))
				zcUpdateBlogSettings(array($originalVariable => $new_order), $blog);
			else
				zcUpdateGlobalSettings(array($originalVariable => $new_order));
		}
	}	
	zc_redirect_exit(zcRequestVarsToString() . '#moveWindows');
}

?>