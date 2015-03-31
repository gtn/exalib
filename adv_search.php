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
 * adv_search.php
 * @package    block_exalib
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  gtn gmbh <office@gtn-solutions.com>
 * @author       Daniel Prieler <dprieler@gtn-solutions.com>
 */
require('inc.php');

block_exalib_require_use();

$PAGE->set_url('/', array());
$PAGE->set_course($SITE);

$overviewpage = new moodle_url('/blocks/exalib');

$PAGE->set_url('/blocks/exalib');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('login');
$PAGE->navbar->add(get_string('heading', 'block_exalib'), $overviewpage);

$PAGE->set_heading(get_string('heading', 'block_exalib'));

$topgroups = array(11 => 'Abstracts', 12 => 'Documents', 13 => 'Images', 14 => 'Podcasts', 15 => 'Webcasts');

$q = optional_param('q', '', PARAM_TEXT);
$categoryids = optional_param_array('category_ids', array(), PARAM_INT);
$subfilterid = optional_param('sub_filter_id', '', PARAM_INT);
$searchby = optional_param('search_by', 'all', PARAM_TEXT);



$perpage = 20;
$page    = optional_param('page', 0, PARAM_INT);

$items = null;
$pagingbar = null;

if ($categoryids) {
    $q = trim($q);

    $sqljoin = "";
    $sqlwhere = "";
    $sqljoinsubfilter = "";
    $sqlparams = array();

    $filterids = $categoryids;
    if (count($categoryids) == 1) {
        // Also filter sub.
        $subfilter = optional_param('sub_filter', 0, PARAM_INT);
        if ($subfilter) {
            $filterids[] = $subfilter;
        }
    }

    $sqljoin .= " JOIN {exalib_item_category} ic_filter ON (ic_filter.item_id = item.id AND (ic_filter.category_id IN (".
        join(',', $filterids).")))";
    if ($subfilterid) {
        $sqljoinsubfilter = " JOIN {exalib_item_category} ic_subfilter ".
            "ON (ic_subfilter.item_id = item.id AND ic_subfilter.category_id=".$subfilterid.")";
    }

    if ($q) {
        $qparams = preg_split('/ /', $q);
        foreach ($qparams as $i => $qparam) {
            if ($searchby == 'title') {
                $sqlwhere .= " AND (item.name LIKE ?) ";
                $sqlparams[] = "%$qparam%";
            } else if ($searchby == 'author') {
                $sqlwhere .= " AND (item.authors LIKE ?) ";
                $sqlparams[] = "%$qparam%";
            } else if ($searchby == 'source') {
                $sqlwhere .= " AND (item.source LIKE ?) ";
                $sqlparams[] = "%$qparam%";
            } else {
                $sqljoin .= " LEFT JOIN {exalib_item_category} AS ic$i ON item.id=ic$i.item_id";
                $sqljoin .= " LEFT JOIN {exalib_category} AS c$i ON ic$i.category_id=c$i.id";

                $sqlwhere .= " AND (item.name LIKE ? OR item.authors LIKE ? OR item.source LIKE ? OR c$i.name LIKE ?) ";
                $sqlparams[] = "%$qparam%";
                $sqlparams[] = "%$qparam%";
                $sqlparams[] = "%$qparam%";
                $sqlparams[] = "%$qparam%";
            }
        }
    }

    $sql = "SELECT COUNT(*) FROM (SELECT item.id
    FROM {exalib_item} AS item
    $sqljoin
    $sqljoinsubfilter
    WHERE 1=1 $sqlwhere
    GROUP BY item.id
    ) AS x";
    $count = $DB->get_field_sql($sql, $sqlparams);

    $pagingbar = new paging_bar($count, $page, $perpage, new moodle_url($_SERVER['REQUEST_URI']));

    $sql = "SELECT item.*
    FROM {exalib_item} AS item
    $sqljoin
    $sqljoinsubfilter
    WHERE 1=1 $sqlwhere
    GROUP BY item.id
    ORDER BY name
    LIMIT ".$page * $perpage.', '.$perpage;
    $items = $DB->get_records_sql($sql, $sqlparams);

    // SUBFILTER.
    $sql = "SELECT c.category_id AS id, COUNT(item.id) AS cnt
    FROM {exalib_item} item
    $sqljoin
    JOIN {exalib_item_category} AS c ON (c.item_id = item.id)
    WHERE 1=1 $sqlwhere
    GROUP BY c.category_id
    ORDER BY c.category_id";
    $subfiltercategories = $DB->get_records_sql($sql, $sqlparams);
    $allcategories = $DB->get_records_sql("SELECT id, name, parent_id
    FROM {exalib_category} category
    WHERE parent_id!=1 -- no types in select
    ORDER BY name");

    foreach ($subfiltercategories as $subfiltercategory) {
        if (!$allcategories[$subfiltercategory->id]) {
            unset($subfiltercategories[$subfiltercategory->id]);
            continue;
        }

        $subfiltercategory->name = $allcategories[$subfiltercategory->id]->name;
        $subfiltercategory->parent_id = $allcategories[$subfiltercategory->id]->parent_id;

        if ($subfiltercategory->parent_id && $allcategories[$subfiltercategory->parent_id]->parent_id) {
            $subfiltercategory->name = $allcategories[$subfiltercategory->parent_id]->name.' >> '.$subfiltercategory->name;
        }
    }
    uasort($subfiltercategories, create_function('$a, $b', 'return strcmp($a->name, $b->name);'));

    /*
    echo "<pre>";
    print_r($subfiltercategories); /* ... */
}

