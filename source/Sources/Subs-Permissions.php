<?php

if (!defined('zc'))
	die('Hacking attempt...');
	
/*
	array zc_prepare_permissions_profile(int $id)
		- returns an array of permissions the desired permission profile can do
		- returns false if permission profile does not exist
		
	zcUpdatePermissions()
		- updates
*/

function zc_prepare_permissions_profile($id)
{
	// restricted reader
	if ($id == 1)
		$profile = array(
			'view_zcommunity' => 1,
			/*'send_articles' => 1,*/
			'post_comments_in_any_b' => 1,
			'comment_on_news' => 1,
		);
	// standard reader
	elseif ($id == 2)
		$profile = array(
			'view_zcommunity' => 1,
			/*'send_articles' => 1,*/
			'mark_notify' => 1,
			'post_comments_in_any_b' => 1,
			'vote_in_polls' => 1,
			'comment_on_news' => 1,
			'report_to_moderator' => 1,
		);
	// standard blogger
	elseif ($id == 3)
		$profile = array(
			'view_zcommunity' => 1,
			'create_blog' => 1,
			'use_blog_themes' => 1,
			'moderate_own_blogs' => 1,
			/*'send_articles' => 1,*/
			'mark_notify' => 1,
			'save_drafts' => 1,
			'report_to_moderator' => 1,
			'post_polls' => 1,
			'post_comments_in_any_b' => 1,
			'edit_own_articles_in_own_b' => 1,
			'edit_any_articles_in_own_b' => 1,
			'delete_own_articles_in_own_b' => 1,
			'delete_any_articles_in_own_b' => 1,
			'lock_own_articles_in_own_b' => 1,
			'lock_any_articles_in_own_b' => 1,
			'edit_own_comments_in_own_b' => 1,
			'edit_any_comments_in_own_b' => 1,
			'delete_own_comments_in_own_b' => 1,
			'delete_any_comments_in_own_b' => 1,
			'edit_own_polls_in_own_b' => 1,
			'edit_any_polls_in_own_b' => 1,
			'delete_own_polls_in_own_b' => 1,
			'delete_any_polls_in_own_b' => 1,
			'lock_own_polls_in_own_b' => 1,
			'lock_any_polls_in_own_b' => 1,
			'vote_in_polls' => 1,
			'comment_on_news' => 1,
		);
	// premium blogger
	elseif ($id == 4)
		$profile = array(
			'view_zcommunity' => 1,
			'create_blog' => 1,
			'multiple_blogs' => 1,
			'delete_own_blogs' => 1,
			'restrict_access_blogs' => 1,
			'use_blog_themes' => 1,
			'moderate_own_blogs' => 1,
			/*'send_articles' => 1,*/
			'mark_notify' => 1,
			'save_drafts' => 1,
			'report_to_moderator' => 1,
			'post_polls' => 1,
			'post_comments_in_any_b' => 1,
			'edit_own_articles_in_own_b' => 1,
			'edit_own_articles_in_any_b' => 1,
			'edit_any_articles_in_own_b' => 1,
			'delete_own_articles_in_own_b' => 1,
			'delete_own_articles_in_any_b' => 1,
			'delete_any_articles_in_own_b' => 1,
			'lock_own_articles_in_own_b' => 1,
			'lock_own_articles_in_any_b' => 1,
			'lock_any_articles_in_own_b' => 1,
			'edit_own_comments_in_own_b' => 1,
			'edit_own_comments_in_any_b' => 1,
			'edit_any_comments_in_own_b' => 1,
			'delete_own_comments_in_own_b' => 1,
			'delete_own_comments_in_any_b' => 1,
			'delete_any_comments_in_own_b' => 1,
			'edit_own_polls_in_own_b' => 1,
			'edit_own_polls_in_any_b' => 1,
			'edit_any_polls_in_own_b' => 1,
			'delete_own_polls_in_own_b' => 1,
			'delete_own_polls_in_any_b' => 1,
			'delete_any_polls_in_own_b' => 1,
			'lock_own_polls_in_own_b' => 1,
			'lock_own_polls_in_any_b' => 1,
			'lock_any_polls_in_own_b' => 1,
			'vote_in_polls' => 1,
			'comment_on_news' => 1,
		);
	// moderator
	elseif ($id == 5)
		$profile = array(
			'view_zcommunity' => 1,
			'moderate_own_blogs' => 1,
			'moderate_any_blog' => 1,
			/*'send_articles' => 1,*/
			'mark_notify' => 1,
			'post_comments_in_any_b' => 1,
			'edit_own_articles_in_own_b' => 1,
			'edit_own_articles_in_any_b' => 1,
			'edit_any_articles_in_own_b' => 1,
			'edit_any_articles_in_any_b' => 1,
			'delete_own_articles_in_own_b' => 1,
			'delete_own_articles_in_any_b' => 1,
			'delete_any_articles_in_own_b' => 1,
			'delete_any_articles_in_any_b' => 1,
			'lock_own_articles_in_own_b' => 1,
			'lock_own_articles_in_any_b' => 1,
			'lock_any_articles_in_own_b' => 1,
			'lock_any_articles_in_any_b' => 1,
			'edit_own_comments_in_own_b' => 1,
			'edit_own_comments_in_any_b' => 1,
			'edit_any_comments_in_own_b' => 1,
			'edit_any_comments_in_any_b' => 1,
			'delete_own_comments_in_own_b' => 1,
			'delete_own_comments_in_any_b' => 1,
			'delete_any_comments_in_own_b' => 1,
			'delete_any_comments_in_any_b' => 1,
			'approve_articles_in_any_b' => 1,
			'approve_comments_in_any_b' => 1,
			'edit_own_polls_in_own_b' => 1,
			'edit_own_polls_in_any_b' => 1,
			'edit_any_polls_in_own_b' => 1,
			'edit_any_polls_in_any_b' => 1,
			'delete_own_polls_in_own_b' => 1,
			'delete_own_polls_in_any_b' => 1,
			'delete_any_polls_in_own_b' => 1,
			'delete_any_polls_in_any_b' => 1,
			'lock_own_polls_in_own_b' => 1,
			'lock_own_polls_in_any_b' => 1,
			'lock_any_polls_in_own_b' => 1,
			'lock_any_polls_in_any_b' => 1,
			'vote_in_polls' => 1,
			'comment_on_news' => 1,
		);
	// no profile loaded!
	else
		return false;
	
	return $profile;
}

