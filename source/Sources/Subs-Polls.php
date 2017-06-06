<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
function zcDeletePoll()
{
	global $txt, $context;
	global $poll, $blog, $zcFunc, $blog_info;
	
	checkSession('get');
	
	// nothing to delete!
	if (empty($poll))
		zcReturnToOrigin();
		
	// this needs to have been loaded
	if (empty($blog_info))
		zc_fatal_error('zc_error_22');
		
	// get poll info
	$request = $zcFunc['db_query']("
		SELECT poster_id
		FROM {db_prefix}polls
		WHERE poll_id = {int:poll_id}
		LIMIT 1", __FILE__, __LINE__,
		array(
			'poll_id' => $poll
		)
	);
	
	// poll didn't exist!
	if ($zcFunc['db_num_rows']($request) == 0)
	{
		$zcFunc['db_free_result']($request);
		zcReturnToOrigin();
	}
	$row = $zcFunc['db_fetch_assoc']($request);
	$zcFunc['db_free_result']($request);
	
	$user_started = $row['poster_id'] == $context['user']['id'];

	$can_do_this = $context['can_moderate_blog'] || (($context['can_delete_any_polls_in_any_b'] || ($context['can_delete_any_polls_in_own_b'] && $context['is_blog_owner']) || ($context['can_delete_own_polls_in_any_b'] && $user_started) || ($context['can_delete_own_polls_in_own_b'] && $context['is_blog_owner'] && $user_started)));
	
	// they are not allowed to delete this poll!
	if ($can_do_this !== true)
		zc_fatal_error(array('zc_error_17', 'b185'));
	
	// delete the poll
	$zcFunc['db_query']("
		DELETE FROM {db_prefix}polls
		WHERE poll_id = {int:poll_id}
		LIMIT 1", __FILE__, __LINE__,
		array(
			'poll_id' => $poll
		)
	);
		
	// delete poll_choices associated with this poll
	$zcFunc['db_query']("
		DELETE FROM {db_prefix}poll_choices
		WHERE poll_id = {int:poll_id}", __FILE__, __LINE__,
		array(
			'poll_id' => $poll
		)
	);
		
	// delete logs associated with this poll
	$zcFunc['db_query']("
		DELETE FROM {db_prefix}log_polls
		WHERE poll_id = {int:poll_id}", __FILE__, __LINE__,
		array(
			'poll_id' => $poll
		)
	);
			
	// redirect back to where they came from...
	zcReturnToOrigin();
}

function zcCreatePoll($processed)
{
	global $context, $txt;
	global $blog, $zcFunc;
	
	if (empty($blog) || empty($processed))
		zc_fatal_error();
		
	$processed['blog_id'] = $blog;
	$processed['poster_name'] = !empty($processed['poster_name']) ? $processed['poster_name'] : $context['user']['name'];
	$processed['poster_id'] = !empty($context['user']['id']) ? $context['user']['id'] : 0;
	$processed['posted_time'] = time();
	
	// convert the # of days they entered for this value to seconds...
	$processed['expire_time'] = !empty($processed['expire_time']) ? (($processed['expire_time'] * 86400) + time()) : 0;
	
	// store the choices until after we create the poll
	$temp_choices = $processed['choices'];
	
	// don't need these anymore...
	if (!empty($context['zc']['form_info']['_info_']['exclude_from_table']))
		foreach($context['zc']['form_info']['_info_']['exclude_from_table'] as $k)
			unset($processed[$k]);
		
	$columns = array();
	foreach ($processed as $k => $dummy)
		$columns[$k] = isset($context['zc']['form_info'][$k]['type']) ? $context['zc']['form_info'][$k]['type'] : 'string';
		
	// inserts the poll into the database
	$zcFunc['db_insert']('insert', '{db_prefix}polls', $columns, $processed);
	$poll_id = $zcFunc['db_insert_id']();
	
	if (empty($poll_id))
		zc_fatal_error();
		
	// delete any pre-existing poll_choices this poll might have....
	$zcFunc['db_query']("
		DELETE FROM {db_prefix}poll_choices
		WHERE poll_id = {int:poll_id}", __FILE__, __LINE__,
		array(
			'poll_id' => $poll_id
		)
	);
		
	$columns = array('poll_id' => 'int', 'choice_id' => 'int', 'label' => 'string');
	$data = array();
	foreach ($temp_choices as $choice_id => $label)
		$data[] = array(
			'poll_id' => $poll_id,
			'choice_id' => $choice_id,
			'label' => $label
		);
		
	// insert choices into poll_choices table
	$zcFunc['db_insert']('insert', '{db_prefix}poll_choices', $columns, $data);
			
	// redirect back to where they came from...
	zcReturnToOrigin();
}
	
