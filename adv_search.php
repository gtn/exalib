<?php

require 'inc.php';

// disabled: all users area allowed to view this page
// require_login(EXALIB_COURSE_ID);

$PAGE->set_url('/', array());
$PAGE->set_course($SITE);

$overviewPage = new moodle_url('/blocks/exalib');

$PAGE->set_url('/blocks/exalib');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('login');
$PAGE->navbar->add(get_string('heading', 'block_exalib'), $overviewPage);

$PAGE->set_heading(get_string('heading', 'block_exalib'));

$topGroups = array(11=>'Abstracts', 12=>'Documents', 13=>'Images', 14=>'Podcasts', 15=>'Webcasts');

$q = optional_param('q', '', PARAM_TEXT);
$category_ids = optional_param_array('category_ids', array(), PARAM_INT);
$sub_filter_id = optional_param('sub_filter_id', '', PARAM_INT);
$search_by = optional_param('search_by', 'all', PARAM_TEXT);



$perpage = 20;
$page    = optional_param('page', 0, PARAM_INT);

$ITEMS = null;
$pagingbar = null;

if ($category_ids) {
	$q = trim($q);
	
	$sqlJoin = "";
	$sqlWhere = "";
	$sqlJoinSubfilter = "";
	$sqlParams = array();
	
	$filter_ids = $category_ids;
	if (count($category_ids) == 1) {
		// also filter sub
		$sub_filter = optional_param('sub_filter', 0, PARAM_INT);
		if ($sub_filter) $filter_ids[] = $sub_filter;
	}
	
	
	$sqlJoin .= "	JOIN {exalib_item_category} AS ic_filter ON (ic_filter.item_id = item.id AND (ic_filter.category_id IN (".join(',',$filter_ids).")))";
	if ($sub_filter_id)
		$sqlJoinSubfilter = " JOIN {exalib_item_category} AS ic_subfilter ON (ic_subfilter.item_id = item.id AND ic_subfilter.category_id=".$sub_filter_id.")";

	if ($q) {
		$qparams = split(' ', $q);
		foreach ($qparams as $i=>$qparam) {
			if ($search_by == 'title') {
				$sqlWhere .= " AND (item.name LIKE ?) ";
				$sqlParams[] = "%$qparam%";
			} elseif ($search_by == 'author') {
				$sqlWhere .= " AND (item.authors LIKE ?) ";
				$sqlParams[] = "%$qparam%";
			} elseif ($search_by == 'source') {
				$sqlWhere .= " AND (item.source LIKE ?) ";
				$sqlParams[] = "%$qparam%";
			} else {
				$sqlJoin .= " LEFT JOIN {exalib_item_category} AS ic$i ON item.id=ic$i.item_id";
				$sqlJoin .= " LEFT JOIN {exalib_category} AS c$i ON ic$i.category_id=c$i.id";
				
				$sqlWhere .= " AND (item.name LIKE ? OR item.authors LIKE ? OR item.source LIKE ? OR c$i.name LIKE ?) ";
				$sqlParams[] = "%$qparam%";
				$sqlParams[] = "%$qparam%";
				$sqlParams[] = "%$qparam%";
				$sqlParams[] = "%$qparam%";
			}
		}
	}
	
	$sql = "SELECT COUNT(*) FROM (SELECT item.id
	FROM {exalib_item} AS item 
	$sqlJoin
	$sqlJoinSubfilter
	WHERE 1=1 $sqlWhere
	GROUP BY item.id
	) AS x";
	$count = $DB->get_field_sql($sql, $sqlParams);

	$pagingbar = new paging_bar($count, $page, $perpage, new moodle_url($_SERVER['REQUEST_URI']));

	$sql = "SELECT item.*
	FROM {exalib_item} AS item 
	$sqlJoin
	$sqlJoinSubfilter
	WHERE 1=1 $sqlWhere
	GROUP BY item.id
	ORDER BY name
	LIMIT ".$page*$perpage.', '.$perpage;
	$ITEMS = $DB->get_records_sql($sql, $sqlParams);



	// SUBFILTER
	$sql = "SELECT c.category_id AS id, COUNT(item.id) AS cnt
	FROM {exalib_item} AS item 
	$sqlJoin
	JOIN {exalib_item_category} AS c ON (c.item_id = item.id)
	WHERE 1=1 $sqlWhere
	GROUP BY c.category_id
	ORDER BY c.category_id";
	$sub_filter_categories = $DB->get_records_sql($sql, $sqlParams);
	$all_categories = $DB->get_records_sql("SELECT id, name, parent_id
	FROM {exalib_category} AS category
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
		
$PAGE->requires->js('/blocks/exalib/js/jquery.js', true);
$PAGE->requires->js('/blocks/exalib/js/library.js', true);
$PAGE->requires->js('/blocks/exalib/js/adv_search.js', true);
$PAGE->requires->css('/blocks/exalib/css/library.css');

echo $OUTPUT->header();

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


/* --- Libary new Styles ----- */ 

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

h1.libary_head, .exalib h3 {
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

.exalib {
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

a.exalib-lib {
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

a.exalib-blue-cat-lib {
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

.exalib input[type="text"] {
	box-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset;
	border-radius: 7px;
	border: 1px solid #CCCCCC;
	font-size: 14px;
	padding: 4px 3px;

}

.exalib input.libaryfront_search[type="text"] {
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
   background: url(http://gtn02.gtn-solutions.com/layouts/exalib/pix/select_arrow.png) no-repeat right #fff;
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
<div class="exalib">


		<h3>Welcome to the <?php echo get_string('heading', 'block_exalib');  ?>!</h3>
		
		<div class="libary_top_search">
		<form method="get" action="adv_search.php">
			<img src="pix/libsearch.png" alt="search" style="vertical-align: middle;padding-right: 10px;"><input name="q" type="text" class="libary_search_input" style="vertical-align: middle;" value="<?php p($q) ?>" /><table style="width: 100%;">
				<tbody><tr colspan="2&gt;
					&lt;td&gt;
						&lt;input name=" category_id"="" value="12" type="hidden">
					<td>
				</td></tr>
				<tr>
					<td colspan="2">
						<label><input type="checkbox" id="search-all-categories" value="Abstracts" <?php if (count($category_ids) == 0) echo 'checked="checked"'; ?>>All</label>&nbsp;&nbsp;&nbsp;&nbsp;
						<label><input type="checkbox" name="category_ids[]" value="11" <?php if(in_array(11, $category_ids)) echo 'checked="checked"'; ?> />Abstracts</label>&nbsp;&nbsp;&nbsp;&nbsp;
						<label><input type="checkbox" name="category_ids[]" value="12" <?php if(in_array(12, $category_ids)) echo 'checked="checked"'; ?> />Documents</label>&nbsp;&nbsp;&nbsp;&nbsp;
						<label><input type="checkbox" name="category_ids[]" value="13" <?php if(in_array(13, $category_ids)) echo 'checked="checked"'; ?> />Images</label>&nbsp;&nbsp;&nbsp;&nbsp;
						<label><input type="checkbox" name="category_ids[]" value="14" <?php if(in_array(14, $category_ids)) echo 'checked="checked"'; ?> />Podcasts</label>&nbsp;&nbsp;&nbsp;&nbsp;
						<label><input type="checkbox" name="category_ids[]" value="15" <?php if(in_array(15, $category_ids)) echo 'checked="checked"'; ?> />Webcasts</label>&nbsp;&nbsp;&nbsp;&nbsp;
						<div style="clear:both;"></div>
					</td>
				</tr>
				
				<tr>
					<td style="">
					search by:&nbsp;
					
							
						  	<select name="search_by">
						  		<option value="all">all</option>
						  		<option value="title" <?php if ($search_by == 'title') echo 'selected="selected"'; ?>>Title</option>
						  		<option value="author" <?php if ($search_by == 'author') echo 'selected="selected"'; ?>>Author</option>
						  		<option value="source" <?php if ($search_by == 'source') echo 'selected="selected"'; ?>>Source</option>
						  	</select>
			
					</td>
					<td style="text-align:right;vertical-align: bottom;">
						<input value="Search" type="submit">
					</td>
				</tr>
			</tbody></table>
				
				
		</form>
		
		</div>
		
		<?php if (count($category_ids) >= 1 && $sub_filter_categories): ?>
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
				<tbody>
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



<div class="library_result_main">

<?php
if ($ITEMS !== null) {
	if (!$ITEMS) {
		echo 'No Items found';
	} else {
		if ($pagingbar) echo $OUTPUT->render($pagingbar);
		print_items($ITEMS);
		if ($pagingbar) echo $OUTPUT->render($pagingbar);
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
echo $OUTPUT->footer();
