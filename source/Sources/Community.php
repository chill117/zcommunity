<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
/*
	zcCommunityPage()
		- Determines what to display the user on the community page
		- Builds $context['zc']['blogs'] or $context['zc']['list_of_articles'] arrays for use in the template
	
	NOTES:
	 ----
	- $context['zc']['blogs'] is used to display a list of blogs
	- $context['zc']['list_of_articles'] is used to display a list of articles
	- REQUEST variables:
		> category is for displaying a list of articles in a specific category
		> date is for displaying a list of articles from a date (year,month,day)
		> tag is for displaying a list of articles associated with a specific tag
*/

function zcCommunityPage()
{
	global $context, $txt, $scripturl, $zcFunc, $zc;
		
	zcLoadTemplate('Community');
	
	$context['page_title'] = $context['zc']['site_name'] . ' - ' . $txt['b213'];
	$context['zc']['viewing_community_page'] = true;
	$context['zc']['main_blocks'] = array();
	$context['zc']['visible_blogs'] = zc_get_visible_blogs();
	$context['zc']['num_blogs'] = count($context['zc']['visible_blogs']);
	
	if (!empty($zc['settings']['community_page_meta_keywords']))
		$context['zc']['meta']['keywords'] = strip_tags($zcFunc['un_htmlspecialchars']($zc['settings']['community_page_meta_keywords']));
	if (!empty($zc['settings']['community_page_meta_description']))
		$context['zc']['meta']['description'] = strip_tags($zcFunc['un_htmlspecialchars']($zc['settings']['community_page_meta_description']));
	
	// we need this file...
	require_once($zc['sources_dir'] . '/Subs-Articles.php');
		
	// try to load a list of articles...
	if (($array = zcGetListOfArticles()) !== false)
	{
		zcLoadTemplate('Blog');
		list($context['zc']['list_of_articles'], $context['zc']['list1']) = $array;
	}
	// ok that didn't work.... let's load the main blocks then...
	else
		zcLoadMainBlocks();

	// load community page side windows
	$context['zc']['side_windows'] = array();
	if (!empty($zc['settings']['community_page_side_bar']))
		$context['zc']['side_windows'] = zcLoadCommunityPageSideWindows();
	
	// plug-in slot #10
	zc_plugin_slot(10);
	
	// first see if we want to show the blog index side bar at all
	if (!empty($zc['settings']['community_page_side_bar']) && !empty($context['zc']['side_windows']))
	{
		// show it on the left?
		if ($zc['settings']['community_page_side_bar'] == 1)
			$context['zc']['side_bar_left'] = true;
		// show it on the right?
		elseif ($zc['settings']['community_page_side_bar'] == 2)
			$context['zc']['side_bar_right'] = true;
			
		if (!empty($context['zc']['side_bar_left']))
			$context['zc']['main_guts_width'] = $context['zc']['main_guts_width'] - $context['zc']['left_side_bar_width'];
			
		if (!empty($context['zc']['side_bar_right']))
			$context['zc']['main_guts_width'] = $context['zc']['main_guts_width'] - $context['zc']['right_side_bar_width'];
	}
			
	if (!empty($zc['settings']['community_page_without_layers']) || !empty($zc['settings']['toggle_all_layerless']))
		$context['zc']['layerless'] = true;
}

