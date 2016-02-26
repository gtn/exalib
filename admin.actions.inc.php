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

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/filelib.php');

$show = optional_param('show', '', PARAM_TEXT);
$categories_request =  optional_param_array('CATEGORIES', null, PARAM_INT);

if ($show == 'categories') {
    block_exalib_require_admin();

    $PAGE->navbar->add('Categories');

    echo $OUTPUT->header();

    echo '<div><a href="admin.php?show=category_add&parent_id=0">'.get_string('addmaincat', 'block_exalib').'</a></div>';

    block_exalib_category_manager::walktree(function($cat) {

        echo '<div>&bullet; ';

        echo $cat->name.' ('.$cat->cnt_inc_subs.') ';

        echo '<span class="library_categories_item_buttons"><span>';
        echo '<a href="admin.php?show=category_edit&category_id='.$cat->id.'">'.get_string('edit', 'block_exalib').'</a>';
        echo ' | <a href="admin.php?show=category_delete&category_id='.$cat->id.'">'.get_string('delete', 'block_exalib').'</a>';
        echo '</span></span>';

        echo '</div>';

        echo '<div style="padding-left: 30px;">';
        echo '<a href="admin.php?show=category_add&parent_id='.$cat->id.'">'.get_string('addcat', 'block_exalib').'</a>';

    }, function() {
        echo '</div>';
    });

    echo $OUTPUT->footer();
    exit;
}

if ($show == 'category_delete') {
    block_exalib_require_admin();

    $confirm = optional_param("confirm", "", PARAM_BOOL);
    $categoryid = required_param('category_id', PARAM_INT);

    if (data_submitted() && $confirm && confirm_sesskey()) {
        $DB->delete_records('block_exalib_category', array(
            'id' => required_param('category_id', PARAM_INT)
        ));
        redirect('admin.php?show=categories');
        exit;
    } else {
        $optionsyes = array('category_id' => $categoryid, 'show' => 'category_delete', 'confirm' => 1, 'sesskey' => sesskey());
        $optionsno = array();

        echo $OUTPUT->header();

        echo '<br />';
        echo $OUTPUT->confirm(get_string('deletecat', 'block_exalib').block_exalib_category_manager::getcategory($categoryid)->name.get_string('deletecat2', 'block_exalib'),
            new moodle_url('admin.php', $optionsyes),
            new moodle_url('admin.php', $optionsno));

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
        $category = $DB->get_record('block_exalib_category', array('id' => required_param('category_id', PARAM_INT)));
    }

    require_once("$CFG->libdir/formslib.php");

    /**
     * Items edit form
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     * @copyright  gtn gmbh <office@gtn-solutions.com>
     */
    class itemeditform extends moodleform {

        /**
         * @var $_category_select - selected category
         */
        public $_category_select = array(0 => 'root');

        /**
         * Definition
         * @return nothing
         */
        public function definition() {
            global $CFG;

            block_exalib_category_manager::walktree(function($cat) {
                $this->_category_select[$cat->id] = str_repeat('&nbsp;&nbsp;&nbsp;', $cat->level).'&bullet; '.$cat->name;
            }, false);

            $mform =& $this->_form; // Don't forget the underscore!

            $mform->addElement('text', 'name', get_string('name', 'block_exalib'), 'size="100"');
            $mform->setType('name', PARAM_TEXT);
            $mform->addRule('name', 'Name required', 'required', null, 'server');

            $mform->addElement('select', 'parent_id', get_string('parent', 'block_exalib'), $this->_category_select);

            /* ... $mform->addElement('static', 'description', 'Groups', $this->get_categories()); /*...*/

            $this->add_action_buttons();
        }

        /**
         * Get categories
         * @return empty now
         */
        public function get_categories() {
            return;
        }
    }



    $categoryeditform = new itemeditform($_SERVER['REQUEST_URI']);

    if ($categoryeditform->is_cancelled()) {
        $fcc = 1; // For Code checker.
    } else if ($fromform = $categoryeditform->get_data()) {
        // Edit/add.

        if (!empty($category->id)) {
            $fromform->id = $category->id;
            $DB->update_record('block_exalib_category', $fromform);
        } else {
            try {
                $fromform->id = $DB->insert_record('block_exalib_category', $fromform);
            } catch (Exception $e) {
                var_dump($e); exit;
            }
        }

        redirect('admin.php?show=categories');
        exit;

    } else {
        // Display form.

        echo $OUTPUT->header();

        $categoryeditform->set_data($category);
        $categoryeditform->display();

        echo $OUTPUT->footer();
    }

    exit;
}

