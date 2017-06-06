<?php

if (!defined('zc'))
	die('Hacking attempt...');
/*
function zc_check_session($method, $is_fatal = true)
{
	// no prefetching of pages that need session checking...
	if (isset($_SERVER['HTTP_X_MOZ']) && $_SERVER['HTTP_X_MOZ'] == 'prefetch')
	{
		ob_end_clean();
		header('HTTP/1.1 403 Forbidden');
		die;
	}
	
	global $zc;
	
	// check user agent...
	if (!isset($_SESSION['USER_AGENT']) || $_SESSION['USER_AGENT'] != $_SERVER['HTTP_USER_AGENT'])
		$error = 'zc_error_100';
	
	if ($method == 'post')
	{
	}
	elseif ($method == 'get')
	{
	}
	else
		trigger_error('zc_check_session(): Invalid method \'' . $method . '\'', E_USER_WARNING);
		
	// session verified successfully!  weeee!
	if (!isset($error))
		return true;
	// not so much...
	elseif (isset($error) && $is_fatal)
		zc_fatal_error($error);
	// session verification failed, but not fatal... we return false...
	else
		return false;
		
	// just in case...
	trigger_error('Hacking attempt...', E_USER_ERROR);
}*/
	
function zc_check_submit_once($action, $is_fatal = true)
{
	global $context;

	if (!isset($_SESSION['zc_forms']))
		$_SESSION['zc_forms'] = '';
		
	$zc_forms = !empty($_SESSION['zc_forms']) ? explode(',', $_SESSION['zc_forms']) : array();

	// Register a form number and store it in the session stack. (use this on the page that has the form.)
	if ($action == 'register')
	{
		$context['zc']['form_seqnum'] = 0;
		while (empty($context['zc']['form_seqnum']) || in_array($context['zc']['form_seqnum'], $zc_forms))
			$context['zc']['form_seqnum'] = mt_rand(1, 16000000);
	}
	// Check whether the submitted number can be found in the session.
	elseif ($action == 'check')
	{
		if (!isset($_REQUEST['seqnum']))
			return true;
		elseif (!in_array($_REQUEST['seqnum'], $zc_forms))
		{
			$_SESSION['zc_forms'] .= ',' . (int) $_REQUEST['seqnum'];
			return true;
		}
		elseif ($is_fatal)
			zc_fatal_error('zc_error_16');
		else
			return false;
	}
	// free the form sequence number from the session data
	elseif ($action == 'free' && isset($_REQUEST['seqnum']) && in_array($_REQUEST['seqnum'], $zc_forms))
		$_SESSION['zc_forms'] = implode(',', array_diff($zc_forms, array($_REQUEST['seqnum'])));
	elseif ($action != 'free')
		trigger_error('zc_check_submit_once(): Invalid action \'' . $action . '\'', E_USER_WARNING);
}

// take away permissions.... since they're banned
function zcBanPermissions()
{
	global $context;

	// Somehow they got here, at least take away all permissions...
	if (isset($_SESSION['zc_ban']['cannot_access']))
		$context['user']['zc_permissions'] = array();
	// They can only browse the community...
	elseif (isset($_SESSION['zc_ban']['cannot_post']))
		$context['user']['zc_permissions'] = array('view_zcommunity');
}

function zc_check_permissions($permissions)
{
	global $context, $zcFunc;
	
	// make sure it's an array...
	if (!is_array($permissions))
		$permissions = !empty($permissions) ? explode(',', $permissions) : array();
		
	$num_permissions = count($permissions);
	
	// if we're checking more than one permission, what we return will be an array
	if ($num_permissions > 1)
		$return = array();
	
	// anyone can do nothing ;)
	if (empty($permissions))
		return true;
	
	// admins can do anything
	if ($context['user']['is_admin'])
		if ($num_permissions <= 1)
			return true;
		else
		{
			foreach ($permissions as $permission)
				$return[$permission] = true;
				
			return $return;
		}
		
	// ok... let's check the permissions then...
	$request = $zcFunc['db_query']("
		SELECT add_deny, permission
		FROM {db_prefix}permissions
		WHERE permission IN ({array_string:permissions})
			AND group_id IN ({array_int:groups})
			AND add_deny = 1
		LIMIT {int:limit}", __FILE__, __LINE__,
		array(
			'limit' => count($permissions),
			'permissions' => $permissions,
			'groups' => $context['user']['member_groups']
		)
	);
	if ($zcFunc['db_num_rows']($request) > 0)
	{
		if ($num_permissions <= 1)
			$return = true;
		else
			while ($row = $zcFunc['db_fetch_assoc']($request))
				$return[$row['permission']] = !empty($row['add_deny']) || !empty($return[$row['permission']]);
	}
	// couldn't find a row for the permission and we're looking for just a single permission... set as false
	elseif ($num_permissions <= 1)
		$return = false;
	
	$zcFunc['db_free_result']($request);
	
	if ($num_permissions > 1)
		foreach ($permissions as $permission)
			// for the permissions that we didn't find a row in the table... set as false
			if (!isset($return[$permission]))
				$return[$permission] = false;
	
	return $return;
}