function zcLoadMainBlocks()
{
	global $context, $txt, $scripturl, $zcFunc, $zc;
	
	// community news block...
	$context['zc']['news'] = zcGetNewsArticles();
	
	// populate community news block....
	if (isset($context['zc']['news']))
		$context['zc']['main_blocks'][] = array(
			'type' => 'news',
			'content' => $context['zc']['news'],
			'enabled' => $zc['settings']['enable_news_block'],
			'exclude_templates' => 'middle',
			'title' => $txt['b338'],
			'page_index' => !empty($context['zc']['list2']['page_index']) ? $context['zc']['list2']['page_index'] : '',
		);
	
	// the blog index block...
	$context['zc']['blogs'] = array();
	$context['zc']['list1']['list_empty_txt'] = $txt['b3042'];
	$context['zc']['list1']['no_form'] = true;
	$context['zc']['list1']['checkbox_name'] = 'blogs';
	
	// only do this stuff if there are blogs this user can view....
	if (!empty($context['zc']['visible_blogs']) && !empty($zc['settings']['enable_blog_index_block']))
	{
		require_once($zc['sources_dir'] . '/Subs-Blogs.php');
	
		if (($array = zc_get_list_of_blogs($context['zc']['visible_blogs'])) != false)
			list($blogs, $context['zc']['list1']) = $array;
	}
	
	// populate blog index block....
	if (isset($context['zc']['blogs']))
		$context['zc']['main_blocks'][] = array(
			'type' => 'blog_index',
			'content' => isset($blogs) ? $blogs : array(),
			'enabled' => $zc['settings']['enable_blog_index_block'],
			'exclude_templates' => 'above,below',
		);
		
	// add option for posting community news...
	if ($context['can_post_community_news'] && !empty($zc['settings']['enable_news_block']))
		$context['zc']['extra_above_side_windows']['options']['links'][] = '<a href="' . $scripturl . '?zc=post;article">'. $txt['b342'] .'</a>';
}