function zcUpdatePermissions()
{
	global $context, $txt;
	global $zcFunc;
	
	// make sure they are allowed access
	if (!zc_check_permissions('access_permissions_tab'))
		zc_fatal_error('zc_error_40');
	
	checkSession('post');
	
	if (empty($context['zc']['permissions']))
		zcPreparePermissionsArray();
		
	if (empty($context['zc']['permissions']))
		zc_fatal_error('zc_error_11');
		
	$data = array();
	$deleteRows = array();
	
	// using single permission, permission profiles, copy member group permissions form...
	if (isset($_POST['add_deny']) && isset($_POST['groups']) && isset($_POST['permission']))
	{
		$groups = array();
		if (isset($_POST['groups']) && is_array($_POST['groups']))
			foreach ($_POST['groups'] as $group)
				$groups[] = (int) $group;
				
		// don't allow admin group...
		$groups = array_unique(array_diff($groups, array(1)));
		
		if (count($groups) == 0)
			zc_fatal_error('zc_error_11');
			
		// verify that all the groups exist...
		$request = $zcFunc['db_query']("
			SELECT {tbl:membergroups::column:id_group} AS id_group
			FROM {db_prefix}{table:membergroups}
			WHERE {tbl:membergroups::column:id_group} IN ({array_int:groups})
			LIMIT {int:limit}", __FILE__, __LINE__,
			array(
				'limit' => count($groups),
				'groups' => $groups
			)
		);
		$temp = array();
		while ($row = $zcFunc['db_fetch_assoc']($request))
			$temp[] = $row['id_group'];
		$zcFunc['db_free_result']($request);
		
		// check special member groups!
		$special_groups = array(-1, 0);
		foreach ($special_groups as $g)
			if (in_array($g, $groups))
				$temp[] = $g;
			
		// none of those groups actually existed... oops!
		if (empty($temp))
			zc_fatal_error('zc_error_11');
			
		$groups = $temp;
				
		// use a pre-defined permission profile?
		if (!empty($_POST['permission_profile']))
		{
			// 1 = restricted reader, 2 = standard reader, 3 = standard blogger, 4 = premium blogger, 5 = moderator
			$profile = (int) $_POST['permission_profile'];
			
			$permissions = zc_prepare_permissions_profile($profile);
			
			// failed to load permission profile
			if (!$permissions)
				zc_fatal_error('zc_error_14');
				
			foreach ($context['zc']['permissions'] as $permission => $dummy)
				if (!isset($permissions[$permission]))
					$permissions[$permission] = 0;
				
			foreach ($groups as $group)
				foreach ($permissions as $permission => $add_deny)
				{
					if (in_array($permission, $context['zc']['non_guest_permissions']) && in_array($group, array(-1)))
						$add_deny = 0;
						
					if (!empty($add_deny))
						$data[] = array('group_id' => $group, 'permission' => $permission, 'add_deny' => $add_deny);
					else
					{
						if (!isset($deleteRows[$group]))
							$deleteRows[$group] = array();
							
						$deleteRows[$group][] = $permission;
					}
				}
		}
		// copy the permissions of another member group?
		elseif (isset($_POST['like_group']) && $_POST['like_group'] != '' && $_POST['like_group'] != 1)
		{
			$like_group = (int) $_POST['like_group'];
			
			// make sure we aren't trying to set a group like itself!
			$groups = array_diff($groups, array($like_group));
			
			// still groups left?
			if (!empty($groups))
			{
				// special groups!
				if (!in_array($like_group, array(-1,0)))
				{
					// verify that this group exists...
					$request = $zcFunc['db_query']("
						SELECT {tbl:membergroups::column:id_group} AS id_group
						FROM {db_prefix}{table:membergroups}
						WHERE {tbl:membergroups::column:id_group} = {int:group_id}
						LIMIT 1", __FILE__, __LINE__,
						array(
							'group_id' => $like_group
						)
					);
						
					// group does not exist!
					if ($zcFunc['db_num_rows']($request) == 0)
						zc_fatal_error('zc_error_12');
						
					$zcFunc['db_free_result']($request);
				}
				
				// go get the permissions for the selected member group
				$request = $zcFunc['db_query']("
					SELECT add_deny, permission
					FROM {db_prefix}permissions
					WHERE group_id = {int:group_id}", __FILE__, __LINE__,
					array(
						'group_id' => $like_group
					)
				);
				$permissions = array();
				while ($row = $zcFunc['db_fetch_assoc']($request))
				{
					$permission = $zcFunc['un_htmlspecialchars']($row['permission']);
					$permissions[$permission] = $row['add_deny'];
				}
				
				// failed to load permissions
				if (empty($permissions))
					zc_fatal_error('zc_error_15');
					
				foreach ($groups as $group)
					foreach ($permissions as $permission => $add_deny)
					{
						if (in_array($permission, $context['zc']['non_guest_permissions']) && in_array($group, array(-1)))
							$add_deny = 0;
					
						if (!empty($add_deny))
							$data[] = array('group_id' => $group, 'permission' => $permission, 'add_deny' => $add_deny);
						else
						{
							if (!isset($deleteRows[$group]))
								$deleteRows[$group] = array();
								
							$deleteRows[$group][] = $permission;
						}
					}
			}
		}
		// adding/clearing a single permission for one or more groups then?
		else
		{
			// just in case slashes were added.... and make sure it's a string
			$permission = (string) stripslashes($_POST['permission']);
			
			// if permission is empty or it isn't in the permissions array... that's a problem...
			if ((trim($permission) == '') || !isset($context['zc']['permissions'][$permission]))
				zc_fatal_error('zc_error_11');
			
			foreach ($groups as $group)
			{
				if (in_array($permission, $context['zc']['non_guest_permissions']) && in_array($group, array(-1)))
					$add_deny = 0;
				else
					$add_deny = !empty($_POST['add_deny']) ? 1 : 0;
						
				if (!empty($add_deny))
					$data[] = array('group_id' => $group, 'permission' => $permission, 'add_deny' => $add_deny);
				else
				{
					if (!isset($deleteRows[$group]))
						$deleteRows[$group] = array();
						
					$deleteRows[$group][] = $permission;
				}
			}
		}
	}
	// single group's permissions form...
	elseif (isset($_POST['group']) && $_POST['group'] != 1)
	{
		$group = (int) $_POST['group'];
		
		// guests and regular members are special groups
		if (!in_array($group, array(-1,0)))
		{
			// verify that the group exists...
			$request = $zcFunc['db_query']("
				SELECT {tbl:membergroups::column:id_group} AS id_group
				FROM {db_prefix}{table:membergroups}
				WHERE {tbl:membergroups::column:id_group} = {int:group_id}
				LIMIT 1", __FILE__, __LINE__,
				array(
					'group_id' => $group
				)
			);
			// group does not exist!
			if ($zcFunc['db_num_rows']($request) == 0)
				zc_fatal_error('zc_error_11');
			$zcFunc['db_free_result']($request);
		}
			
		foreach ($context['zc']['permissions'] as $permission => $dummy)
		{
			$add_deny = !empty($_POST[$permission]) ? 1 : 0;
			
			// if this is the guest group and this is a non_guest_permission... add_deny should be 0
			if (in_array($group, array(-1)) && in_array($permission, $context['zc']['non_guest_permissions']))
				$add_deny = 0;
			
			if (!empty($add_deny))
				$data[] = array('group_id' => $group, 'permission' => $permission, 'add_deny' => $add_deny);
			else
			{
				if (!isset($deleteRows[$group]))
					$deleteRows[$group] = array();
					
				$deleteRows[$group][] = $permission;
			}
		}
	}
	
	if (!empty($deleteRows))
		foreach ($deleteRows as $group_id => $permissions)
			$zcFunc['db_query']("
				DELETE FROM {db_prefix}permissions
				WHERE group_id = {int:group_id}
					AND permission IN ({array_string:permissions})
				LIMIT {int:limit}", __FILE__, __LINE__,
				array(
					'group_id' => $group_id,
					'permissions' => $permissions,
					'limit' => count($permissions)
				)
			);
		
	// replace into the permissions table
	if (!empty($data))
		$zcFunc['db_insert']('replace', '{db_prefix}permissions', array('group_id' => 'int', 'permission' => 'string', 'add_deny' => 'int'), $data);
				
	// redirect to where they came from
	zc_redirect_exit(zcRequestVarsToString());
}

