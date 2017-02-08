<?php
// This file is part of Exabis Eportfolio
//
// (c) 2016 GTN - Global Training Network GmbH <office@gtn-solutions.com>
//
// Exabis Eportfolio is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This script is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You can find the GNU General Public License at <http://www.gnu.org/licenses/>.
//
// This copyright notice MUST APPEAR in all copies of the script!

function xmldb_block_exalib_upgrade($oldversion) {
	global $DB, $CFG;
	$dbman = $DB->get_manager();
	$result = true;

	if ($oldversion < 2015033103) {
		$tables = ['exalib_category', 'exalib_item_category', 'exalib_item'];

		foreach ($tables as $tableName) {
			if ($dbman->table_exists($tableName)) {
				$dbman->rename_table(new xmldb_table($tableName), 'block_'.$tableName);
			}
		}
	}

	if ($oldversion < 2015051300) {
		// Define field reviewer_id to be added to block_exalib_item.
		$table = new xmldb_table('block_exalib_item');

		$field = new xmldb_field('reviewer_id', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0', 'created_by');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		$field = new xmldb_field('abstract', XMLDB_TYPE_TEXT, null, null, null, null, null, 'reviewer_id');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		$DB->execute("UPDATE {block_exalib_item} SET hidden=9 WHERE hidden > 0");
		$DB->execute("UPDATE {block_exalib_item} SET hidden=1 WHERE hidden <> 9 OR hidden IS NULL");
		$DB->execute("UPDATE {block_exalib_item} SET hidden=0 WHERE hidden = 9");

		$field = new xmldb_field('hidden', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'modified_by');
		$dbman->rename_field($table, $field, 'online');

		$field = new xmldb_field('online', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'modified_by');
		$dbman->change_field_default($table, $field);
		$dbman->change_field_notnull($table, $field);


		$DB->execute("UPDATE {block_exalib_category} SET hidden=9 WHERE hidden > 0");
		$DB->execute("UPDATE {block_exalib_category} SET hidden=1 WHERE hidden <> 9 OR hidden IS NULL");
		$DB->execute("UPDATE {block_exalib_category} SET hidden=0 WHERE hidden = 9");


		$table = new xmldb_table('block_exalib_category');

		$field = new xmldb_field('hidden', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'name');
		$dbman->rename_field($table, $field, 'online');

		$field = new xmldb_field('online', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'name');
		$dbman->change_field_default($table, $field);
		$dbman->change_field_notnull($table, $field);


		// Define table block_exalib_item_comments to be created.
		$table = new xmldb_table('block_exalib_item_comments');

		// Adding fields to table block_exalib_item_comments.
		$table->add_field('id', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('itemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('text', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
		$table->add_field('time_created', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('time_modified', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('rating', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

		// Adding keys to table block_exalib_item_comments.
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
		$table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
		$table->add_key('itemid', XMLDB_KEY_FOREIGN, array('itemid'), 'block_exalib_item', array('id'));

		// Conditionally launch create table for block_exalib_item_comments.
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}

		// Exalib savepoint reached.
		upgrade_block_savepoint(true, 2015051300, 'exalib');
	}

	if ($oldversion < 2015051308) {
		// Define field reviewer_id to be added to block_exalib_item.
		$table = new xmldb_table('block_exalib_item');

		$field = new xmldb_field('allow_comments', XMLDB_TYPE_CHAR, '34', null, XMLDB_NOTNULL, null, null, 'abstract');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		upgrade_block_savepoint(true, 2015051308, 'exalib');
	}

	if ($oldversion < 2016070900) {
		// Define field isprivate to be added to block_exalib_item_comments.
		$table = new xmldb_table('block_exalib_item_comments');
		$field = new xmldb_field('isprivate', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'rating');

		// Conditionally launch add field isprivate.
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// Define field real_fiktiv to be added to block_exalib_item.
		$table = new xmldb_table('block_exalib_item');
        $field = new xmldb_field('real_fiktiv', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null, 'allow_comments');

		// Conditionally launch add field real_fiktiv.
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// Exalib savepoint reached.
		upgrade_block_savepoint(true, 2016070900, 'exalib');
	}

	return $result;
}