$PAGE->requires->js('/blocks/exalib/js/jquery.js', true);
$PAGE->requires->js('/blocks/exalib/js/library.js', true);
$PAGE->requires->js('/blocks/exalib/js/adv_search.js', true);
$PAGE->requires->css('/blocks/exalib/css/library.css');

echo $OUTPUT->header();

?>
<style>
.library-item {
    display: block;
    border: 1px solid white;
    padding: 5px 10px;
    margin: 5px 0;
}
.library-item:hover {
    background: -moz-linear-gradient(center top , #FAFAFA 0%, #F5F5F5 100%) repeat scroll 0 0 rgba(0, 0, 0, 0);
    border: 1px solid #DDDDDD;
    border-radius: 5px;
    box-shadow: 0 1px 0 #FFFFFF inset;
}
.library-item .head {
    font-family: 'vegurregular' !important;
    color: #007BB6;
    font-size: 18px;
    font-weight: normal;
    margin-bottom: 15px;
}
.library_filter, .library_filter_main {
float: left;
    width: 300px;
}
.library_filter a {
    display: block;
    padding: 2px 0 3px 5px;
}
.library_filter a:hover {
    text-decoration: underline;
}
.library_filter_main a {
    display: block;
    padding: 1px 0 1px 5px;
}
.library_top_filter a:hover {
    text-decoration: underline;
}

.library_result {
    
    margin-left: 300px;
}
h1.library_result_heading {
    font-size: 16px;
    margin-top: 34px;
    margin-bottom: 20px;
    padding-left: 0;
}

h2.library_filter_heading {
    font-size: 16px;
    padding-bottom: 10px;
}


/* --- Libary new Styles ----- */ 

.library_result .paging, .library_result .library_result_heading {
    text-align: center;
}

.library_result .paging {
    margin-bottom: 20px;
    color: #666666;
}
.library_result .library_result_heading {
    color: #666666;
}


.libary_author {
    color: #000;
}

h1.libary_head, .exalib h3 {
    -moz-border-bottom-colors: none !important;
    -moz-border-left-colors: none !important;
    -moz-border-right-colors: none !important;
    -moz-border-top-colors: none !important;
    background: none repeat scroll 0 0 rgba(0, 0, 0, 0) !important;
    
    border-color: #C8C8C8;
    border-image: none !important;
    border-style: none;
    border-width: 0;
    
    color: #003876;
    
    border-bottom: 1px solid #C8C8C8 !important;
    padding-bottom: 20px !important;
    
    font-size: 1.9em;
    font-weight: normal !important;
    margin-bottom: 35px !important;
    margin-top: 20px !important;
}

.exalib {
    margin: 0 10px;
    padding: 10px;
}

.library_filter a {
    color: #666666;
}

.library_filter_heading {
    color: #666;
}

.libary_nores {
    font-size: 18px;
    color: #007BB6;
}

a.exalib-lib {
    margin-top: 15px;
    margin-top: 15px;
    background: url([[pix:theme|bgbutton]]) repeat-x scroll 0 0 #003876;
    border: 1px solid #003F85;
    border-radius: 10px;
    box-shadow: 0 5px 5px #005BC1 inset, 0 1px 1px rgba(0, 0, 0, 0.05);
    color: #FFFFFF;
    font-size: 16px;
    padding: 5px 15px;
}

a.exalib-blue-cat-lib {
    margin-top: 5px;
    margin-top: 5px;
    background: url([[pix:theme|bgbutton]]) repeat-x scroll 0 0 #003876;
    border: 1px solid #003F85;
    border-radius: 7px;
    box-shadow: 0 5px 5px #005BC1 inset, 0 1px 1px rgba(0, 0, 0, 0.05);
    color: #FFFFFF;
    font-size: 14px;
    padding: 3px 20px;
}

.library_result_main {

    margin-bottom: 100px;
    margin-top: 30px;
}

.libary_top_cat {
    text-align: center;
}

.exalib input[type="text"] {
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset;
    border-radius: 7px;
    border: 1px solid #CCCCCC;
    font-size: 14px;
    padding: 4px 3px;

}

.exalib input.libaryfront_search[type="text"] {
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset;
    border-radius: 7px;
    border: 1px solid #CCCCCC;
    font-size: 16px;
    padding: 6px 3px;

}

input.libaryfront_searchsub[type="submit"] {
    box-shadow: 0 5px 5px #005BC1 inset, 0 1px 1px rgba(0, 0, 0, 0.05);
    color: #FFFFFF;
    cursor: pointer;
    font-size: 16px;
    padding: 5px 15px;
    margin-left: 5px;
}



.libary_cat_select select {
   background: transparent;
   width: 228px;
   padding: 3px;
   font-size: 14px;
   line-height: 1;
   border: 0;
   border-radius: 0;
   height: 24px;
   -webkit-appearance: none;
}

.libary_cat_select {
   width: 200px;
   height: 24px;
   overflow: hidden;
   background: url(http://gtn02.gtn-solutions.com/layouts/exalib/pix/select_arrow.png) no-repeat right #fff;
   border: 1px solid #ccc;
   text-align: center;
   border-radius: 7px;
  
   }

.library_top_filter {
    text-align: right;
    color: #666;
    padding-top: 5px;
}

.library_top_filter a {
    color: #666;
}

.library-item div {
    color: #666;
}
.libary_top_search {
    background: -moz-linear-gradient(center top , #FAFAFA 0%, #F5F5F5 100%) repeat scroll 0 0 rgba(0, 0, 0, 0);
    border: 1px solid #DDDDDD;
    border-radius: 5px;
    box-shadow: 0 1px 0 #FFFFFF inset;
    padding: 15px 15px;
    margin-bottom: 10px;
}

.libary_top_filter {
    background: -moz-linear-gradient(center top , #FAFAFA 0%, #F5F5F5 100%) repeat scroll 0 0 rgba(0, 0, 0, 0);
    border: 1px solid #DDDDDD;
    border-radius: 5px;
    box-shadow: 0 1px 0 #FFFFFF inset;
    padding: 15px 15px;
    margin-bottom: 30px;
}

.libary_top_search p, .libary_top_search table, .libary_top_filter table {
    margin-bottom: 0;
}

.libary_search_input {
    width: 90% !important;

}

.paging{
    text-align: center;
}

.libary_top_filter h4 {
    
}

.paging_bottom {
    margin-top: 25px;
}

.paging_top {
    margin-bottom: 25px;
}

</style>
<div class="exalib">


        <h3>Welcome to the <?php echo get_string('heading', 'block_exalib');  ?>!</h3>
        
        <div class="libary_top_search">
        <form method="get" action="adv_search.php">
            <img src="pix/libsearch.png" alt="search" style="vertical-align: middle;padding-right: 10px;">
                <input name="q" type="text" class="libary_search_input" style="vertical-align: middle;" value="<?php p($q) ?>" />
                <table style="width: 100%;">
                <tbody><tr colspan="2&gt;
                    &lt;td&gt;
                        &lt;input name=" category_id"="" value="12" type="hidden">
                    <td>
                </td></tr>
                <tr>
                    <td colspan="2">
                        <label><input type="checkbox" id="search-all-categories" value="Abstracts" <?php 
if (count($categoryids) == 0) {
    echo 'checked="checked"';
}?>>All</label>&nbsp;&nbsp;&nbsp;&nbsp;
                        <label><input type="checkbox" name="category_ids[]" value="11" <?php
if (in_array(11, $categoryids)) {
    echo 'checked="checked"';
} ?> />Abstracts</label>&nbsp;&nbsp;&nbsp;&nbsp;
                        <label><input type="checkbox" name="category_ids[]" value="12" <?php
if (in_array(12, $categoryids)) {
    echo 'checked="checked"';
}?> />Documents</label>&nbsp;&nbsp;&nbsp;&nbsp;
                        <label><input type="checkbox" name="category_ids[]" value="13" <?php
if (in_array(13, $categoryids)) {
    echo 'checked="checked"';
}?> />Images</label>&nbsp;&nbsp;&nbsp;&nbsp;
                        <label><input type="checkbox" name="category_ids[]" value="14" <?php
if (in_array(14, $categoryids)) {
    echo 'checked="checked"';
}?> />Podcasts</label>&nbsp;&nbsp;&nbsp;&nbsp;
                        <label><input type="checkbox" name="category_ids[]" value="15" <?php
if (in_array(15, $categoryids)) {
    echo 'checked="checked"';
}?> />Webcasts</label>&nbsp;&nbsp;&nbsp;&nbsp;
                        <div style="clear:both;"></div>
                    </td>
                </tr>
                
                <tr>
                    <td style="">
                    search by:&nbsp;
                    
                            
                              <select name="search_by">
                                  <option value="all">all</option>
                                  <option value="title" <?php {
if ($searchby == 'title') {
    echo 'selected="selected"';
}?>>Title</option>
                                  <option value="author" <?php {
if ($searchby == 'author') {
    echo 'selected="selected"';
}?>>Author</option>
                                  <option value="source" <?php {
if ($searchby == 'source') {
    echo 'selected="selected"';
}?>>Source</option>
                              </select>
            
                    </td>
                    <td style="text-align:right;vertical-align: bottom;">
                        <input value="Search" type="submit">
                    </td>
                </tr>
            </tbody></table>
                
                
        </form>
        
        </div>
        
        <?php if (count($categoryids) >= 1 && $subfiltercategories): ?>
        <div class="libary_top_filter">
        <form method="get" action="adv_search.php">
            <input name="q" type="hidden" value="<?php p($q) ?>" />
            <input name="search_by" type="hidden" value="<?php p($searchby) ?>" />
            <?php
    foreach ($categoryids as $id) {
        echo '<input name="category_ids[]" type="hidden" value="'.$id.'" />';
    }
            ?>
            <table style="width: 100%;">
                <tbody>
                <tr>
                    <td style="width:40px;">Filter: </td>
                    <td>
                    
                            
                            <select name="sub_filter_id">
                                <?php
    foreach ($subfiltercategories as $subfiltercategory) {
        echo '<option value="'.$subfiltercategory->id.'"';
        if ($subfilterid == $subfiltercategory->id) {
            echo ' selected="selected"';
        };
        echo '>';
        echo $subfiltercategory->name.' ('.$subfiltercategory->cnt.')</option>';
    }
                                ?>
                              </select>
                    </td>
                    <td style="text-align:right;vertical-align: bottom;">
                        <input value="Apply Filter" type="submit">
                    </td>
                </tr>
            </tbody></table>
        </form>
        </div>
        <?php 
endif;
        ?>



<div class="library_result_main">

<?php
if ($items !== null) {
    if (!$items) {
        echo 'No Items found';
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
/*  else {
    ?>
    <div style="text-align: center; padding: 200px 60px 0 0;" class="libary_nores">
        Not found
    </div>
    <?php
}
*/
?>
</div>
</div>
<?php
echo $OUTPUT->footer();
