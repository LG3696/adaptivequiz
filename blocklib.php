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
 * Back-end code for handling data about quizzes.
 *
 * There are classes for loading all the information about a quiz and attempts.
 *
 * @package    mod_adaptivequiz
 * @copyright  2017 Luca Gladiator <lucamarius.gladiator@stud.tu-darmstadt.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

/**
 * A class encapsulating a block and the questions it contains, and making the
 * information available to scripts like view.php.
 *
 * @copyright  2017 Luca Gladiator <lucamarius.gladiator@stud.tu-darmstadt.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */
class block {
    /** @var int the id of the block. */
    protected $id = 0;
    /** @var adaptivequiz the quiz, this block belongs to. */
    protected $quiz = null;
    /** @var string the name of the block. */
    protected $name = '';
    /**
     * @var array of {@link block_element}, that are contained in this block.
     * Do NOT use directly. Use {@link get_children()} instead. This is due to possible lazy loading of the children.
     */
    protected $children = null;
    /** @var block_condition the condition of this block. */
    protected $condition = null;

    // Constructor =============================================================
    /**
     * Constructor, assuming we already have the necessary data loaded.
     *
     * @param int $id the id of the block.
     * @param adaptivequiz $quiz the id of the quiz, this block belongs to.
     * @param string $name the name of the block.
     * @param array $children an array of block_element representing the parts of this block.
     */
    public function __construct($id, adaptivequiz $quiz, $name, $children) {
        $this->id = $id;
        $this->quiz = $quiz;
        $this->name = $name;
        $this->children = $children;
        $this->condition = null;
    }

    /**
     * Static function to get a block object from a block id.
     *
     * @param adaptivequiz $quiz the quiz, this block belongs to.
     * @param int $blockid the block id.
     * @return block the new block object.
     */
    public static function load(adaptivequiz $quiz, $blockid) {
        global $DB;

        $block = $DB->get_record('adaptivequiz_block', array('id' => $blockid), '*', MUST_EXIST);

        return new block($blockid, $quiz, $block->name, null);
    }

    /**
     * Static function to create a new block in the database.
     *
     * @param adaptivequiz $quiz the quiz this block belongs to.
     * @param string $name the name of the block.
     * @return block the new block object.
     */
    public static function create(adaptivequiz $quiz, $name) {
        global $DB;

        $block = new stdClass();
        $block->name = $name;
        $blockid = $DB->insert_record('adaptivequiz_block', $block);

        return new block($blockid, $quiz, $block->name, null);
    }

    /**
     * loads the children for the block.
     */
    protected function load_children() {
        global $DB;

        //If the children are already loaded we dont need to do anything.
        if ($this->children !== null) {
            return;
        }

        $children = $DB->get_records('adaptivequiz_qinstance', array('blockid' => $this->id), 'slot', 'id');

        $this->children = array_map(function($id) {
                                        return block_element::load($this->quiz, $id->id);
                                    },
                                    array_values($children));
    }

    /**
     * Adds a new question to the block.
     *
     * @param int $questionid the id of the question to be added.
     */
    public function add_question($questionid) {
        global $DB;

        $qinstance = new stdClass();
        $qinstance->blockid = $this->id;
        $qinstance->blockelement = $questionid;
        $qinstance->type = 0;
        $qinstance->grade = 0; //TODO: ???
        $qinstance->slot = count($this->get_children());

        $id = $DB->insert_record('adaptivequiz_qinstance', $qinstance);

        $this->load_children();
        array_push($this->children, block_element::load($this->quiz, $id));
    }

    /**
     * Adds a new subblock to the block.
     *
     * @param object $block the block to be added as a subblock.
     */
    public function add_subblock(block $block) {
        global $DB;

        $qinstance = new stdClass();
        $qinstance->blockid = $this->id;
        $qinstance->blockelement = $block->get_id();
        $qinstance->type = 1;
        $qinstance->grade = 0; //TODO: ???
        $qinstance->slot = count($this->get_children());

        $id = $DB->insert_record('adaptivequiz_qinstance', $qinstance);

        $this->load_children();
        array_push($this->children, block_element::load($this->quiz, $id));
    }

