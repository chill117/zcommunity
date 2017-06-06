<?php

if (!defined('zc'))
	die('Hacking attempt...');

function zc_template_cp_below()
{
	global $context;
	echo '
		</td></tr></table><br />';
}

function zc_template_cp_above()
{
	global $context, $settings, $scripturl, $txt, $blog;
	
	echo '
	<table width="100%" cellspacing="0" cellpadding="0" border="0"><tr><td valign="top">';
	
	zc_template_sandwich_above();
	
	echo '
	<div class="needsPadding">
		<div class="needsPadding">
			<table width="100%">
				<tr>
					<td align="left"><b style="font-size:13px;">', $context['page_title'], '</b></td>
					<td align="right">', !empty($context['blog_page_index']) && !empty($context['show_blog_page_index']) ? $context['blog_page_index'] : '', '</td>
				</tr>
			</table>
		</div>';
				
	if (!empty($context['zc']['bcp_page_info']))
		echo '
		<div class="needsPadding" style="text-align:left;">', $context['zc']['bcp_page_info'], '</div>';
				
	if (!empty($context['secondary_bcp_greeting']))
		echo '
		<div class="needsPadding" style="text-align:left;">', $context['secondary_bcp_greeting'], '</div>';
		
	echo '
	</div>';
	
	zc_template_sandwich_below();
			
	// show the tab menu (main, blog 1, blog 2, etc)
	echo '
	<div style="margin-top:3px; margin-bottom:6px;">
		<table border="0" cellspacing="0" cellpadding="0" width="100%"><tr><td align="left" class="float_inner_list_left">', zc_template_menu($context['zc']['primary_bcp_menu']), '</td></tr></table>
	</div>';
		
	// If an error occurred, explain what happened
	if (!empty($context['zc']['errors']))
		zc_template_error_table();
	// display a success message if there was one...
	elseif (!empty($context['zc']['success_message']))
		zc_template_show_success();
			
	// secondary and tertiary bcp menus
	echo '
	<div style="margin-bottom:3px; margin-top:9px;">
		<table border="0" cellspacing="0" cellpadding="0" width="100%"><tr>
			<td align="left" class="float_inner_list_left">', !empty($context['zc']['tertiary_bcp_menu']) ? zc_template_menu($context['zc']['tertiary_bcp_menu']) : '', '</td>
			<td align="right" class="float_inner_list_right">', !empty($context['zc']['secondary_bcp_menu']) ? zc_template_menu($context['zc']['secondary_bcp_menu']) : '', '</td>
		</tr></table>
	</div>';
}

function zc_template_blogSettings()
{
	global $context, $txt;
	
	// blog ownership form...
	if ($context['can_change_blog_ownership'])
	{
		zc_template_sandwich_above();
		
		echo '
		<center>
			<div class="needsPadding">', $txt['b666'], ':</div>
			<div class="needsPadding">
				<form action="'. $context['zc']['base_bcp_url'] . $context['zc']['u_request'] . $context['zc']['blog_request'] . $context['zc']['sa_request'] . $context['zc']['blogStart_request'] . $context['zc']['listStart_request'] . $context['zc']['all_request'] .';sesc=', $context['session_id'], '" method="post" accept-charset="', $context['character_set'], '">
					<input name="change_blog_owner" value="', !empty($context['zc']['this_blog_owner_name']) ? $context['zc']['this_blog_owner_name'] : '', '" />
					<input type="submit" value="', $txt['b23'], '" />
				</form>
			</div>
		</center>';
		
		zc_template_sandwich_below();
	
		echo '<br />';	
	}
	
	zc_template_form($context['zc']['defaultSettings'], $context['zc']['blog_settings']);
	
	$context['zc']['not_first_display_field'] = false;
	if (!empty($context['zc']['plugin_settings_info']) && count($context['zc']['plugin_settings_info']) > 1)
	{
		echo '<br />';
		zc_template_form($context['zc']['plugin_settings_info'], $context['zc']['plugin_settings']);
	}
}

