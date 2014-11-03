<?php

$show = optional_param('show', '', PARAM_TEXT);

if ($show == 'category_delete') {
	die('TODO: '.$show);
}
if ($show == 'category_add') {
	die('TODO: '.$show);
}
if ($show == 'category_edit') {
	die('TODO: '.$show);
}

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

		function get_categories($level=0, $parent=0) {
			global $CATEGORY_BY_PARENT;

			if (empty($CATEGORY_BY_PARENT[$parent])) return;
			
			$text = '';
			foreach ($CATEGORY_BY_PARENT[$parent] as $cat) {
				if (!$parent) {
					$text .= '<b>'.$cat->name.'</b><br />';
				} else {
					$text .= '<div style="padding-left: '.(20*$level).'px;"><input type="checkbox" name="CATEGORIES[]" value="'.$cat->id.'" '.
							(in_array($cat->id, $this->_customdata['itemCategories'])?'checked ':'').'/>'.$cat->name.'</div>';
				}
				
				$text .= $this->get_categories($level+1, $cat->id);
			}
			
			return $text;
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

