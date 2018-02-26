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
 * This file defines the adaptivequiz grades table.
 *
 * @package    mod_adaptivequiz
 * @copyright  2017 Luca Gladiator <lucamarius.gladiator@stud.tu-darmstadt.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_adaptivequiz\report;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/report/attemptsreport_table.php');


/**
 * This is a table subclass for displaying the adaptivequiz grades report.
 *
 * @copyright  2017 Luca Gladiator <lucamarius.gladiator@stud.tu-darmstadt.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class overview_table extends attempts_table {

    protected $regradedqs = array();

    /**
     * Constructor
     * @param \adaptivequiz $quiz
     * @param \context $context
     * @param string $qmsubselect
     * @param overview_options $options
     * @param array $groupstudents
     * @param array $students
     * @param array $questions
     * @param \moodle_url $reporturl
     */
    public function __construct(\adaptivequiz $quiz, $context, $qmsubselect,
        overview_options $options, $groupstudents, $students, $questions, $reporturl) {
            parent::__construct('mod-quiz-report-overview-report', $quiz , $context,
                $qmsubselect, $options, $groupstudents, $students, $questions, $reporturl);
    }

    public function build_table() {
        global $DB;

        if (!$this->rawdata) {
            return;
        }

        $this->strtimeformat = str_replace(',', ' ', get_string('strftimedatetime'));
        parent::build_table();

        // End of adding the data from attempts. Now add averages at bottom.
        $this->add_separator();
/*TODO
        if ($this->groupstudents) {
            $this->add_average_row(get_string('groupavg', 'grades'), $this->groupstudents);
        }

        if ($this->students) {
            $this->add_average_row(get_string('overallaverage', 'grades'), $this->students);
        }*/
    }
// TODO:
//     /**
//      * Add an average grade over the attempts of a set of users.
//      * @param string $label the title ot use for this row.
//      * @param array $users the users to average over.
//      */
//     protected function add_average_row($label, $users) {
//         global $DB;

//         list($fields, $from, $where, $params) = $this->base_sql($users);
//         $record = $DB->get_record_sql("
//                 SELECT AVG(quiza.sumgrades) AS grade, COUNT(quiza.sumgrades) AS numaveraged
//                   FROM $from
//                  WHERE $where", $params);
//         $record->grade = quiz_rescale_grade($record->grade, $this->quiz, false);

//         if ($this->is_downloading()) {
//             $namekey = 'lastname';
//         } else {
//             $namekey = 'fullname';
//         }
//         $averagerow = array(
//             $namekey    => $label,
//             'sumgrades' => $this->format_average($record),
//             'feedbacktext'=> strip_tags(quiz_report_feedback_for_grade(
//                 $record->grade, $this->quiz->id, $this->context))
//         );

//         if ($this->options->slotmarks) {
//             $dm = new question_engine_data_mapper();
//             $qubaids = new qubaid_join($from, 'quiza.uniqueid', $where, $params);
//             $avggradebyq = $dm->load_average_marks($qubaids, array_keys($this->questions));

//             $averagerow += $this->format_average_grade_for_questions($avggradebyq);
//         }

//         $this->add_data_keyed($averagerow);
//     }

//     /**
//      * Helper userd by {@link add_average_row()}.
//      * @param array $gradeaverages the raw grades.
//      * @return array the (partial) row of data.
//      */
//     protected function format_average_grade_for_questions($gradeaverages) {
//         $row = array();

//         if (!$gradeaverages) {
//             $gradeaverages = array();
//         }

//         foreach ($this->questions as $question) {
//             if (isset($gradeaverages[$question->slot]) && $question->maxmark > 0) {
//                 $record = $gradeaverages[$question->slot];
//                 $record->grade = quiz_rescale_grade(
//                     $record->averagefraction * $question->maxmark, $this->quiz, false);

//             } else {
//                 $record = new stdClass();
//                 $record->grade = null;
//                 $record->numaveraged = 0;
//             }

//             $row['qsgrade' . $question->slot] = $this->format_average($record, true);
//         }

//         return $row;
//     }

