<?php

if (!defined('IS_ADMIN_MODE')) define('IS_ADMIN_MODE', 0);

require 'inc.php';

if (IS_ADMIN_MODE) {
	block_exalib_require_admin();
} else {
	block_exalib_require_use();
}


/*
// disable this site, always use advanced search
header('Location: adv_search.php'.
	(($category_id = optional_param('category_id', '', PARAM_TEXT))?'?category_ids%5B%5D='.$category_id:'')
);
exit;
*/






$url_overview = new moodle_url('/blocks/exalib');
$url_page = block_exalib_new_moodle_url();
$url_search = new moodle_url($url_page, array('page'=>null, 'q'=>null, 'category_id' => null));
$url_add = new moodle_url($url_page, array('show' => 'add'));
$url_category = new moodle_url($url_page, array('page'=>null, 'q'=>null, 'category_id' => null));

$PAGE->set_url($url_page);
$PAGE->set_context(context_system::instance());
// $PAGE->set_pagelayout('login');
$PAGE->navbar->add(get_string('heading', 'block_exalib'), $url_overview);

$PAGE->set_heading(get_string('heading', 'block_exalib'));

// $topGroups = array(11=>'Abstracts', 12=>'Documents', 13=>'Images', 14=>'Podcasts', 15=>'Webcasts');


$category_id = optional_param('category_id', '', PARAM_INT);
$filter_id = 0;

//$FILTER_CATEGORY = $DB->get_record("exalib_category", array('id'=>$filter_id));
//if ($FILTER_CATEGORY) $PAGE->navbar->add($FILTER_CATEGORY->name);

