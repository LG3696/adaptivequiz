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
 * This page allows the teacher to enter a manual grade for a particular question.
 * This page is expected to only be used in a popup window.
 *
 * @package    mod_adaptivequiz
 * @copyright  2017 Luca Gladiator <lucamarius.gladiator@stud.tu-darmstadt.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/adaptivequiz/locallib.php');

$attemptid = required_param('attempt', PARAM_INT);
$slot = required_param('slot', PARAM_INT);

$url = new moodle_url('/mod/adaptivequiz/comment.php', array('attempt' => $attemptid, 'slot' => $slot));

$PAGE->set_url($url);

$attempt = attempt::load($attemptid);
$quiz = $attempt->get_quiz();
list($course, $cm) = get_course_and_cm_from_cmid($quiz->get_cmid());

// Check login.
require_login($cm->course, false, $cm);
$context = $quiz->get_context();
require_capability('mod/adaptivequiz:grade', $context);

$student = $DB->get_record('user', array('id' => $attempt->get_userid()));
$quba = $attempt->get_quba();
$question = $quba->get_question($slot);

$options = new question_display_options();
$options->marks = question_display_options::MARK_AND_MAX;
$options->flags = question_display_options::HIDDEN;
$options->hide_all_feedback();
$options->manualcomment = question_display_options::EDITABLE;

// Can only grade finished attempts.
/*if (!$attempt->is_finished()) {
    print_error('attemptnotclosed', 'adaptivequiz');
}*/

// Print the page header.
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('manualgradequestion', 'adaptivequiz', array(
        'question' => format_string($question->name),
        'quiz' => format_string($quiz->get_name()), 'user' => fullname($student))));
$PAGE->set_heading($course->fullname);
$output = $PAGE->get_renderer('mod_adaptivequiz');

// Prepare summary information about this question attempt.
$summarydata = array();

// Student name.
$userpicture = new user_picture($student);
$userpicture->courseid = $course->id;
$summarydata['user'] = array(
    'title'   => $userpicture,
    'content' => new action_link(new moodle_url('/user/view.php', array(
            'id' => $student->id, 'course' => $course->id)),
            fullname($student, true)),
);

// Quiz name.
$summarydata['quizname'] = array(
    'title'   => get_string('modulename', 'adaptivequiz'),
    'content' => format_string($quiz->get_name()),
);

// Question name.
$summarydata['questionname'] = array(
    'title'   => get_string('question', 'adaptivequiz'),
    'content' => $question->name,
);

echo $OUTPUT->header();

// Process any data that was submitted.
if (data_submitted() && confirm_sesskey()) {
    if (optional_param('submit', false, PARAM_BOOL) && question_engine::is_manual_grade_in_range($quba->get_id(), $slot)) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $quba->process_all_actions(time());
        question_engine::save_questions_usage_by_activity($quba);

        $transaction->allow_commit();

        $attempt->update_grade();

        // Log this action.
        /*TODO: $params = array(
            'objectid' => $attemptobj->get_question_attempt($slot)->get_question()->id,
            'courseid' => $attemptobj->get_courseid(),
            'context' => context_module::instance($attemptobj->get_cmid()),
            'other' => array(
                'quizid' => $attemptobj->get_quizid(),
                'attemptid' => $attemptobj->get_attemptid(),
                'slot' => $slot
            )
        );
        $event = \mod_quiz\event\question_manually_graded::create($params);
        $event->trigger();*/

        echo $output->notification(get_string('changessaved'), 'notifysuccess');
        close_window(2, true);
        die;
    }
}

echo $output->grade_question_page($attempt, $slot, $options, $summarydata);

// End of the page.
echo $OUTPUT->footer();
