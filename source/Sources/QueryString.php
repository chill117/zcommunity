<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
function zc_clean_request()
{
	global $zc, $blog, $article, $comment, $poll, $draft;
	
	// stand-alone zcommunity?
	if (!$zc['with_software']['version'])
	{
		global $scripturl;
		
		// What function to use to reverse magic quotes - if sybase is on we assume that the database sensibly has the right unescape function!
		$reverse_magic_quotes = @ini_get('magic_quotes_sybase') || strtolower(@ini_get('magic_quotes_sybase')) == 'on' ? 'unescape__recursive' : 'stripslashes__recursive';
		
		// save memory... we don't use these...
		unset($GLOBALS['HTTP_POST_VARS'], $GLOBALS['HTTP_POST_VARS']);
		unset($GLOBALS['HTTP_POST_FILES'], $GLOBALS['HTTP_POST_FILES']);
		
		// these keys should never be set...
		if (isset($_REQUEST['GLOBALS']) || isset($_COOKIE['GLOBALS']))
			die('Invalid request variable(s).');
			
		// same for numeric keys...
		foreach (array_merge(array_keys($_POST), array_keys($_GET), array_keys($_FILES)) as $key)
			if (is_numeric($key))
				die('Invalid request variable(s).');
				
		// Numeric keys in cookies are less of a problem. Just unset those.
		foreach ($_COOKIE as $key => $value)
			if (is_numeric($key))
				unset($_COOKIE[$key]);
				
		// get the correct query string....
		if (!isset($_SERVER['QUERY_STRING']))
			$_SERVER['QUERY_STRING'] = getenv('QUERY_STRING');
			
		// sticking a URL at the end of the query string is a no no
		if (strpos($_SERVER['QUERY_STRING'], 'http') === 0)
		{
			header('HTTP/1.1 400 Bad Request');
			die;
		}
		
		// do we need to parse the ; out of the query string?
		if ((strpos(@ini_get('arg_separator.input'), ';') === false || @version_compare(PHP_VERSION, '4.2.0') == -1) && !empty($_SERVER['QUERY_STRING']))
		{
			// let's start fresh...
			$_GET = array();
	
			// Was this redirected?  If so, get the REDIRECT_QUERY_STRING.
			$_SERVER['QUERY_STRING'] = substr($_SERVER['QUERY_STRING'], 0, 5) == 'url=/' ? $_SERVER['REDIRECT_QUERY_STRING'] : $_SERVER['QUERY_STRING'];
	
			// Replace ';' with '&' and '&something&' with '&something=&'.  (this is done for compatibility...)
			parse_str(preg_replace('/&(\w+)(?=&|$)/', '&$1=', strtr($_SERVER['QUERY_STRING'], array(';?' => '&', ';' => '&'))), $_GET);
	
			// Magic quotes still applies with parse_str - so clean it up.
			if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc() != 0)
				$_GET = $reverse_magic_quotes($_GET);
		}
		elseif (strpos(@ini_get('arg_separator.input'), ';') !== false)
		{
			$_GET = urldecode__recursive($_GET);
	
			if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc() != 0)
				$_GET = $reverse_magic_quotes($_GET);
	
			// Search engines will send action=profile%3Bu=1, which confuses PHP.
			foreach ($_GET as $k => $v)
			{
				if (is_string($v) && strpos($k, ';') !== false)
				{
					$temp = explode(';', $v);
					$_GET[$k] = $temp[0];
	
					for ($i = 1, $n = count($temp); $i < $n; $i++)
					{
						@list ($key, $val) = @explode('=', $temp[$i], 2);
						if (!isset($_GET[$key]))
							$_GET[$key] = $val;
					}
				}
	
				// This helps a lot with integration!
				if (strpos($k, '?') === 0)
				{
					$_GET[substr($k, 1)] = $v;
					unset($_GET[$k]);
				}
			}
		}

		// There's no query string, but there is a URL... try to get the data from there.
		if (!empty($_SERVER['REQUEST_URI']))
		{
			// Remove the .html, assuming there is one.
			if (substr($_SERVER['REQUEST_URI'], strrpos($_SERVER['REQUEST_URI'], '.'), 4) == '.htm')
				$request = substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '.'));
			else
				$request = $_SERVER['REQUEST_URI'];
	
			// Replace 'index.php/a,b,c/d/e,f' with 'a=b,c&d=&e=f' and parse it into $_GET.
			parse_str(substr(preg_replace('/&(\w+)(?=&|$)/', '&$1=', strtr(preg_replace('~/([^,/]+),~', '/$1=', substr($request, strpos($request, basename($scripturl)) + strlen(basename($scripturl)))), '/', '&')), 1), $temp);
			if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc() != 0)
				$temp = $reverse_magic_quotes($temp);
			$_GET += $temp;
		}
	
		// no magic quotes!
		if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc() != 0)
		{
			$_ENV = $reverse_magic_quotes($_ENV);
			$_POST = $reverse_magic_quotes($_POST);
			$_COOKIE = $reverse_magic_quotes($_COOKIE);
			foreach ($_FILES as $k => $dummy)
				if (isset($_FILES[$k]['name']))
					$_FILES[$k]['name'] = $reverse_magic_quotes($_FILES[$k]['name']);
		}
	
		// convert html special chars '"<> into their entity forms for all GET data
		$_GET = htmlspecialchars__recursive($_GET);
	
		// the request global should contain POST and GET data only...
		$_REQUEST = $_POST + $_GET;
	}

	// Make sure $blog and $article are numbers.
	if (isset($_REQUEST['blog']))
	{
		// Make sure that its a string and not something else like an array
		$_REQUEST['blog'] = (string) $_REQUEST['blog'];

		// Sometimes $_REQUEST['start'] is stored in blog as blog=1.5 for example...
		if (strpos($_REQUEST['blog'], '.') !== false)
			list ($_REQUEST['blog'], $_REQUEST['start']) = explode('.', $_REQUEST['blog']);
			
		// Now make absolutely sure it's a number.
		$blog = (int) $_REQUEST['blog'];

		$_GET['blog'] = $blog;
	}
	// Well, $blog is going to be a number no matter what.
	else
		$blog = 0;

	// we've got the article request
	if (isset($_REQUEST['article']))
	{
		// Make sure that its a string and not something else like an array
		$_REQUEST['article'] = (string) $_REQUEST['article'];
		
		// Sometimes $_REQUEST['start'] is stored in article as article=2.15 for example...
		if (strpos($_REQUEST['article'], '.') !== false)
			list ($_REQUEST['article'], $_REQUEST['start']) = explode('.', $_REQUEST['article']);

		$article = (int) $_REQUEST['article'];
		
		$_GET['article'] = $article;
	}
	else
		$article = 0;
	
	// these must be integers
	$comment = isset($_REQUEST['comment']) ? (int) $_REQUEST['comment'] : 0;
	$poll = isset($_REQUEST['poll']) ? (int) $_REQUEST['poll'] : 0;
	$draft = isset($_REQUEST['draft']) ? (int) $_REQUEST['draft'] : 0;
	
	// has to be a string!
	if (isset($_REQUEST['zc']))
		$_REQUEST['zc'] = (string) $_REQUEST['zc'];
	
	if (!$zc['with_software']['version'])
	{
		// Make sure we have a valid REMOTE_ADDR.
		if (!isset($_SERVER['REMOTE_ADDR']))
		{
			$_SERVER['REMOTE_ADDR'] = '';
			// A new magic variable to indicate we think this is command line.
			$_SERVER['is_cli'] = true;
		}
		elseif (preg_match('~^((([1]?\d)?\d|2[0-4]\d|25[0-5])\.){3}(([1]?\d)?\d|2[0-4]\d|25[0-5])$~', $_SERVER['REMOTE_ADDR']) === 0)
			$_SERVER['REMOTE_ADDR'] = 'unknown';
	
		// Try to calculate their most likely IP for those people behind proxies.
		$_SERVER['BAN_CHECK_IP'] = $_SERVER['REMOTE_ADDR'];
	}
}

