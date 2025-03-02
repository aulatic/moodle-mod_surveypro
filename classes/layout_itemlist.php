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
 * Surveypro layout class.
 *
 * @package   mod_surveypro
 * @copyright 2013 onwards kordan <stringapiccola@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_surveypro;

use core_text;
use mod_surveypro\local\ipe\layout_customnumber;
use mod_surveypro\local\ipe\layout_insearchform;
use mod_surveypro\local\ipe\layout_required;
use mod_surveypro\local\ipe\layout_reserved;
use mod_surveypro\local\ipe\layout_variable;
use mod_surveypro\utility_layout;

/**
 * The base class representing the list of elements of this surveypro
 *
 * @package   mod_surveypro
 * @copyright 2013 onwards kordan <stringapiccola@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class layout_itemlist {

    /**
     * @var object Course module object
     */
    protected $cm;

    /**
     * @var object Context object
     */
    protected $context;

    /**
     * @var object Surveypro object
     */
    protected $surveypro;

    /**
     * @var string Type of the leading item
     */
    protected $type;

    /**
     * @var string Plugin of the leading item
     */
    protected $plugin;

    /**
     * @var int Id of the leading item
     */
    protected $rootitemid;

    /**
     * @var int Sortindex of the leading item
     */
    protected $sortindex;

    /**
     * @var int Required action
     */
    protected $action;

    /**
     * @var int Required mode
     */
    protected $mode;

    /**
     * @var int Id of the item to move
     */
    protected $itemtomove;

    /**
     * @var int Id of the last item before the moving one
     */
    protected $lastitembefore;

    /**
     * @var int User confirmation to actions
     */
    protected $confirm;

    /**
     * @var int New indent requested for $itemid
     */
    protected $nextindent;

    /**
     * @var int Id of the parent item of $itemid
     */
    protected $parentid;

    /**
     * @var bool True if this surveypro has submissions; false otherwise
     */
    protected $hassubmissions;

    /**
     * @var int Count of the items of this surveypro
     */
    protected $itemcount;

    /**
     * @var int Binary number providing a mask for the feedback of the item editing
     */
    protected $itemeditingfeedback;

    /**
     * @var StdClass object with the feedback for the user
     */
    protected $actionfeedback;

    /**
     * Class constructor.
     *
     * @param object $cm
     * @param object $context
     * @param object $surveypro
     */
    public function __construct($cm, $context, $surveypro) {
        $this->cm = $cm;
        $this->context = $context;
        $this->surveypro = $surveypro;
    }

    /**
     * Setup.
     *
     * @return void
     */
    public function setup() {
        $utilitylayoutman = new utility_layout($this->cm, $this->surveypro);
        $itemcount = $utilitylayoutman->has_items(0, null, true, true, true);
        $this->set_itemcount($itemcount);
    }

    /**
     * Display all the items in a table.
     *
     * @return void
     */
    public function display_items_table() {
        global $CFG, $DB, $OUTPUT;

        require_once($CFG->libdir.'/tablelib.php');

        $riskyediting = ($this->surveypro->riskyeditdeadline > time());

        $table = new \flexible_table('itemslist');

        $paramurl = ['s' => $this->cm->instance, 'section' => 'itemslist'];
        $baseurl = new \moodle_url('/mod/surveypro/layout.php', $paramurl);
        $table->define_baseurl($baseurl);

		$tablecolumns = [];
		$tablecolumns[] = 'plugin';
		$tablecolumns[] = 'sortindex';
		$tablecolumns[] = 'parentid';
		$tablecolumns[] = 'customnumber';
		$tablecolumns[] = 'content';
		$tablecolumns[] = 'dimension';
		$tablecolumns[] = 'idmateria';
		$tablecolumns[] = 'puntajemin';
		$tablecolumns[] = 'puntajemax';
		$tablecolumns[] = 'peso';
		$tablecolumns[] = 'variable';
		$tablecolumns[] = 'formpage';
		$tablecolumns[] = 'availability';
		$tablecolumns[] = 'actions';
		$table->define_columns($tablecolumns);

		$tableheaders = [];
		$tableheaders[] = get_string('typeplugin', 'mod_surveypro');
		$tableheaders[] = get_string('sortindex', 'mod_surveypro');
		$tableheaders[] = get_string('branching', 'mod_surveypro');
		$tableheaders[] = get_string('customnumber_header', 'mod_surveypro');
		$tableheaders[] = get_string('content', 'mod_surveypro');
		$tableheaders[] = 'Dimensión';
		$tableheaders[] = 'Id Materia';
		$tableheaders[] = 'Puntaje Mínimo';
		$tableheaders[] = 'Puntaje Máximo';
		$tableheaders[] = 'Peso';
		$tableheaders[] = get_string('variable', 'mod_surveypro');
		$tableheaders[] = get_string('page');
		$tableheaders[] = get_string('availability', 'mod_surveypro');
		$tableheaders[] = get_string('actions');
		$table->define_headers($tableheaders);

		$table->sortable(true, 'sortindex'); // Sorted by sortindex by default.
		$table->no_sorting('customnumber');
		$table->no_sorting('content');
		$table->no_sorting('dimension');
		$table->no_sorting('idmateria');
		$table->no_sorting('puntajemin');
		$table->no_sorting('puntajemax');
		$table->no_sorting('peso');
		$table->no_sorting('variable');
		$table->no_sorting('availability');
		$table->no_sorting('actions');

        $table->column_class('plugin', 'plugin');
        $table->column_class('sortindex', 'sortindex');
        $table->column_class('parentid', 'parentitem');
        $table->column_class('customnumber', 'customnumber');
        $table->column_class('content', 'content');
        $table->column_class('variable', 'variable');
        $table->column_class('formpage', 'formpage');
        $table->column_class('availability', 'availability');
        $table->column_class('actions', 'actions');

        // General properties for the whole table.
        if ($this->mode == SURVEYPRO_CHANGEORDERASK) {
            $table->set_attribute('id', 'sortitems');
        } else {
            $table->set_attribute('id', 'manageitems');
        }
        $table->set_attribute('class', 'generaltable');
        $table->setup();

        // Strings.
        $iconparams = [];
        // Icons for further use.
        $editstr = get_string('edit');
        $iconparams = ['title' => $editstr];
        $editicn = new \pix_icon('t/edit', $editstr, 'moodle', $iconparams);

        $parentelementstr = get_string('parentelement_title', 'mod_surveypro');
        $iconparams = ['title' => $parentelementstr];
        $branchicn = new \pix_icon('branch', $parentelementstr, 'surveypro', $iconparams);

        $reorderstr = get_string('changeorder_title', 'mod_surveypro');
        $iconparams = ['title' => $reorderstr];
        $moveicn = new \pix_icon('t/move', $editstr, 'moodle', $iconparams);

        $hidestr = get_string('hidefield_title', 'mod_surveypro');
        $iconparams = ['title' => $hidestr];
        $hideicn = new \pix_icon('i/hide', $hidestr, 'moodle', $iconparams);

        $showstr = get_string('showfield_title', 'mod_surveypro');
        $iconparams = ['title' => $showstr];
        $showicn = new \pix_icon('i/show', $showstr, 'moodle', $iconparams);

        $deletestr = get_string('delete');
        $iconparams = ['title' => $deletestr];
        $deleteicn = new \pix_icon('t/delete', $deletestr, 'moodle', $iconparams);

        $indentstr = get_string('indent', 'mod_surveypro');
        $iconparams = ['title' => $indentstr];
        $lefticn = new \pix_icon('t/left', $indentstr, 'moodle', $iconparams);
        $righticn = new \pix_icon('t/right', $indentstr, 'moodle', $iconparams);

        $moveherestr = get_string('movehere');
        $movehereicn = new \pix_icon('movehere', $moveherestr, 'moodle', ['title' => $moveherestr, 'class' => 'placeholder']);

        $availablestr = get_string('available_title', 'mod_surveypro');
        $iconparams = ['title' => $availablestr];
        $freeicn = new \pix_icon('free', $availablestr, 'surveypro', $iconparams);

        $reservedstr = get_string('reserved_title', 'mod_surveypro');
        $iconparams = ['title' => $reservedstr];
        $reservedicn = new \pix_icon('reserved', $reservedstr, 'surveypro', $iconparams);

        $unreservablestr = get_string('unreservable_title', 'mod_surveypro');
        $iconparams = ['title' => $unreservablestr];
        $unreservableicn = new \pix_icon('unreservable', $unreservablestr, 'surveypro', $iconparams);

        $unsearchablestr = get_string('unsearchable_title', 'mod_surveypro');
        $iconparams = ['title' => $unsearchablestr];
        $unsearchableicn = new \pix_icon('unsearchable', $unsearchablestr, 'surveypro', $iconparams);

        $unavailablestr = get_string('unavailableelement_title', 'mod_surveypro');
        $iconparams = ['title' => $unavailablestr];
        $unavailableicn = new \pix_icon('unavailable', $unavailablestr, 'surveypro', $iconparams);

        $forcedoptionalitemstr = get_string('forcedoptionalitem_title', 'mod_surveypro');
        $iconparams = ['title' => $forcedoptionalitemstr];
        $lockedgreenicn = new \pix_icon('lockedgreen', $forcedoptionalitemstr, 'surveypro', $iconparams);

        // Begin of: $paramurlmove definition.
        $paramurlmove = [];
        $paramurlmove['s'] = $this->cm->instance;
        $paramurlmove['act'] = SURVEYPRO_CHANGEORDER;
        $paramurlmove['itm'] = $this->itemtomove;
        // End of: $paramurlmove definition.

        [$where, $params] = surveypro_fetch_items_seeds($this->surveypro->id, false, true, null, null, null, true);
        // If you are reordering, force ordering to...
        $orderby = ($this->mode == SURVEYPRO_CHANGEORDERASK) ? 'sortindex ASC' : $table->get_sql_sort();
        $itemseeds = $DB->get_recordset_select('surveypro_item', $where, $params, $orderby, 'id as itemid, type, plugin');

        // This is the very first position, so if the item has a parent, no "moveherebox" must appear.
        if (($this->mode == SURVEYPRO_CHANGEORDERASK) && (!$this->parentid)) {
            $drawmoveherebox = true;
            $paramurl = $paramurlmove;
            $paramurl['lib'] = 0; // Move just after this sortindex (lib == last item before).
            $paramurl['section'] = 'itemslist';
            $paramurl['sesskey'] = sesskey();

            $link = new \moodle_url('/mod/surveypro/layout.php', $paramurl);
            $paramlink = ['id' => 'moveafter_0', 'title' => $moveherestr];
            $icons = $OUTPUT->action_icon($link, $movehereicn, null, $paramlink);

            $tablerow = [];
            $tablerow[] = $icons;
            $tablerow = array_pad($tablerow, count($table->columns), '');

            $table->add_data($tablerow);
        } else {
            $drawmoveherebox = false;
        }

        foreach ($itemseeds as $itemseed) {
            $item = surveypro_get_item($this->cm, $this->surveypro, $itemseed->itemid, $itemseed->type, $itemseed->plugin, true);

            $itemid = $itemseed->itemid;
            $itemishidden = $item->get_hidden();
            $sortindex = $item->get_sortindex();
			$dimension = isset($item->dimension) && !empty($item->dimension) ? $item->dimension : "noaplica";
			$idmateria = isset($item->idmateria) ? $item->idmateria : "noaplica";
			$puntajemin = isset($item->puntajemin) ? $item->puntajemin : "noaplica";
			$puntajemax = isset($item->puntajemax) ? $item->puntajemax : "noaplica";
			$peso = isset($item->peso) ? $item->peso : "noaplica";
			
            // Begin of: $paramurlbase definition.
            $paramurlbase = [];
            $paramurlbase['s'] = $this->cm->instance;
            $paramurlbase['itemid'] = $item->get_itemid();
            $paramurlbase['type'] = $item->get_type();
            $paramurlbase['plugin'] = $item->get_plugin();
            // End of: $paramurlbase definition.

            $tablerow = [];

            if (($this->mode == SURVEYPRO_CHANGEORDERASK) && ($item->get_itemid() == $this->rootitemid)) {
                // Do not draw the item you are going to move.
                continue;
            }

            // Plugin.
            $component = 'surveypro'.$item->get_type().'_'.$item->get_plugin();
            $alt = get_string('userfriendlypluginname', $component);
            $content = \html_writer::tag('a', '', ['name' => 'sortindex_'.$sortindex]);
            $iconparams = ['title' => $alt];
            $icon = $OUTPUT->pix_icon('icon', $alt, $component, $iconparams);
            $content .= \html_writer::tag('span', $icon, ['class' => 'pluginicon']);

            $tablerow[] = $content;

            // Sortindex.
            $tablerow[] = $sortindex;

            // Parentid.
            if ($item->get_parentid()) {
                $parentsortindex = $DB->get_field('surveypro_item', 'sortindex', ['id' => $item->get_parentid()]);
                $content = $parentsortindex;
                $content .= \html_writer::tag('span', $OUTPUT->render($branchicn), ['class' => 'branch']);
                $content .= $item->get_parentcontent('; ');
            } else {
                $content = '';
            }
            $tablerow[] = $content;

            // Customnumber.
            if (($item->get_type() == SURVEYPRO_TYPEFIELD) || ($item->get_plugin() == 'label')) {
                $itemid = $item->get_itemid();
                $customnumber = $item->get_customnumber();
                $tmpl = new layout_customnumber($itemid, $customnumber);

                $tablerow[] = $OUTPUT->render_from_template('core/inplace_editable', $tmpl->export_for_template($OUTPUT));
            } else {
                $tablerow[] = '';
            }

			// Content.
			$tablerow[] = $item->get_content();

			// CUSTOM DATA CAREY
			// CUSTOM DATA CAREY
			if (($item->get_type() == SURVEYPRO_TYPEFIELD) || ($item->get_plugin() == 'label')) {
				$tablerow[] = $dimension ?? 'noaplica';
				$tablerow[] = $idmateria ?? 'noaplica';
				$tablerow[] = $puntajemin ?? 'noaplica';
				$tablerow[] = $puntajemax ?? 'noaplica';
				$tablerow[] = $peso ?? 'noaplica';
			} else {
				$tablerow = array_merge($tablerow, array_fill(0, 5, ''));
			}

            // Variable.
            if ($item->get_type() == SURVEYPRO_TYPEFIELD) {
                $itemid = $item->get_itemid();
                $variablename = $item->get_variable();
                $tmpl = new layout_variable($itemid, $variablename);

                $tablerow[] = $OUTPUT->render_from_template('core/inplace_editable', $tmpl->export_for_template($OUTPUT));
            } else {
                $tablerow[] = '';
            }

            // Page.
            if ($item->item_uses_form_page()) {
                $content = $item->get_formpage();
            } else {
                $content = '';
            }
            $tablerow[] = $content;

            // Availability.
            $icons = '';
            // First icon: reserved vs generally available.
            if (!$itemishidden) {
                if ($item->get_insetupform('reserved')) {
                    $reserved = $item->get_reserved();
                    if ($item->item_has_children() || $item->item_is_child()) {
                        $paramurl = $paramurlbase;
                        if ($reserved) {
                            $paramurl['act'] = SURVEYPRO_MAKEAVAILABLE;
                            $paramurl['sortindex'] = $sortindex;
                            $paramurl['section'] = 'itemslist';
                            $paramurl['sesskey'] = sesskey();

                            $link = new \moodle_url('/mod/surveypro/layout.php#sortindex_'.$sortindex, $paramurl);
                            $paramlink = ['id' => 'makeavailable_item_'.$sortindex, 'title' => $reservedstr];
                            $actionicon = $OUTPUT->action_icon($link, $reservedicn, null, $paramlink);
                            $icons .= \html_writer::tag('span', $actionicon, ['class' => 'reserveitem']);
                        } else {
                            $paramurl['act'] = SURVEYPRO_MAKERESERVED;
                            $paramurl['sortindex'] = $sortindex;
                            $paramurl['section'] = 'itemslist';
                            $paramurl['sesskey'] = sesskey();

                            $link = new \moodle_url('/mod/surveypro/layout.php#sortindex_'.$sortindex, $paramurl);
                            $paramlink = ['id' => 'makereserved_item_'.$sortindex, 'title' => $availablestr];
                            $actionicon = $OUTPUT->action_icon($link, $freeicn, null, $paramlink);
                            $icons .= \html_writer::tag('span', $actionicon, ['class' => 'freeitem']);
                        }
                    } else {
                        $tmpl = new layout_reserved($itemid, $reserved, $sortindex);
                        $tmpl->set_type_toggle();
                        $icons .= $OUTPUT->render_from_template('core/inplace_editable', $tmpl->export_for_template($OUTPUT));
                    }
                } else {
                    // Icon only, not a link!
                    $icons .= \html_writer::tag('span', $OUTPUT->render($unreservableicn), ['class' => 'noactionicon']);
                }
            } else {
                // Icon only, not a link!
                $icons .= \html_writer::tag('span', $OUTPUT->render($unavailableicn), ['class' => 'noactionicon']);
            }

            // Second icon: insearchform vs notinsearchform.
            if (!$itemishidden) {
                if ($item->get_insetupform('insearchform')) {
                    // Second icon: insearchform vs not insearchform.
                    $insearchform = $item->get_insearchform();
                    $tmpl = new layout_insearchform($itemid, $insearchform, $sortindex);
                    $tmpl->set_type_toggle();
                    $icons .= $OUTPUT->render_from_template('core/inplace_editable', $tmpl->export_for_template($OUTPUT));
                } else {
                    // Icon only, not a link!
                    $icons .= \html_writer::tag('span', $OUTPUT->render($unsearchableicn), ['class' => 'noactionicon']);
                }
            } else {
                // Icon only, not a link!
                $icons .= \html_writer::tag('span', $OUTPUT->render($unavailableicn), ['class' => 'noactionicon']);
            }

            // Third icon: hide vs show.
            // Here I can not use the cool \core\output\inplace_editable because
            // this action make changes not limited to the state of this icon.
            if (!$this->hassubmissions || $riskyediting) {
                $paramurl = $paramurlbase;
                $paramurl['section'] = 'itemslist';
                $paramurl['sesskey'] = sesskey();
                if (empty($itemishidden)) {
                    $paramurl['act'] = SURVEYPRO_HIDEITEM;
                    $paramurl['sortindex'] = $sortindex;
                    $linkidprefix = 'hide_item_';
                } else {
                    $paramurl['act'] = SURVEYPRO_SHOWITEM;
                    $paramurl['sortindex'] = $sortindex;
                    $linkidprefix = 'show_item_';
                }
                $link = new \moodle_url('/mod/surveypro/layout.php#sortindex_'.$sortindex, $paramurl);
                $paramlink = ['id' => $linkidprefix.$sortindex, 'class' => 'icon'];
                if (empty($itemishidden)) {
                    $paramlink['title'] = $hidestr;
                    $actionicon = $OUTPUT->action_icon($link, $hideicn, null, $paramlink);
                    $icons .= \html_writer::tag('span', $actionicon, ['class' => 'hideitem']);
                } else {
                    $paramlink['title'] = $showstr;
                    $actionicon = $OUTPUT->action_icon($link, $showicn, null, $paramlink);
                    $icons .= \html_writer::tag('span', $actionicon, ['class' => 'showitem']);
                }
            }
            $tablerow[] = $icons;

            // Action icons.
            $icons = '';
            if ($this->mode != SURVEYPRO_CHANGEORDERASK) {
                // SURVEYPRO_EDITITEM.
                $paramurl = $paramurlbase;
                $paramurl['mode'] = SURVEYPRO_EDITITEM;
                $paramurl['section'] = 'itemsetup';

                $link = new \moodle_url('/mod/surveypro/layout.php', $paramurl);
                $paramlink = ['id' => 'edit_item_'.$sortindex, 'class' => 'icon', 'title' => $editstr];
                $actionicon = $OUTPUT->action_icon($link, $editicn, null, $paramlink);
                $icons .= \html_writer::tag('span', $actionicon, ['class' => 'fatspan']);

                // SURVEYPRO_CHANGEORDERASK.
                if ($this->itemcount > 1) {
                    $paramurl = $paramurlbase;
                    $paramurl['mode'] = SURVEYPRO_CHANGEORDERASK;
                    $paramurl['itm'] = $sortindex;
                    $paramurl['section'] = 'itemslist';

                    $currentparentid = $item->get_parentid();
                    if (!empty($currentparentid)) {
                        $paramurl['pid'] = $currentparentid;
                    }

                    $link = new \moodle_url('/mod/surveypro/layout.php#sortindex_'.($sortindex - 1), $paramurl);
                    $paramlink = ['id' => 'move_item_'.$sortindex, 'class' => 'icon', 'title' => $reorderstr];
                    $actionicon = $OUTPUT->action_icon($link, $moveicn, null, $paramlink);
                    $icons .= \html_writer::tag('span', $actionicon, ['class' => 'fatspan']);
                }

                // SURVEYPRO_DELETEITEM.
                if (!$this->hassubmissions || $riskyediting) {
                    $paramurl = $paramurlbase;
                    $paramurl['act'] = SURVEYPRO_DELETEITEM;
                    $paramurl['sortindex'] = $sortindex;
                    $paramurl['section'] = 'itemslist';
                    $paramurl['sesskey'] = sesskey();

                    $link = new \moodle_url('/mod/surveypro/layout.php#sortindex_'.$sortindex, $paramurl);
                    $paramlink = ['id' => 'delete_item_'.$sortindex, 'class' => 'icon', 'title' => $deletestr];
                    $actionicon = $OUTPUT->action_icon($link, $deleteicn, null, $paramlink);
                    $icons .= \html_writer::tag('span', $actionicon, ['class' => 'fatspan']);
                }

                // SURVEYPRO_REQUIRED ON/OFF.
                $currentrequired = $item->get_required();
                if ($currentrequired !== false) { // It may be "not set" as in page_break, autofill or some more.
                    $paramurl = $paramurlbase;
                    $paramurl['sesskey'] = sesskey();

                    if ($item->item_canbemandatory()) {
                        $required = $item->get_required();
                        $tmpl = new layout_required($itemid, $required, $sortindex);
                        $tmpl->set_type_toggle();
                        $icons .= $OUTPUT->render_from_template('core/inplace_editable', $tmpl->export_for_template($OUTPUT));
                    } else {
                        $icons .= \html_writer::tag('span', $OUTPUT->render($lockedgreenicn), ['class' => 'noactionicon']);
                    }
                } else {
                    // Icon only, not a link!
                    $icons .= \html_writer::tag('span', $OUTPUT->spacer(), ['class' => 'spacericon']);
                }

                // SURVEYPRO_CHANGEINDENT.
                if ($item->get_insetupform('indent')) { // It may not be set as in page_break, fieldset and some more.
                    $currentindent = $item->get_indent();
                    if ($currentindent !== false) { // It may be false like for labels with fullwidth == 1.
                        $paramurl = $paramurlbase;
                        $paramurl['act'] = SURVEYPRO_CHANGEINDENT;
                        $paramurl['section'] = 'itemslist';
                        $paramurl['sesskey'] = sesskey();

                        $actionicon = '';
                        $paramlink = ['title' => $indentstr, 'class' => 'lastspan'];
                        if ($currentindent > 0) {
                            $indentvalue = $currentindent - 1;
                            $paramurl['ind'] = $indentvalue;

                            $link = new \moodle_url('/mod/surveypro/layout.php#sortindex_'.$sortindex, $paramurl);
                            $paramlink += ['id' => 'reduceindent_item_'.$sortindex];
                            $actionicon .= $OUTPUT->action_icon($link, $lefticn, null, $paramlink);
                        } else {
                            // Icon only, not a link!
                            $actionicon .= $OUTPUT->spacer(['class' => 'spacericon']);
                        }
                        $actionicon .= '['.$currentindent.']';
                        if ($currentindent < 9) {
                            $indentvalue = $currentindent + 1;
                            $paramurl['ind'] = $indentvalue;

                            $link = new \moodle_url('/mod/surveypro/layout.php#sortindex_'.$sortindex, $paramurl);
                            $paramlink += ['id' => 'increaseindent_item_'.$sortindex];
                            $actionicon .= $OUTPUT->action_icon($link, $righticn, null, $paramlink);
                        }
                        $icons .= \html_writer::tag('span', $actionicon, ['class' => 'lastspan']);
                    }
                }
            }
            $tablerow[] = $icons;

            $rowclass = empty($itemishidden) ? '' : 'dimmed';
            $table->add_data($tablerow, $rowclass);

            if ($this->mode == SURVEYPRO_CHANGEORDERASK) {
                // It was asked to move the item with: $this->rootitemid and $this->parentid.
                if ($this->parentid) { // This is the parentid of the item that I am going to move.
                    // If a parentid is foreseen then...
                    // Draw the moveherebox only if the current (already displayed) item has: $item->itemid == $this->parentid.
                    // Once you start to draw the moveherebox, you will never stop.
                    $drawmoveherebox = $drawmoveherebox || ($item->get_itemid() == $this->parentid);

                    // If you just passed an item with $item->get_parentid == $itemid, stop forever.
                    if ($item->get_parentid() == $this->rootitemid) {
                        $drawmoveherebox = false;
                    }
                } else {
                    $drawmoveherebox = $drawmoveherebox && ($item->get_parentid() != $this->rootitemid);
                }

                if (!empty($drawmoveherebox)) {
                    $paramurl = $paramurlmove;
                    $paramurl['lib'] = $sortindex;
                    $paramurl['section'] = 'itemslist';
                    $paramurl['sesskey'] = sesskey();

                    $link = new \moodle_url('/mod/surveypro/layout.php#sortindex_'.$sortindex, $paramurl);
                    $paramlink = ['id' => 'move_item_'.$sortindex, 'title' => $moveherestr];
                    $icons = $OUTPUT->action_icon($link, $movehereicn, null, $paramlink);

                    $tablerow = [];
                    $tablerow[] = $icons;
                    $tablerow = array_pad($tablerow, count($table->columns), '');

                    $table->add_data($tablerow);
                }
            }
        }

        $table->set_attribute('align', 'center');
        $table->summary = get_string('itemlist', 'mod_surveypro');
        $table->print_html();
    }

    /**
     * Display the "validate_relations" table.
     *
     * @return void
     */
    public function display_relations_table() {
        global $CFG, $DB, $OUTPUT;

        require_once($CFG->libdir.'/tablelib.php');

        $statusstr = get_string('relation_status', 'mod_surveypro');
        $table = new \flexible_table('relations');

        $paramurl = ['s' => $this->cm->instance, 'section' => 'branchingvalidation'];
        $baseurl = new \moodle_url('/mod/surveypro/layout.php', $paramurl);
        $table->define_baseurl($baseurl);

        $tablecolumns = [];
        $tablecolumns[] = 'plugin';
        $tablecolumns[] = 'sortindex';
        $tablecolumns[] = 'parentitem';
        $tablecolumns[] = 'customnumber';
        $tablecolumns[] = 'content';
        $tablecolumns[] = 'parentconstraints';
        $tablecolumns[] = 'status';
        $tablecolumns[] = 'actions';
        $table->define_columns($tablecolumns);

        $tableheaders = [];
        $tableheaders[] = get_string('typeplugin', 'mod_surveypro');
        $tableheaders[] = get_string('sortindex', 'mod_surveypro');
        $tableheaders[] = get_string('branching', 'mod_surveypro');
        $tableheaders[] = get_string('customnumber_header', 'mod_surveypro');
        $tableheaders[] = get_string('content', 'mod_surveypro');
        $tableheaders[] = get_string('parentconstraints', 'mod_surveypro');
        $tableheaders[] = $statusstr;
        $tableheaders[] = get_string('actions');
        $table->define_headers($tableheaders);

        $table->column_class('plugin', 'plugin');
        $table->column_class('sortindex', 'sortindex');
        $table->column_class('parentitem', 'parentitem');
        $table->column_class('customnumber', 'customnumber');
        $table->column_class('content', 'content');
        $table->column_class('parentconstraints', 'parentconstraints');
        $table->column_class('status', 'status');
        $table->column_class('actions', 'actions');

        // General properties for the whole table.
        $table->set_attribute('id', 'validaterelations');
        $table->set_attribute('class', 'generaltable');
        $table->setup();

        $okstr = get_string('ok');

        $iconparams = [];

        $editstr = get_string('edit');
        $iconparams = ['title' => $editstr];
        $editicn = new \pix_icon('t/edit', $editstr, 'moodle', $iconparams);

        $parentelementstr = get_string('parentelement_title', 'mod_surveypro');
        $iconparams = ['title' => $parentelementstr];
        $branchicn = new \pix_icon('branch', $parentelementstr, 'surveypro', $iconparams);

        // Get parents id only.
        $sql = 'SELECT DISTINCT id as paretid, 1
                FROM {surveypro_item} parent
                WHERE EXISTS (
                    SELECT \'x\'
                    FROM {surveypro_item} child
                    WHERE child.parentid = parent.id)
                AND surveyproid = ?';
        $whereparams = [$this->surveypro->id];
        $isparent = $DB->get_records_sql_menu($sql, $whereparams);

        // Get itemseeds.
        $sql = 'SELECT DISTINCT id as itemid, plugin, type, sortindex
                FROM {surveypro_item} parent
                WHERE EXISTS (
                    SELECT \'x\'
                    FROM {surveypro_item} child
                    WHERE child.parentid = parent.id)
                AND surveyproid = ?

                UNION

                SELECT DISTINCT id as itemid, plugin, type, sortindex
                FROM {surveypro_item}
                WHERE surveyproid = ?
                    AND parentid > 0

                ORDER BY sortindex;';
        $whereparams = [$this->surveypro->id, $this->surveypro->id];
        $itemseeds = $DB->get_recordset_sql($sql, $whereparams);

        $message = get_string('welcome_relationvalidation', 'mod_surveypro', $statusstr);
        echo $OUTPUT->notification($message, 'notifymessage');

        foreach ($itemseeds as $itemseed) {
            $item = surveypro_get_item($this->cm, $this->surveypro, $itemseed->itemid, $itemseed->type, $itemseed->plugin, true);
            $itemishidden = $item->get_hidden();

            if ($item->get_parentid()) {
                // Here I do not know type and plugin.
                $parentitem = surveypro_get_item($this->cm, $this->surveypro, $item->get_parentid());
            }

            $tablerow = [];

            // Plugin.
            $component = 'surveypro'.$item->get_type().'_'.$item->get_plugin();
            $alt = get_string('pluginname', $component);
            $iconparams = ['title' => $alt, 'class' => 'icon'];
            $content = $OUTPUT->pix_icon('icon', $alt, $component, $iconparams);
            $tablerow[] = $content;

            // Sortindex.
            $tablerow[] = $item->get_sortindex();

            // Parentid.
            if ($item->get_parentid()) {
                $content = $parentitem->get_sortindex();
                $content .= \html_writer::tag('span', $OUTPUT->render($branchicn), ['class' => 'branch']);
                $content .= $item->get_parentcontent('; ');
            } else {
                $content = '';
            }
            $tablerow[] = $content;

            // Customnumber.
            if (($item->get_type() == SURVEYPRO_TYPEFIELD) || ($item->get_plugin() == 'label')) {
                $tablerow[] = $item->get_customnumber();
            } else {
                $tablerow[] = '';
            }

            // Content.
            $tablerow[] = $item->get_content();

            // Parentconstraints.
            if (isset($isparent[$itemseed->itemid])) {
                $tablerow[] = $item->item_list_constraints();
            } else {
                $tablerow[] = '-';
            }

            // Status.
            if ($item->get_parentid()) {
                $status = $parentitem->parent_validate_child_constraints($item->get_parentvalue());
                if ($status == SURVEYPRO_CONDITIONOK) {
                    $tablerow[] = $okstr;
                } else {
                    if ($status == SURVEYPRO_CONDITIONNEVERMATCH) {
                        if (empty($itemishidden)) {
                            $errormessage = \html_writer::start_tag('span', ['class' => 'errormessage']);
                            $errormessage .= get_string('wrongrelation', 'mod_surveypro', $item->get_parentcontent('; '));
                            $errormessage .= \html_writer::end_tag('span');
                            $tablerow[] = $errormessage;
                        } else {
                            $tablerow[] = get_string('wrongrelation', 'mod_surveypro', $item->get_parentcontent('; '));
                        }
                    }
                    if ($status == SURVEYPRO_CONDITIONMALFORMED) {
                        if (empty($itemishidden)) {
                            $errormessage = \html_writer::start_tag('span', ['class' => 'errormessage']);
                            $errormessage .= get_string('badchildparentvalue', 'mod_surveypro', $item->get_parentcontent('; '));
                            $errormessage .= \html_writer::end_tag('span');
                            $tablerow[] = $errormessage;
                        } else {
                            $tablerow[] = get_string('badchildparentvalue', 'mod_surveypro', $item->get_parentcontent('; '));
                        }
                    }
                }
            } else {
                $tablerow[] = '-';
            }

            // Actions.
            // Begin of: $paramurlbase definition.
            $paramurlbase = [];
            $paramurlbase['s'] = $this->cm->instance;
            $paramurlbase['itemid'] = $item->get_itemid();
            $paramurlbase['type'] = $item->get_type();
            $paramurlbase['plugin'] = $item->get_plugin();
            $paramurlbase['section'] = 'itemsetup';
            // End of $paramurlbase definition.

            // SURVEYPRO_NEWITEM.
            $paramurl = $paramurlbase;
            $paramurl['mode'] = SURVEYPRO_NEWITEM;

            $link = new \moodle_url('/mod/surveypro/layout.php', $paramurl);
            $paramlink = ['id' => 'edit_'.$item->get_itemid(), 'title' => $editstr];
            $icons = $OUTPUT->action_icon($link, $editicn, null, $paramlink);

            $tablerow[] = $icons;

            $rowclass = empty($itemishidden) ? '' : 'dimmed';
            $table->add_data($tablerow, $rowclass);
        }
        $itemseeds->close();

        $table->set_attribute('align', 'center');
        $table->summary = get_string('itemlist', 'mod_surveypro');
        $table->print_html();
    }

    /**
     * Adds elements to an array starting from initial conditions.
     *
     * Called by:
     *     item_show_execute()
     *     item_show_feedback()
     *     item_makereserved_execute()
     *     item_makereserved_feedback()
     *     item_makeavailable_execute()
     *     item_makeavailable_feedback()
     *
     * $additionalcondition is ['hidden' => 1] OR ['reserved' => 1]
     *
     * @param array $additionalcondition
     * @return array $nodelist
     */
    public function add_parent_node($additionalcondition) {
        global $DB;

        if (!is_array($additionalcondition)) {
            $a = 'add_parent_node';
            throw new \moodle_exception('arrayexpected', 'mod_surveypro', null, $a);
        }

        $nodelist = [$this->sortindex => $this->rootitemid];

        // Get the first parentid.
        $parentitem = new \stdClass();
        $parentitem->parentid = $DB->get_field('surveypro_item', 'parentid', ['id' => $this->rootitemid]);

        $where = ['id' => $parentitem->parentid] + $additionalcondition;

        while ($parentitem = $DB->get_record('surveypro_item', $where, 'id, parentid, sortindex')) {
            $nodelist[$parentitem->sortindex] = (int)$parentitem->id;
            $where = ['id' => $parentitem->parentid] + $additionalcondition;
        }

        return $nodelist;
    }

    /**
     * Get the recursive list of children of a specific item.
     * This method counts children and children of children for as much generation as it founds.
     *
     * Called by:
     *     item_hide_execute()
     *     item_hide_feedback()
     *     item_makereserved_execute()
     *     item_makereserved_feedback()
     *     item_makeavailable_execute()
     *     item_makeavailable_feedback()
     *     item_delete_execute()
     *     item_delete_feedback()
     *
     * @param int $baseitemid the id of the root item for the tree of children to get
     * @param array $where permanent condition needed to filter target items
     * @return object $childrenitems
     */
    public function get_children($baseitemid=null, $where=null) {
        global $DB;

        if (empty($baseitemid)) {
            $baseitemid = $this->rootitemid;
        }

        if (empty($where)) {
            $where = [];
        }

        if (!is_array($where)) {
            $a = 'get_children';
            throw new \moodle_exception('arrayexpected', 'mod_surveypro', null, $a);
        }

        $idscontainer = [$baseitemid];

        // Lets start populating the list of items to return.
        $childrenitems = $DB->get_records('surveypro_item', ['id' => $baseitemid], 'sortindex', 'id, parentid, sortindex');

        $childid = $baseitemid;
        $i = 1;
        do {
            $where['parentid'] = $childid;
            if ($morechildren = $DB->get_records('surveypro_item', $where, 'sortindex', 'id, parentid, sortindex')) {
                foreach ($morechildren as $k => $unused) {
                    $idscontainer[] = $k;
                }
                $childrenitems += $morechildren;
            }
            $childid = next($idscontainer);
            $i++;
        } while ($i <= count($idscontainer));

        return $childrenitems;
    }

    // MARK item action execution.

    /**
     * Ask for confirmation before a bulk action.
     *
     * Called by:
     *     show_all_feedback()
     *
     * @param string $message
     * @param string $yeskey
     * @return void
     */
    public function bulk_action_ask($message, $yeskey=null) {
        global $OUTPUT;

        $optionbase = ['s' => $this->cm->instance, 'act' => $this->action, 'section' => 'itemslist', 'sesskey' => sesskey()];

        $optionsyes = $optionbase;
        $optionsyes['cnf'] = SURVEYPRO_CONFIRMED_YES;
        $urlyes = new \moodle_url('/mod/surveypro/layout.php', $optionsyes);

        $yeslabel = ($yeskey) ? get_string($yeskey, 'mod_surveypro') : get_string('continue');
        $buttonyes = new \single_button($urlyes, $yeslabel);

        $optionsno = $optionbase;
        $optionsno['cnf'] = SURVEYPRO_CONFIRMED_NO;
        $urlno = new \moodle_url('/mod/surveypro/layout.php', $optionsno);
        $buttonno = new \single_button($urlno, get_string('no'));

        echo $OUTPUT->confirm($message, $buttonyes, $buttonno);
        echo $OUTPUT->footer();
        die();
    }

    /**
     * Perform the actions required through icon click into items table.
     *
     * Called by:
     *     layout.php
     *
     * @return void
     */
    public function actions_execution() {
        global $DB;

        switch ($this->action) {
            case SURVEYPRO_NOACTION:
                break;
            case SURVEYPRO_HIDEITEM:
                $this->item_hide_execute();
                break;
            case SURVEYPRO_SHOWITEM:
                $this->item_show_execute();
                break;
            case SURVEYPRO_DELETEITEM:
                $this->item_delete_execute();
                break;
            case SURVEYPRO_DROPMULTILANG:
                $this->drop_multilang_execute();
                break;
            case SURVEYPRO_CHANGEORDER:
                $this->reorder_items();
                // After item reorder, if you reload the page whithout cleaning the url, the reorder action is performed again.
                $returnurl = new \moodle_url('/mod/surveypro/layout.php', ['s' => $this->cm->instance, 'section' => 'itemslist']);
                redirect($returnurl);
                break;
            case SURVEYPRO_CHANGEINDENT:
                $where = ['itemid' => $this->rootitemid];
                $DB->set_field('surveypro'.$this->type.'_'.$this->plugin, 'indent', $this->nextindent, $where);
                break;
            case SURVEYPRO_MAKERESERVED:
                $this->item_makereserved_execute();
                break;
            case SURVEYPRO_MAKEAVAILABLE:
                $this->item_makeavailable_execute();
                break;
            case SURVEYPRO_HIDEALLITEMS:
                $this->hide_all_execute();
                break;
            case SURVEYPRO_SHOWALLITEMS:
                $this->show_all_execute();
                break;
            case SURVEYPRO_DELETEALLITEMS:
                $this->delete_all_execute();
                break;
            case SURVEYPRO_DELETEVISIBLEITEMS:
                $this->delete_visible_execute();
                break;
            case SURVEYPRO_DELETEHIDDENITEMS:
                $this->delete_hidden_execute();
                break;
            default:
                $message = 'Unexpected $this->action = '.$this->action;
                debugging('Error at line '.__LINE__.' of '.__FILE__.'. '.$message , DEBUG_DEVELOPER);
        }
    }

    /**
     * Provide a feedback for the actions performed in actions_execution.
     *
     * Called by:
     *     layout.php
     *
     * @return void
     */
    public function actions_feedback() {
        switch ($this->action) {
            case SURVEYPRO_NOACTION:
                if (!empty($this->surveypro->template)) {
                    $this->drop_multilang_feedback();
                }
                break;
            case SURVEYPRO_HIDEITEM:
                $this->item_hide_feedback();
                break;
            case SURVEYPRO_SHOWITEM:
                $this->item_show_feedback();
                break;
            case SURVEYPRO_DELETEITEM:
                $this->item_delete_feedback();
                break;
            case SURVEYPRO_MAKERESERVED:
                $this->item_makereserved_feedback();
                break;
            case SURVEYPRO_MAKEAVAILABLE:
                $this->item_makeavailable_feedback();
                break;
            case SURVEYPRO_HIDEALLITEMS:
                $this->hide_all_feedback();
                break;
            case SURVEYPRO_SHOWALLITEMS:
                $this->show_all_feedback();
                break;
            case SURVEYPRO_DELETEALLITEMS:
                $this->delete_all_feedback();
                break;
            case SURVEYPRO_DELETEVISIBLEITEMS:
                $this->delete_visible_feedback();
                break;
            case SURVEYPRO_DELETEHIDDENITEMS:
                $this->delete_hidden_feedback();
                break;
            default:
                // Black hole for all the actions not needing feedback.
        }
    }

    /**
     * Store to the database sortindex field, the relative position at the items according to last changes.
     *
     * Called by:
     *     actions_execution()
     *
     * @return void
     */
    public function reorder_items() {
        global $DB;

        // I start loading the id of the item I want to move starting from its known sortindex.
        $where = ['surveyproid' => $this->surveypro->id, 'sortindex' => $this->itemtomove];
        $itemid = $DB->get_field('surveypro_item', 'id', $where);

        // Am I moving it backward or forward?
        if ($this->itemtomove > $this->lastitembefore) {
            // Moving the item backward.
            $searchitem = $this->itemtomove - 1;
            $replaceitem = $this->itemtomove;

            $where = ['surveyproid' => $this->surveypro->id];
            while ($searchitem > $this->lastitembefore) {
                $where['sortindex'] = $searchitem;
                $DB->set_field('surveypro_item', 'sortindex', $replaceitem, $where);
                $replaceitem = $searchitem;
                $searchitem--;
            }

            $DB->set_field('surveypro_item', 'sortindex', $replaceitem, ['id' => $itemid]);
        } else {
            // Moving the item forward.
            $searchitem = $this->itemtomove + 1;
            $replaceitem = $this->itemtomove;

            $where = ['surveyproid' => $this->surveypro->id];
            while ($searchitem <= $this->lastitembefore) {
                $where['sortindex'] = $searchitem;
                $DB->set_field('surveypro_item', 'sortindex', $replaceitem, $where);
                $replaceitem = $searchitem;
                $searchitem++;
            }

            $DB->set_field('surveypro_item', 'sortindex', $replaceitem, ['id' => $itemid]);
        }

        // You changed item order. Don't forget to reset the page of each items.
        $utilitylayoutman = new utility_layout($this->cm, $this->surveypro);
        $utilitylayoutman->reset_pages();
    }

    // MARK ITEM - hide.

    /**
     * Hide an item and (maybe) all its descendants.
     *
     * Called by:
     *     actions_execution()
     *
     * @return void
     */
    public function item_hide_execute() {
        global $DB;

        // Build tohidelist.
        // Here I must select the whole tree down.
        $itemstohide = $this->get_children(null, ['hidden' => 0]);

        $itemstoprocess = count($itemstohide);
        if ( ($this->confirm == SURVEYPRO_CONFIRMED_YES) || ($itemstoprocess == 1) ) {
            // Hide items.
            foreach ($itemstohide as $itemtohide) {
                $DB->set_field('surveypro_item', 'hidden', 1, ['id' => $itemtohide->id]);
            }
            $utilitylayoutman = new utility_layout($this->cm, $this->surveypro);
            $utilitylayoutman->reset_pages();
        }
    }

    /**
     * Provide a feedback after item_hide_execute.
     *
     * @return void
     */
    public function item_hide_feedback() {
        global $OUTPUT;

        // Build tohidelist.
        // Here I must select the whole tree down.
        $itemstohide = $this->get_children(null, ['hidden' => 0]);

        $itemstoprocess = count($itemstohide);
        if ($this->confirm == SURVEYPRO_UNCONFIRMED) {
            if ($itemstoprocess > 1) { // Ask for confirmation.
                $dependencies = [];
                $item = surveypro_get_item($this->cm, $this->surveypro, $this->rootitemid, $this->type, $this->plugin);

                $a = new \stdClass();
                $a->itemcontent = $item->get_content();
                foreach ($itemstohide as $itemtohide) {
                    $dependencies[] = $itemtohide->sortindex;
                }
                // Drop the original item because it doesn't go in the message.
                $key = array_search($this->sortindex, $dependencies);
                if ($key !== false) { // Should always happen.
                    unset($dependencies[$key]);
                }
                $a->dependencies = implode(', ', $dependencies);
                if (count($dependencies) == 1) {
                    $message = get_string('confirm_hide1item', 'mod_surveypro', $a);
                } else {
                    $message = get_string('confirm_hidechainitems', 'mod_surveypro', $a);
                }

                $optionbase = ['s' => $this->cm->instance];
                $optionbase['act'] = SURVEYPRO_HIDEITEM;
                $optionbase['section'] = 'itemslist';
                $optionbase['sesskey'] = sesskey();

                $optionsyes = $optionbase;
                $optionsyes['cnf'] = SURVEYPRO_CONFIRMED_YES;
                $optionsyes['itemid'] = $this->rootitemid;
                $optionsyes['plugin'] = $this->plugin;
                $optionsyes['type'] = $this->type;
                $urlyes = new \moodle_url('/mod/surveypro/layout.php#sortindex_'.$this->sortindex, $optionsyes);
                $buttonyes = new \single_button($urlyes, get_string('continue'));

                $optionsno = $optionbase;
                $optionsno['cnf'] = SURVEYPRO_CONFIRMED_NO;
                $urlno = new \moodle_url('/mod/surveypro/layout.php#sortindex_'.$this->sortindex, $optionsno);
                $buttonno = new \single_button($urlno, get_string('no'));

                echo $OUTPUT->confirm($message, $buttonyes, $buttonno);
                echo $OUTPUT->footer();
                die();
            }
        }

        if ($this->confirm == SURVEYPRO_CONFIRMED_NO) {
            $message = get_string('usercanceled', 'mod_surveypro');
            echo $OUTPUT->notification($message, 'notifymessage');
        }
    }

    // MARK ITEM - show.

    /**
     * Show an item and (maybe) all its ascendants.
     *
     * Called by:
     *     actions_execution()
     *
     * @return void
     */
    public function item_show_execute() {
        global $DB;

        // Build toshowlist.
        $toshowlist = $this->add_parent_node(['hidden' => 1]);

        $itemstoprocess = count($toshowlist); // This is the list of ancestors.
        if ( ($this->confirm == SURVEYPRO_CONFIRMED_YES) || ($itemstoprocess == 1) ) {
            // Show items.
            foreach ($toshowlist as $toshowitemid) {
                $DB->set_field('surveypro_item', 'hidden', 0, ['id' => $toshowitemid]);
            }
            $utilitylayoutman = new utility_layout($this->cm, $this->surveypro);
            $utilitylayoutman->reset_pages();
        }
    }

    /**
     * Provide a feedback after item_show_execute.
     *
     * @return void
     */
    public function item_show_feedback() {
        global $OUTPUT;

        // Build toshowlist.
        $toshowlist = $this->add_parent_node(['hidden' => 1]);

        $itemstoprocess = count($toshowlist); // This is the list of ancestors.
        if ($this->confirm == SURVEYPRO_UNCONFIRMED) {
            if ($itemstoprocess > 1) { // Ask for confirmation.
                $item = surveypro_get_item($this->cm, $this->surveypro, $this->rootitemid, $this->type, $this->plugin);

                $a = new \stdClass();
                $a->lastitem = $item->get_content();
                $ancestors = array_keys($toshowlist);
                // Drop the original item because it doesn't go in the message.
                $key = array_search($this->sortindex, $ancestors);
                if ($key !== false) { // Should always happen.
                    unset($ancestors[$key]);
                }
                $a->ancestors = implode(', ', $ancestors);
                if (count($ancestors) == 1) {
                    $message = get_string('confirm_show1item', 'mod_surveypro', $a);
                } else {
                    $message = get_string('confirm_showchainitems', 'mod_surveypro', $a);
                }

                $optionbase = [];
                $optionbase['s'] = $this->cm->instance;
                $optionbase['act'] = SURVEYPRO_SHOWITEM;
                $optionbase['itemid'] = $this->rootitemid;
                $optionbase['section'] = 'itemslist';
                $optionbase['sesskey'] = sesskey();

                $optionsyes = $optionbase;
                $optionsyes['cnf'] = SURVEYPRO_CONFIRMED_YES;
                $optionsyes['itemid'] = $this->rootitemid;
                $optionsyes['plugin'] = $this->plugin;
                $optionsyes['type'] = $this->type;
                $urlyes = new \moodle_url('/mod/surveypro/layout.php#sortindex_'.$this->sortindex, $optionsyes);
                $buttonyes = new \single_button($urlyes, get_string('continue'));

                $optionsno = $optionbase;
                $optionsno['cnf'] = SURVEYPRO_CONFIRMED_NO;
                $urlno = new \moodle_url('/mod/surveypro/layout.php#sortindex_'.$this->sortindex, $optionsno);
                $buttonno = new \single_button($urlno, get_string('no'));

                echo $OUTPUT->confirm($message, $buttonyes, $buttonno);
                echo $OUTPUT->footer();
                die();
            }
        }

        if ($this->confirm == SURVEYPRO_CONFIRMED_NO) {
            $message = get_string('usercanceled', 'mod_surveypro');
            echo $OUTPUT->notification($message, 'notifymessage');
        }
    }

    // MARK ITEM - make reserved.

    /**
     * Set the item as reserved.
     *
     * Called by:
     *     actions_execution()
     *
     * the idea is this: in a chain of parent-child items,
     *     -> reserved items can be parent of reserved items only
     *     -> reserved items can be child of reserved items only
     *
     * @return void
     */
    public function item_makereserved_execute() {
        global $DB;

        if ($this->confirm == SURVEYPRO_CONFIRMED_NO) {
            return;
        }

        // Here I must select the whole tree down.
        $itemstoreserve = $this->add_parent_node(['reserved' => 0]);

        // I am interested to oldest parent only.
        $baseitemid = end($itemstoreserve);

        // Build itemstoreserve starting from the oldest parent.
        $itemstoreserve = $this->get_children($baseitemid, ['reserved' => 0]);

        $itemstoprocess = count($itemstoreserve);
        if ( ($this->confirm == SURVEYPRO_CONFIRMED_YES) || ($itemstoprocess == 1) ) {
            // Make items reserved.
            foreach ($itemstoreserve as $itemtoreserve) {
                $DB->set_field('surveypro_item', 'reserved', 1, ['id' => $itemtoreserve->id]);
            }
            $utilitylayoutman = new utility_layout($this->cm, $this->surveypro);
            $utilitylayoutman->reset_pages();
        }
    }

    /**
     * Provide a feedback after item_makereserved_execute.
     *
     * Called by:
     *     actions_feedback()
     *
     * the idea is this: in a chain of parent-child items,
     *     -> reserved items can be parent of reserved items only
     *     -> reserved items can be child of reserved items only
     *
     * @return void
     */
    public function item_makereserved_feedback() {
        global $OUTPUT;

        if ($this->confirm == SURVEYPRO_CONFIRMED_NO) {
            $message = get_string('usercanceled', 'mod_surveypro');
            echo $OUTPUT->notification($message, 'notifymessage');
            return;
        }

        if ($this->confirm == SURVEYPRO_UNCONFIRMED) {
            // Here I must select the whole tree down.
            $itemstoreserve = $this->add_parent_node(['reserved' => 0]);

            // I am interested to oldest parent only.
            $baseitemid = end($itemstoreserve);

            // Build itemstoreserve starting from the oldest parent.
            $itemstoreserve = $this->get_children($baseitemid, ['reserved' => 0]);

            $itemstoprocess = count($itemstoreserve); // This is the list of ancestors.
            if ($itemstoprocess > 1) { // Ask for confirmation.
                // If the clicked element has not parents.
                $a = new \stdClass();
                $item = surveypro_get_item($this->cm, $this->surveypro, $this->rootitemid, $this->type, $this->plugin);
                $a->itemcontent = $item->get_content();
                foreach ($itemstoreserve as $itemtoreserve) {
                    $dependencies[] = $itemtoreserve->sortindex;
                }
                // Drop the original item because it doesn't go in the message.
                $key = array_search($this->sortindex, $dependencies);
                if ($key !== false) { // Should always happen.
                    unset($dependencies[$key]);
                }
                $a->dependencies = implode(', ', $dependencies);

                if ($baseitemid != $this->rootitemid) {
                    $firstparentitem = reset($itemstoreserve);
                    $parentitem = surveypro_get_item($this->cm, $this->surveypro, $firstparentitem->id);
                    $a->parentcontent = $parentitem->get_content();
                    $message = get_string('confirm_reservechainitems_newparent', 'mod_surveypro', $a);
                } else {
                    if (count($dependencies) == 1) {
                        $message = get_string('confirm_reserve1item', 'mod_surveypro', $a);
                    } else {
                        $message = get_string('confirm_reservechainitems', 'mod_surveypro', $a);
                    }
                }

                $optionbase = [];
                $optionbase['s'] = $this->cm->instance;
                $optionbase['act'] = SURVEYPRO_MAKERESERVED;
                $optionbase['section'] = 'itemslist';
                $optionbase['sesskey'] = sesskey();

                $optionsyes = $optionbase;
                $optionsyes['cnf'] = SURVEYPRO_CONFIRMED_YES;
                $optionsyes['itemid'] = $this->rootitemid;
                $optionsyes['plugin'] = $this->plugin;
                $optionsyes['type'] = $this->type;
                $urlyes = new \moodle_url('/mod/surveypro/layout.php#sortindex_'.$this->sortindex, $optionsyes);
                $buttonyes = new \single_button($urlyes, get_string('continue'));

                $optionsno = $optionbase;
                $optionsno['cnf'] = SURVEYPRO_CONFIRMED_NO;
                $urlno = new \moodle_url('/mod/surveypro/layout.php#sortindex_'.$this->sortindex, $optionsno);
                $buttonno = new \single_button($urlno, get_string('no'));

                echo $OUTPUT->confirm($message, $buttonyes, $buttonno);
                echo $OUTPUT->footer();
                die();
            }
        }
    }

    // MARK ITEM - make available.

    /**
     * Set the item as standard (free).
     *
     * Called by:
     *     actions_execution()
     *
     * the idea is this: in a chain of parent-child items,
     *     -> available items (not reserved) can be parent of available items only
     *     -> available items (not reserved) can be child of available items only
     *
     * @return void
     */
    public function item_makeavailable_execute() {
        global $DB;

        if ($this->confirm == SURVEYPRO_CONFIRMED_NO) {
            return;
        }

        // Build itemstoavailable.
        $itemstoavailable = $this->add_parent_node(['reserved' => 1]);

        // I am interested to oldest parent only.
        $baseitemid = end($itemstoavailable);

        // Build itemstoavailable starting from the oldest parent.
        $itemstoavailable = $this->get_children($baseitemid, ['reserved' => 1]);

        $itemstoprocess = count($itemstoavailable); // This is the list of ancestors.
        if ( ($this->confirm == SURVEYPRO_CONFIRMED_YES) || ($itemstoprocess == 1) ) {
            // Make items available.
            foreach ($itemstoavailable as $itemtoavailable) {
                $DB->set_field('surveypro_item', 'reserved', 0, ['id' => $itemtoavailable->id]);
            }
            $utilitylayoutman = new utility_layout($this->cm, $this->surveypro);
            $utilitylayoutman->reset_pages();
        }
    }

    /**
     * Provide a feedback after item_makeavailable_execute.
     *
     * Called by:
     *     actions_feedback()
     *
     * the idea is this: in a chain of parent-child items,
     *     -> available items (not reserved) can be parent of available items only
     *     -> available items (not reserved) can be child of available items only
     *
     * @return void
     */
    public function item_makeavailable_feedback() {
        global $OUTPUT;

        if ($this->confirm == SURVEYPRO_CONFIRMED_NO) {
            $message = get_string('usercanceled', 'mod_surveypro');
            echo $OUTPUT->notification($message, 'notifymessage');
            return;
        }

        if ($this->confirm == SURVEYPRO_UNCONFIRMED) {
            // Build itemstoavailable.
            $itemstoavailable = $this->add_parent_node(['reserved' => 1]);

            // I am interested to oldest parent only.
            $baseitemid = end($itemstoavailable);

            // Build itemstoavailable starting from the oldest parent.
            $itemstoavailable = $this->get_children($baseitemid, ['reserved' => 1]);

            $itemstoprocess = count($itemstoavailable); // This is the list of ancestors.
            if ($itemstoprocess > 1) { // Ask for confirmation.
                // If the clicked element has not parents.
                $a = new \stdClass();
                $item = surveypro_get_item($this->cm, $this->surveypro, $this->rootitemid, $this->type, $this->plugin);
                $a->itemcontent = $item->get_content();
                foreach ($itemstoavailable as $itemtoavailable) {
                    $dependencies[] = $itemtoavailable->sortindex;
                }
                // Drop the original item because it doesn't go in the message.
                $key = array_search($this->sortindex, $dependencies);
                if ($key !== false) { // Should always happen.
                    unset($dependencies[$key]);
                }
                $a->dependencies = implode(', ', $dependencies);

                if ($baseitemid != $this->rootitemid) {
                    $firstparentitem = reset($itemstoavailable);
                    $parentitem = surveypro_get_item($this->cm, $this->surveypro, $firstparentitem->id);
                    $a->parentcontent = $parentitem->get_content();
                    $message = get_string('confirm_freechainitems_newparent', 'mod_surveypro', $a);
                } else {
                    if (count($dependencies) == 1) {
                        $message = get_string('confirm_free1item', 'mod_surveypro', $a);
                    } else {
                        $message = get_string('confirm_freechainitems', 'mod_surveypro', $a);
                    }
                }

                $optionbase = [];
                $optionbase['s'] = $this->cm->instance;
                $optionbase['act'] = SURVEYPRO_MAKEAVAILABLE;
                $optionbase['itemid'] = $this->rootitemid;
                $optionbase['section'] = 'itemslist';
                $optionbase['sesskey'] = sesskey();

                $optionsyes = $optionbase;
                $optionsyes['cnf'] = SURVEYPRO_CONFIRMED_YES;
                $optionsyes['itemid'] = $this->rootitemid;
                $optionsyes['plugin'] = $this->plugin;
                $optionsyes['type'] = $this->type;
                $urlyes = new \moodle_url('/mod/surveypro/layout.php#sortindex_'.$this->sortindex, $optionsyes);
                $buttonyes = new \single_button($urlyes, get_string('continue'));

                $optionsno = $optionbase;
                $optionsno['cnf'] = SURVEYPRO_CONFIRMED_NO;
                $urlno = new \moodle_url('/mod/surveypro/layout.php#sortindex_'.$this->sortindex, $optionsno);
                $buttonno = new \single_button($urlno, get_string('no'));

                echo $OUTPUT->confirm($message, $buttonyes, $buttonno);
                echo $OUTPUT->footer();
                die();
            }
        }
    }

    // MARK ITEM - delete.

    /**
     * Delete an item and (maybe) all its descendants.
     *
     * @return void
     */
    public function item_delete_execute() {
        global $DB;

        if ($this->confirm == SURVEYPRO_CONFIRMED_YES) {
            // After the item deletion action, if the user reload the page, the deletion is performed again rising up an error.
            // If the item to drop is not in the db, this means that the user already deleted it and is reloading the page.
            // In this case, stop the deletion execution.
            if (!$DB->record_exists('surveypro_item', ['id' => $this->rootitemid])) {
                return;
            }

            $utilitylayoutman = new utility_layout($this->cm, $this->surveypro);
            $utilitylayoutman->reset_pages();

            $whereparams = ['surveyproid' => $this->surveypro->id];

            $itemstodelete = $this->get_children();
            array_shift($itemstodelete);
            if ($itemstodelete) {
                foreach ($itemstodelete as $itemtodelete) {
                    $whereparams['id'] = $itemtodelete->id;
                    $utilitylayoutman->delete_items($whereparams);
                }
            }

            // Get the content of the item for the feedback message.
            $item = surveypro_get_item($this->cm, $this->surveypro, $this->rootitemid, $this->type, $this->plugin);

            $killedsortindex = $item->get_sortindex();
            $whereparams = ['id' => $this->rootitemid];
            $utilitylayoutman->delete_items($whereparams);

            $utilitylayoutman->items_reindex($killedsortindex);
            $this->confirm = SURVEYPRO_ACTION_EXECUTED;

            $itemcount = $utilitylayoutman->has_items(0, SURVEYPRO_TYPEFIELD, true, true, true);
            $this->set_itemcount($itemcount);

            $this->actionfeedback = new \stdClass();
            $this->actionfeedback->chain = !empty($itemstodelete);
            $this->actionfeedback->content = $item->get_content();
            $this->actionfeedback->pluginname = strtolower(get_string('pluginname', 'surveypro'.$this->type.'_'.$this->plugin));
        }
    }

    /**
     * Provide a feedback after item_delete_execute.
     *
     * @return void
     */
    public function item_delete_feedback() {
        global $DB, $OUTPUT;

        if ($this->confirm == SURVEYPRO_UNCONFIRMED) {
            // Ask for confirmation.
            // In the frame of the confirmation I need to declare whether some child will break the link.
            $item = surveypro_get_item($this->cm, $this->surveypro, $this->rootitemid, $this->type, $this->plugin);

            $a = new \stdClass();
            $a->content = $item->get_content();
            $a->pluginname = strtolower(get_string('pluginname', 'surveypro'.$this->type.'_'.$this->plugin));
            $message = get_string('confirm_delete1item', 'mod_surveypro', $a);

            // Is there any child item chain to break? (Sortindex is supposed to be a valid key in the next query).
            $itemstodelete = $this->get_children();
            array_shift($itemstodelete);
            if ($itemstodelete) {
                foreach ($itemstodelete as $itemtodelete) {
                    $childrenids[] = $itemtodelete->sortindex;
                }
                $nodes = implode(', ', $childrenids);
                $message .= ' '.get_string('confirm_deletechainitems', 'mod_surveypro', $nodes);
                $labelyes = get_string('continue');
            } else {
                $labelyes = get_string('yes');
            }

            $optionbase['s'] = $this->cm->instance;
            $optionbase['act'] = SURVEYPRO_DELETEITEM;
            $optionbase['section'] = 'itemslist';
            $optionbase['sesskey'] = sesskey();

            $optionsyes = $optionbase;
            $optionsyes['cnf'] = SURVEYPRO_CONFIRMED_YES;
            $optionsyes['itemid'] = $this->rootitemid;
            $optionsyes['plugin'] = $this->plugin;
            $optionsyes['type'] = $this->type;

            $urlyes = new \moodle_url('/mod/surveypro/layout.php', $optionsyes);
            $buttonyes = new \single_button($urlyes, $labelyes);

            $optionsno = $optionbase;
            $optionsno['cnf'] = SURVEYPRO_CONFIRMED_NO;

            $urlno = new \moodle_url('/mod/surveypro/layout.php', $optionsno);
            $buttonno = new \single_button($urlno, get_string('no'));

            echo $OUTPUT->confirm($message, $buttonyes, $buttonno);
            echo $OUTPUT->footer();
            die();
        }

        if ($this->confirm == SURVEYPRO_CONFIRMED_NO) {
            $message = get_string('usercanceled', 'mod_surveypro');
            echo $OUTPUT->notification($message, 'notifymessage');
        }

        if ($this->confirm == SURVEYPRO_ACTION_EXECUTED) {
            $a = new \stdClass();
            $a->content = $this->actionfeedback->content;
            $a->pluginname = $this->actionfeedback->pluginname;
            if ($this->actionfeedback->chain) {
                $message = get_string('feedback_deletechainitems', 'mod_surveypro', $a);
            } else {
                $message = get_string('feedback_delete1item', 'mod_surveypro', $a);
            }
            echo $OUTPUT->notification($message, 'notifysuccess');
        }
    }

    // MARK BULK - all show.

    /**
     * Show all items.
     *
     * Called by:
     *     actions_execution()
     *
     * @return void
     */
    public function show_all_execute() {
        if ($this->confirm == SURVEYPRO_CONFIRMED_YES) {
            $utilitylayoutman = new utility_layout($this->cm, $this->surveypro);

            $whereparams = ['surveyproid' => $this->surveypro->id];
            $utilitylayoutman->items_set_visibility($whereparams, 1);

            $utilitylayoutman->items_reindex();

            $this->set_confirm(SURVEYPRO_ACTION_EXECUTED);
        }
    }

    /**
     * Provide a feedback after show_all_execute.
     *
     * Called by:
     *     actions_feedback()
     *
     * @return void
     */
    public function show_all_feedback() {
        global $OUTPUT;

        if ($this->confirm == SURVEYPRO_UNCONFIRMED) {
            $message = get_string('confirm_showallitems', 'mod_surveypro');
            $yeskey = 'yes_showallitems';
            $this->bulk_action_ask($message, $yeskey);
        }

        if ($this->confirm == SURVEYPRO_CONFIRMED_NO) {
            $message = get_string('usercanceled', 'mod_surveypro');
            echo $OUTPUT->notification($message, 'notifymessage');
        }

        if ($this->confirm == SURVEYPRO_ACTION_EXECUTED) {
            $message = get_string('feedback_showallitems', 'mod_surveypro');
            echo $OUTPUT->notification($message, 'notifysuccess');
        }
    }

    // MARK BULK - all hide.

    /**
     * Hide all items.
     *
     * Called by:
     *     actions_execution()
     *
     * @return void
     */
    public function hide_all_execute() {
        if ($this->confirm == SURVEYPRO_CONFIRMED_YES) {
            $utilitylayoutman = new utility_layout($this->cm, $this->surveypro);
            $whereparams = ['surveyproid' => $this->surveypro->id];
            $utilitylayoutman->items_set_visibility($whereparams, 0);

            $utilitylayoutman->reset_pages();

            $this->set_confirm(SURVEYPRO_ACTION_EXECUTED);
        }
    }

    /**
     * Provide a feedback after hide_all_execute.
     *
     * Called by:
     *     actions_feedback()
     *
     * @return void
     */
    public function hide_all_feedback() {
        global $OUTPUT;

        if ($this->confirm == SURVEYPRO_UNCONFIRMED) {
            $message = get_string('confirm_hideallitems', 'mod_surveypro');
            $yeskey = 'yes_hideallitems';
            $this->bulk_action_ask($message, $yeskey);
        }

        if ($this->confirm == SURVEYPRO_CONFIRMED_NO) {
            $message = get_string('usercanceled', 'mod_surveypro');
            echo $OUTPUT->notification($message, 'notifymessage');
        }

        if ($this->confirm == SURVEYPRO_ACTION_EXECUTED) {
            $message = get_string('feedback_hideallitems', 'mod_surveypro');
            echo $OUTPUT->notification($message, 'notifysuccess');
        }
    }

    // MARK BULK - all delete.

    /**
     * Delete all items.
     *
     * Called by:
     *     actions_execution()
     *
     * @return void
     */
    public function delete_all_execute() {
        if ($this->confirm == SURVEYPRO_CONFIRMED_YES) {
            $utilitylayoutman = new utility_layout($this->cm, $this->surveypro);

            $whereparams = ['surveyproid' => $this->surveypro->id];
            $utilitylayoutman->delete_items($whereparams);

            $paramurl = [];
            $paramurl['s'] = $this->cm->instance;
            $paramurl['section'] = 'itemslist';
            $returnurl = new \moodle_url('/mod/surveypro/layout.php', $paramurl);
            redirect($returnurl);
        }
    }

    /**
     * Provide a feedback after delete_all_execute.
     *
     * Called by:
     *     actions_feedback()
     *
     * @return void
     */
    public function delete_all_feedback() {
        global $OUTPUT;

        if ($this->confirm == SURVEYPRO_UNCONFIRMED) {
            $message = get_string('confirm_deleteallitems', 'mod_surveypro');
            $yeskey = 'yes_deleteallitems';
            $this->bulk_action_ask($message, $yeskey);
        }

        if ($this->confirm == SURVEYPRO_CONFIRMED_NO) {
            $message = get_string('usercanceled', 'mod_surveypro');
            echo $OUTPUT->notification($message, 'notifymessage');
        }

        if ($this->confirm == SURVEYPRO_ACTION_EXECUTED) {
            $message = get_string('feedback_deleteallitems', 'mod_surveypro');
            echo $OUTPUT->notification($message, 'notifysuccess');
        }
    }

    // MARK BULK - visible delete.

    /**
     * Delete visible items.
     *
     * Called by:
     *     actions_execution()
     *
     * @return void
     */
    public function delete_visible_execute() {
        if ($this->confirm == SURVEYPRO_CONFIRMED_YES) {
            $utilitylayoutman = new utility_layout($this->cm, $this->surveypro);

            $whereparams = ['surveyproid' => $this->surveypro->id];
            $whereparams['hidden'] = 0;
            $utilitylayoutman->delete_items($whereparams);

            $utilitylayoutman->items_reindex();

            $paramurl = [];
            $paramurl['s'] = $this->cm->instance;
            $paramurl['act'] = SURVEYPRO_DELETEVISIBLEITEMS;
            $paramurl['section'] = 'itemslist';
            $paramurl['sesskey'] = sesskey();
            $paramurl['cnf'] = SURVEYPRO_ACTION_EXECUTED;
            $returnurl = new \moodle_url('/mod/surveypro/layout.php', $paramurl);
            redirect($returnurl);
        }
    }

    /**
     * Provide a feedback after delete_visible_execute.
     *
     * Called by:
     *     actions_feedback()
     *
     * @return void
     */
    public function delete_visible_feedback() {
        global $OUTPUT;

        if ($this->confirm == SURVEYPRO_UNCONFIRMED) {
            $message = get_string('confirm_deletevisibleitems', 'mod_surveypro');
            $yeskey = 'yes_deletevisibleitems';
            $this->bulk_action_ask($message, $yeskey);
        }

        if ($this->confirm == SURVEYPRO_CONFIRMED_NO) {
            $message = get_string('usercanceled', 'mod_surveypro');
            echo $OUTPUT->notification($message, 'notifymessage');
        }

        if ($this->confirm == SURVEYPRO_ACTION_EXECUTED) {
            $message = get_string('feedback_deletevisibleitems', 'mod_surveypro');
            echo $OUTPUT->notification($message, 'notifysuccess');
        }
    }

    // MARK BULK - hidden delete.

    /**
     * Delete hidden items.
     *
     * Called by:
     *     actions_execution()
     *
     * @return void
     */
    public function delete_hidden_execute() {
        if ($this->confirm == SURVEYPRO_CONFIRMED_YES) {
            $utilitylayoutman = new utility_layout($this->cm, $this->surveypro);

            $whereparams = ['surveyproid' => $this->surveypro->id];
            $whereparams['hidden'] = 1;
            $utilitylayoutman->delete_items($whereparams);

            $utilitylayoutman->items_reindex();

            $paramurl = [];
            $paramurl['s'] = $this->cm->instance;
            $paramurl['act'] = SURVEYPRO_DELETEHIDDENITEMS;
            $paramurl['section'] = 'itemslist';
            $paramurl['sesskey'] = sesskey();
            $paramurl['cnf'] = SURVEYPRO_ACTION_EXECUTED;
            $returnurl = new \moodle_url('/mod/surveypro/layout.php', $paramurl);
            redirect($returnurl);
        }
    }

    /**
     * Provide a feedback after delete_hidden_feedback.
     *
     * Called by:
     *     actions_feedback
     *
     * @return void
     */
    public function delete_hidden_feedback() {
        global $OUTPUT;

        if ($this->confirm == SURVEYPRO_UNCONFIRMED) {
            $message = get_string('confirm_deletehiddenitems', 'mod_surveypro');
            $yeskey = 'yes_deletehiddenitems';
            $this->bulk_action_ask($message, $yeskey);
        }

        if ($this->confirm == SURVEYPRO_CONFIRMED_NO) {
            $message = get_string('usercanceled', 'mod_surveypro');
            echo $OUTPUT->notification($message, 'notifymessage');
        }

        if ($this->confirm == SURVEYPRO_ACTION_EXECUTED) {
            $message = get_string('feedback_deletehiddenitems', 'mod_surveypro');
            echo $OUTPUT->notification($message, 'notifysuccess');
        }
    }

    // MARK feedback section.

    /**
     * Display a feedback for the editing teacher once an item is edited.
     *
     * Called by:
     *     layout.php
     *
     * @return void
     */
    public function display_item_editing_feedback() {
        global $OUTPUT;

        if ($this->itemeditingfeedback == SURVEYPRO_NOFEEDBACK) {
            return;
        }

        // Look at position 1.
        $bit = $this->itemeditingfeedback & 2; // Bitwise logic.
        if ($bit) { // Edit.
            $bit = $this->itemeditingfeedback & 1; // Bitwise logic.
            if ($bit) {
                $message = get_string('feedback_itemediting_ok', 'mod_surveypro');
                $class = 'notifysuccess';
            } else {
                $message = get_string('feedback_itemediting_ko', 'mod_surveypro');
                $class = 'notifyproblem';
            }
        } else {    // Add.
            $bit = $this->itemeditingfeedback & 1; // Bitwise logic.
            if ($bit) {
                $message = get_string('feedback_itemadd_ok', 'mod_surveypro');
                $class = 'notifysuccess';
            } else {
                $message = get_string('feedback_itemadd_ko', 'mod_surveypro');
                $class = 'notifyproblem';
            }
        }

        for ($position = 2; $position <= 5; $position++) {
            $bit = $this->itemeditingfeedback & pow(2, $position); // Bitwise logic.
            switch ($position) {
                case 2: // A chain of items is now shown.
                    if ($bit) {
                        $message .= '<br>'.get_string('feedback_itemediting_showchainitems', 'mod_surveypro');
                    }
                    break;
                case 3: // A chain of items is now hided because one item was hided.
                    if ($bit) {
                        $message .= '<br>'.get_string('feedback_itemediting_hidechainitems', 'mod_surveypro');
                    }
                    break;
                case 4: // A chain of items was moved in the user entry form.
                    if ($bit) {
                        $message .= '<br>'.get_string('feedback_itemediting_freechainitems', 'mod_surveypro');
                    }
                    break;
                case 5: // A chain of items was removed from the user entry form.
                    if ($bit) {
                        $message .= '<br>'.get_string('feedback_itemediting_reservechainitems', 'mod_surveypro');
                    }
                    break;
            }
        }
        echo $OUTPUT->notification($message, $class);
    }

    // MARK drop multilang.

    /**
     * Drop multilang from all the item.
     *
     * Called by:
     *     actions_execution()
     *
     * @return void
     */
    public function drop_multilang_execute() {
        global $DB;

        if ($this->confirm == SURVEYPRO_CONFIRMED_YES) {
            $template = $this->surveypro->template;
            $where = ['surveyproid' => $this->surveypro->id];
            $itemseeds = $DB->get_records('surveypro_item', $where, 'sortindex', 'id, type, plugin');
            foreach ($itemseeds as $itemseed) {
                $id = $itemseed->id;
                $type = $itemseed->type;
                $plugin = $itemseed->plugin;
                $item = surveypro_get_item($this->cm, $this->surveypro, $id, $type, $plugin);
                $itemsmlfields = $item->get_multilang_fields(); // Pagebreak and fieldsetend have no multilang_fields.
                if ($itemsmlfields[$plugin]) {
                    // Note: ml means multi language.
                    foreach ($itemsmlfields as $itemmlfield) { // Note: $itemmlfield is an array of fields.
                        $record = new \stdClass();
                        $record->id = $item->get_pluginid();

                        $where = ['id' => $record->id];
                        $fieldlist = implode(',', $itemmlfield);
                        // SELECT content,extranote,options,labelother,defaultvalue FROM {surveyprofield_radiobutton} WHERE id = 8.
                        $reference = $DB->get_record('surveypro'.$type.'_'.$plugin, $where, $fieldlist, MUST_EXIST);
                        foreach ($itemmlfield as $mlfieldname) {
                            $stringkey = $reference->{$mlfieldname};
                            if (core_text::strlen($stringkey)) {
                                $record->{$mlfieldname} = get_string($stringkey, 'surveyprotemplate_'.$template);
                            } else {
                                $record->{$mlfieldname} = null;
                            }
                        }
                        $DB->update_record('surveypro'.$type.'_'.$plugin, $record);
                    }
                }
            }
            $surveypro = new \stdClass();
            $surveypro->id = $this->surveypro->id;
            $surveypro->template = null;
            $DB->update_record('surveypro', $surveypro);

            $paramurl = [];
            $paramurl['s'] = $this->cm->instance;
            $paramurl['act'] = SURVEYPRO_DROPMULTILANG;
            $paramurl['section'] = 'itemslist';
            $paramurl['sesskey'] = sesskey();
            $paramurl['cnf'] = SURVEYPRO_ACTION_EXECUTED;
            $returnurl = new \moodle_url('/mod/surveypro/layout.php', $paramurl);
            redirect($returnurl);
        }

        if ($this->confirm == SURVEYPRO_CONFIRMED_NO) {
            $paramurl = ['s' => $this->cm->instance, 'section' => 'itemslist'];
            $returnurl = new \moodle_url('/mod/surveypro/layout.php', $paramurl);
            redirect($returnurl);
        }
    }

    /**
     * Provide a feedback after drop_multilang_execute.
     *
     * Called by:
     *     actions_feedback()
     *
     * @return void
     */
    public function drop_multilang_feedback() {
        global $OUTPUT;

        if ($this->confirm == SURVEYPRO_UNCONFIRMED) {
            // Ask for confirmation.
            $message = get_string('confirm_dropmultilang', 'mod_surveypro');

            $optionbase = ['s' => $this->cm->instance, 'act' => SURVEYPRO_DROPMULTILANG, 'section' => 'itemslist'];

            $optionsyes = $optionbase;
            $optionsyes['cnf'] = SURVEYPRO_CONFIRMED_YES;
            $urlyes = new \moodle_url('/mod/surveypro/layout.php', $optionsyes);
            $buttonyes = new \single_button($urlyes, get_string('yes'));

            $optionsno = $optionbase;
            $optionsno['cnf'] = SURVEYPRO_CONFIRMED_NO;
            $urlno = new \moodle_url('/mod/surveypro/layout.php', $optionsno);
            $buttonno = new \single_button($urlno, get_string('no'));

            echo $OUTPUT->confirm($message, $buttonyes, $buttonno);
            echo $OUTPUT->footer();
            die();
        }

        if ($this->confirm == SURVEYPRO_ACTION_EXECUTED) {
            $message = get_string('feedback_dropmultilang', 'mod_surveypro');
            echo $OUTPUT->notification($message, 'notifysuccess');
        }
    }

    // MARK set.

    /**
     * Set type.
     *
     * Called by:
     *     layout.php
     *
     * @param string $type
     * @return void
     */
    public function set_type($type) {
        $this->type = $type;
    }

    /**
     * Set plugin.
     *
     * Called by:
     *     layout.php
     *
     * @param string $plugin
     * @return void
     */
    public function set_plugin($plugin) {
        $this->plugin = $plugin;
    }

    /**
     * Set itemid.
     *
     * Called by:
     *     layout.php
     *
     * @param int $itemid
     * @return void
     */
    public function set_itemid($itemid) {
        $this->rootitemid = $itemid;
    }

    /**
     * Set sortindex.
     *
     * Called by:
     *     layout.php
     *
     * @param int $sortindex
     * @return void
     */
    public function set_sortindex($sortindex) {
        $this->sortindex = $sortindex;
    }

    /**
     * Set action.
     *
     * Called by:
     *     layout.php
     *
     * @param int $action
     * @return void
     */
    public function set_action($action) {
        $this->action = $action;
    }

    /**
     * Set mode.
     *
     * Called by:
     *     layout.php
     *
     * @param int $mode
     * @return void
     */
    public function set_mode($mode) {
        $this->mode = $mode;
    }

    /**
     * Set itemtomove.
     *
     * Called by:
     *     layout.php
     *
     * @param int $itemtomove
     * @return void
     */
    public function set_itemtomove($itemtomove) {
        $this->itemtomove = $itemtomove;
    }

    /**
     * Set last item before.
     *
     * Called by:
     *     layout.php
     *
     * @param int $lastitembefore
     * @return void
     */
    public function set_lastitembefore($lastitembefore) {
        $this->lastitembefore = $lastitembefore;
    }

    /**
     * Set nextindent.
     *
     * Called by:
     *     layout.php
     *
     * @param int $nextindent
     * @return void
     */
    public function set_nextindent($nextindent) {
        $this->nextindent = $nextindent;
    }

    /**
     * Set parentid.
     *
     * Called by:
     *     layout.php
     *
     * @param int $parentid
     * @return void
     */
    public function set_parentid($parentid) {
        $this->parentid = $parentid;
    }

    /**
     * Set confirm.
     *
     * Called by:
     *     layout.php
     *
     * @param int $confirm
     * @return void
     */
    public function set_confirm($confirm) {
        $this->confirm = $confirm;
    }

    /**
     * Set item editing feedback.
     *
     * Called by:
     *     layout.php
     *
     * @param int $itemeditingfeedback
     * @return void
     */
    public function set_itemeditingfeedback($itemeditingfeedback) {
        $this->itemeditingfeedback = $itemeditingfeedback;
    }

    /**
     * Set hassubmissions.
     *
     * Called by:
     *     layout.php
     *
     * @param int $hassubmissions
     * @return void
     */
    public function set_hassubmissions($hassubmissions) {
        $this->hassubmissions = $hassubmissions;
    }

    /**
     * Set itemcount.
     *
     * Called by:
     *     setup()
     *
     * @param int $itemcount
     * @return void
     */
    public function set_itemcount($itemcount) {
        $this->itemcount = $itemcount;
    }
}
