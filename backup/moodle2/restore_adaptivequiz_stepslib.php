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
 * Define all the restore steps that will be used by the restore_adaptivequiz_activity_task
 *
 * @package   mod_adaptivequiz
 * @category  backup
 * @copyright 2016 Your Name <your@email.address>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Structure step to restore one adaptivequiz activity
 *
 * @package   mod_adaptivequiz
 * @category  backup
 * @copyright 2016 Your Name <your@email.address>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_adaptivequiz_activity_structure_step extends restore_questions_activity_structure_step {

    /**
     * Defines structure of path elements to be processed during the restore
     *
     * @return array of {@link restore_path_element}
     */
    protected function define_structure() {
        
        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');
        
        $paths[] = new restore_path_element('adaptivequiz', '/activity/adaptivequiz');
        
        if ($userinfo) {
            $paths[] = new restore_path_element('grade', '/activity/adaptivequiz/grades/grade');
            
            // Process the attempt data.
            $quizattempt = new restore_path_element('attempt', '/activity/adaptivequiz/attempts/attempt');
            $paths[] = $quizattempt;
            
            // Add states and sessions.
            $this->add_question_usages($quizattempt, $paths);
        }
        $paths[] = new restore_path_element('block', '/activity/adaptivequiz/blocks/block');
        $paths[] = new restore_path_element('block_element', '/activity/adaptivequiz/block_elements/block_element');
        $paths[] = new restore_path_element('condition', '/activity/adaptivequiz/conditions/condition');
        $paths[] = new restore_path_element('condition_part', '/activity/adaptivequiz/block_elements/block_element/condition_parts/condition_part');
        $paths[] = new restore_path_element('feedback_block', '/activity/adaptivequiz/feedback_blocks/feedback_block');
        $paths[] = new restore_path_element('feedback_use', '/activity/adaptivequiz/feedback_blocks/feedback_block/feedback_uses/feedback_use');
        
        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the given restore path element data
     *
     * @param array $data parsed element data
     */
    protected function process_adaptivequiz($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $old_mainblock = $data->mainblock;
        $data->course = $this->get_courseid();

        if (empty($data->timecreated)) {
            $data->timecreated = time();
        }

        if (empty($data->timemodified)) {
            $data->timemodified = time();
        }

        if ($data->grade < 0) {
            // Scale found, get mapping.
            $data->grade = -($this->get_mappingid('scale', abs($data->grade)));
        }
        
        // Create the adaptivequiz instance.
        $mainblock = new stdClass();
        $mainblock->name = $data->name;
        $new_mainblock = $DB->insert_record('adaptivequiz_block', $mainblock);
        
        $data->mainblock = $new_mainblock;
        
        $newitemid = $DB->insert_record('adaptivequiz', $data);
        $this->apply_activity_instance($newitemid);
        $this->set_mapping('block', $old_mainblock, $new_mainblock);
    }
    
    protected function process_grade($data) {
        global $DB;
        
        $data = (object) $data;
        
        $data->quiz = $this->get_new_parentid('adaptivequiz');
        
        $newitemid = $DB->insert_record('adaptivequiz_grades', $data);
    }
    
    protected function process_attempt($data) {
        global $DB;
        
        $data = (object) $data;
        
        $data->quiz = $this->get_new_parentid('adaptivequiz');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timestart = $this->apply_date_offset($data->timestart);
        $data->timefinish = $this->apply_date_offset($data->timefinish);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        
        // The data is actually inserted into the database later in inform_new_usage_id.
        $this->currentquizattempt = clone($data);
    }
    
    protected function process_condition($data) {
        global $DB;
        
        $data = (object) $data;
        $oldid = $data->id;
        
        $newitemid = $DB->insert_record('adaptivequiz_condition', $data);
        $this->set_mapping('condition', $oldid, $newitemid);
    }
    
    protected function process_block($data) {
        global $DB;
        
        $data = (object) $data;
        $oldid = $data->id;
        
        if (!is_null($this->get_mappingid('block', $data->id, null))) return;
        
        $data->conditionid = $this->get_mappingid('condition', $data->conditionid);
        $newitemid = $DB->insert_record('adaptivequiz_block', $data);
        $this->set_mapping('block', $oldid, $newitemid);
    }
    
    protected function process_block_element($data) {
        global $DB;
        
        $userinfo = $this->get_setting_value('userinfo');
        $data = (object) $data;
        $oldid = $data->id;
        
        $data->blockid = $this->get_mappingid('block', $data->blockid);
        if ($data->type == 0) { // question
            $data->blockelement = $this->get_mappingid('question', $data->blockelement);
        } else { // block
            $data->blockelement = $this->get_mappingid('block', $data->blockelement);
        }
        
        $newitemid = $DB->insert_record('adaptivequiz_qinstance', $data);
        $this->set_mapping('block_element', $oldid, $newitemid);
    }
    
    protected function process_condition_part($data) {
        global $DB;
        
        $data = (object) $data;
        $data->conditionid = $this->get_mappingid('condition', $data->conditionid);
        $data->on_qinstance = $this->get_mappingid('block_element', $data->on_qinstance);
        
        $newitemid = $DB->insert_record('adaptivequiz_condition_part', $data);
    }
    
    protected function process_feedback_block($data) {
        global $DB;
        
        $data = (object) $data;
        $oldid = $data->id;
        
        $data->quizid = $this->get_new_parentid('adaptivequiz');
        $data->conditionid = $this->get_mappingid('condition', $data->conditionid);
        
        $newitemid = $DB->insert_record('adaptivequiz_feedback_block', $data);
        $this->set_mapping('feedback_block', $oldid, $newitemid);
    }
    
    protected function process_feedback_use($data) {
        global $DB;
        $feedback_use = new restore_path_element('feedback_use', array('id'),
                array('feedbackblockid', 'questioninstanceid'));
        
        $data = (object) $data;
        $oldid = $data->id;
        
        $data->feedbackblockid = $this->get_new_parentid('feedback_block');
        $data->questioninstanceid = $this->get_mappingid('block_element', $data->questioninstanceid);
        
        $newitemid = $DB->insert_record('adaptivequiz_feedback_uses', $data);
    }
    
    /**
     * Post-execution actions
     */
    protected function after_execute() {
        // Restore any files belonging to responses.
        foreach (question_engine::get_all_response_file_areas() as $filearea) {
            $this->add_related_files('question', $filearea, 'question_attempt_step');
        }
        // Add adaptivequiz related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_adaptivequiz', 'intro', null);
    }
    
    protected function inform_new_usage_id($newusageid) {
        global $DB;
        
        $data = $this->currentquizattempt;
        
        $oldid = $data->id;
        $data->quba = $newusageid;
        
        $newitemid = $DB->insert_record('adaptivequiz_attempts', $data);
    }

}
