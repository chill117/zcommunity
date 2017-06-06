<?php

if (!defined('zc'))
	die('Hacking attempt...');

function template_zc_poll($poll)
{
	global $context, $txt, $scripturl;
	
	// obviously need the question ;)
	echo '
		<div class="needsPadding">', $poll['question'], '</div>';
		
	// show expire time if there is one... or show if it is already expired
	if (!empty($poll['expire_time']))
		echo '
		<div class="needsPadding"><span class="smalltext">(', $poll['is_expired'] ? $txt['poll_expired_on'] : $txt['poll_expires_on'], ': ', $poll['expire_time'], ')</span></div>';
							
	echo '
		<div>';						
				
	// Are they not allowed to vote but allowed to view the options?
	if ($poll['show_results'])
	{
		echo '
		<div class="needsPadding" style="text-align:left;">
			<table>';
				
		// Show each option with its corresponding percentage bar.
		foreach ($poll['options'] as $option)
			echo '
				<tr>
					<td style="padding-right: 2ex;', $option['voted_this'] ? 'font-weight: bold;' : '', '">', $option['option'], '</td>', $poll['allow_poll_view'] ? '
					<td nowrap="nowrap">' . $option['bar'] . ' ' . $option['votes'] . ' (' . $option['percent'] . '%)</td>' : '', '
				</tr>';
				
		echo '
			</table>
		</div>
		<div class="needsPadding">';
		
		// echo all the links this user can see
		if (!empty($poll['links']))
			foreach ($poll['links'] as $link)
				echo $link . '<br />';
				
		echo '
		</div>';
		
		if ($poll['allow_poll_view'])
			echo '
		<div class="needsPadding"><b>', $txt['b3013'], ': ', $poll['total_votes'], '</b></div>';
	}
	// They are allowed to vote! Go to it!
	else
	{
		echo '
		<form action="' . $scripturl . '?zc=pollvote'. $context['zc']['blog_request'] . $context['zc']['article_request'] .';poll='. $poll['id'] .'" method="post" accept-charset="'. $context['character_set'] .'">';
				
		// Show a warning if they are allowed more than one option.
		if ($poll['allowed_warning'])
			echo '
		<div class="needsPadding">', $poll['allowed_warning'], '</div>';
									
		echo '
		<div class="needsPadding">
			<table align="center">';
									
		// Show each option with its button - a radio likely.
		foreach ($poll['options'] as $option)
			echo '
				<tr>
					<td align="center">', $option['vote_button'], '</td>
					<td align="left"><label for="' . $option['id'] . '">', $option['option'], '</label></td>
				</tr>';
				
		echo '
			</table>
		</div>
		<div class="needsPadding">';
										
		// echo all the links this user can see
		if (!empty($poll['links']))
			foreach ($poll['links'] as $link)
				echo $link . '<br />';
				
		echo '
		</div>
		<div class="needsPadding">
			<input type="hidden" name="sc" value="', $context['session_id'], '" />
			<input type="submit" value="', $txt['b3014'], '" />
		</div>
		</form>';
	}
	echo '
		</div>';
}

?>