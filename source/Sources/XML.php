<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
/*
<?xml-stylesheet type="text/xsl" href="http://www.degreesofzero.com/Sources/BlogOPML.xls" media="screen"?>
<opml version="2.0">
<head>
	<title>Degrees of Zero zCommunity</title>
	<dateCreated>Sat, 26 Sep 2008 23:09:22 GMT</dateCreated>
</head>
<body>
	<outline text="Blogs">
		<outline type="rss" text="Developer\'s Blog" title="Developer\'s Blog" description="Where I post announcements related to the development of various web-based software." xmlUrl="http://www.degreesofzero.com/index.php?action=.xml;type=rss;board=2" htmlUrl="http://www.degreesofzero.com/index.php?blog=2.0" />
	</outline>
</body>
</opml>
*/


function BlogCommunityXmlFeed()
{
	global $context, $scripturl, $txt, $modSettings, $blog_info, $zc, $blog, $zcFunc;

	// they shouldn't have made this far... but just in case...
	if (empty($zc['settings']['blog_xml_enable']))
		zc_ob_exit(false);

	// Show in rss or proprietary format?
	$xml_format = isset($_GET['type']) && in_array($_GET['type'], array('smf', 'rss', 'rss2', 'atom', 'rdf', 'sitemap')) ? $_GET['type'] : 'smf';

	// List all the different types of data they can pull.
	$getWhat = array(
		'all' => array('zc_xml_getRecent', true),
		'articles' => array('zc_xml_getArticles', true),
		'comments' => array('zc_xml_getComments', empty($zc['settings']['blog_xml_hide_comments'])),
		'blogs' => array('zc_xml_getSiteMap', true),
	);
	
	$_GET['get'] = isset($_GET['get']) ? $_GET['get'] : 'all';
	
	// they are obviously lost... or they just aren't allowed to do what they want to do...
	if (!isset($getWhat[$_GET['get']]) || !function_exists($getWhat[$_GET['get']][0]) || $getWhat[$_GET['get']][1] !== true)
		zcReturnToOrigin();
	
	// what do we want to get?
	if (empty($_GET['get']) || !isset($getWhat[$_GET['get']]))
		$_GET['get'] = 'all';
	
	// if $blog is empty (or we're getting the sitemap)... get all blogs this user can see...
	if (empty($blog) || $_GET['get'] == 'blogs')
	{
		// if news is set, we are getting community news (not all blogs)
		if (!isset($_REQUEST['news']) || $_GET['get'] == 'blogs')
			$blogs = zc_get_visible_blogs();
		
		// make sure this is zero...
		$blog = 0;
	}
	elseif (!empty($_GET['blogs']))
	{
		// eventually want to allow users to specify which blogs they want... then must verify they have accesss...
	}
		
	// to identify the cache with these blogs let's put $blogs into a $_GET variable...
	$_GET['blogs'] = !empty($blogs) ? implode(',', $blogs) : '';
	$_GET['blog'] = $blog;
	
	if (isset($_REQUEST['start']))
		unset($_REQUEST['start']);
	
	$first = true;
	$feed_url = $scripturl;
	if (!empty($_REQUEST))
		foreach ($_REQUEST as $key => $value)
			if (!empty($value))
			{
				$feed_url .= (!empty($first) ? '?' : ';') . $key . '=' . $value;
				$first = false;
			}
			
	// get the xml data...
	$xml = $getWhat[$_GET['get']][0]($xml_format);
	
	if ($xml_format == 'sitemap')
		$feed_title = $zcFunc['htmlspecialchars'](strip_tags($context['zc']['site_name'])) . ' - ' . $txt['b558'];
	else
	{
		$feed_title = ' - ' . strip_tags((!empty($blog_info['name']) ? $blog_info['name'] : (isset($_REQUEST['news']) ? $txt['b338'] : $txt['b51'])));
		$feed_title = $zcFunc['htmlspecialchars'](strip_tags($context['zc']['site_name'])) . (!empty($feed_title) ? $feed_title : '');
	}

	// This is an xml file....
	ob_end_clean();
	if (!empty($modSettings['enableCompressedOutput']))
		@ob_start('ob_gzhandler');
	else
		ob_start();

	if ($xml_format == 'smf' || isset($_REQUEST['debug']) || $xml_format == 'sitemap')
		header('Content-Type: text/xml; charset=' . (empty($context['character_set']) ? 'ISO-8859-1' : $context['character_set']));
	elseif ($xml_format == 'rss' || $xml_format == 'rss2')
		header('Content-Type: application/rss+xml; charset=' . (empty($context['character_set']) ? 'ISO-8859-1' : $context['character_set']));
	elseif ($xml_format == 'atom')
		header('Content-Type: application/atom+xml; charset=' . (empty($context['character_set']) ? 'ISO-8859-1' : $context['character_set']));
	elseif ($xml_format == 'rdf')
		header('Content-Type: application/rdf+xml; charset=' . (empty($context['character_set']) ? 'ISO-8859-1' : $context['character_set']));
	/*elseif ($_GET['get'] == 'blogs' && $xml_format == 'opml')
		header('Content-Type: application/xml; charset=' . (empty($context['character_set']) ? 'ISO-8859-1' : $context['character_set']));*/

	// First, output the xml header.
	echo '<?xml version="1.0" encoding="', $context['character_set'], '"?' . '>';

	// Are we outputting an rss feed or one with more information?
	if ($xml_format == 'rss' || $xml_format == 'rss2')
	{
		// Start with an RSS 2.0 header.
		echo '
<rss version=', $xml_format == 'rss2' ? '"2.0"' : '"0.92"', ' xml:lang="', strtr($txt['lang_locale'], '_', '-'), '">
	<channel>
		<title>', $feed_title, '</title>
		<link>', $scripturl, '</link>
		<description><![CDATA[', strip_tags(sprintf($txt['b293'], (!empty($blog_info['name']) ? $blog_info['name'] : $context['zc']['site_name'] . '\'s ' . (isset($_REQUEST['news']) ? $txt['b338'] : $txt['b51'])))), ']]></description>';

		// Output all of the associative array, start indenting with 2 tabs, and name everything "item".
		dumpTags($xml, 2, 'item', $xml_format);

		// Output the footer of the xml.
		echo '
	</channel>
</rss>';
	}
	elseif ($xml_format == 'atom')
	{
		echo '
<feed xmlns="http://www.w3.org/2005/Atom">
	<title>', $feed_title, '</title>
	<link rel="alternate" type="text/html" href="', $scripturl, '" />

	<updated>', gmstrftime('%Y-%m-%dT%H:%M:%SZ'), '</updated>
	<subtitle><![CDATA[', strip_tags(sprintf($txt['b293'], (!empty($blog_info['name']) ? $blog_info['name'] : $context['zc']['site_name'] . '\'s ' . $txt['b51']))), ']]></subtitle>
	<generator>zCommunity</generator>
	<id>', $feed_url . ';' . gmstrftime('%Y-%m-%dT%H:%M:%SZ'), '</id>
	<author>
		<name>', strip_tags($context['zc']['site_name']), '</name>
	</author>';

		dumpTags($xml, 2, 'entry', $xml_format);

		echo '
