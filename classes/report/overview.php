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
 * This file defines the quiz overview report class.
 *
 * @package    mod_adaptivequiz
 * @copyright  2017 Luca Gladiator <lucamarius.gladiator@stud.tu-darmstadt.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_adaptivequiz\report;


defined('MOODLE_INTERNAL') || die();


/**
 * Quiz report subclass for the overview (grades) report.
 *
 * @copyright  2017 Luca Gladiator <lucamarius.gladiator@stud.tu-darmstadt.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overview extends attempts {

    public function display($cm, $course, \adaptivequiz $quiz) {
        global $CFG, $DB, $OUTPUT, $PAGE;

        list($currentgroup, $students, $groupstudents, $allowed) =
                $this->init('\mod_adaptivequiz\report\overview_form', $quiz, $cm, $course);
        $options = new overview_options($quiz, $cm, $course);

        if ($fromform = $this->form->get_data()) {
            $options->process_settings_from_form($fromform);

        } else {
            $options->process_settings_from_params();
        }

        $this->form->set_data($options->get_initial_form_data());

        if ($options->attempts == self::ALL_WITH) {
            // This option is only available to users who can access all groups in
            // groups mode, so setting allowed to empty (which means all quiz attempts
            // are accessible, is not a security porblem.
            $allowed = array();
        }

        $questions = $this->get_significant_questions();

        // Prepare for downloading, if applicable.
        $courseshortname = format_string($course->shortname, true,
                array('context' => \context_course::instance($course->id)));
        $table = new overview_table($quiz, $this->context, $this->qmsubselect,
                $options, $groupstudents, $students, $questions, $options->get_url());
        $filename = $this->download_filename(get_string('overviewfilename', 'adaptivequiz'),
                $courseshortname, $quiz->get_name());
        $table->is_downloading($options->download, $filename,
                $courseshortname . ' ' . format_string($quiz->get_name(), true));
        if ($table->is_downloading()) {
            raise_memory_limit(MEMORY_EXTRA);
        }

        $this->course = $course; // Hack to make this available in process_actions.
        $this->process_actions($quiz, $cm, $currentgroup, $groupstudents, $allowed, $options->get_url());

        // Start output.
        if (!$table->is_downloading()) {
            // Only print headers if not asked to download data.*/
            $this->print_header_and_tabs($cm, $course, $quiz);
        }

        if ($groupmode = groups_get_activity_groupmode($cm)) {
            // Groups are being used, so output the group selector if we are not downloading.
            if (!$table->is_downloading()) {
                groups_print_activity_menu($cm, $options->get_url());
            }
        }

        // Print information on the number of existing attempts.
        if (!$table->is_downloading()) {
            // Do not print notices when downloading.
            $numattempts = $quiz->get_num_attempts();
            $strattemptsnum = get_string('attemptsnum', 'adaptivequiz', $numattempts);
            echo '<div class="quizattemptcounts">' . $strattemptsnum . '</div>';
        }

        if (!$table->is_downloading()) {
            if (!$students) {
                echo $OUTPUT->notification(get_string('nostudentsyet'));
            } else if ($currentgroup && !$groupstudents) {
                echo $OUTPUT->notification(get_string('nostudentsingroup'));
            }

            // Print the display options.
            $this->form->display();
        }

        $hasstudents = $students && (!$currentgroup || $groupstudents);
        if ($hasstudents || $options->attempts == self::ALL_WITH) {
            // Construct the SQL.
            $fields = $DB->sql_concat('u.id', "'#'", 'COALESCE(quiza.attempt, 0)') .
                    ' AS uniqueid, ';

            list($fields, $from, $where, $params) = $table->base_sql($allowed);

            $table->set_count_sql("SELECT COUNT(1) FROM $from WHERE $where", $params);
            $table->set_sql($fields, $from, $where, $params);

            // Define table columns.
            $columns = array();
            $headers = array();

            $this->add_user_columns($table, $columns, $headers);
            $this->add_state_column($columns, $headers);
            $this->add_time_columns($columns, $headers);

            $this->add_grade_columns($quiz, $columns, $headers, false);

            if ($options->slotmarks) {
                foreach ($questions as $slot => $question) {
                    // Ignore questions of zero length.
                    $columns[] = 'qsgrade' . $slot;
                    $headers[] = $question->name;
                }
            }

            $this->set_up_table_columns($table, $columns, $headers, $this->get_base_url(), $options, false);
            $table->set_attribute('class', 'generaltable generalbox grades');

            $table->out($options->pagesize, true);
        }
