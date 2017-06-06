<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
function zcPrintPage()
{
	global $txt, $scripturl, $context, $txt, $article, $zcFunc;

	if (empty($article))
		zc_fatal_error();

	zcLoadTemplate('Print');
	$context['zc']['template_layers'] = array();
	$context['zc']['sub_template'] = 'printPage';

	// Get the topic starter information.
	$request = $zcFunc['db_query']("
		SELECT t.subject, t.body, t.posted_time, IFNULL(mem.{tbl:members::column:real_name}, t.poster_name) AS poster_name
		FROM {db_prefix}articles AS t
			LEFT JOIN {db_prefix}{table:members} AS mem ON (mem.{tbl:members::column:id_member} = t.poster_id)
		WHERE t.article_id = {int:article_id}
		LIMIT 1", __FILE__, __LINE__,
		array(
			'article_id' => $article
		)
	);
	if ($zcFunc['db_num_rows']($request) == 0)
		zc_fatal_error();
	$row = $zcFunc['db_fetch_assoc']($request);
	$zcFunc['db_free_result']($request);
	
	$row['poster_name'] = $zcFunc['un_htmlspecialchars']($row['poster_name']);
	$row['body'] = $zcFunc['un_htmlspecialchars']($row['body']);
	$row['subject'] = $zcFunc['un_htmlspecialchars']($row['subject']);
	zc_censor_text($row['body']);
	
	// add the article's info to the posts array...
	$context['zc']['article'] = array(
		'subject' => $row['subject'],
		'member' => $row['poster_name'],
		'time' =>  timeformat($row['posted_time'], false),
		'timestamp' => forum_time(true, $row['posted_time']),
		'body' => $zcFunc['parse_bbc']($row['body'], 'print'),
	);

	// get any comments...
	$request = $zcFunc['db_query']("
		SELECT c.posted_time, c.body, IFNULL(mem.{tbl:members::column:real_name}, c.poster_name) AS poster_name
		FROM {db_prefix}comments AS c
			LEFT JOIN {db_prefix}{table:members} AS mem ON (mem.{tbl:members::column:id_member} = c.poster_id)
		WHERE c.article_id = {int:article_id}
		ORDER BY c.comment_id DESC", __FILE__, __LINE__,
		array(
			'article_id' => $article
		)
	);
	$context['zc']['comments'] = array();
	while ($row = $zcFunc['db_fetch_assoc']($request))
	{
		$row['body'] = $zcFunc['un_htmlspecialchars']($row['body']);
		zc_censor_text($row['body']);

		$context['zc']['comments'][] = array(
			'subject' => '',
			'member' => $row['poster_name'],
			'time' =>  timeformat($row['posted_time'], false),
			'timestamp' => forum_time(true, $row['posted_time']),
			'body' => $zcFunc['parse_bbc']($row['body'], 'print'),
		);
	}
	$zcFunc['db_free_result']($request);
}
    
?>