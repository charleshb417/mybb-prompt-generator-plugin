# mybb-prompt-generator-plugin
A random prompt generator plugin for MyBB.

## How to Install

1)  Copy the appropriate files over. From the root directory of the project:
    
    `cp -r inc/ <mybb_location>/inc/`
    
2) Go to the AdminCP Home page. Click "Plugins" on the sidebar.
3) There should be a "Random Prompt Generator" option. Click "Install and Activate".

4) Look for the "Prompt Generator" tab at the top of the page.

5) Click "Add Prompt" to add a prompt.

6) The list of prompts allows you to view, edit and delete.

7) To use the plugin, you must add the template to any of the following template groups:
    - New Thread
    - New Reply
    - Show Thread

8) The code to add the template is
` {$promptGenerator_reply} `

9) This will add a "Generate" button that picks a random prompt from the database. This button will not be show unless there is at least one prompt in the database.

10) Feel free to style to your liking. The css class is "promptGeneratorButton".

## How to edit in ModCP

1) Add the navlink template to the modcp_nav_forums_post template.

2) The code to add the template is
` {$promptGenerator_navlink} `

3) This should place a navigation link for "Prompt Generator" in the ModCP. This page will allow you to view, create, edit, and delete prompts.
