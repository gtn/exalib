<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**  
 * block_exalib.php
 * @package    block_exalib
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  gtn gmbh <office@gtn-solutions.com>
 * @author       Daniel Prieler <dprieler@gtn-solutions.com>
 */
require_once(__DIR__.'/lib.php');


/**
 * Exalib block
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  gtn gmbh <office@gtn-solutions.com>
 */
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
        global $CFG, $COURSE, $USER;

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

        $this->content->items[] = '<a title="'.get_string('heading', 'block_exalib').'"
            href="' . $CFG->wwwroot . '/blocks/exalib/index.php?courseid=' . $COURSE->id . '">'.
            get_string('heading', 'block_exalib').
            '</a>';
        $this->content->icons[] = '<img src="'.$CFG->wwwroot.'/blocks/exalib/pix/module_search.png" height="16" width="23"
            alt="'.get_string("heading", "block_exalib").'" />';

        if (block_exalib_is_creator()) {
            $this->content->items[] = '<a title="'.exalib_t('en:Manage Library Content', 'de:Inhalte bearbeiten').'"
                href="' . $CFG->wwwroot . '/blocks/exalib/admin.php?courseid=' . $COURSE->id . '">'.
                exalib_t('en:Manage Library Content', 'de:Inhalte bearbeiten') . '</a>';
            $this->content->icons[] = '<img src="'.$CFG->wwwroot.'/blocks/exalib/pix/module_config.png"
                height="16" width="23" alt="'.exalib_t('en:Manage Library Content', 'de:Inhalte bearbeiten').'" />';
        }

        return $this->content;
    }
}
