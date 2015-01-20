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
 * test_stats.php
 * @package    block_exalib
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  gtn gmbh <office@gtn-solutions.com>
 * @author       Daniel Prieler <dprieler@gtn-solutions.com>
 */

require('inc.php');

$PAGE->set_url('/', array());
$PAGE->set_course($SITE);

block_exalib_require_admin();

$overviewpage = new moodle_url('/blocks/exalib');

$PAGE->set_url('/blocks/exalib');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('login');
$PAGE->navbar->add(get_string('heading', 'block_exalib'), $overviewpage);

$PAGE->set_heading(get_string('heading', 'block_exalib'));

$items = $DB->get_records_sql("SELECT item.*
FROM {exalib_item} item
JOIN {exalib_item_category} ic ON item.id=ic.item_id
LEFT JOIN {exalib_category} sub_category ON ic.category_id=sub_category.id
WHERE 1=1
GROUP BY item.id
ORDER BY item.name");

var_dump(count($items));

$stats = array();

$fs = get_file_storage();
echo '<pre>';
        require_once($CFG->dirroot . '/repository/lib.php');

foreach ($items as $item) {
    $areafiles = $fs->get_area_files(get_context_instance(CONTEXT_SYSTEM)->id,
        'block_exalib',
        'item_file',
        $item->id,
        'itemid',
        '',
        false);
    $file = reset($areafiles);

    $downloadurl = null;
    $podcast = false;
    $targetnewwindow = false;

    if ($file) {
        $stats['file_area']++;
        echo '<a href="index.php?q='.urlencode($item->name).'">'.$item->name.'</a><br />';

    } else if ($item->resource_id) {
        $stats['resource_id']++;
    } else if ($item->link) {
        if (strpos($item->link, 'rtmp://') === 0) {
            $stats['link_rtmp']++;
        } else if (preg_match('!^filesystemrepo://(.*)$!', $item->link, $matches)) {
            $repo = repository::get_repository_by_id(FILESYSTEMREPO_ID, SYSCONTEXTID);
            $file = $repo->get_file(urldecode(trim($matches[1])));
            if (!$file || !file_exists($file['path'])) {
                $stats['filesystemrepo_not_found']++;
                continue;
            }
            $stats['filesystemrepo']++;
        } else {
            $url = $item->link;
            if (preg_match('!library/Podcasts/(.*)!', $url, $matches)) {
                $stats['link_podcast']++;
            } else {
                $stats['link_normal']++;
            }
        }
    } else if ($item->content || $item->background) {
        $stats['content_detail']++;
    } else {
        $stats['no_link']++;
    }
}

// ... print_r ($stats);.
var_dump($stats);
