<?php

if (!defined('zc'))
	die('Hacking attempt...');

function zcReportToModerators()
{
	global $context, $txt, $scripturl, $comment, $article, $zcFunc, $blog, $zc;
	
	zcLoadTemplate('Post');
	zcLoadTemplate('Blog');
	
	// user has to have the report_to_moderator permission...
	if (!$context['can_report_to_moderator'] && !$context['user']['is_admin'])
		zc_fatal_error(array('zc_error_17', $txt['b238'] . ' ' . (!empty($comment) ? $txt['b130'] : $txt['b129'])));
		
	// both of these are empty?  Can't do anything then....
	if (empty($comment) && empty($article))
		zc_redirect_exit(!empty($context['zc']['blog_request']) ? $context['zc']['blog_request'] : 'zc');
	
	$hidden_form_values = array();
	$form_url = $scripturl . '?zc=' . $_REQUEST['zc'] . $context['zc']['blog_request'] . $context['zc']['article_request'] . (!empty($comment) ? ';comment=' . $comment : '');
	$context['zc']['sub_sub_template'] = 'post_form';
	
	$context['page_title'] = $txt['b241'];
		
	// trying to report a comment?
	if (!empty($comment))
		$request = $zcFunc['db_query']("
			SELECT c.poster_id, c.blog_id, c.article_id, t.subject, b.name AS blog_name
			FROM {db_prefix}comments AS c
				LEFT JOIN {db_prefix}articles AS t ON (t.article_id = c.article_id)
				LEFT JOIN {db_prefix}blogs AS b ON (b.blog_id = c.blog_id)
			WHERE comment_id = {int:comment_id}
			LIMIT 1", __FILE__, __LINE__,
			array(
				'comment_id' => $comment
			)
		);
	// or maybe an article?
	elseif (!empty($article))
		$request = $zcFunc['db_query']("
			SELECT t.poster_id, t.blog_id, t.article_id, t.subject, b.name AS blog_name
			FROM {db_prefix}articles AS t
				LEFT JOIN {db_prefix}blogs AS b ON (b.blog_id = t.blog_id)
			WHERE t.article_id = {int:article_id}
			LIMIT 1", __FILE__, __LINE__,
			array(
				'article_id' => $article
			)
		);
	
	// oops... doesn't exist!
	if ($zcFunc['db_num_rows']($request) == 0)
		zc_fatal_error(array('zc_error_38', (!empty($comment) ? $txt['b130'] : $txt['b129'])));
	// let's get some info about the comment or article...
	else
	{
		$row = $zcFunc['db_fetch_assoc']($request);
		$zcFunc['db_free_result']($request);
		// cannot report your own comments/articles!
		if (!$context['user']['is_guest'] && $row['poster_id'] == $context['user']['id'])
			zc_fatal_error(array('zc_error_37', (!empty($comment) ? $txt['b130'] : $txt['b129'])));
			
		$article = $row['article_id'];
		
		$row['subject'] = $zcFunc['un_htmlspecialchars']($row['subject']);
		
		if (!empty($row['blog_id']))
		{
			$blog = $row['blog_id'];
			$row['blog_name'] = $zcFunc['un_htmlspecialchars']($row['blog_name']);
		}
		// Community News....
		else
		{
			$blog = 0;
			$row['blog_name'] = $txt['b338'];
			$context['zc']['link_tree']['blog'] = $txt['b338'];
		}
			
		$context['zc']['link_tree']['article'] = '<a href="' . $scripturl . '?article=' . $article . '.0">' . $row['subject'] . '</a>';
		
		// prepare the form info!
		$context['zc']['form_info'] = prepareReportToModForm();
			
		// submitted report to moderator form?
		if (isset($_POST['seqnum']) && isset($_POST['sc']))
		{
			// verify that this isn't a double post...
			zc_check_submit_once('check');
			
			// verify user session
			checkSession('post');
			
			zcAntiBot('check');
			
			// form processing...
			if (empty($context['zc']['errors']))
				list($processed, $context['zc']['errors']) = zcProcessForm($context['zc']['form_info']);
				
			// still no errors?  Well alright then...
			if (empty($context['zc']['errors']))
			{
				// who is doing the reporting?
				$from = array(
					'id' => !empty($context['user']['id']) ? $context['user']['id'] : 0,
					'name' => $context['user']['is_guest'] ? $txt['b567'] : $context['user']['name'],
					'username' => $context['user']['is_guest'] ? $txt['b567'] : $context['user']['username'],
				);
				
				$subject = !empty($processed['subject']) ? $zcFunc['un_htmlspecialchars']($processed['subject']) : $txt['b240'];
				$message = !empty($processed['message']) ? $zcFunc['un_htmlspecialchars']($processed['message']) : '';
				
				$post_href = $scripturl . '?article=' . $article . '.0';
				
				if (!empty($comment))
					$post_href .= '#c' . $comment;
					
				$link_to_blog = !empty($blog) ? '[url=' . $scripturl . '?blog=' . $blog . '.0]' . $row['blog_name'] . '[/url]' : '[b]' . $row['blog_name'] . '[/b]';
				
				// add link to comment/article being reported at the end of the message...
				$message .= '[br][br]You have received this notification, because you are a moderator of ' . $link_to_blog . '[br][url=' . $post_href . ']' . $txt['b239'] . ' ' . (!empty($comment) ? $txt['b171'] : $txt['b170']) . '[/url]';
				
				$recipients = array('to' => array(), 'bcc' => array());
				
				// get the blog owner's info and the moderators
				if (!empty($blog))
				{
					$request = $zcFunc['db_query']("
						SELECT blog_owner, moderators
						FROM {db_prefix}blogs
						WHERE blog_id = {int:blog_id}
						LIMIT 1", __FILE__, __LINE__,
						array(
							'blog_id' => $blog
						)
					);
					while ($this_blog = $zcFunc['db_fetch_assoc']($request))
					{
						$moderators = !empty($this_blog['moderators']) ? explode(',', $this_blog['moderators']) : array();
						$moderators[] = $this_blog['blog_owner'];
					}
				}
				// this is Community News... so let's get the administrators
				else
				{
					$request = $zcFunc['db_query']("
						SELECT {tbl:members::column:id_member} AS id_member
						FROM {db_prefix}{table:members}
						WHERE {tbl:members::column:id_group} = 1", __FILE__, __LINE__);
					$moderators = array();
					while ($admins = $zcFunc['db_fetch_assoc']($request))
						$moderators[] = $admins['id_member'];
				}
				$zcFunc['db_free_result']($request);
				
				// now let's get all the moderators of this blog...
				if (!empty($moderators))
				{
					$request = $zcFunc['db_query']("
						SELECT mem.{tbl:members::column:member_name} AS member_name, mem.{tbl:members::column:id_member} AS id_member, pref.report_to_mod_notices
						FROM {db_prefix}{table:members} AS mem
							LEFT JOIN {db_prefix}preferences AS pref ON (pref.member_id = mem.{tbl:members::column:id_member})
						WHERE mem.{tbl:members::column:id_member} IN ({array_int:moderators})", __FILE__, __LINE__,
						array(
							'moderators' => $moderators
						)
					);
					while ($mod = $zcFunc['db_fetch_assoc']($request))
					{
						// they just want to be left alone!
						if (empty($mod['report_to_mod_notices']))
							continue;
							
						// they only want to receive notices about their blogs, not anyone else's...
						if ($mod['report_to_mod_notices'] == 1 && isset($this_blog['blog_owner']) && $this_blog['blog_owner'] != $mod['id_member'])
							continue;
							
						// alright... send them a notification....
						$recipients['to'][$mod['id_member']] = $zcFunc['un_htmlspecialchars']($mod['member_name']);
					}
					$zcFunc['db_free_result']($request);
				}
			
				// send it!
				if (!empty($recipients['to']) && !empty($message))
				{
					require_once($zc['sources_dir'] . '/Subs-PM.php');
					zc_send_pm($recipients, $subject, $message, false, $from);
				}
				
				zc_redirect_exit(zcRequestVarsToString('zc'));
			}
		}
	
		// Register this form in the session variables.
		zc_check_submit_once('register');
	
		$hidden_form_values['seqnum'] = $context['zc']['form_seqnum'];
		
		// add the from request info to the form action url...
		if (!empty($_REQUEST['from']))
			$form_url .= ';from=' . $_REQUEST['from'];
			
		$context['zc']['form_info']['_info_']['hidden_form_values'] = $hidden_form_values;
		$context['zc']['form_info']['_info_']['form_name'] = 'thisform';
		$context['zc']['form_info']['_info_']['form_url'] = $form_url;
		
		if (!empty($context['page_title']))
			$context['zc']['link_tree']['action'] = $context['page_title'];;
			
		zcAntiBot('prepare');
	}
}

function prepareReportToModForm()
{
	global $context, $txt, $zc;
	
	return array(
		'_info_' => array(
			'template_info' => array(
				'left_column_width' => '25%',
				'right_column_width' => '75%',
			),
			'primary_submit_text' => $txt['b238a'],
		),
		'subject' => array(
			'type' => 'text',
			'value' => '',
			'max_length' => 120,
			'parses_bbc' => true,
			'label' => 'b3032',
			'use_substr_to_shorten' => true,
			'field_width' => '240px',
		),
		'message' => array(
			'type' => 'text',
			'custom' => 'textarea',
			'value' => '',
			'max_length' => 1000,
			'parses_bbc' => true,
			'label' => 'b173',
			'required' => true,
			'ta_rows' => 20,
			'template_above_field' => !empty($context['user']['blog_preferences']['show_bbc_cloud']) || !empty($zc['settings']['show_bbc_cloud_for_guests']) ? 'bbcCloud' : '',
			'chop_words' => true,
		),
	);
}

function zcNotifyOnOff()
{
	global $context, $txt, $scripturl, $article, $zcFunc, $blog, $blog_info;
	
	// nothing to do...
	if (empty($article) && empty($blog))
		zc_redirect_exit('zc');
		
	$turn_on = isset($_REQUEST['sa']) && $_REQUEST['sa'] == 'on';
	
	// if sesc request is empty... we have to show them a confirm page...
	if (empty($_REQUEST['sesc']))
	{
		if (!empty($blog))
			$context['zc']['blog_names'] = array($blog => '<a href="' . $scripturl . '?blog=' . $blog . '.0">' . $blog_info['name'] . '</a>');
		else
		{
			// get the subject of the article...
			$request = $zcFunc['db_query']("
				SELECT subject
				FROM {db_prefix}articles
				WHERE article_id = {int:article_id}
				LIMIT 1", __FILE__, __LINE__,
				array(
					'article_id' => $article
				)
			);
				
			if ($zcFunc['db_num_rows']($request) > 0)
				$row = $zcFunc['db_fetch_assoc']($request);
			$zcFunc['db_free_result']($request);
			
			if (!empty($row))
				$context['zc']['article_names'] = array($article => $zcFunc['un_htmlspecialchars']($row['subject'])) . ' ' . $txt['b3007'] . ' <a href="' . $scripturl . '?blog=' . $blog . '.0">' . $blog_info['name'] . '</a>';
		}
		
		$context['blog_control_panel'] = false;
		$context['page_title'] = $txt['b278'];
		$context['zc']['requires_confirmation'] = array(!empty($article) ? $article : $blog);
		$context['zc']['confirm_text'] = sprintf($txt['b264'], ($turn_on ? $txt['b113a'] : $txt['b114a']), (!empty($article) ? $txt['b130'] : $txt['b129']), (!empty($article) ? $txt['b170'] : $txt['b3003']));
		$context['zc']['sub_sub_template'] = 'zc_sandwich';
		$context['zc']['sandwich_inner_template'] = 'confirmation_page';
		$context['zc']['confirm_href'] = $scripturl . '?zc=notify' . (!empty($article) ? ';article=' . $article : ';blog=' . $blog) . '.0;sa=' . ($turn_on ? 'on' : 'off') . ';sesc=' . $context['session_id'];
		$context['zc']['cancel_href'] = $scripturl . '?' . (!empty($article) ? 'article=' . $article : 'blog=' . $blog . '.0');
	}
	// sesc request is not empty...
	else
	{
		// session check!
		checkSession('get');
		
		// they aren't allowed to mark notify!
		if (!$context['can_mark_notify'])
			zc_fatal_error();
		
		// delete any previous notification log for this user and article or blog
		$zcFunc['db_query']("
			DELETE FROM {db_prefix}log_notify
			WHERE " . (!empty($article) ? "article_id = {int:article_id}" : "blog_id = {int:blog_id}") . "
				AND member_id = {int:user_id}", __FILE__, __LINE__,
			array(
				'user_id' => $context['user']['id'],
				'blog_id' => $blog,
				'article_id' => $article
			)
		);
		
		$blog_id = !empty($article) ? 0 : $blog;
		
		// do we want to enable notifications for a blog or article?
		if ($turn_on)
			// insert the new notification into the blog_log_notify table...
			$zcFunc['db_insert']('insert', '{db_prefix}log_notify', array('member_id' => 'int', 'article_id' => 'int', 'blog_id' => 'int', 'sent' => 'int'), array('member_id' => $context['user']['id'], 'article_id' => $article, 'blog_id' => $blog_id, 'sent' => 0));
					
		// send them back where they came from...
		zcReturnToOrigin();
	}
}

function zcSendNotifications($subject = null, $body)
{
	global $context, $scripturl, $txt;
	global $article, $zcFunc, $blog, $zc;
	
	zc_censor_text($body);
	$body = trim(strip_tags($zcFunc['un_htmlspecialchars'](strtr($zcFunc['parse_bbc']($body, false), array('<br />' => "\n", '</div>' => "\n", '</li>' => "\n", '&#91;' => '[', '&#93;' => ']')))));
	
	if (!empty($article))
		$text_strings = array(
			'message' => 'b1001',
			'subject' => 'b1000',
			'notify_once' => 'b1004',
			'text_below' => 'b1002',
			'unsubscribe' => 'b1008',
		);
	else
		$text_strings = array(
			'message' => 'b1005',
			'subject' => 'b1006',
			'notify_once' => 'b1007',
			'text_below' => 'b1003',
			'unsubscribe' => 'b1009',
		);
		
	if (!empty($article) && $subject === null)
	{
		// get the subject of this article...
		$request = $zcFunc['db_query']("
			SELECT subject
			FROM {db_prefix}articles
			WHERE article_id = {int:article_id}
			LIMIT 1", __FILE__, __LINE__,
			array(
				'article_id' => $article
			)
		);
		
		// that's not right...
		if ($zcFunc['db_num_rows']($request) == 0)
			zc_fatal_error();
		
		$article_info = $zcFunc['db_fetch_assoc']($request);
		$subject = $article_info['subject'];
		$zcFunc['db_free_result']($request);
	}
	
	$subject = $zcFunc['un_htmlspecialchars']($subject);
	zc_censor_text($subject);
	
	// get info about the users who may be getting notifications...
	$request = $zcFunc['db_query']("
		SELECT 
			mem.{tbl:members::column:id_member} AS id_member, mem.{tbl:members::column:email_address} AS email_address, mem.{tbl:members::column:lngfile} AS lngfile, mem.{tbl:members::column:id_post_group} AS id_post_group, mem.{tbl:members::column:id_group} AS id_group, mem.{tbl:members::column:additional_groups} AS additional_groups,
			ln.sent, b.member_groups" . (!empty($article) ? ", t.poster_id" : '') . ", p.send_body_with_notifications, p.notify_once
		FROM ({db_prefix}log_notify AS ln, {db_prefix}{table:members} AS mem" . (!empty($article) ? ", {db_prefix}articles AS t" : '') . ", {db_prefix}blogs AS b, {db_prefix}preferences AS p)
		WHERE " . (!empty($article) ? "ln.article_id = {int:article_id}
			AND t.article_id = {int:article_id}
			AND b.blog_id = t.blog_id" : "ln.blog_id = {int:blog_id}
			AND b.blog_id = {int:blog_id}") . "
			AND mem.{tbl:members::column:id_member} != {int:user_id}
			AND mem.{tbl:members::column:is_activated} = 1
			AND ln.member_id = mem.{tbl:members::column:id_member}
			AND p.member_id = mem.{tbl:members::column:id_member}
		GROUP BY mem.{tbl:members::column:id_member}
		ORDER BY mem.{tbl:members::column:lngfile}", __FILE__, __LINE__,
		array(
			'user_id' => $context['user']['id'],
			'blog_id' => $blog,
			'article_id' => $article
		)
	);
	
	require_once($zc['sources_dir'] . '/Subs-Mail.php');
	
	$sent = 0;
	$sent_to_members = array();
	while ($row = $zcFunc['db_fetch_assoc']($request))
	{
		// if they aren't in the admin group, then check to see if they are allowed...
		if ($row['id_group'] != 1)
		{
			$allowed = explode(',', $row['member_groups']);
			$row['additional_groups'] = explode(',', $row['additional_groups']);
			$row['additional_groups'][] = $row['id_group'];
			$row['additional_groups'][] = $row['id_post_group'];

			if (count(array_intersect($allowed, $row['additional_groups'])) == 0)
				continue;
		}

		$needed_language = empty($row['lngfile']) || empty($modSettings['userLanguage']) ? $zc['language'] : $row['lngfile'];
		if (empty($current_language) || $current_language != $needed_language)
			$current_language = zcLoadLanguage('Notify', $needed_language, false);

		$message = sprintf($txt[$text_strings['message']], $context['user']['name']);
		$message .= 
			$scripturl . '?blog=' . $blog . (!empty($article) ? ';article=' . $article : '') .'.0#new' . "\n\n" .
			$txt[$text_strings['unsubscribe']] . ': ' . $scripturl . '?zc=notify;sa=off;blog='. $blog . (!empty($article) ? ';article=' . $article : '') . '.0';
			
		// Do they want the body of the comment or article sent too?
		if (!empty($row['send_body_with_notifications']) && empty($zc['settings']['disallow_send_body']))
			$message .= "\n\n" . $txt[$text_strings['text_below']] . "\n\n" . $body;
			
		// if they only want to receive one notice, we should remind them about that...
		if (!empty($row['notify_once']))
			$message .= "\n\n" . $txt[$text_strings['notify_once']];
			
		// add "Regards, \nThe <forum's name> Team" to the end of the message
		$message .= "\n\n" . $txt['b1010'];

		// Send only if once is off or it's on and it hasn't been sent.
		if (empty($row['notify_once']) || empty($row['sent']))
		{
			zc_send_mail($row['email_address'], sprintf($txt[$text_strings['subject']], $subject), $message, null);
			$sent++;
			
			$sent_to_members[] = $row['id_member'];
		}
	}
	$zcFunc['db_free_result']($request);
	
	// update the logs...
	if (!empty($sent_to_members))
		$zcFunc['db_update'](
			'{db_prefix}log_notify',
			array('blog_id' => 'int', 'article_id' => 'int', 'sent' => 'int'),
			array('sent' => 1),
			array_merge(
				array('member_id' => array('IN', $sent_to_members)),
				!empty($article) ? array('article_id' => $article) : array('blog_id' => $blog)
			),
			count($sent_to_members));
}

?>