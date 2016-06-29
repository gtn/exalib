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

require __DIR__.'/config.php';
require __DIR__.'/common.php';

use \block_exalib\globals as g;

/**
 * block exalib new moodle url
 * @return url
 */
function block_exalib_new_moodle_url() {
	global $CFG;

	$moodlepath = preg_replace('!^[^/]+//[^/]+!', '', $CFG->wwwroot);

	return new moodle_url(str_replace($moodlepath, '', $_SERVER['REQUEST_URI']));
}

function block_exalib_is_reviewer() {
	return (bool)get_user_preferences('block_exalib_is_reviewer');
}

/**
 * is creator?
 * @return boolean
 */
function block_exalib_is_creator() {
	return block_exalib_is_admin() || has_capability('block/exalib:creator', context_system::instance());
}

/**
 * is admin?
 * @return boolean
 */
function block_exalib_is_admin() {
	return has_capability('block/exalib:admin', context_system::instance());
}

function block_exalib_require_cap($cap, $user = null) {
	// all capabilities require use
	if (!has_capability('block/exalib:use', context_system::instance(), $user)) {
		if (!g::$USER->id) {
			// not logged in and no guest
			require_login();
		} else {
			throw new require_login_exception(get_string('notallowed', 'block_exalib'));
		}
	}

	switch ($cap) {
		case \block_exalib\CAP_USE:
			// already checked
			return;
		case \block_exalib\CAP_MANAGE_CONTENT:
		case \block_exalib\CAP_MANAGE_CATS:
			if (!block_exalib_is_creator()) {
				throw new block_exalib_permission_exception('no creator');
			}

			return;
		case \block_exalib\CAP_MANAGE_REVIEWERS:
		case \block_exalib\CAP_COURSE_SETTINGS:
			if (!block_exalib_is_admin()) {
				throw new block_exalib_permission_exception('no admin');
			}

			return;
	}

	require_capability('block/exalib:'.$cap, context_system::instance(), $user);
}

function block_exalib_has_cap($cap, $user = null) {
	try {
		block_exalib_require_cap($cap, $user);

		return true;
	} catch (block_exalib_permission_exception $e) {
		return false;
	} catch (\required_capability_exception $e) {
		return false;
	}
}

/**
 * block exalib require open
 * @return nothing
 */
function block_exalib_require_view_item($item_or_id) {
	block_exalib_require_cap(\block_exalib\CAP_USE);

	if (is_object($item_or_id)) {
		$item = $item_or_id;
	} else {
		$item = g::$DB->get_record('block_exalib_item', array('id' => $item_or_id));
	}

	if (!$item) {
		throw new moodle_exception('item not found');
	}

	if ($item->created_by == g::$USER->id || $item->reviewer_id == g::$USER->id) {
		// creator and reviewer can view it
		return true;
	}

	if ($item->online > 0) {
		// all online items can be viewed
		return true;
	}

	if (block_exalib_has_cap(block_exalib\CAP_MANAGE_CONTENT)) {
		// admin can view
		return true;
	}

	throw new block_exalib_permission_exception('not allowed');
}

class block_exalib_permission_exception extends \block_exalib\moodle_exception {
}

/**
 * block exalib require can edit item
 * @param stdClass $item
 */
function block_exalib_require_can_edit_item(stdClass $item) {
	if (block_exalib_has_cap(block_exalib\CAP_MANAGE_CONTENT)) {
		return true;
	}

	if (block_exalib_is_reviewer() && $item->reviewer_id == g::$USER->id && $item->online != \block_exalib\ITEM_STATE_NEW) {
		return true;
	}

	// Item creator can edit when not freigegeben
	if ($item->created_by == g::$USER->id && $item->online == \block_exalib\ITEM_STATE_NEW) {
		return true;
	}

	throw new block_exalib_permission_exception(get_string('noedit', 'block_exalib'));
}

/**
 * can edit item ?
 * @param stdClass $item
 * @return boolean
 */
