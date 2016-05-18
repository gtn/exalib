<?php
// This file is part of Exabis Competencies
//
// (c) 2016 GTN - Global Training Network GmbH <office@gtn-solutions.com>
//
// Exabis Competencies is free software: you can redistribute it and/or modify
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

defined('MOODLE_INTERNAL') || die;

use block_exalib\globals as g;

class block_exalib_renderer extends plugin_renderer_base {

	var $tabs = [];

	public function header($items = null) {

		if ($items === null) {
			$items = $this->tabs;
		}

		// check for tos
		if (!get_user_preferences('block_exalib_terms_of_service')) {
			redirect(new moodle_url('terms_of_service.php', ['forward' => g::$PAGE->url->out_as_local_url(false)]));
			exit;
		}

		$items = (array)$items;
		$strheader = \block_exalib\get_string('pluginname');

		$last_item_name = '';
		$tabs = array();

		$tabs[] = new tabobject('tab_library', new moodle_url('/blocks/exalib/index.php', ['courseid' => g::$COURSE->id]), \block_exalib\get_string("heading"), '', true);
		$tabs[] = new tabobject('tab_mine', new moodle_url('/blocks/exalib/mine.php', ['courseid' => g::$COURSE->id]), \block_exalib\get_string("tab_mine"), '', true);

		if (block_exalib_has_global_cap(\block_exalib\CAP_MANAGE_CONTENT)) {
			$tabs[] = new tabobject('tab_manage_content', new moodle_url('/blocks/exalib/admin.php', ['courseid' => g::$COURSE->id]), \block_exalib\get_string("tab_manage_content"), '', true);
		}
		if (block_exalib_has_global_cap(\block_exalib\CAP_MANAGE_CATS)) {
			$tabs[] = new tabobject('tab_manage_cats', new moodle_url('/blocks/exalib/admin.php', ['courseid' => g::$COURSE->id, 'show' => 'categories']), \block_exalib\get_string("tab_manage_cats"), '', true);
		}

		if (block_exalib_has_global_cap(\block_exalib\CAP_MANAGE_REVIEWERS)) {
			$tabs[] = new tabobject('tab_manage_reviewers', new moodle_url('/blocks/exalib/reviewers.php', ['courseid' => g::$COURSE->id]), \block_exalib\get_string("tab_manage_reviewers"), '', true);
		}

		$tabtree = new tabtree($tabs);

		g::$PAGE->navbar->add(get_string('heading', 'block_exalib'), new moodle_url('/blocks/exalib'));

		foreach ($items as $level => $item) {
			if (!is_array($item)) {
				if (!is_string($item)) {
					trigger_error('not supported');
				}

				if ($item[0] == '=') {
					$item_name = substr($item, 1);
				} else {
					$item_name = \block_exalib\get_string($item);
				}

				$item = array('name' => $item_name, 'id' => $item);
			}

			if (!empty($item['id']) && $tabobj = $tabtree->find($item['id'])) {
				// overwrite selected
				$tabobj->selected = true;
				if (empty($item['link']) && $tabobj->link) {
					$item['link'] = $tabobj->link;
				}
			}

			$last_item_name = $item['name'];
			g::$PAGE->navbar->add($item['name'], !empty($item['link']) ? $item['link'] : null);
		}

		if (!array_filter($tabtree->subtree, function($t) {
			return $t->selected;
		})
		) {
			// none selected => always select first
			reset($tabtree->subtree)->selected = true;
		}

		g::$PAGE->set_title($strheader.': '.$last_item_name);
		g::$PAGE->set_heading(get_string('heading', 'block_exalib'));

		$this->init_js_css();

		$content = '';
		$content .= parent::header();
		$content .= '<div id="block_exalib">';
		$content .= $this->render($tabtree);

		return $content;
	}

	public function footer() {
		$content = '';
		$content .= '</div>';
		$content .= parent::footer();

		return $content;
	}

	public function set_tabs($tabs) {
		$this->tabs = $tabs;
	}

	public function init_js_css() {
		static $init = true;
		if (!$init) {
			return;
		}
		$init = false;

		// init default js / css
		g::$PAGE->requires->css('/blocks/exalib/css/exalib.css');
		g::$PAGE->requires->css('/blocks/exalib/css/skin-lion/ui.easytree.css');

		g::$PAGE->requires->jquery();
		g::$PAGE->requires->js('/blocks/exalib/javascript/common.js');
		g::$PAGE->requires->js('/blocks/exalib/javascript/exalib.js');
		g::$PAGE->requires->js('/blocks/exalib/javascript/jquery.easytree.js');
	}

	public function requires() {
		$this->init_js_css();

		return g::$PAGE->requires;
	}

	function back_button($url) {
		return $this->link_button(
			new moodle_url($url),
			\block_exalib\get_string('back')
		);
	}

