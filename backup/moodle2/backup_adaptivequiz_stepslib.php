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
        
        $blockElements = new backup_nested_element('block_elements');
        
        $blockElement = new backup_nested_element('block_element', array('id'), array('blockid',
                'blockelement', 'type', 'grade', 'slot'));
        
        $conditions = new backup_nested_element('conditions');
        
        $condition = new backup_nested_element('condition', array('id'), array('useand'));
        
        $conditionParts = new backup_nested_element('condition_parts');
        
        $conditionPart = new backup_nested_element('condition_part', array('id'),
                array('on_qinstance', 'type', 'gade', 'conditionid'));
        
        $feedbackBlocks = new backup_nested_element('feedback_blocks');
        
        $feedbackBlock = new backup_nested_element('feedback_block', array('id'),
                array('name', 'quizid', 'conditionid', 'feedbacktext'));
        
        $feedbackUses = new backup_nested_element('feedback_uses');
        
        $feedbackUse = new backup_nested_element('feedback_use', array('id'),
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
        
        $adaptivequiz->add_child($blockElements);
        $blockElements->add_child($blockElement);
        
        $blockElement->add_child($conditionParts);
        $conditionParts->add_child($conditionPart);
        
        $adaptivequiz->add_child($feedbackBlocks);
        $feedbackBlocks->add_child($feedbackBlock);
        
        $feedbackBlock->add_child($feedbackUses);
        $feedbackUses->add_child($feedbackUse);
        
        // Get ids.
        $cm = get_coursemodule_from_id('adaptivequiz', $this->cmid, 0, false, MUST_EXIST);
        $quizid = $cm->instance;
        $quizInstance = adaptivequiz::load($quizid);
        $mainblock = block::load($quizInstance, $quizInstance->get_main_block()->get_id());
        $arrayOfBlocks = $mainblock->get_blocks();
        $blockIds = array_merge(array($mainblock->get_id()),
                        array_map(
                            function(block_element $blockelement) { return $blockelement->get_element()->get_id(); },
                            $arrayOfBlocks
                        )
                    );
        $blockElementIds = array_map(
                function(block_element $blockelement) { return $blockelement->get_id(); },
                $mainblock->get_elements()
                );
        $blockConditionIds = array_map(
                function(block_element $blockelement) { return $blockelement->get_element()->get_condition()->get_id(); },
                $arrayOfBlocks
                );
        $feedbackBlockRecords = $DB->get_records('adaptivequiz_feedback_block', array('quizid' => $quizid));
        $feedbackblockConditionIds = array_map(
                function($record) { return $record->conditionid; },
                $feedbackBlockRecords
                ); 
        $conditionIds = array_merge($blockConditionIds, $feedbackblockConditionIds);
        
        // Define data sources.
        $adaptivequiz->set_source_table('adaptivequiz', array('id' => backup::VAR_ACTIVITYID));
        
        // These elements only happen if we are including user info.
        if ($userinfo) {
            $grade->set_source_table('adaptivequiz_grades', array('quiz' => backup::VAR_PARENTID));
            $attempt->set_source_table('adaptivequiz_attempts', array('quiz' => backup::VAR_PARENTID));
        }
        
        if (count($conditionIds) > 0) {
            $sqlParams = implode(", ", $conditionIds);
            $sql = 'SELECT * FROM mdl_adaptivequiz_condition WHERE id IN (' . $sqlParams . ');';
            $condition->set_source_sql($sql, array());
        }
        
        if (count($blockIds) > 0) {
            $sqlParams = implode(", ", $blockIds);
            $sql = 'SELECT * FROM mdl_adaptivequiz_block WHERE id IN (' . $sqlParams . ');';
            $block->set_source_sql($sql, array());
        }
        
        if (count($blockElementIds) > 0) {
            $sqlParams = implode(", ", $blockElementIds);
            $sql = 'SELECT * FROM mdl_adaptivequiz_qinstance WHERE id IN (' . $sqlParams . ');';
            $blockElement->set_source_sql($sql, array());
        }
        
        $conditionPart->set_source_table('adaptivequiz_condition_part', array('on_qinstance' => backup::VAR_PARENTID));
        
        $feedbackBlock->set_source_table('adaptivequiz_feedback_block', array('quizid' => backup::VAR_PARENTID));
        
        $feedbackUse->set_source_table('adaptivequiz_feedback_uses', array('feedbackblockid' => backup::VAR_PARENTID));
        
        // Define file annotations (we do not use itemid in this example).
        $adaptivequiz->annotate_files('mod_adaptivequiz', 'intro', null);

        // Return the root element (adaptivequiz), wrapped into standard activity structure.
        return $this->prepare_activity_structure($adaptivequiz);
    }
}
