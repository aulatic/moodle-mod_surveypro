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
 * Surveypro pluginform class.
 *
 * @package   surveyprofield_careybutton
 * @copyright 2013 onwards kordan <stringapiccola@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace surveyprofield_careybutton;

defined('MOODLE_INTERNAL') || die();

use mod_surveypro\utility_item;
use mod_surveypro\local\form\item_setupbaseform;

require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/mod/surveypro/field/careybutton/lib.php');

/**
 * The class representing the plugin form
 *
 * @package   surveyprofield_careybutton
 * @copyright 2013 onwards kordan <stringapiccola@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class itemsetupform extends item_setupbaseform {

    /**
     * Definition.
     *
     * @return void
     */
    public function definition() {
        // Start with common section of the form.
        parent::definition();

        $mform = $this->_form;

        // Get _customdata.
        $item = $this->_customdata['item'];

        $textareaoptions = ['wrap' => 'virtual', 'rows' => '10', 'cols' => '65'];

        // Item: options.
        $fieldname = 'options';
        $mform->addElement('textarea', $fieldname, get_string($fieldname, 'surveyprofield_careybutton'), $textareaoptions);
        $mform->addHelpButton($fieldname, $fieldname, 'surveyprofield_careybutton');
        $mform->addRule($fieldname, get_string('required'), 'required', null, 'client');
        $mform->setType($fieldname, PARAM_RAW); // PARAM_RAW and not PARAM_TEXT otherwise '<' is not accepted.

        // Item: labelother.
        $fieldname = 'labelother';
        $attributes = ['maxlength' => '64', 'size' => '50'];
        $mform->addElement('text', $fieldname, get_string($fieldname, 'surveyprofield_careybutton'), $attributes);
        $mform->addHelpButton($fieldname, $fieldname, 'surveyprofield_careybutton');
        $mform->setType($fieldname, PARAM_TEXT);

        // Item: defaultoption.
        $fieldname = 'defaultoption';
        $customdefaultstr = get_string('customdefault', 'surveyprofield_careybutton');
        $invitedefaultstr = get_string('invitedefault', 'mod_surveypro');
        $noanswerstr = get_string('noanswer', 'mod_surveypro');
        $elementgroup = [];
        $elementgroup[] = $mform->createElement('radio', $fieldname, '', $customdefaultstr, SURVEYPRO_CUSTOMDEFAULT);
        $elementgroup[] = $mform->createElement('radio', $fieldname, '', $invitedefaultstr, SURVEYPRO_INVITEDEFAULT);
        $elementgroup[] = $mform->createElement('radio', $fieldname, '', $noanswerstr, SURVEYPRO_NOANSWERDEFAULT);
        $mform->addGroup($elementgroup, $fieldname.'_group', get_string($fieldname, 'surveyprofield_careybutton'), ' ', false);
        $mform->setDefault($fieldname, SURVEYPRO_INVITEDEFAULT);
        $mform->addHelpButton($fieldname.'_group', $fieldname, 'surveyprofield_careybutton');

        // Item: defaultvalue.
        $fieldname = 'defaultvalue';
        $elementgroup = [];
        $mform->addElement('text', $fieldname, '');
        $mform->disabledIf($fieldname, 'defaultoption', 'neq', SURVEYPRO_CUSTOMDEFAULT);
        $mform->setType($fieldname, PARAM_RAW);

        // Item: downloadformat.
        $fieldname = 'downloadformat';
        $options = $item->get_downloadformats();
        $mform->addElement('select', $fieldname, get_string($fieldname, 'surveyprofield_careybutton'), $options);
        $mform->setDefault($fieldname, $item->get_friendlyformat());
        $mform->addHelpButton($fieldname, $fieldname, 'surveyprofield_careybutton');
        $mform->setType($fieldname, PARAM_INT);

        // Item: adjustment.
        $fieldname = 'adjustment';
        $options = [];
        $options[SURVEYPRO_HORIZONTAL] = get_string('horizontal', 'surveyprofield_careybutton');
        $options[SURVEYPRO_VERTICAL] = get_string('vertical', 'surveyprofield_careybutton');
        $mform->addElement('select', $fieldname, get_string($fieldname, 'surveyprofield_careybutton'), $options);
        $mform->setDefault($fieldname, SURVEYPRO_VERTICAL);
        $mform->addHelpButton($fieldname, $fieldname, 'surveyprofield_careybutton');
        $mform->setType($fieldname, PARAM_TEXT);

        // Here I open a custom fieldset.
        $mform->addElement('header', 'carey', 'Opciones especiales CAREY Estudios');

        // New Item: dimension.
        $fieldname = 'dimension';
        $options = [
            'usointerno' => 'Uso Interno',
            'conocimiento' => get_string('conocimiento', 'surveyprofield_careybutton'),
            'percepcion' => get_string('percepcion', 'surveyprofield_careybutton'),
            'cumplimiento' => get_string('compliance', 'surveyprofield_careybutton')
        ];
        $mform->addElement('select', $fieldname, get_string($fieldname, 'surveyprofield_careybutton'), $options);
        $mform->setType($fieldname, PARAM_TEXT);


        // New Item: idmateria.
        $qid = 'PDP';
        $fieldname = 'idmateria';
        $options = ['0' => 'Información de la Organización'] + careybutton_get_materias_options($qid); // Assuming $item has qid property.
        $mform->addElement('select', $fieldname, get_string($fieldname, 'surveyprofield_careybutton'), $options);
        $mform->setType($fieldname, PARAM_INT);

        // New Item: puntajemin.
        $fieldname = 'puntajemin';
        $mform->addElement('text', $fieldname, get_string($fieldname, 'surveyprofield_careybutton'), ['maxlength' => '4', 'size' => '4']);
        $mform->setType($fieldname, PARAM_INT);

        // New Item: puntajemax.
        $fieldname = 'puntajemax';
        $mform->addElement('text', $fieldname, get_string($fieldname, 'surveyprofield_careybutton'), ['maxlength' => '4', 'size' => '4']);
        $mform->setType($fieldname, PARAM_INT);

        // New Item: peso.
        $fieldname = 'peso';
        $mform->addElement('text', $fieldname, get_string($fieldname, 'surveyprofield_careybutton'), ['maxlength' => '4', 'size' => '4']);
        $mform->setType($fieldname, PARAM_INT);

        $this->add_item_buttons();
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array $errors
     */
    public function validation($data, $files) {
        // Get _customdata.
        $item = $this->_customdata['item'];
        $surveypro = $item->surveypro;

        $cm = $item->get_cm();

        $errors = parent::validation($data, $files);

        // Clean inputs.
        // First of all get the value from the field.
        $utilityitemman = new utility_item($cm, $surveypro);
        $cleanoptions = $utilityitemman->multilinetext_to_array($data['options']);
        $cleanlabelother = trim($data['labelother']);
        $cleandefaultvalue = isset($data['defaultvalue']) ? trim($data['defaultvalue']) : '';

        // Build $value and $label arrays starting from $cleanoptions and $cleanlabelother.
        $values = [];
        $labels = [];

        foreach ($cleanoptions as $option) {
            if (strpos($option, SURVEYPRO_VALUELABELSEPARATOR) === false) {
                $values[] = trim($option);
                $labels[] = trim($option);
            } else {
                $pair = explode(SURVEYPRO_VALUELABELSEPARATOR, $option);
                $values[] = $pair[0];
                $labels[] = $pair[1];
            }
        }
        if (!empty($cleanlabelother)) {
            if (strpos($cleanlabelother, SURVEYPRO_OTHERSEPARATOR) === false) {
                $values[] = '';
                $labels[] = $cleanlabelother;
            } else {
                $pair = explode(SURVEYPRO_OTHERSEPARATOR, $cleanlabelother);
                $values[] = $pair[1];
                $labels[] = $pair[0];
            }
        }

        // First check.
        // Each single value has to be unique.
        $arrayunique = array_unique($values);
        if (count($values) != count($arrayunique)) {
            $errors['options'] = get_string('ierr_valuesduplicated', 'surveyprofield_careybutton');
        }
        // Each single label has to be unique.
        $arrayunique = array_unique($labels);
        if (count($labels) != count($arrayunique)) {
            $errors['options'] = get_string('ierr_labelsduplicated', 'surveyprofield_careybutton');
        }

        // Second check.
        // Editing teacher can not set "noanswer" as default option if the item is mandatory.
        if ( ($data['defaultoption'] == SURVEYPRO_NOANSWERDEFAULT) && isset($data['required']) ) {
            $a = get_string('noanswer', 'mod_surveypro');
            $errors['defaultoption_group'] = get_string('ierr_notalloweddefault', 'mod_surveypro', $a);
        }

        if ($data['defaultoption'] == SURVEYPRO_CUSTOMDEFAULT) {
            if (empty($data['defaultvalue'])) {
                // Third check.
                // User asks for SURVEYPRO_CUSTOMDEFAULT but doesn't provide it.
                $a = get_string('invitedefault', 'mod_surveypro');
                $errors['defaultoption_group'] = get_string('ierr_missingdefault', 'surveyprofield_careybutton', $a);
            } else {
                // Fourth check.
                // Each item of default has to also be among options OR has to be == to otherlabel value.
                if (!in_array($cleandefaultvalue, $labels)) {
                    $a = $cleandefaultvalue;
                    $errors['defaultvalue'] = get_string('ierr_foreigndefaultvalue', 'surveyprofield_careybutton', $a);
                }
            }
        }

        return $errors;
    }
}
