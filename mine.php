<?php
// This file is part of Exabis Library
//
// (c) 2016 GTN - Global Training Network GmbH <office@gtn-solutions.com>
//
// Exabis Library is free software: you can redistribute it and/or modify
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

require __DIR__.'/inc.php';

block_exalib_init_page();
block_exalib_require_cap(\block_exalib\CAP_USE);

$show = optional_param('show', '', PARAM_TEXT);
$type = optional_param('type', '', PARAM_TEXT);

if ($type == 'review') {
	// ok
} else {
	$type = 'mine';
}

$output = block_exalib_get_renderer();
$output->set_tabs('tab_'.$type);

if (in_array($show, ['change_state', 'edit', 'add', 'delete'])) {
	block_exalib_handle_item_edit($type, $show);
	exit;
}

$where = '';
$params = [];

if ($type == 'review') {
	$where .= "AND (item.reviewer_id=? AND item.online<>".\block_exalib\ITEM_STATE_NEW.")";
	$params[] = $USER->id;
} else {
	$where .= "AND (item.created_by = ?)";
	$params[] = $USER->id;
}

$items = $DB->get_records_sql("
    SELECT item.*
    FROM {block_exalib_item} AS item
    WHERE 1=1
    $where
	".block_exalib_limit_item_to_category_where(block_exalib_course_settings::root_category_id())."

    ORDER BY GREATEST(time_created,time_modified) DESC
", $params);

echo $output->header();

if ($type == 'mine') {
	echo '<div>';
	echo $output->link_button(new moodle_url($PAGE->url, ['show' => 'add', 'back' => $PAGE->url->out_as_local_url(false)]), \block_exalib\get_string('add'));
	echo '</div>';
}

if (!$items) {
	echo get_string('noitemsfound', 'block_exalib');
} else {
	$output->item_list($type, $items);
}

echo $output->footer();
