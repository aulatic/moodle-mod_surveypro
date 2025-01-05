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
 * sliders mform element
 *
 * Extends the core mform class for sliders element
 *
 * @package   surveyprofield_sliders
 * @copyright 2013 onwards kordan <stringapiccola@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir.'/form/sliders.php');

// @codingStandardsIgnoreFile

/**
 * sliders mform element
 *
 * Extends the core mform class for sliders element
 *
 * @package   surveyprofield_sliders
 * @copyright 2013 onwards kordan <stringapiccola@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class surveypromform_sliders extends \MoodleQuickForm_sliders {

    /**
     * Constructor.
     *
     * @param string $elementName Optional name of the sliders
     * @param string $elementLabel Optional sliders label
     * @param string $text Optional text to put after the sliders
     * @param mixed $attributes Optional either a typical HTML attribute string
     *              or an associative array
     */
    public function __construct($elementName=null, $elementLabel=null, $text=null, $attributes=null) {
        parent::__construct($elementName, $elementLabel, $text, $attributes);
    }

    /**
     * What to display when element is frozen.
     *
     * @return html of the frozen element
     */
    public function getFrozenHtml() {
        $output = parent::getFrozenHtml();

        if (isset($this->_attributes['class'])) {
            $pattern = 'disabled="disabled"';
            $class = $this->_attributes['class'];
            $replacement = 'disabled="disabled" class="'.$class.'"';
            $output = str_replace($pattern, $replacement, $output);
        }

        return $output;
    }
}
