<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
/*
	zc_db_init()
		- populates $zcFunc with database related functions
		- makes a connection to the database (if stand-alone zCommunity)
		
	zc_db_query($query, $file = null, $line = null, $values = null, $identifier = null, $connection = null)
		- protects against database related attacks by cleaning and checking $query
		- queries the database
		- returns a database result resource
		
	zc_db_insert()
		- easily + securely insert data into the database
		
	zc_db_update()
		- easily + securely update data in the database
*/

function zc_db_init()
{
	global $zc, $zcFunc;
	
	if (!isset($zcFunc['db_fetch_assoc']) || $zcFunc['db_fetch_assoc'] != 'mysql_fetch_assoc')
		$zcFunc += array(
			'db_query' => 'zc_db_query',
			'db_insert' => 'zc_db_insert',
			'db_update' => 'zc_db_update',
			'db_fetch_assoc' => 'mysql_fetch_assoc',
			'db_fetch_row' => 'mysql_fetch_row',
			'db_free_result' => 'mysql_free_result',
			'db_insert_id' => 'mysql_insert_id',
			'db_num_rows' => 'mysql_num_rows',
			'db_num_fields' => 'mysql_num_fields',
			'db_escape_string' => 'addslashes',
			'db_unescape_string' => 'stripslashes',
			'db_server_info' => 'mysql_get_server_info',
			'db_error' => 'mysql_error',
			'db_select_db' => 'mysql_select_db',
			'db_affected_rows' => 'mysql_affected_rows',
			'db_title' => 'MySQL',
		);

	// this stuff we do only if zCommunity is stand-alone
	if (!$zc['with_software']['version'])
	{
		if (!empty($zc['db_persist']))
			$connection = @mysql_pconnect($zc['db_server'], $zc['db_user'], $zc['db_passwd']);
		else
			$connection = @mysql_connect($zc['db_server'], $zc['db_user'], $zc['db_passwd']);

		if (!$connection)
			zc_db_fatal_error();

		if (!@mysql_select_db($zc['db_name'], $connection))
			zc_db_fatal_error();
	
		return $connection;
	}
}

