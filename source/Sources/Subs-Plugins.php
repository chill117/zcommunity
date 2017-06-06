<?php

if (!defined('zc'))
	die('Hacking attempt...');

function zcEnableDisablePlugin($memID)
{
	global $zcFunc, $context;
	
	checkSession('get');
	
	if (!zc_check_permissions('access_plugins_tab'))
		zc_fatal_error('zc_error_52');
		
	$plugin_id = !empty($_REQUEST['id']) ? urldecode((string) $_REQUEST['id']) : '';
	
	// nothin' to do...
	if (empty($plugin_id) || ($plugin_id != 'all' && !isset($context['zc']['plugins'][$plugin_id])))
		zc_redirect_exit(zcRequestVarsToString('sa', '?') . ';sa=plugins');
		
	// 1 is enabled... 0 is disabled...
	$enabled = !empty($_REQUEST['enable_disable']) ? 1 : 0;
	$updates = array();
	
	// do this for ALL plug-ins...
	if ($plugin_id == 'all')
	{
		if (!empty($context['zc']['plugins']))
			foreach ($context['zc']['plugins'] as $id => $dummy)
				$updates['zcp_' . $id . '_enabled'] = $enabled;
	}
	// just for one plug-in...
	else
		$updates['zcp_' . $plugin_id . '_enabled'] = $enabled;
	
	zcUpdateGlobalSettings($updates);
		
	zc_redirect_exit(zcRequestVarsToString('sa') . ';sa=plugins');
}

function zc_delete_plugins()
{
	global $zc, $zcFunc, $context;

	checkSession('post');
	
	// make sure they are allowed to access the plugins tab
	if (!zc_check_permissions('access_plugins_tab'))
		zc_fatal_error('zc_error_52');
		
	$plugins_to_delete = array();
	if (!empty($_POST['items']))
		foreach ($_POST['items'] as $id)
			if (isset($context['zc']['plugins'][(string) $id]))
				$plugins_to_delete[] = (string) $id;
				
	$delete_settings = array();
	if (!empty($plugins_to_delete))
		foreach ($plugins_to_delete as $plugin_id)
		{
			$delete_settings[] = 'zcp_' . $plugin_id . '_enabled';
			$delete_settings[] = $plugin_id . '_p_db_updates';
			
			if (file_exists($zc['plugins_dir'] . '/' . $plugin_id . '.php'))
				if (!@unlink($zc['plugins_dir'] . '/' . $plugin_id . '.php'))
				{
					@chmod($zc['plugins_dir'], 0777);
					@chmod($zc['plugins_dir'] . '/' . $plugin_id . '.php', 0777);
					@unlink($zc['plugins_dir'] . '/' . $plugin_id . '.php');
				}
		}
	@chmod($zc['plugins_dir'], 0755);
		
	if (!empty($delete_settings))
		$zcFunc['db_query']("
			DELETE FROM {db_prefix}global_settings
			WHERE variable IN ({array_string:settings})
			LIMIT {int:limit}", __FILE__, __LINE__,
			array(
				'settings' => $delete_settings,
				'limit' => count($delete_settings)
			)
		);
		
	zc_redirect_exit(zcRequestVarsToString('sa') . ';sa=plugins');
}

?>