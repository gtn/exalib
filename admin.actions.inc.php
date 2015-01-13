<?php

require_once $CFG->libdir.'/formslib.php';
require_once $CFG->libdir.'/filelib.php';

$show = optional_param('show', '', PARAM_TEXT);

if ($show == 'categories') {
	block_exalib_require_admin();

	$PAGE->navbar->add('Categories');
	
	echo $OUTPUT->header();

	echo '<div><a href="admin.php?show=category_add&parent_id=0">add main category</a></div>';
	
	block_exalib_category_manager::walkTree(function($cat) {
	
		echo '<div>&bullet; ';
		
		echo $cat->name.' ('.$cat->cnt_inc_subs.') ';
		
		echo '<span class="library_categories_item_buttons"><span>';
		echo '<a href="admin.php?show=category_edit&category_id='.$cat->id.'">edit</a>';
		echo ' | <a href="admin.php?show=category_delete&category_id='.$cat->id.'">delete</a>';
		echo '</span></span>';

		echo '</div>';

		echo '<div style="padding-left: 30px;">';
		echo '<a href="admin.php?show=category_add&parent_id='.$cat->id.'">add category here</a>';
		
	}, function() {
		echo '</div>';
	});
 
	echo $OUTPUT->footer();
	exit;
}

if ($show == 'category_delete') {
	block_exalib_require_admin();
	
	$confirm = optional_param("confirm", "", PARAM_BOOL);
	$category_id = required_param('category_id', PARAM_INT);
	
	if (data_submitted() && $confirm && confirm_sesskey()) {
		$DB->delete_records('exalib_category', array(
			'id' => required_param('category_id', PARAM_INT)
		));
		redirect('admin.php?show=categories');
		exit;
	} else {
		$optionsyes = array('category_id' => $category_id, 'show' => 'category_delete', 'confirm' => 1, 'sesskey' => sesskey());
		$optionsno = array();

		echo $OUTPUT->header();
	 
		echo '<br />';
		echo $OUTPUT->confirm('delete category '.block_exalib_category_manager::getCategory($category_id)->name.'?', new moodle_url('admin.php', $optionsyes), new moodle_url('admin.php', $optionsno));

		echo $OUTPUT->footer();
		exit;
	}
	exit;
}
if (($show == 'category_add') || ($show == 'category_edit')) {
	block_exalib_require_admin();
	
	if ($show == 'category_add') {
		$category = (object)array(
			'id' => 0,
			'parent_id' => required_param('parent_id', PARAM_INT)
		);
	} else {
		$category = $DB->get_record('exalib_category', array('id'=>required_param('category_id', PARAM_INT)));
	}
	
	require_once("$CFG->libdir/formslib.php");
	 
	class item_edit_form extends moodleform {
	 
		var $_category_select = array(0 => 'root');
		
		function definition() {
			global $CFG;
	 
			block_exalib_category_manager::walkTree(function($cat) {
				$this->_category_select[$cat->id] = str_repeat('&nbsp;&nbsp;&nbsp;', $cat->level).'&bullet; '.$cat->name;
			}, false);

			$mform =& $this->_form; // Don't forget the underscore! 
	 
			$mform->addElement('text', 'name', 'Name', 'size="100"');
			$mform->setType('name', PARAM_TEXT);
			$mform->addRule('name', 'Name required', 'required', null, 'server');
			
			$mform->addElement('select', 'parent_id', 'Parent', $this->_category_select);

			// $mform->addElement('static', 'description', 'Groups', $this->get_categories());

			$this->add_action_buttons();
		}

		function get_categories() {
			return ;
		}
	}
	


	$category_edit_form = new item_edit_form($_SERVER['REQUEST_URI']);

	if ($category_edit_form->is_cancelled()){

	} else if ($fromform = $category_edit_form->get_data()){
		// edit/add
		
		if (!empty($category->id)) {
			$fromform->id = $category->id;
			$DB->update_record('exalib_category', $fromform);
		} else {
			try {
				$fromform->id = $DB->insert_record('exalib_category', $fromform);
			} catch (Exception $e) { var_dump($e); exit;}
		}

		redirect('admin.php?show=categories');
		exit;
		
	} else {
		// display form
		
		echo $OUTPUT->header();
	 
		$category_edit_form->set_data($category);
		$category_edit_form->display();
		
		echo $OUTPUT->footer();
	}

	exit;
}

if ($show == 'delete') {
	$id = required_param('id', PARAM_INT);
	$item = $DB->get_record('exalib_item', array('id'=>$id));
	
	block_exalib_require_can_edit_item($item);
	
	$confirm = optional_param("confirm", "", PARAM_BOOL);
	
	if (data_submitted() && $confirm && confirm_sesskey()) {
		$DB->delete_records('exalib_item', array('id'=>$id));
		$DB->delete_records('exalib_item_category', array("item_id" => $id));
		redirect('admin.php');
		exit;
	} else {
		$optionsyes = array('id' => $id, 'show' => 'delete', 'confirm' => 1, 'sesskey' => sesskey());
		$optionsno = array();

		echo $OUTPUT->header();
	 
		echo '<br />';
		echo $OUTPUT->confirm('delete '.$item->name.'?', new moodle_url('admin.php', $optionsyes), new moodle_url('admin.php', $optionsno));

		echo $OUTPUT->footer();
		exit;
	}
}

