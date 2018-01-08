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
 * Displays a page to edit an adaptive quiz.
 *
 * @package    mod_adaptivequiz
 * @copyright  2017 Luca Gladiator <lucamarius.gladiator@stud.tu-darmstadt.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/adaptivequiz/locallib.php');
require_once($CFG->dirroot . '/question/editlib.php');

$blockid = optional_param('bid', 0, PARAM_INT);
$addquestion = optional_param('addquestion', 0, PARAM_INT);
$addblock = optional_param('addblock', 0, PARAM_INT);
$save = optional_param('save', 0, PARAM_INT);

list($thispageurl, $contexts, $cmid, $cm, $quiz, $pagevars) =
    question_edit_setup('editq', '/mod/adaptivequiz/edit.php', true);

require_capability('mod/adaptivequiz:manage', $contexts->lowest());

// if no block id was passed, we default to editing the main block of the quiz.
if (!$blockid) {
    $blockid = $quiz->mainblock;
}

$thispageurl->param('bid', $blockid);

$PAGE->set_url($thispageurl);

$adaptivequiz = adaptivequiz::load($quiz->id);
$block = block::load($adaptivequiz, $blockid);

if ($save) {
    $name = required_param('blockname', PARAM_TEXT);
    $block->set_name($name);
    if (array_key_exists('conditionparts', $_POST)) {
        $block->get_condition()->update($_POST['conditionparts']);
    }
    $useand = optional_param('use_and', null, PARAM_INT);
    if (!is_null($useand)) {
        $block->get_condition()->set_use_and($useand);
    }
    // Take different actions, depending on which submit button was clicked.
    if (optional_param('done', 0, PARAM_INT)) {
        if ($parentid = $block->get_parentid()) {
            $nexturl = new moodle_url('/mod/adaptivequiz/edit.php', array('cmid' => $cmid, 'bid' => $parentid));
        }
        else {
            $nexturl = new moodle_url('/mod/adaptivequiz/view.php', array('id' => $cmid));
        }
    }
    else if ($delete = optional_param('delete', 0, PARAM_INT)) {
        $block->remove_child($delete);
        $nexturl = $thispageurl;
    }
    else if ($edit = optional_param('edit', 0, PARAM_INT)) {
        $element = block_element::load($adaptivequiz, $edit);
        $elementparams = array('cmid' => $cmid, 'returnurl' => $thispageurl->out_as_local_url(false));
        $nexturl = $element->get_edit_url($elementparams);
    }
    else {
        $nexturl = new moodle_url('/mod/adaptivequiz/view.php', array('id' => $cmid));
    }
    redirect($nexturl);
}

if ($addquestion) {
    $block->add_question($addquestion);
}

if ($addblock) {
    $newblock = block::create($adaptivequiz, get_string('blockname', 'adaptivequiz'));
    $block->add_subblock($newblock);
    $newblockurl = new moodle_url('/mod/adaptivequiz/edit.php', array('cmid' => $cmid, 'bid' => $newblock->get_id()));
    redirect($newblockurl);
}

$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('editingblockx', 'adaptivequiz', format_string($block->get_name())));

$output = $PAGE->get_renderer('mod_adaptivequiz', 'edit');

echo $OUTPUT->header();

echo $output->edit_page($block, $thispageurl, $pagevars);

echo $OUTPUT->footer();