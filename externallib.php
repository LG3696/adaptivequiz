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
 * Adaptiveuiz external API
 *
 * @package    mod_adaptivequiz
 * @category   external
 * @copyright  2017 Luca Gladiator <lucamarius.gladiator@stud.tu-darmstadt.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/question/editlib.php');

/**
 * Adaptiveuiz external functions
 *
 * @package    mod_adaptivequiz
 * @category   external
 * @copyright  2017 Luca Gladiator <lucamarius.gladiator@stud.tu-darmstadt.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */
class mod_adaptivequiz_external extends external_api {
    /**
     * Returns the description of get_questionbank.
     * @return external_function_parameters the function parameters.
     */
    public static function get_questionbank_parameters() {
        return new external_function_parameters(
            array('cmid' => new external_value(PARAM_INT, 'the course module id'),
                'page' => new external_value(PARAM_INT, 'the page of the question bank view', VALUE_DEFAULT, 0),
                'qperpage' => new external_value(PARAM_INT, 'the number of questions per page', VALUE_DEFAULT,
                    DEFAULT_QUESTIONS_PER_PAGE),
                'category' => new external_value(PARAM_TEXT, 'the question category', VALUE_DEFAULT, null)
            )
        );
    }

    /**
     * Renders the questionbank view HTML.
     *
     * @param int $cmid the id of the course module.
     * @param int $page the page of the questionbank view.
     * @param int $qperpage the number of questions per page.
     * @param string $category the category of the question.
     * @return string the questionbank view HTML.
     */
    public static function get_questionbank($cmid, $page, $qperpage, $category) {
        global $PAGE;

        $params = self::validate_parameters(self::get_questionbank_parameters(),
            array('cmid' => $cmid, 'page' => $page, 'qperpage' => $qperpage, 'category' => $category));

        $context = context_module::instance($params['cmid']);
        external_api::validate_context($context);

        $cmid = $params['cmid'];
        $thispageurl = new moodle_url('/mod/adaptivequiz/edit.php');

        list($course, $cm) = get_module_from_cmid($cmid);

        $contexts = new question_edit_contexts($context);
        $contexts->require_one_edit_tab_cap('editq');

        if (!$category) {
            $defaultcategory = question_make_default_categories($contexts->all());
            $category = "{$defaultcategory->id},{$defaultcategory->contextid}";
        }
        $pagevars['cat'] = $category;

        $pagevars['page'] = $params['page'];
        $pagevars['qperpage'] = $params['qperpage'];

        require_capability('mod/adaptivequiz:manage', $contexts->lowest());

        $questionbank = new mod_adaptivequiz\question\bank\custom_view($contexts, $thispageurl, $course, $cm);

        $output = $PAGE->get_renderer('mod_adaptivequiz', 'edit');

        // Output.
        return external_api::clean_returnvalue(mod_adaptivequiz_external::get_questionbank_returns(),
            $output->question_bank_contents($questionbank, $pagevars));
    }

    /**
     * Returns the return description of get_questionbank.
     * @return external_description the description.
     */
    public static function get_questionbank_returns() {
        return new external_value(PARAM_RAW, 'the questionbank view HTML');
    }
}
