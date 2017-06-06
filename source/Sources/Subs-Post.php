<?php

if (!defined('zc'))
	die('Hacking attempt...');

function zc_prepare_article_form_array()
{
	global $context, $txt, $scripturl, $blog, $zc;
		
	if (!empty($context['user']['blog_preferences']['show_bbc_cloud']) || (!empty($zc['settings']['show_bbc_cloud_for_guests']) && $context['user']['is_guest']))
		zc_prepare_bbc_tags_array();
	
	$temp = array(
		'_info_' => array(
			'template_info' => array(
				'left_column_width' => '20%',
				'right_column_width' => '80%',
			),
			'additional_submit_buttons' => array(
				'preview' => $txt['b159'],
				'save_as_draft' => $txt['b160'],
			),
			'primary_submit_text' => $txt['b320'],
			'table_info' => array(
				'unprefixed_name' => 'articles',
				'id_column' => 'article_id',
			),
			'exclude_from_table' => array(
				'no_count_last_edit',
			),
		),
		'poster_name' => array(
			'type' => 'text',
			'value' => '',
			'label' => 'b180',
			'no_template' => (!$context['user']['is_guest'] || !empty($context['zc']['editing_something'])) && (!empty($context['zc']['current_info']) || !empty($context['zc']['current_info']['poster_id'])),
			'must_return_true' => ($context['user']['is_guest'] && empty($context['zc']['editing_something'])) || (!empty($context['zc']['current_info']) && empty($context['zc']['current_info']['poster_id'])),
			'required' => true,
		),
		'poster_email' => array(
			'type' => 'text',
			'value' => '',
			'label' => 'b179',
			'no_template' => (!$context['user']['is_guest'] || !empty($context['zc']['editing_something'])) && (!empty($context['zc']['current_info']) || !empty($context['zc']['current_info']['poster_id'])),
			'must_return_true' => ($context['user']['is_guest'] && empty($context['zc']['editing_something'])) || (!empty($context['zc']['current_info']) && empty($context['zc']['current_info']['poster_id'])),
			'required' => true,
		),
		'subject' => array(
			'type' => 'text',
			'value' => '',
			'max_length' => 100,
			'label' => 'b3032',
			'required' => true,
			'use_substr_to_shorten' => true,
			'field_width' => '240px',
			'chop_words' => true,
		),
		'body' => array(
			'type' => 'text',
			'custom' => 'textarea',
			'value' => '',
			'max_length' => 12000,
			'parses_bbc' => true,
			'label' => 'b173',
			'required' => true,
			'ta_rows' => 20,
			'template_above_field' => !empty($context['user']['blog_preferences']['show_bbc_cloud']) || !empty($zc['settings']['show_bbc_cloud_for_guests']) ? 'bbcCloud' : '',
			'chop_words' => true,
		),
		'blog_tags' => array(
			'type' => 'text',
			'value' => '',
			'extra' => 'comma_separated_list',
			'max_length_per_list_item' => 42,
			'label' => 'b26a',
			'clear_all_option' => true,
			'field_width' => '240px',
			'template_above_field' => 'tagCloud',
			'must_return_true' => !empty($blog),
			'chop_words' => true,
		),
		'blog_category_id' => array(
			'type' => 'int',
			'custom' => 'select',
			'options' => array(0 => ''),
			'value' => '',
			'label' => 'b16',
			'must_return_true' => !empty($blog),
			'show_beside_field' => !empty($blog) && (!empty($context['is_blog_owner']) || !empty($context['can_access_any_blog_control_panel'])) ? '<a href="' . $scripturl . '?zc=bcp;blog=' . $blog . '.0;sa=categories" title="' . $txt['b549'] . '"><img src="' . $context['zc']['default_images_url'] . '/icons/category_icon.gif" alt="' . $txt['b549'] . '" /></a>' : '',
		),
		'smileys_enabled' => array(
			'type' => 'check',
			'value' => 1,
			'label' => 'b181',
		),
		'locked' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b182',
			'no_template' => !isset($context['zc']['can_lock_this']) || ($context['zc']['can_lock_this'] !== true),
			'must_return_true' => isset($context['zc']['can_lock_this']) && ($context['zc']['can_lock_this'] === true),
		),
		'no_count_last_edit' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b672',
			'no_template' => !$context['user']['is_admin'] || empty($context['zc']['editing_something']),
			'must_return_true' => $context['user']['is_admin'] && !empty($context['zc']['editing_something']),
		),
		'access_restrict' => array(
			'type' => 'int',
			'value' => 0,
			'label' => '',
			'custom' => 'radio',
			'options' => array(
				'b25',
				'b682',
				'b683',
				//'b684',
			),
		),
		/*'users_allowed' => array(
			'type' => 'text',
			'value' => '',
			'extra' => 'comma_separated_list',
			'label' => '',
			'clear_all_option' => true,
			'field_width' => '240px',
			'disable_options' => array(
				// if false, it is ignored...
				false,
				false,
				// key 0 is the field this option is dependent upon
				// key 1 is the value of that field when this option becomes disabled
				// key 2 is the value of that field when this option becomes enabled
				array('access_restrict', '', '3'),
			),
		),*/
		/*'is_sticky' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b563',
			'no_template' => !$context['can_make_articles_sticky'],
			'must_return_true' => $context['can_make_articles_sticky'],
		),*/
		/*'attachments' => array(
			'type' => 'file',
			'value' => '',
			'minimum_num_fields' => 0,
			'max_num_fields' => 4,
			'custom' => 'multi_field',
			'add_field_option' => true,
			'label' => 'Attachments',
			'dir' => $zc['settings']['attachments_dir'],
			'allowed_file_extensions' => array('txt'),
		),*/
	);
		
	// we'll need this for the ( More Attachments )
	$context['zc']['raw_javascript'][] = '
