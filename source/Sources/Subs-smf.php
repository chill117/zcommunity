<?php

if (!defined('zc'))
	die('Hacking attempt...');

function zc_update_forum_settings($updates)
{
	global $zcFunc, $zc;
	
	// nothing to update....
	if (empty($updates))
		return;
		
	// figure out forum's settings table info
	if (in_array($zc['with_software']['version'], $zc['smf_versions']))
	{
		$table = '{db_prefix}{table:settings}';
		
		$columns = array(
			'{tbl:settings::column:variable}' => 'string',
			'{tbl:settings::column:value}' => 'string',
		);

		// kill the cache
		zc_cache_put_data('modSettings', null, 90);
	}
	// not valid forum version...
	else
		return false;
		
	$data = array();
	foreach ($updates as $k => $v)
		$data[] = array(
			'{tbl:settings::column:variable}' => $k,
			'{tbl:settings::column:value}' => $v
		);
		
	$zcFunc['db_insert']('replace', $table, $columns, $data);
		
	return true;
}

function zc_forum_permission_check($permission, $boards = null)
{
	global $zc;
	$return = false;
	if (in_array($zc['with_software']['version'], $zc['smf_versions']))
	{
		if (!function_exists('allowedTo'))
		{
			require_once($zc['with_software']['sources_dir'] . '/Security.php');
		}
		
		if (function_exists('allowedTo'))
			$return = allowedTo($permission, $boards);
	}
	return $return;
}

function zc_get_meta_for_smf($data = null)
{
	global $context, $zc, $zcFunc;
	
	if (!empty($data))
	{
		if (!empty($data['description']))
			$context['zc']['meta']['description'] = zcTruncateText(strip_tags($zcFunc['un_htmlspecialchars'](str_replace('<br />', ' ', $data['description']))), 80, $break_with = ' ', $min_after_break = 1, $padding_element = '');
			
		if (!empty($data['keywords']))
			$context['zc']['meta']['keywords'] = zcTruncateText(strip_tags($zcFunc['un_htmlspecialchars'](str_replace('<br />', ' ', $data['keywords']))), 80, $break_with = ' ', $min_after_break = 1, $padding_element = '');
	}
	elseif (in_array($zc['with_software']['version'], $zc['smf_versions']))
	{
		global $board_info;
		
		if (!empty($board_info))
		{
			if (!empty($board_info['cat']['name']))
				$context['zc']['meta']['keywords'] = zcTruncateText(strip_tags($zcFunc['un_htmlspecialchars'](str_replace('<br />', ' ', $board_info['cat']['name']))), 80, $break_with = ' ', $min_after_break = 1, $padding_element = '');
			if (!empty($board_info['name']))
				$context['zc']['meta']['keywords'] .= ' ' . zcTruncateText(strip_tags($zcFunc['un_htmlspecialchars'](str_replace('<br />', ' ', $board_info['name']))), 80, $break_with = ' ', $min_after_break = 1, $padding_element = '');
			if (!empty($board_info['description']))
			{
				$description = zcTruncateText(strip_tags($zcFunc['un_htmlspecialchars'](str_replace('<br />', ' ', $board_info['description']))), 80, $break_with = ' ', $min_after_break = 1, $padding_element = '');
				$context['zc']['meta']['keywords'] .= ' ' . $description;
				$context['zc']['meta']['description'] = $description;
			}
		}
	}
}

