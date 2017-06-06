<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
/*
	load_zc_plugins()
		- reads all the plug-in files in zCommunity's Plugins directory
		- prepares $context['zc']['plugins'] array
		- prepares any other $context arrays that plugins may use
*/

function load_zc_plugins()
{
	global $context, $settings, $options, $scripturl, $txt;
	global $zc, $blog, $blog_info, $article, $comment, $draft;
	
	/* 
		functions in each slot's function array are executed wherever those plug-in slots are in zCommunity's code...
		SLOT NUMBER => array(
			functions => array('function1', 'function2'),
		),
	*/
	
	$edit_files = array();
	$context['zc']['plugin_slots'] = array();
	$context['zc']['plugins'] = array();
	
	// plugins directory is not readable or doesn't exist!
	if (!file_exists($zc['plugins_dir']) || !is_readable($zc['plugins_dir']))
		return false;
	
	if (is_dir($zc['plugins_dir']))
	{
		if ($dir = @opendir($zc['plugins_dir']))
		{
			while (($file = readdir($dir)) !== false)
			{
				// skip folders ... skip files that don't end with .php... and skip the index.php
				if (is_dir($zc['plugins_dir'] . '/' . $file) || (substr($file, -4) !== '.php') || $file === 'index.php')
					continue;
					
				$full_path = $zc['plugins_dir'] . '/' . $file;
				
				// get the file...
				require_once($full_path);
				
				if (!empty($context['zc']['plugin']))
				{
					$plugin = $context['zc']['plugin'];
					
					// plugin ID must be unique!
					if (!isset($context['zc']['plugins'][$plugin['id']]))
						$context['zc']['plugins'][$plugin['id']] = $plugin;
					// we'll have to make it unique then...
					else
					{
						$old_plugin_id = $plugin['id'];
						$plugin['id'] = zc_make_unique_array_key($plugin['id'], array($context['zc']['plugins']));
						$context['zc']['plugins'][$plugin['id']] = $plugin;
						
						if (!isset($edit_files[$full_path]))
							$edit_files[$full_path] = array();
							
						if (!isset($edit_files[$full_path]['str_replace']))
							$edit_files[$full_path]['str_replace'] = array();
							
						$edit_files[$full_path]['str_replace'][] = array('\''. $old_plugin_id .'\'', '\''. $plugin['id'] .'\'');
					}
					$context['zc']['plugins'][$plugin['id']]['file'] = $full_path;
					unset($context['zc']['plugin'], $plugin);
				}
			}
			closedir($dir);
		}
	}
	
	// stuff to add to $context['zc'] as arrays... key => array
	$key_array_pairs = array('menu', 'zcActions', 'page_relative_links', 'preferences');
	
	// only need these in the blog control panel...
	if (isset($_REQUEST['zc']) && $_REQUEST['zc'] == 'bcp')
		$key_array_pairs += array('bcp_subMenu', 'bcp_subActions');
	
	// stuff to add to $context['zc'] as arrays... value
	$simple_arrays = array('load_js_files', 'load_css_stylesheets', 'extra_small_bottom_links');
				
	$context['zc']['admin_plugin_settings'] = array();
	if (!empty($context['zc']['plugins']))
		foreach ($context['zc']['plugins'] as $plugin_id => $array)
		{
			// plugins must be enabled...
			if (empty($zc['settings']['zcp_' . $plugin_id . '_enabled']))
				continue;
		
			if (!empty($array['plugin_slots']))
				foreach ($array['plugin_slots'] as $slot => $functions)
					if (!empty($functions) && is_array($functions))
					{
						if (!isset($context['zc']['plugin_slots'][$slot]))
							$context['zc']['plugin_slots'][$slot] = array();
							
						if (!isset($context['zc']['plugin_slots'][$slot]['functions']))
							$context['zc']['plugin_slots'][$slot]['functions'] = array();
							
						$context['zc']['plugin_slots'][$slot]['functions'] = array_merge($context['zc']['plugin_slots'][$slot]['functions'], $functions);
					}
					
			foreach ($key_array_pairs as $key)
				if (!empty($array[$key]))
				{
					if (!isset($context['zc'][$key]))
						$context['zc'][$key] = array();
						
					foreach ($array[$key] as $k => $v)
						$context['zc'][$key][$k] = $v;
				}
					
			foreach ($simple_arrays as $key)
				if (!empty($array[$key]))
				{
					if (!isset($context['zc'][$key]))
						$context['zc'][$key] = array();
						
					foreach ($array[$key] as $v)
						$context['zc'][$key][] = $v;
				}
		
			if (!empty($array['settings']))
				foreach ($array['settings'] as $setting => $array2)
					// if $setting is already taken... we'll need to make a unique key...
					if (!isset($context['zc']['admin_plugin_settings'][$setting]) && !isset($temp[$setting]))
						$temp[$setting] = '';
					else
					{
						$old_setting_key = $setting;
						$setting = zc_make_unique_array_key($setting, array($temp, $context['zc']['admin_plugin_settings']));
						$context['zc']['plugins'][$plugin_id]['settings'][$setting] = $array2;
						$temp[$setting] = '';
						
						if (!isset($edit_files[$array['file']]))
							$edit_files[$array['file']] = array();
							
						if (!isset($edit_files[$array['file']]['str_replace']))
							$edit_files[$array['file']]['str_replace'] = array();
							
						$edit_files[$array['file']]['str_replace'][] = array('\''. $old_setting_key .'\'', '\''. $setting .'\'');
					}
				
			if (!empty($array['admin_settings']))
				foreach ($array['admin_settings'] as $setting => $array2)
					// make sure $setting is not already taken
					if (!isset($context['zc']['admin_plugin_settings'][$setting]) && !isset($temp[$setting]))
						$context['zc']['admin_plugin_settings'][$setting] = $array2;
					// we have to make a unique key...
					else
					{
						$old_setting_key = $setting;
						$setting = zc_make_unique_array_key($setting, array($context['zc']['admin_plugin_settings']));
						$context['zc']['admin_plugin_settings'][$setting] = $array2;
						$context['zc']['plugins'][$plugin_id]['admin_settings'][$setting] = $array2;
						
						if (!isset($edit_files[$array['file']]))
							$edit_files[$array['file']] = array();
							
						if (!isset($edit_files[$array['file']]['str_replace']))
							$edit_files[$array['file']]['str_replace'] = array();
							
						$edit_files[$array['file']]['str_replace'][] = array('\''. $old_setting_key .'\'', '\''. $setting .'\'');
					}
		}
		
	// now for admin plugin settings...
	if (!empty($context['zc']['admin_plugin_settings']))
		foreach($context['zc']['admin_plugin_settings'] as $setting => $array)
		{
			if (!isset($zc['settings'][$setting]))
				$zc['settings'][$setting] = $array['value'];
				
			if ($array['type'] == 'text')
				$zc['settings'][$setting] = $zcFunc['un_htmlspecialchars']($zc['settings'][$setting]);
				
			if (!empty($array['needs_explode']) && !is_array($zc['settings'][$setting]))
				$zc['settings'][$setting] = !empty($zc['settings'][$setting]) ? explode(',', $zc['settings'][$setting]) : array();
		}
		
	if (!empty($edit_files) && file_exists($zc['sources_dir'] . '/Subs-Files.php'))
	{
		// we'll need this...
		require_once($zc['sources_dir'] . '/Subs-Files.php');
		
		if (function_exists('zcWriteChangeToFile'))
			foreach ($edit_files as $file => $edits)
				if (!empty($edits))
					zcWriteChangeToFile($file, $edits);
	}
}