</feed>';
	}
	elseif ($xml_format == 'rdf')
	{
		echo '
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns="http://purl.org/rss/1.0/">
	<channel rdf:about="', $scripturl, '">
		<title>', $feed_title, '</title>
		<link>', $scripturl, '</link>
		<description><![CDATA[', strip_tags(sprintf($txt['b293'], (!empty($blog_info['name']) ? $blog_info['name'] : $context['zc']['site_name'] . '\'s ' . $txt['b51']))), ']]></description>
		<items>
			<rdf:Seq>';

		foreach ($xml as $item)
			echo '
				<rdf:li rdf:resource="', $item['link'], '" />';

		echo '
			</rdf:Seq>
		</items>
	</channel>
';

		dumpTags($xml, 1, 'item', $xml_format);

		echo '
</rdf:RDF>';
	}
	// sitemap?
	elseif ($xml_format == 'sitemap')
	{
		echo '
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
	<url>
		<loc>' . $scripturl . (!empty($context['zc']['zCommunity_is_home']) ? '' : '?zc') .'</loc>
		<changefreq>daily</changefreq>
		<priority>1.0</priority>
	</url>';
	
	if (!empty($context['zc']['zCommunity_is_home']))
		echo '
	<url>
		<loc>', $scripturl, '?action=forum</loc>
		<changefreq>daily</changefreq>
		<priority>1.0</priority>
	</url>';

		// Dump out that associative array.  Indent properly.... and use the right names for the base elements.
		dumpTags($xml, 1, 'url', $xml_format);
		
		echo '
</urlset>';
	}
	// Otherwise, we're using our proprietary formats - they give more data, though.
	else
	{
		echo '
<smf:xml-feed xmlns:smf="http://www.simplemachines.org/" xmlns="http://www.simplemachines.org/xml/', $_GET['get'], '" xml:lang="', strtr($txt['lang_locale'], '_', '-'), '">';

		// Dump out that associative array.  Indent properly.... and use the right names for the base elements.
		dumpTags($xml, 1, $getWhat[$_GET['get']][1], $xml_format);

		echo '
</smf:xml-feed>';
}

	zc_ob_exit(false);
}

