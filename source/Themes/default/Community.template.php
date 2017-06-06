<?php

if (!defined('zc'))
	die('Hacking attempt...');

function zc_template_showMainBlocks()
{
	global $context;

	$blocks = $context['zc']['main_blocks'];
	
	if (!empty($blocks))
	{
		$b = false;
		for ($i = 0; $i <= count($blocks); $i++)
		{
			// skip if disabled....
			if (empty($blocks[$i]['enabled']) || empty($blocks[$i]['type']))
				continue;
				
			if ($b)
				echo '<br />';
				
			if (!empty($blocks[$i]['exclude_templates']) && !is_array($blocks[$i]['exclude_templates']))
				$excluded_templates = explode(',', $blocks[$i]['exclude_templates']);
			elseif (empty($blocks[$i]['exclude_templates']))
				$excluded_templates = array();
	
			zc_template_sandwich_above();
	
			if (!empty($blocks[$i]['title']))
				echo '
		<div class="needsPadding">
			<table width="100%" cellspacing="0" cellpadding="0">
				<tr class="needsPadding">
					<td align="left"><b><span class="block_titles">', $blocks[$i]['title'], '</span></b></td>
					<td align="right">', !empty($blocks[$i]['page_index']) ? $blocks[$i]['page_index'] : '', '</td>
				</tr>
			</table>
		</div>';
				
			zc_template_MainBlockContent($blocks[$i]['type'], $blocks[$i]['content'], $excluded_templates);
			
			zc_template_sandwich_below();
	
			$b = true;
		}
	}
}

function zc_template_MainBlockContent($type, $content, $excluded_templates)
{
	global $context;
	
	// now the meat of the block...
	if ($type == 'blog_index')
		zc_template_list_items($content, $context['zc']['list1'], $excluded_templates);
	elseif ($type == 'news')
		zc_template_communityNews();
}

function zc_template_communityNews()
{
	global $context, $txt, $blog;
	
	if (!empty($context['zc']['news']))
	{
		echo '
		<div class="needsPadding">';
		$c=1;
		foreach ($context['zc']['news'] as $article)
		{
			//$c++;
			echo '
				<table width="100%" id="a', $article['id'], '" cellspacing="0" cellpadding="0">
					<tr>
						<td valign="top" width="100%" align="left" colspan="2">
							<table width="100%" cellspacing="0" cellpadding="0">
								<tr class="needsPadding">
									<td colspan="2" width="100%" align="left">
										<h1 style="font-size:12px;"><b>', $article['link'], '</b></h1>
									</td>
								</tr>
								<tr class="needsPadding">
									<td colspan="2" width="100%" align="left">
										<span class="article_posted_by">', $txt['b346'], ' ', $article['poster']['link'], ' ', $txt['b3034'], ' ', $article['time'], '</span>
									</td>
								</tr>
								<tr class="needsPadding">
									<td valign="top" width="100%">', $article['body'], '</td>
									<td valign="top" align="right">';
									
			$base_margin_fix = $context['browser']['is_ie'] ? 0 : (-23);
				
			if (!empty($article['bookmarking_links']))
			{
				$max_icons_per_row = 4;
				$margin_fix = $base_margin_fix + (count($article['bookmarking_links']) * 26);
				$width_fix = ($base_margin_fix * (-1)) + $margin_fix;
				
				if ($width_fix > ($max_icons_per_row * 26))
				{
					$width_fix = $max_icons_per_row * 26;
					$margin_fix = ($max_icons_per_row * 26) + $base_margin_fix;
				}
				
				// don't ask questions... just know it works ;)
				$margin_fix = $margin_fix * (-1);
				echo '
										<div class="msgOptionsDropDown">
											<div class="displayOnHover" style="margin-top:-3px; width:', $width_fix, 'px; margin-left:', $margin_fix, 'px;">
												<div class="share_icons">', implode('', $article['bookmarking_links']), '</div>
											</div>
											<div class="alwaysDisplay"><div class="share_icon">&nbsp;</div></div>
										</div><br />';
			}
			
			if (!empty($article['options']))
			{
				$margin_fix = $base_margin_fix;
				if (isset($article['options']['edit']))
					$margin_fix = $margin_fix + 29;
				if (isset($article['options']['delete']))
					$margin_fix = $margin_fix + 26;
				if (isset($article['options']['lock']))
					$margin_fix = $margin_fix + 23;
				if (isset($article['options']['send']))
					$margin_fix = $margin_fix + 31;
				if (isset($article['options']['print']))
					$margin_fix = $margin_fix + 26;
				if (isset($article['options']['notify']))
					$margin_fix = $margin_fix + 24;
					
				$width_fix = ($base_margin_fix * (-1)) + $margin_fix;
					
				// don't ask questions... just know it works ;)
				$margin_fix = $margin_fix * (-1);
						
				echo '
										<div class="msgOptionsDropDown" style="white-space:nowrap;">
											<div class="displayOnHover" style="white-space:nowrap; width:', $width_fix, 'px; margin-left:', $margin_fix, 'px;"><div class="action_icons">', implode('', $article['options']), '</div></div>
											<div class="alwaysDisplay"><div class="options_icon">&nbsp;</div></div>
										</div>';
			}
										
			// article requires approval alert (and its a link to approve the article)
			if (empty($article['is_approved']) && !empty($article['approve_link']))
				echo '
										<div style="margin-top:12px; margin-bottom:6px;">', $article['approve_link'], '</div>';
					
										
			echo '
									</td>
								</tr>';
						
			if (!empty($article['modified']['name']))
				echo '
								<tr class="commentSpecial">
									<td colspan="2" align="left" width="50%">
										<span class="lastEdit">&#171; <i>', $txt['b3043'], ': ', $article['modified']['time'], ' ', $txt['b3045'], ' ', $article['modified']['name'], '</i> &#187;</span>
									</td>
								</tr>';
			echo '
							</table>
						</td>
					</tr>
					<tr class="commentsHeader">
						<td align="left">', $article['view_comments_link'], '</td>
						<td align="right">', $article['num_comments'], '&nbsp;', $article['num_comments'] == 1 ? $txt['b15'] : $txt['b15a'], $article['can_reply'] ? '&nbsp;<span class="commentSpecial" style="font-size:14px;">|</span>&nbsp;'. $article['new_comment'] : '', '</td>	
					</tr>
				</table>';
		}
		echo '
		</div>';
	}
	else
		echo '
		<div class="morePadding">', $txt['b344'], '</div>';
}

?>