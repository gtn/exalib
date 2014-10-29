<?php

require 'inc.php';

$itemid = required_param('itemid', PARAM_INT);
if (!$item = $DB->get_record("exalib_item", array('id'=>$itemid))) {
	print_error("item not found");
}


$PAGE->set_url('/blocks/exalib/detail.php?itemid='.$itemid, array());
$PAGE->set_course($SITE);

block_exalib_require_open();

$PAGE->set_url('/blocks/exalib');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('login');

$PAGE->set_heading(get_string('heading', 'block_exalib'));

echo $OUTPUT->header();

// <script type="text/javascript">jwplayer.key="YOUR_JWPLAYER_LICENSE_KEY";</script>

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

<div class="exalib">

<h1 class="libary_head"><?php echo get_string('heading', 'block_exalib');  ?></h1>


<?php

echo '<h2 class="head">'.$item->name.'</h2>';
if ($item->source) echo '<div>Source: '.$item->source.'</div>';
if ($item->authors) echo '<div>Authors: '.$item->authors.'</div>';

if ($item->content)
	echo $item->content;
elseif ($item->link) {

	block_exalib_print_jwplayer(array(
		'file'	=> $item->link,
		'width'	=> "960",
		'height' => "540",
	));

} else {
	if ($item->background)
		echo '<h3>Background</h3>'.$item->background;
	if ($item->methods)
		echo '<h3>Methods</h3>'.$item->methods;
	if ($item->results)
		echo '<h3>Results</h3>'.$item->results;
	if ($item->conclusion)
		echo '<h3>Conclusion</h3>'.$item->conclusion;
}
?>
<br /><br />
<a class="exalib-blue-cat-lib" href="javascript:history.back();">back</a>
</div>
<?php
echo $OUTPUT->footer();