	function link_button($url, $label, $attributes = []) {
		return html_writer::empty_tag('input', $attributes + [
				'type' => 'button',
				'exa-type' => 'link',
				'href' => $url,
				'value' => $label,
			]);
	}

	function item_list($type, $items) {
		global $CFG, $DB;

		foreach ($items as $item) {

			$fs = get_file_storage();
			$files = $fs->get_area_files(context_system::instance()->id,
				'block_exalib',
				'item_file',
				$item->id,
				'itemid',
				'',
				false);

			$areafiles = $fs->get_area_files(context_system::instance()->id,
				'block_exalib',
				'preview_image',
				$item->id,
				'itemid',
				'',
				false);
			$previewimage = reset($areafiles);

			/*
			$linkurl = '';
			$linktext = '';
			$linktextprefix = '';
			$targetnewwindow = false;

			if ($item->resource_id) {
				$linkurl = '/mod/resource/view.php?id='.$item->resource_id;
			} else {
				if ($item->link) {
					if (strpos($item->link, 'rtmp://') === 0) {
						$linkurl = 'detail.php?itemid='.$item->id.'&back='.g::$PAGE->url->out_as_local_url();
					} else {
						$linkurl = block_exalib_format_url($item->link);
						$linktext = trim($item->link_titel) ? $item->link_titel : $item->link;
						$targetnewwindow = true;
					}
				} else {
					if ($item->content) {
						$linkurl = 'detail.php?itemid='.$item->id.'&back='.g::$PAGE->url->out_as_local_url();
					}
				}
			}
			if (!$linkurl) {
				$linkurl = 'detail.php?itemid='.$item->id.($type !== 'public' ? '&type='.$type : '').'&back='.g::$PAGE->url->out_as_local_url();
			}
			*/

			echo '<div class="library-item">';

			$linkurl = 'detail.php?itemid='.$item->id.($type !== 'public' ? '&type='.$type : '').'&back='.g::$PAGE->url->out_as_local_url();
			echo '<a class="head" href="'.$linkurl.'">'.$item->name.'</a>';

			if ($type != 'public') {
				echo '<div><span class="libary_author">'.\block_exalib\trans('de:Status').':</span> ';
				if ($item->online) {
					echo \block_exalib\get_string('online');
				} else {
					echo \block_exalib\get_string('offline');
				}
				echo '</div>';
			}

			if ($previewimage) {
				$url = "{$CFG->wwwroot}/pluginfile.php/{$previewimage->get_contextid()}/block_exalib/item_file/".
					$previewimage->get_itemid()."?preview=thumb";
				echo '<div><img src="'.$url.'" /></div>';
			}

			if ($item->authors) {
				echo '<div><span class="libary_author">'.get_string('authors', 'block_exalib').':</span> '.$item->authors.'</div>';
			}

			if ($item->time_created) {
				echo '<div><span class="libary_author">'.get_string('created', 'block_exalib').':</span> '.
					userdate($item->time_created);
				if ($item->created_by && $tmpuser = $DB->get_record('user', array('id' => $item->created_by))) {
					echo ' '.get_string('by_person', 'block_exalib', fullname($tmpuser));
				}
				echo '</div>';
			}
			if ($item->time_modified > $item->time_created) {
				echo '<div><span class="libary_author">'.\block_exalib\trans(['en:Last Modified', 'de:Zulätzt geändert']).':</span> '.
					userdate($item->time_modified);
				if ($item->modified_by && $tmpuser = $DB->get_record('user', array('id' => $item->modified_by))) {
					echo ' '.get_string('by_person', 'block_exalib', fullname($tmpuser));
				}
				echo '</div>';
			}

			if ($item->abstract) {
				echo '<div class="libary_content">'.$item->abstract.'</div>';
			}
			if ($item->source) {
				echo '<div><span class="libary_author">'.get_string('source', 'block_exalib').':</span> '.$item->source.'</div>';
			}

			if ($files) {
				echo '<div>';
				echo '<span class="libary_author">'.\block_exalib\get_string('files').':</span> ';
				echo count($files);
				echo '</div>';
			}

			if ($type != 'public' && block_exalib_can_edit_item($item)) {
				echo '<span class="library-item-buttons">';
				echo $this->link_button($type.'.php?show=edit&type='.$type.'&id='.$item->id, get_string('edit', 'block_exalib'));
				echo $this->link_button($type.'.php?show=delete&type='.$type.'&id='.$item->id.'&sesskey='.sesskey(), get_string('delete', 'block_exalib'), [
					'exa-confirm' => \block_exalib\get_string('delete_confirmation', null, $item->name),
				]);
				echo '</span>';
			}

			echo '</div>';
		}
	}
}