var num_fields = 0;
function addField(key, inputType, maxNumFields)
{
	num_fields++;
	
	if ((maxNumFields == 0) || (num_fields < maxNumFields))
	{
		var more_options_html = \'<br /><input type="\' + inputType + \'" name="\' + key + \'[]" value="" /><span id="more_\' + key + \'"></span>\';
		setOuterHTML(document.getElementById("more_" + key), more_options_html);
	}
}';
	
	// load this blog's categories.....
	if (!empty($blog))
		$categories = zcLoadBlogCategories();
	
	// populate the blog_category_id options array...
	if (!empty($categories))
		foreach ($categories as $id => $category)
			$temp['blog_category_id']['options'][$id] = $category['name'];
			
	return $temp;
}

function zc_prepare_comment_form_array()
{
	global $context, $txt, $blog, $article, $zc;
		
	if (!empty($context['user']['blog_preferences']['show_bbc_cloud']) || !empty($zc['settings']['show_bbc_cloud_for_guests']))
		zc_prepare_bbc_tags_array();
	
	$temp = array(
		'_info_' => array(
			'template_info' => array(
				'left_column_width' => '25%',
				'right_column_width' => '75%',
			),
			'primary_submit_text' => $txt['b158'],
			'additional_submit_buttons' => array(
				'preview' => $txt['b159'],
				'save_as_draft' => $txt['b160'],
			),
			'table_info' => array(
				'unprefixed_name' => 'comments',
				'id_column' => 'comment_id',
			),
			'exclude_from_table' => array(
				'no_count_last_edit',
			),
		),
		'poster_name' => array(
			'type' => 'text',
			'value' => '',
			'label' => 'b180',
			'no_template' => !$context['user']['is_guest'] && (!empty($context['zc']['editing_something']) || !empty($context['zc']['current_info']['poster_id'])),
			'must_return_true' => $context['user']['is_guest'] && empty($context['zc']['editing_something']) || (!empty($context['zc']['current_info']) && empty($context['zc']['current_info']['poster_id'])),
			'required' => true,
		),
		'poster_email' => array(
			'type' => 'text',
			'value' => '',
			'label' => 'b179',
			'no_template' => !$context['user']['is_guest'] && (!empty($context['zc']['editing_something']) || !empty($context['zc']['current_info']['poster_id'])),
			'must_return_true' => $context['user']['is_guest'] && empty($context['zc']['editing_something']) || (!empty($context['zc']['current_info']) && empty($context['zc']['current_info']['poster_id'])),
			'required' => true,
		),
		'body' => array(
			'type' => 'text',
			'custom' => 'textarea',
			'value' => '',
			'max_length' => 1000,
			'parses_bbc' => true,
			'ta_rows' => 10,
			'label' => 'b173',
			'required' => true,
			'chop_words' => true,
			'template_above_field' => !empty($context['user']['blog_preferences']['show_bbc_cloud']) || !empty($zc['settings']['show_bbc_cloud_for_guests']) ? 'bbcCloud' : '',
		),
		'smileys_enabled' => array(
			'type' => 'check',
			'value' => 1,
			'label' => 'b181',
		),
		'no_count_last_edit' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b672',
			'no_template' => !$context['user']['is_admin'] || empty($context['zc']['editing_something']),
			'must_return_true' => $context['user']['is_admin'] && !empty($context['zc']['editing_something']),
		),
	);
	return $temp;
}

