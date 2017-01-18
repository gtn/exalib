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

require_login(1);

block_exalib_init_page();
/*
if (BLOCK_EXALIB_IS_ADMIN_MODE) {
	block_exalib_require_cap(BLOCK_EXALIB_CAP_MANAGE_CONTENT);
} else {
	block_exalib_require_cap(BLOCK_EXALIB_CAP_USE);
}
*/

$urloverview = new moodle_url('/blocks/exalib');
$urlpage = block_exalib_new_moodle_url();
$urlsearch = new block_exalib\url($urlpage, array('page' => null, 'q' => null));
$urladd = new moodle_url($urlpage, array('show' => 'add'));
$urlcategory = new moodle_url($urlpage, array('page' => null, 'q' => null, 'category_id' => null));

$PAGE->set_url($urlpage);

$topGroups = array(11=>'Abstracts', 12=>'Documents', 13=>'Images', 14=>'Podcasts', 15=>'Webcasts');

$q = optional_param('q', '', PARAM_TEXT);
$category_ids = optional_param_array('category_ids', array(), PARAM_INT);
$sub_filter_id = optional_param('sub_filter_id', '', PARAM_INT);
$search_by = optional_param('search_by', 'all', PARAM_TEXT);
$filter_year = optional_param('filter_year', 0, PARAM_INT);

$filter_category = optional_param('filter_category', '', PARAM_INT);
$filter_sub_type = optional_param('filter_sub_type', '', PARAM_INT);

$perpage = 20;
$page    = optional_param('page', 0, PARAM_INT);

$items = null;
$pagingbar = null;

$categoryManager = new block_exalib_category_manager(BLOCK_EXALIB_IS_ADMIN_MODE, block_exalib_course_settings::root_category_id());

if (BLOCK_EXALIB_IS_ADMIN_MODE) {
	$sqlItemWhere = "";
} else {
	$sqlItemWhere = "AND item.online > 0
		AND (item.online_from=0 OR item.online_from IS NULL OR item.online_from <= ".time().")
		AND (item.online_to=0 OR item.online_to IS NULL OR item.online_to >= ".time().")";
}

$sql = "SELECT DISTINCT year, year as tmp FROM {block_exalib_item} AS item 
WHERE 1=1 $sqlItemWhere
AND year>0
ORDER BY year
";
$years = $DB->get_records_sql_menu($sql);

