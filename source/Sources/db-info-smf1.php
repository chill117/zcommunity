<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
/*
	This file contains information about SMF 1.1.x's database tables
	
	array zc_smf_db_table_info(string $table)
		- Returns an array containing the structure of the SMF database table with the name $table
*/

function zc_smf_db_table_info($table)
{
	if ($table == 'attachments')
		return array(
			'table_name' => 'attachments',
			'columns' => array(
				'id_attach' => 'ID_ATTACH',
				'id_thumb' => 'ID_THUMB',
				'id_msg' => 'ID_MSG',
				'id_member' => 'ID_MEMBER',
				'attachment_type' => 'attachmentType',
				'filename' => 'filename',
				'size' => 'size',
				'downloads' => 'downloads',
				'width' => 'width',
				'height' => 'height',
			),
		);
	elseif ($table == 'boards')
		return array(
			'table_name' => 'boards',
			'columns' => array(
				'id_board' => 'ID_BOARD',
				'id_cat' => 'ID_CAT',
				'child_level' => 'childLevel',
				'id_parent' => 'ID_PARENT',
				'board_order' => 'boardOrder',
				'id_last_msg' => 'ID_LAST_MSG',
				'id_msg_updated' => 'ID_MSG_UPDATED',
				'member_groups' => 'memberGroups',
				'name' => 'name',
				'description' => 'description',
				'num_topics' => 'numTopics',
				'num_posts' => 'numPosts',
				'count_posts' => 'countPosts',
				'id_theme' => 'ID_THEME',
				'override_theme' => 'override_theme',
				'permission_mode' => 'permission_mode',
			),
		);
	elseif ($table == 'log_online')
		return array(
			'table_name' => 'log_online',
			'columns' => array(
				'session' => 'session',
				'log_time' => 'logTime',
				'id_member' => 'ID_MEMBER',
				'ip' => 'ip',
				'url' => 'url',
			),
		);
	elseif ($table == 'log_polls')
		return array(
			'table_name' => 'log_polls',
			'columns' => array(
				'id_poll' => 'ID_POLL',
				'id_choice' => 'ID_CHOICE',
				'id_member' => 'ID_MEMBER',
			),
		);
	elseif ($table == 'membergroups')
		return array(
			'table_name' => 'membergroups',
			'columns' => array(
				'id_group' => 'ID_GROUP',
				'group_name' => 'groupName',
				'online_color' => 'onlineColor',
				'min_posts' => 'minPosts',
				'max_messages' => 'maxMessages',
				'stars' => 'stars',
			),
		);
	elseif ($table == 'members')
		return array(
			'table_name' => 'members',
			'columns' => array(
				'id_member' => 'ID_MEMBER',
				'member_name' => 'memberName',
				'date_registered' => 'dateRegistered',
				'posts' => 'posts',
				'id_group' => 'ID_GROUP',
				'lngfile' => 'lngfile',
				'last_login' => 'lastLogin',
				'real_name' => 'realName',
				'instant_messages' => 'instantMessages',
				'unread_messages' => 'unreadMessages',
				'buddy_list' => 'buddy_list',
				'pm_ignore_list' => 'pm_ignore_list',
				'message_labels' => 'messageLabels',
				'passwd' => 'passwd',
				'email_address' => 'emailAddress',
				'personal_text' => 'personalText',
				'gender' => 'gender',
				'birthdate' => 'birthdate',
				'website_title' => 'websiteTitle',
				'website_url' => 'websiteUrl',
				'location' => 'location',
				'icq' => 'ICQ',
				'aim' => 'AIM',
				'yim' => 'YIM',
				'msn' => 'MSN',
				'hide_email' => 'hideEmail',
				'show_online' => 'showOnline',
				'time_format' => 'timeFormat',
				'signature' => 'signature',
				'time_offset' => 'timeOffset',
				'avatar' => 'avatar',
				'pm_email_notify' => 'pm_email_notify',
				'karma_bad' => 'karmaBad',
				'karma_good' => 'karmaGood',
				'usertitle' => 'usertitle',
				'notify_announcements' => 'notifyAnnouncements',
				'notify_once' => 'notifyOnce',
				'notify_send_body' => 'notifySendBody',
				'notify_types' => 'notifyTypes',
				'member_ip' => 'memberIP',
				'member_ip2' => 'memberIP2',
				'secret_question' => 'secretQuestion',
				'secret_answer' => 'secretAnswer',
				'id_theme' => 'ID_THEME',
				'is_activated' => 'is_activated',
				'validation_code' => 'validation_code',
				'id_msg_last_visit' => 'ID_MSG_LAST_VISIT',
				'additional_groups' => 'additionalGroups',
				'smiley_set' => 'smileySet',
				'id_post_group' => 'ID_POST_GROUP',
				'total_time_logged_in' => 'totalTimeLoggedIn',
				'password_salt' => 'passwordSalt',
			),
		);
	elseif ($table == 'messages')
		return array(
			'table_name' => 'messages',
			'columns' => array(
				'id_msg' => 'ID_MSG',
				'id_topic' => 'ID_TOPIC',
				'id_board' => 'ID_BOARD',
				'poster_time' => 'posterTime',
				'id_member' => 'ID_MEMBER',
				'id_msg_modified' => 'ID_MSG_MODIFIED',
				'subject' => 'subject',
				'poster_name' => 'posterName',
				'poster_email' => 'posterEmail',
				'poster_ip' => 'posterIP',
				'smileys_enabled' => 'smileysEnabled',
				'modified_time' => 'modifiedTime',
				'modified_name' => 'modifiedName',
				'body' => 'body',
				'icon' => 'icon',
			),
		);
	elseif ($table == 'moderators')
		return array(
			'table_name' => 'moderators',
			'columns' => array(
				'id_board' => 'ID_BOARD',
				'id_member' => 'ID_MEMBER',
			),
		);
	elseif ($table == 'polls')
		return array(
			'table_name' => 'polls',
			'columns' => array(
				'id_poll' => 'ID_POLL',
				'question' => 'question',
				'voting_locked' => 'votingLocked',
				'max_votes' => 'maxVotes',
				'expire_time' => 'expireTime',
				'hide_results' => 'hideResults',
				'change_vote' => 'changeVote',
				'id_member' => 'ID_MEMBER',
				'poster_name' => 'posterName',
			),
		);
	elseif ($table == 'poll_choices')
		return array(
			'table_name' => 'poll_choices',
			'columns' => array(
				'id_poll' => 'ID_POLL',
				'id_choice' => 'ID_CHOICE',
				'label' => 'label',
				'votes' => 'votes',
			),
		);
	elseif ($table == 'topics')
		return array(
			'table_name' => 'topics',
			'columns' => array(
				'id_topic' => 'ID_TOPIC',
				'is_sticky' => 'isSticky',
				'id_board' => 'ID_BOARD',
				'id_first_msg' => 'ID_FIRST_MSG',
				'id_last_msg' => 'ID_LAST_MSG',
				'id_member_started' => 'ID_MEMBER_STARTED',
				'id_member_updated' => 'ID_MEMBER_UPDATED',
				'id_poll' => 'ID_POLL',
				'num_replies' => 'numReplies',
				'num_views' => 'numViews',
				'locked' => 'locked',
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
