<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library for surveyprofield_textareacarey
 *
 * @package   surveyprofield_textareacarey
 * @copyright 2013 onwards kordan <stringapiccola@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('SURVEYPROFIELD_textareacarey_DEFAULTCOLS', 60);
define('SURVEYPROFIELD_textareacarey_DEFAULTROWS', 10);

define('SURVEYPROFIELD_textareacarey_FILEAREA', 'textareacareycontent');

function textareacareycarey_get_materias_options($qid) {
    global $DB;

    // Initialize an empty array for the options.
    $options = array();

    // Validate that qid is not empty.
    if (empty($qid)) {
        return $options;
    }

    // Query the database to get the materias for the given qid.
    $records = $DB->get_records('surveypro_materias', array('qid' => $qid), '', 'id, fullname');

    // Populate the options array with id => fullname pairs.
    foreach ($records as $record) {
        $options[$record->id] = $record->fullname;
    }

    return $options;
}