function block_exalib_can_edit_item(stdClass $item) {
	try {
		block_exalib_require_can_edit_item($item);

		return true;
	} catch (block_exalib_permission_exception $e) {
		return false;
	}
}


/**
 * wrote own function, so eclipse knows which type the output renderer is
 * @return \block_exalib_renderer
 */
function block_exalib_get_renderer($init = true) {
	if ($init) {
		block_exalib_init_page();
	}

	static $renderer = null;
	if ($renderer) {
		return $renderer;
	}

	return $renderer = g::$PAGE->get_renderer('block_exalib');
}

function block_exalib_init_page() {
	static $init = true;
	if (!$init) {
		return;
	}
	$init = false;

	require_login(optional_param('courseid', 0, PARAM_INT));
	// g::$PAGE->set_course(g::$SITE);

	if (!g::$PAGE->has_set_url()) {
		g::$PAGE->set_url(block_exalib_new_moodle_url());
	}
}

function block_exalib_get_url_for_file(stored_file $file) {
	return moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(),
		$file->get_itemid(), $file->get_filepath(), $file->get_filename());
}

/**
 * print jwplayer
 * @param array $options
 * @return nothing
 */
function block_exalib_print_jwplayer($options) {

	$options = array_merge(array(
		'flashplayer' => "jwplayer/player.swf",
		'primary' => "flash",
		'autostart' => false,
	), $options);

	if (isset($options['file']) && preg_match('!^(rtmp://.*):(.*)$!i', $options['file'], $matches)) {
		$options = array_merge($options, array(
			'provider' => 'rtmp',
			'streamer' => $matches[1],
			'file' => str_replace('%20', ' ', $matches[2]),
		));
	}

	?>
	<div id='player_2834'></div>
	<script type='text/javascript'>
		var options = <?php echo json_encode($options); ?>;
		if (options.width == 'auto')
			options.width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
		if (options.height == 'auto')
			options.height = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;

		var preview_start = false;
		if (!options.autostart) {
			preview_start = true;

			options.autostart = true;
			options.mute = true;
		}

		var p = jwplayer('player_2834').setup(options);

		if (preview_start) {
			p.onPlay(function () {
				if (preview_start) {
					this.pause();
					this.setMute(false);
					preview_start = false;
				}
			});
		}
	</script>
	<?php
}

/**
 * Exalib category manager
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  gtn gmbh <office@gtn-solutions.com>
 */
class block_exalib_category_manager {
	/**
	 * @var $categories - categories
	 */
	private $categories = null;
	/**
	 * @var $categoriesbyparent - categories by parent
	 */
	private $categoriesbyparent = null;