function zc_db_replacement__callback($matches)
{
	global $zc;

	list($values, $connection) = $zc['for_db_callback'];
	
	if ($matches[1] === 'db_prefix')
		return (!$zc['with_software']['version'] ? '' : $zc['with_software']['db_prefix']) . $zc['db_prefix'];
		
	if ($matches[1] === 'empty_string')
		return '\'\'';

	if (!isset($matches[2]))
	{
		list($err_msg, $log_msg) = zc_get_err_log_msg('zc_error_83', 'There appears to have been a problem with the database.  Please try again.<br />If the problem persists, please contact the site\'s administrator.', 'zc_error_89', 'Invalid value inserted or no type specified.');
		zc_db_error_backtrace($err_msg, $log_msg, E_USER_ERROR, __FILE__, __LINE__);
	}

	if (!isset($values[$matches[2]]))
	{
		list($err_msg, $log_msg) = zc_get_err_log_msg('zc_error_83', 'There appears to have been a problem with the database.  Please try again.<br />If the problem persists, please contact the site\'s administrator.', 'zc_error_90', 'The value you\'re trying to insert into a database query does not exist');
		zc_db_error_backtrace($err_msg, $log_msg . ': ' . htmlspecialchars($matches[2]), E_USER_ERROR, __FILE__, __LINE__);
	}

	$replacement = $values[$matches[2]];

	if ($matches[1] == 'int')
	{
		// expecting an integer here
		if (!is_numeric($replacement) || (string) $replacement !== (string) (int) $replacement)
		{
			list($err_msg, $log_msg) = zc_get_err_log_msg('zc_error_83', 'There appears to have been a problem with the database.  Please try again.<br />If the problem persists, please contact the site\'s administrator.', 'zc_error_91', 'Wrong value type sent to the database. Integer expected. (%1$s)');
			zc_db_error_backtrace($err_msg, sprintf($log_msg, $matches[2]), E_USER_ERROR, __FILE__, __LINE__);
		}
		return (int) $replacement;
	}
	elseif (in_array($matches[1], array('string', 'text')))
		// escape with mysql_real_escape_string and place inside single quotes
		return sprintf('\'%1$s\'', mysql_real_escape_string($replacement, $connection));
	elseif ($matches[1] == 'array_int')
	{
		if (is_array($replacement))
		{
			// database syntax error happens if we were to pass an empty value through to the db query function
			if (empty($replacement))
			{
				list($err_msg, $log_msg) = zc_get_err_log_msg('zc_error_83', 'There appears to have been a problem with the database.  Please try again.<br />If the problem persists, please contact the site\'s administrator.', 'zc_error_92', 'Database error, given array of integer values is empty. (%1$s)');
				zc_db_error_backtrace($err_msg, sprintf($log_msg, $matches[2]), E_USER_ERROR, __FILE__, __LINE__);
			}

			foreach ($replacement as $k => $v)
			{
				// we're expecting integers only here
				if (!is_numeric($v) || (string) $v !== (string) (int) $v)
				{
					list($err_msg, $log_msg) = zc_get_err_log_msg('zc_error_83', 'There appears to have been a problem with the database.  Please try again.<br />If the problem persists, please contact the site\'s administrator.', 'zc_error_92', 'Wrong value type sent to the database. Array of integers expected. (%1$s)');
					zc_db_error_backtrace($err_msg, sprintf($log_msg, $matches[2]), E_USER_ERROR, __FILE__, __LINE__);
				}

				$replacement[$k] = (int) $v;
			}

			return implode(', ', $replacement);
		}
		else
		{
			// we were expecting an array...
			list($err_msg, $log_msg) = zc_get_err_log_msg('zc_error_83', 'There appears to have been a problem with the database.  Please try again.<br />If the problem persists, please contact the site\'s administrator.', 'zc_error_92', 'Wrong value type sent to the database. Array of integers expected. (%1$s)');
			zc_db_error_backtrace($err_msg, sprintf($log_msg, $matches[2]), E_USER_ERROR, __FILE__, __LINE__);
		}
	}
	elseif ($matches[1] == 'array_string')
	{
		if (is_array($replacement))
		{
			// database syntax error happens if we were to pass an empty value through to the db query function
			if (empty($replacement))
			{
				list($err_msg, $log_msg) = zc_get_err_log_msg('zc_error_83', 'There appears to have been a problem with the database.  Please try again.<br />If the problem persists, please contact the site\'s administrator.', 'zc_error_93', 'Database error, given array of string values is empty. (%1$s)');
				zc_db_error_backtrace($err_msg, sprintf($log_msg, $matches[2]), E_USER_ERROR, __FILE__, __LINE__);
			}

			// for each value in the array, escape with mysql_real_escape_string and place inside single quotes
			foreach ($replacement as $k => $v)
				// mysql_real_escape_string escapes special characters in the string for use in an SQL statement
				$replacement[$k] = sprintf('\'%1$s\'', mysql_real_escape_string($v, $connection));

			return implode(', ', $replacement);
		}
		else
		{
			// we were expecting an array of strings...
			list($err_msg, $log_msg) = zc_get_err_log_msg('zc_error_83', 'There appears to have been a problem with the database.  Please try again.<br />If the problem persists, please contact the site\'s administrator.', 'zc_error_93', 'Database error, given array of string values is empty. (%1$s)');
			zc_db_error_backtrace($err_msg, sprintf($log_msg, $matches[2]), E_USER_ERROR, __FILE__, __LINE__);
		}
	}
	elseif ($matches[1] == 'float')
	{
		// floating point NUMBER
		if (!is_numeric($replacement))
		{
			// we were expecting an array of strings...
			list($err_msg, $log_msg) = zc_get_err_log_msg('zc_error_83', 'There appears to have been a problem with the database.  Please try again.<br />If the problem persists, please contact the site\'s administrator.', 'zc_error_94', 'Wrong value type sent to the database. Floating point number expected. (%1$s)');
			zc_db_error_backtrace($err_msg, sprintf($log_msg, $matches[2]), E_USER_ERROR, __FILE__, __LINE__);
		}
		return (string) (float) $replacement;
	}
	// we don't do anything if it's raw...
	elseif ($matches[1] == 'raw')
		return $replacement;
	else
	{
		// probably a typo or something?
		list($err_msg, $log_msg) = zc_get_err_log_msg('zc_error_83', 'There appears to have been a problem with the database.  Please try again.<br />If the problem persists, please contact the site\'s administrator.', 'zc_error_95', 'Undefined type used in the database query. (%1$s)');
		zc_db_error_backtrace($err_msg, sprintf($log_msg, $matches[2]), false, __FILE__, __LINE__);
	}
}

