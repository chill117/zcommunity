<?php

if (!defined('zc'))
	die('Hacking attempt...');

function zc_send_pm($to, $subject, $body, $save_to_outbox = false, $from = null)
{
	global $zc;
	
	if (in_array($zc['with_software']['version'], $zc['smf_versions']))
	{
		require_once($zc['with_software']['sources_dir'] . '/Subs-Post.php');
		sendpm($to, $subject, $body, $save_to_outbox, $from);
	}
	else
	{
	}
}

?>