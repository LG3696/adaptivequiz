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
 * This file keeps track of upgrades to the adaptivequiz module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package    mod_adaptivequiz
 * @copyright  2017 Luca Gladiator <lucamarius.gladiator@stud.tu-darmstadt.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute adaptivequiz upgrade from the given old version.
 *
 * @param int $oldversion the current version number.
 * @return bool
 */
function xmldb_adaptivequiz_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes

    if ($oldversion < 2018012400) {

        // Define table adaptivequiz_feedback_block to be created.
        $table = new xmldb_table('adaptivequiz_feedback_block');

        // Adding fields to table adaptivequiz_feedback_block.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('quizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('conditionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('feedbacktext', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table adaptivequiz_feedback_block.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('quizid', XMLDB_KEY_FOREIGN, array('quizid'), 'adaptivequiz', array('id'));
        $table->add_key('conditionid', XMLDB_KEY_FOREIGN, array('conditionid'), 'adaptivequiz_condition', array('id'));

        // Conditionally launch create table for adaptivequiz_feedback_block.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }



        // Define table adaptivequiz_feedback_uses to be created.
        $table = new xmldb_table('adaptivequiz_feedback_uses');

        // Adding fields to table adaptivequiz_feedback_uses.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('feedbackblockid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('questioninstanceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table adaptivequiz_feedback_uses.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('feedbackblockid', XMLDB_KEY_FOREIGN, array('feedbackblockid'), 'adaptivequiz_feedbackblock', array('id'));
        $table->add_key('questioninstanceid', XMLDB_KEY_FOREIGN, array('questioninstanceid'), 'adaptivequiz_qinstance', array('id'));

        // Conditionally launch create table for adaptivequiz_feedback_uses.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define field name to be added to adaptivequiz_feedback_block.
        $table = new xmldb_table('adaptivequiz_feedback_block');
        $field = new xmldb_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, 'feedbacktext');

        // Conditionally launch add field name.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Adaptivequiz savepoint reached.
        upgrade_mod_savepoint(true, 2018012400, 'adaptivequiz');
    }
    return true;
}
