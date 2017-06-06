<?php

if (!defined('zc'))
	die('Hacking attempt...');

function zc_prepare_func_names()
{
	global $zcFunc;
	
	$zcFunc += array(
		'log_error' => 'zc_log_error',
		'un_htmlentities' => 'zc_un_htmlentities',
		'htmlentities' => 'zc_htmlentities',
		'un_htmlspecialchars' => 'zc_un_htmlspecialchars',
		'htmlspecialchars' => 'zc_htmlspecialchars',
		'htmltrim' => 'zc_htmltrim',
		'forum_permission_check' => 'zc_forum_permission_check',
		'parse_bbc' => 'zc_parse_bbc',
	);
}

function zc_can_see_article($info)
{
	global $context;
	
	// admin can access anything!
	if ($context['user']['is_admin'])
		return true;
	// the authors can access their own work... unless they are a guest...
	elseif (!empty($info['poster_id']) && !$context['user']['is_guest'] && $context['user']['id'] == $info['poster_id'])
		return true;
	else
	{
		// assume they can see
		$can_see = true;
		
		// access_restrict?
		if (!empty($info['access_restrict']))
		{
			// it's private!
			if ($info['access_restrict'] == 1)
				$can_see = false;
			// restricted to friends only!
			elseif ($info['access_restrict'] == 2)
			{
				// guests can't have friends... awwwww
				if ($context['user']['is_guest'])
					$can_see = false;
				// can't find the author's friends... return false
				elseif (empty($info['poster_id']))
					$can_see = false;
				else
				{
					global $zcFunc;
					
					// check if user is one of this author's friends...
					$request = $zcFunc['db_query']("
						SELECT {tbl:members::column:id_member} as member_id, {tbl:members::column:buddy_list} as buddy_list
						FROM {db_prefix}{table:members}
						WHERE {tbl:members::column:id_member} = {int:member_id}
						LIMIT 1", __FILE__, __LINE__,
						array(
							'member_id' => $info['poster_id']
						)
					);
					while ($row = $zcFunc['db_fetch_assoc']($request))
						// either the author is a very lonely person... or this user isn't one of the author's friends :(
						if (empty($row['buddy_list']) || !in_array($context['user']['id'], explode(',', $row['buddy_list'])))
							$can_see = false;
					$zcFunc['db_free_result']($request);
				}
			}
			// only specific users are allowed access...
			elseif ($info['access_restrict'] == 3 && (empty($info['users_allowed']) || !in_array($context['user']['id'], explode(',', $info['users_allowed']))))
				$can_see = false;
		}
		
		// does the article require approval, is not approved, and they are not allowed to approve articles?
		if ($can_see && !empty($info['articles_require_approval']) && empty($info['approved']) && empty($context['can_moderate_blog']) && empty($context['can_approve_comments_in_any_b']))
			$can_see = false;
	}
	
	return $can_see;
}