/*TODO:
        if (!$table->is_downloading() && $options->usercanseegrades) {
            $output = $PAGE->get_renderer('mod_quiz');
            if ($currentgroup && $groupstudents) {
                list($usql, $params) = $DB->get_in_or_equal($groupstudents);
                $params[] = $quiz->id;
                if ($DB->record_exists_select('adaptivequiz_grades', "userid $usql AND quiz = ?",
                        $params)) {
                    $imageurl = new moodle_url('/mod/adaptivequiz/report/overview/overviewgraph.php',
                            array('id' => $quiz->id, 'groupid' => $currentgroup));
                    $graphname = get_string('overviewreportgraphgroup', 'adaptivequiz_overview',
                            groups_get_group_name($currentgroup));
                    echo $output->graph($imageurl, $graphname);
                }
            }

            if ($DB->record_exists('adaptivequiz_grades', array('quiz'=> $quiz->id))) {
                $imageurl = new moodle_url('/mod/adaptivequiz/report/overview/overviewgraph.php',
                        array('id' => $quiz->id));
                $graphname = get_string('overviewreportgraph', 'adaptivequiz');
                echo $output->graph($imageurl, $graphname);
            }
        }*/
        return true;
    }

    /**
     * Regrade a particular quiz attempt. Either for real ($dryrun = false), or
     * as a pretend regrade to see which fractions would change. The outcome is
     * stored in the quiz_overview_regrades table.
     *
     * Note, $attempt is not upgraded in the database. The caller needs to do that.
     * However, $attempt->sumgrades is updated, if this is not a dry run.
     *
     * @param object $attempt the quiz attempt to regrade.
     * @param bool $dryrun if true, do a pretend regrade, otherwise do it for real.
     * @param array $slots if null, regrade all questions, otherwise, just regrade
     *      the quetsions with those slots.
     */
    /*protected function regrade_attempt($attempt, $dryrun = false, $slots = null) {
        global $DB;
        // Need more time for a quiz with many questions.
        core_php_time_limit::raise(300);

        $transaction = $DB->start_delegated_transaction();

        $quba = question_engine::load_questions_usage_by_activity($attempt->uniqueid);

        if (is_null($slots)) {
            $slots = $quba->get_slots();
        }

        $finished = $attempt->state == quiz_attempt::FINISHED;
        foreach ($slots as $slot) {
            $qqr = new stdClass();
            $qqr->oldfraction = $quba->get_question_fraction($slot);

            $quba->regrade_question($slot, $finished);

            $qqr->newfraction = $quba->get_question_fraction($slot);

            if (abs($qqr->oldfraction - $qqr->newfraction) > 1e-7) {
                $qqr->questionusageid = $quba->get_id();
                $qqr->slot = $slot;
                $qqr->regraded = empty($dryrun);
                $qqr->timemodified = time();
                $DB->insert_record('quiz_overview_regrades', $qqr, false);
            }
        }

        if (!$dryrun) {
            question_engine::save_questions_usage_by_activity($quba);
        }

        $transaction->allow_commit();

        // Really, PHP should not need this hint, but without this, we just run out of memory.
        $quba = null;
        $transaction = null;
        gc_collect_cycles();
    }*/

    /**
     * Regrade attempts for this quiz, exactly which attempts are regraded is
     * controlled by the parameters.
     * @param object $quiz the quiz settings.
     * @param bool $dryrun if true, do a pretend regrade, otherwise do it for real.
     * @param array $groupstudents blank for all attempts, otherwise regrade attempts
     * for these users.
     * @param array $attemptids blank for all attempts, otherwise only regrade
     * attempts whose id is in this list.
     */
    /*protected function regrade_attempts($quiz, $dryrun = false,
            $groupstudents = array(), $attemptids = array()) {
        global $DB;
        $this->unlock_session();

        $where = "quiz = ? AND preview = 0";
        $params = array($quiz->id);

        if ($groupstudents) {
            list($usql, $uparams) = $DB->get_in_or_equal($groupstudents);
            $where .= " AND userid $usql";
            $params = array_merge($params, $uparams);
        }

        if ($attemptids) {
            list($asql, $aparams) = $DB->get_in_or_equal($attemptids);
            $where .= " AND id $asql";
            $params = array_merge($params, $aparams);
        }

        $attempts = $DB->get_records_select('adaptivequiz_attempts', $where, $params);
        if (!$attempts) {
            return;
        }

        $this->clear_regrade_table($quiz, $groupstudents);

        $progressbar = new progress_bar('quiz_overview_regrade', 500, true);
        $a = array(
            'count' => count($attempts),
            'done'  => 0,
        );
        foreach ($attempts as $attempt) {
            $this->regrade_attempt($attempt, $dryrun);
            $a['done']++;
            $progressbar->update($a['done'], $a['count'],
                    get_string('regradingattemptxofy', 'adaptivequiz', $a));
        }

        if (!$dryrun) {
            $this->update_overall_grades($quiz);
        }
    }*/

    /**
     * Regrade those questions in those attempts that are marked as needing regrading
     * in the quiz_overview_regrades table.
     * @param object $quiz the quiz settings.
     * @param array $groupstudents blank for all attempts, otherwise regrade attempts
     * for these users.
     */
    /*protected function regrade_attempts_needing_it($quiz, $groupstudents) {
        global $DB;
        $this->unlock_session();

        $where = "quiza.quiz = ? AND quiza.preview = 0 AND qqr.regraded = 0";
        $params = array($quiz->id);

        // Fetch all attempts that need regrading.
        if ($groupstudents) {
            list($usql, $uparams) = $DB->get_in_or_equal($groupstudents);
            $where .= " AND quiza.userid $usql";
            $params = array_merge($params, $uparams);
        }

        $toregrade = $DB->get_recordset_sql("
                SELECT quiza.uniqueid, qqr.slot
                FROM {adaptivequiz_attempts} quiza
                JOIN {quiz_overview_regrades} qqr ON qqr.questionusageid = quiza.uniqueid
                WHERE $where", $params);

        $attemptquestions = array();
        foreach ($toregrade as $row) {
            $attemptquestions[$row->uniqueid][] = $row->slot;
        }
        $toregrade->close();

        if (!$attemptquestions) {
            return;
        }

        $attempts = $DB->get_records_list('adaptivequiz_attempts', 'uniqueid',
                array_keys($attemptquestions));

        $this->clear_regrade_table($quiz, $groupstudents);

        $progressbar = new progress_bar('quiz_overview_regrade', 500, true);
        $a = array(
            'count' => count($attempts),
            'done'  => 0,
        );
        foreach ($attempts as $attempt) {
            $this->regrade_attempt($attempt, false, $attemptquestions[$attempt->uniqueid]);
            $a['done']++;
            $progressbar->update($a['done'], $a['count'],
                    get_string('regradingattemptxofy', 'adaptivequiz', $a));
        }

        $this->update_overall_grades($quiz);
    }*/

