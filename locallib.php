<?php
use mod_quiz\plugininfo\quiz;

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
 * Internal library of functions for module adaptivequiz
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

 /**
  * Get the main block of the quiz.
  *
  * @param int $quizid the id of the quiz.
  * @return block the main block of the quiz.
  */
 function get_main_block($quizid) {
     global $DB;

     $quiz = $DB->get_record('adaptivequiz', array('id' => $quizid), '*', MUST_EXIST);
     return block::load($quizid, $quiz->mainblock);
 }

 /**
  * A class encapsulating a adaptive quiz.
  *
  * @copyright  2017 Jan Emrich <jan.emrich@stud.tu-darmstadt.de>
  * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
  * @since      Moodle 3.1
  */
 class adaptivequiz {

 	/** @var int the id of this adaptive quiz. */
 	protected $id;
 	/** @var int the course id for this quiz. */
 	protected $cmid;
 	/** @var int the id of the main block of this adaptive quiz. */
 	protected $mainblock;

 	// Constructor =============================================================
 	/**
 	 * Constructor assuming we already have the necessary data loaded.
 	 * @param int $id the id of this quiz.
 	 * @param int the course id for this quiz.
 	 * @param int the id of the main block of this adaptive quiz.
 	 */
 	public function __construct($id, $courseid, $mainblock) {

 		$this->id = $id;
 		$this->cmid = $courseid;
 		$this->mainblock = $mainblock;
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

 		return new adaptivequiz($quiz->id, $quiz->course, $quiz->mainblock);
 	}

 	public function get_id() {
 	    return $this->id;
 	}

 	public function get_context() {
 		//TODO:
 	}

 	public function add_questions_to_quba($quba) {
 		//TODO
 	}

 }