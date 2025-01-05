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
 * Surveypro formbase class.
 *
 * @package   mod_surveypro
 * @copyright 2013 onwards kordan <stringapiccola@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_surveypro;

use mod_surveypro\utility_layout;

/**
 * The base class representing the commom part of the item form
 *
 * @package   mod_surveypro
 * @copyright 2013 onwards kordan <stringapiccola@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class formbase {

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
     * @var int Id of the saved submission
     */
    protected $submissionid;

    /**
     * @var int Form page as recalculated according to the first non empty page
     */
    protected $formpage;

    /**
     * @var int The minimum page the user may see
     */
    protected $userfirstpage;

    /**
     * @var int The maximum page the user may see
     */
    protected $userlastpage;

    /**
     * @var int Does the user went to overflow asking for a different page?
     */
    protected $overflowpage;

    /**
     * @var int Last page of the out form
     */
    protected $userformpagecount;

    /**
     * @var int The page number where the user is going to go.
     */
    protected $nextpage;

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
     * Set the first and the past page of the surveypro for the current user.
     *
     * Of course the surveypro is divided into userformpagecount
     * so the first page is ALWaYS 1 and the last page is ALWAYS $userformpagecount
     * But what I am asking now is:
     * according with the user capability has_capability('mod/surveypro:accessreserveditems', $context)
     * which is the minumun page the user can access? And the maximum?
     * In ither words: if in the first page there are ONLY reserved item,
     * a simple user may browse the surveypro only from page 2 to $userformpagecount.
     * I am looking for these new boundary (that will be === with 1 and $userformpagecount in 99% of cases).
     */
    public function set_user_boundary_formpages() {
        global $DB;

        $canaccessreserveditems = has_capability('mod/surveypro:accessreserveditems', $this->context);

        if ($canaccessreserveditems) {
            $userfirstpage = 1;
            $userlastpage = $this->userformpagecount;
        } else {
            $sql = 'SELECT MIN(formpage) as userfirstpage, MAX(formpage) as userlastpage
                        FROM {surveypro_item}
                        WHERE surveyproid = :surveyproid
                            AND reserved = :reserved
                            AND plugin <> :plugin';
            $whereparams = ['surveyproid' => $this->surveypro->id, 'reserved' => 0, 'plugin' => 'pagebreak'];
            $boundaries = $DB->get_record_sql($sql, $whereparams);

            $userfirstpage = isset($boundaries->userfirstpage) ? $boundaries->userfirstpage : 1;
            $userlastpage = isset($boundaries->userlastpage) ? $boundaries->userlastpage : $this->userformpagecount;
        }

        $this->set_userfirstpage($userfirstpage);
        $this->set_userlastpage($userlastpage);
    }

    /**
     * Get the first NON EMPTY page on the right or on the left.
     *
     * Depending on answers provided by the user, the previous or next page may have no items to display
     * The purpose of this function is to get the first page WITH items.
     * It even may happen that there are no more pages with items.
     * In this case this method sets $this->overflowpage to 1 otherwise sets $this->overflowpage to 0.
     *
     * If $rightdirection == true, this method sets to $this->nextpage
     *     the page number of the lower non empty page (according to user answers)
     *     greater than $startingpage and 0 in $this->overflowpage.
     *     If no more empty pages are found on the right sets:
     *     $this->nextpage = $userformpagecount and $this->overflowpage = 1
     *
     * If $rightdirection == false, this method sets to $this->nextpage
     *     the page number of the greater non empty page (according to user answers)
     *     lower than $startingpage and 0 in $this->overflowpage.
     *     If no more empty pages are found on the left sets:
     *     $this->nextpage = 1 and $this->overflowpage = 1
     *
     * @param bool $rightdirection
     * @param int $startingpage
     * @return void
     */
    public function next_not_empty_page($rightdirection, $startingpage=null) {
        if ($startingpage === null) {
            $startingpage = $this->get_formpage();
        }

        $userformpagecount = $this->get_userformpagecount();
        $condition = ($startingpage == $userformpagecount) && ($rightdirection);
        $condition = $condition || (($startingpage == 1) && (!$rightdirection));
        if ($condition) {
            $a = new \stdClass();
            $a->methodname = 'next_not_empty_page';
            $a->startingpage = $startingpage;
            throw new \moodle_exception('wrong_direction_found', 'mod_surveypro', null, $a);
        }

        // Let's start saying next page is the trivial one.
        if ($rightdirection) {
            $nextpage = $startingpage + 1;
            // Here maxpage should be $maxformpage, but I have to add 1 because of ($i != $overflowpage).
            $overflowpage = $userformpagecount + 1;
        } else {
            $nextpage = $startingpage - 1;
            // Here minpage should be 1, but I have to take 1 out because of ($i != $overflowpage).
            $overflowpage = 0;
        }

        do {
            if ($this->page_has_items($nextpage)) {
                break;
            }
            $nextpage = ($rightdirection) ? $nextpage + 1 : $nextpage - 1;
        } while ($nextpage != $overflowpage);

        if ($nextpage == $overflowpage) {
            $this->set_overflowpage(1);
            $nextpage = ($rightdirection) ? $userformpagecount : 1;
        } else {
            $this->set_overflowpage(0);
        }

        $this->set_nextpage($nextpage);
    }

    /**
     * Declares if the passed page of the survey is going to hold at least one item.
     *
     * In this method, I am not ONLY going to check if the page $formpage has items
     * but I also verify that those items are going to be displayed
     * on the basis of the answers provided to their parent
     *
     * @param int $formpage
     * @return bool
     */
    private function page_has_items($formpage) {
        global $DB;

        $canaccessreserveditems = has_capability('mod/surveypro:accessreserveditems', $this->context);

        [$where, $params] = surveypro_fetch_items_seeds(
            $this->surveypro->id, true, $canaccessreserveditems, null, null, $formpage
        );
        // Here I can not use get_recordset_select because I could browse returned records twice.
        $itemseeds = $DB->get_records_select('surveypro_item', $where, $params, 'sortindex', 'id, parentid, parentvalue');

        // Start looking ONLY at empty($itemseed->parentid) because it doesn't involve extra queries.
        foreach ($itemseeds as $itemseed) {
            if (empty($itemseed->parentid)) {
                // If at least one item has no parent, I finished. The page is going to display items.
                return true;
            }
        }

        foreach ($itemseeds as $itemseed) {
            $parentitem = surveypro_get_item($this->cm, $this->surveypro, $itemseed->parentid);
            if ($parentitem->userform_is_child_allowed_static($this->get_submissionid(), $itemseed)) {
                // If at least one parent allows its child, I finished. The page is going to display items.
                return true;
            }
        }

        // If you were not able to get out in the two previous occasions... this page is empty.
        return false;
    }

    /**
     * Stop the page load with a warning because no item is available.
     *
     * @return void
     */
    public function noitem_stopexecution() {
        global $COURSE, $OUTPUT;

        $canaccessreserveditems = has_capability('mod/surveypro:accessreserveditems', $this->context);

        $utilitylayoutman = new utility_layout($this->cm, $this->surveypro);
        if (!$utilitylayoutman->has_items(0, SURVEYPRO_TYPEFIELD, false, $canaccessreserveditems)) {
            $canmanageitems = has_capability('mod/surveypro:manageitems', $this->context);

            if ($canmanageitems) {
                $a = get_string('layout', 'mod_surveypro');
                $a .= ' > ';
                $a .= get_string('layout_items', 'mod_surveypro');
                $message = get_string('noitemsfoundadmin', 'mod_surveypro', $a);
                echo $OUTPUT->notification($message, 'notifyproblem');
            } else {
                // More or less no user without $canmanageitems should ever be here.
                $message = get_string('noitemsfound', 'mod_surveypro');
                echo $OUTPUT->container($message, 'notifyproblem');

                $continueurl = new \moodle_url('/course/view.php', ['id' => $COURSE->id]);
                echo $OUTPUT->continue_button($continueurl);
            }

            echo $OUTPUT->footer();
            die();
        }
    }

    /**
     * Get prefill data.
     *
     * @return array
     */
    public function get_prefill_data() {
        global $DB;

        $prefill = [];
        if (empty($this->submissionid)) {
            return $prefill;
        }

        $canaccessreserveditems = has_capability('mod/surveypro:accessreserveditems', $this->context);
        $id = $this->surveypro->id;
        $page = $this->formpage;
        [$where, $params] = surveypro_fetch_items_seeds($id, true, $canaccessreserveditems, null, SURVEYPRO_TYPEFIELD, $page);
        if ($itemseeds = $DB->get_recordset_select('surveypro_item', $where, $params, 'sortindex', 'id, type, plugin')) {
            foreach ($itemseeds as $itemseed) {
                $item = surveypro_get_item($this->cm, $this->surveypro, $itemseed->id, $itemseed->type, $itemseed->plugin);

                $where = ['submissionid' => $this->submissionid, 'itemid' => $item->get_itemid()];
                $olduserdata = $DB->get_record('surveypro_answer', $where);
                $singleprefill = $item->userform_get_prefill($olduserdata);
                $prefill = array_merge($prefill, $singleprefill);
            }
            $itemseeds->close();
        }

        $prefill['submissionid'] = $this->submissionid;

        return $prefill;
    }
	
    public function getMateriaQuestionCount($surveyid) {
        global $DB;
        // Prepare the associative array to hold idmateria and question count
        $result = [];

        try {
            // Prepare the SQL query to get idmateria and question count from both tables via the itemid relation
            $sql = "SELECT combined_questions.idmateria, COUNT(*) AS question_count
        FROM (
            SELECT itemid, idmateria FROM {surveyprofield_careybutton}
            UNION ALL
            SELECT itemid, idmateria FROM {surveyprofield_sliders}
            UNION ALL
            SELECT itemid, idmateria FROM {surveyprofield_textareacarey}
            UNION ALL
            SELECT itemid, idmateria FROM {surveyproformat_labelmateria}
        ) AS combined_questions
        JOIN {surveypro_item} si ON si.id = combined_questions.itemid
        WHERE si.surveyproid = :surveyproid
        GROUP BY combined_questions.idmateria";
            
            // Execute the query using Moodle's $DB API
            $params = ['surveyproid' => $surveyid];
            $records = $DB->get_records_sql($sql, $params);

            // Fetch the results as an associative array
            foreach ($records as $idmateria => $record) {
                $result[$idmateria] = $record->question_count;
            }
			
			// If surveyid is 2, fijamos la primera materia en 8 páginas 
            if ($surveyid == 2) {
                if (isset($result[0])) {
                    $result[0] = 7;
                } else {
                    $result[0] = 7;
                }
            }
        } catch (dml_exception $e) {
            // Handle SQL errors (e.g., log them)
            debugging('Database error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        return $result;
    }
	
	    /**
     * Calculate and return the percentage progress on the current materia.
     *
     * @param int $currentPage The current page number.
     * @param int $surveyid The survey ID.
     * @return float The progress percentage of the current materia.
     */
	public function getMateriaProgress($currentPage, $surveyid) {
		$materiaCounts = $this->getMateriaQuestionCount($surveyid);

		$cumulativeQuestions = 0;
		$currentMateriaQuestions = 0;

		foreach ($materiaCounts as $idmateria => $questionCount) {
			$cumulativeQuestions += $questionCount;

			if ($cumulativeQuestions >= $currentPage) {
				$currentMateriaQuestions = $questionCount;
				$questionsBeforeCurrentMateria = $cumulativeQuestions - $currentMateriaQuestions;
				$questionsOnCurrentPage = $currentPage - $questionsBeforeCurrentMateria;

				// Adjust numerator and denominator to exclude the "non-question" step
				$adjustedNumerator = max(0, $questionsOnCurrentPage - 1);
				$adjustedDenominator = max(1, $currentMateriaQuestions - 1);

				$progressPercentage = ($adjustedNumerator / $adjustedDenominator) * 100;

				return min(100, max(0, $progressPercentage));
			}
		}

		return 100; // If we've reached beyond all questions, return 100%
	}


    /**
     * Display the text "Page x of y".
     *
     * @return void
     */
    public function display_page_x_of_y() {
        global $OUTPUT, $DB;

        if ($this->userformpagecount > 1) {
            $a = new \stdClass();
            $a->formpage = $this->formpage;
            $a->userformpagecount = $this->userformpagecount;

            if ( ($this->userfirstpage > 1) || ($this->userlastpage < $this->userformpagecount) ) {
                $unaccesiblepagesnote = get_string('unaccesiblepages_note', 'mod_surveypro');
            } else {
                $unaccesiblepagesnote = '';
            }
			if (is_siteadmin()) {
            	echo $OUTPUT->heading(get_string('pagexofy', 'mod_surveypro', $a).' '.$unaccesiblepagesnote);
			}
        }
		
        // Obtener los ítems de la página actual.
        $canaccessreserveditems = has_capability('mod/surveypro:accessreserveditems', $this->context);
        [$where, $params] = surveypro_fetch_items_seeds(
            $this->surveypro->id, true, $canaccessreserveditems, null, null, $this->formpage
        );
		
		// Obtener todos los ítems de la página actual desde la base de datos.
        $itemseeds = $DB->get_records_select('surveypro_item', $where, $params, 'sortindex', 'id, parentid, parentvalue, plugin, type');

 		// Variable para almacenar el último idmateria encontrado.
        $last_idmateria = 0;

        foreach ($itemseeds as $item) {
			
			//debug
			//print_r ($item);
			
            // Obtener información adicional del ítem de la tabla específica del plugin.
            $plugin_table = "surveypro" . $item->type . "_" . $item->plugin;
			
			//debug
			//echo "plugin" . $item->plugin . "id" . $item->id;

            try {
                $plugin_data = $DB->get_record($plugin_table, ['itemid' => $item->id]);

                if ($plugin_data) {
                    // Almacenar el valor del campo idmateria del último ítem procesado.
                    $last_idmateria = $plugin_data->idmateria;
                }
            } catch (\dml_exception $e) {
                echo "Error al obtener información del ítem desde la tabla {$plugin_table}: " . $e->getMessage() . "\n";
            }
        }
		// Consultar el fullname de la materia correspondiente.
        $fullname = "Información de la Organización"; // Valor por defecto si el idmateria es 0.
        if ($last_idmateria !== 0) {
            try {
                $materia = $DB->get_record('surveypro_materias', ['id' => $last_idmateria], 'fullname');
                if ($materia) {
                    $fullname = $materia->fullname;
                }
            } catch (\dml_exception $e) {
                echo "Error al obtener el fullname de la materia con id {$last_idmateria}: " . $e->getMessage() . "\n";
            }
        }

        // Mostrar el fullname obtenido.
        echo "<h3>Materia: {$fullname}</h3>\n";
		
		$materiaCounts = $this->getMateriaQuestionCount($this->surveypro->id);
		//print_r($materiaCounts);
		$pagina = $this->formpage;
		$progress = $this->getMateriaProgress($pagina, $this->surveypro->id);
        echo "Progreso de la materia:<br>";
		
		        // Crear una barra de progreso en HTML puro
        echo '<div style="width: 100%; background-color: #f3f3f3; border: 1px solid #ccc; margin-top: 10px; border-radius: 10px; overflow: hidden;">
                <div style="width: ' . $progress . '%; background-color: #4caf50; height: 20px; text-align: center; color: white; line-height: 20px; border-radius: 10px 0 0 10px;">
                    ' . round($progress, 2) . '%
                </div>
              </div>
			  <br><br>';
		
		
    }

    /**
     * Warn this submission is a copy.
     *
     * @return void
     */
    public function warning_submission_copy() {
        global $OUTPUT;

        if ( (!empty($this->surveypro->history)) && (!empty($this->submissionid)) ) {
            echo $OUTPUT->notification(get_string('editingcopy', 'mod_surveypro'), 'notifysuccess');
        }
    }

    // MARK set.

    /**
     * Set submissionid.
     *
     * @param int $submissionid
     * @return void
     */
    public function set_submissionid($submissionid) {
        $this->submissionid = $submissionid;
    }

    /**
     * Set userformpagecount.
     *
     * @param int $userformpagecount
     * @return void
     */
    public function set_userformpagecount($userformpagecount) {
        $this->userformpagecount = $userformpagecount;
    }

    /**
     * Set formpage.
     *
     * @param int $formpage
     * @return void
     */
    public function set_formpage($formpage) {
        $this->formpage = $formpage;
    }

    /**
     * Set nextpage.
     *
     * @param int $nextpage
     * @return void
     */
    public function set_nextpage($nextpage) {
        $this->nextpage = $nextpage;
    }

    /**
     * Set user first page.
     *
     * @param int $userfirstpage
     * @return void
     */
    public function set_userfirstpage($userfirstpage) {
        $this->userfirstpage = $userfirstpage;
    }

    /**
     * Set user last page.
     *
     * @param int $userlastpage
     * @return void
     */
    public function set_userlastpage($userlastpage) {
        $this->userlastpage = $userlastpage;
    }

    /**
     * Set overflowpage.
     *
     * @param int $overflowpage
     * @return void
     */
    public function set_overflowpage($overflowpage) {
        $this->overflowpage = $overflowpage;
    }

    // MARK get.

    /**
     * Get submissionid.
     *
     * @return the content of $submissionid property
     */
    public function get_submissionid() {
        return $this->submissionid;
    }

    /**
     * Get submissionid.
     *
     * @return the content of $formpage property
     */
    public function get_formpage() {
        return $this->formpage;
    }

    /**
     * Get max assigned page.
     *
     * @return the content of $userformpagecount property
     */
    public function get_userformpagecount() {
        return $this->userformpagecount;
    }

    /**
     * Get next page.
     *
     * @return the content of $nextpage property
     */
    public function get_nextpage() {
        return $this->nextpage;
    }

    /**
     * Get user first page.
     *
     * @return the content of $userfirstpage property
     */
    public function get_userfirstpage() {
        return $this->userfirstpage;
    }

    /**
     * Get user last page.
     *
     * @return the content of $userlastpage property
     */
    public function get_userlastpage() {
        return $this->userlastpage;
    }

    /**
     * Get overflowpage.
     *
     * @return the content of $overflowpage property
     */
    public function get_overflowpage() {
        return $this->overflowpage;
    }
}
