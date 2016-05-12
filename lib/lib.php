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

/**
 * block exalib require use
 * @return nothing
 */
function block_exalib_require_use() {
	if (!has_capability('block/exalib:use', context_system::instance())) {
		throw new require_login_exception(get_string('notallowed', 'block_exalib'));
	}
}

/**
 * block exalib require open
 * @return nothing
 */
function block_exalib_require_view_item($item_or_id) {
	block_exalib_require_use();

	if (is_object($item_or_id)) {
		$item = $item_or_id;
	} else {
		$item = g::$DB->get_record('block_exalib_item', array('id' => $item_or_id));
	}

	if (!$item) {
		throw new moodle_exception('item not found');
	}

	if ($item->created_by == g::$USER->id) {
		return true;
	}

	// TODO: is reviewer
	// TODO: is online
}

/**
 * block exalib require creator
 * @return nothing
 */
function block_exalib_require_creator() {
	block_exalib_require_use();
	if (!block_exalib_is_creator()) {
		throw new require_login_exception(get_string('nocreator', 'block_exalib'));
	}
}

/**
 * block exalib require admin
 * @return nothing
 */
function block_exalib_require_admin() {
	block_exalib_require_use();
	if (!block_exalib_is_admin()) {
		throw new require_login_exception(get_string('noadmin', 'block_exalib'));
	}
}

/**
 * block exalib require can edit item
 * @param stdClass $item
 * @return nothing
 */
function block_exalib_require_can_edit_item(stdClass $item) {
	if (!block_exalib_can_edit_item($item)) {
		throw new require_login_exception(get_string('noedit', 'block_exalib'));
	}
}

/**
 * wrote own function, so eclipse knows which type the output renderer is
 * @return \block_exastud_renderer
 */
function block_exalib_get_renderer() {
	return g::$PAGE->get_renderer('block_exalib');
}

function block_exalib_is_kasuistik() {
	return true;
}

function block_exalib_get_url_for_file(stored_file $file) {
	return moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(),
		$file->get_itemid(), $file->get_filepath(), $file->get_filename());
}

/**
 * can edit item ?
 * @param stdClass $item
 * @return boolean
 */
function block_exalib_can_edit_item(stdClass $item) {
	global $USER;

	// Admin is allowed.
	if (block_exalib_is_admin()) {
		return true;
	}

	// Item creator is allowed.
	if ($item->created_by == $USER->id) {
		return true;
	} else {
		return false;
	};
}

/**
 * print items
 * @param array $items
 * @param boolean $admin
 * @return wrapped items
 */
