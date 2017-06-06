<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
/*
	zc_load_main_menu_array()
		- $context['zc']['layerless'] must be true
		- returns the main navigation menu array
		
	zc_load_login_form_info()
		- returns the login form info array
		
	zc_load_page_context()
		- gets stuff ready if we are going to be viewing a page and not just performing some action
		
	zcLoadGlobalSettings()
		- prepares the $zc['settings'] array
		
	zcLoadBlog()
		- uses $blog or $article to load settings/info about a blog
		
	zcLoadBlogTags($blogs = null)
		- loads tags for a blog or blogs
		
	zcLoadPermissions()
		- loads a user's permissions
		
	zcLoadLanguage($template_name, $lang = '', $fatal = false, $dir = null)
		- loads a language file
	
	zcLoadTemplate($template, $fatal = true)
		- loads a template file
*/

function zc_load_main_menu_array()
{
	global $context, $scripturl, $zc;
	
	// start the menu array...
	$menu = array(
		'_info_' => array(
			'location' => 'main',
			'hidden_form_values' => array(
				'hash_passwrd' => '',
			),
		),
		'home' => array(
			'label' => 'b3000',
			'is_active' => false,
			'can_see' => true,
			'attributes' => array(
				'href' => $scripturl,
			),
		),
	);
	
	if (empty($context['zc']['zCommunity_is_home']))
		$menu['zc'] = array(
			'label' => 'b1a',
			'is_active' => true,
			'attributes' => array(
				'href' => $scripturl,
			),
		);
	else
		$menu['forum'] = array(
			'label' => 'b540',
			'is_active' => false,
			'can_see' => true,
			'attributes' => array(
				'href' => $scripturl . '?action=forum',
			),
		);
	
	// $context['zc']['menu'] could have been populated by plug-ins
	if (!empty($context['zc']['menu']))
		$menu += $context['zc']['menu'];
		
	$menu += array(
		'profile' => array(
			'label' => 'b3019',
			'is_active' => false,
			'can_see' => !$context['user']['is_guest'],
			'attributes' => array(
				'href' => $scripturl . '?action=profile',
				'rel' => 'nofollow',
			),
		),
		'pm' => array(
			'label' => array('%1$s%2$s', 'b3020', (isset($context['user']['unread_messages']) && $context['user']['unread_messages'] > 0 ? ' [<strong>' . $context['user']['unread_messages'] . '</strong>]' : '')),
			'is_active' => false,
			'can_see' => !$context['user']['is_guest'],
			'attributes' => array(
				'href' => $scripturl . '?action=pm',
				'rel' => 'nofollow',
			),
		),
		'admin' => array(
			'label' => 'b3021',
			'is_active' => false,
			'can_see' => $context['user']['is_admin'],
			'attributes' => array(
				'href' => $scripturl . '?action=admin',
				'rel' => 'nofollow',
			),
			'sub_menu' => array(
				'errLog' => array(
					'label' => 'b3040',
					'can_see' => $context['user']['is_admin'],
					'attributes' => array(
						'href' => $scripturl . '?' . ($zc['with_software']['version'] == 'SMF 2.0' ? 'action=admin;area=logs;sa=errorlog' : 'action=viewErrorLog'),
						'rel' => 'nofollow',
					),
				),
			),
		),
		'bcp' => array(
			'label' => 'b3002',
			'is_active' => false,
			'can_see' => !$context['user']['is_guest'],
			'attributes' => array(
				'href' => $scripturl . '?zc=bcp',
				'rel' => 'nofollow',
			),
			'sub_menu' => array(
				'preferences' => array(
					'label' => 'b90',
					'can_see' => !$context['user']['is_guest'],
					'attributes' => array(
						'href' => $scripturl . '?zc=bcp;sa=preferences',
						'rel' => 'nofollow',
					),
				),
				'notifications' => array(
					'label' => 'b257',
					'can_see' => !empty($context['can_mark_notify']),
					'attributes' => array(
						'href' => $scripturl . '?zc=bcp;sa=notifications',
						'rel' => 'nofollow',
					),
				),
			),
		),
		'logout' => array(
			'label' => 'b3016',
			'is_active' => false,
			'can_see' => !$context['user']['is_guest'],
			'attributes' => array(
				'href' => $scripturl . '?action=logout;sesc=' . $context['session_id'],
				'rel' => 'nofollow',
			),
		),
		'register' => array(
			'label' => 'b3017',
			'is_active' => false,
			'can_see' => $context['user']['is_guest'],
			'attributes' => array(
				'href' => $scripturl . '?action=register',
				'rel' => 'nofollow',
			),
		),
		'login' => array(
			'label' => 'b3018',
			'is_active' => false,
			'can_see' => $context['user']['is_guest'],
			'attributes' => array(
				'href' => 'javascript:void(0);',
				'onclick' => 'getElementById(\'popup_login\').style.display=\'block\';',
				'rel' => 'nofollow',
			),
		),
	);
	
	return $menu;
}

function zc_load_login_form_info()
{
	return array(
		'_info_' => array(),
		'user' => array(
			'type' => 'text',
			'value' => '',
			'label' => 'b3038',
		),
		'passwrd' => array(
			'type' => 'password',
			'value' => '',
			'label' => 'b3039',
		),
		'cookielength' => array(
			'type' => 'int',
			'value' => 60,
			'max_length' => 4,
			'label' => 'b3037',
		),
		'cookieneverexp' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b3036',
			//'disable_others' => array(
				// id of input field to disable => value THIS field must equal to disable the target input field
				//'cookielength' => 1,
			//),
		),
	);
}

