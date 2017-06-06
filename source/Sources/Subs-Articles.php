<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
function zcDeleteArticle($articles = null)
{
	global $context, $txt, $blog, $article, $blog_info, $zcFunc, $zc;
	
	checkSession('get');
	
	// if $blog is empty, this is community news...
	if (empty($blog) && !$context['can_delete_community_news'])
		zc_fatal_error(array('zc_error_17', 'b341'));
	
	// assemble array of articles to delete...
	if (!empty($articles) && is_array($articles))
		$articles = $articles;
	elseif (!empty($articles) && !is_array($articles))
		$articles = array($articles);
	elseif (!empty($_POST['articles']))
	{
		$articles = array();
		foreach ($_POST['articles'] as $id)
			$articles[] = (int) $id;
	}
	// just deleting a single article then?
	elseif (!empty($article))
		$articles = array($article);
	// nothing to delete!
	else
		zc_redirect_exit((!empty($blog_info['id']) ? 'blog=' . $blog_info['id'] . '.' . $_REQUEST['start'] : 'zc'));
	
	// nothing to delete!
	if (empty($articles))
		zc_redirect_exit((!empty($blog_info['id']) ? 'blog=' . $blog_info['id'] . '.' . $_REQUEST['start'] : 'zc'));
		
	$articles = array_unique($articles);
		
	// get info about the articles
	$request = $zcFunc['db_query']("
		SELECT 
			t.poster_id, t.article_id, t.blog_id, t.blog_category_id, t.locked, t.blog_tags, t.approved,
			b.num_articles AS blog_num_articles, b.num_comments AS blog_num_comments, b.last_article_id AS blog_last_article,
			bs.articles_require_approval
		FROM {db_prefix}articles AS t
			LEFT JOIN {db_prefix}blogs AS b ON (b.blog_id = t.blog_id)
			LEFT JOIN {db_prefix}settings AS bs ON (bs.blog_id = t.blog_id)
		WHERE t.article_id IN ({array_int:articles})
		LIMIT {int:limit}", __FILE__, __LINE__,
		array(
			'limit' => count($articles),
			'articles' => $articles
		)
	);
	
	// none of those articles exist!
	if ($zcFunc['db_num_rows']($request) == 0)
	{
		$zcFunc['db_free_result']($request);
		zcReturnToOrigin();
	}
	
	$update_blogs = array();
	$blogs = array();
	$temp_article_count = array();
	$update_blog_categories = array();
	$update_blog_tags = array();
	$community_total_comments = 0;
	$community_news_article_count = 0;
	
	while ($row = $zcFunc['db_fetch_assoc']($request))
	{
		$user_started = $row['poster_id'] == $context['user']['id'];
	
		if (!empty($blog))
			$can_do_this = $context['can_moderate_blog'] || ((empty($row['locked']) || $context['can_moderate_blog']) && ($context['can_delete_any_articles_in_any_b'] || ($context['can_delete_any_articles_in_own_b'] && $context['is_blog_owner']) || ($context['can_delete_own_articles_in_any_b'] && $user_started) || ($context['can_delete_own_articles_in_own_b'] && $context['is_blog_owner'] && $user_started)));
		else
			$can_do_this = $context['can_delete_community_news'];
		
		// if not allowed to delete... log an error, remove from articles array, and skip to the next article...
		if ($can_do_this !== true)
		{
			$context['zc']['errors']['c' . $row['article_id']] = array('zc_error_36', $txt['b127'] . ' ' . $txt['b170'] . ' ' . $row['article_id']);
			$articles = array_diff($articles, array($row['article_id']));
			$not_redirect = true;
			continue;
		}
		
		// are we going to have to update a blog_category?
		if (!empty($row['blog_category_id']))
		{
			if (!isset($update_blog_categories[$row['blog_category_id']]))
				$update_blog_categories[$row['blog_category_id']] = array();
				
			$update_blog_categories[$row['blog_category_id']]['total'] = !empty($update_blog_categories[$row['blog_category_id']]['total']) ? $update_blog_categories[$row['blog_category_id']]['total'] + 1 : 1;
		}
		
		// are we going to have to update any blog tags?
		if (!empty($row['blog_tags']))
		{
			if (!isset($update_blog_tags[$row['blog_id']]))
				$update_blog_tags[$row['blog_id']] = array();
		
			$tags = explode(',', $row['blog_tags']);
			foreach ($tags as $tag)
				$update_blog_tags[$row['blog_id']][$tag] = isset($update_blog_tags[$row['blog_id']][$tag]) ? $update_blog_tags[$row['blog_id']][$tag] + 1 : 1;
		}
		
		// we only do this stuff for actual blogs... blog = 0 means it's community news
		if (!empty($row['blog_id']))
		{
			if (!isset($update_blogs[$row['blog_id']]))
				$update_blogs[$row['blog_id']] = array();
			
			// we'll need to know how many articles we are deleting for each blog...
			$update_blogs[$row['blog_id']]['num_articles'] = !empty($update_blogs[$row['blog_id']]['num_articles']) ? $update_blogs[$row['blog_id']]['num_articles'] + 1 : 1;
			
			// number of unapproved articles...
			if (empty($row['approved']))
				$update_blogs[$row['blog_id']]['num_unapproved_articles'] = !empty($update_blogs[$row['blog_id']]['num_unapproved_articles']) ? $update_blogs[$row['blog_id']]['num_unapproved_articles'] + 1 : 1;
				
			if (!in_array($row['blog_id'], $blogs))
				$blogs[] = $row['blog_id'];
		}
		// community news... hmmmm?
		else
		{
			$community_news_comment_count = !empty($community_news_comment_count) ? $community_news_comment_count + $row['article_num_comments'] : $row['article_num_comments'];
			$community_news_article_count += 1;
		}
		
		if (!empty($row['article_num_comments']))
			$community_total_comments += $row['article_num_comments'];
	}
	$zcFunc['db_free_result']($request);
		
	// we'll need to update the community news comments count
	if (!empty($community_news_comment_count))
		$zc_updates['community_news_num_comments'] = !empty($zc['settings']['community_news_num_comments']) && $zc['settings']['community_news_num_comments'] >= $community_news_comment_count ? $zc['settings']['community_news_num_comments'] - $community_news_comment_count : 0;
		
	// we'll need to update the community news articles count
	if (!empty($community_news_article_count))
		$zc_updates['community_news_num_articles'] = !empty($zc['settings']['community_news_num_articles']) && $zc['settings']['community_news_num_articles'] >= $community_news_article_count ? $zc['settings']['community_news_num_articles'] - $community_news_article_count : 0;
		
	// total # of comments we are deleting?
	if (!empty($community_total_comments))
		$zc_updates['community_total_comments'] = !empty($zc['settings']['community_total_comments']) && $zc['settings']['community_total_comments'] >= $community_total_comments ? $zc['settings']['community_total_comments'] - $community_total_comments : 0;
	
	// gotta be at least one article we can delete!
	if (!empty($articles))
	{
		$total_articles = count($articles);
	
		// total # of articles we are deleting?
		$zc_updates['community_total_articles'] = !empty($zc['settings']['community_total_articles']) && $zc['settings']['community_total_articles'] >= $total_articles ? $zc['settings']['community_total_articles'] - $total_articles : 0;
		
		// delete the articles
		$zcFunc['db_query']("
			DELETE FROM {db_prefix}articles
			WHERE article_id IN ({array_int:articles})
			LIMIT {int:limit}", __FILE__, __LINE__,
			array(
				'limit' => count($articles),
				'articles' => $articles
			)
		);
			
		// get all the comment IDs of the comments to these articles and whether each one is approved...
		$request = $zcFunc['db_query']("
			SELECT comment_id, approved, blog_id
			FROM {db_prefix}comments
			WHERE article_id IN ({array_int:articles})", __FILE__, __LINE__,
			array(
				'articles' => $articles
			)
		);
		$comments = array();
		while ($row = $zcFunc['db_fetch_assoc']($request))
		{
			$comments[] = $row['comment_id'];
			
			if (!isset($update_blogs[$row['blog_id']]))
				$update_blogs[$row['blog_id']] = array();
				
			$update_blogs[$row['blog_id']]['num_comments'] = !empty($update_blogs[$row['blog_id']]['num_comments']) ? $update_blogs[$row['blog_id']]['num_comments'] + 1 : 1;
				
			$update_blogs[$row['blog_id']]['num_unapproved_comments'] = !empty($update_blogs[$row['blog_id']]['num_unapproved_comments']) ? $update_blogs[$row['blog_id']]['num_unapproved_comments'] + 1 : 1;
		}
		$zcFunc['db_free_result']($request);
			
		// delete all the comments to these articles...
		$zcFunc['db_query']("
			DELETE FROM {db_prefix}comments
			WHERE article_id IN ({array_int:articles})", __FILE__, __LINE__,
			array(
				'articles' => $articles
			)
		);
		
		// check to see if any of the comments we deleted were the last_comment in any of their blogs...
		if (!empty($comments) && !empty($blogs))
		{
			$request = $zcFunc['db_query']("
				SELECT blog_id
				FROM {db_prefix}blogs
				WHERE blog_id IN ({array_int:blogs})
					AND last_comment_id IN ({array_int:comments})
				LIMIT {int:limit}", __FILE__, __LINE__,
				array(
					'limit' => count($blogs),
					'blogs' => $blogs,
					'comments' => $comments
				)
			);
			while ($row = $zcFunc['db_fetch_assoc']($request))
				$blogs_need_fix[] = $row['blog_id'];
			$zcFunc['db_free_result']($request);
			
			if (!empty($blogs_need_fix))
				foreach ($blogs_need_fix as $blog_id)
				{
					// get new last_comment
					$request = $zcFunc['db_query']("
						SELECT comment_id
						FROM {db_prefix}comments
						WHERE blog_id = {int:blog_id}
						ORDER BY posted_time DESC
						LIMIT 1", __FILE__, __LINE__,
						array(
							'blog_id' => $blog_id
						)
					);
					if ($zcFunc['db_num_rows']($request) > 0)
						$row = $zcFunc['db_fetch_assoc']($request);
					$zcFunc['db_free_result']($request);
					
					if (!isset($update_blogs[$blog_id]))
						$update_blogs[$blog_id] = array();
						
					$last_comment_id = !empty($row['comment_id']) ? $row['comment_id'] : 0;
					$update_blogs[$blog_id]['last_comment_id'] = $last_comment_id;
				}
		}
		
		// check to see if any of the articles we deleted were the last_article in any of their blogs...
		if (!empty($blogs))
		{
			$request = $zcFunc['db_query']("
				SELECT blog_id
				FROM {db_prefix}blogs
				WHERE blog_id IN ({array_int:blogs})
					AND last_article_id IN ({array_int:articles})
				LIMIT {int:limit}", __FILE__, __LINE__,
				array(
					'limit' => count($blogs),
					'blogs' => $blogs,
					'articles' => $articles,
				)
			);
			while ($row = $zcFunc['db_fetch_assoc']($request))
				$blogs_need_fix[] = $row['blog_id'];
			$zcFunc['db_free_result']($request);
			
			if (!empty($blogs_need_fix))
				foreach ($blogs_need_fix as $blog_id)
				{
					// get new last_article
					$request = $zcFunc['db_query']("
						SELECT article_id
						FROM {db_prefix}articles
						WHERE blog_id = {int:blog_id}
						ORDER BY posted_time DESC
						LIMIT 1", __FILE__, __LINE__,
						array(
							'blog_id' => $blog_id
						)
					);
					if ($zcFunc['db_num_rows']($request) > 0)
						$row = $zcFunc['db_fetch_assoc']($request);
					$zcFunc['db_free_result']($request);
					
					if (!isset($update_blogs[$blog_id]))
						$update_blogs[$blog_id] = array();
						
					$last_article_id = !empty($row['article_id']) ? $row['article_id'] : 0;
					$update_blogs[$blog_id]['last_article_id'] = $last_article_id;
				}
				
			// update each blog...
			if (!empty($update_blogs))
				foreach ($update_blogs as $blog_id => $updates)
					if (!empty($updates))
						$zcFunc['db_update'](
							'{db_prefix}blogs',
							array('blog_id' => 'int', 'last_article_id' => 'int', 'last_comment_id' => 'int', 'num_articles' => 'int', 'num_comments' => 'int', 'num_unapproved_articles' => 'int', 'num_unapproved_comments' => 'int'),
							$updates,
							array('blog_id' => $blog_id));
		}
			
		// update each blog category...
		if (!empty($update_blog_categories))
			foreach ($update_blog_categories as $blog_category_id => $updates)
				if (!empty($updates))
					$zcFunc['db_update'](
						'{db_prefix}categories',
						array('blog_category_id' => 'int'),
						$updates,
						array('blog_category_id' => $blog_category_id));
	
		// update blog tags....
		if (!empty($update_blog_tags))
			foreach ($update_blog_tags as $blog_id => $update_tags)
				foreach ($update_tags as $tag => $num_articles)
					$zcFunc['db_update'](
						'{db_prefix}tags',
						array('blog_id' => 'int', 'tag' => 'string', 'num_articles' => 'int'),
						array('num_articles' => array('-', $num_articles)),
						array('blog_id' => $blog_id, 'tag' => $tag));
		
		// delete tags that have num_articles < 1
		$zcFunc['db_query']("
			DELETE FROM {db_prefix}tags
			WHERE num_articles < 1", __FILE__, __LINE__);
						
		// update community news article count and overall community articles count
		if (!empty($zc_updates))
			zcUpdateGlobalSettings($zc_updates);
			
		// delete logs...
		$zcFunc['db_query']("
			DELETE FROM {db_prefix}log_articles
			WHERE article_id IN ({array_int:articles})
			LIMIT {int:limit}", __FILE__, __LINE__,
			array(
				'limit' => count($articles),
				'articles' => $articles
			)
		);
	}
	
	if (!empty($not_redirect))
	{
		if (!empty($_REQUEST['blog']))
			unset($_REQUEST['article']);
			
		$_REQUEST['zc'] = '';
		return zC_START();
	}
	else
		zc_redirect_exit((!empty($blog_info['id']) ? 'blog=' . $blog_info['id'] . '.' . $_REQUEST['start'] : 'zc'));
}

function zcCreateArticle($processed)
{
	if (empty($processed))
		zc_fatal_error();
		
	global $context, $txt, $blog, $zc, $zcFunc;
		
	$processed['blog_id'] = $blog;
	$processed['poster_email'] = !empty($processed['poster_email']) ? $processed['poster_email'] : $context['user']['email'];
	$processed['poster_name'] = !empty($processed['poster_name']) ? $processed['poster_name'] : $context['user']['name'];
	$processed['poster_ip'] = $context['user']['ip'];
	$processed['poster_id'] = !empty($context['user']['id']) ? $context['user']['id'] : 0;
	$processed['approved'] = $context['can_moderate_blog'] || $context['can_approve_articles_in_any_b'] ? 1 : 0;
	$processed['posted_time'] = time();
	$processed['month'] = (int) strftime('%m', time());
	$processed['year'] = (int) strftime('%Y', time());
	
	if (!empty($processed['access_restrict']) && $processed['access_restrict'] == 3 && !empty($processed['users_allowed']))
	{
		$request = $zcFunc['db_query']("
			SELECT {tbl:members::column:id_member} AS member_id
			FROM {db_prefix}{table:members}
			WHERE {tbl:members::column:real_name} IN ({array_string:users})
				OR {tbl:members::column:member_name} IN ({array_string:users})
			LIMIT {int:limit}", __FILE__, __LINE__,
			array(
				'limit' => count(explode(',', $processed['users_allowed'])),
				'users' => explode(',', $processed['users_allowed'])
			)
		);
		$users_allowed = array();
		while ($row = $zcFunc['db_fetch_assoc']($request))
			$users_allowed[] = $row['member_id'];
		$processed['users_allowed'] = implode(',', array_unique($users_allowed));
	}
	else
		$processed['users_allowed'] = '';
	
	// don't need these anymore...
	if (!empty($context['zc']['form_info']['_info_']['exclude_from_table']))
		foreach($context['zc']['form_info']['_info_']['exclude_from_table'] as $k)
			unset($processed[$k]);
		
	$columns = array();
	foreach ($processed as $k => $dummy)
		$columns[$k] = isset($context['zc']['form_info'][$k]['type']) ? $context['zc']['form_info'][$k]['type'] : 'string';
		
	// inserts the article into the database
	$zcFunc['db_insert']('insert', '{db_prefix}articles', $columns, $processed);
	$article_id = $zcFunc['db_insert_id']();
	
	if (empty($article_id))
		zc_fatal_error();
		
	// + 1 to blog_category if they chose one...
	if (!empty($processed['blog_category_id']))
		$zcFunc['db_update'](
			'{db_prefix}categories',
			array('blog_id' => 'int', 'blog_category_id' => 'int', 'total' => 'int'),
			array('total' => array('+', 1)),
			array('blog_id' => $blog, 'blog_category_id' => $processed['blog_category_id']));
			
	$new_tags = !empty($processed['blog_tags']) ? explode(',', $processed['blog_tags']) : array();
			
	// update blog_tags table for the new tags
	if (!empty($new_tags))
	{
		$info = array('limit' => count($new_tags), 'blog_id' => $blog);
		$conditions = array();
		foreach ($new_tags as $k => $new_tag)
		{
			$info[$k] = $new_tag;
			$conditions[] = '(tag = {string:' . $k . '})';
		}
	
		$tags = array();
		// get the tags that already exist in the table....
		if (!empty($conditions))
		{
			$request = $zcFunc['db_query']("
				SELECT tag
				FROM {db_prefix}tags
				WHERE blog_id = {int:blog_id}
					AND (" . implode(' OR ', $conditions) . ")
				LIMIT {int:limit}", __FILE__, __LINE__, $info);
			while ($row = $zcFunc['db_fetch_assoc']($request))
				$tags[] = $row['tag'];
			$zcFunc['db_free_result']($request);
	
			// +1 to num_articles for the tags that already exist
			$zcFunc['db_query']("
				UPDATE {db_prefix}tags
				SET num_articles = num_articles + 1
				WHERE blog_id = {int:blog_id}
					AND (" . implode(' OR ', $conditions) . ")
				LIMIT {int:limit}", __FILE__, __LINE__,
				array_merge($info, array('limit' => count($tags), 'blog_id' => $blog))
			);
		}
			
		// make array of tags that are not already in the table...
		$new_tags = array_diff($new_tags, $tags);
	
		$columns = array('blog_id' => 'int', 'tag' => 'string', 'num_articles' => 'int');
		$data = array();
		foreach ($new_tags as $tag)
			$data[] = array(
				'blog_id' => $blog,
				'tag' => $tag,
				'num_articles' => 1
			);
			
		// insert brand new tags...
		if (!empty($data))
			$zcFunc['db_insert']('insert', '{db_prefix}tags', $columns, $data);
	}
	
	// update last_article_id and num_articles for blog...
	if (!empty($blog))
		$zcFunc['db_update'](
			'{db_prefix}blogs',
			array('blog_id' => 'int', 'last_article_id' => 'int', 'num_articles' => 'int', 'num_unapproved_articles' => 'int'),
			array_merge(
				array('num_articles' => array('+', 1), 'last_article_id' => $article_id),
				empty($processed['approved']) ? array('num_unapproved_articles' => array('+', 1)) : array()
			),
			array('blog_id' => $blog));
		
	// was a draft used to create this article and does the user want to delete that draft upon posting?
	if (!empty($_POST['draft_id']) && !empty($_POST['delete_draft']))
		$zcFunc['db_query']("
			DELETE FROM {db_prefix}drafts
			WHERE poster_id = {int:user_id}
				AND draft_id = {int:draft_id}
			LIMIT 1", __FILE__, __LINE__,
			array(
				'draft_id' => (int) $_POST['draft_id'],
				'user_id' => $context['user']['id']
			)
		);
	
	$updates = array();
	$updates['max_article_id'] = $article_id;
	$updates['community_total_articles'] = (!empty($zc['settings']['community_total_articles']) ? $zc['settings']['community_total_articles'] + 1 : 1);
	
	// add one to community news articles total?
	if (empty($blog))
		$updates['community_news_num_articles'] = (!empty($zc['settings']['community_news_num_articles']) ? $zc['settings']['community_news_num_articles'] + 1 : 1);
	
	// update global settings....
	zcUpdateGlobalSettings($updates);
		
	require_once($zc['sources_dir'] . '/Notify.php');
		
	// sends notifications to users who may be watching this blog...
	zcSendNotifications($processed['subject'], $processed['body']);
			
	// redirect to new article page
	zc_redirect_exit('article=' . $article_id . '.0');
}
	
function zcUpdateArticle($processed)
{
	global $context, $txt, $article, $blog, $zcFunc;
	
	if (empty($processed) || empty($article))
		zc_fatal_error();
		
	if (empty($context['zc']['no_last_edit']))
	{
		$processed['last_edit_name'] = $context['user']['is_guest'] ? $txt['b567'] : $context['user']['name'];
		$processed['last_edit_time'] = time();
	}
	
	if (!empty($processed['access_restrict']) && $processed['access_restrict'] == 3 && !empty($processed['users_allowed']))
	{
		$request = $zcFunc['db_query']("
			SELECT {tbl:members::column:id_member} AS member_id
			FROM {db_prefix}{table:members}
			WHERE {tbl:members::column:real_name} IN ({array_string:users})
				OR {tbl:members::column:member_name} IN ({array_string:users})
			LIMIT {int:limit}", __FILE__, __LINE__,
			array(
				'limit' => count(explode(',', $processed['users_allowed'])),
				'users' => explode(',', $processed['users_allowed'])
			)
		);
		$users_allowed = array();
		while ($row = $zcFunc['db_fetch_assoc']($request))
			$users_allowed[] = $row['member_id'];
		$processed['users_allowed'] = implode(',', array_unique($users_allowed));
	}
	else
		$processed['users_allowed'] = '';
	
	// don't need these anymore...
	if (!empty($context['zc']['form_info']['_info_']['exclude_from_table']))
		foreach($context['zc']['form_info']['_info_']['exclude_from_table'] as $k)
			unset($processed[$k]);
	
	$columns = array('article_id' => 'int');
	foreach ($processed as $k => $dummy)
		$columns[$k] = $columns[$k] = isset($context['zc']['form_info'][$k]['type']) ? $context['zc']['form_info'][$k]['type'] : 'string';
		
	// get info about article before updating...
	$request = $zcFunc['db_query']("
		SELECT blog_category_id, blog_tags
		FROM {db_prefix}articles
		WHERE article_id = {int:article_id}
		LIMIT 1", __FILE__, __LINE__,
		array(
			'article_id' => $article
		)
	);
	if ($zcFunc['db_num_rows']($request) > 0)
		$row = $zcFunc['db_fetch_assoc']($request);
		
	$zcFunc['db_update'](
		'{db_prefix}articles',
		$columns,
		$processed,
		array('article_id' => $article));
		
	$old_blog_category_id = !empty($row['blog_category_id']) ? $row['blog_category_id'] : 0;
	$old_blog_tags = !empty($row['blog_tags']) ? explode(',', $row['blog_tags']) : array();
	$new_blog_tags = !empty($processed['blog_tags']) ? explode(',', $processed['blog_tags']) : array();
		
	// they changed something with this article's blog_category
	if (isset($processed['blog_category_id']) && $old_blog_category_id != $processed['blog_category_id'])
	{
		// - 1 to old blog_category if its ID is not 0
		if (!empty($old_blog_category_id))
			$zcFunc['db_update'](
				'{db_prefix}categories',
				array('blog_id' => 'int', 'blog_category_id' => 'int', 'total' => 'int'),
				array('total' => array('-', 1)),
				array('blog_id' => $blog, 'blog_category_id' => $old_blog_category_id));
			
		// + 1 to new blog_category if they chose one...
		if (!empty($processed['blog_category_id']))
			$zcFunc['db_update'](
				'{db_prefix}categories',
				array('blog_id' => 'int', 'blog_category_id' => 'int', 'total' => 'int'),
				array('total' => array('+', 1)),
				array('blog_id' => $blog, 'blog_category_id' => $processed['blog_category_id']));
	}
	
	$new_tags = array_diff($new_blog_tags, $old_blog_tags);
	$obsolete_tags = array_diff($old_blog_tags, $new_blog_tags);
	
	// they changed something with this article's blog_tags?
	if (!empty($new_tags) || !empty($obsolete_tags))
	{
		// update blog_tags table for the obsolete tags
		if (!empty($obsolete_tags))
		{
			$info = array('blog_id' => $blog, 'limit' => count($obsolete_tags));
			$conditions = array();
			foreach ($obsolete_tags as $k => $obsolete_tag)
			{
				$conditions[] = '(tag = {string:tag' . $k . '})';
				$info['tag' . $k] = $obsolete_tag;
			}
			
			if (!empty($conditions))
				$zcFunc['db_query']("
					UPDATE {db_prefix}tags
					SET num_articles = num_articles - 1
					WHERE blog_id = {int:blog_id}
						AND (" . implode(' OR ', $conditions) . ")
					LIMIT {int:limit}", __FILE__, __LINE__, $info);
				
			// delete tags that have 0 or fewer num_articles...
			$zcFunc['db_query']("
				DELETE FROM {db_prefix}tags
				WHERE num_articles < 1
					AND blog_id = {int:blog_id}", __FILE__, __LINE__,
				array(
					'blog_id' => $blog
				)
			);
		}
		
		// update blog_tags table for the new tags
		if (!empty($new_tags))
		{
			$info = array('blog_id' => $blog, 'limit' => count($new_tags));
			$conditions = array();
			foreach ($new_tags as $k => $new_tag)
			{
				$conditions[] = '(tag = {string:tag' . $k . '})';
				$info['tag' . $k] = $new_tag;
			}
			
			// get the tags that already exist in the table....
			if (!empty($conditions))
			{
				$request = $zcFunc['db_query']("
					SELECT tag
					FROM {db_prefix}tags
					WHERE blog_id = {int:blog_id}
						AND (" . implode(' OR ', $conditions) . ")
					LIMIT {int:limit}", __FILE__, __LINE__, $info);
				$tags = array();
				while ($row = $zcFunc['db_fetch_assoc']($request))
					$tags[] = $row['tag'];
				$zcFunc['db_free_result']($request);
				
				$info['limit'] = count($tags);
			
				// update the tags that are already in the table...
				$zcFunc['db_query']("
					UPDATE {db_prefix}tags
					SET num_articles = num_articles + 1
					WHERE blog_id = {int:blog_id}
						AND (" . implode(' OR ', $conditions) . ")
					LIMIT {int:limit}", __FILE__, __LINE__, $info);
			}
				
			// make array of tags that are not already in the table...
			$new_tags = array_diff($new_tags, $tags);
	
			$columns = array('blog_id' => 'int', 'tag' => 'string', 'num_articles' => 'int');
			$data = array();
			foreach ($new_tags as $tag)
				$data[] = array(
					'blog_id' => $blog,
					'tag' => $tag,
					'num_articles' => 1
				);
				
			// insert brand new tags...
			if (!empty($data))
				$zcFunc['db_insert']('insert', '{db_prefix}tags', $columns, $data);
		}
	}
	
	$_SESSION['zc_success_msg'] = 'zc_success_1';
			
	// redirect back to where they came from...
	zcReturnToOrigin();
}

function zcApproveArticle()
{
	global $txt, $context, $article, $blog, $zcFunc;
	
	if (!empty($article))
	{
		checkSession('get');
		
		// are they allowed to approve articles?
		if (!$context['can_moderate_blog'] && !$context['can_approve_articles_in_any_b'])
			zc_fatal_error(array('zc_error_21', 'b129'));
			
		$approved = $_REQUEST['zc'] == 'approvearticle' ? 1 : 0;
		
		// let's approve/unapprove the article
		$zcFunc['db_update'](
			'{db_prefix}articles',
			array('article_id' => 'int', 'approved' => 'int'),
			array('approved' => $approved),
			array('article_id' => $article));
			
		// update num_unapproved_articles in this blog...
		$zcFunc['db_update'](
			'{db_prefix}blogs',
			array('blog_id' => 'int', 'num_unapproved_articles' => 'int'),
			array('num_unapproved_articles' => array($approved ? '-' : '+', 1)),
			array('blog_id' => $blog));
	}
			
	// redirect back to where they came from...
	zcReturnToOrigin();
}

function zcLockUnlockArticle()
{
	global $context, $txt, $article, $zcFunc;
	
	checkSession('get');
	
	// nothing to lock!
	if (empty($article))
		zcReturnToOrigin();
		
	// get info about this article
	$request = $zcFunc['db_query']("
		SELECT poster_id, locked
		FROM {db_prefix}articles
		WHERE article_id = {int:article_id}
		LIMIT 1", __FILE__, __LINE__,
		array(
			'article_id' => $article
		)
	);
	
	// article didn't exist!
	if ($zcFunc['db_num_rows']($request) == 0)
	{
		$zcFunc['db_free_result']($request);
		zcReturnToOrigin();
	}
	$row = $zcFunc['db_fetch_assoc']($request);
	$zcFunc['db_free_result']($request);
				
	$user_started = $row['poster_id'] == $context['user']['id'];

	$can_do_this = $context['can_moderate_blog'] || (($context['can_lock_any_articles_in_any_b'] || ($context['can_lock_any_articles_in_own_b'] && $context['is_blog_owner']) || ($context['can_lock_own_articles_in_any_b'] && $user_started) || ($context['can_lock_own_articles_in_own_b'] && $context['is_blog_owner'] && $user_started)));
	
	// they are not allowed to lock this article!
	if ($can_do_this !== true)
		zc_fatal_error(array('zc_error_17', 'b201'));
		
	$locked = empty($row['locked']) ? 1 : 0;
		
	// lock/unlock article...
	$zcFunc['db_update'](
		'{db_prefix}articles',
		array('article_id' => 'int', 'locked' => 'int'),
		array('locked' => $locked),
		array('article_id' => $article));
			
	// redirect back to where they came from...
	zcReturnToOrigin();
}

function zcGetListOfArticles()
{
	global $context, $txt, $scripturl, $blog, $zcFunc, $zc;
	
	$list_info = array();
	
	$zcRequests = !empty($blog) ? '?blog='. $blog . '.0' : (empty($context['zc']['zCommunity_is_home']) ? '?zc' : '');
	if (!empty($_REQUEST['listStart']))
		$zcRequests .= (!empty($zcRequests) ? ';' : '?') . 'listStart=' . $_REQUEST['listStart'];
	
	if (!empty($_REQUEST['category']))
	{
		// sanitize the request variable
		$blog_category_id = (int) $_REQUEST['category'];
		
		$zcRequests .= (!empty($zcRequests) ? ';' : '?') . 'category=' . $_REQUEST['category'];
		
		$title = '';
		
		if (!empty($context['zc']['categories'][$blog_category_id]))
		{
			$title = $context['zc']['categories'][$blog_category_id]['name'];
			$context['zc']['link_tree']['action'] = '<b>' . $txt['b16'] . ':</b>&nbsp;&nbsp;<a href="' . $scripturl . '?' . (!empty($blog) ? 'blog=' . $blog . '.0;' : (empty($context['zc']['zCommunity_is_home']) ? 'zc;' : '')) . 'category=' . $blog_category_id . '">' . $title . '</a>';
		}
		else
			$context['zc']['error'] = 'zc_error_65';
		
		if (!empty($title))
			$extra_title = $txt['b16'] . ':&nbsp;&nbsp;' . $title;
	}
	elseif (!empty($_REQUEST['date']))
	{
		// start with false...
		$date_valid = false;
		
		$date = explode('_', $_REQUEST['date']);
		
		$zcRequests .= (!empty($zcRequests) ? ';' : '?') . 'date=' . $_REQUEST['date'];
		
		// reindex and clean the values...
		$date = array(
			'day' => isset($date[2]) ? (int) $date[2] : 0,
			'month' => isset($date[1]) ? (int) $date[1] : 0,
			'year' => isset($date[0]) ? (int) $date[0] : 0,
		);
			
		$first_month = $date['month'];
		$last_month = $date['month'];
		$first_day = $date['day'];
		$last_day = $date['day'];
		
		// numeric values for first and last months...	
		if (empty($date['month']))
		{
			$first_month = 1;
			$last_month = 12;
			$date['day'] = 0;
			$first_day = 1;
			$last_day = 31;
		}
		// numeric values for the first and last days...
		elseif (empty($date['day']))
		{
			$first_day = 1;
			// months with 31 days...
			if (in_array($date['month'], array(1,3,5,7,8,10,12)))
				$last_day = 31;
			// months with 30 days...
			elseif (in_array($date['month'], array(4,6,9,11)))
				$last_day = 30;
			// 29 days o rly?
			elseif (($date['year'] % 4) == 0)
				$last_day = 29;
			// month with 28 days...
			else
				$last_day = 28;
		}
			
		// check the date
		$date_valid = checkdate($last_month, $last_day, $date['year']) && checkdate($first_month, $first_day, $date['year']);
		
		// invalid date given?
		if (empty($date_valid))
			zc_fatal_error('zc_error_43');
		else
		{
			$title = (!empty($date['month']) ? $txt['months_titles'][$date['month']] : '') . (!empty($date['day']) ? '&nbsp;' . $date['day'] . ',' : '') . (!empty($date['month']) ? '&nbsp;' : '') . $date['year'];
			
			// timestamp for last second of given date...
			$date['last_timestamp'] = mktime(23, 59, 59, $last_month, $last_day, $date['year']);
			
			// timestamp for first second of given date...
			$date['start_timestamp'] = mktime(0, 0, 0, $first_month, $first_day, $date['year']);
			
			$context['zc']['link_tree']['action'] = '<b>' . $txt['b22'] . ':</b>&nbsp;&nbsp;<a href="' . $scripturl . '?' . (!empty($blog) ? 'blog=' . $blog . '.0;' : (empty($context['zc']['zCommunity_is_home']) ? 'zc;' : '')) . 'date=' . $_REQUEST['date'] . '">' . $title . '</a>';
		}
		if (!empty($title))
			$extra_title = $txt['b22'] . ':&nbsp;&nbsp;' . $title;
	}
	elseif (!empty($_REQUEST['tag']))
	{
		// urldecode then process the tag so we can check for its occurrances in the database table...
		$tag = addslashes($zcFunc['htmlspecialchars'](urldecode($_REQUEST['tag']), ENT_QUOTES));
		$title = strip_tags($zcFunc['un_htmlspecialchars']($tag));
		$context['zc']['link_tree']['action'] = $txt['b26'] . ': <a href="' . $scripturl . '?' . (!empty($blog) ? 'blog=' . $blog . '.0;' : (empty($context['zc']['zCommunity_is_home']) ? 'zc;' : '')) . 'tag='. urlencode($tag) .'">' . $title . '</a>';
		
		$zcRequests .= (!empty($zcRequests) ? ';' : '?') . 'tag=' . urlencode(urldecode($_REQUEST['tag']));
		
		if (!empty($title))
			$extra_title = $txt['b26'] . ':&nbsp;&nbsp;' . $title;
	}
	// not looking for a list of articles....
	else
		return false;
	
	// add title to page title...
	if (!empty($extra_title))
		// redo page_title if $blog is empty...
		if (empty($blog))
			$context['page_title'] = $context['zc']['site_name'] . ' - ' . $extra_title;
		else
			$context['page_title'] .= ' - ' . $extra_title;
	
	// make sure we have this info if we need it...
	if (empty($blog) && !isset($context['zc']['visible_blogs']))
		$context['zc']['visible_blogs'] = zc_get_visible_blogs();
		
	$info = array('blogs' => !empty($context['zc']['visible_blogs']) ? $context['zc']['visible_blogs'] : array(), 'start_timestamp' => !empty($date['start_timestamp']) ? $date['start_timestamp'] : 0, 'last_timestamp' => !empty($date['last_timestamp']) ? $date['last_timestamp'] : 0, 'blog_category_id' => !empty($blog_category_id) ? $blog_category_id : 0, 'user_id' => $context['user']['id'], 'blog_id' => $blog);
	
	if (!empty($tag))
		$info += array('tag0' => '%,' . $tag . ',%', 'tag1' => $tag . ',%', 'tag2' => '%,' . $tag, 'tag3' => $tag, 'tag4' => '% ' . $tag . ',%', 'tag5' => '% ' . $tag . ' %', 'tag6' => '%,' . $tag . ' %');
		
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

	// let's see how many articles there are that meet our criteria...
	$request = $zcFunc['db_query']("
		SELECT COUNT(t.article_id)
		FROM {db_prefix}articles AS t" . (empty($blog) ? "
			LEFT JOIN {db_prefix}settings AS bs ON (bs.blog_id = t.blog_id)" : '') . (!$context['user']['is_admin'] && !$context['user']['is_guest'] ? "
			LEFT JOIN {db_prefix}{table:members} AS mem ON (mem.{tbl:members::column:id_member} = t.poster_id)" : '') . "
		WHERE " . (empty($blog) ? "t.blog_id IN ({array_int:blogs})
			AND ((t.approved = 1) OR (bs.articles_require_approval = 0))" : "t.blog_id = {int:blog_id}" . (!empty($context['zc']['blog_settings']['articles_require_approval']) ? "
			AND t.approved = 1" : '')) . (!empty($date['last_timestamp']) && !empty($date['start_timestamp']) ? "
			AND t.posted_time <= {int:last_timestamp}
			AND t.posted_time >= {int:start_timestamp}" : '') . (!empty($blog_category_id) ? "
			AND t.blog_category_id = {int:blog_category_id}" : '') . (!empty($tag) ? "
			AND ((t.blog_tags LIKE {string:tag0}) OR (t.blog_tags LIKE {string:tag1}) OR (t.blog_tags LIKE {string:tag2}) OR (t.blog_tags = {string:tag3}))
			AND t.blog_tags NOT LIKE {string:tag4}
			AND t.blog_tags NOT LIKE {string:tag5}
			AND t.blog_tags NOT LIKE {string:tag6}" : '') . $access_restrict_query, __FILE__, __LINE__, $info);
	list($num_articles) = $zcFunc['db_fetch_row']($request);
	$zcFunc['db_free_result']($request);
	
	$start = isset($_REQUEST['listStart']) ? (int) $_REQUEST['listStart'] : 0;
	
	// View all the articles, or just a few?
	$maxindex = isset($_REQUEST['all']) && !empty($zc['settings']['allow_show_all_link']) ? 99999 : min($zc['settings']['maxIndexArticleList'], $num_articles);
	
	$list_info['show_page_index'] = !empty($num_articles) && $num_articles > $maxindex;
		
	if ($list_info['show_page_index'])
	{
		$list_info['page_index'] = zcConstructPageIndex($scripturl . '?listStart=%d' . zcRequestVarsToString('all,listStart', ';'), $start, $num_articles, $maxindex, true);
	
		if (!empty($zc['settings']['allow_show_all_link']))
			$list_info['show_all_link'] = '<a href="' . $scripturl . '?all' . zcRequestVarsToString('listStart', ';') .'">'. $txt['b81'] .'</a>';
	}

	// specific blog...
	if (!empty($blog))
	{
		// Default sort methods.
		$sort_methods = array(
			'subject' => 't.subject',
			'comments' => 't.num_comments',
			'views' => 't.num_views',
			'author' => 't.poster_name',
			'date' => 't.posted_time',
		);
	
		if (!isset($_REQUEST['sort']) || !isset($sort_methods[$_REQUEST['sort']]) || (!empty($context['zc']['blog_settings']['articleListDefaultSort']) && !isset($sort_methods[$context['zc']['blog_settings']['articleListDefaultSort']])))
		{
			$sort_by = !empty($context['zc']['blog_settings']['articleListDefaultSort']) ? $context['zc']['blog_settings']['articleListDefaultSort'] : 'date';
			$sort = !empty($context['zc']['blog_settings']['articleListDefaultSort']) && !empty($sort_methods[$context['zc']['blog_settings']['articleListDefaultSort']]) ? $sort_methods[$context['zc']['blog_settings']['articleListDefaultSort']] : 't.article_id';
			$ascending = !isset($_REQUEST['asc']) && !isset($_REQUEST['desc']) ? !empty($context['zc']['blog_settings']['articleListSortAscending']) : isset($_REQUEST['asc']);
		}
		else
		{
			$sort_by = $_REQUEST['sort'];
			$sort = $sort_methods[$_REQUEST['sort']];
			$ascending = !isset($_REQUEST['asc']) && !isset($_REQUEST['desc']) ? !empty($context['zc']['blog_settings']['articleListSortAscending']) : isset($_REQUEST['asc']);
		}
		
		// make array of table header info
		$tableHeaders = array(
			'url_requests' => $zcRequests,
			'headers' => array(
				'subject' => array('label' => $txt['b3032']),
				'comments' => array('label' => $txt['b15a']),
				'views' => array('label' => $txt['b3027']),
				'author' => array('label' => $txt['b3033']),
				'date' => array('label' => $txt['b30']),
			),
			'sort_direction' => $ascending ? 'up' : 'down',
			'sort_by' => $sort_by,
		);
	}
	// for ALL visible blogs we do things differently....
	else
	{
		// Default sort methods.
		$sort_methods = array(
			'subject' => 't.subject',
			'blog' => 'b.blog_id',
			'comments' => 't.num_comments',
			'views' => 't.num_views',
			'author' => 't.poster_name',
			'date' => 't.posted_time',
		);
	
		if (!isset($_REQUEST['sort']) || !isset($sort_methods[$_REQUEST['sort']]))
		{
			$sort_by = !empty($zc['settings']['globalArticleListDefaultSort']) ? $zc['settings']['globalArticleListDefaultSort'] : 'views';
			$sort = !empty($zc['settings']['globalArticleListDefaultSort']) && !empty($sort_methods[$zc['settings']['globalArticleListDefaultSort']]) ? $sort_methods[$zc['settings']['globalArticleListDefaultSort']] : 't.num_views';
			$ascending = !isset($_REQUEST['asc']) && !isset($_REQUEST['desc']) ? !empty($zc['settings']['globalArticleListSortAscending']) : isset($_REQUEST['asc']);
		}
		else
		{
			$sort_by = $_REQUEST['sort'];
			$sort = $sort_methods[$_REQUEST['sort']];
			$ascending = !isset($_REQUEST['asc']) && !isset($_REQUEST['desc']) ? !empty($zc['settings']['globalArticleListSortAscending']) : isset($_REQUEST['asc']);
		}
	
		// make array of table header info
		$tableHeaders = array(
			'url_requests' => $zcRequests,
			'headers' => array(
				'subject' => array('label' => $txt['b3032']),
				'blog' => array('label' => $txt['b3003']),
				'comments' => array('label' => $txt['b15a']),
				'views' => array('label' => $txt['b3027']),
				'author' => array('label' => $txt['b3033']),
				'date' => array('label' => $txt['b30']),
			),
			'sort_direction' => $ascending ? 'up' : 'down',
			'sort_by' => $sort_by,
		);
	}
	
	// create the table headers
	$list_info['table_headers'] = zcCreateTableHeaders($tableHeaders);
	
	$info += array('sort' => $sort, 'start' => $start, 'maxindex' => $maxindex);
	
	// let's get some articles
	$request = $zcFunc['db_query']("
		SELECT t.subject, t.posted_time, IFNULL(mem.{tbl:members::column:real_name}, t.poster_name) AS poster_name, t.poster_id, 
			t.article_id, t.num_comments, t.num_unapproved_comments, t.num_views" . (!empty($zc['settings']['show_vpreview_on_lists']) ? ", t.body" : '') . ", t.smileys_enabled, t.locked" . (empty($blog) ? ", b.name AS blog_name, b.blog_id" : '') . "
		FROM {db_prefix}articles AS t" . (empty($blog) ? "
			LEFT JOIN {db_prefix}blogs AS b ON (b.blog_id = t.blog_id)
			LEFT JOIN {db_prefix}settings AS bs ON (bs.blog_id = b.blog_id)" : '') . "
			LEFT JOIN {db_prefix}{table:members} AS mem ON (mem.{tbl:members::column:id_member} = t.poster_id)
		WHERE " . (empty($blog) ? "t.blog_id IN ({array_int:blogs})
			AND ((t.approved = 1) OR (bs.articles_require_approval = 0))" : "t.blog_id = {int:blog_id}" . (!empty($context['zc']['blog_settings']['articles_require_approval']) ? "
			AND t.approved = 1" : '')) . (!empty($date['last_timestamp']) && !empty($date['start_timestamp']) ? "
			AND t.posted_time <= {int:last_timestamp}
			AND t.posted_time >= {int:start_timestamp}" : '') . (!empty($blog_category_id) ? "
			AND t.blog_category_id = {int:blog_category_id}" : '') . (!empty($tag) ? "
			AND ((t.blog_tags LIKE {string:tag0}) OR (t.blog_tags LIKE {string:tag1}) OR (t.blog_tags LIKE {string:tag2}) OR (t.blog_tags = {string:tag3}))
			AND t.blog_tags NOT LIKE {string:tag4}
			AND t.blog_tags NOT LIKE {string:tag5}
			AND t.blog_tags NOT LIKE {string:tag6}" : '') . $access_restrict_query . "
		ORDER BY {raw:sort}" . ($ascending ? '' : ' DESC') . "
		LIMIT {int:start}, {int:maxindex}", __FILE__, __LINE__, $info);

	$list_of_articles = array();
	if ($zcFunc['db_num_rows']($request) > 0)
	{
		while ($row = $zcFunc['db_fetch_assoc']($request))
		{
			$row['subject'] = strip_tags($zcFunc['un_htmlspecialchars']($row['subject']));
			zc_censor_text($row['subject']);
			
			if (!empty($row['body']))
			{
				$row['body'] = strip_tags($zcFunc['un_htmlspecialchars']($row['body']), '<br><a>');
				$row['body'] = $zcFunc['parse_bbc']($row['body'], $row['smileys_enabled']);
				zc_censor_text($row['body']);
			}
			
			if (isset($row['blog_name']))
				$row['blog_name'] = strip_tags($zcFunc['un_htmlspecialchars']($row['blog_name']));
			
			$list_of_articles[$row['article_id']] = array(
				'subject' => '<a href="' . $scripturl . '?article='. $row['article_id'] .'.0">'. $row['subject'] .'</a>'
			);
			
			if (!empty($row['locked']))
				$list_of_articles[$row['article_id']]['subject'] .= '&nbsp;<img src="' . $context['zc']['default_images_url'] . '/icons/lock_icon.png" alt="" title="' . sprintf($txt['b363'], $txt['b170']) . '" />';
			
			if (empty($blog))
				$list_of_articles[$row['article_id']]['blog'] = '<a href="' . $scripturl . '?blog='. $row['blog_id'] .'.0">'. $row['blog_name'] .'</a>';
			
			$list_of_articles[$row['article_id']] += array(
				'comments' => !empty($context['zc']['blog_settings']['comments_require_approval']) ? $row['num_comments'] - $row['num_unapproved_comments'] : $row['num_comments'],
				'views' => $row['num_views'],
				'author' => !empty($row['poster_id']) ? sprintf($context['zc']['link_templates']['user_profile'], $row['poster_id'], $row['poster_name'], ' title="' . $txt['b41'] . '"') : $row['poster_name'],
				'date' => timeformat($row['posted_time']),
			);
				
			// add "view preview" below subject
			if (!empty($zc['settings']['show_vpreview_on_lists']))
				$list_of_articles[$row['article_id']]['subject'] .= '<br /><span class="hoverBoxActivator" onclick="document.getElementById(\'preview_' . $row['article_id'] . '\').style.display = \'block\';">'. $txt['b306'] .'</span><div class="hoverBox" id="preview_' . $row['article_id'] . '" style="display:none; margin-top:3px;"><div class="hoverBoxHeader"><span class="hoverBoxClose" onmouseup="document.getElementById(\'preview_' . $row['article_id'] . '\').style.display = \'none\';" title="'. $txt['b305'] .'">X</span>&nbsp;&nbsp;<span class="hoverBoxTitle">'. $txt['b159'] .'</span></div><div class="hoverBoxBody" style="line-height:135%;"><table width="100%" cellspacing="0" cellpadding="0" border="0" style="table-layout:fixed;"><tr class="noPadding"><td><div style="width:100%; overflow:auto;">' . zcTruncateText($row['body'], $zc['settings']['max_length_preview_popups'], ' ', 40, $txt['b31a'], $scripturl . '?article='. $row['article_id'] .'.0', $txt['b31']) . '</div></td></tr></table></div></div>';
		}
	}
	// no articles found.... figure out which error message to use...
	elseif (!empty($tag))
		$context['zc']['error'] = array('zc_error_46', $title);
	elseif (!empty($blog_category_id))
		$context['zc']['error'] = array('zc_error_44', $title);
	elseif (!empty($date))
		$context['zc']['error'] = array('zc_error_45', $title);
		
	$zcFunc['db_free_result']($request);

	return array($list_of_articles, $list_info);
}

?>