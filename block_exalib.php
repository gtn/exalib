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

defined('MOODLE_INTERNAL') || die();

require __DIR__.'/inc.php';

use \block_exalib\globals as g;

class block_exalib extends block_list {

	/**
	 * Init
	 * @return nothing
	 */
	public function init() {
		$this->title = get_string('pluginname', 'block_exalib');
		$this->version = 2014102000;
	}

	/**
	 * Inctance allow multiple
	 * @return false
	 */
	public function instance_allow_multiple() {
		return false;
	}

	/**
	 * Inctance allow config
	 * @return false
	 */
	public function instance_allow_config() {
		return false;
	}

	/**
	 * Get content
	 * @return content
	 */
	public function get_content() {
		global $CFG, $COURSE, $OUTPUT;

		$context = context_system::instance();

		if (!has_capability('block/exalib:use', $context)) {
			$this->content = '';

			return $this->content;
		}

		if ($this->content !== null) {
			return $this->content;
		}

		if (empty($this->instance)) {
			$this->content = '';

			return $this->content;
		}

		$this->content = new stdClass;
		$this->content->items = array();
		$this->content->icons = array();
		$this->content->footer = '';

		$icon = '<img src="'.$OUTPUT->pix_url('module_search', 'block_exalib').'" class="icon" alt="" />';
		$url = new moodle_url('/blocks/exalib/index.php', ['courseid' => g::$COURSE->id]);
		$this->content->items[] = '<a href="'.$url.'">'.$icon.
			\block_exalib\get_string('tab_library').
			'</a>';

		if (block_exalib_course_settings::use_review()) {
			$url = new moodle_url('/blocks/exalib/mine.php', ['courseid' => g::$COURSE->id]);
			$this->content->items[] = '<a href="'.$url.'">'.$icon.
				\block_exalib\get_string('tab_mine').
				'</a>';

			if (block_exalib_is_reviewer()) {
				$url = new moodle_url('/blocks/exalib/mine.php?type=review', ['courseid' => g::$COURSE->id]);
				$this->content->items[] = '<a href="'.$url.'">'.$icon.
					\block_exalib\get_string('tab_review').
					'</a>';
			}
		}

		if (block_exalib_get_fachsprachliches_lexikon_id()) {
			$url = new moodle_url('/blocks/exalib/fachsprachliches_lexikon.php', ['courseid' => g::$COURSE->id]);
			$this->content->items[] = '<a href="'.$url.'">'.$icon.
				\block_exalib\get_string('tab_fachsprachliches_lexikon').
				'</a>';
		}

		if (block_exalib_has_cap(\block_exalib\CAP_MANAGE_CONTENT)) {
			$icon = '<img src="'.$OUTPUT->pix_url('module_config', 'block_exalib').'" class="icon" alt="" />';
			$url = new moodle_url('/blocks/exalib/admin.php', ['courseid' => g::$COURSE->id]);
			$this->content->items[] = '<a href="'.$url.'">'.$icon.
				\block_exalib\get_string('tab_manage_content').
				'</a>';
		}
		if (block_exalib_has_cap(\block_exalib\CAP_MANAGE_CATS)) {
			$icon = '<img src="'.$OUTPUT->pix_url('module_config', 'block_exalib').'" class="icon" alt="" />';
			$url = new moodle_url('/blocks/exalib/admin.php', ['courseid' => g::$COURSE->id, 'show' => 'categories']);
			$this->content->items[] = '<a href="'.$url.'">'.$icon.
				\block_exalib\get_string('tab_manage_cats').
				'</a>';
		}

		if (block_exalib_has_cap(\block_exalib\CAP_MANAGE_REVIEWERS) && block_exalib_course_settings::use_review()) {
			$icon = '<img src="'.$OUTPUT->pix_url('module_config', 'block_exalib').'" class="icon" alt="" />';
			$url = new moodle_url('/blocks/exalib/reviewers.php', ['courseid' => g::$COURSE->id]);
			$this->content->items[] = '<a href="'.$url.'">'.$icon.
				\block_exalib\get_string('tab_manage_reviewers').
				'</a>';
		}

		if (block_exalib_has_cap(\block_exalib\CAP_COURSE_SETTINGS)) {
			$icon = '<img src="'.$OUTPUT->pix_url('module_config', 'block_exalib').'" class="icon" alt="" />';
			$url = new moodle_url('/blocks/exalib/course_settings.php', ['courseid' => g::$COURSE->id]);
			$this->content->items[] = '<a href="'.$url.'">'.$icon.
				\block_exalib\get_string('tab_course_settings').
				'</a>';
		}

		return $this->content;
	}
}