if ($show == 'edit' || $show == 'add') {
	$category_id = optional_param('category_id', '', PARAM_INT);
	$textfieldoptions = array('trusttext'=>true, 'subdirs'=>true, 'maxfiles'=>99, 'context'=>context_system::instance());
	$fileoptions = array('subdirs'=>false, 'maxfiles'=> 1);

	if ($show == 'add') {
		$id = 0;
		$item = new StdClass;
		$item->contentformat = FORMAT_HTML;
	
		block_exalib_require_creator();
	} else {
		$id = required_param('id', PARAM_INT);
		$item = $DB->get_record('exalib_item', array('id'=>$id));
		
		block_exalib_require_can_edit_item($item);
		
		$item->contentformat = FORMAT_HTML;
		$item = file_prepare_standard_editor($item, 'content', $textfieldoptions, context_system::instance(), 'block_exalib', 'item_content', $item->id);
		$item = file_prepare_standard_filemanager($item, 'file', $fileoptions, context_system::instance(), 'block_exalib', 'item_file', $item->id);
	}
	
	require_once("$CFG->libdir/formslib.php");
	 
	class item_edit_form extends moodleform {
	 
		function definition() {
			global $CFG;
	 
			$mform =& $this->_form; // Don't forget the underscore! 
	 
			$mform->addElement('text', 'name', 'Name', 'size="100"');
			$mform->setType('name', PARAM_TEXT);
			$mform->addRule('name', 'Name required', 'required', null, 'server');
			
			$mform->addElement('text', 'source', 'Source', 'size="100"');
			$mform->setType('source', PARAM_TEXT);

			$mform->addElement('header', 'contentheader', 'Content');

			$mform->addElement('text', 'link_titel', 'Link Titel', 'size="100"');
			$mform->setType('link_titel', PARAM_TEXT);
			
			$mform->addElement('text', 'link', 'Link', 'size="100"');
			$mform->setType('link', PARAM_TEXT);

			$mform->addElement('filemanager', 'file_filemanager', 'File', null, $this->_customdata['fileoptions']);

			$mform->addElement('editor', 'content_editor', 'Content', 'rows="20" cols="50" style="width: 95%"');
			$mform->setType('content', PARAM_RAW);

			$mform->addElement('text', 'authors', 'Authors', 'size="100"');
			$mform->setType('authors', PARAM_TEXT);

			$mform->addElement('header', 'onlineheader', 'Online Settings');

			$mform->addElement('date_selector', 'online_from', 'Online From', array(
				'startyear' => 2014, 
				'stopyear'  => date('Y'),
				'optional'  => true
			));
			$mform->addElement('date_selector', 'online_to', 'Online To', array(
				'startyear' => 2014, 
				'stopyear'  => date('Y'),
				'optional'  => true
			));
			$mform->addElement('checkbox', 'hidden', 'Hidden');

			$mform->addElement('header', 'categoriesheader', 'Categories');

			$mform->addElement('static', 'categories', 'Groups', $this->get_categories());

			$this->add_action_buttons();
		}

		function get_categories() {
			return block_exalib_category_manager::walkTree(function($cat, $subOutput) {
				return '<div style="padding-left: '.(20*$cat->level).'px;"><input type="checkbox" name="CATEGORIES[]" value="'.$cat->id.'" '.
						(in_array($cat->id, $this->_customdata['itemCategories'])?'checked ':'').'/>'.
						($cat->level == 0 ? '<b>'.$cat->name.'</b>' : $cat->name).
						'</div>'.$subOutput;
			});
		}
	}
	


	$itemCategories = $DB->get_records_sql_menu("SELECT category.id, category.id AS val
	FROM {exalib_category} AS category
	LEFT JOIN {exalib_item_category} AS ic ON category.id=ic.category_id
	WHERE ic.item_id=?", array($id));
	
	if (!$itemCategories && $category_id) {
		$itemCategories[$category_id] = $category_id;
	}
	
	$item_edit_form = new item_edit_form($_SERVER['REQUEST_URI'], array('itemCategories'=>$itemCategories, 'fileoptions'=>$fileoptions));

	if ($item_edit_form->is_cancelled()){

	} else if ($fromform = $item_edit_form->get_data()){
		// edit/add
		
		if (!isset($fromform->hidden)) $fromform->hidden = null;

		if (!empty($item->id)) {
			$fromform->id = $item->id;
			$fromform->modified_by = $USER->id;
			$fromform->time_modified = time();
		} else {
			try {
				$fromform->created_by = $USER->id;
				$fromform->time_created = time();
				$fromform->id = $DB->insert_record('exalib_item', $fromform);
			} catch (Exception $e) { var_dump($e); exit; }
		}

		$fromform->contentformat = FORMAT_HTML;
		$fromform = file_postupdate_standard_editor($fromform, 'content', $textfieldoptions, context_system::instance(), 'block_exalib', 'item_content', $fromform->id);
		$DB->update_record('exalib_item', $fromform);

		// save file
        $fromform = file_postupdate_standard_filemanager($fromform, 'file', $fileoptions, context_system::instance(), 'block_exalib', 'item_file', $fromform->id);
				
		// save categories
		$DB->delete_records('exalib_item_category', array("item_id" => $fromform->id));
		foreach ($_REQUEST['CATEGORIES'] as $tmp=>$categoryId) {
			$DB->execute('INSERT INTO {exalib_item_category} (item_id, category_id) VALUES (?, ?)', array($fromform->id, $categoryId));
		}
		
		if (!$category_id && is_array($_REQUEST['CATEGORIES'])) {
			$category_id = reset($_REQUEST['CATEGORIES']);
			// read first category
		}
		redirect('admin.php?category_id='.$category_id);
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