function zc_load_page_context()
{
	global $context, $settings, $scripturl, $txt, $zc, $article, $blog, $blog_info;
	
	$context['zc']['string_help_browser_cache'] = '?zc080';
	
	require_once($zc['sources_dir'] . '/Subs-smf.php');
	if (in_array($zc['with_software']['version'], $zc['smf_versions']))
		zc_load_page_context_smf();
	
	$context['zc']['link_tree_divider'] = '&nbsp;&raquo;&nbsp;';
	
	// some basic css for zcommunity...
	$context['zc']['load_css_stylesheets'][] = $zc['default_theme_url'] . '/css/zc_basics.css' . $context['zc']['string_help_browser_cache'];
	
	// do we want to show this page without layers?
	$context['zc']['layerless'] = ((!isset($_REQUEST['zc']) || $_REQUEST['zc'] != 'bcp') && !empty($blog) && !empty($context['zc']['theme_settings']['independent'])) || !empty($zc['settings']['toggle_all_layerless']) || (empty($blog) && !empty($zc['settings']['community_page_without_layers']));
	
	// figure out which theme we're using....
	if ((!isset($_REQUEST['zc']) || $_REQUEST['zc'] != 'bcp') && !empty($context['zc']['layerless']) && !empty($context['zc']['theme_settings']['blog_theme']) && !empty($context['zc']['themes'][$context['zc']['theme_settings']['blog_theme']]) && in_array($context['zc']['theme_settings']['blog_theme'], $zc['settings']['enabled_themes']))
		$context['zc']['current_theme'] = $context['zc']['theme_settings']['blog_theme'];
	elseif (!empty($zc['settings']['blog_community_theme']) && !empty($context['zc']['themes'][$zc['settings']['blog_community_theme']]))
		$context['zc']['current_theme'] = $zc['settings']['blog_community_theme'];
	else
		$context['zc']['current_theme'] = 'default';
		
	if (empty($context['zc']['layerless']))
		$context['zc']['current_theme'] = !empty($zc['settings']['blog_community_theme']) && !empty($context['zc']['themes'][$zc['settings']['blog_community_theme']]) ? $zc['settings']['blog_community_theme'] : 'default';
	
	// !!!
	if (file_exists($settings['default_theme_url'] . '/images/icons/favicon.png'))
		$context['zc']['page_relative_links']['icon'] = array('type' => 'image/png', 'url' => $settings['default_theme_url'] . '/images/icons/favicon.png');
		
	$context['zc']['load_css_stylesheets'][] = $zc['themes_url'] . '/' . $context['zc']['current_theme'] . '/css/style.css' . $context['zc']['string_help_browser_cache'];
		
	if (!empty($context['zc']['layerless']))
	{
		$context['zc']['container_alignment'] = !empty($context['zc']['theme_settings']['blog_page_alignment']) ? $context['zc']['theme_settings']['blog_page_alignment'] : $zc['settings']['community_page_alignment'];
		$context['zc']['container_width'] = !empty($context['zc']['theme_settings']['blog_page_width']) ? $context['zc']['theme_settings']['blog_page_width'] : $zc['settings']['community_page_width'];
		
		$settings['theme_url'] = $zc['themes_url'] . '/' . $context['zc']['current_theme'];
		$settings['images_url'] = $zc['themes_url'] . '/' . $context['zc']['current_theme'] . '/images';
		
		$context['zc']['extra_links'] = array();
	
		/* Internet Explorer 4/5 and Opera 6 just don't do font sizes properly. (they are big...)
			Thus, in Internet Explorer 4, 5, and Opera 6 this will show fonts one size smaller than usual.
			Note that this is affected by whether IE 6 is in standards compliance mode.. if not, it will also be big.
			Standards compliance mode happens when you use xhtml... */
		if ($context['browser']['needs_size_fix'])
			$context['zc']['load_css_stylesheets'][] = $settings['theme_url'] . '/css/fonts-compat.css';
			
		$context['zc']['load_js_files'][] = $context['zc']['forum_default_scripts_url'] . '/script.js' . $context['zc']['string_help_browser_cache'];
		
		if ($zc['with_software']['version'] == 'SMF 2.0')
			$context['zc']['load_js_files'][] = $context['zc']['forum_default_scripts_url'] . '/theme.js' . $context['zc']['string_help_browser_cache'];
			
		$context['zc']['page_relative_links']['help'] = array('url' => $scripturl . '?action=help', 'target' => '_blank');
		$context['zc']['page_relative_links']['search'] = array('url' => $scripturl . '?action=search');
		$context['zc']['page_relative_links']['contents'] = array('url' => $scripturl);
			
		$context['zc']['menu'] = zc_load_main_menu_array();
		
		if ($context['user']['is_guest'])
			$context['zc']['login_form_info'] = zc_load_login_form_info();
	}
		
	// PNG fix and csshover for IE < 7
	if ($context['browser']['is_ie6'] || $context['browser']['is_ie5'] || $context['browser']['is_ie4'])
	{
		if (!empty($context['zc']['layerless']) || $zc['with_software']['version'] != 'SMF 2.0')
			$context['zc']['load_js_files'][] = $zc['default_theme_url'] . '/scripts/pngfix.js';
			
		$context['zc']['extra_css_style'][] = 'body{behavior:url(' . $zc['default_theme_url'] . '/scripts/csshover.htc); font-size:100%;}';
		
		// only if layerless...
		if (!empty($context['zc']['layerless']))
			$context['zc']['load_css_stylesheets'][] = $settings['theme_url'] . '/css/ie6lte.css' . $context['zc']['string_help_browser_cache'];
	}
	
	if (!empty($context['current_blog']))
		$context['zc']['page_relative_links']['index'] = array('url' => $scripturl . '?blog=' . $context['current_blog'] . '.0');
		
	// If RSS feeds are enabled, advertise the presence of one.
	if (!empty($zc['settings']['blog_xml_enable']) && !empty($context['current_blog']))
		$context['zc']['page_relative_links']['alternate'] = array('type' => 'application/rss+xml', 'url' => $scripturl . '?zc=.xml;blog=' . $context['current_blog'] . '.0;type=rss', 'title' => $blog_info['name'] . ' - RSS');
	elseif (!empty($zc['settings']['blog_xml_enable']) && empty($context['current_article']))
		$context['zc']['page_relative_links']['alternate'] = array('type' => 'application/rss+xml', 'url' => $scripturl . '?zc=.xml;type=rss', 'title' => $context['zc']['site_name'] . ' - zCommunity - RSS');
	
	// load template files...
	zcLoadTemplate('Generic-index');
	zcLoadTemplate('Generic-list');
	zcLoadTemplate('Generic-form');
	zcLoadTemplate('index');
	
	// a success message to show the user?
	if (!empty($_SESSION['zc_success_msg']))
	{
		// load success lang file...
		zcLoadLanguage('Success');
		
		// format it and place it in a context variable so that it gets displayed to the user...
		$context['zc']['success_message'] = zcFormatTxtString($_SESSION['zc_success_msg']);
		
		// destroy this, because we only want to display success messages once...
		unset($_SESSION['zc_success_msg']);
	}
	
	// tell robots to not index pages with sort, desc, asc, all request variables
	if (isset($_REQUEST['sort']) || isset($_REQUEST['asc']) || isset($_REQUEST['desc']) || isset($_REQUEST['all']) || isset($_REQUEST['sort2']) || isset($_REQUEST['asc2']) || isset($_REQUEST['desc2']) || isset($_REQUEST['all2']) || (isset($_REQUEST['listStart']) && empty($_REQUEST['listStart'])) || (isset($_REQUEST['listStart2']) && empty($_REQUEST['listStart2'])))
		$context['robot_no_index'] = true;
		
	// also tell robots to not index pages that are basic lists of articles
	if (isset($_REQUEST['date']) || isset($_REQUEST['category']) || isset($_REQUEST['tag']))
		$context['robot_no_index'] = true;
			
	// syndication links...
	if (!empty($zc['settings']['blog_xml_enable']))
	{
		$xml_formats = array(
			'rss' => $txt['b277'],
			'atom' => $txt['b292'],
			'rdf' => $txt['b295'],
		);
		$gets = array(
			'all' => ' ',
			'articles' => ' ' . $txt['b66a'] . ' ',
			'comments' => ' ' . $txt['b15a'] . ' ',
		);
		
		$context['zc']['syndication'] = array('links' => array());
		foreach ($xml_formats as $format => $base_txt)
			foreach ($gets as $get => $more_txt)
				$context['zc']['syndication']['links'][] = '<a href="' . $scripturl . '?zc=.xml' . $context['zc']['blog_request'] . (!empty($article) && empty($blog) ? ';news' : '') . ';type=' . $format . ';get=' . $get . '" rel="nofollow"><img src="' . $context['zc']['default_images_url'] . '/icons/small_' . $format . '_icon.png" alt="" style="vertical-align:middle;" />&nbsp;&nbsp;' . sprintf($base_txt, $more_txt) . '</a>';
	}
	
	$extra_small_bottom_links = array();
	
	// I *hate* Internet Explorer!
	if (!empty($zc['settings']['firefox_icon']) && ($context['browser']['is_ie'] || $zc['settings']['firefox_icon'] == 1))
		$extra_small_bottom_links[] = '<a href="http://www.mozilla.com" title="' . $txt['b367'] . '" rel="nofollow" target="_blank">' . $txt['b537'] . '</a>';
	
	// Site Map link
	if (!empty($zc['settings']['blog_xml_enable']))
		$extra_small_bottom_links[] = '<a href="' . $scripturl . '?zc=.xml;get=blogs;type=sitemap">' . $txt['b558'] . '</a>';
	
	if (!isset($context['zc']['extra_small_bottom_links']))
		$context['zc']['extra_small_bottom_links'] = array();
		
	$context['zc']['extra_small_bottom_links'] = array_merge($extra_small_bottom_links, $context['zc']['extra_small_bottom_links']);
	
	if ($context['zc']['layerless'] && empty($context['zc']['no_change_template_layers']))
	{
		// get rid of 'main' layer if integrated software uses it
		if (in_array($zc['with_software']['version'], array('SMF 1.1.x')) && isset($context['zc']['template_layers']['main']))
			unset($context['zc']['template_layers']['main']);
			
		$context['zc']['template_layers']['html'] = array();
		$context['zc']['template_layers']['body'] = array();
	}
	
	if (empty($context['zc']['no_change_template_layers']))
		$context['zc']['template_layers']['body2'] = array();
	
	if (!empty($context['blog_control_panel']) && empty($context['zc']['no_change_template_layers']) && empty($context['zc']['error']))
	{
		$context['zc']['template_layers']['cp'] = array();
		zcLoadTemplate('ControlPanel');
	}
	
	$context['zc']['page_header'] = !empty($blog_info['name']) && empty($context['blog_control_panel']) ? $blog_info['name'] : $context['zc']['site_name'];
		
	$context['zc']['page_context_loaded'] = true;
}

