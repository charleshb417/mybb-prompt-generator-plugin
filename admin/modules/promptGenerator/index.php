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

$page->add_breadcrumb_item($lang->promptGenerator_breadcrumb);
$page->output_header($lang->promptGenerator_breadcrumb);

$sub_tabs = array();
$sub_tabs['promptGenerator'] = array(
	'title' => $lang->promptGenerator_breadcrumb,
	'link' => "index.php?module=promptGenerator",
	'description' => $lang->setting_group_promptGenerator_desc
);

$sub_tabs['add_forum'] = array(
		'title' => $lang->promptGenerator_add,
		'link' => "index.php?module=promptGenerator&amp;action=add",
		'description' => $lang->add_forum_desc
);

$page->output_nav_tabs($sub_tabs, 'edit_mod');

if ( $mybb->input['action'] == 'add')
{
	if($mybb->request_method == "post")
	{
		if(!trim($mybb->input['prompt']))
		{
			$errors[] = $lang->promptGenerator_error_missing_title;
		}
		else
		{
			// trim excess whitespace
			$p = trim($mybb->input['prompt']);

			// replace newlines with spaces
			$p = trim(preg_replace('/\s\s+/', ' ', $p));

			if (strlen($p) > 2048){
				$errors[] = $lang->promptGenerator_error_too_long;
			}
		}

		if (!$errors)
		{
			$insert_array = array(
				"prompt" => $db->escape_string($p)
			);

			$pid = $db->insert_query("prompt_generator", $insert_array);
			flash_message($lang->promptGenerator_add_success, 'success');
			admin_redirect("index.php?module=promptGenerator");
		}
		else
		{
			$page->output_inline_error($errors);
		}

	}

	$form = new Form("index.php?module=promptGenerator&amp;action=add", "post");
	$form_container = new FormContainer($lang->promptGenerator_add);
	$form_container->output_row($lang->promptGenerator_header_prompt, "", $form->generate_text_area('prompt', '', array('id' => 'prompt', 'maxlength'=>2048, 'required'=>true)), 'prompt');
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->promptGenerator_submit_button);
	$form->output_submit_wrapper($buttons);
	$form->end();

}
elseif ( $mybb->input['action'] == 'edit')
{
	if ($mybb->input['pid']){
		$pid = $mybb->input['pid'];
		$query = $db->simple_select("prompt_generator", "*", "pid='{$pid}'");
		$prompt = $db->fetch_array($query);

		// Does the forum not exist?
		if(!$prompt['pid'])
		{
			flash_message($lang->promptGenerator_error_invalid, 'error');
			admin_redirect("index.php?module=promptGenerator");
		}
		else {
			if($mybb->request_method == "post")
			{
				if(!trim($mybb->input['prompt']))
				{
					$errors[] = $lang->promptGenerator_error_missing_title;
				}
				else
				{
					// trim excess whitespace
					$p = trim($mybb->input['prompt']);

					// replace newlines with spaces
					$p = trim(preg_replace('/\s\s+/', ' ', $p));

					if (strlen($p) > 2048){
						$errors[] = $lang->promptGenerator_error_too_long;
					}

					if($errors)
					{
						$page->output_inline_error($errors);
						$forum_data = $mybb->input;
					}

					$db->update_query("prompt_generator", array("prompt"=>$p), "pid='{$mybb->input['pid']}'");

					flash_message($lang->promptGenerator_edit_success, 'success');
					admin_redirect("index.php?module=promptGenerator");

				}
			}

			$form = new Form("index.php?module=promptGenerator&amp;action=edit", "post");
			echo $form->generate_hidden_field("pid", $pid);

			$form_container = new FormContainer($lang->promptGenerator_edit);
			$form_container->output_row($lang->promptGenerator_header_prompt, "", $form->generate_text_area('prompt', $prompt['prompt'], array('id' => 'prompt', 'maxlength' => 2048)), 'prompt');
			$form_container->end();

			$buttons[] = $form->generate_submit_button($lang->promptGenerator_submit_button);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
	}
	else
	{
		flash_message($lang->promptGenerator_error_missing_id, 'error');
		admin_redirect("index.php?module=promptGenerator");
	}
}
elseif ( $mybb->input['action'] == 'delete')
{
	if ($mybb->input['pid']){
		$query = $db->simple_select("prompt_generator", "*", "pid='{$mybb->input['pid']}'");
		$prompt = $db->fetch_array($query);

		// Does the forum not exist?
		if(!$prompt['pid'])
		{
			flash_message($lang->promptGenerator_error_invalid, 'error');
			admin_redirect("index.php?module=promptGenerator");
		}
		else {
			$db->delete_query('prompt_generator', "pid='{$mybb->input['pid']}'");	
			flash_message($lang->promptGenerator_delete_success, 'success');
			admin_redirect("index.php?module=promptGenerator");
		}
	}
	else
	{
		flash_message($lang->promptGenerator_error_missing_id, 'error');
		admin_redirect("index.php?module=promptGenerator");
	}
	
}
elseif(!$mybb->input['action'])
{
	$table = new Table;
	$table->construct_header($lang->promptGenerator_header_prompt, array("colspan" => 4));

	$query = $db->simple_select('prompt_generator', '*', '', array('order_by' => 'pid', 'order_dir' => 'DESC'));

	if($db->num_rows($query))
	{
		while($row = $db->fetch_array($query))
        {
            $table->construct_cell($row['prompt']);

            $pid = $row['pid'];

            $editLink = '<a href="index.php?module=promptGenerator&amp;action=edit&amp;pid=' . $pid . '">Edit</a>';
            $deleteLink = '<a href="index.php?module=promptGenerator&amp;action=delete&amp;pid=' . $pid . '">Delete</a>';

            $table->construct_cell($editLink);
            $table->construct_cell($deleteLink);
			$table->construct_row();
        }
	} else {
		$table->construct_cell("There aren't any prompts yet!");
		$table->construct_row();
	}

	$table->output($lang->dashboard);

}

$page->output_footer();
