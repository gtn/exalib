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

if (!defined('BLOCK_EXALIB_IS_ADMIN_MODE')) {
	define('BLOCK_EXALIB_IS_ADMIN_MODE', 0);
}

require __DIR__.'/inc.php';

block_exalib_init_page();
if (BLOCK_EXALIB_IS_ADMIN_MODE) {
	block_exalib_require_cap(BLOCK_EXALIB_CAP_MANAGE_CONTENT);
} else {
	block_exalib_require_cap(BLOCK_EXALIB_CAP_USE);
}

$urloverview = new moodle_url('/blocks/exalib');
$urlpage = block_exalib_new_moodle_url();
$urlsearch = new block_exalib\url($urlpage, array('page' => null, 'q' => null));
$urladd = new moodle_url($urlpage, array('show' => 'add'));
$urlcategory = new moodle_url($urlpage, array('page' => null, 'q' => null, 'category_id' => null));

$PAGE->set_url($urlpage);

$categoryid = optional_param('category_id', -1, PARAM_INT);
$filterid = 0;

/* $FILTER_CATEGORY = $DB->get_record("block_exalib_category", array('id' => $filterid));
 if ($FILTER_CATEGORY) $PAGE->navbar->add($FILTER_CATEGORY->name); */
/*
if (BLOCK_EXALIB_IS_ADMIN_MODE) {
    $PAGE->navbar->add(block_exalib_get_string('administration'), 'admin.php');
}
*/

$mgr = new block_exalib_category_manager(BLOCK_EXALIB_IS_ADMIN_MODE, block_exalib_course_settings::root_category_id());
$currentcategory = $mgr->getcategory($categoryid);
$currentcategorysubids = $currentcategory ? $currentcategory->self_inc_all_sub_ids : array(-9999);
$currentcategoryparents = $mgr->getcategoryparentids($categoryid);

if (BLOCK_EXALIB_IS_ADMIN_MODE) {
	require('admin.actions.inc.php');
}

$perpage = 20;
$page = optional_param('page', 0, PARAM_INT);

$items = null;
$pagingbar = null;
$show = null;

if (BLOCK_EXALIB_IS_ADMIN_MODE) {
	$sqlwhere = "";
} else {
	$sqlwhere = "AND item.online > 0
        AND (item.online_from=0 OR item.online_from IS NULL OR item.online_from <= ".time().")
        AND (item.online_to=0 OR item.online_to IS NULL OR item.online_to >= ".time().")";
}

if ($root_category_id = block_exalib_course_settings::root_category_id()) {
	// $root_category = $mgr->getcategory($root_category_id);
	// $sqlwhere .= " AND item.id IN (".join(',', [0] + ($root_category ? $root_category->item_ids_inc_subs : [])).")";
	$sqlwhere .= block_exalib_limit_item_to_category_where($root_category_id);
}