function zc_load_member_groups_permissions()
{
	global $context, $txt, $scripturl;
	global $zcFunc;
	
	// make sure they are allowed access
	if (!zc_check_permissions('access_permissions_tab'))
		zc_fatal_error('zc_error_40');
	
	// prepares $context['zc']['permissions'] array
	zcPreparePermissionsArray();
	
	// load a single group's permissions?
	if (isset($_REQUEST['group']))
	{
		$group = (int) $_REQUEST['group'];
		
		// cannot view this page for the Admin group
		if ($group == 1)
			zc_redirect_exit(zcRequestVarsToString('group'));
		
		// guests and regular members are special member groups...
		if (in_array($group, array(-1,0)))
		{
			$context['zc']['member_group'] = array(
				'id' => $group,
				'name' => $group == 0 ? $txt['b134'] : $txt['b133'],
				'permissions' => array(),
				'is_guest_group' => ($group == -1),
			);
		}
		else
		{	
			// verify such a non-post-based member group exists
			$request = $zcFunc['db_query']("
				SELECT {tbl:membergroups::column:id_group} AS id_group, {tbl:membergroups::column:group_name} AS group_name
				FROM {db_prefix}{table:membergroups}
				WHERE {tbl:membergroups::column:id_group} = {int:group_id}
				LIMIT 1", __FILE__, __LINE__,
				array(
					'group_id' => $group
				)
			);
				
			if ($zcFunc['db_num_rows']($request) == 0)
				zc_fatal_error('zc_error_12');
				
			$row = $zcFunc['db_fetch_assoc']($request);
			$context['zc']['member_group'] = array(
				'id' => $row['id_group'],
				'name' => $zcFunc['un_htmlspecialchars']($row['group_name']),
				'permissions' => array(),
			);
			$zcFunc['db_free_result']($request);
		}
		
		$request = $zcFunc['db_query']("
			SELECT permission, add_deny
			FROM {db_prefix}permissions
			WHERE group_id = {int:group_id}", __FILE__, __LINE__,
			array(
				'group_id' => $group
			)
		);
		$add_deny = array();
		while ($row = $zcFunc['db_fetch_assoc']($request))
			$add_deny[$row['permission']] = $row['add_deny'];
		$zcFunc['db_free_result']($request);
		
		foreach ($context['zc']['permissions'] as $permission => $dummy)
			$context['zc']['member_group']['permissions'][$permission] = isset($add_deny[$permission]) ? $add_deny[$permission] : 0;
	}
	// load all groups' permissions...
	else
	{
		$context['zc']['list1']['table_headers'] = array(
			'name' => $txt['b3025'],
			'members' => $txt['b3024'],
			'allowed' => $txt['b136'],
			'checkbox' => '<input type="checkbox" onclick="invertAll(this, this.form, \'groups[]\');" class="check" />'
		); 
	
		// array of predefined permission profiles
		$context['predefined_permission_profiles'] = array(
			1 => $txt['b138'],
			$txt['b139'],
			$txt['b140'],
			$txt['b150'],
			$txt['b151'],
		);
	
		$context['zc']['member_groups'] = array(
			1 => array(
				'name' => '',
				'members' => 0,
				'allowed' => 0,
				'modify_link' => '',
				'checkbox' => '',
			),
			-1 => array(
				'name' => $txt['b133'],
				'members' => '<i>' . $txt['b135'] . '</i>',
				'allowed' => 0,
				'modify_link' => '',
				'checkbox' => '',
			),
			0 => array(
				'name' => $txt['b134'],
				'members' => 0,
				'allowed' => 0,
				'modify_link' => '',
				'checkbox' => '',
			),
			3 => array(
				'name' => $txt['b236'],
				'members' => '<i>' . $txt['b135'] . '</i>',
				'allowed' => 0,
				'modify_link' => '',
				'checkbox' => '',
			),
		);
		
		// get member groups...
		$request = $zcFunc['db_query']("
			SELECT g.{tbl:membergroups::column:id_group} AS id_group, g.{tbl:membergroups::column:group_name} AS group_name, g.{tbl:membergroups::column:min_posts} AS min_posts
			FROM {db_prefix}{table:membergroups} AS g
				LEFT JOIN {db_prefix}{table:members} AS m ON (m.{tbl:members::column:id_group} = g.id_group)
			WHERE g.{tbl:membergroups::column:id_group} != 3
			GROUP BY g.{tbl:membergroups::column:id_group}", __FILE__, __LINE__);
		$groups = array(-1, 0, 3);
		$post_count_based_groups = array();
		while ($row = $zcFunc['db_fetch_assoc']($request))
		{
			if (!isset($context['zc']['member_groups'][$row['id_group']]))
				$context['zc']['member_groups'][$row['id_group']] = array();
			$context['zc']['member_groups'][$row['id_group']]['name'] = $zcFunc['un_htmlspecialchars']($row['group_name']);
			$context['zc']['member_groups'][$row['id_group']]['members'] = 0;
			$context['zc']['member_groups'][$row['id_group']]['allowed'] = 0;
			$context['zc']['member_groups'][$row['id_group']]['modify_link'] = '';
			$context['zc']['member_groups'][$row['id_group']]['checkbox'] = '';
			$groups[] = $row['id_group'];
			
			// is this a post count based member group?
			if ($row['min_posts'] >= 0)
				$post_count_based_groups[] = $row['id_group'];
		}
		$zcFunc['db_free_result']($request);
		
		// get num_allowed
		$request = $zcFunc['db_query']("
			SELECT group_id, COUNT(add_deny) AS num_allowed
			FROM {db_prefix}permissions
			WHERE group_id IN ({array_int:groups})
				AND add_deny = 1
			GROUP BY group_id", __FILE__, __LINE__,
			array(
				'groups' => $groups
			)
		);
		while ($row = $zcFunc['db_fetch_assoc']($request))
			$context['zc']['member_groups'][$row['group_id']]['allowed'] = !empty($row['num_allowed']) ? $row['num_allowed'] : 0;
		$zcFunc['db_free_result']($request);
		
		// take moderators and guests outa the array now... their num_members is n/a
		$groups = array_diff($groups, array(-1, 3));
		
		// get num_members for each group
		$request = $zcFunc['db_query']("
			SELECT {tbl:members::column:id_group} AS id_group, COUNT({tbl:members::column:id_member}) AS num_members
			FROM {db_prefix}{table:members}
			WHERE {tbl:members::column:id_group} IN ({array_int:groups})
			GROUP BY {tbl:members::column:id_group}", __FILE__, __LINE__,
			array(
				'groups' => $groups
			)
		);
		while ($row = $zcFunc['db_fetch_assoc']($request))
			$context['zc']['member_groups'][$row['id_group']]['members'] = !empty($row['num_members']) ? $row['num_members'] : 0;
		$zcFunc['db_free_result']($request);
		
		// get num_members for post count based member groups
		if (isset($post_count_based_groups) && count($post_count_based_groups) > 0)
		{
			$request = $zcFunc['db_query']("
				SELECT {tbl:members::column:id_post_group} AS id_post_group, COUNT({tbl:members::column:id_member}) AS num_members
				FROM {db_prefix}{table:members}
				WHERE {tbl:members::column:id_post_group} IN ({array_int:groups})
				GROUP BY {tbl:members::column:id_post_group}", __FILE__, __LINE__,
				array(
					'groups' => $post_count_based_groups
				)
			);
			while ($row = $zcFunc['db_fetch_assoc']($request))
				$context['zc']['member_groups'][$row['id_post_group']]['members'] = !empty($row['num_members']) ? $row['num_members'] : 0;
			$zcFunc['db_free_result']($request);
		}
		
		// administrator group has all permissions....
		$context['zc']['member_groups'][1]['allowed'] = '<i>' . $txt['b557a'] . '</i>';
		
		// add modify links and checkboxes to each member_group's array
		foreach ($context['zc']['member_groups'] as $id => $dummy)
		{
			// no modify link for the Admin group
			$context['zc']['member_groups'][$id]['modify_link'] = $id != 1 ? '<a href="' . $scripturl . '?zc=bcp;sa=permissions;group='. $id .'">'. $txt['b3023'] .'</a>' : '';
			// no checkbox for the Admin group...
			$context['zc']['member_groups'][$id]['checkbox'] = $id != 1 ? '<input type="checkbox" id="groups'. $id .'" name="groups[]" value="'. $id .'" />' : '';
		}	
	}
}

?>