/*
function zc_load_server_context()
{
}

function zc_load_user_context()
{
}*/

/*
function zc_load_browser_context()
{
	global $context;

	$context['browser'] = array(
		'is_opera' => strpos($_SERVER['HTTP_USER_AGENT'], 'Opera') !== false,
		'is_ie4' => strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 4') !== false && strpos($_SERVER['HTTP_USER_AGENT'], 'WebTV') === false,
		'is_safari' => strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== false,
		'is_mac_ie' => strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 5.') !== false && strpos($_SERVER['HTTP_USER_AGENT'], 'Mac') !== false,
		'is_web_tv' => strpos($_SERVER['HTTP_USER_AGENT'], 'WebTV') !== false,
		'is_konqueror' => strpos($_SERVER['HTTP_USER_AGENT'], 'Konqueror') !== false,
		'is_firefox' => preg_match('~(Firefox|Ice[wW]easel|IceCat)/~', $_SERVER['HTTP_USER_AGENT']) === 1,
		'is_iphone' => strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'iPod') !== false,
	);
	
	// figure out specific firefox version...
	if ($context['is_firefox'])
		$context['browser'] += array(
			'is_firefox1' => preg_match('~(Firefox|Ice[wW]easel|IceCat)/1\\.~', $_SERVER['HTTP_USER_AGENT']) === 1,
			'is_firefox2' => preg_match('~(Firefox|Ice[wW]easel|IceCat)/2\\.~', $_SERVER['HTTP_USER_AGENT']) === 1,
			'is_firefox3' => preg_match('~(Firefox|Ice[wW]easel|IceCat)/3\\.~', $_SERVER['HTTP_USER_AGENT']) === 1,
		);
	else
		$context['browser'] += array(
			'is_firefox1' => false,
			'is_firefox2' => false,
			'is_firefox3' => false,
		);
	
	// figure out specific opera version...
	if ($context['is_opera'])
		$context['browser'] += array(
			'is_opera6' => strpos($_SERVER['HTTP_USER_AGENT'], 'Opera 6') !== false,
			'is_opera7' => strpos($_SERVER['HTTP_USER_AGENT'], 'Opera 7') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera/7') !== false,
			'is_opera8' => strpos($_SERVER['HTTP_USER_AGENT'], 'Opera 8') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera/8') !== false,
			'is_opera9' => strpos($_SERVER['HTTP_USER_AGENT'], 'Opera 9') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera/9') !== false,
		);
	else
		$context['browser'] += array(
			'is_opera6' => false,
			'is_opera7' => false,
			'is_opera8' => false,
			'is_opera9' => false,
		);
		
	$context['browser']['is_gecko'] = strpos($_SERVER['HTTP_USER_AGENT'], 'Gecko') !== false && !$context['browser']['is_safari'] && !$context['browser']['is_konqueror'];
		
	// internet explorer...
	$context['browser'] += array(
		'is_ie5' => strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 5.0') !== false && !$context['browser']['is_opera'] && !$context['browser']['is_gecko'] && !$context['browser']['is_web_tv'],
		'is_ie5.5' => strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 5.5') !== false && !$context['browser']['is_opera'] && !$context['browser']['is_gecko'] && !$context['browser']['is_web_tv'],
		'is_ie6' => strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6') !== false && !$context['browser']['is_opera'] && !$context['browser']['is_gecko'] && !$context['browser']['is_web_tv'],
		'is_ie7' => strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 7') !== false && !$context['browser']['is_opera'] && !$context['browser']['is_gecko'] && !$context['browser']['is_web_tv'],
		'is_ie8' => strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 8') !== false && !$context['browser']['is_opera'] && !$context['browser']['is_gecko'] && !$context['browser']['is_web_tv'],
	);
	
	$context['browser']['is_ie'] = $context['browser']['is_ie4'] || $context['browser']['is_ie5'] || $context['browser']['is_ie5.5'] || $context['browser']['is_ie6'] || $context['browser']['is_ie7'] || $context['browser']['is_ie8'];
	$context['browser']['ie_standards_fix'] = !$context['browser']['is_ie8'];
	$context['browser']['needs_size_fix'] = ($context['browser']['is_ie5'] || $context['browser']['is_ie5.5'] || $context['browser']['is_ie4'] || $context['browser']['is_opera6']) && strpos($_SERVER['HTTP_USER_AGENT'], 'Mac') === false;
}*/
	
