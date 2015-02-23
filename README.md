moodle-local_coursechecklist
============================

Pre-release course 'best practice' checklist.

<h2>USAGE</h2>

When the checklist is visited from the 'module leader' block, the IDs of all relevant courses are passed in as parameters. If the ‘Yes’ option is clicked at the bottom of the checklist, this is recorded for all passed in courses and the link to the checklist will not be shown again in the course status block. If ‘No’ is clicked, there is no further action or effect, so the link will still be shown.

The checklist itself is a site page, and the ID of the page needs to be set in the settings .

<h2>INSTALLATION</h2>

This plugin and the moduleleader block are mutually dependant.

There are two parts to this:

1. The course checklist form, which is a plugin that should be installed in the local directory of the Moodle instance.
3. The course checklist itself, which is done as a site page.

To put live, install the local plugin and block. The page needs to be created (as a site page) on live. The ID of the page can then be entered in the course checklist settings (Site administration - Plugins - Local plugins - Course checklist).

The coursechecklist plugin will create a new database table, local_coursechecklist, which is used to track when a user has viewed the checklist for a given course or courses - see 
coursechecklist/db/install.xml.
