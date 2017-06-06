<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
/*
	This file just contains information for zCommunity's database table structure
	
	array zc_prepare_db_updates(string $prev_version)
		- Returns an array of information about changes that need to be made to the database from one version to the next
		
	array zc_prepare_db_table_info()
		- Returns array containing complete database table structure of zCommunity
*/

function zc_do_db_updates($prev_version)
{
	global $zcFunc, $zc;
	
	$tables = array();
	if ($prev_version == 'BC 2.0.2')
	{
		$tables['to_drop'] = array('blog_verification', 'blog_tags', 'blog_settings', 'blog_categories', 'blog_custom_windows', 'blog_links');
	}
	elseif (in_array($prev_version, array('0.7.0 Alpha', '0.7.1 Alpha', '0.7.2 Alpha', '0.7.3 Alpha', '0.7.4 Alpha', '0.7.5 Alpha')))
	{
		$tables['to_drop'] = array('blog_links');
	}
	
	// drop tables?
	if (!empty($tables['to_drop']))
	{
		$tables_to_drop = array();
		foreach ($tables['to_drop'] as $table_to_drop)
			$tables_to_drop[] = $zc['db_prefix'] . $table_to_drop;
			
		$zcFunc['db_query']("
			DROP TABLE IF EXISTS " . implode(',
				', $tables_to_drop), __FILE__, __LINE__);
				
		unset($tables['to_drop'], $tables_to_drop);
	}
	
	// make the blog_permissions table smaller by getting rid of rows where add_deny is 0
	if (in_array($prev_version, array('0.7.0 Alpha', '0.7.1 Alpha', '0.7.2 Alpha', '0.7.3 Alpha', '0.7.4 Alpha', '0.7.5 Alpha', '0.7.6 Alpha', '0.7.7 Alpha', '0.7.8 Alpha', '0.7.9 Alpha')))
		$zcFunc['db_query']("
			DELETE FROM {db_prefix}permissions
			WHERE add_deny = 0", __FILE__, __LINE__);
}

function zc_prepare_db_table_info()
{
	return array(
		'blogs' => array(
			'table_name' => 'blogs',
			'primary_key' => 'blog_id',
			'columns' => array(
				'blog_id' => array(
					'auto_increment' => true,
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'member_groups' => array(
					'type' => 'varchar',
					'default' => '-1,0',
					'limit' => 255,
					'null' => false,
				),
				'name' => array(
					'type' => 'text',
					'null' => false,
				),
				'description' => array(
					'type' => 'text',
					'null' => false,
				),
				'num_articles' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'num_unapproved_articles' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'num_comments' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'num_unapproved_comments' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'blog_owner' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'time_created' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'num_views' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'last_article_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'last_comment_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'moderators' => array(
					'type' => 'text',
					'null' => false,
				),
			),
		),
		'articles' => array(
			'table_name' => 'articles',
			'primary_key' => 'article_id',
			'columns' => array(
				'article_id' => array(
					'auto_increment' => true,
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'blog_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'body' => array(
					'type' => 'text',
					'null' => false,
				),
				'subject' => array(
					'type' => 'text',
					'null' => false,
				),
				'last_comment_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'posted_time' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'poster_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'poster_ip' => array(
					'type' => 'text',
					'null' => false,
				),
				'poster_email' => array(
					'type' => 'text',
					'null' => false,
				),
				'poster_name' => array(
					'type' => 'text',
					'null' => false,
				),
				'num_comments' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'num_unapproved_comments' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'num_views' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'locked' => array(
					'type' => 'tinyint',
					'limit' => 1,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'smileys_enabled' => array(
					'type' => 'tinyint',
					'limit' => 1,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'approved' => array(
					'type' => 'tinyint',
					'limit' => 1,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'is_sticky' => array(
					'type' => 'tinyint',
					'limit' => 1,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'month' => array(
					'type' => 'mediumint',
					'limit' => 2,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'year' => array(
					'type' => 'mediumint',
					'limit' => 4,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'blog_category_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'blog_tags' => array(
					'type' => 'text',
					'null' => false,
				),
				'last_edit_time' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'last_edit_name' => array(
					'type' => 'text',
					'null' => false,
				),
				'access_restrict' => array(
					'type' => 'tinyint',
					'limit' => 1,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'users_allowed' => array(
					'type' => 'text',
					'null' => false,
				),
			),
		),
		'categories' => array(
			'table_name' => 'categories',
			'primary_key' => 'blog_category_id',
			'columns' => array(
				'blog_category_id' => array(
					'type' => 'int',
					'auto_increment' => true,
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'blog_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'name' => array(
					'type' => 'varchar',
					'limit' => 100,
					'null' => false,
				),
				'total' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'cat_order' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
			),
		),
		'comments' => array(
			'table_name' => 'comments',
			'primary_key' => 'comment_id',
			'columns' => array(
				'comment_id' => array(
					'auto_increment' => true,
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'article_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'blog_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'posted_time' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'poster_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'poster_name' => array(
					'type' => 'text',
					'null' => false,
				),
				'poster_email' => array(
					'type' => 'text',
					'null' => false,
				),
				'poster_ip' => array(
					'type' => 'text',
					'null' => false,
				),
				'smileys_enabled' => array(
					'type' => 'tinyint',
					'limit' => 1,
					'default' => 1,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'subject' => array(
					'type' => 'text',
					'null' => false,
				),
				'body' => array(
					'type' => 'text',
					'null' => false,
				),
				'last_edit_time' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'last_edit_name' => array(
					'type' => 'text',
					'null' => false,
				),
				'approved' => array(
					'type' => 'tinyint',
					'limit' => 1,
					'null' => false,
					'attributes' => 'unsigned',
				),
			),
		),
		'custom_blocks' => array(
			'table_name' => 'custom_blocks',
			'primary_key' => 'block_id',
			'columns' => array(
				'block_id' => array(
					'type' => 'int',
					'auto_increment' => true,
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'blog_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'title' => array(
					'type' => 'varchar',
					'limit' => 100,
					'null' => false,
				),
				'content' => array(
					'type' => 'text',
					'null' => false,
				),
				'block_order' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'enabled' => array(
					'type' => 'tinyint',
					'limit' => 1,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'content_type' => array(
					'type' => 'tinyint',
					'limit' => 1,
					'null' => false,
					'attributes' => 'unsigned',
				),
			),
		),
		'custom_windows' => array(
			'table_name' => 'custom_windows',
			'primary_key' => 'window_id',
			'columns' => array(
				'window_id' => array(
					'type' => 'int',
					'auto_increment' => true,
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'blog_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'title' => array(
					'type' => 'varchar',
					'limit' => 100,
					'null' => false,
				),
				'content' => array(
					'type' => 'text',
					'null' => false,
				),
				'win_order' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'enabled' => array(
					'type' => 'tinyint',
					'limit' => 1,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'content_type' => array(
					'type' => 'tinyint',
					'limit' => 1,
					'null' => false,
					'attributes' => 'unsigned',
				),
			),
		),
		'drafts' => array(
			'table_name' => 'drafts',
			'primary_key' => 'draft_id',
			'columns' => array(
				'draft_id' => array(
					'auto_increment' => true,
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'last_saved_time' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'poster_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'locked' => array(
					'type' => 'tinyint',
					'limit' => 1,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'access_restrict' => array(
					'type' => 'tinyint',
					'limit' => 1,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'smileys_enabled' => array(
					'type' => 'tinyint',
					'limit' => 1,
					'default' => 1,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'subject' => array(
					'type' => 'text',
					'null' => false,
				),
				'body' => array(
					'type' => 'text',
					'null' => false,
				),
				'blog_category_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'blog_tags' => array(
					'type' => 'text',
					'null' => false,
				),
				'article' => array(
					'type' => 'tinyint',
					'limit' => 1,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'comment' => array(
					'type' => 'tinyint',
					'limit' => 1,
					'null' => false,
					'attributes' => 'unsigned',
				),
			),
		),
		'global_settings' => array(
			'table_name' => 'global_settings',
			'primary_key' => 'variable',
			'columns' => array(
				'variable' => array(
					'type' => 'varchar',
					'limit' => 255,
					'null' => false,
				),
				'value' => array(
					'type' => 'text',
					'null' => false,
				),
			),
		),
		'log_articles' => array(
			'table_name' => 'log_articles',
			'primary_key' => 'article_id, comment_id, member_id',
			'columns' => array(
				'article_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'comment_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'member_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
			),
		),
		'log_bad_bots' => array(
			'table_name' => 'log_bad_bots',
			'columns' => array(
				'ip' => array(
					'type' => 'varchar',
					'limit' => 15,
				),
				'timestamp' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'query_string' => array(
					'type' => 'text',
				),
			),
		),
		'log_blogs' => array(
			'table_name' => 'log_blogs',
			'primary_key' => 'blog_id, article_id, member_id',
			'columns' => array(
				'article_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'blog_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'member_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
			),
		),
		'log_errors' => array(
			'table_name' => 'log_errors',
			'primary_key' => 'error_id',
			'columns' => array(
				'error_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'error_type' => array(
					'type' => 'varchar',
					'limit' => 36,
				),
				'member_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'ip' => array(
					'type' => 'varchar',
					'limit' => 15,
				),
				'session' => array(
					'type' => 'varchar',
					'limit' => 32,
				),
				'timestamp' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'url' => array(
					'type' => 'text',
				),
				'message' => array(
					'type' => 'text',
				),
				'file' => array(
					'type' => 'varchar',
					'limit' => 255,
				),
				'line' => array(
					'type' => 'int',
					'limit' => 10,
				),
			),
		),
		'log_notify' => array(
			'table_name' => 'log_notify',
			'primary_key' => 'blog_id, article_id, member_id',
			'columns' => array(
				'blog_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'article_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'member_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'sent' => array(
					'type' => 'tinyint',
					'limit' => 1,
					'null' => false,
					'attributes' => 'unsigned',
				),
			),
		),
		'log_polls' => array(
			'table_name' => 'log_polls',
			'primary_key' => 'poll_id, choice_id, member_id',
			'columns' => array(
				'poll_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'choice_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'member_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
			),
		),
		'permissions' => array(
			'table_name' => 'permissions',
			'primary_key' => 'group_id, permission',
			'columns' => array(
				'group_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'signed',
				),
				'permission' => array(
					'type' => 'varchar',
					'limit' => 100,
					'null' => false,
				),
				'add_deny' => array(
					'type' => 'tinyint',
					'limit' => 1,
					'default' => 1,
					'null' => false,
					'attributes' => 'unsigned',
				),
			),
		),
		'plugin_settings' => array(
			'table_name' => 'plugin_settings',
			'primary_key' => 'blog_id',
			'columns' => array(
				'blog_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
			),
		),
		'polls' => array(
			'table_name' => 'polls',
			'primary_key' => 'poll_id',
			'columns' => array(
				'poll_id' => array(
					'type' => 'int',
					'auto_increment' => true,
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'blog_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'question' => array(
					'type' => 'text',
					'null' => false,
				),
				'voting_locked' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'max_votes' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'expire_time' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'hide_results' => array(
					'type' => 'tinyint',
					'limit' => 1,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'change_vote' => array(
					'type' => 'tinyint',
					'limit' => 1,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'posted_time' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'poster_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'poster_name' => array(
					'type' => 'text',
					'null' => false,
				),
				'last_edit_time' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
				),
				'last_edit_name' => array(
					'type' => 'text',
					'null' => false,
				),
			),
		),
		'poll_choices' => array(
			'table_name' => 'poll_choices',
			'primary_key' => 'poll_id, choice_id',
			'columns' => array(
				'poll_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'choice_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'label' => array(
					'type' => 'text',
					'null' => false,
				),
				'votes' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
			),
		),
		'preferences' => array(
			'table_name' => 'preferences',
			'primary_key' => 'member_id',
			'columns' => array(
				'member_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
			),
		),
		'settings' => array(
			'table_name' => 'settings',
			'primary_key' => 'blog_id',
			'columns' => array(
				'blog_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'member_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'blog_avatar' => array(
					'type' => 'text',
					'null' => false,
				),
			),
		),
		'tags' => array(
			'table_name' => 'tags',
			'primary_key' => 'tag, blog_id',
			'columns' => array(
				'tag' => array(
					'type' => 'varchar',
					'limit' => 42,
					'null' => false,
				),
				'blog_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'num_articles' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
			),
		),
		'theme_settings' => array(
			'table_name' => 'theme_settings',
			'primary_key' => 'blog_id',
			'columns' => array(
				'blog_id' => array(
					'type' => 'int',
					'limit' => 10,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'blog_theme' => array(
					'type' => 'varchar',
					'limit' => 255,
					'null' => false,
				),
				'independent' => array(
					'type' => 'tinyint',
					'limit' => 1,
					'null' => false,
					'attributes' => 'unsigned',
				),
				'blog_page_width' => array(
					'type' => 'varchar',
					'limit' => 21,
					'null' => false,
				),
				'blog_page_alignment' => array(
					'type' => 'varchar',
					'limit' => 6,
					'null' => false,
				),
			),
		),
	);
}
?>