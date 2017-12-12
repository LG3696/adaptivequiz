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
 * Back-end code for handling data about quizzes and the current user's attempt.
 *
 * There are classes for loading all the information about a quiz and attempts,
 * and for displaying the navigation panel.
 *
 * @package   mod_adaptivequiz
 * @copyright 2017 Jan Emrich <jan.emrich@stud.tu-darmstadt.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

/**
 * This class extends the quiz class to hold data about the state of a particular attempt,
 * in addition to the data about the quiz.
 *
 * @copyright  2017 Jan Emrich
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      ??
 */
class attempt {

	
	/** @var string to identify the in progress state. */
	//const IN_PROGRESS = 'inprogress';
	/** @var string to identify the overdue state. */
	//const OVERDUE     = 'overdue';
	/** @var string to identify the finished state. */
	//const FINISHED    = 'finished';
	/** @var string to identify the abandoned state. */
	//const ABANDONED   = 'abandoned';
	

// 	/** @var quiz object containing the quiz settings. */
// 	protected $quizobj;

	/** @var int the id of this adaptivequiz_attempt. */
	protected $id;

	/** @var int question_usage_by_activity the question usage for this quiz attempt. */
	protected $quba;
	
	/** @var int the quiz for this attempt and user. */
	protected $quiz;
	
	/** @var int the user this attempt belongs to. */
	protected $userid;
	
	/** @var int attempt */
// 	protected $attempt;
	
// 	/** @var float the sum of the grades. */
// 	protected $sumgrades;
	
// 	/** @var int time of starting this attempt. */
// 	protected $timestart;
	
// 	/** @var int time of finishing this attempt. */
// 	protected $timefinish;
	
// 	/** @var int time of last modification of this attempt. */
// 	protected $timemodified;
	
	
	// Constructor =============================================================
	/**
	 * Constructor assuming we already have the necessary data loaded.
	 * 
	 * 
	 */
	public function __construct($id, $qubaid, $quizid, $userid, $attempt) {
		$this->id = $id;
		$this->quba = $qubaid;
		$this->quiz = $quizid;
		$this->userid = $userid;
		$this->attempt = $attempt;
	}
	
	
	/**
	 * Static function to get a attempt object from a attempt id.
	 * 
	 * @param int $attemptid the id of this attempt.
	 * @return attempt the new attempt object.
	 */
	public static function load($attemptid) {
		global $DB;
		
		$attempt = $DB->get_record('adaptivequiz_attempts', array('id' => $attemptid), '*', MUST_EXIST);
		
		return new attempt($attemptid, $attempt->quba, $attempt->quizid, $attempt->userid, $attempt->attempt);
	}
	
	/**
	 * Static function to create a new attempt in the database.
	 * @param int $qubaid the question_usages_by_activity id this attempt belongs to.
	 * @param int $quizid the id of the quiz this attempt belongs to.
	 * @param int $userid the id of the user this attempt belongs to.
	 * @param int $attempt the number of this attempt.
	 * @return attempt the new attempt object.
	 */
	public static function create($qubaid, $quizid, $userid, $attempt) {
		global $DB;
	
		$attempt = new stdClass();
		$attempt->uniqueid = $qubaid;
		$attempt->quiz = $quizid;
		$attempt->userid = $userid;
		$attempt->attempt = $attempt;
		$attemptid = $DB->insert_record('adaptivequiz_attempts', $attempt);
	
		return new attempt($attemptid, $qubaid, $quizid, $userid, $attempt);
	}
	
	// getters
	
	
	// URL
	
	/**
	 * @param int $attemptid the id of an attempt.
	 * @param int $page optional page number to go to in the attempt.
	 * @return string the URL of that attempt.
	 */
	public function attempt_url($attemptid, $page = 0) {
		global $CFG;
		$url = $CFG->wwwroot . '/mod/adaptivequiz/attempt.php?attempt=' . $attemptid;
		if ($page) {
			$url .= '&page=' . $page;
		}
		return $url;
	}
	
	
}