function zc_template_manageBlogs()
{
	global $context, $txt;
	
	zc_template_list_items($context['my_blogs'], $context['zc']['list1']);
}

function zc_template_customWindows()
{
	global $settings, $txt, $context, $scripturl, $blog;
	
	zc_template_form($context['zc']['sw_settings'], $context['zc']['blog_settings']);
	
	echo '<br />';
	
	zc_template_list_items($context['zc']['custom_windows'], $context['zc']['list1']);
	
	echo '<br />';
	
	zc_template_form($context['zc']['form_info'], $context['zc']['current_info']);
	
	echo '<br />';
	
	zc_template_moveWindowsForm($txt['b521']);
}

function zc_template_categories()
{
	global $txt, $context;
	
	zc_template_list_items($context['zc']['categories'], $context['zc']['list1']);
	
	echo '<br />';
	
	zc_template_form($context['zc']['form_info'], $context['zc']['current_info']);
}

function zc_template_tags()
{
	global $txt, $context;
	
	$context['zc']['list1']['title'] = $txt['b26a'];
	zc_template_list_items($context['zc']['tags'], $context['zc']['list1']);
}

function zc_template_preferences()
{
	global $context, $txt;
	
	zc_template_form($context['zc']['preferences'], $context['zc']['cp_owner']['blog_preferences']);
}

function zc_template_notifications()
{
	global $context, $txt;
	
	zc_template_form($context['zc']['n_preferences'], $context['zc']['cp_owner']['blog_preferences']);
	
	echo '<br />';
	
	zc_template_list_items($context['zc']['article_notifications'], $context['zc']['list1']);
	
	echo '<br />';
	
	zc_template_list_items($context['zc']['blog_notifications'], $context['zc']['list2']);
}

function zc_template_communityPage()
{
	global $context, $zc, $txt;
	
	if ($context['zc']['ssa'] == 'settings')
		zc_template_form($context['zc']['bi_settings'], $zc['settings']);
	elseif ($context['zc']['ssa'] == 'windows')
	{
		zc_template_form($context['zc']['bi_settings'], $zc['settings']);
		
		echo '<br />';
		
		zc_template_list_items($context['zc']['custom_windows'], $context['zc']['list1']);
		
		echo '<br />';
		
		zc_template_form($context['zc']['form_info'], $context['zc']['current_info']);
		
		echo '<br />';
		
		zc_template_moveWindowsForm($txt['b521']);
	}
}

function zc_template_other()
{
	global $txt, $context;
	
	if ($context['zc']['ssa'] == 'maintenance')
	{
		if (isset($context['zc']['known_issues']))
		{
			zc_template_simple_list((!empty($context['zc']['known_issues']) ? $txt['b99'] : ''), $context['zc']['known_issues'], $txt['b98']);
		
			echo '<br />';
		}
		
		zc_template_simple_list($txt['b523'], $context['zc']['maintenance_links']);
	}
	elseif ($context['zc']['ssa'] == 'error_log')
	{
		global $zc;
		
		zc_template_list_items($context['zc']['list_of_errors'], $context['zc']['list1']);
		
		echo '<br />';
		
		zc_template_form($context['zc']['error_settings_array'], $zc['settings']);
	}
}

function zc_template_accessRestrictions()
{
	global $context, $scripturl, $blog, $txt, $settings;
	
	$context['zc']['list1']['title'] = $txt['b309'];
	zc_template_list_items($context['zc']['users_allowed_access'], $context['zc']['list1']);
	
	echo '<br />';
	
	zc_template_form($context['zc']['form_info']);
	
	echo '<br />';
	
	zc_template_form($context['zc']['access_restrictions'], $context['zc']['current_info']);
}

