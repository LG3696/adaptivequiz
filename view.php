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
 * Prints a particular adaptive quiz
 *
 * @package    mod_adaptivequiz
 * @copyright  2017 Luca Gladiator <lucamarius.gladiator@stud.tu-darmstadt.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->dirroot . '/mod/adaptivequiz/renderer.php');


$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // ... adaptivequiz instance ID - it should be named as the first character of the module.

if ($id) {
    $cm         = get_coursemodule_from_id('adaptivequiz', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $adaptivequiz  = $DB->get_record('adaptivequiz', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $adaptivequiz  = $DB->get_record('adaptivequiz', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $adaptivequiz->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$event = \mod_adaptivequiz\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $adaptivequiz);
$event->trigger();

$context = context_module::instance($id);
$quiz = adaptivequiz::load($adaptivequiz->id);
$mainblock = $quiz->get_main_block();
$canpreview = has_capability('mod/adaptivequiz:preview', $context);
$canattempt = has_capability('mod/adaptivequiz:attempt', $context);
$viewobj = new mod_adaptivequiz_view_object();

$viewobj->cmid = $id;
$viewobj->quizhasquestions = $mainblock->has_questions();
$viewobj->preventmessages = array();
$viewobj->canmanage = has_capability('mod/adaptivequiz:manage', $context);

// TODO: unfinished check
$unfinished = false;

if (!$viewobj->quizhasquestions) {
    $viewobj->buttontext = '';
} else {
    if ($unfinished) {
        if ($canattempt) {
            $viewobj->buttontext = get_string('continueattemptquiz', 'adaptivequiz');
        } else if ($canpreview) {
            $viewobj->buttontext = get_string('continuepreview', 'adaptivequiz');
        }

    } else {
        if ($canattempt) {
            if ($viewobj->numattempts == 0) {
                $viewobj->buttontext = get_string('attemptquiznow', 'adaptivequiz');
            } else {
                $viewobj->buttontext = get_string('reattemptquiz', 'adaptivequiz');
            }

        } else if ($canpreview) {
            $viewobj->buttontext = get_string('previewquiznow', 'adaptivequiz');
        }
    }
}

// Print the page header.

$PAGE->set_url('/mod/adaptivequiz/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($adaptivequiz->name));
$PAGE->set_heading(format_string($course->fullname));
$output = $PAGE->get_renderer('mod_adaptivequiz');

/*
 * Other things you may want to set - remove if not needed.
 * $PAGE->set_cacheable(false);
 * $PAGE->set_focuscontrol('some-html-id');
 * $PAGE->add_body_class('adaptivequiz-'.$somevar);
 */

// Output starts here.
echo $OUTPUT->header();

echo $output->view_page($adaptivequiz, $viewobj);

// Finish the page.
echo $OUTPUT->footer();
