<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
/*
	This file contains functions for modifying the database
*/

function zcCreateDatabaseTable($table_info)
{
	global $zcFunc;
	
	if (empty($table_info))
		return false;
		
	// default limits for each type...
	$limits_by_type = array(
		'varchar' => 255,
		'tinyint' => 1,
		'mediumint' => 4,
		'int' => 10,
		'text' => '',
		'blob' => '',
	);
	
	$info = array('table_name' => $table_info['table_name'], 'primary_key' => isset($table_info['primary_key']) ? $table_info['primary_key'] : '');
	$new_columns = array();
	foreach ($table_info['columns'] as $column => $array)
	{
		if (!in_array($array['type'], array('text', 'blob')) && (!empty($default) || (isset($default) && $default === 0)))
		{
			$info[$k] = $default;
			$default = ' default {string:' . $k . '}';
		}
		else
			$default = '';
		
		$new_columns[] = $column . ' ' . $array['type'] . (!empty($array['limit']) ? '(' . $array['limit'] . ')' : (!empty($limits_by_type[$array['type']]) ? '(' . $limits_by_type[$array['type']] . ')' : '')) . ' ' . (!empty($array['attributes']) ? $array['attributes'] : '') . (!empty($array['null']) ? ' NULL' : ' NOT NULL') . (!empty($array['auto_increment']) && $array['type'] != 'text' ? ' auto_increment' : $default);
	}
	
	// create the new table...
	if (!empty($new_columns))
		$result = $zcFunc['db_query']("
			CREATE TABLE IF NOT EXISTS {db_prefix}{raw:table_name}(
				" . implode(',
				', $new_columns) . (!empty($table_info['primary_key']) ? ",
				PRIMARY KEY({raw:primary_key})" : '') . ")", __FILE__, __LINE__, $info);
	else
		return false;
			
	if ($result !== false)
		return true;
	else
		return false;
}

function zcAlterDatabaseTable($alter_table)
{
	global $zcFunc;
	
	if (empty($alter_table) || empty($alter_table['table_name']))
		return false;
		
	if (!empty($alter_table['new_columns']) || !empty($alter_table['change_columns']))
	{
		// default limits for each type of column...
		$limits_by_type = array(
			'varchar' => 255,
			'tinyint' => 1,
			'mediumint' => 4,
			'int' => 10,
			'text' => '',
			'blob' => '',
		);
	
		$alterations = array();
		if (!empty($alter_table['new_columns']))
			foreach ($alter_table['new_columns'] as $column => $array)
				$alterations[] = 'ADD `' . $column . '` ' . $array['type'] . (!empty($array['limit']) ? '(' . $array['limit'] . ')' : (!empty($limits_by_type[$array['type']]) ? '(' . $limits_by_type[$array['type']] . ')' : '')) . ' ' . (!empty($array['attributes']) ? $array['attributes'] : '') . ' ' . (!empty($array['null']) ? 'NULL' : 'NOT NULL') . ' ' . (!empty($array['auto_increment']) ? 'auto_increment' : 'default \'' . (isset($array['default']) ? $array['default'] : 0) . '\'');
			
		if (!empty($alter_table['change_columns']))
			foreach ($alter_table['change_columns'] as $column => $array)
				$alterations[] = 'CHANGE `' . $column . '` `' . (!empty($array['column_name']) ? $array['column_name'] : $column) . '` ' . $array['type'] . (!empty($array['limit']) ? '(' . $array['limit'] . ')' : (!empty($limits_by_type[$array['type']]) ? '(' . $limits_by_type[$array['type']] . ')' : '')) . ' ' . (!empty($array['attributes']) ? $array['attributes'] : '') . ' ' . (!empty($array['null']) ? 'NULL' : 'NOT NULL') . ' ' . (!empty($array['auto_increment']) ? 'auto_increment' : 'default \'' . (isset($array['default']) ? $array['default'] : 0) . '\'');
				
		if (!empty($alter_table['drop_columns']))
			foreach ($alter_table['drop_columns'] as $column => $array)
				$alterations[] = 'DROP `' . $column . '`';
	}
	
	if (!empty($alterations) || !empty($alter_table['primary_key']))
		$result = $zcFunc['db_query']("
			ALTER TABLE `{db_prefix}$alter_table[table_name]`
				" . (!empty($alterations) ? implode(',
				', $alterations) : '') . (!empty($alter_table['primary_key']) ? ",
				PRIMARY KEY($alter_table[primary_key])" : '') . "", __FILE__, __LINE__);
	else
		return false;
			
	if ($result !== false)
		return true;
	else
		return false;
}

?>