function zcLoadGlobalSettings()
{
	global $context, $modSettings, $txt, $zc, $zcFunc;
	
	if (($zc['settings'] = zc_cache_get_data('zc_global_settings', 90)) == null)
	{
		$zc['settings'] = array();
		$request = $zcFunc['db_query']("
			SELECT variable, value
			FROM {db_prefix}global_settings", __FILE__, __LINE__);
		while ($row = $zcFunc['db_fetch_row']($request))
			$zc['settings'][$row[0]] = $row[1];
		$zcFunc['db_free_result']($request);
			
		if (!function_exists('zc_prepare_global_settings_array') && file_exists($zc['sources_dir'] . '/Settings.php'))
				require_once($zc['sources_dir'] . '/Settings.php');
	
		if (function_exists('zc_prepare_global_settings_array'))
			zc_prepare_global_settings_array();
			
		if (!empty($context['zc']['zc_settings']))
			foreach($context['zc']['zc_settings'] as $k => $a)
				if (!in_array($k, array('_info_')))
				{
					if (!isset($zc['settings'][$k]))
						$zc['settings'][$k] = $a['value'];
						
					if ($a['type'] == 'text')
						$zc['settings'][$k] = $zcFunc['un_htmlspecialchars']($zc['settings'][$k]);
						
					if (!empty($a['needs_explode']) && !is_array($zc['settings'][$k]))
						$zc['settings'][$k] = !empty($zc['settings'][$k]) ? explode(',', $zc['settings'][$k]) : array();
				}
			
		// privkey and pubkey cannot be empty for recaptcha to be enabled...
		if (empty($zc['settings']['recaptcha_pubkey']) || empty($zc['settings']['recaptcha_privkey']))
			$zc['settings']['enable_recaptcha'] = 0;
					
		// wonder what this does ;)
		zc_prepare_admin_theme_settings_array();
			
		// now for admin theme settings...
		if (!empty($context['zc']['admin_theme_settings']))
			foreach($context['zc']['admin_theme_settings'] as $k => $a)
				if (!in_array($k, array('_info_')))
				{
					if (!isset($zc['settings'][$k]))
						$zc['settings'][$k] = $a['value'];
						
					if ($a['type'] == 'text')
						$zc['settings'][$k] = $zcFunc['un_htmlspecialchars']($zc['settings'][$k]);
						
					if (!empty($a['needs_explode']) && !is_array($zc['settings'][$k]))
						$zc['settings'][$k] = !empty($zc['settings'][$k]) ? explode(',', $zc['settings'][$k]) : array();
				}
			
		// variable global settings... depend upon forum version / stand-alone zcommunity
		$variable_global_settings = array(
			'zc_mode' => array(
				$zc['settings']['zc_mode'],
				'SMF 2.0' => $modSettings['zc_mode'],
				'SMF 1.1.x' => $modSettings['zc_mode']
			),
			'global_character_set' => array(
				'SMF 2.0' => empty($modSettings['global_character_set']) ? $txt['lang_character_set'] : $modSettings['global_character_set'],
				'SMF 1.1.x' => empty($modSettings['global_character_set']) ? $txt['lang_character_set'] : $modSettings['global_character_set']
			),
			'need_reverse_magic_quotes' => array(
				false,
				// SMF 2.0+ reverses magic quotes if magic quotes is on
				'SMF 2.0' => false,
				// SMF 1.1.x adds slashes to all post data if magic quotes is off... so just pretend it's always on for SMF 1.1.x
				'SMF 1.1.x' => true
			),
			'cookie_time' => array(
				isset($zc['settings']['cookie_time']) ? $zc['settings']['cookie_time'] : '',
				'SMF 2.0' => $modSettings['cookieTime'],
				'SMF 1.1.x' => $modSettings['cookieTime']
			),
			'enable_output_compression' => array(
				isset($zc['settings']['enable_output_compression']) ? $zc['settings']['enable_output_compression'] : 0,
				'SMF 2.0' => $modSettings['enableCompressedOutput'],
				'SMF 1.1.x' => $modSettings['enableCompressedOutput'],
			),
		);
		
		$version = !empty($zc['with_software']['version']) ? $zc['with_software']['version'] : 0;
		foreach ($variable_global_settings as $k => $v)
			$zc['settings'][$k] = $v[$version];
		
		// stats that *have to* be set....  variable => default_value
		$must_be_set = array('max_comment_id' => 0, 'max_article_id' => 0, 'community_news_num_articles' => 0, 'community_news_num_comments' => 0, 'community_total_articles' => 0, 'community_total_comments' => 0, 'community_total_blogs' => 0, 'attachments_dir' => $zc['main_dir'] . '/attachments', 'attachments_url' => $zc['main_url'] . '/attachments', 'max_size_attachments_dir' => 4194304);
		foreach ($must_be_set as $k => $v)
			if (!isset($zc['settings'][$k]))
				$zc['settings'][$k] = $v;

		// cookie time...
		if (isset($_POST['cookieneverexp']) || (!empty($_POST['cookielength']) && $_POST['cookielength'] == -1))
			$zc['settings']['cookie_time'] = 3153600;
		elseif (!empty($_POST['cookielength']) && ($_POST['cookielength'] >= 1 || $_POST['cookielength'] <= 525600))
			$zc['settings']['cookie_time'] = (int) $_POST['cookielength'];
			
		if (!empty($zc['settings']))
			zc_cache_put_data('zc_global_settings', $zc['settings'], 90);
	}
}
	
function zcLoadBlog()
{
	global $scripturl, $context, $txt, $blog_info, $blog, $article, $zcFunc;
	
	// need at least one of these...
	if (empty($blog) && empty($article))
		return false;
		
	$context['is_blog_owner'] = false;

	$request = $zcFunc['db_query']("
		SELECT b.description, b.name, b.member_groups, b.time_created, b.num_articles, b.num_comments, b.num_unapproved_articles, b.moderators, b.num_unapproved_comments, b.num_views, 
			b.blog_owner" . (!empty($article) ? ", b.blog_id" : '') . ",
			ps.*, ps.blog_id AS plugin_settings_row_exists,
			ts.*, ts.blog_id AS theme_settings_row_exists,
			bs.*, bs.blog_id AS blog_settings_row_exists
		FROM " . (empty($article) ? "{db_prefix}blogs AS b" : "{db_prefix}articles AS t
			LEFT JOIN {db_prefix}blogs AS b ON (b.blog_id = t.blog_id)") . "
			LEFT JOIN {db_prefix}settings AS bs ON (bs.blog_id = b.blog_id)
			LEFT JOIN {db_prefix}plugin_settings AS ps ON (ps.blog_id = b.blog_id)
			LEFT JOIN {db_prefix}theme_settings AS ts ON (ts.blog_id = b.blog_id)
		WHERE " . (empty($article) ? "b.blog_id = {int:current_blog}" : "t.article_id = {int:current_article}") . "
		LIMIT 1", __FILE__, __LINE__,
		array(
			'current_blog' => $blog,
			'current_article' => $article,
		)
	);
	if ($zcFunc['db_num_rows']($request) != 0)
	{
		$row = $zcFunc['db_fetch_assoc']($request);
		
		// means we're using $article to load the blog... so set $blog
		if (!empty($row['blog_id']))
		{
			$blog = $row['blog_id'];
			$_GET['blog'] = $blog;
		}
		elseif (empty($blog))
			return;
		
		$blog_info = array(
			'id' => $blog,
			'blog_owner' => $row['blog_owner'],
			'num_views' => $row['num_views'],
			'num_comments' => !empty($row['comments_require_approval']) ? $row['num_comments'] - $row['num_unapproved_comments'] : $row['num_comments'],
			'num_articles' => !empty($row['articles_require_approval']) ? $row['num_articles'] - $row['num_unapproved_articles'] : $row['num_articles'],
			'time_created' => $row['time_created'],
			'description' => $row['description'],
			'name' => $row['name'],
			'member_groups' => explode(',', $row['member_groups']),
			'moderators' => !empty($row['moderators']) ? explode(',', $row['moderators']) : array(),
			'theme_settings' => array(),
			'plugin_settings' => array(),
			'settings' => array(),
		);
		
		$context['zc']['defaultSettings'] = zc_prepare_blog_settings_array();
		if (!empty($context['zc']['defaultSettings']))
		{
			foreach ($context['zc']['defaultSettings'] as $key => $array)
				if (!in_array($key, array('_info_')))
				{
					$value = isset($row[$key]) ? $row[$key] : $array['value'];
					if ($array['type'] == 'text')
						$blog_info['settings'][$key] = $zcFunc['un_htmlspecialchars']($value);
					else
						$blog_info['settings'][$key] = $value;
					
					if (!empty($array['needs_explode']))
						$blog_info['settings'][$key] = !empty($blog_info['settings'][$key]) ? explode(',', $blog_info['settings'][$key]) : array();
				}
			
			$context['zc']['blog_settings_row_exists'] = !empty($row['blog_settings_row_exists']);
		}
		// doesn't need a row in the blog_settings table...
		else
			$context['zc']['blog_settings_row_exists'] = true;
		
		$blog_info['settings']['blogDescription'] = $blog_info['description'];
		$blog_info['settings']['blogName'] = $blog_info['name'];
		$blog_info['settings']['allowedGroups'] = $blog_info['member_groups'];
		
		$context['zc']['theme_settings_info'] = zc_prepare_theme_settings_array();
		if (!empty($context['zc']['theme_settings_info']))
		{
			foreach ($context['zc']['theme_settings_info'] as $key => $array)
				if (!in_array($key, array('_info_')))
				{
					$value = isset($row[$key]) ? $row[$key] : $array['value'];
					if ($array['type'] == 'text')
						$blog_info['theme_settings'][$key] = $zcFunc['un_htmlspecialchars']($value);
					else
						$blog_info['theme_settings'][$key] = $value;
					
					if (!empty($array['needs_explode']))
						$blog_info['theme_settings'][$key] = !empty($blog_info['theme_settings'][$key]) ? explode(',', $blog_info['theme_settings'][$key]) : array();
				}
			
			$context['zc']['theme_settings_row_exists'] = !empty($row['theme_settings_row_exists']);
		}
		// doesn't need a row in the theme_settings table...
		else
			$context['zc']['theme_settings_row_exists'] = true;
		
		$context['zc']['plugin_settings_info'] = zc_prepare_plugin_settings_array();
		if (!empty($context['zc']['plugin_settings_info']))
		{
			foreach ($context['zc']['plugin_settings_info'] as $key => $array)
				if (!in_array($key, array('_info_')))
				{
					$value = isset($row[$key]) ? $row[$key] : $array['value'];
					if ($array['type'] == 'text')
						$blog_info['plugin_settings'][$key] = $zcFunc['un_htmlspecialchars']($value);
					else
						$blog_info['plugin_settings'][$key] = $value;
					
					if (!empty($array['needs_explode']))
						$blog_info['plugin_settings'][$key] = !empty($blog_info['plugin_settings'][$key]) ? explode(',', $blog_info['plugin_settings'][$key]) : array();
				}
			
			$context['zc']['plugin_settings_row_exists'] = !empty($row['plugin_settings_row_exists']);
		}
		// doesn't need a row in the plugin_settings table...
		else
			$context['zc']['plugin_settings_row_exists'] = true;
		
		if (!empty($blog_info))
			$return = true;
	}
	// check to see if the article DOES exist...
	elseif (!empty($article))
	{
		$zcFunc['db_free_result']($request);
		$request = $zcFunc['db_query']("
			SELECT blog_id
			FROM {db_prefix}articles
			WHERE article_id = {int:current_article}
			LIMIT 1", __FILE__, __LINE__,
			array(
				'current_article' => $article
			)
		);
		// $article is invalid...
		if ($zcFunc['db_num_rows']($request) == 0)
			zc_fatal_error(array('zc_error_33', 'b170'));
		else
		{
			list($blog) = $zcFunc['db_fetch_row']($request);
			$zcFunc['db_free_result']($request);
			zc_redirect_exit('blog=' . $blog . '.0');
		}
	}
	// $blog is invalid...
	else
		$context['zc']['error'] = array('zc_error_33', 'b3003a');
	
	$zcFunc['db_free_result']($request);
		
	if (!empty($context['zc']['error']))
		$return = false;
		
	$context['current_blog'] = $blog;
	
	if (!empty($blog))
	{
		// user viewing page is a moderator of this blog?
		$context['user']['is_mod'] = !$context['user']['is_guest'] && in_array($context['user']['id'], $blog_info['moderators']);
		// user viewing page is blog owner?
		$context['is_blog_owner'] = !$context['user']['is_guest'] && !empty($blog_info['blog_owner']) && $context['user']['id'] == $blog_info['blog_owner'];
		$context['zc']['settings_empty_for_this_blog'] = !empty($context['zc']['settings_empty_for_this_blog']) || empty($blog_info);
		$context['zc']['blog_settings'] = isset($blog_info['settings']) ? $blog_info['settings'] : array();
		$context['zc']['plugin_settings'] = isset($blog_info['plugin_settings']) ? $blog_info['plugin_settings'] : array();
		$context['zc']['theme_settings'] = isset($blog_info['theme_settings']) ? $blog_info['theme_settings'] : array();
	}
	
	return $return;
}

function zc_load_blog_extra()
{
	global $context, $blog, $article, $zc, $blog_info, $txt, $scripturl;
	
	// start these as false...
	$context['can_post_articles'] = false;
	$context['can_moderate_blog'] = false;
	$context['can_view_blog'] = false;
			
	// failed to load the blog... and we needed to...
	if (!empty($context['zc']['error']))
		zc_fatal_error($context['zc']['error']);
		
	if (!empty($blog))
	{
		// are they allowed to moderate this blog?
		$context['can_moderate_blog'] = $context['can_moderate_any_blog'] || ($context['can_moderate_own_blogs'] && $context['is_blog_owner']) || $context['user']['is_mod'];
	
		if (!$context['is_blog_owner'] && !$context['user']['is_admin'] && !$context['can_moderate_blog'])
		{
			// if some groups are allowed to blog... check to see if this user is in one of them
			if (!empty($blog_info['member_groups']))
				if ($context['user']['is_guest'])
					$context['can_view_blog'] = in_array(-1, $blog_info['member_groups']);
				else
					$context['can_view_blog'] = count(array_intersect($blog_info['member_groups'], $context['user']['member_groups'])) > 0;
		
			// this user might be allowed explicitly by the users_allowed_access setting
			if (empty($context['can_view_blog']) && !empty($context['zc']['blog_settings']['users_allowed_access']) && !$context['user']['is_guest'])
				$context['can_view_blog'] = in_array($context['user']['id'], $context['zc']['blog_settings']['users_allowed_access']);
		}
		
		// admins, the owner of the blog, and moderators can all see the blog
		if ($context['user']['is_admin'] || $context['is_blog_owner'] || $context['can_moderate_blog'] || $context['can_view_any_blog'])
			$context['can_view_blog'] = true;
			
		// blog is in hidden mode... only admins and blog owners!
		if (!empty($context['zc']['blog_settings']['hideBlog']) && !$context['user']['is_admin'] && !$context['is_blog_owner'] && !$context['can_view_any_blog'])
			$context['can_view_blog'] = false;
	
		// they aren't allowed?
		if (empty($context['can_view_blog']))
			zc_fatal_error('zc_error_66');
		
		if (!$context['is_blog_owner'] && !$context['user']['is_admin'])
		{
			// start with false
			$temp = false;
		
			// if some groups are allowed to blog... check to see if this user is in one of them
			if (!empty($context['zc']['blog_settings']['groupsAllowedToBlog']))
				if ($context['user']['is_guest'])
					$temp = in_array(-1, $context['zc']['blog_settings']['groupsAllowedToBlog']);
				else
					$temp = count(array_intersect($context['zc']['blog_settings']['groupsAllowedToBlog'], $context['user']['member_groups'])) > 0;
		
			// this user might be allowed explicitly by the usersAllowedToBlog setting
			if (!empty($context['zc']['blog_settings']['usersAllowedToBlog']) && !$context['user']['is_guest'])
				$temp = in_array($context['user']['id'], $context['zc']['blog_settings']['usersAllowedToBlog']) ? true : $temp;
		}
		// obviously blog owners and admins can always post new articles
		else
			$temp = true;
		
		// revise the can_post_articles variable
		$context['can_post_articles'] = $temp;
		
		// extra check for posting polls...
		if (!$context['user']['is_admin'] && !$context['is_blog_owner'])
			$context['can_post_polls'] = false;
	}
	// community news then?
	else
	{
		$context['can_post_articles'] = $context['can_post_community_news'];
		
		// can't post polls when dealing with community news...
		$context['can_post_polls'] = false;
	}

	// these are special, because they incorporate $_REQUEST['start']
	$context['zc']['article_request'] = !empty($article) ? ';article=' . $article . '.' . $_REQUEST['start'] : '';
	$context['zc']['blog_request'] = !empty($blog) ? ';blog=' . $blog . (!empty($article) ? '.0' : '.' . $_REQUEST['start']) : '';
			
	// this is combo of article/blog/zc request
	if (!empty($context['zc']['blog_request']) || !empty($context['zc']['article_request']))
		$context['zc']['zc_blog_article_request'] = !empty($context['zc']['blog_request']) ? $context['zc']['blog_request'] : $context['zc']['article_request'];
	
	// add link to link tree...
	if (!empty($blog_info['name']) && !empty($blog))
		$context['zc']['link_tree']['blog'] = '<a href="' . $scripturl . '?blog=' . $blog . '.0">' . $blog_info['name'] . '</a>';
		
	// Blog Control Panel link
	if (!empty($blog) && ($context['is_blog_owner'] || $context['user']['is_admin']))
		$context['zc']['extra_above_side_windows']['options']['links'][] = '<a href="' . $scripturl . '?zc=bcp;u=' . $blog_info['blog_owner'] . $context['zc']['blog_request'] . '" rel="nofollow">' . $txt['b281'] . ' ' . $txt['b279'] . '</a>';
	
	// new article (blog) link
	if ($context['can_post_articles'] && !empty($blog))
		$context['zc']['extra_above_side_windows']['options']['links'][] = '<a href="' . $scripturl . '?zc=post;blog='. $blog .'.0;article" rel="nofollow">' . $txt['b76'] . '</a>';
	
	// new poll (blog) link
	if ($context['can_post_polls'] && !empty($blog) && !empty($context['zc']['blog_settings']['enablePollsWindow']))
		$context['zc']['extra_above_side_windows']['options']['links'][] = '<a href="' . $scripturl . '?zc=post;blog=' . $blog . '.0;poll" rel="nofollow">' . $txt['b87'] . '</a>';
		
	// post news link
	if ($context['can_post_articles'] && empty($blog))
		$context['zc']['extra_above_side_windows']['options']['links'][] = '<a href="' . $scripturl . '?zc=post;article" rel="nofollow">' . $txt['b342'] . '</a>';
}

function zcLoadUserPreferences($member_id = null)
{
	global $context;
	global $zcFunc;
	
	if ($context['user']['is_guest'])
		return false;
		
	if ($member_id === null)
		$member_id = $context['user']['id'];
	
	$preferences = array();
	
	// prepares $context['zc']['preferences']
	$context['zc']['preferences'] = zc_prepare_preferences_array();
	
	if (($preferences = zc_cache_get_data('zc_user_preferences' . $member_id, 90)) == null)
	{
		// get this user's blog preferences
		$request = $zcFunc['db_query']("
			SELECT *
			FROM {db_prefix}preferences
			WHERE member_id = {int:member_id}
			LIMIT 1", __FILE__, __LINE__,
			array(
				'member_id' => $member_id
			)
		);
			
		if ($zcFunc['db_num_rows']($request) > 0)
		{
			$row = $zcFunc['db_fetch_assoc']($request);
			foreach ($context['zc']['preferences'] as $k => $a)
				if (!in_array($k, array('_info_')))
				{
					if ($a['type'] == 'text')
						$preferences[$k] = !empty($row[$k]) ? $zcFunc['un_htmlspecialchars']($row[$k]) : '';
					else
						$preferences[$k] = isset($row[$k]) ? $row[$k] : '';
						
					if (!empty($a['needs_explode']))
						$preferences[$k] = !empty($preferences[$k]) ? explode(',', $preferences[$k]) : array();
				}
		}
		// they don't have a row in the blog_preferences table... make one!
		else
			// now insert their row
			$zcFunc['db_insert']('insert', '{db_prefix}preferences', array('member_id' => 'int'), array('member_id' => $member_id));
		$zcFunc['db_free_result']($request);
	}
	else
		foreach ($context['zc']['preferences'] as $k => $a)
			if (!in_array($k, array('_info_')) && !isset($preferences[$k]))
			{
				if ($a['type'] == 'text')
					$preferences[$k] = !empty($a['value']) ? $zcFunc['un_htmlspecialchars']($a['value']) : '';
				else
					$preferences[$k] = isset($a['value']) ? $a['value'] : '';
					
				if (!empty($a['needs_explode']))
					$preferences[$k] = !empty($preferences[$k]) ? explode(',', $preferences[$k]) : array();
			}

	// clean up a bit
	unset($context['zc']['preferences']);
	
	if ($member_id != $context['user']['id'])
		return $preferences;
	
	$context['user']['blog_preferences'] = $preferences;
}

function zcLoadPermissions()
{
	global $context, $zcFunc;
	
	if (!isset($context['user']['zc_permissions']))
		$context['user']['zc_permissions'] = array();
		
	if (!function_exists('zcPreparePermissionsArray'))
	{
		global $zc;
			
		if (file_exists($zc['sources_dir'] . '/Permissions.php'))
			require_once($zc['sources_dir'] . '/Permissions.php');
	}
		
	// builds $context['zc']['permissions'] and $context['zc']['non_guest_permissions'] arrays...
	if (function_exists('zcPreparePermissionsArray'))
		zcPreparePermissionsArray();
		
	// admins can do anything and everything
	if ($context['user']['is_admin'])
	{
		if (!empty($context['zc']['permissions']))
			foreach ($context['zc']['permissions'] as $permission => $array)
				if (!in_array($permission, array('_info_')))
					$context['user']['zc_permissions'][] = $permission;
	}
	else
	{
		// get all rows from blog_permissions table for member groups this user is in
		$request = $zcFunc['db_query']("
			SELECT add_deny, permission
			FROM {db_prefix}permissions
			WHERE group_id IN ({array_int:groups})
				AND add_deny = 1", __FILE__, __LINE__,
			array(
				'groups' => $context['user']['member_groups']
			)
		);
			
		while ($row = $zcFunc['db_fetch_assoc']($request))
			// guests can't do certain things....
			if ($context['user']['is_guest'] && !in_array($row['permission'], $context['zc']['non_guest_permissions']))
				$context['user']['zc_permissions'][] = $row['permission'];
			elseif (!in_array($row['permission'], $context['user']['zc_permissions']))
				$context['user']['zc_permissions'][] = $row['permission'];
	}
}

function zcLoadLanguage($template_name, $lang = '', $fatal = false, $dir = null, $force_load = false)
{
	global $zc, $txt, $scripturl, $context;
	
	// if $lang empty, default to the user's language
	if ($lang == '')
	{
		global $context;
		$lang = $context['user']['language'];
	}
		
	// if still empty, default to english
	if (empty($lang))
		$lang = 'english';
		
	$files = array(
		$lang => $template_name . '.' . $lang . '.php',
		$zc['language'] => $template_name . '.' . $zc['language'] . '.php',
		'english' => $template_name . '.english.php'
	);
	
	$files = array_unique($files);
	
	// if $dir is empty... use main Languages directory...
	if (empty($dir))
		$dir = $zc['main_dir'] . '/Languages';
	
	$lang_loaded = false;
	foreach ($files as $k => $file)
	{
		if (!$force_load && isset($zc['langs_loaded'][$template_name]) && $zc['langs_loaded'][$template_name] == $k)
			return $k;
		
		if (file_exists($dir . '/' . $file))
		{
			require_once($dir . '/' . $file);
			$lang_loaded = $k;
			break;
		}
	}
		
	if ($fatal && $lang_loaded == false)
		if (function_exists('zc_log_error'))
		{
			global $txt;
		
			// attempt to load Errors lang file in the site's default language...
			if ($template_name != 'Errors')
				zcLoadLanguage('Errors', $zc['language']);
			
			zc_log_error(sprintf((isset($txt['zc_error_87']) ? $txt['zc_error_87'] : 'Failed to load language file: %1$s'), (string) $template_name), 'language');
		}
		
	if ($lang_loaded != false)
	{
		if (!isset($zc['langs_loaded']))
			$zc['langs_loaded'] = array();
			
		$zc['langs_loaded'][$template_name] = $lang_loaded;
	}
			
	return $lang_loaded;
}

function zcLoadTemplate($template, $fatal = true)
{
	global $context, $zc;
	
	if (!isset($context['zc']['current_theme']))
	{
		// do we want to show this page without layers?
		$context['zc']['layerless'] = ((!isset($_REQUEST['zc']) || $_REQUEST['zc'] != 'bcp') && !empty($blog) && !empty($context['zc']['theme_settings']['independent'])) || !empty($zc['settings']['toggle_all_layerless']) || (empty($blog) && !empty($zc['settings']['community_page_without_layers']));
	}
		
	$template_file = $template . '.template.php';
	// first try to get the template file from the current theme....
	if (isset($context['zc']['current_theme']) && file_exists($zc['themes_dir'] . '/' . $context['zc']['current_theme'] . '/' . $template_file))
		require_once($zc['themes_dir'] . '/' . $context['zc']['current_theme'] . '/' . $template_file);
	// ok... try the default theme...
	elseif (file_exists($zc['themes_dir'] . '/default/' . $template_file))
		require_once($zc['themes_dir'] . '/default/' . $template_file);
	// failed to load the template!
	else
	{
		global $txt;
		
		// attempt to load Errors lang file in the site's default language...
		zcLoadLanguage('Errors', $zc['language']);
			
		$err_msg = sprintf((isset($txt['zc_error_88']) ? $txt['zc_error_88'] : 'Failed to load template file: %1$s'), (string) $template);
		zc_log_error($err_msg, 'template');
		
		if ($fatal && !in_array($template, array('Errors', 'index')) && function_exists('zc_fatal_error'))
			zc_fatal_error($err_msg);
		elseif ($fatal)
			die($err_msg);
		else
			return false;
	}
		
	return true;
}

function zc_load_sub_template($template_name, $fatal = false, $prefix = 'zc_template_')
{
	global $zc;

	$sub_template_func = $prefix . $template_name;
	
	if (function_exists($sub_template_func))
		$sub_template_func();
	else
	{
		global $txt;
		
		// attempt to load Errors lang file in the site's default language...
		zcLoadLanguage('Errors', $zc['language']);
		
		$err_msg = sprintf((isset($txt['zc_error_86']) ? $txt['zc_error_86'] : 'Failed to load sub template: %1$s'), (string) $template_name);
		zc_log_error($err_msg, 'template');
		if ($fatal === false)
			zc_fatal_error($err_msg);
		else
			die($err_msg);
	}
}

function zcLoadBlogCategories()
{
	global $scripturl, $blog, $zcFunc;
	
	// Get all the blog categories in this blog
	$request = $zcFunc['db_query']("
		SELECT blog_category_id, name, total
		FROM {db_prefix}categories
		WHERE blog_id = {int:current_blog}
		ORDER BY cat_order ASC", __FILE__, __LINE__,
		array(
			'current_blog' => $blog
		)
	);

	$return = array();
	while ($row = $zcFunc['db_fetch_assoc']($request))
	{
		$row['name'] = $zcFunc['un_htmlspecialchars']($row['name']);
		zc_censor_text($row['name']);
	
		$return[$row['blog_category_id']] = array(
			'id' => $row['blog_category_id'],
			'name' => $row['name'],
			'total' => $row['total'],
			'link' => '<a href="' . $scripturl . '?blog=' . $blog . '.0;category=' . $row['blog_category_id'] . '">' . $row['name'] . '</a>',
		);
	}
	$zcFunc['db_free_result']($request);

	return $return;
}

function zcLoadBlogTags($blogs = null)
{
	global $scripturl, $context, $zcFunc, $zc;
	
	// if not empty... it has to be an array
	if (!empty($blogs) && !is_array($blogs))
		$blogs = array($blogs);
		
	if (count($blogs) > 1)
		$limit = isset($zc['settings']['max_tags_in_globaltag_window']) ? $zc['settings']['max_tags_in_globaltag_window'] : 48;
	else
		$limit = isset($context['zc']['blog_settings']['max_num_tags_in_tag_window']) ? $context['zc']['blog_settings']['max_num_tags_in_tag_window'] : 48;
	
	$request = $zcFunc['db_query']("
		SELECT tag, num_articles, blog_id
		FROM {db_prefix}tags" . (!empty($blogs) ? "
		WHERE blog_id IN ({array_int:blogs})" : '') . "
		ORDER BY num_articles DESC" . (!empty($limit) ? "
		LIMIT {int:limit}" : ''), __FILE__, __LINE__,
		array(
			'limit' => $limit,
			'blogs' => $blogs
		)
	);
		
	$tags = array();
	$total_instances = 0;
	while ($row = $zcFunc['db_fetch_assoc']($request))
	{
		$row['tag'] = $zcFunc['un_htmlspecialchars']($row['tag']);
		zc_censor_text($row['tag']);
		
		$total_instances += $row['num_articles'];
		$tags[$row['tag']] = array(
			'link' => '<a href="' . $scripturl . '?' . (!empty($row['blog_id']) ? 'blog=' . $row['blog_id'] . '.0;' : (empty($context['zc']['zCommunity_is_home']) ? 'zc;' : '')) . 'tag=' . urlencode($row['tag']) . '" style="white-space:nowrap;">' . $row['tag'] . '</a>',
			'tag' => $row['tag'],
			'num_articles' => $row['num_articles'],
		);
	}
	ksort($tags);
	$zcFunc['db_free_result']($request);
	
	return array($tags, $total_instances);
}

function zc_cache_get_data($key, $value, $ttl = 120)
{
	global $zc;
	
	if (in_array($zc['with_software']['version'], $zc['smf_versions']))
		cache_get_data($key, $value, $ttl);
}

function zc_cache_put_data($key, $value, $ttl = 120)
{
	global $zc;
	
	if (in_array($zc['with_software']['version'], $zc['smf_versions']))
		cache_put_data($key, $value, $ttl);
}
/*
function load_session()
{
	global $zc;
	
	@ini_set('session.use_cookies', true);
	@ini_set('session.use_only_cookies', false);
	
	if (!isset($_SESSION['session_var']))
	{
		$_SESSION['session_value'] = uniqid(md5(mt_rand()), true);
	}
	$zc['session_id'] = $_SESSION['session_value'];
}*/
	
?>