    /**
     * Checks whether this is the main block of the quiz.
     *
     * @return bool true if this is the main block of the quiz.
     */
    public function is_main_block() {
        return $this->id == $this->quiz->get_main_block()->get_id();
    }

    /**
     * Checks whether the block or subblock has any questions.
     *
     * @return bool true if there are questions in this block.
     */
    public function has_questions() {
        foreach ($this->get_children() as $element) {
            if ($element->is_question()) {
                return true;
            }
            else if ($element->is_block() && $element->get_element()->has_questions()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns all block_elements that can be used for a condition on this block.
     *
     * @return array the block_elements that can be used for a condition.
     */
    public function get_condition_candidates() {
        $parent = $this->quiz->get_main_block()->search_parent($this->id);
        $candidates = array();
        foreach ($parent->get_children() as $element) {
            if ($element->is_block() && $element->get_element()->get_id() == $this->id) {
                break;
            }
            array_push($candidates, $element);
        }
        return $candidates;
    }

    /**
     * Removes the child with the give adaptivequiz_qinstance id.
     *
     * @param int $id the id of the child to remove.
     */
    public function remove_child($id) {
        global $DB;

        $DB->delete_records('adaptivequiz_qinstance', array('id' => $id));

        // necessary because now the loaded children information is outdated.
        $this->children = null;
    }

    /**
     * Returns the children block.
     *
     * @return array an array of {@link block_element}, which represents the children of this block.
     */
    public function get_children() {
        $this->load_children();
        return $this->children;
    }

    /**
     * Returns the name of the block.
     *
     * @return string the name of this block.
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Sets the name of the block.
     * @param string $name new name of the block.
     */
    public function set_name($name) {
        global $DB;

        $this->name = $name;

        $record = new stdClass();
        $record->id = $this->id;
        $record->name = $name;
        $DB->update_record('adaptivequiz_block', $record);
    }

    /**
     * Returns the id of the block.
     *
     * @return int the id of this block.
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Returns the quiz of the block.
     *
     * @return adaptivequiz the quiz, this block belongs to.
     */
    public function get_quiz() {
        return $this->quiz;
    }

    /**
     * Returns the id of the parent block or false, if this block has no parent block.
     *
     * @return bool|int the the id of the parent block or false.
     */
    public function get_parentid() {
        //if this is the main block, there is no parent block.
        if ($this->is_main_block()) {
            return false;
        }
        else {
            //top down search in the block-tree to find the parent.
            return $this->quiz->get_main_block()->search_parent($this->id)->get_id();
        }
        return false;
    }

    /**
     * Gets the condition under which this block should be shown to a student.
     *
     * @return block_condition the condition under which to show this block.
     */
    public function get_condition() {
        if (!$this->condition) {
            $this->condition = block_condition::load($this);
        }
        return $this->condition;
    }

    /**
     * Finds the parent of a block.
     * @param int $childid the id of the child to find the parent for.
     *
     * @return bool|block the parent block or fals, if the parent can not be found.
     */
    protected function search_parent($childid) {
        foreach ($this->get_children() as $element) {
            if ($element->is_block()) {
                $block = $element->get_element();
                if ($block->get_id() == $childid) {
                    return $this;
                }
                if ($parent = $block->search_parent($childid)) {
                    return $parent;
                }
            }
        }
        return false;
    }

    /**
     * Adds the questions of this block to a question usage.
     *
     * @param question_usage_by_activity $quba the question usage to add the questions to.
     */
    public function add_questions_to_quba(question_usage_by_activity $quba) {
        foreach ($this->get_children() as $element) {
            $element->add_questions_to_quba($quba);
        }
    }
}


/**
 * A class encapsulating a block element, which is either a question or another block.
 *
 * @copyright  2017 Luca Gladiator <lucamarius.gladiator@stud.tu-darmstadt.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */
class block_element {
    /** @var int the id of the block_element. */
    protected $id = 0;
    /** @var adaptivequiz the quiz, this element belongs to. */
    protected $quiz = null;
    /** @var int the type of the block_element: 0 = question, 1 = block. */
    protected $type = 0;
    /** @var int the id of the element referenced. */
    protected $elementid = 0;
    /** @var object the {@link block} or question, this element refers to. */
    protected $element = null;

    // Constructor =============================================================
    /**
     * Constructor, assuming we already have the necessary data loaded.
     *
     * @param int $id the id of the block_elem.
     * @param adaptivequiz $quiz the quiz this reference belongs to.
     * @param int $type the type of this block_element.
     * @param object $element the element referenced by this block.
     */
    public function __construct($id, adaptivequiz $quiz, $type, $elementid, $element) {
        $this->id = $id;
        $this->quiz = $quiz;
        $this->type = $type;
        $this->elementid = $elementid;
        $this->element = $element;
    }

    /**
     * Static function to get a block_element object from a its id.
     *
     * @param adaptivequiz $quiz the quiz this reference belongs to.
     * @param int $blockelementid the blockelement id.
     * @return block the new block object.
     */
    public static function load(adaptivequiz $quiz, $blockelementid) {
        global $DB;

        $questioninstance = $DB->get_record('adaptivequiz_qinstance', array('id' => $blockelementid), '*', MUST_EXIST);

        $element = null;
        if ($questioninstance->type == 0) {
            $element = $DB->get_record('question', array('id' => $questioninstance->blockelement), '*', MUST_EXIST);
        }
        else if ($questioninstance->type == 1) {
            $element = block::load($quiz, $questioninstance->blockelement);
        }
        else {
            return null;
        }
        return new block_element($blockelementid, $quiz, (int)$questioninstance->type, (int)$questioninstance->blockelement, $element);
    }

    /**
     * Return whether this element is a question.
     *
     * @return bool whether this element is a question.
     */
    public function is_question() {
        return $this->type === 0;
    }

    /**
     * Return whether this element is a block.
     *
     * @return bool whether this element is a block.
     */
    public function is_block() {
        return $this->type === 1;
    }

    /**
     * Returns the name of the element.
     *
     * @return string The name of the element.
     */
    public function get_name() {
        if ($this->is_question()) {
            return $this->element->name;
        }
        if ($this->is_block()) {
            return $this->element->get_name();
        }
    }


    /**
     * Return the element.
     *
     * @return object the element.
     */
    public function get_element() {
        return $this->element;
    }

    /**
     * Checks whether the element can be edited.
     *
     * @return bool True if it may be edited, false otherwise.
     */
    public function may_edit() {
        if ($this->is_question()) {
            $question = $this->element;
            return !empty($question->id) &&
            (question_has_capability_on($question, 'edit', $question->category) ||
                question_has_capability_on($question, 'move', $question->category));
        }
        if ($this->is_block()) {
            return true;
        }
    }

    /**
     * Checks whether the element can be viewed.
     *
     * @return bool True if it may be viewed, false otherwise.
     */
    public function may_view() {
        if ($this->is_question()) {
            $question = $this->element;
            return !empty($question->id) &&
            question_has_capability_on($question, 'view', $question->category);
        }
        if ($this->is_block()) {
            return true;
        }
    }

    /**
     * Get a URL for the edit page of this element.
     *
     * @param array $params paramters to use for the url.
     *
     * @return \moodle_url the edit URL of the element.
     */
    public function get_edit_url(array $params) {
        global $CFG;

        if ($this->is_question()) {
            $questionparams = array_merge($params, array('id' => $this->element->id));
            return new moodle_url("$CFG->wwwroot/question/question.php", $questionparams);
        }
        if ($this->is_block()) {
            $blockparams = array_merge($params, array('bid' => $this->element->get_id()));
            unset($blockparams['returnurl']);
            return new moodle_url('edit.php', $blockparams);
        }
    }

    /**
     * Returns the id of the qinstance database row.
     *
     * @return int the row id.
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Adds the question(s) of this element to a question usage.
     *
     * @param question_usage_by_activity $quba the question usage to add the questions to.
     */
    public function add_questions_to_quba(question_usage_by_activity $quba) {
        if ($this->is_question()) {
            $question = question_bank::load_question($this->get_element()->id, false);
            $quba->add_question($question);
        }
        else if ($this->is_block()) {
            $this->get_element()->add_questions_to_quba($quba);
        }
    }
}

/**
 * A class encapsulating the condition, under which a block should be shown to a student.
 *
 * @copyright  2017 Luca Gladiator <lucamarius.gladiator@stud.tu-darmstadt.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */
class block_condition {
    /** @var block the block this condition is for. */
    var $block = null;
    /** @var array the parts this condition is made from. */
    var $parts = null;
    /** @var bool whether the parts are connected with and. Otherwise they are connected with or. */
    var $use_and = true;

    // Constructor =============================================================
    /**
     * Constructor, assuming we already have the necessary data loaded.
     *
     * @param int $block the block this condition is for.
     * @param array $parts the parts this condition is made from.
     * @param bool $use_and whether the parts are connected with and. Otherwise they are connected with or.
     */
    public function __construct(block $block, $parts, $use_and) {
        $this->block = $block;
        $this->parts = $parts;
        $this->use_and = $use_and;
    }

    /**
     * Loads the condition for one block from the database.
     *
     * @param int $block the block to get the condition for.
     *
     * @rteturn block_condition the loaded condition.
     */
    public static function load(block $block) {
        global $DB;

        $blockid = $block->get_id();

        $use_and = $DB->get_field('adaptivequiz_block', 'use_and', array('id' => $blockid), MUST_EXIST);
        $parts = $DB->get_records('adaptivequiz_block_condition', array('block' => $blockid));
        $partobjs = array_map(function($part) {
            return new block_condition_part($part->id, $part->type, $part->on_qinstance, $part->grade);
                },
                array_values($parts));

        return new block_condition($block, $partobjs, $use_and);
    }

    /**
     * Adds a part to this condition.
     *
     * @param int $type the type of this condition.
     * @param int $elementid the id of the element this condition references.
     * @param int $grade the grade this condition is relative to.
     */
    public function add_part($type, $elementid, $grade) {
        $part = block_condition_part::create($this->block, $type, $elementid, $grade);
        array_push($this->parts, $part);
    }

    /**
     * Checks whether this condition is met for a certain attempt.
     *
     * @param object $attempt the attempt to check this part of the condition for.
     *
     * @return bool whether this condition is fullfilled.
     */
    public function is_fullfilled($attempt) {
        if ($this->use_and) {
            foreach ($this->parts as $part) {
                if (!$part->is_fullfilled($attempt)) {
                    return false;
                }
            }
            return true;
        }
        else {
            foreach ($this->parts as $part) {
                if ($part->is_fullfilled($attempt)) {
                    return true;
                }
            }
            return false;
        }
    }


    /**
     * Sets how the parts of this condition are connected.
     *
     * @param bool whether the parts of this condition should be connected with and. Or is used otherwise.
     */
    public function set_use_and($use_and) {
        if ($this->use_and != $use_and) {
            global $DB;

            $this->use_and = $use_and;

            $record = new stdClass();
            $record->id = $this->block->get_id();
            $record->use_and = $this->use_and;
            $DB->update_record('adaptivequiz_block', $record);
        }
    }

    /**
     * Returns whether the parts are connected with and.
     *
     * @return bool true if the parts are connected with and, false for a connection with or.
     */
    public function get_use_and() {
        return $this->use_and;
    }

    /**
     * Returns the parts of this condition.
     *
     * @return array the parts of this condition.
     */
    public function get_parts() {
        return $this->parts;
    }

    /**
     * Updates the condition using the submitted array.
     *
     * @param array $conditionparts the array from the form.
     */
    public function update($conditionparts) {
        foreach ($this->parts as $existingpart) {
            $deleted = true;
            foreach ($conditionparts as $part) {
                if (array_key_exists('id', $part) && $part['id'] == $existingpart->get_id()) {
                    $deleted = false;
                    break;
                }
            }
            if ($deleted) {
                global $DB;
                $DB->delete_records('adaptivequiz_block_condition', array('id' => $existingpart->get_id()));
            }
        }
        foreach ($conditionparts as $part) {
            //update existing condition parts
            if (array_key_exists('id', $part)) {
                foreach ($this->parts as $existingpart) {
                    if ($existingpart->get_id() == $part['id']) {
                        $existingpart->update($part['type'], $part['question'], $part['points']);
                        break;
                    }
                }
            }
            //insert new condition parts
            else {
                $this->add_part($part['type'], $part['question'], $part['points']);
            }
        }
    }
}

/**
 * A class encapsulating one part of a condition, under which a block should be shown to a student.
 *
 * @copyright  2017 Luca Gladiator <lucamarius.gladiator@stud.tu-darmstadt.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */
class block_condition_part {
    // block_condition type
    const WAS_DISPLAYED     = 0;
    const LESS              = 1;
    const LESS_OR_EQUAL     = 2;
    const GREATER           = 3;
    const GREATER_OR_EQUAL  = 4;
    const EQUAL             = 5;
    const NOT_EQUAL         = 6;

    /** @var int the id of the block_condition. */
    protected $id = 0;
    /**
     * @var int the type of the block_condition. One of WAS_DISPLAYED, LESS, LESS_OR_EQUAL,
     * GREATER, GREATER_OR_EQUAL or EQUAL.
     */
    protected $type = 0;
    /** @var int the id of the element this condition references. */
    protected $elementid = 0;
    /** @var int the grade this condition is relative to. */
    protected $grade = 0;

    // Constructor =============================================================
    /**
     * Constructor, assuming we already have the necessary data loaded.
     *
     * @param int $id the id of the block_elem.
     * @param int $type the type of this condition.
     * @param int $elementid the id of the element this condition references.
     * @param int $grade the grade this condition is relative to.
     */
    public function __construct($id, $type, $elementid, $grade) {
        $this->id = $id;
        $this->type = $type;
        $this->elementid = $elementid;
        $this->grade = $grade;
    }

    /**
     * Inserts a new condition part into the database.
     *
     * @param block $block the block to create this condition part for.
     * @param int $type the type of this condition.
     * @param int $elementid the id of the element this condition references.
     * @param int $grade the grade this condition is relative to.
     *
     * @return block_condition_part the newly created condtion part.
     */
    public static function create(\block $block, $type, $elementid, $grade) {
        global $DB;

        $record = new stdClass();
        $record->block = $block->get_id();
        $record->on_qinstance = $elementid;
        $record->type = $type;
        $record->grade = $grade;

        $id = $DB->insert_record('adaptivequiz_block_condition', $record);

        return new block_condition_part($id, $type, $elementid, $grade);
    }

    /**
     * Checks whether this part of the condition is met for a certain attempt.
     *
     * @param object $attempt the attempt to check this part of the condition for.
     *
     * @return bool whether this part of the condition is fullfilled.
     */
    public function is_fullfilled($attempt) {
        //TODO
        return true;
    }

    /**
     * Gets the id of this condition part.
     *
     * @return int the id of this condition part.
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Gets the type of this condition part.
     *
     * @return int the type of this condition part.
     */
    public function get_type() {
        return $this->type;
    }

    /**
     * Gets the grade of this condition part.
     *
     * @return int the grade of this condition part.
     */
    public function get_grade() {
        return $this->grade;
    }

    /**
     * Gets the element id this condition part is about.
     *
     * @return int the element id.
     */
    public function get_elementid() {
        return $this->elementid;
    }

    /**
     * Updates the part to the new values.
     *
     * @param int $type the new type.
     * @param int $elementid the id of the new element to refer to.
     * @param int $grade the new grade to use for this part.
     */
    public function update($type, $elementid, $grade) {
        if ($this->type != $type || $this->elementid != $elementid || $this->grade != $grade) {
            global $DB;

            $this->type = $type;
            $this->elementid = $elementid;
            $this->grade = $grade;

            $record = new stdClass();
            $record->id = $this->id;
            $record->type = $this->type;
            $record->elementid = $this->elementid;
            $record->grade = $this->grade;

            $DB->update_record('adaptivequiz_block_condition', $record);
        }
    }
}
