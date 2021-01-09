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
elseif(!$mybb->input['action'])
{
	$table = new Table;
	$table->construct_header($lang->promptGenerator_header_prompt, array("colspan" => 1));

	$query = $db->simple_select('prompt_generator', 'prompt', '', array('order_by' => 'pid', 'order_dir' => 'DESC'));

	if($db->num_rows($query))
	{
		while($prompt = $db->fetch_field($query, 'prompt'))
        {
            $table->construct_cell($prompt);
			$table->construct_row();
        }
	} else {
		$table->construct_cell("There aren't any prompts yet!");
		$table->construct_row();
	}

	$table->output($lang->dashboard);

}

$page->output_footer();