function fix_possible_url($val)
{
	global $modSettings, $context, $scripturl;

	if (substr($val, 0, strlen($scripturl)) != $scripturl)
		return $val;

	if (isset($modSettings['integrate_fix_url']) && function_exists($modSettings['integrate_fix_url']))
		$val = call_user_func($modSettings['integrate_fix_url'], $val);

	if (empty($modSettings['queryless_urls']) || ($context['server']['is_cgi'] && @ini_get('cgi.fix_pathinfo') == 0) || !$context['server']['is_apache'])
		return $val;

	$val = preg_replace('/^' . preg_quote($scripturl, '/') . '\?((?:board|topic)=[^#"]+)(#[^"]*)?$/e', "'' . \$scripturl . '/' . strtr('\$1', '&;=', '//,') . '.html\$2'", $val);
	return $val;
}

function cdata_parse($data, $ns = '')
{
	$cdata = '<![CDATA[';

	for ($pos = 0, $n = strlen($data); $pos < $n; null)
	{
		$positions = array(
			strpos($data, '&', $pos),
			strpos($data, ']', $pos),
		);
		if ($ns != '')
			$positions[] = strpos($data, '<', $pos);
		foreach ($positions as $k => $dummy)
		{
			if ($dummy === false)
				unset($positions[$k]);
		}

		$old = $pos;
		$pos = empty($positions) ? $n : min($positions);

		if ($pos - $old > 0)
			$cdata .= substr($data, $old, $pos - $old);
		if ($pos >= $n)
			break;

		if (substr($data, $pos, 1) == '<')
		{
			$pos2 = strpos($data, '>', $pos);
			if ($pos2 === false)
				$pos2 = $n;
			if (substr($data, $pos + 1, 1) == '/')
				$cdata .= ']]></' . $ns . ':' . substr($data, $pos + 2, $pos2 - $pos - 1) . '<![CDATA[';
			else
				$cdata .= ']]><' . $ns . ':' . substr($data, $pos + 1, $pos2 - $pos) . '<![CDATA[';
			$pos = $pos2 + 1;
		}
		elseif (substr($data, $pos, 1) == ']')
		{
			$cdata .= ']]>&#093;<![CDATA[';
			$pos++;
		}
		elseif (substr($data, $pos, 1) == '&')
		{
			$pos2 = strpos($data, ';', $pos);
			if ($pos2 === false)
				$pos2 = $n;
			$ent = substr($data, $pos + 1, $pos2 - $pos - 1);

			if (substr($data, $pos + 1, 1) == '#')
				$cdata .= ']]>' . substr($data, $pos, $pos2 - $pos + 1) . '<![CDATA[';
			elseif (in_array($ent, array('amp', 'lt', 'gt', 'quot')))
				$cdata .= ']]>' . substr($data, $pos, $pos2 - $pos + 1) . '<![CDATA[';
			// !!! ??

			$pos = $pos2 + 1;
		}
	}

	$cdata .= ']]>';

	return strtr($cdata, array('<![CDATA[]]>' => ''));
}