function print_items($items, $admin = false) {
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

		$linkurl = '';
		$linktext = '';
		$linktextprefix = '';
		$targetnewwindow = false;

		if ($item->resource_id) {
			$linkurl = '/mod/resource/view.php?id='.$item->resource_id;
		} else if ($item->link) {
			if (strpos($item->link, 'rtmp://') === 0) {
				$linkurl = 'detail.php?itemid='.$item->id.'&back='.g::$PAGE->url->out_as_local_url();
			} else {
				$linkurl = $item->link;
				$linktext = trim($item->link_titel) ? $item->link_titel : $item->link;
				$targetnewwindow = true;
			}
		} else if ($item->content) {
			$linkurl = 'detail.php?itemid='.$item->id.'&back='.g::$PAGE->url->out_as_local_url();
		}

		echo '<div class="library-item">';

		if ($linkurl) {
			echo '<a class="head" href="'.$linkurl.($targetnewwindow ? '" target="_blank' : '').'">'.$item->name.'</a>';
		} else {
			echo '<div class="head">'.$item->name.'</div>';
		};

		if ($previewimage) {
			$url = "{$CFG->wwwroot}/pluginfile.php/{$previewimage->get_contextid()}/block_exalib/item_file/".
				$previewimage->get_itemid()."?preview=thumb";
			echo '<div><img src="'.$url.'" /></div>';
		}

		/*
		if ($item->content) {
			echo '<div class="libary_content">'.$item->content.'</div>';
		}
		*/
		if ($item->source) {
			echo '<div><span class="libary_author">'.get_string('source', 'block_exalib').':</span> '.$item->source.'</div>';
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
		if ($item->time_modified) {
			echo '<div><span class="libary_author">'.\block_exalib\trans(['en:Last Modified', 'de:Zulätzt geändert']).':</span> '.
				userdate($item->time_modified);
			if ($item->modified_by && $tmpuser = $DB->get_record('user', array('id' => $item->modified_by))) {
				echo ' '.get_string('by_person', 'block_exalib', fullname($tmpuser));
			}
			echo '</div>';
		}

		if ($linktext) {
			echo '<div>';
			if ($linktextprefix) {
				echo '<span class="libary_author">'.$linktextprefix.'</span> ';
			};
			echo '<a href="'.$linkurl.($targetnewwindow ? '" target="_blank"' : '').'">'.$linktext.'</a>';
			echo '</div>';
		}

		if ($files) {
			echo '<div>';
			echo '<span class="libary_author">'.\block_exalib\get_string('files').':</span> ';

			foreach ($files as $file) {
				echo '<a href="'.block_exalib_get_url_for_file($file).'" target="_blank">'.
					block_exalib_get_renderer()->pix_icon(file_file_icon($file), get_mimetype_description($file)).
					' '.$file->get_filename().'</a>&nbsp;&nbsp;&nbsp;';
			}
			echo '</div>';
		}

		if ($admin && block_exalib_can_edit_item($item)) {
			echo '<span class="library-item-buttons">';
			echo '<input type="button" href="admin.php?show=edit&id='.$item->id.'" value="'.get_string('edit', 'block_exalib').'" onclick="document.location.href=this.getAttribute(\'href\');" />';
			echo '<input type="button" href="admin.php?show=delete&id='.$item->id.'" value="'.get_string('delete', 'block_exalib').'" onclick="document.location.href=this.getAttribute(\'href\');" />';
			echo '</span>';
		}

		echo '</div>';
	}
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
	public static $categories = null;
	/**
	 * @var $categoriesbyparent - categories by parent
	 */
	public static $categoriesbyparent = null;

	/**
	 * get category
	 * @param integer $categoryid
	 * @return category
	 */
	public static function getcategory($categoryid) {
		self::load();

		return isset(self::$categories[$categoryid]) ? self::$categories[$categoryid] : null;
	}

	/**
	 * get category parent id
	 * @param integer $categoryid
	 * @return array of category
	 */
	public static function getcategoryparentids($categoryid) {
		self::load();

		$parents = array();
		for ($i = 0; $i < 100; $i++) {
			$c = self::getcategory($categoryid);
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
	 * @param function $functionbefore
	 * @param boolean $functionafter
	 * @return tree item
	 */
	public static function walktree($functionbefore, $functionafter = true) {
		self::load();

		if ($functionafter === true) {
			$functionafter = $functionbefore;
			$functionbefore = null;
		}

		return self::walktreeitem($functionbefore, $functionafter);
	}

	/**
	 * walk tree item
	 * @param Callback $functionbefore
	 * @param Callback $functionafter
	 * @param integer $level
	 * @param integer $parent
	 * @return output
	 */
	static private function walktreeitem($functionbefore, $functionafter, $level = 0, $parent = 0) {
		if (empty(self::$categoriesbyparent[$parent])) {
			return;
		}

		$output = '';
		foreach (self::$categoriesbyparent[$parent] as $cat) {
			if ($functionbefore) {
				$output .= $functionbefore($cat);
			};

			$suboutput = self::walktreeitem($functionbefore, $functionafter, $level + 1, $cat->id);

			if ($functionafter) {
				$output .= $functionafter($cat, $suboutput);
			};
		}

		return $output;
	}

	/**
	 * create default categories
	 * @return nothing
	 */
	public static function createdefaultcategories() {
		global $DB;

		if ($DB->get_records('block_exalib_category', null, '', 'id', 0, 1)) {
			return;
		}

		$DB->execute("INSERT INTO {block_exalib_category} (id, parent_id, name, hidden) VALUES (1, 0, 'Tags', 0)");
		if (block_exalib_is_kasuistik()) {
			$DB->execute("INSERT INTO {block_exalib_category} (id, parent_id, name, hidden) VALUES (2, 0, 'Schulstufe', 0)");
			$DB->execute("INSERT INTO {block_exalib_category} (id, parent_id, name, hidden) VALUES (3, 0, 'Schulform', 0)");
		}

		$DB->execute("ALTER TABLE {block_exalib_category} AUTO_INCREMENT=1001");
	}

	/**
	 * load object
	 * @return nothing
	 */
	public static function load() {
		global $DB;

		if (self::$categories !== null) {
			// Already loaded.
			return;
		}

		self::createdefaultcategories();

		self::$categories = $DB->get_records_sql("
        	SELECT category.*, count(DISTINCT item.id) AS cnt
        	FROM {block_exalib_category} category
        	LEFT JOIN {block_exalib_item_category} ic ON (category.id=ic.category_id)
        	LEFT JOIN {block_exalib_item} item ON item.id=ic.item_id 
        	WHERE 1=1
        	".(BLOCK_EXALIB_IS_ADMIN_MODE ? '' : "
	            AND (category.hidden=0 OR category.hidden IS NULL)
    	        AND (item.hidden=0 OR item.hidden IS NULL)
        	    AND (item.online_from=0 OR item.online_from IS NULL OR
                    (item.online_from <= ".time()." AND item.online_to >= ".time()."))
			")."
			GROUP BY category.id
			ORDER BY name
		");
        self::$categoriesbyparent = array();

        foreach (self::$categories as &$cat) {

            self::$categoriesbyparent[$cat->parent_id][$cat->id] = &$cat;

            $cnt = $cat->cnt;
            $catid = $cat->id;

            $cat->level = 0;
            $level =& $cat->level;

            // Find parents.
            while (true) {
                if (!isset($cat->cnt_inc_subs)) {
                    $cat->cnt_inc_subs = 0;
                };
                $cat->cnt_inc_subs += $cnt;

                if (!isset($cat->self_inc_all_sub_ids)) {
                    $cat->self_inc_all_sub_ids = array();
                };
                $cat->self_inc_all_sub_ids[] = $catid;

                if (($cat->parent_id > 0) && isset(self::$categories[$cat->parent_id])) {
                    // ParentCat.
                    $level++;
                    $cat =& self::$categories[$cat->parent_id];
                } else {
                    break;
                }
            }
        }
        unset($cat);
    }
}
