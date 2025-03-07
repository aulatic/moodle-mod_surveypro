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
 * This file contains the surveyprofield_careybutton
 *
 * @package   surveyprofield_careybutton
 * @copyright 2013 onwards kordan <stringapiccola@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace surveyprofield_careybutton;

defined('MOODLE_INTERNAL') || die();

use mod_surveypro\itembase;
use mod_surveypro\utility_item;

require_once($CFG->dirroot.'/mod/surveypro/field/careybutton/lib.php');

/**
 * Class to manage each aspect of the careybutton item
 *
 * @package   surveyprofield_careybutton
 * @copyright 2013 onwards kordan <stringapiccola@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item extends itembase {

    /**
     * @var string $content
     */
    public $content = '';

    /**
     * @var string $contentformat
     */
    public $contentformat = '';

    /**
     * @var string Custom number of the item
     *
     * It usually is 1, 1.1, a, 2.1.a..
     */
    protected $customnumber;

    /**
     * @var int SURVEYPRO_POSITIONLEFT, SURVEYPRO_POSITIONTOP or SURVEYPRO_POSITIONFULLWIDTH
     */
    protected $position;

    /**
     * @var string Optional text with item custom note
     */
    protected $extranote;

    /**
     * @var bool 0 => optional item; 1 => mandatory item;
     */
    protected $required;

    /**
     * @var string Name of the field storing data in the db table
     */
    protected $variable;

    /**
     * @var int Indent of the item in the form page
     */
    protected $indent;

    /**
     * @var string List of options in the form of "$value SURVEYPRO_VALUELABELSEPARATOR $label"
     */
    protected $options;

    /**
     * @var string Value of the default setting (invite, custom...)
     */
    protected $defaultoption;

    /**
     * @var string Text label for the optional option "other" in the form of "$value SURVEYPRO_OTHERSEPARATOR $label"
     */
    protected $labelother;

    /**
     * @var string Value of the field when the form is initially displayed
     */
    protected $defaultvalue;

    /**
     * @var string Format of the content once downloaded
     */
    protected $downloadformat;

    /**
     * @var bool Orientation of the list of options
     */
    protected $adjustment;

    /**
     * @var bool Can this item be parent?
     */
    protected static $canbeparent = true;

    /**
     * Class constructor.
     *
     * If itemid is provided, load the object (item + base + plugin) from database
     * If evaluateparentcontent is true, load the parentitem parentcontent property too
     *
     * @param \stdClass $cm
     * @param object $surveypro
     * @param int $itemid Optional item ID
     * @param bool $getparentcontent True to include $item->parentcontent (as decoded by the parent item) too, false otherwise
     */
    public function __construct($cm, $surveypro, $itemid, $getparentcontent) {
        parent::__construct($cm, $surveypro, $itemid, $getparentcontent);

        // List of properties set to static values.
        $this->type = SURVEYPRO_TYPEFIELD;
        $this->plugin = 'careybutton';

        // Override the list of fields using format, whether needed.
        // Nothing to override, here.

        // Other element specific properties.
        // No properties here.

        // Override properties depending from $surveypro settings.
        // No properties here.

        // List of fields I do not want to have in the item definition form.
        $this->insetupform['hideinstructions'] = false;

        if (!empty($itemid)) {
            $this->item_load($itemid, $getparentcontent);
        }
    }

    /**
     * Item load.
     *
     * @param int $itemid
     * @param bool $getparentcontent True to include $item->parentcontent (as decoded by the parent item) too, false otherwise
     * @return void
     */
    public function item_load($itemid, $getparentcontent) {
        parent::item_load($itemid, $getparentcontent);

        // Multilang load support for builtin surveypro.
        // Whether executed, the 'content' field is ALWAYS handled.
        $this->item_builtin_string_load_support();

        $this->item_custom_fields_to_form();
    }

    /**
     * Item save.
     *
     * @param object $record
     * @return void
     */
    public function item_save($record) {
        $this->get_common_settings($record);

        // Now execute very specific plugin level actions.

        // Begin of: plugin specific settings (eventually overriding general ones).
        // Drop empty rows and trim trialing spaces from each row of each textarea field.
        $fieldlist = ['options'];
        $this->item_clean_textarea_fields($record, $fieldlist);

        // Set custom fields value as defined for this question plugin.
        $this->item_custom_fields_to_db($record);
        // End of: plugin specific settings (eventually overriding general ones).

        // Do parent item saving stuff here (mod_surveypro_itembase::item_save($record))).
        return parent::item_save($record);
    }

    /**
     * Item add mandatory plugin fields
     * Copy mandatory fields to $record
     *
     * @param \stdClass $record
     * @return void
     */
    public function item_add_mandatory_plugin_fields(&$record) {
        $record->content = 'Radio buttons';
        $record->contentformat = 1;
        $record->position = 0;
        $record->required = 0;
        $record->variable = 'careybutton_001';
        $record->indent = 0;
        $record->options = "first\nsecond";
        $record->defaultoption = SURVEYPRO_INVITEDEFAULT;
        $record->downloadformat = SURVEYPRO_ITEMRETURNSLABELS;
        $record->adjustment = SURVEYPRO_VERTICAL;
    }

    /**
     * Prepare values for the mform of this item.
     *
     * @return void
     */
    public function item_custom_fields_to_form() {
        // 1. Special management for composite fields.
        // Nothing to do: they don't exist in this plugin.
    }

    /**
     * Traslate values from the mform of this item to values for corresponding properties.
     *
     * @param object $record
     * @return void
     */
    public function item_custom_fields_to_db($record) {
        // 1. Special management for composite fields.
        // Nothing to do: they don't exist in this plugin.

        // 2. Override few values.
        // Hideinstructions is set by design.
        $record->hideinstructions = 1;

        // 3. Set values corresponding to checkboxes.
        // Take care: 'required', 'hideinstructions' were already considered in get_common_settings.
        // Nothing to do: no checkboxes in this plugin item form.

        // 4. Other.
    }

    /**
     * Make the list of constraints the child has to respect in order to create a valid relation
     *
     * @return list of contraints of the plugin (as parent) in text format
     */
    public function item_list_constraints() {
        $constraints = [];

        $labelsep = get_string('labelsep', 'langconfig'); // Separator usually is ': '.
        $values = $this->get_content_array(SURVEYPRO_VALUES, 'options');
        $optionstr = get_string('option', 'surveyprofield_careybutton');
        foreach ($values as $value) {
            $constraints[] = $optionstr.$labelsep.$value;
        }
        if (!empty($this->labelother)) {
            $labelotherstr = get_string('labelother', 'surveyprofield_careybutton');
            $allowedstr = get_string('allowed', 'surveyprofield_careybutton');
            $constraints[] = $labelotherstr.$labelsep.$allowedstr;
        }

        return implode('<br>', $constraints);
    }

    // MARK get.

    /**
     * Is this item available as a parent?
     *
     * @return the content of the static property "canbeparent"
     */
    public static function get_canbeparent() {
        return self::$canbeparent;
    }

    /**
     * Get the content of the downloadformats menu of the item setup form.
     *
     * @return array of downloadformats
     */
    public function get_downloadformats() {
        $options = [];

        $options[SURVEYPRO_ITEMSRETURNSVALUES] = get_string('returnvalues', 'surveyprofield_careybutton');
        $options[SURVEYPRO_ITEMRETURNSLABELS] = get_string('returnlabels', 'surveyprofield_careybutton');
        $options[SURVEYPRO_ITEMRETURNSPOSITION] = get_string('returnposition', 'surveyprofield_careybutton');

        return $options;
    }

    /**
     * Get the format recognized (without any really good reason) as friendly.
     *
     * @return the friendly format
     */
    public function get_friendlyformat() {
        return SURVEYPRO_ITEMRETURNSLABELS;
    }

    /**
     * Make the list of the fields using multilang
     *
     * @return array of felds
     */
    public function get_multilang_fields() {
        $fieldlist = [];
        $fieldlist[$this->plugin] = ['content', 'extranote', 'options', 'labelother', 'defaultvalue'];

        return $fieldlist;
    }

    /**
     * Get if the plugin uses the position of options to save user answers.
     *
     * @return bool The plugin uses the position of options to save user answers.
     */
    public function get_uses_positional_answer() {
        return true;
    }

    /**
     * Return the xml schema for surveypro_<<plugin>> table.
     *
     * @return string $schema
     */
    public static function get_plugin_schema() {
        $schema = <<<EOS
<?xml version="1.0" encoding="UTF-8"?>
<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema" elementFormDefault="qualified">
    <xs:element name="surveyprofield_careybutton">
        <xs:complexType>
            <xs:sequence>
                <xs:element name="content" type="xs:string"/>
                <xs:element name="embedded" minOccurs="0" maxOccurs="unbounded">
                    <xs:complexType>
                        <xs:sequence>
                            <xs:element name="filename" type="xs:string"/>
                            <xs:element name="filecontent" type="xs:base64Binary"/>
                        </xs:sequence>
                    </xs:complexType>
                </xs:element>
                <xs:element name="contentformat" type="xs:int"/>

                <xs:element name="required" type="xs:int"/>
                <xs:element name="indent" type="xs:int"/>
                <xs:element name="position" type="xs:int"/>
                <xs:element name="customnumber" type="xs:string" minOccurs="0"/>
                <!-- <xs:element name="hideinstructions" type="xs:int"/> -->
                <xs:element name="variable" type="xs:string"/>
                <xs:element name="extranote" type="xs:string" minOccurs="0"/>

                <xs:element name="options" type="xs:string"/>
                <xs:element name="labelother" type="xs:string" minOccurs="0"/>
                <xs:element name="defaultoption" type="xs:int"/>
                <xs:element name="defaultvalue" type="xs:string" minOccurs="0"/>
                <xs:element name="downloadformat" type="xs:int"/>
                <xs:element name="adjustment" type="xs:int"/>
            </xs:sequence>
        </xs:complexType>
    </xs:element>
</xs:schema>
EOS;

        return $schema;
    }

    // MARK parent.

    /**
     * Translate the parentcontent of the child item to the corresponding parentvalue.
     *
     * @param string $childparentcontent
     * return string childparentvalue
     */
    public function parent_encode_child_parentcontent($childparentcontent) {
        $utilityitemman = new utility_item($this->cm, $this->surveypro);
        $parentcontents = array_unique($utilityitemman->multilinetext_to_array($childparentcontent));
        $values = $this->get_content_array(SURVEYPRO_VALUES, 'options');

        $childparentvalue = [];
        $labels = [];
        foreach ($parentcontents as $parentcontent) {
            $key = array_search($parentcontent, $values);
            if ($key !== false) {
                $childparentvalue[] = $key;
            } else {
                $labels[] = $parentcontent;
            }
        }
        if (!empty($labels)) {
            $childparentvalue[] = '>';
            $childparentvalue = array_merge($childparentvalue, $labels);
        }

        return implode(SURVEYPRO_DBMULTICONTENTSEPARATOR, $childparentvalue);
    }

    /**
     * I can not make ANY assumption about $childparentvalue because of the following explanation:
     * At child save time, I encode its $parentcontent to $parentvalue.
     * The encoding is done through a parent method according to parent values.
     * Once the child is saved, I can return to parent and I can change it as much as I want.
     * For instance by changing the number and the content of its options
     * At parent save time, the child parentvalue is rewritten
     * -> but it may result in a too short or too long list of keys
     * -> or with a wrong number of unrecognized keys so I need to..
     * ...implement all possible checks to avoid crashes/malfunctions during code execution
     *
     * this method decodes parentindex to parentcontent
     *
     * @param string $childparentvalue
     * return string $childparentcontent
     */
    public function parent_decode_child_parentvalue($childparentvalue) {

        $values = $this->get_content_array(SURVEYPRO_VALUES, 'options');
        $parentvalues = explode(SURVEYPRO_DBMULTICONTENTSEPARATOR, $childparentvalue);
        $actualcount = count($parentvalues);
		
		error_log("Reached parent_decode_child_parentvalue point in the script, variable x is: " . $childparentvalue, 3, "/tmp/carey.log");

        $childparentcontent = [];
        $key = array_search('>', $parentvalues);
        if ($key !== false) {
            for ($i = 0; $i < $key; $i++) {
                $k = $parentvalues[$i];
                if (isset($values[$k])) {
                    $childparentcontent[] = $values[$k];
                } else {
                    $childparentcontent[] = $k;
                }
            }

            $key++;
            // Only garbage after the first label, but user wrote it.
            for ($i = $key; $i < $actualcount; $i++) {
                $childparentcontent[] = $parentvalues[$i];
            }
        } else {
            foreach ($parentvalues as $parentvalue) {
                if (isset($values[$parentvalue])) {
                    $childparentcontent[] = $values[$parentvalue];
                } else {
                    $childparentcontent[] = $parentvalue;
                }
            }
        }

        return implode("\n", $childparentcontent);
    }

    /**
     * This method, starting from child parentvalue (index/es), declares if the child could be included in the surveypro.
     *
     * @param string $childparentvalue
     * @return status of child relation
     *     0 = it will never match
     *     1 = OK
     *     2 = $childparentvalue is malformed
     */
