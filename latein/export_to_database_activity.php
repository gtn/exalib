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
 * export_to_database_activity.php
 * @package    block_exalib
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  gtn gmbh <office@gtn-solutions.com>
 * @author       Daniel Prieler <dprieler@gtn-solutions.com>
 */

if (file_exists('../inc.php')) {
    require('../inc.php');
} else {
    require('../../exalib/inc.php');
}

block_exalib_require_cap(\block_exalib\CAP_MANAGE_CONTENT);
define('BLOCK_EXALIB_IS_ADMIN_MODE', true);



/**
 * Export to database activity
 * @param integer $fromexalibcategoryid 
 * @param integer $toactivitydataid 
 * @return nothing
 */
function exalib_export_to_database_activity($fromexalibcategoryid, $toactivitydataid) {
    global $DB, $USER;

    $dbman = $DB->get_manager();
    $tablePrefix = $dbman->table_exists(new xmldb_table('block_exalib_item')) ? 'block_exalib_' : 'exalib_';
    
    $fs = get_file_storage();
    $dataid = $toactivitydataid;

	$mgr = new block_exalib_category_manager(true);

    if (! $category = $mgr->getcategory($fromexalibcategoryid)) {
        die('from_exalib_category_id not found');
    }
    if (! $data = $DB->get_record('data', array('id' => $dataid))) {
        die('to_activity_data_id not found');
    }
    if (! $course = $DB->get_record('course', array('id' => $data->course))) {
        die('coursemisconf');
    }
    if (! $cm = get_coursemodule_from_instance('data', $data->id, $course->id)) {
        die('invalidcoursemodule');
    }

    $fields = $DB->get_records_sql_menu("SELECT name AS id, id AS tmp FROM {data_fields} WHERE dataid=$dataid");
    if (empty($fields)) {
        die('no fields');
    }
    $GLOBALS['latein_fields'] = array_change_key_case($fields, CASE_LOWER);


    // Delete old data.
    if (@!$_GET['skip_delete']) {
        $DB->execute("DELETE FROM {data_content} WHERE recordid IN (SELECT id from {data_records} WHERE dataid=$dataid)");
        $DB->delete_records('data_records', array('dataid' => $dataid));
    }
    // TODO: also delete files? not needed, delete it before insert
    /* ...For code checker... $DB->execute("DELETE FROM {mdl_files}
        WHERE component='mod_data' AND filearea='content' AND itemid IN ()"); */

    $items = $DB->get_records_sql("
        SELECT item.*, c.name AS category, u.id AS userid FROM {{$tablePrefix}item} item
        LEFT JOIN {user} u ON u.id = item.created_by
        JOIN {{$tablePrefix}item_category} ic
            ON (item.id=ic.item_id AND ic.category_id IN (".join(',', $category->self_inc_all_sub_ids)."))
        JOIN {{$tablePrefix}category} c ON ic.category_id=c.id

        -- WHERE item.name LIKE '%politische%'
        GROUP BY item.id
        ORDER BY GREATEST(IFNULL(time_created,0),IFNULL(time_modified,0))
        -- LIMIT 10
    ");

    function block_exalib_latein_has_field($name) {
        $fields = $GLOBALS['latein_fields'];
        $name = strtolower($name);
        
        if (isset($fields[$name])) {
            return $fields[$name];
        } elseif ($name == 'gruppe') {
            if (isset($fields['schulstufe'])) {
                return $fields['schulstufe'];
            } else if (isset($fields['modul'])) {
                return $fields['modul'];
            }
        }
        
        return null;
    }
    
    function block_exalib_latein_get_field($name) {
        if ($id = block_exalib_latein_has_field($name)) {
            return $id;
        } else {
            die('field not found: '.$name);
        }
    }

    var_dump(count($items));

    foreach ($items as $item) {
        $newid = $DB->insert_record('data_records', array(
            'userid' => $item->userid ? $item->userid : $USER->id,
            'groupid' => 0,
            'dataid' => $dataid,
            'timecreated' => $item->time_created ? $item->time_created : 0,
            'timemodified' => $item->time_modified ? $item->time_modified : 0,
            'approved' => 1,
        ));

        $DB->insert_record('data_content', array(
            'fieldid' => block_exalib_latein_get_field('Titel'),
            'recordid' => $newid,
            'content' => $item->name,
        ));
        $DB->insert_record('data_content', array(
            'fieldid' => block_exalib_latein_get_field('Beschreibung'),
            'recordid' => $newid,
            'content' => $item->content,
        ));
        $DB->insert_record('data_content', array(
            'fieldid' => block_exalib_latein_get_field('erstellt am'),
            'recordid' => $newid,
            'content' => $item->time_created ? $item->time_created : 0,
        ));
        $DB->insert_record('data_content', array(
            'fieldid' => block_exalib_latein_get_field('Autor'),
            'recordid' => $newid,
            'content' => $item->authors,
        ));
        $DB->insert_record('data_content', array(
            'fieldid' => block_exalib_latein_get_field('Gruppe'),
            'recordid' => $newid,
            'content' => $item->category,
        ));
        
        if (block_exalib_latein_has_field('Link')) {
            $DB->insert_record('data_content', array(
                'fieldid' => block_exalib_latein_get_field('Link'),
                'recordid' => $newid,
                'content' => $item->link,
            ));
        }

        $areafiles = $fs->get_area_files(context_system::instance()->id,
            'block_exalib',
            'item_file',
            $item->id,
            'itemid',
            '',
            false);
        $file = reset($areafiles);
        if (!$file) {
            $DB->insert_record('data_content', array(
                'fieldid' => block_exalib_latein_get_field('Datei'),
                'recordid' => $newid,
                'content' => null,
            ));
        } else {
            var_dump($item->name);
            $newfileid = $DB->insert_record('data_content', array(
                'fieldid' => block_exalib_latein_get_field('Datei'),
                'recordid' => $newid,
                'content' => $file->get_filename(),
            ));

            $context = $context = context_module::instance($cm->id);
            $fileinfo = array(
                'contextid' => $context->id,
                'component' => 'mod_data',
                'filearea' => 'content',
                'itemid' => $newfileid
            );
            $fs->create_file_from_storedfile($fileinfo, $file);
        }
    }

    // Reset group field.
    $categories = array_map(
        function($item){
            return $item->category;
        },
        $items);
    $categories = array_unique($categories);
    asort($categories);
    $DB->update_record('data_fields', array(
        'id' => block_exalib_latein_get_field('Gruppe'),
        'param1' => join("\n", $categories)
    ));
}

exalib_export_to_database_activity($_REQUEST['from_exalib_category_id'], $_REQUEST['to_activity_data_id']);

die('finished');

/*
http://www.edumoodle.at/latein/mod/data/view.php?id=10
http://latein.gtn-solutions.com/blocks/exalib/index.php?courseid=1&page&q&category_id=101379
    SELECT item.*
    FROM {exalib_item} AS item
    JOIN {exalib_item_category} AS ic
    ON (item.id=ic.item_id AND ic.category_id
    IN (101406,101407,101402,101405,101404,101403,101401,101400,101399,101410,101408,101396,101398,
        101397,101394,101393,101392,101395,101391,101390,101389,101411,101388,101387,101385,101379))
    WHERE 1=1 AND IFNULL(item.hidden,0)=0
    AND (IFNULL(item.online_from,0)=0 OR (item.online_from <= 1419132641 AND item.online_to >= 1419132641))
    GROUP BY item.id ORDER BY GREATEST(IFNULL(time_created,0),IFNULL(time_modified,0)) DESC LIMIT 0, 20


http://www.edumoodle.at/latein/mod/data/view.php?id=4
http://latein.gtn-solutions.com/blocks/exalib/index.php?courseid=1&page&q&category_id=96878
    SELECT item.*
    FROM {exalib_item} AS item
    JOIN {exalib_item_category} AS ic
    ON (item.id=ic.item_id AND ic.category_id
    IN (96880,96881,99019,96883,96882,99020,97809,97812,99022,97810,97813,97811,97814,98910,99056,99023,98950,96878,99958))
    WHERE 1=1 AND IFNULL(item.hidden,0)=0
    AND (IFNULL(item.online_from,0)=0 OR (item.online_from <= 1419132608 AND item.online_to >= 1419132608))
 GROUP BY item.id ORDER BY GREATEST(IFNULL(time_created,0),IFNULL(time_modified,0)) DESC LIMIT 0, 20
*/