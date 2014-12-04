<?php

define('FILESYSTEMREPO_ID', 9);

function exalib_t($x) {
	global $SESSION;
	
	$args = func_get_args();
	$languageStrings = array();
	$params = array();
	
	foreach ($args as $i => $string) {
		if (preg_match('!^([^:]+):(.*)$!', $string, $matches)) {
			$languageStrings[$matches[1]] = $matches[2];
		} elseif ($i == 0) {
			// first entry is default
			$languageStrings[''] = $string;
		} else {
			// is param
			$params[] = $string;
		}
	}
	
	if (isset($languageStrings[$SESSION->lang]))
		$string = $languageStrings[$SESSION->lang];
	else
		$string = reset($languageStrings);
	
	return $string;
}

function block_exalib_new_moodle_url() {
	global $CFG;
	
	$moodle_path = preg_replace('!^[^/]+//[^/]+!', '', $CFG->wwwroot);
	
	return new moodle_url(str_replace($moodle_path, '', $_SERVER['REQUEST_URI']));
}

function block_exalib_is_creator() {
	return block_exalib_is_admin() || has_capability('block/exalib:creator', context_system::instance());
}

function block_exalib_is_admin() {
	return has_capability('block/exalib:admin', context_system::instance());
}

function block_exalib_require_use() {
	if (!has_capability('block/exalib:use', context_system::instance())) {
		throw new require_login_exception('You are no allowed to view Library Content');
	}
}

function block_exalib_require_open() {
	block_exalib_require_use();
	if (!has_capability('block/exalib:use', context_system::instance())) {
		throw new require_login_exception('You are no allowed to view Library Content');
	}
}

function block_exalib_require_creator() {
	block_exalib_require_use();
	if (!block_exalib_is_creator()) {
		throw new require_login_exception('You are no Exalib Creator');
	}
}

function block_exalib_require_admin() {
	block_exalib_require_use();
	if (!block_exalib_is_admin()) {
		throw new require_login_exception('You are no Exalib Admin');
	}
}

function block_exalib_require_can_edit_item(stdClass $item) {
	if (!block_exalib_can_edit_item($item)) {
		throw new require_login_exception('You are no allowed to edit this Item');
	}
}

function block_exalib_can_edit_item(stdClass $item) {
	global $USER;
	
	// admin is allowed
	if (block_exalib_is_admin()) return true;
	
	// item creator is allowed
	if ($item->created_by == $USER->id) return true;
	
	else return false;
}