function zcLoadCommunityPageSideWindows()
{
	global $txt, $context, $scripturl, $zcFunc, $zc;
	
	$return = array();
	zc_prepare_side_window_arrays();
	
	$windowOrders = array();
	if (!empty($context['zc']['side_windows']))
		foreach ($context['zc']['side_windows'] as $win_order => $array)
			$windowOrders[$array['type']] = $win_order;

	// All Custom Windows
	if (!empty($context['zc']['custom_windows']))
		foreach ($context['zc']['custom_windows'] as $window)
			if (!empty($window['id']) && !empty($window['win_order']) && !empty($window['enabled']))
			{
				$return[$window['win_order']] = array(
					'content' => empty($window['content_type']) ? $zcFunc['parse_bbc']($window['content']) : $zcFunc['un_htmlspecialchars']($window['content']),
					'is_php' => !empty($window['content_type']) && $window['content_type'] == 2,
					'title' => $window['title'],
					'type' => 'custom',
				);
				$windowOrders[$window['var_name']] = $window['win_order'];
				
				if (!isset($return_not_empty) && !empty($window['content']))
					$return_not_empty = true;
			}
	
	// global most recent window
	if (!empty($zc['settings']['enableGlobalMostRecentWindow']) && !empty($context['zc']['visible_blogs']))
	{
		$return[$windowOrders['globalMostRecent']] = array();
		$return[$windowOrders['globalMostRecent']]['title'] = $txt['b503'];
		$return[$windowOrders['globalMostRecent']]['type'] = 'list';
		$request = $zcFunc['db_query']("
			SELECT t.subject, t.article_id
			FROM {db_prefix}articles AS t
				LEFT JOIN {db_prefix}settings AS bs ON (bs.blog_id = t.blog_id)
			WHERE t.blog_id IN ({array_int:visible_blogs})
				AND ((t.approved = 1) OR (bs.articles_require_approval = 0))
			ORDER BY t.posted_time DESC
			LIMIT 5", __FILE__, __LINE__,
			array(
				'visible_blogs' => $context['zc']['visible_blogs']
			)
		);
		$return[$windowOrders['globalMostRecent']]['content'] = array();
		while ($row = $zcFunc['db_fetch_assoc']($request))
		{
			$row['subject'] = strip_tags($zcFunc['un_htmlspecialchars']($row['subject']));
			zc_censor_text($row['subject']);
			$return[$windowOrders['globalMostRecent']]['content'][] = '<a href="' . $scripturl . '?article='. $row['article_id'] .'.0">'. $row['subject'] .'</a>';
		}
			
		if (empty($return[$windowOrders['globalMostRecent']]['content']))
			$return[$windowOrders['globalMostRecent']] = array();
		else
			$return_not_empty = true;
			
		$zcFunc['db_free_result']($request);
	}
	
	// global tags window
	if (!empty($zc['settings']['enableGlobalTagsWindow']) && !empty($context['zc']['visible_blogs']) && !empty($windowOrders['globalTags']))
	{
		$return[$windowOrders['globalTags']] = array();
		$return[$windowOrders['globalTags']]['title'] = $txt['b26a'];
		$return[$windowOrders['globalTags']]['type'] = 'custom';
		list($context['zc']['tags'], $total_instances) = zcLoadBlogTags($context['zc']['visible_blogs']);
			
		if (!empty($context['zc']['tags']))
		{
			$return[$windowOrders['globalTags']]['content'] = array();
			foreach ($context['zc']['tags'] as $tag)
				$return[$windowOrders['globalTags']]['content'][] = '<a href="' . $scripturl . '?zc;tag=' . urlencode($tag['tag']) . '" title="' . $tag['num_articles'] . ($tag['num_articles'] == 1 ? ' Article' : ' Articles') . '" style="font-size:' . zcTagFontSize($tag['num_articles'], $total_instances) . 'px;">' . $tag['tag'] . '</a>';
		}
		
		if (!empty($return[$windowOrders['globalTags']]['content']))
			$return[$windowOrders['globalTags']]['content'] = implode(', ', $return[$windowOrders['globalTags']]['content']);
		
		if (empty($return[$windowOrders['globalTags']]['content']))
			$return[$windowOrders['globalTags']] = array();
		else
			$return_not_empty = true;
	}
	
	// global archives window...
	if (!empty($zc['settings']['enableGlobalArchivesWindow']) && !empty($context['zc']['visible_blogs']) && !empty($windowOrders['globalArchives']))
	{
		// this is the most efficient way of gathering all the month, year pairs and num_articles for each
		$request = $zcFunc['db_query']("
			SELECT t.month, t.year, COUNT(t.article_id) as num_articles
			FROM {db_prefix}articles AS t
				LEFT JOIN {db_prefix}settings AS bs ON (bs.blog_id = t.blog_id)
			WHERE t.blog_id IN ({array_int:visible_blogs})
				AND t.month != {empty_string}
				AND t.year != {empty_string}
				AND ((t.approved = 1) OR (bs.articles_require_approval = 0))
			GROUP BY t.month, t.year
			ORDER BY t.year DESC, t.month DESC", __FILE__, __LINE__,
			array(
				'visible_blogs' => $context['zc']['visible_blogs']
			)
		);
				
		$articles_exist = array();
		while ($row = $zcFunc['db_fetch_assoc']($request))
			$articles_exist[] = array(
				'num_articles' => $row['num_articles'],
				'month' => $row['month'],
				'year' => $row['year'],
			);
		$zcFunc['db_free_result']($request);
		
		$return[$windowOrders['globalArchives']] = array();
		$return[$windowOrders['globalArchives']]['content'] = array();
		$return[$windowOrders['globalArchives']]['type'] = 'list';
		
		if (!empty($articles_exist))
			foreach ($articles_exist as $archive)
				$return[$windowOrders['globalArchives']]['content'][] = '<a href="' . $scripturl . '?' . (empty($context['zc']['zCommunity_is_home']) ? 'zc;' : '') . 'date=' . $archive['year'] . '_' . $archive['month'] . '">' . $txt['months_titles'][$archive['month']] . ' ' . $archive['year'] . ' ' . '(' . $archive['num_articles'] . ')</a>';
		
		if (!empty($return[$windowOrders['globalArchives']]['content']))
			$return[$windowOrders['globalArchives']]['title'] = $txt['b22'];
			
		if (empty($return[$windowOrders['globalArchives']]['content']))
			$return[$windowOrders['globalArchives']] = array();
		else
			$return_not_empty = true;
	}
	
	$context['zc']['max_window_order'] = max($windowOrders);
	
	if (empty($return) || empty($return_not_empty))
		$return = array();
		
	return $return;
}

