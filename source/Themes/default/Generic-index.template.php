<?php

if (!defined('zc'))
	die('Hacking attempt...');

function zc_template_html_above()
{
	global $context, $settings, $scripturl, $txt, $zc;

	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"', $context['right_to_left'] ? ' dir="rtl"' : '', '>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=', $context['character_set'], '" />
<meta name="description" content="', !empty($context['zc']['meta']['description']) ? $context['zc']['meta']['description'] : $context['page_title_html_safe'], '" />
<meta name="keywords" content="', !empty($context['zc']['meta']['keywords']) ? $context['zc']['meta']['keywords'] : 'community blogging smf', '" />';

	// tell robots to not index?
	if (!empty($context['robot_no_index']))
		echo '
<meta name="robots" content="noindex" />';

	// load js files?
	if (!empty($context['zc']['load_js_files']))
		foreach ($context['zc']['load_js_files'] as $url)
			echo '
<script type="text/javascript" src="', $url, '"></script>';

	// raw header data...
	if (!empty($context['zc']['raw_header_1']))
		echo $context['zc']['raw_header_1'];
	
	echo '
<title>', $context['page_title_html_safe'], '</title>';

	// load stylesheets?
	if (!empty($context['zc']['load_css_stylesheets']))
		foreach ($context['zc']['load_css_stylesheets'] as $url)
			echo '
<link rel="stylesheet" type="text/css" href="', $url, '" />';

	// special style info?
	if (!empty($context['zc']['extra_css_style']))
		echo '
<style type="text/css" media="screen">', implode('
', $context['zc']['extra_css_style']), '
</style>';

	// relative links...
	if (!empty($context['zc']['page_relative_links']))
		foreach ($context['zc']['page_relative_links'] as $rel => $array)
			echo '
<link rel="', $rel, '"', !empty($array['type']) ? ' type="' . $array['type'] . '"' : '', ' href="', $array['url'], '"', !empty($array['target']) ? ' target="' . $array['target'] . '"' : '', ' />';

	// SMF uses this to help modders...
	if (!empty($context['html_headers']))
		echo $context['html_headers'];

	// more raw header data...
	if (!empty($context['zc']['raw_header_2']))
		echo $context['zc']['raw_header_2'];
	
	// javascripts we want to use...
	if (!empty($context['zc']['raw_javascript']) && is_array($context['zc']['raw_javascript']))
		echo '
	<script language="JavaScript" type="text/javascript"><!-- // --><![CDATA[', implode('
	', $context['zc']['raw_javascript']), '
	// ]]></script>';

	echo '
</head>
<body>';
}

function zc_template_html_below()
{
	echo '
</body>
</html>';
}
	
function zc_template_syndication_links()
{
	global $context, $txt;
	
	$i = 0;
	$m = 0;
	$total = count($context['zc']['syndication']['links']);
	foreach ($context['zc']['syndication']['links'] as $link)
	{
		$i++;
		$m++;
		
		if ($m == 1)
			echo '
		<div class="needsPadding">';
				
		echo '
		' . $link . ($m != 3 ? '&nbsp;&nbsp;' : '');
			
		if ($m == 3 || $i == $total)
			echo '
		</div>';
					
		// start the show/hide div for links beyond 3...
		if ($i == 3 && $i != $total)
		{
			echo '
		<a href="javascript:void(0);" id="showSyndicationLinks" onClick="document.getElementById(\'showSyndicationLinks\').style.display = \'none\'; document.getElementById(\'moreSyndicationLinks\').style.display = \'block\';" style="display:block;"><b>'. $txt['b296'] .'</b></a>
		<div id="moreSyndicationLinks" style="display:none;">';
		}
			
		if ($i == $total && $i > 3)
			echo '
		</div>';
			
		if ($m >= 3)
			$m = 0;
	}
}
	
function zc_template_main_guts()
{
	global $context;
	
	// display error message(s)
	if (empty($context['blog_control_panel']) && !empty($context['zc']['errors']) && empty($context['zc']['error']))
		zc_template_error_table();
	// display a success message if there was one...
	elseif (empty($context['blog_control_panel']) && !empty($context['zc']['success_message']))
		zc_template_show_success();
			
	// if there was an error... display it
	if (!empty($context['zc']['error']))
		zc_template_show_error();
	// viewing an article or articles?... show them
	elseif (!empty($context['zc']['articles']) && empty($context['zc']['previewing']))
		zc_template_show_articles();
	// viewing a list of articles?
	elseif (!empty($context['zc']['list_of_articles']) && empty($context['zc']['previewing']))
		zc_template_list_items($context['zc']['list_of_articles'], $context['zc']['list1']);
	// community page?
	elseif (!empty($context['zc']['viewing_community_page']) && empty($context['zc']['previewing']))
		zc_template_showMainBlocks();
	// zc sub_sub template?
	elseif (!empty($context['zc']['sub_sub_template']))
		zc_load_sub_template($context['zc']['sub_sub_template']);
}

