<?php

if (!defined('zc'))
	die('Hacking attempt...');

function zc_template_post_form()
{
	global $context, $txt;
	
	zc_template_form($context['zc']['form_info'], isset($context['zc']['current_info']) ? $context['zc']['current_info'] : null);
	
	// show list of this user's drafts?
	if (!empty($context['zc']['drafts']))
	{
		echo '<br />';
				
		zc_template_sandwich_above();
		
		echo '
				<div class="morePadding" id="drafts" style="font-size:14px; font-weight:bold; text-align:center;">
					<a href="javascript:void(0);" id="hideDrafts" onclick="document.getElementById(\'showDrafts\').style.display = \'block\'; document.getElementById(\'hideDrafts\').style.display = \'none\'; document.getElementById(\'drafts_show_hide\').style.display = \'none\';" style="display:', !empty($context['zc']['unhide_drafts']) ? 'block' : 'none', ';">'. $txt['b212'] .'</a>
					<a href="javascript:void(0);" id="showDrafts" onclick="document.getElementById(\'hideDrafts\').style.display = \'block\'; document.getElementById(\'showDrafts\').style.display = \'none\'; document.getElementById(\'drafts_show_hide\').style.display = \'block\';" style="display:', !empty($context['zc']['unhide_drafts']) ? 'none' : 'block', ';">'. $txt['b211'] .'</a>
				</div>';
				
		zc_template_sandwich_below();
				
		echo '
	<div id="drafts_show_hide" style="display:', !empty($context['zc']['unhide_drafts']) ? 'block' : 'none', '; margin-top:1px;">';
	
	zc_template_list_items($context['zc']['drafts'], $context['zc']['list2']);
	
	echo '
	</div>';
	}
	
	// show parent?
	if (!empty($context['zc']['parent']))
	{
		echo '<br /><div style="margin-bottom:1px;">';
		
		zc_template_sandwich_above();
		
		echo '<div class="morePadding"><b>', $txt['b357'], '</b></div>';
		
		zc_template_sandwich_below();
		
		echo '</div>';
	
		$context['zc']['articles'] = array($context['zc']['parent']);
		zc_template_show_articles();
	}
	echo '
	<br />';
}

function template_tagCloud()
{
	global $context;
	if (!empty($context['zc']['tags']))
		echo '
					<tr class="needsPadding">
						<td></td>
						<td>
							<div class="tagCloud">'. implode(' , ', $context['zc']['tags']) .'</div>
						</td>
					</tr>';
}

function template_bbcCloud()
{
	global $context, $settings;
	
	if (!empty($context['zc']['bbc_tags']))
	{
		echo '
					<tr class="needsPadding">
						<td></td>
						<td valign="middle">';

		foreach ($context['zc']['bbc_tags'] as $key => $bbc_tags)
		{
			if (!empty($key))
				echo '<br />';
			
			$found_button = false;
			// Here loop through the array, printing the images/rows/separators!
			foreach ($bbc_tags as $image => $tag)
			{
				// Is this tag disabled?
				if (isset($tag['code']) && !empty($context['zc']['disabled_tags'][$tag['code']]) && isset($tag['before']))
				{
					// do nothing!
				}
				// Is there a "before" part for this bbc button? If not, it can't be a button!!
				elseif (isset($tag['before']))
				{
					$found_button = true;
	
					// If there's no after, we're just replacing the entire selection in the post box.
					if (!isset($tag['after']))
						echo '<a href="javascript:void(0);" onclick="replaceText(\'', $tag['before'], '\', document.forms.', $context['zc']['form_info']['_info_']['form_name'], '.', $context['zc']['post_box_name'], '); return false;">';
					// On the other hand, if there is one we are surrounding the selection ;).
					else
						echo '<a href="javascript:void(0);" onclick="surroundText(\'', $tag['before'], '\', \'', $tag['after'], '\', document.forms.', $context['zc']['form_info']['_info_']['form_name'], '.', $context['zc']['post_box_name'], '); return false;">';
	
					// Okay... we have the link. Now for the image and the closing </a>!
					echo '<img onmouseover="bbc_highlight(this, true);" onmouseout="if (window.bbc_highlight) bbc_highlight(this, false);" src="', $settings['images_url'], '/bbc/', $image, '.gif" align="bottom" width="23" height="22" alt="', $tag['description'], '" title="', $tag['description'], '" style="background-image: url(', $settings['images_url'], '/bbc/bbc_bg.gif); margin: 1px 2px 1px 1px;" /></a>';
				}
				// I guess it's a divider...
				elseif ($found_button)
				{
					echo '&nbsp;&nbsp;';
					$found_button = false;
				}
			}
		}
		
		echo '
						</td>
					</tr>';
	}
}

?>