function zc_template_postingRestrictions()
{
	global $context, $scripturl, $blog, $txt, $settings;
	
	$context['zc']['list1']['title'] = $txt['b513'];
	zc_template_list_items($context['zc']['users_allowed_to_blog'], $context['zc']['list1']);
	
	echo '<br />';
	
	zc_template_form($context['zc']['form_info']);
	
	echo '<br />';
	
	zc_template_form($context['zc']['posting_restrictions'], $context['zc']['current_info']);
}

function zc_template_globalSettings()
{
	global $txt, $scripturl, $context, $zc;
	
	// import SMF board form...
	if (!empty($context['zc']['forum_boards']))
	{
		zc_template_sandwich_above();
		
		echo '
			<div class="needsPadding">
				<form method="post" action="', ($scripturl . zcRequestVarsToString('sa', '?')), ';sa=initBlog">
				<table width="100%">
					<tr class="needsPadding">
						<td colspan="2" align="center" width="100%">', $txt['b514'], '</td>
					</tr>
					<tr class="needsPadding">
						<td align="right" width="50%">
							<select name="convertBoard">';
		
		foreach($context['zc']['forum_boards'] as $id => $name)
			echo '
								<option value="', $id, '" style="padding:2px;">', $name, '</option>';
										
		echo '
							</select>
						</td>
						<td align="left" width="50%">
							<input type="hidden" name="sc" value="', $context['session_id'], '" />
							<input type="submit" value="', $txt['b20'], '" onclick="return confirm(\'', $txt['b515'], '\');" />
						</td>
					</tr>
				</table>
				</form>
			</div>';
				
		zc_template_sandwich_below();
	
		echo '<br />';
	}
	
	zc_template_form($context['zc']['zc_settings'], $zc['settings']);
}

function zc_template_plugins()
{
	global $txt, $context;
	
	if (!empty($context['zc']['install_plugin_form']))
	{
		zc_template_form($context['zc']['install_plugin_form']);
	
		echo '<br />';
	}
	
	
	zc_template_list_items($context['zc']['list_plugins'], $context['zc']['list1']);
}

function zc_template_themes()
{
	global $zc, $context, $scripturl, $txt;
	
	// theme settings page...
	if (empty($context['zc']['pick_a_theme']))
	{
		if (!empty($context['zc']['install_theme_form']))
			zc_template_form($context['zc']['install_theme_form']);
		
		zc_template_form($context['zc']['form_theme_settings'], $context['zc']['form_data']);
	}
	// choose a theme page....
	else
	{
		if (!empty($context['zc']['available_themes']))
		{
			echo '
			<center>';
			foreach ($context['zc']['available_themes'] as $theme_id => $info)
			{
				zc_template_sandwich_above();
				
				echo '
				<div class="needsPadding">', !empty($info['thumbnails']) ? implode('&nbsp;&nbsp;', $info['thumbnails']) : '<span class="smalltext"><i>' . $txt['b539'] . '</i></span>', '</div>';
			
				if (!empty($info['name']))
					echo '
				<div class="needsPadding" style="font-size:14px;"><b>', $info['name'], '</b></div>';
				
				echo '
				<div class="needsPadding"><a class="smalltext" href="', $scripturl, '?theme=', ($theme_id .  zcRequestVarsToString(null, ';')), '">', $txt['b538'], '</a></div>';
				
				zc_template_sandwich_below();
				
				echo '<br />';
			}
			echo '
			</center>';
		}
	}
}

