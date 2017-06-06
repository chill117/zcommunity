<?php

if (!defined('zc'))
	die('Hacking attempt...');

function zc_template_show_articles()
{
	global $context, $scripturl, $txt, $settings, $blog;
	
	$n = 0;
	foreach ($context['zc']['articles'] as $article)
	{
		$n++;
		if ($n > 1)
			echo '<br />';
		
		echo '
					<div id="a'. $article['id'] .'"></div>
					<div class="', empty($article['is_approved']) && !empty($article['approve_link']) ? 'err' : 'commentbg', '">
					<div class="morePadding">
					<table width="100%" cellspacing="0" cellpadding="0" border="0">
						<tr class="needsPadding">
							<td align="left" width="95%" style="padding-left:0;">
								<div class="articleTitle"><h1>'. $article['link'] .'</h1></div>
							</td>
							<td align="right" valign="top">', !empty($article['is_sticky']) ? '<img src="' . $context['zc']['default_images_url'] . '/icons/sticky_icon.png" alt="" />' : '', '</td>
						</tr>
						<tr class="needsPadding">
							<td colspan="2" align="left" width="100%" style="padding-left:0;">
								<span class="article_posted_by">', $txt['b40'], ' ', $article['poster']['link'], ' ', $txt['b3034'], ' ', $article['time'], '</span>
							</td>
						</tr>
						<tr class="needsPadding">
							<td align="left" width="97%" style="padding-left:0;">
								<table width="100%" border="0" cellspacing="0" cellpadding="0" style="table-layout:fixed;">
									<tr class="noPadding"><td><div id="article_body_', $article['id'], '" style="width:100%; overflow:auto;">', $article['body'], '</div></td></tr>
								</table>
							</td>
							<td align="right" width="3%" valign="top">';
		
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
									<div class="displayOnHover" style="width:'. $width_fix .'px; margin-top:-3px; margin-left:'. $margin_fix .'px;">
										<div class="share_icons">'. implode('', $article['bookmarking_links']) .'</div>
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
									<div class="displayOnHover" style="white-space:nowrap; width:'. $width_fix .'px; margin-left:'. $margin_fix .'px;"><div class="action_icons">'. implode('', $article['options']) .'</div></div>
									<div class="alwaysDisplay"><div class="options_icon">&nbsp;</div></div>
								</div>';
		}
		
		// article requires approval alert (and its a link to approve the article)
		if (empty($article['is_approved']) && !empty($article['approve_link']))
			echo '
								<div style="margin-top:12px; margin-bottom:6px;">'. $article['approve_link'] .'</div>';
			
		// more links?
		if (!empty($article['extra']['links']))
			foreach ($article['extra']['links'] as $link)
				echo '
								<div style="margin-top:6px; margin-bottom:6px;">', $link, '</div>';
		echo '
							</td>
						</tr>';
		
		if (!empty($article['modified']['name']))
			echo '
						<tr class="needsPadding">
							<td colspan="2" align="left">
								<span class="lastEdit">&#171; <i>', $txt['b3043'], ': ', $article['modified']['time'], ' ', $txt['b3045'], ' ', $article['modified']['name'], '</i> &#187;</span>
							</td>
						</tr>';
		
		if (!empty($article['tags']))
			echo '
						<tr class="needsPadding">
							<td colspan="2" align="left">
								<img src="', $context['zc']['default_images_url'], '/icons/tag_icon.png" title="', $txt['b26a'] .'" alt="', $txt['b26a'], ':" />&nbsp;&nbsp;', implode(', ', $article['tags']), '
							</td>
						</tr>';
		
		if (!empty($article['category']))
			echo '
						<tr class="needsPadding">
							<td colspan="2" align="left">
								<img src="', $context['zc']['default_images_url'], '/icons/category_icon.gif" alt="'. $txt['b16'] .'" />&nbsp;&nbsp;', $article['category'], '
							</td>
						</tr>';
		
		if (!empty($article['related_articles']))
			echo '
						<tr class="needsPadding">
							<td colspan="2" align="left">
								', $txt['b3050'], ':<br />', implode('<br />', $article['related_articles']), '
							</td>
						</tr>';
		
		echo '
					</table>
					<table width="100%" cellspacing="0" cellpadding="0" border="0" id="comments', $article['id'], '">
						<tr class="needsPadding">
							<td align="left">';
							
			// show comments link..
			if (!empty($article['show_comments_link']) && empty($context['viewing_single_article']))
				echo $article['show_comments_link'];
				
			echo '
							</td>
							<td align="right">
								' . $article['num_comments'] . '&nbsp;' . ($article['num_comments'] == 1 ? $txt['b15'] : $txt['b15a']) . ($article['can_reply'] ? '&nbsp;<span class="plain_text_divider">|</span>&nbsp;'. $article['new_comment'] : '') .'
							</td>
						</tr>
					</table>
					</div></div>';
					
		// previous and next links
		if (!empty($context['zc']['previous_link']) && !empty($context['zc']['next_link']))
			echo '
					<div class="needsPadding"><center>', $context['zc']['previous_link'], '&nbsp;&nbsp;', $context['zc']['next_link'], '</center></div>';
					
		// extra stuff in between article and comments?
		if (!empty($article['extra']['between_article_and_comments']))
			echo '
					<div class="needsPadding"><center>', implode('&nbsp;&nbsp;', $article['extra']['between_article_and_comments']), '</center></div>';
						
			// show comments page index if viewing single article
			if (!empty($article['comments']) && !empty($article['page_index']) && !empty($article['show_page_index']) && !empty($context['viewing_single_article']))
				echo '
					<div style="text-align:left;">' . $article['page_index'] . '&nbsp;&nbsp;'. $article['show_all_comments_link'] . '</div>';

		
		if (!empty($article['show_comments']))
		{
			if (!empty($article['comments']))
			{
				echo '
					<table width="100%" cellspacing="0" cellpadding="0" border="0">
						<tr>
							<td colspan="2" width="100%">';
				$c=0;
				foreach ($article['comments'] as $comment)
				{
					// skip unapproved comments if this user is not allowed to see them....
					if (!empty($context['zc']['blog_settings']['comments_require_approval']) && empty($comment['is_approved']) && !$comment['can_see_unapproved'])
						continue;
						
					$c++;
					template_showComment($comment, $c);
				}
				echo '
							</td>
						</tr>
					</table>';
			}
		}
	}
}

