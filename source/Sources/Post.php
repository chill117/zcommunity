<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
function zcPost()
{
	global $context, $scripturl, $txt, $blog_info, $blog, $article, $comment, $poll, $draft, $zcFunc, $zc;
	
	// load post lang file and template...
	zcLoadLanguage('Post');
	zcLoadTemplate('Post');
	
	$context['zc']['sub_sub_template'] = 'post_form';
	$context['zc']['current_info'] = array();
	$hidden_form_values = array();
	$form_url = $scripturl . '?zc=' . $_REQUEST['zc'] . (!empty($_REQUEST['blog']) ? ';blog=' . $_REQUEST['blog'] : '');
	
	$context['zc']['form_data_loaded'] = false;
	
	// plug-in slot #11
	zc_plugin_slot(11);
	
	// article?
	if (empty($context['zc']['form_data_loaded']) && isset($_REQUEST['article']) && !isset($_REQUEST['comment']))
	{
		$form_url .= ';article';
		// editing an existing article?
		if (!empty($article))
		{
			$form_url .= '=' . $article . '.' . $_REQUEST['start'];
			$context['page_title'] = $txt['b17'] . ' ' . (!empty($blog) ? $txt['b66'] : $txt['b343']);
			
			// load the article's current info
			$request = $zcFunc['db_query']("
				SELECT poster_id, locked, smileys_enabled, blog_category_id, blog_tags, num_comments, poster_email, poster_name, body, subject, last_edit_time, last_edit_name, blog_id, posted_time, approved, access_restrict, users_allowed
				FROM {db_prefix}articles
				WHERE article_id = {int:article_id}
				LIMIT 1", __FILE__, __LINE__,
				array(
					'article_id' => $article
				)
			);
				
			// article doesn't exist!
			if ($zcFunc['db_num_rows']($request) == 0)
				zc_fatal_error(array('zc_error_18', 'b170'));
			
			$article_info = $zcFunc['db_fetch_assoc']($request);
			$zcFunc['db_free_result']($request);
				
			$user_started = $article_info['poster_id'] == $context['user']['id'];
				
			$access_info = array(
				'access_restrict' => $article_info['access_restrict'],
				'users_allowed' => $article_info['users_allowed'],
				'articles_require_approval' => $context['zc']['blog_settings']['articles_require_approval'],
				'approved' => $article_info['approved'],
				'poster_id' => $article_info['poster_id']
			);
			
			$can_see = zc_can_see_article($access_info);
				
			if (!$can_see)
				zc_fatal_error(array('zc_error_68', 'b170'));
			
			$article_info['subject'] = $zcFunc['un_htmlspecialchars']($article_info['subject']);
			$article_info['body'] = $zcFunc['un_htmlspecialchars']($article_info['body']);
			$article_info['poster_email'] = $zcFunc['un_htmlspecialchars']($article_info['poster_email']);
			$article_info['poster_name'] = $zcFunc['un_htmlspecialchars']($article_info['poster_name']);
			$article_info['last_edit_name'] = $zcFunc['un_htmlspecialchars']($article_info['last_edit_name']);
			
			if (empty($article_info['last_edit_name']) && (time() - $article_info['posted_time']) < $zc['settings']['posting_grace_period'])
				$context['zc']['no_last_edit'] = true;
		
			if (!empty($blog))
				$can_edit_this = $context['can_moderate_blog'] || (empty($article_info['locked']) || $context['can_moderate_blog']) && ($context['can_edit_any_articles_in_any_b'] || ($context['can_edit_any_articles_in_own_b'] && $context['is_blog_owner']) || ($context['can_edit_own_articles_in_any_b'] && $user_started) || ($context['can_edit_own_articles_in_own_b'] && $context['is_blog_owner'] && $user_started));
			else
				$can_edit_this = $context['can_edit_community_news'];
		
			// are they allowed to edit this article?
			if (!$can_edit_this)
				zc_fatal_error(sprintf('zc_error_17', 'b164'));
				
			// add link to this article to link tree...
			$context['zc']['link_tree']['article'] = '<a href="' . $scripturl . '?article=' . $article . '.0">' . $article_info['subject'] . '</a>';
				
			if (!empty($blog))
				$context['zc']['can_lock_this'] = $context['can_moderate_blog'] || (((($context['can_lock_own_articles_in_own_b'] && $user_started) || $context['can_lock_any_articles_in_own_b']) && $context['is_blog_owner']) || ($context['can_lock_own_articles_in_any_b'] && $user_started) || $context['can_lock_any_articles_in_any_b']);
			else
				$context['zc']['can_lock_this'] = $context['can_lock_community_news'];
				
			$context['zc']['current_info'] = $article_info;
			$context['zc']['editing_something'] = true;
		}
		// new article...
		else
		{
			// if no blog is specified... they are trying to post community news...
			if (empty($blog) && !$context['can_post_community_news'])
				zc_fatal_error(array('zc_error_17', 'b337'));
			// they are just not allowed to post articles...
			elseif (!empty($blog) && !$context['can_post_articles'])
				zc_fatal_error(array('zc_error_17', 'b163'));
				
			// load a draft of an article?
			if (!empty($draft))
			{
				// we'll need this file...
				require_once($zc['sources_dir'] . '/Subs-Drafts.php');
			
				$context['zc']['current_info'] = zcLoadDraft($draft, 'article');
				
				if (!empty($context['zc']['current_info']))
					$form_url .= ';draft=' . $draft;
			}
				
			$context['page_title'] = (!empty($blog) ? $txt['b223a'] : $txt['b223b']);
			
			// figure out if they are allowed to lock this article...
			if (!empty($blog))
				$context['zc']['can_lock_this'] = $context['can_moderate_blog'] || ($context['can_lock_any_articles_in_own_b'] && $context['is_blog_owner']) || $context['can_lock_any_articles_in_any_b'];
			else
				$context['zc']['can_lock_this'] = $context['can_lock_community_news'];
				
			// let's get a list of all their article drafts...
			if (empty($article) && !$context['user']['is_guest'] && $context['can_save_drafts'])
			{
				require_once($zc['sources_dir'] . '/Subs-Drafts.php');
				list($context['zc']['drafts'], $context['zc']['list2']) = zcGetDrafts('article');
			}
		}
		
		require_once($zc['sources_dir'] . '/Subs-Post.php');
		
		// this prepares an array of info about the article form...
		$context['zc']['form_info'] = zc_prepare_article_form_array();
		
		$context['zc']['raw_javascript'][] = '
						function addStringToField(string, fieldName)
						{
							var formField = document.getElementById(fieldName);
							var contents = formField.value;
							if (contents.search(string) != "-1")
							{
								return false;
							}
							else
							{
								if (contents == "")
								{
									var newContents = string;
								}
								else
								{
									var newContents = contents + "," + string;
								}
								formField.value = newContents;
								return true;
							}
						}';
		
		$need_load_blog_tags = true;
	}
	// comment?
	elseif (empty($context['zc']['form_data_loaded']) && isset($_REQUEST['comment']))
	{
		$form_url .= ';article=' . $article . '.' . $_REQUEST['start'] . ';comment';
		// editing an existing comment?
		if (!empty($comment))
		{
			$context['page_title'] = $txt['b17'] . ' ' . $txt['b15'];
			$form_url .= '=' . $comment;
			
			// load the comment's current info (with some info about the article it's under)
			$request = $zcFunc['db_query']("
				SELECT 
					c.poster_id, c.smileys_enabled, c.poster_email, c.poster_name, c.body, c.last_edit_time, c.last_edit_name, c.posted_time, c.approved AS comment_approved,
					t.article_id, t.approved AS article_approved, t.access_restrict, t.users_allowed, t.locked, t.poster_id AS article_poster_id, IFNULL(mem.{tbl:members::column:real_name}, t.poster_name) AS article_poster_name, t.posted_time AS article_posted_time, t.subject AS article_subject, t.body AS article_body, t.smileys_enabled AS article_smileys_enabled, t.num_comments
				FROM {db_prefix}comments AS c
					LEFT JOIN {db_prefix}articles AS t ON (t.article_id = c.article_id)
					LEFT JOIN {db_prefix}{table:members} AS mem ON (mem.{tbl:members::column:id_member} = t.poster_id)
				WHERE c.comment_id = {int:comment_id}
				LIMIT 1", __FILE__, __LINE__,
				array(
					'comment_id' => $comment
				)
			);
				
			// comment doesn't exist!
			if ($zcFunc['db_num_rows']($request) == 0)
				zc_fatal_error(array('zc_error_18', 'b171'));
			
			$comment_info = $zcFunc['db_fetch_assoc']($request);
			$zcFunc['db_free_result']($request);
				
			$user_started = $comment_info['poster_id'] == $context['user']['id'];
			
			// not allowed to view unapproved (comment)?
			if (!empty($context['zc']['blog_settings']['comments_require_approval']) && empty($comment_info['comment_approved']) && empty($context['can_moderate_blog']) && empty($context['can_approve_comments_in_any_b']) && !$user_started)
				zc_fatal_error(array('zc_error_68', 'b171'));
				
			$access_info = array(
				'access_restrict' => $comment_info['access_restrict'],
				'users_allowed' => $comment_info['users_allowed'],
				'articles_require_approval' => $context['zc']['blog_settings']['articles_require_approval'],
				'approved' => $comment_info['article_approved'],
				'poster_id' => $comment_info['article_poster_id']
			);
			
			$can_see = zc_can_see_article($access_info);
				
			if (!$can_see)
				zc_fatal_error(array('zc_error_68', 'b170'));
		
			$can_edit_this = $context['can_moderate_blog'] || ((empty($comment_info['locked']) || $context['can_moderate_blog']) && ($context['can_edit_any_comments_in_any_b'] || ($context['can_edit_any_comments_in_own_b'] && $context['is_blog_owner']) || ($context['can_edit_own_comments_in_any_b'] && $user_started) || ($context['can_edit_own_comments_in_own_b'] && $context['is_blog_owner'] && $user_started)));
		
			// are they allowed to edit this comment?
			if (!$can_edit_this)
				zc_fatal_error(array('zc_error_17', 'b167'));
			
			$comment_info['body'] = $zcFunc['un_htmlspecialchars']($comment_info['body']);
			$comment_info['poster_email'] = $zcFunc['un_htmlspecialchars']($comment_info['poster_email']);
			$comment_info['poster_name'] = $zcFunc['un_htmlspecialchars']($comment_info['poster_name']);
			$comment_info['last_edit_name'] = $zcFunc['un_htmlspecialchars']($comment_info['last_edit_name']);
			
			if (empty($comment_info['last_edit_name']) && (time() - $comment_info['posted_time']) < $zc['settings']['posting_grace_period'])
				$context['zc']['no_last_edit'] = true;
				
			$context['zc']['current_info'] = $comment_info;
			$context['zc']['editing_something'] = true;
		}
		// new comment...
		else
		{
			// $article empty?
			if (empty($article))
				zc_fatal_error('zc_error_35');
				
			$context['page_title'] = $txt['b223'];
				
			// get info about this article...
			$request = $zcFunc['db_query']("
				SELECT t.article_id, t.last_comment_id, t.locked, t.approved AS article_approved, t.access_restrict, t.users_allowed, t.poster_id AS article_poster_id, IFNULL(mem.{tbl:members::column:real_name}, t.poster_name) AS article_poster_name, t.posted_time AS article_posted_time, t.subject AS article_subject, t.body AS article_body, t.smileys_enabled AS article_smileys_enabled, t.num_comments, mem.{tbl:members::column:id_member} AS article_poster_mem_id
				FROM {db_prefix}articles AS t
					LEFT JOIN {db_prefix}{table:members} AS mem ON (mem.{tbl:members::column:id_member} = t.poster_id)
				WHERE t.article_id = {int:article_id}
				LIMIT 1", __FILE__, __LINE__,
				array(
					'article_id' => $article
				)
			);
				
			// article does not exist!
			if ($zcFunc['db_num_rows']($request) == 0)
				zc_fatal_error('zc_error_24');
				
			$comment_info = $zcFunc['db_fetch_assoc']($request);
			$zcFunc['db_free_result']($request);
				
			$access_info = array(
				'access_restrict' => $comment_info['access_restrict'],
				'users_allowed' => $comment_info['users_allowed'],
				'articles_require_approval' => $context['zc']['blog_settings']['articles_require_approval'],
				'approved' => $comment_info['article_approved'],
				'poster_id' => $comment_info['article_poster_id']
			);
			
			$can_see = zc_can_see_article($access_info);
				
			if (!$can_see)
				zc_fatal_error(array('zc_error_68', 'b170'));

			// figure out if they are allowed to post a comment....
			$can_post_comment = $context['can_moderate_blog'] || ((empty($comment_info['locked']) || $context['can_moderate_blog']) && ($context['can_post_comments_in_any_b'] || ($context['can_post_comments_in_own_b'] && $context['is_blog_owner'])));
				
			// they can't post comments, because the article is locked
			if (!empty($comment_info['locked']) && !$context['can_moderate_blog'])
				zc_fatal_error('zc_error_81');
			
			// they are just not allowed to post comments...
			if (!$can_post_comment)
				zc_fatal_error(array('zc_error_17', 'b166'));
		
			// we will use this upon posting this comment....
			$hidden_form_values['last_comment_id'] = $comment_info['last_comment_id'];
				
			// load a draft of an article?
			if (!empty($draft))
			{
				// we'll need this file...
				require_once($zc['sources_dir'] . '/Subs-Drafts.php');
			
				$context['zc']['current_info'] = zcLoadDraft($draft, 'comment');
				
				if (!empty($context['zc']['current_info']))
					$form_url .= ';draft=' . $draft;
			}
		}
		
		require_once($zc['sources_dir'] . '/Subs-Post.php');
		
		// this prepares an array of info about the comment form...
		$context['zc']['form_info'] = zc_prepare_comment_form_array();
		
		// do we want to get a list of all the comment drafts?
		if (empty($comment) && !$context['user']['is_guest'] && $context['can_save_drafts'])
		{
			require_once($zc['sources_dir'] . '/Subs-Drafts.php');
			list($context['zc']['drafts'], $context['zc']['list2']) = zcGetDrafts('comment');
		}
		
		if (!empty($comment_info))
		{
			zcLoadTemplate('Blog');
			
			$comment_info['article_subject'] = $zcFunc['un_htmlspecialchars']($comment_info['article_subject']);
			$comment_info['article_body'] = $zcFunc['un_htmlspecialchars']($comment_info['article_body']);
		
			zc_censor_text($comment_info['article_subject']);
			zc_censor_text($comment_info['article_body']);
			
			// now assemble parent array...
			$context['zc']['parent'] = array(
				'id' => $comment_info['article_id'],
				'link' => '<a href="' . $scripturl . '?article='. $comment_info['article_id'] .'.0">'. strip_tags($comment_info['article_subject']) .'</a>',
				'subject' => strip_tags($comment_info['article_subject']),
				'body' => $zcFunc['parse_bbc']($comment_info['article_body'], $comment_info['article_smileys_enabled']),
				'time' => timeformat($comment_info['article_posted_time'], false),
				'poster' => array(
					'link' => !empty($comment_info['article_poster_mem_id']) ? sprintf($context['zc']['link_templates']['user_profile'], $comment_info['article_poster_mem_id'], $comment_info['article_poster_name'], ' title="' . $txt['b41'] . '"') : $comment_info['article_poster_name'],
				),
				'show_comments' => true,
				'can_reply' => false,
				'num_comments' => isset($comment_info['num_comments']) ? $comment_info['num_comments'] : 0,
				'show_all_comments_link' => false,
				'more_links' => array(),
				'comments' => array(),
			);
			
			// get comments to parent...
			if (!empty($context['zc']['parent']['id']))
			{
				$start = isset($_REQUEST['listStart']) ? (int) $_REQUEST['listStart'] : 0;
				$maxindex = 10;
				$context['zc']['parent']['show_page_index'] = !empty($comment_info['num_comments']) && $comment_info['num_comments'] > $maxindex;
				
				if ($context['zc']['parent']['show_page_index'])
					$context['zc']['parent']['page_index'] = zcConstructPageIndex($scripturl . '?listStart=%d' . zcRequestVarsToString('all,listStart', ';'), $start, $comment_info['num_comments'], $maxindex, true);
				
				$request = $zcFunc['db_query']("
					SELECT c.comment_id, c.body, c.poster_id, c.posted_time, IFNULL(mem.{tbl:members::column:real_name}, c.poster_name) AS poster_name, c.smileys_enabled, c.approved, c.last_edit_time, c.last_edit_name, mem.{tbl:members::column:id_member} AS id_member
					FROM {db_prefix}comments AS c
						LEFT JOIN {db_prefix}{table:members} AS mem ON (mem.{tbl:members::column:id_member} = c.poster_id)
					WHERE c.article_id = {int:article_id}" . (!empty($context['zc']['blog_settings']['comments_require_approval']) ? "
						AND c.approved = 1" : '') . "
					ORDER BY c.posted_time DESC
					LIMIT {int:start}, {int:maxindex}", __FILE__, __LINE__,
					array(
						'start' => $start,
						'maxindex' => $maxindex,
						'article_id' => $context['zc']['parent']['id']
					)
				);
				while ($row = $zcFunc['db_fetch_assoc']($request))
				{
					$row['body'] = $zcFunc['un_htmlspecialchars']($row['body']);
					zc_censor_text($row['body']);
					
					$user_started = !empty($context['user']['id']) && $context['user']['id'] == $row['poster_id'];
					
					$context['zc']['parent']['comments'][] = array(
						'id' => $row['comment_id'],
						'article_id' => $context['zc']['parent']['id'],
						'time' => timeformat($row['posted_time'], false),
						'body' => zcTruncateText($zcFunc['parse_bbc']($row['body'], $row['smileys_enabled']), 360, ' ', 20, $txt['b31a'], $scripturl . '?article='. $context['zc']['parent']['id'] .'.0#c'. $row['comment_id'], $txt['b31']),
						'modified' => array(
							'time' => timeformat($row['last_edit_time'], false),
							'name' => $row['last_edit_name'],
						),
						'poster' => array(
							'link' => !empty($row['id_member']) ? sprintf($context['zc']['link_templates']['user_profile'], $row['id_member'], $row['poster_name'], ' title="' . $txt['b41'] . '"') : $row['poster_name'],
						),
						'approved' => !empty($row['approved']),
						'can_see_unapproved' => $context['can_moderate_blog'] || $context['can_approve_comments_in_any_b'] || $user_started,
						'can_approve' => $context['can_moderate_blog'] || $context['can_approve_comments_in_any_b'],
						'options' => array(),
						'extra_links' => array(),
					);
				}
				$zcFunc['db_free_result']($request);
			}
			
			$context['zc']['link_tree']['article'] = $context['zc']['parent']['link'];
		}
	}
	// poll?
	elseif (empty($context['zc']['form_data_loaded']) && isset($_REQUEST['poll']))
	{
		$form_url .= ';poll';
		// editing an existing poll?
		if (!empty($poll))
		{
			$form_url .= '=' . $poll;
			$context['page_title'] = $txt['b17'] . ' ' . $txt['b225'];
			// load the poll's current info
			$request = $zcFunc['db_query']("
				SELECT poster_id, question, max_votes, change_vote, expire_time, hide_results, last_edit_name, last_edit_time, posted_time
				FROM {db_prefix}polls
				WHERE poll_id = {int:poll_id}
				LIMIT 1", __FILE__, __LINE__,
				array(
					'poll_id' => $poll
				)
			);
				
			// poll doesn't exist!
			if ($zcFunc['db_num_rows']($request) == 0)
				zc_fatal_error(array('zc_error_18', 'b172'));
			
			$poll_info = $zcFunc['db_fetch_assoc']($request);
			$zcFunc['db_free_result']($request);
			
			$context['zc']['poll_expire_time'] = !empty($poll_info['expire_time']) ? ($poll_info['expire_time'] <= time() ? '<b>' . $txt['b298'] . '</b>' : $txt['b297'] . ': ' . timeformat($poll_info['expire_time'], false)) : '';
			$poll_info['expire_time'] = !empty($poll_info['expire_time']) ? round(((($poll_info['expire_time'] - time()) / 86400)), 2) : '';
			$poll_info['question'] = $zcFunc['un_htmlspecialchars']($poll_info['question']);
			$poll_info['last_edit_name'] = !empty($poll_info['last_edit_name']) ? $zcFunc['un_htmlspecialchars']($poll_info['last_edit_name']) : '';
			
			if (empty($poll_info['last_edit_name']) && (time() - $poll_info['posted_time']) < $zc['settings']['posting_grace_period'])
				$context['zc']['no_last_edit'] = true;
				
			$user_started = $poll_info['poster_id'] == $context['user']['id'];
		
			$can_edit_this = $context['can_moderate_blog'] || $context['can_edit_any_polls_in_any_b'] || ($context['can_edit_any_polls_in_own_b'] && $context['is_blog_owner']) || ($context['can_edit_own_polls_in_any_b'] && $user_started) || ($context['can_edit_own_polls_in_own_b'] && $context['is_blog_owner'] && $user_started);
		
			// are they allowed to edit this poll?
			if (!$can_edit_this)
				zc_fatal_error(array('zc_error_17', 'b169'));
				
			// time to get all the options for this poll...
			$request = $zcFunc['db_query']("
				SELECT choice_id, label
				FROM {db_prefix}poll_choices
				WHERE poll_id = {int:poll_id}
				ORDER BY choice_id", __FILE__, __LINE__,
				array(
					'poll_id' => $poll
				)
			);
			$poll_info['choices'] = array();
			while ($row = $zcFunc['db_fetch_assoc']($request))
				$poll_info['choices'][$row['choice_id']] = $zcFunc['un_htmlspecialchars']($row['label']);
			$zcFunc['db_free_result']($request);
			
			$context['zc']['current_info'] = $poll_info;
			$context['zc']['editing_something'] = true;
		}
		// new poll...
		elseif (!empty($blog))
		{
			// they are just not allowed to post polls...
			if (!$context['can_post_polls'])
				zc_fatal_error(array('zc_error_17', 'b168'));
				
			$context['page_title'] = $txt['b224'];
		}
		else
			zc_fatal_error('zc_error_34');
		
		require_once($zc['sources_dir'] . '/Subs-Post.php');
			
		// this prepares an array of info about the poll form...
		$context['zc']['form_info'] = zc_prepare_poll_form_array();
	}
	// couldn't find anything to do!
	elseif (empty($context['zc']['form_data_loaded']))
		zc_redirect_exit((!empty($context['zc']['zCommunity_is_home']) ? '' : 'zc'));
		
	// this will be used in the form itself, so that we can know which draft when we do finally post something...
	$context['zc']['current_draft'] = !empty($draft) ? $draft : 0;
	
	if (!empty($context['zc']['current_draft']))
		$hidden_form_values['draft_id'] = $context['zc']['current_draft'];
		
	// no save_as_draft button for editing!
	if (isset($context['zc']['form_info']['_info_']['additional_submit_buttons']['save_as_draft']) && (!empty($context['zc']['editing_something']) || !$context['can_save_drafts']))
		unset($context['zc']['form_info']['_info_']['additional_submit_buttons']['save_as_draft']);
		
	// form was submitted...
	if (isset($_POST['seqnum']) && isset($_POST['sc']))
	{
		// verify that this isn't a double post...
		zc_check_submit_once('check');
		
		// verify user session
		checkSession('post');
			
		zcAntiBot('check');
		
		// add the rand_string from the session variable to each field in the form info array...
		foreach ($context['zc']['form_info'] as $k => $array)
		{
			if (in_array($k, array('_info_')))
				continue;
				
			// unset and skip fields that shouldn't be used for whatever reason
			if (isset($array['must_return_true']) && $array['must_return_true'] !== true)
			{
				unset($context['zc']['form_info'][$k]);
				continue;
			}
				
			// are there any file upload fields?
			if (isset($array['type']) && $array['type'] == 'file')
				$context['zc']['file_upload_field_exists'] = true;
				
			// NOTE: when register_globals is ON, $_SESSION cannot be a multidimensional array...
			$encrypted_key = 'n' . sha1($k . '_' . $_SESSION['zc_rand_string']);
			$decrypt_keys[$encrypted_key] = $k;
			$encrypt_keys[$k] = $encrypted_key;
			
			if ($k == 'body')
				$context['zc']['post_box_name'] = $encrypted_key;
				
			$context['zc']['form_info'][$encrypted_key] = $array;
			unset($context['zc']['form_info'][$k]);
			
			// don't forget about $context['zc']['current_info']
			if (isset($context['zc']['current_info'][$k]))
			{
				$context['zc']['current_info'][$encrypted_key] = $context['zc']['current_info'][$k];
				unset($context['zc']['current_info'][$k]);
			}
		}
		
		// form processing...
		if (empty($context['zc']['errors']))
		{
			// important to know if we're just previewing or not...
			if (!empty($_POST['button_preview']))
				$context['zc']['previewing'] = true;
		
			list($processed, $context['zc']['errors']) = zcProcessForm($context['zc']['form_info']);
		}
		
		if (isset($processed) && is_array($processed))
			// reindex the $processed array with the real field names...
			foreach ($processed as $encrypted_key => $v)
			{
				$processed[$decrypt_keys[$encrypted_key]] = $v;
				unset($processed[$encrypted_key]);
			
				// don't forget about $context['zc']['current_info']
				if (isset($context['zc']['current_info'][$encrypted_key]))
				{
					$context['zc']['current_info'][$decrypt_keys[$encrypted_key]] = $context['zc']['current_info'][$encrypted_key];
					unset($context['zc']['current_info'][$encrypted_key]);
				}
			}
			
		if (!empty($processed['no_count_last_edit']) && $context['user']['is_admin'])
			$context['zc']['no_last_edit'] = true;
		
		// proceed only if there are no errors....
		if (empty($context['zc']['errors']) && count($context['zc']['errors']) == 0)
		{
			$context['zc']['processing_complete'] = false;
		
			// plug-in slot #2
			zc_plugin_slot(2);
		
			// previewing?
			if (empty($context['zc']['processing_complete']) && !empty($_POST['button_preview']))
			{
				// make sure this can be previewed... (look in form_info array for preview button)
				if (!isset($context['zc']['form_info']['_info_']['additional_submit_buttons']['preview']))
					zc_fatal_error('zc_error_29');
					
				$context['zc']['previewing'] = true;
						
				if (!empty($processed['subject']))
				{
					$processed['subject'] = $zcFunc['un_htmlspecialchars']($processed['subject']);
					zc_censor_text($processed['subject']);
				}
						
				if (!empty($processed['body']))
				{
					$processed['body'] = $zcFunc['un_htmlspecialchars']($processed['body']);
					zc_censor_text($processed['body']);
				}
					
				if (isset($_REQUEST['article']) && !isset($_REQUEST['comment']))
				{
					$context['zc']['form_info']['_info_']['preview_template'] = 'show_articles';
					
					zcLoadTemplate('Blog');
					
					$context['zc']['articles'] = array();
					$context['zc']['articles'][0] = array(
						'id' => 0,
						'has_comments' => false,
						'subject' => $processed['subject'],
						'tags' => !empty($processed['blog_tags']) ? explode(',', $processed['blog_tags']) : array(),
						'category' => !empty($context['zc']['blog_settings']['show_categories']) && !empty($processed['blog_category_id']) && !empty($context['zc']['categories'][$processed['blog_category_id']]['link']) ? $context['zc']['categories'][$processed['blog_category_id']]['link'] : '',
						'body' => $zcFunc['parse_bbc']($processed['body'], $processed['smileys_enabled']),
						'num_comments' => 0,
						'new_comment' => '<a href="#a0">'. $txt['b223'] .'</a>',
						'can_reply' => true,
						'show_comments' => true,
						'time' => timeformat(time(), false),
						'link' => '<a href="#a0">' . $processed['subject'] . '</a>',
						'poster' => array(
							'link' => !$context['user']['is_guest'] ? sprintf($context['zc']['link_templates']['user_profile'], $context['user']['id'], $context['user']['name'], ' title="' . $txt['b41'] . '"') : $context['user']['name'],
						),
						'options' => array(),
						'comments' => array(),
						'bookmarking_links' => array(),
					);
				}
				elseif (isset($_REQUEST['comment']) && !empty($article))
				{
					$context['zc']['form_info']['_info_']['preview_template'] = 'show_articles';
					
					zcLoadTemplate('Blog');
					
					$context['zc']['articles'] = array();
					$context['zc']['articles'][0] = array(
						'id' => 0,
						'has_comments' => false,
						'subject' => $txt['b364'],
						'tags' => array(),
						'category' => '',
						'body' => $txt['b365'],
						'num_comments' => 1,
						'new_comment' => '<a href="#a0">'. $txt['b223'] .'</a>',
						'can_reply' => true,
						'show_comments' => true,
						'time' => timeformat(time(), false),
						'link' => '<a href="#a0">' . $txt['b364'] . '</a>',
						'poster' => array(
							'link' => $txt['b366'],
						),
						'options' => array(),
						'comments' => array(
							0 => array(
								'id' => 0,
								'article_id' => $article,
								'body' => $zcFunc['parse_bbc']($processed['body'], $processed['smileys_enabled']),
								'options' => array(),
								'time' => timeformat(time(), false),
								'poster' => array(
									'link' => !$context['user']['is_guest'] ? sprintf($context['zc']['link_templates']['user_profile'], $context['user']['id'], $context['user']['name'], ' title="' . $txt['b41'] . '"') : $context['user']['name'],
								),
								'is_approved' => true,
							),
						),
						'bookmarking_links' => array(),
					);
				}/*
				elseif (isset($_REQUEST['poll']))
				{
					// !!! could not get this working quite right ... needs work
				
					$context['zc']['form_info']['_info_']['preview_template'] = 'sideWindows';
					$context['zc']['max_window_order'] = 1;
					$context['zc']['side_windows'] = array(1 => array());
					$context['zc']['side_windows'][1]['title'] = $txt['b225'];
					$context['zc']['side_windows'][1]['type'] = 'polls';
					$context['zc']['side_windows'][1]['info'] = array();
					
					$pollinfo = $processed;
					$pollinfo['question'] = stripslashes($pollinfo['question']);
					$pollOptions = $processed['choices'];
		
					$user_started = true;
					$is_expired = !empty($pollinfo['expire_time']) && $pollinfo['expire_time'] <= time();
					$is_locked = !empty($pollinfo['voting_locked']);
					
					$allow_vote = !$is_expired  && !$is_locked && !$user_started;
					$allow_poll_view = true;
			
					// Set up the basic poll information.
					$context['zc']['side_windows'][1]['info'] = array(
						'id' => 0,
						'image' => 'normal_' . (empty($pollinfo['voting_locked']) ? 'poll' : 'locked_poll'),
						'question' => $zcFunc['parse_bbc']($pollinfo['question']),
						'total_votes' => 0,
						'change_vote' => false,
						'is_locked' => $is_locked,
						'has_voted' => false,
						'is_expired' => $is_expired,
						'allow_vote' => $allow_vote,
						'allow_poll_view' => true,
						'allow_change_vote' => false,
						'show_results' => false,
						'expire_time' => !empty($pollinfo['expire_time']) ? timeformat($pollinfo['expire_time']) : 0,
						'options' => array(),
						'can_lock' => $context['can_moderate_blog'] || ((((($context['can_lock_own_polls_in_own_b'] && $user_started) || $context['can_lock_any_polls_in_own_b']) && $context['is_blog_owner']) || ($context['can_lock_own_polls_in_any_b'] && $user_started) || $context['can_lock_any_polls_in_any_b'])),
						'can_edit' => $context['can_moderate_blog'] || (($context['can_moderate_blog'] || empty($pollinfo['voting_locked'])) && (((($context['can_edit_own_polls_in_own_b'] && $user_started) || $context['can_edit_any_polls_in_own_b']) && $context['is_blog_owner']) || ($context['can_edit_own_polls_in_any_b'] && $user_started) || $context['can_edit_any_polls_in_any_b'])),
						'can_delete' => $context['can_moderate_blog'] || ((((($context['can_delete_own_polls_in_own_b'] && $user_started) || $context['can_delete_any_polls_in_own_b']) && $context['is_blog_owner']) || ($context['can_delete_own_polls_in_any_b'] && $user_started) || $context['can_delete_any_polls_in_any_b'])),
						'allowed_warning' => $pollinfo['max_votes'] > 1 ? sprintf($txt['poll_options6'], $pollinfo['max_votes']) : '',
						'starter' => array(
							'link' => '<a href="' . $scripturl . '?action=profile;u=' . $context['user']['id'] . '">' . $context['user']['name'] . '</a>',
						),
						'links' => array(),
					);
					
					// view result link
					if ($allow_poll_view)
						$context['zc']['side_windows'][1]['info']['links'][] = '<a href="#p0">'. $txt['b3008'] .'</a>';
						
					// lock voting link
					if ($context['zc']['side_windows'][1]['info']['can_lock'])
						$context['zc']['side_windows'][1]['info']['links'][] = '<a href="#p0">'. $txt['b3009'] .'</a>';
						
					// edit link
					if ($context['zc']['side_windows'][1]['info']['can_edit'])
						$context['zc']['side_windows'][1]['info']['links'][] = '<a href="#p0">'. $txt['b3012'] .'</a>';
						
					// remove link
					if ($context['zc']['side_windows'][1]['info']['can_delete'])
						$context['zc']['side_windows'][1]['info']['links'][] = '<a href="#p0">'. $txt['b3011'] .'</a>';
			
					// Now look through each option, and...
					foreach ($pollOptions as $i => $label)
					{
						$label = $zcFunc['un_htmlspecialchars']($label);
						// Now add it to the poll's contextual theme data.
						$context['zc']['side_windows'][1]['info']['options'][$i] = array(
							'id' => 'poll-'. 0 .'_options-' . $i,
							'option' => $zcFunc['parse_bbc']($label),
							'vote_button' => '<input type="' . ($pollinfo['max_votes'] > 1 ? 'checkbox' : 'radio') . '" name="options[]" id="poll-'. 0 .'_options-' . $i . '" value="' . $i . '" class="check" />'
						);
					}
				}*/
				// previewing something else?
				else
				{
					// plug-in slot #7
					zc_plugin_slot(7);
				}
				
				$template_func = 'zc_template_' . $context['zc']['form_info']['_info_']['preview_template'];
				$context['zc']['form_info']['_info_']['preview_template'] = function_exists($template_func) ? $template_func : '';
				
				// failed to load necessary template function for previewing...
				if (empty($context['zc']['form_info']['_info_']['preview_template']))
					$context['zc']['errors'][] = array('zc_error_53', (!empty($blog) ? $txt['b170'] : $txt['b359']));
			}
			// save as a draft?
			elseif (empty($context['zc']['processing_complete']) && !empty($_POST['button_save_as_draft']))
			{
				// make sure this can be saved as a draft... (look in form_info array for save_as_draft button)
				if (!isset($context['zc']['form_info']['_info_']['additional_submit_buttons']['save_as_draft']))
					zc_fatal_error('zc_error_27');
					
				// they aren't allowed to save articles as drafts?
				if (!$context['can_save_drafts'])
					zc_fatal_error(array('zc_error_17', 'b165'));
					
				require_once($zc['sources_dir'] . '/Subs-Drafts.php');
					
				zcSaveDraft($processed);
			}
			// spell checking?
			/*elseif (empty($context['zc']['processing_complete']) && !empty($_POST['button_spell_check']))
			{
				// !!! not available yet
				
				// make sure this can be spell-checked... (look in form_info array for spell check button)
				if (!isset($context['zc']['form_info']['_info_']['additional_submit_buttons']['spell_check']))
					zc_fatal_error('zc_error_30');
					
				$context['zc']['spell_checking'] = true;
					
				foreach ($processed as $k => $v)
				{
					// skip non-text fields...
					if ($context['zc']['form_info'][$encrypt_keys[$k]]['type'] != 'text')
						continue;
						
					$raw_string = trim($zcFunc['un_htmlspecialchars']($v));
					
					// skip if empty...
					if (empty($raw_string))
						continue;
					
					$_POST[$encrypt_keys[$k]] = zc_spell_check($raw_string);
				}
			}*/
			// we're posting then...
			elseif (empty($context['zc']['processing_complete']))
			{
				// plug-in slot #12
				zc_plugin_slot(12);
								
				// article?
				if (empty($context['zc']['processing_complete']) && isset($_REQUEST['article']) && !isset($_REQUEST['comment']))
				{
					require_once($zc['sources_dir'] . '/Subs-Articles.php');
					
					// update existing article...
					if (!empty($_REQUEST['article']))
						zcUpdateArticle($processed);
					// create new article...
					else
						zcCreateArticle($processed);
				}
				// comment?
				elseif (empty($context['zc']['processing_complete']) && isset($_REQUEST['comment']) && !empty($article))
				{
					require_once($zc['sources_dir'] . '/Subs-Comments.php');
					
					// update existing comment...
					if (!empty($_REQUEST['comment']))
						zcUpdateComment($processed);
					// create new comment...
					else
					{
						// make sure there were no new comments since the user began writing...
						if ($_POST['last_comment_id'] != $hidden_form_values['last_comment_id'])
							$context['zc']['errors'][] = 'zc_error_54';
						
						// still no errors?
						if (empty($context['zc']['errors']))
							zcCreateComment($processed);
					}
				}
				// poll?
				elseif (empty($context['zc']['processing_complete']) && isset($_REQUEST['poll']))
				{
					require_once($zc['sources_dir'] . '/Subs-Polls.php');
					
					// update existing poll...
					if (!empty($_REQUEST['poll']))
						zcUpdatePoll($processed);
					// create new poll...
					else
						zcCreatePoll($processed);
				}
				// posting something else?
				elseif (empty($context['zc']['processing_complete']))
				{
					// plug-in slot #6
					zc_plugin_slot(6, array($processed));
				}
			}
		}
	}
		
	// add random letters to field names and then encrypt... anti-spam and extra security
	if (!empty($context['zc']['form_info']) && !isset($processed))
	{
		$character_range = array_merge(range('a', 'h'), array('7', '$', '2', '8'), range('r', 'z'));
		
		$_SESSION['zc_rand_string'] = '';
		for ($i = 0; $i < 7; $i++)
			$_SESSION['zc_rand_string'] .= $character_range[array_rand($character_range)];
		
		$encrypt_keys = array();
		$redo_disable_others = array();
		$redo_disable_options = array();
		foreach ($context['zc']['form_info'] as $k => $array)
		{
			if (in_array($k, array('_info_')))
				continue;
				
			// unset and skip fields that shouldn't be used for whatever reason
			if (isset($array['must_return_true']) && $array['must_return_true'] !== true)
			{
				unset($context['zc']['form_info'][$k]);
				continue;
			}
				
			// are there any file upload fields?
			if (isset($array['type']) && $array['type'] == 'file')
				$context['zc']['file_upload_field_exists'] = true;
				
			$encrypt_keys[$k] = 'n' . sha1($k . '_' . $_SESSION['zc_rand_string']);
			
			if ($k == 'body')
				$context['zc']['post_box_name'] = $encrypt_keys[$k];
				
			$context['zc']['form_info'][$encrypt_keys[$k]] = $array;
			
			// this gets tricky...	
			if (isset($array['disable_others']))
				foreach ($array['disable_others'] as $other_field => $this_field_value)
				{
					$context['zc']['form_info'][$encrypt_keys[$k]]['disable_others']['n' . sha1(ereg_replace('[0-9]', '', $other_field) . '_' . $_SESSION['zc_rand_string']) . ereg_replace('[^0-9]', '', $other_field)] = $this_field_value;
					
					unset($context['zc']['form_info'][$encrypt_keys[$k]]['disable_others'][$other_field]);
				}
			
			// not quite as tricky...
			if (isset($array['disable_options']))
				foreach ($array['disable_options'] as $key => $stuff)
					if ($stuff !== false)
						$redo_disable_options[$encrypt_keys[$k]] = array($key, $stuff);
			
			// don't forget about $context['zc']['current_info']
			if (isset($context['zc']['current_info'][$k]))
			{
				$context['zc']['current_info'][$encrypt_keys[$k]] = $context['zc']['current_info'][$k];
				unset($context['zc']['current_info'][$k]);
			}
			
			unset($context['zc']['form_info'][$k]);
		}
		
		// now redo those disable options...
		if (!empty($redo_disable_options))
			foreach ($redo_disable_options as $encrypted_key => $array)
				$context['zc']['form_info'][$encrypted_key]['disable_options'][$array[0]] = array($encrypt_keys[$array[1][0]], $array[1][1]);
	
		if (!empty($need_load_blog_tags) && !empty($encrypt_keys['blog_tags']))
		{
			// load blog tags...
			list($context['zc']['tags'], $context['total_tag_instances']) = zcLoadBlogTags($blog);
			
			$temp = array();
			// use the tags info to make an array of links
			if (!empty($context['zc']['tags']))
				foreach ($context['zc']['tags'] as $tag)
					$temp[] = '<a href="javascript:void(0);" onclick="addStringToField(\'' . $tag['tag'] . '\', \'' . $encrypt_keys['blog_tags'] . '\');" style="font-size:' . zcTagFontSize($tag['num_articles'], $context['total_tag_instances']) . 'px; white-space:nowrap;">' . $tag['tag'] . '</a>';
			
			$context['zc']['tags'] = $temp;
		}
	}
	
	// Register this form in the session variables.
	zc_check_submit_once('register');
	
	zcAntiBot('prepare');
	
	$hidden_form_values['seqnum'] = $context['zc']['form_seqnum'];
	
	// add "delete draft upon posting" checkbox to form
	if (!empty($context['zc']['current_draft']))
		$context['zc']['form_info']['delete_draft'] = array(
			'type' => 'check',
			'value' => !empty($context['user']['blog_preferences']['delete_drafts_upon_posting']) ? 1 : 0,
			'label' => $txt['b205'],
		);
		
	// add the from request info to the form action url...
	if (!empty($_REQUEST['from']))
		$form_url .= ';from=' . $_REQUEST['from'];
		
	$context['zc']['form_info']['_info_']['hidden_form_values'] = $hidden_form_values;
	$context['zc']['form_info']['_info_']['form_name'] = 'thisform';
	$context['zc']['form_info']['_info_']['form_url'] = $form_url;
	
	if (!empty($context['zc']['current_info']))
		$context['zc']['form_info']['_info_']['primary_submit_text'] = $txt['b237'];
	
	if (!empty($context['page_title']))
		$context['zc']['link_tree']['action'] = $context['page_title'];
	
	// plug-in slot #3
	zc_plugin_slot(3);
}

?>