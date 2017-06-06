<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
function zc_register_member($processed)
{
	if (empty($processed))
		zc_fatal_error();
		
	global $zcFunc, $zc;
	
	$processed['time_registered'] = time();
	
	// don't need these anymore...
	if (!empty($context['zc']['form_info']['_info_']['exclude_from_table']))
		foreach($context['zc']['form_info']['_info_']['exclude_from_table'] as $k)
			unset($processed[$k]);
		
	$columns = array();
	foreach ($processed as $k => $dummy)
		$columns[$k] = isset($context['zc']['form_info'][$k]['type']) ? $context['zc']['form_info'][$k]['type'] : 'string';
		
	$zcFunc['db_insert']('insert', '{db_prefix}members', $columns, $processed);
	$member_id = $zcFunc['db_insert_id']();
}

function zc_update_member_data($updates, $member_id)
{
	if (empty($updates) || empty($member_id))
		return;
		
	global $zcFunc;
	
	// don't need these anymore...
	if (!empty($context['zc']['form_info']['_info_']['exclude_from_table']))
		foreach($context['zc']['form_info']['_info_']['exclude_from_table'] as $k)
			unset($processed[$k]);
		
	$columns = array('member_id' => 'int');
	foreach ($updates as $k => $v)
		$columns[$k] = isset($context['zc']['form_info'][$k]['type']) ? $context['zc']['form_info'][$k]['type'] : 'string';
	
	$zcFunc['db_update'](
		'{db_prefix}members',
		$columns,
		$updates,
		array('member_id' => $member_id));
}

?>