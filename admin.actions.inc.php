<?php

$show = optional_param('show', '', PARAM_TEXT);

if ($show == 'categories') {
	$PAGE->navbar->add('Categories');
	
	echo $OUTPUT->header();

	echo '<div><a href="admin.php?show=category_add&parent_id=0">add main category</a></div>';
	
	block_exalib_category_manager::walkTree(function($level, $parent, $cat) {
	
		echo '<div>&bullet; ';
		
		echo $cat->name.' ('.$cat->cnt_inc_subs.') ';
		
		echo '<span class="library_categories_item_buttons"><span>';
		echo '<a href="admin.php?show=category_edit&category_id='.$cat->id.'">edit</a>';
		echo ' | <a href="admin.php?show=category_delete&category_id='.$cat->id.'">delete</a>';
		echo '</span></span>';

		echo '</div>';

		echo '<div style="padding-left: 30px;">';
		echo '<a href="admin.php?show=category_add&parent_id='.$cat->id.'">add category here</a>';
		
	}, function($level) {
		echo '</div>';
	});
 
	echo $OUTPUT->footer();
	exit;
}

if ($show == 'category_delete') {
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
	 
			block_exalib_category_manager::walkTree(function($level, $parent, $cat) {
				$this->_category_select[$cat->id] = str_repeat('&nbsp;&nbsp;&nbsp;', $level).'&bullet; '.$cat->name;
			});

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
	
	if ($show == 'add') {
		$id = 0;
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
			$mform->setType('name', PARAM_TEXT);
			$mform->addRule('name', 'Name required', 'required', null, 'server');
			
			$mform->addElement('text', 'source', 'Source', 'size="100"');
			$mform->setType('source', PARAM_TEXT);

			$mform->addElement('header', 'content', 'Content');

			$mform->addElement('text', 'link', 'Link', 'size="100"');
			$mform->setType('link', PARAM_TEXT);
			
			$mform->addElement('filemanager', 'file', 'File', null, array('subdirs' => false, 'maxfiles' => 1));

			$mform->closeHeaderBefore('authors');
			
			$mform->addElement('text', 'authors', 'Authors', 'size="100"');
			$mform->setType('authors', PARAM_TEXT);

			$mform->addElement('static', 'description', 'Groups', $this->get_categories());

			$this->add_action_buttons();
		}

		function get_categories() {
			return block_exalib_category_manager::walkTree(function($level, $parent, $cat) {
				return '<div style="padding-left: '.(20*$level).'px;"><input type="checkbox" name="CATEGORIES[]" value="'.$cat->id.'" '.
						(in_array($cat->id, $this->_customdata['itemCategories'])?'checked ':'').'/>'.
						($level == 0 ? '<b>'.$cat->name.'</b>' : $cat->name).
						'</div>';
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
	
	$item_edit_form = new item_edit_form($_SERVER['REQUEST_URI'], array('itemCategories'=>$itemCategories));

	if ($item_edit_form->is_cancelled()){

	} else if ($fromform = $item_edit_form->get_data()){
		// edit/add
		
		if (!empty($item->id)) {
			$fromform->id = $item->id;
			$DB->update_record('exalib_item', $fromform);
		} else {
			try {
				if (!isset($fromform->resource_id)) $fromform->resource_id = 0;
				if (!isset($fromform->content)) $fromform->content = '';
				
				$fromform->id = $DB->insert_record('exalib_item', $fromform);
			} catch (Exception $e) { var_dump($e); exit;}
		}

		// save file
		$context = context_system::instance();
		file_save_draft_area_files($fromform->file, $context->id, 'block_exalib', 'item_file', $fromform->id, null);
				
		// save categories
		$DB->delete_records('exalib_item_category', array("item_id" => $fromform->id));
		foreach ($_REQUEST['CATEGORIES'] as $tmp=>$categoryId) {
			$DB->execute('INSERT INTO {exalib_item_category} (item_id, category_id) VALUES (?, ?)', array($fromform->id, $categoryId));
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

