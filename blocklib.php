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
    /** @var string the name of the block. */
    protected $name = '';
    /** @var array of {@link block_element}, that are contained in this block. */
    protected $children = null;

    // Constructor =============================================================
    /**
     * Constructor, assuming we already have the necessary data loaded.
     *
     * @param int $id the id of the block.
     * @param string $name the name of the block.
     * @param array $children an array of block_element representing the parts of this block.
     */
    public function __construct($id, $name, $children) {
        $this->id = $id;
        $this->name = $name;
        $this->children = $children;
    }

    /**
     * Static function to get a block object from a block id.
     *
     * @param int $blockid the block id.
     * @return block the new block object.
     */
    public static function load($blockid) {
        global $DB;

        $block = $DB->get_record('adaptivequiz_block', array('id' => $blockid), '*', MUST_EXIST);

        return new block($blockid, $block->name, null);
    }

    /**
     * Static function to create a new block in the database.
     *
     * @param string $name the name of the block.
     * @return block the new block object.
     */
    public static function create($name) {
        global $DB;

        $block = new stdClass();
        $block->name = $name;
        $blockid = $DB->insert_record('adaptivequiz_block', $block);

        return new block($blockid, $block->name, null);
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

        $this->children = array_map(function($id) {
                                        return block_element::load($id);
                                    },
                                    array_values($children));
    }

    /**
     * Adds a new question to the block.
     *
     * @param object $question the question to be added.
     */
    public function add_question($question) {
        global $DB;

        $this->load_children();

        $qinstance = new stdClass();
        $qinstance->blockid = $this->id;
        $qinstance->blockelement = $question->id;
        $qinstance->type = 0;
        $qinstance->grade = 0; //TODO: ???
        $qinstance->slot = count($this->children);

        $id = $DB->insert_record('adaptivequiz_qinstance', $qinstance);

        array_push($this->children, block_element::load($id));
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

        array_push($this->children, block_element::load($id));
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
     * Returns the id of the block.
     *
     * @return int the id of this block.
     */
    public function get_id() {
        return $this->id;
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
    /** @var object the {@link block} or question, this element refers to. */
    protected $element = null;

    // Constructor =============================================================
    /**
     * Constructor, assuming we already have the necessary data loaded.
     *
     * @param int $id the id of the block_elem.
     * @param int $type the type of this block_element.
     * @param object $element the element referenced by this block.
     */
    public function __construct($id, $type, $element) {
        $this->id = $id;
        $this->type = $type;
        $this->element = $element;
    }

    /**
     * Static function to get a block_element object from a its id.
     *
     * @param int $blockelementid the blockelement id.
     * @return block the new block object.
     */
    public static function load($blockelementid) {
        global $DB;

        $questioninstance = $DB->get_record('adaptivequiz_qinstance', array('id' => $blockelementid), '*', MUST_EXIST);
        $element = null;
        if ($questioninstance->type === 0) {
            $element = $DB->get_record('question', array('id' => $questioninstance->question), MUST_EXIST);
        }
        else if ($questioninstance->type === 1) {
            $element = block::load($questioninstance->question);
        }
        return new block_element($blockelementid, $questioninstance->type, $element);
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
     * Return the element.
     *
     * @return object the element.
     */
    public function get_element() {
        return $this->element;
    }
}