function dumpTags($data, $i, $tag = null, $xml_format = '')
{
	global $modSettings, $context, $scripturl;

	// For every array in the data...
	foreach ($data as $key => $val)
	{
		// Skip it, it's been set to null.
		if ($val == null)
			continue;

		// If a tag was passed, use it instead of the key.
		$key = isset($tag) ? $tag : $key;

		// First let's indent!
		echo "\n", str_repeat("\t", $i);

		// Grr, I hate kludges... almost worth doing it properly, here, but not quite.
		if ($xml_format == 'atom' && $key == 'link')
		{
			echo '<link rel="alternate" type="text/html" href="', fix_possible_url($val), '" />';
			continue;
		}

		// If it's empty/0/nothing simply output an empty tag.
		if ($val == '')
			echo '<', $key, ' />';
		else
		{
			// Beginning tag.
			if ($xml_format == 'rdf' && $key == 'item' && isset($val['link']))
			{
				echo '<', $key, ' rdf:about="', fix_possible_url($val['link']), '">';
				echo "\n", str_repeat("\t", $i + 1);
				echo '<dc:format>text/html</dc:format>';
			}
			elseif ($xml_format == 'atom' && $key == 'summary')
				echo '<', $key, ' type="html">';
			else
				echo '<', $key, '>';

			if (is_array($val))
			{
				// An array.  Dump it, and then indent the tag.
				dumpTags($val, $i + 1, null, $xml_format);
				echo "\n", str_repeat("\t", $i), '</', $key, '>';
			}
			// A string with returns in it.... show this as a multiline element.
			elseif (strpos($val, "\n") !== false || strpos($val, '<br />') !== false)
				echo "\n", fix_possible_url($val), "\n", str_repeat("\t", $i), '</', $key, '>';
			// A simple string.
			else
				echo fix_possible_url($val), '</', $key, '>';
		}
	}
}

function zc_xml_getSiteMap($xml_format)
{
	global $blog, $zc;
	
	// $_GET['blogs'] cannot be empty
	if (empty($_GET['blogs']))
		return array();
		
	$items = zc_xml_get_items();
	
	$data = zc_xml_format_items_to_data($xml_format, $items);

	return $data;
}

function zc_xml_getArticles($xml_format)
{
	global $blog, $zc;
	
	// both $blog and $_GET['blogs'] cannot be empty if !isset($_REQUES['news']) !
	if (empty($_GET['blogs']) && empty($blog) && !isset($_REQUEST['news']))
		return array();
		
	$posts = zc_xml_get_articles();
	
	$data = zc_xml_format_posts_to_data($xml_format, $posts);

	return $data;
}

function zc_xml_getComments($xml_format)
{
	global $blog, $zc;
	
	// both $blog and $_GET['blogs'] cannot be empty if !isset($_REQUES['news']) !
	if (empty($_GET['blogs']) && empty($blog) && !isset($_REQUEST['news']))
		return array();
		
	$posts = empty($zc['settings']['blog_xml_hide_comments']) ? zc_xml_get_comments() : array();
	
	$data = zc_xml_format_posts_to_data($xml_format, $posts);

	return $data;
}

