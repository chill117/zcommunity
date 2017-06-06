<?php

if (!defined('zc'))
	die('Hacking attempt...');

function zcWriteChangeToFile($file, $edits)
{
	if (!is_readable($file))
		return false;
		
	if (($lines = file($file)) !== false)
		foreach ($lines as $line_number => $line)
		{
			if (!empty($edits['str_replace']))
				foreach ($edits['str_replace'] as $arg)
					if (strpos($line, $arg[0]) !== false)
					{
						$lines[$line_number] = str_replace($arg[0], $arg[1], $line);
						$changes_made = true;
					}
		}
		
	if (!empty($changes_made))
	{
		// make back-up
		copy($file, $file . '~');
		unlink($file);
		$handle = fopen($file, 'x');
		fwrite($handle, implode('', $lines));
		fclose($handle);
	}
	else
		return false;
}

?>