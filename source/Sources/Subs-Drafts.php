<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
function zcSaveDraft($processed)
{
	global $context, $txt;
	global $article, $comment, $blog, $zc, $zcFunc;
	
	// for drafts of articles...
	if (isset($_REQUEST['article']) && !isset($_REQUEST['comment']) && (!empty($article) || !empty($comment) || !empty($context['zc']['current_info']) || empty($processed)))
		zc_fatal_error();
	
	// for drafts of comments...
	if (isset($_REQUEST['comment']) && (!empty($comment) || !empty($context['zc']['current_info']) || empty($processed)))
		zc_fatal_error();
		
	// have they reached the maximum number of drafts allowed?
	if (!$context['user']['is_admin'] && $context['zc']['num_drafts'] >= $zc['settings']['drafts_max_num'])
		zc_fatal_error(array('zc_error_50', 'b321a'));
		
	$processed['poster_id'] = $context['user']['id'];
	$processed['last_saved_time'] = time();
	
	// is this a draft of an article?
	$processed['article'] = isset($_REQUEST['article']) && !isset($_REQUEST['comment']) ? 1 : 0;
	// maybe it's a draft of a comment?
	$processed['comment'] = isset($_REQUEST['comment']) ? 1 : 0;
	
	// saving over a pre-existing draft?
	if (!empty($_POST['draft_id']))
	{
		$draft_id = (int) $_POST['draft_id'];
		
		// make sure the draft actually exists!
		$request = $zcFunc['db_query']("
			SELECT draft_id
			FROM {db_prefix}drafts
			WHERE draft_id = {int:draft_id}
			LIMIT 1", __FILE__, __LINE__,
			array(
				'draft_id' => $draft_id
			)
		);
		// doesn't exist... so we're gonna make a new draft instead...
		if ($zcFunc['db_num_rows']($request) == 0)
			$draft_id = 0;
		$zcFunc['db_free_result']($request);
	}
	
	
	if (!empty($draft_id))
		$columns = array('draft_id' => 'int');
	else
		$columns = array();
		
	foreach ($processed as $k => $v)
		$columns[$k] = 'string';
	
	if (!empty($draft_id))
		// update pre-existing draft...
		$zcFunc['db_update']('{db_prefix}drafts', $columns, $processed, array('draft_id' => $draft_id));
	else
		// insert the draft into the blog_drafts table
		$zcFunc['db_insert']('insert', '{db_prefix}drafts', $columns, $processed);
	
	// redirect back to where they came from...
	zc_redirect_exit('zc=post' . (!empty($blog) ? ';blog=' . $blog : '') . (isset($_REQUEST['article']) ? ';article' . (!empty($article) ? '=' . $article : '') : '') . (isset($_REQUEST['comment']) ? ';comment' : '') . (isset($_REQUEST['poll']) ? ';poll' : ''));
}

function zcDeleteDraft()
{
	global $context;
	global $draft, $blog, $article, $zcFunc;
	
	// check that session!
	checkSession('get');
	
	$drafts = array();
	// assemble array of drafts to delete...
	if (!empty($_POST['drafts']))
		foreach ($_POST['drafts'] as $draft_id)
			$drafts[] = (int) $draft_id;
	elseif (!empty($draft))
		$drafts = array($draft);
	
	// nothing to delete?
	if (empty($drafts))
		zc_redirect_exit('zc=post' . (!empty($blog) ? ';blog=' . $blog : '') . (isset($_REQUEST['article']) ? ';article' . (!empty($article) ? '=' . $article : '') : '') . (isset($_REQUEST['comment']) ? ';comment' : '') . (isset($_REQUEST['poll']) ? ';poll' : ''));
		
	// get info about the drafts
	$request = $zcFunc['db_query']("
		SELECT draft_id, poster_id
		FROM {db_prefix}drafts
		WHERE draft_id IN ({array_int:drafts})
		LIMIT {int:limit}", __FILE__, __LINE__,
		array(
			'drafts' => $drafts,
			'limit' => count($drafts)
		)
	);
		
	$drafts = array();
	// we must make sure they are allowed to delete the drafts...
	while ($row = $zcFunc['db_fetch_assoc']($request))
	{
		$can_do_this = $context['user']['is_admin'] || ($context['can_save_drafts'] && !$context['user']['is_guest'] && $row['poster_id'] == $context['user']['id']);
			
		// are they allowed to delete this draft?
		if ($can_do_this)
			$drafts[] = $row['draft_id'];
	}
	$zcFunc['db_free_result']($request);
		
	// delete the drafts
	if (!empty($drafts))
		$zcFunc['db_query']("
			DELETE FROM {db_prefix}drafts
			WHERE draft_id IN ({array_int:drafts})
			LIMIT {int:limit}", __FILE__, __LINE__,
		array(
			'drafts' => $drafts,
			'limit' => count($drafts)
		)
	);

	// redirect back to where they came from...
	zc_redirect_exit('zc=post' . (!empty($blog) ? ';blog=' . $blog : '') . (isset($_REQUEST['article']) ? ';article' . (!empty($article) ? '=' . $article : '') : '') . (isset($_REQUEST['comment']) ? ';comment' : '') . (isset($_REQUEST['poll']) ? ';poll' : ''));
}