// stand-alone zcommunity?
if (!$zc['with_software']['version'])
{
	// Adds slashes to the array/variable.  Uses two underscores to guard against overloading.
	function addslashes__recursive($var, $level = 0)
	{
		if (!is_array($var))
			return addslashes($var);
	
		$new_var = array();
	
		// Add slashes to every element of the array, even the indexes!
		foreach ($var as $k => $v)
			$new_var[addslashes($k)] = $level > 25 ? null : addslashes__recursive($v, $level + 1);
	
		return $new_var;
	}
	
	// Adds html entities to the array/variable.  Uses two underscores to guard against overloading.
	function htmlspecialchars__recursive($var, $level = 0)
	{
		global $zcFunc;
	
		if (!is_array($var))
			return isset($zcFunc) ? $zcFunc['htmlspecialchars']($var, ENT_QUOTES) : htmlspecialchars($var, ENT_QUOTES);
	
		// Add the htmlspecialchars to every element of the array.
		foreach ($var as $k => $v)
			$var[$k] = $level > 25 ? null : htmlspecialchars__recursive($v, $level + 1);
	
		return $var;
	}

	// Removes url stuff from the array/variable.  Uses two underscores to guard against overloading.
	function urldecode__recursive($var, $level = 0)
	{
		if (!is_array($var))
			return urldecode($var);
	
		$new_var = array();
	
		// urldecode every element of the array.
		foreach ($var as $k => $v)
			$new_var[urldecode($k)] = $level > 25 ? null : urldecode__recursive($v, $level + 1);
	
		return $new_var;
	}

	// Strips the slashes off any array or variable.  Two underscores for the normal reason.
	function stripslashes__recursive($var, $level = 0)
	{
		if (!is_array($var))
			return stripslashes($var);
	
		$new_var = array();
	
		// Strip slashes from every element of the array.
		foreach ($var as $k => $v)
			$new_var[stripslashes($k)] = $level > 25 ? null : stripslashes__recursive($v, $level + 1);
	
		return $new_var;
	}

	// Trim a string including the HTML space, character 160.
	function htmltrim__recursive($var, $level = 0)
	{
		global $zcFunc;
	
		// Remove spaces (32), tabs (9), returns (13, 10, and 11), nulls (0), and hard spaces. (160)
		if (!is_array($var))
			return isset($zcFunc) ? $zcFunc['htmltrim']($var) : trim($var, " \t\n\r\x0B\0\xA0");
	
		$new_var = array();
	
		// Go through all the elements of the array and remove the whitespace.
		foreach ($var as $k => $v)
			$new_var[$k] = $level > 25 ? null : htmltrim__recursive($v, $level + 1);
	
		return $new_var;
	}

	// Adds slashes to the array/variable.  Uses two underscores to guard against overloading.
	function escapestring__recursive($var)
	{
		global $zcFunc;
	
		if (!is_array($var))
			return $zcFunc['db_escape_string']($var);
	
		$new_var = array();
	
		// Add slashes to every element of the array, even the indexes!
		foreach ($var as $k => $v)
			$new_var[$zcFunc['db_escape_string']($k)] = escapestring__recursive($v);
	
		return $new_var;
	}
	
	// Unescapes any array or variable.  Two underscores for the normal reason.
	function unescapestring__recursive($var)
	{
		global $zcFunc;
	
		if (!is_array($var))
			return $zcFunc['db_unescape_string']($var);
	
		$new_var = array();
	
		// Strip the slashes from every element of the array.
		foreach ($var as $k => $v)
			$new_var[$zcFunc['db_unescape_string']($k)] = unescapestring__recursive($v);
	
		return $new_var;
	}
}

?>