function zcUpdatePoll($processed)
{
	global $context, $txt;
	global $zcFunc, $poll, $blog;
	
	if (empty($blog) || empty($processed) || empty($poll))
		zc_fatal_error();
	
	if (empty($context['zc']['no_last_edit']))
	{
		$processed['last_edit_name'] = $context['user']['is_guest'] ? $txt['b567'] : $context['user']['name'];
		$processed['last_edit_time'] = time();
	}
	
	// maybe they don't mean to change the expire_time?
	if ($processed['expire_time'] == $processed['expire_time_hidden'])
		unset($processed['expire_time']);
	
	// convert the # of days they entered for this value to seconds...
	if (isset($processed['expire_time']))
		$processed['expire_time'] = !empty($processed['expire_time']) ? (($processed['expire_time'] * 86400) + time()) : 0;
	
	// store the choices until after we create the poll
	$temp_choices = $processed['choices'];
		
	// don't need these anymore...
	if (!empty($context['zc']['form_info']['_info_']['exclude_from_table']))
		foreach($context['zc']['form_info']['_info_']['exclude_from_table'] as $k)
			unset($processed[$k]);
	
	$columns = array();
	foreach ($processed as $k => $v)
		$columns[$k] = isset($context['zc']['form_info'][$k]['type']) ? $context['zc']['form_info'][$k]['type'] : 'string';
	
	// update poll in blog_polls table
	$zcFunc['db_update'](
		'{db_prefix}polls',
		$columns,
		$processed,
		array('poll_id' => $poll));
		
	// get vote totals for old choices....
	$request = $zcFunc['db_query']("
		SELECT choice_id, votes
		FROM {db_prefix}poll_choices
		WHERE poll_id = {int:poll_id}", __FILE__, __LINE__,
		array(
			'poll_id' => $poll
		)
	);
	$old_choices = array();
	while ($row = $zcFunc['db_fetch_assoc']($request))
		$old_choices[$row['choice_id']] = $row['votes'];
	$zcFunc['db_free_result']($request);
		
	$columns = array('poll_id' => 'int', 'choice_id' => 'int', 'label' => 'string', 'votes' => 'int');
	$data = array();
	foreach ($temp_choices as $choice_id => $label)
	{
		if (!isset($max_choice_id) || $choice_id > $max_choice_id)
			$max_choice_id = $choice_id;
			
		$data[] = array(
			'poll_id' => $poll,
			'choice_id' => $choice_id,
			'label' => $label,
			'votes' => isset($old_choices[$choice_id]) ? $old_choices[$choice_id] : 0
		);
	}
		
	// delete the votes that are having their choices removed...
	$zcFunc['db_query']("
		DELETE FROM {db_prefix}log_polls
		WHERE poll_id = {int:poll_id}
			AND choice_id > {int:max_choice_id}", __FILE__, __LINE__,
		array(
			'poll_id' => $poll,
			'max_choice_id' => $max_choice_id
		)
	);
		
	// delete any pre-existing poll_choices this poll might have....
	$zcFunc['db_query']("
		DELETE FROM {db_prefix}poll_choices
		WHERE poll_id = {int:poll_id}", __FILE__, __LINE__,
		array(
			'poll_id' => $poll
		)
	);
		
	// insert choices into poll_choices table
	$zcFunc['db_insert']('insert', '{db_prefix}poll_choices', $columns, $data);
			
	// redirect back to where they came from...
	zcReturnToOrigin();
}

