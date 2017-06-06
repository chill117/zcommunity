<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
/*
	zcRecountBlogTotals(array $blogs = null, bool $skip_permission_check = false)
		- recount totals for only blogs in $blogs or all blogs if $blogs is null
		- recounts num_comments for all articles in blogs
		- recounts num_articles and num_comments for blogs

	zcMaintainBlogCategories(array $blogs = null, bool $skip_permission_check = false)
		- recounts the totals for all blog categories if $blogs is null
		- or for only the blogs in $blogs
		
	zcMaintainTags(array $articles = null, array $blogs = null, bool $skip_permission_check = false)
		- recounts the number of articles a tag appears in for all tags if $articles and $blogs are null
		- or for only the articles in $articles
		- or for only the blogs in $blogs
*/

function zcRecountBlogTotals($blogs = null, $skip_permission_check = false)
{
	global $zc, $zcFunc, $context;
	
	// have to be able to access the maintenance tab....
	if (!$skip_permission_check && !zc_check_permissions('access_maintenance_tab'))
		zc_fatal_error('zc_error_52');
	
	if (!empty($blogs) && !is_array($blogs))
		$blogs = array($blogs);
	elseif (!empty($blogs))
		$blogs = array_unique($blogs);
	
	$context['zc']['continue_post_data'] = '';
	$context['zc']['continue_countdown'] = '3';
	$context['zc']['sub_sub_template'] = 'zc_auto_submit_form';
	zcLoadTemplate('Generic-cpform');
	
	$total_steps = 3;

	// get as much time as possible...
	@set_time_limit(600);
	
	if (!isset($_REQUEST['start']))
		$_REQUEST['start'] = 0;
		
	$_REQUEST['start'] = (int) $_REQUEST['start'];
	
	if (!isset($_REQUEST['step']))
		$_REQUEST['step'] = 0;
	
	// recounting num_comments for all articles
	if ($_REQUEST['step'] == 0)
	{
		$request = $zcFunc['db_query']("
			SELECT /*!40001 SQL_NO_CACHE */ MAX(article_id)
			FROM {db_prefix}articles" . (!empty($blogs) ? "
			WHERE blog_id IN ({array_int:blogs})" : ''), __FILE__, __LINE__,
			array(
				'blogs' => $blogs
			)
		);
		list($max_articles) = $zcFunc['db_fetch_row']($request);
		$zcFunc['db_free_result']($request);
			
		$increment = min(ceil($max_articles / 4), 2000);
		
		$columns = array('num_comments' => 'int', 'article_id' => 'int');
		while ($_REQUEST['start'] < $max_articles)
		{
			// recount comments for each article
			$request = $zcFunc['db_query']("
				SELECT /*!40001 SQL_NO_CACHE */ t.article_id, t.num_comments, COUNT(c.comment_id) AS real_num_comments
				FROM ({db_prefix}articles AS t, {db_prefix}comments AS c)
				WHERE c.article_id = t.article_id
					AND t.article_id > {int:start}
					AND t.article_id <= {int:maxindex}" . (!empty($blogs) ? "
					AND t.blog_id IN ({array_int:blogs})" : '') . "
				GROUP BY t.article_id", __FILE__, __LINE__,
				array(
					'blogs' => $blogs,
					'start' => $_REQUEST['start'],
					'maxindex' => $_REQUEST['start'] + $increment
				)
			);
			while ($row = $zcFunc['db_fetch_assoc']($request))
				$zcFunc['db_update']('{db_prefix}articles', $columns, array('num_comments' => $row['real_num_comments']), array('article_id' => $row['article_id']));
			$zcFunc['db_free_result']($request);
			
			$_REQUEST['start'] += $increment;
			
			if (array_sum(explode(' ', microtime())) - array_sum(explode(' ', $zc['time_start'])) > 3)
			{
				$context['zc']['continue_get_data'] = '?zc=bcp;u=' . $context['user']['id'] . ';sa=recountblogtotals;step=0;start=' . $_REQUEST['start'];
				$context['zc']['continue_percent'] = round((100 * $_REQUEST['start'] / $max_articles) / $total_steps);
				return;
			}
		}
		$_REQUEST['step']++;
		$_REQUEST['start'] = 0;
	}
	
	// recount num_comments for all blogs
	if ($_REQUEST['step'] == 1)
	{
		if (empty($blogs))
		{
			$request = $zcFunc['db_query']("
				SELECT /*!40001 SQL_NO_CACHE */ MAX(blog_id)
				FROM {db_prefix}blogs", __FILE__, __LINE__);
			list($max_blogs) = $zcFunc['db_fetch_row']($request);
			$zcFunc['db_free_result']($request);
		}
		else
			$max_blogs = max($blogs);
			
		$increment = min(ceil($max_blogs / 4), 2000);
		
		$columns = array('num_comments' => 'int', 'blog_id' => 'int');
		while ($_REQUEST['start'] < $max_blogs)
		{
			// recount comments for each blog
			$request = $zcFunc['db_query']("
				SELECT /*!40001 SQL_NO_CACHE */ b.blog_id, b.num_comments, COUNT(c.comment_id) AS real_num_comments
				FROM ({db_prefix}blogs AS b, {db_prefix}comments AS c)
				WHERE c.blog_id = b.blog_id
					AND b.blog_id > {int:start}
					AND b.blog_id <= {int:maxindex}" . (!empty($blogs) ? "
					AND b.blog_id IN ({array_int:blogs})" : '') . "
				GROUP BY b.blog_id", __FILE__, __LINE__,
				array(
					'blogs' => $blogs,
					'start' => $_REQUEST['start'],
					'maxindex' => $_REQUEST['start'] + $increment
				)
			);
			while ($row = $zcFunc['db_fetch_assoc']($request))
				$zcFunc['db_update']('{db_prefix}blogs', $columns, array('num_comments' => $row['real_num_comments']), array('blog_id' => $row['blog_id']));
			$zcFunc['db_free_result']($request);
			
			$_REQUEST['start'] += $increment;
			
			if (array_sum(explode(' ', microtime())) - array_sum(explode(' ', $zc['time_start'])) > 3)
			{
				$context['zc']['continue_get_data'] = '?zc=bcp;u=' . $context['user']['id'] . ';sa=recountblogtotals;step=1;start=' . $_REQUEST['start'];
				$context['zc']['continue_percent'] = round((100 * $_REQUEST['start'] / $max_blogs) / $total_steps);
				return;
			}
		}
		$_REQUEST['step']++;
		$_REQUEST['start'] = 0;
	}
	
	// recount num_articles for all blogs
	if ($_REQUEST['step'] == 2)
	{
		if (empty($blogs))
		{
			$request = $zcFunc['db_query']("
				SELECT /*!40001 SQL_NO_CACHE */ MAX(blog_id)
				FROM {db_prefix}blogs", __FILE__, __LINE__);
			list($max_blogs) = $zcFunc['db_fetch_row']($request);
			$zcFunc['db_free_result']($request);
		}
		else
			$max_blogs = max($blogs);
			
		$increment = min(ceil($max_blogs / 4), 2000);
		
		$columns = array('num_articles' => 'int', 'blog_id' => 'int');
		while ($_REQUEST['start'] < $max_blogs)
		{
			// recount articles for each blog
			$request = $zcFunc['db_query']("
				SELECT /*!40001 SQL_NO_CACHE */ b.blog_id, b.num_articles, COUNT(t.article_id) AS real_num_articles
				FROM ({db_prefix}blogs AS b, {db_prefix}articles AS t)
				WHERE t.blog_id = b.blog_id
					AND b.blog_id > {int:start}
					AND b.blog_id <= {int:maxindex}" . (!empty($blogs) ? "
					AND b.blog_id IN ({array_int:blogs})" : '') . "
				GROUP BY b.blog_id", __FILE__, __LINE__,
				array(
					'blogs' => $blogs,
					'start' => $_REQUEST['start'],
					'maxindex' => $_REQUEST['start'] + $increment
				)
			);
			while ($row = $zcFunc['db_fetch_assoc']($request))
				$zcFunc['db_update']('{db_prefix}blogs', $columns, array('num_articles' => $row['real_num_articles']), array('blog_id' => $row['blog_id']));
			$zcFunc['db_free_result']($request);
			
			$_REQUEST['start'] += $increment;
			
			if (array_sum(explode(' ', microtime())) - array_sum(explode(' ', $zc['time_start'])) > 3)
			{
				$context['zc']['continue_get_data'] = '?zc=bcp;u=' . $context['user']['id'] . ';sa=recountblogtotals;step=2;start=' . $_REQUEST['start'];
				$context['zc']['continue_percent'] = round((100 * $_REQUEST['start'] / $max_blogs) / $total_steps);
				return;
			}
		}
		$_REQUEST['step']++;
		$_REQUEST['start'] = 0;
	}
	
	// recount num_unapproved_comments for all blogs
	if ($_REQUEST['step'] == 3)
	{
		if (empty($blogs))
		{
			$request = $zcFunc['db_query']("
				SELECT /*!40001 SQL_NO_CACHE */ MAX(blog_id)
				FROM {db_prefix}blogs", __FILE__, __LINE__);
			list($max_blogs) = $zcFunc['db_fetch_row']($request);
			$zcFunc['db_free_result']($request);
		}
		else
			$max_blogs = max($blogs);
			
		$increment = min(ceil($max_blogs / 4), 2000);
		
		$columns = array('num_unapproved_comments' => 'int', 'blog_id' => 'int');
		while ($_REQUEST['start'] < $max_blogs)
		{
			// recount comments for each blog
			$request = $zcFunc['db_query']("
				SELECT /*!40001 SQL_NO_CACHE */ b.blog_id, b.num_unapproved_comments, COUNT(c.comment_id) AS real_num_unapproved_comments
				FROM ({db_prefix}blogs AS b, {db_prefix}comments AS c)
				WHERE c.blog_id = b.blog_id
					AND c.approved = 0
					AND b.blog_id > {int:start}
					AND b.blog_id <= {int:maxindex}" . (!empty($blogs) ? "
					AND b.blog_id IN ({array_int:blogs})" : '') . "
				GROUP BY b.blog_id", __FILE__, __LINE__,
				array(
					'blogs' => $blogs,
					'start' => $_REQUEST['start'],
					'maxindex' => $_REQUEST['start'] + $increment
				)
			);
			while ($row = $zcFunc['db_fetch_assoc']($request))
				$zcFunc['db_update']('{db_prefix}blogs', $columns, array('num_unapproved_comments' => $row['real_num_unapproved_comments']), array('blog_id' => $row['blog_id']));
			$zcFunc['db_free_result']($request);
			
			$_REQUEST['start'] += $increment;
			
			if (array_sum(explode(' ', microtime())) - array_sum(explode(' ', $zc['time_start'])) > 3)
			{
				$context['zc']['continue_get_data'] = '?zc=bcp;u=' . $context['user']['id'] . ';sa=recountblogtotals;step=3;start=' . $_REQUEST['start'];
				$context['zc']['continue_percent'] = round((100 * $_REQUEST['start'] / $max_blogs) / $total_steps);
				return;
			}
		}
		$_REQUEST['step']++;
		$_REQUEST['start'] = 0;
	}
	
	// recount num_unapproved_articles for all blogs
	if ($_REQUEST['step'] == 4)
	{
		if (empty($blogs))
		{
			$request = $zcFunc['db_query']("
				SELECT /*!40001 SQL_NO_CACHE */ MAX(blog_id)
				FROM {db_prefix}blogs", __FILE__, __LINE__);
			list($max_blogs) = $zcFunc['db_fetch_row']($request);
			$zcFunc['db_free_result']($request);
		}
		else
			$max_blogs = max($blogs);
			
		$increment = min(ceil($max_blogs / 4), 2000);
		
		$columns = array('num_unapproved_articles' => 'int', 'blog_id' => 'int');
		while ($_REQUEST['start'] < $max_blogs)
		{
			// recount articles for each blog
			$request = $zcFunc['db_query']("
				SELECT /*!40001 SQL_NO_CACHE */ b.blog_id, b.num_unapproved_articles, COUNT(t.article_id) AS real_num_unapproved_articles
				FROM ({db_prefix}blogs AS b, {db_prefix}articles AS t)
				WHERE t.blog_id = b.blog_id
					AND t.approved = 0
					AND b.blog_id > {int:start}
					AND b.blog_id <= {int:maxindex}" . (!empty($blogs) ? "
					AND b.blog_id IN ({array_int:blogs})" : '') . "
				GROUP BY b.blog_id", __FILE__, __LINE__,
				array(
					'blogs' => $blogs,
					'start' => $_REQUEST['start'],
					'maxindex' => $_REQUEST['start'] + $increment
				)
			);
			while ($row = $zcFunc['db_fetch_assoc']($request))
				$zcFunc['db_update']('{db_prefix}blogs', $columns, array('num_unapproved_articles' => $row['real_num_unapproved_articles']), array('blog_id' => $row['blog_id']));
			$zcFunc['db_free_result']($request);
			
			$_REQUEST['start'] += $increment;
			
			if (array_sum(explode(' ', microtime())) - array_sum(explode(' ', $zc['time_start'])) > 3)
			{
				$context['zc']['continue_get_data'] = '?zc=bcp;u=' . $context['user']['id'] . ';sa=recountblogtotals;step=4;start=' . $_REQUEST['start'];
				$context['zc']['continue_percent'] = round((100 * $_REQUEST['start'] / $max_blogs) / $total_steps);
				return;
			}
		}
		$_REQUEST['step']++;
		$_REQUEST['start'] = 0;
	}
	
	$_SESSION['zc_success_msg'] = 'zc_success_7';
}
	
function zcMaintainBlogCategories($blogs = null, $skip_permission_check = false)
{
	global $zc, $zcFunc, $context;
	
	// have to be able to access the maintenance tab....
	if (!$skip_permission_check && !zc_check_permissions('access_maintenance_tab'))
		zc_fatal_error('zc_error_52');
	
	if (!empty($blogs) && !is_array($blogs))
		$blogs = array($blogs);
		
	if (!empty($blogs))
		$blogs = array_unique($blogs);
	
	$context['zc']['continue_post_data'] = '';
	$context['zc']['continue_countdown'] = '3';
	$context['zc']['sub_sub_template'] = 'zc_auto_submit_form';
	zcLoadTemplate('Generic-cpform');

	// get as much time as possible...
	@set_time_limit(600);
	
	if (!isset($_REQUEST['start']))
		$_REQUEST['start'] = 0;
		
	$_REQUEST['start'] = (int) $_REQUEST['start'];
	
	$info = array('blogs' => $blogs);
	$request = $zcFunc['db_query']("
		SELECT MAX(blog_category_id)
		FROM {db_prefix}categories" . (!empty($info['blogs']) ? "
		WHERE blog_id IN ({array_int:blogs})" : ''), __FILE__, __LINE__, $info);
	list ($max_id) = $zcFunc['db_fetch_row']($request);
	$zcFunc['db_free_result']($request);
	
	$columns = array('total' => 'int', 'blog_category_id' => 'int');
	while ($_REQUEST['start'] < $max_id)
	{
		// get an id
		$request = $zcFunc['db_query']("
			SELECT /*!40001 SQL_NO_CACHE */ blog_category_id
			FROM {db_prefix}categories
			WHERE blog_category_id > {int:last_id}
				AND blog_category_id <= {int:max_id}" . (!empty($blogs) ? "
				AND blog_id IN ({array_int:blogs})" : '') . "
			ORDER BY blog_category_id ASC
			LIMIT 1", __FILE__, __LINE__,
			array(
				'blogs' => $blogs,
				'max_id' => $max_id,
				'last_id' => (int) $_REQUEST['start']
			)
		);
		if ($zcFunc['db_num_rows']($request) > 0)
		{
			list($id) = $zcFunc['db_fetch_row']($request);
			$zcFunc['db_free_result']($request);
		
			$request = $zcFunc['db_query']("
				SELECT /*!40001 SQL_NO_CACHE */ COUNT(article_id)
				FROM {db_prefix}articles
				WHERE blog_category_id = {int:id}", __FILE__, __LINE__,
				array(
					'id' => $id
				)
			);
			list($num_articles) = $zcFunc['db_fetch_row']($request);
			
			$_REQUEST['start'] = $id;
			
			$zcFunc['db_update']('{db_prefix}categories', $columns, array('total' => $num_articles), array('blog_category_id' => $id));
			
			if (array_sum(explode(' ', microtime())) - array_sum(explode(' ', $zc['time_start'])) > 3)
			{
				$context['zc']['continue_get_data'] = '?zc=bcp;u='. $context['user']['id'] .';sa=maintainblogcategories;start=' . $_REQUEST['start'];
				$context['zc']['continue_percent'] = round(100 * $_REQUEST['start'] / $max_id);
		
				return;
			}
		}
		else
			$_REQUEST['start'] = $max_id;
			
		$zcFunc['db_free_result']($request);
	}
	$_SESSION['zc_success_msg'] = 'zc_success_7';
}

function zcMaintainTags($articles = null, $blogs = null, $skip_permission_check = false)
{
	global $zc, $zcFunc, $context;
	
	// have to be able to access the maintenance tab....
	if (!$skip_permission_check && !zc_check_permissions('access_maintenance_tab'))
		zc_fatal_error('zc_error_52');
	
	if (!empty($articles) && !is_array($articles))
		$articles = array($articles);
	
	if (!empty($blogs) && !is_array($blogs))
		$blogs = array($blogs);
	
	$context['zc']['continue_post_data'] = '';
	$context['zc']['continue_countdown'] = '3';
	$context['zc']['sub_sub_template'] = 'zc_auto_submit_form';
	zcLoadTemplate('Generic-cpform');
		
	if (!empty($articles))
		$articles = array_unique($articles);
	elseif (!empty($blogs))
		$blogs = array_unique($blogs);

	// get as much time as possible...
	@set_time_limit(600);
	
	if (!isset($_REQUEST['start']))
		$_REQUEST['start'] = 0;
		
	$_REQUEST['start'] = (int) $_REQUEST['start'];
	
	if (empty($articles))
	{
		$info = array('blogs' => $blogs);
		$request = $zcFunc['db_query']("
			SELECT /*!40001 SQL_NO_CACHE */ MAX(article_id)
			FROM {db_prefix}articles" . (!empty($blogs) ? "
			WHERE blog_id IN ({array_int:blogs})" : ''), __FILE__, __LINE__, $info);
		list($max_articles) = $zcFunc['db_fetch_row']($request);
		$zcFunc['db_free_result']($request);
	}
	else
		$max_articles = max($articles);
		
	$increment = min(ceil($max_articles / 4), 2000);
	
	$columns = array('blog_id' => 'int', 'tag' => 'string', 'num_articles' => 'int');
	while ($_REQUEST['start'] < $max_articles)
	{
		// get the blog_tags from all articles
		$request = $zcFunc['db_query']("
			SELECT /*!40001 SQL_NO_CACHE */ blog_tags, article_id, blog_id
			FROM {db_prefix}articles
			WHERE blog_tags != {empty_string}
				AND article_id > {int:start}
				AND article_id <= {int:maxindex}" . (!empty($articles) ? "
				AND article_id IN ({array_int:articles})" : (!empty($blogs) ? "
				AND blog_id IN ({array_int:blogs})" : '')), __FILE__, __LINE__,
			array(
				'articles' => $articles,
				'blogs' => $blogs,
				'start' => $_REQUEST['start'],
				'maxindex' => $_REQUEST['start'] + $increment
			)
		);
		$tags_by_blog = array();
		while ($row = $zcFunc['db_fetch_assoc']($request))
		{
			if (!isset($tags_by_blog[$row['blog_id']]))
				$tags_by_blog[$row['blog_id']] = array();
				
			$blog_tags = explode(',', $row['blog_tags']);
			
			if (!empty($blog_tags))
				foreach ($blog_tags as $tag)
					if (!isset($tags_by_blog[$row['blog_id']][$tag]))
						$tags_by_blog[$row['blog_id']][$tag] = 1;
					else
						$tags_by_blog[$row['blog_id']][$tag] += 1;
		}
		$zcFunc['db_free_result']($request);
		
		$data = array();
		if (!empty($tags_by_blog))
			foreach ($tags_by_blog as $blog_id => $tags)
				foreach ($tags as $tag => $num_articles)
					$data[] = array('blog_id' => $blog_id, 'tag' => $tag, 'num_articles' => $num_articles);
			
		if (!empty($data))
			$zcFunc['db_insert']('replace', '{db_prefix}tags', $columns, $data);
		
		$_REQUEST['start'] += $increment;
		
		if (array_sum(explode(' ', microtime())) - array_sum(explode(' ', $zc['time_start'])) > 3)
		{
			$context['zc']['continue_get_data'] = '?zc=bcp;u='. $context['user']['id'] .';sa=maintaintags;start=' . $_REQUEST['start'];
			$context['zc']['continue_percent'] = round(100 * $_REQUEST['start'] / $max_articles);
			return;
		}
	}
	$_SESSION['zc_success_msg'] = 'zc_success_7';
}

function zcPruneDrafts()
{
	global $zcFunc;
	
	if (empty($_POST['pruneDrafts']))
		return;

	// get as much time as possible...
	@set_time_limit(600);
	
	if (!isset($_REQUEST['start']))
		$_REQUEST['start'] = 0;
		
	$_REQUEST['start'] = (int) $_REQUEST['start'];
	
	$request = $zcFunc['db_query']("
		SELECT /*!40001 SQL_NO_CACHE */ MAX(draft_id)
		FROM {db_prefix}drafts
		WHERE last_saved_time < {int:cutoff}", __FILE__, __LINE__,
		array(
			'cutoff' => time() - (((int) $_POST['pruneDrafts']) * 24 * 60)
		)
	);
	list($max_drafts) = $zcFunc['db_fetch_row']($request);
	$zcFunc['db_free_result']($request);
		
	$increment = min(ceil($max_drafts / 4), 2000);
	
	while ($_REQUEST['start'] < $max_drafts)
	{
		$zcFunc['db_query']("
			DELETE FROM {db_prefix}drafts
			WHERE last_saved_time < {int:cutoff}
				AND draft_id > {int:start}
				AND draft_id <= {int:maxindex}", __FILE__, __LINE__,
			array(
				'cutoff' => time() - (((int) $_POST['pruneDrafts']) * 24 * 60),
				'start' => $_REQUEST['start'],
				'maxindex' => $_REQUEST['start'] + $increment
			)
		);
		
		$_REQUEST['start'] += $increment;
		
		if (array_sum(explode(' ', microtime())) - array_sum(explode(' ', $zc['time_start'])) > 3)
		{
			$context['zc']['continue_get_data'] = '?zc=bcp;u=' . $context['user']['id'] . ';sa=prunedrafts;start=' . $_REQUEST['start'];
			$context['zc']['continue_percent'] = round(100 * $_REQUEST['start'] / $max_drafts);
			return;
		}
	}
	
	$_SESSION['zc_success_msg'] = 'zc_success_7';
}

function zc_repair_db_table($table_name, $reference_array, $omit = null, $redirect = true)
{
	global $context, $zcFunc, $zc;
	
	// make sure it's an array if we're using it...
	if (!empty($omit) && !is_array($omit))
		$omit = explode(',', $omit);
	
	$columns = array();
	if (isset($reference_array) && is_array($reference_array))
	{
		// How is the information in the reference array formatted?
		if (isset($reference_array['table_name']) && isset($reference_array['columns']))
			$array_format = 'custom2';
		elseif (isset($reference_array['_info_']))
			$array_format = 'custom1';
			
		if (!isset($array_format))
			return false;
			
		$exclude = !empty($reference_array['_info_']['exclude_from_table']) ? $reference_array['_info_']['exclude_from_table'] : array();
		$exclude[] = '_info_';
		if (!empty($omit) && is_array($omit))
			$exclude += $omit;
			
		$table_columns = $array_format == 'custom1' ? $reference_array : $reference_array['columns'];
		foreach($table_columns as $k => $array)
			if (!in_array($k, $exclude))
				$columns[$k] = $array;
	}
	else
		return false;
			
	$request = $zcFunc['db_query']("
		SHOW COLUMNS FROM {db_prefix}{raw:table_name}", __FILE__, __LINE__,
		array(
			'table_name' => $table_name
		)
	);
	
	// unset $columns[$key] where $key is a column name that exists
	while ($row = $zcFunc['db_fetch_assoc']($request))
		if (isset($columns[$row['Field']]))
			unset($columns[$row['Field']]);
	
	$zcFunc['db_free_result']($request);
			
	if (!empty($columns))
	{
		$info = array('table_name' => $table_name);
		$insertColumns = array();
		foreach ($columns as $k => $array)
		{
			$limit = '';
			$col_type = '';
			$attr = '';
			$default = '';
			$null = '';
		
			if ($array_format == 'custom1')
			{
				$default = $array['value'];
				if (in_array($array['type'], array('int', 'check', 'float')) && !is_numeric($default))
					$default = 0;
					
				if (isset($array['custom']))
				{
					// figure out column size in table
					if ($array['custom'] == 'select' && isset($array['options']) && count($array['options']) > 0)
					{
						$keys = array_keys($array['options']);
						$longest_key = 0;
						foreach ($keys as $key)
							if (strlen((string)$key) > $longest_key)
								$longest_key = strlen((string)$key);
								
						if (!empty($longest_key))
						{
							if ($array['type'] == 'int')
							{
								if ($longest_key >= 2 && $longest_key <= 8)
									$col_type = 'mediumint';
								elseif ($longest_key == 1)
									$col_type = 'tinyint';
							}
							$limit = $longest_key;
						}
					}
				}
				
				if ($array['type'] == 'check')
				{
					$limit = 1;
					$col_type = 'tinyint';
					$attr = 'unsigned';
				}
				elseif ($array['type'] == 'int')
				{
					if (empty($limit))
						$limit = 10;
					if (empty($col_type))
						$col_type = 'int';
					$attr = 'unsigned';
				}
				elseif ($array['type'] == 'float')
				{
					if (empty($limit))
						$limit = 10;
					$col_type = 'float';
					$attr = 'unsigned';
				}
				elseif ($array['type'] == 'text')
				{
					if (!empty($default) || !empty($limit))
					{
						if (empty($limit))
							$limit = 255;
							
						$col_type = 'varchar';
					}
					else
					{
						$col_type = 'text';
						
						if (isset($default))
							unset($default);
							
						$limit = '';
					}
					$attr = '';
				}
				
				$null = ' NOT NULL';
			}
			elseif ($array_format == 'custom2')
			{
				$col_type = $array['type'];
				
				$null = !empty($array['null']) ? ' NULL' : ' NOT NULL';
				
				if (isset($array['limit']))
					$limit = $array['limit'];
				
				if (isset($array['attributes']))
					$attr = $array['attributes'];
					
				if (isset($array['default']))
					$default = $array['default'];
				elseif (in_array($col_type, array('tinyint', 'mediumint', 'int')))
					$default = 0;
			}
			
			$col_type = ' ' . $col_type . (!empty($limit) ? '(' . $limit . ')' : '');
			if (!empty($default) || (isset($default) && $default === 0))
			{
				$info[$k] = $default;
				$default = ' default {string:' . $k . '}';
			}
			else
				$default = '';
				
			$attr = !empty($attr) ? ' ' . $attr : '';
			
			if (!empty($k) && !empty($col_type))
				$insertColumns[] = $k . $col_type . $attr . $null . $default;
		}
		
		// alter the table
		if (!empty($insertColumns))
			$result = $zcFunc['db_query']("
				ALTER TABLE {db_prefix}{raw:table_name}
					ADD " . implode(',
					ADD ', $insertColumns), __FILE__, __LINE__, $info);
		else
			return false;
	}
	else
		return true;
	
	if (!empty($redirect))
		zc_redirect_exit(zcRequestVarsToString());
	else
		return $result;
}

function zc_db_check_for_missing_columns($tables, $auto_fix = false)
{
	if (empty($tables))
		return false;
		
	global $zcFunc;
		
	$issues_discovered = $auto_fix ? 0 : array();
	
	foreach ($tables as $table_name => $array)
	{
		// skip if no table name
		if (empty($table_name))
			continue;
			
		$exclude = !empty($array['_info_']['exclude_from_table']) ? $array['_info_']['exclude_from_table'] : array();
		$exclude[] = '_info_';
		$table_columns = isset($array['columns']) ? $array['columns'] : $array;
		
		$columns = array();
		foreach($table_columns as $k => $dummy)
			if (!in_array($k, $exclude))
				$columns[$k] = true;
				
		// get info about all the columns from the table
		$request = $zcFunc['db_query']("
			SHOW COLUMNS FROM {db_prefix}{raw:table_name}", __FILE__, __LINE__, array('table_name' => $table_name));
		
		// unset $columns[] where the key has a corresponding column in this table
		while ($row = $zcFunc['db_fetch_assoc']($request))
			if (isset($columns[$row['Field']]))
				unset($columns[$row['Field']]);
				
		$zcFunc['db_free_result']($request);
				
		// problem found!
		if (!empty($columns))
		{
			if ($auto_fix)
			{
				$issues_discovered++;
				zc_repair_db_table($table_name, $array, null, false);
			}
			else
				$issues_discovered[] = $table_name;
		}
	}
	return $issues_discovered;
}

?>