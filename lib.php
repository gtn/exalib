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
 * lib.php
 * @package    block_exalib
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  gtn gmbh <office@gtn-solutions.com>
 * @author       Daniel Prieler <dprieler@gtn-solutions.com>
 */
define('FILESYSTEMREPO_ID', 9);

/**
 * get current lang
 * @return lang
 */
function exalib_get_current_lang() {
    global $SESSION, $USER, $CFG;

    if (!empty($SESSION->lang)) {
        return $SESSION->lang;
    }
    if (!empty($USER->lang)) {
        return $USER->lang;
    }
    if (!empty($CFG->lang)) {
        return $CFG->lang;
    }
    return null;
}

/**
 * exalib_t
 * @param string $x 
 * @return string
 */
function exalib_t($x) {
    $args = func_get_args();
    $languagestrings = array();
    $params = array();

    foreach ($args as $i => $string) {
        if (preg_match('!^([^:]+):(.*)$!', $string, $matches)) {
            $languagestrings[$matches[1]] = $matches[2];
        } else if ($i == 0) {
            // ... first entry is default.
            $languagestrings[''] = $string;
        } else {
            // ... is param.
            $params[] = $string;
        }
    }

    $lang = exalib_get_current_lang();

    if ($lang && isset($languagestrings[$lang])) {
        $string = $languagestrings[$lang];
    } else {
        $string = reset($languagestrings);
    };

    return $string;
}

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
        throw new require_login_exception('You are no allowed to view Library Content');
    }
}

/**
 * block exalib require open
 * @return nothing
 */
function block_exalib_require_open() {
    block_exalib_require_use();
    if (!has_capability('block/exalib:use', context_system::instance())) {
        throw new require_login_exception('You are no allowed to view Library Content');
    }
}

/**
 * block exalib require creator
 * @return nothing
 */
function block_exalib_require_creator() {
    block_exalib_require_use();
    if (!block_exalib_is_creator()) {
        throw new require_login_exception('You are no Exalib Creator');
    }
}

/**
 * block exalib require admin
 * @return nothing
 */
function block_exalib_require_admin() {
    block_exalib_require_use();
    if (!block_exalib_is_admin()) {
        throw new require_login_exception('You are no Exalib Admin');
    }
}

/**
 * block exalib require can edit item
 * @param stdClass $item
 * @return nothing
 */