function zc_xml_getRecent($xml_format)
{
	global $blog, $zc;
	
	// both $blog and $_GET['blogs'] cannot be empty if !isset($_REQUES['news']) !
	if (empty($_GET['blogs']) && empty($blog) && !isset($_REQUEST['news']))
		return array();
		
	$articles = zc_xml_get_articles();
	
	$comments = empty($zc['settings']['blog_xml_hide_comments']) ? zc_xml_get_comments() : array();
	
	$posts = array_merge($articles, $comments);
	
	// sort posts by key (posted_time)
	krsort($posts);
	
	$data = zc_xml_format_posts_to_data($xml_format, $posts);

	return $data;
}

function zc_xml_get_comments()
{
	global $zcFunc, $blog, $zc, $txt, $scripturl;
	
	// get all the comments this user can see...
	$request = $zcFunc['db_query']("
		SELECT c.comment_id, c.body, IFNULL(mem.{tbl:members::column:real_name}, c.poster_name) AS poster_name, c.posted_time, c.poster_id, c.smileys_enabled, c.last_edit_name, c.last_edit_time, c.poster_email, mem.{tbl:members::column:hide_email} AS hide_email, b.name AS blog_name, b.blog_id, t.subject, t.article_id
		FROM {db_prefix}comments AS c
			LEFT JOIN {db_prefix}{table:members} AS mem ON (mem.{tbl:members::column:id_member} = c.poster_id)
			LEFT JOIN {db_prefix}blogs AS b ON (b.blog_id = c.blog_id)
			LEFT JOIN {db_prefix}articles AS t ON (t.article_id = c.article_id)
			LEFT JOIN {db_prefix}settings AS bs ON (bs.blog_id = c.blog_id)
		WHERE c.blog_id " . (empty($_GET['blogs']) ? "= {int:blog_id}" : "IN ({array_int:blogs})") . "
			AND ((c.approved = 1) OR (bs.comments_require_approval = 0))
		ORDER BY c.comment_id DESC" . (!empty($zc['settings']['blog_xml_max_num_comments']) ? "
		LIMIT {int:limit}" : ''), __FILE__, __LINE__,
		array(
			'limit' => !empty($zc['settings']['blog_xml_max_num_comments']) ? $zc['settings']['blog_xml_max_num_comments'] : 0,
			'blog_id' => !empty($blog) ? $blog : 0,
			'blogs' => !empty($_GET['blogs']) ? explode(',', $_GET['blogs']) : array(),
		)
	);
	
	$posts = array();
	while ($row = $zcFunc['db_fetch_assoc']($request))
	{
		$row['body'] = $zcFunc['un_htmlspecialchars']($row['body']);
		$row['subject'] = $zcFunc['un_htmlspecialchars']($row['subject']);
		$row['poster_name'] = $zcFunc['un_htmlspecialchars']($row['poster_name']);
		$row['last_edit_name'] = $zcFunc['un_htmlspecialchars']($row['last_edit_name']);
		$row['blog_name'] = $zcFunc['un_htmlspecialchars']($row['blog_name']);
		
		zc_censor_text($row['subject']);
		zc_censor_text($row['body']);
		
		// gotta do this before shortening the body text
		$row['body'] = $zcFunc['parse_bbc']($row['body'], $row['smileys_enabled']);
	
		// if the article is too long... chop it and add a [ ... ] (read more) link at the end
		if (!empty($zc['settings']['blog_xml_article_maxlen']))
			$row['body'] = zcTruncateText($row['body'], $zc['settings']['blog_xml_article_maxlen'], ' ', 40, $txt['b31a'], $scripturl . '?article='. $row['article_id'] . '.0', $txt['b31']);
			
		$posts[$row['posted_time'] . 'c' . $row['comment_id']] = array(
			'id' => $row['comment_id'],
			'article_id' => $row['article_id'],
			'subject' => cdata_parse($row['subject']),
			'posted_time' => $row['posted_time'],
			'last_edit_time' => $row['last_edit_time'],
			'last_edit_name' => cdata_parse($row['last_edit_name']),
			'body' => cdata_parse($row['body']),
			'href' => $scripturl . '?article=' . $row['article_id'] .'.0#c' . $row['comment_id'],
			'poster_name' => cdata_parse($row['poster_name']),
			'poster_id' => $row['poster_id'],
			'first_poster_id' => $row['poster_id'],
			'first_poster_name' => cdata_parse($row['poster_name']),
			'poster_email' => $row['poster_email'],
			'hide_email' => $row['hide_email'],
			'blog' => array(
				'name' => cdata_parse($row['blog_name']),
				'id' => $row['blog_id'],
				'href' => $scripturl . '?blog=' . $row['blog_id'] .'.0',
			),
		);
	}
	$zcFunc['db_free_result']($request);
	
	return $posts;
}