function zc_db_query($query, $file = null, $line = null, $values = null, $identifier = null, $connection = null)
{
	global $context, $zcFunc, $zc;
	
	$result = false;
	
	// array of special tables...
	if (!$zc['with_software']['version'])
		$special_tables = array('membergroups', 'members');
	elseif (in_array($zc['with_software']['version'], $zc['smf_versions']));
		$special_tables = array('attachments', 'boards', 'log_online', 'log_polls', 'messages', 'moderators', 'polls', 'poll_choices', 'topics', 'membergroups', 'members', 'settings');
		
	// check $query for {db_prefix}{table:x}
	if (preg_match_all('~{db_prefix}{table:([A-Za-z0-9_]+)?}~', $query, $matches) >= 1)
	{
		if (isset($values))
			$values_keys = implode(',', array_keys($values));
	
		foreach ($matches[1] as $table)
		{
			// not a valid special table...
			if (!in_array($table, $special_tables))
			{
				list($err_msg, $log_msg) = zc_get_err_log_msg('zc_error_83', 'There appears to have been a problem with the database.  Please try again.<br />If the problem persists, please contact the site\'s administrator.', 'zc_error_79', 'An invalid table name was used in the following database query:<br /><br />%1$s');
				zc_db_error_backtrace($err_msg, sprintf($log_msg, $query), false, __FILE__, __LINE__);
			}
			
			// stand-alone zCommunity...
			if (!$zc['with_software']['version'])
			{
				$query = str_replace('{db_prefix}{table:' . $table . '}', $zc['db_prefix'] . $table, $query);
				
				// check database query for {tbl:x::column:y}
				if (preg_match_all('~{tbl:' . $table . '::column:([A-Za-z0-9_]+)?}~', $query, $matches) >= 1)
				{
					// we simply replace the string that matches the full pattern with the sub-pattern match
					foreach ($matches[1] as $k => $column)
					{
						$query = str_replace($matches[0][$k], $column, $query);
						
						if (isset($values_keys))
							$values_keys = str_replace($matches[0][$k], $column, $values_keys);
					}
				}
				continue;
			}
			// for software zCommunity is integrated with...
			elseif (!isset($table_info[$table]))
			{
				if (!isset($table_info))
					$table_info = array();
		
				if ($zc['with_software']['version'] == 'SMF 2.0')
					require_once($zc['sources_dir'] . '/db-info-smf2.php');
				elseif ($zc['with_software']['version'] == 'SMF 1.1.x')
					require_once($zc['sources_dir'] . '/db-info-smf1.php');
					
				// get info about this table...
				if (in_array($zc['with_software']['version'], $zc['smf_versions']) && function_exists('zc_smf_db_table_info'))
					$table_info[$table] = zc_smf_db_table_info($table);
			}
				
			if (!empty($table_info[$table]))
			{
				$table_name = !empty($table_info[$table]['table_name']) ? $table_info[$table]['table_name'] : $table;
				$query = str_replace('{db_prefix}{table:' . $table . '}', $zc['with_software']['db_prefix'] . $table_name, $query);
				
				if (!empty($table_info[$table]['columns']))
				{
					// check database query for {tbl:x::column:y}
					if (preg_match_all('~{tbl:' . $table . '::column:([A-Za-z0-9_]+)?}~', $query, $matches) >= 1)
					{
						foreach ($matches[1] as $k => $column)
						{
							$query = str_replace($matches[0][$k], $table_info[$table]['columns'][$column], $query);
						
							if (isset($values_keys))
								$values_keys = str_replace($matches[0][$k], $table_info[$table]['columns'][$column], $values_keys);
						}
					}
				}
			}
			// table info couldn't be found....
			else
			{
				list($err_msg, $log_msg) = zc_get_err_log_msg('zc_error_83', 'There appears to have been a problem with the database.  Please try again.<br />If the problem persists, please contact the site\'s administrator.', 'zc_error_78', 'Failed to load table information for the following database query:<br /><br />%1$s');
				zc_db_error_backtrace($err_msg, sprintf($log_msg, $query), false, __FILE__, __LINE__);
			}
		}
		
		if (isset($values_keys))
		{
			$new_keys_values = explode(',', $values_keys);
			$i = 0;
			foreach ($values as $old_key => $v)
			{
				if ($new_keys_values[$i] != $old_key)
				{
					$values[$new_keys_values[$i]] = $v;
					unset($values[$old_key]);
				}
				$i++;
			}
			unset($new_keys_values, $values_keys, $i);
		}
	}
	
	// single quotes not allowed in queries!
	if (strpos($query, '\'') !== false)
	{
		list($err_msg, $log_msg) = zc_get_err_log_msg('zc_error_96', 'Hacking attempt...', 'zc_error_97', 'Illegal character (\') used in query...');
		zc_db_error_backtrace($err_msg, $log_msg, __FILE__, __LINE__);
	}
	
	static $allowed_comments_from = array('~/\*!40001 SQL_NO_CACHE \*/~');
	static $allowed_comments_to = array('');
	
	$clean = trim(strtolower(preg_replace($allowed_comments_from, $allowed_comments_to, $query)));
	
	$hacking_attempt = false;
	// comments not allowed in queries...
	if (strpos($clean, '/*') > 2 || strpos($clean, '--') !== false || strpos($clean, ';') !== false)
		$hacking_attempt = true;
	// union not allowed in queries...
	elseif (strpos($clean, 'union') !== false && preg_match('~(^|[^a-z])union($|[^[a-z])~s', $clean) != 0)
		$hacking_attempt = true;
	// sleep not allowed in queries...
	elseif (strpos($clean, 'sleep') !== false && preg_match('~(^|[^a-z])sleep($|[^[_a-z])~s', $clean) != 0)
		$hacking_attempt = true;
	// benchmark not allowed in queries...
	elseif (strpos($clean, 'benchmark') !== false && preg_match('~(^|[^a-z])benchmark($|[^[a-z])~s', $clean) != 0)
		$hacking_attempt = true;
	// sub-selects are not allowed...
	elseif (preg_match('~\([^)]*?select~s', $clean) != 0)
		$hacking_attempt = true;
		
	if ($hacking_attempt)
	{
		list($err_msg, $log_msg) = zc_get_err_log_msg('zc_error_96', 'Hacking attempt...');
		zc_db_error_backtrace($err_msg, $log_msg . '<br />' . $query, __FILE__, __LINE__);
	}
		
	$connection = $connection == null ? $zc['db_connection'] : $connection;
	$zc['for_db_callback'] = array($values, $connection);
	// replaces stuff like  {int:column}  and  {string:column}  with sanitized values
	$query = preg_replace_callback('~{([a-z_]+)(?::([a-zA-Z0-9_-]+))?}~', 'zc_db_replacement__callback', $query);
	$zc['for_db_callback'] = array();
		
	// we got this far....
	$result = @mysql_query($query, $connection);
	
	if (!$result)
		zc_db_error($query, '', '', $connection);
	
	$zc['db_query_count']++;
	
	return $result;
}