function zc_template_confirmation_page()
{
	global $txt, $context;
	echo '
		<table width="100%">
			<tr class="blogRowTitle">
				<td align="center">', $txt['b278'], '</td>
			</tr>
			<tr class="needsPadding">
				<td align="center" style="padding:8px;">', $context['zc']['confirm_text'], '</td>
			</tr>
			<tr class="needsPadding">
				<td align="center">
					<table align="center">';
					
	foreach ($context['zc']['requires_confirmation'] as $key)
	{
		echo '
						<tr class="needsPadding">
							<td align="center">';
							
		if (!empty($context['zc']['blog_names'][$key]))
			echo $context['zc']['blog_names'][$key];
		elseif (!empty($context['zc']['article_names'][$key]))
			echo $context['zc']['article_names'][$key];
		
		echo '
							</td>
						</tr>';
	}
	
	echo '
					</table>
				</td>
			</tr>
			<tr class="needsPadding" style="font-size:11px;">
				<td align="center" style="padding:8px;"><a class="zcButtonLink" href="', $context['zc']['confirm_href'], '" rel="nofollow">', $txt['b67'], '</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a class="zcButtonLink" href="', $context['zc']['cancel_href'], '" rel="nofollow">', $txt['modify_cancel'], '</a></td>
			</tr>
		</table>';
}

function zc_template_simple_popup()
{
	global $txt;

	echo '
		<div class="morePadding" style="text-align:center;">';
			
	zc_template_main_guts();
			
	echo '<br />
			<a href="javascript:self.close();">', $txt['b305'], '</a>
		</div>';
}

function zc_template_sandwich()
{
	global $context;
	
	if (empty($context['zc']['sandwich_inner_template']))
		return;
		
	zc_template_sandwich_above();
	
	$inner_template = '';
	if (function_exists('zc_template_' . $context['zc']['sandwich_inner_template']))
		$inner_template = 'zc_template_' . $context['zc']['sandwich_inner_template'];
	elseif (function_exists($context['zc']['sandwich_inner_template']))
		$inner_template = $context['zc']['sandwich_inner_template'];
		
	if (function_exists($inner_template))
		$inner_template();
		
	zc_template_sandwich_below();
}

function zc_template_error_table()
{
	global $txt, $context;
	
	if (empty($context['zc']['errors']))
		return;
				
	zc_template_sandwich_above('err');
			
	echo '
			<div class="morePadding" style="text-align:center;" id="err">
				<div style="font-weight: bold;">
					', $txt['b473'], ':
				</div>
				<div style="padding:1ex 0 1ex 0;">';
				
	$first = true;
	foreach ($context['zc']['errors'] as $error)
	{
		if (empty($first))
			echo '<br />';
		echo zcFormatTxtString($error);
		$first = false;
	}
			
	echo '
				</div>
			</div>';
				
	zc_template_sandwich_below('err');
	
	if (empty($context['blog_control_panel']))
		echo '<br />';
}

function zc_template_show_error()
{
	global $context, $txt;
		
	zc_template_sandwich_above('err');
	
	echo '
					<div class="morePadding" style="text-align:center;">', zcFormatTxtString($context['zc']['error']), '</div>';
					
	if (!empty($context['zc']['show_guest_msg']))
		echo '
					<div class="morePadding" style="text-align:center;">', $txt['zc_error_1'], '</div>';
					
	if (!empty($context['zc']['show_back_link']))
		echo '
					<div class="morePadding" style="text-align:center;">
						<a href="javascript:history.go(-1)">', $txt['b3022'], '</a>
					</div>';
		
	zc_template_sandwich_below('err');
}

function zc_template_show_success()
{
	global $context;
		
	zc_template_sandwich_above('success');
	
	echo '
					<div class="morePadding" style="text-align:center;">', $context['zc']['success_message'], '</div>';
		
	zc_template_sandwich_below('success');
	
	if (empty($context['blog_control_panel']))
		echo '<br />';
}

function zc_template_show_message()
{
	global $context;
	
	if (empty($context['zc']['text']))
		return;
		
	echo '
		<div class="needsPadding">', $context['zc']['text'], '</div>';
}

function zc_template_dhtml_popup_login()
{
	global $blog, $context, $txt, $scripturl, $modSettings;
	
	if (empty($context['zc']['login_form_info']))
		return;
	
	echo '
	<div id="popup_login" class="popup_login">
		<center>
		<div class="popup_login_outer">
			<div class="popup_login_inner">
				<span class="hoverBoxClose" onMouseUp="document.getElementById(\'popup_login\').style.display = \'none\';" title="'. $txt['b305'] .'">X</span><br />
				<form action="', $scripturl, '?action=login2', !empty($blog) ? ';blog='. $blog : ';community', '" method="post" accept-charset="', $context['character_set'], '" name="frmLogin" id="frmLogin"', empty($context['disable_login_hashing']) ? ' onsubmit="hashLoginPassword(this, \'' . $context['session_id'] . '\');"' : '', '>
					<table border="0" cellspacing="0" cellpadding="0">';
					
	foreach ($context['zc']['login_form_info'] as $field_name => $array)
		zc_template_form_field($field_name, $array);
					
	echo '
						<tr class="needsPadding">
							<td align="center" colspan="2"><input type="submit" tabindex="', $context['zc']['tab_index']++, '" value="', $txt['b3018'], '" style="margin-top: 2ex;" /></td>
						</tr>
						<tr class="needsPadding">
							<td align="center" colspan="2" class="smalltext"><a href="', $scripturl, '?action=reminder">', $txt['b3035'], '</a></td>
						</tr>
					</table>
				</form><br />
			</div>
		</div>
		</center>
	</div>';
}