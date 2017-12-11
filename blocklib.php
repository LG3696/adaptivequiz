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
    /** @var int the id of the quiz, this block belongs to. */
    protected $quizid;
    /** @var string the name of the block. */
    protected $name = '';
    /** @var array of {@link block_element}, that are contained in this block. */
    protected $children = null;

    // Constructor =============================================================
    /**
     * Constructor, assuming we already have the necessary data loaded.
     *
     * @param int $id the id of the block.
     * @param int $quizid the id of the quiz, this block belongs to.
     * @param string $name the name of the block.
     * @param array $children an array of block_element representing the parts of this block.
     */
    public function __construct($id, $quizid, $name, $children) {
        $this->id = $id;
        $this->quizid = $quizid;
        $this->name = $name;
        $this->children = $children;
    }

    /**
     * Static function to get a block object from a block id.
     *
     * @param int $quizid the id of the quiz, this block belongs to.
     * @param int $blockid the block id.
     * @return block the new block object.
     */
    public static function load($quizid, $blockid) {
        global $DB;

        $block = $DB->get_record('adaptivequiz_block', array('id' => $blockid), '*', MUST_EXIST);

        return new block($blockid, $quizid, $block->name, null);
    }

    /**
     * Static function to create a new block in the database.
     *
     * @param int $quizid the quiz this block belongs to.
     * @param string $name the name of the block.
     * @return block the new block object.
     */
    public static function create($quizid, $name) {
        global $DB;

        $block = new stdClass();
        $block->name = $name;
        $blockid = $DB->insert_record('adaptivequiz_block', $block);

        return new block($blockid, $quizid, $block->name, null);
    }

    /**
     * loads the children for the block.
     */
    private function load_children() {
        global $DB;

        //If the children are already loaded we dont need to do anything.
        if ($this->children !== null) {
            return;
        }

        $children = $DB->get_records('adaptivequiz_qinstance', array('blockid' => $this->id), 'slot', 'id');

        $qid = $this->quizid;
        $this->children = array_map(function($id) {
                                        return block_element::load($qid, $id->id);
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

        $this->load_children();

        $qinstance = new stdClass();
        $qinstance->blockid = $this->id;
        $qinstance->blockelement = $questionid;
        $qinstance->type = 0;
        $qinstance->grade = 0; //TODO: ???
        $qinstance->slot = count($this->children);

        $id = $DB->insert_record('adaptivequiz_qinstance', $qinstance);

        array_push($this->children, block_element::load($this->quizid, $id));
    }

    /**
     * Adds a new subblock to the block.
     *
     * @param object $block the block to be added as a subblock.
     */
    public function add_subblock($block) {
        global $DB;

        $this->load_children();

        $qinstance = new stdClass();
        $qinstance->blockid = $this->id;
        $qinstance->blockelement = $block->get_id();
        $qinstance->type = 1;
        $qinstance->grade = 0; //TODO: ???
        $qinstance->slot = count($this->children);

        $id = $DB->insert_record('adaptivequiz_qinstance', $qinstance);

        array_push($this->children, block_element::load($this->quizid, $id));
    }

    /**
     * Moves one element from an old slot to a new one. Used to reorder the parts of the block.
     * @param int $oldslot the number of the old slot.
     * @param int $newslot the number of the slot, that the element should be moved to.
     * @return boolean whether the reordering was successfull.
     */
    public function move($oldslot, $newslot) {
        global $DB;

        $this->load_children();

        //TODO: check if allowed -> return false if not

        //TODO: otherwise update the DB
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
        if ($this->is_main_block()) {
            $record->id = $this->quizid;
            $DB->update_record('adaptivequiz', $record);
        }
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
     * Returns the quiz id of the block.
     *
     * @return int the id of the quiz, this block belongs to.
     */
    public function get_quizid() {
        return $this->quiz_id;
    }

    /**
     * Returns the id of the parent block or false, if this block has no parent block.
     *
     * @return bool|int the the id of the parent block or false.
     */
    public function get_parentid() {
        $mainblock = get_main_block($this->quizid);

        //if this is the main block, there is no parent block.
        if ($this->id == $mainblock->get_id()) {
            return false;
        }
        else {
            //top down search in the block-tree to find the parent.
            return $mainblock->search_parent($this->id)->get_id();
        }
        return false;
    }

    /**
     * Finds the parent of a block.
     * @param int $childid the id of the child to find the parent for.
     *
     * @return bool|block the parent block or fals, if the parent can not be found.
     */
    protected function search_parent($childid) {
        $this->load_children();
        foreach ($this->children as $element) {
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
     * @param int $quizid the quiz this reference belongs to.
     * @param int $type the type of this block_element.
     * @param object $element the element referenced by this block.
     */
    public function __construct($id, $quizid, $type, $elementid, $element) {
        $this->id = $id;
        $this->quizid = $quizid;
        $this->type = $type;
        $this->elementid = $elementid;
        $this->element = $element;
    }

    /**
     * Static function to get a block_element object from a its id.
     *
     * @param int $quizid the quiz this reference belongs to.
     * @param int $blockelementid the blockelement id.
     * @return block the new block object.
     */
    public static function load($quizid, $blockelementid) {
        global $DB;

        $questioninstance = $DB->get_record('adaptivequiz_qinstance', array('id' => $blockelementid), '*', MUST_EXIST);

        $element = null;
        if ($questioninstance->type == 0) {
            $element = $DB->get_record('question', array('id' => $questioninstance->blockelement), '*', MUST_EXIST);
        }
        else if ($questioninstance->type == 1) {
            $element = block::load($quizid, $questioninstance->blockelement);
        }
        else {
            return null;
        }
        return new block_element($blockelementid, $quizid, (int)$questioninstance->type, (int)$questioninstance->blockelement, $element);
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
}
