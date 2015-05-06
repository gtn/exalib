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
 * index.php
 * @package    block_exalib
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  gtn gmbh <office@gtn-solutions.com>
 * @author       Daniel Prieler <dprieler@gtn-solutions.com>
 */

if (!defined('BLOCK_EXALIB_IS_ADMIN_MODE')) {
    define('BLOCK_EXALIB_IS_ADMIN_MODE', 0);
}

require('inc.php');


if (BLOCK_EXALIB_IS_ADMIN_MODE) {
    block_exalib_require_creator();
} else {
    block_exalib_require_use();
}





$urloverview = new moodle_url('/blocks/exalib');
$urlpage = block_exalib_new_moodle_url();
$urlsearch = new moodle_url($urlpage, array('page' => null, 'q' => null, 'category_id' => null));
$urladd = new moodle_url($urlpage, array('show' => 'add'));
$urlcategory = new moodle_url($urlpage, array('page' => null, 'q' => null, 'category_id' => null));

$PAGE->set_url($urlpage);
$PAGE->set_context(context_system::instance());
/* ...For code checker... $PAGE->set_pagelayout('login'); */
$PAGE->navbar->add(get_string('heading', 'block_exalib'), $urloverview);

$PAGE->set_heading(get_string('heading', 'block_exalib'));

// $topGroups = array(11 => 'Abstracts', 12 => 'Documents', 13 => 'Images', 14 => 'Podcasts', 15 => 'Webcasts');


$categoryid = optional_param('category_id', '', PARAM_INT);
$filterid = 0;

/* $FILTER_CATEGORY = $DB->get_record("exalib_category", array('id' => $filterid));
 if ($FILTER_CATEGORY) $PAGE->navbar->add($FILTER_CATEGORY->name); */
if (BLOCK_EXALIB_IS_ADMIN_MODE) {
    $PAGE->navbar->add(get_string('administration', 'block_exalib'), 'admin.php');
}


$currentcategory = block_exalib_category_manager::getcategory($categoryid);
$currentcategorysubids = $currentcategory ? $currentcategory->self_inc_all_sub_ids : array(-9999);
$currentcategoryparents = block_exalib_category_manager::getcategoryparentids($categoryid);

if (BLOCK_EXALIB_IS_ADMIN_MODE) {
    require('admin.actions.inc.php');
}

$perpage = 20;
$page    = optional_param('page', 0, PARAM_INT);

$items = null;
$pagingbar = null;
$show = null;

if (BLOCK_EXALIB_IS_ADMIN_MODE) {
    $sqlwhere = "";
} else {
    $sqlwhere = "AND (item.hidden=0 OR item.hidden IS NULL)
        AND (item.online_from=0 OR item.online_from IS NULL
        OR (item.online_from <= ".time()."
        AND item.online_to >= ".time()."))";
}