function zcAntiBot($action)
{
	global $context, $txt, $zc, $zcFunc;
	
	$required = $context['user']['is_guest'];
	
	if (!$required)
		return;
	
	if ($action == 'check')
	{
		// have to spend time filling out the form.... almost always a bot if it submits this quickly...
		if (!isset($_SESSION['zc_form_loaded_time']) || (array_sum(explode(' ', microtime())) - $_SESSION['zc_form_loaded_time']) <= 1)
			$too_fast = true;
			
		// check log_bad_bots table...
		$request = $zcFunc['db_query']("
			SELECT timestamp
			FROM {db_prefix}log_bad_bots
			WHERE ip = {string:user_ip}
			LIMIT 1", __FILE__, __LINE__,
			array(
				'user_ip' => $_SERVER['REMOTE_ADDR']
			)
		);
		while ($row = $zcFunc['db_fetch_assoc']($request))
		{
			// they were previously logged as a bad bot...
			$context['zc']['errors'][] = 'zc_error_77';
			
			// log the attempt into the bad_bots table
			zc_log_bad_bot();
			
			break;
		}
		$zcFunc['db_free_result']($request);
			
		// if any of these fields were filled in with anything... they are definitely a bot...
		if (empty($context['zc']['errors']) && count($context['zc']['errors']) == 0 && isset($_SESSION['zc_anti_bot_field_names']))
		{
			// check each of the anti bot fields to see if they have anything in them...
			foreach ($_SESSION['zc_anti_bot_field_names'] as $field_name)
			{
				if (!empty($_POST[$field_name]))
				{
					$context['zc']['errors'][] = 'zc_error_77';
					
					// log them into the bad_bots table
					zc_log_bad_bot();
					break;
				}
			}
		}
		
		// they submitted too fast...
		if (!empty($too_fast) && empty($context['zc']['errors']) && count($context['zc']['errors']) == 0)
			$context['zc']['errors'][] = 'zc_error_80';
			
		// recaptcha... rly?
		if (empty($context['zc']['errors']) && count($context['zc']['errors']) == 0 && !empty($zc['settings']['enable_recaptcha']) && isset($_POST['recaptcha_challenge_field']) && isset($_POST['recaptcha_response_field']))
		{
			require_once($zc['main_dir'] . '/lib/recaptcha/recaptchalib.php');
			$resp = recaptcha_check_answer($zc['settings']['recaptcha_privkey'], $_SERVER['REMOTE_ADDR'], $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);
			
			// did they respond correctly?
			if (!$resp->is_valid)
				$context['zc']['errors']['recaptcha'] = 'zc_error_70';
		}
		
		// if not in the blogging community, do a fatal error
		if (empty($context['zc']['in_zcommunity']))
		{
			if (!empty($context['zc']['errors']))
				foreach ($context['zc']['errors'] as $zc_error)
				{
					zc_fatal_error($zc_error);
					break;
				}
		}
	}
	elseif ($action == 'prepare')
	{
		// these are special fields that we will use to try to catch bots...
		$for_da_bots = array('age', 'address', 'phone', 'color', 'body', 'message', 'email', 'first_name', 'last_name', 'name', 'street', 'country', 'city', 'province', 'question', 'answer', 'height', 'language', 'site', 'url', 'link', 'icq', 'aim', 'yahoo', 'msn', 'yim', 'signature', 'gender', 'location', 'birthdate', 'personal_text', 'subject');
		$_SESSION['zc_anti_bot_field_names'] = array();
		$used = array();
		for ($n = 1; $n <= rand(1,5); $n++)
		{
			$rand = rand(0,count($for_da_bots) - 1);
			for ($i = 0; $i <= count($for_da_bots); $i++)
			{
				if (!in_array($rand, $used))
				{
					$used[] = $rand;
					$_SESSION['zc_anti_bot_field_names'][] = $for_da_bots[$rand];
					break 1;
				}
				else
					$rand = rand(0,count($for_da_bots) - 1);
			}
		}
	
		if (!empty($zc['settings']['enable_recaptcha']))
			$context['zc']['recaptcha'] = '
						<script type="text/javascript" src="http://api.recaptcha.net/challenge?k=' . $zc['settings']['recaptcha_pubkey'] . '"></script>
						<noscript>
						   <iframe src="http://api.recaptcha.net/noscript?k=' . $zc['settings']['recaptcha_pubkey'] . '" height="300" width="500" frameborder="0"></iframe><br />
						   <textarea name="recaptcha_challenge_field" rows="3" cols="40"></textarea>
						   <input type="hidden" name="recaptcha_response_field" value="manual_challenge" />
						</noscript>';
	
		// we will use the difference between this time and the time at submission of the form to see if they are a bot
		$_SESSION['zc_form_loaded_time'] = array_sum(explode(' ', microtime()));
	}
}

function zc_log_bad_bot()
{
	global $zcFunc;
	
	$query_string = empty($_SERVER['QUERY_STRING']) ? '' : addslashes($zcFunc['htmlspecialchars']('?' . preg_replace(array('~;sesc=[^&;]+~', '~' . session_name() . '=' . session_id() . '[&;]~'), array(';sesc', ''), $_SERVER['QUERY_STRING'])));
	
	// log them in the log_bad_bots table...
	$zcFunc['db_insert']('insert', '{db_prefix}log_bad_bots', array('ip' => 'string', 'timestamp' => 'int', 'query_string' => 'string'), array('ip' => $_SERVER['REMOTE_ADDR'], 'timestamp' => time(), 'query_string' => substr($query_string, 1, 65534)));
}

?>