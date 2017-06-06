<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
function zc_template_form($form_info, $current_info = null, $exclude_templates = null)
{
	// need form info...
	if (empty($form_info) || !is_array($form_info) || (isset($form_info['_info_']) && count($form_info) == 1))
		return;
		
	global $context, $txt;
	
	// maybe we don't want to show the template above or below the list
	if (!empty($exclude_templates) && !is_array($exclude_templates))
		$exclude_templates = explode(',', $exclude_templates);
	elseif (empty($exclude_templates))
		$exclude_templates = array();
		
	// previewing something?  Let's try to show it in its "natural environment"
	if (!empty($form_info['_info_']['preview_template']) && function_exists($form_info['_info_']['preview_template']))
	{
		$form_info['_info_']['preview_template']();
		
		echo '<br />';
	}

	if (!in_array('above', $exclude_templates))
		zc_template_sandwich_above();
		
	echo '
	<form action="', $form_info['_info_']['form_url'], '"', !empty($form_info['_info_']['form_name']) ? ' name="' . $form_info['_info_']['form_name'] . '"' : '', ' method="post" accept-charset="', $context['character_set'], '"', !empty($form_info['_info_']['file_upload_field_exists']) ? ' enctype="multipart/form-data"' : '', '>
	<table width="100%"', !empty($form_info['_info_']['form_id']) ? ' id="' . $form_info['_info_']['form_id'] . '"' : '', '>';
		
		// form title?
		if (!empty($form_info['_info_']['title']))
			echo '
		<tr class="generic_list_title">
			<td align="left" colspan="2" style="text-align:left;"><div class="needsPadding"><b>', !empty($form_info['_info_']['title']) ? $form_info['_info_']['title'] : '', '</b></div></td>
		</tr>';
			
	$context['zc']['not_first_display_field'] = false;
		
	// let's create all the form fields...
	foreach ($form_info as $k => $array)
		if (!in_array($k, array('_info_')))
		{
			// skip if must_return_true is set, but not true... or no_template is true
			if ((isset($array['must_return_true']) && $array['must_return_true'] !== true) || (isset($array['no_template']) && $array['no_template'] === true))
				continue;
		
			// extra template to show above this form field?
			if (!empty($array['template_above_field']))
			{
				$temp_func = 'template_' . $array['template_above_field'];
				if (function_exists($temp_func))
					$temp_func();
			}
		
			// creates the template for a form field...
			zc_template_form_field($k, $array, (isset($current_info[$k]) ? $current_info[$k] : null), (!empty($form_info['_info_']['template_info']['left_column_width']) ? $form_info['_info_']['template_info']['left_column_width'] : '50%'), (!empty($form_info['_info_']['template_info']['right_column_width']) ? $form_info['_info_']['template_info']['right_column_width'] : '50%'));
			
			$context['zc']['not_first_display_field'] = true;
		}
	
	// recaptcha... o rly?
	if (!empty($context['zc']['recaptcha']))
		echo '
		<tr align="right" valign="top" class="needsPadding">
			<td></td>
			<td align="left">', $context['zc']['recaptcha'], '</td>
		</tr>';
					
	echo '
		<tr class="needsPadding">
			<td colspan="2" align="center">';
				
	// O Hi Der bots ;)
	if (!empty($_SESSION['zc_anti_bot_field_names']))
		foreach ($_SESSION['zc_anti_bot_field_names'] as $field_name)
			echo '
				<input type="text" name="', $field_name, '" value="" style="display:none;" />';
				
	// hidden values...
	if (!empty($form_info['_info_']['hidden_form_values']))
		foreach ($form_info['_info_']['hidden_form_values'] as $n => $v)
			echo '
				<input type="hidden" name="', $n, '" value="', $v, '" />';
							
	// the primary submit button
	echo '<br />
				<input type="hidden" name="sc" value="', $context['session_id'], '" />
				<input type="submit" tabindex="', $context['zc']['tab_index']++, '" value="', !empty($form_info['_info_']['primary_submit_text']) ? $form_info['_info_']['primary_submit_text'] : $txt['b3054'], '" />';
					
	// extra submit buttons... examples: Preview, Spell Check, Save as Draft
	if (!empty($form_info['_info_']['additional_submit_buttons']))
		foreach ($form_info['_info_']['additional_submit_buttons'] as $k => $label)
			echo '
				<input type="hidden" name="button_', $k, '" id="button_', $k, '" value="0" />
				<input type="submit" tabindex="', $context['zc']['tab_index']++, '" onclick="document.getElementById(\'button_', $k, '\').value = \'1\';" value="', $label, '" />';
					
	echo '
			</td>
		</tr>
	</table>
	</form><br />';

	if (!in_array('below', $exclude_templates))
		zc_template_sandwich_below();
}

