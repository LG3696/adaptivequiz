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
 * Execute adaptivequiz upgrade from the given old version
 *
 * @param int $oldversion
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
	
	if ($oldversion < 2017120400) {

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
        upgrade_mod_savepoint(true, 2017120400, 'adaptivequiz');
    }



    return true;
}
