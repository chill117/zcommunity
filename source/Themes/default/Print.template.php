<?php

if (!defined('zc'))
	die('Hacking attempt...');

function zc_template_printPage()
{
	global $context, $settings, $options, $txt;

	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"', $context['right_to_left'] ? ' dir="rtl"' : '', '>
	<head>
		', empty($context['robot_no_index']) ? '' : '<meta name="robots" content="noindex" />', '
		<meta http-equiv="Content-Type" content="text/html; charset=', $context['character_set'], '" />
		<title>', $txt['b3046'], ' - ', $context['zc']['article']['subject'], '</title>
		<style type="text/css">
			body
			{
				color: black;
				background-color: white;
			}
			body, td, .normaltext
			{
				font-family: Verdana, arial, helvetica, serif;
				font-size: small;
			}
			*, a:link, a:visited, a:hover, a:active
			{
				color: black !important;
			}
			table
			{
				empty-cells: show;
			}
			.code
			{
				font-size: x-small;
				font-family: monospace;
				border: 1px solid black;
				margin: 1px;
				padding: 1px;
			}
			.quote
			{
				font-size: x-small;
				border: 1px solid black;
				margin: 1px;
				padding: 1px;
			}
			.smalltext, .quoteheader, .codeheader
			{
				font-size: x-small;
			}
			.largetext
			{
				font-size: large;
			}
			hr
			{
				height: 1px;
				border: 0;
				color: black;
				background-color: black;
			}
		</style>';

	/* Internet Explorer 4/5 and Opera 6 just don't do font sizes properly. (they are big...)
		Thus, in Internet Explorer 4, 5, and Opera 6 this will show fonts one size smaller than usual.
		Note that this is affected by whether IE 6 is in standards compliance mode.. if not, it will also be big.
		Standards compliance mode happens when you use xhtml... */
	if ($context['browser']['needs_size_fix'])
		echo '
		<link rel="stylesheet" type="text/css" href="', $settings['theme_url'], '/fonts-compat.css" />';

	echo '
	</head>
	<body>
		<div class="needsPadding">
			<h1 class="largetext">', $context['zc']['site_name'], '</h1>
			<h2 class="normaltext">', $txt['b51'], ' => ', $context['zc']['page_header'], ' => ', $txt['b242'], ': ', $context['zc']['article']['member'], ' ', $txt['b3034'], ' ', $context['zc']['article']['time'] . '</h2>
';

	// the article...
	echo '
			<hr />
			<b>', $context['zc']['article']['subject'], '</b><br /><br />
			', $context['zc']['article']['body'], '<br /><br />
			', $txt['b40'], ': <b>', $context['zc']['article']['member'], '</b> ', $txt['b3034'], ' <b>', $context['zc']['article']['time'], '</b>
			<hr /><br />
			<b>', $txt['b15a'], ':</b><br />';

	// comments....
	if (!empty($context['zc']['comments']))
		foreach ($context['zc']['comments'] as $comment)
			echo '
			<hr />
			', $comment['body'], '<br /><br />
			', $txt['b40'], ': <b>', $comment['member'], '</b> ', $txt['b3034'], ' <b>', $comment['time'], '</b>
			<hr />';
					
	echo '<br /><br />
			<div align="center" class="smalltext">', zCommunityCopyRight(), '</div>
		</div>
	</body>
</html>';
}

?>