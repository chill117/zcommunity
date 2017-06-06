<?php

if (!defined('zc'))
	die('Hacking attempt...');

function zc_template_body_above()
{
	global $context;
	
	echo '
	<div id="top"></div>
	<table border="0" cellspacing="0" cellpadding="0" style="width:', $context['zc']['container_width'], ';"><tr><td align="', !empty($context['zc']['container_alignment']) ? $context['zc']['container_alignment'] : 'center', '" width="100%">';
	
	// title and navigation menu
	echo '
	<div id="topSection">
		<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
			<td width="35%" align="left"><span class="blogTitle">', $context['zc']['page_header'], '</span></td>
			<td width="65%" align="right">', zc_template_menu($context['zc']['menu']), '</td>
		</tr></table>
	</div>';
	
	echo zc_template_dhtml_popup_login();
}

function zc_template_body_below()
{
	global $txt, $context;
	
	echo '
	<div class="smalltext" style="width:100%; text-align:center; margin-bottom:4px; line-height:175%;">
		<div class="copyright">';
	
	if (!empty($context['zc']['templates_other_copyrights']))
		foreach ($context['zc']['templates_other_copyrights'] as $template_copyright)
			if (function_exists($template_copyright))
				echo $template_copyright();
	
	if (!empty($context['show_load_time']))
		echo '<br />
			<div class="smalltext">', sprintf($txt['b3015'], $context['load_time'], $context['load_queries']), '</div>';
	
	echo '
		</div>
	</div>
	</td></tr></table><br />';
}

function zc_template_body2_above()
{
	global $context;
	
	// link tree and extra links
	zc_template_link_tree();
	
	// side windows are hidden completely... want to show anything at the top that got hidden?
	if (empty($context['blog_control_panel']) && empty($context['zc']['side_bar_left']) && empty($context['zc']['side_bar_right']))
	{
		// let's show the options links
		if (!empty($context['zc']['extra_above_side_windows']['options']['links']))
			echo '
	<div style="width:100%; text-align:center; margin-bottom:8px;">', implode('&nbsp;&nbsp;&nbsp;&nbsp;', $context['zc']['extra_above_side_windows']['options']['links']), '</div>';
	}
}

function zc_template_body2_below()
{
	global $context;
	
	// go up link
	if (!empty($context['zc']['go_top_link']))
		echo '<br />
	<div style="width:100%;"><center>', $context['zc']['go_top_link'], '</center></div>';
	
	// show the main blog page index?
	if (!empty($context['main_blog_page_index']))
		echo '<br />
	<div style="width:100%;"><center>', $context['main_blog_page_index'], '</div>';
			
	// let's show the syndication links
	if (!empty($context['zc']['syndication']['links']))
	{
		echo '<br />
	<div style="width:100%;"><center>';
	
		zc_template_syndication_links();
		
		echo '
	</center></div>';
	}
	
	// extra "small" links...
	if (!empty($context['zc']['extra_small_bottom_links']))
		echo '<br />
	<div style="width:100%;"><center><span class="smalltext">', implode('&nbsp;&nbsp;&nbsp;', $context['zc']['extra_small_bottom_links']), '</span></center></div>';
		
	// copyright ... license agreement is void if removed
	echo '<br />
	<div class="smalltext" style="width:100%; text-align:center; margin-bottom:4px;">
		<div class="copyright">';
			
	// do not mess with this ... please :) ... seriously... don't
	zCommunityCopyRight();

	echo '<br />
		</div>
	</div>';
}

function zc_template_main()
{
	global $context;
	
	echo '
	<table width="100%" cellspacing="0" cellpadding="0" border="0"><tr>';
	
	// side bar
	if (!empty($context['zc']['side_bar_left']))
	{
		echo '
	<td valign="top" width="', $context['zc']['left_side_bar_width'], '%">';
				
		if (!empty($context['zc']['extra_above_side_windows']))
			zc_template_spewExtra($context['zc']['extra_above_side_windows']);
				
		zc_template_side_windows();
	
		if (!empty($context['zc']['extra_below_side_windows']))
			zc_template_spewExtra($context['zc']['extra_below_side_windows']);
		
		echo '
	</td>';
	}
		
	// main area
	echo '
	<td valign="top" width="', $context['zc']['main_guts_width'], '%" style="', !empty($context['zc']['side_bar_left']) || !empty($context['zc']['side_bar_right']) ? 'padding-' . (!empty($context['zc']['side_bar_right']) ? 'right' : 'left') . ':10px; ' : '', '">';
	
		zc_template_main_guts();
	
	echo '
	</td>';
	
	// side bar ... right
	if (!empty($context['zc']['side_bar_right']))
	{
		echo '
	<td valign="top" width="', $context['zc']['right_side_bar_width'], '%">';
				
		if (!empty($context['zc']['extra_above_side_windows']) && empty($context['zc']['side_bar_left']))
			zc_template_spewExtra($context['zc']['extra_above_side_windows']);
				
		zc_template_side_windows();
	
		if (!empty($context['zc']['extra_below_side_windows']) && empty($context['zc']['side_bar_left']))
			zc_template_spewExtra($context['zc']['extra_below_side_windows']);
		
		echo '
	</td>';
	}
	
	echo '
	</tr></table>';
}

