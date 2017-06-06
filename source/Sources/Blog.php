<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
/*
	void zcBlog()
		- Determines what to display the user on the blog page
		- Builds $context['zc']['articles'] or $context['zc']['list_of_articles'] arrays for use in the template
	
	NOTES:
	 ----
	- $context['zc']['articles'] is used for showing detailed information about a single article or several articles
	- $context['zc']['list_of_articles'] is used to display a list of articles from a category or a month
	- REQUEST variables:
		> article is for displaying a single blog article
		> category is for displaying a list of articles in a specific category
		> date is for displaying a list of articles from a date (year,month,day)
		> tag is for displaying a list of articles associated with a specific tag
		> blog is for viewing a specific blog
*/
	
function zcBlog()
{
	global $txt, $scripturl, $context, $blog, $blog_info, $article, $zc, $zcFunc, $in_context;
	
	zcLoadTemplate('Blog');
	
	// if this is true they can see all users' IP addresses - like on comments and stuff (for moderation purposes)
	$context['can_moderate_site'] = !$zc['with_software']['version'] ? $context['user']['is_admin'] : $zcFunc['forum_permission_check']('moderate_forum');
	
	// settings are different depending upon what we want to view...
	if (!empty($blog))
		$in_context = array(
			'show_go_to_top' => !empty($context['zc']['blog_settings']['show_go_to_top']),
			'show_tags' => !empty($context['zc']['blog_settings']['show_tags']),
			'show_categories' => !empty($context['zc']['blog_settings']['show_categories']),
			'display_comments' => empty($context['zc']['blog_settings']['hide_comments']) && (!empty($context['zc']['blog_settings']['display_comments_blog']) || !empty($article)),
			'socialbookmarks' => array(
				'show' => !empty($context['zc']['blog_settings']['show_socialbookmarks_articles']),
				'sites' => $context['zc']['blog_settings']['socialbookmarks_multicheck'],
			),
		);
	// if $blog is empty... we're viewing community news
	else
		$in_context = array(
			'show_go_to_top' => false,
			'show_tags' => false,
			'show_categories' => false,
			'display_comments' => true,
			'socialbookmarks' => array(
				'show' => !empty($zc['settings']['show_socialbookmarks_news']),
				'sites' => $zc['settings']['news_socialbookmarks_multicheck'],
			),
		);
	
	// community news..... some stats...
	if (empty($blog))
	{
		$blog_info['num_articles'] = $zc['settings']['community_news_num_articles'];
		$blog_info['num_comments'] = $zc['settings']['community_news_num_comments'];
	}
	
	// meta for you...
	if (!empty($blog))
	{
		$context['zc']['meta']['keywords'] = strip_tags($zcFunc['un_htmlspecialchars']($context['zc']['blog_settings']['meta_keywords']));
		$context['zc']['meta']['description'] = !empty($blog_info['description']) ? zcTruncateText(strip_tags($zcFunc['un_htmlspecialchars']($blog_info['description'])), 60, ' ', 10, '') : '';
	}
	else
	{
		$context['zc']['meta']['keywords'] = strip_tags($zcFunc['un_htmlspecialchars']($zc['settings']['community_page_meta_keywords']));
		$context['zc']['meta']['description'] = strip_tags($zcFunc['un_htmlspecialchars']($zc['settings']['community_page_meta_description']));
	}
	
	$context['page_title'] = !empty($blog_info['name']) ? strip_tags($zcFunc['un_htmlspecialchars']($blog_info['name'])) : $txt['b338'];
	
	// Find the previous or next article.  Make a fuss if there are no more.
	if (!empty($article) && isset($_REQUEST['prev_next']) && in_array($_REQUEST['prev_next'], array('prev', 'next')))
	{
		// No use in calculating the next article if there's only one.
		if ($blog_info['num_articles'] > 1)
		{
			// Just prepare some variables that are used in the query.
			$gt_lt = $_REQUEST['prev_next'] == 'next' ? '>' : '<';
			$order = $_REQUEST['prev_next'] == 'next' ? '' : ' DESC';
			
			// make part of query for access restrictions (access_restrict / approved) for articles...
			$access_restrict_query = '';
			$info = array();
			if (!$context['user']['is_admin'])
			{
				$access_restrict_query .= "
					AND ((t2.access_restrict = 0)";
					 
				if (!$context['user']['is_guest'])
				{
					$info += array('user_id0' => '%,' . $context['user']['id'] . ',%', 'user_id1' => $context['user']['id'] . ',%', 'user_id2' => '%,' . $context['user']['id'], 'user_id3' => $context['user']['id'], 'user_id4' => '% ' . $context['user']['id'] . ',%', 'user_id5' => '% ' . $context['user']['id'] . ' %', 'user_id6' => '%,' . $context['user']['id'] . ' %');
					$access_restrict_query .= '
						OR (t2.poster_id = {int:user_id})
						OR ((t2.access_restrict = 2) AND (((mem.{tbl:members::column:buddy_list} LIKE {string:user_id0}) OR (mem.{tbl:members::column:buddy_list} LIKE {string:user_id1}) OR (mem.{tbl:members::column:buddy_list} LIKE {string:user_id2}) OR (mem.{tbl:members::column:buddy_list} = {string:user_id3})) AND (mem.{tbl:members::column:buddy_list} NOT LIKE {string:user_id4}) AND (mem.{tbl:members::column:buddy_list} NOT LIKE {string:user_id5}) AND (mem.{tbl:members::column:buddy_list} NOT LIKE {string:user_id6})))';
				}
						
				$access_restrict_query .= ")";
						
				if (!empty($context['zc']['blog_settings']['articles_require_approval']) && !$context['can_approve_articles_in_any_b'] && !$context['can_moderate_blog'])
					$access_restrict_query .= "
					AND ((t2.approved = 1)" . (!$context['user']['is_guest'] ? " OR (t2.poster_id = {int:user_id})" : '') . ")";
					
				if (!$context['user']['is_guest'] && !$context['can_approve_articles_in_any_b'] && !$context['can_moderate_blog'])
					$info['user_id'] = $context['user']['id'];
			}
			
			$left_joins = '';
			if (!$context['user']['is_admin'] && !$context['user']['is_guest'])
				$left_joins = "
					LEFT JOIN {db_prefix}{table:members} AS mem ON (mem.{tbl:members::column:id_member} = t2.poster_id)";
	
			$request = $zcFunc['db_query']("
				SELECT t2.article_id
				FROM ({db_prefix}articles AS t, {db_prefix}articles AS t2)" . $left_joins . "
				WHERE t.article_id = {int:current_article}
					AND t2.article_id $gt_lt t.article_id
					AND t2.blog_id = {int:current_blog}" . $access_restrict_query . "
				ORDER BY t2.article_id$order
				LIMIT 1", __FILE__, __LINE__,
				array(
					'current_article' => $article,
					'current_blog' => $blog
				)
			);
	
			// No more left.
			if ($zcFunc['db_num_rows']($request) == 0)
			{
				$zcFunc['db_free_result']($request);
	
				// Roll over - if we're going prev, get the last - otherwise the first.
				$request = $zcFunc['db_query']("
					SELECT article_id
					FROM {db_prefix}articles" . str_replace('t2.', '', $left_joins) . "
					WHERE blog_id = {int:current_blog}" . str_replace('t2.', '', $access_restrict_query) . "
					ORDER BY article_id$order
					LIMIT 1", __FILE__, __LINE__,
					array(
						'current_blog' => $blog
					)
				);
			}
			$row = $zcFunc['db_fetch_assoc']($request);
			// Now you can be sure $article is the article_id to view.
			$article = !empty($row['article_id']) ? $row['article_id'] : $article;
			$zcFunc['db_free_result']($request);
		}
		// Duplicate link!  Tell the robots not to link this.
		$context['robot_no_index'] = true;
	}
	
	// make sure this is after the prev_next stuff
	$context['current_article'] = $article;
	
	// redo $context['zc']['article_request']
	$context['zc']['article_request'] = !empty($article) ? ';article=' . $article . '.' . $_REQUEST['start'] : '';
	
	// previous and next links
	if (!empty($article))
	{
		$context['zc']['page_relative_links']['next'] = array('url' => $scripturl . '?article=' . $article . '.0;prev_next=next');
		$context['zc']['page_relative_links']['prev'] = array('url' => $scripturl . '?article=' . $article . '.0;prev_next=prev');
		$context['zc']['next_link'] = '<a href="' . $scripturl . '?article='. $article .'.0;prev_next=next" title="'. $txt['b61'] .'">' . $txt['b3048'] . '&nbsp;&raquo;</a>';
		$context['zc']['previous_link'] = '<a href="' . $scripturl . '?article='. $article .'.0;prev_next=prev" title="'. $txt['b60'] .'">&laquo;&nbsp;' . $txt['b3049'] . '</a>';
	}
		
	// go up link
	if (!empty($in_context['show_go_to_top']))
		$context['zc']['go_top_link'] = '<a href="#top" title="'. $txt['b62'] .'"><img src="'. $context['zc']['default_images_url'] .'/arrow_up.gif" alt="'. $txt['b62'] .'" /></a>';
		
	// only load tags if we need 'em
	if (!empty($in_context['show_tags']) || (!empty($context['zc']['blog_settings']['enableTagsWindow']) && empty($context['zc']['blog_settings']['hide_side_windows'])))
		list($context['zc']['tags'], $context['total_tag_instances']) = zcLoadBlogTags($blog);
	
	// only load categories if we need 'em
	if (!empty($in_context['show_categories']) || (!empty($context['zc']['blog_settings']['enableCategoryList']) && empty($context['zc']['blog_settings']['hide_side_windows'])))
		$context['zc']['categories'] = zcLoadBlogCategories();
		
	if (!empty($blog))
	{
		$context['zc']['is_marked_notify'] = array();
		$context['zc']['is_marked_notify']['blogs'] = array();
		// check if the user viewing has notify enabled for this blog
		if (!$context['user']['is_guest'])
		{
			$request = $zcFunc['db_query']("
				SELECT sent
				FROM {db_prefix}log_notify
				WHERE blog_id = {int:current_blog}
					AND member_id = {int:user_id}
				LIMIT 1", __FILE__, __LINE__,
				array(
					'user_id' => $context['user']['id'],
					'current_blog' => $blog
				)
			);
			$context['zc']['is_marked_notify']['blogs'][$blog] = $zcFunc['db_num_rows']($request) != 0;
			if ($context['zc']['is_marked_notify']['blogs'][$blog])
			{
				list ($sent) = $zcFunc['db_fetch_row']($request);
				if (!empty($sent))
					$zcFunc['db_update'](
						'{db_prefix}log_notify',
						array('blog_id' => 'int', 'member_id' => 'int', 'sent' => 'int'),
						array('sent' => 0),
						array('blog_id' => $blog, 'member_id' => $context['user']['id']));
			}
			$zcFunc['db_free_result']($request);
		}
		// for guets this is always false...
		else
			$context['zc']['is_marked_notify']['blogs'][$blog] = false;
	
		// Add 1 to the number of views this blog has
		if (empty($_SESSION['zc_last_read_blog']) || $blog != $_SESSION['zc_last_read_blog'])
		{
			$zcFunc['db_update'](
				'{db_prefix}blogs',
				array('blog_id' => 'int', 'num_views' => 'int'),
				array('num_views' => array('+', 1)),
				array('blog_id' => $blog));
	
			$_SESSION['zc_last_read_blog'] = $blog;
			$blog_info['num_views'] = !empty($blog_info['num_views']) ? $blog_info['num_views'] + 1 : 1;
		}
		
		$context['zc']['side_windows'] = array();
		if (empty($context['zc']['blog_settings']['hide_side_windows']))
			$context['zc']['side_windows'] = zcLoadSideWindows();
	}
	
	// start with this as false
	$context['viewing_single_article'] = false;
	if (!empty($article))
	{
		$context['viewing_single_article'] = true;
		$context['zc']['articles'] = zc_get_articles();
	
		// if $context['zc']['articles'] was populated with stuff... keep it... otherwise use zc_get_articles()
		if (!empty($context['zc']['articles']))
		{
			// add the article link to the blog link tree
			$context['zc']['link_tree']['article'] = '<a href="' . $scripturl . '?article=' . $article . '.0" rel="nofollow">' . $context['zc']['articles'][$article]['subject'] . '</a>';
		}
		// failed to load a single article and we wanted to... redirect to this blog's main page
		else
			zc_redirect_exit('blog=' . $blog . '.' . $_REQUEST['start']);
	}
	
	// maybe we should try to load a list of articles?
	if (!empty($blog) && empty($context['zc']['articles']))
	{
		// we need this file...
		require_once($zc['sources_dir'] . '/Subs-Articles.php');
		
		if (($array = zcGetListOfArticles()) !== false)
			list($context['zc']['list_of_articles'], $context['zc']['list1']) = $array;
		// ok then... let's try to get any recent whole articles...
		else
			$context['zc']['articles'] = zc_get_articles();
	}
	
	// if we managed to retrieve some whole articles, we should do some stuff...
	if (!empty($context['zc']['articles']))
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
		$articles = array();
		foreach ($context['zc']['articles'] as $art)
		{
			$articles[] = $art['id'];
			
			// Default each article to not marked for notifications... of course...
			$context['zc']['is_marked_notify']['articles'][$art['id']] = false;
		}
			
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
			if (!empty($articles))
			{
				$data = array();
				$columns = array('comment_id' => 'int', 'member_id' => 'int', 'article_id' => 'int');
				foreach ($articles as $article_id)
					$data[] = array(
						'comment_id' => $zc['settings']['max_comment_id'],
						'member_id' => $context['user']['id'],
						'article_id' => $article_id
					);
						
				if (!empty($data))
					$zcFunc['db_insert']('replace', '{db_prefix}log_articles', $columns, $data);
			
				// Check for notifications on this article OR blog.
				$request = $zcFunc['db_query']("
					SELECT sent, article_id
					FROM {db_prefix}log_notify
					WHERE (article_id IN ({array_int:articles}) OR blog_id = {int:current_blog})
						AND member_id = {int:user_id}
					LIMIT 2", __FILE__, __LINE__,
					array(
						'articles' => $articles,
						'user_id' => $context['user']['id'],
						'current_blog' => $blog
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
						$zcFunc['db_query']("
							UPDATE {db_prefix}log_notify
							SET sent = 0
							WHERE (article_id IN ({array_int:articles}) OR blog_id = {int:current_blog})
								AND member_id = {int:user_id}
							LIMIT 2", __FILE__, __LINE__,
							array(
								'articles' => $articles,
								'user_id' => $context['user']['id'],
								'current_blog' => $blog
							)
						);
						$do_once = false;
					}
				}
			}
	
			// mark blog as seen
			$zcFunc['db_insert']('replace', '{db_prefix}log_blogs', array('article_id' => 'int', 'member_id' => 'int', 'blog_id' => 'int'), array('article_id' => $zc['settings']['max_article_id'], 'member_id' => $context['user']['id'], 'blog_id' => $blog));
		}
		
		// add notify option to options array for each article if they can mark notify
		if ($context['can_mark_notify'])
			foreach ($articles as $art)
				$context['zc']['articles'][$art]['options']['notify'] = '<a href="' . $scripturl . '?zc=notify;sa=' . ($context['zc']['is_marked_notify']['articles'][$art] ? 'off' : 'on') . $context['zc']['blog_request'] . ';article=' . $art . '.0;sesc=' . $context['session_id'] . ';from=' . (!empty($article) && $article == $art ? 'article,' . $article . ',' . $_REQUEST['start'] : (!empty($blog) ? 'blog,' . $blog . ',' . $_REQUEST['start'] : 'community')) . '" onclick="return confirm(\'' . ($context['zc']['is_marked_notify']['articles'][$art] ? sprintf($txt['b264'], $txt['b114a'], $txt['b130'], $txt['b170']) : sprintf($txt['b264'], $txt['b113a'], $txt['b130'], $txt['b170'])) . '\');" rel="nofollow" title="' . ($context['zc']['is_marked_notify']['articles'][$art] ? $txt['b276'] : $txt['b275']) . '"><span class="notify_icon">&nbsp;</span></a>';
				
		// if we want to display comments, get all the comments to be displayed
		if ($in_context['display_comments'])
		{
			// if we are viewing a single article (not the main blog page)...
			/*if (!empty($article) && !is_numeric($_REQUEST['start']) && $context['zc']['articles'][$article]['has_comments'])
			{
				// start at the newest comment
				if ($_REQUEST['start'] == 'new')
				{
					// we don't know what the newest comment is to a guest... assume the last one...
					if ($context['user']['is_guest'])
					{
						$start_from = $context['zc']['articles'][$article]['num_comments'] - 1;
						$_REQUEST['start'] = empty($context['user']['blog_preferences']['newest_comments_first']) ? $start_from : 0;
					}
					else
					{
						// find the earliest unread comment in the article
						$request = $zcFunc['db_query']("
							SELECT IFNULL(lt.comment_id, IFNULL(lmr.comment_id, -1)) + 1 AS new_from
							FROM {db_prefix}comments AS c
								LEFT JOIN {db_prefix}log_articles AS lt ON (lt.article_id = {int:current_article} AND lt.member_id = {int:member_id})
								LEFT JOIN {db_prefix}log_mark_read AS lmr ON (lmr.blog_id = {int:current_blog} AND lmr.member_id = {int:member_id})
							WHERE c.article_id = {int:current_article}
							LIMIT 1", __FILE__, __LINE__,
							array(
								'member_id' => $context['user']['id'],
								'current_blog' => $blog,
								'current_article' => $article
							)
						);
						list($new_from) = $zcFunc['db_fetch_row']($request);
						$zcFunc['db_free_result']($request);
						$_REQUEST['start'] = 'comment' . $new_from;
					}
				}
				
				// link to a comment in the article
				if (substr($_REQUEST['start'], 0, 7) == 'comment')
				{
					$comment_id = (int) substr($_REQUEST['start'], 7);
					if (empty($context['zc']['articles'][$article]['num_unapproved_comments']) && $comment_id >= $topicinfo['id_last_msg'])
						$start_from = $context['zc']['articles'][$article]['num_comments'] - 1;
					elseif (empty($context['zc']['articles'][$article]['num_unapproved_comments']) && $comment_id <= $topicinfo['id_first_msg'])
						$start_from = 0;
					else
					{
						$request = $zcFunc['db_query']("
							SELECT COUNT(*)
							FROM {db_prefix}comments
							WHERE comment_id < {int:comment_id}
								AND article_id = {int:current_article}" . (!empty($context['zc']['blog_settings']['comments_require_approval']) && !$context['can_moderate_blog'] && !$context['can_approve_comments_in_any_b'] ? "
								AND (approved = 1" . ($context['user']['is_guest'] ? '' : " OR member_id = {int:member_id}") . ")" : ''),
							array(
								'member_id' => $context['user']['id'],
								'current_article' => $article,
								'comment_id' => $comment_id
							)
						);
						list ($start_from) = $zcFunc['db_fetch_row']($request);
						$zcFunc['db_free_result']($request);
					}
		
					$_REQUEST['start'] = empty($context['user']['blog_preferences']['newest_comments_first']) ? $start_from : $context['zc']['articles'][$article]['num_comments'] - $start_from - 1;
				}
			}*/
		
			foreach ($context['zc']['articles'] as $art)
				if ($context['zc']['articles'][$art['id']]['has_comments'])
					$context['zc']['articles'][$art['id']]['comments'] = zc_get_comments($art['id']/*, (isset($start_from) ? $start_from : null)*/);
		}
	}
	
	// Subscribe/Unsubscribe link?
	if ($context['can_mark_notify'] && !empty($blog))
		$context['zc']['extra_above_side_windows']['options']['links'][] = '<a href="' . $scripturl . '?zc=notify;sa=' . ($context['zc']['is_marked_notify']['blogs'][$blog] ? 'off' : 'on') . $context['zc']['blog_request'] . ';sesc=' . $context['session_id'] . ';from=' . (!empty($article) ? 'article,' . $article . ',' . $_REQUEST['start'] : (!empty($blog) ? 'blog,' . $blog . ',' . $_REQUEST['start'] : 'community')) .'" onclick="return confirm(\'' . ($context['zc']['is_marked_notify']['blogs'][$blog] ? sprintf($txt['b264'], $txt['b114a'], $txt['b129'], $txt['b3003a']) : sprintf($txt['b264'], $txt['b113a'], $txt['b129'], $txt['b3003a'])) . '\');" rel="nofollow">'. ($context['zc']['is_marked_notify']['blogs'][$blog] ? $txt['b276'] : $txt['b275']) .'</a>';
	
	// add article subject to page title...
	if (!empty($article) && !empty($context['zc']['articles'][$article]['subject']))
		$context['page_title'] .= ' - ' . $context['zc']['articles'][$article]['subject'];
			
	// generic "no articles to display" error...
	if (empty($context['zc']['articles']) && empty($context['zc']['list_of_articles']) && empty($context['zc']['error']))
		// no articles in blog...
		if (!empty($blog))
			$context['zc']['error'] = array('b470', 'b471');
		// no news....
		else
			$context['zc']['error'] = 'b344';
	
	// plug-in slot #9
	zc_plugin_slot(9);
	
	if (!empty($blog))
	{
		// first check if we want to show the side windows at all
		if (empty($context['zc']['blog_settings']['hide_side_windows']) && !empty($context['zc']['side_windows']))
		{
			// show the side bar on the left?
			if (!empty($context['zc']['blog_settings']['justifySideWindows']))
				$context['zc']['side_bar_left'] = true;
			// show the side bar on the right?
			elseif (empty($context['zc']['blog_settings']['justifySideWindows']))
				$context['zc']['side_bar_right'] = true;
			
			if (!empty($context['zc']['side_bar_left']))
				$context['zc']['main_guts_width'] = $context['zc']['main_guts_width'] - $context['zc']['left_side_bar_width'];
				
			if (!empty($context['zc']['side_bar_right']))
				$context['zc']['main_guts_width'] = $context['zc']['main_guts_width'] - $context['zc']['right_side_bar_width'];
		}
	}
}

