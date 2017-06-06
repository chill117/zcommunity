<?php

if (!defined('zc'))
	die('Hacking attempt...');

if (!isset($txt['lang_locale']))
{
	// Locale (strftime, pspell_new) and spelling. (pspell_new, can be left as '' normally.)
	// For more information see:
	//   - http://www.php.net/function.pspell-new
	//   - http://www.php.net/function.setlocale
	// Again, SPELLING SHOULD BE '' 99% OF THE TIME!!  Please read this!
	$txt['lang_locale'] = 'en_US';
	$txt['lang_dictionary'] = 'en';
	$txt['lang_spelling'] = 'american';
	$txt['lang_character_set'] = 'ISO-8859-1';
	$txt['lang_rtl'] = false;
}

$txt['b0'] = 'zCommunity';
$txt['b1'] = 'blogs';
$txt['b1a'] = 'Blogs';

$txt['b170'] = 'article';

$txt['b338'] = 'Community News';

$txt['b524'] = 'choose a theme';
$txt['b551'] = 'Control Panel';

$txt['b685'] = 'File';
$txt['b686'] = 'Line';

$txt['b3000'] = 'Home';
$txt['b3001'] = 'Go to Blog';
$txt['b3002'] = 'Blog Control Panel';
$txt['b3003'] = 'Blog';
$txt['b3003a'] = 'blog';

$txt['b3016'] = 'Logout';
$txt['b3017'] = 'Register';
$txt['b3018'] = 'Login';
$txt['b3019'] = 'Profile';
$txt['b3020'] = 'My Messages';
$txt['b3021'] = 'Admin';

$txt['b3022'] = 'Back';
$txt['b3023'] = 'Modify';
$txt['b3024'] = 'Members';
$txt['b3024a'] = 'Member';
$txt['b3025'] = 'Name';
$txt['b3026'] = 'Title';

?>