function zc_db_error_backtrace($err_msg, $log_msg = '', $error_type = false, $file = null, $line = null)
{
	if (empty($log_msg))
		$log_msg = $err_msg;
		
	$log_msg = zcFormatTxtString($log_msg);

	if (function_exists('debug_backtrace'))
	{
		foreach (debug_backtrace() as $step)
		{
			if (strpos($step['function'], 'query') === false && !in_array(substr($step['function'], 0, 6), array('zc_db_', 'preg_r')))
			{
				$log_msg .= '<br />Function: ' . $step['function'];
				break;
			}

			if (isset($step['line']))
			{
				$file = $step['file'];
				$line = $step['line'];
			}
		}
	}

	// just return the file and line number....
	if ($error_type == 'return')
		return array($file, $line);

	if (function_exists('zc_log_error'))
		zc_log_error($log_msg, 'critical', $file, $line);
		
	$err_msg = zcFormatTxtString($err_msg);

	if (function_exists('zc_fatal_error'))
	{
		zc_fatal_error($err_msg, false);
		exit;
	}
	elseif ($error_type)
		trigger_error($err_msg . ($line !== null ? '<i>(' . basename($file) . ' - ' . $line . ')</i>' : ''), $error_type);
	else
		trigger_error($err_msg . ($line !== null ? '<i>(' . basename($file) . ' - ' . $line . ')</i>' : ''));
}

