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

	public function header($items = []) {
		$items = (array)$items;
		$strheader = \block_exalib\get_string('pluginname');

		$last_item_name = '';
		$tabs = array();

		$tabs[] = new tabobject('tab_library', new moodle_url('/blocks/exalib/index.php', ['courseid' => g::$COURSE->id]), \block_exalib\get_string("heading"), '', true);

		if (block_exalib_is_creator()) {
			$tabs[] = new tabobject('tab_managecontent', new moodle_url('/blocks/exalib/admin.php', ['courseid' => g::$COURSE->id]), \block_exalib\get_string("tab_managecontent"), '', true);
			$tabs[] = new tabobject('tab_managecats', new moodle_url('/blocks/exalib/admin.php', ['courseid' => g::$COURSE->id, 'show' => 'categories']), \block_exalib\get_string("tab_managecats"), '', true);
		}

		$tabtree = new tabtree($tabs);

		foreach ($items as $level => $item) {
			if (!is_array($item)) {
				if (!is_string($item)) {
					trigger_error('not supported');
				}

				if ($item[0] == '=')
					$item_name = substr($item, 1);
				else
					$item_name = \block_exalib\get_string($item);

				$item = array('name' => $item_name, 'id'=>$item);
			}

			if (!empty($item['id']) && $tabobj = $tabtree->find($item['id'])) {
				// overwrite selected
				$tabobj->selected = true;
				if (empty($item['link']) && $tabobj->link) {
					$item['link'] = $tabobj->link;
				}
			}

			$last_item_name = $item['name'];
			g::$PAGE->navbar->add($item['name'], !empty($item['link'])? $item['link'] : null);
		}

		if (!array_filter($tabtree->subtree, function($t) { return $t->selected; })) {
			// none selected => always select first
			reset($tabtree->subtree)->selected = true;
		}

		g::$PAGE->set_title($strheader.': '.$last_item_name);
		g::$PAGE->set_heading(get_string('heading', 'block_exalib'));
		/*

		block_exastud_init_js_css();
		*/

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

	public function requires() {
		global $PAGE;

		// init default js / css
		block_exacomp_init_js_css();

		return $PAGE->requires;
	}
}
