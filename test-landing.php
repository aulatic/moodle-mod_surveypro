<?php

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/careylib.php');

// Ensure you have access to Moodle's global configuration and libraries.
require_once($CFG->libdir . '/filelib.php');

//Get the user id from the actual user
$userid = $USER->id;


// Get the file storage instance.
$fs = get_file_storage();

// Get the user context for the given user id.
$context = context_user::instance($userid);

// Retrieve all files in the specified file area. 
// The parameters are: contextid, component, filearea, itemid, sort order, and whether to include directories.
$files = $fs->get_area_files($context->id, 'profilefield_file', 'files_3', 0, 'sortorder, id', false);

// Loop through each file and generate its URL.
foreach ($files as $file) {
    $fileurl = moodle_url::make_pluginfile_url(
        $file->get_contextid(),
        $file->get_component(),
        $file->get_filearea(),
        $file->get_itemid(),
        $file->get_filepath(),
        $file->get_filename()
    );
    echo $fileurl . "<br />";
}

