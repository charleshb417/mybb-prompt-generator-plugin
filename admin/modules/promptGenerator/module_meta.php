<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

/**
 * @return bool true
 */
function promptGenerator_meta()
{
	global $page, $lang, $plugins;

	$sub_menu = array();
	$sub_menu['10'] = array("id" => "promptGenerator", "title" => $lang->setting_group_promptGenerator, "link" => "index.php?module=promptGenerator");

	$page->add_menu_item("Prompt Generator", "promptGenerator", "index.php?module=promptGenerator", 1, $sub_menu);

	return true;
}

/**
 * @param string $action
 *
 * @return string
 */
function promptGenerator_action_handler($action)
{
	global $page, $db, $lang, $plugins;

	$page->active_module = "promptGenerator";

	$actions = array(
		'promptGenerator' => array('active' => 'promptGenerator', 'file' => 'index.php')
	);

	if(!isset($actions[$action]))
	{
		$page->active_action = "promptGenerator";
	}
	else
	{
		$page->active_action = $actions[$action]['active'];
	}

	if($page->active_action == "promptGenerator")
	{
		// Quick Access
		$sub_menu = array();

		// Online Administrators in the last 30 minutes
		$timecut = TIME_NOW-60*30;
		$query = $db->simple_select("adminsessions", "uid, ip, useragent", "lastactive > {$timecut}");
		$online_users = "<ul class=\"menu online_admins\">";
		$online_admins = array();

		// If there's only 1 user online, it has to be us.
		if($db->num_rows($query) == 1)
		{
			$user = $db->fetch_array($query);
			global $mybb;

			// Are we on a mobile device?
			// Stolen from http://stackoverflow.com/a/10989424
			$user_type = "desktop";
			if(is_mobile($user["useragent"]))
			{
				$user_type = "mobile";
			}

			$online_admins[$mybb->user['username']] = array(
				"uid" => $mybb->user['uid'],
				"username" => $mybb->user['username'],
				"ip" => $user["ip"],
				"type" => $user_type
			);
		}
		else
		{
			$uid_in = array();
			while($user = $db->fetch_array($query))
			{
				$uid_in[] = $user['uid'];

				$user_type = "desktop";
				if(is_mobile($user['useragent']))
				{
					$user_type = "mobile";
				}

				$online_admins[$user['uid']] = array(
					"ip" => $user['ip'],
					"type" => $user_type
				);
			}

			$query = $db->simple_select("users", "uid, username", "uid IN(".implode(',', $uid_in).")", array('order_by' => 'username'));
			while($user = $db->fetch_array($query))
			{
				$online_admins[$user['username']] = array(
					"uid" => $user['uid'],
					"username" => $user['username'],
					"ip" => $online_admins[$user['uid']]['ip'],
					"type" => $online_admins[$user['uid']]['type']
				);
				unset($online_admins[$user['uid']]);
			}
		}

		$done_users = array();

		asort($online_admins);

		foreach($online_admins as $user)
		{
			if(!isset($done_users["{$user['uid']}.{$user['ip']}"]))
			{
				if($user['type'] == "mobile")
				{
					$class = " class=\"mobile_user\"";
				}
				else
				{
					$class = "";
				}
				$ip_address = my_inet_ntop($db->unescape_binary($user['ip']));
				$online_users .= "<li title=\"{$lang->ipaddress} {$ip_address}\"{$class}>".build_profile_link(htmlspecialchars_uni($user['username']).' ('.$ip_address.')', $user['uid'], "_blank")."</li>";
				$done_users["{$user['uid']}.{$user['ip']}"] = 1;
			}
		}
		$online_users .= "</ul>";
		$sidebar = new SidebarItem($lang->online_admins);
		$sidebar->set_contents($online_users);

		$page->sidebar .= $sidebar->get_markup();
	}

	if(isset($actions[$action]))
	{
		$page->active_action = $actions[$action]['active'];
		return $actions[$action]['file'];
	}
	else
	{
		$page->active_action = "promptGenerator";
		return "index.php";
	}
}

