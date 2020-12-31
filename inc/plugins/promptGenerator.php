<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.");
}


function promptGenerator_info()
{
    return array(
        "name"          => "Random Prompt Generator",
        "description"   => "Allows poster to generate a random prompt for creative writing exercises.",
        "website"       => "",
        "author"        => "Charlie Bishop",
        "authorsite"    => "",
        "version"       => "0.1",
        "guid"          => "",
        "codename"      => "promptGenerator",
        "compatibility" => "*"
    );
}

function promptGenerator_install()
{
    global $db;

    // Create our table collation
    $collation = $db->build_create_table_collation();

    // Create table if it doesn't exist already
    if(!$db->table_exists('prompt_generator'))
    {
        switch($db->type)
        {
            case "pgsql":
                $db->write_query("CREATE TABLE ".TABLE_PREFIX."prompt_generator (
                    pid serial,
                    prompt varchar(2048) NOT NULL default '',
                    PRIMARY KEY (pid)
                );");
                break;
            case "sqlite":
                $db->write_query("CREATE TABLE ".TABLE_PREFIX."prompt_generator (
                    pid INTEGER PRIMARY KEY,
                    prompt varchar(2048) NOT NULL default ''
                );");
                break;
            default:
                $db->write_query("CREATE TABLE ".TABLE_PREFIX."prompt_generator (
                    pid int unsigned NOT NULL auto_increment,
                    prompt varchar(2048) NOT NULL default '',
                    PRIMARY KEY (pid)
                ) ENGINE=MyISAM{$collation};");
                break;
        }
    }
}

function promptGenerator_is_installed()
{
    global $db;
    return $db->table_exists('prompt_generator');
}

function promptGenerator_uninstall()
{
    global $db, $mybb;

    if($mybb->request_method != 'post')
    {
        global $page, $lang;

        $page->output_confirm_action('index.php?module=config-plugins&action=deactivate&uninstall=1&plugin=promptGenerator', $lang->promptGenerator_uninstall_message, $lang->promptGenerator_uninstall);
    }

    // Drop tables if desired
    if(!isset($mybb->input['no']))
    {
        $db->drop_table('prompt_generator');
    }
}

function promptGenerator_activate()
{

}

function promptGenerator_deactivate()
{

}