function zc_db_error($query, $file = null, $line = null, $connection = null)
{
	global $zc, $zcFunc, $txt, $context;
		
	list($file, $line) = zc_db_error_backtrace('', '', 'return', __FILE__, __LINE__);

	$connection = $connection == null ? $zc['db_connection'] : $connection;

	// This is the error message...
	$query_error = mysql_error($connection);
	$query_errno = mysql_errno($connection);

	// Error numbers:
	//    1016: Can't open file '....MYI'
	//    1030: Got error ??? from table handler.
	//    1034: Incorrect key file for table.
	//    1035: Old key file for table.
	//    1205: Lock wait timeout exceeded.
	//    1213: Deadlock found.
	//    2006: Server has gone away.
	//    2013: Lost connection to server during query.

	// Log the error.
	if ($query_errno != 1213 && $query_errno != 1205 && function_exists('zc_log_error'))
	{
		if (function_exists('zcLoadLanguage'))
			zcLoadLanguage('Errors', $zc['language']);
			
		$log_msg = array('%1$s%2$s', (isset($txt['zc_error_82']) ? $txt['zc_error_82'] : 'Database Error'), ': ' . $query_error . '<br /><br />' . $query);
		zc_log_error($log_msg, 'database', $file, $line);
	}
		
	// .......... auto-fixing here eventually.......
	
	// notification to administrator(s) also...
	
	// we haven't loaded anything yet...
	if (empty($context) || empty($txt))
		die($query_error);
		
	if ($context['user']['is_admin'])
		$error_msg = nl2br($query_error) . '<br />' . $txt['b685'] . ': ' . $file . '<br />' . $txt['b686'] . ': ' . $line;
	else
		$error_msg = 'zc_error_83';
		
	zc_fatal_error($error_msg);
}

