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
 * Define all the backup steps that will be used by the backup_adaptivequiz_activity_task
 *
 * @package   mod_adaptivequiz
 * @category  backup
 * @copyright 2018 Jan Emrich <jan.emrich@stud.tu-darmstadt.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/adaptivequiz/locallib.php');
/**
 * Define the complete adaptivequiz structure for backup, with file and id annotations
 *
 * @package   mod_adaptivequiz
 * @category  backup
 * @copyright 2018 Jan Emrich <jan.emrich@stud.tu-darmstadt.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_adaptivequiz_activity_structure_step extends backup_questions_activity_structure_step {

    /** @var int the id of this course module. */
    protected $cmid;

    /**
     * Constructor - instantiates one object of this class.
     *
     * @param string $name the name of
     * @param string $filename the name of
     * @param int $cmid the id of the course module.
     */
    public function __construct($name, $filename, $cmid) {
        $this->cmid = $cmid;
        parent::__construct($name, $filename);
    }

    /**
     * Defines the backup structure of the module.
     *
     * @return backup_nested_element.
     */
    protected function define_structure() {

        global $DB;

        // Get know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define the root element describing the adaptivequiz instance.
        $adaptivequiz = new backup_nested_element('adaptivequiz', array('id'), array(
                'name', 'intro', 'introformat', 'grade', 'maxgrade', 'mainblock'));

        // Define elements.
        $grades = new backup_nested_element('grades');

        $grade = new backup_nested_element('grade', array('id'), array('quiz', 'userid',
                'grade', 'timemodified'));

        $attempts = new backup_nested_element('attempts');

        $attempt = new backup_nested_element('attempt', array('id'), array('quiz', 'userid',
                'attempt', 'quba', 'currentslot', 'state', 'timestart', 'timefinish',
                'timemodified', 'timecheckstate', 'sumgrades'));

        $blocks = new backup_nested_element('blocks');

        $block = new backup_nested_element('block', array('id'), array('name', 'conditionid'));

        $blockelements = new backup_nested_element('blockelements');

        $blockelement = new backup_nested_element('blockelement', array('id'), array('blockid',
                'blockelement', 'type', 'grade', 'slot'));

        $conditions = new backup_nested_element('conditions');

        $condition = new backup_nested_element('condition', array('id'), array('useand'));

        $conditionparts = new backup_nested_element('conditionparts');

        $conditionpart = new backup_nested_element('conditionpart', array('id'),
                array('on_qinstance', 'type', 'gade', 'conditionid'));

        $feedbackblocks = new backup_nested_element('feedbackblocks');

        $feedbackblock = new backup_nested_element('feedbackblock', array('id'),
                array('name', 'quizid', 'conditionid', 'feedbacktext'));

        $feedbackuses = new backup_nested_element('feedbackuses');

        $feedbackuse = new backup_nested_element('feedbackuse', array('id'),
                array('feedbackblockid', 'questioninstanceid'));

        // This module is using questions, so produce the related question states and sessions
        // attaching them to the $attempt element based in 'quba' matching.
        $this->add_question_usages($attempt, 'quba');

        // Build the tree.
        $adaptivequiz->add_child($grades);
        $grades->add_child($grade);

        $adaptivequiz->add_child($attempts);
        $attempts->add_child($attempt);

        $adaptivequiz->add_child($conditions);
        $conditions->add_child($condition);

        $condition->add_child($blocks);
        $blocks->add_child($block);

        $block->add_child($blockelements);
        $blockelements->add_child($blockelement);

        $blockelement->add_child($conditionparts);
        $conditionparts->add_child($conditionpart);

        $condition->add_child($feedbackblocks);
        $feedbackblocks->add_child($feedbackblock);

        $feedbackblock->add_child($feedbackuses);
        $feedbackuses->add_child($feedbackuse);

        // Get ids.
        $idscm = get_coursemodule_from_id('adaptivequiz', $this->cmid, 0, false, MUST_EXIST);
        $quizid = $idscm->instance;
        $idsquizrecord = $DB->get_record('adaptivequiz', array('id' => $quizid), '*', MUST_EXIST);
        $idsquiz = adaptivequiz::load($quizid);
        $idsmainblock = block::load($idsquiz, $idsquiz->get_main_block()->get_id());
        $idsblocks = $idsmainblock->get_blocks();
        $blockconditionids = array_map(
                function(block_element $blockelement) {
                    $blockelement->get_element()->get_condition()->get_id();
                },
                $idsblocks
                );
        $blockids = array_map(
                function($blockelement) {
                    $blockelement->get_element()->get_id();
                },
                $idsblocks
                );
        $feedbackblockrecords = $DB->get_records('adaptivequiz_feedbackblock', array('id' => $quizid), 'id', MUST_EXIST);
        $feedbackblockids = array_map(
                function($record) {
                    $record->id;
                },
                $feedbackblockrecords
                );
        $feedbackblockconditionids = array_map(
                function($record) {
                    $record->conditionid;
                },
                $feedbackblockrecords
                );
        $conditionids = array_merge($blockconditionids, $feedbackblockconditionids);
        // Define data sources.
        $adaptivequiz->set_source_table('adaptivequiz', array('id' => backup::VAR_ACTIVITYID));

        // These elements only happen if we are including user info.
        if ($userinfo) {
            $grade->set_source_table('adaptivequiz_grades', array('quiz' => backup::VAR_PARENTID));
            $attempt->set_source_table('adaptivequiz_attempts', array('quiz' => backup::VAR_PARENTID));
        }

        $sqlparam = implode(', ', $conditionids);
        $condition->set_source_sql($sql, array());

        // Define file annotations (we do not use itemid in this example).
        $adaptivequiz->annotate_files('mod_adaptivequiz', 'intro', null);

        // Return the root element (adaptivequiz), wrapped into standard activity structure.
        return $this->prepare_activity_structure($adaptivequiz);
    }

    /**
     * Adds the backup structure of the block elements.
     *
     * @param backup_nested_element $backupblock the element this block elements to add to.
     * @param block_element $quizblockelement the adaptivequiz block element.
     **/
    protected function add_blockelement(backup_nested_element $backupblock, block_element $quizblockelement) {
        // Define backup_nested_elements.
        $blockelements = new backup_nested_element('blockelements');

        $blockelement = new backup_nested_element('blockelement', array('id'), array('name', 'blockid',
                'blockelement', 'type', 'grade', 'slot'));

        // Add to the tree.
        $backupblock->add_child($blockelements);

        $blockelements->add_child($blockelement);

        // Define source.
        // $params = array('blockid' => backup_helper::is_sqlparam($quizblock->get_id()));
        $blockelement->set_source_table('adaptivequiz_qinstances', array('blockid' => backup::VAR_PARENTID));

        // Add blocks that are part of this block.
        // foreach ($quizblock->get_blocks() as $be) {
        // add_block($blockelement, $be);
        // }
    }

    /**
     * Adds the backup structure of a block.
     * @param backup_nested_element $backupelement the element this block will be added to.
     * @param blockelement $quizblockelement the adaptivequiz block element that contains the block.
     */
    // protected function add_block
}