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

require('inc.php');

block_exalib_require_view();

if ($itemid = optional_param('itemid', 0, PARAM_INT)) {
    if (!$item = $DB->get_record('block_exalib_item', array('id' => $itemid))) {
        print_error(get_string('itemnotfound', 'block_exalib'));
    }

    $videourl = $item->link;
} else if ($videourl = optional_param('video_url', '', PARAM_TEXT)) {
    $fcc = 1 // For code checker.
} else {
    print_error("no video url");
}

$PAGE->set_url('/', array());
$PAGE->set_course($SITE);

$PAGE->set_url('/blocks/exalib');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('embedded');

$PAGE->set_heading(get_string('heading', 'block_exalib'));

$width = optional_param('width', 'auto', PARAM_TEXT);
$height = optional_param('height', $width > 0 ? $width / 610 * 510 : 'auto', PARAM_TEXT);

/* <script type="text/javascript">jwplayer.key="YOUR_JWPLAYER_LICENSE_KEY";</script> */

echo $OUTPUT->header();

?>
<script type="text/javascript" src="jwplayer/jwplayer.js"></script>
<style>
html, body {
    overflow: hidden;
    width: 100%;
    height: 100%;
}
.pagelayout-embedded #content {
    padding: 0;
    margin: 0;
}
</style>

<?php
    block_exalib_print_jwplayer(array(
        'file'    => $videourl,
        'width'    => $width,
        'height' => $height,
        'autostart' => optional_param('autostart', 0, PARAM_INT)
    ));

echo $OUTPUT->footer();