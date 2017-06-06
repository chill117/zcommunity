<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
function zcDeleteComment($comments = null)
{
	global $context, $txt;
	global $comment, $blog, $article, $zcFunc, $zc;
	
	checkSession('get');
	
	// assemble array of comments to delete...
	if (!empty($comments) && is_array($comments))
		$comments = $comments;
	elseif (!empty($comments) && !is_array($comments))
		$comments = array($comments);
	elseif (!empty($_POST['comments']))
	{
		$comments = array();
		foreach ($_POST['comments'] as $id)
			$comments[] = (int) $id;
	}
	// just deleting a single comment then?
	elseif (!empty($comment))
		$comments = array($comment);
	// nothing to delete!
	else
		zcReturnToOrigin();
		
	// get stuff...
	$request = $zcFunc['db_query']("
		SELECT 
			c.comment_id, c.poster_id, c.article_id, c.blog_id, c.approved,
			t.num_comments AS article_num_comments, t.locked, t.last_comment_id AS article_last_comment,
			b.num_comments AS blog_num_comments, b.last_comment_id AS blog_last_comment,
			bs.comments_require_approval
		FROM {db_prefix}comments AS c
			LEFT JOIN {db_prefix}blogs AS b ON (b.blog_id = c.blog_id)
			LEFT JOIN {db_prefix}settings AS bs ON (bs.blog_id = c.blog_id)
			LEFT JOIN {db_prefix}articles AS t ON (t.article_id = c.article_id)
		WHERE c.comment_id IN ({array_int:comments})
		LIMIT {int:limit}", __FILE__, __LINE__,
		array(
			'limit' => count($comments),
			'comments' => $comments
		)
	);
	
	// comments don't exist!
	if ($zcFunc['db_num_rows']($request) == 0)
	{
		$zcFunc['db_free_result']($request);
		zcReturnToOrigin();
	}
	
	$update_articles = array();
	$articles = array();
	$update_blogs = array();
	$blogs = array();
	$community_news_comment_count = 0;
	
	while ($row = $zcFunc['db_fetch_assoc']($request))
	{
		$user_started = $row['poster_id'] == $context['user']['id'];
	
		// comment in an ordinary blog...
		if (!empty($row['blog_id']))
			$can_do_this = $context['can_moderate_blog'] || ((empty($row['locked']) || $context['can_moderate_blog']) && ($context['can_delete_any_comments_in_any_b'] || ($context['can_delete_any_comments_in_own_b'] && $context['is_blog_owner']) || ($context['can_delete_own_comments_in_any_b'] && $user_started) || ($context['can_delete_own_comments_in_own_b'] && $context['is_blog_owner'] && $user_started)));
		// comment to community news then....
		else
			$can_do_this = ($context['can_delete_own_news_comments'] && $user_started) || $context['can_delete_any_news_comments'];
		
		// if not allowed to delete... log an error, remove from comments array, and skip to the next comment...
		if ($can_do_this !== true)
		{
			$context['zc']['errors']['c' . $row['comment_id']] = array('zc_error_36', $txt['b127'] . ' ' . $txt['b171'] . ' ' . $row['comment_id']);
			$comments = array_diff($comments, array($row['comment_id']));
			$not_redirect = true;
			continue;
		}
		
		// we only do this detailed stuff for real blogs (not community news)
		if (!empty($row['blog_id']))
		{
			// we'll need to know how many comments we are deleting for each article...
			$temp_comment_count_articles[$row['article_id']] = !empty($temp_comment_count_articles[$row['article_id']]) ? $temp_comment_count_articles[$row['article_id']] + 1 : 1;
			
			// we'll need to know how many comments we are deleting for each blog...
			$temp_comment_count_blogs[$row['blog_id']] = !empty($temp_comment_count_blogs[$row['blog_id']]) ? $temp_comment_count_blogs[$row['blog_id']] + 1 : 1;
			
			if (!empty($row['comments_require_approval']) && empty($row['approved']))
				$temp_unapproved_comment_count_blogs[$row['blog_id']] = !empty($temp_unapproved_comment_count_blogs[$row['blog_id']]) ? $temp_unapproved_comment_count_blogs[$row['blog_id']] + 1 : 1;
			
			if (!empty($row['comments_require_approval']) && empty($row['approved']))
				$temp_unapproved_comment_count_articles[$row['article_id']] = !empty($temp_unapproved_comment_count_articles[$row['article_id']]) ? $temp_unapproved_comment_count_articles[$row['article_id']] + 1 : 1;
		}
		// community news... hmmmm?
		else
			$community_news_comment_count += 1;
		
		if (!in_array($row['article_id'], $articles))
			$articles[] = $row['article_id'];
			
		if (!in_array($row['blog_id'], $blogs))
			$blogs[] = $row['blog_id'];
	}
	$zcFunc['db_free_result']($request);
	
	if (!empty($temp_comment_count_articles))
		foreach ($temp_comment_count_articles as $article_id => $comment_count)
		{
			if (!isset($update_articles[$article_id]))
				$update_articles[$article_id] = array();
			$update_articles[$article_id][] = 'num_comments = num_comments - ' . count($comment_count);
		}
	
	if (!empty($temp_comment_count_blogs))
		foreach ($temp_comment_count_blogs as $blog_id => $comment_count)
		{
			if (!isset($update_blogs[$blog_id]))
				$update_blogs[$blog_id] = array();
			$update_blogs[$blog_id][] = 'num_comments = num_comments - ' . count($comment_count);
		}
	
	if (!empty($temp_unapproved_comment_count_articles))
		foreach ($temp_unapproved_comment_count_articles as $article_id => $comment_count)
		{
			if (!isset($update_articles[$article_id]))
				$update_articles[$article_id] = array();
			$update_articles[$article_id][] = 'num_unapproved_comments = num_unapproved_comments - ' . count($comment_count);
		}
	
	if (!empty($temp_unapproved_comment_count_blogs))
		foreach ($temp_unapproved_comment_count_blogs as $blog_id => $comment_count)
		{
			if (!isset($update_blogs[$blog_id]))
				$update_blogs[$blog_id] = array();
			$update_blogs[$blog_id][] = 'num_unapproved_comments = num_unapproved_comments - ' . count($comment_count);
		}
		
	// we'll need to update the community news comments count
	if (!empty($community_news_comment_count))
		$zc_updates['community_news_num_comments'] = !empty($zc['settings']['community_news_num_comments']) && $zc['settings']['community_news_num_comments'] >= $community_news_comment_count ? $zc['settings']['community_news_num_comments'] - $community_news_comment_count : 0;
	
	// gotta be at least one comment we can delete!
	if (!empty($comments))
	{
		$total_comments = count($comments);
	
		// total # of comments we are deleting?
		$zc_updates['community_total_comments'] = !empty($zc['settings']['community_total_comments']) && $zc['settings']['community_total_comments'] >= $total_comments ? $zc['settings']['community_total_comments'] - $total_comments : 0;
	
		// delete the comments
		$zcFunc['db_query']("
			DELETE FROM {db_prefix}comments
			WHERE comment_id IN ({array_int:comments})
			LIMIT {int:limit}", __FILE__, __LINE__,
			array(
				'limit' => $total_comments,
				'comments' => $comments
			)
		);
		
		// check to see if any of the comments we deleted were the last_comment in any of their blogs...
		if (!empty($blogs))
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
						
					$row['last_comment_id'] = !empty($row['last_comment_id']) ? $row['last_comment_id'] : 0;
					$update_blogs[$blog_id][] = 'last_comment_id = '. $row['last_comment_id'];
				}
				
			// update each blog...
			if (!empty($update_blogs))
				foreach ($update_blogs as $blog_id => $updates)
					if (!empty($updates))
						$zcFunc['db_query']("
							UPDATE {db_prefix}blogs
							SET " . implode(',
								', $updates) . "
							WHERE blog_id = {int:blog_id}
							LIMIT 1", __FILE__, __LINE__,
							array(
								'blog_id' => $blog_id
							)
						);
		}
		
		// check to see if any of the comments we deleted were the last_comment in any of their articles...
		$request = $zcFunc['db_query']("
			SELECT article_id
			FROM {db_prefix}articles
			WHERE blog_id IN ({array_int:articles})
				AND last_comment_id IN ({array_int:comments})
			LIMIT {int:limit}", __FILE__, __LINE__,
			array(
				'limit' => count($blogs),
				'articles' => $articles,
				'comments' => $comments
			)
		);
		while ($row = $zcFunc['db_fetch_assoc']($request))
			$articles_need_fix[] = $row['article_id'];
		$zcFunc['db_free_result']($request);
		
		if (!empty($articles_need_fix))
			foreach ($articles_need_fix as $article_id)
			{
				// get new last_comment
				$request = $zcFunc['db_query']("
					SELECT comment_id
					FROM {db_prefix}comments
					WHERE article_id = {int:article_id}
					ORDER BY posted_time DESC
					LIMIT 1", __FILE__, __LINE__,
					array(
						'article_id' => $article_id
					)
				);
				if ($zcFunc['db_num_rows']($request) > 0)
					$row = $zcFunc['db_fetch_assoc']($request);
				$zcFunc['db_free_result']($request);
					
				$row['last_comment_id'] = !empty($row['last_comment_id']) ? $row['last_comment_id'] : 0;
				$update_articles[$article_id][] = 'last_comment_id = '. $row['last_comment_id'];
			}
			
		// update each article...
		if (!empty($update_articles))
			foreach ($update_articles as $article_id => $updates)
				if (!empty($updates))
					$zcFunc['db_query']("
						UPDATE {db_prefix}articles
						SET " . implode(',
							', $updates) . "
						WHERE article_id = {int:article_id}
						LIMIT 1", __FILE__, __LINE__,
						array(
							'article_id' => $article_id
						)
					);
						
		// update community news comment count and overall community comments count
		if (!empty($zc_updates))
			zcUpdateGlobalSettings($zc_updates);
	}
	
	if (!empty($not_redirect))
	{
		if (!empty($_REQUEST['blog']))
			unset($_REQUEST['article']);
			
		$_REQUEST['zc'] = '';
		return zC_START();
	}
	else
		// redirect back to where they came from...
		zcReturnToOrigin();
}