/*
	Plug-in Slots:
		1 ... 
		  ... 
		  
		2 ... Sources/Post.php
		  ... after the initial form processing (if no errors occurred)
		  
		3 ... Sources/Post.php
		  ... after the form is registered and the anti bot features are prepared
		  
		4 ... Sources/Blog.php
		  ... In the load articles function
		  
		5 ... Sources/Who.php
		  ... Near the end of the function that determines what page a user is viewing in zCommunity
		  
		6 ... Sources/Post.php
		  ... perform final processing for things that are not articles, comments, or polls
		  
		7 ... Sources/Post.php
		  ... prepare preview for things that are not articles, comments, or polls
		  
		8 ... index.php
		  ... before zcActions in zcStart()
		  
		9 ... Blog.php
		  ... near the end of zcBlog()
		  
		10 ... Community.php
		   ... near the end of zcCommunityPage()
		  
		11 ... Sources/Post.php
		   ... near the beginning of zcPost(), before the form data is loaded
		   ... use  $context['zc']['form_data_loaded'] = true  to prevent the loading of other form data
		  
		12 ... Sources/Post.php
		   ... before the processing is finalized (before anything is actually posted)
		   ... use  $context['zc']['processing_complete'] = true  to prevent anything else from being posted
*/

function zc_plugin_slot($slot_num, $args = null)
{
	global $context;
	
	if (!empty($args))
		$num_args = count($args);
		
	if (!empty($context['zc']['plugin_slots'][$slot_num]['functions']))
		foreach ($context['zc']['plugin_slots'][$slot_num]['functions'] as $function)
			if (function_exists($function))
				if (empty($num_args))
					$function();
				elseif ($num_args == 1)
					$function($args[0]);
				elseif ($num_args == 2)
					$function($args[0], $args[1]);
				elseif ($num_args == 3)
					$function($args[0], $args[1], $args[2]);
				elseif ($num_args == 4)
					$function($args[0], $args[1], $args[2], $args[3]);
				elseif ($num_args == 5)
					$function($args[0], $args[1], $args[2], $args[3], $args[4]);
				elseif ($num_args == 6)
					$function($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
}

function zcPluginDatabaseChanges()
{
	global $context, $zc;
	
	// no changes to verify/process... or we cannot find Database.php
	if (empty($context['zc']['plugins']) || !file_exists($zc['sources_dir'] . '/Database.php'))
		return;
		
	$updates = array();
	foreach ($context['zc']['plugins'] as $plugin_id => $array)
	{
		// already did db updates for this plugin...
		if (!empty($zc['settings'][$plugin_id . '_p_db_updates']))
			continue;
			
		// need this...
		require_once($zc['sources_dir'] . '/Subs-Database.php');
	
		if (!empty($array['new_db_tables']))
			foreach ($array['new_db_tables'] as $new_table)
				zcCreateDatabaseTable($new_table);
			
		if (!empty($array['alter_db_tables']))
			foreach ($array['alter_db_tables'] as $alter_table)
				zcAlterDatabaseTable($alter_table);
			
		$zc['settings'][$plugin_id . '_p_db_updates'] = 1;
		$updates[$plugin_id . '_p_db_updates'] = 1;
	}
	
	// update the global_settings table...
	if (!empty($updates))
		zcUpdateGlobalSettings($updates);
}

?>