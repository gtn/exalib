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

require __DIR__.'/inc.php';

$itemid = required_param('itemid', PARAM_INT);
if (!$item = $DB->get_record('block_exalib_item', array('id' => $itemid))) {
    print_error(get_string('itemnotfound', 'block_exalib'));
}

$type = optional_param('type', '', PARAM_TEXT);
$back = optional_param('back', '', PARAM_LOCALURL);
if ($back) {
    $back = (new moodle_url($back))->out(false);
} else {
    $back = 'javascript:history.back();';
}

block_exalib_require_view_item($item);

$fs = get_file_storage();
$files = $fs->get_area_files(context_system::instance()->id,
    'block_exalib',
    'item_file',
    $item->id,
    'itemid',
    '',
    false);

$output = block_exalib_get_renderer();

if ($type == 'mine') {
    $output->set_tabs('tab_mine');
} elseif ($type == 'admin') {
    $output->set_tabs('tab_managecontent');
}

echo $output->header();

/* <script type="text/javascript">jwplayer.key="YOUR_JWPLAYER_LICENSE_KEY";</script> */

/*
?>
<script type="text/javascript" src="jwplayer/jwplayer.js"></script>
<style>


h1.libary_head {
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
</style>
<?
*/

echo '<h2 class="head">'.$item->name.'</h2>';
if ($item->source) {
    echo '<div>Source: '.$item->source.'</div>';
}
if ($item->authors) {
    echo '<div>Authors: '.$item->authors.'</div>';
}

if ($item->content) {
    echo $item->content;
/*
} else if ($item->link) {

    block_exalib_print_jwplayer(array(
        'file'    => $item->link,
        'width'    => "960",
        'height' => "540",
    ));
*/
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

?>
<br /><br />
<a class="exalib-blue-cat-lib" href="<?php echo $back ?>"><?php echo get_string('back', 'block_exalib')?></a>
</div>
<?php

echo $output->footer();
