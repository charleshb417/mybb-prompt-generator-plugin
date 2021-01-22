<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.");
}

if(defined('THIS_SCRIPT'))
{
    global $templatelist;

    if(in_array(THIS_SCRIPT, array('showthread.php', 'newreply.php')))
    {
        $templatelist .= ',promptGenerator_reply';
    }

    if(in_array(THIS_SCRIPT, array('modcp.php')))
    {
        $templatelist .= ',promptGenerator_navlink';
    }

}

$plugins->add_hook('newthread_start', 'promptGenerator_reply');
$plugins->add_hook('newreply_start', 'promptGenerator_reply');
$plugins->add_hook('showthread_start', 'promptGenerator_reply');
$plugins->add_hook('modcp_nav', 'promptGenerator_modcp_link');


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
                <td class="trow1">
                    <input 
                        type="button" 
                        class="promptGeneratorButton" 
                        onclick="promptGenerator.handlePromptGeneratorClick()"
                        value="Generate">
                    </input>
                    <span id="promptGenerator_output"></span>
                </td>
            </tr>
        </tbody>
    </table><br/>';

    $navlinkHTML = '<tr>
     <td class="trow1 smalltext"><a href="modcp.php?action=promptGenerator" class="modcp_nav_item">Prompt Generator</a></td>
    </tr>';

    $modcpContentHTML = '
    <html>
        <head>
            <title>Prompt Generator</title>
            {$headerinclude}
        </head>
        <body>
            {$header}
            <table width="100%" border="0" align="center">
                <tr>
                {$modcp_nav}
                    <td valign="top">
                        <div class="promptGeneratorLinkGroup">
                            <a href="/modcp.php?action=promptGenerator" class="promptGeneratorLink">Prompt List</a>
                            <a href="/modcp.php?action=promptGenerator&do=create" class="promptGeneratorLink">Add Prompt</a>
                        </div>        
                        <table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
                            <tr>
                                <td class="thead" colspan="4"><strong>Prompts</strong></td>
                            </tr>
                            {$promptGenerator_table_content}
                        </table> 
                    </td>
                </tr>
            </table>
            {$footer}
        </body>
    </html>';

    $templateArray = array(
        'reply' => $replyHTML,
        'navlink' => $navlinkHTML,
        'modcp_content' => $modcpContentHTML
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
    global $db, $lang, $templates, $promptGenerator_reply;

    if(!isset($lang->promptGenerator))
    {
        $lang->load('promptGenerator');
    }

    $query = $db->simple_select('prompt_generator', 'prompt', '', array('order_by' => 'pid', 'order_dir' => 'DESC'));

    if ($db->num_rows($query)){
        // Generate button handling JavaScript on the fly
        $js = '
        <script type="text/javascript">
            let promptGenerator = (function(){
            let self = this;
            let prompts = [];';

            while($prompt = $db->fetch_field($query, 'prompt'))
            {
                $prompt = htmlspecialchars_uni($prompt);
                $js .= 'prompts.push("' . $prompt . '");';
            }

            $js .=  
                'this.handlePromptGeneratorClick = function(){
                    if (prompts.length > 0){
                        const i = prompts[Math.floor(Math.random() * prompts.length)];
                        document.getElementById("promptGenerator_output").innerHTML = i;   
                    } else {
                            // This should never happen
                        document.getElementById("promptGenerator_output").innerHTML = "No prompts exist yet! Let an admin know that you need inspiration.";
                    }
                }
                return self;
                })();
            </script>';

        echo htmlspecialchars_decode($js, ENT_NOQUOTES);
        $promptGenerator_reply = eval($templates->render('promptGenerator_reply'));
    }
}

function promptGenerator_modcp_link() {
    global $mybb, $templates, $promptGenerator_navlink;
    global $nav_edittags;

    $promptGenerator_navlink = eval($templates->render('promptGenerator_navlink'));
}


