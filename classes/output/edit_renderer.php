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
 * Defines the edit renderer for the adaptive quiz module.
 *
 * @package    mod_adaptivequiz
 * @copyright  2017 Jana Vatter <jana.vatter@stud.tu-darmstadt.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_adaptivequiz\output;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/adaptivequiz/locallib.php');

use \html_writer;
use single_button;

/**
 * The renderer for the adaptive quiz module.
 *
 * @copyright  2017 Jana Vatter <jana.vatter@stud.tu-darmstadt.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_renderer extends \plugin_renderer_base {
    /**
     * Render the edit page
     *
     * @param \block $blockobj object containing all the block information.
     * @param \moodle_url $pageurl The URL of the page.
     * @param int $quizid The ID of the quiz.
     * @param array $pagevars the variables from {@link question_edit_setup()}.
     * @return string HTML to output.
     */
    public function edit_page(\block $blockobj, \moodle_url $pageurl, $quizid, array $pagevars) {
        $output = '';

        $output .= html_writer::start_tag('form', array('action' => $pageurl->out()));
        $output .= html_writer::tag('input', '', array('type' => 'hidden', 'name' => 'cmid', 'value' => $pageurl->get_param('cmid')));
        $output .= html_writer::tag('input', '', array('type' => 'hidden', 'name' => 'bid', 'value' => $blockobj->get_id()));
        $output .= html_writer::tag('input', '', array('type' => 'hidden', 'name' => 'done', 'value' => 1));
        $namefield = html_writer::tag('input', '', array('type' => 'text', 'name' => 'blockname', 'value' => $blockobj->get_name()));
        $output .= $this->heading(get_string('editingblock', 'adaptivequiz') . ' ' . $namefield);

        if (/*TODO:!$blockobj->is_main_block()*/true) {
            $output .= $this->condition_block();
        }

        $output .= html_writer::start_tag('ul', array('id' => 'block-children-list'));

        $children = $blockobj->get_children();
        foreach($children as $child) {
            $output .= $this->block_elem($child, $pageurl, $pageurl->get_param('cmid'));
        }

        $category = question_get_category_id_from_pagevars($pagevars);

        $addmenu = $this->add_menu($pageurl, $category);
        $output .= html_writer::tag('li', $addmenu);
        $output .= html_writer::end_tag('ul');

        $output .= html_writer::tag('button', get_string('done', 'adaptivequiz'));
        $output .= html_writer::end_tag('form');

        $output .= $this->question_chooser($pageurl, $category);
        $this->page->requires->js_call_amd('mod_adaptivequiz/questionchooser', 'init');
        $output .= $this->condition_type_chooser();
        $this->page->requires->js_call_amd('mod_adaptivequiz/blockconditions', 'init');

        return $output;
    }

    /**
     * Render one element of a block.
     *
     * @param \block_element $blockelem An element of a block.
     * @param \moodle_url $pageurl The URL of the page.
     * @param int $cmid the course module id of the quiz.
     * @return string HTML to display this element.
     */
    public function block_elem(\block_element $blockelem, $pageurl, $cmid) {
        //Description of the element.
        $element_html = '';
        $edit_html = '';

        $element_html = $blockelem->get_name();
        $edit_html = $this->element_edit_button($blockelem, $pageurl, $cmid);
        $remove_html = $this->element_remove_button($blockelem, $pageurl);
        return html_writer::tag('li', $element_html . $edit_html . $remove_html);
    }

    /**
     * Outputs the edit button HTML for an element.
     *
     * @param \block_element $element the element to get the button for.
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
            if ($returnurl instanceof \moodle_url) {
                $returnurl = $returnurl->out_as_local_url(false);
            }
            $elementparams = array('cmid' => $cmid, 'returnurl' => $returnurl);
            $elementurl = $element->get_edit_url($elementparams);
            return '<a title="' . $action . '" href="' . $elementurl->out() . '" class="elementeditbutton element-edit-button"><img src="' .
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
     * @param \block_element $element the element to get the button for.
     * @return string HTML to output.
     */
    public function element_remove_button($element, $pageurl) {
        $url = new \moodle_url($pageurl, array('sesskey' => sesskey(), 'remove' => $element->get_id()));
        $strdelete = get_string('delete');

        $image = $this->pix_icon('t/delete', $strdelete);
        return $this->action_link($url, $image, null, array('title' => $strdelete,
            'class' => 'cm-edit-action editing_delete element-remove-button', 'data-action' => 'delete'));
    }

    /**
     * Outputs the add menu HTML.
     *
     * @param \moodle_url $pageurl The URL of the page.
     * @param int $category the id of the category for new questions.
     *
     * @return string HTML to output.
     */
    protected function add_menu(\moodle_url $pageurl, $category) {
        $menu = new \action_menu();
        $menu->set_alignment(\action_menu::TL, \action_menu::TL);
        $trigger = html_writer::tag('span', get_string('add', 'adaptivequiz'));
        $menu->set_menu_trigger($trigger);
        // The menu appears within an absolutely positioned element causing width problems.
        // Make sure no-wrap is set so that we don't get a squashed menu.
        $menu->set_nowrap_on_items(true);
        $params = array('returnurl' => $pageurl->out_as_local_url(false),
            'category' => $category,
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

    /**
     * Renders the HTML for the condition block.
     *
     * @return string the HTML of condition block.
     */
    public function condition_block() {
        $header = \html_writer::tag('h3', get_string('conditions', 'mod_adaptivequiz'), array('class' => 'conditionblockheader'));
        $addcondition = \html_writer::tag('a', get_string('addacondition', 'mod_adaptivequiz'), array('href' => '#', 'class' => 'addblockcondition'));
        $container = $header . $addcondition;
        return html_writer::div($container, 'conditionblock');
    }

    /**
     * Renders the HTML for the condition type chooser.
     *
     * @return string the HTML of the condtion type chooser.
     */
    protected function condition_type_chooser() {
        $output = \html_writer::start_tag('form', array('action' => new \moodle_url('/mod/adaptivequiz/view.php'),
            'id' => 'chooserform', 'method' => 'get'));
        $output .= \html_writer::tag('input', '',
                array('type' => 'submit', 'name' => 'addpointscondition', 'class' => 'submitbutton', 'value' => get_string('addpointscondition', 'mod_adaptivequiz')));
        $output .= \html_writer::end_tag('form');
        $formdiv = \html_writer::div($output, 'choseform');
        $header = html_writer::div(get_string('choosecondtiontypetoadd', 'mod_adaptivequiz'), 'chooserheader hd');
        $dialogue = $header . \html_writer::div(\html_writer::div($formdiv, 'choosercontainer'), 'chooserdialogue');
        $container = html_writer::div($dialogue, '',
            array('id' => 'conditiontypechoicecontainer'));
        return html_writer::div($container, 'addcondition') . $this->points_condition();
    }

    /**
     * Renders the HTML for the condition over question points.
     *
     * @return string the HTML of points condition.
     */
    protected function points_condition() {
        $condition = 'blablabla';
        $conditiondiv = \html_writer::div($condition, 'pointscondition');
        return \html_writer::div($conditiondiv, 'pointsconditioncontainer');
    }

    /**
     * Renders the HTML for the question type chooser dialogue.
     *
     * @param \moodle_url $returnurl the url to return to after creating the question.
     * @param int $category the id of the category for the question.
     * @return string the HTML of the dialogue.
     */
    public function question_chooser(\moodle_url $returnurl, $category) {
        $container = html_writer::div(print_choose_qtype_to_add_form(array('returnurl' => $returnurl->out_as_local_url(false), 'cmid' => $returnurl->get_param('cmid'), 'appendqnumstring' => 'addquestion', 'category' => $category), null, false), '',
            array('id' => 'qtypechoicecontainer'));
        return html_writer::div($container, 'createnewquestion');
    }
}
