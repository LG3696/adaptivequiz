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
     * Constructor - instantiates one object of this class
     */
    public function __construct($name, $filename, $cmid) {
        $this->cmid = $cmid;
        parent::__construct($name, $filename);
    }
    
    /**
     * Defines the backup structure of the module
     *
     * @return backup_nested_element
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
                'timemodified', 'timecheckstate', 'sumgrades', 'preview'));
        
        $blocks = new backup_nested_element('blocks');
        
        $block = new backup_nested_element('block', array('id'), array('name', 'conditionid'));
        
        $block_elements = new backup_nested_element('block_elements');
        
        $block_element = new backup_nested_element('block_element', array('id'), array('blockid',
                'blockelement', 'type', 'grade', 'slot'));
        
        $conditions = new backup_nested_element('conditions');
        
        $condition = new backup_nested_element('condition', array('id'), array('useand'));
        
        $condition_parts = new backup_nested_element('condition_parts');
        
        $condition_part = new backup_nested_element('condition_part', array('id'),
                array('on_qinstance', 'type', 'gade', 'conditionid'));
        
        $feedback_blocks = new backup_nested_element('feedback_blocks');
        
        $feedback_block = new backup_nested_element('feedback_block', array('id'),
                array('name', 'quizid', 'conditionid', 'feedbacktext'));
        
        $feedback_uses = new backup_nested_element('feedback_uses');
        
        $feedback_use = new backup_nested_element('feedback_use', array('id'),
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
        
        $adaptivequiz->add_child($block_elements);
        $block_elements->add_child($block_element);
        
        $block_element->add_child($condition_parts);
        $condition_parts->add_child($condition_part);
        
        $adaptivequiz->add_child($feedback_blocks);
        $feedback_blocks->add_child($feedback_block);
        
        $feedback_block->add_child($feedback_uses);
        $feedback_uses->add_child($feedback_use);
        
        // Get ids.
        $ids_cm = get_coursemodule_from_id('adaptivequiz', $this->cmid, 0, false, MUST_EXIST);
        $quizid = $ids_cm->instance;
        $ids_quiz = adaptivequiz::load($quizid);
        $ids_mainblock = block::load($ids_quiz, $ids_quiz->get_main_block()->get_id());
        $ids_blocks = $ids_mainblock->get_blocks();
        $block_ids = array_merge(array($ids_mainblock->get_id()),
                        array_map(
                            function(block_element $blockelement) { return $blockelement->get_element()->get_id(); },
                            $ids_blocks
                        )
                    );
        $blockElementIds = array_map(
                function(block_element $blockelement) { echo $blockelement->get_id()."\n";return $blockelement->get_id(); },
                $ids_mainblock->get_elements()
                );
        echo count($ids_mainblock->get_children());
        $block_condition_ids = array_map(
                function(block_element $blockelement) { return $blockelement->get_element()->get_condition()->get_id(); },
                $ids_blocks
                );
        $feedback_block_records = $DB->get_records('adaptivequiz_feedback_block', array('quizid' => $quizid));
        $feedbackblock_condition_ids = array_map(
                function($record) { return $record->conditionid; },
                $feedback_block_records
                ); 
        $condition_ids = array_merge($block_condition_ids, $feedbackblock_condition_ids);
        
        // Define data sources.
        $adaptivequiz->set_source_table('adaptivequiz', array('id' => backup::VAR_ACTIVITYID));
        
        // These elements only happen if we are including user info.
        if ($userinfo) {
            $grade->set_source_table('adaptivequiz_grades', array('quiz' => backup::VAR_PARENTID));
            $attempt->set_source_table('adaptivequiz_attempts', array('quiz' => backup::VAR_PARENTID));
        }
        
        if (count($condition_ids) > 0) {
            $sql_param = implode(", ", $condition_ids);
            $sql = 'SELECT * FROM mdl_adaptivequiz_condition WHERE id IN (' . $sql_param . ');';
            $condition->set_source_sql($sql, array());
        }
        
        if (count($block_ids) > 0) {
            $sql_param = implode(", ", $block_ids);
            $sql = 'SELECT * FROM mdl_adaptivequiz_block WHERE id IN (' . $sql_param . ');';
            $block->set_source_sql($sql, array());
        }
        
        if (count($blockElementIds) > 0) {
            $sql_param = implode(", ", $blockElementIds);
            $sql = 'SELECT * FROM mdl_adaptivequiz_qinstance WHERE id IN (' . $sql_param . ');';
            $block_element->set_source_sql($sql, array());
        }
        
        $condition_part->set_source_table('adaptivequiz_condition_part', array('on_qinstance' => backup::VAR_PARENTID));
        
        $feedback_block->set_source_table('adaptivequiz_feedback_block', array('quizid' => backup::VAR_PARENTID));
        
        $feedback_use->set_source_table('adaptivequiz_feedback_uses', array('feedbackblockid' => backup::VAR_PARENTID));
        
        // Define file annotations (we do not use itemid in this example).
        $adaptivequiz->annotate_files('mod_adaptivequiz', 'intro', null);

        // Return the root element (adaptivequiz), wrapped into standard activity structure.
        return $this->prepare_activity_structure($adaptivequiz);
    }
}
