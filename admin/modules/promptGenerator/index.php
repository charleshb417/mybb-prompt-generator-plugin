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

if(!$mybb->input['action'])
{
	$page->add_breadcrumb_item($lang->promptGenerator_breadcrumb);
	$page->output_header($lang->promptGenerator_breadcrumb);

	$sub_tabs['promptGenerator'] = array(
		'title' => $lang->promptGenerator_breadcrumb,
		'link' => "index.php?module=promptGenerator",
		'description' => $lang->setting_group_promptGenerator_desc
	);


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

	$page->output_footer();
}
