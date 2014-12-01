<?php

require_once __DIR__.'/lib.php';

class block_exalib extends block_list {

	function init() {
        $this->title = get_string('pluginname', 'block_exalib');
        $this->version = 2014102000;
    }

    function instance_allow_multiple() {
        return false;
    }
    
    function instance_allow_config() {
        return false;
    }
    
	function has_config() {
	    return true;
	}
    
    function get_content() {
    	global $CFG, $COURSE, $USER;
		
    	$context = context_system::instance();
        if (!has_capability('block/exalib:use', $context)) {
	        $this->content = '';
        	return $this->content;
        }
        
        if ($this->content !== NULL) {
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
        
		$this->content->items[]='<a title="' . get_string('heading', 'block_exalib') . '" href="' . $CFG->wwwroot . '/blocks/exalib/index.php?courseid=' . $COURSE->id . '">' . get_string('heading', 'block_exalib') . '</a>';
		$this->content->icons[]='<img src="' . $CFG->wwwroot . '/blocks/exalib/pix/module_search.png" height="16" width="23" alt="'.get_string("heading", "block_exalib").'" />';

		if (block_exalib_is_creator()) {
			$this->content->items[]='<a title="' . exalib_t('en:Manage Library Content', 'de:Inhalte bearbeiten') . '" href="' . $CFG->wwwroot . '/blocks/exalib/admin.php?courseid=' . $COURSE->id . '">' . exalib_t('en:Manage Library Content', 'de:Inhalte bearbeiten') . '</a>';
			$this->content->icons[]='<img src="' . $CFG->wwwroot . '/blocks/exalib/pix/module_config.png" height="16" width="23" alt="'.exalib_t('en:Manage Library Content', 'de:Inhalte bearbeiten').'" />';
		}

        return $this->content;
    }
}
