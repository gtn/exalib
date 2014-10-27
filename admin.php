<?php

require 'inc.php';

$PAGE->set_url('/', array());
$PAGE->set_course($SITE);

require_login(EXALIB_COURSE_ID);
if (!has_capability('moodle/site:config', context_system::instance())) {
	throw new require_login_exception('you are no admin, please login first');
}

$PAGE->set_url('/blocks/exalib');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');

$PAGE->set_heading(get_string('heading', 'block_exalib'));





$CATEGORIES = $DB->get_records_sql("SELECT category.*, count(item.id) AS cnt
FROM {exalib_category} AS category
LEFT JOIN {exalib_item_category} AS ic ON category.id=ic.category_id
LEFT JOIN {exalib_item} AS item ON item.id=ic.item_id
GROUP BY category.id
ORDER BY name");

$parents = array();
foreach ($CATEGORIES as $cat) {
	$parents[$cat->parent_id][$cat->id] = $cat;
}





$show = optional_param('show', '', PARAM_TEXT);

if ($show == 'delete') {
	$id = required_param('id', PARAM_INT);
	$item = $DB->get_record('exalib_item', array('id'=>$id));
	
	$confirm = optional_param("confirm", "", PARAM_BOOL);
	
	if (data_submitted() && $confirm && confirm_sesskey()) {
		$DB->delete_records('exalib_item', array('id'=>$id));
		$DB->delete_records('exalib_item_category', array("item_id" => $id));
		redirect('admin.php');
	} else {
		$optionsyes = array('id' => $id, 'show' => 'delete', 'confirm' => 1, 'sesskey' => sesskey());
		$optionsno = array();

		echo $OUTPUT->header();
	 
		echo '<br />';
		echo $OUTPUT->confirm('delete '.$item->name.'?', new moodle_url('admin.php', $optionsyes), new moodle_url('admin.php', $optionsno));

		echo $OUTPUT->footer();
		die;
	}
}

if ($show == 'edit' || $show == 'add') {
	if ($show == 'add') {
		$item = new StdClass;
	} else {
		$id = required_param('id', PARAM_INT);
		$item = $DB->get_record('exalib_item', array('id'=>$id));
	}
	
	require_once("$CFG->libdir/formslib.php");
	 
	class item_edit_form extends moodleform {
	 
		function definition() {
			global $CFG;
	 
			$mform =& $this->_form; // Don't forget the underscore! 
	 
			$mform->addElement('text', 'name', 'Name', 'size="100"');
			$mform->addRule('name', 'Name required', 'required', null, 'server');
			
			$mform->addElement('text', 'source', 'Source', 'size="100"');

			$mform->addElement('header', 'content', 'Content');

			$mform->addElement('text', 'resource_id', 'Resource', 'size="100"');
			$mform->addElement('text', 'link', 'Link', 'size="100"');
			
			$mform->addElement('filemanager', 'file', 'File', null, array('subdirs' => false, 'maxfiles' => 1));

			$mform->closeHeaderBefore('authors');
			
			$mform->addElement('text', 'authors', 'Authors', 'size="100"');

			$text = '';

			global $DB, $parents;
			foreach ($parents[0] as $cat) {
				$text .= '<b>'.$cat->name.'</b><br />';

				foreach ($parents[$cat->id] as $cat) {
					$text .= '<input type="checkbox" name="CATEGORIES[]" value="'.$cat->id.'" '.
							(in_array($cat->id, $this->_customdata['itemCategories'])?'checked ':'').'/>'.$cat->name.'<br />';
					
					foreach ($parents[$cat->id] as $cat) {
						$text .= '&nbsp;&nbsp;&gt;&nbsp;<input type="checkbox" name="CATEGORIES[]" value="'.$cat->id.'" '.
							(in_array($cat->id, $this->_customdata['itemCategories'])?'checked ':'').'/>'.$cat->name.'<br />';
					}
				}
			}
			
			$mform->addElement('static', 'description', 'Groups', $text);

			$this->add_action_buttons();
		}
	}
	


	$itemCategories = $DB->get_records_sql_menu("SELECT id, id AS val
	FROM {exalib_category} AS category
	LEFT JOIN {exalib_item_category} AS ic ON category.id=ic.category_id
	WHERE ic.item_id=?", array($item->id));
	
	$item_edit_form = new item_edit_form($_SERVER['REQUEST_URI'], array('itemCategories'=>$itemCategories));

	if ($item_edit_form->is_cancelled()){

	} else if ($fromform = $item_edit_form->get_data()){
		// edit/add
		
		if ($item->id) {
			$fromform->id = $item->id;
			$DB->update_record('exalib_item', $fromform);
		} else {
			try {
			$fromform->id = $DB->insert_record('exalib_item', $fromform);
			} catch (Exception $e) { var_dump($e); exit;}
		}

		// save file
		$context = get_context_instance(CONTEXT_SYSTEM);
		file_save_draft_area_files($fromform->file, $context->id, 'block_exalib', 'item_file', $fromform->id, null);
				
		// save categories
		$DB->delete_records('exalib_item_category', array("item_id" => $fromform->id));
		foreach ($_REQUEST['CATEGORIES'] as $tmp=>$categoryId) {
			$DB->execute('INSERT INTO {exalib_item_category} (item_id, category_id) VALUES (?, ?)', array($fromform->id, $categoryId));
		}
		
		redirect('admin.php');
		exit;
		
	} else {
		// display form
		
		echo $OUTPUT->header();
	 
		$item_edit_form->set_data($item);
		$item_edit_form->display();
		
		echo $OUTPUT->footer();
	}

	exit;
}





$perpage = 5;
$page    = optional_param('page', 0, PARAM_INT);
$pagingbar = null;

$ITEMS = null;
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
} elseif ($category_id = optional_param('category_id', 0, PARAM_INT)) {
	$ITEMS = $DB->get_records_sql("SELECT *
	FROM {exalib_item} AS item
	JOIN {exalib_item_category} AS ic ON item.id=ic.item_id AND ic.category_id=?
	ORDER BY name", array($category_id));
} else {
	$ITEMS = $DB->get_records_sql("SELECT *
	FROM {exalib_item} AS item
	ORDER BY name");
}
		




		
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
	border: 1px solid #007BB6;
}
.library-item .head {
	color: #007BB6;
	font-size: 21px;
	font-weight: bold;
}
</style>
<?php

?>
<a href="admin.php?show=add">Add new</a>
<form method="get" action="admin.php">
	<input type="text" name="q" />
	<input type="submit" value="Fulltext Search" />
</form>
<?php

echo '<h1>Filter</h1>';
foreach ($parents[0] as $cat) {
	echo '<h2 style="margin: 30px 0 0 0;">'.$cat->name.'</h1>';

	foreach ($parents[$cat->id] as $cat) {
		echo '<a href="admin.php?category_id='.$cat->id.'">'.$cat->name.' ('.$cat->cnt.')</a><br />';
		
		foreach ($parents[$cat->id] as $cat) {
			echo '&nbsp;&nbsp;&gt;&nbsp;<a href="admin.php?category_id='.$cat->id.'">'.$cat->name.' ('.$cat->cnt.')</a><br />';
		}
	}
}

echo '<hr style="margin: 20px 0"/>';

echo '<h1>Ergebnisse</h1>';
if ($ITEMS !== null) {
	if (!$ITEMS) {
		echo 'no items found';
	} else {
		if ($pagingbar) echo $OUTPUT->render($pagingbar);
		print_items($ITEMS, true);
		if ($pagingbar) echo $OUTPUT->render($pagingbar);
	}
}

echo $OUTPUT->footer();