if ($q = optional_param('q', '', PARAM_TEXT)) {
    $show = 'search';

    $q = trim($q);

    $qparams = preg_split('!\s+!', $q);

    $sqljoin = "";
    $sqlparams = array();

    if ($currentcategory) {
        $sqljoin .= " JOIN {exalib_item_category} ic
            ON (ic.item_id = item.id AND ic.category_id IN (".join(',', $currentcategorysubids)."))";
    }

    foreach ($qparams as $i => $qparam) {
        $qparam = $DB->sql_like_escape($qparam);
        
        $sqljoin .= " LEFT JOIN {exalib_item_category} ic$i ON item.id=ic$i.item_id";
        $sqljoin .= " LEFT JOIN {exalib_category} c$i ON ic$i.category_id=c$i.id";
        // $sqljoin .= " LEFT JOIN {exalib_item_category} ic$i ON item.id=ic$i.item_id AND ic$i.category_id=c$i";
        $sqlwhere .= " AND (item.link LIKE ? OR item.source LIKE ? OR item.file LIKE ? OR item.name LIKE ?
            OR item.authors LIKE ? OR item.content LIKE ? OR item.link_titel LIKE ? OR c$i.name LIKE ?) ";
        $sqlparams[] = "%$qparam%";
        $sqlparams[] = "%$qparam%";
        $sqlparams[] = "%$qparam%";
        $sqlparams[] = "%$qparam%";
        $sqlparams[] = "%$qparam%";
        $sqlparams[] = "%$qparam%";
        $sqlparams[] = "%$qparam%";
        $sqlparams[] = "%$qparam%";
    }

    // JOIN {exalib_item_category} AS ic ON item.id=ic.item_id AND ic.category_id=?

    $sql = "SELECT COUNT(*) FROM (SELECT item.id
    FROM {exalib_item} AS item
    $sqljoin
    WHERE 1=1 $sqlwhere
    GROUP BY item.id
    ) AS x";
    $count = $DB->get_field_sql($sql, $sqlparams);

    $pagingbar = new paging_bar($count, $page, $perpage, $urlpage);

    $sql = "SELECT item.*
    FROM {exalib_item} item
    $sqljoin
    WHERE 1=1 $sqlwhere
    GROUP BY item.id
    ORDER BY name";
    
    $items = $DB->get_recordset_sql($sql, $sqlparams, $page * $perpage, $perpage);

} else if ($currentcategory) {
    $show = 'category';

    $sqljoin = "    JOIN {exalib_item_category} ic ON (ic.item_id = item.id
        AND ic.category_id IN (".join(',', $currentcategorysubids)."))";

    $sql = "
        SELECT COUNT(DISTINCT item.id)
        FROM {exalib_item} item
        JOIN {exalib_item_category} ic ON (item.id=ic.item_id AND ic.category_id IN (".join(',', $currentcategorysubids)."))
    ";
   
    $count = $DB->get_field_sql($sql);

    $pagingbar = new paging_bar($count, $page, $perpage, $urlpage);

    $sql = "
        SELECT item.*
        FROM {exalib_item} item
        JOIN {exalib_item_category} ic ON (item.id=ic.item_id AND ic.category_id IN (".join(',', $currentcategorysubids)."))
        WHERE 1=1 $sqlwhere
        GROUP BY item.id
        ORDER BY GREATEST(time_created,time_modified) DESC
    ";
   
    $items = $DB->get_recordset_sql($sql, array(), $page * $perpage, $perpage);
} else {
    // Latest changes.
    $show = 'latest_changes';

    $sql = "
        SELECT item.*
        FROM {exalib_item} AS item
        WHERE 1=1 $sqlwhere
        GROUP BY item.id
        ORDER BY GREATEST(time_created,time_modified) DESC
    ";
    
    $items = $DB->get_recordset_sql($sql, array(), 0, 20);
}



if(!$items->valid())	$items=Array();


$PAGE->requires->css('/blocks/exalib/css/library.css');
$PAGE->requires->css('/blocks/exalib/css/skin-lion/ui.easytree.css');

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('exalib-main', 'block_exalib');
$PAGE->requires->jquery_plugin('easytree', 'block_exalib');

echo $OUTPUT->header();

?>
<div class="exalib_lib">

<?php

if (false && !$filterid) {
        ?>
        <h1 class="libary_head"><?php echo get_string('welcome', 'block_exalib');  ?></h1>


        <div class="libary_top_cat">
            <a class="exalib-blue-cat-lib" href="<?php echo $CFG->wwwroot; ?>/blocks/exalib/index.php?category_id=11"><?php echo get_string('abstracts', 'block_exalib')?></a>
            <a class="exalib-blue-cat-lib" href="<?php echo $CFG->wwwroot; ?>/blocks/exalib/index.php?category_id=12"><?php echo get_string('documents', 'block_exalib')?></a>
            <a class="exalib-blue-cat-lib" href="<?php echo $CFG->wwwroot; ?>/blocks/exalib/index.php?category_id=13"><?php echo get_string('images', 'block_exalib')?></a>
            <a class="exalib-blue-cat-lib" href="<?php echo $CFG->wwwroot; ?>/blocks/exalib/index.php?category_id=14"><?php echo get_string('podcasts', 'block_exalib')?></a>
            <a class="exalib-blue-cat-lib" href="<?php echo $CFG->wwwroot; ?>/blocks/exalib/index.php?category_id=15"><?php echo get_string('webcasts', 'block_exalib')?></a>


        </div>


        <!-- <div class="library_filter_main">
<a href="index.php?category_id=11">
    <img src="<? echo $CFG->wwwroot; ?>/pluginfile.php/213/course/section/154/ecoo_abstracts.png" height="43" width="212" /></a>
<a href="index.php?category_id=12">
    <img src="<? echo $CFG->wwwroot; ?>/pluginfile.php/213/course/section/154/ecoo_documents.png" height="43" width="212" /></a>
<a href="index.php?category_id=13">
    <img src="<? echo $CFG->wwwroot; ?>/pluginfile.php/213/course/section/154/ecoo_images.png" height="43" width="212" /></a>
<a href="index.php?category_id=14">
    <img src="<? echo $CFG->wwwroot; ?>/pluginfile.php/213/course/section/154/ecoo_podcasts.png" height="43" width="212" /></a>
<a href="index.php?category_id=15">
    <img src="<? echo $CFG->wwwroot; ?>/pluginfile.php/213/course/section/154/ecoo_webcasts.png" height="43" width="212" /></a>
        </div> -->

        <div class="library_result library_result_main">

<?php
    if (!$q):
?>
            <br /><br /><br />
            <form method="get" action="search.php">
                <input name="q" type="text" value="<?php p($q) ?>" style="width: 240px;" class="libaryfront_search" />
                <input value="<?php echo get_string('search', 'block_exalib'); ?>" type="submit" class="libaryfront_searchsub">
            </form>
<?php 
    else:
?>
            <form method="get" action="search.php">
                <input name="q" type="text" value="<?php p($q) ?>" style="width: 240px;" class="libaryfront_search" />
                <input value="<?php echo get_string('search', 'block_exalib'); ?>" type="submit" class="libaryfront_searchsub">
            </form>
<?php
    endif;
?>

<?php
    if ($items !== null) {
        echo '<h1 class="library_result_heading">'.get_string('results', 'block_exalib').'</h1>';

        if (!$items) {
            echo get_string('noitemsfound', 'block_exalib');
        } else {
            if ($pagingbar) {
                echo $OUTPUT->render($pagingbar);
            };
            print_items($items);
            if ($pagingbar) {
                echo $OUTPUT->render($pagingbar);
            };
        }
    }
?>
        </div>
        <?php
        echo $OUTPUT->footer();
        exit;
}
?>

