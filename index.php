<?php

require 'inc.php';

// disabled: all users area allowed to view this page
// require_login(EXALIB_COURSE_ID);


// disable this site, always use advanced search
header('Location: adv_search.php'.
	(($category_id = optional_param('category_id', '', PARAM_TEXT))?'?category_ids%5B%5D='.$category_id:'')
);
exit;






$PAGE->set_url('/', array());
$PAGE->set_course($SITE);

$overviewPage = new moodle_url('/blocks/exalib');

$PAGE->set_url('/blocks/exalib');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('login');
$PAGE->navbar->add(get_string('heading', 'block_exalib'), $overviewPage);

$PAGE->set_heading(get_string('heading', 'block_exalib'));

$topGroups = array(11=>'Abstracts', 12=>'Documents', 13=>'Images', 14=>'Podcasts', 15=>'Webcasts');


$category_id = optional_param('category_id', '', PARAM_TEXT);
/*
if (!$category_id) {
	redirect($overviewPage);
	exit;
}
*/

$tmp = explode('-', $category_id);
$filter_id = (int)$tmp[0];
$category_id = (int)$tmp[1];

$FILTER_CATEGORY = $DB->get_record("exalib_category", array('id'=>$filter_id));
if ($FILTER_CATEGORY) $PAGE->navbar->add($FILTER_CATEGORY->name);

$CATEGORIES = $DB->get_records_sql("SELECT category.*, count(DISTINCT item.id) AS cnt
FROM {exalib_category} AS category
LEFT JOIN {exalib_category} AS sub_category ON category.id=sub_category.parent_id
LEFT JOIN {exalib_item_category} AS ic ON (category.id=ic.category_id OR sub_category.id=ic.category_id)
JOIN {exalib_item} AS item ON item.id=ic.item_id
JOIN {exalib_item_category} AS ic_filter ON (ic_filter.item_id = item.id AND (ic_filter.category_id=".$filter_id."))
GROUP BY category.id
ORDER BY name");

$CATEGORY_PARENTS = array();
foreach ($CATEGORIES as $cat) {
	$CATEGORY_PARENTS[$cat->parent_id][$cat->id] = $cat;
}

// reverse sort for abstracts category, latest category on top
if ($CATEGORY_PARENTS[3])
	$CATEGORY_PARENTS[3] = array_reverse($CATEGORY_PARENTS[3], true);



$perpage = 5;
$page    = optional_param('page', 0, PARAM_INT);

$ITEMS = null;
$pagingbar = null;

if ($q = optional_param('q', '', PARAM_TEXT)) {
	$q = trim($q);
	
	$qparams = split(' ', $q);
	
	$sqlJoin = "";
	$sqlWhere = "";
	$sqlParams = array();
	
	if ($filter_id)
		$sqlJoin .= "	JOIN {exalib_item_category} AS ic_filter ON (ic_filter.item_id = item.id AND (ic_filter.category_id=".$filter_id."))";

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

	$pagingbar = new paging_bar($count, $page, $perpage, new moodle_url($_SERVER['REQUEST_URI']));

	$sql = "SELECT item.*
	FROM {exalib_item} AS item 
	$sqlJoin
	WHERE 1=1 $sqlWhere
	GROUP BY item.id
	ORDER BY name
	LIMIT ".$page*$perpage.', '.$perpage;
	$ITEMS = $DB->get_records_sql($sql, $sqlParams);
	
} elseif ($filter_id && $category_id) {
	$count = $DB->get_field_sql("SELECT COUNT(DISTINCT item.id)
	FROM {exalib_item} AS item
	JOIN {exalib_item_category} AS ic ON item.id=ic.item_id
	LEFT JOIN {exalib_category} AS sub_category ON ic.category_id=sub_category.id
	JOIN {exalib_item_category} AS ic_filter ON (ic_filter.item_id = item.id AND (ic_filter.category_id=".$filter_id."))
	WHERE (ic.category_id=? OR sub_category.parent_id=?)
	ORDER BY item.name", array($category_id, $category_id));

	$pagingbar = new paging_bar($count, $page, $perpage, new moodle_url($_SERVER['REQUEST_URI']));

	$ITEMS = $DB->get_records_sql("SELECT item.*
	FROM {exalib_item} AS item
	JOIN {exalib_item_category} AS ic ON item.id=ic.item_id
	LEFT JOIN {exalib_category} AS sub_category ON ic.category_id=sub_category.id
	JOIN {exalib_item_category} AS ic_filter ON (ic_filter.item_id = item.id AND (ic_filter.category_id=".$filter_id."))
	WHERE (ic.category_id=? OR sub_category.parent_id=?)
	GROUP BY item.id
	ORDER BY item.name LIMIT ".$page*$perpage.', '.$perpage, array($category_id, $category_id));
}	



$PAGE->requires->css('/blocks/exalib/css/library.css');
		
echo $OUTPUT->header();

?>
<div class="exalib_lib">

<?php
	
	if (!$filter_id) {
		?>
		<h1 class="libary_head">Welcome to the <?php echo get_string('heading', 'block_exalib');  ?>!</h1>
		
		
		<div class="libary_top_cat">
			<a class="exalib-blue-cat-lib" href="/blocks/exalib/index.php?category_id=11">Abstracts</a>
			<a class="exalib-blue-cat-lib" href="/blocks/exalib/index.php?category_id=12">Documents</a>
			<a class="exalib-blue-cat-lib" href="/blocks/exalib/index.php?category_id=13">Images</a>
			<a class="exalib-blue-cat-lib" href="/blocks/exalib/index.php?category_id=14">Podcasts</a>
			<a class="exalib-blue-cat-lib" href="/blocks/exalib/index.php?category_id=15">Webcasts</a>
			

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
	
<h1 class="libary_head"><?php echo get_string('heading', 'block_exalib');  ?>: <?php echo $FILTER_CATEGORY->name; ?></h1>

<div class="library_filter">

<form method="get" action="search.php">
	<input name="category_id" type="hidden" value="<?php echo $filter_id; ?>" />
	<input name="q" type="text" value="<?php p($q) ?>" />
	<input value="Search<?php echo (!empty($topGroups[$filter_id]) ? ' '.$topGroups[$filter_id] : '') ?>" type="submit">
</form>

<h2 class="library_filter_heading" style="margin: 30px 0 0 0;">Categories</h2>
<?php

foreach (($CATEGORY_PARENTS[3] ? $CATEGORY_PARENTS[3] : $CATEGORY_PARENTS[2]) as $cat) {
	echo '<div style="padding-bottom: 8px;"><a href="index.php?category_id='.$filter_id.'-'.$cat->id.'"'.($cat->id==$category_id?' style="color: #007BB6;">»':'>»').'&nbsp;&nbsp;'.$cat->name.' ('.$cat->cnt.')</a>';
	
	foreach ($CATEGORY_PARENTS[$cat->id] as $cat) {
		echo '<a href="index.php?category_id='.$filter_id.'-'.$cat->id.'" style="padding-left: 23px;'.($cat->id==$category_id?' color: #007BB6;">»':'">»').'&nbsp;&nbsp;'.$cat->name.' ('.$cat->cnt.')</a>';
	}
	echo '</div>';
}
?>
</div> 
<div class="library_result">

<div class="library_top_filter">
	<a href="index.php"><!--☐&nbsp;&nbsp;-->All Categories</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<?php

foreach ($topGroups as $id=>$cat) {
	echo '<a href="index.php?category_id='.$id.'"'.($id==$filter_id?' style="color: #007BB6;">»':'>»').'&nbsp;&nbsp;'.$cat.'</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
}
?>
</div>
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