function print_items($ITEMS, $admin=false) {
	global $CFG, $DB;

	foreach ($ITEMS as $item) {
		
		$fs = get_file_storage();
		$areafiles = $fs->get_area_files(context_system::instance()->id, 'block_exalib', 'item_file', $item->id, 'itemid', '', false);
		$file = reset($areafiles);

		$linkUrl = '';
		$linkText = '';
		$linkTextPrefix = '';
		$targetNewWindow = false;
		
		if ($file) {
			$linkUrl = "{$CFG->wwwroot}/pluginfile.php/{$file->get_contextid()}/block_exalib/item_file/".$file->get_itemid();
			$linkTextPrefix = exalib_t('en:File', 'de:Datei');
			$linkText = $file->get_filename();
			$targetNewWindow = true;
		} elseif ($item->resource_id) {
			$linkUrl = '/mod/resource/view.php?id='.$item->resource_id;
		} elseif ($item->link) {
			if (strpos($item->link, 'rtmp://') === 0) {
				$linkUrl = 'detail.php?itemid='.$item->id;
			} elseif (strpos($item->link, 'filesystemrepo://') === 0) {
				$linkUrl = 'file.php?itemid='.$item->id;
				$linkText = exalib_t('Download');
				$targetNewWindow = true;
			} else {
				$linkUrl = $item->link;
				$linkText = trim($item->link_titel) ? $item->link_titel : $item->link;
				$targetNewWindow = true;
			}
		} elseif ($item->content) {
			$linkUrl = 'detail.php?itemid='.$item->id;
		}
		
		echo '<div class="library-item">';

		if ($linkUrl)
			echo '<a class="head" href="'.$linkUrl.($targetNewWindow?'" target="_blank':'').'">'.$item->name.'</a>';
		else
			echo '<div class="head">'.$item->name.'</div>';
			
		if ($item->content) echo '<div class="libary_content">'.$item->content.'</div>';
		if ($item->source) echo '<div><span class="libary_author">'.exalib_t('en:Source', 'de:Quelle').':</span> '.$item->source.'</div>';
		if ($item->authors) echo '<div><span class="libary_author">'.exalib_t('en:Authors', 'de:Autoren').':</span> '.$item->authors.'</div>';
		
		if ($item->time_created) {
			echo '<div><span class="libary_author">'.exalib_t('en:Created', 'de:Erstellt').':</span> '.userdate($item->time_created);
			if ($item->created_by && $tmp_user = $DB->get_record('user', array('id'=>$item->created_by))) {
				echo ' '.exalib_t('en:by', 'de:von').' '.fullname($tmp_user);
			}
			echo '</div>';
		}
		if ($item->time_modified) {
			echo '<div><span class="libary_author">'.exalib_t('en:Last Modified', 'de:Zulätzt geändert').':</span> '.userdate($item->time_modified);
			if ($item->modified_by && $tmp_user = $DB->get_record('user', array('id'=>$item->modified_by))) {
				echo ' '.exalib_t('en:by', 'de:von').' '.fullname($tmp_user);
			}
			echo '</div>';
		}
		
		if ($linkText) {
			echo '<div>';
			if ($linkTextPrefix) echo '<span class="libary_author">'.$linkTextPrefix.'</span> ';
			echo '<a href="'.$linkUrl.($targetNewWindow?'" target="_blank"':'').'">'.$linkText.'</a>';
			echo '</div>';
		}
		if ($admin && block_exalib_can_edit_item($item)) {
			echo '<span class="library-item-buttons">';
			echo '<a href="admin.php?show=edit&id='.$item->id.'">'.exalib_t('en:Edit', 'de:Ändern').'</a>';
			echo ' | <a href="admin.php?show=delete&id='.$item->id.'"">'.exalib_t('en:Delete', 'de:Löschen').'</a>';
			echo '</span>';
		}
		
		echo '</div>';
	}
}

