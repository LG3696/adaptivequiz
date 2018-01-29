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
 * This script displays a particular page of a quiz attempt that is in progress.
 *
 * @package   mod_adaptivequiz
 * @copyright  2017 Jana Vatter <jana.vatter@stud.tu-darmstadt.de>
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

// TODO: clean handling
if ($attempt->is_finished()) {
    $timenow = time();
    $attempt->finish_attempt($timenow);
    redirect($attempt->review_url());
}

$adaptivequiz = adaptivequiz::load($cm->instance);
$PAGE->set_url($attempt->attempt_url());
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(format_string($adaptivequiz->get_main_block()->get_name()));
$PAGE->set_heading($course->fullname);
$output = $PAGE->get_renderer('mod_adaptivequiz');

$options = new question_display_options();

echo $OUTPUT->header();
echo $output->attempt_page($attempt, $attempt->get_current_slot(), $options, $cmid);
echo $OUTPUT->footer();
