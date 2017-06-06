<?php

if (!defined('zc'))
	die('Hacking attempt...');

/*
	Example info.php file:
	
<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
$context['zc']['theme'] = array(
	'id' => 'doz_lite',
	'name' => 'Degrees of Zero - Light',
	'version' => '1.0.1',
	'preview_images' => array($zc['themes_dir'] . '/doz_lite/preview001.png'),
	'settings' => array(),
	'admin_settings' => array(),
);

?>

*/

function load_zc_themes()
{
	global $context;
	global $zcFunc, $zc;
	
	$edit_files = array();
	$context['zc']['themes'] = array();
	
	if (is_dir($zc['themes_dir']))
	{
		if ($dir = @opendir($zc['themes_dir']))
		{
			while (($folder = readdir($dir)) !== false)
			{
				// skip non-folders
				if (!is_dir($zc['themes_dir'] . '/' . $folder))
					continue;
				
				// we want the theme's info file...
				if (file_exists($zc['themes_dir'] . '/' . $folder . '/info.php'))
				{
					$full_path = $zc['themes_dir'] . '/' . $folder . '/info.php';
					
					// get the file...
					require_once($full_path);
				
					if (!empty($context['zc']['theme']))
					{
						$theme = $context['zc']['theme'];
						// theme ID must be unique!
						if (!isset($context['zc']['themes'][$theme['id']]))
							$context['zc']['themes'][$theme['id']] = $theme;
						// we'll have to make it unique then...
						else
						{
							$old_theme_id = $theme['id'];
							$theme['id'] = zc_make_unique_array_key($theme['id'], array($context['zc']['themes']));
							$context['zc']['themes'][$theme['id']] = $theme;
						
							if (!isset($edit_files[$full_path]))
								$edit_files[$full_path] = array();
								
							if (!isset($edit_files[$full_path]['str_replace']))
								$edit_files[$full_path]['str_replace'] = array();
								
							$edit_files[$full_path]['str_replace'][] = array('\''. $old_theme_id .'\'', '\''. $theme['id'] .'\'');
						}
						$context['zc']['themes'][$theme['id']]['file'] = $full_path;
						unset($context['zc']['theme'], $theme);
					}
				}
			}
			closedir($dir);
		}
	}
	
	$context['zc']['admin_theme_settings'] = array();
	if (!empty($context['zc']['themes']))
		foreach ($context['zc']['themes'] as $theme_id => $array)
		{
			if (!empty($array['settings']))
				foreach ($array['settings'] as $setting => $array2)
					// if $setting is already taken... we'll need to make a unique key...
					if (!isset($context['zc']['admin_theme_settings'][$setting]) && !isset($temp[$setting]))
						$temp[$setting] = '';
					else
					{
						$old_setting_key = $setting;
						$setting = zc_make_unique_array_key($setting, array($temp, $context['zc']['admin_theme_settings']));
						$context['zc']['themes'][$theme_id]['settings'][$setting] = $array2;
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
					if (!isset($context['zc']['admin_theme_settings'][$setting]) && !isset($temp[$setting]))
						$context['zc']['admin_theme_settings'][$setting] = $array2;
					// we have to make a unique key...
					else
					{
						$old_setting_key = $setting;
						$setting = zc_make_unique_array_key($setting, array($temp, $context['zc']['admin_theme_settings']));
						$context['zc']['admin_theme_settings'][$setting] = $array2;
						$context['zc']['themes'][$theme_id]['admin_settings'][$setting] = $array2;
						
						if (!isset($edit_files[$array['file']]))
							$edit_files[$array['file']] = array();
							
						if (!isset($edit_files[$array['file']]['str_replace']))
							$edit_files[$array['file']]['str_replace'] = array();
							
						$edit_files[$array['file']]['str_replace'][] = array('\''. $old_setting_key .'\'', '\''. $setting .'\'');
					}
		}
		
	// adds the rest of the admin_theme_settings
	zc_prepare_admin_theme_settings_array();
		
	// now for admin theme settings...
	if (!empty($context['zc']['admin_theme_settings']))
		foreach($context['zc']['admin_theme_settings'] as $k => $array)
		{
			// skip base admin theme settings, because we already processed them...
			if (isset($context['zc']['base_admin_theme_settings'][$k]))
				continue;
		
			if (!isset($zc['settings'][$k]))
				$zc['settings'][$k] = $array['value'];
				
			if ($array['type'] == 'text')
				$zc['settings'][$k] = $zcFunc['un_htmlspecialchars']($zc['settings'][$k]);
				
			if (!empty($array['needs_explode']) && !is_array($zc['settings'][$k]))
				$zc['settings'][$k] = !empty($zc['settings'][$k]) ? explode(',', $zc['settings'][$k]) : array();
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


?>