if ($category_ids) {
	$q = trim($q);
	$qparams = preg_split('!\s+!', $q);

	$sqlWhere = $sqlItemWhere;
	$sqlJoin = "";
	$sqlJoinSubfilter = "";
	$sqlParams = array();


	if ($category_ids) {
		$filter_ids = [];
		foreach ($category_ids as $category_id) {
			$cat = $categoryManager->getcategory($category_id);
			if ($cat) {
				$filter_ids = array_merge($filter_ids, $cat->self_inc_all_sub_ids);
			}
		}
	}

	if ($filter_ids) {
		$sqlJoin .= " JOIN {block_exalib_item_category} AS ic_filter ON (ic_filter.item_id = item.id AND (ic_filter.category_id IN (".join(',',$filter_ids).")))";
	}

	if ($filter_category) {
		$sqlJoin .= " JOIN {block_exalib_item_category} filter_category ON item.id=filter_category.item_id AND filter_category.category_id=?";
		$sqlParams[] = $filter_category;
	}
	if ($filter_sub_type) {
		$sqlJoin .= " JOIN {block_exalib_item_category} filter_sub_type ON item.id=filter_sub_type.item_id AND filter_sub_type.category_id=?";
		$sqlParams[] = $filter_sub_type;
	}

	if ($q) {
		foreach ($qparams as $i => $qparam) {
			$search_fields = [
				'item.link', 'item.source', 'item.file', 'item.name', 'item.authors',
				'item.abstract', 'item.content', 'item.link_titel', "c$i.name",
			];

			$sqlJoin .= " LEFT JOIN {block_exalib_item_category} ic$i ON item.id=ic$i.item_id";
			$sqlJoin .= " LEFT JOIN {block_exalib_category} c$i ON ic$i.category_id=c$i.id";
			$sqlWhere .= " AND ".$DB->sql_concat_join("' '", $search_fields)." LIKE ?";
			$sqlParams[] = "%".$DB->sql_like_escape($qparam)."%";
		}
	}

	if ($filter_year) {
		$sqlWhere .= ' AND item.year=?';
		$sqlParams[] = $filter_year;
	}

	$sql = "SELECT COUNT(*) FROM (SELECT item.id
	FROM {block_exalib_item} AS item 
	$sqlJoin
	$sqlJoinSubfilter
	WHERE 1=1 $sqlWhere
	GROUP BY item.id
	) AS x";
	$count = $DB->get_field_sql($sql, $sqlParams);

	$pagingbar = new paging_bar($count, $page, $perpage, new moodle_url($_SERVER['REQUEST_URI']));

	$sql = "SELECT item.*
	FROM {block_exalib_item} AS item 
	$sqlJoin
	$sqlJoinSubfilter
	WHERE 1=1 $sqlWhere
	GROUP BY item.id
	ORDER BY name
	LIMIT ".$page*$perpage.', '.$perpage;
	$items = $DB->get_records_sql($sql, $sqlParams);



	// SUBFILTER
	$sub_filter_categories = [];
	/*
	$sql = "SELECT c.category_id AS id, COUNT(item.id) AS cnt
	FROM {block_exalib_item} AS item 
	$sqlJoin
	JOIN mdl_library_item_category AS c ON (c.item_id = item.id)
	WHERE 1=1 $sqlWhere
	GROUP BY c.category_id
	ORDER BY c.category_id";
	$sub_filter_categories = $DB->get_records_sql($sql, $sqlParams);
	$all_categories = $DB->get_records_sql("SELECT id, name, parent_id
	FROM mdl_library_categories AS category
	WHERE parent_id!=1 -- no types in select
	ORDER BY name");

	foreach ($sub_filter_categories as $sub_filter_category) {
		if (!$all_categories[$sub_filter_category->id]) {
			unset($sub_filter_categories[$sub_filter_category->id]);
			continue;
		}

		$sub_filter_category->name = $all_categories[$sub_filter_category->id]->name;
		$sub_filter_category->parent_id = $all_categories[$sub_filter_category->id]->parent_id;

		if ($sub_filter_category->parent_id && $all_categories[$sub_filter_category->parent_id]->parent_id) {
			$sub_filter_category->name = $all_categories[$sub_filter_category->parent_id]->name.' >> '.$sub_filter_category->name;
		}
	}
	uasort($sub_filter_categories, create_function('$a, $b', 'return strcmp($a->name, $b->name);'));

	/*
	echo "<pre>";
	print_r($sub_filter_categories);
	*/
}

$output = block_exalib_get_renderer();

echo $output->header();

?>
<script>
	$(function(){
		// un/check all
		var $all_cat = $('#search-all-categories');
		var $category_ids = $('input[name="category_ids[]"]:visible'); // :visible, because other form has hidden category_ids[] fields!

		$all_cat.click(function(){
			var checked = $(this).prop('checked');
			$category_ids.prop('checked', checked);
		});

		$category_ids.click(function(){
			if ($category_ids.not(':checked').length == 0) {
				$all_cat.prop('checked', true);
			}
			else if ($category_ids.not(':checked').length > 0) {
				$all_cat.prop('checked', false);
			}
		});

		if ($all_cat.prop('checked')) {
			$all_cat.triggerHandler('click');
		} else {
			$($category_ids[0]).triggerHandler('click');
		}
	});
