<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
	/* 
		Here's how it works:  
		'option_name' => array(
			'value' => this setting's default value (required), 
			'type' => check/int/text/float/file (required),
			'label' => primary text displayed next to a form field (required for most types),
			'required' => true means the field is required (optional),
			'must_be_unique' => true means this option's value must be unique (optional),
			'custom' => hidden/select/multiCheck/radio (optional),
			'options' => array of options for a setting that uses select or radio (optional),
			'always_include' => array of values to always include in this value when saving (optional),
			'header_above' => text string to display as a header above this setting (optional),
			'instructions' => text string to display as normal text above this setting (optional),
			'subtext' => appears below the primary text for the form field (optional),
			'helptext' => if true, will add help link next to main text (optional),
			'must_return_true' => must return true for this setting to be available (optional),
			'needs_explode' => if true, the value held in this setting will be exploded into an array when a blog's settings are loaded (optional),
			'dir' => full path to directory to which files for the setting are stored (optional),
			'max_dir_size' => maximum size allowed for directory in 'dir' (optional),
			'max_file_size' => maximum file size of uploaded file (optional),
		),
	*/

function zc_prepare_preferences_array()
{
	global $context, $txt, $scripturl;
	
	if (!isset($context['zc']['preferences']))
		$context['zc']['preferences'] = array();
	
	$zc_preferences = array(
		'_info_' => array(
			'hidden_form_values' => array('save_preferences' => 1),
			'form_url' => $scripturl . zcRequestVarsToString(null, '?') .';sesc='. $context['session_id'],
		),
		'newest_comments_first' => array(
			'type' => 'check',
			'value' => 1,
			'label' => 'b91',
		),
		'show_comment_numbers' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b543',
		),
		'show_bbc_cloud' => array(
			'type' => 'check',
			'value' => 1,
			'label' => 'b561',
		),
		'delete_drafts_upon_posting' => array(
			'type' => 'check',
			'value' => 1,
			'label' => 'b206',
		),
		'send_body_with_notifications' => array(
			'type' => 'check',
			'value' => 0,
			'must_return_true' => false,
		),
		'notify_once' => array(
			'type' => 'check',
			'value' => 1,
			'must_return_true' => false,
		),
		'report_to_mod_notices' => array(
			'type' => 'int',
			'value' => 2,
			'must_return_true' => false,
			'custom' => 'select',
			'options' => array(
				'b283',
				'b284',
				'b285',
			),
		),
	);
	
	return array_merge($zc_preferences, $context['zc']['preferences']);
}

function zc_prepare_theme_settings_array()
{
	global $context, $scripturl, $txt, $blog, $zc;
			
	$temp = array(
		'_info_' => array(
			'hidden_form_values' => array('save_themes' => 1),
			'form_url' => $scripturl . zcRequestVarsToString(null, '?') .';sesc='. $context['session_id'],
		),
		'independent' => array(
			'value' => 0,
			'type' => 'check',
			'label' => 'b457',
			'subtext' => 'b458',
		),
		'blog_page_width' => array(
			'type' => 'text',
			'value' => $zc['settings']['community_page_width'],
			'label' => 'b416',
			'subtext' => 'b327',
			'instructions' => array('b460', 'b457'),
		),
		'blog_page_alignment' => array(
			'type' => 'text',
			'custom' => 'select',
			'value' => 'center',
			'label' => 'b417',
			'options' => array(
				'center' => 'b82',
				'left' => 'b10',
				'right' => 'b9',
			),
		),
		'blog_theme' => array(
			'value' => !empty($zc['settings']['default_blog_theme']) ? $zc['settings']['default_blog_theme'] : 'default',
			'type' => 'text',
			'label' => 'b459',
			'custom' => 'select',
			'options' => array(),
			'show_beside_field' => '<a href="' . $scripturl . '?zc=bcp'. (!empty($_REQUEST['u']) ? ';u=' . $_REQUEST['u'] : '') .';blog='. $blog .'.0;sa=themes;do=choose">' . $txt['b524'] . '</a>',
			'must_return_true' => !empty($context['zc']['themes']) && count(array_intersect(array_keys($context['zc']['themes']), $zc['settings']['enabled_themes'])) >= 1,
		),
	);
			
	if (!empty($context['zc']['themes']))
		foreach ($context['zc']['themes'] as $theme_id => $array)
			if (in_array($theme_id, $zc['settings']['enabled_themes']))
				$temp['blog_theme']['options'][$theme_id] = $array['name'];
				
	return $temp;
}