//     /**
//      * Count the number of attempts in need of a regrade.
//      * @param object $quiz the quiz settings.
//      * @param array $groupstudents user ids. If this is given, only data relating
//      * to these users is cleared.
//      */
//     protected function count_question_attempts_needing_regrade($quiz, $groupstudents) {
//         global $DB;

//         $usertest = '';
//         $params = array();
//         if ($groupstudents) {
//             list($usql, $params) = $DB->get_in_or_equal($groupstudents);
//             $usertest = "quiza.userid $usql AND ";
//         }

//         $params[] = $quiz->id;
//         $sql = "SELECT COUNT(DISTINCT quiza.id)
//                 FROM {adaptivequiz_attempts} quiza
//                 JOIN {quiz_overview_regrades} qqr ON quiza.uniqueid = qqr.questionusageid
//                 WHERE
//                     $usertest
//                     quiza.quiz = ? AND
//                     quiza.preview = 0 AND
//                     qqr.regraded = 0";
//         return $DB->count_records_sql($sql, $params);
//     }

//     /**
//      * Are there any pending regrades in the table we are going to show?
//      * @param string $from tables used by the main query.
//      * @param string $where where clause used by the main query.
//      * @param array $params required by the SQL.
//      * @return bool whether there are pending regrades.
//      */
//     protected function has_regraded_questions($from, $where, $params) {
//         global $DB;
//         return $DB->record_exists_sql("
//                 SELECT 1
//                   FROM {$from}
//                   JOIN {quiz_overview_regrades} qor ON qor.questionusageid = quiza.uniqueid
//                  WHERE {$where}", $params);
//     }

//     /**
//      * Remove all information about pending/complete regrades from the database.
//      * @param object $quiz the quiz settings.
//      * @param array $groupstudents user ids. If this is given, only data relating
//      * to these users is cleared.
//      */
//     protected function clear_regrade_table($quiz, $groupstudents) {
//         global $DB;

//         // Fetch all attempts that need regrading.
//         $where = '';
//         $params = array();
//         if ($groupstudents) {
//             list($usql, $params) = $DB->get_in_or_equal($groupstudents);
//             $where = "userid $usql AND ";
//         }

//         $params[] = $quiz->id;
//         $DB->delete_records_select('quiz_overview_regrades',
//                 "questionusageid IN (
//                     SELECT uniqueid
//                     FROM {adaptivequiz_attempts}
//                     WHERE $where quiz = ?
//                 )", $params);
//     }

//     /**
//      * Update the final grades for all attempts. This method is used following
//      * a regrade.
//      * @param object $quiz the quiz settings.
//      * @param array $userids only update scores for these userids.
//      * @param array $attemptids attemptids only update scores for these attempt ids.
//      */
//     protected function update_overall_grades($quiz) {
//         quiz_update_all_attempt_sumgrades($quiz);
//         quiz_update_all_final_grades($quiz);
//         quiz_update_grades($quiz);
//     }

    protected function get_base_url() {
        return new \moodle_url('/mod/adaptivequiz/report.php',
            array('id' => $this->context->instanceid, 'mode' => 'overview'));
    }
}