function zc_prepare_poll_form_array()
{
	global $context, $txt, $blog, $article;
	
	$temp = array(
		'_info_' => array(
			'template_info' => array(
				'left_column_width' => '40%',
				'right_column_width' => '60%',
			),
			'exclude_from_table' => array(
				'choices',
				'expire_time_hidden',
			),
			'primary_submit_text' => $txt['b158'],
			/*'additional_submit_buttons' => array(
				'preview' => $txt['b159'],
			),*/
			'table_info' => array(
				'unprefixed_name' => 'polls',
				'id_column' => 'poll_id',
			),
		),
		'question' => array(
			'type' => 'text',
			'value' => '',
			'label' => 'b190',
			'required' => true,
			'field_width' => '300px',
			'max_length' => 150,
			'chop_words' => true,
		),
		'choices' => array(
			'type' => 'text',
			'value' => '',
			'minimum_num_fields' => 2,
			'max_num_fields' => 256,
			'custom' => 'multi_field',
			'add_field_option' => true,
			'label' => 'b191a',
			'chop_words' => true,
		),
		'max_votes' => array(
			'type' => 'int',
			'value' => 1,
			'label' => 'b196',
			'field_width' => '30px',
		),
		'change_vote' => array(
			'type' => 'check',
			'value' => 0,
			'label' => 'b193',
		),
		'expire_time' => array(
			'type' => 'float',
			'value' => '',
			'label' => 'b194',
			'field_width' => '30px',
			'disable_others' => array(
				// id of input field to disable => value THIS field must equal to disable the target input field
				'hide_results2' => '',
			),
			'show_below_field' => !empty($context['zc']['poll_expire_time']) ? $context['zc']['poll_expire_time'] : '',
		),
		'expire_time_hidden' => array(
			'type' => 'float',
			'custom' => 'hidden',
			'value' => !empty($context['zc']['current_info']['expire_time']) ? $context['zc']['current_info']['expire_time'] : 0,
		),
		'hide_results' => array(
			'type' => 'int',
			'value' => 0,
			'label' => '',
			'custom' => 'radio',
			'options' => array(
				'b197',
				'b198',
				'b199',
			),
			'disable_options' => array(
				// if false, it is ignored...
				false,
				false,
				// key 0 is the field this option is dependent upon
				// key 1 is the value of that field when this option becomes disabled
				array('expire_time', ''),
			),
		),
	);
		
	// we'll need this for the ( More Options )
	$context['zc']['raw_javascript'][] = '
var num_fields = 0;
function addField(key, inputType, maxNumFields)
{
	num_fields++;
	
	if ((maxNumFields == 0) || (num_fields < maxNumFields))
	{
		var more_options_html = \'<br /><input type="\' + inputType + \'" name="\' + key + \'[]" value="" /><span id="more_\' + key + \'"></span>\';
		setOuterHTML(document.getElementById("more_" + key), more_options_html);
	}
}';
	return $temp;
}