function zc_template_permissions()
{
	global $context, $settings, $txt, $scripturl;
	
	zc_template_sandwich_above();
	
	echo '
	<center>';
	
	// list of member groups and info about them... and complex form for changing permissions
	if (!empty($context['zc']['member_groups']))
	{
		echo '
	<form method="post" action="' . $context['zc']['base_bcp_url'] . $context['zc']['u_request'] . $context['zc']['sa_request'] . $context['zc']['blogStart_request'] . ';sesc=' . $context['session_id'] . '">';
	
		zc_template_list_items($context['zc']['member_groups'], $context['zc']['list1'], 'below,above');
		
		echo '
	<table width="100%">
		<tr>
			<td align="right" width="80%">
				<div class="needsPadding">', $txt['b141'], '</div>';
							
		if (!empty($context['predefined_permission_profiles']))
		{
			echo '
				<div class="needsPadding">
					<select name="permission_profile">
						<option value="">', sprintf($txt['b137'], $txt['b145']), '</option>';
			foreach ($context['predefined_permission_profiles'] as $key => $profile)
				echo '
						<option value="', $key, '">', $profile, '</option>';
			echo '
					</select>
				</div>';
		}
					
		echo '
				<div class="needsPadding">
					<select name="like_group">
						<option value="">', sprintf($txt['b137'], $txt['b142']), '</option>';
		foreach ($context['zc']['member_groups'] as $id => $group)
			if ($id != 1)
				echo '
						<option value="', $id, '">', $group['name'], '</option>';
		echo '
					</select>
				</div>';
					
		echo '
				<div class="needsPadding">
					<select name="add_deny">
						<option value="1">', $txt['b143'], '</option>
						<option value="0">', $txt['b144'], '</option>
					</select>
					<select name="permission">
						<option value="">', sprintf($txt['b137'], $txt['b146']), '</option>';
		foreach ($context['zc']['permissions'] as $permission => $array)
		{
			if (!empty($array['header_above']))
				echo '
						<option value="" disabled="disabled">- - -&nbsp;', zcFormatTxtString($array['header_above']), '&nbsp;- - -</option>';
		
			echo '
						<option value="', $permission, '">', zcFormatTxtString($array['label']), '</option>';
		}
		echo '
					</select>
				</div>';
							
		echo '
			</td>
			<td width="20%" align="center" valign="middle">
				<input type="hidden" name="sc" value="', $context['session_id'], '" />
				<input type="submit" value="', $txt['b152'], '" />
			</td>
		</tr>
	</table>
	</form>';
	}
	// single member group's permissions page...
	elseif (!empty($context['zc']['member_group']))
	{
		if (!empty($context['zc']['member_group']['permissions']))
		{
			echo '
	<form method="post" action="'. $context['zc']['base_bcp_url'] . $context['zc']['u_request'] . $context['zc']['sa_request'] . $context['zc']['blogStart_request'] . $context['zc']['group_request'] . ';sesc=', $context['session_id'], '">
	<div class="needsPadding">
		<span class="controlPanelSectionHeader">', $txt['b235'], ' - ', $context['zc']['member_group']['name'], '</span>
	</div>
	<div class="needsPadding">
		<table width="50%" cellpadding="0">';
				
			foreach ($context['zc']['member_group']['permissions'] as $permission => $add_deny)
				zc_template_formatPermissionForm($permission, $add_deny);
						
			echo '
		</table>
	</div>
	<div class="needsPadding" style="width:100%;">
		<input type="hidden" name="sc" value="', $context['session_id'], '" />
		<input type="hidden" name="group" value="', $context['zc']['member_group']['id'], '" />
		<input type="submit" value="', $txt['b237'], '" />
	</div>
	</form>';
		}
		else
			echo '
	<div class="needsPadding">', $txt['zc_error_5'], '</div>';
	}
	// nothing to display?  That's .... strange
	else
		echo '
	<div class="needsPadding">', $txt['zc_error_13'], '</div>';
	
	echo '
	</center><br />';
	
	zc_template_sandwich_below();
}

