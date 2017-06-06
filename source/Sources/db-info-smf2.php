<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
/*
	This file contains information about SMF 2.0's database tables
	
	array zc_smf_db_table_info(string $table)
		- Returns an array containing the structure of the SMF database table with the name $table
*/

function zc_smf_db_table_info($table)
{
	if ($table == 'attachments')
		return array(
			'table_name' => 'attachments',
			'columns' => array(
				'id_attach' => 'id_attach',
				'id_thumb' => 'id_thumb',
				'id_msg' => 'id_msg',
				'id_member' => 'id_member',
				'id_folder' => 'id_folder',
				'attachment_type' => 'attachment_type',
				'filename' => 'filename',
				'fileext' => 'fileext',
				'size' => 'size',
				'downloads' => 'downloads',
				'width' => 'width',
				'height' => 'height',
				'mime_type' => 'mime_type',
				'approved' => 'approved',
			),
		);
	elseif ($table == 'boards')
		return array(
			'table_name' => 'boards',
			'columns' => array(
				'id_board' => 'id_board',
				'id_cat' => 'id_cat',
				'child_level' => 'child_level',
				'id_parent' => 'id_parent',
				'board_order' => 'board_order',
				'id_last_msg' => 'id_last_msg',
				'id_msg_updated' => 'id_msg_updated',
				'member_groups' => 'member_groups',
				'id_profile' => 'id_profile',
				'name' => 'name',
				'description' => 'description',
				'num_topics' => 'num_topics',
				'num_posts' => 'num_posts',
				'count_posts' => 'count_posts',
				'id_theme' => 'id_theme',
				'override_theme' => 'override_theme',
				'unapproved_posts' => 'unapproved_posts',
				'unapproved_topics' => 'unapproved_topics',
				'redirect' => 'redirect',
			),
		);
	elseif ($table == 'log_online')
		return array(
			'table_name' => 'log_online',
			'columns' => array(
				'session' => 'session',
				'log_time' => 'log_time',
				'id_member' => 'id_member',
				'id_spider' => 'id_spider',
				'ip' => 'ip',
				'url' => 'url',
			),
		);
	elseif ($table == 'log_polls')
		return array(
			'table_name' => 'log_polls',
			'columns' => array(
				'id_poll' => 'id_poll',
				'id_choice' => 'id_choice',
				'id_member' => 'id_member',
			),
		);
	elseif ($table == 'membergroups')
		return array(
			'table_name' => 'membergroups',
			'columns' => array(
				'id_group' => 'id_group',
				'group_name' => 'group_name',
				'description' => 'description',
				'online_color' => 'online_color',
				'min_posts' => 'min_posts',
				'max_messages' => 'max_messages',
				'stars' => 'stars',
				'group_type' => 'group_type',
				'hidden' => 'hidden',
				'id_parent' => 'id_parent',
			),
		);
	elseif ($table == 'members')
		return array(
			'table_name' => 'members',
			'columns' => array(
				'id_member' => 'id_member',
				'member_name' => 'member_name',
				'date_registered' => 'date_registered',
				'posts' => 'posts',
				'id_group' => 'id_group',
				'lngfile' => 'lngfile',
				'last_login' => 'last_login',
				'real_name' => 'real_name',
				'instant_messages' => 'instant_messages',
				'unread_messages' => 'unread_messages',
				'buddy_list' => 'buddy_list',
				'pm_ignore_list' => 'pm_ignore_list',
				'pm_prefs' => 'pm_prefs',
				'mod_prefs' => 'mod_prefs',
				'message_labels' => 'message_labels',
				'passwd' => 'passwd',
				'openid_uri' => 'openid_uri',
				'email_address' => 'email_address',
				'personal_text' => 'personal_text',
				'gender' => 'gender',
				'birthdate' => 'birthdate',
				'website_title' => 'website_title',
				'website_url' => 'website_url',
				'location' => 'location',
				'icq' => 'icq',
				'aim' => 'aim',
				'yim' => 'yim',
				'msn' => 'msn',
				'hide_email' => 'hide_email',
				'show_online' => 'show_online',
				'time_format' => 'time_format',
				'signature' => 'signature',
				'time_offset' => 'time_offset',
				'avatar' => 'avatar',
				'pm_email_notify' => 'pm_email_notify',
				'karma_bad' => 'karma_bad',
				'karma_good' => 'karma_good',
				'usertitle' => 'usertitle',
				'notify_announcements' => 'notify_announcements',
				'notify_regularity' => 'notify_regularity',
				'notify_send_body' => 'notify_send_body',
				'notify_types' => 'notify_types',
				'member_ip' => 'member_ip',
				'member_ip2' => 'member_ip2',
				'secret_question' => 'secret_question',
				'secret_answer' => 'secret_answer',
				'id_theme' => 'id_theme',
				'is_activated' => 'is_activated',
				'validation_code' => 'validation_code',
				'id_msg_last_visit' => 'id_msg_last_visit',
				'additional_groups' => 'additional_groups',
				'smiley_set' => 'smiley_set',
				'id_post_group' => 'id_post_group',
				'total_time_logged_in' => 'total_time_logged_in',
				'password_salt' => 'password_salt',
				'ignore_boards' => 'ignore_boards',
				'warning' => 'warning',
				'passwd_flood' => 'passwd_flood',
			),
		);
	elseif ($table == 'messages')
		return array(
			'table_name' => 'messages',
			'columns' => array(
				'id_msg' => 'id_msg',
				'id_topic' => 'id_topic',
				'id_board' => 'id_board',
				'poster_time' => 'poster_time',
				'id_member' => 'id_member',
				'id_msg_modified' => 'id_msg_modified',
				'subject' => 'subject',
				'poster_name' => 'poster_name',
				'poster_email' => 'poster_email',
				'poster_ip' => 'poster_ip',
				'smileys_enabled' => 'smileys_enabled',
				'modified_time' => 'modified_time',
				'modified_name' => 'modified_name',
				'body' => 'body',
				'icon' => 'icon',
				'approved' => 'approved',
			),
		);
	elseif ($table == 'moderators')
		return array(
			'table_name' => 'moderators',
			'columns' => array(
				'id_board' => 'id_board',
				'id_member' => 'id_member',
			),
		);
	elseif ($table == 'polls')
		return array(
			'table_name' => 'polls',
			'columns' => array(
				'id_poll' => 'id_poll',
				'question' => 'question',
				'voting_locked' => 'voting_locked',
				'max_votes' => 'max_votes',
				'expire_time' => 'expire_time',
				'hide_results' => 'hide_results',
				'change_vote' => 'change_vote',
				'guest_vote' => 'guest_vote',
				'id_member' => 'id_member',
				'poster_name' => 'poster_name',
			),
		);
	elseif ($table == 'poll_choices')
		return array(
			'table_name' => 'poll_choices',
			'columns' => array(
				'id_poll' => 'id_poll',
				'id_choice' => 'id_choice',
				'label' => 'label',
				'votes' => 'votes',
			),
		);
	elseif ($table == 'topics')
		return array(
			'table_name' => 'topics',
			'columns' => array(
				'id_topic' => 'id_topic',
				'is_sticky' => 'is_sticky',
				'id_board' => 'id_board',
				'id_first_msg' => 'id_first_msg',
				'id_last_msg' => 'id_last_msg',
				'id_member_started' => 'id_member_started',
				'id_member_updated' => 'id_member_updated',
				'id_poll' => 'id_poll',
				'id_previous_board' => 'id_previous_board',
				'id_previous_topic' => 'id_previous_topic',
				'num_replies' => 'num_replies',
				'num_views' => 'num_views',
				'locked' => 'locked',
				'unapproved_posts' => 'unapproved_posts',
				'approved' => 'approved',
			),
		);
	elseif ($table == 'settings')
		return array(
			'table_name' => 'settings',
			'columns' => array(
				'variable' => 'variable',
				'value' => 'value',
			),
		);
	return false;
}

?>