function block_exalib_require_can_edit_item(stdClass $item) {
    if (!block_exalib_can_edit_item($item)) {
        throw new require_login_exception('You are no allowed to edit this Item');
    }
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
function print_items($items, $admin=false) {
    global $CFG, $DB, $OUTPUT;

    foreach ($items as $item) {

        $fs = get_file_storage();
        $areafiles = $fs->get_area_files(context_system::instance()->id,
            'block_exalib',
            'item_file',
            $item->id,
            'itemid',
            '',
            false);
        $file = reset($areafiles);

        $linkurl = '';
        $linktext = '';
        $linktextprefix = '';
        $targetnewwindow = false;

        if ($file) {
            $linkurl = "{$CFG->wwwroot}/pluginfile.php/{$file->get_contextid()}/block_exalib/item_file/".$file->get_itemid();
            $linktextprefix = exalib_t('en:File', 'de:Datei');
            $linktextprefix .= ' '.$OUTPUT->pix_icon(file_file_icon($file), get_mimetype_description($file));
            $linktext = $file->get_filename();
            $targetnewwindow = true;
        } else if ($item->resource_id) {
            $linkurl = '/mod/resource/view.php?id='.$item->resource_id;
        } else if ($item->link) {
            if (strpos($item->link, 'rtmp://') === 0) {
                $linkurl = 'detail.php?itemid='.$item->id;
            } else if (strpos($item->link, 'filesystemrepo://') === 0) {
                $linkurl = 'file.php?itemid='.$item->id;
                $linktext = exalib_t('Download');
                $targetnewwindow = true;
            } else {
                $linkurl = $item->link;
                $linktext = trim($item->link_titel) ? $item->link_titel : $item->link;
                $targetnewwindow = true;
            }
        } else if ($item->content) {
            $linkurl = 'detail.php?itemid='.$item->id;
        }

        echo '<div class="library-item">';

        if ($linkurl) {
            echo '<a class="head" href="'.$linkurl.($targetnewwindow ? '" target="_blank' : '').'">'.$item->name.'</a>';
        } else {
            echo '<div class="head">'.$item->name.'</div>';
        };

        if ($item->content) {
            echo '<div class="libary_content">'.$item->content.'</div>';
        }
        if ($item->source) {
            echo '<div><span class="libary_author">'.exalib_t('en:Source', 'de:Quelle').':</span> '.$item->source.'</div>';
        }
        if ($item->authors) {
            echo '<div><span class="libary_author">'.exalib_t('en:Authors', 'de:Autoren').':</span> '.$item->authors.'</div>';
        }

        if ($item->time_created) {
            echo '<div><span class="libary_author">'.exalib_t('en:Created', 'de:Erstellt').':</span> '.
                userdate($item->time_created);
            if ($item->created_by && $tmpuser = $DB->get_record('user', array('id' => $item->created_by))) {
                echo ' '.exalib_t('en:by', 'de:von').' '.fullname($tmpuser);
            }
            echo '</div>';
        }
        if ($item->time_modified) {
            echo '<div><span class="libary_author">'.exalib_t('en:Last Modified', 'de:Zulätzt geändert').':</span> '.
                userdate($item->time_modified);
            if ($item->modified_by && $tmpuser = $DB->get_record('user', array('id' => $item->modified_by))) {
                echo ' '.exalib_t('en:by', 'de:von').' '.fullname($tmpuser);
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
        if ($admin && block_exalib_can_edit_item($item)) {
            echo '<span class="library-item-buttons">';
            echo '<a href="admin.php?show=edit&id='.$item->id.'">'.exalib_t('en:Edit', 'de:Ändern').'</a>';
            echo ' | <a href="admin.php?show=delete&id='.$item->id.'"">'.exalib_t('en:Delete', 'de:Löschen').'</a>';
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

/**
 * send stored file
 * @param integer $itemid 
 * @return nothing
 */
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

/**
 * plugin file
 * @param integer $course 
 * @param integer $cm 
 * @param string $context 
 * @param string $filearea 
 * @param array $args 
 * @param integer $forcedownload 
 * @param array $options 
 * @return nothing
 */
function block_exalib_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options) {
    block_exalib_require_open();

    $fs = get_file_storage();
    $areafiles = $fs->get_area_files(get_context_instance(CONTEXT_SYSTEM)->id,
        'block_exalib',
        'item_file',
        $args[0],
        'itemid',
        '',
        false);
    $file = reset($areafiles);

    if (!$file) {
        send_file_not_found();
    }

    session_get_instance()->write_close(); // Unlock session during fileserving.
    send_stored_file($file, 0, 0, $forcedownload, array('preview' => $preview));
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
     * @param function $functionbefore 
     * @param boolean $functionafter 
     * @param integer $level 
     * @param integer $parent     
     * @return output
     */
    static private function walktreeitem($functionbefore, $functionafter, $level=0, $parent=0) {
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

        if ($DB->get_records('exalib_category', null, '', 'id', 0, 1)) {
            return;
        }

        $mainid = $DB->insert_record('exalib_category', array(
            'parent_id' => 0,
            'name' => 'Main Category'
        ));
        $subid = $DB->insert_record('exalib_category', array(
            'parent_id' => $mainid,
            'name' => 'Sub Category'
        ));

        $itemid = $DB->insert_record('exalib_item', array(
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
            'item_id' => $itemid,
            'category_id' => $mainid
        ));
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

        self::$categories = $DB->get_records_sql("SELECT category.*, count(DISTINCT item.id) AS cnt
        FROM {exalib_category} category
        LEFT JOIN {exalib_item_category} ic ON (category.id=ic.category_id)
        LEFT JOIN {exalib_item} item ON item.id=ic.item_id ".
        (IS_ADMIN_MODE ?
        '' : "AND item.hidden=0
            AND item.online_from=0 OR (item.online_from <= ".time()." AND item.online_to >= ".time()."))").
        " WHERE 1=1
        ".(IS_ADMIN_MODE ? '' : "AND category.hidden=0")."
        GROUP BY category.id
        ORDER BY name");
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