function zc_xml_get_articles()
{
	global $txt, $scripturl;
	global $zcFunc, $blog, $zc;
	
	// get all the articles this user can see...
	$request = $zcFunc['db_query']("
		SELECT t.article_id, t.subject, t.body, IFNULL(mem.{tbl:members::column:real_name}, t.poster_name) AS poster_name, t.posted_time, t.poster_id, t.smileys_enabled, t.last_edit_name, t.last_edit_time, t.poster_email, mem.{tbl:members::column:hide_email} AS hide_email, b.name AS blog_name, b.blog_id
		FROM {db_prefix}articles AS t
			LEFT JOIN {db_prefix}{table:members} AS mem ON (mem.{tbl:members::column:id_member} = t.poster_id)
			LEFT JOIN {db_prefix}blogs AS b ON (b.blog_id = t.blog_id)
			LEFT JOIN {db_prefix}settings AS bs ON (bs.blog_id = t.blog_id)
		WHERE t.blog_id " . (empty($_GET['blogs']) ? "= {int:blog_id}" : "IN ({array_int:blogs})") . "
			AND ((t.approved = 1) OR (bs.articles_require_approval = 0))
		ORDER BY t.article_id DESC" . (!empty($zc['settings']['blog_xml_max_num_articles']) ? "
		LIMIT {int:limit}" : ''), __FILE__, __LINE__,
		array(
			'limit' => !empty($zc['settings']['blog_xml_max_num_articles']) ? $zc['settings']['blog_xml_max_num_articles'] : 0,
			'blog_id' => !empty($blog) ? $blog : 0,
			'blogs' => !empty($_GET['blogs']) ? explode(',', $_GET['blogs']) : array(),
		)
	);
	
	$posts = array();
	while ($row = $zcFunc['db_fetch_assoc']($request))
	{
		$row['body'] = $zcFunc['un_htmlspecialchars']($row['body']);
		$row['subject'] = $zcFunc['un_htmlspecialchars']($row['subject']);
		$row['poster_name'] = $zcFunc['un_htmlspecialchars']($row['poster_name']);
		$row['last_edit_name'] = $zcFunc['un_htmlspecialchars']($row['last_edit_name']);
		$row['blog_name'] = $zcFunc['un_htmlspecialchars']($row['blog_name']);
		
		zc_censor_text($row['subject']);
		zc_censor_text($row['body']);
		
		// gotta do this before shortening the body text
		$row['body'] = $zcFunc['parse_bbc']($row['body'], $row['smileys_enabled']);
	
		// if the article is too long... chop it and add a [ ... ] (read more) link at the end
		if (!empty($zc['settings']['blog_xml_article_maxlen']))
			$row['body'] = zcTruncateText($row['body'], $zc['settings']['blog_xml_article_maxlen'], ' ', 40, $txt['b31a'], $scripturl . '?article='. $row['article_id'] . '.0', $txt['b31']);
			
		$posts[$row['posted_time'] . 'a' . $row['article_id']] = array(
			'id' => $row['article_id'],
			'article_id' => $row['article_id'],
			'has_comments' => !empty($row['num_comments']),
			'subject' => cdata_parse($row['subject']),
			'posted_time' => $row['posted_time'],
			'last_edit_time' => $row['last_edit_time'],
			'last_edit_name' => cdata_parse($row['last_edit_name']),
			'body' => cdata_parse($row['body']),
			'href' => $scripturl . '?article=' . $row['article_id'] .'.0',
			'poster_name' => cdata_parse($row['poster_name']),
			'poster_id' => $row['poster_id'],
			'first_poster_id' => $row['poster_id'],
			'first_poster_name' => cdata_parse($row['poster_name']),
			'poster_email' => $row['poster_email'],
			'hide_email' => $row['hide_email'],
			'blog' => array(
				'name' => cdata_parse($row['blog_name']),
				'id' => $row['blog_id'],
				'href' => $scripturl . '?blog=' . $row['blog_id'] .'.0',
			),
		);
	}
	$zcFunc['db_free_result']($request);
	
	return $posts;
}

