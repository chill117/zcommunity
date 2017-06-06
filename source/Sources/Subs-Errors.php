<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
function zc_prepare_error_settings_array()
{
	global $context, $scripturl;
	
	return array(
		'_info_' => array(
			'hidden_form_values' => array('save_errorSettings' => 1),
			'form_url' => $scripturl . zcRequestVarsToString(null, '?') .';sesc='. $context['session_id'],
		),
		'do_error_logging' => array(
			'type' => 'check',
			'label' => 'b699',
			'value' => 1,
			'header_above' => 'b442',
		),
		'error_types_to_log' => array(
			'type' => 'text',
			'value' => 'general,critical,database,template,language,undefined_index',
			'custom' => 'multi_check',
			'options' => array(
				'general' => 'b245',
				'critical' => 'b700',
				'database' => 'b701',
				'template' => 'b702',
				'language' => 'b703',
				'undefined_index' => 'b704',
			),
			'instructions' => 'b705',
			'needs_explode' => true,
		),
	);
}

// enable/disable logging  of errors of specific types
function zc_get_list_of_errors()
{
	global $zc, $zcFunc, $txt, $scripturl, $context;
	
	$list_info = array();
	$list_of_errors = array();
	
	// find out the number of errors we are dealing with....
	$request = $zcFunc['db_query']("
		SELECT COUNT(error_id)
		FROM {db_prefix}log_errors", __FILE__, __LINE__);
	list($num_errors) = $zcFunc['db_fetch_row']($request);
	$zcFunc['db_free_result']($request);
	
	$start = isset($_REQUEST['listStart']) ? (int) $_REQUEST['listStart'] : 0;
	$maxindex = isset($_REQUEST['all']) && !empty($zc['settings']['allow_show_all_link']) ? 99999 : min(10, $num_errors);
	$list_info['show_page_index'] = !empty($num_errors) && $num_errors > $maxindex;
		
	if ($list_info['show_page_index'])
	{
		$list_info['page_index'] = zcConstructPageIndex($scripturl . zcRequestVarsToString('all,listStart', '?') . ';listStart=%d', $start, $num_errors, $maxindex, true);
		
		if (!empty($zc['settings']['allow_show_all_link']))
			$list_info['show_all_link'] = '<a href="' . $scripturl . zcRequestVarsToString('listStart', '?') . ';all">' . $txt['b81'] . '</a>';
	}
	
	// Default sort methods.
	$sort_methods = array(
		'error_type' => 'le.error_type',
		'file' => 'le.file',
		'line' => 'le.line',
		'member' => 'le.member_id',
		'ip' => 'le.ip',
		'time' => 'le.timestamp',
	);

	if (!isset($_REQUEST['sort']) || !isset($sort_methods[$_REQUEST['sort']]))
	{
		$sort_by = 'time';
		$sort = 'le.timestamp';
		$ascending = isset($_REQUEST['asc']);
	}
	else
	{
		$sort_by = $_REQUEST['sort'];
		$sort = $sort_methods[$_REQUEST['sort']];
		$ascending = isset($_REQUEST['asc']);
	}

	// make array of table header info
	$tableHeaders = array(
		'url_requests' => zcRequestVarsToString('sort,asc,desc', '?'),
		'headers' => array(
			'error_type' => array('label' => $txt['b697']),
			'file' => array('label' => $txt['b685']),
			'line' => array('label' => $txt['b686']),
			'member' => array('label' => $txt['b3024a']),
			'ip' => array('label' => $txt['b694']),
			'time' => array('label' => $txt['b695']),
		),
		'sort_direction' => $ascending ? 'up' : 'down',
		'sort_by' => $sort_by,
	);
	
	// create the table headers
	$list_info['table_headers'] = zcCreateTableHeaders($tableHeaders);
	$list_info['table_headers']['checkbox'] = '<input type="checkbox" onclick="invertAll(this, this.form, \'errors[]\');" class="check" />';
	$list_info['alignment'] = array('message' => 'left', 'time' => 'right');
	
	$list_info['title'] = $txt['b3040'];
	$list_info['list_empty_txt'] = $txt['b692'];
	$list_info['submit_button_txt'] = $txt['b3006'];
	$list_info['confirm_submit_txt'] = sprintf($txt['b71'], $txt['b693']);
	$list_info['form_url'] = $scripturl . zcRequestVarsToString(null, '?');

	// get errors...
	$request = $zcFunc['db_query']("
		SELECT 
			le.error_id, le.error_type, le.member_id, le.ip, le.session, le.file, le.line, le.timestamp, le.url, le.message, le.line,
			mem.{tbl:members::column:real_name} AS real_name
		FROM {db_prefix}log_errors AS le
			LEFT JOIN {db_prefix}{table:members} AS mem ON (mem.{tbl:members::column:id_member} = le.member_id)
		ORDER BY {raw:sort}" . ($ascending ? '' : ' DESC') . "
		LIMIT {int:start}, {int:maxindex}", __FILE__, __LINE__,
		array(
			'sort' => $sort,
			'start' => $start,
			'maxindex' => $maxindex
		)
	);
	
	while ($row = $zcFunc['db_fetch_assoc']($request))
	{
		$row['message'] = $zcFunc['un_htmlspecialchars']($row['message']);
		$truncated_msg = zcTruncateText($row['message'], 65, ' ', 10, '');
		$list_of_errors[$row['error_id']] = array(
			'error_type' => isset($context['zc']['error_settings_array']['error_types_to_log']['options'][$row['error_type']]) ? zcFormatTxtString($context['zc']['error_settings_array']['error_types_to_log']['options'][$row['error_type']]) : $row['error_type'],
			'message' => strlen($truncated_msg) < strlen($row['message']) ? '<span id="part_msg_' . $row['error_id'] . '" style="display:block;">' . $truncated_msg . '<a href="javascript:void(0);" onclick="getElementById(\'full_msg_' . $row['error_id'] . '\').style.display=\'block\'; getElementById(\'part_msg_' . $row['error_id'] . '\').style.display=\'none\';" title="See full error message"> ... </a></span><span id="full_msg_' . $row['error_id'] . '" style="display:none;">' . $row['message'] . '</span>' : $row['message'],
			'file' => substr($row['file'], (-1) * strrpos_n('/', $row['file'], 2)),
			'line' => !empty($row['file']) ? $row['line'] : '',
			'member' => !empty($row['member_id']) ? sprintf($context['zc']['link_templates']['user_profile'], $row['member_id'], $zcFunc['un_htmlspecialchars']($row['real_name']), ' title="' . $txt['b41'] . '"') : $txt['b567'],
			'ip' => sprintf($context['zc']['link_templates']['trackip'], $row['ip'], $row['ip'], ' title="' . $txt['b698'] . '"'),
			'time' => timeformat($row['timestamp']),
			'checkbox' => '<input type="checkbox" name="errors[]" value="' . $row['error_id'] . '" class="check" />',
		);
	}
	$zcFunc['db_free_result']($request);
	
	return array($list_of_errors, $list_info);
}

?>