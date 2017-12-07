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
 * Defines the renderer for the adaptive quiz module.
 *
 * @package    mod_adaptivequiz
 * @copyright  2017 Jana Vatter <jana.vatter@stud.tu-darmstadt.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/adaptivequiz/locallib.php');

/**
 * The renderer for the adaptive quiz module.
 *
 * @copyright  2017 Jana Vatter <jana.vatter@stud.tu-darmstadt.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_adaptivequiz_renderer extends plugin_renderer_base {
    /**
     * Render the edit page
     *
     * @param block $blockobj object containing all the block information.
     * @param \moodle_url $pageurl The URL of the page.
     * @param int $quizid The ID of the quiz.
     * @return string HTML to output.
     */
    public function edit_page(block $blockobj, $pageurl, $quizid) {
        $output = '';
        //TODO Page title.
        $output .= html_writer::start_tag('form', array('action' => $pageurl->out()));
        $output .= html_writer::tag('input', '', array('type' => 'hidden', 'name' => 'qid', 'value' => $quizid));
        $output .= html_writer::tag('input', '', array('type' => 'hidden', 'name' => 'bid', 'value' => $blockobj->get_id()));
        $output .= html_writer::tag('input', '', array('type' => 'hidden', 'name' => 'done', 'value' => 1));
        $namefield = html_writer::tag('input', '', array('type' => 'text', 'name' => 'blockname', 'value' => $blockobj->get_name()));
        $output .= $this->heading(get_string('editingblock', 'adaptivequiz') . ' ' . $namefield);
        $output .= html_writer::start_tag('ul');

        $children = $blockobj->get_children();
        foreach($children as $child) {
            $output .= $this->block_elem($child, $pageurl, $quizid);
        }
        $addmenu = $this->add_menu($pageurl);
        $output .= html_writer::tag('li', $addmenu);
        $output .= html_writer::end_tag('ul');
        $output .= html_writer::tag('button', get_string('done', 'adaptivequiz'));
        $output .= html_writer::end_tag('form');
        return $output;
    }

    /**
     * Render one element of a block.
     * 
     * @param block_element $blockelem An element of a block.
     * @param \moodle_url $pageurl The URL of the page.
     * @param int $quizid The ID of the quiz.
     * @return string HTML to display this element.
     */
    public function block_elem(block_element $blockelem, $pageurl, $quizid) {
        //Description of the element.
        $element_html = '';
        $edit_html = '';
        $cmid = 1;
        
        /*if ($blockelem->is_question()) {
            $element_html = $blockelem->get_name();
            $edit_html = $this->question_edit_button($blockelem->get_element(), $pageurl, $cmid);
            
        }
        else if ($blockelem->is_block()) {
            $element_html = $blockelem->get_name();
            $edit_html = $this->block_edit_button($blockelem->get_element(), $pageurl, $cmid, $quizid);
        }
        else {
            $element_html = 'This elementtype is not supported.';
        }*/
        $element_html = $blockelem->get_name();
        $edit_html = $this->element_edit_button($blockelem, $pageurl, $cmid);
        $remove_html = $this->element_remove_button($blockelem, $pageurl);
        return html_writer::tag('li', $element_html . $edit_html . $remove_html);
    }

    /**
     * Outputs the edit button HTML for an element.
     * 
     * @param block_element $element the element to get the button for.
     * @param \moodle_url $returnurl the URL of the page.
     * @param int $cmid the ID of the course.
     * @return string HTML to output.
     */
    public function element_edit_button($element, $returnurl, $cmid) {
        global $OUTPUT, $CFG;
        // Minor efficiency saving. Only get strings once, even if there are a lot of icons on one page.
        static $stredit = null;
        static $strview = null;
        if ($stredit === null) {
            $stredit = get_string('edit');
            $strview = get_string('view');
        }

        // What sort of icon should we show?
        $action = '';
        if ($element->may_edit()) {
            $action = $stredit;
            $icon = '/t/edit';
        } else if ($element->may_view()) {
            $action = $strview;
            $icon = '/i/info';
        }

        // Build the icon.
        if ($action) {
            if ($returnurl instanceof moodle_url) {
                $returnurl = $returnurl->out_as_local_url(false);
            }
            $elementparams = array('returnurl' => $returnurl, 'cmid' => $cmid);
            $elementurl = $element->get_edit_url();
            $elementurl->params($elementparams);
            return '<a title="' . $action . '" href="' . $elementurl->out() . '" class="elementeditbutton"><img src="' .
                $OUTPUT->pix_url($icon) . '" alt="' . $action . '" />' .
                '</a>';
            return '';
        } 
        else {
            return '';
        }
    }
    
    /**
     * Outputs the remove button HTML for an element.
     * 
     * @param block_element $element the element to get the button for.
     * @return string HTML to output.
     */
    public function element_remove_button($element, $pageurl) {
        $url = new \moodle_url($pageurl, array('sesskey' => sesskey(), 'remove' => $element->get_id()));
        $strdelete = get_string('delete');
        
        $image = $this->pix_icon('t/delete', $strdelete);
        return $this->action_link($url, $image, null, array('title' => $strdelete,
            'class' => 'cm-edit-action editing_delete', 'data-action' => 'delete'));
    }
        
    /**
     * Outputs the add menu HTML.
     * 
     * @param \moodle_url $pageurl The URL of the page.
     * @return string HTML to output.
     */
    protected function add_menu(\moodle_url $pageurl) {
        $menu = new \action_menu();
        $menu->set_alignment(\action_menu::TL, \action_menu::TL);
        $trigger = html_writer::tag('span', get_string('add', 'adaptivequiz'));
        $menu->set_menu_trigger($trigger);
        // The menu appears within an absolutely positioned element causing width problems.
        // Make sure no-wrap is set so that we don't get a squashed menu.
        $menu->set_nowrap_on_items(true);
        $params = array('returnurl' => $pageurl->out_as_local_url(false),
            'cmid' => 3, //TODO
            'category' => 2,//TODO
            'appendqnumstring' => 'addquestion');
        
        //Button to add a question. 
        $addaquestion = new \action_menu_link_secondary(
            new \moodle_url('/question/addquestion.php', $params),
            new \pix_icon('t/add', get_string('addaquestion', 'adaptivequiz'), 'moodle', array('class' => 'iconsmall', 'title' => '')),
            get_string('addaquestion', 'adaptivequiz'),
            array('class' => 'cm-edit-action addquestion', 'data-action' => 'addquestion')
            );
        $menu->add($addaquestion);

        //Button to add question from question bank.
        $questionbank =  new \action_menu_link_secondary($pageurl,
            new \pix_icon('t/add', $str->questionbank, 'moodle', array('class' => 'iconsmall', 'title' => '')),
            get_string('questionbank', 'adaptivequiz'),
            array('class' => 'cm-edit-action questionbank', 'data-action' => 'questionbank'));
        $menu->add($questionbank);
        $menu->prioritise = true;
        
        //Button to add a block.
        $addblockurl = new \moodle_url($pageurl, array('addblock' => 1));
        $addablock = new \action_menu_link_secondary($addblockurl, 
            new \pix_icon('t/add', get_string('addablock', 'adaptivequiz'), 'moodle', array('class' => 'iconsmall', 'title' => '')),
            get_string('addablock', 'adaptivequiz'),
            array('class' => 'cm-edit-action questionbank', 'data-action' => 'questionbank'));
        $menu->add($addablock);
        
        return html_writer::tag('span', $this->render($menu),
            array('class' => 'add-menu-outer'));
    }
}