function zc_prepare_plugin_settings_array()
{
	global $context, $zc, $scripturl;
			
	$temp = array(
		'_info_' => array(
			'hidden_form_values' => array('save_blogPlugInSettings' => 1),
			'form_url' => $scripturl . zcRequestVarsToString(null, '?') .';sesc='. $context['session_id'],
		),
	);

	if (!empty($context['zc']['plugins']))
		foreach ($context['zc']['plugins'] as $plugin_id => $array)
		{
			// plugins must be enabled...
			if (empty($zc['settings']['zcp_' . $plugin_id . '_enabled']))
				continue;
				
			if (!empty($array['settings']))
				foreach ($array['settings'] as $k => $a)
					$temp[$k] = $a;
		}
	return $temp;
}

function zc_prepare_blog_settings_array()
{
	global $txt, $context, $settings, $scripturl, $zc;
		
	// make sure these exist...
	$txt_must_exist = array(
		'b7' => 'for',
		'b426' => '<b>required</b>',
		'b37' => 'and',
	);
	
	foreach ($txt_must_exist as $key => $text)
		if (!isset($txt[$key]))
			$txt[$key] = $text;
		
	$temp = array(
		'_info_' => array(
			'hidden_form_values' => array('save_blogSettings' => 1),
			'form_url' => $scripturl . zcRequestVarsToString(null, '?') .';sesc='. $context['session_id'],
			'exclude_from_table' => array(
				'blogName',
				'blogDescription',
				'allowedGroups',
			),
			'table_info' => array(
				'unprefixed_name' => 'settings',
			),
			'file_upload_field_exists' => true,
		),
		'hideBlog' => array(
			'value' => 0,
			'type' => 'int',
			'custom' => 'enable_disable_radio',
			'txt' => array(
				'b25',
				'b682',
			),
		),
		'blogName' => array(
			'value' => '',
			'type' => 'text',
			'label' => 'b391',
			'required' => true,
		),
		'blogDescription' => array(
			'value' => '',
			'type' => 'text',
			'label' => 'b452',
			'custom' => 'textarea',
			'max_length' => isset($zc['settings']['max_length_blog_desc']) ? $zc['settings']['max_length_blog_desc'] : 100,
		),
		'blog_avatar' => array(
			'value' => '',
			'type' => 'file',
			'label' => 'b687',
			'dir' => $zc['settings']['attachments_dir'],
			'max_dir_size' => $zc['settings']['max_size_attachments_dir'],
			'allowed_file_extensions' => array('jpg', 'gif', 'png', 'bmp'),
			'max_width_image' => $zc['settings']['blog_index_max_avatar_width'],
			'max_height_image' => $zc['settings']['blog_index_max_avatar_height'],
			'resize_image_if_too_large' => true,
			'more_processing' => array($zc['sources_dir'] . '/Subs-Images.php', 'zc_process_image_upload'),
		),
		'show_go_to_top' => array(
			'value' => 0,
			'type' => 'check',
			'label' => 'b409',
		),
		'meta_keywords' => array(
			'value' => '',
			'type' => 'text',
			'custom' => 'textarea',
			'ta_rows' => 5,
			'label' => 'b418',
			'helptext' => 'zc_help_8',
			'header_above' => 'b552',
		),
		'articles_require_approval' => array(
			'value' => 0,
			'type' => 'check',
			'label' => 'b243',
			'helptext' => 'zc_help_4',
			'header_above' => 'b399',
		),
		'max_articles_on_blog' => array(
			'value' => 2,
			'type' => 'int',
			'label' => 'b449',
			'subtext' => 'b429',
		),
		'max_length_articles' => array(
			'value' => 2400,
			'type' => 'int',
			'label' => 'b450',
			'subtext' => 'b429',
		),
		'show_last_edit_articles' => array(
			'value' => 1,
			'type' => 'check',
			'label' => array('b425', 'b129'),
		),
		'show_related_articles' => array(
			'value' => 1,
			'type' => 'check',
			'label' => 'b410',
			'subtext' => 'b410a',
		),
		'limit_related_articles' => array(
			'value' => 3,
			'type' => 'int',
			'label' => 'b3051',
			'subtext' => 'b429',
		),
		'show_tags' => array(
			'value' => 1,
			'type' => 'check',
			'label' => 'b419',
			'subtext' => 'b420',
		),
		'show_categories' => array(
			'value' => 1,
			'type' => 'check',
			'label' => 'b421',
			'subtext' => 'b422',
		),
		'hide_comments' => array(
			'value' => 0,
			'type' => 'check',
			'label' => 'b428',
		),
		'display_comments_blog' => array(
			'value' => 1,
			'type' => 'check',
			'label' => 'b447',
		),
		'comments_require_approval' => array(
			'value' => 0,
			'type' => 'check',
			'label' => 'b427',
			'helptext' => 'zc_help_3',
		),
		'max_comments_per_topic' => array(
			'value' => 3,
			'type' => 'int',
			'label' => 'b448',
			'subtext' => 'b429',
		),
		'max_length_comments' => array(
			'value' => 500,
			'type' => 'int',
			'label' => 'b451',
			'subtext' => 'b429',
		),
		'show_last_edit_comments' => array(
			'value' => 1,
			'type' => 'check',
			'label' => array('b425', 'b130'),
		),
		'articleListDefaultSort' => array(
			'type' => 'text',
			'value' => 'date',
			'custom' => 'select',
			'options' => array(
				'subject' => 'b3032',
				'comments' => 'b15a',
				'views' => 'b3027',
				'author' => 'b3033',
				'date' => 'b30',
			),
			'label' => 'b396',
		),
		'articleListSortAscending' => array(
			'type' => 'int',
			'value' => 0,
			'custom' => 'select',
			'options' => array(
				'b94',
				'b95',
			),
			'label' => 'b395',
		),
		'show_socialbookmarks_articles' => array(
			'value' => 1,
			'type' => 'int',
			'custom' => 'enable_disable_radio',
			'header_above' => 'b433',
		),
		'socialbookmarks_multicheck' => array(
			'value' => 'digg,delicious,furl,stumbleupon,technorati',
			'type' => 'text',
			'custom' => 'multi_check',
			'needs_explode' => true,
			'instructions' => 'b432',
			'options' => array(),
		),
		'allowedGroups' => array(
			'type' => 'text',
			'value' => isset($zc['settings']['defaultAllowedGroups']) ? $zc['settings']['defaultAllowedGroups'] : '-1,0,2',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'hide_side_windows' => array(
			'value' => 0,
			'type' => 'check',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'justifySideWindows' => array(
			'value' => 0,
			'type' => 'int',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'enableRecentEntries' => array(
			'value' => 1,
			'type' => 'check',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'num_recent_entries' => array(
			'value' => 7,
			'type' => 'int',
			'subtext' => 'b429',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'enableCategoryList' => array(
			'value' => 0,
			'type' => 'check',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'enableArchives' => array(
			'value' => 1,
			'type' => 'check',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'enableStatsWindow' => array(
			'value' => 0,
			'type' => 'check',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'enableTagsWindow' => array(
			'value' => 0,
			'type' => 'check',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'max_num_tags_in_tag_window' => array(
			'value' => 48,
			'type' => 'int',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'enableWhoViewingWindow' => array(
			'value' => 0,
			'type' => 'check',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'useAvatarsForWhoViewing' => array(
			'value' => 1,
			'type' => 'check',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'enableMostCommentedWindow' => array(
			'value' => 0,
			'type' => 'check',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'limit_most_commented' => array(
			'value' => 5,
			'type' => 'int',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'enableRecentCommentsWindow' => array(
			'value' => 0,
			'type' => 'check',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'limit_recent_comments' => array(
			'value' => 5,
			'type' => 'int',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'enablePollsWindow' => array(
			'value' => 0,
			'type' => 'check',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'limit_num_polls' => array(
			'value' => 2,
			'type' => 'int',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'recentWindowOrder' => array(
			'value' => 1,
			'type' => 'int',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'categoriesWindowOrder' => array(
			'value' => 2,
			'type' => 'int',
			'custom' => 'hidden',
		),
		'archivesWindowOrder' => array(
			'value' => 3,
			'type' => 'int',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'tagsWindowOrder' => array(
			'value' => 4,
			'type' => 'int',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'statsWindowOrder' => array(
			'value' => 5,
			'type' => 'int',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'whoViewingWindowOrder' => array(
			'value' => 6,
			'type' => 'int',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'mostCommentedWindowOrder' => array(
			'value' => 7,
			'type' => 'int',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'recentCommentsWindowOrder' => array(
			'value' => 8,
			'type' => 'int',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'pollsWindowOrder' => array(
			'value' => 9,
			'type' => 'int',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'usersAllowedToBlog' => array(
			'value' => '',
			'type' => 'text',
			'custom' => 'hidden',
			'must_return_true' => false,
			'needs_explode' => true,
		),
		'groupsAllowedToBlog' => array(
			'value' => '',
			'type' => 'text',
			'custom' => 'hidden',
			'must_return_true' => false,
			'needs_explode' => true,
		),
		'users_allowed_access' => array(
			'value' => '',
			'type' => 'text',
			'custom' => 'hidden',
			'must_return_true' => false,
			'needs_explode' => true,
		),
		'windows_inner_alignment' => array(
			'value' => 'center',
			'type' => 'text',
			'must_return_true' => false,
			'custom' => 'select',
			'options' => array(
				'left' => 'b10',
				'center' => 'b82',
				'right' => 'b9',
			),
		),
	);
	
	// get info about the social bookmarks we have available...
	zc_prepare_bookmarking_options_array();
	
	// populate options array of socialbookmarks_multicheck
	if (!empty($context['zc']['bookmarking_options']))
		foreach ($context['zc']['bookmarking_options'] as $site => $array)
			$temp['socialbookmarks_multicheck']['options'][$site] = '<a href="'. $array['site_home'] .'" rel="nofollow" target="_blank">&raquo;</a>&nbsp;' . $array['name'];
			
	return $temp;
}

function zc_prepare_sw_settings_array()
{
	global $context, $txt, $scripturl;
	$context['zc']['sw_settings'] = array(
		'_info_' => array(
			'hidden_form_values' => array('save_customWindows' => 1),
			'form_url' => $scripturl . zcRequestVarsToString(null, '?') .';sesc='. $context['session_id'],
		),
		'hide_side_windows' => array(
			'value' => 0,
			'type' => 'check',
			'label' => 'b456',
		),
		'justifySideWindows' => array(
			'value' => 0,
			'type' => 'int',
			'label' => 'b455',
			'custom' => 'select',
			'options' => array(
				'b9',
				'b10',
			),
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
		'enableRecentEntries' => array(
			'value' => 1,
			'type' => 'check',
			'label' => array('b501', 'b503'),
		),
		'num_recent_entries' => array(
			'value' => 7,
			'type' => 'int',
			'label' => 'b446',
			'subtext' => 'b429',
		),
		'enableCategoryList' => array(
			'value' => 0,
			'type' => 'check',
			'label' => array('b501', 'b16a'),
		),
		'enableArchives' => array(
			'value' => 1,
			'type' => 'check',
			'label' => array('b501', 'b22'),
		),
		'enableStatsWindow' => array(
			'value' => 0,
			'type' => 'check',
			'label' => array('b501', 'b517'),
		),
		'enableTagsWindow' => array(
			'value' => 0,
			'type' => 'check',
			'label' => array('b501', 'b26a'),
		),
		'max_num_tags_in_tag_window' => array(
			'value' => 48,
			'type' => 'int',
			'label' => 'b262',
			'subtext' => 'b263',
		),
		'enableWhoViewingWindow' => array(
			'value' => 0,
			'type' => 'check',
			'label' => array('b501', 'b502'),
		),
		'useAvatarsForWhoViewing' => array(
			'value' => 1,
			'type' => 'check',
			'label' => 'b505',
		),
		'enableMostCommentedWindow' => array(
			'value' => 0,
			'type' => 'check',
			'label' => array('b439', 'b440'),
		),
		'limit_most_commented' => array(
			'value' => 5,
			'type' => 'int',
			'label' => 'b434',
			'subtext' => 'b429',
		),
		'enableRecentCommentsWindow' => array(
			'value' => 0,
			'type' => 'check',
			'label' => array('b439', 'b441'),
		),
		'limit_recent_comments' => array(
			'value' => 5,
			'type' => 'int',
			'label' => 'b435',
			'subtext' => 'b429',
		),
		'enablePollsWindow' => array(
			'value' => 0,
			'type' => 'check',
			'label' => array('b501', 'b247'),
		),
		'limit_num_polls' => array(
			'value' => 2,
			'type' => 'int',
			'label' => 'b436',
			'subtext' => 'b429',
		),
	);
}

function zc_prepare_global_settings_array()
{
	global $context, $txt, $scripturl, $zc;
	$context['zc']['zc_settings'] = array(
		'_info_' => array(
			'hidden_form_values' => array('save_globalSettings' => 1),
			'form_url' => $scripturl . zcRequestVarsToString(null, '?') .';sesc='. $context['session_id'],
			'file_upload_field_exists' => true,
		),
		'zc_mode' => array(
			'type' => 'int',
			'value' => 0,
			'custom' => 'select',
			'options' => array(
				'b387',
				'b388',
				'b389',
				'b390',
			),
			'helptext' => 'zc_help_1',
			'label' => 'b386',
		),
		'blogBoard' => array(
			'type' => 'int',
			'value' => 0,
			'custom' => 'select',
			'options' => array(),
			'helptext' => 'zc_help_2',
			'label' => 'b392',
		),
		'max_num_blogs' => array(
			'type' => 'int',
			'value' => 5,
			'subtext' => array('b430', 'b429', 'b454'),
			'label' => 'b453',
		),
		'toggle_all_layerless' => array(
			'type' => 'check',
			'value' => 0,
			'must_return_true' => false,
		),/*
		'hide_portal_blocks' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b438',
		),*/
		'firefox_icon' => array(
			'type' => 'int',
			'value' => 1,
			'custom' => 'select',
			'options' => array(
				'b369',
				'b370',
				'b371',
			),
			'label' => 'b368',
		),
		'who_viewing_max_avatar_width' => array(
			'type' => 'int',
			'value' => 48,
			'label' => array('b259', 'b260'),
			'subtext' => 'b354',
		),
		'who_viewing_max_avatar_height' => array(
			'type' => 'int',
			'value' => 48,
			'label' => array('b259', 'b261'),
			'subtext' => 'b354',
		),
		'max_length_blog_desc' => array(
			'type' => 'int',
			'value' => 100,
			'label' => 'b248',
			'subtext' => 'b429',
		),/*
		'attachments_dir' => array(
			'type' => 'text',
			'value' => '',
			'label' => 'b378',
			'subtext' => 'b379',
			'header_above' => 'b377',
		),
		'max_size_attachments_dir' => array(
			'type' => 'int',
			'value' => 1000,
			'label' => 'b380',
			'subtext' => 'b381',
		),
		'allow_attachments_articles' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b382', 'b129'),
		),
		'allow_attachments_comments' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b382', 'b130'),
		),*/
		'posting_grace_period' => array(
			'type' => 'int',
			'value' => 60,
			'label' => 'b352',
			'subtext' => 'b353',
			'header_above' => 'b351',
		),
		'max_chars_per_word' => array(
			'type' => 'int',
			'value' => 24,
			'label' => 'b544',
			'subtext' => 'b429',
		),
		'show_bbc_cloud_for_guests' => array(
			'type' => 'check',
			'value' => 1,
			'label' => 'b561',
			'subtext' => 'b562',
		),
		'allow_show_all_link' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b324',
			'header_above' => 'b317',
		),
		'maxIndexArticleList' => array(
			'type' => 'int',
			'value' => 20,
			'subtext' => 'b429',
			'label' => 'b415',
		),
		'maxIndexCommentList' => array(
			'type' => 'int',
			'value' => 10,
			'subtext' => array('b405', 'b429', 'b404'),
			'label' => 'b403',
		),
		'show_vpreview_on_lists' => array(
			'type' => 'check',
			'value' => 1,
			'label' => 'b545',
		),
		'max_length_preview_popups' => array(
			'type' => 'int',
			'value' => 450,
			'label' => 'b316',
			'subtext' => 'b429',
		),
		'compact_page_indexes' => array(
			'type' => 'check',
			'value' => 1,
			'label' => 'b550',
		),
		'compact_page_indexes_contiguous' => array(
			'type' => 'int',
			'value' => 5,
			'label' => 'b554',
			'subtext' => 'b679',
		),
		'blog_xml_enable' => array(
			'type' => 'int',
			'value' => 1,
			'custom' => 'enable_disable_radio',
			'header_above' => 'b268',
		),
		'blog_xml_article_maxlen' => array(
			'type' => 'int',
			'value' => 1800,
			'label' => array('b266', 'b129'),
		),
		'blog_xml_max_num_articles' => array(
			'type' => 'int',
			'value' => 5,
			'label' => array('b267', 'b129'),
		),
		'blog_xml_hide_comments' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b265',
		),
		'blog_xml_comment_maxlen' => array(
			'type' => 'int',
			'value' => 450,
			'label' => array('b266', 'b130'),
		),
		'blog_xml_max_num_comments' => array(
			'type' => 'int',
			'value' => 3,
			'label' => array('b267', 'b130'),
		),
		'drafts_max_num' => array(
			'type' => 'int',
			'value' => 20,
			'subtext' => 'b429',
			'label' => 'b322',
			'header_above' => 'b321',
		),
		'drafts_max_preview_length' => array(
			'type' => 'int',
			'value' => 800,
			'subtext' => 'b429',
			'label' => 'b323',
		),
		'enable_recaptcha' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b533',
			'subtext' => 'b529',
			'header_above' => 'b372',
		),
		'recaptcha_pubkey' => array(
			'type' => 'text',
			'value' => '',
			'label' => 'b530',
			'subtext' => 'b532',
		),
		'recaptcha_privkey' => array(
			'type' => 'text',
			'value' => '',
			'label' => 'b531',
			'subtext' => 'b532',
		),/*
		'visual_verification_enabled' => array(
			'type' => 'check',
			'value' => 1,
			'label' => 'b318',
		),
		'visual_verif_num_letters' => array(
			'type' => 'int',
			'value' => 5,
			'label' => 'b319',
		),*/
		'guests_no_post_links' => array(
			'type' => 'check',
			'value' => 1,
			'label' => 'b546',
		),/*
		'anti_bot_questions_enabled' => array(
			'type' => 'check',
			'value' => 1,
			'label' => 'b375',
		),
		'anti_bot_questions' => array(
			'type' => 'text',
			'value' => '',
			'max_num_fields' => 9,
			'minimum_num_fields' => 0,
			'custom' => 'multi_field',
			'add_field_option' => true,
			'label' => 'b373',
			'subtext' => array('b405', 'b376', 'b374'),
			'splice' => true,
			'value_pairs' => true,
		),*/
		'defaultAllowedGroups' => array(
			'type' => 'text',
			'value' => '-1,0,2',
			'custom' => 'multi_check',
			'always_include' => array('2'),
			'options' => array(),
			'instructions' => 'b401',
			'header_above' => 'b400',
		),
		'base_new_blogs_name' => array(
			'type' => 'text',
			'field_width' => '240px',
			'value' => '{special:owner_name}\'s Blog',
			'label' => 'b565',
			'subtext' => 'b566',
		),
		'default_blog_theme' => array(
			'value' => 'default',
			'type' => 'text',
			'label' => 'b665',
			'custom' => 'select',
			'options' => array(),
		),
		'default_blog_avatar' => array(
			'value' => '',
			'type' => 'file',
			'label' => 'b690',
			'allowed_file_extensions' => array('jpg', 'gif', 'png', 'bmp'),
			'resize_image_if_too_large' => true,
			'more_processing' => array($zc['sources_dir'] . '/Subs-Images.php', 'zc_process_image_upload'),
		),
		'globalMostRecentWindowOrder' => array(
			'type' => 'int',
			'value' => 1,
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'globalTagsWindowOrder' => array(
			'type' => 'int',
			'value' => 2,
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'globalArchivesWindowOrder' => array(
			'type' => 'int',
			'value' => 3,
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'blog_index_max_avatar_width' => array(
			'type' => 'int',
			'value' => 65,
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'blog_index_max_avatar_height' => array(
			'type' => 'int',
			'value' => 50,
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'blogIndexDefaultSort' => array(
			'type' => 'text',
			'value' => 'views',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'blogIndexSortAscending' => array(
			'type' => 'int',
			'value' => 0,
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'showAvatarsOnBlogIndex' => array(
			'type' => 'check',
			'value' => 1,
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'showRSSFeedsOnBlogIndex' => array(
			'type' => 'check',
			'value' => 0,
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'maxIndexOnBlogIndex' => array(
			'type' => 'int',
			'value' => 20,
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'globalArticleListDefaultSort' => array(
			'type' => 'text',
			'value' => 'date',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'globalArticleListSortAscending' => array(
			'type' => 'int',
			'value' => 0,
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'enable_blog_index_block' => array(
			'type' => 'check',
			'value' => 1,
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'enable_news_block' => array(
			'type' => 'check',
			'value' => 0,
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'news_block_max_num' => array(
			'type' => 'int',
			'value' => 1,
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'news_block_show_last_edit' => array(
			'type' => 'check',
			'value' => 0,
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'news_block_max_length' => array(
			'type' => 'int',
			'value' => 400,
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'show_socialbookmarks_news' => array(
			'value' => 1,
			'type' => 'int',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'news_socialbookmarks_multicheck' => array(
			'value' => 'digg,delicious,furl,stumbleupon,technorati',
			'type' => 'text',
			'custom' => 'hidden',
			'needs_explode' => true,
			'must_return_true' => false,
		),
		'community_page_without_layers' => array(
			'type' => 'check',
			'value' => 0,
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'community_page_width' => array(
			'type' => 'text',
			'value' => '80%',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'community_page_alignment' => array(
			'type' => 'text',
			'value' => 'center',
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'community_page_side_bar' => array(
			'type' => 'int',
			'value' => 0,
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'enableGlobalTagsWindow' => array(
			'type' => 'check',
			'value' => 0,
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'max_tags_in_globaltag_window' => array(
			'type' => 'int',
			'value' => 48,
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'enableGlobalMostRecentWindow' => array(
			'type' => 'check',
			'value' => 0,
			'custom' => 'hidden',
			'must_return_true' => false,
		),
		'enableGlobalArchivesWindow' => array(
			'type' => 'check',
			'value' => 0,
			'custom' => 'hidden',
			'must_return_true' => false,
		),
	);
}

function zc_prepare_admin_theme_settings_array()
{
	global $context, $scripturl, $txt;
	
	$choose_theme_link = '<a href="' . $scripturl . '?zc=bcp;sa=themes;do=choose">%s</a>';
	
	$context['zc']['base_admin_theme_settings'] = array(
		'_info_' => array(
			'hidden_form_values' => array('save_themes' => 1),
			'form_url' => $scripturl . zcRequestVarsToString(null, '?') .';sesc='. $context['session_id'],
		),
		'toggle_all_layerless' => array(
			'type' => 'check',
			'value' => 0,
			'subtext' => 'b412',
			'label' => 'b411',
		),
		'blog_community_theme' => array(
			'type' => 'text',
			'custom' => 'select',
			'options' => array(),
			'value' => 'default',
			'label' => 'b493',
			'show_beside_field' => sprintf($choose_theme_link, zcFormatTxtString('b524')),
		),
		'enabled_themes' => array(
			'type' => 'text',
			'value' => 'default',
			'custom' => 'multi_check',
			'options' => array(),
			'needs_explode' => true,
			'instructions' => 'b528',
			'must_return_true' => !empty($context['zc']['themes']),
		),
	);
	
	// we had previously prepared this array in load_zc_themes() in Themes.php
	$context['zc']['admin_theme_settings'] = !empty($context['zc']['admin_theme_settings']) ? array_merge($context['zc']['base_admin_theme_settings'], $context['zc']['admin_theme_settings']) : $context['zc']['base_admin_theme_settings'];
	
	// only do this stuff if in blog control panel
	if (!empty($context['blog_control_panel']))
	{
		$temp = array();
		if (!empty($context['zc']['themes']))
			foreach ($context['zc']['themes'] as $theme_id => $array)
				$temp[$theme_id] = $array['name'];
				
		$context['zc']['admin_theme_settings']['enabled_themes']['options'] = $temp;
		$context['zc']['admin_theme_settings']['blog_community_theme']['options'] = $temp;
	}
}

function zc_prepare_side_window_arrays($memID = null)
{
	global $context, $txt, $scripturl, $settings;
	global $zcFunc, $blog, $zc;
	
	// viewing a blog...
	if (!empty($blog))
		$context['zc']['base_windows'] = array(
			'recent' => $txt['b503'],
			'categories' => $txt['b16a'],
			'archives' => $txt['b22'],
			'stats' => $txt['b517'],
			'tags' => $txt['b26a'],
			'whoViewing' => $txt['b502'],
			'mostCommented' => $txt['b440'],
			'recentComments' => $txt['b441'],
			'polls' => $txt['b247'],
		);
	// community page...
	else
		$context['zc']['base_windows'] = array(
			'globalTags' => $txt['b26a'],
			'globalMostRecent' => $txt['b503'],
			'globalArchives' => $txt['b22'],
		);
	
	$context['zc']['side_windows'] = $context['zc']['base_windows'];
	
	$request = $zcFunc['db_query']("
		SELECT window_id, title, content, win_order, enabled, content_type
		FROM {db_prefix}custom_windows
		WHERE blog_id = {int:blog_id}
		ORDER BY window_id", __FILE__, __LINE__,
		array(
			'blog_id' => (int) $blog
		)
	);
	
	$i = 0;
	$context['zc']['custom_windows'] = array();
	while ($row = $zcFunc['db_fetch_assoc']($request))
	{
		$i++;
		
		$context['zc']['custom_windows'][$row['window_id']] = array(
			'id' => $row['window_id'],
			'title' => $zcFunc['un_htmlspecialchars']($row['title']),
			'content' => empty($row['content_type']) ? $zcFunc['un_htmlspecialchars']($row['content']) : $row['content'],
			'content_type' => $row['content_type'],
			'win_order' => $row['win_order'],
			'enabled' => $row['enabled'],
			'name' => $zcFunc['un_htmlspecialchars']($row['title']),
			'custom_num' => $i,
			'var_name' => 'custom' . $i,
		);
		
		$context['zc']['side_windows']['custom' . $i] = $zcFunc['un_htmlspecialchars']($row['title']);
		if (!empty($blog))
			$context['zc']['blog_settings']['custom' . $i . 'WindowOrder'] = $row['win_order'];
		else
			$zc['settings']['custom' . $i . 'WindowOrder'] = $row['win_order'];
	}
	$zcFunc['db_free_result']($request);
	
	$settings_array = !empty($blog) ? $context['zc']['blog_settings'] : $zc['settings'];
	
	// redo the context side windows array using the window orders as the keys
	$temp = array();
	$needs_repair = array();
	foreach ($context['zc']['side_windows'] as $type => $name)
		if (!empty($settings_array[$type . 'WindowOrder']))
			$temp[$settings_array[$type . 'WindowOrder']] = array('name' => $name, 'type' => $type);
		else
			$needs_repair[] = $type;
	
	if (!empty($needs_repair))
	{
		$real_orders = array_keys($temp);
		
		$w = 0;
		for ($i = 1; $i <= count($real_orders); $i++)
		{
			if (!in_array($i, $real_orders))
			{
				if (!empty($blog))
				{
					$context['zc']['blog_settings'][$needs_repair[$w] . 'WindowOrder'] = $i;
					zcUpdateBlogSettings(array($type . 'WindowOrder' => $i), $blog);
				}
				else
				{
					$zc['settings'][$needs_repair[$w] . 'WindowOrder'] = $i;
					zcUpdateGlobalSettings(array($needs_repair[$w] . 'WindowOrder' => $i));
				}
				$temp[$i] = array('name' => $context['zc']['side_windows'][$needs_repair[$w]], 'type' => $needs_repair[$w]);
				unset($needs_repair[$w]);
				$w++;
			}
		}
		
		// are there still windows that need repair?
		$updates = array();
		if (!empty($needs_repair))
		{
			$m = !empty($real_orders) ? max($real_orders) : 1;
			foreach ($needs_repair as $type)
			{
				$m++;
				$updates[$type . 'WindowOrder'] = $m;
				$temp[$m] = array('name' => $context['zc']['side_windows'][$type], 'type' => $type);
				if (!empty($blog))
					$context['zc']['blog_settings'][$type . 'WindowOrder'] = $m;
				else
					$zc['settings'][$type . 'WindowOrder'] = $m;
			}
		}
		
		if (!empty($updates))
			if (!empty($blog))
				zcUpdateBlogSettings($updates, $blog);
			else
				zcUpdateGlobalSettings($updates);
	}
	
	$context['zc']['max_side_window_order'] = !empty($temp) ? max(array_keys($temp)) : 1;
	$context['zc']['side_windows'] = !empty($temp) ? $temp : array();
}

?>