<h1 class="libary_head"><?php
echo get_string('heading', 'block_exalib');
if ($currentcategory) {
    echo ': '.$currentcategory->name;
};
?></h1>

<div class="library_categories">

<form method="get" action="<?php echo $urlsearch; ?>">
    <?php echo html_writer::input_hidden_params($urlsearch); ?>
    <input name="q" type="text" value="<?php p($q) ?>" />
        <?php if ($currentcategory): ?>
        <select name="category_id">
            <option value="<?php echo $currentcategory->id; ?>">
            <?php echo get_string('inthiscat', 'block_exalib'); ?></option>
            <option value="0"><?php echo get_string('wholelib', 'block_exalib'); ?></option>
        </select>
        <?php
endif;
?>
    <input value="<?php echo get_string('search', 'block_exalib') ?>" type="submit">
</form>

<?php

if (BLOCK_EXALIB_IS_ADMIN_MODE && block_exalib_is_admin()) {
    echo '<a href="admin.php?show=categories">'.get_string('managecats', 'block_exalib').'</a>';
}

echo '<div id="exalib-categories"><ul>';
echo block_exalib_category_manager::walktree(function($cat, $suboutput) {
    global $urlcategory, $categoryid, $currentcategoryparents;

    if (!BLOCK_EXALIB_IS_ADMIN_MODE && !$cat->cnt_inc_subs) {
        // Hide empty categories.
        return;
    }

    $output = '<li id="exalib-menu-item-'.$cat->id.'" class="'.
        ($suboutput ? 'isFolder' : '').
        (in_array($cat->id, $currentcategoryparents) ? ' isExpanded' : '').
        ($cat->id == $categoryid ? ' isActive' : '').'">';
    $output .= '<a class="library_categories_item_title"
        href="'.$urlcategory->out(true, array('category_id' => $cat->id)).'">'.$cat->name.' ('.$cat->cnt_inc_subs.')'.'</a>';

    if ($suboutput) {
        $output .= '<ul>'.$suboutput.'</ul>';
    }

    echo '</li>';

    return $output;
});
echo '</ul></div>';

?>
</div>
<div class="library_result">

<?php

if (BLOCK_EXALIB_IS_ADMIN_MODE) {
    ?><a href="<?php echo $urladd; ?>"><?php echo get_string('newentry', 'block_exalib')?></a><?php
}

if ($show == 'latest_changes') {
    echo '<h1 class="library_result_heading">'.get_string('latest', 'block_exalib').'</h1>';
} else {
    echo '<h1 class="library_result_heading">'.get_string('results', 'block_exalib').'</h1>';
};

if (!$items) {
    echo get_string('noitemsfound', 'block_exalib');
} else {
    if ($pagingbar) {
        echo $OUTPUT->render($pagingbar);
    };
    print_items($items, BLOCK_EXALIB_IS_ADMIN_MODE);
    if ($pagingbar) {
        echo $OUTPUT->render($pagingbar);
    };
}

?>
</div>
</div>
<?php
echo $OUTPUT->footer();