function zc_template_form_field($field_name, $array, $value = null, $lcw = '50%', $rcw = '50%', $data = null)
{
	global $context, $txt, $scripturl;
	
	// have to have a type
	if (empty($array['type']))
		return;
	
	if (!isset($context['zc']['tab_index']))
		$context['zc']['tab_index'] = 1;

	// if we submitted the form and returned here... that means there was an error... use the value we submitted
	if (isset($_POST[$field_name]))
		$current_value = $_POST[$field_name];
	// we input a set of data to use as the current values...
	elseif ($data !== null && isset($data[$field_name]))
		$current_value = $data[$field_name];
	// we input a value as an argument... let's use that...
	elseif ($value !== null)
		$current_value = $value;
	// if there is a default value for this field... let's use it
	elseif (isset($array['value']))
		$current_value = $array['value'];
	// well I guess it's just empty...
	else
		$current_value = '';
		
	// if needs explode... make sure it's exploded....
	if (!empty($array['needs_explode']) || (!empty($array['custom']) && $array['custom'] == 'multi_check'))
		$current_value = !is_array($current_value) ? (!empty($current_value) ? explode(',', $current_value) : array()) : $current_value;

	if (!empty($array['header_above']))
	{
		$show_header_above = true;
		
		if (!empty($context['zc']['not_first_display_field']))
			echo '
					<tr class="needsPadding">
						<td colspan="2" width="100%" align="center" id="', $field_name, '_header"><hr size="1" width="100%" class="hrcolor" /></td>
					</tr>';
					
		echo '
					<tr class="needsPadding">
						<td colspan="2" width="100%" align="center" style="padding-bottom:12px;"><span class="controlPanelSectionHeader">', zcFormatTxtString($array['header_above']), '</span></td>
					</tr>';
	}
	
	// text that goes above this form field...
	if (!empty($array['instructions']))
	{
		if (empty($show_header_above))
			echo '
					<tr class="needsPadding">
						<td colspan="2" width="40%" align="center"><hr size="1" width="40%" class="hrcolor" style="padding:0; margin:0; margin-top:2px;" /></td>
					</tr>';
					
		echo '
					<tr class="needsPadding">
						<td colspan="2" width="100%" align="center">', zcFormatTxtString($array['instructions']), ':</td>
					</tr>';
	}
		
	// not custom... so it's somewhat normal...
	if (empty($array['custom']))
	{
		echo '
					<tr class="needsPadding">
						<td width="', $lcw, '" align="right"', isset($context['zc']['errors'][$field_name]) ? ' style="color:#FF6D6D;"' : '', '>
							<label for="', $field_name, '">';
							
		// main text
		if (!empty($array['label']))
			echo zcFormatTxtString($array['label']);
		
		// help icon/link?
		if (!empty($array['helptext']))
			echo '
								&nbsp;<a href="', $scripturl, '?zc=help;txt=', $array['helptext'], '" onclick="return reqWin(this.href);" class="help" rel="nofollow"><img src="', $context['zc']['default_images_url'], '/icons/question_icon.png" alt="(?)" /></a>';
							
		// subtext?
		if (!empty($array['subtext']))
			echo '<br />
								<span class="smalltext">', zcFormatTxtString($array['subtext']), '</span>';
							
			echo '
							</label>
						</td>
						<td width="', $rcw, '" align="left">';
				
		// show something above the input?
		if (!empty($array['show_above_field']))
			echo '
							<span style="margin-bottom:3px;">', $array['show_above_field'], '</span><br />';
		
		// is it a checkbox?
		if ($array['type'] == 'check')
			echo '
							<input type="checkbox" tabindex="', $context['zc']['tab_index']++, '" id="', $field_name, '" name="', $field_name, '"', !empty($current_value) ? ' checked="checked"' : ' value="1"';
		// use a plain ol' input field?
		else
			echo '
							<input type="', in_array($array['type'], array('file', 'password')) ? $array['type'] : 'text', '" tabindex="', $context['zc']['tab_index']++, '" name="', $field_name, '" id="', $field_name, '" value="', $current_value, '"', !empty($array['max_length']) ? ' maxlength="'. $array['max_length'] .'"' : '', ' style="', !empty($array['field_width']) ? 'width:' . $array['field_width'] . ';' : '', isset($context['zc']['errors'][$field_name]) ? 'border: 1px solid #FF7373;' : '', '"';
							
		// does messing with this input field affect other fields in this form?
		if (!empty($array['disable_others']))
			foreach ($array['disable_others'] as $other_field => $state_of_current_field)
				echo ' onchange="if (document.getElementById(\'', $field_name, '\').value == \'', $state_of_current_field, '\'){document.getElementById(\'', $other_field, '\').disabled = \'disabled\';if (document.getElementById(\'', $other_field, '\').checked)document.getElementById(\'', $other_field, '\').checked = false;if (document.getElementById(\'', $other_field, '\').type != \'checkbox\' && document.getElementById(\'', $other_field, '\').value != \'\')document.getElementById(\'', $other_field, '\').value = \'\';}else if (document.getElementById(\'', $field_name, '\').value != \'', $state_of_current_field, '\'){document.getElementById(\'', $other_field, '\').disabled = \'\';};"';
		
		// now we can close the input...
		echo ' />';
		
		// show a clear all javascript "link"?
		if (!empty($array['clear_all_option']))
			echo '&nbsp;<a href="javascript:void(0);" title="', $txt['b174'], '" onclick="document.getElementById(\'', $field_name, '\').value=\'\';"><img src="', $context['zc']['default_images_url'], '/icons/disable_icon.gif" alt="" /></a>';
				
		// show something beside the input?
		if (!empty($array['show_beside_field']))
			echo '&nbsp;&nbsp;' . $array['show_beside_field'];
				
		if (!empty($array['max_width_image']))
			echo '<br />
							<span class="smalltext">', $txt['b689'], ':&nbsp;&nbsp;', $array['max_width_image'], '</span>';
				
		if (!empty($array['max_height_image']))
			echo '<br />
							<span class="smalltext">', $txt['b688'], ':&nbsp;&nbsp;', $array['max_height_image'], '</span>';
				
		// allowed file extensions?
		if (!empty($array['allowed_file_extensions']))
			echo '<br />
							<span class="smalltext">', $txt['b479'], ':&nbsp;&nbsp;', implode(', ', $array['allowed_file_extensions']), '</span>';
				
		// show anything else after the input?
		if (!empty($array['show_below_field']))
			echo '<br />
							<span class="smalltext">', $array['show_below_field'], '</span>';
							
		echo '
						</td>
					</tr>';
							
	}
	// a simple horizontal, two-option radio
	elseif ($array['custom'] == 'enable_disable_radio')
	{
		echo '
					<tr class="needsPadding">
						<td colspan="2" width="100%" align="center">
							<label for="', $field_name, '0"><input type="radio" tabindex="', $context['zc']['tab_index']++, '" name="', $field_name, '" id="', $field_name, '0" class="check" value="0"', empty($current_value) ? ' checked="checked"' : '', ' />&nbsp;', !empty($array['txt'][0]) ? zcFormatTxtString($array['txt'][0]) : $txt['b11'], '</label>&nbsp;&nbsp;
							<label for="', $field_name, '1"><input type="radio" tabindex="', $context['zc']['tab_index']++, '" name="', $field_name, '" id="', $field_name, '1" class="check" value="1"', !empty($current_value) ? ' checked="checked"' : '', ' />&nbsp;', !empty($array['txt'][1]) ? zcFormatTxtString($array['txt'][1]) : $txt['b86'], '</label>
						</td>
					</tr>';
	}
	// sometimes we like to hide stuff :)
	elseif ($array['custom'] == 'hidden')
	{
			echo '
							<input type="hidden" name="', $field_name, '" value="', isset($current_value) ? $current_value : '', '" />';
							
	}
	// a select field?
	elseif ($array['custom'] == 'select')
	{
		echo '
					<tr class="needsPadding">
						<td width="', $lcw, '" align="right"', isset($context['zc']['errors'][$field_name]) ? ' style="color:#FF6D6D;"' : '', '>';
							
		// main text
		if (!empty($array['label']))
			echo zcFormatTxtString($array['label']);
		
		// help icon/link?
		if (!empty($array['helptext']))
			echo '
							&nbsp;<a href="', $scripturl, '?zc=help', $context['zc']['blog_request'], ';txt=', $array['helptext'], '" onclick="return reqWin(this.href);" class="help" rel="nofollow"><img src="', $context['zc']['default_images_url'], '/icons/question_icon.png" alt="(?)" /></a>';
							
		// subtext?
		if (!empty($array['subtext']))
				echo '<br />
							<span class="smalltext">', zcFormatTxtString($array['subtext']), '</span>';
							
		echo '
						</td>
						<td width="', $rcw, '" align="left">';
				
		// show something above the input?
		if (!empty($array['show_above_field']))
			echo '
							<span style="margin-bottom:3px;">', $array['show_above_field'], '</span><br />';
						
		echo '
							<select name="', $field_name, '" tabindex="', $context['zc']['tab_index']++, '">';
			
		// blank or current value...		
		echo '
								<option value="', isset($array['options'][$current_value]) ? $current_value : '', '">', isset($array['options'][$current_value]) ? zcFormatTxtString($array['options'][$current_value]) : '', '</option>';
						
		foreach ($array['options'] as $value => $option)
			if ($value != $current_value)
				echo '
								<option value="', $value, '">', zcFormatTxtString($option), '</option>';
		echo '	
							</select>';
				
		// show something beside the input?
		if (!empty($array['show_beside_field']))
			echo '&nbsp;&nbsp;' . $array['show_beside_field'];
				
		if (!empty($array['max_width_image']))
			echo '<br />
							<span class="smalltext">', $txt['b689'], ':&nbsp;&nbsp;', $array['max_width_image'], '</span>';
				
		if (!empty($array['max_height_image']))
			echo '<br />
							<span class="smalltext">', $txt['b688'], ':&nbsp;&nbsp;', $array['max_height_image'], '</span>';
				
		// allowed file extensions?
		if (!empty($array['allowed_file_extensions']))
			echo '<br />
							<span class="smalltext">', $txt['b479'], ':&nbsp;&nbsp;', implode(', ', $array['allowed_file_extensions']), '</span>';
				
		// show anything else after the input?
		if (!empty($array['show_below_field']))
			echo '<br />
							<span class="smalltext">', $array['show_below_field'], '</span>';
							
		echo '
						</td>
					</tr>';
	}
	// text area... cool!
	elseif ($array['custom'] == 'textarea')
	{
		echo '
					<tr class="needsPadding">
						<td width="', $lcw, '" align="right"', isset($context['zc']['errors'][$field_name]) ? ' style="color:#FF6D6D;"' : '', '>';
							
		// main text
		if (!empty($array['label']))
			echo zcFormatTxtString($array['label']);
		
		// help icon/link?
		if (!empty($array['helptext']))
			echo '
							&nbsp;<a href="', $scripturl, '?zc=help', $context['zc']['blog_request'], ';txt=', $array['helptext'], '" onclick="return reqWin(this.href);" class="help" rel="nofollow"><img src="', $context['zc']['default_images_url'], '/icons/question_icon.png" alt="(?)" /></a>';
							
		// subtext?
		if (!empty($array['subtext']))
			echo '<br />
							<span class="smalltext">', zcFormatTxtString($array['subtext']), '</span>';
							
		echo '
						</td>
						<td width="', $rcw, '" align="left">
							<textarea name="', $field_name, '" tabindex="', $context['zc']['tab_index']++, '" style="width:98%;', isset($context['zc']['errors'][$field_name]) ? 'border: 1px solid #FF7373;' : '', '" rows="', !empty($array['ta_rows']) ? $array['ta_rows'] : 10, '" cols="', !empty($array['ta_cols']) ? $array['ta_cols'] : 30, '">', !empty($current_value) ? $current_value : '', '</textarea>';
				
		// show something beside the input?
		if (!empty($array['show_beside_field']))
			echo '&nbsp;&nbsp;' . $array['show_beside_field'];
				
		if (!empty($array['max_width_image']))
			echo '<br />
							<span class="smalltext">', $txt['b689'], ':&nbsp;&nbsp;', $array['max_width_image'], '</span>';
				
		if (!empty($array['max_height_image']))
			echo '<br />
							<span class="smalltext">', $txt['b688'], ':&nbsp;&nbsp;', $array['max_height_image'], '</span>';
				
		// allowed file extensions?
		if (!empty($array['allowed_file_extensions']))
			echo '<br />
							<span class="smalltext">', $txt['b479'], ':&nbsp;&nbsp;', implode(', ', $array['allowed_file_extensions']), '</span>';
				
		// show anything else after the input?
		if (!empty($array['show_below_field']))
			echo '<br />
							<span class="smalltext">', $array['show_below_field'], '</span>';
							
		echo '
						</td>
					</tr>';
	}
	elseif ($array['custom'] == 'multi_check')
	{			
		if (!empty($array['options']))
		{
			// do we want to split the options into multiple columns?
			if (count($array['options']) >= 10)
			{
				$columns = array();
				$num_elements = count($array['options']);
				
				// 10 per column maximum...
				$num_columns = ceil($num_elements / 10);
				
				for ($i = 0; $i <= $num_columns; $i++)
					$columns[] = array_slice($array['options'], ($i * 10), 10);
				
				echo '
					<tr class="noPadding">
						<td colspan="2">
							<table width="100%">
								<tr class="needsPadding">';
							
				if (!empty($columns))
				{
					foreach ($columns as $stuff)
					{
						echo '
									<td width="', round(100 / $num_columns, 3), '%" valign="top">
										<table width="100%">';
						foreach ($stuff as $id => $option)
							echo '
											<tr class="needsPadding">
												<td width="50%" align="right"><label for="', ($field_name . $id), '">', zcFormatTxtString($option), '&nbsp;</label></td>
												<td width="50%" align="left">
													<input type="checkbox" tabindex="', $context['zc']['tab_index']++, '" name="'. $field_name .'[]" id="'. $field_name . $id .'"', !empty($current_value) ? (in_array($id, $current_value) ? ' checked="checked"' : '') : '', ' value="', $id, '" />
												</td>
											</tr>';
						echo '
										</table>
									</td>';
					}
				}
							
				echo '
								</tr>
							</table>
						</td>
					</tr>';
			}
			// no extra columns... how boring...
			else
				foreach ($array['options'] as $id => $option)
					echo '
					<tr class="needsPadding">
						<td width="', $lcw, '" align="right"><label for="', ($field_name . $id), '">', zcFormatTxtString($option), '&nbsp;</label></td>
						<td width="', $rcw, '" align="left">
							<input type="checkbox" tabindex="', $context['zc']['tab_index']++, '" name="', $field_name, '[]" id="', ($field_name . $id), '"', !empty($current_value) ? (in_array($id, $current_value) ? ' checked="checked"' : '') : '', ' value="'. $id .'" />
						</td>
					</tr>';
		}
		
		// check/uncheck all
		echo '
					<tr class="needsPadding">
						<td width="', $lcw, '" align="right"><label for="', $field_name, '_checkall"><i>', $txt['b3005'], '</i></label>&nbsp;</td>
						<td width="', $rcw, '" align="left">
							<input type="checkbox" id="', $field_name, '_checkall" onclick="invertAll(this, this.form, \'', $field_name, '[]\');" class="check" />
						</td>
					</tr>';
	}
	// this one allows us to have multiple input fields for one setting...
	elseif ($array['custom'] == 'multi_field')
	{
		echo '
					<tr class="needsPadding">
						<td width="', $lcw, '" align="right"', isset($context['zc']['errors'][$field_name]) ? ' style="color:#FF6D6D;"' : '', '>';
						
		if (!empty($array['label']))
			echo zcFormatTxtString($array['label']);
		
		// help icon/link?
		if (!empty($array['helptext']))
			echo '
							&nbsp;<a href="', $scripturl, '?zc=help', $context['zc']['blog_request'], ';txt=', $array['helptext'], '" onclick="return reqWin(this.href);" class="help" rel="nofollow"><img src="', $context['zc']['default_images_url'], '/icons/question_icon.png" alt="(?)" /></a>';
							
		// subtext?
		if (!empty($array['subtext']))
			echo '<br />
							<span class="smalltext">', zcFormatTxtString($array['subtext']), '</span>';
		
		echo '
						</td>
						<td width="', $rcw, '" align="left">';
		
		// do we want to use existing values?
		if (!empty($current_value))
		{
			$input_type = in_array($array['type'], array('file', 'password')) ? $array['type'] : 'text';
			$numOptions = count($current_value);
			$i = 0;
			foreach ($current_value as $option_id => $option_value)
			{
				$i++;
				echo '
							<input type="', in_array($array['type'], array('file', 'password')) ? $array['type'] : 'text', '" tabindex="', $context['zc']['tab_index']++, '" name="', $field_name, '[', $option_id, ']" value="', $option_value, '"', !empty($array['max_length']) ? ' maxlength="' . $array['max_length'] . '"' : '', ' />';
							
				if ($i != $numOptions)
					echo '<br />';
				elseif (!empty($array['add_field_option']))
					echo '<span id="more_', $field_name, '"></span>&nbsp;<a href="javascript:void(0);" onclick="addField(\'', $field_name, '\', \'', $input_type, '\', ', !empty($array['max_num_fields']) && is_int($array['max_num_fields']) ? $array['max_num_fields'] : 0, ');">( ', sprintf($txt['b249'], zcFormatTxtString($array['label'])), ' )</a><br />';
			}
		}
		// otherwise we're going to setup fresh
		else
		{
			$starting_num_fields = isset($array['minimum_num_fields']) && $array['minimum_num_fields'] > 0 ? $array['minimum_num_fields'] : 1;
			if (!empty($starting_num_fields))
				for ($i = 1; $i <= $starting_num_fields; $i++)
				{
					echo '
								<input type="', in_array($array['type'], array('file', 'password')) ? $array['type'] : 'text', '" tabindex="', $context['zc']['tab_index']++, '" name="', $field_name, '[]" value=""', !empty($array['max_length']) ? ' maxlength="' . $array['max_length'] . '"' : '', ' />';
								
					if ($i != $starting_num_fields)
						echo '<br />';
					// maybe we want to let them add even more fields?
					elseif (!empty($array['add_field_option']))
						echo '<span id="more_', $field_name, '"></span>&nbsp;<a href="javascript:void(0);" onclick="addField(\'', $field_name, '\', \'', in_array($array['type'], array('file', 'password')) ? $array['type'] : 'text', '\', ', !empty($array['max_num_fields']) && is_int($array['max_num_fields']) ? $array['max_num_fields'] : 0, ');">( ', sprintf($txt['b249'], zcFormatTxtString($array['label'])), ' )</a><br />';
				}
		}
							
		echo '
						</td>
					</tr>';
	}
	// a radio!
	elseif ($array['custom'] == 'radio')
	{	
		if (!empty($array['options']))
			foreach ($array['options'] as $id => $option)
				echo '
					<tr class="needsPadding">
						<td width="', $lcw, '" align="right">
							<input type="radio" tabindex="', $context['zc']['tab_index']++, '" name="', $field_name, '" id="', ($field_name . $id), '"', isset($array['disable_options']) && !empty($array['disable_options'][$id]) ? (!isset($data[$array['disable_options'][$id][0]]) || (isset($data[$array['disable_options'][$id][0]]) && $data[$array['disable_options'][$id][0]] == $array['disable_options'][$id][1]) ? ' disabled="disabled"' : '') : '', ' class="check" value="', $id, '"', isset($current_value) ? ($current_value == $id ? ' checked="checked"' : '') : '', ' /> 
						</td>
						<td width="', $rcw, '" align="left"><label for="', ($field_name . $id), '">', zcFormatTxtString($option), '</label></td>
					</tr>';
	}
}

?>