function zcLoadSideWindows() 
{
	global $txt, $context, $scripturl, $blog_info, $blog, $article, $zc, $zcFunc;
	
	// gets custom windows and prepares base windows
	zc_prepare_side_window_arrays();
	
	$return = array();
	$windowOrders = array();

	// All Custom Windows
	if (!empty($context['zc']['custom_windows']))
		foreach ($context['zc']['custom_windows'] as $window)
			if (!empty($window['id']) && !empty($window['win_order']) && !empty($window['enabled']))
			{
				$return[$window['win_order']] = array();
				$return[$window['win_order']]['content'] = empty($window['content_type']) ? $zcFunc['parse_bbc']($window['content']) : $zcFunc['un_htmlspecialchars']($window['content']);
				$return[$window['win_order']]['is_php'] = !empty($window['content_type']) && $window['content_type'] == 2;
				$return[$window['win_order']]['title'] = $window['title'];
				$return[$window['win_order']]['type'] = 'custom';
			}
	
	// assemble array of window orders using blog settings array
	foreach ($context['zc']['side_windows'] as $win_order => $array)
		$windowOrders[$array['type']] = $win_order;
		
	// make part of query for access restrictions (access_restrict / approved) for articles...
	$access_restrict_query = '';
	$info = array();
	if (!$context['user']['is_admin'])
	{
	 	$access_restrict_query .= "
			AND ((t.access_restrict = 0)";
			 
		if (!$context['user']['is_guest'])
		{
			$info += array('user_id0' => '%,' . $context['user']['id'] . ',%', 'user_id1' => $context['user']['id'] . ',%', 'user_id2' => '%,' . $context['user']['id'], 'user_id3' => $context['user']['id'], 'user_id4' => '% ' . $context['user']['id'] . ',%', 'user_id5' => '% ' . $context['user']['id'] . ' %', 'user_id6' => '%,' . $context['user']['id'] . ' %');
			$access_restrict_query .= '
				OR (t.poster_id = {int:user_id})
				OR ((t.access_restrict = 2) AND (((mem.{tbl:members::column:buddy_list} LIKE {string:user_id0}) OR (mem.{tbl:members::column:buddy_list} LIKE {string:user_id1}) OR (mem.{tbl:members::column:buddy_list} LIKE {string:user_id2}) OR (mem.{tbl:members::column:buddy_list} = {string:user_id3})) AND (mem.{tbl:members::column:buddy_list} NOT LIKE {string:user_id4}) AND (mem.{tbl:members::column:buddy_list} NOT LIKE {string:user_id5}) AND (mem.{tbl:members::column:buddy_list} NOT LIKE {string:user_id6})))';
		}
				
	 	$access_restrict_query .= ")";
				
		if (!$context['can_approve_articles_in_any_b'] && !$context['can_moderate_blog'])
			$access_restrict_query .= "
			AND ((t.approved = 1) OR (bs.articles_require_approval = 0)" . (!$context['user']['is_guest'] ? " OR (t.poster_id = {int:user_id})" : '') . ")";
			
		if (!$context['user']['is_guest'] && !$context['can_approve_articles_in_any_b'] && !$context['can_moderate_blog'])
			$info['user_id'] = $context['user']['id'];
	}
	
	$left_joins = '';
	if (!$context['user']['is_admin'] && !$context['user']['is_guest'])
		$left_joins = "
			LEFT JOIN {db_prefix}{table:members} AS mem ON (mem.{tbl:members::column:id_member} = t.poster_id)";
			
	// Polls window
	if (!empty($context['zc']['blog_settings']['enablePollsWindow']))
	{
		$return[$windowOrders['polls']] = array();
		$return[$windowOrders['polls']]['title'] = $txt['b247'];
		$return[$windowOrders['polls']]['type'] = 'polls';
		
		require_once($zc['sources_dir'] . '/Subs-Polls.php');
		$context['zc']['polls'] = zcGetPolls();
		
		if (!empty($context['zc']['polls']))
		{
			$return[$windowOrders['polls']]['content'] = $context['zc']['polls'];
			
			// only load template if we have poll(s) to display...
			zcLoadTemplate('Generic-poll');
		}
			
		if (empty($return[$windowOrders['polls']]['content']))
			$return[$windowOrders['polls']] = array();
		else
			$return_not_empty = true;
	}
	
	// Who Viewing window  ...extremely cool :)
	if (!empty($context['zc']['blog_settings']['enableWhoViewingWindow']))
	{
		global $modSettings;
		
		// get the members who are online and viewing this blog
		$request = $zcFunc['db_query']("
			SELECT
				lo.{tbl:log_online::column:id_member} AS id_member, lo.{tbl:log_online::column:session} AS session, lo.{tbl:log_online::column:log_time} AS log_time, mem.{tbl:members::column:real_name} AS real_name, mem.{tbl:members::column:member_name} AS member_name, mem.{tbl:members::column:show_online} AS show_online,
				mg.{tbl:membergroups::column:online_color} AS online_color, mg.{tbl:membergroups::column:id_group} AS id_group, mg.{tbl:membergroups::column:group_name} AS group_name" . (!empty($context['zc']['blog_settings']['useAvatarsForWhoViewing']) ? ", 
				mem.{tbl:members::column:avatar} AS avatar, IFNULL(a.{tbl:attachments::column:id_attach}, 0) AS id_attach, a.{tbl:attachments::column:filename} AS filename, a.{tbl:attachments::column:attachment_type} AS attachment_type, a.{tbl:attachments::column:width} AS avatar_width, a.{tbl:attachments::column:height} AS avatar_height" : '') . "
			FROM {db_prefix}{table:log_online} AS lo
				LEFT JOIN {db_prefix}{table:members} AS mem ON (mem.{tbl:members::column:id_member} = lo.{tbl:log_online::column:id_member})
				LEFT JOIN {db_prefix}{table:membergroups} AS mg ON (mg.{tbl:membergroups::column:id_group} = IF(mem.{tbl:members::column:id_group} = 0, mem.{tbl:members::column:id_post_group}, mem.{tbl:members::column:id_group}))" . (!empty($context['zc']['blog_settings']['useAvatarsForWhoViewing']) ? "
				LEFT JOIN {db_prefix}{table:attachments} AS a ON (a.{tbl:attachments::column:id_member} = mem.{tbl:members::column:id_member})" : '') . "
			WHERE INSTR(lo.{tbl:log_online::column:url}, {string:log_online_url_1})", __FILE__, __LINE__,
			array(
				'log_online_url_1' => 's:4:"blog";s:2:"' . $blog . '"',
				'current_blog' => $blog
			)
		);
		if (!isset($avatars))
			$avatars = array();
			
		$view_members_list = array();
		$view_num_hidden = 0;
		$view_num_members = 0;
		$view_num_guests = 0;
		while ($row = $zcFunc['db_fetch_assoc']($request))
		{
			if (empty($row['id_member']))
			{
				$view_num_guests++;
			
				if (!empty($context['zc']['blog_settings']['useAvatarsForWhoViewing']))
				{
					$avatars['guest'] = '<img src="' . $context['zc']['default_images_url'] . '/mysteryperson.jpg" alt="' . $txt['b567'] . '" border="0" />';
					
					$ip = str_replace('ip', '', $row['session']);
					
					$view_members_list[$row['log_time'] . 'guest' . $row['session']] = $context['can_moderate_site'] ? sprintf($context['zc']['link_templates']['trackip'], $ip, $avatars['guest'], ' title="' . $txt['b567'] . ' - ' . $ip . '" style="margin:3px;" rel="nofollow"') : '<a href="" title="' . $txt['b567'] . ($context['can_moderate_site'] ? ' - ' . $ip : '') . '">' . $avatars['guest'] . '</a>';
				}
			}
			else
			{
				$view_num_members++;
					
				// we're using avatars instead of names....
				if (!empty($context['zc']['blog_settings']['useAvatarsForWhoViewing']))
				{
					// figure out the filename
					if ($row['avatar'] == '')
					{
						if ($row['id_attach'] > 0)
						{
							if (empty($row['attachment_type']))
								$filename = $scripturl . '?action=dlattach;attach=' . $row['id_attach'] . ';type=avatar';
							else
								$filename = $modSettings['custom_avatar_url'] . '/' . $row['filename'];
						}
						else
							$filename = '';
					}
					elseif (stristr($row['avatar'], 'http://'))
						$filename = $row['avatar'];
					else
						$filename = $modSettings['avatar_url'] . '/' . $zcFunc['htmlspecialchars']($row['avatar']);
								
					$current_width = $row['avatar_width'];
					$current_height = $row['avatar_height'];
							
					// prepare this user's avatar
					if ((!empty($row['show_online']) || $context['can_moderate_site']) && empty($avatars[$row['id_member']]) && !empty($filename))
						$avatars[$row['id_member']] = zcLoadAvatar($row, $filename, $zc['settings']['who_viewing_max_avatar_height'], $zc['settings']['who_viewing_max_avatar_height'], $current_width, $current_height);
					else
						$avatars[$row['id_member']] = '<img src="'. $context['zc']['default_images_url'] .'/mysteryperson.jpg" height="'. ($zc['settings']['who_viewing_max_avatar_height'] > 48 ? 48 : $zc['settings']['who_viewing_max_avatar_height']) .'" alt="" border="0" />';
						
					$view_members_list[$row['log_time'] . $row['member_name']] = sprintf($context['zc']['link_templates']['user_profile'], $row['id_member'], $avatars[$row['id_member']], ' title="' . (!empty($row['show_online']) || $context['can_moderate_site'] ? $row['real_name'] : $txt['b315']) . '" rel="nofollow" style="margin:3px; padding:0;"');
				}
				// ok we're not using avatars....
				elseif (!empty($row['show_online']) || $context['can_moderate_site'])
				{
					$is_buddy = in_array($row['id_member'], $context['user']['buddies']);
					$link = sprintf($context['zc']['link_templates']['user_profile'], $row['id_member'], $row['real_name'], ' title="' . $txt['b41'] . '" style="white-space:nowrap;' . (!empty($row['onlineColor']) ? ' color:' . $row['onlineColor'] . ';' : '') . '"');
		
					if ($is_buddy)
						$link = '<b>' . $link . '</b>';
						
					$view_members_list[$row['log_time'] . $row['member_name']] = empty($row['show_online']) ? '<i>' . $link . '</i>' : $link;
				}
	
				if (empty($row['show_online']))
					$view_num_hidden++;
			}
		}
		$zcFunc['db_free_result']($request);

		// Put them in "last clicked" order.
		krsort($view_members_list);
		
		$return[$windowOrders['whoViewing']] = array();
		$return[$windowOrders['whoViewing']]['title'] = $txt['b502'];
		$return[$windowOrders['whoViewing']]['type'] = 'custom';
		$return[$windowOrders['whoViewing']]['content'] = '';
		
		if (empty($context['zc']['blog_settings']['useAvatarsForWhoViewing']))
		{
			if (!empty($view_members_list))
				$return[$windowOrders['whoViewing']]['content'] = implode(', ', $view_members_list);
				
			if (!empty($view_num_members))
				$return[$windowOrders['whoViewing']]['content'] .= (!empty($return[$windowOrders['whoViewing']]['content']) ? '<br />' : '') . $view_num_members . ' ' . ($view_num_members == 1 ? $txt['b3024a'] : $txt['b3024']);
				
			if (!empty($view_num_guests))
				$return[$windowOrders['whoViewing']]['content'] .= (!empty($return[$windowOrders['whoViewing']]['content']) ? '<br />' : '') . $view_num_guests . ' ' . ($view_num_guests == 1 ? $txt['b567'] : $txt['b133']);
				
			if (!empty($view_num_hidden))
				$return[$windowOrders['whoViewing']]['content'] .= (!empty($return[$windowOrders['whoViewing']]['content']) ? '<br />' : '') . $view_num_hidden . ' ' . $txt['b315'];
		}
		elseif (!empty($view_members_list))
			$return[$windowOrders['whoViewing']]['content'] = implode('', $view_members_list);
			
		if (empty($return[$windowOrders['whoViewing']]['content']))
			$return[$windowOrders['whoViewing']] = array();
		else
			$return_not_empty = true;
	}
	
	$limit_recent_entries = $context['zc']['blog_settings']['num_recent_entries'];

	// Most Recent window (shows most recent month/year combo and its most recent articles)
	if (empty($context['zc']['blog_settings']['enableRecentEntries']) && !empty($windowOrders['recent']))
	{
		$return[$windowOrders['recent']] = array();
		$return[$windowOrders['recent']]['type'] = 'list';
		
		// get the month/year for the most recent topic in this blog board
		$request = $zcFunc['db_query']("
			SELECT t.month, t.year
			FROM {db_prefix}articles AS t
				LEFT JOIN {db_prefix}settings AS bs ON (bs.blog_id = t.blog_id)" . $left_joins . "
			WHERE t.blog_id = {int:current_blog}
				AND t.month != {empty_string}
				AND t.year != {empty_string}" . $access_restrict_query . "
			ORDER BY t.posted_time DESC
			LIMIT 1", __FILE__, __LINE__,
			array_merge(
				$info,
				array(
					'current_blog' => $blog
				)
			)
		);
			
		if ($zcFunc['db_num_rows']($request) > 0)
			$row = $zcFunc['db_fetch_assoc']($request);
			
		$month = !empty($row['month']) ? $row['month'] : 0;
		$year = !empty($row['year']) ? $row['year'] : 0;
		
		$zcFunc['db_free_result']($request);
		
		// if there is no recent month with any articles... obviously don't need to get articles
		if (!empty($month) && !empty($year))
		{
			// get this month's articles so that we can display them as a little list
			$request = $zcFunc['db_query']("
				SELECT t.article_id, t.subject
				FROM {db_prefix}articles AS t
					LEFT JOIN {db_prefix}settings AS bs ON (bs.blog_id = t.blog_id)" . $left_joins . "
				WHERE t.month = {int:month}
					AND t.year = {int:year}
					AND t.blog_id = {int:current_blog}" . $access_restrict_query . "
				ORDER BY t.article_id DESC " . (!empty($limit_recent_entries) ? "
				LIMIT {int:limit}" : ''), __FILE__, __LINE__,
				array_merge(
					$info,
					array(
						'month' => $month,
						'year' => $year,
						'limit' => $limit_recent_entries,
						'current_blog' => $blog
					)
				)
			);
				
			if ($zcFunc['db_num_rows']($request) > 0)
			{
				$rows = $zcFunc['db_num_rows']($request);
				
				$return[$windowOrders['recent']]['content'][] = array();
				// Build articles array
				while ($row = $zcFunc['db_fetch_assoc']($request))
				{
					$row['subject'] = strip_tags($zcFunc['un_htmlspecialchars']($row['subject']));
					zc_censor_text($row['subject']);
					
					$return[$windowOrders['recent']]['content'][] = '<a href="' . $scripturl . '?article=' . $row['article_id'] . '.0"' . (!empty($article) && $article == $row['article_id'] ? ' rel="nofollow"' : '') . '>' . $row['subject'] . '</a>';
				}
			}
			$zcFunc['db_free_result']($request);
		}
		if (!empty($return[$windowOrders['recent']]['content']))
			$return[$windowOrders['recent']]['title'] = $txt['months_titles'][$month] . ', ' . $year;
			
		if (empty($return[$windowOrders['recent']]['content']))
			$return[$windowOrders['recent']] = array();
		else
			$return_not_empty = true;
	}
	// Most Recent window
	elseif (!empty($windowOrders['recent']))
	{
		$return[$windowOrders['recent']] = array();
		$return[$windowOrders['recent']]['type'] = 'list';
		
		$request = $zcFunc['db_query']("
			SELECT t.article_id, t.subject
			FROM {db_prefix}articles AS t
				LEFT JOIN {db_prefix}settings AS bs ON (bs.blog_id = t.blog_id)" . $left_joins . "
			WHERE t.blog_id = {int:current_blog}
				AND ((t.approved = 1) OR (bs.articles_require_approval = 0))" . $access_restrict_query . "
			ORDER BY t.article_id DESC " . (!empty($limit_recent_entries) ? "
			LIMIT {int:limit}" : ''), __FILE__, __LINE__,
			array_merge(
				$info,
				array(
					'limit' => $limit_recent_entries,
					'current_blog' => $blog
				)
			)
		);
		$rows = $zcFunc['db_num_rows']($request);
		
		$return[$windowOrders['recent']]['content'] = array();
		// Build articles array
		while ($row = $zcFunc['db_fetch_assoc']($request))
		{
			$row['subject'] = strip_tags($zcFunc['un_htmlspecialchars']($row['subject']));
			zc_censor_text($row['subject']);
			
			$return[$windowOrders['recent']]['content'][] = '<a href="' . $scripturl . '?article=' . $row['article_id'] . '.0"' . (!empty($article) && $article == $row['article_id'] ? ' rel="nofollow"' : '') . '>' . $row['subject'] . '</a>';
		}
		$zcFunc['db_free_result']($request);
		
		if (!empty($return[$windowOrders['recent']]['content']))
			$return[$windowOrders['recent']]['title'] = $txt['b503'];
			
		if (empty($return[$windowOrders['recent']]['content']))
			$return[$windowOrders['recent']] = array();
		else
			$return_not_empty = true;
	}

	// Categories window
	if (!empty($context['zc']['blog_settings']['enableCategoryList']) && !empty($context['zc']['categories']) && !empty($windowOrders['categories']))
	{
		$return[$windowOrders['categories']] = array();
		$return[$windowOrders['categories']]['type'] = 'list';
	
		$return[$windowOrders['categories']]['content'] = array();
		foreach ($context['zc']['categories'] as $category)
			$return[$windowOrders['categories']]['content'][] = '<a href="' . $scripturl . '?blog=' . $blog . '.0;category=' . $category['id'] . '">' . $category['name'] . '&nbsp;(' . $category['total'] . ')</a>';
		
		if (!empty($return[$windowOrders['categories']]['content']))
			$return[$windowOrders['categories']]['title'] = $txt['b16a'];
			
		if (empty($return[$windowOrders['categories']]['content']))
			$return[$windowOrders['categories']] = array();
		else
			$return_not_empty = true;
	}
	
	// Archives window
	if (!empty($context['zc']['blog_settings']['enableArchives']) && !empty($windowOrders['archives']))
	{
		// this is the most efficient way of gathering all the month, year pairs and num_articles for each
		$request = $zcFunc['db_query']("
			SELECT t.month, t.year, COUNT(t.article_id) as num_articles
			FROM {db_prefix}articles AS t
				LEFT JOIN {db_prefix}settings AS bs ON (bs.blog_id = t.blog_id)" . $left_joins . "
			WHERE t.blog_id = {int:current_blog}
				AND t.month != {empty_string}
				AND t.year != {empty_string}" . $access_restrict_query . "
			GROUP BY t.month, t.year
			ORDER BY t.year DESC, t.month DESC", __FILE__, __LINE__,
			array_merge(
				$info,
				array(
					'current_blog' => $blog
				)
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
		
		$return[$windowOrders['archives']] = array();
		$return[$windowOrders['archives']]['content'] = array();
		$return[$windowOrders['archives']]['type'] = 'list';
		
		if (!empty($articles_exist))
			foreach ($articles_exist as $archive)
				$return[$windowOrders['archives']]['content'][] = '<a href="' . $scripturl . '?blog=' . $blog . '.0;date=' . $archive['year'] . '_' . $archive['month'] . '">' . $txt['months_titles'][$archive['month']] . ' ' . $archive['year'] . ' ' . '(' . $archive['num_articles'] . ')</a>';
		
		if (!empty($return[$windowOrders['archives']]['content']))
			$return[$windowOrders['archives']]['title'] = $txt['b22'];
			
		if (empty($return[$windowOrders['archives']]['content']))
			$return[$windowOrders['archives']] = array();
		else
			$return_not_empty = true;
	}
	
	// Tags Window
	if (!empty($context['zc']['blog_settings']['enableTagsWindow']) && !empty($context['zc']['tags']) && !empty($windowOrders['tags']))
	{
		$return[$windowOrders['tags']] = array();
		
		foreach ($context['zc']['tags'] as $tag)
			$return[$windowOrders['tags']]['content'][] = '<a href="' . $scripturl . '?blog=' . $blog . '.0;tag=' . urlencode($tag['tag']) . '" style="white-space:nowrap;font-size:' . zcTagFontSize($tag['num_articles'], $context['total_tag_instances']) . 'px;" title="' . $tag['num_articles'] . ' ' . ($tag['num_articles'] == 1 ? $txt['b66'] : $txt['b66a']) . '">' . $tag['tag'] . '</a>';
		
		$return[$windowOrders['tags']]['type'] = 'custom';
		$return[$windowOrders['tags']]['content'] = implode(', ', $return[$windowOrders['tags']]['content']);
		
		if (!empty($return[$windowOrders['tags']]['content']))
			$return[$windowOrders['tags']]['title'] = $txt['b26a'];
			
		if (empty($return[$windowOrders['tags']]['content']))
			$return[$windowOrders['tags']] = array();
		else
			$return_not_empty = true;
	}
	
	// Blog Statistics window
	if (!empty($context['zc']['blog_settings']['enableStatsWindow']) && !empty($windowOrders['stats']))
	{
		$return[$windowOrders['stats']] = array();
		$return[$windowOrders['stats']]['type'] = 'list';
		
		$format_string = '%1$s: <b>%2$s</b>';
		$return[$windowOrders['stats']]['content'] = array(
			sprintf($format_string, $txt['b3027'], !empty($blog_info['num_views']) ? $blog_info['num_views'] : 0),
			sprintf($format_string, $txt['b66a'], !empty($blog_info['num_articles']) ? $blog_info['num_articles'] : 0),
			sprintf($format_string, $txt['b15a'], !empty($blog_info['num_comments']) ? $blog_info['num_comments'] : 0),
			sprintf($format_string, $txt['b3047'], !empty($context['zc']['blog_settings']['hideBlog']) ? $txt['b315'] : $txt['b25']),
		);
		
		if (!empty($return[$windowOrders['stats']]['content']))
			$return[$windowOrders['stats']]['title'] = $txt['b517'];
			
		if (empty($return[$windowOrders['stats']]['content']))
			$return[$windowOrders['stats']] = array();
		else
			$return_not_empty = true;
	}
	
	// "Most Commented" window
	if (!empty($context['zc']['blog_settings']['enableMostCommentedWindow']) && !empty($windowOrders['mostCommented']))
	{
		$return[$windowOrders['mostCommented']] = array();
		$return[$windowOrders['mostCommented']]['type'] = 'list';
		
		$limit_most_commented = isset($context['zc']['blog_settings']['limit_most_commented']) ? $context['zc']['blog_settings']['limit_most_commented'] : 5;
		
		$return[$windowOrders['mostCommented']]['content'] = array();
		// get articles with the most comments
		$request = $zcFunc['db_query']("
			SELECT t.subject, t.article_id, t.num_comments
			FROM {db_prefix}articles AS t
				LEFT JOIN {db_prefix}settings AS bs ON (bs.blog_id = t.blog_id)" . $left_joins . "
			WHERE t.blog_id = {int:current_blog}
				AND t.num_comments > 0" . $access_restrict_query . "
			ORDER BY t.num_comments DESC" . (!empty($limit_most_commented) ? "
			LIMIT {int:limit}" : ''), __FILE__, __LINE__,
			array_merge(
				$info,
				array(
					'limit' => $limit_most_commented,
					'current_blog' => $blog
				)
			)
		);
			
		// populate info array with list of articles
		while ($row = $zcFunc['db_fetch_assoc']($request))
		{
			$row['subject'] = strip_tags($zcFunc['un_htmlspecialchars']($row['subject']));
			zc_censor_text($row['subject']);
			$return[$windowOrders['mostCommented']]['content'][] = '<a href="' . $scripturl . '?article=' . $row['article_id'] . '.0"' . (!empty($article) && $article == $row['article_id'] ? ' rel="nofollow"' : '') . '>' . $row['subject'] . ' (' . $row['num_comments'] . ')</a>';
		}
		$zcFunc['db_free_result']($request);
		
		if (!empty($return[$windowOrders['mostCommented']]['content']))
			$return[$windowOrders['mostCommented']]['title'] = $txt['b440'];
			
		if (empty($return[$windowOrders['mostCommented']]['content']))
			$return[$windowOrders['mostCommented']] = array();
		else
			$return_not_empty = true;
	}
	
	// "Recent Comments" window
	if (!empty($context['zc']['blog_settings']['enableRecentCommentsWindow']) && !empty($windowOrders['recentComments']))
	{
		$return[$windowOrders['recentComments']] = array();
		$limit_recent_comments = isset($context['zc']['blog_settings']['limit_recent_comments']) ? $context['zc']['blog_settings']['limit_recent_comments'] : 5;
		
		$return[$windowOrders['recentComments']]['content'] = array();
		// get the most recent comments
		$request = $zcFunc['db_query']("
			SELECT c.body, c.article_id, c.comment_id
			FROM {db_prefix}comments AS c
				LEFT JOIN {db_prefix}settings AS bs ON (bs.blog_id = c.blog_id)
			WHERE c.blog_id = {int:current_blog}
				AND ((c.approved = 1) OR (bs.comments_require_approval = 0)" . (!$context['user']['is_guest'] ? " OR (c.poster_id = {int:user_id})" : '') . ")
			ORDER BY c.posted_time DESC" . (!empty($limit_recent_comments) ? "
			LIMIT {int:limit}" : ''), __FILE__, __LINE__,
			array(
				'user_id' => $context['user']['id'],
				'limit' => $limit_recent_comments,
				'current_blog' => $blog
			)
		);
			
		// populate info array with list of comments
		while ($row = $zcFunc['db_fetch_assoc']($request))
		{
			$row['body'] = strip_tags($zcFunc['un_htmlspecialchars']($zcFunc['parse_bbc']($row['body'], false)));
			$row['body'] = zcTruncateText($row['body'], 40, ' ', 1, '...');
			zc_censor_text($row['body']);
			$return[$windowOrders['recentComments']]['content'][] = '<a href="' . $scripturl . '?article=' . $row['article_id'] . '.0#c' . $row['comment_id'] . '"' . (!empty($article) && $article == $row['article_id'] ? ' rel="nofollow"' : '') . '>' . $row['body'] . '</a>';
		}
		$zcFunc['db_free_result']($request);
		
		$return[$windowOrders['recentComments']]['type'] = 'list';
		
		if (!empty($return[$windowOrders['recentComments']]['content']))
			$return[$windowOrders['recentComments']]['title'] = $txt['b441'];
			
		if (empty($return[$windowOrders['recentComments']]['content']))
			$return[$windowOrders['recentComments']] = array();
		else
			$return_not_empty = true;
	}
	
	$context['zc']['max_window_order'] = max($windowOrders);
	
	// if $return is empty or $return_not_empty
	if (empty($return) || empty($return_not_empty))
		$return = array();
		
	return $return;
}

function zc_get_articles($blogs = null, $members = null)
{
	global $scripturl, $txt, $context, $blog_info, $blog, $article, $zc, $in_context, $zcFunc;
	
	$limit_num_articles = empty($context['viewing_single_article']) && !empty($context['zc']['blog_settings']['max_articles_on_blog']) ? $context['zc']['blog_settings']['max_articles_on_blog'] : '';
	$length = isset($context['zc']['blog_settings']['max_length_articles']) ? $context['zc']['blog_settings']['max_length_articles'] : 1800;
	$start = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;
	
	// should we make a page index?
	if (!empty($limit_num_articles) && ($blog_info['num_articles'] > $limit_num_articles))
		$context['main_blog_page_index'] = zcConstructPageIndex($scripturl . '?blog=' . $blog . '.%d' . zcRequestVarsToString('start,blog', ';'), $start, $blog_info['num_articles'], $limit_num_articles, true);
		
	$info = array('start' => $start, 'maxindex' => $limit_num_articles, 'user_id' => $context['user']['id'], 'current_blog' => $blog, 'current_article' => $article);
	// make part of query for access restrictions (access_restrict / approved)
	$access_restrict_query = '';
	if (!$context['user']['is_admin'])
	{
	 	$access_restrict_query .= "
			AND ((t.access_restrict = 0)";
			 
		if (!$context['user']['is_guest'])
		{
			$info += array('user_id0' => '%,' . $context['user']['id'] . ',%', 'user_id1' => $context['user']['id'] . ',%', 'user_id2' => '%,' . $context['user']['id'], 'user_id3' => $context['user']['id'], 'user_id4' => '% ' . $context['user']['id'] . ',%', 'user_id5' => '% ' . $context['user']['id'] . ' %', 'user_id6' => '%,' . $context['user']['id'] . ' %');
			$access_restrict_query .= '
				OR (t.poster_id = {int:user_id})
				OR ((t.access_restrict = 3) AND (((t.users_allowed LIKE {string:user_id0}) OR (t.users_allowed LIKE {string:user_id1}) OR (t.users_allowed LIKE {string:user_id2}) OR (t.users_allowed = {string:user_id3})) AND (t.users_allowed NOT LIKE {string:user_id4}) AND (t.users_allowed NOT LIKE {string:user_id5}) AND (t.users_allowed NOT LIKE {string:user_id6})))
				OR ((t.access_restrict = 2) AND (((mem.{tbl:members::column:buddy_list} LIKE {string:user_id0}) OR (mem.{tbl:members::column:buddy_list} LIKE {string:user_id1}) OR (mem.{tbl:members::column:buddy_list} LIKE {string:user_id2}) OR (mem.{tbl:members::column:buddy_list} = {string:user_id3})) AND (mem.{tbl:members::column:buddy_list} NOT LIKE {string:user_id4}) AND (mem.{tbl:members::column:buddy_list} NOT LIKE {string:user_id5}) AND (mem.{tbl:members::column:buddy_list} NOT LIKE {string:user_id6})))';
		}
				
	 	$access_restrict_query .= ")";
				
		if (!empty($context['zc']['blog_settings']['articles_require_approval']) && !$context['can_approve_articles_in_any_b'] && !$context['can_moderate_blog'])
			$access_restrict_query .= "
			AND ((t.approved = 1)" . (!$context['user']['is_guest'] ? " OR (t.poster_id = {int:user_id})" : '') . ")";
	}

	// get the article or articles
	$request = $zcFunc['db_query']("
		SELECT
			t.subject, t.body, IFNULL(mem.{tbl:members::column:real_name}, t.poster_name) AS poster_name, t.posted_time, t.last_edit_time, t.last_edit_name, t.article_id, t.smileys_enabled, t.poster_id, t.blog_tags, t.blog_category_id, t.locked, t.num_comments, t.num_unapproved_comments, t.num_views, t.last_comment_id, t.approved" . (!$context['user']['is_admin'] ? ", t.access_restrict, t.users_allowed" : '') . ", 
			" . ($context['user']['is_guest'] ? '0' : 'IFNULL(la.comment_id, -1) + 1') . " AS new_from, mem.{tbl:members::column:id_member} AS id_member
		FROM {db_prefix}articles AS t " . ($context['user']['is_guest'] ? '' : "
			LEFT JOIN {db_prefix}log_articles AS la ON (la.article_id = {int:current_article} AND la.member_id = {int:user_id})") ."
			LEFT JOIN {db_prefix}{table:members} AS mem ON (mem.{tbl:members::column:id_member} = t.poster_id) " . (!empty($article) && !empty($context['viewing_single_article']) ? "
		WHERE t.article_id = {int:current_article}" . $access_restrict_query . "
		LIMIT 1" : "
		WHERE t.blog_id = {int:current_blog}" . $access_restrict_query . "
		ORDER BY t.posted_time DESC" . (!empty($limit_num_articles) ? "
		LIMIT {int:start}, {int:maxindex}" : '')), __FILE__, __LINE__, $info);
		
	$return = array();
	while ($row = $zcFunc['db_fetch_assoc']($request))
	{
		// Did the user start this article?
		$user_started = ($row['poster_id'] == $context['user']['id']) && !$context['user']['is_guest'];
		
		// access restricted?
		if (!empty($row['access_restrict']) && !$context['user']['is_admin'] && !$user_started)
		{
			$access_info = array(
				'access_restrict' => $row['access_restrict'],
				'users_allowed' => $row['users_allowed'],
				'poster_id' => $row['poster_id']
			);
			
			$can_see = zc_can_see_article($access_info);
				
			// if they can't see this article and they were trying to view just the one article, redirect them to the blog
			if (!$can_see && !empty($article))
				zc_redirect_exit('blog=' . $blog . '.0');
			// otherwise they just can't see this one article, continue to the next article
			elseif (!$can_see)
				continue;
		}
	
		$row['body'] = $zcFunc['un_htmlspecialchars']($row['body']);
		$row['subject'] = $zcFunc['un_htmlspecialchars']($row['subject']);
		zc_censor_text($row['subject']);
		zc_censor_text($row['body']);
		
		if (!empty($article))
			$context['zc']['meta']['keywords'] = zcTruncateText(strip_tags($zcFunc['un_htmlspecialchars']($row['body'])), 50, ' ', 1, '');
		
		// gotta do this before shortening the body text
		$row['body'] = $zcFunc['parse_bbc']($row['body'], $row['smileys_enabled']);
	
		// if the article is too long... chop it and add a [ ... ] (read more) link at the end
		if (!empty($length) && empty($context['viewing_single_article']))
			$row['body'] = zcTruncateText($row['body'], $length, ' ', 40, $txt['b31a'], $scripturl . '?article='. $row['article_id'] . '.0', $txt['b31']);
		
		$tags = array();
		$related_articles = array();
		// let's populate the tags variable with all of this article's tags (links)
		if (!empty($context['zc']['blog_settings']['show_tags']) || !empty($context['zc']['blog_settings']['show_related_articles']))
		{
			$tempTags = !empty($row['blog_tags']) ? explode(',', $row['blog_tags']) : array();
			$raw_tags = array();
			if (!empty($tempTags))
				foreach ($tempTags as $tag)
					if (!empty($context['zc']['tags'][$tag]['link']))
					{
						$tags[] = $context['zc']['tags'][$tag]['link'];
						$raw_tags[] = $tag;
					}
			unset($tempTags);
			
			// let's get related articles....
			if (!empty($context['zc']['blog_settings']['show_related_articles']) && !empty($raw_tags))
			{
				$info = array('blog_id' => $blog, 'user_id' => $context['user']['id'], 'article_id' => $row['article_id'], 'limit' => $context['zc']['blog_settings']['limit_related_articles']);
				foreach ($raw_tags as $k => $tag)
				{
					$tag = (string) $tag;
					$info += array('tag' . $k . 'a' => '%,' . $tag . ',%', 'tag' . $k . 'b' => $tag . ',%', 'tag' . $k . 'c' => '%,' . $tag, 'tag' . $k . 'd' => $tag, 'tag' . $k . 'e' => '% ' . $tag . ',%', 'tag' . $k . 'f' => '% ' . $tag . ' %', 'tag' . $k . 'g' => '%,' . $tag . ' %');
					$conditions[] = '(((blog_tags LIKE {string:tag' . $k . 'a}) OR (blog_tags LIKE {string:tag' . $k . 'b}) OR (blog_tags LIKE {string:tag' . $k . 'c}) OR (blog_tags = {string:tag' . $k . 'd})) AND (blog_tags NOT LIKE {string:tag' . $k . 'e}) AND (blog_tags NOT LIKE {string:tag' . $k . 'f}) AND (blog_tags NOT LIKE {string:tag' . $k . 'g}))';
				}
				$request2 = $zcFunc['db_query']("
					SELECT subject, article_id
					FROM {db_prefix}articles
					WHERE blog_id = {int:blog_id}
						AND article_id != {int:article_id}" . (!empty($context['zc']['blog_settings']['articles_require_approval']) && !$context['can_approve_articles_in_any_b'] && !$context['can_moderate_blog'] ? "
						AND ((approved = 1)" . (!$context['user']['is_guest'] ? " OR (poster_id = {int:user_id})" : '') . ")" : '') . "
						AND (" . implode('
						OR ', $conditions) . ")" . (!empty($info['limit']) ? "
					LIMIT {int:limit}" : ''), __FILE__, __LINE__, $info);
					
				while ($row2 = $zcFunc['db_fetch_assoc']($request2))
					$related_articles[] = '<a href="' . $scripturl . '?article=' . $row2['article_id'] . '.0" title="' . strip_tags($zcFunc['un_htmlspecialchars']($row2['subject'])) . '">' . zcTruncateText(strip_tags($zcFunc['un_htmlspecialchars']($row2['subject'])), 40, ' ', 1, '...') . '</a>';
			}
		}
		
		$can_edit = !empty($blog) ? $context['can_moderate_blog'] || (($context['can_moderate_blog'] || empty($row['locked'])) && (((($context['can_edit_own_articles_in_own_b'] && $user_started) || $context['can_edit_any_articles_in_own_b']) && $context['is_blog_owner']) || ($context['can_edit_own_articles_in_any_b'] && $user_started) || $context['can_edit_any_articles_in_any_b'])) : $context['can_edit_community_news'];
		$can_delete = !empty($blog) ? $context['can_moderate_blog'] || (($context['can_moderate_blog'] || empty($row['locked'])) && (((($context['can_delete_own_articles_in_own_b'] && $user_started) || $context['can_delete_any_articles_in_own_b']) && $context['is_blog_owner']) || ($context['can_delete_own_articles_in_any_b'] && $user_started) || $context['can_delete_any_articles_in_any_b'])) : $context['can_delete_community_news'];
		$can_lock = !empty($blog) ? $context['can_moderate_blog'] || (((($context['can_lock_own_articles_in_own_b'] && $user_started) || $context['can_lock_any_articles_in_own_b']) && $context['is_blog_owner']) || ($context['can_lock_own_articles_in_any_b'] && $user_started) || $context['can_lock_any_articles_in_any_b']) : $context['can_lock_community_news'];
		$can_reply = !empty($blog) ? $context['can_moderate_blog'] || (($context['can_moderate_blog'] || empty($row['locked'])) && $context['can_post_comments_in_any_b']) : $context['can_comment_on_news'];
		
		$num_comments = !empty($context['zc']['blog_settings']['comments_require_approval']) ? $row['num_comments'] - $row['num_unapproved_comments'] : $row['num_comments'];
		
		$return[$row['article_id']] = array(
			'id' => $row['article_id'],
			'has_comments' => !empty($num_comments) || (!empty($row['num_comments']) && ($context['can_moderate_blog'] || $context['can_approve_comments_in_any_b'] || $user_started)),
			'subject' => $row['subject'],
			'related_articles' => !empty($related_articles) ? $related_articles : array(),
			'tags' => !empty($tags) && is_array($tags) ? $tags : array(),
			'category' => !empty($context['zc']['blog_settings']['show_categories']) && !empty($row['blog_category_id']) && !empty($context['zc']['categories'][$row['blog_category_id']]['link']) ? $context['zc']['categories'][$row['blog_category_id']]['link'] : '',
			'body' => $row['body'],
			'locked' => !empty($row['locked']),
			'show_comments' => !empty($in_context['display_comments']),
			'num_unapproved_comments' => !empty($context['zc']['blog_settings']['comments_require_approval']) ? $row['num_unapproved_comments'] : 0,
			'num_comments' => comma_format($num_comments),
			'new_comment' => !$can_reply ? '' : '<a href="' . $scripturl . '?zc=post;article=' . $row['article_id'] . '.' . $row['num_comments'] . ';comment;from=' . (!empty($article) ? 'article,' . $article . ',' . $_REQUEST['start'] : (!empty($blog) ? 'blog,' . $blog . ',' . $_REQUEST['start'] : 'community')) . '" rel="nofollow">' . $txt['b223'] . '</a>',
			'time' => timeformat($row['posted_time'], false),
			'link' => '<a href="' . $scripturl . '?article=' . $row['article_id'] . '.0"' . (!empty($article) && $article == $row['article_id'] ? ' rel="nofollow"' : '') . '>' . $row['subject'] . '</a>',
			'poster' => array(
				'link' => !empty($row['id_member']) ? sprintf($context['zc']['link_templates']['user_profile'], $row['id_member'], $row['poster_name'], ' title="' . $txt['b41'] . '"') : $row['poster_name'],
			),
			'modified' => array(
				'time' => timeformat($row['last_edit_time'], false),
				'name' => !empty($context['zc']['blog_settings']['show_last_edit_articles']) || (empty($blog) && !empty($zc['settings']['news_block_show_last_edit'])) ? $row['last_edit_name'] : '',
			),
			'can_edit' => $can_edit,
			'can_delete' => $can_delete,
			'can_lock' => $can_lock,
			'can_reply' => $can_reply,
			'can_see_ip' => $context['can_moderate_site'] || ($row['poster_id'] == $context['user']['id'] && !empty($context['user']['id'])),
			/*'can_send' => $context['can_send_articles'],*/
			'can_mark_notify' => false,
			'bookmarking_links' => array(),
			'options' => array(),
			'new_from' => $row['new_from'],
			'last_comment_id' => $row['last_comment_id'],
			'is_approved' => !empty($row['approved']),
			'can_see_unapproved' => $context['can_moderate_blog'] || $context['can_approve_articles_in_any_b'] || $user_started,
			'can_approve' => $context['can_moderate_blog'] || $context['can_approve_articles_in_any_b'],
			'extra' => array(
				'links' => array(),
				'between_article_and_comments' => array(),
			),
		);
		
		// approve link?
		if ($return[$row['article_id']]['can_see_unapproved'] && !empty($context['zc']['blog_settings']['articles_require_approval']))
			$return[$row['article_id']]['approve_link'] = '<a title="' . ($return[$row['article_id']]['can_approve'] ? sprintf($txt['b290'], $txt['b170']) . '" href="' . $scripturl . '?zc=approvearticle' . $context['zc']['blog_request'] . ';article=' . $row['article_id'] . '.0;sesc=' . $context['session_id'] . ';from=' . (!empty($article) ? 'article,' . $article . ',' . $_REQUEST['start'] : (!empty($blog) ? 'blog,' . $blog . ',' . $_REQUEST['start'] : 'community')) : sprintf($txt['b289'], $txt['b170'])) . '"><img src="' . $context['zc']['default_images_url'] . '/icons/failure_icon.gif" alt="(!)" /></a>';
		
		// make the bookmarking links for this article
		if (!empty($in_context['socialbookmarks']['show']))
		{
			$subject = urlencode(strip_tags($row['subject']));
			$url = urlencode($scripturl . '?article=' . $row['article_id'] .'.0');
			$article_tags = urlencode(strip_tags($row['blog_tags']));
			
			zc_prepare_bookmarking_options_array();
			
			// load the bookmarking options...
			if (!empty($in_context['socialbookmarks']['sites']))
				foreach ($in_context['socialbookmarks']['sites'] as $site)
					if (!empty($context['zc']['bookmarking_options'][$site]['href']))
					{
						$href = strtr($context['zc']['bookmarking_options'][$site]['href'], array('$1' => $url, '$2' => $subject, '$3' => $article_tags));
						$return[$row['article_id']]['bookmarking_links'][] = '<a href="' . $href . '" title="' . $context['zc']['bookmarking_options'][$site]['name'] . '" target="_blank" rel="nofollow">' . $context['zc']['bookmarking_options'][$site]['icon'] . '</a>';
					}
		}
		
		// can they edit the article?
		if ($return[$row['article_id']]['can_edit'])
			$return[$row['article_id']]['options']['edit'] = '<a href="' . $scripturl . '?zc=post' . (empty($context['viewing_single_article']) ? '' : $context['zc']['blog_request']) . ';article=' . $row['article_id'] . '.0;from=' . (!empty($article) ? 'article,' . $article . ',' . $_REQUEST['start'] : (!empty($blog) ? 'blog,' . $blog . ',' . $_REQUEST['start'] : 'community')) . '" rel="nofollow" title="' . $txt['b47'] . '"><span class="edit_icon">&nbsp;</span></a>';
		
		// can they delete the article?
		if ($return[$row['article_id']]['can_delete'])
			$return[$row['article_id']]['options']['delete'] = '<a href="' . $scripturl . '?zc=deletearticle'. (empty($context['viewing_single_article']) ? '' : $context['zc']['blog_request']) .';article='. $row['article_id'] .'.0;sesc='. $context['session_id'] . ';from=' . (!empty($article) ? 'article,' . $article . ',' . $_REQUEST['start'] : (!empty($blog) ? 'blog,' . $blog . ',' . $_REQUEST['start'] : 'community')) .'" onclick="return confirm(\''. $txt['b45'] .'\');" rel="nofollow" title="'. $txt['b49'] .'"><span class="delete_icon">&nbsp;</span></a>';
			
		// can they lock the article?
		if ($return[$row['article_id']]['can_lock'])
			$return[$row['article_id']]['options']['lock'] = '<a href="' . $scripturl . '?zc=lockarticle'. (empty($context['viewing_single_article']) ? '' : $context['zc']['blog_request']) .';article=' . $row['article_id'] . '.0;sesc='. $context['session_id'] . ';from=' . (!empty($article) ? 'article,' . $article . ',' . $_REQUEST['start'] : (!empty($blog) ? 'blog,' . $blog . ',' . $_REQUEST['start'] : 'community')) .'" rel="nofollow" title="'. (empty($row['locked']) ? $txt['b55'] : $txt['b56']) .'"><span class="lock_icon">&nbsp;</span></a>';
			
		// can they send this article?
		/*if ($return[$row['article_id']]['can_send'])
			$return[$row['article_id']]['options']['send'] = '<a href="' . $scripturl . '?zc=sendarticle'. (empty($context['viewing_single_article']) ? '' : $context['zc']['blog_request']) .';article='. $row['article_id'] .'.0' . ';from=' . (!empty($article) ? 'article,' . $article . ',' . $_REQUEST['start'] : (!empty($blog) ? 'blog,' . $blog . ',' . $_REQUEST['start'] : 'community')) . '" rel="nofollow" title="'. $txt['b58'] .'"><span class="send_icon">&nbsp;</span></a>';*/
			
		// they can always print
		$return[$row['article_id']]['options']['print'] = '<a href="' . $scripturl . '?zc=printpage;article=' . $row['article_id'] . '.0" target="_blank" rel="nofollow" title="' . $txt['b59'] . '"><span class="print_icon">&nbsp;</span></a>';
		
		$context['zc']['extra_article_stuff']['options'] = array();
		$context['zc']['extra_article_stuff']['between_article_and_comments'] = array();
		$context['zc']['extra_article_stuff']['links'] = array();
		
		// plug-in slot #4
		zc_plugin_slot(4);
		
		// more options?
		if (!empty($context['zc']['extra_article_stuff']['options']))
			$return[$row['article_id']]['options'] += $context['zc']['extra_article_stuff']['options'];
			
		// stuff in between the article and its comments?
		if (!empty($context['zc']['extra_article_stuff']['between_article_and_comments']))
			$return[$row['article_id']]['extra']['between_article_and_comments'] += $context['zc']['extra_article_stuff']['between_article_and_comments'];
			
		// links below unapproved icon / options icon / share icon
		if (!empty($context['zc']['extra_article_stuff']['links']))
			$return[$row['article_id']]['extra']['links'] += $context['zc']['extra_article_stuff']['links'];
			
		// dont need these anymore
		unset($context['zc']['extra_article_stuff']['options'], $context['zc']['extra_article_stuff']['links'], $context['zc']['extra_article_stuff']['between_article_and_comments']);
	}
	$zcFunc['db_free_result']($request);
	
	return $return;
}

function zc_get_comments($art)
{
	global $scripturl, $txt, $context, $blog, $article, $zc, $in_context, $zcFunc;
		
	$limit_comments = empty($context['viewing_single_article']) ? $context['zc']['blog_settings']['max_comments_per_topic'] : '';
	$comment_length = isset($context['zc']['blog_settings']['max_length_comments']) ? $context['zc']['blog_settings']['max_length_comments'] : 400;
	$ascending = empty($context['user']['blog_preferences']['newest_comments_first']);
	$start = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;
	
	$maxindex = !empty($zc['settings']['maxIndexCommentList']) ? $zc['settings']['maxIndexCommentList'] : 8;
	if (empty($maxindex) || isset($_REQUEST['all']))
		$maxindex = 99999;
		
	if (!empty($context['viewing_single_article']))
		$context['zc']['articles'][$art]['page_index'] = zcConstructPageIndex($scripturl . '?article=' . $art . '.%d' . zcRequestVarsToString('start,article,blog', ';'), $start, $context['zc']['articles'][$art]['num_comments'], $maxindex, true);
	
	$context['zc']['articles'][$art]['show_page_index'] = !empty($context['zc']['articles'][$art]['num_comments']) && $context['zc']['articles'][$art]['num_comments'] > $maxindex;
	
	if (!empty($context['zc']['articles'][$art]['show_page_index']))
	{
		$context['zc']['articles'][$art]['show_all_comments_link'] = '<a href="' . $scripturl . '?article='. $art .';all#comments' . $art . '">'. $txt['b112'] .'</a>';
	}
	
	if (empty($context['viewing_single_article']) && !empty($context['zc']['articles'][$art]['num_comments']) && $context['zc']['articles'][$art]['num_comments'] > $limit_comments)
		$context['zc']['articles'][$art]['show_comments_link'] = '<a href="' . $scripturl . '?article='. $art .'.0#comments' . $art . '">'. $txt['b476'] .'</a>';
	
	$request = $zcFunc['db_query']("
		SELECT
			c.body, IFNULL(mem.{tbl:members::column:real_name}, c.poster_name) AS poster_name, c.posted_time, c.last_edit_time, c.poster_ip, c.last_edit_name, c.poster_id, c.smileys_enabled, c.comment_id, c.approved, mem.{tbl:members::column:id_member} AS id_member
		FROM {db_prefix}comments AS c
			LEFT JOIN {db_prefix}{table:members} AS mem ON (mem.{tbl:members::column:id_member} = c.poster_id)
		WHERE c.article_id = {int:article_id}" . (!empty($context['zc']['blog_settings']['comments_require_approval']) && !$context['can_approve_comments_in_any_b'] && !$context['can_moderate_blog'] ? "
			AND ((c.approved = 1)" . (!$context['user']['is_guest'] ? " OR (c.poster_id = {int:user_id})" : '') . ")" : '') . "
		GROUP BY c.comment_id
		ORDER BY c.posted_time" . ($ascending ? '' : " DESC") . (!empty($context['viewing_single_article']) ? "
		LIMIT {int:start}, {int:maxindex}" : '') . (!empty($limit_comments) ? "
		LIMIT {int:limit}" : ''), __FILE__, __LINE__,
		array(
			'start' => $start,
			'maxindex' => $maxindex,
			'limit' => $limit_comments,
			'article_id' => $art,
			'user_id' => $context['user']['id']
		)
	);
		
	$i = $ascending ? $start : ($context['zc']['articles'][$art]['num_comments'] + 1) - $start;
	$return = array();
	while ($row = $zcFunc['db_fetch_assoc']($request))
	{
		// comment # within this article...
		if (!$ascending)
			$i--;
		else
			$i++;
		
		$row['body'] = $zcFunc['un_htmlspecialchars']($row['body']);
		$row['body'] = $zcFunc['parse_bbc']($row['body'], $row['smileys_enabled'], $row['comment_id']);
	
		// truncate the body text if it needs to be
		if (!empty($comment_length) && empty($context['viewing_single_article']))
			$row['body'] = zcTruncateText($row['body'], $comment_length, ' ', 40, $txt['b31a'], $scripturl . '?article=' . $art . '.0#c' . $row['comment_id'], $txt['b31']);

		zc_censor_text($row['body']);
		
		// Did the user write this comment?
		$user_started = $row['poster_id'] == $context['user']['id'] && !$context['user']['is_guest'];
		$can_edit = !empty($blog) ? $context['can_moderate_blog'] || (($context['can_moderate_blog'] || empty($row['locked'])) && (((($context['can_edit_own_comments_in_own_b'] && $user_started) || $context['can_edit_any_comments_in_own_b']) && $context['is_blog_owner']) || ($context['can_edit_own_comments_in_any_b'] && $user_started) || $context['can_edit_any_comments_in_any_b'])) : $context['can_edit_any_news_comments'] || ($context['can_edit_own_news_comments'] && $user_started);
		$can_delete = !empty($blog) ? $context['can_moderate_blog'] || (($context['can_moderate_blog'] || empty($row['locked'])) && (((($context['can_delete_own_comments_in_own_b'] && $user_started) || $context['can_delete_any_comments_in_own_b']) && $context['is_blog_owner']) || ($context['can_delete_own_comments_in_any_b'] && $user_started) || $context['can_delete_any_comments_in_any_b'])) : $context['can_delete_any_news_comments'] || ($context['can_delete_own_news_comments'] && $user_started);

		$return[$row['comment_id']] = array(
			'id' => $row['comment_id'],
			'article_id' => $art,
			'time' => timeformat($row['posted_time'], false),
			'body' => $row['body'],
			'poster' => array(
				'id' => !empty($row['id_member']) ? $row['id_member'] : '',
				'ip' => !empty($row['poster_ip']) ? $row['poster_ip'] : '',
				'link' => !empty($row['id_member']) ? sprintf($context['zc']['link_templates']['user_profile'], $row['id_member'], $row['poster_name'], ' title="' . $txt['b41'] . '"') : $row['poster_name'],
				'avatar' => '',
			),
			'can_edit' => $can_edit,
			'can_delete' => $can_delete,
			'can_see_ip' => $context['can_moderate_site'] || $user_started,
			'modified' => array(
				'time' => timeformat($row['last_edit_time'], false),
				'name' => !empty($context['zc']['blog_settings']['show_last_edit_comments']) ? $row['last_edit_name'] : '',
			),
			'is_approved' => !empty($row['approved']),
			'can_see_unapproved' => $context['can_moderate_blog'] || $context['can_approve_comments_in_any_b'] || $user_started,
			'can_approve' => $context['can_moderate_blog'] || $context['can_approve_comments_in_any_b'],
			'more_links' => array(),
			'options' => array(),
			'comment_number' => !$context['user']['is_guest'] && !empty($context['user']['blog_preferences']['show_comment_numbers']) ? $i : '',
		);
		
		// approve link?
		if ($return[$row['comment_id']]['can_see_unapproved'] && !empty($context['zc']['blog_settings']['comments_require_approval']))
			$return[$row['comment_id']]['approve_link'] = '<a title="' . ($return[$row['comment_id']]['can_approve'] ? sprintf($txt['b290'], $txt['b171']) . '" href="' . $scripturl . '?zc=approvecomment' . $context['zc']['blog_request'] . ';article=' . $art . '.0;comment=' . $row['comment_id'] . ';sesc=' . $context['session_id'] . ';from=' . (!empty($article) ? 'article,' . $article . ',' . $_REQUEST['start'] : (!empty($blog) ? 'blog,' . $blog . ',' . $_REQUEST['start'] : 'community')) : sprintf($txt['b289'], $txt['b171'])) . '"><img src="' . $context['zc']['default_images_url'] . '/icons/failure_icon.gif" alt="(!)" /></a>';
		
		// let's populate the options array with things this user can do
		// if they are allowed to edit the comment, show a button for it
		if ($return[$row['comment_id']]['can_edit'])
			$return[$row['comment_id']]['options']['edit'] = '<a href="' . $scripturl . '?zc=post;comment=' . $row['comment_id'] . ';article=' . $art . '.' . $_REQUEST['start'] . ($context['viewing_single_article'] ? '' : ';blog=' . $blog . '') . ';sesc=' . $context['session_id'] . ';from=' . (!empty($article) ? 'article,' . $article . ',' . $_REQUEST['start'] : (!empty($blog) ? 'blog,' . $blog . ',' . $_REQUEST['start'] : 'community')) . '" rel="nofollow" title="' . $txt['b46'] . '"><span class="edit_icon">&nbsp;</span></a>';

		// if they are allowed to delete the message, show a button for it
		if ($return[$row['comment_id']]['can_delete'])
			$return[$row['comment_id']]['options']['delete'] = '<a href="' . $scripturl . '?zc=deletecomment;comment=' . $row['comment_id'] . ($context['viewing_single_article'] ? '' : $context['zc']['blog_request']) . $context['zc']['article_request'] . ';sesc=' . $context['session_id'] . ';from=' . (!empty($article) ? 'article,' . $article . ',' . $_REQUEST['start'] : (!empty($blog) ? 'blog,' . $blog . ',' . $_REQUEST['start'] : 'community')) . '" onclick="return confirm(\'' . $txt['b44'] . '\');" rel="nofollow" title="' . $txt['b48'] . '"><span class="delete_icon">&nbsp;</span></a>';	
			
		// now we're gonna populate the more_links array with things like "report to moderator"
		// Maybe they want to report this post to the moderator(s)?
		if ($context['can_report_to_moderator'] && !$user_started)
			$return[$row['comment_id']]['more_links'][] = '<a href="' . $scripturl . '?zc=reporttm' . ($context['viewing_single_article'] ? '' : $context['zc']['blog_request']) . $context['zc']['article_request'] .';comment=' . $row['comment_id'] . ';from=' . (!empty($article) ? 'article,' . $article . ',' . $_REQUEST['start'] : (!empty($blog) ? 'blog,' . $blog . ',' . $_REQUEST['start'] : 'community')) . '" rel="nofollow" title="' . $txt['b3055'] . '"><img src="' . $context['zc']['default_images_url'] . '/icons/warning_icon.gif" alt="' . $txt['b241'] . '" /></a>&nbsp;';
					
		// you can see the ip, because you can moderate
		if ($context['can_moderate_site'] && !empty($row['poster_ip']))
			$return[$row['comment_id']]['more_links'][] = sprintf($context['zc']['link_templates']['trackip'], $row['poster_ip'], '<img src="' . $context['zc']['default_images_url'] . '/icons/icon_ip.gif" alt="' . $row['poster_ip'] . '" />', ' rel="nofollow" title="' . $row['poster_ip'] . '"') . '&nbsp;&nbsp;<a href="' . $scripturl . '?zc=help;txt=zc_help_6" onclick="return reqWin(this.href);" class="help" rel="nofollow"><img src="' . $context['zc']['default_images_url'] . '/icons/question_icon.png" alt="(?)" /></a>';
		// Or, should we show it because this is you?
		elseif ($return[$row['comment_id']]['can_see_ip'])
			$return[$row['comment_id']]['more_links'][] = '<a href="' . $scripturl . '?zc=help;txt=zc_help_6" onclick="return reqWin(this.href);" class="help" rel="nofollow" title="' . $row['poster_ip'] . '"><img src="' . $context['zc']['default_images_url'] . '/icons/icon_ip.gif" alt="' . $row['poster_ip'] . '" /></a>&nbsp;<a href="' . $scripturl . '?zc;txt=zc_help_6" onclick="return reqWin(this.href);" class="help" rel="nofollow"><img src="' . $context['zc']['default_images_url'] . '/icons/question_icon.png" alt="(?)" /></a>';	
		// Okay, are you at least logged in?  Then we can show something about why IPs are logged...
		elseif (!$context['user']['is_guest'])
			$return[$row['comment_id']]['more_links'][] = '<a href="' . $scripturl . '?zc=help;txt=zc_help_6" onclick="return reqWin(this.href);" class="help" rel="nofollow" title="' . $txt['b3044'] . '"><img src="' . $context['zc']['default_images_url'] . '/icons/icon_ip.gif" alt="' . $txt['b3044'] . '" /></a>';
	}
	$zcFunc['db_free_result']($request);
	return $return;
}

?>