$plugins->add_hook('modcp_start', 'promptGenerator_modcp');
function promptGenerator_modcp() 
{
    global $mybb, $lang, $db, $page, $templates, $theme;

    global $header, $headerinclude, $modcp_nav, $footer;

    if ($mybb->get_input('action') == 'promptGenerator')
    {
        $table = '';
        $do = '';
        if ($mybb->get_input('do')){
            $do = strtolower($mybb->get_input('do'));
        }

        if ($do == 'edit' && $mybb->get_input('pid'))
        {
            $pid = $mybb->get_input('pid');
            if ($mybb->request_method == 'post')
            {
                if(!trim($mybb->input['prompt']))
                {
                    $error = $lang->promptGenerator_error_missing_title;
                }
                else
                {
                    // trim excess whitespace
                    $p = trim($mybb->input['prompt']);

                    // replace newlines with spaces
                    $p = trim(preg_replace('/\s\s+/', ' ', $p));

                    if (strlen($p) > 2048){
                        $error = $lang->promptGenerator_error_too_long;
                    }

                    if(!$error)
                    {
                        $db->update_query("prompt_generator", array("prompt"=>$p), "pid='{$mybb->input['pid']}'");
                    }
                    
                    header("Location: /modcp.php?action=promptGenerator");
                }
            }
            else {
                $query = $db->simple_select("prompt_generator", "*", "pid='{$pid}'");
                $prompt = $db->fetch_array($query);

                if ($prompt['pid'])
                {
                    $table .= 
                    '<tr><td class="first" colspan="4">
                        <form action="modcp.php?action=promptGenerator&amp;do=edit&amp;pid='. $pid .'" method="POST">
                            <label for="prompt">Prompt</label><br/>
                            <textarea 
                                id="promptGenerator_prompt" 
                                name="prompt" 
                                rows="5" cols="125" 
                                maxlength="2048" required>' . $prompt['prompt'] . '</textarea>
                            <br/>
                            <input type="submit" value="Edit Prompt"/>
                        </form>
                    </td></tr>';
                }
                else
                {
                    header("Location: /modcp.php?action=promptGenerator");
                }
                
            }
        }
        else if ($do == 'delete' && $mybb->get_input('pid'))
        {
            $pid = $mybb->get_input('pid');
            if ($mybb->request_method == 'post')
            {
                $query = $db->simple_select("prompt_generator", "*", "pid='{$mybb->input['pid']}'");
                $prompt = $db->fetch_array($query);

                // Does the prompt not exist?
                if($prompt['pid'])
                {
                    $db->delete_query('prompt_generator', "pid='{$mybb->input['pid']}'");
                }
            
                header("Location: /modcp.php?action=promptGenerator");  
            }
            else
            {
                $table .= '
                <tr>
                    <td class="first promptGenerator_confirm">Are you sure that you want to delete this prompt?</td>
                </tr>
                <tr>
                    <td class="first">
                        <form action="modcp.php?action=promptGenerator&amp;do=delete&amp;pid=' . $pid . '" method="POST">
                            <input type="submit" value="Delete Prompt"/>
                        </form>
                    </td>
                    <td>
                        <form action="modcp.php" method="GET">
                            <input type="hidden" name="action" value="promptGenerator"/>
                            <input type="submit" value="Go Back"/>
                        </form>
                    </td>
                </tr>
                ';
            }
            
        }
        else if ($do == 'create'){
            if($mybb->request_method == 'post')
            {
                if(!trim($mybb->input['prompt']))
                {
                    $error = $lang->promptGenerator_error_missing_title;
                }
                else
                {
                    // trim excess whitespace
                    $p = trim($mybb->input['prompt']);

                    // replace newlines with spaces
                    $p = trim(preg_replace('/\s\s+/', ' ', $p));

                    if (strlen($p) > 2048){
                        $error = $lang->promptGenerator_error_too_long;
                    }
                }

                if (!$error)
                {
                    $insert_array = array(
                        "prompt" => $db->escape_string($p)
                    );

                    $pid = $db->insert_query("prompt_generator", $insert_array);

                    $table .= '
                    <tr>
                        <td class="first promptGenerator_success">Prompt successfully created!</td>
                    </tr>
                    ';
                }
                else
                {
                    $table .= 
                    '<tr>
                        <td class="first promptGenerator_error">' . $error . '</td>
                    </tr>';
                }
            }
            
            $table .= 
            '<tr><td class="first" colspan="4">
                <form action="modcp.php?action=promptGenerator&amp;do=create" method="POST">
                    <label for="prompt">Prompt</label><br/>
                    <textarea id="promptGenerator_prompt" name="prompt" rows="5" cols="125" maxlength="2048" required></textarea>
                    <br/>
                    <input type="submit" value="Add Prompt"/>
                </form>
            </td></tr>';
            
        }
        else
        {
            $query = $db->simple_select('prompt_generator', '*', '', array('order_by' => 'pid', 'order_dir' => 'DESC'));

            if($db->num_rows($query))
            {
                while($row = $db->fetch_array($query))
                {
                    $table .= '<tr><td class="first" colspan="2">'. $row['prompt'] .'</td>';

                    $pid = $row['pid'];

                    $editLink = '<a href="modcp.php?action=promptGenerator&amp;do=edit&amp;pid=' . $pid . '">Edit</a>';
                    $deleteLink = '<a href="modcp.php?action=promptGenerator&amp;do=delete&amp;pid=' . $pid . '">Delete</a>';

                    $table .= '<td class="alt_col">' . $editLink . '</td>';
                    $table .= '<td class="last">' . $deleteLink . '</td></tr>';
                }
            } 
            else 
            {
                $table .= '<tr><td>There aren\'t any prompts yet!</td></tr>';
            }
        }
        
        $promptGenerator_table_content = $table;

        add_breadcrumb($lang->nav_modcp, "modcp.php");

        eval("\$promptGenerator_modcp_content = \"".$templates->get("promptGenerator_modcp_content")."\";");
        output_page($promptGenerator_modcp_content);
    }
}