function zc_xml_get_items()
{
	global $txt, $scripturl;
	global $zcFunc, $zc;
	
	// they can't see any blogs....
	if (empty($_GET['blogs']))
		return array();
		
	$num_blogs = count(explode(',', $_GET['blogs']));
	
	// get all the blogs this user can see...
	$request = $zcFunc['db_query']("
		SELECT b.blog_id, b.num_articles, b.num_comments, t.posted_time AS article_last_post_time, c.posted_time AS comment_last_post_time
		FROM {db_prefix}blogs AS b
			LEFT JOIN {db_prefix}articles AS t ON (t.article_id = b.last_article_id)
			LEFT JOIN {db_prefix}comments AS c ON (c.comment_id = b.last_comment_id)
		WHERE b.blog_id IN ({array_int:blogs})
		ORDER BY b.num_views DESC
		LIMIT {int:limit}", __FILE__, __LINE__,
		array(
			'limit' => $num_blogs,
			'blogs' => !empty($_GET['blogs']) ? explode(',', $_GET['blogs']) : array(),
		)
	);
	
	$items = array();
	while ($row = $zcFunc['db_fetch_assoc']($request))
	{
		$row['article_last_post_time'] = !empty($row['article_last_post_time']) ? $row['article_last_post_time'] : 0;
		$row['comment_last_post_time'] = !empty($row['comment_last_post_time']) ? $row['comment_last_post_time'] : 0;
		$last_edit_time = max($row['article_last_post_time'], $row['comment_last_post_time']);
		
		$items['b' . $row['blog_id']] = array(
			'id' => $row['blog_id'],
			'changefreq' => 'daily',
			'href' => $scripturl . '?blog=' . $row['blog_id'] .'.0',
			'last_edit_time' => $last_edit_time,
			'priority' => '0.9',
		);
	}
	$zcFunc['db_free_result']($request);
	
	if (isset($row))
		unset($row);
	
	// get all the articles this user can see...
	$request = $zcFunc['db_query']("
		SELECT t.article_id, t.posted_time AS article_last_post_time, c.posted_time AS comment_last_post_time
		FROM {db_prefix}articles AS t
			LEFT JOIN {db_prefix}comments AS c ON (c.comment_id = t.last_comment_id)
		WHERE t.blog_id IN ({array_int:blogs})
		ORDER BY t.article_id DESC", __FILE__, __LINE__,
		array(
			'blogs' => !empty($_GET['blogs']) ? explode(',', $_GET['blogs']) : array(),
		)
	);
	
	while ($row = $zcFunc['db_fetch_assoc']($request))
	{
		$row['article_last_post_time'] = !empty($row['article_last_post_time']) ? $row['article_last_post_time'] : 0;
		$row['comment_last_post_time'] = !empty($row['comment_last_post_time']) ? $row['comment_last_post_time'] : 0;
		$last_edit_time = max($row['article_last_post_time'], $row['comment_last_post_time']);
		
		$items['a' . $row['article_id']] = array(
			'id' => $row['article_id'],
			'changefreq' => 'daily',
			'href' => $scripturl . '?article=' . $row['article_id'] .'.0',
			'last_edit_time' => $last_edit_time,
			'priority' => '0.8',
		);
	}
	$zcFunc['db_free_result']($request);
	
	return $items;
}