function zc_template_link_tree()
{
	global $context;
	
	if (empty($context['zc']['link_tree_divider']) && empty($context['zc']['extra_links']))
		return;
			
	echo '
	<div style="width:100%; margin-top:8px; margin-bottom:8px;">
		<table width="100%">
			<tr>
				<td align="left">', implode($context['zc']['link_tree_divider'], $context['zc']['link_tree']), '</td>
				<td align="right">', implode('&nbsp;-&nbsp;', $context['zc']['extra_links']), '</td>
			</tr>
		</table>
	</div>';
}

function zc_template_side_windows($side = null)
{
	global $context, $zc;
	
	// nothing to display....
	if (empty($context['zc']['side_windows']) || !empty($context['zc']['windows_previously_displayed']))
		return;
	
	if (empty($side) || empty($context['zc']['side_windows'][$side]) || !in_array($side, array('left', 'right')))
	{
		$windows = $context['zc']['side_windows'];
		$context['zc']['windows_previously_displayed'] = true;
	}
	elseif ($side == 'left')
		$windows = $context['zc']['side_windows']['left'];
	elseif ($side == 'right')
		$windows = $context['zc']['side_windows']['right'];
	
	// get max_window_order if it wasn't set...
	if (empty($context['zc']['max_window_order']))
		$context['zc']['max_window_order'] = max(array_keys($context['zc']['side_windows']));
		
	$n = 0;
	// it's done this way for ordering purposes...
	for ($i = 1; $i <= $context['zc']['max_window_order']; $i++)
	{
		if (!isset($windows[$i]) || !isset($windows[$i]['type']) || empty($windows[$i]['content']))
			continue;
	
		// figure out inner alignment of windows...
		if ($windows[$i]['type'] == 'polls')
			$alignment = 'center';
		elseif (!empty($context['zc']['blog_settings']['windows_inner_alignment']))
			$alignment = $context['zc']['blog_settings']['windows_inner_alignment'];
		elseif (!empty($zc['settings']['windows_inner_alignment']))
			$alignment = $zc['settings']['windows_inner_alignment'];
		else
			$alignment = !empty($context['zc']['side_bar_left']) ? 'left' : 'right';
			
		if (!empty($n))
			echo '<br />';
			
		$n++;
			
		echo '
	<div class="sideWindow">
		<div class="needsPadding">';
		
		if (!empty($windows[$i]['title']))
			echo '
			<div class="blogSideWindowHeader" style="text-align:', $alignment, ';">', $windows[$i]['title'], '</div>';
		
		echo '
			<div class="needsPadding" style="line-height:150%; text-align:', $alignment, ';">';
	
		// do we want to display the info as a simple list?
		if ($windows[$i]['type'] == 'list')
			echo implode('<br />', $windows[$i]['content']);
		// is this a custom window?
		elseif ($windows[$i]['type'] == 'custom')
		{
			if (!empty($windows[$i]['is_php']))
				eval($windows[$i]['content']);
			else
				echo $windows[$i]['content'];
		}
		elseif ($windows[$i]['type'] == 'polls')
		{
			$b = false;
			foreach ($windows[$i]['content'] as $poll)
			{
				if (!empty($b))
					echo '
				<hr class="hrcolor" size="1" width="80%" />';
				template_zc_poll($poll);
				$b = true;
			}
		}
									
		echo '
			</div>
		</div>
	</div>';
	}
}

