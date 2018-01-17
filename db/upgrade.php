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
 * @copyright  2016 Your Name <your@email.address>
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

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2017112800) {

        // Define table adaptivequiz to be created.
        $table = new xmldb_table('adaptivequiz');

        // Adding fields to table adaptivequiz.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('intro', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('introformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('grade', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '100');
        $table->add_field('mainblock', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table adaptivequiz.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('mainblock', XMLDB_KEY_FOREIGN, array('mainblock'), 'adaptivequiz_block', array('id'));

        // Adding indexes to table adaptivequiz.
        $table->add_index('course', XMLDB_INDEX_NOTUNIQUE, array('course'));

        // Conditionally launch create table for adaptivequiz.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Adaptivequiz savepoint reached.
        upgrade_mod_savepoint(true, 2017112800, 'adaptivequiz');
    }

    if ($oldversion < 2017112801) {

        // Define table adaptivequiz_block to be created.
        $table = new xmldb_table('adaptivequiz_block');

        // Adding fields to table adaptivequiz_block.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table adaptivequiz_block.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for adaptivequiz_block.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table adaptivequiz_qinstance to be created.
        $table = new xmldb_table('adaptivequiz_qinstance');

        // Adding fields to table adaptivequiz_qinstance.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('blockid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('blockelement', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('type', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('grade', XMLDB_TYPE_NUMBER, '10, 5', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('slot', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table adaptivequiz_qinstance.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('block', XMLDB_KEY_FOREIGN, array('blockid'), 'adaptivequiz_block', array('id'));

        // Conditionally launch create table for adaptivequiz_qinstance.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Adaptivequiz savepoint reached.
        upgrade_mod_savepoint(true, 2017112801, 'adaptivequiz');
    }

    if ($oldversion < 2017121200) {

        // Define table adaptivequiz_attempts to be created.
        $table = new xmldb_table('adaptivequiz_attempts');

        // Adding fields to table adaptivequiz_attempts.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('quiz', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('attempt', XMLDB_TYPE_INTEGER, '6', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('uniqueid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('layout', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('currentpage', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('preview', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('state', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, 'inprogress');
        $table->add_field('timestart', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timefinish', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecheckstate', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('sumgrades', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null);

        // Adding keys to table adaptivequiz_attempts.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('quiz', XMLDB_KEY_FOREIGN, array('quiz'), 'adaptivequiz', array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_key('uniqueid', XMLDB_KEY_FOREIGN_UNIQUE, array('uniqueid'), 'question_usages', array('id'));

        // Adding indexes to table adaptivequiz_attempts.
        $table->add_index('quiz_userid_attempt', XMLDB_INDEX_UNIQUE, array('quiz', 'userid', 'attempt'));
        $table->add_index('state-timecheckstate', XMLDB_INDEX_NOTUNIQUE, array('state', 'timecheckstate'));

        // Conditionally launch create table for adaptivequiz_attempts.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table adaptivequiz_grades to be created.
        $table = new xmldb_table('adaptivequiz_grades');

        // Adding fields to table adaptivequiz_grades.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('quiz', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('grade', XMLDB_TYPE_NUMBER, '10, 5', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table adaptivequiz_grades.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('quiz', XMLDB_KEY_FOREIGN, array('quiz'), 'adaptivequiz', array('id'));

        // Adding indexes to table adaptivequiz_grades.
        $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));

        // Conditionally launch create table for adaptivequiz_grades.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Adaptivequiz savepoint reached.
        upgrade_mod_savepoint(true, 2017121200, 'adaptivequiz');

    }
    if ($oldversion < 2017121202) {

        // Define field layout to be dropped from adaptivequiz_attempts.
        $table = new xmldb_table('adaptivequiz_attempts');
        $field = new xmldb_field('layout');

        // Conditionally launch drop field layout.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field preview to be dropped from adaptivequiz_attempts.
        $table = new xmldb_table('adaptivequiz_attempts');
        $field = new xmldb_field('preview');

        // Conditionally launch drop field preview.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define key uniqueid (foreign-unique) to be dropped form adaptivequiz_attempts.
        $table = new xmldb_table('adaptivequiz_attempts');
        $key = new xmldb_key('uniqueid', XMLDB_KEY_FOREIGN_UNIQUE, array('uniqueid'), 'question_usages', array('id'));

        // Launch drop key uniqueid.
        $dbman->drop_key($table, $key);

        // Rename field uniqueid on table adaptivequiz_attempts to quba.
        $table = new xmldb_table('adaptivequiz_attempts');
        $field = new xmldb_field('uniqueid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'attempt');

        // Launch rename field uniqueid.
        $dbman->rename_field($table, $field, 'quba');

        // Define key quba (foreign-unique) to be added to adaptivequiz_attempts.
        $table = new xmldb_table('adaptivequiz_attempts');
        $key = new xmldb_key('quba', XMLDB_KEY_FOREIGN_UNIQUE, array('quba'), 'question_usages', array('id'));

        // Launch add key quba.
        $dbman->add_key($table, $key);

        // Adaptivequiz savepoint reached.
        upgrade_mod_savepoint(true, 2017121202, 'adaptivequiz');
    }
    if ($oldversion < 2017121500) {

        // Define table adaptivequiz to be created.
        $table = new xmldb_table('adaptivequiz');

        // Adding fields to table adaptivequiz.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('intro', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('introformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('grade', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '100');
        $table->add_field('mainblock', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table adaptivequiz.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('mainblock', XMLDB_KEY_FOREIGN, array('mainblock'), 'adaptivequiz_block', array('id'));

        // Adding indexes to table adaptivequiz.
        $table->add_index('course', XMLDB_INDEX_NOTUNIQUE, array('course'));

        // Conditionally launch create table for adaptivequiz.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Adaptivequiz savepoint reached.
        upgrade_mod_savepoint(true, 2017121500, 'adaptivequiz');
    }

    if ($oldversion < 2018010300) {

        // Rename field currentpage on table adaptivequiz_attempts to currentslot.
        $table = new xmldb_table('adaptivequiz_attempts');
        $field = new xmldb_field('currentpage', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'quba');

        // Launch rename field currentslot.
        $dbman->rename_field($table, $field, 'currentslot');

        // Adaptivequiz savepoint reached.
        upgrade_mod_savepoint(true, 2018010300, 'adaptivequiz');
    }

    return true;
}