function zc_xml_format_items_to_data($xml_format, $items)
{
	global $scripturl;

	// cannot be empty...
	if (empty($items))
		return array();
	
	$data = array();
	// this'll format the data depending upon the xml_format...
	foreach ($items as $item)
	{
		if ($xml_format == 'sitemap')
			$data[] = array(
				'loc' => $item['href'],
				'lastmod' => !empty($item['last_edit_time']) ? date('Y-m-d', $item['last_edit_time']) : '',
				'changefreq' => $item['changefreq'],
				'priority' => $item['priority'],
			);
	}

	return $data;
}

function zc_xml_format_posts_to_data($xml_format, $posts)
{
	global $modSettings, $scripturl;

	// cannot be empty...
	if (empty($posts))
		return array();

	$data = array();
	// this'll format the data depending upon the xml_format...
	foreach ($posts as $post)
	{
		// Doesn't work as well as news, but it kinda does..
		if ($xml_format == 'rss' || $xml_format == 'rss2')
			$data[] = array(
				'title' => $post['subject'],
				'link' => $post['href'],
				'description' => $post['body'],
				'author' => (!empty($modSettings['guest_hideContacts']) && $context['user']['is_guest']) || (!empty($post['hide_email']) && !empty($modSettings['allow_hide_email']) && !$context['user']['is_admin']) ? null : $post['poster_email'],
				'category' => $post['blog']['name'],
				'comments' => $post['href'],
				'pubDate' => gmdate('D, d M Y H:i:s \G\M\T', $post['posted_time']),
				'guid' => $post['href'],
			);
		elseif ($xml_format == 'rdf')
			$data[] = array(
				'title' => $post['subject'],
				'link' => $post['href'],
				'description' => $post['body'],
			);
		elseif ($xml_format == 'atom')
			$data[] = array(
				'title' => $post['subject'],
				'link' => $post['href'],
				'summary' => $post['body'],
				'author' => array('name' => $post['poster_name']),
				'published' => gmstrftime('%Y-%m-%dT%H:%M:%SZ', $post['posted_time']),
				//'issued' => gmstrftime('%Y-%m-%dT%H:%M:%SZ', $post['posted_time']),
				'updated' => gmstrftime('%Y-%m-%dT%H:%M:%SZ', empty($post['last_edit_time']) ? $post['posted_time'] : $post['last_edit_time']),
				'id' => $post['href'],
			);
		// A lot of information here.  Should be enough to please the rss-ers.
		else
			$data[] = array(
				'time' => strip_tags(timeformat($post['posted_time'])),
				'id' => $post['id'],
				'subject' => $post['subject'],
				'body' => $post['body'],
				'starter' => array(
					'name' => $post['first_poster_name'],
					'id' => $post['first_poster_id'],
					'link' => !empty($post['first_poster_id']) ? $scripturl . '?action=profile;u=' . $post['first_poster_id'] : ''
				),
				'poster' => array(
					'name' => $post['poster_name'],
					'id' => $post['poster_id'],
					'link' => !empty($row['poster_id']) ? $scripturl . '?action=profile;u=' . $post['poster_id'] : ''
				),
				'topic' => array(
					'subject' => $post['subject'],
					'id' => $post['article_id'],
					'link' => $post['href'],
				),
				'board' => array(
					'name' => $post['blog']['name'],
					'id' => $post['blog_id'],
					'link' => $post['blog']['href'],
				),
				'link' => $post['href'],
			);
	}
	return $data;
}


?>