function zcGetDrafts($type)
{
	global $context, $scripturl, $txt;
	global $zcFunc, $blog, $article, $zc;
	
	$drafts = array();
	$list = array();
	$supported_draft_types = array('article', 'comment');
	
	// must have a type...
	if (empty($type) || !in_array($type, $supported_draft_types))
		return array($drafts, $list);
	
	// get the number of drafts this user has...
	$request = $zcFunc['db_query']("
		SELECT COUNT(draft_id) AS num_drafts
		FROM {db_prefix}drafts
		WHERE poster_id = {int:user_id}
			AND {raw:type} = 1", __FILE__, __LINE__,
		array(
			'type' => $type,
			'user_id' => $context['user']['id']
		)
	);
	list($context['zc']['num_drafts']) = $zcFunc['db_fetch_row']($request);
	$zcFunc['db_free_result']($request);
	
	$context['zc']['unhide_drafts'] = isset($_REQUEST['listStart2']) || isset($_REQUEST['all2']) || isset($_REQUEST['sort2']) || isset($_REQUEST['asc2']) || isset($_REQUEST['desc2']);

	$start = isset($_REQUEST['listStart2']) ? (int) $_REQUEST['listStart2'] : 0;
	$maxindex = isset($_REQUEST['all2']) && !empty($zc['settings']['allow_show_all_link']) ? 99999 : min(10, $context['zc']['num_drafts']);
	
	$list_info['show_page_index'] = !empty($context['zc']['num_drafts']) && $context['zc']['num_drafts'] > $maxindex;
		
	if ($list_info['show_page_index'])
	{
		$list_info['page_index'] = zcConstructPageIndex($scripturl . zcRequestVarsToString('all2,listStart2', '?') . ';listStart2=%d#drafts', $start, $context['zc']['num_drafts'], $maxindex, true);
		
		if (!empty($zc['settings']['allow_show_all_link']))
			$list_info['show_all_link'] = '<a href="' . $scripturl . zcRequestVarsToString('listStart2', '?') . ';all2#drafts">'. $txt['b81'] .'</a>';
	}

	// Default sort methods.
	$sort_methods = array(
		'subject' => 'subject',
		'last_saved' => 'last_saved_time',
	);

	if (!isset($_REQUEST['sort2']) || !isset($sort_methods[$_REQUEST['sort2']]))
	{
		$sort_by = 'title';
		$sort2 = 'subject';
		$ascending = !isset($_REQUEST['asc2']);
	}
	else
	{
		$sort_by = $_REQUEST['sort2'];
		$sort2 = $sort_methods[$_REQUEST['sort2']];
		$ascending = $sort_by == 'title' ? !isset($_REQUEST['asc2']) : isset($_REQUEST['asc2']);
	}
	
	// make array of table header info
	$tableHeaders = array(
		'url_requests' => zcRequestVarsToString('sort2,asc2,desc2', '?'),
		'headers' => array(
			'subject' => array('label' => $txt['b227']),
			'load_link' => array('label' => ''),
			'last_saved' => array('label' => $txt['b228']),
		),
		'sort_direction' => $ascending ? 'up' : 'down',
		'sort_by' => $sort_by,
		'url_end' => '#drafts',
	);
	
	// create the table headers
	$list_info['table_headers'] = zcCreateTableHeaders($tableHeaders, 2);
	$list_info['table_headers']['checkbox'] = '<input type="checkbox" onclick="invertAll(this, this.form, \'drafts[]\');" class="check" />';

	// get info about the drafts....
	$request = $zcFunc['db_query']("
		SELECT draft_id, subject, last_saved_time, body, smileys_enabled
		FROM {db_prefix}drafts
		WHERE {raw:type} = 1
			AND poster_id = {int:user_id}
		ORDER BY {raw:sort}" . ($ascending ? '' : ' DESC') . "
		LIMIT {int:start}, {int:maxindex}", __FILE__, __LINE__,
		array(
			'type' => $type,
			'sort' => $sort2,
			'start' => $start,
			'maxindex' => $maxindex,
			'user_id' => $context['user']['id']
		)
	);
	
	while ($row = $zcFunc['db_fetch_assoc']($request))
	{
		$row['subject'] = $zcFunc['un_htmlspecialchars']($row['subject']);
		$row['body'] = $zcFunc['un_htmlspecialchars']($row['body']);
		zc_censor_text($row['subject']);
		zc_censor_text($row['body']);
		$row['body'] = $zcFunc['parse_bbc']($row['body'], $row['smileys_enabled']);
		$drafts[$row['draft_id']] = array(
			'subject' => $type == 'article' ? $row['subject'] : zcTruncateText($row['body'], 30, ' ', 1),
			'last_saved' => timeformat($row['last_saved_time']),
			'load_link' => '<a href="' . $scripturl . zcRequestVarsToString('draft', '?') .';draft=' . $row['draft_id'] . '">'. $txt['b209'] .'</a>',
			'checkbox' => '<input type="checkbox" name="drafts[]" value="'. $row['draft_id'] .'" />',
		);
		
		$drafts[$row['draft_id']]['subject'] .= '<br /><span class="hoverBoxActivator" onclick="document.getElementById(\'preview_' . $row['draft_id'] . '\').style.display = \'block\';">' . $txt['b306'] . '</span><div class="hoverBox" id="preview_' . $row['draft_id'] . '" style="display:none; margin-top:3px;"><div class="hoverBoxHeader"><span class="hoverBoxClose" onmouseup="document.getElementById(\'preview_' . $row['draft_id'] .'\').style.display = \'none\';" title="' . $txt['b305'] . '">X</span>&nbsp;&nbsp;<span class="hoverBoxTitle">' . $txt['b159'] . '</span></div><div class="hoverBoxBody" style="line-height:135%;"><table width="100%" cellspacing="0" cellpadding="0" border="0" style="table-layout:fixed;"><tr class="noPadding"><td><div style="width:100%; overflow:auto;">' . (zcTruncateText($row['body'], $zc['settings']['drafts_max_preview_length'], ' ', 40, $txt['b31a'], $scripturl . '?draft='. $row['draft_id'] . zcRequestVarsToString('draft', ';'), $txt['b209'])) . '</div></td></tr></table></div></div>';
	}
	$zcFunc['db_free_result']($request);
	
	$list_info['submit_button_txt'] = $txt['b3006'];
	$list_info['confirm_submit_txt'] = sprintf($txt['b71'], $txt['b321a']);
	$list_info['form_url'] = $scripturl . '?zc=deletedraft;sesc='. $context['session_id'] . zcRequestVarsToString('zc', ';');
			
	return array($drafts, $list_info);
}

