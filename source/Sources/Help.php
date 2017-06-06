<?php

if (!defined('zc'))
	die('Hacking attempt...');

function zc_help_popup()
{
	global $txt, $context;
	
	// load help lang file
	zcLoadLanguage('Help');
		
	zcLoadTemplate('index');
	zcLoadTemplate('Generic-index');
	$context['page_title'] = $txt['b3041'];
	$context['blog_control_panel'] = false;
	$context['zc']['layerless'] = true;
	$context['zc']['sub_template'] = 'simple_popup';
	$context['zc']['sub_sub_template'] = 'show_message';
	$context['zc']['template_layers'] = array('html' => array());
	$context['zc']['no_change_template_layers'] = true;
	
	// what text to display?
	if (!empty($txt[$_REQUEST['txt']]))
		$context['zc']['text'] = '<div class="blogHelpText">' . $txt[$_REQUEST['txt']] . '</div>';
	else
		$context['zc']['text'] = $_REQUEST['txt'];
}
/*
function zcTutorials()
{
	global $zc;
	
	$context['zc']['tutorials'] = array();
	
	// directory must exist...
	$tutorials_dir = $zc['main_dir'] . '/Tutorials';
	if (is_dir($tutorials_dir))
	{
		if ($dir = @opendir($tutorials_dir))
		{
			while (($file = readdir($dir)) !== false)
			{
				// skip folders ... skip files that don't end with .php... and skip the index.php
				if (is_dir($tutorials_dir . '/' . $file) || (substr($file, -4) !== '.php') || $file === 'index.php')
					continue;
				
				// get the tutorial file...
				require_once($tutorials_dir . '/' . $file);
			}
			closedir($dir);
		}
	}
}*/

?>