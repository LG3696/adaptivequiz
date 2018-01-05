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

require_once($CFG->libdir . '/questionlib.php');

/**
 * This class extends the quiz class to hold data about the state of a particular attempt,
 * in addition to the data about the quiz.
 *
 * @copyright  2017 Jan Emrich
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
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

	/** @var int the id of this adaptivequiz_attempt. */
	protected $id;

	/** @var question_usage_by_activity the question usage for this quiz attempt. */
	protected $quba;

	/** @var int the quiz this attempt belongs to. */
	protected $quiz;

	/** @var int the user this attempt belongs to. */
	protected $userid;

	/** @var int the number of this attempt */
 	protected $attempt_number;

	/** @var float the sum of the grades. */
	protected $sumgrades;

// 	/** @var int time of starting this attempt. */
// 	protected $timestart;

// 	/** @var int time of finishing this attempt. */
// 	protected $timefinish;

// 	/** @var int time of last modification of this attempt. */
// 	protected $timemodified;


	// Constructor =============================================================
	/**
	 * Constructor assuming we already have the necessary data loaded.
	 * @param int $id the id of this attempt.
	 * @param question_usage_by_activity $quba the question_usages_by_activity id this attempt belongs to.
	 * @param adaptivequiz $quiz the quiz this attempt belongs to.
	 * @param int $userid the id of the user this attempt belongs to.
	 * @param int $attemptnumber the number of this attempt.
	 */
	public function __construct($id, question_usage_by_activity $quba, adaptivequiz $quiz, $userid, $attemptnumber) {
		$this->id = $id;
		$this->quba = $quba;
		$this->quiz = $quiz;

		$this->userid = $userid;
		$this->attempt_number = $attemptnumber;
	}


	/**
	 * Static function to get a attempt object from a attempt id.
	 *
	 * @param int $attemptid the id of this attempt.
	 * @return attempt the new attempt object.
	 */
	public static function load($attemptid) {
		global $DB;

		$attemptrow = $DB->get_record('adaptivequiz_attempts', array('id' => $attemptid), '*', MUST_EXIST);
		$quba = question_engine::load_questions_usage_by_activity($attemptrow->quba);
		$quiz = adaptivequiz::load($attemptrow->quiz);
		
		return new attempt($attemptid, $quba, $quiz, $attemptrow->userid, $attemptrow->attempt);
	}

	/**
	 * Static function to create a new attempt in the database.
	 * @param adaptivequiz $quiz the quiz this attempt belongs to.
	 * @param int $userid the id of the user this attempt belongs to.
	 * @return attempt the new attempt object.
	 */
	public static function create(adaptivequiz $quiz, $userid) {
		global $DB;

		$quba = attempt::create_quba($quiz);
		
		$attemptrow = new stdClass();
		$attemptrow->quba = $quba->get_id();
		$attemptrow->quiz = $quiz->get_id();
		$attemptrow->userid = $userid;
		$attemptrow->attempt = $DB->count_records('adaptivequiz_attempts', array('quiz' => $quiz->get_id(), 'userid' => $userid)) + 1;

		$attemptid = $DB->insert_record('adaptivequiz_attempts', $attemptrow);

		$attempt = new attempt($attemptid, $quba, $quiz, $userid, $attemptrow->attempt);
		return $attempt;
	}

	// getters

	/** @return int the id of this attempt. */
	public function get_attemptid() {
		return $this->id;
	}

	/** @return question_usage_by_activity the quba of this attempt. */
	public function get_quba() {
		return $this->quba;
	}

	/** @return adaptivequiz the quiz this attempt belongs to. */
	public function get_quiz() {
		return $this->quiz;
	}

	/** @return int the id of the user. */
	public function get_userid() {
		return $this->userid;
	}
	
	/** @return int the number of this attempt. */
	public function get_attempt_number() {
		return $this->attempt_number;
	}

	/**
	 * Processes the slot.
	 * 
	 * @param int $timenow the current time.
	 * @param question_usage_by_activity $quba the question usage.
	 */
	public function process_slot($timenow) {
	    global $DB;
	    
	    $transaction = $DB->start_delegated_transaction();
	    
	    $quba = $this->get_quba();
	    
	    $quba->process_all_actions($timenow);
	    question_engine::save_questions_usage_by_activity($quba);
	    
	    $transaction->allow_commit();
	}

	/**
	 * Process responses during an attempt at a quiz and finish the attempt.
	 *
	 * @param  int $timenow the current time.
	 */
	public function finish_attempt($timenow) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        
        $quba = $this->get_quba();
        $quba->finish_all_questions($timenow);
        
        question_engine::save_questions_usage_by_activity($quba);
        
        $attempt = new stdClass();
        $attempt->id = $this->get_attemptid();
        $attempt->quba = $this->get_quba()->get_id();
        $attempt->quiz = $this->get_quiz()->get_id();
        $attempt->userid = $this->get_userid();
        $attempt->attempt = $this->get_attempt_number();
        $attempt->sumgrades = $this->quba->get_total_mark();
        $DB->update_record('adaptivequiz_attempts', $attempt);

        // TODO in later userstory
        //quiz_save_best_grade($this->get_quiz(), $this->attempt->userid);

        $transaction->allow_commit();
	}
	
	/**
	 * Checks if this is the last slot.
	 * 
	 * @param int $slot the slot
	 * @return boolean wether this is the last slot.
	 */
	public function is_last_slot($slot) {
		//TODO Blöcke beachten
		$quba = $this->get_quba();
		return $slot == $quba->question_count();
	}
	
	/**
	 * Determines the next slot based on the conditions of the blocks.
	 * 
	 * @param int $currentslot the current slot
	 * @return number the next slot
	 */
	public function next_slot($currentslot) {
		//TODO Blöcke beachten
		return $currentslot + 1;
	}
	
	// URL

	/**
	 * Generates the URL to view this attempt.
	 *
	 * @return moodle_url the URL of that attempt.
	 */
	public function attempt_url() {
		return new moodle_url('/mod/adaptivequiz/attempt.php', array('attempt' => $this->id));
	}

	/**
	 * Creates a new question usage for this attempt.
	 *
	 * @param adaptivequiz $quiz the quiz to create the usage for.
	 *
	 * @return question_usage_by_activity the created question usage.
	 */
	protected static function create_quba(adaptivequiz $quiz) {
	    $quba = question_engine::make_questions_usage_by_activity('mod_adaptivequiz', $quiz->get_context());
	    $quba->set_preferred_behaviour('deferredfeedback');
	    $quiz->add_questions_to_quba($quba);
	    $quba->start_all_questions();
	    question_engine::save_questions_usage_by_activity($quba);
	    return $quba;
	}
}