function template_showComment($comment, $c = null)
{
	global $txt, $context;
	if ($c === null)
		$c = 1;
	echo '<br />
					<div id="c'. $comment['id'] .'"></div>
					<div class="', empty($comment['is_approved']) && !empty($comment['approve_link']) ? 'err' : ($c%2 ? 'commentbg' : 'commentbg2'), '">
						<div class="needsPadding">
						<table border="0" cellpadding="0" cellspacing="0" width="100%"><tr>';
									
	if (!empty($comment['poster']['avatar']))
		echo '
							<td valign="middle" width="20%"><div class="needsPadding">', $comment['poster']['avatar'], '</div></td>';
	
	echo '
							<td valign="top">
								<table width="100%" cellspacing="0" cellpadding="0" border="0">
									<tr class="needsPadding">
										<td valign="top" align="left" style="width:97%;"><table width="100%" border="0" cellspacing="0" cellpadding="0" style="table-layout:fixed;"><tr class="noPadding"><td><div id="comment_body_'. $comment['id'] .'" style="overflow: auto; width:100%;">'. $comment['body'] .'</div></td></tr></table></td>
										<td valign="top" align="right" style="width:3%;">';
	
	if (!empty($comment['options']))
	{
		$base_margin_fix = ($context['browser']['is_ie'] ? 0 : (-23));
		$margin_fix = $base_margin_fix;
		if (isset($comment['options']['edit']))
			$margin_fix = $margin_fix + 29;
		if (isset($comment['options']['delete']))
			$margin_fix = $margin_fix + 26;
			
		$width_fix = ($base_margin_fix * (-1)) + $margin_fix;
			
		// don't ask questions... just know it works ;)
		$margin_fix = $margin_fix * (-1);
					
		echo '
											<div class="msgOptionsDropDown" style="white-space:nowrap;">
												<div class="displayOnHover" style="white-space:nowrap; width:'. $width_fix .'px; margin-left:'. $margin_fix .'px;"><div class="action_icons">'. implode('', $comment['options']) .'</div></div>
												<div class="alwaysDisplay"><div class="options_icon">&nbsp;</div></div>
											</div>';
	}
										
	// comment requires approval alert (and its a link to approve the comment)
	if (empty($comment['is_approved']) && !empty($comment['approve_link']))
		echo '
											<div style="margin-top:12px; margin-bottom:6px;">'. $comment['approve_link'] .'</div>';
											
	if (!empty($comment['comment_number']))
		echo '
											<div class="comment_numbers" style="margin-right:3px; margin-top:12px; margin-bottom:6px;">', $comment['comment_number'], '</div>';
										
	echo '
										</td>
									</tr>
								</table>
								<table width="100%" cellspacing="0" cellpadding="0" border="0">
									<tr class="needsPadding">
										<td valign="top" align="left" style="width:92%; padding-top:8px;">
											'. $txt['b40'] .'&nbsp;' . $comment['poster']['link'] . '<span class="commentSpecial">&nbsp;' . $txt['b3034'] . '&nbsp;' . $comment['time'] .'</span>
										</td>
										<td valign="top" align="right" style="width:8%; padding-top:8px; white-space:nowrap;">
									', empty($comment['modified']['name']) && !empty($comment['more_links']) ? implode('', $comment['more_links']) : '', '
										</td>
									</tr>
								</table>';
						
	if (!empty($comment['modified']['name']))
		echo '
								<table width="100%" cellspacing="0" cellpadding="0" border="0">
									<tr class="needsPadding">
										<td valign="top" align="left" style="width:92%; padding-top:8px;">
											<span class="lastEdit">&#171;&nbsp;<i>', $txt['b3043'], ':&nbsp;', $comment['modified']['time'], ' ', $txt['b3045'], ' ', $comment['modified']['name'], '</i>&nbsp;&#187;</span>
										</td>
										<td valign="top" align="right" style="width:8%; padding-top:8px; white-space:nowrap;">
											', !empty($comment['more_links']) ? implode('', $comment['more_links']) : '', '
										</td>
									</tr>
								</table>';
	echo '
							</td>
						</tr></table>
						</div>
					</div>';
}

?>