function zcCreateComment($processed)
{
	global $context, $txt;
	global $article, $blog, $zc, $zcFunc;
	
	if (empty($article))
		zc_fatal_error('zc_error_24');
	elseif (empty($processed))
		zc_fatal_error();
		
	$processed['article_id'] = $article;
	$processed['blog_id'] = $blog;
	$processed['poster_email'] = !empty($processed['poster_email']) ? $processed['poster_email'] : $context['user']['email'];
	$processed['poster_name'] = !empty($processed['poster_name']) ? $processed['poster_name'] : $context['user']['name'];
	$processed['poster_ip'] = $context['user']['ip'];
	$processed['poster_id'] = !empty($context['user']['id']) ? $context['user']['id'] : 0;
	$processed['approved'] = $context['can_moderate_blog'] || $context['can_approve_comments_in_any_b'] ? 1 : 0;
	$processed['posted_time'] = time();
	
	// don't need these anymore...
	if (!empty($context['zc']['form_info']['_info_']['exclude_from_table']))
		foreach($context['zc']['form_info']['_info_']['exclude_from_table'] as $k)
			unset($processed[$k]);
		
	$columns = array();
	foreach ($processed as $k => $dummy)
		$columns[$k] = isset($context['zc']['form_info'][$k]['type']) ? $context['zc']['form_info'][$k]['type'] : 'string';
		
	// inserts the comment into the database
	$zcFunc['db_insert']('insert', '{db_prefix}comments', $columns, $processed);
		
	$comment_id = $zcFunc['db_insert_id']();
	
	if (empty($comment_id))
		zc_fatal_error();
	
	// update last_comment_id and num_comments for article...
	$zcFunc['db_update'](
		'{db_prefix}articles',
		array('comment_id' => 'int', 'article_id' => 'int', 'last_comment_id' => 'int', 'num_comments' => 'int', 'num_unapproved_comments' => 'int'),
		array_merge(
			array('last_comment_id' => $comment_id, 'num_comments' => array('+', 1)),
			empty($processed['approved']) ? array('num_unapproved_comments' => array('+', 1)) : array()
		),
		array('article_id' => $article));
	
	// update last_comment_id and num_comments for blog...
	$zcFunc['db_update'](
		'{db_prefix}blogs',
		array('comment_id' => 'int', 'blog_id' => 'int', 'last_comment_id' => 'int', 'num_comments' => 'int', 'num_unapproved_comments' => 'int'),
		array_merge(
			array('last_comment_id' => $comment_id, 'num_comments' => array('+', 1)),
			empty($processed['approved']) ? array('num_unapproved_comments' => array('+', 1)) : array()
		),
		array('blog_id' => $blog));
		
	// was a draft used to create this comment and does the user want to delete that draft upon posting?
	if (!empty($_POST['draft_id']) && !empty($_POST['delete_draft']))
	{
		$draft_id = (int) $_POST['draft_id'];
		$zcFunc['db_query']("
			DELETE FROM {db_prefix}drafts
			WHERE poster_id = {int:user_id}
				AND draft_id = {int:draft_id}
			LIMIT 1", __FILE__, __LINE__,
			array(
				'draft_id' => $draft_id,
				'user_id' => $context['user']['id']
			)
		);
	}
		
	$updates = array();
	$updates['max_comment_id'] = $comment_id;
	$updates['community_total_comments'] = (!empty($zc['settings']['community_total_comments']) ? $zc['settings']['community_total_comments'] + 1 : 1);
	
	// add one to community news comments total?
	if (empty($blog))
		$updates['community_news_num_comments'] = (!empty($zc['settings']['community_news_num_comments']) ? $zc['settings']['community_news_num_comments'] + 1 : 1);
	
	// update global settings....
	zcUpdateGlobalSettings($updates);
		
	require_once($zc['sources_dir'] . '/Notify.php');
		
	// sends notifications to users who may be watching this article...
	zcSendNotifications(null, $processed['body']);
			
	// redirect back to where they came from...
	zcReturnToOrigin();
}
	
