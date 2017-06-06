<?php

if (!defined('zc'))
	die('Hacking attempt...');

/*
	I would eventually like to make a moderation area where users can go to see moderation 
	requests that require their attention...
*/
/*
function zc_process_adv_moderation()
{
	global $context, $txt;
	global $zcFunc, $zc;
	
	checkSession('get');
	
	// have to select an action to do something....
	if (empty($_POST['action']))
		zc_redirect_exit((!empty($_REQUEST['blog']) || !empty($_REQUEST['articles']) ? '' : 'zc') . zcRequestVarsToString('zc', ';'));
		
	// actions array....
	$actions = array(
		'blogs' => array(
			// action => array(file, function, must_return_true)
			'delete' => array('Subs-Blogs.php', 'zcDeleteBlog', $context['user']['is_admin']),
			'lock' => array('Subs-Blogs.php', 'LockBlogs', $context['user']['is_admin']),
			'unlock' => array('Subs-Blogs.php', 'UnLockBlogs', $context['user']['is_admin']),
		),
		'articles' => array(
			// action => array(file, function, must_return_true)
			'delete' => array('Subs-Articles.php', 'zcDeleteArticle', true),
			'lock_unlock' => array('Subs-Articles.php', 'zcLockUnlockArticle', true),
		),
		'comments' => array(
			// action => array(file, function, must_return_true)
			'delete' => array('Subs-Comments.php', 'zcDeleteComment', true),
		),
	);
	
	$context['zc']['using_adv_moderation'] = true;
	
	if (!empty($actions))
		foreach ($actions as $type => $specific_actions)
			if (!empty($_POST[$type]))
				if (isset($specific_actions[$_POST['action']]))
				{
					if ($specific_actions[$_POST['action']][2] === true && file_exists($zc['sources_dir']. '/' . $specific_actions[$_POST['action']][0]))
					{
						require_once($zc['sources_dir']. '/' . $specific_actions[$_POST['action']][0]);
						if (function_exists($actions['blogs'][$_POST['action']][1]))
							$result_of_action = $specific_actions[$_POST['action']][1]();
						// function doesn't exist...
						else
							$context['zc']['errors'][] = 'zc_error_5';
					}
					// couldn't find file?
					elseif (!file_exists($zc['sources_dir']. '/' . $specific_actions[$_POST['action']][0]))
						$context['zc']['errors'][] = 'zc_error_5';
					// simply not allowed to perform the action?
					else
						$context['zc']['errors'][] = 'zc_error_52';
				}
	
	// showing errors?
	if (!empty($context['zc']['errors']))
	{
		if (!empty($_REQUEST['blog']))
			unset($_REQUEST['article']);
			
		$_REQUEST['zc'] = '';
		return zC_START();
	}
	// make sure we send them back wherever they came from...
	else
		zc_redirect_exit((!empty($_REQUEST['blog']) || !empty($_REQUEST['articles']) ? '' : 'zc') . zcRequestVarsToString('zc', ';'));
}*/

?>