</script>
<?php
/*
?>
<style>
.library-item {
	display: block;
	border: 1px solid white;
	padding: 5px 10px;
	margin: 5px 0;
}
.library-item:hover {
	background: -moz-linear-gradient(center top , #FAFAFA 0%, #F5F5F5 100%) repeat scroll 0 0 rgba(0, 0, 0, 0);
    border: 1px solid #DDDDDD;
    border-radius: 5px;
    box-shadow: 0 1px 0 #FFFFFF inset;
}
.library-item .head {
	font-family: 'vegurregular' !important;
	color: #007BB6;
	font-size: 18px;
	font-weight: normal;
	margin-bottom: 15px;
}
.library_filter, .library_filter_main {
float: left;
	width: 300px;
}
.library_filter a {
	display: block;
	padding: 2px 0 3px 5px;
}
.library_filter a:hover {
	text-decoration: underline;
}
.library_filter_main a {
	display: block;
	padding: 1px 0 1px 5px;
}
.library_top_filter a:hover {
	text-decoration: underline;
}

.library_result {

	margin-left: 300px;
}
h1.library_result_heading {
	font-size: 16px;
	margin-top: 34px;
	margin-bottom: 20px;
	padding-left: 0;
}

h2.library_filter_heading {
	font-size: 16px;
	padding-bottom: 10px;
}


/* --- Libary new Styles ----- * /

.library_result .paging, .library_result .library_result_heading {
	text-align: center;
}

.library_result .paging {
	margin-bottom: 20px;
	color: #666666;
}
.library_result .library_result_heading {
	color: #666666;
}


.libary_author {
	color: #000;
}

h1.libary_head, .ecco_lib h3 {
    -moz-border-bottom-colors: none !important;
    -moz-border-left-colors: none !important;
    -moz-border-right-colors: none !important;
    -moz-border-top-colors: none !important;
    background: none repeat scroll 0 0 rgba(0, 0, 0, 0) !important;

    border-color: #C8C8C8;
    border-image: none !important;
    border-style: none;
    border-width: 0;

    color: #003876;

    border-bottom: 1px solid #C8C8C8 !important;
    padding-bottom: 20px !important;

    font-size: 1.9em;
    font-weight: normal !important;
    margin-bottom: 35px !important;
    margin-top: 20px !important;
}

.ecco_lib {
    margin: 0 10px;
    padding: 10px;
}

.library_filter a {
	color: #666666;
}

.library_filter_heading {
	color: #666;
}

.libary_nores {
	font-size: 18px;
	color: #007BB6;
}

a.ecco-lib {
	margin-top: 15px;
	margin-top: 15px;
    background: url([[pix:theme|bgbutton]]) repeat-x scroll 0 0 #003876;
    border: 1px solid #003F85;
    border-radius: 10px;
    box-shadow: 0 5px 5px #005BC1 inset, 0 1px 1px rgba(0, 0, 0, 0.05);
    color: #FFFFFF;
    font-size: 16px;
    padding: 5px 15px;
}

a.ecco-blue-cat-lib {
	margin-top: 5px;
	margin-top: 5px;
    background: url([[pix:theme|bgbutton]]) repeat-x scroll 0 0 #003876;
    border: 1px solid #003F85;
    border-radius: 7px;
    box-shadow: 0 5px 5px #005BC1 inset, 0 1px 1px rgba(0, 0, 0, 0.05);
    color: #FFFFFF;
    font-size: 14px;
    padding: 3px 20px;
}

.library_result_main {

	margin-bottom: 100px;
	margin-top: 30px;
}

.libary_top_cat {
	text-align: center;
}

.ecco_lib input[type="text"] {
	box-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset;
	border-radius: 7px;
	border: 1px solid #CCCCCC;
	font-size: 14px;
	padding: 4px 3px;

}

.ecco_lib input.libaryfront_search[type="text"] {
	box-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset;
	border-radius: 7px;
	border: 1px solid #CCCCCC;
	font-size: 16px;
	padding: 6px 3px;

}

input.libaryfront_searchsub[type="submit"] {
    box-shadow: 0 5px 5px #005BC1 inset, 0 1px 1px rgba(0, 0, 0, 0.05);
    color: #FFFFFF;
    cursor: pointer;
    font-size: 16px;
    padding: 5px 15px;
    margin-left: 5px;
}



.libary_cat_select select {
   background: transparent;
   width: 228px;
   padding: 3px;
   font-size: 14px;
   line-height: 1;
   border: 0;
   border-radius: 0;
   height: 24px;
   -webkit-appearance: none;
}

.libary_cat_select {
   width: 200px;
   height: 24px;
   overflow: hidden;
   background: url(http://gtn02.gtn-solutions.com/layouts/ecco_lib/pix/select_arrow.png) no-repeat right #fff;
   border: 1px solid #ccc;
   text-align: center;
   border-radius: 7px;

   }

.library_top_filter {
	text-align: right;
	color: #666;
	padding-top: 5px;
}

.library_top_filter a {
	color: #666;
}

.library-item div {
	color: #666;
}
.libary_top_search {
	background: -moz-linear-gradient(center top , #FAFAFA 0%, #F5F5F5 100%) repeat scroll 0 0 rgba(0, 0, 0, 0);
    border: 1px solid #DDDDDD;
    border-radius: 5px;
    box-shadow: 0 1px 0 #FFFFFF inset;
    padding: 15px 15px;
    margin-bottom: 10px;
}

.libary_top_filter {
	background: -moz-linear-gradient(center top , #FAFAFA 0%, #F5F5F5 100%) repeat scroll 0 0 rgba(0, 0, 0, 0);
    border: 1px solid #DDDDDD;
    border-radius: 5px;
    box-shadow: 0 1px 0 #FFFFFF inset;
    padding: 15px 15px;
    margin-bottom: 30px;
}

.libary_top_search p, .libary_top_search table, .libary_top_filter table {
	margin-bottom: 0;
}

.libary_search_input {
	width: 90% !important;

}

.paging{
	text-align: center;
}

.libary_top_filter h4 {

}

.paging_bottom {
	margin-top: 25px;
}

.paging_top {
	margin-bottom: 25px;
}

</style>
*/
?>
<div class="ecco_lib">


		<h3>Welcome to the e-CCO Library!</h3>

		<div class="libary_top_search">
		<form method="get" action="adv_search.php">
			<img src="pix/libsearch.png" alt="search" style="vertical-align: middle;padding-right: 10px;">
			<input name="q" type="text" class="libary_search_input" style="vertical-align: middle;" value="<?php p($q) ?>" />

			<div style="display: inline-block; padding-left: 50px;">
				<label><input type="checkbox" id="search-all-categories" value="Abstracts" <?php if (count($category_ids) == 0) echo 'checked="checked"'; ?>>All</label>
				<?php
				foreach ($categoryManager->getChildren(51002) as $category) {
					echo '<label style="padding-left: 20px;"><input type="checkbox" name="category_ids[]" value="'.$category->id.'" '.
						(in_array($category->id, $category_ids) ? 'checked="checked"' : '').
						'/>'.$category->name.'</label>';
				}
				?>
			</div>

			<div style="padding-top: 15px;">
				<?php

					echo '<span>Year: ';
					echo html_writer::select($years, 'filter_year', $filter_year);

					$values = array_map(function($cat) { return $cat->name; }, $categoryManager->getChildren(51001));
					echo '<span style="padding-left: 20px;">Category: ';
					echo html_writer::select($values, 'filter_category', $filter_category);

					$subtypes = [];
					foreach ($categoryManager->getChildren(51002) as $category) {
						if (in_array($category->id, $category_ids)) {
							$subtypes += $categoryManager->getChildren($category->id) ?: [];
						}
					}
					$values = array_map(function($cat) { return $cat->name; }, $subtypes);
					if ($values) {
						echo '<span style="padding-left: 20px;">Sub Type: ';
						echo html_writer::select($values, 'filter_sub_type', $filter_sub_type);
					}

				?>
			</div>

			<div style="padding-top: 15px;">
				<input value="Clear Filter" type="button" class="clear-filter" onclick="document.location.href='<?php echo $_SERVER['PHP_SELF']; ?>';">
				<input value="Search" type="submit"  style="margin-left: 50px;">
			</div>
		</form>

		</div>

		<?php if (false && count($category_ids) >= 1 && $sub_filter_categories): ?>
		<div class="libary_top_filter">
		<form method="get" action="adv_search.php">
			<input name="q" type="hidden" value="<?php p($q) ?>" />
			<input name="search_by" type="hidden" value="<?php p($search_by) ?>" />
			<?php
				foreach ($category_ids as $id) {
					echo '<input name="category_ids[]" type="hidden" value="'.$id.'" />';
				}
			?>
			<table style="width: 100%;">
				<tbody><!-- tr>
					<td colspan="4"><h4>e-CCO Library: <?php echo $topGroups[reset($category_ids)]; ?></h4></td>
				</tr -->
				<tr>
					<td style="width:40px;">Filter: </td>
					<td>


							<select name="sub_filter_id">
								<?php
									foreach ($sub_filter_categories as $sub_filter_category) {
										echo '<option value="'.$sub_filter_category->id.'"';
										if ($sub_filter_id == $sub_filter_category->id) echo ' selected="selected"';
										echo '>';
										echo $sub_filter_category->name.' ('.$sub_filter_category->cnt.')</option>';
									}
								?>
						  	</select>
					</td>
					<td style="text-align:right;vertical-align: bottom;">
						<input value="Apply Filter" type="submit">
					</td>
				</tr>
			</tbody></table>
		</form>
		</div>
		<?php endif; ?>


<div style="padding-top: 30px;">

<?php
if ($items !== null) {
	if (!$items) {
		echo block_exalib_get_string('noitemsfound');
	} else {
		if ($pagingbar) {
			echo $output->render($pagingbar);
		}
		$output->item_list('public', $items);
		if ($pagingbar) {
			echo $output->render($pagingbar);
		}
	}
}
/*  else {
	?>
	<div style="text-align: center; padding: 200px 60px 0 0;" class="libary_nores">
		Not found
	</div>
	<?php
}
*/
?>
</div>
</div>
<?php
echo $output->footer();
