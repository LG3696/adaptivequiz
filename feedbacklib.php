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
 * Back-end code for handling data about specialized feedback.
 *
 * @package    mod_adaptivequiz
 * @copyright  2017 Luca Gladiator <lucamarius.gladiator@stud.tu-darmstadt.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();



/**
 * A class encapsulating the specialized feedback of an adaptivequiz.
 *
 * @copyright  2017 Luca Gladiator <lucamarius.gladiator@stud.tu-darmstadt.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */
class feedback {
    /**
     * Constructor, assuming we already have the necessary data loaded.
     */
    public function __construct() {
        //TODO
    }

    /**
     * Gets the specialized feedback for an adaptivequiz.
     *
     * @param adaptivequiz $quiz the adaptivequiz to get the feedback for.
     *
     * @return feedback the feedback for this quiz.
     */
    public static function get_feedback(adaptivequiz $quiz) {
        //TODO
        return new feedback();
    }

    /**
     * Checks whether specialized feedback exist for a block element.
     *
     * @param block_element $blockelement the block element to check.
     *
     * @return bool true if specialized feedback for the block element exists.
     */
    public function has_specialized_feedback(block_element $blockelement) {
        //TODO
        return false;
    }
}

/**
 * A class encapsulating a specialized feedback block.
 *
 * @copyright  2017 Luca Gladiator <lucamarius.gladiator@stud.tu-darmstadt.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */
class feedback_block {
    /** @var int the id of the feedback block. */
    protected $id = 0;
    /** @var adaptivequiz the quiz, this block belongs to. */
    protected $quiz = null;

    /**
     * Constructor, assuming we already have the necessary data loaded.
     *
     * @param int $id the id of the feedback block.
     * @param adaptivequiz $quiz the id of the quiz, this block belongs to.
     */
    public function __construct($id, $quiz) {
        $this->id = $id;
        $this->quiz = $quiz;
    }

    /**
     * Static function to get a feedback block object from an id.
     *
     * @param int $blockid the feedback block id.
     * @param adaptivequiz $quiz the id of the quiz, this block belongs to.
     * @return feedback_block the new feedback block object.
     */
    public static function load($blockid, adaptivequiz $quiz) {
        return new feedback_block($blockid, $quiz);
    }

    /**
     * Creates a new feedback block in the database.
     *
     * @param adaptivequiz $quiz the quiz this feedbackblock belongs to.
     * @return feedback_block the created feedback block.
     */
    public static function create(adaptivequiz $quiz) {
        global $DB;
        // TODO
    }

    /**
     * Returns the id of the feedbackblock.
     *
     * @return int the id of the feedbackblock.
     */
    public function get_id() {
        return $this->id;
    }
}