function zcGetNewsArticles()
{
	global $context, $scripturl, $txt;
	global $zc, $zcFunc;
	
	// get number of news articles...
	$request = $zcFunc['db_query']("
		SELECT COUNT(article_id) AS num_articles
		FROM {db_prefix}articles
		WHERE blog_id = 0", __FILE__, __LINE__);
	list($num_articles) = $zcFunc['db_fetch_row']($request);
	$zcFunc['db_free_result']($request);
	
	$maxindex = min($num_articles, $zc['settings']['news_block_max_num']);
	$start = isset($_REQUEST['listStart2']) ? (int) $_REQUEST['listStart2'] : 0;
	$context['zc']['list2']['show_page_index'] = !empty($num_articles) && $num_articles > $maxindex;
	
	// don't index when viewing alternate "pages" of community news...
	if ($start > 0)
		$context['robot_no_index'] = true;
	
	if ($context['zc']['list2']['show_page_index'])
		$context['zc']['list2']['page_index'] = zcConstructPageIndex($scripturl . '?listStart2=%d' . zcRequestVarsToString('listStart2,all2', ';'), $start, $num_articles, $maxindex, true);

	// retrieve some news articles....
	$request = $zcFunc['db_query']("
		SELECT t.article_id, t.subject, t.body, t.smileys_enabled, t.locked, t.poster_id, t.posted_time, IFNULL(mem.{tbl:members::column:real_name}, t.poster_name) AS poster_name, t.last_edit_time, t.last_edit_name, t.num_comments, t.blog_tags
		FROM {db_prefix}articles AS t
			LEFT JOIN {db_prefix}{table:members} AS mem ON (mem.{tbl:members::column:id_member} = t.poster_id)
		WHERE t.blog_id = 0
		ORDER BY t.posted_time DESC
		LIMIT {int:start}, {int:maxindex}", __FILE__, __LINE__,
		array(
			'start' => $start,
			'maxindex' => $maxindex
		)
	);
		
	$articles = array();
	$news = array();
	while ($row = $zcFunc['db_fetch_assoc']($request))
	{
		$articles[] = $row['article_id'];
		$row['body'] = $zcFunc['un_htmlspecialchars']($row['body']);
		$row['subject'] = $zcFunc['un_htmlspecialchars']($row['subject']);
		$row['body'] = $zcFunc['parse_bbc']($row['body'], $row['smileys_enabled']);
		zc_censor_text($row['body']);
		zc_censor_text($row['subject']);
		
		$news[$row['article_id']] = array(
			'id' => $row['article_id'],
			'body' => zcTruncateText($row['body'], $zc['settings']['news_block_max_length'], ' ', 40, $txt['b31a'], $scripturl . '?article='. $row['article_id'] .'.0', $txt['b31']),
			'subject' => $row['subject'],
			'locked' => !empty($row['locked']),
			'view_comments_link' => !empty($row['num_comments']) ? '<a href="' . $scripturl . '?article='. $row['article_id'] .'.0#comments' . $row['article_id'] . '" rel="nofollow">'. $txt['b476'] .'</a>' : '',
			'num_comments' => $row['num_comments'],
			'new_comment' => '<a href="' . $scripturl . '?zc=post;article='. $row['article_id'] . '.0;comment' . ';from=' . (!empty($article) ? 'article,' . $article . ',' . $_REQUEST['start'] : (!empty($blog) ? 'blog,' . $blog . ',' . $_REQUEST['start'] : 'community')) .'">'. $txt['b223'] .'</a>',
			'time' => timeformat($row['posted_time'], false),
			'link' => '<a href="' . $scripturl . '?article=' . $row['article_id'] .'.0"'. (!empty($article) && $article == $row['article_id'] ? ' rel="nofollow"' : '') .'>' . $row['subject'] . '</a>',
			'poster' => array(
				'link' => !empty($row['poster_id']) ? sprintf($context['zc']['link_templates']['user_profile'], $row['poster_id'], $row['poster_name'], ' title="' . $txt['b41'] . '"') : $row['poster_name'],
			),
			'modified' => array(
				'time' => timeformat($row['last_edit_time'], false),
				'name' => !empty($zc['settings']['news_block_show_last_edit']) ? $row['last_edit_name'] : '',
			),
			'options' => array(),
			'bookmarking_links' => array(),
			'can_edit' => $context['can_edit_community_news'],
			'can_delete' => $context['can_delete_community_news'],
			'can_lock' => $context['can_lock_community_news'],
			'can_reply' => $context['can_comment_on_news'] && empty($row['locked']),
		);
		
		if ($context['can_edit_community_news'])
			$news[$row['article_id']]['options']['edit'] = '<a href="' . $scripturl . '?zc=post;article='. $row['article_id'] .'.0;from=' . (!empty($article) ? 'article,' . $article . ',' . $_REQUEST['start'] : (!empty($blog) ? 'blog,' . $blog . ',' . $_REQUEST['start'] : 'community')) . '" rel="nofollow" title="'. $txt['b47'] .'"><span class="edit_icon">&nbsp;</span></a>';
		
		if ($context['can_delete_community_news'])
			$news[$row['article_id']]['options']['delete'] = '<a href="' . $scripturl . '?zc=deletearticle;article='. $row['article_id'] .'.0;sesc='. $context['session_id'] . ';from=' . (!empty($article) ? 'article,' . $article . ',' . $_REQUEST['start'] : (!empty($blog) ? 'blog,' . $blog . ',' . $_REQUEST['start'] : 'community')) .'" onclick="return confirm(\''. $txt['b45'] .'\');" rel="nofollow" title="'. $txt['b49'] .'"><span class="delete_icon">&nbsp;</span></a>';
		
		if ($context['can_lock_community_news'])
			$news[$row['article_id']]['options']['lock'] = '<a href="' . $scripturl . '?zc=lockarticle;article=' . $row['article_id'] . '.0;sesc='. $context['session_id'] . ';from=' . (!empty($article) ? 'article,' . $article . ',' . $_REQUEST['start'] : (!empty($blog) ? 'blog,' . $blog . ',' . $_REQUEST['start'] : 'community')) .'" rel="nofollow" title="'. (empty($row['locked']) ? $txt['b55'] : $txt['b56']) .'"><span class="lock_icon">&nbsp;</span></a>';
		
		// bookmarking links....
		if (!empty($zc['settings']['show_socialbookmarks_news']))
		{
			// urlencode the subject and url...
			$subject = urlencode(strip_tags($row['subject']));
			$url =  urlencode($scripturl . '?article=' . $row['article_id'] .'.0');
			$article_tags = urlencode(strip_tags($row['blog_tags']));
			
			zc_prepare_bookmarking_options_array();
			
			// load the bookmarking options...
			if (!empty($zc['settings']['news_socialbookmarks_multicheck']))
				foreach ($zc['settings']['news_socialbookmarks_multicheck'] as $site)
					if (!empty($context['zc']['bookmarking_options'][$site]['href']))
					{
						$href = strtr($context['zc']['bookmarking_options'][$site]['href'], array('$1' => $url, '$2' => $subject, '$3' => $article_tags));
						$news[$row['article_id']]['bookmarking_links'][] = '<a href="' . $href . '" title="' . $context['zc']['bookmarking_options'][$site]['name'] . '" target="_blank" rel="nofollow">' . $context['zc']['bookmarking_options'][$site]['icon'] . '</a>';
					}
		}
	}
	$zcFunc['db_free_result']($request);
	
	// we will need to mark read/notify for the articles we're viewing here...
	if (!empty($articles))
	{
		$max_cache = 5; // maximum number of blog articles to keep in the last_read cache at a time
		$time_limit_cache = 600; // maximum time (in seconds) to keep articles in the last_read cache
		$last_read = array();
		
		if (!isset($_SESSION['zc_last_read_articles']))
			$_SESSION['zc_last_read_articles'] = '';
				
		if (!empty($_SESSION['zc_last_read_articles']))
		{
			// the data held in $_SESSION['zc_last_read_articles'] looks like this...
			// 		ID.TIMESTAMP,ID.TIMESTAMP,ID.TIMESTAMP
			$temp = explode(',', $_SESSION['zc_last_read_articles']);
			$last_read = array();
			$cached_articles = array();
			foreach ($temp as $k => $stuff)
			{
				$last_read[$k] = explode('.', $stuff);
				
				// clear cached articles if they are older than $time_limit_cache
				if ((time() - $last_read[$k][1]) > $time_limit_cache)
					unset($last_read[$k]);
				else
					$cached_articles[] = $last_read[$k][0];
			}
			unset($temp);
		}
			
		$context['zc']['is_marked_notify']['articles'] = array();
		
		// also mark read/notify for non-guests
		foreach ($articles as $article_id)
			// Default each article to not marked for notifications... of course...
			$context['zc']['is_marked_notify']['articles'][$article_id] = false;
			
		$uncached_articles = !empty($cached_articles) ? array_diff($articles, $cached_articles) : $articles;
			
		// are there uncached articles?
		if (!empty($uncached_articles))
		{
			// give each uncached article +1 view
			$zcFunc['db_update'](
				'{db_prefix}articles',
				array('article_id' => 'int', 'num_views' => 'int'),
				array('num_views' => array('+', 1)),
				array('article_id' => array('IN', $uncached_articles)),
				count($uncached_articles));
				
			// add the uncached articles to the last_read array
			foreach ($uncached_articles as $id)
				$last_read[] = array($id , time());
		}
		
		// redo the last_read cache...
		$_SESSION['zc_last_read_articles'] = '';
		$last_read = array_slice($last_read, (-1) * $max_cache);
		foreach ($last_read as $cached_article)
			$_SESSION['zc_last_read_articles'] .= (!empty($_SESSION['zc_last_read_articles']) ? ',' : '') . implode('.', $cached_article);
		unset($last_read);
			
		// Guests can't mark articles read or mark notify
		if (!$context['user']['is_guest'])
		{
			// make the rows to insert into the blog_log_articles table
			$data = array();
			$columns = array('comment_id' => 'int', 'member_id' => 'int', 'article_id' => 'int');
			foreach ($articles as $article_id)
				$data[] = array('comment_id' => $zc['settings']['max_comment_id'], 'member_id' => $context['user']['id'], 'article_id' => $article_id);
					
			if (!empty($data))
				$zcFunc['db_insert']('replace', '{db_prefix}log_articles', $columns, $data);
		
			// Check for notifications on this article OR blog.
			$request = $zcFunc['db_query']("
				SELECT sent, article_id
				FROM {db_prefix}log_notify
				WHERE article_id IN ({array_int:articles})
					AND member_id = {int:user_id}
				LIMIT {int:limit}", __FILE__, __LINE__,
				array(
					'limit' => count($articles),
					'articles' => $articles,
					'user_id' => $context['user']['id']
				)
			);
			$do_once = true;
			while ($row = $zcFunc['db_fetch_assoc']($request))
			{
				// Find if this article is marked for notification...
				$context['zc']['is_marked_notify']['articles'][$row['article_id']] = !empty($row['article_id']);
	
				// Only do this once, but mark the notifications as "not sent yet" for next time.
				if (!empty($row['sent']) && $do_once)
				{
					$zcFunc['db_update'](
						'{db_prefix}log_notify',
						array('article_id' => 'int', 'member_id' => 'int', 'sent' => 'int'),
						array('sent' => 0),
						array('article_id' => array('IN', $articles), 'member_id' => $context['user']['id']),
						count($articles));
					$do_once = false;
				}
			}
		}
	}
	
	return $news;
}

?>