function zcLockUnlockPoll()
{
	global $context, $txt;
	global $zcFunc, $blog_info, $poll;
	
	checkSession('get');
	
	// nothing to lock!
	if (empty($poll))
		zcReturnToOrigin();
		
	// this needs to have been loaded
	if (empty($blog_info))
		zc_fatal_error('zc_error_22');
		
	// get info about this poll
	$request = $zcFunc['db_query']("
		SELECT poster_id, voting_locked
		FROM {db_prefix}polls
		WHERE poll_id = {int:poll_id}
		LIMIT 1", __FILE__, __LINE__,
		array(
			'poll_id' => $poll
		)
	);
	
	// poll didn't exist!
	if ($zcFunc['db_num_rows']($request) == 0)
	{
		$zcFunc['db_free_result']($request);
		zcReturnToOrigin();
	}
	$row = $zcFunc['db_fetch_assoc']($request);
	$zcFunc['db_free_result']($request);
				
	$user_started = $row['poster_id'] == $context['user']['id'];

	$can_do_this = $context['can_moderate_blog'] || (($context['can_lock_any_polls_in_any_b'] || ($context['can_lock_any_polls_in_own_b'] && $context['is_blog_owner']) || ($context['can_lock_own_polls_in_any_b'] && $user_started) || ($context['can_lock_own_polls_in_own_b'] && $context['is_blog_owner'] && $user_started)));
	
	// they are not allowed to lock this poll!
	if ($can_do_this !== true)
		zc_fatal_error(array('zc_error_17', 'b202'));
		
	$voting_locked = empty($row['voting_locked']) ? 1 : 0;
		
	// lock/unlock poll...
	$zcFunc['db_update'](
		'{db_prefix}polls',
		array('poll_id' => 'int', 'voting_locked' => 'int'),
		array('voting_locked' => $voting_locked),
		array('poll_id' => $poll));
			
	// redirect back to where they came from...
	zcReturnToOrigin();
}

function zcCastVotePoll()
{
	global $context, $txt;
	global $zcFunc, $poll;
	
	checkSession('post');
	
	if (empty($poll))
		zcReturnToOrigin();
	
	// get info about the poll
	$request = $zcFunc['db_query']("
		SELECT poster_id, voting_locked, max_votes, change_vote
		FROM {db_prefix}polls
		WHERE poll_id = {int:poll_id}
		LIMIT 1", __FILE__, __LINE__,
		array(
			'poll_id' => $poll
		)
	);
	
	// poll didn't exist!
	if ($zcFunc['db_num_rows']($request) == 0)
	{
		$zcFunc['db_free_result']($request);
		zcReturnToOrigin();
	}
	$poll_info = $zcFunc['db_fetch_assoc']($request);
	$zcFunc['db_free_result']($request);
	
	// poll starter cannot vote!
	if ($poll_info['poster_id'] == $context['user']['id'])
		zc_fatal_error('zc_error_28');

	$can_do_this = $context['can_moderate_blog'] || ((empty($poll_info['voting_locked']) || $context['can_moderate_blog']) && (empty($old_choices) || !empty($poll_info['change_vote'])) && $context['can_vote_in_polls']);
		
	// are they allowed to vote?
	if (!$can_do_this || $context['user']['is_guest'])
		zc_fatal_error(array('zc_error_17', 'b203'));
	
	// get any/all votes this user might have cast already for this poll
	$request = $zcFunc['db_query']("
		SELECT choice_id
		FROM {db_prefix}log_polls
		WHERE poll_id = {int:poll_id}
			AND member_id = {int:user_id}", __FILE__, __LINE__,
		array(
			'poll_id' => $poll,
			'user_id' => $context['user']['id']
		)
	);
	$old_choices = array();
	while ($row = $zcFunc['db_fetch_assoc']($request))
		$old_choices[] = $row['choice_id'];
	$zcFunc['db_free_result']($request);
		
	$new_choices = array();
	$insertVotes = array();
	// figure out what they voted for based on the post data...
	if (!empty($_POST['options']))
		foreach ($_POST['options'] as $dummy => $choice_id)
		{
			$new_choices[] = $choice_id;
			$insertVotes[] = array(
				'poll_id' => $poll,
				'choice_id' => $choice_id,
				'member_id' => $context['user']['id']
			);
		}
	
	// did they exceed the number of votes allowed?
	if (count($new_choices) > $poll_info['max_votes'])
		$context['zc']['errors'][] = array('zc_error_41', $poll_info['max_votes']);
		
	if (count($new_choices) == 0)
		$context['zc']['errors'][] = 'zc_error_42';
	
	// no errors?
	if (empty($context['zc']['errors']))
	{
		if (!empty($old_choices))
		{
			// remove this user's votes for these choices...
			$zcFunc['db_query']("
				DELETE FROM {db_prefix}log_polls
				WHERE choice_id IN ({array_int:choices})
					AND poll_id = {int:poll_id}
					AND member_id = {int:user_id}
				LIMIT {int:limit}", __FILE__, __LINE__,
				array(
					'choices' => $old_choices,
					'limit' => count($old_choices),
					'poll_id' => $poll,
					'user_id' => $context['user']['id']
				)
			);
				
			// decrease each of these choice's vote total
			$zcFunc['db_update'](
				'{db_prefix}poll_choices',
				array('poll_id' => 'int', 'choice_id' => 'int', 'votes' => 'int'),
				array('votes' => array('-', 1)),
				array('poll_id' => $poll, 'choice_id' => array('IN', $old_choices)),
				count($old_choices));
		}
		
		// cast their new vote(s)!
		if (!empty($insertVotes))
			$zcFunc['db_insert'](
				'insert',
				'{db_prefix}log_polls',
				array('poll_id' => 'int', 'choice_id' => 'int', 'member_id' => 'int'), 
				$insertVotes);
				
		// update each choice's vote total
		if (!empty($new_choices))
			$zcFunc['db_update'](
				'{db_prefix}poll_choices',
				array('poll_id' => 'int', 'choice_id' => 'int', 'votes' => 'int'),
				array('votes' => array('+', 1)),
				array('poll_id' => $poll, 'choice_id' => array('IN', $new_choices)),
				count($new_choices));
				
		// redirect back to where they came from...
		zcReturnToOrigin();
	}
	else
	{
		if (!empty($_REQUEST['blog']))
			unset($_REQUEST['article']);
			
		$_REQUEST['zc'] = '';
		return zC_START();
	}
}