if ($show == 'delete') {
    $id = required_param('id', PARAM_INT);
    $item = $DB->get_record('block_exalib_item', array('id' => $id));

    block_exalib_require_can_edit_item($item);

    $confirm = optional_param("confirm", "", PARAM_BOOL);

    if (data_submitted() && $confirm && confirm_sesskey()) {
        $DB->delete_records('block_exalib_item', array('id' => $id));
        $DB->delete_records('block_exalib_item_category', array("item_id" => $id));
        redirect('admin.php');
        exit;
    } else {
        $optionsyes = array('id' => $id, 'show' => 'delete', 'confirm' => 1, 'sesskey' => sesskey());
        $optionsno = array();

        echo $OUTPUT->header();

        echo '<br />';
        echo $OUTPUT->confirm('delete '.$item->name.'?',
            new moodle_url('admin.php', $optionsyes),
            new moodle_url('admin.php', $optionsno));

        echo $OUTPUT->footer();
        exit;
    }
}

if ($show == 'edit' || $show == 'add') {
    $categoryid = optional_param('category_id', '', PARAM_INT);
    $textfieldoptions = array('trusttext' => true, 'subdirs' => true, 'maxfiles' => 99, 'context' => context_system::instance());
    $fileoptions = array('subdirs' => false, 'maxfiles' => 1);

    if ($show == 'add') {
        $id = 0;
        $item = new StdClass;
        $item->contentformat = FORMAT_HTML;

        block_exalib_require_creator();
    } else {
        $id = required_param('id', PARAM_INT);
        $item = $DB->get_record('block_exalib_item', array('id' => $id));

        block_exalib_require_can_edit_item($item);

        $item->contentformat = FORMAT_HTML;
        $item = file_prepare_standard_editor($item, 'content', $textfieldoptions, context_system::instance(),
            'block_exalib', 'item_content', $item->id);
        $item = file_prepare_standard_filemanager($item, 'file', $fileoptions, context_system::instance(),
            'block_exalib', 'item_file', $item->id);
        $item = file_prepare_standard_filemanager($item, 'preview_image', $fileoptions, context_system::instance(),
            'block_exalib', 'preview_image', $item->id);
    }

    require_once($CFG->libdir."/formslib.php");

    /**
     * Items edit form
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     * @copyright  gtn gmbh <office@gtn-solutions.com>
     */
    class itemeditform extends moodleform {

        /**
         * Definition
         * @return nothing
         */
        public function definition() {
            global $CFG;

            $mform =& $this->_form; // Don't forget the underscore!

            $mform->addElement('text', 'name', get_string('name', 'block_exalib'), 'size="100"');
            $mform->setType('name', PARAM_TEXT);
            $mform->addRule('name', 'Name required', 'required', null, 'server');

            $mform->addElement('text', 'source', get_string('source', 'block_exalib'), 'size="100"');
            $mform->setType('source', PARAM_TEXT);

            $mform->addElement('header', 'contentheader', get_string('content', 'block_exalib'));

            $mform->addElement('text', 'link_titel', get_string('linktitle', 'block_exalib'), 'size="100"');
            $mform->setType('link_titel', PARAM_TEXT);

            $mform->addElement('text', 'link', get_string('link', 'block_exalib'), 'size="100"');
            $mform->setType('link', PARAM_TEXT);

            $mform->addElement('filemanager', 'preview_image_filemanager', get_string('previmg', 'block_exalib'), null,
                $this->_customdata['fileoptions']);
                
            $mform->addElement('filemanager', 'file_filemanager', get_string('file', 'block_exalib'), null, $this->_customdata['fileoptions']);

            $mform->addElement('editor', 'content_editor', get_string('content', 'block_exalib'), 'rows="20" cols="50" style="width: 95%"');
            $mform->setType('content', PARAM_RAW);

            $mform->addElement('text', 'authors', get_string('authors', 'block_exalib'), 'size="100"');
            $mform->setType('authors', PARAM_TEXT);

            $mform->addElement('header', 'onlineheader', get_string('onlineset', 'block_exalib'));

            $mform->addElement('date_selector', 'online_from', get_string('onlinefrom', 'block_exalib'), array(
                'startyear' => 2014,
                'stopyear'  => date('Y'),
                'optional'  => true
            ));
            $mform->addElement('date_selector', 'online_to', get_string('onlineto', 'block_exalib'), array(
                'startyear' => 2014,
                'stopyear'  => date('Y'),
                'optional'  => true
            ));
            $mform->addElement('checkbox', 'hidden', get_string('hidden', 'block_exalib'));

            $mform->addElement('header', 'categoriesheader', get_string('categories', 'block_exalib'));

            $mform->addElement('static', 'categories', get_string('groups', 'block_exalib'), $this->get_categories());

            $this->add_action_buttons();
        }

        /**
         * Get categories
         * @return checkbox
         */
        public function get_categories() {
            return block_exalib_category_manager::walktree(function($cat, $suboutput) {
                return '<div style="padding-left: '.(20 * $cat->level).'px;">'.
                        '<input type="checkbox" name="CATEGORIES[]" value="'.$cat->id.'" '.
                        (in_array($cat->id, $this->_customdata['itemCategories']) ? 'checked ' : '').'/>'.
                        ($cat->level == 0 ? '<b>'.$cat->name.'</b>' : $cat->name).'</div>'.$suboutput;
            });
        }
    }

    $itemcategories = $DB->get_records_sql_menu("SELECT category.id, category.id AS val
    FROM {block_exalib_category} category
    LEFT JOIN {block_exalib_item_category} ic ON category.id=ic.category_id
    WHERE ic.item_id=?", array($id));

    if (!$itemcategories && $categoryid) {
        $itemcategories[$categoryid] = $categoryid;
    }

    $itemeditform = new itemeditform($_SERVER['REQUEST_URI'],
        array('itemCategories' => $itemcategories,
        'fileoptions' => $fileoptions));

    if ($itemeditform->is_cancelled()) {
        $fcc = 1; // For Code checker.
    } else if ($fromform = $itemeditform->get_data()) {
        // Edit/add.

        if (!isset($fromform->hidden)) {
            $fromform->hidden = null;
        };

        if (!empty($item->id)) {
            $fromform->id = $item->id;
            $fromform->modified_by = $USER->id;
            $fromform->time_modified = time();
        } else {
            try {
                $fromform->created_by = $USER->id;
                $fromform->time_created = time();
                $fromform->time_modified = 0;
                $fromform->id = $DB->insert_record('block_exalib_item', $fromform);
            } catch (Exception $e) {
                var_dump($e); exit;
            }
        }

        $fromform->contentformat = FORMAT_HTML;
        $fromform = file_postupdate_standard_editor($fromform,
            'content',
            $textfieldoptions,
            context_system::instance(),
            'block_exalib',
            'item_content',
            $fromform->id);
        $DB->update_record('block_exalib_item', $fromform);

        // Save file.
        $fromform = file_postupdate_standard_filemanager($fromform,
            'file',
            $fileoptions,
            context_system::instance(),
            'block_exalib',
            'item_file',
            $fromform->id);
        $fromform = file_postupdate_standard_filemanager($fromform,
            'preview_image',
            $fileoptions,
            context_system::instance(),
            'block_exalib',
            'preview_image',
            $fromform->id);

        
        // Save categories.
        $DB->delete_records('block_exalib_item_category', array("item_id" => $fromform->id));
        foreach ($categories_request as $tmp => $categoryidforinsert) {
            $DB->execute('INSERT INTO {block_exalib_item_category} (item_id, category_id) VALUES (?, ?)',
                array($fromform->id, $categoryidforinsert));
        }

        if (!$categoryid && is_array($categories_request)) {
            $categoryid = reset($categories_request);
            // Read first category.
        }
        redirect('admin.php?category_id='.$categoryid);
        exit;

    } else {
        // Display form.

        echo $OUTPUT->header();

        $itemeditform->set_data($item);
        $itemeditform->display();

        echo $OUTPUT->footer();
    }

    exit;
}