function zcLoadDraft($draft, $type)
{
	global $context, $txt;
	global $article, $blog, $zcFunc;
	
	// must be allowed to save drafts....
	if ($context['user']['is_guest'] || !$context['can_save_drafts'])
		zc_fatal_error(array('zc_error_17', 'b165'));

	// get the draft from the blog_drafts table
	$request = $zcFunc['db_query']("
		SELECT poster_id, subject, body, smileys_enabled, locked, article, blog_category_id, blog_tags, comment
		FROM {db_prefix}drafts
		WHERE draft_id = {int:draft_id}
		LIMIT 1", __FILE__, __LINE__,
		array(
			'draft_id' => $draft
		)
	);
		
	// the draft simply doesn't exist...
	if ($zcFunc['db_num_rows']($request) == 0)
		zc_fatal_error('zc_error_31');
	
	$draft_info = $zcFunc['db_fetch_assoc']($request);
	$zcFunc['db_free_result']($request);
	
	// make sure it's the correct type of draft....
	if (empty($draft_info[$type]))
		zc_fatal_error('zc_error_32');
	
	// this is not their draft... error!
	if ($draft_info['poster_id'] != $context['user']['id'])
		zc_fatal_error(array('zc_error_17', 'b475'));
	
	$draft_info['subject'] = $zcFunc['un_htmlspecialchars']($draft_info['subject']);
	$draft_info['body'] = $zcFunc['un_htmlspecialchars']($draft_info['body']);
	$draft_info['blog_tags'] = $zcFunc['un_htmlspecialchars']($draft_info['blog_tags']);
			
	return $draft_info;
}
	
?>