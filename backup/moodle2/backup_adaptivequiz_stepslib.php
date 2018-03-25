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
                'name', 'intro', 'introformat', 'grade', 'maxgrade', 'grademethod', 'mainblock'));

        // Define elements.
        $grades = new backup_nested_element('grades');

        $grade = new backup_nested_element('grade', array('id'), array('quiz', 'userid',
                'grade', 'timemodified'));

        $attempts = new backup_nested_element('attempts');

        $attempt = new backup_nested_element('attempt', array('id'), array('quiz', 'userid',
                'attempt', 'quba', 'currentslot', 'state', 'timestart', 'timefinish',
                'timemodified', 'timecheckstate', 'sumgrades', 'preview'));
        $blocks = new backup_nested_element('blocks');

        $block = new backup_nested_element('block', array('id'), array('name', 'conditionid'));
        
        $blockelements = new backup_nested_element('block_elements');
        
        $blockelementquestion = new backup_nested_element('block_element_question', array('id'), array('blockid',
            'blockelement', 'type', 'grade', 'slot'));
        
        $blockelementblock = new backup_nested_element('block_element_block', array('id'), array('blockid',
            'blockelement', 'type', 'grade', 'slot'));

        $conditions = new backup_nested_element('conditions');

        $condition = new backup_nested_element('condition', array('id'), array('useand'));
        
        $conditionparts = new backup_nested_element('condition_parts');
        
        $conditionpart = new backup_nested_element('condition_part', array('id'),
                array('on_qinstance', 'type', 'grade', 'conditionid'));
        
        $feedbackblocks = new backup_nested_element('feedback_blocks');
        
        $feedbackblock = new backup_nested_element('feedback_block', array('id'),
                array('name', 'quizid', 'conditionid', 'feedbacktext'));
        
        $feedbackuses = new backup_nested_element('feedback_uses');
        
        $feedbackuse = new backup_nested_element('feedback_use', array('id'),
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
        
        $adaptivequiz->add_child($blocks);
        $blocks->add_child($block);
        
        $adaptivequiz->add_child($blockelements);
        $blockelements->add_child($blockelementquestion);
        $blockelements->add_child($blockelementblock);
        
        $adaptivequiz->add_child($conditionparts);
        $conditionparts->add_child($conditionpart);
        
        $adaptivequiz->add_child($feedbackblocks);
        $feedbackblocks->add_child($feedbackblock);
        
        $feedbackblock->add_child($feedbackuses);
        $feedbackuses->add_child($feedbackuse);
        
        // Get ids.
        $cm = get_coursemodule_from_id('adaptivequiz', $this->cmid, 0, false, MUST_EXIST);
        $quizid = $cm->instance;
        $quizinstance = adaptivequiz::load($quizid);
        $mainblock = block::load($quizinstance, $quizinstance->get_main_block()->get_id());
        
        $blockids = array_merge(array($mainblock->get_id()),
                        array_map(
                            function(block_element $blockelement) { return $blockelement->get_element()->get_id(); },
                            $mainblock->get_blocks()
                        )
                    );
        $blockelementids = array_map(
                function(block_element $blockelement) { return $blockelement->get_id(); },
                $mainblock->get_elements()
                );
        $blockconditionids = array_map(
                function(block_element $blockelement) { return $blockelement->get_element()->get_condition()->get_id(); },
                $mainblock->get_blocks()
                );
        $feedbackblockrecords = $DB->get_records('adaptivequiz_feedback_block', array('quizid' => $quizid));
        $feedbackblockconditionids = array_map(
                function($record) { return $record->conditionid; },
                $feedbackblockrecords
                ); 
        $conditionIds = array_merge($blockconditionids, $feedbackblockconditionids);

        // Define data sources.
        $adaptivequiz->set_source_table('adaptivequiz', array('id' => backup::VAR_ACTIVITYID));

        // These elements only happen if we are including user info.
        if ($userinfo) {
            $grade->set_source_table('adaptivequiz_grades', array('quiz' => backup::VAR_PARENTID));
            $attempt->set_source_table('adaptivequiz_attempts', array('quiz' => backup::VAR_PARENTID));
        }
        
        if (count($conditionIds) > 0) {
            $sqlParams = implode(", ", $conditionIds);
            $sql = 'SELECT * FROM {adaptivequiz_condition} WHERE id IN (' . $sqlParams . ');';
            $condition->set_source_sql($sql, array());
        }
        
        if (count($blockids) > 0) {
            $sqlParams = implode(", ", $blockids);
            $sql = 'SELECT * FROM {adaptivequiz_block} WHERE id IN (' . $sqlParams . ');';
            $block->set_source_sql($sql, array());
        }
        
        if (count($blockelementids) > 0) {
            $sqlParams = implode(", ", $blockelementids);
            $questionsql = 'SELECT * FROM {adaptivequiz_qinstance} WHERE id IN (' . $sqlParams . ') AND type = 0;';
            $blockelementquestion->set_source_sql($questionsql, array());
            $blocksql = 'SELECT * FROM {adaptivequiz_qinstance} WHERE id IN (' . $sqlParams . ') AND type = 1;';
            $blockelementblock->set_source_sql($blocksql, array());
        }
        
        $conditionpart->set_source_table('adaptivequiz_condition_part', array('on_qinstance' => backup::VAR_PARENTID));
        $feedbackblock->set_source_table('adaptivequiz_feedback_block', array('quizid' => backup::VAR_PARENTID));
        $feedbackuse->set_source_table('adaptivequiz_feedback_uses', array('feedbackblockid' => backup::VAR_PARENTID));
        
        // Define id annotations.
        $blockelementquestion->annotate_ids('question', 'blockelement');
        $grade->annotate_ids('user', 'userid');
        $attempt->annotate_ids('user', 'userid');
        
        // Define file annotations (we do not use itemid in this example).
        $adaptivequiz->annotate_files('mod_adaptivequiz', 'intro', null);

        // Return the root element (adaptivequiz), wrapped into standard activity structure.
        return $this->prepare_activity_structure($adaptivequiz);
    }
}
