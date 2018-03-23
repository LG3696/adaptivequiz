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
 * Base class for the settings form for reports.
 *
 *
 * @package    mod_adaptivequiz
 * @copyright  2017 Luca Gladiator <lucamarius.gladiator@stud.tu-darmstadt.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_adaptivequiz\report;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');


/**
 * Base class for the settings form for reports.
 *
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class attempts_form extends \moodleform {

    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'preferencespage',
            get_string('reportwhattoinclude', 'adaptivequiz'));

        $this->standard_attempt_fields($mform);
        $this->other_attempt_fields($mform);

        $mform->addElement('header', 'preferencesuser',
            get_string('reportdisplayoptions', 'adaptivequiz'));

        $this->standard_preference_fields($mform);
        $this->other_preference_fields($mform);

        $mform->addElement('submit', 'submitbutton',
            get_string('showreport', 'adaptivequiz'));
    }

    protected function standard_attempt_fields(\MoodleQuickForm $mform) {

        $mform->addElement('select', 'attempts', get_string('reportattemptsfrom', 'adaptivequiz'), array(
            attempts::ENROLLED_WITH     => get_string('reportuserswith', 'adaptivequiz'),
            attempts::ENROLLED_WITHOUT  => get_string('reportuserswithout', 'adaptivequiz'),
            attempts::ENROLLED_ALL      => get_string('reportuserswithorwithout', 'adaptivequiz'),
            attempts::ALL_WITH          => get_string('reportusersall', 'adaptivequiz'),
        ));

        $stategroup = array(
            $mform->createElement('advcheckbox', 'stateinprogress', '',
                get_string('stateinprogress', 'adaptivequiz')),
            $mform->createElement('advcheckbox', 'statefinished', '',
                get_string('statefinished', 'adaptivequiz')),
        );
        $mform->addGroup($stategroup, 'stateoptions',
            get_string('reportattemptsthatare', 'adaptivequiz'), array(' '), false);
        $mform->setDefault('stateinprogress', 1);
        $mform->setDefault('statefinished',   1);
        $mform->disabledIf('stateinprogress', 'attempts', 'eq', attempts::ENROLLED_WITHOUT);
        $mform->disabledIf('statefinished',   'attempts', 'eq', attempts::ENROLLED_WITHOUT);
    }

    protected function other_attempt_fields(\MoodleQuickForm $mform) {
    }

    protected function standard_preference_fields(\MoodleQuickForm $mform) {
        $mform->addElement('text', 'pagesize', get_string('pagesize', 'adaptivequiz'));
        $mform->setType('pagesize', PARAM_INT);
    }

    protected function other_preference_fields(\MoodleQuickForm $mform) {
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['attempts'] != attempts::ENROLLED_WITHOUT && !(
            $data['stateinprogress'] || $data['stateoverdue'] || $data['statefinished'] || $data['stateabandoned'])) {
                $errors['stateoptions'] = get_string('reportmustselectstate', 'adaptivequiz');
        }

            return $errors;
    }
}