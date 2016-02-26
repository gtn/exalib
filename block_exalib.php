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

require_once(__DIR__.'/lib.php');

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
            $this->content->items[] = '<a title="'.get_string('managecontent', 'block_exalib').'"
                href="' . $CFG->wwwroot . '/blocks/exalib/admin.php?courseid=' . $COURSE->id . '">'.
                get_string('managecontent', 'block_exalib'). '</a>';
            $this->content->icons[] = '<img src="'.$CFG->wwwroot.'/blocks/exalib/pix/module_config.png"
                height="16" width="23" alt="'.get_string('managecontent', 'block_exalib').'" />';
        }

        return $this->content;
    }
}