	function __construct($showOfflineToo, $limitToCategoryId = null) {
		if ($this->categories !== null) {
			// Already loaded.
			return;
		}

		$this->createdefaultcategories();

		/*
		$fields = [];
		$join = [];
		$where = [];
		$params = [];
		*/

		$this->categories = g::$DB->get_records_sql("
        	SELECT category.*
        	FROM {block_exalib_category} category
        	WHERE 1=1
        	".($showOfflineToo ? '' : "
	            AND category.online
			")."
			ORDER BY name
		");
		$this->categoriesbyparent = array();

		$item_category_ids = iterator_to_array(g::$DB->get_recordset_sql("
        	SELECT item.id AS item_id, ic.category_id
        	FROM {block_exalib_item} item
        	JOIN {block_exalib_item_category} ic ON item.id=ic.item_id
        	WHERE 1=1
        	".($showOfflineToo ? '' : "
    	        AND item.online > 0
				AND (item.online_from=0 OR item.online_from IS NULL OR item.online_from <= ".time().")
				AND (item.online_to=0 OR item.online_to IS NULL OR item.online_to >= ".time().")
			")."
			".block_exalib_limit_item_to_category_where($limitToCategoryId)."
		"), false);

		// init
		foreach ($this->categories as $cat) {
			$cat->self_inc_all_sub_ids = [$cat->id => $cat->id];
			$cat->cnt_inc_subs = [];
			$cat->item_ids = [];
			$cat->item_ids_inc_subs = [];
			$cat->cnt = 0;
			$cat->level = 0;
		}

		// add items for counting
		foreach ($item_category_ids as $item_category) {
			if (!isset($this->categories[$item_category->category_id])) {
				continue;
			}

			$this->categories[$item_category->category_id]->item_ids[$item_category->item_id] = $item_category->item_id;
			$this->categories[$item_category->category_id]->item_ids_inc_subs[$item_category->item_id] = $item_category->item_id;
		}

		foreach ($this->categories as $cat) {

			$this->categoriesbyparent[$cat->parent_id][$cat->id] = $cat;
			$catLeaf = $cat;

			// find parents
			while ($cat->parent_id && isset($this->categories[$cat->parent_id])) {
				// has parent
				$parentCat = $this->categories[$cat->parent_id];
				$catLeaf->level++;
				$parentCat->self_inc_all_sub_ids += $cat->self_inc_all_sub_ids;
				$parentCat->item_ids_inc_subs += $cat->item_ids_inc_subs;

				$cat = $parentCat;
			}
		}

		if ($limitToCategoryId) {
			$this->categoriesbyparent[0] = $this->categoriesbyparent[$limitToCategoryId];
		}

		// count unique ids
		foreach ($this->categories as $cat) {
			$cat->cnt_inc_subs = count($cat->item_ids_inc_subs);
		}
	}

	/**
	 * get category
	 * @param integer $categoryid
	 * @return category
	 */
	public function getcategory($categoryid) {
		return isset($this->categories[$categoryid]) ? $this->categories[$categoryid] : null;
	}

	public function getChildren($categoryid) {
		return @$this->categoriesbyparent[$categoryid];
	}

	/**
	 * get category parent id
	 * @param integer $categoryid
	 * @return array of category
	 */
	public function getcategoryparentids($categoryid) {
		$parents = array();
		for ($i = 0; $i < 100; $i++) {
			$c = $this->getcategory($categoryid);
			if ($c) {
				$parents[] = $c->id;
				$categoryid = $c->parent_id;
			} else {
				break;
			}
		}

		return $parents;
	}

	/**
	 * walk tree
	 * @param \Closure $functionbefore
	 * @param \Closure $functionafter
	 * @return string item
	 */
	public function walktree($functionbefore, $functionafter = null) {
		return $this->walktreeitem($functionbefore, $functionafter);
	}

	/**
	 * walk tree item
	 * @param \Closure $functionbefore
	 * @param \Closure $functionafter
	 * @param integer $level
	 * @param integer $parent
	 * @return output
	 */
	private function walktreeitem($functionbefore, $functionafter, $level = 0, $parent = 0) {
		if (empty($this->categoriesbyparent[$parent])) {
			return;
		}

		$output = '';
		foreach ($this->categoriesbyparent[$parent] as $cat) {
			if ($functionbefore) {
				$output .= $functionbefore($cat);
			}

			$suboutput = $this->walktreeitem($functionbefore, $functionafter, $level + 1, $cat->id);

			if ($functionafter) {
				$output .= $functionafter($cat, $suboutput);
			}
		}

		return $output;
	}

	/**
	 * create default categories
	 * @return nothing
	 */
	public function createdefaultcategories() {
		global $DB;

		if ($DB->get_records('block_exalib_category', null, '', 'id', 0, 1)) {
			return;
		}

		$DB->execute("INSERT INTO {block_exalib_category} (id, parent_id, name, online) VALUES
 			(".\block_exalib\CATEGORY_TAGS.", 0, 'Tags', 1)");
		/*
		$DB->execute("INSERT INTO {block_exalib_category} (id, parent_id, name, online) VALUES
			(".\block_exalib\CATEGORY_SCHULSTUFE.", 0, 'Schulstufe', 1)");
		$DB->execute("INSERT INTO {block_exalib_category} (id, parent_id, name, online) VALUES
			(".\block_exalib\CATEGORY_SCHULFORM.", 0, 'Schulform', 1)");
		*/

		$DB->execute("ALTER TABLE {block_exalib_category} AUTO_INCREMENT=1001");
	}
}

function block_exalib_get_reviewers() {
	return g::$DB->get_records_sql("
		SELECT u.*
		FROM {user} u
		JOIN {user_preferences} p ON u.id=p.userid AND p.name='block_exalib_is_reviewer'
		WHERE p.value
		ORDER BY lastname, firstname
	");
}

function block_exalib_handle_item_delete($type) {
	$id = required_param('id', PARAM_INT);
	require_sesskey();

	$item = g::$DB->get_record('block_exalib_item', array('id' => $id));
	block_exalib_require_can_edit_item($item);

	g::$DB->delete_records('block_exalib_item', array('id' => $id));
	g::$DB->delete_records('block_exalib_item_category', array("item_id" => $id));

	if ($back = optional_param('back', '', PARAM_LOCALURL)) {
		redirect(new moodle_url($back));
	} elseif ($type == 'mine') {
		redirect(new moodle_url('mine.php', ['courseid' => g::$COURSE->id]));
	} else {
		redirect(new moodle_url('admin.php', ['courseid' => g::$COURSE->id]));
	}

	exit;
}

function block_exalib_handle_item_edit($type = '', $show) {
	global $CFG, $USER;

	if ($show == 'delete') {
		block_exalib_handle_item_delete($type);
	}

	if ($show == 'change_state') {
		$id = required_param('id', PARAM_INT);
		$state = required_param('state', PARAM_INT);
		require_sesskey();

		$item = g::$DB->get_record('block_exalib_item', array('id' => $id));
		block_exalib_require_can_edit_item($item);

		/*
		if ($item->created_by == g::$USER->id && $item->online == \block_exalib\ITEM_STATE_NEW && $state == \block_exalib\ITEM_STATE_IN_REVIEW) {
			// ok
		} elseif ($item->online == 0 || $item->online == \block_exalib\ITEM_STATE_IN_REVIEW && $state == \block_exalib\ITEM_STATE_NEW) {
			// ok
		} else {
			throw new moodle_exception('not allowed');
		}
		*/

		// send email to reviewer
		if ($state == block_exalib\ITEM_STATE_IN_REVIEW) {
			$reviewer = g::$DB->get_record('user', ['id' => $item->reviewer_id]);
			$creator = g::$USER;

			if ($reviewer) {
				$message = block_exalib\trans('de:'.join('<br />', [
						'de:Liebe/r '.fullname($reviewer).',',
						'',
						'Im Fallarchiv der PH-OÖ wurde von '.fullname($creator).' ('.$creator->email.') ein Fall eingetragen.',
						''.fullname($creator).' bittet Sie den Fall zu Reviewen. Bitte sehen sie den Fall durch und',
						'- geben Sie den Fall gegebenfalls frei',
						'- oder verbessern Sie den Fall',
						'- oder geben Sie den Fall zurück an den Autor zur erneuten Bearbeitung',
						'',
						'<a href="'.g::$CFG->wwwroot.'/blocks/exalib/detail.php?itemid='.$item->id.'&type=mine">Klicken Sie hier um den Fall zu reviewen.</a>',
						'',
						'Vielen Dank',
						'',
						'Das ist eine automatisch generierte E-Mail, bitte nicht Antworten.',
					]));

				$eventdata = new stdClass();
				$eventdata->name = 'item_status_changed';
				$eventdata->component = 'block_exalib';
				$eventdata->userfrom = $creator;
				$eventdata->userto = $reviewer;
				$eventdata->subject = block_exalib\trans('de:PH - Kasuistik Reviewanfrage');
				$eventdata->fullmessage = $message;
				$eventdata->fullmessageformat = FORMAT_HTML;
				$eventdata->fullmessagehtml = $message;
				$eventdata->smallmessage = '';
				message_send($eventdata);
			}
		}

		// send email to creator
		if ($state == block_exalib\ITEM_STATE_NEW) {
			$reviewer = g::$USER;
			$creator = g::$DB->get_record('user', ['id' => $item->created_by]);

			if ($creator) {
				$message = block_exalib\trans('de:'.join('<br />', [
						'de:Liebe/r '.fullname($creator).',',
						'',
						'Im Fallarchiv der PH-OÖ wurde Ihnen ein Fall zur Überarbeitung übergeben. Bitte überarbeiten Sie den Fall und geben in erneut zum Review frei.',
						'',
						'<a href="'.g::$CFG->wwwroot.'/blocks/exalib/detail.php?itemid='.$item->id.'&type=mine">Klicken Sie hier um den Fall zu überarbeiten.</a>',
						'',
						'Vielen Dank',
						'',
						'Das ist eine automatisch generierte E-Mail, bitte nicht Antworten.',
					]));

				$eventdata = new stdClass();
				$eventdata->name = 'item_status_changed';
				$eventdata->component = 'block_exalib';
				$eventdata->userfrom = $reviewer;
				$eventdata->userto = $creator;
				$eventdata->subject = block_exalib\trans('de:PH - Kasuistik Reviewfeedback');
				$eventdata->fullmessageformat = FORMAT_HTML;
				$eventdata->fullmessagehtml = $message;
				$eventdata->smallmessage = '';
				message_send($eventdata);
			}
		}

		g::$DB->update_record('block_exalib_item', [
			'id' => $item->id,
			'online' => $state,
		]);

		if ($type == 'mine') {
			redirect(new moodle_url('mine.php', ['courseid' => g::$COURSE->id]));
		} else {
			redirect(new moodle_url('admin.php', ['courseid' => g::$COURSE->id]));
		}

		exit;
	}

	require_once($CFG->libdir.'/formslib.php');

	$categoryid = optional_param('category_id', '', PARAM_INT);
	$textfieldoptions = array('trusttext' => true, 'subdirs' => true, 'maxfiles' => 99, 'context' => context_system::instance());
	$fileoptions = array('subdirs' => false, 'maxfiles' => 5);

	if ($show == 'add') {
		$id = 0;
		$item = new StdClass;
		$item->online = 1;

		// block_exalib_require_creator();
	} else {
		$id = required_param('id', PARAM_INT);
		$item = g::$DB->get_record('block_exalib_item', array('id' => $id));

		block_exalib_require_can_edit_item($item);

		if ($item->online_to > 10000000000) {
			// bei den lateinern ist ein fiktiv hohes online_to drinnen
			$item->online_to = 0;
		}

		$item->contentformat = FORMAT_HTML;
		$item = file_prepare_standard_editor($item, 'content', $textfieldoptions, context_system::instance(),
			'block_exalib', 'item_content', $item->id);
		$item->abstractformat = FORMAT_HTML;
		$item = file_prepare_standard_editor($item, 'abstract', $textfieldoptions, context_system::instance(),
			'block_exalib', 'item_abstract', $item->id);
		$item = file_prepare_standard_filemanager($item, 'file', $fileoptions, context_system::instance(),
			'block_exalib', 'item_file', $item->id);
		$item = file_prepare_standard_filemanager($item, 'preview_image', $fileoptions, context_system::instance(),
			'block_exalib', 'preview_image', $item->id);
	}

	/**
	 * Items edit form
	 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
	 * @copyright  gtn gmbh <office@gtn-solutions.com>
	 */
	class item_edit_form extends moodleform {

		/**
		 * Definition
		 * @return nothing
		 */
		public function definition() {
			$mform =& $this->_form;

			$mform->addElement('text', 'name', get_string('name', 'block_exalib'), 'size="100"');
			$mform->setType('name', PARAM_TEXT);
			$mform->addRule('name', 'Name required', 'required', null, 'server');

			if (block_exalib_course_settings::use_review()) {
				$values = array_map('fullname', block_exalib_get_reviewers());
				$values = ['' => ''] + $values;
				$mform->addElement('select', 'reviewer_id', \block_exalib\trans('de:Reviewer'), $values);
				$mform->addRule('reviewer_id', get_string('requiredelement', 'form'), 'required');
			}

			if (!block_exalib_course_settings::alternative_wording()) {
				$mform->addElement('text', 'source', get_string('source', 'block_exalib'), 'size="100"');
				$mform->setType('source', PARAM_TEXT);
			}

			/*
			$values = g::$DB->get_records_sql_menu("
				SELECT c.id, c.name
				FROM {block_exalib_category} c
				WHERE parent_id=".\block_exalib\CATEGORY_SCHULSTUFE."
			   ");
			$mform->addElement('select', 'schulstufeid', \block_exalib\trans('de:Schulstufe'), $values);
			$mform->addRule('schulstufeid', get_string('requiredelement', 'form'), 'required');

			$values = g::$DB->get_records_sql_menu("
				SELECT c.id, c.name
				FROM {block_exalib_category} c
				WHERE parent_id=".\block_exalib\CATEGORY_SCHULFORM."
			   ");
			$mform->addElement('select', 'schulformid', \block_exalib\trans('de:Schulform'), $values);
			$mform->addRule('schulformid', get_string('requiredelement', 'form'), 'required');
			*/

			$mform->addElement('text', 'authors', get_string('authors', 'block_exalib'), 'size="100"');
			$mform->setType('authors', PARAM_TEXT);

			$mform->addElement('header', 'contentheader', get_string('content', 'block_exalib'));

			/*
			$mform->addElement('text', 'link_titel', get_string('linktitle', 'block_exalib'), 'size="100"');
			$mform->setType('link_titel', PARAM_TEXT);
			*/

			$mform->addElement('text', 'link', get_string('link', 'block_exalib'), 'size="100"');
			$mform->setType('link', PARAM_TEXT);

			$mform->addElement('editor', 'abstract_editor', get_string('abstract', 'block_exalib'), 'rows="10" cols="50" style="width: 95%"');
			$mform->setType('abstract', PARAM_RAW);

			$mform->addElement('editor', 'content_editor', get_string('content', 'block_exalib'), 'rows="20" cols="50" style="width: 95%"');
			$mform->setType('content', PARAM_RAW);

			$mform->addElement('filemanager', 'preview_image_filemanager', get_string('previmg', 'block_exalib'), null,
				$this->_customdata['fileoptions']);

			$mform->addElement('filemanager', 'file_filemanager', get_string('files', 'block_exalib'), null, $this->_customdata['fileoptions']);

			if ($this->_customdata['type'] != 'mine') {
				$mform->addElement('header', 'onlineheader', get_string('onlineset', 'block_exalib'));

				$mform->addElement('advcheckbox', 'online', \block_exalib\get_string('online'));

				$mform->addElement('date_selector', 'online_from', get_string('onlinefrom', 'block_exalib'), array(
					'startyear' => 2014,
					'stopyear' => date('Y')+10,
					'optional' => true,
				));
				$mform->addElement('date_selector', 'online_to', get_string('onlineto', 'block_exalib'), array(
					'startyear' => 2014,
					'stopyear' => date('Y')+10,
					'optional' => true,
				));
			} elseif (block_exalib_is_reviewer()) {
				// $mform->addElement('advcheckbox', 'online', \block_exalib\get_string('online'));

				$radioarray = array();
				$radioarray[] = $mform->createElement('radio', 'online', '', \block_exalib\trans('de:in review'), \block_exalib\ITEM_STATE_IN_REVIEW);
				$radioarray[] = $mform->createElement('radio', 'online', '', \block_exalib\get_string('offline'), 0);
				$radioarray[] = $mform->createElement('radio', 'online', '', \block_exalib\get_string('online'), 1);
				$mform->addGroup($radioarray, 'online', \block_exalib\get_string("status"), array(' '), false);
			}

			$radioarray = array();
			$radioarray[] = $mform->createElement('radio', 'allow_comments', '', \block_exalib\trans('de:Allen Benutzern'), '');
			$radioarray[] = $mform->createElement('radio', 'allow_comments', '', \block_exalib\trans('de:Lehreneden und Redaktionsteam'), 'teachers_and_reviewers');
			$radioarray[] = $mform->createElement('radio', 'allow_comments', '', \block_exalib\trans('de:Redaktionsteam'), 'reviewers');
			$radioarray[] = $mform->createElement('radio', 'allow_comments', '', \block_exalib\trans('de:Keine Kommentare'), 'none');
			$mform->addGroup($radioarray, 'allow_comments', \block_exalib\trans("de:Kommentare erlauben von"), array(' '), false);

			$mform->addElement('header', 'categoriesheader', get_string('categories', 'block_exalib'));

			$mform->addElement('static', 'categories', get_string('groups', 'block_exalib'), $this->get_categories());

			$this->add_action_buttons();
		}

		/**
		 * Get categories
		 * @return checkbox
		 */
		public function get_categories() {
			$mgr = new block_exalib_category_manager(true, block_exalib_course_settings::root_category_id());

			return $mgr->walktree(null, function($cat, $suboutput) {
				return '<div style="padding-left: '.(20 * $cat->level).'px;">'.
				'<input type="checkbox" name="categories[]" value="'.$cat->id.'" '.
				(in_array($cat->id, $this->_customdata['itemCategories']) ? 'checked ' : '').'/>'.
				($cat->level == 0 ? '<b>'.$cat->name.'</b>' : $cat->name).
				'</div>'.$suboutput;
			});
		}
	}

	$itemcategories = g::$DB->get_records_sql_menu("SELECT category.id, category.id AS val
    FROM {block_exalib_category} category
    LEFT JOIN {block_exalib_item_category} ic ON category.id=ic.category_id
    WHERE ic.item_id=?", array($id));

	if (!$itemcategories && $categoryid) {
		// at least one category
		$itemcategories[$categoryid] = $categoryid;
	}

	$itemeditform = new item_edit_form($_SERVER['REQUEST_URI'], [
		'itemCategories' => $itemcategories,
		'fileoptions' => $fileoptions,
		'type' => $type,
	]);

	if ($itemeditform->is_cancelled()) {
		if ($back = optional_param('back', '', PARAM_LOCALURL)) {
			redirect(new moodle_url($back));
		} else {
			redirect(new moodle_url('admin.php', ['courseid' => g::$COURSE->id]));
		}
	} else {
		if ($fromform = $itemeditform->get_data()) {
			// Edit/add.

			if ($type == 'mine' && empty($item->id)) {
				// normal user items should be offline first
				$fromform->online = \block_exalib\ITEM_STATE_NEW;
			}

			if (!empty($item->id)) {
				$fromform->id = $item->id;
				$fromform->modified_by = $USER->id;
				$fromform->time_modified = time();
			} else {
				$fromform->created_by = $USER->id;
				$fromform->time_created = time();
				$fromform->time_modified = 0;
				$fromform->id = g::$DB->insert_record('block_exalib_item', $fromform);
			}

			$fromform->contentformat = FORMAT_HTML;
			$fromform = file_postupdate_standard_editor($fromform,
				'content',
				$textfieldoptions,
				context_system::instance(),
				'block_exalib',
				'item_content',
				$fromform->id);
			$fromform->abstractformat = FORMAT_HTML;
			$fromform = file_postupdate_standard_editor($fromform,
				'abstract',
				$textfieldoptions,
				context_system::instance(),
				'block_exalib',
				'item_content',
				$fromform->id);

			g::$DB->update_record('block_exalib_item', $fromform);

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
			g::$DB->delete_records('block_exalib_item_category', array("item_id" => $fromform->id));
			$categories_request = \block_exalib\param::optional_array('categories', PARAM_INT);

			if ($root_category_id = block_exalib_course_settings::root_category_id()) {
				// if course has a root category, always add it
				if (!in_array($root_category_id, $categories_request)) {
					$categories_request[$root_category_id] = $root_category_id;
				}
			}

			foreach ($categories_request as $categoryidforinsert) {
				g::$DB->execute('INSERT INTO {block_exalib_item_category} (item_id, category_id) VALUES (?, ?)',
					array($fromform->id, $categoryidforinsert));
			}

			if ($back = optional_param('back', '', PARAM_LOCALURL)) {
				redirect(new moodle_url($back));
			} elseif ($type == 'mine') {
				redirect(new moodle_url('mine.php', ['courseid' => g::$COURSE->id]));
			} else {
				redirect(new moodle_url('admin.php', ['courseid' => g::$COURSE->id]));
			}
			exit;

		} else {
			// Display form.

			$output = block_exalib_get_renderer();

			echo $output->header(defined('BLOCK_EXALIB_IS_ADMIN_MODE') && BLOCK_EXALIB_IS_ADMIN_MODE ? 'tab_manage_content' : null);

			$itemeditform->set_data($item);
			$itemeditform->display();

			echo $output->footer();
		}
	}
}

function block_exalib_format_url($url) {
	if (!preg_match('!^.*://!', $url)) {
		$url = 'http://'.$url;
	}

	return $url;
}

function block_exalib_get_fachsprachliches_lexikon_id() {
	return g::$DB->get_field('glossary', 'id', ['course' => g::$COURSE->id, 'name' => 'Fachsprachliches Lexikon']);
}

function block_exalib_get_fachsprachliches_lexikon_items() {
	$glossaryid = block_exalib_get_fachsprachliches_lexikon_id();

	return g::$DB->get_records_sql("
		SELECT concept, definition
		FROM {glossary_entries}
		WHERE glossaryid = ?
		ORDER BY concept
	", [$glossaryid]);

	return $records;
}

/**
 * @method static int root_category_id()
 * @method static bool alternative_wording()
 * @method static bool use_review()
 * @method static bool use_terms_of_service()
 * @method static bool allow_comments()
 * @method static bool allow_rating()
 * @property int root_category_id
 * @property bool alternative_wording
 * @property bool use_review
 * @property bool use_terms_of_service
 * @property bool allow_comments
 * @property bool allow_rating
 */
class block_exalib_course_settings {

	static protected $courses = [];

	protected $courseid;
	protected $settings;

	function __construct($courseid) {
		$this->courseid = $courseid;

		$settings = get_config('block_exalib', "course[$courseid]");
		if ($settings) {
			$settings = json_decode($settings);
		}

		if (!$settings) {
			$this->settings = (object)[];
		} else {
			$this->settings = (object)$settings;
		}
	}

	static function get_course($courseid = null) {
		if ($courseid === null) {
			$courseid = g::$COURSE->id;
		}

		if (isset(static::$courses[$courseid])) {
			return static::$courses[$courseid];
		} else {
			return static::$courses[$courseid] = new static($courseid);
		}
	}

	static function __callStatic($name, $arguments) {
		$settings = static::get_course();

		return $settings->$name;
	}

	function __get($name) {
		//if (in_array($name, ['root_category_id'])) {
		if ($name == 'allow_rating') {
			$name = 'allow_comments';
		}

		return @$this->settings->$name;
		//} else {
		//	throw new moodle_exception("function $name not found");
		//}
	}

	function __set($name, $value) {
		$this->settings->$name = $value;
	}

	function save() {
		$settings = json_encode($this->settings);
		set_config("course[{$this->courseid}]", $settings, 'block_exalib');
	}
}

function block_exalib_limit_item_to_category_where($category_id) {
	if (!$category_id) {
		return '';
	} else {
		return " AND item.id IN (
			SELECT item_id FROM {block_exalib_item_category}
			WHERE category_id=".(int)$category_id."
		)";
	}
}