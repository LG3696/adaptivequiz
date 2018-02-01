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
require_once($CFG->dirroot . '/lib/editorlib.php');

use \html_writer;

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
     * @param \block $block object containing all the block information.
     * @param \moodle_url $pageurl The URL of the page.
     * @param array $pagevars the variables from {@link question_edit_setup()}.
     * @return string HTML to output.
     */
    public function edit_page(\block $block, \moodle_url $pageurl, array $pagevars) {
        $output = '';

        $output .= html_writer::start_tag('form',
            array('method' => 'POST', 'id' => 'blockeditingform', 'action' => $pageurl->out()));
        $output .= html_writer::tag('input', '',
            array('type' => 'hidden', 'name' => 'cmid', 'value' => $pageurl->get_param('cmid')));
        $output .= html_writer::tag('input', '', array('type' => 'hidden', 'name' => 'bid', 'value' => $block->get_id()));
        $output .= html_writer::tag('input', '', array('type' => 'hidden', 'name' => 'save', 'value' => 1));
        if ($block->is_main_block()) {
            $output .= $this->heading(get_string('editingquizx', 'adaptivequiz', format_string($block->get_name())));
            $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'blockname', 'value' => $block->get_name()));
        }
        else {
            $namefield = html_writer::tag('input', '', array('type' => 'text', 'name' => 'blockname', 'value' => $block->get_name()));
            $output .= $this->heading(get_string('editingblock', 'adaptivequiz') . ' ' . $namefield);
        }

        if (!$block->is_main_block()) {
            $output .= $this->condition_block($block->get_condition(), $block->get_condition_candidates());
        }

        $output .= html_writer::start_tag('ul', array('id' => 'block-children-list'));

        $children = $block->get_children();
        foreach ($children as $child) {
            $output .= $this->block_elem($child, $pageurl, $pageurl->get_param('cmid'));
        }

        $category = question_get_category_id_from_pagevars($pagevars);

        $addmenu = $this->add_menu($block, $pageurl, $category);
        $output .= html_writer::tag('li', $addmenu);
        $output .= html_writer::end_tag('ul');

        if ($block->is_main_block()) {
            $output .= $this->feedback_block();
        }

        $output .= html_writer::tag('button', get_string('done', 'adaptivequiz'),
            array('type' => 'submit', 'name' => 'done', 'value' => 1));
        $output .= html_writer::end_tag('form');

        $output .= $this->question_chooser($pageurl, $category);
        $this->page->requires->js_call_amd('mod_adaptivequiz/questionchooser', 'init');

        $output .= $this->questionbank_loading();
        $this->page->requires->js_call_amd('mod_adaptivequiz/questionbank', 'init');

        $this->page->requires->js_call_amd('mod_adaptivequiz/addnewblock', 'init');

        if (!$block->is_main_block()) {
            $output .= $this->condition_type_chooser($block->get_condition_candidates());
            $this->page->requires->js_call_amd('mod_adaptivequiz/blockconditions', 'init');
        }

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
        // Description of the element.
        $elementhtml = '';
        $edithtml = '';

        $elementhtml = \html_writer::div($this->block_elem_desc($blockelem), 'blockelement');
        $edithtml = $this->element_edit_button($blockelem, $pageurl, $cmid);
        $removehtml = $this->element_remove_button($blockelem, $pageurl);
        $buttons = \html_writer::div($edithtml . $removehtml, 'blockelementbuttons');
        return html_writer::tag('li', html_writer::div($elementhtml . $buttons, 'blockelementline'));
    }

    /**
     * Render the description
     * @param \block_element $blockelem
     */
    protected function block_elem_desc(\block_element $blockelem) {
        $output = \html_writer::div($blockelem->get_name(), 'blockelementdescriptionname');
        if ($blockelem->is_block()) {
            $childrendescription = '';
            foreach ($blockelem->get_element()->get_children() as $child) {
                $childrendescription .= $this->block_elem_desc($child);
            }
            $output .= \html_writer::div($childrendescription, 'blockelementchildrendescription');
            return html_writer::div($output, 'blockelementdescription blockelementblock');
        }
        else {
            return html_writer::div($output, 'blockelementdescription');
        }
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
            return html_writer::tag('button',
                '<img src="' . $OUTPUT->pix_url($icon) . '" alt="' . $action . '" />',
                array('type' => 'submit', 'name' => 'edit', 'value' => $element->get_id()));
        } else {
            return '';
        }
    }

    /**
     * Outputs the remove button HTML for an element.
     *
     * @param \block_element $element the element to get the button for.
     * @param \moodle_url $pageurl The URL of the page.
     * @return string HTML to output.
     */
    public function element_remove_button($element, $pageurl) {
        $image = $this->pix_icon('t/delete', get_string('delete'));
        return html_writer::tag('button', $image,
            array('type' => 'submit', 'name' => 'delete', 'value' => $element->get_id()));
    }

    /**
     * Outputs the add menu HTML.
     *
     * @param \block $block object containing all the block information.
     * @param \moodle_url $pageurl The URL of the page.
     * @param int $category the id of the category for new questions.
     *
     * @return string HTML to output.
     */
    protected function add_menu(\block $block, \moodle_url $pageurl, $category) {
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

        // Button to add a question.
        $addaquestion = new \action_menu_link_secondary(
            new \moodle_url('/question/addquestion.php', $params),
            new \pix_icon('t/add', get_string('addaquestion', 'adaptivequiz'),
                'moodle', array('class' => 'iconsmall', 'title' => '')), get_string('addaquestion', 'adaptivequiz'),
            array('class' => 'cm-edit-action addquestion', 'data-action' => 'addquestion')
            );
        $menu->add($addaquestion);

        //Button to add question from question bank.
        $questionbank =  new \action_menu_link_secondary($pageurl,
            new \pix_icon('t/add', get_string('questionbank', 'adaptivequiz'), 'moodle',
                array('class' => 'iconsmall', 'title' => '')), get_string('questionbank', 'adaptivequiz'),
            array('class' => 'cm-edit-action questionbank', 'data-action' => 'questionbank',
                'data-cmid' => $block->get_quiz()->get_cmid()));
        $menu->add($questionbank);
        $menu->prioritise = true;

        // Button to add a block.
        $addblockurl = new \moodle_url($pageurl, array('addblock' => 1));
        $addablock = new \action_menu_link_secondary($addblockurl,
            new \pix_icon('t/add', get_string('addablock', 'adaptivequiz'), 'moodle', array('class' => 'iconsmall', 'title' => '')),
            get_string('addablock', 'adaptivequiz'),
            array('class' => 'cm-edit-action addnewblock'));
        $menu->add($addablock);

        return html_writer::tag('span', $this->render($menu),
            array('class' => 'add-menu-outer'));
    }

    /**
     * Renders the HTML for the condition block.
     *
     * @param \condition $condition the condition to be rendered.
     * @param array $candidates the block_elements the condition can depend on.
     *
     * @return string the HTML of the condition block.
     */
    public function condition_block(\condition $condition, $candidates) {
        $header = \html_writer::tag('h3', get_string('conditions', 'mod_adaptivequiz'), array('class' => 'conditionblockheader'));
        $conjunctionchooser = $this->conjunction_chooser($condition);
        $conditionlist = \html_writer::div($this->condition($condition, $candidates), 'conditionpartslist');
        $addcondition = \html_writer::tag('a', get_string('addacondition', 'mod_adaptivequiz'),
            array('href' => '#', 'class' => 'addblockcondition'));
        $container = $header . $conjunctionchooser . $conditionlist . $addcondition;
        return html_writer::div($container, 'conditionblock');
    }

    /**
     * Renders the HTML for the conjunction type chooser.
     *
     * @param \condition $condition the condition to render this chooser for.
     *
     * @return string the HTML of the chooser.
     */
    protected function conjunction_chooser(\condition $condition) {
        if ($condition->get_useand()) {
            $options = \html_writer::tag('option', get_string('all', 'adaptivequiz'), array('value' => 1, 'selected' => ''));
            $options .= \html_writer::tag('option', get_string('atleastone', 'adaptivequiz'), array('value' => 0));
        } else {
            $options = \html_writer::tag('option', get_string('all', 'adaptivequiz'), array('value' => 1));
            $options .= \html_writer::tag('option', get_string('atleastone', 'adaptivequiz'),
                array('value' => 0, 'selected' => ''));
        }

        $chooser = \html_writer::tag('select', $options, array('name' => 'use_and'));
        $output = \html_writer::tag('label', get_string('mustfullfill', 'adaptivequiz') . ' ' .
            $chooser . ' ' . get_string('oftheconditions', 'adaptivequiz'));
        return \html_writer::div(\html_writer::span($output, 'conjunctionchooserspan'));
    }

    /**
     * Renders the HTML for the condition type chooser.
     *
     * @param array $candidates the block_elements the condition can depend on.
     *
     * @return string the HTML of the condtion type chooser.
     */
    protected function condition_type_chooser($candidates) {
        $output = \html_writer::start_tag('form', array('action' => new \moodle_url('/mod/adaptivequiz/view.php'),
            'id' => 'chooserform', 'method' => 'get'));
        $output .= \html_writer::tag('input', '',
                array('type' => 'submit', 'name' => 'addpointscondition', 'class' => 'submitbutton',
                    'value' => get_string('addpointscondition', 'mod_adaptivequiz')));
        $output .= \html_writer::end_tag('form');
        $formdiv = \html_writer::div($output, 'choseform');
        $header = html_writer::div(get_string('choosecondtiontypetoadd', 'mod_adaptivequiz'), 'chooserheader hd');
        $dialogue = $header . \html_writer::div(\html_writer::div($formdiv, 'choosercontainer'), 'chooserdialogue');
        $container = html_writer::div($dialogue, '',
            array('id' => 'conditiontypechoicecontainer'));
        return html_writer::div($container, 'addcondition') .
            \html_writer::div(\html_writer::div($this->points_condition($candidates), 'conditionpart'), 'pointsconditioncontainer');
    }

    /**
     * Renders the HTML for a condition.
     *
     * @param \condition $condition the condition to render.
     * @param array $candidates the block_elements the condition can depend on.
     *
     * @return string the HTML of the condition.
     */
    protected function condition(\condition $condition, $candidates) {
        $output = '';
        foreach ($condition->get_parts() as $part) {
            $output .= $this->condition_part($candidates, $part);
        }
        return $output;
    }

    /**
     * Renders the HTML for a condition part.
     *
     * @param array $candidates the block_elements the condition can depend on.
     * @param \condition_part $part the part of the condition to render.
     *
     * @return string the HTML of the condition part.
     */
    protected function condition_part($candidates, \condition_part $part) {
        static $index = 0;
        $index += 1;
        $conditionpart = '';
        switch ($part->get_type()) {
            case \condition_part::WAS_DISPLAYED:
                // TODO: .
                break;
            default:
                $conditionpart = $this->points_condition($candidates, 'part' . $index, $part);
        }
        $conditionpart .= \html_writer::tag('input', '',
            array('class' => 'conditionid', 'name' => 'conditionparts[part' . $index . '][id]', 'value' => $part->get_id()));
        return \html_writer::div($conditionpart, 'conditionpart');
    }

    /**
     * Renders the HTML for the condition over question points.
     *
     * @param array $candidates the block_elements the condition can depend on.
     * @param string $index the index into the conditionparts array for this condition.
     * @param \condition_part|null $part hte condtion part to fill in or null.
     *
     * @return string the HTML of the points condition.
     */
    protected function points_condition($candidates, $index = '', $part = null) {
        $questionspan = \html_writer::tag('span', $this->question_selector($candidates, $index, $part));
        $condition = \html_writer::tag('label', get_string('grade', 'adaptivequiz') . ' ' . $questionspan,
            array('class' => 'conditionelement'));
        $comparatorspan = \html_writer::tag('span', $this->comparator_selector($index, $part));
        $condition .= ' ' . \html_writer::tag('label', get_string('mustbe', 'adaptivequiz') . ' ' . $comparatorspan,
            array('class' => 'conditionelement'));
        $value = 0;
        if ($part) {
            $value = $part->get_grade();
        }
        $condition .= ' ' . \html_writer::tag('input', '',
            array('class' => 'conditionelement conditionpoints', 'name' => 'conditionparts[' . $index . '][points]',
                'type' => 'number', 'value' => $value));

        $strdelete = get_string('delete');
        $image = $this->pix_icon('t/delete', $strdelete);
        $condition .= $this->action_link('#', $image, null, array('title' => $strdelete,
            'class' => 'cm-edit-action editing_delete element-remove-button conditionpartdelete', 'data-action' => 'delete'));
        $conditionspan = \html_writer::span($condition, 'conditionspan');
        $conditiondiv = \html_writer::div($conditionspan, 'pointscondition');
        return $conditiondiv;
    }

    /**
     * Renders the HTML for a dropdownbox of all questions, that this block can have conditions on.
     *
     * @param array $candidates the block_elements the condition can depend on.
     * @param string $index the index into the conditionparts array for this condition.
     * @param \condition_part|null $part the condition part used to fill in a value or null.
     *
     * @return string the HTML of the dropdownbox.
     */
    protected function question_selector($candidates, $index, $part) {
        $options = '';
        foreach ($candidates as $element) {
            $attributes = array('value' => $element->get_id());
            if ($part && $part->get_elementid() == $element->get_id()) {
                $attributes['selected'] = '';
            }
            $options .= \html_writer::tag('option', $element->get_name(), $attributes);
        }
        return \html_writer::tag('select', $options,
            array('class' => 'conditionquestion', 'name' => 'conditionparts[' . $index . '][question]'));
    }

    /**
     * Renders the HTML for a dropdownbox of all comparators that can be used in conditions.
     *
     * @param string $index the index into the conditionparts array for this condition.
     * @param \condition_part|null $part the condition part used to fill in a value or null.
     *
     * @return string the HTML of the dropdownbox.
     */
    protected function comparator_selector($index, $part = null) {
        if ($part) {
            $attributes = array();
            $attributes[\condition_part::LESS] = array('value' => \condition_part::LESS);
            $attributes[\condition_part::LESS_OR_EQUAL] = array('value' => \condition_part::LESS_OR_EQUAL);
            $attributes[\condition_part::GREATER] = array('value' => \condition_part::GREATER);
            $attributes[\condition_part::GREATER_OR_EQUAL] = array('value' => \condition_part::GREATER_OR_EQUAL);
            $attributes[\condition_part::EQUAL] = array('value' => \condition_part::EQUAL);
            $attributes[\condition_part::NOT_EQUAL] = array('value' => \condition_part::NOT_EQUAL);

            $attributes[$part->get_type()]['selected'] = '';

            $options = \html_writer::tag('option', '<', $attributes[\condition_part::LESS]);
            $options .= \html_writer::tag('option', '&le;', $attributes[\condition_part::LESS_OR_EQUAL]);
            $options .= \html_writer::tag('option', '>', $attributes[\condition_part::GREATER]);
            $options .= \html_writer::tag('option', '&ge;', $attributes[\condition_part::GREATER_OR_EQUAL]);
            $options .= \html_writer::tag('option', '=', $attributes[\condition_part::EQUAL]);
            $options .= \html_writer::tag('option', '&ne;', $attributes[\condition_part::NOT_EQUAL]);
        } else {
            $options = \html_writer::tag('option', '<', array('value' => \condition_part::LESS));
            $options .= \html_writer::tag('option', '&le;', array('value' => \condition_part::LESS_OR_EQUAL));
            $options .= \html_writer::tag('option', '>', array('value' => \condition_part::GREATER));
            $options .= \html_writer::tag('option', '&ge;', array('value' => \condition_part::GREATER_OR_EQUAL));
            $options .= \html_writer::tag('option', '=', array('value' => \condition_part::EQUAL));
            $options .= \html_writer::tag('option', '&ne;', array('value' => \condition_part::NOT_EQUAL));
        }
        return \html_writer::tag('select', $options,
            array('class' => 'conditiontype', 'name' => 'conditionparts[' . $index . '][type]'));
    }

    /**
     * Renders the HTML for the question type chooser dialogue.
     *
     * @param \moodle_url $returnurl the url to return to after creating the question.
     * @param int $category the id of the category for the question.
     * @return string the HTML of the dialogue.
     */
    public function question_chooser(\moodle_url $returnurl, $category) {
        $container = html_writer::div(print_choose_qtype_to_add_form(array('returnurl' => $returnurl->out_as_local_url(false),
            'cmid' => $returnurl->get_param('cmid'), 'appendqnumstring' => 'addquestion', 'category' => $category),
            null, false), '', array('id' => 'qtypechoicecontainer'));
        return html_writer::div($container, 'createnewquestion');
    }

    /**
     * Renders the HTML for the question bank loading icon.
     *
     * @return string the HTML div of the icon.
     */
    public function questionbank_loading() {
        return html_writer::div(html_writer::div(html_writer::empty_tag('img',
            array('alt' => 'loading', 'class' => 'loading-icon', 'src' => $this->pix_url('i/loading'))),
            'questionbankloading'), 'questionbankloadingcontainer',
            array('data-title' => get_string('addfromquestionbank', 'adaptivequiz')));
    }



    /**
     * Return the contents of the question bank, to be displayed in the question-bank pop-up.
     *
     * @param \mod_adaptivequiz\question\bank\custom_view $questionbank the question bank view object.
     * @param array $pagevars the variables from {@link \question_edit_setup()}.
     * @return string HTML to output / send back in response to an AJAX request.
     */
    public function question_bank_contents(\mod_adaptivequiz\question\bank\custom_view $questionbank, array $pagevars) {
        return $questionbank->render('editq', $pagevars['page'], $pagevars['qperpage'], $pagevars['cat'], true, false, false);
    }

    /**
     *
     */
    public function feedback_block() {
        $header = html_writer::tag('h3', get_string('feedback', 'mod_adaptivequiz'), array('class' => 'feedbackheader'));
        $addbutton = html_writer::tag('button', get_string('addfeedback', 'adaptivequiz'),
            array('type' => 'submit', 'name' => 'addfeedback', 'value' => 1));
        $container = $header . $addbutton;
        return html_writer::div($container, 'feedbackblock');
    }

    /**
     * Render the feedback edit page
     *
     * @param \feedback_block $block object containing all the feedback block information.
     * @param \moodle_url $pageurl The URL of the page.
     * @param array $pagevars the variables from {@link question_edit_setup()}.
     * @return string HTML to output.
     */
    public function edit_feedback_page(\feedback_block $block, \moodle_url $pageurl, array $pagevars) {
        $candidates = $block->get_quiz()->get_questions();
        $output = '';

        $output .= html_writer::start_tag('form',
            array('method' => 'POST', 'id' => 'blockeditingform', 'action' => $pageurl->out()));
        $output .= html_writer::tag('input', '',
            array('type' => 'hidden', 'name' => 'cmid', 'value' => $pageurl->get_param('cmid')));
        $output .= html_writer::tag('input', '', array('type' => 'hidden', 'name' => 'bid', 'value' => $block->get_id()));
        $output .= html_writer::tag('input', '', array('type' => 'hidden', 'name' => 'save', 'value' => 1));

        $namefield = html_writer::tag('input', '', array('type' => 'text', 'name' => 'blockname', 'value' => $block->get_name()));
        $output .= $this->heading(get_string('editingfeedback', 'adaptivequiz') . ' ' . $namefield);

        $output .= $this->uses_block($block);

        $output .= $this->condition_block($block->get_condition(), $candidates);

        $output .= $this->feedback_editor($block->get_feedback_text());

        $output .= html_writer::tag('button', get_string('done', 'adaptivequiz'),
            array('type' => 'submit', 'name' => 'done', 'value' => 1));
        $output .= html_writer::end_tag('form');

        $output .= $this->condition_type_chooser($candidates);
        $this->page->requires->js_call_amd('mod_adaptivequiz/blockconditions', 'init');

        return $output;
    }

    /**
     * Outputs the HTML to choose which questions feedback is replaced by the feedback block.
     *
     * @param \feedback_block $block the block for which to generate the HTML.
     *
     * @return string HTML to output.
     */
    public function uses_block(\feedback_block $block) {
        $output = '';

        $output .= \html_writer::tag('h3', get_string('usesquestions', 'mod_adaptivequiz'), array('class' => 'usesquestionblockheader'));

        $output .= \html_writer::start_div('usedquestions');
        foreach ($block->get_used_question_instances() as $instance) {
            $output .= $this->uses_question($block, $instance);
        }
        $output .= \html_writer::end_div();

        $output .= \html_writer::link('#', get_string('addusedquestion', 'adaptivequiz'), array('class' =>'addusedquestion'));

        $output .= \html_writer::div($this->uses_question($block), 'usesquestioncontainer');

        $this->page->requires->js_call_amd('mod_adaptivequiz/feedback', 'init');
        return $output;
    }

    /**
     * Outputs the HTML for a question whose feedback is replaced by the feedback block.
     *
     * @param \feedback_block $block the block for which to generate the HTML.
     * @param int $index the index this selector should have.
     * @param null|\block_element $selected the question for which to generate the HTML.
     *
     * @return string HTML to output.
     */
    public function uses_question(\feedback_block $block, \block_element $question = null) {
        static $index = 64; // 'A' - 1
        $index += 1;

        $content = '';
        $content .= \html_writer::div(chr($index), 'usesquestionletter');
        $content .= $this->uses_selector($block, $question);
        return \html_writer::div($content, 'usesquestion');
    }

    /**
     * Outputs the HTML to choose a question whose feedback is replaced by the feedback block.
     *
     * @param \feedback_block $block the block for which to generate the HTML.
     * @param null|\block_element $selected the selected option.
     *
     * @return string HTML to output.
     */
    public function uses_selector(\feedback_block $block, \block_element $selected = null) {
        $options = '';

        static $index = 0;
        $index += 1;

        foreach ($block->get_quiz()->get_questions() as $element) {
            $attributes = array('value' => $element->get_id());
            if ($selected && $selected->get_id() == $element->get_id()) {
                $attributes['selected'] = '';
            }
            $options .= \html_writer::tag('option', $element->get_name(), $attributes);
        }
        return \html_writer::tag('select', $options,
            array('class' => 'usesquestionselector', 'name' => 'usesquestions[' . $index . ']'));
    }

    /**
     * Generates the HTML for the feeback text editor.
     *
     * @param string $feedbacktext the feedback text to put in at the start.
     *
     * @return string the HTML output.
     */
    public function feedback_editor($feedbacktext = '') {
        $heading = $this->heading_with_help(get_string('feedbacktext', 'adaptivequiz'), 'feedbackquestion', 'adaptivequiz');
        //$heading = $this->heading(get_string('feedbacktext', 'adaptivequiz'));
        $editor = editors_get_preferred_editor();
        $editor->set_text($feedbacktext);
        $editor->use_editor('feedbacktext');
        $editorhtml = \html_writer::tag('textarea', s($feedbacktext),
            array('id' => 'feedbacktext', 'name' => 'feedbacktext', 'rows' => 15, 'cols' => 80));
        return \html_writer::div($heading . \html_writer::div($editorhtml), 'feedbacktext');
    }
}