function zc_prepare_bbc_tags_array()
{
	global $context, $txt, $modSettings, $settings;
	
	// load Post lang file
	zcLoadLanguage('Post');

	$context['zc']['show_bbc'] = !empty($modSettings['enableBBC']) && !empty($settings['show_bbc']);
	
	if (empty($context['zc']['show_bbc']))
		return false;
		
	$context['zc']['raw_javascript'][] = '
		function bbc_highlight(something, mode)
		{
			something.style.backgroundImage = "url(" + smf_images_url + (mode ? "/bbc/bbc_hoverbg.gif)" : "/bbc/bbc_bg.gif)");
		}';

	// Generate a list of buttons that shouldn't be shown - this should be the fastest way to do this.
	if (!empty($modSettings['disabledBBC']))
	{
		$disabled_tags = explode(',', $modSettings['disabledBBC']);
		$context['zc']['disabled_tags'] = array();
		foreach ($disabled_tags as $tag)
			$context['zc']['disabled_tags'][trim($tag)] = true;
	}
	
	$context['zc']['bbc_tags'] = array();
	$context['zc']['bbc_tags'][] = array(
		'bold' => array('code' => 'b', 'before' => '[b]', 'after' => '[/b]', 'description' => $txt['b616']),
		'italicize' => array('code' => 'i', 'before' => '[i]', 'after' => '[/i]', 'description' => $txt['b617']),
		'underline' => array('code' => 'u', 'before' => '[u]', 'after' => '[/u]', 'description' => $txt['b618']),
		'strike' => array('code' => 's', 'before' => '[s]', 'after' => '[/s]', 'description' => $txt['b653']),
		array(),
		'glow' => array('code' => 'glow', 'before' => '[glow=red,2,300]', 'after' => '[/glow]', 'description' => $txt['b654']),
		'shadow' => array('code' => 'shadow', 'before' => '[shadow=red,left]', 'after' => '[/shadow]', 'description' => $txt['b655']),
		'move' => array('code' => 'move', 'before' => '[move]', 'after' => '[/move]', 'description' => $txt['b651']),
		array(),
		'pre' => array('code' => 'pre', 'before' => '[pre]', 'after' => '[/pre]', 'description' => $txt['b656']),
		'left' => array('code' => 'left', 'before' => '[left]', 'after' => '[/left]', 'description' => $txt['b657']),
		'center' => array('code' => 'center', 'before' => '[center]', 'after' => '[/center]', 'description' => $txt['b619']),
		'right' => array('code' => 'right', 'before' => '[right]', 'after' => '[/right]', 'description' => $txt['b658']),
		array(),
		'hr' => array('code' => 'hr', 'before' => '[hr]', 'description' => $txt['b662']),
		array(),
		'size' => array('code' => 'size', 'before' => '[size=10pt]', 'after' => '[/size]', 'description' => $txt['b663']),
		'face' => array('code' => 'font', 'before' => '[font=Verdana]', 'after' => '[/font]', 'description' => $txt['b664']),
	);
	$context['zc']['bbc_tags'][] = array(
		'flash' => array('code' => 'flash', 'before' => '[flash=200,200]', 'after' => '[/flash]', 'description' => $txt['b646']),
		'img' => array('code' => 'img', 'before' => '[img]', 'after' => '[/img]', 'description' => $txt['b648']),
		'url' => array('code' => 'url', 'before' => '[url]', 'after' => '[/url]', 'description' => $txt['b620']),
		'email' => array('code' => 'email', 'before' => '[email]', 'after' => '[/email]', 'description' => $txt['b621']),
		'ftp' => array('code' => 'ftp', 'before' => '[ftp]', 'after' => '[/ftp]', 'description' => $txt['b647']),
		array(),
		'table' => array('code' => 'table', 'before' => '[table]', 'after' => '[/table]', 'description' => $txt['b649']),
		'tr' => array('code' => 'td', 'before' => '[tr]', 'after' => '[/tr]', 'description' => $txt['b661']),
		'td' => array('code' => 'td', 'before' => '[td]', 'after' => '[/td]', 'description' => $txt['b650']),
		array(),
		'sup' => array('code' => 'sup', 'before' => '[sup]', 'after' => '[/sup]', 'description' => $txt['b659']),
		'sub' => array('code' => 'sub', 'before' => '[sub]', 'after' => '[/sub]', 'description' => $txt['b660']),
		'tele' => array('code' => 'tt', 'before' => '[tt]', 'after' => '[/tt]', 'description' => $txt['b652']),
		array(),
		'code' => array('code' => 'code', 'before' => '[code]', 'after' => '[/code]', 'description' => $txt['b622']),
		'quote' => array('code' => 'quote', 'before' => '[quote]', 'after' => '[/quote]', 'description' => $txt['b612']),
		array(),
		'list' => array('code' => 'list', 'before' => '[list]\n[li]', 'after' => '[/li]\n[li][/li]\n[/list]', 'description' => $txt['b613']),
	);
	
	if (!empty($context['zc']['bbc_tags']))
		foreach ($context['zc']['bbc_tags'] as $key => $bbc_tags)
			if (!empty($bbc_tags))
				foreach ($bbc_tags as $tag => $dummy)
					if (!empty($context['zc']['disabled_tags'][$tag]))
						unset($context['zc']['bbc_tags'][$key][$tag]);
}
/*
function zc_spell_check($string)
{
	global $zcFunc;

	// words we know about, but pspell doesn't
	$known_words = array('zcommunity', 'zcomm', 'zc', 'doz', 'blog', 'blogs', 'blogger', 'blogging', 'smf', 'www', 'php', 'mysql', 'www', 'gif', 'jpeg', 'png', 'http');
	
	// some windows machines don't load pspell properly on the first try... dumb, but this is a workaround
	pspell_new('en');
	
	$pspell_link = pspell_new($txt['lang_dictionary'], $txt['lang_spelling'], '', strtr($context['character_set'], array('iso-' => 'iso', 'ISO-' => 'iso')), PSPELL_FAST | PSPELL_RUN_TOGETHER);
	
	// failed to load a dictionary... try english
	if (!$pspell_link)
		$pspell_link = pspell_new('en', '', '', '', PSPELL_FAST | PSPELL_RUN_TOGETHER);
		
	// couldn't load a dictionary....
	if (!$pspell_link)
		return false;
		
	// explode $string into individual words
	
	return $string;
}*/

?>