function zcGetPolls($polls = null, $blogs = null, $members = null, $limit = null)
{
	global $context, $txt, $scripturl, $settings;
	global $blog, $article, $zcFunc;
	
	$return = array();
	
	// do we want to get info about specific polls?
	if ($polls !== null)
	{
		if (!empty($polls) && !is_array($polls))
			$polls = explode(',', $polls);
		elseif (empty($polls))
			$polls = array();
	}
	else
		$polls = array();
	
	// what blog(s) do we want to get polls from?
	if ($blogs !== null)
	{
		if (!empty($blogs) && !is_array($blogs))
			$blogs = explode(',', $blogs);
		elseif (empty($blogs))
			$blogs = array();
	}
	else
		$blogs = array();
	
	$default_limit = 2;
	
	// do we want to limit the # of polls we get (0 for no limit)?
	if ($limit !== null)
		$limit_polls = $limit;
	// this is a single blog....
	elseif (!empty($blog))
		$limit_polls = !empty($context['zc']['blog_settings']['limit_num_polls']) ? $context['zc']['blog_settings']['limit_num_polls'] : (isset($context['zc']['blog_settings']['limit_num_polls']) && empty($context['zc']['blog_settings']['limit_num_polls']) ? 0 : $default_limit);
	// default limit....
	else
		$limit_polls = $default_limit;
	
	// get info about the polls
	$request = $zcFunc['db_query']("
		SELECT
			p.poll_id, p.question, p.voting_locked, p.hide_results, p.expire_time, p.max_votes, p.change_vote,
			p.poster_id, IFNULL(mem.{tbl:members::column:real_name}, p.poster_name) AS poster_name,
			COUNT(DISTINCT lp.member_id) AS total
		FROM {db_prefix}polls AS p
			LEFT JOIN {db_prefix}log_polls AS lp ON (lp.poll_id = p.poll_id)
			LEFT JOIN {db_prefix}{table:members} AS mem ON (mem.{tbl:members::column:id_member} = p.poster_id)
		WHERE " . (!empty($polls) ? "p.poll_id IN ({array_int:polls})" : (!empty($blogs) ? "p.blog_id IN ({array_int:blogs})" : "p.blog_id = {int:blog_id}")) . "
		GROUP BY p.poll_id
		ORDER BY p.posted_time DESC" . (!empty($limit_polls) ? "
		LIMIT {int:limit}" : ''), __FILE__, __LINE__,
		array(
			'limit' => $limit_polls,
			'blog_id' => $blog,
			'blogs' => $blogs,
			'polls' => $polls
		)
	);
	$return = array();
	while ($pollinfo = $zcFunc['db_fetch_assoc']($request))
	{
		$pollinfo['question'] = $zcFunc['un_htmlspecialchars']($pollinfo['question']);
		$pollinfo['poster_name'] = $zcFunc['un_htmlspecialchars']($pollinfo['poster_name']);
		// Get all the options, and calculate the total votes.
		$request_pc = $zcFunc['db_query']("
			SELECT pc.choice_id, pc.label, pc.votes, IFNULL(lp.choice_id, -1) AS votedThis
			FROM {db_prefix}poll_choices AS pc
				LEFT JOIN {db_prefix}log_polls AS lp ON (lp.choice_id = pc.choice_id AND lp.poll_id = $pollinfo[poll_id] AND lp.member_id = {int:user_id})
			WHERE pc.poll_id = {int:poll_id}
			ORDER BY pc.choice_id", __FILE__, __LINE__,
			array(
				'user_id' => $context['user']['id'],
				'poll_id' => $pollinfo['poll_id']
			)
		);
		$pollOptions = array();
		$realtotal = 0;
		$pollinfo['has_voted'] = false;
		while ($row = $zcFunc['db_fetch_assoc']($request_pc))
		{
			$row['label'] = $zcFunc['un_htmlspecialchars']($row['label']);
			zc_censor_text($row['label']);
			$pollOptions[$row['choice_id']] = $row;
			$realtotal += $row['votes'];
			$pollinfo['has_voted'] |= $row['votedThis'] != -1;
		}
		$zcFunc['db_free_result']($request_pc);
		
		$user_started = $context['user']['id'] == $pollinfo['poster_id'];
		$has_voted = !empty($pollinfo['has_voted']);
		$change_vote = !empty($pollinfo['change_vote']);
		$is_expired = !empty($pollinfo['expire_time']) && $pollinfo['expire_time'] <= time();
		$is_locked = !empty($pollinfo['voting_locked']);
		
		$allow_vote = !$is_expired && !$context['user']['is_guest'] && $context['can_vote_in_polls'] && !$is_locked && !$has_voted && !$user_started;
		$allow_poll_view = $context['can_moderate_blog'] || $pollinfo['hide_results'] == 0 || ($pollinfo['hide_results'] == 1 && $has_voted) || $is_expired;
		$allow_change_vote = !$is_expired && !$context['user']['is_guest'] && $context['can_vote_in_polls'] && !$is_locked && $has_voted && $change_vote;
		$show_results = $allow_poll_view && (!isset($_REQUEST['changeVote']) || !$allow_change_vote) && (!$allow_vote || (isset($_REQUEST['viewResults']) && isset($_REQUEST['poll']) && $_REQUEST['poll'] == $pollinfo['poll_id']));

		// Set up the basic poll information.
		$return[$pollinfo['poll_id']] = array(
			'id' => $pollinfo['poll_id'],
			'image' => 'normal_' . (empty($pollinfo['voting_locked']) ? 'poll' : 'locked_poll'),
			'question' => $zcFunc['parse_bbc']($pollinfo['question']),
			'total_votes' => $pollinfo['total'],
			'change_vote' => $change_vote,
			'is_locked' => $is_locked,
			'has_voted' => $has_voted,
			'is_expired' => $is_expired,
			'allow_vote' => $allow_vote,
			'allow_poll_view' => $allow_poll_view,
			'allow_change_vote' => $allow_change_vote,
			'show_results' => $show_results,
			'expire_time' => !empty($pollinfo['expire_time']) ? timeformat($pollinfo['expire_time']) : 0,
			'options' => array(),
			'can_lock' => $context['can_moderate_blog'] || ((((($context['can_lock_own_polls_in_own_b'] && $user_started) || $context['can_lock_any_polls_in_own_b']) && $context['is_blog_owner']) || ($context['can_lock_own_polls_in_any_b'] && $user_started) || $context['can_lock_any_polls_in_any_b'])),
			'can_edit' => $context['can_moderate_blog'] || (($context['can_moderate_blog'] || empty($pollinfo['voting_locked'])) && (((($context['can_edit_own_polls_in_own_b'] && $user_started) || $context['can_edit_any_polls_in_own_b']) && $context['is_blog_owner']) || ($context['can_edit_own_polls_in_any_b'] && $user_started) || $context['can_edit_any_polls_in_any_b'])),
			'can_delete' => $context['can_moderate_blog'] || ((((($context['can_delete_own_polls_in_own_b'] && $user_started) || $context['can_delete_any_polls_in_own_b']) && $context['is_blog_owner']) || ($context['can_delete_own_polls_in_any_b'] && $user_started) || $context['can_delete_any_polls_in_any_b'])),
			'allowed_warning' => $pollinfo['max_votes'] > 1 ? sprintf($txt['poll_options6'], $pollinfo['max_votes']) : '',
			'links' => array(),
		);
		
		// view result link
		if ($allow_poll_view && !$show_results && (($allow_vote && !$has_voted) || ($allow_change_vote && $has_voted)))
			$return[$pollinfo['poll_id']]['links'][] = '<a href="' . $scripturl . '?'. $context['zc']['zc_blog_article_request'] .';poll='. $pollinfo['poll_id'] .';viewResults">'. $txt['b3008'] .'</a>';
			
		// change vote link
		if ($allow_change_vote && $show_results)
			$return[$pollinfo['poll_id']]['links'][] = '<a href="' . $scripturl . '?'. $context['zc']['zc_blog_article_request'] .';poll='. $pollinfo['poll_id'] .';changeVote">'. $txt['b299'] .'</a>';
			
		// return vote link
		if ($show_results && $allow_vote && !$has_voted)
			$return[$pollinfo['poll_id']]['links'][] = '<a href="' . $scripturl . '?' . $context['zc']['zc_blog_article_request'] .'">'. $txt['poll_return_vote'] .'</a>';
			
		// lock voting link
		if ($return[$pollinfo['poll_id']]['can_lock'])
			$return[$pollinfo['poll_id']]['links'][] = '<a href="' . $scripturl . '?zc=lockpoll'. $context['zc']['blog_request'] . $context['zc']['article_request'] .';poll='. $pollinfo['poll_id'] .';sesc='. $context['session_id'] .';from=' . (!empty($article) ? 'article,' . $article . ',' . $_REQUEST['start'] : (!empty($blog) ? 'blog,' . $blog . ',' . $_REQUEST['start'] : 'community')) . '">'. (!$is_locked ? $txt['b3009'] : $txt['b3010']) .'</a>';
			
		// edit link
		if ($return[$pollinfo['poll_id']]['can_edit'])
			$return[$pollinfo['poll_id']]['links'][] = '<a href="' . $scripturl . '?zc=post'. $context['zc']['blog_request'] .';poll='. $pollinfo['poll_id'] .';from=' . (!empty($article) ? 'article,' . $article . ',' . $_REQUEST['start'] : (!empty($blog) ? 'blog,' . $blog . ',' . $_REQUEST['start'] : 'community')) . '">'. $txt['b3012'] .'</a>';
			
		// remove link
		if ($return[$pollinfo['poll_id']]['can_delete'])
			$return[$pollinfo['poll_id']]['links'][] = '<a href="' . $scripturl . '?zc=deletepoll'. $context['zc']['blog_request'] .';poll='. $pollinfo['poll_id'] .';sesc='. $context['session_id'] .';from=' . (!empty($article) ? 'article,' . $article . ',' . $_REQUEST['start'] : (!empty($blog) ? 'blog,' . $blog . ',' . $_REQUEST['start'] : 'community')) . '" onclick="return confirm(\''. $txt['b89'] .'\');">'. $txt['b3011'] .'</a>';

		// Calculate the percentages and bar lengths...
		$divisor = $realtotal == 0 ? 1 : $realtotal;

		// Determine if a decimal point is needed in order for the options to add to 100%.
		$precision = $realtotal == 100 ? 0 : 1;

		// Now look through each option, and...
		foreach ($pollOptions as $i => $option)
		{
			// First calculate the percentage, and then the width of the bar...
			$bar = round(($option['votes'] * 100) / $divisor, $precision);
			$barWide = $bar == 0 ? 1 : floor(($bar * 1.02) / 3);

			// Now add it to the poll's contextual theme data.
			$return[$pollinfo['poll_id']]['options'][$i] = array(
				'id' => 'poll-'. $pollinfo['poll_id'] .'_options-' . $i,
				'percent' => $bar,
				'votes' => $option['votes'],
				'voted_this' => $option['votedThis'] != -1,
				'bar' => '<span style="white-space: nowrap;"><img src="' . $settings['images_url'] . '/poll_left.gif" alt="" /><img src="' . $settings['images_url'] . '/poll_middle.gif" width="' . $barWide . '" height="12" alt="-" /><img src="' . $settings['images_url'] . '/poll_right.gif" alt="" /></span>',
				'bar_width' => $barWide,
				'option' => $zcFunc['parse_bbc']($option['label']),
				'vote_button' => '<input type="' . ($pollinfo['max_votes'] > 1 ? 'checkbox' : 'radio') . '" name="options[]" id="poll-'. $pollinfo['poll_id'] .'_options-' . $i . '" value="' . $i . '" class="check" />'
			);
		}
	}
	$zcFunc['db_free_result']($request);
	
	return $return;
}


?>