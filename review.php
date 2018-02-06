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
 * This page prints a review of a particular quiz attempt.
 *
 * @package   mod_adaptivequiz
 * @copyright  2018 Jana Vatter <jana.vatter@stud.tu-darmstadt.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/attemptlib.php');

// Get submitted parameters.
$attemptid = required_param('attempt', PARAM_INT);

$attempt = attempt::load($attemptid);
$cmid = $attempt->get_quiz()->get_cmid();

if (!$cm = get_coursemodule_from_id('adaptivequiz', $cmid)) {
    print_error('invalidcoursemodule');
}
if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error("coursemisconf");
}

// Check login.
require_login($course, false, $cm);

$options = new question_display_options();
$options->feedback = question_display_options::VISIBLE;
$options->generalfeedback = question_display_options::VISIBLE;
$options->marks = question_display_options::MARK_AND_MAX;
$options->correctness = question_display_options::VISIBLE;
$options->flags = question_display_options::HIDDEN;
$options->rightanswer = question_display_options::VISIBLE;

$adaptivequiz = adaptivequiz::load($cm->instance);
$PAGE->set_url($attempt->review_url());
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(format_string($adaptivequiz->get_main_block()->get_name()));
$PAGE->set_heading($course->fullname);

$timestart = $attempt->get_start_time();
$timefinish = $attempt->get_finish_time();
$timetaken = format_time($timefinish - $timestart);

$summarydata = array();
$summarydata['startedon'] = array(
    'title'   => get_string('startedon', 'adaptivequiz'),
    'content' => userdate($timestart));

$summarydata['state'] = array(
    'title'   => get_string('attemptstate', 'adaptivequiz'),
    'content' => $attempt->get_state());

$summarydata['completedon'] = array(
    'title'   => get_string('completedon', 'adaptivequiz'),
    'content' => userdate($timefinish));

$summarydata['timetaken'] = array(
    'title'   => get_string('timetaken', 'adaptivequiz'),
    'content' => $timetaken);

$a = new stdClass();
$a->grade = $attempt->get_quba()->get_total_mark();
$a->maxgrade = $adaptivequiz->get_maxgrade();
$summarydata['marks'] = array(
    'title'   => get_string('marks', 'adaptivequiz'),
    'content' => get_string('outofshort', 'adaptivequiz', $a));

$output = $PAGE->get_renderer('mod_adaptivequiz');

$feedback = feedback::get_feedback($attempt->get_quiz());

echo $OUTPUT->header();

echo $output->review_page($attempt, $options, $summarydata, $feedback);

echo $OUTPUT->footer();