function zcUpdateComment($processed)
{
	global $context, $txt, $comment, $article, $blog, $zcFunc;
	
	if (empty($processed) || empty($comment))
		zc_fatal_error();
		
	if (empty($context['zc']['no_last_edit']))
	{
		$processed['last_edit_name'] = $context['user']['is_guest'] ? $txt['b567'] : $context['user']['name'];
		$processed['last_edit_time'] = time();
	}
	
	// don't need these anymore...
	if (!empty($context['zc']['form_info']['_info_']['exclude_from_table']))
		foreach($context['zc']['form_info']['_info_']['exclude_from_table'] as $k)
			unset($processed[$k]);
	
	$columns = array('comment_id' => 'int');
	foreach ($processed as $k => $dummy)
		$columns[$k] = isset($context['zc']['form_info'][$k]['type']) ? $context['zc']['form_info'][$k]['type'] : 'string';
	
	// update comment in blog_comments table
	$zcFunc['db_update'](
		'{db_prefix}comments',
		$columns,
		$processed,
		array('comment_id' => $comment));
	
	$_SESSION['zc_success_msg'] = 'zc_success_1';
			
	// redirect back to where they came from...
	zcReturnToOrigin();
}

function zcApproveComment()
{
	global $txt, $context, $article, $blog, $comment, $zcFunc;
	
	if (!empty($comment))
	{
		checkSession('get');
		
		// are they allowed to approve comments?
		if (!$context['can_moderate_blog'] && !$context['can_approve_comments_in_any_b'])
			zc_fatal_error(array('zc_error_21', 'b130'));
			
		$approved = $_REQUEST['zc'] == 'approvecomment' ? 1 : 0;
		
		// all the permission checks are done... now let's approve the comment
		$zcFunc['db_update'](
			'{db_prefix}comments',
			array('comment_id' => 'int', 'approved' => 'int'),
			array('approved' => $approved),
			array('comment_id' => $comment));
		
		// update num_unapproved_comments in this article...
		if (!empty($article))
			$zcFunc['db_update'](
				'{db_prefix}articles',
				array('article_id' => 'int', 'num_unapproved_comments' => 'int'),
				array('num_unapproved_comments' => array($approved ? '-' : '+', 1)),
				array('article_id' => $article));
			
		// update num_unapproved_comments in this blog...
		if (!empty($blog))
			$zcFunc['db_update'](
				'{db_prefix}blogs',
				array('blog_id' => 'int', 'num_unapproved_comments' => 'int'),
				array('num_unapproved_comments' => array($approved ? '-' : '+', 1)),
				array('blog_id' => $blog));
	}
			
	// redirect back to where they came from...
	zcReturnToOrigin();
}


?>