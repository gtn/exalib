<?php

require 'inc.php';

$PAGE->set_url('/', array());
$PAGE->set_course($SITE);

block_exalib_require_admin();

$overviewPage = new moodle_url('/blocks/exalib');

$PAGE->set_url('/blocks/exalib');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('login');
$PAGE->navbar->add(get_string('heading', 'block_exalib'), $overviewPage);

$PAGE->set_heading(get_string('heading', 'block_exalib'));

$ITEMS = $DB->get_records_sql("SELECT item.*
FROM {exalib_item} AS item
JOIN {exalib_item_category} AS ic ON item.id=ic.item_id
LEFT JOIN {exalib_category} AS sub_category ON ic.category_id=sub_category.id
WHERE 1=1
GROUP BY item.id
ORDER BY item.name");
	
var_dump(count($ITEMS));

$stats = array();

$fs = get_file_storage();
echo '<pre>';
		require_once($CFG->dirroot . '/repository/lib.php');

	foreach ($ITEMS as $item) {
		
		$areafiles = $fs->get_area_files(get_context_instance(CONTEXT_SYSTEM)->id, 'block_exalib', 'item_file', $item->id, 'itemid', '', false);
		$file = reset($areafiles);

		$downloadUrl = null;
		$podcast = false;
		$targetNewWindow = false;
		
		if ($file) {
			$stats['file_area']++;
			echo '<a href="index.php?q='.urlencode($item->name).'">'.$item->name.'</a><br />';
			//var_dump($item);
		} elseif ($item->resource_id) {
			$stats['resource_id']++;
		} elseif ($item->link) {
			if (strpos($item->link, 'rtmp://') === 0) {
				$stats['link_rtmp']++;
			} elseif (preg_match('!^filesystemrepo://(.*)$!', $item->link, $matches)) {
				$repo = repository::get_repository_by_id(FILESYSTEMREPO_ID, SYSCONTEXTID);
				$file = $repo->get_file(urldecode(trim($matches[1])));
				if (!$file || !file_exists($file['path'])) {
					$stats['filesystemrepo_not_found']++;
					//var_dump($item);
					//var_dump($file);
					continue;
				}
				$stats['filesystemrepo']++;
			} else {
				$url = $item->link;
				if (preg_match('!library/Podcasts/(.*)!', $url, $matches)) {
					$stats['link_podcast']++;
				} else {
					$stats['link_normal']++;
					// var_dump($url);
				}
			}
		} elseif ($item->content || $item->background) {
			$stats['content_detail']++;
		} else {
			$stats['no_link']++;
		}
	}

print_r($stats);
