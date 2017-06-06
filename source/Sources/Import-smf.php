<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
/*
	zc_import_smf_boards($boards = null)
		- $boards can be an array of board IDs to import from SMF db tables, a single board ID as a string, or null
		- if $boards is null, there must be post data to tell the function what board(s) to import
*/

function zc_import_smf_boards($boards = null)
{
	global $txt, $context;
	global $zcFunc, $zc;
	
	// have to be able to access the global settings tab....
	if (!zc_check_permissions('access_global_settings_tab'))
		zc_fatal_error('zc_error_52');
		
	$board = 0;
	$context['zc']['continue_post_data'] = '';
	$context['zc']['continue_countdown'] = '3';
	$context['zc']['sub_sub_template'] = 'zc_auto_submit_form';
	zcLoadTemplate('Generic-cpform');
	
	if ($boards === null && (!empty($_POST['importing_board']) || !empty($_POST['queued_boards'])))
	{
		$board = !empty($_POST['importing_board']) ? (int) $_POST['importing_board'] : 0;
		$boards = !empty($_POST['queued_boards']) ? explode(',', $_POST['queued_boards']) : array();
		
		// make sure each id is a non-zero integer
		$temp = array();
		foreach ($boards as $id)
		{
			$id = (int) $id;
			if (!empty($id))
				$temp[] = $id;
		}
			
		$boards = sort(array_unique($temp));
		unset($temp);
	}
	// $boards has to be an array...
	elseif (!empty($boards))
		$boards = !is_array($boards) ? array($boards) : $boards;
	// couldn't do it....
	else
		zc_fatal_error('zc_error_2');
		
	// if we are not currently importing a board, let's start importing the first queued board...
	if (empty($board))
	{
		$board = $boards[0];
		unset($boards[0]);
		$_REQUEST['step'] = 0;
	}
	
	if (!empty($boards))
		$context['zc']['continue_post_data'] .= '<input type="hidden" name="queued_boards" value="' . implode(',', $boards) . '" />';
		
	$blog_id = isset($_POST['blog_id']) ? (int) $_POST['blog_id'] : 0;
		
	// get as much time as possible...
	@set_time_limit(600);
	
	if (empty($_REQUEST['start']))
		$_REQUEST['start'] = 0;
	
	if (empty($_REQUEST['step']))
		$_REQUEST['step'] = 0;
		
	$_REQUEST['start'] = (int) $_REQUEST['start'];
	$_REQUEST['step'] = (int) $_REQUEST['step'];
	$total_steps = 6;
	
	$steps_done = isset($_POST['steps_done']) ? explode(',', $_POST['steps_done']) : array();
	
	$next_board = true;
	// if all the steps are done, let's move to the next board...
	for ($i = 0; $i <= $total_steps; $i++)
		if (!in_array($i, $steps_done))
		{
			$next_board = false;
			$_REQUEST['step'] = $i;
			break;
		}
		
	if ($next_board === true)
		$_REQUEST['step'] = -1;
			
	if ($zc['with_software']['version'] == 'SMF 2.0')
		require_once($zc['sources_dir'] . '/db-info-smf2.php');
	elseif ($zc['with_software']['version'] == 'SMF 1.1.x')
		require_once($zc['sources_dir'] . '/db-info-smf1.php');
	
	// step 0 imports the board's info
	if ($_REQUEST['step'] == 0)
	{
		// get highest blog ID
		$request = $zcFunc['db_query']("
			SELECT MAX(blog_id)
			FROM {db_prefix}blogs", __FILE__, __LINE__);
		list ($blogs_base_id) = $zcFunc['db_fetch_row']($request);
		$zcFunc['db_free_result']($request);
		
		// get info about this board...
		$request = $zcFunc['db_query']("
			SELECT /*!40001 SQL_NO_CACHE */ *
			FROM {db_prefix}{table:boards}
			WHERE {tbl:boards::column:id_board} = {int:board_id}
			LIMIT 1", __FILE__, __LINE__,
			array(
				'board_id' => (int) $board
			)
		);
		
		if ($zcFunc['db_num_rows']($request) > 0)
		{
			$row = $zcFunc['db_fetch_assoc']($request);
		
			$table_info = zc_smf_db_table_info('boards');
				
			if (!empty($table_info['columns']))
				foreach ($table_info['columns'] as $col_key => $real_col)
					$row[$col_key] = isset($row[$real_col]) ? $row[$real_col] : (isset($row[$col_key]) ? $row[$col_key] : '');
			
			$blog_id = $blogs_base_id + 1;
			$blog_info = array(
				'blog_id' => $blog_id,
				'last_article_id' => 0,
				'last_comment_id' => 0,
				'num_articles' => $row['num_topics'],
				'num_comments' => $row['num_posts'] - $row['num_topics'],
				'blog_owner' => !empty($row['blog_owner']) ? $row['blog_owner'] : $context['user']['id'],
				'time_created' => !empty($row['timeCreated']) ? $row['timeCreated'] : time(),
				'num_views' => !empty($row['blogViews']) ? $row['blogViews'] : 0,
				'member_groups' => addslashes($zcFunc['htmlspecialchars'](stripslashes($zcFunc['un_htmlspecialchars']($row['member_groups'])), ENT_QUOTES)),
				'name' => addslashes($zcFunc['htmlspecialchars'](stripslashes($zcFunc['un_htmlspecialchars']($row['name'])), ENT_QUOTES)),
				'description' => addslashes($zcFunc['htmlspecialchars'](stripslashes($zcFunc['un_htmlspecialchars']($row['description'])), ENT_QUOTES)),
				'moderators' => '',
			);
			
			// insert the new blog into the db...
			$zcFunc['db_insert']('insert', '{db_prefix}blogs', array('blog_id' => 'int', 'last_article_id' => 'int', 'last_comment_id' => 'int', 'num_articles' => 'int', 'num_comments' => 'int', 'blog_owner' => 'int', 'time_created' => 'int', 'num_views' => 'int', 'member_groups' => 'string', 'name' => 'string', 'description' => 'string', 'moderators' => 'string'), $blog_info);
			
			// start rows in the settings tables for this new blog...
			zc_init_blog_settings($blog_id);
			
			$_POST['imported_blogs'] = (!empty($_POST['imported_blogs']) ? $_POST['imported_blogs'] . ',' : '') . $blog_id;
			
			// get true last_comment_id
			$request2 = $zcFunc['db_query']("
				SELECT /*!40001 SQL_NO_CACHE */ m.{tbl:messages::column:id_msg} AS id_msg
				FROM {db_prefix}{table:messages} AS m
					LEFT JOIN {db_prefix}{table:topics} AS t ON (t.{tbl:topics::column:id_topic} = m.{tbl:messages::column:id_topic})
				WHERE m.{tbl:messages::column:id_board} = {int:board_id}
					AND m.{tbl:messages::column:id_msg} != t.{tbl:topics::column:id_first_msg}
				ORDER BY m.{tbl:messages::column:id_msg} DESC
				LIMIT 1", __FILE__, __LINE__,
				array(
					'board_id' => $row['id_board']
				)
			);
			while ($row2 = $zcFunc['db_fetch_assoc']($request2))
			{
				$_POST['last_comment_of_blog'] = $row2['id_msg'];
				$context['zc']['continue_post_data'] .= '<input type="hidden" name="last_comment_of_blog" value="' . $row2['id_msg'] . '" />';
			}
			$zcFunc['db_free_result']($request2);
			
			// get true last_article_id
			$request2 = $zcFunc['db_query']("
				SELECT /*!40001 SQL_NO_CACHE */ {tbl:topics::column:id_topic} AS id_topic
				FROM {db_prefix}{table:topics}
				WHERE {tbl:topics::column:id_board} = {int:board_id}
				ORDER BY {tbl:topics::column:id_topic} DESC
				LIMIT 1", __FILE__, __LINE__,
				array(
					'board_id' => $row['id_board']
				)
			);
			while ($row2 = $zcFunc['db_fetch_assoc']($request2))
			{
				$_POST['last_article_of_blog'] = $row2['id_topic'];
				$context['zc']['continue_post_data'] .= '<input type="hidden" name="last_article_of_blog" value="' . $row2['id_topic'] . '" />';
			}
			$zcFunc['db_free_result']($request2);
			
			zcUpdateGlobalSettings(array('community_total_blogs' => $zc['settings']['community_total_blogs'] + 1));
			$_REQUEST['step']++;
			$steps_done[] = 0;
			$context['zc']['continue_post_data'] .= '<input type="hidden" name="blog_id" value="' . $blog_id . '" />';
		}
		// move to next queued board...
		else
		{
			$_REQUEST['step'] = -1;
			$next_board = true;
		}
		
		$_REQUEST['start'] = 0;
			
		$zcFunc['db_free_result']($request);
	}
	
	// step 1 imports this board's moderators
	if ($_REQUEST['step'] == 1)
	{
		$request = $zcFunc['db_query']("
			SELECT /*!40001 SQL_NO_CACHE */ MAX({tbl:moderators::column:id_member})
			FROM {db_prefix}{table:moderators}
			WHERE {tbl:moderators::column:id_board} = {int:board_id}", __FILE__, __LINE__,
			array(
				'board_id' => (int) $board
			)
		);
		list ($max_moderators) = $zcFunc['db_fetch_row']($request);
		$zcFunc['db_free_result']($request);
		
		$increment = min(ceil($max_moderators / 4), 250);
		
		while ($_REQUEST['start'] < $max_moderators)
		{
			// get all moderators
			$request = $zcFunc['db_query']("
				SELECT /*!40001 SQL_NO_CACHE */ {tbl:moderators::column:id_member} AS id_member
				FROM {db_prefix}{table:moderators}
				WHERE {tbl:moderators::column:id_board} = {int:board_id}
					AND {tbl:moderators::column:id_member} > " . $_REQUEST['start'] . "
					AND {tbl:moderators::column:id_member} <= " . $_REQUEST['start'] + $increment, __FILE__, __LINE__,
				array(
					'board_id' => (int) $board
				)
			);
		
			$moderators = !empty($_POST['moderators']) ? (string) $_POST['moderators'] : '';
			while ($row = $zcFunc['db_fetch_assoc']($request))
				$moderators .= (!empty($moderators) ? ',' : '') . $row['id_member'];
			$zcFunc['db_free_result']($request);
			
			$_REQUEST['start'] += $increment;
			
			if ($_REQUEST['start'] >= $max_moderators)
				$zcFunc['db_update'](
					'{db_prefix}blogs',
					array('blog_id' => 'int', 'moderators' => 'string'),
					array('moderators' => (string) $moderators),
					array('blog_id' => (int) $blog_id));
			else
				$context['zc']['continue_post_data'] .= '<input type="hidden" name="moderators" value="'. $moderators .'" />';
			
			if (array_sum(explode(' ', microtime())) - array_sum(explode(' ', $zc['time_start'])) > 3)
			{
				$context['zc']['continue_get_data'] = '?zc=bcp;u='. $context['user']['id'] .';sa=importsmfboards;step=1;start=' . $_REQUEST['start'];
				$context['zc']['continue_percent'] = round(((100 * $_REQUEST['start'] / $max_moderators) / $total_steps) / (count($boards) + 1));
				
				zc_import_smf_boards_continue_post_data(array('importing_board' => $board, 'steps_done' => $steps_done));

				return;
			}
		}
		
		$_REQUEST['start'] = 0;
		$_REQUEST['step']++;
		$steps_done[] = 1;
	}
	
	$get_polls = !empty($_POST['get_polls']) ? explode(',', $_POST['get_polls']) : array();
	$topics_retrieved = !empty($_POST['topics_retrieved']) ? explode(',', $_POST['topics_retrieved']) : array();
	$articles = !empty($_POST['articles']) ? explode(',', $_POST['articles']) : array();
	
	// step 2 involves importing all of a board's topics (including first msg of every topic)
	if ($_REQUEST['step'] == 2)
	{
		// get highest article ID
		$request = $zcFunc['db_query']("
			SELECT /*!40001 SQL_NO_CACHE */ MAX(article_id)
			FROM {db_prefix}articles", __FILE__, __LINE__);
		list ($articles_base_id) = $zcFunc['db_fetch_row']($request);
		$zcFunc['db_free_result']($request);
		
		$request = $zcFunc['db_query']("
			SELECT /*!40001 SQL_NO_CACHE */ MAX({tbl:topics::column:id_topic})
			FROM {db_prefix}{table:topics}
			WHERE {tbl:topics::column:id_board} = {int:board_id}", __FILE__, __LINE__,
			array(
				'board_id' => (int) $board
			)
		);
		list ($max_topics) = $zcFunc['db_fetch_row']($request);
		$zcFunc['db_free_result']($request);
		
		$increment = min(ceil($max_topics / 4), 250);
		$table_info = zc_smf_db_table_info('topics');
		
		$i = $articles_base_id;	
		while ($_REQUEST['start'] < $max_topics)
		{
			// get topics
			$request = $zcFunc['db_query']("
				SELECT /*!40001 SQL_NO_CACHE */ 
					t.*,
					m.{tbl:messages::column:id_msg} AS id_msg, m.{tbl:messages::column:body} AS body, m.{tbl:messages::column:subject} AS subject, m.{tbl:messages::column:poster_name} AS poster_name, m.{tbl:messages::column:poster_email} AS poster_email, m.{tbl:messages::column:poster_ip} AS poster_ip, m.{tbl:messages::column:smileys_enabled} AS smileys_enabled, m.{tbl:messages::column:poster_time} AS poster_time, m.{tbl:messages::column:modified_time} AS modified_time, m.{tbl:messages::column:modified_name} AS modified_name, m.{tbl:messages::column:id_member} AS id_member
				FROM {db_prefix}{table:topics} AS t
					LEFT JOIN {db_prefix}{table:messages} AS m ON (m.{tbl:messages::column:id_msg} = t.{tbl:topics::column:id_first_msg})
				WHERE t.{tbl:topics::column:id_board} = {int:board_id}
					AND t.{tbl:topics::column:id_topic} > {int:start}
					AND t.{tbl:topics::column:id_topic} <= {int:maxindex}", __FILE__, __LINE__,
				array(
					'start' => $_REQUEST['start'],
					'maxindex' => $_REQUEST['start'] + $increment,
					'board_id' => (int) $board
				)
			);
			
			$data = array();
			while ($row = $zcFunc['db_fetch_assoc']($request))
			{
				if (!empty($table_info['columns']))
					foreach ($table_info['columns'] as $col_key => $real_col)
						$row[$col_key] = isset($row[$real_col]) ? $row[$real_col] : (isset($row[$col_key]) ? $row[$col_key] : '');
					
				$i++;
				if (!empty($row['id_poll']))
					$get_polls[] = $row['id_poll'];
					
				$topics_retrieved[] = $row['id_topic'];
				$articles[] = $i;
				
				if (!empty($row['poster_time']) && (empty($row['month']) || empty($row['year'])))
					list($row['month'], $row['year']) = explode(',', date('n,Y', $row['poster_time']));
				
				$data[] = array(
					'article_id' => $i,
					'blog_id' => $blog_id,
					'poster_id' => $row['id_member'],
					'num_comments' => $row['num_replies'],
					'num_views' => $row['num_views'],
					'month' => !empty($row['month']) ? $row['month'] : 0,
					'year' => !empty($row['year']) ? $row['year'] : 0,
					'blog_category_id' => !empty($row['categoryID']) ? $row['categoryID'] : 0,
					'posted_time' => $row['poster_time'],
					'last_edit_time' => $row['modified_time'],
					'locked' => $row['locked'],
					'smileys_enabled' => $row['smileys_enabled'],
					'last_edit_name' => addslashes($zcFunc['htmlspecialchars'](stripslashes($zcFunc['un_htmlspecialchars']($row['modified_name'])), ENT_QUOTES)),
					'blog_tags' => !empty($row['blogTags']) ? addslashes($zcFunc['htmlspecialchars'](stripslashes($zcFunc['un_htmlspecialchars']($row['blogTags'])), ENT_QUOTES)) : '',
					'poster_name' => addslashes($zcFunc['htmlspecialchars'](stripslashes($zcFunc['un_htmlspecialchars']($row['poster_name'])), ENT_QUOTES)),
					'poster_email' => addslashes($zcFunc['htmlspecialchars'](stripslashes($zcFunc['un_htmlspecialchars']($row['poster_email'])), ENT_QUOTES)),
					'poster_ip' => addslashes($zcFunc['htmlspecialchars'](stripslashes($zcFunc['un_htmlspecialchars']($row['poster_ip'])), ENT_QUOTES)),
					'subject' => addslashes($zcFunc['htmlspecialchars'](stripslashes($zcFunc['un_htmlspecialchars']($row['subject'])), ENT_QUOTES)),
					'body' => addslashes($zcFunc['htmlspecialchars'](stripslashes($zcFunc['un_htmlspecialchars'](str_replace('<br />', '
', $row['body']))), ENT_QUOTES)),
					'approved' => 1
				);	
				
				// is this article the last article posted in the blog?
				if (!empty($_POST['last_article_of_blog']) && $_POST['last_article_of_blog'] == $row['id_topic'])
				{
					$zcFunc['db_update']('{db_prefix}blogs', array('last_article_id' => 'int', 'blog_id' => 'int'), array('last_article_id' => $i), array('blog_id' => $blog_id));
					unset($_POST['last_article_of_blog']);
				}
				
				// get last_comment_id
				$request2 = $zcFunc['db_query']("
					SELECT /*!40001 SQL_NO_CACHE */ {tbl:messages::column:id_msg} AS id_msg
					FROM {db_prefix}{table:messages}
					WHERE {tbl:messages::column:id_topic} = {int:id_topic}
						AND {tbl:messages::column:id_msg} != {int:id_first_msg}
					LIMIT 1", __FILE__, __LINE__,
					array(
						'id_first_msg' => $row['id_first_msg'],
						'id_topic' => $row['id_topic']
					)
				);
				while ($row2 = $zcFunc['db_fetch_assoc']($request2))
				{
					if (!isset($_POST['last_comment_of_article']))
						$_POST['last_comment_of_article'] = array();
						
					$_POST['last_comment_of_article'][$i] = $row2['id_msg'];
					$context['zc']['continue_post_data'] .= '<input type="hidden" name="last_comment_of_article[' . $i . ']" value="' . $row2['id_msg'] . '" />';
				}
				$zcFunc['db_free_result']($request2);
			}
			$zcFunc['db_free_result']($request);
			
			// insert this set into the db...
			if (!empty($data))
				$zcFunc['db_insert']('insert', '{db_prefix}articles', array('article_id' => 'int', 'blog_id' => 'int', 'poster_id' => 'int', 'num_comments' => 'int', 'num_views' => 'int', 'month' => 'int', 'year' => 'int', 'blog_category_id' => 'int', 'posted_time' => 'int', 'last_edit_time' => 'int', 'locked' => 'int', 'smileys_enabled' => 'int', 'last_edit_name' => 'string', 'blog_tags' => 'string', 'poster_name' => 'string', 'poster_email' => 'string', 'poster_ip' => 'string', 'subject' => 'string', 'body' => 'string', 'approved' => 'int'), $data);
						
			zcUpdateGlobalSettings(array('max_article_id' => $i, 'community_total_articles' => $zc['settings']['community_total_articles'] + count($data)));
			
			$_REQUEST['start'] += $increment;
			
			if (array_sum(explode(' ', microtime())) - array_sum(explode(' ', $zc['time_start'])) > 3)
			{
				$context['zc']['continue_get_data'] = '?zc=bcp;u='. $context['user']['id'] .';sa=importsmfboards;step=2;start=' . $_REQUEST['start'];
				$context['zc']['continue_percent'] = round(((100 * $_REQUEST['start'] / $max_topics) / $total_steps) / (count($boards) + 1));
				
				zc_import_smf_boards_continue_post_data(array('importing_board' => $board, 'steps_done' => $steps_done, 'get_polls' => $get_polls, 'topics_retrieved' => $topics_retrieved, 'articles' => $articles));

				return;
			}
		}
		
		$_REQUEST['start'] = 0;
		$_REQUEST['step']++;
		$steps_done[] = 2;
	}
	
	if (isset($_POST['last_article_of_blog']))
		unset($_POST['last_article_of_blog']);
	
	// step 3 involves importing all of a board's messages (excluding first msg of topics)
	if ($_REQUEST['step'] == 3)
	{
		if (!empty($topics_retrieved))
		{
			$topics = $topics_retrieved;
			$temp = $articles;
			$articles = array();
			foreach ($topics as $k => $topic_id)
				$articles[$topic_id] = $temp[$k];
			unset($temp);
			
			$request = $zcFunc['db_query']("
				SELECT /*!40001 SQL_NO_CACHE */ MAX({tbl:messages::column:id_msg})
				FROM {db_prefix}{table:messages}
				WHERE {tbl:messages::column:id_board} = {int:board_id}
					AND {tbl:messages::column:id_topic} IN ({array_int:topics})", __FILE__, __LINE__,
				array(
					'board_id' => (int) $board,
					'topics' => $topics
				)
			);
			list ($max_messages) = $zcFunc['db_fetch_row']($request);
			$zcFunc['db_free_result']($request);
			
			$increment = min(ceil($max_messages / 4), 250);
			$table_info = zc_smf_db_table_info('messages');
			
			// get highest comment ID
			$request = $zcFunc['db_query']("
				SELECT MAX(comment_id)
				FROM {db_prefix}comments", __FILE__, __LINE__);
			list ($comments_base_id) = $zcFunc['db_fetch_row']($request);
			$zcFunc['db_free_result']($request);
			
			$i = $comments_base_id;	
			while ($_REQUEST['start'] < $max_messages)
			{
				$request = $zcFunc['db_query']("
					SELECT /*!40001 SQL_NO_CACHE */ m.*, t.{tbl:topics::column:id_topic} AS id_topic
					FROM {db_prefix}{table:messages} AS m
						LEFT JOIN {db_prefix}{table:topics} AS t ON (t.{tbl:topics::column:id_topic} = m.{tbl:messages::column:id_topic})
					WHERE m.{tbl:messages::column:id_board} = {int:board_id}
						AND m.{tbl:messages::column:id_msg} != t.{tbl:topics::column:id_first_msg}
						AND m.{tbl:messages::column:id_msg} > {int:start}
						AND m.{tbl:messages::column:id_msg} <= {int:maxindex}", __FILE__, __LINE__,
					array(
						'start' => $_REQUEST['start'],
						'maxindex' => $_REQUEST['start'] + $increment,
						'board_id' => (int) $board
					)
				);
		
				$data = array();
				while ($row = $zcFunc['db_fetch_assoc']($request))
				{
					if (!empty($table_info['columns']))
						foreach ($table_info['columns'] as $col_key => $real_col)
							$row[$col_key] = isset($row[$real_col]) ? $row[$real_col] : (isset($row[$col_key]) ? $row[$col_key] : '');
						
					$i++;
					
					$data[] = array(
						'comment_id' => $i,
						'article_id' => $articles[$row['id_topic']],
						'blog_id' => $blog_id,
						'poster_id' => $row['id_member'],
						'posted_time' => $row['poster_time'],
						'smileys_enabled' => $row['smileys_enabled'],
						'approved' => 1,
						'last_edit_time' => $row['modified_time'],
						'last_edit_name' => addslashes($zcFunc['htmlspecialchars'](stripslashes($zcFunc['un_htmlspecialchars']($row['modified_name'])), ENT_QUOTES)),
						'subject' => addslashes($zcFunc['htmlspecialchars'](stripslashes($zcFunc['un_htmlspecialchars']($row['subject'])), ENT_QUOTES)),
						'body' => addslashes($zcFunc['htmlspecialchars'](stripslashes($zcFunc['un_htmlspecialchars'](str_replace('<br />', '
', $row['body']))), ENT_QUOTES)),
						'poster_email' => addslashes($zcFunc['htmlspecialchars'](stripslashes($zcFunc['un_htmlspecialchars']($row['poster_email'])), ENT_QUOTES)),
						'poster_name' => addslashes($zcFunc['htmlspecialchars'](stripslashes($zcFunc['un_htmlspecialchars']($row['poster_name'])), ENT_QUOTES)),
						'poster_ip' => addslashes($zcFunc['htmlspecialchars'](stripslashes($zcFunc['un_htmlspecialchars']($row['poster_ip'])), ENT_QUOTES))
					);
					
					// is this comment the last comment posted in the blog?
					if (isset($_POST['last_comment_of_blog']) && $_POST['last_comment_of_blog'] == $row['id_msg'])
					{
						$zcFunc['db_update']('{db_prefix}blogs', array('last_comment_id' => 'int', 'blog_id' => 'int'), array('last_comment_id' => $i), array('blog_id' => $blog_id));
						unset($_POST['last_comment_of_blog']);
					}
					
					// is this comment the last comment posted in an article?
					if (isset($_POST['last_comment_of_article'][$row['id_topic']]) && $_POST['last_comment_of_article'][$row['id_topic']] == $row['id_msg'])
					{
						$zcFunc['db_update']('{db_prefix}articles', array('last_comment_id' => 'int', 'blog_id' => 'int'), array('last_comment_id' => $i), array('blog_id' => $blog_id));
						unset($_POST['last_comment_of_article'][$row['id_topic']]);
					}
				}
				$zcFunc['db_free_result']($request);
				
				// insert this set into the db...
				if (!empty($data))
					$zcFunc['db_insert']('insert', '{db_prefix}comments', array('comment_id' => 'int', 'article_id' => 'int', 'blog_id' => 'int', 'poster_id' => 'int', 'posted_time' => 'int', 'smileys_enabled' => 'int', 'approved' => 'int', 'last_edit_time' => 'int', 'last_edit_name' => 'string', 'subject' => 'string', 'body' => 'string', 'poster_email' => 'string', 'poster_name' => 'string', 'poster_ip' => 'string'), $data);
							
				zcUpdateGlobalSettings(array('max_comment_id' => $i, 'community_total_comments' => $zc['settings']['community_total_comments'] + count($data)));
				
				$_REQUEST['start'] += $increment;
				
				if (array_sum(explode(' ', microtime())) - array_sum(explode(' ', $zc['time_start'])) > 3)
				{
					$context['zc']['continue_get_data'] = '?zc=bcp;u='. $context['user']['id'] .';sa=importsmfboards;step=3;start=' . $_REQUEST['start'];
					$context['zc']['continue_percent'] = round(((100 * $_REQUEST['start'] / $max_messages) / $total_steps) / (count($boards) + 1));
					
					zc_import_smf_boards_continue_post_data(array('importing_board' => $board, 'steps_done' => $steps_done));
	
					return;
				}
			}
			unset($articles);
			unset($topics);
		}
		$_REQUEST['start'] = 0;
		$_REQUEST['step']++;
		$steps_done[] = 3;
	}
	
	if (isset($_POST['last_comment_of_blog']))
		unset($_POST['last_comment_of_blog']);
	
	if (isset($_POST['last_comment_of_article']))
		unset($_POST['last_comment_of_article']);
	
	if (isset($_POST['topics_retrieved']))
		unset($_POST['topics_retrieved']);
	
	if (isset($_POST['articles']))
		unset($_POST['articles']);
		
	$imported_polls = !empty($_POST['imported_polls']) ? explode(',', $_POST['imported_polls']) : array();
	
	// step 4 is importing the polls
	if ($_REQUEST['step'] == 4)
	{
		if (!empty($get_polls))
		{
			$request = $zcFunc['db_query']("
				SELECT /*!40001 SQL_NO_CACHE */ MAX({tbl:polls::column:id_poll})
				FROM {db_prefix}{table:polls}
				WHERE {tbl:polls::column:id_poll} IN ({array_int:polls})", __FILE__, __LINE__,
				array(
					'board_id' => (int) $board,
					'polls' => $get_polls
				)
			);
			list ($max_polls) = $zcFunc['db_fetch_row']($request);
			$zcFunc['db_free_result']($request);
		
			// get highest poll ID
			$request = $zcFunc['db_query']("
				SELECT MAX(poll_id)
				FROM {db_prefix}polls", __FILE__, __LINE__);
			list ($polls_base_id) = $zcFunc['db_fetch_row']($request);
			$zcFunc['db_free_result']($request);
			
			$increment = min(ceil($max_polls / 4), 250);
			$table_info = zc_smf_db_table_info('polls');
			
			$i = $polls_base_id;	
			while ($_REQUEST['start'] < $max_polls)
			{
				$request = $zcFunc['db_query']("
					SELECT /*!40001 SQL_NO_CACHE */ 
						p.{tbl:polls::column:id_poll} AS id_poll, p.{tbl:polls::column:question} AS question, p.{tbl:polls::column:max_votes} AS max_votes, p.{tbl:polls::column:voting_locked} AS voting_locked, p.{tbl:polls::column:expire_time} AS expire_time, p.{tbl:polls::column:hide_results} AS hide_results, p.{tbl:polls::column:change_vote} AS change_vote, p.{tbl:polls::column:id_member} AS id_member, p.{tbl:polls::column:poster_name} AS poster_name,
						t.{tbl:topics::column:id_board},
						m.{tbl:messages::column:poster_email} AS poster_email, m.{tbl:messages::column:poster_ip} AS poster_ip, m.{tbl:messages::column:poster_time} AS poster_time, m.{tbl:messages::column:modified_time} AS modified_time, m.{tbl:messages::column:modified_name} AS modified_name
					FROM {db_prefix}{table:polls} AS p
						LEFT JOIN {db_prefix}{table:topics} AS t ON (t.{tbl:topics::column:id_poll} = p.{tbl:polls::column:id_poll})
						LEFT JOIN {db_prefix}{table:messages} AS m ON (m.{tbl:messages::column:id_msg} = t.{tbl:topics::column:id_first_msg})
					WHERE p.{tbl:polls::column:id_poll} IN ({array_int:polls})
						AND p.{tbl:polls::column:id_poll} > {int:start}
						AND p.{tbl:polls::column:id_poll} <= {int:maxindex}", __FILE__, __LINE__,
					array(
						'start' => $_REQUEST['start'],
						'maxindex' => $_REQUEST['start'] + $increment,
						'polls' => $get_polls
					)
				);
		
				$data = array();
				while ($row = $zcFunc['db_fetch_assoc']($request))
				{
					if (!empty($table_info['columns']))
						foreach ($table_info['columns'] as $col_key => $real_col)
							$row[$col_key] = isset($row[$real_col]) ? $row[$real_col] : (isset($row[$col_key]) ? $row[$col_key] : '');
						
					$i++;
					$imported_polls[] = $i;
					
					$data[] = array(
						'poll_id' => $i,
						'blog_id' => $blog_id,
						'voting_locked' => $row['voting_locked'],
						'max_votes' => $row['max_votes'],
						'expire_time' => $row['expire_time'],
						'hide_results' => $row['hide_results'],
						'change_vote' => $row['change_vote'],
						'poster_id' => $row['id_member'],
						'posted_time' => $row['poster_time'],
						'last_edit_time' => $row['modified_time'],
						'question' => addslashes($zcFunc['htmlspecialchars'](stripslashes($zcFunc['un_htmlspecialchars']($row['question'])), ENT_QUOTES)),
						'last_edit_name' => addslashes($zcFunc['htmlspecialchars'](stripslashes($zcFunc['un_htmlspecialchars']($row['modified_name'])), ENT_QUOTES)),
						'poster_name' => addslashes($zcFunc['htmlspecialchars'](stripslashes($zcFunc['un_htmlspecialchars']($row['poster_name'])), ENT_QUOTES))
					);
				}
				$zcFunc['db_free_result']($request);
				
				// insert this set of polls into the db...
				if (!empty($data))
					$zcFunc['db_insert']('insert', '{db_prefix}polls', array('poll_id' => 'int', 'blog_id' => 'int', 'voting_locked' => 'int', 'max_votes' => 'int', 'expire_time' => 'int', 'hide_results' => 'int', 'change_vote' => 'int', 'poster_id' => 'int', 'question' => 'string', 'last_edit_time' => 'int', 'last_edit_name' => 'string', 'posted_time' => 'int', 'poster_name' => 'string'), $data);
				
				$_REQUEST['start'] += $increment;
				
				if (array_sum(explode(' ', microtime())) - array_sum(explode(' ', $zc['time_start'])) > 3)
				{
					$context['zc']['continue_get_data'] = '?zc=bcp;u='. $context['user']['id'] .';sa=importsmfboards;step=4;start=' . $_REQUEST['start'];
					$context['zc']['continue_percent'] = round(((100 * $_REQUEST['start'] / $max_polls) / $total_steps) / (count($boards) + 1));
					
					zc_import_smf_boards_continue_post_data(array('importing_board' => $board, 'steps_done' => $steps_done, 'imported_polls' => $imported_polls));
	
					return;
				}
			}
		}
		$_REQUEST['start'] = 0;
		$_REQUEST['step']++;
		$steps_done[] = 4;
	}
		
	$pre_imported_poll_choices = !empty($_POST['pre_imported_poll_choices']) ? explode(',', $_POST['pre_imported_poll_choices']) : array();
	$imported_poll_choices = !empty($_POST['imported_poll_choices']) ? explode(',', $_POST['imported_poll_choices']) : array();
	
	// step 5 is importing the poll choices
	if ($_REQUEST['step'] == 5)
	{
		if (!empty($get_polls))
		{
			$polls = array();
			foreach ($get_polls as $k => $poll_id)
				$polls[$poll_id] = $imported_polls[$k];
			
			$request = $zcFunc['db_query']("
				SELECT /*!40001 SQL_NO_CACHE */ COUNT({tbl:poll_choices::column:id_choice})
				FROM {db_prefix}{table:poll_choices}
				WHERE {tbl:poll_choices::column:id_poll} IN ({array_int:polls})", __FILE__, __LINE__,
				array(
					'polls' => $get_polls
				)
			);
			list ($max_poll_choices) = $zcFunc['db_fetch_row']($request);
			$zcFunc['db_free_result']($request);
			
			$increment = min(ceil($max_poll_choices / 4), 250);
			$table_info = zc_smf_db_table_info('poll_choices');
			
			$i = 0;
			while ($_REQUEST['start'] < $max_poll_choices)
			{
				$request = $zcFunc['db_query']("
					SELECT /*!40001 SQL_NO_CACHE */ {tbl:poll_choices::column:id_poll} AS id_poll, {tbl:poll_choices::column:id_choice} AS id_choice, {tbl:poll_choices::column:label} AS label, {tbl:poll_choices::column:votes} AS votes
					FROM {db_prefix}{table:poll_choices}
					WHERE {tbl:poll_choices::column:id_poll} IN ({array_int:polls})
					LIMIT {int:start}, {int:maxindex}", __FILE__, __LINE__,
					array(
						'start' => $_REQUEST['start'],
						'maxindex' => $_REQUEST['start'] + $increment,
						'polls' => $get_polls
					)
				);
		
				$data = array();
				while ($row = $zcFunc['db_fetch_assoc']($request))
				{
					if (!empty($table_info['columns']))
						foreach ($table_info['columns'] as $col_key => $real_col)
							$row[$col_key] = isset($row[$real_col]) ? $row[$real_col] : (isset($row[$col_key]) ? $row[$col_key] : '');
						
					$i++;
					$pre_imported_poll_choices[] = $row['id_choice'];
					$imported_poll_choices[] = $i;
					
					$data[] = array(
						'poll_id' => $polls[$row['id_poll']],
						'choice_id' => $i,
						'votes' => $row['votes'],
						'label' => addslashes($zcFunc['htmlspecialchars'](stripslashes($zcFunc['un_htmlspecialchars']($row['label'])), ENT_QUOTES))
					);
				}
				$zcFunc['db_free_result']($request);
				
				// insert this set of poll choices into the db...
				if (!empty($data))
					$zcFunc['db_insert']('replace', '{db_prefix}poll_choices', array('poll_id' => 'int', 'choice_id' => 'int', 'votes' => 'int', 'label' => 'string'), $data);
				
				$_REQUEST['start'] += $increment;
				
				if (array_sum(explode(' ', microtime())) - array_sum(explode(' ', $zc['time_start'])) > 3)
				{
					$context['zc']['continue_get_data'] = '?zc=bcp;u='. $context['user']['id'] .';sa=importsmfboards;step=5;start=' . $_REQUEST['start'];
					$context['zc']['continue_percent'] = round(((100 * $_REQUEST['start'] / $max_poll_choices) / $total_steps) / (count($boards) + 1));
					
					zc_import_smf_boards_continue_post_data(array('importing_board' => $board, 'steps_done' => $steps_done, 'pre_imported_poll_choices' => $pre_imported_poll_choices, 'imported_poll_choices' => $imported_poll_choices));
	
					return;
				}
			}
		}
		$_REQUEST['start'] = 0;
		$_REQUEST['step']++;
		$steps_done[] = 5;
	}
	
	// step 6 is importing the poll logs
	if ($_REQUEST['step'] == 6)
	{
		if (!empty($pre_imported_poll_choices))
		{
			if (!isset($polls))
			{
				$polls = array();
				foreach ($get_polls as $k => $poll_id)
					$polls[$poll_id] = $imported_polls[$k];
			}
				
			$poll_choices = array();
			foreach ($pre_imported_poll_choices as $k => $choice_id)
				$poll_choices[$choice_id] = $imported_poll_choices[$k];
			
			$request = $zcFunc['db_query']("
				SELECT /*!40001 SQL_NO_CACHE */ COUNT({tbl:log_polls::column:id_choice})
				FROM {db_prefix}{table:log_polls}
				WHERE {tbl:log_polls::column:id_poll} IN ({array_int:polls})", __FILE__, __LINE__,
				array(
					'polls' => $get_polls
				)
			);
			list ($max_poll_logs) = $zcFunc['db_fetch_row']($request);
			$zcFunc['db_free_result']($request);
			
			$increment = min(ceil($max_poll_logs / 4), 2000);
			$table_info = zc_smf_db_table_info('poll_choices');
			
			$i = 0;
			while ($_REQUEST['start'] < $max_poll_logs)
			{
				$request = $zcFunc['db_query']("
					SELECT /*!40001 SQL_NO_CACHE */ {tbl:log_polls::column:id_poll} AS id_poll, {tbl:log_polls::column:id_choice} AS id_choice, {tbl:log_polls::column:id_member} AS id_member
					FROM {db_prefix}{table:log_polls}
					WHERE {tbl:log_polls::column:id_poll} IN ({array_int:polls})
					LIMIT {int:start}, {int:maxindex}", __FILE__, __LINE__,
					array(
						'start' => $_REQUEST['start'],
						'maxindex' => $_REQUEST['start'] + $increment,
						'polls' => $get_polls
					)
				);
		
				$data = array();
				while ($row = $zcFunc['db_fetch_assoc']($request))
				{
					if (!empty($table_info['columns']))
						foreach ($table_info['columns'] as $col_key => $real_col)
							$row[$col_key] = isset($row[$real_col]) ? $row[$real_col] : (isset($row[$col_key]) ? $row[$col_key] : '');
						
					$i++;
					
					$data[] = array(
						'poll_id' => $polls[$row['id_poll']],
						'choice_id' => $poll_choices[$row['id_choice']],
						'member_id' => $row['id_member']
					);
				}
				$zcFunc['db_free_result']($request);
				
				// insert this set of poll logs into the db...
				if (!empty($data))
					$zcFunc['db_insert']('replace', '{db_prefix}log_polls', array('poll_id' => 'int', 'choice_id' => 'int', 'member_id' => 'int'), $data);
				
				$_REQUEST['start'] += $increment;
				
				if (array_sum(explode(' ', microtime())) - array_sum(explode(' ', $zc['time_start'])) > 3)
				{
					$context['zc']['continue_get_data'] = '?zc=bcp;u='. $context['user']['id'] .';sa=importsmfboards;step=6;start=' . $_REQUEST['start'];
					$context['zc']['continue_percent'] = round(((100 * $_REQUEST['start'] / $max_poll_logs) / $total_steps) / (count($boards) + 1));
					
					zc_import_smf_boards_continue_post_data(array('importing_board' => $board, 'steps_done' => $steps_done));
	
					return;
				}
			}
		}
		$_REQUEST['start'] = 0;
		$_REQUEST['step']++;
		$steps_done[] = 6;
	}
	
	if (isset($_POST['get_polls']))
		unset($_POST['get_polls']);
	
	if (isset($_POST['imported_polls']))
		unset($_POST['imported_polls']);
	
	if (isset($_POST['imported_poll_choices']))
		unset($_POST['imported_poll_choices']);
	
	if (isset($_POST['pre_imported_poll_choices']))
		unset($_POST['pre_imported_poll_choices']);
	
	// move on to the next queued board....
	if ($next_board === true)
	{
		$context['zc']['continue_get_data'] = '?zc=bcp;u='. $context['user']['id'] .';sa=importsmfboard';
		$context['zc']['continue_percent'] = round(100 / count($boards));
		return;
	}
	else
	{
		$blogs = !empty($_POST['imported_blogs']) ? explode(',', $_POST['imported_blogs']) : false;
		
		// do maintenance on tags for all the newly imported blogs...
		if (!empty($blogs) && file_exists($zc['sources_dir'] . '/Subs-Maintenance.php'))
		{
			require_once($zc['sources_dir'] . '/Subs-Maintenance.php');
			if (function_exists('zcMaintainTags'))
				zcMaintainTags(null, $blogs, true);
		}
	
		return $blogs;
	}
}

function zc_import_smf_boards_continue_post_data($make_post_data = null)
{
	global $context;
	
	if (!empty($_POST['imported_blogs']))
		$context['zc']['continue_post_data'] .= '<input type="hidden" name="imported_blogs" value="' . $_POST['imported_blogs'] . '" />';
	
	if (!empty($make_post_data['steps_done']) || !empty($_POST['steps_done']))
		$context['zc']['continue_post_data'] .= '<input type="hidden" name="steps_done" value="' . (empty($make_post_data['steps_done']) ? $_POST['steps_done'] : implode(',', $make_post_data['steps_done'])) . '" />';
	
	if (!empty($make_post_data['importing_board']) || !empty($_POST['importing_board']))
		$context['zc']['continue_post_data'] .= '<input type="hidden" name="importing_board" value="' . (empty($make_post_data['importing_board']) ? $_POST['importing_board'] : $make_post_data['importing_board']) . '" />';
	
	if (!empty($make_post_data['topics_retrieved']) || !empty($_POST['topics_retrieved']))
		$context['zc']['continue_post_data'] .= '<input type="hidden" name="topics_retrieved" value="' . (empty($make_post_data['topics_retrieved']) ? $_POST['topics_retrieved'] : implode(',', $make_post_data['topics_retrieved'])) . '" />';
	
	if (!empty($make_post_data['articles']) || !empty($_POST['articles']))
		$context['zc']['continue_post_data'] .= '<input type="hidden" name="articles" value="' . (empty($make_post_data['articles']) ? $_POST['articles'] : implode(',', $make_post_data['articles'])) . '" />';
	
	if (!empty($make_post_data['imported_polls']) || !empty($_POST['imported_polls']))
		$context['zc']['continue_post_data'] .= '<input type="hidden" name="imported_polls" value="' . (empty($make_post_data['imported_polls']) ? $_POST['imported_polls'] : implode(',', $make_post_data['imported_polls'])) . '" />';
	
	if (!empty($make_post_data['get_polls']) || isset($_POST['get_polls']))
		$context['zc']['continue_post_data'] .= '<input type="hidden" name="get_polls" value="' . (empty($make_post_data['get_polls']) ? $_POST['get_polls'] : implode(',', $make_post_data['get_polls'])) . '" />';
	
	if (!empty($make_post_data['pre_imported_poll_choices']) || !empty($_POST['pre_imported_poll_choices']))
		$context['zc']['continue_post_data'] .= '<input type="hidden" name="pre_imported_poll_choices" value="' . (empty($make_post_data['pre_imported_poll_choices']) ? $_POST['pre_imported_poll_choices'] : implode(',', $make_post_data['pre_imported_poll_choices'])) . '" />';
	
	if (!empty($make_post_data['imported_poll_choices']) || !empty($_POST['imported_poll_choices']))
		$context['zc']['continue_post_data'] .= '<input type="hidden" name="imported_poll_choices" value="' . (empty($make_post_data['imported_poll_choices']) ? $_POST['imported_poll_choices'] : implode(',', $make_post_data['imported_poll_choices'])) . '" />';
	
	if (!empty($_POST['last_comment_of_article']))
		foreach ($_POST['last_comment_of_article'] as $article_id => $last_comment_id)
			$context['zc']['continue_post_data'] .= '<input type="hidden" name="last_comment_of_article[' . $article_id . ']" value="' . $last_comment_id . '" />';
	
	if (!empty($_POST['blog_id']))
		$context['zc']['continue_post_data'] .= '<input type="hidden" name="blog_id" value="' . $_POST['blog_id'] . '" />';
	
	if (!empty($_POST['last_comment_of_blog']))
		$context['zc']['continue_post_data'] .= '<input type="hidden" name="last_comment_of_blog" value="' . $_POST['last_comment_of_blog'] . '" />';
	
	if (!empty($_POST['last_article_of_blog']))
		$context['zc']['continue_post_data'] .= '<input type="hidden" name="last_article_of_blog" value="' . $_POST['last_article_of_blog'] . '" />';
}

?>