//     /**
//      * Format an entry in an average row.
//      * @param object $record with fields grade and numaveraged
//      */
//     protected function format_average($record, $question = false) {
//         if (is_null($record->grade)) {
//             $average = '-';
//         } else if ($question) {
//             $average = quiz_format_question_grade($this->quiz, $record->grade);
//         } else {
//             $average = quiz_format_grade($this->quiz, $record->grade);
//         }

//         if ($this->download) {
//             return $average;
//         } else if (is_null($record->numaveraged) || $record->numaveraged == 0) {
//             return html_writer::tag('span', html_writer::tag('span',
//                 $average, array('class' => 'average')), array('class' => 'avgcell'));
//         } else {
//             return html_writer::tag('span', html_writer::tag('span',
//                 $average, array('class' => 'average')) . ' ' . html_writer::tag('span',
//                     '(' . $record->numaveraged . ')', array('class' => 'count')),
//                 array('class' => 'avgcell'));
//         }
//     }

//     public function col_sumgrades($attempt) {
//         if ($attempt->state != quiz_attempt::FINISHED) {
//             return '-';
//         }

//         $grade = quiz_rescale_grade($attempt->sumgrades, $this->quiz);
//         if ($this->is_downloading()) {
//             return $grade;
//         }

//         if (isset($this->regradedqs[$attempt->usageid])) {
//             $newsumgrade = 0;
//             $oldsumgrade = 0;
//             foreach ($this->questions as $question) {
//                 if (isset($this->regradedqs[$attempt->usageid][$question->slot])) {
//                     $newsumgrade += $this->regradedqs[$attempt->usageid]
//                     [$question->slot]->newfraction * $question->maxmark;
//                     $oldsumgrade += $this->regradedqs[$attempt->usageid]
//                     [$question->slot]->oldfraction * $question->maxmark;
//                 } else {
//                     $newsumgrade += $this->lateststeps[$attempt->usageid]
//                     [$question->slot]->fraction * $question->maxmark;
//                     $oldsumgrade += $this->lateststeps[$attempt->usageid]
//                     [$question->slot]->fraction * $question->maxmark;
//                 }
//             }
//             $newsumgrade = quiz_rescale_grade($newsumgrade, $this->quiz);
//             $oldsumgrade = quiz_rescale_grade($oldsumgrade, $this->quiz);
//             $grade = html_writer::tag('del', $oldsumgrade) . '/' .
//                 html_writer::empty_tag('br') . $newsumgrade;
//         }
//         return html_writer::link(new moodle_url('/mod/quiz/review.php',
//             array('attempt' => $attempt->attempt)), $grade,
//             array('title' => get_string('reviewattempt', 'quiz')));
//     }

    /**
     * @param string $colname the name of the column.
     * @param object $attempt the row of data - see the SQL in display() in
     * mod/quiz/report/overview/report.php to see what fields are present,
     * and what they are called.
     * @return string the contents of the cell.
     */
    public function other_cols($colname, $attempt) {
        if (!preg_match('/^qsgrade(\d+)$/', $colname, $matches)) {
            return null;
        }
        $slot = $matches[1];

        $question = $this->questions[$slot];
        if (!isset($this->lateststeps[$attempt->usageid][$slot])) {
            return '-';
        }

        $stepdata = $this->lateststeps[$attempt->usageid][$slot];
        $state = \question_state::get($stepdata->state);

        if ($question->defaultmark == 0) {
            $grade = '-';
        } else if (is_null($stepdata->fraction)) {
            if ($state == \question_state::$needsgrading) {
                $grade = get_string('requiresgrading', 'question');
            } else {
                $grade = '-';
            }
        } else {
            $grade = $this->quiz->format_grade($stepdata->fraction * $question->defaultmark);
        }

        if ($this->is_downloading()) {
            return $grade;
        }

        return $this->make_review_link($grade, $attempt, $slot);
    }

    protected function requires_latest_steps_loaded() {
        return $this->options->slotmarks;
    }

    protected function is_latest_step_column($column) {
        if (preg_match('/^qsgrade([0-9]+)/', $column, $matches)) {
            return $matches[1];
        }
        return false;
    }

    protected function get_required_latest_state_fields($slot, $alias) {
        return "$alias.fraction * $alias.maxmark AS qsgrade$slot";
    }

    public function query_db($pagesize, $useinitialsbar = true) {
        parent::query_db($pagesize, $useinitialsbar);
    }
}