/*     public function parent_validate_child_constraints($childparentvalue) {
        // Split the parent values into an array (e.g., "1;2" becomes [1, 2]).
        $parentvalues = explode(SURVEYPRO_DBMULTICONTENTSEPARATOR, $childparentvalue);

        // Retrieve the possible values for this item.
        $values = $this->get_content_array(SURVEYPRO_VALUES, 'options');

        error_log("Reached parent_validate_child_constraints point in the script, variable x is: " . $childparentvalue, 3, "/hostvirtual/aula.cloud/careydatos/temp/carey.log");
    
        // Iterate through each of the parent values to validate them.
        foreach ($parentvalues as $value) {
            echo "Checking if value ($value) is a valid parent...<br>";
            
            if (isset($values[$value])) {
                // If the value exists in the options, the condition is met.
                echo "Value ($value) is valid, returning CONDITION_OK.<br>";
                die(); // Stop further execution here for debugging purposes.
                return SURVEYPRO_CONDITIONOK;
            }
        }

        // If none of the parent values match, return a 'never match' condition.
        echo "No valid parents found, returning CONDITION_NEVER_MATCH.<br>";
        die(); // Stop further execution here for debugging purposes.
        return SURVEYPRO_CONDITIONNEVERMATCH;
    } */

    public function parent_validate_child_constraints($childparentvalue) {

        error_log("Reached parent_validate_child_constraints\n", 3, "/hostvirtual/aula.cloud/careydatos/temp/carey.log");
        return SURVEYPRO_CONDITIONOK;
    }
	
	

    // MARK userform.

    /**
     * Define the mform element for the userform and the searchform.
     *
     * @param \moodleform $mform
     * @param bool $searchform
     * @param bool $readonly
     * @return void
     */
    public function userform_mform_element($mform, $searchform, $readonly) {
        $choosedotsstr = get_string('choosedots');
        $starsstr = get_string('star', 'mod_surveypro');
        $noanswerstr = get_string('noanswer', 'mod_surveypro');
        if ($this->position == SURVEYPRO_POSITIONLEFT) {
            $elementlabel = $this->get_contentwithnumber();
        } else {
            $elementlabel = '&nbsp;';
        }

        $idprefix = 'id_surveypro_field_careybutton_'.$this->sortindex;

        $attributes = [];
        $attributes['class'] = 'indent-'.$this->indent.' careybutton_radio';

        $elementgroup = [];

        // Begin of: mform element.
        if (!$searchform) {
            if ($this->defaultoption == SURVEYPRO_INVITEDEFAULT) {
                $attributes['id'] = $idprefix.'_invite';
                $elementgroup[] = $mform->createElement('mod_surveypro_careybutton', $this->itemname, '',
                                                        $choosedotsstr, SURVEYPRO_INVITEVALUE, $attributes);
                if ($this->adjustment == SURVEYPRO_HORIZONTAL) {
                    $attributes['class'] = 'careybutton_radio';
                }
            }
        } else {
            $attributes['id'] = $idprefix.'_ignoreme';
            $elementgroup[] = $mform->createElement('mod_surveypro_careybutton', $this->itemname, '',
                                                    $starsstr, SURVEYPRO_IGNOREMEVALUE, $attributes);
            if ($this->adjustment == SURVEYPRO_HORIZONTAL) {
                $attributes['class'] = 'careybutton_radio';
            }
        }

        $labels = $this->get_content_array(SURVEYPRO_LABELS, 'options');
        foreach ($labels as $k => $label) {
            $attributes['id'] = $idprefix.'_'.$k;
            $elementgroup[] = $mform->createElement('mod_surveypro_careybutton', $this->itemname, '', $label, "$k", $attributes);
            if ($this->adjustment == SURVEYPRO_HORIZONTAL) {
                $attributes['class'] = 'careybutton_radio';
            }
        }

        if (!empty($this->labelother)) {
            [$othervalue, $otherlabel] = $this->get_other();
            $labels['other'] = $otherlabel;

            $attributes['id'] = $idprefix.'_other';
            $elementgroup[] = $mform->createElement('mod_surveypro_careybutton', $this->itemname, '',
                                                    $otherlabel, 'other', $attributes);

            $attributes['id'] = $idprefix.'_text';
            $attributes['class'] = 'careybutton_radio';
            $elementgroup[] = $mform->createElement('text', $this->itemname.'_text', '', $attributes);
            $mform->setType($this->itemname.'_text', PARAM_RAW);
            $mform->disabledIf($this->itemname.'_text', $this->itemname, 'neq', 'other');
        }

        if (!$this->required) {
            $attributes['id'] = $idprefix.'_noanswer';
            $attributes['class'] = 'indent-'.$this->indent.' careybutton_radio';
            $elementgroup[] = $mform->createElement('mod_surveypro_careybutton', $this->itemname, '',
                                                    $noanswerstr, SURVEYPRO_NOANSWERVALUE, $attributes);
        }
        // End of: mform element.

        // Begin of: definition of separator.
        if ($this->adjustment == SURVEYPRO_VERTICAL) {
            $separator = $this->userform_get_separator();
        } else { // SURVEYPRO_HORIZONTAL.
            $separator = ' ';
        }
        // End of: definition of separator.

        $mform->addGroup($elementgroup, $this->itemname.'_group', $elementlabel, $separator, false);

        // Begin of: default section.
        if (!$searchform) {
            if ($this->required) {
                // Even if the item is required I CAN NOT ADD ANY RULE HERE because...
                // I do not want JS form validation if the page is submitted through the "previous" button.
                // I do not want JS field validation even if this item is required BUT disabled. See: MDL-34815.
                // Because of this, I simply add a dummy star to the item and the footer note about mandatory fields.
                $starplace = ($this->position == SURVEYPRO_POSITIONTOP) ? $this->itemname.'_extrarow' : $this->itemname.'_group';
                $mform->_required[] = $starplace;
            }

            switch ($this->defaultoption) {
                case SURVEYPRO_CUSTOMDEFAULT:
                    $key = array_search($this->defaultvalue, $labels);
                    if ($key !== false) {
                        $mform->setDefault($this->itemname, "$key");
                    }
                    break;
                case SURVEYPRO_INVITEDEFAULT:
                    $mform->setDefault($this->itemname, SURVEYPRO_INVITEVALUE);
                    break;
                case SURVEYPRO_NOANSWERDEFAULT:
                    $mform->setDefault($this->itemname, SURVEYPRO_NOANSWERVALUE);
                    break;
                default:
                    $message = 'Unexpected $this->defaultoption = '.$this->defaultoption;
                    debugging('Error at line '.__LINE__.' of '.__FILE__.'. '.$message , DEBUG_DEVELOPER);
            }
        } else {
            $mform->setDefault($this->itemname, SURVEYPRO_IGNOREMEVALUE);
        }
        // Note: $this->itemname.'_text' has to ALWAYS get a default (if it exists) even if it is not selected.
        if (!empty($this->labelother)) {
            $mform->setDefault($this->itemname.'_text', $othervalue);
        }
        // End of: default section.
    }

    /**
     * Define the separator needed to create the userform element.
     *
     * @return array $separator
     */
    public function userform_get_separator(): array {
        $labels = $this->get_content_array(SURVEYPRO_LABELS, 'options');
        $optioncount = count($labels);
        $invitation = ($this->defaultoption == SURVEYPRO_INVITEDEFAULT);
        $addother = !empty($this->labelother);
        $mandatory = $this->required;

        $condition = true;
        $condition = $condition && ($optioncount == 1);
        $condition = $condition && (!$invitation);
        $condition = $condition && (!$addother);
        $condition = $condition && ($mandatory);
        if ($condition) { // This message should never appear because corresponding item setupform should not allow this case.
            $message = 'Mandatory radio buttons with only one option and with no invite, no "other" option are not allowed';
            debugging($message, DEBUG_DEVELOPER);
        }

        $separator = [];

        // Invitation.
        if ($invitation) {
            $separator[] = '<br>'; // From "Invitation" to "Option 1".
        }

        // Options.
        for ($i = 1; $i < $optioncount; $i++) {
            $separator[] = '<br>'; // From "Option i" to "Option i+1".
        }

        if ( $addother || (!$mandatory) ) {
            array_pop($separator); // Bloody workaround: drop last break.
        }

        // Other and no answer.
        if ($addother) {
            $separator[] = '<br>'; // From "Option N" to "Other".
            $separator[] = ' '; // From "Other" to the corresponding "text field".
            if (!$mandatory) {
                $separator[] = '<br>'; // From the "text field" to "No answer".
            }
        } else {
            if (!$mandatory) {
                $separator[] = '<br>'; // From "Option N" to "No answer".
            }
        }

        return $separator;
    }

    /**
     * Perform userform and searchform data validation.
     *
     * @param array $data
     * @param array $errors
     * @param bool $searchform
     * @return void
     */
    public function userform_mform_validation($data, &$errors, $searchform) {
        // This plugin displays as a set of radio buttons. It will never return empty values.
        // If ($this->required) { if (empty($data[$this->itemname])) { is useless.

        if ($searchform) {
            return;
        }

        $errorkey = $this->itemname.'_group';

        if ( ($data[$this->itemname] == 'other') && empty($data[$this->itemname.'_text']) ) {
            $errors[$errorkey] = get_string('uerr_missingothertext', 'surveyprofield_careybutton');
            return;
        }

        // I need to check value is different from SURVEYPRO_INVITEVALUE even if it is not required.
        if ($data[$this->itemname] == SURVEYPRO_INVITEVALUE) {
            $errors[$errorkey] = get_string('uerr_optionnotset', 'surveyprofield_careybutton');
            return;
        }
    }

    /**
     * From childparentvalue defines syntax for disabledIf.
     *
     * @param string $childparentvalue
     * @return array
     */
    public function userform_get_parent_disabilitation_info($childparentvalue) {
        $disabilitationinfo = [];
		
		
        $parentvalues = explode(SURVEYPRO_DBMULTICONTENTSEPARATOR, $childparentvalue); // 1;1;0;.
		
		$numberArray = range(0, 50);
    	$filteredArray = array_diff($numberArray, $parentvalues);
		array_unshift($filteredArray, '@@_INVITE_@@');

        if ($parentvalues[0] == '>') {
            $mformelementinfo = new \stdClass();
            $mformelementinfo->parentname = $this->itemname;
            $mformelementinfo->operator = 'neq';
            $mformelementinfo->content = 'other';
            $disabilitationinfo[] = $mformelementinfo;

            $mformelementinfo = new \stdClass();
            $mformelementinfo->parentname = $this->itemname.'_text';
            $mformelementinfo->operator = 'neq';
            $mformelementinfo->content = $parentvalues[1];
            $disabilitationinfo[] = $mformelementinfo;
        } else {
			if (count($parentvalues) > 1) {				
            $mformelementinfo = new \stdClass();
            $mformelementinfo->parentname = $this->itemname;
            $mformelementinfo->operator = 'in';
            $mformelementinfo->content = $filteredArray;
            $disabilitationinfo[] = $mformelementinfo;
			 
			} else {
            $mformelementinfo = new \stdClass();
            $mformelementinfo->parentname = $this->itemname;
            $mformelementinfo->operator = 'neq';
            $mformelementinfo->content = $parentvalues[0];
            $disabilitationinfo[] = $mformelementinfo;
			} 
				
        }

        return $disabilitationinfo;
    }

    /**
     * Dynamically decide if my child (living in my same page) is allowed or not.
     *
     * This method is called if (and only if) parent item and child item live in the same form page.
     * This method has two purposes:
     * - stop userpageform item validation
     * - drop unexpected returned values from $userpageform->formdata
     *
     * As parentitem I declare whether my child item is allowed to return a value (is enabled) or is not (is disabled).
     *
     * @param string $childparentvalue
     * @param array $data
     * @return boolean: true: if the item is welcome; false: if the item must be dropped out
     */
    public function userform_is_child_allowed_dynamic($childparentvalue, $data) {
        $parentvalues = explode(SURVEYPRO_DBMULTICONTENTSEPARATOR, $childparentvalue); // For instance: shark.


// Log the exploded parent values
    print_r($parentvalues);
    echo "\n[DEBUG] Parent values: ";
    print_r($parentvalues);

    // Log the provided data
    echo "\n[DEBUG] Provided data: ";
    print_r($data);

    // Log the item name
    echo "\n[DEBUG] Item name: " . $this->itemname;

    $status = false; // Initialize status as false by default

    // Log the value being checked
    echo "\n[DEBUG] Checking if value '" . $data[$this->itemname] . "' is in parent values...";

    // Check if the value in data matches any of the parent values
    if (in_array($data[$this->itemname], $parentvalues)) {
        $status = true;
        echo "\n[DEBUG] Match found. Status set to true.";
    } else {
        echo "\n[DEBUG] No match found. Status remains false.";
    }

    // Log the final status
    echo "\n[DEBUG] Final status: " . ($status ? 'true' : 'false');

    die();

			return $status;
		}

    /**
     * Starting from the info set by the user in the form
     * this method calculates what to save in the db
     * or what to return for the search form.
     * I don't set $olduseranswer->contentformat in order to accept the default db value.
     *
     * @param array $answer
     * @param object $olduseranswer
     * @param bool $searchform
     * @return void
     */
    public function userform_get_user_answer($answer, &$olduseranswer, $searchform) {
        if (isset($answer['mainelement'])) {
            switch ($answer['mainelement']) {
                case 'other':
                    $olduseranswer->content = $answer['text'];
                    break;
                case SURVEYPRO_INVITEVALUE:
                    $olduseranswer->content = null;
                    break;
                default:
                    $olduseranswer->content = $answer['mainelement'];
                    break;
            }

            return;
        }

        $a = '$answer = '.$answer;
        throw new \moodle_exception('unhandledvalue', 'mod_surveypro', null, $a);
    }

    /**
     * This method is called from get_prefill_data (in formbase.class.php) to set $prefill at user form display time.
     *
     * @param object $fromdb
     * @return associative array with disaggregate element values
     */
    public function userform_get_prefill($fromdb) {
        $prefill = [];

        if (!$fromdb) { // Param $fromdb may be boolean false for not existing data.
            return $prefill;
        }

        if (isset($fromdb->content)) {
            $labels = $this->get_content_array(SURVEYPRO_LABELS, 'options');
            if (array_key_exists($fromdb->content, $labels)) {
                $prefill[$this->itemname] = $fromdb->content;
            } else {
                if ($fromdb->content == SURVEYPRO_NOANSWERVALUE) {
                    $prefill[$this->itemname] = SURVEYPRO_NOANSWERVALUE;
                } else {
                    // It is, for sure, the content of _text.
                    $prefill[$this->itemname] = 'other';
                    $prefill[$this->itemname.'_text'] = $fromdb->content;
                }
            }
        }

        return $prefill;
    }

    /**
     * Starting from the info stored into $answer, this function returns the corresponding content for the export file.
     *
     * @param object $answer
     * @param string $format
     * @return string - the string for the export file
     */
    public function userform_db_to_export($answer, $format='') {
        // The content of the provided answer.
        $content = $answer->content;

        // Trigger 'answernotsubmitted' and 'answerisnoanswer'.
        $quickresponse = self::userform_standardcontent_to_string($content);
        if (isset($quickresponse)) { // Parent method provided the response.
            return $quickresponse;
        }

        // Format.
        if ($format == SURVEYPRO_FRIENDLYFORMAT) {
            $format = $this->get_friendlyformat();
        }
        if (empty($format)) {
            $format = $this->downloadformat;
        }

        // Output.
        switch ($format) {
            case SURVEYPRO_ITEMSRETURNSVALUES:
                $values = $this->get_content_array(SURVEYPRO_VALUES, 'options');
                if (array_key_exists($content, $values)) {
                    $return = $values[$content];
                } else {
                    $return = $content;
                }
                break;
            case SURVEYPRO_ITEMRETURNSLABELS:
                $values = $this->get_content_array(SURVEYPRO_LABELS, 'options');
                if (array_key_exists($content, $values)) {
                    $return = $values[$content];
                } else {
                    $return = $content;
                }
                break;
            case SURVEYPRO_ITEMRETURNSPOSITION:
                $return = $content;
                break;
            default:
                $message = 'Unexpected $format = '.$format;
                debugging('Error at line '.__LINE__.' of '.__FILE__.'. '.$message , DEBUG_DEVELOPER);
        }

        return $return;
    }

    /**
     * Returns an array with the names of the mform element added using $mform->addElement or $mform->addGroup.
     *
     * @return array
     */
    public function userform_get_root_elements_name() {
        $elementnames = [$this->itemname.'_group'];

        return $elementnames;
    }
}
