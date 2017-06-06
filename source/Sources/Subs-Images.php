<?php

if (!defined('zc'))
	die('Hacking attempt...');

function zc_process_image_upload()
{
	global $context;
	
	list($info, $file) = $context['zc']['for_more_processing'];
	
	$sizes = @getimagesize($file['tmp_name']);
	
	$max_attempts = 12;
	$i = 0;
	while (file_exists($info['dir'] . '/' . $file['basename']))
	{
		$i++;
		
		if (empty($basename_no_extension))
			$basename_no_extension = substr($file['basename'], 0, (-1) * (strlen($file['file_extension']) + 1));
	
		$rand = mt_rand();
		if (!file_exists($info['dir'] . '/' . $basename_no_extension . $rand . '.' . $file['file_extension']))
			$file['basename'] = $basename_no_extension . $rand . '.' . $file['file_extension'];
		// says unknown error... but really it was that all the file names we tried were taken...
		elseif ($i >= $max_attempts)
			return 'zc_error_72';
	}

	// new location for file...
	$destination = $info['dir'] . '/' . $file['basename'];
	
	// not a valid image file...
	if (!$sizes)
		return array('zc_error_69', $info['label']);
	// check to see if it's too large
	elseif ((!empty($info['max_width_image']) && $sizes[0] > $info['max_width_image']) || (!empty($info['max_height_image']) && $sizes[1] > $info['max_height_image']))
	{
		// resize the image...
		/*if (!empty($info['resize_image_if_too_large']))
		{
			if (!zc_resize_image_file($file['tmp_name'], $destination, (!empty($info['max_width_image']) ? $info['max_width_image'] : null), (!empty($info['max_height_image']) ? $info['max_height_image'] : null)))
				return array('zc_error_85', $info['label']);
		}
		// reject the image stating that its dimensions are too large...
		else*/
			return array('zc_error_85', $info['label']);
	}
	
	// attempt to save the file to a new location... if we didn't already do something else with it...
	if (file_exists($file['tmp_name']) && !@move_uploaded_file($file['tmp_name'], $destination))
		return 'zc_error_72';
	
	// if we successfully uploaded the file, attempt to chmod it
	@chmod($destination, 0644);
	
	return array('processed', $file['basename']);
}
/*
function zc_resize_image_file($src_url, $destination, $max_width = null, $max_height = null)
{
	// resizing is not necessary...
	if (empty($max_width) && empty($max_height))
		return true;

	if (($sizes = @getimagesize($src_url)) != false)
		list($src_width, $src_height, $src_img_type) = array($sizes[0], $sizes[1], $sizes[2]);
	// not a valid image file...
	else
		return false;
		
	$supported_image_types = array('jpeg', 'bmp', 'png', 'gif');
	$src_img_extension = image_type_to_extension($src_img_type, false);
	if (!in_array($src_img_extension, $supported_image_types))
		return false;
		
	$imagecreatefrom = 'imagecreatefrom' . $src_img_extension;
	if (function_exists($imagecreatefrom))
		$src = $imagecreatefrom($src_url);
	else
		return false;
		
	list($dest_width, $dest_height) = zcResizeImage($src, $max_width, $max_height);
	
	// don't need to resize...
	if (($dest_width <= $max_width) && ($dest_height <= $max_height))
		return;
		
	$gd = zc_test_gd();
	// gd is not installed...
	if (empty($gd))
		return false;
		
	$gd2 = zc_test_gd2();
	// gd2 is not installed...
	if (empty($gd2))
		return false;
		
	$dest_img = imagecreatetruecolor($dest_width, $dest_height);

	imagealphablending($dest_img, false);
	if (function_exists('imagesavealpha'))
		imagesavealpha($dest_img, true);

	imagecopyresampled($dest_img, $src, 0, 0, 0, 0, $dest_width, $dest_height, $src_width, $src_height);
	imagepng($dest_img, $destination);
	
	// don't need these anymore
	imagedestroy($src);
	if ($dest_img != $src)
		imagedestroy($dest_img);
}

function zc_test_gd()
{
	global $zc;
	if (!isset($zc['gd']))
		$zc['gd'] = get_extension_funcs('gd');
	return $zc['gd'];
}

function zc_test_gd2()
{
	global $zc;
	if (!isset($zc['gd2']))
		$zc['gd2'] = in_array('imagecreatetruecolor', zc_test_gd()) && function_exists('imagecreatetruecolor');
	return $zc['gd2'];
}

if (!function_exists('image_type_to_extension'))
{
	function image_type_to_extension($type, $include_dot = true)
	{
		$e = array(1 => 'gif', 'jpeg', 'png', 'swf', 'psd', 'bmp', 'tiff', 'tiff', 'jpc', 'jp2', 'jpf', 'jb2', 'swc', 'aiff', 'wbmp', 'xbm');
	
		$type = (int) $type;
		if (!isset($e[$type]))
			return false;
		
		return ($include_dot ? '.' : '') . $e[$type];
	}
}*/

?>