<?php

if (!defined('IS_ADMIN_MODE')) define('IS_ADMIN_MODE', 0);

require 'inc.php';



if ($importlatein = optional_param('importlatein', '0', PARAM_TEXT)) {
	block_exalib_require_admin();

	require 'importlatein.php';

	if ($importlatein == '1') {
		block_exalib_importlatein();
	}
	if ($importlatein == 'urls') {
		block_exalib_importlatein_urls();
	}
	if ($importlatein == '2') {
		block_exalib_importlatein2();
	}
	if ($importlatein == '3') {
		block_exalib_importlatein3();
	}
	exit;
}




if (IS_ADMIN_MODE) {
	block_exalib_require_creator();
} else {
	block_exalib_require_use();
}





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
if (IS_ADMIN_MODE) $PAGE->navbar->add('Administration', 'admin.php');


$CURRENT_CATEGORY = block_exalib_category_manager::getCategory($category_id);
$CURRENT_CATEGORY_SUB_IDS = $CURRENT_CATEGORY ? $CURRENT_CATEGORY->self_inc_all_sub_ids : array(-9999);
$CURRENT_CATEGORY_PARENTS = block_exalib_category_manager::getCategoryParentIDs($category_id);

if (IS_ADMIN_MODE) {
	require('admin.actions.inc.php');
}





$perpage = 20;
$page    = optional_param('page', 0, PARAM_INT);

$ITEMS = null;
$pagingbar = null;
$SHOW = null;

if (IS_ADMIN_MODE) {
	$sqlWhere = "";
} else {
	$sqlWhere = "AND IFNULL(item.hidden,0)=0 AND (IFNULL(item.online_from,0)=0 OR (item.online_from <= ".time()." AND item.online_to >= ".time()."))";
}

if ($q = optional_param('q', '', PARAM_TEXT)) {
	$SHOW = 'search';
	
	$q = trim($q);
	
	$qparams = preg_split('!\s+!', $q);
	
	$sqlJoin = "";
	$sqlParams = array();
	
	if ($CURRENT_CATEGORY) {
		$sqlJoin .= "	JOIN {exalib_item_category} AS ic ON (ic.item_id = item.id AND ic.category_id IN (".join(',', $CURRENT_CATEGORY_SUB_IDS)."))";
	}
	
	foreach ($qparams as $i=>$qparam) {
		$sqlJoin .= " LEFT JOIN {exalib_item_category} AS ic$i ON item.id=ic$i.item_id";
		$sqlJoin .= " LEFT JOIN {exalib_category} AS c$i ON ic$i.category_id=c$i.id";
		// $sqlJoin .= " LEFT JOIN {exalib_item_category} AS ic$i ON item.id=ic$i.item_id AND ic$i.category_id=c$i";
		$sqlWhere .= " AND (item.link LIKE ? OR item.source LIKE ? OR item.file LIKE ? OR item.name LIKE ? OR item.authors LIKE ? OR item.content LIKE ? OR item.link_titel LIKE ? OR c$i.name LIKE ?) ";
		$sqlParams[] = "%$qparam%";
		$sqlParams[] = "%$qparam%";
		$sqlParams[] = "%$qparam%";
		$sqlParams[] = "%$qparam%";
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
	
} elseif ($CURRENT_CATEGORY) {
	$SHOW = 'category';

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
		WHERE 1=1 $sqlWhere
		GROUP BY item.id
		ORDER BY GREATEST(IFNULL(time_created,0),IFNULL(time_modified,0)) DESC
		LIMIT ".$page*$perpage.', '.$perpage."
	");
} else {
	// latest changes
	$SHOW = 'latest_changes';

	$ITEMS = $DB->get_records_sql("
		SELECT item.*
		FROM {exalib_item} AS item
		WHERE 1=1 $sqlWhere
		GROUP BY item.id
		ORDER BY GREATEST(IFNULL(time_created,0),IFNULL(time_modified,0)) DESC
		LIMIT 20
	");
}



$PAGE->requires->css('/blocks/exalib/css/library.css');
$PAGE->requires->css('/blocks/exalib/css/skin-lion/ui.easytree.css');

$PAGE->requires->js('/blocks/exalib/js/jquery.js', true);
$PAGE->requires->js('/blocks/exalib/js/jquery.easytree.js', true);
$PAGE->requires->js('/blocks/exalib/js/exalib.js', true);
		
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
				<input value="<?php echo exalib_t('en:Search', 'de:Suchen'); ?>" type="submit" class="libaryfront_searchsub">
			</form>
			<?php else: ?>
			<form method="get" action="search.php">
				<input name="q" type="text" value="<?php p($q) ?>" style="width: 240px;" class="libaryfront_search" />
				<input value="<?php echo exalib_t('en:Search', 'de:Suchen'); ?>" type="submit" class="libaryfront_searchsub">
			</form>
			<?php endif; ?>

			<?php
			if ($ITEMS !== null) {
				echo '<h1 class="library_result_heading">'.exalib_t('en:Results', 'de:Ergebnisse').'</h1>';
				
				if (!$ITEMS) {
					exalib_t('en:No Items found', 'de:Keine Einträge gefunden');
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
		<?php if ($CURRENT_CATEGORY): ?>
		<select name="category_id">
			<option value="<?php echo $CURRENT_CATEGORY->id; ?>"><?php echo exalib_t('en:In this Category', 'de:in dieser Kategorie'); ?></option>
			<option value="0"><?php echo exalib_t('en:Whole Library', 'de:ganze Bibliothek'); ?></option>
		</select>
		<?php endif; ?>
	<input value="<?php echo exalib_t('en:Search', 'de:Suchen'); ?>" type="submit">
</form>

<?php

if (IS_ADMIN_MODE && block_exalib_is_admin()) {
	echo '<a href="admin.php?show=categories">Manage Categories</a>';
}

echo '<div id="exalib-categories"><ul>';
echo block_exalib_category_manager::walkTree(function($cat, $subOutput) {
	global $url_category, $category_id, $CURRENT_CATEGORY_PARENTS;
	
	if (!IS_ADMIN_MODE && !$cat->cnt_inc_subs) {
		// hide empty categories
		return;
	}
	
	$output = '<li id="exalib-menu-item-'.$cat->id.'" class="'.
		($subOutput ? 'isFolder' : '').
		(in_array($cat->id, $CURRENT_CATEGORY_PARENTS)?' isExpanded':'').
		($cat->id==$category_id?' isActive':'').'">';
	$output .= '<a class="library_categories_item_title" href="'.$url_category->out(true, array('category_id' => $cat->id)).'">'.$cat->name.' ('.$cat->cnt_inc_subs.')'.'</a>';
	
	if ($subOutput) {
		$output .= '<ul>'.$subOutput.'</ul>';
	}
	
	echo '</li>';
	
	return $output;
});
echo '</ul></div>';

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

if (IS_ADMIN_MODE) {
	?><a href="<?php echo $url_add; ?>">Add new Entry</a><?php
}

if ($SHOW == 'latest_changes')
	echo '<h1 class="library_result_heading">'.exalib_t('de:Letzte Änderungen').'</h1>';
else 
	echo '<h1 class="library_result_heading">'.exalib_t('en:Results', 'de:Ergebnisse').'</h1>';

if (!$ITEMS) {
	echo exalib_t('en:No Items found', 'de:Keine Einträge gefunden');
} else {
	if ($pagingbar) echo $OUTPUT->render($pagingbar);
	print_items($ITEMS, IS_ADMIN_MODE);
	if ($pagingbar) echo $OUTPUT->render($pagingbar);
}

?>
</div>
</div>
<?php
echo $OUTPUT->footer();
