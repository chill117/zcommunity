<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
function zc_template_list_items($items, $list_info, $exclude_templates = null)
{
	// we need at least something to display in here....
	if (empty($items) && empty($list_info['list_empty_txt']))
		return;
	
	// maybe we don't want to show the template above or below the list
	if (!empty($exclude_templates) && !is_array($exclude_templates))
		$exclude_templates = explode(',', $exclude_templates);
	elseif (empty($exclude_templates))
		$exclude_templates = array();

	if (!in_array('above', $exclude_templates))
		zc_template_sandwich_above();
		
	if (!empty($items))
	{
		// start a form around our list?
		if (!empty($list_info['form_url']))
		{
			global $context;
			echo '
	<form action="', $list_info['form_url'], '" method="post" accept-charset="', $context['character_set'], '">';
		}
	
		echo '
	<table width="100%" border="0" cellspacing="0" cellpadding="4"', !empty($list_info['list_id']) ? ' id="' . $list_info['list_id'] . '"' : '', '>';
	
		// use the first item to calculate the # of columns...
		foreach ($items as $item)
		{
			$num_columns = count($item);
			break;
		}
		
		// title and/or page index?
		if (!empty($list_info['title']) || (!empty($list_info['page_index']) && !empty($list_info['show_page_index'])))
			echo '
		<tr class="generic_list_title">
			<td align="left" colspan="', ($num_columns - 1), '" style="text-align:left;"><div class="needsPadding"><b>', !empty($list_info['title']) ? $list_info['title'] : '', '</b></div></td>
			<td align="right" style="text-align:right;"><div class="needsPadding" style="white-space:nowrap;">', !empty($list_info['page_index']) && !empty($list_info['show_page_index']) ? $list_info['page_index'] : '', '</div></td>
		</tr>';
		
		echo '
		<tr class="generic_list_column_header">';
		// use the first item to figure out how many columns we're going to need...
		foreach ($items as $item)
		{
			$i = 0;
			$alignment = array();
			foreach ($item as $k => $dummy)
			{
				$i++;
				
				// figure out the alignment of this column...
				$alignment[$k] = isset($list_info['alignment'][$k]) ? $list_info['alignment'][$k] : ($i == 1 ? 'left' : ($i == count($item) ? 'right' : 'center'));
				echo '
			<td align="', $alignment[$k], '"><div class="needsPadding">', !empty($list_info['table_headers'][$k]) ? $list_info['table_headers'][$k] : '', '</div></td>';
			}
			
			break;
		}
		echo '
		</tr>';
			
		$i = 0;
		// now all the items...
		foreach ($items as $item)
		{
			$i++;
			echo '
		<tr class="generic_list_row', $i % 2 ? 1 : 2, '">';
			
			// each column for this item...
			foreach ($item as $k => $v)
				echo '
			<td align="', $alignment[$k], '"><div class="needsPadding">', $v, '</div></td>';
			
			echo '
		</tr>';
		}
		
		// show a submit button
		if (!empty($list_info['form_url']) && empty($list_info['hide_primary_submit']))
		{
			global $txt;
			echo '
		<tr class="generic_list_row', $i % 2 ? 2 : 1, '">
			<td colspan="', ($num_columns - 1), '"><div class="needsPadding">', !empty($list_info['special_links']) ? implode('&nbsp;&nbsp;&nbsp;', $list_info['special_links']) : '', '</div></td>
			<td align="right" style="text-align:right;"><div class="needsPadding"><input type="hidden" name="sc" value="' . $context['session_id'] . '" /><input type="submit" value="', !empty($list_info['submit_button_txt']) ? $list_info['submit_button_txt'] : $txt['b3006'], '"', isset($list_info['confirm_submit_txt']) ? ' onclick="return confirm(\'' . (!empty($list_info['confirm_submit_txt']) ? htmlspecialchars($list_info['confirm_submit_txt']) : htmlspecialchars($txt['b278'])) . '\');"' : '', ' /></div></td>
		</tr>';
		}
		// show another page index or "special" links...
		elseif (!empty($list_info['special_links']) || (!empty($list_info['page_index']) && !empty($list_info['show_page_index'])))
			echo '
		<tr class="generic_list_row', $i % 2 ? 2 : 1, '">
			<td colspan="', ($num_columns - 1), '"><div class="needsPadding">', !empty($list_info['special_links']) ? implode('&nbsp;&nbsp;&nbsp;', $list_info['special_links']) : '', '</div></td>
			<td align="right" style="text-align:right;"><div class="needsPadding">', !empty($list_info['page_index']) && !empty($list_info['show_page_index']) ? $list_info['page_index'] : '', '</div></td>
		</tr>';

		echo '
	</table>';
		
		// close the form around our list?
		if (!empty($list_info['form_url']))
			echo '
	</form>';
	}
	// no items to display in the list... let's show some text explaining the situation...
	elseif (!empty($list_info['list_empty_txt']))
		echo '
		<div class="morePadding">', zcFormatTxtString($list_info['list_empty_txt']), '&nbsp;', !empty($list_info['list_help_link']) ? $list_info['list_help_link'] : '', '</div>';

	if (!in_array('below', $exclude_templates))
		zc_template_sandwich_below();
}

function zc_template_simple_list($title, $list, $txt_if_empty = null)
{
	if (empty($list) && empty($txt_if_empty))
		return;
	
	zc_template_sandwich_above();
	
		echo '
	<div class="needsPadding">';
		if (!empty($title))
			echo '
		<div class="needsPadding" style="font-size:13px;"><b>', $title, '</b></div>';
	
		if (!empty($list))
			foreach ($list as $item)
				echo '
		<div class="needsPadding">', $item, '</div>';
		else
			echo '
		<div class="needsPadding">', !empty($txt_if_empty) ? $txt_if_empty : '', '</div>';
	
		echo '
	</div>';
	
	zc_template_sandwich_below();
}

?>
