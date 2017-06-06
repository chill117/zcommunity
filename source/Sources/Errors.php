<?php

if (!defined('zc'))
	die('Hacking attempt...');

/*
	zc_log_error($error_msg, $error_type = 'general', $file = null, $line = null)
		- logs an error in the log_errors table
*/

function zc_log_error($error_msg, $error_type = 'general', $file = null, $line = null)
{
	global $zc;
	
	// don't log the error if error logging is disabled... or if we just don't want to log this type of error...
	if (empty($zc['settings']['do_error_logging']) || (isset($zc['settings']['error_settings_array']) && !in_array($error_type, $zc['settings']['error_settings_array'])))
		return;

	global $context, $scripturl;
	
	// attempt to load Errors lang file in the site's default language...
	zcLoadLanguage('Errors', $zc['language']);
		
	$error_msg = zcFormatTxtString($error_msg);
	
	$file = $file == null ? $file = '' : str_replace('\\', '/', $file);
	$line = $line == null ? 0 : (int) $line;
	
	$valid_error_types = array('general', 'critical', 'database', 'template', 'language', 'undefined_index');
	
	// make sure the error type is valid... default to general
	if (empty($error_type) || !in_array($error_type, $valid_error_types))
		$error_type = 'general';

	// we need the query string... not the whole URL
	$query_string = empty($_SERVER['QUERY_STRING']) ? (empty($_SERVER['REQUEST_URL']) ? '' : str_replace($scripturl, '', $_SERVER['REQUEST_URL'])) : $_SERVER['QUERY_STRING'];
	
	// don't log the session hash in the url twice
	$query_string = htmlspecialchars('?' . preg_replace(array('~;sesc=[^&;]+~', '~' . session_name() . '=' . session_id() . '[&;]~'), array(';sesc', ''), $query_string));
	
	$error_info = array('error_type' => $error_type, 'member_id' => $context['user']['id'], 'ip' => $context['user']['ip'], 'session' => $context['session_id'], 'timestamp' => time(), 'message' => $error_msg, 'file' => $file, 'line' => $line, 'url' => $query_string);
	// so that we don't insert the same exact error over and over... and over...
	if (empty($zc['last_error']) || $zc['last_error'] != $error_info)
	{
		global $zcFunc;
		$columns = array('error_type' => 'string', 'member_id' => 'int', 'ip' => 'string', 'session' => 'string', 'timestamp' => 'int', 'message' => 'string', 'file' => 'string', 'line' => 'int', 'url' => 'string');
		$zcFunc['db_insert']('insert', '{db_prefix}log_errors', $columns, $error_info);
		$zc['last_error'] = $error_info;
	}
	
	return $error_msg;
}

// http://www.php.net/set_error_handler
function zc_error_handler($errno, $errstr, $errfile, $errline, $errcontext)
{
}

?>