function zc_get_visible_blogs()
{
	global $context;
	global $zcFunc;
	
	// get all the blogs this user can see
	$request = $zcFunc['db_query']("
		SELECT b.blog_id, b.blog_owner, b.member_groups, bs.users_allowed_access, bs.hideBlog AS hidden
		FROM {db_prefix}blogs AS b
			LEFT JOIN {db_prefix}settings AS bs ON (bs.blog_id = b.blog_id)", __FILE__, __LINE__);
		
	$visible_blogs = array();
	while ($row = $zcFunc['db_fetch_assoc']($request))
	{
		$can_see_this_blog = false;
		
		// admins and blog owner can always see
		if ($context['user']['is_admin'] || ($context['user']['id'] == $row['blog_owner']))
		{
			$visible_blogs[] = $row['blog_id'];
			continue;
		}
			
		// figure out if this user can see this blog
		if (empty($row['hidden']))
		{
			$allowedGroups = !empty($row['member_groups']) ? explode(',', $row['member_groups']) : array();
			$can_see_this_blog = count(array_intersect($context['user']['member_groups'], $allowedGroups)) > 0;
		
			// this user might be allowed explicitly by the users_allowed_access setting
			if (empty($can_see_this_blog) && !empty($row['users_allowed_access']) && !$context['user']['is_guest'])
			{
				$users_allowed = !empty($row['users_allowed_access']) ? explode(',', $row['users_allowed_access']) : array();
				$can_see_this_blog = in_array($context['user']['id'], $users_allowed);
			}
		}
					
		if ($can_see_this_blog)	
			$visible_blogs[] = $row['blog_id'];
	}
	return $visible_blogs;
}

function zc_prepare_bookmarking_options_array()
{
	global $context;
	$context['zc']['bookmarking_options'] = array(
		'digg' => array(
			'name' => 'Digg',
			'site_home' => 'http://digg.com',
			'href' => 'http://digg.com/submit?phase=2&amp;url=$1%26title=$2',
			'icon' => '<span class="digg_icon">&nbsp;</span>',
		),
		'delicious' => array(
			'name' => 'Del.icio.us',
			'site_home' => 'http://del.icio.us',
			'href' => 'http://del.icio.us/post?url=$1&amp;title=$2',
			'icon' => '<span class="delicious_icon">&nbsp;</span>',
		),
		'furl' => array(
			'name' => 'Furl',
			'site_home' => 'http://www.furl.net',
			'href' => 'http://www.furl.net/storeIt.jsp?u=$1&amp;t=$2',
			'icon' => '<span class="furl_icon">&nbsp;</span>',
		),
		'stumbleupon' => array(
			'name' => 'Stumble Upon',
			'site_home' => 'http://www.stumbleupon.com',
			'href' => 'http://www.stumbleupon.com/submit?url=$1&amp;title=$2',
			'icon' => '<span class="stumbleupon_icon">&nbsp;</span>',
		),
		'technorati' => array(
			'name' => 'Technorati',
			'site_home' => 'http://technorati.com',
			'href' => 'http://technorati.com/faves?add&amp;url=$1&amp;title=$2',
			'icon' => '<span class="technorati_icon">&nbsp;</span>',
		),
		'reddit' => array(
			'name' => 'Reddit',
			'site_home' => 'http://reddit.com',
			'href' => 'http://reddit.com/submit?url=$1&amp;title=$2',
			'icon' => '<span class="reddit_icon">&nbsp;</span>',
		),
		'blinklist' => array(
			'name' => 'BlinkList',
			'site_home' => 'http://blinklist.com',
			'href' => 'http://blinklist.com/index.php?Action=Blink/addblink.php;url=$1&amp;title=$2',
			'icon' => '<span class="blinklist_icon">&nbsp;</span>',
		),
		'google' => array(
			'name' => 'Google',
			'site_home' => 'http://www.google.com',
			'href' => 'http://www.google.com/bookmarks/mark?op=edit&amp;bkmk=$1&amp;title=$2&amp;labels=$3',
			'icon' => '<span class="google_icon">&nbsp;</span>',
		),
		'twitter' => array(
			'name' => 'Twitter',
			'site_home' => 'http://twitter.com',
			'href' => 'http://twitter.com/home?status=$1',
			'icon' => '<span class="twitter_icon">&nbsp;</span>',
		),
		'facebook' => array(
			'name' => 'Facebook',
			'site_home' => 'http://www.facebook.com',
			'href' => 'http://www.facebook.com/sharer.php?u=$1',
			'icon' => '<span class="facebook_icon">&nbsp;</span>',
		),
		'magnolia' => array(
			'name' => 'Ma.gnolia',
			'site_home' => 'http://ma.gnolia.com',
			'href' => 'http://ma.gnolia.com/bookmarklet/add?url=$1&amp;title=$2',
			'icon' => '<span class="magnolia_icon">&nbsp;</span>',
		),
		'myweb' => array(
			'name' => 'Yahoo! MyWeb',
			'site_home' => 'http://myweb2.search.yahoo.com',
			'href' => 'http://myweb2.search.yahoo.com/myresults/bookmarklet?u=$1&amp;t=$2',
			'icon' => '<span class="myweb_icon">&nbsp;</span>',
		),
		'windowslive' => array(
			'name' => 'Windows Live',
			'site_home' => 'http://favorites.live.com',
			'href' => 'http://favorites.live.com/quickadd.aspx?market=1&amp;mkt=en-us&amp;top=1&amp;url=$1',
			'icon' => '<span class="windowslive_icon">&nbsp;</span>',
		),
		'slashdot' => array(
			'name' => 'Slash Dot',
			'site_home' => 'http://slashdot.org',
			'href' => 'http://slashdot.org/bookmark.pl?title=$2&amp;url=$1',
			'icon' => '<span class="slashdot_icon">&nbsp;</span>',
		),
		'ask' => array(
			'name' => 'Ask',
			'site_home' => 'http://ask.com',
			'href' => 'http://mystuff.ask.com/mysearch/QuickWebSave?v=2.0&amp;t=webpages&url=$1&amp;title=$2',
			'icon' => '<span class="ask_icon">&nbsp;</span>',
		),
		'blinkbits' => array(
			'name' => 'Blink Bits',
			'site_home' => 'http://blinkbits.com',
			'href' => 'http://blinkbits.com/bookmarklets/save.php?v=1&amp;source_url=$1&amp;title=$2',
			'icon' => '<span class="blinkbits_icon">&nbsp;</span>',
		),
		'comments' => array(
			'name' => 'Co.mments',
			'site_home' => 'http://co.mments.com',
			'href' => 'http://co.mments.com/track?url=$1&amp;title=$2',
			'icon' => '<span class="comments_icon">&nbsp;</span>',
		),
		'delirious' => array(
			'name' => 'Delirious',
			'site_home' => 'http://de.lirio.us',
			'href' => 'http://de.lirio.us/rubric/post?uri=$1',
			'icon' => '<span class="delirious_icon">&nbsp;</span>',
		),
		'linkagogo' => array(
			'name' => 'Linkagogo',
			'site_home' => 'http://www.linkagogo.com',
			'href' => 'http://www.linkagogo.com/go/AddNoPopup?url=$1&amp;title=$2',
			'icon' => '<span class="linkagogo_icon">&nbsp;</span>',
		),
		'netvouz' => array(
			'name' => 'Netvouz',
			'site_home' => 'http://netvouz.com',
			'href' => 'http://netvouz.com/action/submitBookmark?url=$1;title=$2&amp;popup=no',
			'icon' => '<span class="netvouz_icon">&nbsp;</span>',
		),
		'newsvine' => array(
			'name' => 'Newsvine',
			'site_home' => 'http://www.newsvine.com',
			'href' => 'http://www.newsvine.com/_wine/save?u=$1&amp;h=$2',
			'icon' => '<span class="newsvine_icon">&nbsp;</span>',
		),
		'rawsugar' => array(
			'name' => 'Raw Sugar',
			'site_home' => 'http://www.rawsugar.com',
			'href' => 'http://www.rawsugar.com/tagger/?turl=$1&amp;tttl=$2',
			'icon' => '<span class="rawsugar_icon">&nbsp;</span>',
		),
		'socializer' => array(
			'name' => 'Socializer',
			'site_home' => 'http://ekstreme.com/socializer',
			'href' => 'http://ekstreme.com/socializer/?url=$1&amp;title=$2',
			'icon' => '<span class="socializer_icon">&nbsp;</span>',
		),
		'sphinn' => array(
			'name' => 'Sphinn',
			'site_home' => 'http://sphinn.com',
			'href' => 'http://sphinn.com/submit.php?url=$1&amp;title=$2',
			'icon' => '<span class="sphinn_icon">&nbsp;</span>',
		),
		'squidoo' => array(
			'name' => 'Squidoo',
			'site_home' => 'http://www.squidoo.com',
			'href' => 'http://www.squidoo.com/lensmaster/bookmark?$1',
			'icon' => '<span class="squidoo_icon">&nbsp;</span>',
		),
	);
}

// font sizes in pixels (px)...
function zcTagFontSize($numArticles, $total_instances)
{
	$maxfontsize = 24;
	$basefontsize = 12;
	
	if (empty($total_instances) || empty($numArticles))
		return $basefontsize;
	
	// percent_per_pixel_increase is the number of % points that are needed to increase the pixel-size of text by 1
	$percent_per_pixel_increase = ($total_instances > 5) && (($total_instances - 3) > 1) ? 50 / ($total_instances - 2) : 200;
		
	// add to the basefontsize...
	$return = $basefontsize + floor((100 * $numArticles) / ($total_instances * $percent_per_pixel_increase));
	
	// can't be more than maxfontsize
	if ($return > $maxfontsize)
		return $maxfontsize;
		
	return $return;
}

function zcCreateTableHeaders($tableHeaders, $list_num = null)
{
	global $zc, $txt, $scripturl;
	
	if ($list_num === null || $list_num == 1)
		$list_num = '';
	
	$return = array();
	
	if (!empty($tableHeaders['headers']))
		foreach ($tableHeaders['headers'] as $key => $header)
			if (!empty($header['label']))
				$return[$key] = '<a href="' . $scripturl . $tableHeaders['url_requests'] . (!empty($tableHeaders['url_requests']) ? ';' : '?') . 'sort'. $list_num .'=' . $key . ($tableHeaders['sort_by'] == $key ? ($tableHeaders['sort_direction'] == 'up' ? ';desc'. $list_num : ';asc'. $list_num) : '') . (!empty($tableHeaders['url_end']) ? $tableHeaders['url_end'] : '') . '" title="'. (!empty($header['title']) ? $header['title'] : $txt['b105'] . ' ' . $header['label'] . ($tableHeaders['sort_by'] == $key ? ' (' . ($tableHeaders['sort_direction'] == 'up' ? $txt['b94a'] : $txt['b95a']) . ')' : '')) .'" rel="nofollow" style="white-space:nowrap;">' . $header['label'] . ($tableHeaders['sort_by'] == $key ? ' <img src="' . $zc['default_images_url'] . '/sort_' . $tableHeaders['sort_direction'] . '.png" alt="" style="margin-bottom:-2px;" />' : '') . '</a>';
			else
				$return[$key] = '';
			
	return $return;
}

function zcConstructPageIndex($base_url, &$start, $max_value, $num_per_page, $flexible_start = false)
{
	global $zc, $txt;

	// Save whether $start was less than 0 or not.
	$start_invalid = $start < 0;

	// Make sure $start is a proper variable - not less than 0.
	if ($start_invalid)
		$start = 0;
	// Not greater than the upper bound.
	elseif ($start >= $max_value)
		$start = max(0, (int) $max_value - (((int) $max_value % (int) $num_per_page) == 0 ? $num_per_page : ((int) $max_value % (int) $num_per_page)));
	// And it has to be a multiple of $num_per_page!
	else
		$start = max(0, (int) $start - ((int) $start % (int) $num_per_page));

	// Wireless will need the protocol on the URL somewhere.
	if (WIRELESS)
		$base_url .= ';' . WIRELESS_PROTOCOL;

	$base_link = '<a href="' . ($flexible_start ? $base_url : strtr($base_url, array('%' => '%%')) . ';start=%1$d') . '" title="' . $txt['b111'] . ' %2$d">%3$s</a> ';

	// compact page indexes is off or on?
	if (empty($zc['settings']['compact_page_indexes']))
	{
		// Show the left arrow.
		$pageindex = $start == 0 ? ' ' : sprintf($base_link, $start - $num_per_page, '', '&#171;');

		// Show all the pages.
		$display_page = 1;
		for ($counter = 0; $counter < $max_value; $counter += $num_per_page)
			$pageindex .= $start == $counter && !$start_invalid ? '<b>' . $display_page++ . '</b> ' : sprintf($base_link, $counter, $display_page++, $display_page++);

		// Show the right arrow.
		$display_page = ($start + $num_per_page) > $max_value ? $max_value : ($start + $num_per_page);
		if ($start != $counter - $max_value && !$start_invalid)
			$pageindex .= $display_page > $counter - $num_per_page ? ' ' : sprintf($base_link, $display_page, '', '&#187;');
	}
	else
	{
		// If they didn't enter an odd value, pretend they did.
		$PageContiguous = (int) ($zc['settings']['compact_page_indexes_contiguous'] - ($zc['settings']['compact_page_indexes_contiguous'] % 2)) / 2;

		// Show the first page. (>1< ... 6 7 [8] 9 10 ... 15)
		if ($start > $num_per_page * $PageContiguous)
			$pageindex = sprintf($base_link, 0, 1, 1);
		else
			$pageindex = '';

		// Show the ... after the first page.  (1 >...< 6 7 [8] 9 10 ... 15)
		if ($start > $num_per_page * ($PageContiguous + 1))
			$pageindex .= '<span class="zcPageIndexDots"> ... </span>';

		// Show the pages before the current one. (1 ... >6 7< [8] 9 10 ... 15)
		for ($nCont = $PageContiguous; $nCont >= 1; $nCont--)
			if ($start >= $num_per_page * $nCont)
			{
				$tmpStart = $start - $num_per_page * $nCont;
				$pageindex.= sprintf($base_link, $tmpStart, $tmpStart / $num_per_page + 1, $tmpStart / $num_per_page + 1);
			}

		// Show the current page. (1 ... 6 7 >[8]< 9 10 ... 15)
		if (!$start_invalid)
			$pageindex .= '<span class="zcPageIndexCurrent">' . ($start / $num_per_page + 1) . '</span> ';
		else
			$pageindex .= sprintf($base_link, $start, $start / $num_per_page + 1, $start / $num_per_page + 1);

		// Show the pages after the current one... (1 ... 6 7 [8] >9 10< ... 15)
		$tmpMaxPages = (int) (($max_value - 1) / $num_per_page) * $num_per_page;
		for ($nCont = 1; $nCont <= $PageContiguous; $nCont++)
			if ($start + $num_per_page * $nCont <= $tmpMaxPages)
			{
				$tmpStart = $start + $num_per_page * $nCont;
				$pageindex .= sprintf($base_link, $tmpStart, $tmpStart / $num_per_page + 1, $tmpStart / $num_per_page + 1);
			}

		// Show the '...' part near the end. (1 ... 6 7 [8] 9 10 >...< 15)
		if ($start + $num_per_page * ($PageContiguous + 1) < $tmpMaxPages)
			$pageindex .= '<span class="zcPageIndexDots"> ... </span>';

		// Show the last number in the list. (1 ... 6 7 [8] 9 10 ... >15<)
		if ($start + $num_per_page * $PageContiguous < $tmpMaxPages)
			$pageindex .= sprintf($base_link, $tmpMaxPages, $tmpMaxPages / $num_per_page + 1, $tmpMaxPages / $num_per_page + 1);
	}

	return '<span class="zcPageIndex">' . $pageindex . '</span>';
}

function zcTruncateText($text, $max_length, $break_with = ' ', $min_after_break = 1, $padding_element = '[ ... ]', $padding_anchor_href = '', $padding_anchor_title = '')
{
	if (empty($max_length) || empty($text))
		return $text;

	if (strlen($text) > $max_length)
	{
		// we are looking for the first instance of $break_with AT/AFTER the max_length
		$breakpoint = strpos($text, $break_with, $max_length);
		
		// there has to be a breakpoint
		if (!empty($breakpoint))
		{
			$after_breakpoint = substr($text, $breakpoint);
			
			// there must be a minimum # of characters after the break point
			if (strlen($after_breakpoint) <= $min_after_break)
				return $text;
				
			$before_breakpoint = substr($text, 0, $breakpoint);
			
			$last_chars = array();
			$last_chars[9] = substr($before_breakpoint, $breakpoint - 9, $breakpoint);
			$last_chars[8] = substr($last_chars[9], -8);
			$last_chars[7] = substr($last_chars[8], -7);
			$last_chars[6] = substr($last_chars[7], -6);
			$last_chars[5] = substr($last_chars[6], -5);
			$last_chars[4] = substr($last_chars[5], -4);
			$last_chars[3] = substr($last_chars[4], -3);
			$last_chars[2] = substr($last_chars[3], -2);
			
			// common html tags that need a closing tag
			$html_tags = array('a', 'abbr', 'area', 'b', 'center', 'div', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'i', 'label', 'li', 'noscript', 'object', 'p', 'pre', 'script', 'span', 'strike', 'strong', 'sub', 'sup', 'table', 'td', 'th', 'thead', 'tr', 'tt', 'ul', 'u');
			
			// most common self-closing tags
			$self_closing_tags = array('br', 'hr', 'img', 'input', 'param');
			
			// self-closing tags...
			foreach ($self_closing_tags as $tag)
			{
				// make sure the last occurrence of this tag in the text was closed....
				$pos = strrpos($before_breakpoint, '<' . $tag);
				
				// chop off the broken self-closing tag....
				if ($pos !== false && strpos(substr($before_breakpoint, $pos), '/>') === false)
					$before_breakpoint = substr($before_breakpoint, 0, $pos);
			}
			
			$closing_tags_needed = array();
			foreach ($html_tags as $tag)
			{
				$num_open = substr_count($before_breakpoint, '<'. $tag . '>');
				$num_closed = substr_count($before_breakpoint, '</'. $tag . '>');
				if ($num_open > $num_closed)
				{
					$num_closers_needed = $num_open - $num_closed;
					$closing_tags_needed[$tag] = $num_closers_needed;
				}
				
				// if the text ends with a broken tag... we'll just cut it off....
				$l = strlen($tag) + 1;
				if (isset($last_chars[$l]) && $last_chars[$l] == '<' . $tag)
					$before_breakpoint = substr($before_breakpoint, (-1) * $l);
			}
			
			// add closing tags if needed
			if (!empty($closing_tags_needed))
				foreach($closing_tags_needed as $tag => $num_needed)
					for($i = 0; $i < $num_needed; $i++)
						$before_breakpoint = $before_breakpoint . '</'. $tag . '>';
		
			// $min_after_break has to be at least 1 and has to be an int
			if (!is_int($min_after_break) || $min_after_break <= 0)
				$min_after_break = 1;
			
			// set $text equal to the sub string before the breakpoint
			$text = $before_breakpoint;
			
			// do we want to add on a padding element?
			if (!empty($padding_element))
			{
				// will this padding_element be an anchor?
				if (!empty($padding_anchor_href))
				{
					$text .= ' <a href="' . $padding_anchor_href . '"' . (!empty($padding_anchor_title) ? ' title="' . $padding_anchor_title . '"' : '') . ' style="white-space:nowrap;" rel="nofollow">' . $padding_element . '</a>';
				}
				else
					$text .= ' ' . $padding_element;
			}
		}
	}
	return $text;
}

function zcResizeImage($file, $max_width = null, $max_height = null, $current_width = null, $current_height = null)
{
	if (!empty($current_width) && !empty($current_height))
	{
		$image[0] = $current_width;
		$image[1] = $current_height;
	}
	else
	{
		$image = @getimagesize($file);
		if ($image == false)
			return false;
	}
	$width = $image[0];
	$height = $image[1];
	if ($max_width !== null && $width > $max_width)
	{
		$height = floor($height * ($max_width / $width));
		$width = $max_width;
	}
	if ($max_height !== null && $height > $max_height)
	{
		$width = floor($width * ($max_height / $height));
		$height = $max_height;
	}
	return array($width, $height);
}

function zcLoadAvatar($array, $filename, $max_width = null, $max_height = null, $current_width = null, $current_height = null)
{
	global $scripturl, $modSettings;
	global $zcFunc;
	
	list($avatar_width, $avatar_height) = zcResizeImage($filename, $max_width, $max_height, $current_width, $current_height);
	
	$avatar_width = ' width="'. $avatar_width .'"';
	$avatar_height = ' height="'. $avatar_height .'"';
	
	$return = $array['avatar'] == '' ? ($array['id_attach'] > 0 ? '<img src="' . (empty($array['attachment_type']) ? $scripturl . '?action=dlattach;attach=' . $array['id_attach'] . ';type=avatar' : $modSettings['custom_avatar_url'] . '/' . $array['filename']) . '"' . $avatar_width . $avatar_height . ' alt="" class="avatar" border="0" />' : '') : (stristr($array['avatar'], 'http://') ? '<img src="' . $array['avatar'] . '"' . $avatar_width . $avatar_height . ' alt="" class="avatar" border="0" />' : '<img src="' . $modSettings['avatar_url'] . '/' . $zcFunc['htmlspecialchars']($array['avatar']) . '"' . $avatar_width . $avatar_height . ' alt="" class="avatar" border="0" />');
	
	return $return;
}

function zcProcessForm($form_info)
{
	global $txt, $context;
	global $zcFunc, $zc;
	
	// these are file names we never want to allow... ever
	$disabled_files = array('con', 'com1', 'com2', 'com3', 'com4', 'prn', 'aux', 'lpt1', '.htaccess', 'index.php');
	// file extensions that we want to disallow for any and all file uploads
	$disallowed_file_extensions = array();
	// default maximum upload directory size (bytes)
	$default_dir_size = 4194304;
	
	// post variable for id... this is used when editing an item from a database
	$id = isset($_POST['id']) ? (int) $_POST['id'] : '';
	
	$id_column = isset($form_info['_info_']['table_info']['id_column']) ? $form_info['_info_']['table_info']['id_column'] : '';
	$table_name = isset($form_info['_info_']['table_info']['unprefixed_name']) ? $form_info['_info_']['table_info']['unprefixed_name'] : '';
	
	$can_post_urls = !$context['user']['is_guest'] || empty($zc['settings']['guests_no_post_links']);
	
	// set up some arrays
	$errors = array();
	$processed = array();
	foreach ($form_info as $key => $info)
	{
		// these aren't form fields...
		if (empty($key) || in_array($key, array('_info_')))
			continue;
			
		// must_return_true is used if we want to exclude form fields from processing under specific circumstances...
		if (isset($info['must_return_true']) && ($info['must_return_true'] !== true))
			continue;
			
		// must have a valid type!
		if (!isset($info['type']) || !in_array($info['type'], array('file', 'check', 'int', 'text', 'float')))
			continue;
			
		// not using $_POST data... use value from form_info array...
		if (!empty($info['no_template']))
		{
			$processed[$key] = addslashes($zcFunc['htmlspecialchars'](stripslashes($info['value'])));
			continue;
		}
		
		// additional columns for preventing duplicates
		if (!empty($info['additional_prevent_duplicates']))
		{
			$x = 0;
			foreach ($info['additional_prevent_duplicates'] as $c => $v)
			{
				if (!isset($additional_prevent_duplicates))
					$additional_prevent_duplicates = array('conditions' => array(), 'info' => array());
				
				$additional_prevent_duplicates['info'][$c . $x] = $v;
				$additional_prevent_duplicates['conditions'][] = $c . ' = {string:' . $c . $x . '}';
				
				$x++;
			}
		}
		
		// format label...
		if (!empty($info['label']))
			$info['label'] = zcFormatTxtString($info['label']);
			
		// file uploads... weeeeee!
		if ($info['type'] == 'file')
		{
			// nothing to do....
			if (!isset($_FILES[$key]) || (empty($_FILES[$key]['tmp_name']) && empty($_FILES[$key]['tmp_name'][0])))
				continue;
				
			// we're previewing ... so don't process the file uploads yet...
			if (!empty($context['zc']['previewing']))
				continue;
				
			if (empty($info['dir']))
			{
				// says unknown problem, but it's obvious... we're missing the directory info!
				$errors[$key] = 'zc_error_72';
				continue;
			}
			elseif (!is_writable($info['dir']))
			{
				// attempt to chmod it
				@chmod($info['dir'], 0777);
				
				if (!is_writable($info['dir']))
				{
					// the upload directory has to be writable!
					$errors[$key] = 'zc_error_84';
					continue;
				}
			}
				
			// we do this so that even file upload fields that are not arrays, are processed as arrays... it's easier
			if (!is_array($_FILES[$key]['tmp_name']))
				$_FILES[$key] = array(
					'tmp_name' => array($_FILES[$key]['tmp_name']),
					'name' => array($_FILES[$key]['name']),
					'size' => array($_FILES[$key]['size']),
					'error' => array($_FILES[$key]['error']),
					'type' => array($_FILES[$key]['type']),
				);
				
			$files = array();
			foreach ($_FILES[$key]['tmp_name'] as $n => $dummy)
				if (!empty($_FILES[$key]['tmp_name'][$n]))
					$files[$n] = array(
						'tmp_name' => $_FILES[$key]['tmp_name'][$n],
						'name' => $_FILES[$key]['name'][$n],
						'size' => $_FILES[$key]['size'][$n],
						'error' => $_FILES[$key]['error'][$n],
						'type' => $_FILES[$key]['type'][$n],
					);
		
			if (count($files) != 0)
			{
				$dir_size = 0;
				// let's get the current size of the target directory...
				if (($dir = @opendir($info['dir'])) !== false)
				{
					while ($file = readdir($dir))
					{
						if ($file != '.' || $file != '..')
						{
							$size = filesize($info['dir'] . '/' . $file);
						
							if (preg_match('~^post_tmp_\d+_\d+$~', $file) != 0)
							{
								// Temp file is more than 5 hours old!
								if (time() - filemtime($info['dir'] . '/' . $file) > 18000)
									unlink($info['dir'] . '/' . $file);
								elseif (!empty($info['max_dir_size']) || !empty($default_dir_size))
									$dir_size += $size;
							}
							elseif (!empty($info['max_dir_size']) || !empty($default_dir_size))
								$dir_size += $size;
						}
					}
					closedir($dir);
				}
				// couldn't access the target directory...
				else
				{
					$errors[$key] = 'zc_error_58';
					continue;
				}
					
				// if there is a max directory size... check it
				if ((!empty($info['max_dir_size']) && $dir_size >= $info['max_dir_size']) || (empty($info['max_dir_size']) && !empty($default_dir_size) && $dir_size >= $default_dir_size))
				{
					$errors[$key] = 'zc_error_59';
					continue;
				}
				
				// how many files did they upload? (this is before we extract any archives)
				$num_files = count($files);
					
				// have they tried to upload more than are allowed?
				if (isset($info['max_num_fields']) && $num_files > $info['max_num_fields'])
				{
					$errors[$key] = array('zc_error_56', $info['max_num_fields'], $info['label']);
					continue;
				}
	
				// no errors?
				if (!isset($errors[$key]))
				{
					// now we process each file
					foreach ($files as $n => $dummy)
					{
						$file = array(
							'tmp_name' => $_FILES[$key]['tmp_name'][$n],
							'name' => str_replace(' ', '', $_FILES[$key]['name'][$n]),
							'type' => $_FILES[$key]['type'][$n],
							'size' => $_FILES[$key]['size'][$n],
							'error' => $_FILES[$key]['error'][$n],
							'basename' => basename(str_replace(' ', '', $_FILES[$key]['name'][$n])),
						);
						
						// make sure this file was uploaded via HTTP POST
						if (!is_uploaded_file($file['tmp_name']) || (!@ini_get('open_basedir') != '' && !file_exists($file['tmp_name'])))
						{
							// it says it's unknown... but we know... they are up to no good
							$errors[$key] = 'zc_error_72';
							continue;
						}
					
						// there were errors... so destroy everything
						if (isset($errors[$key]))
						{
							// destroy the temp file on the server
							if (!empty($_FILES[$key]['tmp_name'][$n]) && is_uploaded_file($_FILES[$key]['tmp_name'][$n]))
								unlink($_FILES[$key]['tmp_name'][$n]);
							// destroy the variables
							unset($_FILES[$key][$n], $files[$n]);
						}
						else
						{
							$file['file_extension'] = strtolower(strrpos($file['name'], '.') !== false ? substr($file['name'], strrpos($file['name'], '.') + 1) : '');
							
							/*if (in_array($file['file_extension'], array('zip')))
							{
								$actual_size = 0;
								$handle = @zip_open($file['tmp_name']);
								while ($entry = @zip_read($handle))
								{
									if (@zip_entry_open($handle, $entry) !== false)
									{
										$size = @zip_entry_filesize($entry);
										if (!empty($size))
											$actual_size += $size;
											
										@zip_entry_close($entry);
									}
								}
								@zip_close($handle);
								
								// set this file's size to the actual unzipped size of all files inside
								$file['size'] = $actual_size;
							}*/
							
							// make sure it has an allowed file extension...
							if (empty($info['allowed_file_extensions']) || !in_array($file['file_extension'], $info['allowed_file_extensions']))
							{
								$errors[$key] = array('zc_error_61', $info['label'], $file['file_extension']);
								continue;
							}
							
							// some files are not allowed....
							if (in_array(strtolower($file['basename']), $disabled_files))
							{
								$errors[$key] = $num_files > 1 ? 'zc_error_57' : 'zc_error_55';
								continue;
							}
							
							// if there is a maximum individual file size... check it
							if (!empty($info['max_file_size']) && $file['size'] >= $info['max_file_size'])
							{
								$errors[$key] = array('zc_error_71', number_format($info['max_file_size'], 0, '.', ',') . ' ' . $txt['b534']);
								continue;
							}
							
							// make sure the directory has room...
							if (!empty($info['max_dir_size']) && $file['size'] + $dir_size > $info['max_dir_size'])
							{
								$errors[$key] = 'zc_error_59';
								continue;
							}
								
							// only continue processing this file if no errors....
							if (!isset($errors[$key]))
							{
								// do a check if this file is going to be an attachment
								/*if (!empty($info['is_attachment']))
								{
									// make sure there isn't another attachment with that name
									$request = $zcFunc['db_query']("
										SELECT attachment_id
										FROM {db_prefix}attachments
										WHERE file_name = '" . $file['name'] . "'
										LIMIT 1", __FILE__, __LINE__);
										
									// oops... name taken...
									if ($zcFunc['db_num_rows']($request) > 0)
										$errors[$key] = array('zc_error_60', $file['name']);
										
									$zcFunc['db_free_result']($request);
									
									if (isset($errors[$key]))
										continue;
								}*/
								
								// extra processing?
								if (!empty($info['more_processing']))
								{
									// place $info and $file into global space
									$context['zc']['for_more_processing'] = array($info, $file);
								
									if (is_array($info['more_processing']) && file_exists($info['more_processing'][0]))
									{
										// get the file in which the function is located...
										require_once($info['more_processing'][0]);
										$more_processing_func = $info['more_processing'][1];
									}
									elseif (!is_array($info['more_processing']))
										$more_processing_func = $info['more_processing'];
									else
										$more_processing_func = '';
										
									if (!empty($more_processing_func) && function_exists($more_processing_func))
									{
										$temp = $info['more_processing'][1]();
										if (!empty($temp))
										{
											if (is_array($temp) && in_array($temp[0], array('error', 'processed')))
											{
												list($result_type, $result) = $temp;
												
												if ($result_type == 'processed')
													$processed[$key] = $result;
												// assume error...
												else
													$errors[$key] = $result;
												
												unset($result_code, $result);
											}
											// otherwise assume it's an error...
											else
												$errors[$key] = $temp;
										}
										unset($temp);
									}
									else
										$errors[$key] = 'zc_error_72';
										
									// don't need this anymore
									unset($context['zc']['for_more_processing']);
								}
									
								// still no errors and we haven't finalized the upload yet...
								if (!isset($errors[$key]) && file_exists($file['tmp_name']))
								{
									// don't want to extract archive files OR this isn't an archive file?
									//if (empty($info['extract_archives']) || !in_array($file['file_extension'], array('zip')))
									//{
										// new location for file...
										$destination = $info['dir'] . '/' . $file['basename'];
										
										// attempt to save the file to a new location
										if (!move_uploaded_file($file['tmp_name'], $destination))
											$errors[$key] = 'zc_error_72';
										
										// if we successfully uploaded the file, attempt to chmod it
										if (!isset($errors[$key]))
											chmod($destination, 0644);
									//}
									// it's an archive file and we want to extract it
									/*else
									{
										require_once($zc['with_software']['sources_dir'] . '/Subs-Package.php');
										if (function_exists('read_tgz_file'))
											$extracted = read_tgz_file($file['tmp_name'], $info['dir'], false, true);
									}*/
										
									// destroy the tmp file if it still exists...
									if (file_exists($file['tmp_name']))
										unlink($file['tmp_name']);
								}
							}
						}
						
						// there were errors... make sure this file is destroyed...
						if (isset($errors[$key]) && isset($_FILES[$key][$n]))
						{
							// destroy the tmp file on the server
							if (!empty($_FILES[$key]['tmp_name'][$n]) && is_uploaded_file($_FILES[$key]['tmp_name'][$n]))
								unlink($_FILES[$key]['tmp_name'][$n]);
							// destroy the variables
							unset($files[$n]);
						}
					}
				}
			}
			continue;
		}
		// all other types....
		else
		{
			$_POST[$key] = isset($_POST[$key]) ? $_POST[$key] : '';
				
			// do we need to reverse magic quotes?
			if ($zc['settings']['need_reverse_magic_quotes'])
			{
				if (is_array($_POST[$key]))
				{
					foreach ($_POST[$key] as $k => $v)
						$_POST[$key][$k] = stripslashes($v);
				}
				else
					$_POST[$key] = stripslashes($_POST[$key]);
			}
			
			// multiple checkbox fields...
			if (!empty($info['custom']) && $info['custom'] == 'multi_check')
			{
				$temp[$key] = array();
				
				// add always included values to the temp array
				if (!empty($info['always_include']))
					foreach ($info['always_include'] as $v)
						$temp[$key][] = $v;
				
				// for each option that is checked... add it to the temp array (if it is actually an option)
				if (!empty($_POST[$key]) && is_array($_POST[$key]))
					foreach ($_POST[$key] as $k)
						if (isset($info['options'][$k]))
							$temp[$key][] = $k;
				
				// convert this array to a text string
				if ($info['type'] == 'text')
					$_POST[$key] = implode(',', $temp[$key]);
							
				unset($temp);
			}
			// for when a user is filling a field with a list of items separated by commas
			elseif (!empty($_POST[$key]) && !empty($info['extra']) && $info['extra'] == 'comma_separated_list')
			{
				// get rid of double and single quotes
				$temp = strtr($_POST[$key], array('\'' => '', '"' => ''));
				
				// explode... then trim each value... gets rid of excess blank space around values and excess commas
				$temp = explode(',', $temp);
				$cleaned_items = array();
				if (!empty($temp))
					foreach ($temp as $untrimmed)
						if (trim($untrimmed) != '')
						{
							$temp = trim($untrimmed);
							if (!empty($info['chop_words']))
								$temp = zcBreakWords($untrimmed, $zc['settings']['max_chars_per_word'], (isset($info['parses_bbc']) ? $info['parses_bbc'] : false));
								
							// make sure each tag is not longer than the max num characters...
							$cleaned_items[] = isset($info['max_length_per_list_item']) && strlen(trim($untrimmed)) > $info['max_length_per_list_item'] ? substr($temp, 0, $info['max_length_per_list_item']) : $temp;
						}
							
				$do_not_break_words = true;
							
				// implode back into text string...
				$_POST[$key] = implode(',', $cleaned_items);
							
				unset($temp);
			}
			
			$was_not_array = !is_array($_POST[$key]);
			
			// if post is not an array... make it an array...
			if (!is_array($_POST[$key]))
				$_POST[$key] = array($_POST[$key]);
			
			$processed[$key] = array();
			foreach ($_POST[$key] as $sub_key => $post_value)
			{
				if ($info['type'] == 'int')
					$temp_value = isset($post_value) ? (int) $post_value : 0;
				elseif ($info['type'] == 'check')
					$temp_value = empty($post_value) ? 0 : 1;
				elseif ($info['type'] == 'float')
					$temp_value = isset($post_value) ? (!empty($info['precision']) && is_int($info['precision']) ? round((string) (float) $post_value, $info['precision']) : (string) (float) $post_value) : 0;
				elseif ($info['type'] == 'text')
				{
					if (!empty($info['max_length']) && strlen($post_value) > $info['max_length'])
					{
						// use substr() to shorten...
						if (!empty($info['use_substr_to_shorten']))
						{
							$_POST[$key][$sub_key] = substr($post_value, 0, $info['max_length']);
							$temp_value = (string) $post_value;
							
							// if html and php is not allowed... strip all tags
							if (empty($info['is_raw_html']) && empty($info['is_php']))
								$temp_value = strip_tags($temp_value);
							elseif (!empty($info['is_raw_html']) && !empty($info['allowed_html_tags']))
								$temp_value = strip_tags($temp_value, $info['allowed_html_tags']);
								
							$temp_value = substr(addslashes($zcFunc['htmlspecialchars']($temp_value, ENT_QUOTES)), 0, $info['max_length']);
						}
						// it's too long... error!
						else
							$errors[$key] = array('zc_error_74', 'b541', $info['label'], $info['max_length']);
					}
					elseif (!empty($info['min_length']) && strlen(strip_tags($zcFunc['parse_bbc']($post_value))) < $info['min_length'])
						$errors[$key] = array('zc_error_74', 'b542', $info['label'], $info['min_length']);
					else
						$temp_value = !empty($post_value) ? (string) $post_value : '';
							
					if (!empty($temp_value))
					{
						// check to see if there are any URLs in the text...
						if (empty($can_post_urls) && ((strpos($temp_value, '://') !== false || strpos($temp_value, 'www.') !== false)))
						{
							$data = $temp_value;
							// Switch out quotes really quick because they can cause problems.
							$data = strtr($data, array('&#039;' => '\'', '&nbsp;' => $context['utf8'] ? "\xC2\xA0" : "\xA0", '&quot;' => '>">', '"' => '<"<', '&lt;' => '<lt<'));
							if (preg_match('~(?<=[\s>\.(;\'"]|^)((?:http|https|ftp|ftps)://[\w\-_%@:|]+(?:\.[\w\-_%]+)*(?::\d+)?(?:/[\w\-_\~%\.@,\?&;=#+:\'\\\\]*|[\(\{][\w\-_\~%\.@,\?&;=#(){}+:\'\\\\]*)*[/\w\-_\~%@\?;=#}\\\\])~i', $data) == 1 || preg_match('~(?<=[\s>(\'<]|^)(www(?:\.[\w\-_]+)+(?::\d+)?(?:/[\w\-_\~%\.@,\?&;=#+:\'\\\\]*|[\(\{][\w\-_\~%\.@,\?&;=#(){}+:\'\\\\]*)*[/\w\-_\~%@\?;=#}\\\\])~i', $data) == 1)
								// ah ha!
								$errors[$key] = 'zc_error_75';
							unset($data);
						}
					
						// if html and php is not allowed... strip all tags
						if (empty($info['is_raw_html']) && empty($info['is_php']))
							$temp_value = strip_tags($temp_value);
						// if this is raw html and we have a set of allowed html tags.. strip all tags but those...
						elseif (!empty($info['is_raw_html']) && !empty($info['allowed_html_tags']))
							$temp_value = strip_tags($temp_value, $info['allowed_html_tags']);
						
						// break up long words...
						if (empty($do_not_break_words) && !empty($info['chop_words']) && !empty($zc['settings']['max_chars_per_word']))
							$temp_value = zcBreakWords($temp_value, $zc['settings']['max_chars_per_word'], (isset($info['parses_bbc']) ? $info['parses_bbc'] : false));
							
						$temp_value = addslashes($zcFunc['htmlspecialchars']($temp_value, ENT_QUOTES));
					}
				}
				else
					$errors[$key] = array('zc_error_10', $info['label']);
						
				$processed[$key][] = isset($temp_value) ? $temp_value : '';
				
				if (isset($temp_value))
					unset($temp_value);
			}
			
			if ($was_not_array)
			{
				if (empty($errors[$key]))
					$processed[$key] = $processed[$key][0];
				 
				 // turn $_POST value back into non-array...
				 $_POST[$key] = $_POST[$key][0];
			}
			else
			{
				// do special processing for fields that were arrays?
				$i = 0;
				foreach ($processed[$key] as $sub_key => $sub_value)
					if (empty($sub_value))
						unset($processed[$key][$sub_key]);
					else
						$i++;
						
				// we don't have enough non-empty fields!
				if (!empty($info['minimum_num_fields']) && $i < $info['minimum_num_fields'])
					$errors[$key] = array('zc_error_39', $info['minimum_num_fields'], $info['label']);
				
				continue;
			}
			
			// if this is a required field, we should make sure it's definitely not empty...
			if (!empty($info['required']) && empty($errors[$key]))
				// make sure it's not just whitespace
				if (trim($processed[$key]) == '')
					$errors[$key] = array('zc_error_6', $info['label']);
				// if this field gets its bbc parsed, make sure it's got more than just bbc tags
				elseif (!empty($info['parses_bbc']))
					if (trim(strip_tags($zcFunc['parse_bbc']($processed[$key], false), '<img>')) === '')
						$errors[$key] = array('zc_error_6', $info['label']);
			
			// check database table for duplicate value
			if (!empty($info['must_be_unique']) && empty($errors[$key]) && !empty($table_name) && (!empty($id_column) || empty($id)))
			{
				$request = $zcFunc['db_query']("
					SELECT {raw:key_column}
					FROM {db_prefix}{raw:table_name}
					WHERE {raw:key_column} = {string:key}" . (!empty($id) ? "
						AND {raw:id_column} != {string:id}" : '') . (!empty($additional_prevent_duplicates['conditions']) ? "
						AND " . implode(',
						AND ', $additional_prevent_duplicates['conditions']) : '') . "
					LIMIT 1", __FILE__, __LINE__,
					array_merge(
						array(
							'table_name' => (string) $table_name,
							'key_column' => (string) $key,
							'key' => (string) $processed[$key],
							'id_column' => (string) $id_column,
							'id' => (string) $id
						),
						!empty($additional_prevent_duplicates['info']) ? $additional_prevent_duplicates['info'] : array()
					)
				);
				if ($zcFunc['db_num_rows']($request) > 0)
					$errors[$key] = array('zc_error_8', $info['label']);
				$zcFunc['db_free_result']($request);
			}
			
			// add to combo_unique array to process after all the individual fields are processed
			if (!empty($info['combo_unique']))
			{
				if (!isset($combo_unique))
					$combo_unique = array('conditions' => array(), 'info' => array());
					
				$combo_unique['info'][$key] = $processed[$key];
			}
		}
	}
	
	// verify that this is a unique combination of values for these fields...
	if (!empty($combo_unique) && empty($errors))
	{
		$x = 0;
		$labels = array();
		// we want to get one of the columns in the table... and the labels for all the fields in combo_unique
		foreach ($combo_unique['info'] as $k => $stuff)
		{
			if (!isset($some_column) || ($some_column[1] != 'check' && $form_info[$k]['type'] == 'check') || (!in_array($some_column[1], array('int', 'check')) && in_array($form_info[$k]['type'], array('int', 'check'))))
				$some_column = array($k, $form_info[$k]['type']);
			$labels[] = $form_info[$k]['label'];
			
			// reindex $combo_unique and make condition for each...
			// we reindex to prevent conflicts with table_name and some_column in the info array for the query
			$combo_unique['info'][$k . $x] = $stuff;
			$combo_unique['conditions'][] = $k . ' = {string:' . $k . $x . '}';
			unset($combo_unique['info'][$k]);
			$x++;
		}
		$request = $zcFunc['db_query']("
			SELECT {raw:some_column}
			FROM {db_prefix}{raw:table_name}
			WHERE " . implode("
				AND ", $combo_unique['conditions']) . "
			LIMIT 1", __FILE__, __LINE__,
			array_merge(
				array(
					'table_name' => (string) $table_name,
					'some_column' => (string) $some_column[0]
				),
				$combo_unique['info']
			)
		);
		if ($zcFunc['db_num_rows']($request) > 0)
			$errors[] = array('zc_error_9', implode(', ', $labels));
		$zcFunc['db_free_result']($request);
	}
	
	return array($processed, $errors);
}

function zcBreakWords($text, $max_length, $parses_bbc = false, $allows_html = false)
{
	global $context, $zcFunc;

	// we don't break words if we parse bbc tags or if max_length is 0
	if (empty($max_length) || !empty($parses_bbc))
		return $text;
		
	$words = strpos($text, ' ') !== false ? explode(' ', $text) : array($text);
	$cleaned_words = array();
	if (!empty($words))
		foreach ($words as $k => $word)
			if (strlen($word) > $max_length)
			{
				// if it's a URL we'll let it slide....
				$temp = $word;
				// Switch out quotes really quick because they can cause problems.
				$temp = strtr($temp, array('&#039;' => '\'', '&nbsp;' => $context['utf8'] ? "\xC2\xA0" : "\xA0", '&quot;' => '>">', '"' => '<"<', '&lt;' => '<lt<'));
				
				// if it's a url... keep it as is...
				if (preg_match('~(?<=[\s>\.(;\'"]|^)((?:http|https|ftp|ftps)://[\w\-_%@:|]+(?:\.[\w\-_%]+)*(?::\d+)?(?:/[\w\-_\~%\.@,\?&;=#+:\'\\\\]*|[\(\{][\w\-_\~%\.@,\?&;=#(){}+:\'\\\\]*)*[/\w\-_\~%@\?;=#}\\\\])~i', $temp) == 1 || preg_match('~(?<=[\s>(\'<]|^)(www(?:\.[\w\-_]+)+(?::\d+)?(?:/[\w\-_\~%\.@,\?&;=#+:\'\\\\]*|[\(\{][\w\-_\~%\.@,\?&;=#(){}+:\'\\\\]*)*[/\w\-_\~%@\?;=#}\\\\])~i', $temp) == 1 || preg_match('~(?<=[\s>\.(;\'"]|^)\[url=((?:http|https|ftp|ftps)://[\w\-_%@:|]+(?:\.[\w\-_%]+)*(?::\d+)?(?:/[\w\-_\~%\.@,\?&;=#+:\'\\\\]*|[\(\{][\w\-_\~%\.@,\?&;=#(){}+:\'\\\\]*)*[/\w\-_\~%@\?;=#}\\\\])\](.*)\[/url\]~i', $temp) == 1 || preg_match('~(?<=[\s>(\'<]|^)\[url=(www(?:\.[\w\-_]+)+(?::\d+)?(?:/[\w\-_\~%\.@,\?&;=#+:\'\\\\]*|[\(\{][\w\-_\~%\.@,\?&;=#(){}+:\'\\\\]*)*[/\w\-_\~%@\?;=#}\\\\])\](.*)\[/url\]~i', $temp) == 1)
				{
					$cleaned_words[] = $word;
					continue;
				}
				unset($temp);
				
				if (!empty($word))
					$cleaned_words += str_split($word, $max_length);
			}
			else
				$cleaned_words[] = $word;
				
	if (!empty($cleaned_words))
		$text = implode(' ', $cleaned_words);
			
	return $text;
}

function zc_fatal_error($error_code = '', $show_back_link = true, $show_guest_msg = false)
{
	global $context, $txt;
	
	if (empty($context['zc']['in_zcommunity']))
		$context['zc']['sub_template'] = 'show_error';
	
	$context['blog_control_panel'] = false;
	
	// load errors lang file...
	zcLoadLanguage('Errors');
		
	zcLoadTemplate('index');
	zcLoadTemplate('Generic-index');
	
	$context['page_title'] = $txt['zc_error_0'];
	$context['zc']['error'] = empty($error_code) ? 'zc_error_5' : $error_code;
	$context['zc']['show_back_link'] = $show_back_link;
	$context['zc']['show_guest_msg'] = $show_guest_msg;
	zc_ob_exit();
}

function zcReturnToOrigin()
{
	global $blog_info, $context, $article;
	
	$from = '';
	$id = 0;
	if (isset($_REQUEST['from']) && strpos($_REQUEST['from'], ',') != false)
	{
		$num = substr_count($_REQUEST['from'], ',');
		if ($num == 1)
			list($from, $id) = explode(',', $_REQUEST['from']);
		elseif ($num == 2)
			list($from, $id, $start) = explode(',', $_REQUEST['from']);
	}
	elseif (isset($_REQUEST['from']) && in_array($_REQUEST['from'], array('community')))
		$from = $_REQUEST['from'];
		
	$_REQUEST['start'] = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;
	
	// figure out what we wanna do with 'em...
	if ($from == 'article')
		$requests = 'article=' . $id . '.' . (isset($start) ? $start : 0);
	elseif ($from == 'blog')
		$requests = 'blog=' . $id . '.' . (isset($start) ? $start : 0);
	elseif ($from == 'community')
		$requests = (!empty($context['zc']['zCommunity_is_home']) ? '' : 'zc');
	elseif (!empty($_REQUEST['article']))
		$requests = 'article=' . $article . '.' . $_REQUEST['start'];
	elseif (!empty($blog_info['id']))
		$requests = 'blog=' . $blog_info['id'] . '.' . $_REQUEST['start'];
	else
		$requests = (!empty($context['zc']['zCommunity_is_home']) ? '' : 'zc');
	
	zc_redirect_exit($requests);
}

function zcRequestVarsToString($exclude_requests = null, $first_separator = null)
{
	global $context;
	
	// we want this in array form...
	if (!empty($exclude_requests) && !is_array($exclude_requests))
		$exclude_requests = explode(',', $exclude_requests);
	elseif (empty($exclude_requests))
		$exclude_requests = array();
	
	// always exclude...
	$always_exclude = array('sesc', 'customWindows', 'moveWindow', 'tags', 'categories', 'edit', 'getErrors', 'task', 'deleteBlog', 'enable_disable', 'id', 'move', 'usersAllowedAccess', 'usersAllowedToBlog');
	
	// requests that include the .START method...
	$special_requests = array('article', 'blog');
	if (!empty($special_requests))
		foreach ($special_requests as $req)
			if (!in_array($req, $exclude_requests) && !empty($_GET[$req]))
			{
				$_REQUEST[$req] = $_GET[$req] . '.' . (isset($_REQUEST['start']) ? $_REQUEST['start'] : 0);
					
				$exclude_requests[] = 'start';
				break;
			}
	
	$string = '';
	if (!empty($_GET))
	{
		$b = false;
		foreach ($_GET as $req => $value)
		{
			if (in_array($req, $exclude_requests) || in_array($req, $always_exclude))
				continue;
		
			if (in_array($req, $special_requests))
				$value = isset($_REQUEST[$req]) ? $_REQUEST[$req] : $_GET[$req];
		
			$string .= (empty($b) ? ($first_separator !== null ? $first_separator : '') : ';') . $req . (empty($value) && $value !== '0' && $value !== 0 ? '' : '=' . $value);
		
			$b = true;
		}
	}
	
	// return the string of request variables...
	return $string;
}

function zc_make_unique_array_key($key, $arrays)
{
	// $key cannot be empty...
	if (empty($key))
		return false;

	// must be an array...
	if (!is_array($arrays))
		return false;
		
	// assume it *is* unique
	$is_unique = true;
	foreach ($arrays as $array)
		if (isset($array[$key]))
		{
			$is_unique = false;
			break;
		}
	
	// yup... it's unique already
	if ($is_unique)
		return $key;
	
	for ($i = 1; $i <= 99; $i++)
	{
		$is_unique = true;
		foreach ($arrays as $array)
			if (isset($array[$key]))
			{
				$is_unique = false;
				break 1;
			}
		
		if ($is_unique)
			return $key . $i;
	}
	
	return false;
}

function zcFormatTxtString($txt_code)
{
	global $txt;
	
	// plain $txt variable...
	if (!is_array($txt_code) && isset($txt[$txt_code]))
		return $txt[$txt_code];
	// it's not an array... but there's no txt for it...
	elseif (!is_array($txt_code))
		return $txt_code;
	// array of values means we are going to sprintf....
	elseif (is_array($txt_code) && count($txt_code) > 1)
	{
		if (isset($txt[$txt_code[0]]))
			$txt_code[0] = $txt[$txt_code[0]];
	
		// if the second part is an array itself... we'll use it as the args array in vsprintf
		if (is_array($txt_code[1]))
		{
			foreach ($txt_code[1] as $k => $v)
				if (isset($txt[$v]))
					$txt_code[1][$k] = $txt[$v];
			return vsprintf($txt_code[0], $txt_code[1]);
		}
		else
			return sprintf($txt_code[0], (!empty($txt_code[1]) ? (!empty($txt[$txt_code[1]]) ? $txt[$txt_code[1]] : $txt_code[1]) : ''), (!empty($txt_code[2]) ? (!empty($txt[$txt_code[2]]) ? $txt[$txt_code[2]] : $txt_code[2]) : ''), (!empty($txt_code[3]) ? (!empty($txt[$txt_code[3]]) ? $txt[$txt_code[3]] : $txt_code[3]) : ''), (!empty($txt_code[4]) ? (!empty($txt[$txt_code[4]]) ? $txt[$txt_code[4]] : $txt_code[4]) : ''), (!empty($txt_code[5]) ? (!empty($txt[$txt_code[5]]) ? $txt[$txt_code[5]] : $txt_code[5]) : ''));
	}
	// for some reason it's in an array with just one txt_code
	elseif (is_array($txt_code) && isset($txt_code[0]) && isset($txt[$txt_code[0]]))
		return $txt[$txt_code[0]];
	// no txt for it...
	elseif (is_array($txt_code) && isset($txt_code[0]))
		return $txt_code[0];
	// it's an array... but dunno what to do with it...
	else
		return 'Array';
}

function zcFormatTextSpecialMeanings($text)
{
	global $context, $txt;
	
	if (empty($text))
		return false;
		
	if (strpos($text, '{special:') !== false)
	{
		if (!empty($context['zc']['cp_owner']['name']))
			$text = str_replace('{special:owner_name}', $context['zc']['cp_owner']['name'], $text);
			
		$text = str_replace('{special:user_name}', (!empty($context['user']['name']) ? $context['user']['name'] : $txt['b567']), $text);
		
		$text = str_replace('{special:date}', timeformat(time()), $text);
	}
		
	return $text;
}

function zcUpdateGlobalSettings($updates)
{
	global $zcFunc;
	
	// nothing to update....
	if (empty($updates))
		return;
		
	$data = array();
	foreach ($updates as $k => $v)
		$data[] = array(
			'variable' => $k,
			'value' => $v
		);
		
	if (!empty($data))
	{
		$zcFunc['db_insert']('replace', '{db_prefix}global_settings', array('variable' => 'string', 'value' => 'string'), $data);
	
		// kill the cache
		zc_cache_put_data('zc_global_settings', null, 90);
	}
		
	return true;
}

function zcUpdateBlogSettings($updates, $blog_id)
{
	global $context, $zcFunc;
	
	// nothing to update....
	if (empty($updates) || empty($blog_id))
		return;
	
	if (!isset($context['zc']['defaultSettings']))
		$context['zc']['defaultSettings'] = zc_prepare_blog_settings_array();
		
	$where = array('blog_id' => (int) $blog_id);
	$columns = array('blog_id' => 'int');
	$data = array();
	foreach ($updates as $k => $v)
	{
		if (!isset($context['zc']['defaultSettings'][$k]))
			continue;
		
		$columns[$k] = isset($context['zc']['defaultSettings'][$k]['type']) ? $context['zc']['defaultSettings'][$k]['type'] : 'string';
		$data[$k] = $v;
	}
		
	if (!empty($data))
		$zcFunc['db_update']('{db_prefix}settings', $columns, $data, $where);
		
	return true;
}

function zcUpdateThemeSettings($updates, $blog_id = null)
{
	global $context, $zcFunc;
	
	// nothing to update....
	if (empty($updates))
		return;
		
	if (empty($blog_id))
		$blog_id = 0;
		
	if (!empty($blog_id))
	{
		$columns = array('blog_id' => 'int');
		$where = array('blog_id' => (int) $blog_id);
	}
	else
		$data = array();
		
	foreach ($updates as $k => $v)
		if (!empty($blog_id))
		{
			if (!isset($context['zc']['theme_settings_info'][$k]))
				continue;
			
			$columns[$k] = isset($context['zc']['theme_settings_info'][$k]['type']) ? $context['zc']['theme_settings_info'][$k]['type'] : 'string';
			$data[$k] = $v;
		}
		else
			$data[] = array(
				'variable' => $k,
				'value' => $v
			);
		
	if (empty($blog_id) && !empty($data))
		$zcFunc['db_insert']('replace', '{db_prefix}global_settings', array('variable' => 'string', 'value' => 'string'), $data);
	elseif (!empty($data))
		$zcFunc['db_update']('{db_prefix}theme_settings', $columns, $data, $where);
		
	return true;
}

function zcUpdatePlugInSettings($updates, $blog_id = null)
{
	global $context, $zcFunc;
	
	// nothing to update....
	if (empty($updates))
		return;
		
	if (empty($blog_id))
		$blog_id = 0;
		
	if (!empty($blog_id))
	{
		$columns = array('blog_id' => 'int');
		$where = array('blog_id' => (int) $blog_id);
	}
	else
		$data = array();
		
	foreach ($updates as $k => $v)
		if (!empty($blog_id))
		{
			if (!isset($context['zc']['plugin_settings_info'][$k]))
				continue;
			
			$columns[$k] = isset($context['zc']['plugin_settings_info'][$k]['type']) ? $context['zc']['plugin_settings_info'][$k]['type'] : 'string';
			$data[$k] = $v;
		}
		else
			$data[] = array(
				'variable' => $k,
				'value' => $v
			);
		
	if (empty($blog_id) && !empty($data))
		$zcFunc['db_insert']('replace', '{db_prefix}global_settings', array('variable' => 'string', 'value' => 'string'), $data);
	elseif (!empty($data))
		$zcFunc['db_update']('{db_prefix}plugin_settings', $columns, $data, $where);
		
	return true;
}

function zc_un_htmlspecialchars($string)
{
	if (is_array($string))
		return false;
		
	static $htmlspecialchars_translations;
	if (!isset($htmlspecialchars_translations))
		$htmlspecialchars_translations = array_flip(get_html_translation_table(HTML_SPECIALCHARS, ENT_QUOTES)) + array('&#039;' => '\'', '&nbsp;' => ' ');
	return strtr($string, $htmlspecialchars_translations);
}

function zc_htmlspecialchars($string, $quote_style = ENT_COMPAT, $charset = 'ISO-8859-1')
{
	global $zc;
	if ($zc['settings']['global_character_set'] == 'UTF-8')
		$charset = $zc['settings']['global_character_set'];
	
	if (is_array($string))
		return false;
		
	return htmlspecialchars($string, $quote_style, $charset);
}

function zc_un_htmlentities($string, $quote_style = ENT_COMPAT, $charset = 'ISO-8859-1')
{
	global $zc;
	
	if (is_array($string))
		return false;
	
	if ($zc['settings']['global_character_set'] == 'UTF-8')
	{
		if (@version_compare(PHP_VERSION, '5.0.0') != -1)
			$charset = $zc['settings']['global_character_set'];
		else
		{
			$string = utf8_decode($string);
			$charset = 'ISO-8859-1';
		}
	}
	
	static $htmlentities_translations;
	if (!isset($htmlentities_translations))
		$htmlentities_translations = array_flip(get_html_translation_table(HTML_ENTITIES, ENT_QUOTES)) + array('&#039;' => '\'', '&nbsp;' => ' ');
	return strtr($string, $htmlentities_translations);
}

function zc_htmlentities($string, $quote_style = ENT_COMPAT, $charset = 'ISO-8859-1', $double_encode = true)
{
	global $zc;
	if ($zc['settings']['global_character_set'] == 'UTF-8')
		$charset = $zc['settings']['global_character_set'];
	
	if (is_array($string))
		return false;
		
	return htmlentities($string, $quote_style, $charset);
}

function zc_htmltrim($string)
{
	return preg_replace('~^([ \t\n\r\x0B\x00]|&nbsp;)+|([ \t\n\r\x0B\x00]|&nbsp;)+$~', '', $string);
}

if (!function_exists('str_split'))
{
	function str_split($string, $split_length = 1)
	{
		$array = array();
		for ($i = 1; $i <= ceil(strlen($string) / $split_length); $i++)
			$array[] = substr($string, (($i - 1) * $split_length), $split_length);
		return $array;
	}
}

if (!function_exists('stripos'))
{
	function stripos($haystack, $needle, $offset = 0)
	{
		return strpos(strtolower($haystack), strtolower($needle), $offset);
	}
}

function zc_parse_bbc($string, $parse_smileys = true, $cache_id = '', $parse_tags = null, $disallow_tags = null)
{
	global $zc;
	
	if ($zc['with_software']['version'] == 'SMF 2.0')
		return parse_bbc($string, $parse_smileys, $cache_id, $parse_tags);
	elseif ($zc['with_software']['version'] == 'SMF 1.1.x')
		return parse_bbc($string, $parse_smileys, $cache_id);
	else
	{
		// zc's bbc parsing...
	}
	
	return $string;
}

function &zc_censor_text(&$text)
{
	global $zc;
	
	if (in_array($zc['with_software']['version'], $zc['smf_versions']))
		return censorText($text);
	else
	{
		// zcommunity text censoring code will go here...
	}
}
/*
function zc_format_time($timestamp, $show_today = true)
{
	global $zc;
}*/

/*
function zc_comma_format($number)
{
	static $decimals = null, $dec_point = null, $thousands_sep = null;
	
	return number_format($number, $decimals, $dec_point, $thousands_sep);
}

function zc_zc_redirect_exit()
{
}

function zc_log_moderation_action()
{
	
}*/

function zc_redirect_exit($url = '')
{
	global $scripturl;

	$needs_scripturl = preg_match('~^(ftp|http)[s]?://~', $url) == 0 && substr($url, 0, 6) != 'about:';

	if ($needs_scripturl)
		$url = $scripturl . ($url != '' ? '?' . $url : '');

	// add the session id to url
	if (defined('SID') && SID != '')
		$url = preg_replace('/^' . preg_quote($scripturl, '/') . '(?!\?' . preg_quote(SID, '/') . ')(\?)?/', $scripturl . '?' . SID . ';', $url);

	header('Location: ' . str_replace(' ', '%20', $url));

	zc_ob_exit(false);
}

function zCommunityCopyRight()
{
	global $txt, $context, $zc;
	
	global $modSettings;
	$context['show_load_time'] = !empty($modSettings['timeLoadPageEnable']);
	$context['load_time'] = round(array_sum(explode(' ', microtime())) - array_sum(explode(' ', $zc['time_start'])), 3);
	$context['load_queries'] = $zc['db_query_count'];
	
	$product_name = 'zCommunity';
	$owner_copyright = 'Charles Hill';
	$doz_link = 'http://www.degreesofzero.com';
	$link_title = 'Free Community Blogging Software';
	if (empty($txt['b226']))
		$txt['b226'] = 'Powered by';
	echo '<a href="' . $doz_link . '" title="' . $link_title . '">' . $txt['b226'] . ' ' . $product_name . ' ' . $zc['version'] . '</a>&nbsp;&nbsp;|&nbsp;&nbsp;zCommunity &copy; 2008-2009 ' . $owner_copyright;
}

function zc_header()
{
	global $context, $zc, $zcFunc;
	
	if ($zc['with_software']['version'] == 'SMF 2.0' && function_exists('setupThemeContext'))
		setupThemeContext();
		
	zc_load_page_context();
	
	$context['page_title_html_safe'] = !empty($context['page_title']) ? $zcFunc['htmlspecialchars']($zcFunc['un_htmlspecialchars']($context['page_title'])) : '';
		
	// load Errors lang file if we need it...
	if (!empty($context['zc']['errors']) || !empty($context['zc']['error']))
		zcLoadLanguage('Errors');
	
	// prevents caching of page...
	if (empty($context['zc']['no_last_modified']))
	{
		header('Expires: Thu, 31 Dec 1999 23:59:59 GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	}
	
	header('Content-Type: text/html; charset=' . (empty($context['character_set']) ? 'ISO-8859-1' : $context['character_set']));
	
	$do_warnings = $context['user']['is_admin'];
	if (isset($context['zc']['template_layers']))
		foreach ($context['zc']['template_layers'] as $layer => $info)
		{
			zc_load_sub_template((!empty($info['above']) ? $info['above'] : $layer . '_above'), in_array($layer, array('main', 'html', 'body')), (isset($info['prefix']) ? $info['prefix'] : 'zc_template_'));
				
			// we do warnings after the first layer
			if ($do_warnings)
			{
				zc_do_warnings();
				$do_warnings = false;
			}
		}
}

function zc_footer()
{
	global $context;
	
	if (isset($context['zc']['template_layers']))
		// gotta do this in reverse order...
		foreach (array_reverse($context['zc']['template_layers']) as $layer => $info)
			zc_load_sub_template((!empty($info['below']) ? $info['below'] : $layer . '_below'), in_array($layer, array('main', 'html', 'body')), (isset($info['prefix']) ? $info['prefix'] : 'zc_template_'));
}

function zc_do_warnings()
{
	// this function will display warnings such as files existing on the server that should've been removed...
}

function zc_ob_exit($do_header = null, $do_footer = null, $from_index = false)
{
	global $zc, $context;
	
	$do_header = $do_header === null ? empty($zc['header_done']) : $do_header;
	$do_footer = $do_footer === null ? $do_header : $do_footer;
	
	if ($do_header)
	{
		// rewrites URLs to include session ID
		ob_start('ob_sessrewrite');
		
		zc_header();
		$zc['header_done'] = true;
	}
	
	if ($do_footer)
	{
		zc_load_sub_template(isset($context['zc']['sub_template']) ? $context['zc']['sub_template'] : 'main');
		
		if (empty($zc['footer_done']))
		{
			zc_footer();
			$zc['footer_done'] = true;
		}
	}
	
	// remember the previous URL
	$_SESSION['old_url'] = $_SERVER['REQUEST_URL'];
	
	// for session checking...
	$_SESSION['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
	
	if (!$from_index || in_array($zc['with_software']['version'], $zc['smf_versions']))
		exit;
}

function zc_get_err_log_msg($txt_code, $default_text = '', $log_txt_code = null, $log_default_text = null)
{
	global $zc, $txt;
	
	if (function_exists('zcLoadLanguage'))
	{
		// first get the user's language
		zcLoadLanguage('Errors');
		$err_msg = !empty($txt[$txt_code]) ? $txt[$txt_code] : '';
		
		// now get the site's language for the error log...
		zcLoadLanguage('Errors', $zc['language']);
		$log_msg = !empty($txt[(!empty($log_txt_code) ? $log_txt_code : $txt_code)]) ? $txt[(!empty($log_txt_code) ? $log_txt_code : $txt_code)] : '';
	}
	
	if (empty($log_msg) && empty($err_msg))
	{
		$err_msg = $default_text;
		$log_msg = $err_msg;
	}
	elseif (empty($err_msg))
		$err_msg = $log_msg;
	elseif (empty($log_msg))
		$log_msg = $err_msg;
		
	return array($err_msg, $log_msg);
}

// find the position (from the beginning of $string) of the Nth occurance ($n) of $search within $string
function strpos_n($search, $string, $n)
{
	if (empty($n) || !is_numeric($n))
		return false;

	$array = explode($search, $string);
	
	if ($n > max(array_keys($array)))
		return false;
		
	return strlen(implode($search, array_slice($array, 0, $n)));
}

// find the position (from the end of $string) of the Nth occurance ($n) of $search within $string
function strrpos_n($search, $string, $n)
{
	if (empty($n) || !is_numeric($n))
		return false;

	$array = explode($search, $string);
	
	if ($n > max(array_keys($array)))
		return false;
		
	return strlen(implode($search, array_slice(array_reverse($array), 0, $n)));
}

?>