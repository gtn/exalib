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

$show = optional_param('show', '', PARAM_TEXT);

block_exalib_require_global_cap(\block_exalib\CAP_USE);

$output = block_exalib_get_renderer();
$output->set_tabs('tab_mine');

if (in_array($show, ['edit', 'add', 'delete'])) {
	block_exalib_handle_item_edit('mine', $show);
	exit;
}

$items = $DB->get_records_sql("
    SELECT item.*
    FROM {block_exalib_item} AS item
    WHERE 1=1
    AND (item.created_by = ? OR item.reviewer_id=?)
    ORDER BY GREATEST(time_created,time_modified) DESC
", [$USER->id, $USER->id]);

echo $output->header();

echo '<div>';
echo $output->link_button(new moodle_url($PAGE->url, ['show' => 'add']), \block_exalib\get_string('add'));
echo '</div>';

if (!$items) {
	echo get_string('noitemsfound', 'block_exalib');
} else {
	$output->item_list('mine', $items);
}

echo $output->footer();