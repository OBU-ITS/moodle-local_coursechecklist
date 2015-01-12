moodle-local_coursechecklist
============================

Pre-release course 'best practice' checklist.

<h2>USAGE</h2>

<h3>Module leader block</h3>

The module leader (course status) block can be added to pages as any other block (suggest it be added to all /my pages, and promoted somewhere near or at the top).

It displays in the block all courses starting in the near future (see settings) for which the current user is in a teacher role and which are either not yet visible or have a small amount of content (see settings). If any of these courses have not been checked against the checklist, a link to the checklist is also displayed.

The settings of this block will set the number of days to look ahead to pick up courses (defaults to starting today, lookahead 90 days), and the number of activities required in a course before it will be flagged as needing content/rollover.

<h3>Checklist</h3>

When the checklist is visited from the above block, the IDs of all relevant courses are passed in as parameters. If the ‘Yes’ option is clicked at the bottom of the checklist, this is recorded for all passed in courses and the link to the checklist will not be shown again in the course status block. If ‘No’ is clicked, there is no further action or effect, so the link will still be shown.

The checklist itself is a site page, and the ID of the page needs to be set in the settings .

<h2>INSTALLATION</h2>

There are three parts to this:

1. The course checklist form, which is a plugin that should be installed in the local directory of the Moodle instance.
2. The ‘module leader’ or 'course status' block - blocks/moduleleader.
3. The course checklist itself, which is done as a site page.

To put live, install the local plugin and block. The page needs to be created (as a site page) on live. The ID of the page can then be entered in the course checklist settings (Site administration - Plugins - Local plugins - Course checklist).

The coursechecklist plugin will create a new database table, local_coursechecklist, which is used to track when a user has viewed the checklist for a given course or courses - see 
coursechecklist/db/install.xml.
