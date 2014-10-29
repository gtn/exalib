<?php

require 'inc.php';

block_exalib_require_view();

if ($itemid = optional_param('itemid', 0, PARAM_INT)) {
	if (!$item = $DB->get_record("exalib_item", array('id'=>$itemid))) {
		print_error("item not found");
	}
	
	$video_url = $item->link;
} elseif ($video_url = optional_param('video_url', '', PARAM_TEXT)) {
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

// <script type="text/javascript">jwplayer.key="YOUR_JWPLAYER_LICENSE_KEY";</script>

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
		'file'	=> $video_url,
		'width'	=> $width,
		'height' => $height,
		'autostart' => optional_param('autostart', 0, PARAM_INT)
	));

echo $OUTPUT->footer();