function zc_load_page_context_smf()
{
	global $context, $zc, $modSettings;
	
	if (!empty($context['template_layers']) && empty($context['zc']['no_change_template_layers']))
		foreach ($context['template_layers'] as $layer)
			$context['zc']['template_layers'][$layer] = array('prefix' => 'template_');
		
	// clear these ...
	$context['template_layers'] = array();
	$context['linktree'] = array();
	
	$context['zc']['templates_other_copyrights'][] = 'theme_copyright';
	$modSettings['cookieTime'] = $zc['settings']['cookie_time'];
	$context['never_expire'] = $context['zc']['cookie_never_expire'];
	
	$context['zc']['raw_header_1'] = '';
	$context['zc']['raw_header_2'] = '';
	
	if ($zc['with_software']['version'] == 'SMF 2.0')
	{
		global $settings, $scripturl, $txt, $options, $user_info;
		
		if ($user_info['unread_messages'] > (isset($_SESSION['unread_messages']) ? $_SESSION['unread_messages'] : 0))
			$context['user']['popup_messages'] = true;
		else
			$context['user']['popup_messages'] = false;
		
		$context['show_pm_popup'] = $context['user']['popup_messages'] && !empty($options['popup_messages']) && (!isset($_REQUEST['action']) || $_REQUEST['action'] != 'pm');
		
		$context['zc']['raw_header_1'] .= '
<script language="JavaScript" type="text/javascript"><!-- // --><![CDATA[
	var smf_theme_url = "' . $settings['theme_url'] . '";
	var smf_default_theme_url = "' . $settings['default_theme_url'] . '";
	var smf_images_url = "' . $settings['images_url'] . '";
	var smf_scripturl = "' . $scripturl . '";
	var smf_iso_case_folding = ' . ($context['server']['iso_case_folding'] ? 'true' : 'false') . ';
	var smf_charset = "' . $context['character_set'] . '";';
		
		if ($context['show_pm_popup'])
			$context['zc']['raw_header_1'] .= '
	if (confirm("' . $txt['show_personal_messages'] . '"))
		window.open("' . $scripturl . '?action=pm");';
			
		$context['zc']['raw_header_1'] .= '
	var ajax_notification_text = "' . $txt['ajax_in_progress'] . '";
	var ajax_notification_cancel_text = "' . $txt['modify_cancel'] . '";
// ]]></script>';
	
		$context['zc']['raw_header_2'] .= '
<script language="JavaScript" type="text/javascript"><!-- // --><![CDATA[
	// Create the main header object.
	var mainHeader = new smfToggle("upshrink", ' . (empty($options['collapse_header']) ? 'false' : 'true') . ');
	mainHeader.useCookie(' . ($context['user']['is_guest'] ? 1 : 0) . ');
	mainHeader.setOptions("collapse_header", "' . $context['session_id'] . '");
	mainHeader.addToggleImage("upshrink", "/upshrink.gif", "/upshrink2.gif");
	mainHeader.addTogglePanel("user_section");
	mainHeader.addTogglePanel("news_section");
// ]]></script>';
	}
	elseif ($zc['with_software']['version'] == 'SMF 1.1.x')
	{
		global $settings, $scripturl;
	
		$context['zc']['raw_header_1'] .= '
<script language="JavaScript" type="text/javascript"><!-- // --><![CDATA[
	var smf_theme_url = "' . $settings['theme_url'] . '";
	var smf_images_url = "' . $settings['images_url'] . '";
	var smf_scripturl = "' . $scripturl . '";
	var smf_iso_case_folding = ' . ($context['server']['iso_case_folding'] ? 'true' : 'false') . ';
	var smf_charset = "' . $context['character_set'] . '";
// ]]></script>';
	}
}

function zc_load_extra_smf()
{
	global $context, $scripturl, $settings, $zc, $user_info;

	$context['user'] += array(
		'ip' => $user_info['ip'],
		'member_groups' => $user_info['groups'],
		'is_guest' => $user_info['is_guest'],
		'is_admin' => $user_info['is_admin'],
		'buddies' => $user_info['buddies'],
		'language' => $user_info['language'],
	);
	
	$context['zc']['link_templates'] += array(
		'login' => '<a href="' . $scripturl . '?action=login">%1$s</a>',
		'register' => '<a href="' . $scripturl . '?action=register">%1$s</a>',
		'user_profile' => '<a href="' . $scripturl . '?action=profile;u=%1$d"%3$s>%2$s</a>',
		'trackip' => '<a href="' . $scripturl . '?action=trackip;ip=%1$s"%3$s>%2$s</a>',
	);
	
	if ($zc['with_software']['version'] == 'SMF 2.0')
		$context['zc']['string_help_browser_cache'] = '?rc1';
	elseif ($zc['with_software']['version'] == 'SMF 1.1.x')
		$context['zc']['string_help_browser_cache'] = '?fin11';
	
	if ($zc['with_software']['version'] == 'SMF 2.0')
	{
		$context['zc']['forum_default_scripts_url'] = $settings['default_theme_url'] . '/scripts';
		$context['zc']['forum_default_css_url'] = $settings['default_theme_url'] . '/css';
	}
	elseif ($zc['with_software']['version'] == 'SMF 1.1.x')
	{
		$context['zc']['forum_default_scripts_url'] = $settings['default_theme_url'];
		$context['zc']['forum_default_css_url'] = $settings['default_theme_url'];
	}
}

?>