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

	if ($oldversion < 2015051302) {
		// Define field reviewer_id to be added to block_exalib_item.
		$table = new xmldb_table('block_exalib_item');

		$field = new xmldb_field('reviewer_id', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0', 'created_by');

		// Conditionally launch add field reviewer_id.
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

		// Exalib savepoint reached.
		upgrade_block_savepoint(true, 2015051302, 'exalib');
	}

	return $result;
}