function zc_template_formatPermissionForm($permission, $add_deny)
{
	global $scripturl, $context;
	
	if (!empty($context['zc']['permissions'][$permission]['header_above']))
	{
		if (!empty($context['zc']['done_first_permission_header']))
			echo '
		<tr class="noPadding"><td colspan="2">&nbsp;</td></tr>';
	
		echo '
		<tr class="noPadding"><td colspan="2"><b>', zcFormatTxtString($context['zc']['permissions'][$permission]['header_above']), '</b><hr class="hrcolor" width="100%" size="1" /><td></tr>';
		$context['zc']['done_first_permission_header'] = true;
	}

	if (empty($context['zc']['member_group']['is_guest_group']) || !in_array($permission, $context['zc']['non_guest_permissions']))
	{
		echo '
		<tr class="noPadding">
			<td align="left" width="95%">';

		if (!empty($context['zc']['permissions'][$permission]['helptext']))
			echo '
				<a href="', $scripturl, '?zc=help;txt=', $context['zc']['permissions'][$permission]['helptext'], '" onclick="return reqWin(this.href);" class="help" rel="nofollow"><img src="', $context['zc']['default_images_url'], '/icons/question_icon.png" height="12" width="12" alt="(?)" /></a>&nbsp;';
				
		echo '
				<label for="', $permission, '">';
	
		if (!empty($context['zc']['permissions'][$permission]['label']))
			echo zcFormatTxtString($context['zc']['permissions'][$permission]['label']);
		
		echo '
				</label>
			</td>
			<td align="right" width="5%">
				<input type="checkbox" id="', $permission, '" name="', $permission, '" ', !empty($add_deny) ? 'checked="checked"' : 'value="1"', ' />
			</td>
		</tr>';
	}
}

function zc_template_moveWindowsForm($title)
{
	global $txt, $context;
				
	zc_template_sandwich_above();
			
	echo '
			<div class="needsPadding">';
			
	if (!empty($title))
		echo '
				<div class="needsPadding" style="font-size:13px; text-align:left;"><b>', $title, '</b></div><br />';
			
	echo '
				<table width="100%" align="center" id="moveWindows">
					<tr class="needsPadding">
						<td width="100%">
							<table width="100%" align="center">';
						
	for ($i = 1; $i <= $context['zc']['max_side_window_order']; $i++)
	{
		if (isset($context['zc']['side_windows'][$i]))
		{
			echo '
								<tr class="needsPadding">
									<td width="50%" align="right">'. $context['zc']['side_windows'][$i]['name'] .' </td>
									<td width="50%" align="left">';
									
			if ($context['zc']['display_move_window_form'] != $context['zc']['side_windows'][$i]['type'])
				echo '
										<span class="controlPanelDisplayBox">', $i, '</span> <a class="zcButtonLink" href="'. $context['zc']['base_bcp_url'] . $context['zc']['u_request'] . $context['zc']['blog_request'] . $context['zc']['sa_request'] . $context['zc']['blogStart_request'] .';moveWindow='. $context['zc']['side_windows'][$i]['type'] .'#moveWindows" rel="nofollow">'. $txt['b23'] .'</a>';
			elseif (!empty($context['zc']['display_move_window_form']))
			{
				echo '
								<form action="', $context['zc']['base_bcp_url'] . $context['zc']['u_request'] . $context['zc']['blog_request'] . $context['zc']['sa_request'] . $context['zc']['blogStart_request'] . $context['zc']['listStart_request'] . $context['zc']['all_request'] .';sesc='. $context['session_id'] .';moveWindow='. $context['zc']['display_move_window_form'] .'" method="post" accept-charset="', $context['character_set'], '">
									<select name="'. $context['zc']['display_move_window_form'] .'WindowOrder">
										<option value="', !empty($i) ? $i : '', '" style="padding-right:6px;">', !empty($i) ? $i : '', '</option>';
										
				for ($n = 1; $n <= $context['zc']['max_side_window_order']; $n++)
					if ($n != $i)
						echo '
										<option value="'. $n .'" style="padding-right:6px;">'. $n .'</option>';
				echo '
									</select>
									<input type="hidden" name="changeWinOrder" />
									<input type="submit" value="'. $txt['b19'] .'" />
								</form>';
			}
					
			echo '
									</td>
								</tr>';
		}
	}
			
	echo '
							</table>
						</td>
					</tr>
				</table>
			</div>';
				
	zc_template_sandwich_below();
}

?>