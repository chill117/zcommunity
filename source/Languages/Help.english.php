<?php

if (!defined('zc'))
	die('Hacking attempt...');

$txt['zc_help_1'] = '<b>Single Blog Mode</b><br />Through the Blog link in your forum\'s navigation menu, users can view your blog.  Use the Blog Control Panel to manage this blog.<br /><br /><b>Single Blog (Hidden) Mode</b><br />Same as regular Single Blog mode, except your blog is only visible to administrators.<br /><br /><b>Blogging Community Mode</b><br />All users with the write_blog permission can access their Blog Control Panel.  Don\'t forget to look at the various blog permissions available.  You can set these permissions at Admin > Members > Permissions.';

$txt['zc_help_2'] = 'This is the blog that will be displayed on the blog page.<br />Applies to <b>Single Blog modes only</b>.';

$txt['zc_help_3'] = 'When "Comments require approval" is checked, all comments made to articles in your blog will have to be approved by a moderator or an admistrator before they become visible to ordinary readers.';

if (!empty($context['can_moderate_blog']) || !empty($context['can_approve_comments_in_any_b']))
	$txt['zc_help_3'] .= '<br /><br />You are allowed to approve comments in this blog.  To approve a comment, simply click the "Click here to approve comment" link - which is located somewhere on the comment itself.';
else
	$txt['zc_help_3'] .= '<br /><br />You are not allowed to approve comments in this blog.';

$txt['zc_help_4'] = 'When "Articles require approval" is checked, all articles posted in your blog will have to be approved by a moderator or an admistrator before they become visible to ordinary readers.';

if (!empty($context['can_moderate_blog']) || !empty($context['can_approve_articles_in_any_b']))
	$txt['zc_help_4'] .= '<br /><br />You are allowed to approve articles in this blog.  To approve an article, simply click the "Click here to approve article" link - which is located somewhere on the article itself.';
else
	$txt['zc_help_4'] .= '<br /><br />You are not allowed to approve articles in this blog.';
	
$txt['zc_help_6'] = 'IP addresses are shown to administrators and moderators to facilitate moderation and to make it easier to track people up to no good. Remember that IP addresses may not always be identifying, and most people\'s IP addresses change periodically.<br /><br />Members are also allowed to see their own IPs.';

$txt['zc_help_7'] = '<div style="font-weight:bold; font-size:12px;">' . $txt['b79'] . '</div><br />Tags are used to help categorize articles in your blogs.  Each blog has its own unique tags.  New tags are made whenever you input tags that have not been used before in the Tags field when posting or modifying an article.';

$txt['zc_help_8'] = 'What you put in the meta keywords field is what will appear in the meta keyword tag on every page of this blog except individual article pages.  There are a couple simple rules you should follow when figuring out what keywords you should use:<br /><ul style="margin-left:30px;"><li>Order matters!  Keywords at the start will be given more weight by search engines.</li><li>Do not repeat the same words more than a few times, even if you are using them in different phrases.</li><li>You don\'t have to separate keywords with commas, but it doesn\'t hurt you if you do.  Search engines interpret commas as spaces.</li></ul>';

$txt['zc_help_9'] = 'What you put in the meta keywords field is what will appear in the meta keyword tag on the community page.  There are a couple simple rules you should follow when figuring out what keywords you should use:<br /><ul style="margin-left:30px;"><li>Order matters!  Keywords at the start will be given more weight by search engines.</li><li>Do not repeat the same words more than a few times, even if you are using them in different phrases.</li><li>You don\'t have to separate keywords with commas, but it doesn\'t hurt you if you do.  Search engines interpret commas as spaces.</li></ul>';

//$txt['help_txt_use_zc_as_home_page'] = 'This will make <b>' . $scripturl . '</b> lead to the main page of your zCommunity.  Additionally, <b>' . $scripturl . '?action=forum</b> will lead to your forum\'s board index.';

//$txt['help_txt_cascading_comments'] = 'When cascading comments is enabled, users can comment on other comments.  Comments to other comments appear after that comment (in chronological order).  This means that all the comments in an article will not necessarily be in chronological order from top to bottom.  When cascading comments is disabled, comments will appear in normal order and the option to comment on another comment will no longer be available.';*/

$txt['zc_help_10'] = 'This permission should be taken seriously.  You do not want just anyone to have the ability to place Raw HTML on your web pages.  Potential serious security issues could arise if you give this permission to the wrong users.';
$txt['zc_help_11'] = 'This permission should be taken very very seriously.  Only give this permission to users you would trust as an administrator on your site.';

?>