function block_exalib_load_categories() {
	global $DB, $CATEGORIES, $CATEGORY_BY_PARENT;
	
	$CATEGORIES = $DB->get_records_sql("SELECT category.*, count(DISTINCT item.id) AS cnt
	FROM {exalib_category} AS category
	LEFT JOIN {exalib_item_category} AS ic ON (category.id=ic.category_id)
	JOIN {exalib_item} AS item ON item.id=ic.item_id
	GROUP BY category.id
	ORDER BY name");

	$CATEGORY_BY_PARENT = array();

	foreach ($CATEGORIES as &$cat) {

		$CATEGORY_BY_PARENT[$cat->parent_id][$cat->id] = &$cat;
		
		$cnt = $cat->cnt;
		$cat_id = $cat->id;

		// find parents
		while (true) {
			if (!isset($cat->cnt_inc_subs)) $cat->cnt_inc_subs = 0;
			$cat->cnt_inc_subs += $cnt;
			
			if (!isset($cat->self_inc_all_sub_ids)) $cat->self_inc_all_sub_ids = array();
			$cat->self_inc_all_sub_ids[] = $cat_id;
		
			if (($cat->parent_id > 0) && isset($CATEGORIES[$cat->parent_id])) {
				// $parentCat
				$cat =& $CATEGORIES[$cat->parent_id];
			} else {
				break;
			}
		}
	}
	unset($cat);
}
block_exalib_load_categories();


$CURRENT_CATEGORY = $category_id && isset($CATEGORIES[$category_id]) ? $CATEGORIES[$category_id] : null;
$CURRENT_CATEGORY_SUB_IDS = $CURRENT_CATEGORY ? $CURRENT_CATEGORY->self_inc_all_sub_ids : array(-9999);

if (IS_ADMIN_MODE) {
	require('admin.actions.inc.php');
}





$perpage = 5;
$page    = optional_param('page', 0, PARAM_INT);

$ITEMS = null;
$pagingbar = null;

if ($q = optional_param('q', '', PARAM_TEXT)) {
	$q = trim($q);
	
	$qparams = preg_split('!\s+!', $q);
	
	$sqlJoin = "";
	$sqlWhere = "";
	$sqlParams = array();
	
	if ($CURRENT_CATEGORY) {
		$sqlJoin .= "	JOIN {exalib_item_category} AS ic ON (ic.item_id = item.id AND ic.category_id IN (".join(',', $CURRENT_CATEGORY_SUB_IDS)."))";
	}
	
	foreach ($qparams as $i=>$qparam) {
		$sqlJoin .= " LEFT JOIN {exalib_item_category} AS ic$i ON item.id=ic$i.item_id";
		$sqlJoin .= " LEFT JOIN {exalib_category} AS c$i ON ic$i.category_id=c$i.id";
		// $sqlJoin .= " LEFT JOIN {exalib_item_category} AS ic$i ON item.id=ic$i.item_id AND ic$i.category_id=c$i";
		$sqlWhere .= " AND (item.name LIKE ? OR item.authors LIKE ? OR item.source LIKE ? OR c$i.name LIKE ?) ";
		$sqlParams[] = "%$qparam%";
		$sqlParams[] = "%$qparam%";
		$sqlParams[] = "%$qparam%";
		$sqlParams[] = "%$qparam%";
	}
	
	// JOIN {exalib_item_category} AS ic ON item.id=ic.item_id AND ic.category_id=?

	$sql = "SELECT COUNT(*) FROM (SELECT item.id
	FROM {exalib_item} AS item 
	$sqlJoin
	WHERE 1=1 $sqlWhere
	GROUP BY item.id
	) AS x";
	$count = $DB->get_field_sql($sql, $sqlParams);

	$pagingbar = new paging_bar($count, $page, $perpage, $url_page);

	$sql = "SELECT item.*
	FROM {exalib_item} AS item 
	$sqlJoin
	WHERE 1=1 $sqlWhere
	GROUP BY item.id
	ORDER BY name
	LIMIT ".$page*$perpage.', '.$perpage;
	$ITEMS = $DB->get_records_sql($sql, $sqlParams);
	
} else {
	$sqlJoin = "	JOIN {exalib_item_category} AS ic ON (ic.item_id = item.id AND ic.category_id IN (".join(',', $CURRENT_CATEGORY_SUB_IDS)."))";

	$count = $DB->get_field_sql("
		SELECT COUNT(DISTINCT item.id)
		FROM {exalib_item} AS item
		JOIN {exalib_item_category} AS ic ON (item.id=ic.item_id AND ic.category_id IN (".join(',', $CURRENT_CATEGORY_SUB_IDS)."))
		ORDER BY item.name
	");

	$pagingbar = new paging_bar($count, $page, $perpage, $url_page);

	$ITEMS = $DB->get_records_sql("
		SELECT item.*
		FROM {exalib_item} AS item
		JOIN {exalib_item_category} AS ic ON (item.id=ic.item_id AND ic.category_id IN (".join(',', $CURRENT_CATEGORY_SUB_IDS)."))
		GROUP BY item.id
		ORDER BY item.name
		LIMIT ".$page*$perpage.', '.$perpage."
	");
}	



$PAGE->requires->css('/blocks/exalib/css/library.css');
		
echo $OUTPUT->header();

?>
<div class="exalib_lib">

<?php
	
	if (false && !$filter_id) {
		?>
		<h1 class="libary_head">Welcome to the <?php echo get_string('heading', 'block_exalib');  ?>!</h1>
		
		
		<div class="libary_top_cat">
			<a class="exalib-blue-cat-lib" href="<?php echo $CFG->wwwroot; ?>/blocks/exalib/index.php?category_id=11">Abstracts</a>
			<a class="exalib-blue-cat-lib" href="<?php echo $CFG->wwwroot; ?>/blocks/exalib/index.php?category_id=12">Documents</a>
			<a class="exalib-blue-cat-lib" href="<?php echo $CFG->wwwroot; ?>/blocks/exalib/index.php?category_id=13">Images</a>
			<a class="exalib-blue-cat-lib" href="<?php echo $CFG->wwwroot; ?>/blocks/exalib/index.php?category_id=14">Podcasts</a>
			<a class="exalib-blue-cat-lib" href="<?php echo $CFG->wwwroot; ?>/blocks/exalib/index.php?category_id=15">Webcasts</a>
			

		</div>
		
		
		<!-- <div class="library_filter_main">
			<a href="index.php?category_id=11"><img src="<? echo $CFG->wwwroot; ?>/pluginfile.php/213/course/section/154/ecoo_abstracts.png" height="43" width="212" /></a>
			<a href="index.php?category_id=12"><img src="<? echo $CFG->wwwroot; ?>/pluginfile.php/213/course/section/154/ecoo_documents.png" height="43" width="212" /></a>
			<a href="index.php?category_id=13"><img src="<? echo $CFG->wwwroot; ?>/pluginfile.php/213/course/section/154/ecoo_images.png" height="43" width="212" /></a>
			<a href="index.php?category_id=14"><img src="<? echo $CFG->wwwroot; ?>/pluginfile.php/213/course/section/154/ecoo_podcasts.png" height="43" width="212" /></a>
			<a href="index.php?category_id=15"><img src="<? echo $CFG->wwwroot; ?>/pluginfile.php/213/course/section/154/ecoo_webcasts.png" height="43" width="212" /></a>
		</div> -->

		<div class="library_result library_result_main">

			<?php if (!$q): ?>
			<br /><br /><br />
			<form method="get" action="search.php">
				<input name="q" type="text" value="<?php p($q) ?>" style="width: 240px;" class="libaryfront_search" />
				<input value="Search" type="submit" class="libaryfront_searchsub">
			</form>
			<?php else: ?>
			<form method="get" action="search.php">
				<input name="q" type="text" value="<?php p($q) ?>" style="width: 240px;" class="libaryfront_search" />
				<input value="Search" type="submit" class="libaryfront_searchsub">
			</form>
			<?php endif; ?>

			<?php
			if ($ITEMS !== null) {
				echo '<h1 class="library_result_heading">Results</h1>';
				
				if (!$ITEMS) {
					echo 'no items found';
				} else {
					if ($pagingbar) echo $OUTPUT->render($pagingbar);
					print_items($ITEMS);
					if ($pagingbar) echo $OUTPUT->render($pagingbar);
				}
			}
			?>
		</div>
		<?php
		echo $OUTPUT->footer();
		exit;
	}
?>
	
<h1 class="libary_head"><?php echo get_string('heading', 'block_exalib');  ?><?php if ($CURRENT_CATEGORY) echo ': '.$CURRENT_CATEGORY->name; ?></h1>

<div class="library_categories">

<form method="get" action="<?php echo $url_search; ?>">
	<?php echo html_writer::input_hidden_params($url_search); ?>
	<input name="q" type="text" value="<?php p($q) ?>" />
		<select name="category_id">
			<?php if ($category_id): ?>
			<option value="<?php echo $category_id; ?>">in this category</option>
			<?php endif; ?>
			<option value="0">whole library</option>
		</select>
	<input value="Search" type="submit">
</form>

<?php

function block_exalib_print_nav($level=0, $parent=0) {
	global $CATEGORY_BY_PARENT, $url_category, $category_id;
	
	if (empty($CATEGORY_BY_PARENT[$parent])) return;
	
	if ($level > 1) echo '<div class="library_categories_subgroup">';

	foreach ($CATEGORY_BY_PARENT[$parent] as $cat) {
		echo '<div class="library_categories_item library_categories_item-level'.$level.($cat->id==$category_id?' selected':'').'">';
		
		echo '<a class="library_categories_item_title" href="'.$url_category->out(true, array('category_id' => $cat->id)).'">'.$cat->name.' ('.$cat->cnt_inc_subs.')</a>';
		
		if (IS_ADMIN_MODE) {
			echo '<span class="library_categories_item_buttons"><span>';
			echo '<a href="admin.php?show=category_add&category_id='.$cat->id.'">add sub category</a><br />';
			if ($level > 0) {
				echo '<a href="admin.php?show=category_edit&category_id='.$cat->id.'">edit category</a><br />';
				echo '<a href="admin.php?show=category_delete&category_id='.$cat->id.'">delete category</a>';
			}
			echo '</span></span>';
		}

		echo '</div>';
		
		block_exalib_print_nav($level+1, $cat->id);
	}
	if ($level > 1) echo '</div>';
}

echo block_exalib_print_nav();

?>
</div> 
<div class="library_result">

<?php

/*
<div class="library_top_filter">
	<a href="index.php"><!--☐&nbsp;&nbsp;-->All Categories</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
foreach ($topGroups as $id=>$cat) {
	echo '<a href="index.php?category_id='.$id.'"'.($id==$filter_id?' style="color: #007BB6;">»':'>»').'&nbsp;&nbsp;'.$cat.'</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
}
</div>
*/

if ($ITEMS !== null) {
	if (IS_ADMIN_MODE) {
		?><a href="<?php echo $url_add; ?>">Add new Entry</a><?php
	}

	echo '<h1 class="library_result_heading">Results</h1>';
	
	if (!$ITEMS) {
		echo 'no items found';
	} else {
		if ($pagingbar) echo $OUTPUT->render($pagingbar);
		print_items($ITEMS, IS_ADMIN_MODE);
		if ($pagingbar) echo $OUTPUT->render($pagingbar);
	}
} else {
	?>
	<div style="text-align: center; padding: 200px 60px 0 0;" class="libary_nores">
		Please choose a category on the left to help narrow your search,<br />or simply type a keyword in the search box.
	</div>
	<?php
}
?>
</div>
</div>
<?php
echo $OUTPUT->footer();
