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
 * Internal library of functions for module adaptivequiz.
 *
 * All the adaptivequiz specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_adaptivequiz
 * @copyright  2017 Luca Gladiator <lucamarius.gladiator@stud.tu-darmstadt.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/adaptivequiz/blocklib.php');
require_once($CFG->dirroot . '/mod/adaptivequiz/conditionlib.php');
require_once($CFG->dirroot . '/mod/adaptivequiz/feedbacklib.php');
require_once($CFG->dirroot . '/mod/adaptivequiz/attemptlib.php');

/**
 * A class encapsulating a adaptive quiz.
 *
 * @copyright  2017 Jan Emrich <jan.emrich@stud.tu-darmstadt.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */
class adaptivequiz {
    /** @var int the id of this adaptive quiz. */
    protected $id = 0;
    /** @var int the course module id for this quiz. */
    protected $cmid = 0;
    /** @var block the main block of this quiz. */
    protected $mainblock = null;
    /** @var int the id of the main block of this adaptive quiz. */
    protected $mainblockid = 0;

    // Constructor =============================================================
    /**
     * Constructor assuming we already have the necessary data loaded.
     * @param int $id the id of this quiz.
     * @param int $cmid the course module id for this quiz.
     * @param int $mainblockid the id of the main block of this adaptive quiz.
     */
    public function __construct($id, $cmid, $mainblockid) {
        $this->id = $id;
        $this->cmid = $cmid;
        $this->mainblock = null;
        $this->mainblockid = $mainblockid;
    }

    /**
     * Static function to get a quiz object from a quiz id.
     *
     * @param int $quizid the id of this adaptive quiz.
     * @return adaptivequiz the new adaptivequiz object.
     */
    public static function load($quizid) {
        global $DB;

        $quiz = $DB->get_record('adaptivequiz', array('id' => $quizid), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('adaptivequiz', $quizid, $quiz->course, false, MUST_EXIST);

        return new adaptivequiz($quizid, $cm->id, $quiz->mainblock);
    }

    /**
     * Get the main block of the quiz.
     *
     * @return block the main block of the quiz.
     */
    public function get_main_block() {
        if (!$this->mainblock) {
            $this->mainblock = block::load($this, $this->mainblockid);
            $this->enumerate();
        }
        return $this->mainblock;
    }

    /**
     * Gets the id of this quiz.
     *
     * @return int the id of this quiz.
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Gets the course module id of this quiz.
     *
     * @return int the course module id of this quiz.
     */
    public function get_cmid() {
        return $this->cmid;
    }

    /**
     * Get the context of this module.
     *
     * @return context_module the context for this module.
     */
    public function get_context() {
        return context_module::instance($this->cmid);
    }

    /**
     * Returns the number of slots in this quiz.
     *
     * @return int the number of slots used by this quiz.
     */
    public function get_slotcount() {
        $this->enumerate();
        return $this->get_main_block()->get_slotcount();
    }

    /**
     * Returns the next slot that a student should work on for a certain attempt.
     *
     * @param attempt $attempt the attempt that  the student is currently working on.
     * @return null|int the number of the next slot that the student should work on or null, if no such slot exists.
     */
    public function next_slot(attempt $attempt) {
        $this->enumerate();
        return $this->get_main_block()->next_slot($attempt);
    }

    /**
     * Enumerates the questions of this quiz.
     */
    protected function enumerate() {
        $this->get_main_block()->enumerate(1);
    }

    /**
     * Returns the slot number for an element id.
     *
     * @param int $elementid the id of the element.
     * @return null|int the slot number of the element or null, if the element can not be found.
     */
    public function get_slot_for_element($elementid) {
        $this->enumerate();
        return $this->get_main_block()->get_slot_for_element($elementid);
    }

    /**
     * Adds the questions of this quiz to a question usage.
     *
     * @param question_usage_by_activity $quba the question usage to add the questions to.
     */
    public function add_questions_to_quba(question_usage_by_activity $quba) {
        $this->get_main_block()->add_questions_to_quba($quba);
    }
    
    /**
     * Returns the number of attainable marks.
     * 
     * @return int the attainable marks.
     */
    public function get_max_marks(question_usage_by_activity $quba) {
        $maxmarks = 0;
        foreach ($this->mainblock->get_children() as $child) {
            if ($child->is_question()) {
                $mark = $quba->get_question_max_mark($this->get_slot_for_element($child->get_id()));
                $maxmarks += $mark;
            }
        }
        return $maxmarks;
    }
}