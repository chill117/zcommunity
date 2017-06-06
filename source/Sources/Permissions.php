<?php

if (!defined('zc'))
	die('Hacking attempt...');

function zcPreparePermissionsArray()
{
	global $context, $txt;
	$context['zc']['permissions'] = array(
		'view_zcommunity' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b147',
			'header_above' => 'b245',
		),
		'report_to_moderator' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b230',
		),
		/*'send_articles' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b156',
		),*/
		'mark_notify' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b155',
		),
		'save_drafts' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b162',
		),
		'moderate_own_blogs' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b153',
			'header_above' => 'b246',
		),
		'moderate_any_blog' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b154',
		),/*
		'lock_blogs' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b331', 'b330'),
		),*/
		'access_global_settings_tab' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b286', 'b218'),
		),
		'access_permissions_tab' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b286', 'b235'),
		),
		'access_blog_index_tab' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b286', 'b213'),
		),
		'access_plugins_tab' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b286', 'b217'),
		),
		'access_themes_tab' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b286', 'b527'),
		),
		'access_other_tab' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b286', 'b5'),
		),
		'access_any_blog_control_panel' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b287',
		),
		'change_blog_ownership' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b559',
		),
		'view_any_blog' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b677',
			'header_above' => 'b1a',
		),
		'create_blog' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b115',
		),
		'multiple_blogs' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b116',
		),
		'delete_own_blogs' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b117',
		),
		'restrict_access_blogs' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b118',
		),
		'set_posting_restrictions' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b120',
		),
		'use_blog_themes' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b119',
		),
		'use_raw_html_in_custom_windows' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b678', 'b675'),
			'helptext' => 'zc_help_10',
		),
		'use_php_in_custom_windows' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b678', 'b676'),
			'helptext' => 'zc_help_11',
		),
		'post_community_news' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b331', 'b337'),
			'header_above' => 'b338',
		),
		'edit_community_news' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b331', 'b339'),
		),
		'delete_community_news' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b331', 'b341'),
		),
		'lock_community_news' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b331', 'b340'),
		),
		'comment_on_news' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b331', 'b345'),
			'header_above' => array('b430', 'b15a', 'b338'),
		),
		'edit_own_news_comments' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b383', 'b126', 'b362'),
		),
		'edit_any_news_comments' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b384', 'b126', 'b362'),
		),
		'delete_own_news_comments' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b383', 'b127', 'b362'),
		),
		'delete_any_news_comments' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b384', 'b127', 'b362'),
		),
		'approve_articles_in_any_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b189',
			'header_above' => 'b66a',
		),/*
		'make_articles_sticky' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b564',
		),*/
		'edit_own_articles_in_own_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b122', 'b126', 'b129'),
		),
		'edit_own_articles_in_any_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b123', 'b126', 'b129'),
		),
		'edit_any_articles_in_own_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b124', 'b126', 'b129'),
		),
		'edit_any_articles_in_any_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b125', 'b126', 'b129'),
		),
		'delete_own_articles_in_own_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b122', 'b127', 'b129'),
		),
		'delete_own_articles_in_any_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b123', 'b127', 'b129'),
		),
		'delete_any_articles_in_own_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b124', 'b127', 'b129'),
		),
		'delete_any_articles_in_any_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b125', 'b127', 'b129'),
		),
		'lock_own_articles_in_own_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b122', 'b128', 'b129'),
		),
		'lock_own_articles_in_any_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b123', 'b128', 'b129'),
		),
		'lock_any_articles_in_own_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b124', 'b128', 'b129'),
		),
		'lock_any_articles_in_any_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b125', 'b128', 'b129'),
		),
		'post_comments_in_any_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b188',
			'header_above' => 'b15a',
		),
		'approve_comments_in_any_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b149',
		),
		'edit_own_comments_in_own_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b122', 'b126', 'b130'),
		),
		'edit_own_comments_in_any_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b123', 'b126', 'b130'),
		),
		'edit_any_comments_in_own_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b124', 'b126', 'b130'),
		),
		'edit_any_comments_in_any_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b125', 'b126', 'b130'),
		),
		'delete_own_comments_in_own_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b122', 'b127', 'b130'),
		),
		'delete_own_comments_in_any_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b123', 'b127', 'b130'),
		),
		'delete_any_comments_in_own_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b124', 'b127', 'b130'),
		),
		'delete_any_comments_in_any_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b125', 'b127', 'b130'),
		),
		'post_polls' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b186',
			'header_above' => 'b247',
		),
		'vote_in_polls' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b187',
		),
		'edit_own_polls_in_own_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b122', 'b126', 'b172a'),
		),
		'edit_own_polls_in_any_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b123', 'b126', 'b172a'),
		),
		'edit_any_polls_in_own_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b124', 'b126', 'b172a'),
		),
		'edit_any_polls_in_any_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b125', 'b126', 'b172a'),
		),
		'delete_own_polls_in_own_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b122', 'b127', 'b172a'),
		),
		'delete_own_polls_in_any_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b123', 'b127', 'b172a'),
		),
		'delete_any_polls_in_own_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b124', 'b127', 'b172a'),
		),
		'delete_any_polls_in_any_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b125', 'b127', 'b172a'),
		),
		'lock_own_polls_in_own_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b122', 'b128', 'b172a'),
		),
		'lock_own_polls_in_any_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b123', 'b128', 'b172a'),
		),
		'lock_any_polls_in_own_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b124', 'b128', 'b172a'),
		),
		'lock_any_polls_in_any_b' => array(
			'type' => 'check',
			'value' => 0,
			'label' => array('b125', 'b128', 'b172a'),
		),
	);
	
	// permissions that guests CANNOT have...
	$context['zc']['non_guest_permissions'] = array(
		'create_blog',
		'multiple_blogs',
		'delete_own_blogs',
		'restrict_access_blogs',
		'use_blog_themes',
		'set_posting_restrictions',
		'mark_notify',
		'send_articles',
		'save_drafts',
		'moderate_own_blogs',
		'lock_own_articles_in_own_b',
		'lock_own_articles_in_any_b',
		'lock_any_articles_in_own_b',
		'delete_own_articles_in_own_b',
		'delete_own_articles_in_any_b',
		'delete_any_articles_in_own_b',
		'edit_own_articles_in_own_b',
		'edit_own_articles_in_any_b',
		'edit_any_articles_in_own_b',
		'delete_own_comments_in_own_b',
		'delete_own_comments_in_any_b',
		'delete_any_comments_in_own_b',
		'edit_own_comments_in_own_b',
		'edit_own_comments_in_any_b',
		'edit_any_comments_in_own_b',
		'post_polls',
		'vote_in_polls',
		'lock_own_polls_in_own_b',
		'lock_own_polls_in_any_b',
		'lock_any_polls_in_own_b',
		'edit_own_polls_in_own_b',
		'edit_own_polls_in_any_b',
		'edit_any_polls_in_own_b',
		'delete_own_polls_in_own_b',
		'delete_own_polls_in_any_b',
		'delete_any_polls_in_own_b',
		'access_global_settings_tab',
		'access_permissions_tab',
		'access_blog_index_tab',
		'access_plugins_tab',
		'access_themes_tab',
		'access_maintenance_tab',
		'access_any_blog_control_panel',
		'lock_blogs',
		'change_blog_ownership',
		'use_raw_html_in_custom_windows',
		'use_php_in_custom_windows',
	);
}

?>