function zc_template_spewExtra($data)
{
	global $context, $zc;
	
	if (!empty($data))
		foreach ($data as $group => $stuff)
			if (!in_array($group, array('buttons')) && !empty($stuff['links']))
			{
				// figure out inner alignment of windows...
				if (!empty($context['zc']['blog_settings']['windows_inner_alignment']))
					$alignment = $context['zc']['blog_settings']['windows_inner_alignment'];
				elseif (!empty($zc['settings']['windows_inner_alignment']))
					$alignment = $zc['settings']['windows_inner_alignment'];
				else
					$alignment = !empty($context['zc']['side_bar_left']) ? 'left' : 'right';
			
				if (!empty($stuff['title']))
					echo '
	<div class="sideWindow">
		<div class="needsPadding">
			<div class="blogSideWindowHeader" style="text-align:', $alignment, ';">', $stuff['title'], '</div>';
				
				echo '
			<div class="needsPadding" style="line-height:150%; text-align:', $alignment, ';">';
					
				echo implode('<br />', $stuff['links']);
		
				echo '
			</div>
		</div>
	</div><br />';
			}
			elseif (in_array($group, array('buttons')) && !empty($stuff))
				echo '<br />' . implode('<br />', $stuff);
}

function zc_template_menu($menu)
{
	if (empty($menu))
		return;

	global $context;
	
	// assume they can...
	$context['zc']['use_drop_down'] = true;
	// if IE, we only allow IE7 to see the drop down sub menus
	if ($context['browser']['is_ie'])
		$context['zc']['use_drop_down'] = $context['browser']['is_ie7'];
		
	$classes_by_location = array(
		'main' => 'dropNav',
		'bcp1' => 'dropNav2',
		'bcp2' => 'dropNav2',
	);
	
	echo '
	<ul class="', isset($menu['_info_']['location']) && isset($classes_by_location[$menu['_info_']['location']]) ? $classes_by_location[$menu['_info_']['location']] : $classes_by_location['main'], '">';
		
	$a = false;
	foreach ($menu as $key => $item)
	{
		// _info_ is for informational purposes only
		if (in_array($key, array('_info_')) || empty($item['label']) || !isset($item['can_see']) || $item['can_see'] !== true)
			continue;
			
		$item['label'] = zcFormatTxtString($item['label']);
			
		echo '
		<li', !$a && !empty($item['is_active']) ? ' class="current"' : '', '>
			<a'; 
			
		// spew all the attributes this anchor should have...
		if (!empty($item['attributes']))
			foreach ($item['attributes'] as $attr => $v)
				echo ' ' . $attr . '="' . $v . '"';
			
		// finish the anchor and list item
		echo '>', $item['label'], '</a>';
		
		// is there a sub menu?  if so, do we really wanna show it?
		if (!empty($context['zc']['use_drop_down']) && !empty($item['sub_menu']))
		{
			echo '
			<ul>';
				
			$b = false;
			$c = 0;
			foreach ($item['sub_menu'] as $sub_key => $sub_item)
				if (!in_array($sub_key, array('_info_')) && !empty($sub_item['label']) && isset($sub_item['can_see']) && $sub_item['can_see'] === true)
				{
					
					$sub_item['label'] = zcFormatTxtString($sub_item['label']);
		
					echo '
				<li', !$b && !empty($sub_item['is_active']) ? ' class="current"' : '', '><a', empty($c) ? ' class="first_in_menu_list"' : ''; 
				
					// spew all the attributes this anchor should have...
					if (!empty($sub_item['attributes']))
						foreach ($sub_item['attributes'] as $attr => $v)
							echo ' ' . $attr . '="' . $v . '"';
			
					// finish the anchor and list item
					echo '>', $sub_item['label'], '</a></li>';
				
					// we want to prevent any other sub menu items from being shown as active
					if (!empty($sub_item['is_active']))
						$b = true;
						
					$c++;
				}
						
			echo '
			</ul>';
		}
			
		echo '
		</li>';
		
		// we want to prevent any other primary menu items from being shown as active
		if (!empty($item['is_active']))
			$a = true;
	}
	echo '
	</ul>';
}

function zc_template_sandwich_above($class = null)
{
	if (empty($class))
		$class = 'commentbg';
	else
	{
		$classes = array(
			2 => 'commentbg2',
			'err' => 'err',
			'success' => 'success',
		);
		
		if (empty($classes[$class]))
			$class = 'commentbg';
		else
			$class = $classes[$class];
	}

	echo '
	<div class="', $class, '">';
}

function zc_template_sandwich_below($class = null)
{
	echo '
	</div>';
}

?>