if ($q = optional_param('q', '', PARAM_TEXT)) {
	$show = 'search';

	$q = trim($q);

	$qparams = preg_split('!\s+!', $q);

	$sqljoin = "";
	$sqlparams = array();

	if ($currentcategory) {
		$sqlwhere .= " AND item.id IN (".join(',', [0] + $currentcategory->item_ids_inc_subs).")";
	}

	foreach ($qparams as $i => $qparam) {
		$search_fields = [
			'item.link', 'item.source', 'item.file', 'item.name', 'item.authors',
			'item.abstract', 'item.content', 'item.link_titel', "c$i.name",
		];

		$sqljoin .= " LEFT JOIN {block_exalib_item_category} ic$i ON item.id=ic$i.item_id";
		$sqljoin .= " LEFT JOIN {block_exalib_category} c$i ON ic$i.category_id=c$i.id";
		$sqlwhere .= " AND ".$DB->sql_concat_join("' '", $search_fields)." LIKE ?";
		$sqlparams[] = "%".$DB->sql_like_escape($qparam)."%";
	}

	$sql = "SELECT COUNT(DISTINCT item.id)
		FROM {block_exalib_item} AS item
		$sqljoin
		WHERE 1=1 $sqlwhere
	";
	$count = $DB->get_field_sql($sql, $sqlparams);

	$pagingbar = new paging_bar($count, $page, $perpage, $urlpage);

	$sql = "SELECT item.*
    FROM {block_exalib_item} item
    $sqljoin
    WHERE 1=1 $sqlwhere
    GROUP BY item.id
    ORDER BY name";

	$items = $DB->get_records_sql($sql, $sqlparams, $page * $perpage, $perpage);

} elseif ($currentcategory) {
	$show = 'category';

	$count = $currentcategory->cnt_inc_subs;

	$pagingbar = new paging_bar($count, $page, $perpage, $urlpage);

	$sql = "
		SELECT item.*
		FROM {block_exalib_item} item
		WHERE 1=1
			AND item.id IN (".join(',', [0] + $currentcategory->item_ids_inc_subs).")
			$sqlwhere
		ORDER BY GREATEST(time_created,time_modified) DESC
    ";

	$items = $DB->get_records_sql($sql, array(), $page * $perpage, $perpage);
} elseif ($categoryid == 0) {
	// All items
	$show = 'all_items';

	$sql = "SELECT COUNT(*)
		FROM {block_exalib_item} AS item
		WHERE 1=1 $sqlwhere
	";
	$count = $DB->get_field_sql($sql);

	$pagingbar = new paging_bar($count, $page, $perpage, $urlpage);

	$sql = "SELECT item.*
    FROM {block_exalib_item} item
    WHERE 1=1 $sqlwhere
    GROUP BY item.id
    ORDER BY GREATEST(time_created,time_modified)";

	$items = $DB->get_records_sql($sql, array(), $page * $perpage, $perpage);
} else {
	// Latest changes
	$show = 'latest_changes';

	$sql = "
		SELECT item.*
		FROM {block_exalib_item} AS item
		WHERE 1=1 $sqlwhere
		ORDER BY GREATEST(time_created,time_modified) DESC
	";

	$items = $DB->get_records_sql($sql, array(), 0, 20);
}

$output = block_exalib_get_renderer();

echo $output->header(BLOCK_EXALIB_IS_ADMIN_MODE ? 'tab_manage_content' : null);