function zc_db_insert($method = 'replace', $table, $columns, $data, $values = null, $connection = null)
{
	global $zc, $zcFunc;
	
	// no data to insert...
	if (empty($data))
		return;
		
	$connection = $connection !== null ? $connection : $zc['db_connection'];
	$values = $values !== null ? $values : array();
	
	// if $data is not multi-dimensional, let's make it so...
	if (!is_array($data[array_rand($data)]))
		$data = array($data);
	
	$convert_to_real_type = array('check' => 'int', 'text' => 'string', 'file' => 'string');
	$insertRows = array();
	$just_columns = array();
	foreach ($data as $k => $array)
	{
		$insertRows[$k] = '(';
		$b = false;
		foreach ($array as $c => $v)
		{
			$insertRows[$k] .= ($b ? ', ' : '') . '{' . (isset($convert_to_real_type[$columns[$c]]) ? $convert_to_real_type[$columns[$c]] : $columns[$c]) . ':' . $c . $k . '}';
			$values[$c . $k] = $v;
			$b = true;
			
			// this is to make sure the columns are in the same order as the data...
			if (!isset($just_columns[$c]))
				$just_columns[$c] = true;
		}
		$insertRows[$k] .= ')';
	}

	$insertion_method = $method == 'replace' ? 'REPLACE' : ($method == 'ignore' ? 'INSERT IGNORE' : 'INSERT');
	
	$result = $zcFunc['db_query']("
		" . $insertion_method . " INTO " . $table . "(" . implode(', ', array_keys($just_columns)) . ")
		VALUES
			" . implode(',
			', $insertRows), __FILE__, __LINE__, $values, null, $connection);
			
	return $result;
}

function zc_db_update($table, $columns, $data, $where = null, $limit = 1, $values = null, $connection = null)
{
	global $zc, $zcFunc;
	
	// no data to update...
	if (empty($data))
		return;
		
	$connection = $connection !== null ? $connection : $zc['db_connection'];
	$values = $values !== null ? $values : array();
	
	if (!empty($limit))
		$values['limit'] = $limit;
		
	$convert_to_real_type = array('check' => 'int', 'text' => 'string', 'file' => 'string');
	
	// create the SET portion of the query...
	$updateColumns = array();
	foreach ($data as $c => $v)
	{
		// if $v is an array... we are trying to do something more complex
		if (is_array($v))
		{
			$equals_what = '';
				
			// key 2 should be the column to which we are adding/subtracting/equating  (default to itself)
			$equals_what .= sprintf('%1$s ', isset($v[2]) ? $v[2] : $c);
			
			// are we using a value?
			if (isset($v[1]))
			{
				// the operator should correspond to key 0 in the array...
				if (isset($v[0]) && in_array($v[0], array('+', '-')))
					$equals_what .= sprintf('%1$s ', $v[0]);
				
				// the value we are adding/subtracting/equating with the column
				$values[$c] = $v[1];
				
				$equals_what .= '{' . (isset($convert_to_real_type[$columns[$c]]) ? $convert_to_real_type[$columns[$c]] : $columns[$c]) . ':' . $c . '}';
			}
			
			$updateColumns[] = $c . ' = ' . $equals_what;
		}
		// simply equal to new value ($v)
		else
		{
			$values[$c] = $v;
			$updateColumns[] = $c . ' = {' . (isset($convert_to_real_type[$columns[$c]]) ? $convert_to_real_type[$columns[$c]] : $columns[$c]) . ':' . $c . '}';
		}
	}
	
	// create the WHERE portion of the query...
	if (!empty($where))
	{
		$conditions = array();
		$track = array();
		foreach ($where as $c => $v)
		{
			// assume the operator is =
			$operator = '=';
			
			if (!is_array($v))
			{
				$values[$c . '_c'] = $v;
				$conditions[] = $c . ' ' . $operator . ' {' . $columns[$c] . ':' . $c . '_c}';
				continue;
			}
			
			if (!isset($track[$c]))
				$track[$c] = 0;
				
			// if $v is not multi-dimensional, make it so...
			if (!is_array($v[0]))
				$v = array($v);
			
			foreach ($v as $vv)
				if (isset($vv[1]))
				{
					$values[$c . $track[$c] . '_c'] = $vv[1];
					
					// the operator should correspond to key 0 in the array...
					if (isset($vv[0]) && $vv[0] == 'IN')
					{
						if (isset($columns[$c]))
							$col_type = in_array($columns[$c], array('int', 'check')) ? 'array_int' : 'array_string';
						else
							$col_type = 'array_string';
					
						// IN is a special case...
						$conditions[] = $c . ' IN ({' . $col_type . ':' . $c . $track[$c] . '_c})';
						$track[$c]++;
						continue;
					}
					// is it one of the other valid operators?
					elseif (isset($vv[0]) && in_array($vv[0], array('<', '>', '<=', '>=', '!=')))
						$operator = $vv[0];
					
					$conditions[] = $c . ' ' . $operator . ' {' . $columns[$c] . ':' . $c . $track[$c] . '_c}';
					$track[$c]++;
				}
		}
	}
	
	$result = $zcFunc['db_query']("
		UPDATE " . $table . "
		SET " . implode(',
			', $updateColumns) . (!empty($conditions) ? "
		WHERE " . implode('
			AND ', $conditions) : '') . (!empty($limit) ? "
		LIMIT {int:limit}" : ''), __FILE__, __LINE__, $values, null, $connection);
			
	return $result;
}

?>