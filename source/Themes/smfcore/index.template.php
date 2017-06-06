<?php

if (!defined('zc'))
	die('Hacking attempt...');

function zc_template_body_above()
{
	global $context;
	
	echo '
	<div id="top"></div>
	<table border="0" cellspacing="0" cellpadding="0" style="width:', $context['zc']['container_width'], ';" align="', !empty($context['zc']['container_alignment']) ? $context['zc']['container_alignment'] : 'center', '"><tr><td align="', !empty($context['zc']['container_alignment']) ? $context['zc']['container_alignment'] : 'center', '" width="100%">';
	
	echo '
	<div class="bordercolor"><div class="windowbg2" style="padding-left:8px; padding-right:8px;"><table width="100%"><tr><td><span class="blogTitle">', $context['zc']['page_header'], '</span></td><td class="float_inner_list_right">', zc_template_menu($context['zc']['menu']), '</td></tr></table></div></div>';
	
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
	<div style="width:100%; text-align:center; margin-bottom:6px;">', implode('&nbsp;&nbsp;&nbsp;&nbsp;', $context['zc']['extra_above_side_windows']['options']['links']), '</div>';
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
	<div style="width:100%;"><center>', $context['main_blog_page_index'], '</center></div>';
			
	// let's show the syndication links
	if (!empty($context['zc']['syndication']['links']))
	{
		echo '<br />
	<div style="width:100%; text-align:center; padding:5px; font-size:11px;">';
	
		zc_template_syndication_links();
		
		echo '
	</div>';
	}
	
	// extra "small" links...
	if (!empty($context['zc']['extra_small_bottom_links']))
		echo '<br />
	<div class="needsPadding" style="width:100%;"><center><span class="smalltext">', implode('&nbsp;&nbsp;&nbsp;', $context['zc']['extra_small_bottom_links']), '</span></center></div>';
		
	// copyright ... license agreement is void if removed
	echo '<br />
	<div class="smalltext" style="width:100%; text-align:center;">
		<div class="copyright">';
			
	// do not mess with this ... please :) ... seriously... don't
	zCommunityCopyRight();

	echo '<br />
		</div>
	</div>';
}

function zc_template_main()
{
	global $context, $txt, $scripturl, $blog, $zc;
	
	echo '
	<table width="100%" cellspacing="0" cellpadding="0" border="0"><tr>';
	
	// side bar ... left
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
	<div style="padding-left:5px; width:100%; margin-bottom:6px; margin-top:6px;">
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
	<div class="bordercolor">';
		
		if (!empty($windows[$i]['title']))
			echo '
		<div class="catbg" style="text-align:', $alignment, ';"><div class="needsPadding">', $windows[$i]['title'], '</div></div>';
	
		echo '
		<div class="windowbg2" style="text-align:', $alignment, ';"><div class="needsPadding">';
	
		// do we want to display the info as a simple list?
		if ($windows[$i]['type'] == 'list')
			echo implode('<br />', $windows[$i]['content']);
		// is this a custom window?
		if ($windows[$i]['type'] == 'custom')
		{
			if (!empty($windows[$i]['is_php']))
				eval($windows[$i]['content']);
			else
				echo $windows[$i]['content'];
		}
		elseif ($windows[$i]['type'] == 'polls')
		{
			$c = false;
			foreach ($windows[$i]['content'] as $poll)
			{
				if (!empty($c))
					echo '
						<hr class="hrcolor" size="1" width="80%" />';
				template_zc_poll($poll);
				$c = true;
			}
		}
		
		echo '
		</div></div>
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
					
	echo '
	<div class="bordercolor">';
			
				if (!empty($stuff['title']))
					echo '
		<div class="catbg" style="text-align:', $alignment, ';"><div class="needsPadding">', $stuff['title'], '</div></div>';
					
				echo '
		<div class="windowbg2" style="text-align:', $alignment, ';"><div class="needsPadding">', implode('<br />', $stuff['links']), '</div></div>
	</div><br />';
			}
			elseif (in_array($group, array('buttons')) && !empty($stuff))
				echo '<br />' . implode('<br />', $stuff);
}

function zc_template_menu($menu)
{
	global $context;
	
	$context['zc']['use_drop_down'] = true;
	if ($context['browser']['is_ie'])
		$context['zc']['use_drop_down'] = $context['browser']['is_ie7'];
		
	$classes_by_location = array(
		'main' => 'dropNav',
		'bcp1' => 'dropNav2',
		'bcp2' => 'dropNav2',
	);
	
	if (!empty($menu))
	{
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
				
			if (!empty($item['attributes']))
				foreach ($item['attributes'] as $attr => $v)
					echo ' ' . $attr . '="' . $v . '"';
				
			echo '>', $item['label'], '</a>';
			
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
				
						if (!empty($sub_item['attributes']))
							foreach ($sub_item['attributes'] as $attr => $v)
								echo ' ' . $attr . '="' . $v . '"';
				
						echo '>', $sub_item['label'], '</a></li>';
				
						if (!empty($sub_item['is_active']))
							$b = true;
							
						$c++;
					}
						
				echo '
			</ul>';
			}
			
			echo '
		</li>';
				
			if (!empty($item['is_active']))
				$a = true;
		}
			
		echo '
	</ul>';
	}
}

function zc_template_sandwich_above($class = null)
{
	if (empty($class))
		$class = 'windowbg2';
	else
	{
		$classes = array(
			2 => 'windowbg',
			'err' => 'err',
			'success' => 'success',
		);
		
		if (empty($classes[$class]))
			$class = 'windowbg2';
		else
			$class = $classes[$class];
	}

	echo '
	<div class="bordercolor">
		<div class="', $class, '">';
}

function zc_template_sandwich_below($class = null)
{
	echo '
		</div>
	</div>';
}

?>