?>
	<div class="block_exalib_lib">

		<?php

		/*
		if (false && !$filterid) {
				?>
				<h1 class="libary_head"><?php echo block_exalib_get_string('welcome');  ?></h1>


				<div class="libary_top_cat">
					<a class="exalib-blue-cat-lib" href="<?php echo $CFG->wwwroot; ?>/blocks/exalib/index.php?category_id=11"><?php echo block_exalib_get_string('abstracts')?></a>
					<a class="exalib-blue-cat-lib" href="<?php echo $CFG->wwwroot; ?>/blocks/exalib/index.php?category_id=12"><?php echo block_exalib_get_string('documents')?></a>
					<a class="exalib-blue-cat-lib" href="<?php echo $CFG->wwwroot; ?>/blocks/exalib/index.php?category_id=13"><?php echo block_exalib_get_string('images')?></a>
					<a class="exalib-blue-cat-lib" href="<?php echo $CFG->wwwroot; ?>/blocks/exalib/index.php?category_id=14"><?php echo block_exalib_get_string('podcasts')?></a>
					<a class="exalib-blue-cat-lib" href="<?php echo $CFG->wwwroot; ?>/blocks/exalib/index.php?category_id=15"><?php echo block_exalib_get_string('webcasts')?></a>


				</div>


				<!-- <div class="library_filter_main">
		<a href="index.php?category_id=11">
			<img src="<? echo $CFG->wwwroot; ?>/pluginfile.php/213/course/section/154/ecoo_abstracts.png" height="43" width="212" /></a>
		<a href="index.php?category_id=12">
			<img src="<? echo $CFG->wwwroot; ?>/pluginfile.php/213/course/section/154/ecoo_documents.png" height="43" width="212" /></a>
		<a href="index.php?category_id=13">
			<img src="<? echo $CFG->wwwroot; ?>/pluginfile.php/213/course/section/154/ecoo_images.png" height="43" width="212" /></a>
		<a href="index.php?category_id=14">
			<img src="<? echo $CFG->wwwroot; ?>/pluginfile.php/213/course/section/154/ecoo_podcasts.png" height="43" width="212" /></a>
		<a href="index.php?category_id=15">
			<img src="<? echo $CFG->wwwroot; ?>/pluginfile.php/213/course/section/154/ecoo_webcasts.png" height="43" width="212" /></a>
				</div> -->

				<div class="library_result library_result_main">

		<?php
			if (!$q):
		?>
					<br /><br /><br />
					<form method="get" action="search.php">
						<input name="q" type="text" value="<?php p($q) ?>" style="width: 240px;" class="libaryfront_search" />
						<input value="<?php echo block_exalib_get_string('search'); ?>" type="submit" class="libaryfront_searchsub">
					</form>
		<?php
			else:
		?>
					<form method="get" action="search.php">
						<input name="q" type="text" value="<?php p($q) ?>" style="width: 240px;" class="libaryfront_search" />
						<input value="<?php echo block_exalib_get_string('search'); ?>" type="submit" class="libaryfront_searchsub">
					</form>
		<?php
			endif;
		?>

		<?php
			if ($items !== null) {
				echo '<h1 class="library_result_heading">'.block_exalib_get_string('results').'</h1>';

				if (!$items) {
					echo block_exalib_get_string('noitemsfound');
				} else {
					if ($pagingbar) {
						echo $output->render($pagingbar);
					}
					print_items($items);
					if ($pagingbar) {
						echo $output->render($pagingbar);
					}
				}
			}
		?>
				</div>
				<?php
				echo $output->footer();
				exit;
		}
		*/

		if ($currentcategory) {
			$PAGE->set_heading(block_exalib_get_string('heading').': '.$currentcategory->name);
		}

		?>
		<div class="library_categories">

			<form method="get" action="<?php echo $urlsearch; ?>">
				<?php echo html_writer::input_hidden_params($urlsearch); ?>
				<input name="q" type="text" value="<?php p($q) ?>"/>
				<input value="<?php p($currentcategory
					? block_exalib_trans(['de:In "{$a}" suchen', 'en:Search in "{$a}"'], $currentcategory->name)
					: block_exalib_get_string('search_all')) ?>" type="submit">
			</form>

			<?php

			echo '<h3>'.block_exalib_get_string('categories').'</h3>';

			echo '<div id="exalib-categories"><ul>';
			echo '<li id="exalib-menu-item-0" class="'.(-1 == $categoryid ? ' isActive' : '').'">';
			echo '<a class="library_categories_item_title"
        			href="'.$urlcategory->out(true, array('category_id' => -1)).'">'.block_exalib_get_string('latest').'</a>';
			echo '<li id="exalib-menu-item-0" class="'.(0 == $categoryid ? ' isActive' : '').'">';
			echo '<a class="library_categories_item_title"
        			href="'.$urlcategory->out(true, array('category_id' => 0)).'">'.block_exalib_get_string('all_entries').'</a>';

			echo $mgr->walktree(null, function($cat, $suboutput) {
				global $urlcategory, $categoryid, $currentcategoryparents;

				if (!BLOCK_EXALIB_IS_ADMIN_MODE && !$cat->cnt_inc_subs) {
					// Hide empty categories.
					return;
				}

				$output = '<li id="exalib-menu-item-'.$cat->id.'" class="'.
					($suboutput ? 'isFolder' : '').
					(in_array($cat->id, $currentcategoryparents) ? ' isExpanded' : '').
					($cat->id == $categoryid ? ' isActive' : '').'">';
				$output .= '<a class="library_categories_item_title"
        href="'.$urlcategory->out(true, array('category_id' => $cat->id)).'">'.$cat->name.' ('.$cat->cnt_inc_subs.')'.'</a>';

				if ($suboutput) {
					$output .= '<ul>'.$suboutput.'</ul>';
				}

				echo '</li>';

				return $output;
			});
			echo '</ul></div>';

			?>
		</div>
		<div class="library_result">

			<?php

			if (BLOCK_EXALIB_IS_ADMIN_MODE) {
				?><input type="button" href="<?php echo $urladd; ?>"
						 onclick="document.location.href=this.getAttribute('href');"
						 value="<?php echo block_exalib_get_string('newentry') ?>" /><?php
			}

			echo '<h1 class="library_result_heading">';
			if ($show == 'latest_changes') {
				echo block_exalib_get_string('latest');
			} elseif ($show == 'all_items') {
				echo block_exalib_get_string('all_entries');
			} else {
				echo block_exalib_get_string('results');
			}
			echo '</h1>';

			if (!$items) {
				echo block_exalib_get_string('noitemsfound');
			} else {
				if ($pagingbar) {
					echo $output->render($pagingbar);
				}
				$output->item_list(BLOCK_EXALIB_IS_ADMIN_MODE ? 'admin' : 'public', $items);
				if ($pagingbar) {
					echo $output->render($pagingbar);
				}
			}

			?>
		</div>
	</div>
<?php
echo $output->footer();