function block_exalib_print_jwplayer($options) {

	$options = array_merge(array(
		'flashplayer' => "jwplayer/player.swf",
		'primary' => "flash",
		'autostart' => false
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
		if (options.width == 'auto') options.width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
		if (options.height == 'auto') options.height = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
		
		var preview_start = false;
		if (!options.autostart) {
			preview_start = true;

			options.autostart = true;
			options.mute = true;
		}
		
		var p = jwplayer('player_2834').setup(options);
		
		if (preview_start) {
			p.onPlay(function(){
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

function block_exalib_send_stored_file($itemid) {
	global $DB, $CFG;
	
	$item = $DB->get_record('exalib_item', array('id' => $itemid));
	if (!$item) {
		send_file_not_found();
		die('file not found');
	}
	
	if (preg_match('!^filesystemrepo://(.*)$!', $item->link, $matches)) {
		require_once($CFG->dirroot . '/repository/lib.php');

		$repo = repository::get_repository_by_id(FILESYSTEMREPO_ID, SYSCONTEXTID);
		$file = $repo->get_file(urldecode(trim($matches[1])));
		if (!$file || !file_exists($file['path'])) {
			send_file_not_found();
			return;
		}

		send_file($file['path'], basename($file['path']));
	} else {
		send_file_not_found();
		die('file not found #2');
	}
}

function block_exalib_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options) {
	block_exalib_require_open();

	$fs = get_file_storage();
	$areafiles = $fs->get_area_files(get_context_instance(CONTEXT_SYSTEM)->id, 'block_exalib', 'item_file', $args[0], 'itemid', '', false);
	$file = reset($areafiles);
	
	if (!$file) {
		send_file_not_found();
	}

	session_get_instance()->write_close(); // unlock session during fileserving
	send_stored_file($file, 0, 0, $forcedownload, array('preview' => $preview));
}

class block_exalib_category_manager {
	static $categories = null;
	static $categoriesByParent = null;
	
	static function getCategory($category_id) {
		self::load();
		
		return isset(self::$categories[$category_id]) ? self::$categories[$category_id] : null;
	}
	
	static function walkTree($functionBefore, $functionAfter = null) {
		self::load();

		return self::walkTreeItem($functionBefore, $functionAfter);
	}
	
	static private function walkTreeItem($functionBefore, $functionAfter, $level=0, $parent=0) {
		if (empty(self::$categoriesByParent[$parent])) return;
		
		$output = '';
		foreach (self::$categoriesByParent[$parent] as $cat) {
			if ($functionBefore) $output .= $functionBefore($level, $parent, $cat);
			$output .= self::walkTreeItem($functionBefore, $functionAfter, $level+1, $cat->id);
			if ($functionAfter) $output .= $functionAfter($level, $parent, $cat);
		}
		return $output;
	}
	
	static function createDefaultCategories() {
		global $DB;
		
		if ($DB->get_records('exalib_category', null, '', 'id', 0, 1)) {
			return;
		}
		
		$main_id = $DB->insert_record('exalib_category', array(
			'parent_id' => 0,
			'name' => 'Main Category'
		));
		$sub_id = $DB->insert_record('exalib_category', array(
			'parent_id' => $main_id,
			'name' => 'Sub Category'
		));
		
		$item_id = $DB->insert_record('exalib_item', array(
			'resource_id' => '',
			'link' => '',
			'source' => '',
			'file' => '',
			'name' => '',
			'authors' => '',
			'content' => '',
			'name' => 'Test Entry'
		));

		$DB->insert_record('exalib_item_category', array(
			'item_id' => $item_id,
			'category_id' => $main_id
		));
	}
	
	static function load() {
		global $DB;
		
		if (self::$categories !== null) {
			// already loaded
			return;
		}
		
		self::createDefaultCategories();
		
		self::$categories = $DB->get_records_sql("SELECT category.*, count(DISTINCT item.id) AS cnt
		FROM {exalib_category} AS category
		LEFT JOIN {exalib_item_category} AS ic ON (category.id=ic.category_id)
		LEFT JOIN {exalib_item} AS item ON item.id=ic.item_id ".(IS_ADMIN_MODE?'':"AND IFNULL(item.hidden,0)=0 AND (IFNULL(item.online_from,0)=0 OR (item.online_from <= ".time()." AND item.online_to >= ".time()."))")."
		WHERE 1=1
		".(IS_ADMIN_MODE?'':"AND IFNULL(category.hidden,0)=0")."
		GROUP BY category.id
		ORDER BY name");
		self::$categoriesByParent = array();

		foreach (self::$categories as &$cat) {

			self::$categoriesByParent[$cat->parent_id][$cat->id] = &$cat;
			
			$cnt = $cat->cnt;
			$cat_id = $cat->id;

			// find parents
			while (true) {
				if (!isset($cat->cnt_inc_subs)) $cat->cnt_inc_subs = 0;
				$cat->cnt_inc_subs += $cnt;
				
				if (!isset($cat->self_inc_all_sub_ids)) $cat->self_inc_all_sub_ids = array();
				$cat->self_inc_all_sub_ids[] = $cat_id;
			
				if (($cat->parent_id > 0) && isset(self::$categories[$cat->parent_id])) {
					// $parentCat
					$cat =& self::$categories[$cat->parent_id];
				} else {
					break;
				}
			}
		}
		unset($cat);
	}
}
