<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.");
}

if(defined('THIS_SCRIPT'))
{
    global $templatelist;

    if(THIS_SCRIPT== 'index.php')
    {
        $templatelist .= ',promptGenerator_reply';
    }
    elseif(THIS_SCRIPT== 'showthread.php')
    {
        $templatelist .= ',promptGenerator_reply';
    }
}

if(defined('IN_ADMINCP'))
{
    // TO DO
}
else
{
    $plugins->add_hook('postbit', 'promptGenerator_reply');
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

    // Delete template groups.
    $db->delete_query('templategroups', "prefix='promptGenerator'");

    // Delete templates belonging to template groups.
    $db->delete_query('templates', "title='promptGenerator' OR title LIKE 'promptGenerator_%'");

    // Drop tables if desired
    if(!isset($mybb->input['no']))
    {
        $db->drop_table('prompt_generator');
    }
}

function promptGenerator_activate()
{
    global $db, $lang;

    // promptGenerator_reply HTML
    $replyHTML = '<br\>
    <table border="0" 
        cellspacing="{$theme[\'borderwidth\']}" 
        cellpadding="{$theme[\'tablespace\']}" 
        class="tborder">
        <thead>
            <tr>
                <td class="thead">
                    <strong>{$lang->promptGenerator}</strong>
                </td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <input type="button" class="promptGeneratorButton">Generate</input>
            </tr>
        </tbody>
    </table><br/>';

    $templateArray = array(
        'reply' => $replyHTML
    );

    $group = array(
        'prefix' => $db->escape_string('promptGenerator'),
        'title' => $db->escape_string('Prompt Generator')
    );

    // Update or create template group:
    $query = $db->simple_select('templategroups', 'prefix', "prefix='{$group['prefix']}'");

    if($db->fetch_field($query, 'prefix'))
    {
        $db->update_query('templategroups', $group, "prefix='{$group['prefix']}'");
    }
    else
    {
        $db->insert_query('templategroups', $group);
    }

    // Query already existing templates.
    $query = $db->simple_select('templates', 'tid,title,template', "sid=-2 AND (title='{$group['prefix']}' OR title LIKE '{$group['prefix']}=_%' ESCAPE '=')");
    $templates = $duplicates = array();

    while($row = $db->fetch_array($query))
    {
        $title = $row['title'];
        $row['tid'] = (int)$row['tid'];

        if(isset($templates[$title]))
        {
            // PluginLibrary had a bug that caused duplicated templates.
            $duplicates[] = $row['tid'];
            $templates[$title]['template'] = false; // force update later
        }
        else
        {
            $templates[$title] = $row;
        }
    }

    // Delete duplicated master templates, if they exist.
    if($duplicates)
    {
        $db->delete_query('templates', 'tid IN ('.implode(",", $duplicates).')');
    }

    // Update or create templates.
    foreach($templateArray as $name => $code)
    {
        if(strlen($name))
        {
            $name = "promptGenerator_{$name}";
        }
        else
        {
            $name = "promptGenerator";
        }

        $template = array(
            'title' => $db->escape_string($name),
            'template' => $db->escape_string($code),
            'version' => 1,
            'sid' => -2,
            'dateline' => TIME_NOW
        );

        // Update
        if(isset($templates[$name]))
        {
            if($templates[$name]['template'] !== $code)
            {
                // Update version for custom templates if present
                $db->update_query('templates', array('version' => 0), "title='{$template['title']}'");

                // Update master template
                $db->update_query('templates', $template, "tid={$templates[$name]['tid']}");
            }
        }
        // Create
        else
        {
            $db->insert_query('templates', $template);
        }

        // Remove this template from the earlier queried list.
        unset($templates[$name]);
    }

    // Remove no longer used templates.
    foreach($templates as $name => $row)
    {
        $db->delete_query('templates', "title='{$db->escape_string($name)}'");
    }

    // Include this file because it is where find_replace_templatesets is defined
    require_once MYBB_ROOT.'inc/adminfunctions_templates.php';

    // Edit the index template and add our variable to above {$forums}
    find_replace_templatesets('index', '#'.preg_quote('{$forums}').'#', "{\$promptGenerator}\n{\$forums}");
}

function promptGenerator_deactivate()
{
    require_once MYBB_ROOT.'inc/adminfunctions_templates.php';

    // remove template edits
    find_replace_templatesets('index', '#'.preg_quote('{$promptGenerator}').'#', '');
}

/*
 * Displays the prompt generation button - depending on the setting.
 * @param $post Array containing information about the current post. Note: must be received by reference otherwise our changes are not preserved.
*/
function promptGenerator_reply(&$post)
{
    global $settings; //TODO
    global $lang, $templates;

    
}