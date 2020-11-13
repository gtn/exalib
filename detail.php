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

use block_exalib\globals as g;

$output = block_exalib_get_renderer();
$itemid = required_param('itemid', PARAM_INT);
//if($USER->id) {echo $USER->id;echo "ja";die;} else {echo "nein";die;}
if (g::$USER->id==1) {
	$allowdetail=0;
	if ($_SERVER["REMOTE_ADDR"]=="217.149.166.65") $allowdetail=2;
	if ($_SERVER["REMOTE_ADDR"]=="185.85.64.18") $allowdetail=2;
	if ($_SERVER["REMOTE_ADDR"]=="185.85.64.19") $allowdetail=2;
	if ($_SERVER["REMOTE_ADDR"]=="185.85.64.20") $allowdetail=2;
}else{
	$allowdetail=1;
}

if ($allowdetail==0) {
	  define('NO_MOODLE_COOKIES', false);
	  setcookie("exalib_lastitem", $itemid, time() + (60 * 3), "/", 'e-learning.ecco-ibd.eu');
    echo $output->header();
    echo "Guests cannot access this course. Please log in via the ECCO Portal...<br><br>";
    echo "<button onclick=\"window.location.href='https://cm.ecco-ibd.eu/cmportal/OAuthRedirect/EC20/config/normal'\">Continue </button> <br>";
    //echo "(If redirect fails, press button in order to proceed to login page.)";
    echo "<!--script> window.location.href='https://e-learning.ecco-ibd.eu/login/index.php'</script-->";
   
    
    //$cookiesecure = is_moodle_cookie_secure();
    //setcookie("exalib_lastitem", $itemid, time() - HOURSECS, $CFG->sessioncookiepath, $CFG->sessioncookiedomain, $cookiesecure, $CFG->cookiehttponly);
    //print_r($_COOKIE);
    //echo "ca4";
    echo $output->footer();


    //$linkurl = new moodle_url('detail.php', ['courseid' => g::$COURSE->id, 'catt'=>$catt, 'itemid' => $item->id, 'back' => g::$PAGE->url->out_as_local_url()] + ($type != 'public' ? ['type' => $type] : []));
 //redirect('https://e-learning.ecco-ibd.eu/login/index.php') ;
// redirect('https://gtntest-e-learning.ecco-ibd.eu/login/index.php') ;
 //block_exalib_init_page();
 //echo "sie müssen angemeldet sein";
 //echo "nto logein";die;
}
else{
	if ($allowdetail==2) {}
	else require_login();
	
    block_exalib_init_page();

    
    $catt = optional_param('catt',0, PARAM_INT);
    if (!$item = $DB->get_record('block_exalib_item', array('id' => $itemid))) {
        print_error(block_exalib_get_string('itemnotfound'));
    }

    $type = optional_param('type', '', PARAM_TEXT);
    $back = optional_param('back', '', PARAM_LOCALURL);
    if ($back) {
        $back = (new moodle_url($back))->out(false);
    } else {
        $back = 'javascript:history.back();';
    }
		if ($allowdetail==2) {}
    else block_exalib_require_view_item($item);


    require_once $CFG->libdir.'/formslib.php';

    class block_exalib_comment_form extends moodleform {

        function definition() {
            $mform = &$this->_form;

            $this->_form->_attributes['action'] = $_SERVER['REQUEST_URI'];

            $mform->addElement('hidden', 'action');
            $mform->setType('action', PARAM_TEXT);
            $mform->setDefault('action', 'comment_add');

            // $mform->addElement('header', 'comment', get_string("addcomment", "block_exaport"));

            $mform->addElement('editor', 'text', block_exalib_trans("de:Kommentar"), null, array('rows' => 10));
            $mform->setType('text', PARAM_TEXT);
            //$mform->addRule('text', block_exalib_get_string('requiredelement', 'form'), 'required');

            if (block_exalib_course_settings::allow_rating() && $this->_customdata['item']->created_by != g::$USER->id) {
                $radioarray = array();
                $radioarray[] = $mform->createElement('radio', 'rating', '', block_exalib_trans(['de:keine', 'en:none']), 0);
                $radioarray[] = $mform->createElement('radio', 'rating', '', 1, 1);
                $radioarray[] = $mform->createElement('radio', 'rating', '', 2, 2);
                $radioarray[] = $mform->createElement('radio', 'rating', '', 3, 3);
                $radioarray[] = $mform->createElement('radio', 'rating', '', 4, 4);
                $radioarray[] = $mform->createElement('radio', 'rating', '', 5, 5);
                $mform->addGroup($radioarray, 'ratingarr', block_exalib_trans(["de:Bewertung", 'en:Rating']), array(' '), false);
            }

            if (block_exalib_course_settings::use_review() && in_array(g::$USER->id, [$this->_customdata['item']->created_by, $this->_customdata['item']->reviewer_id])) {
                $mform->addElement('advcheckbox', 'isprivate', block_exalib_trans('de:Privater Kommentar (nur für Ersteller und Reviewer'));
            }

            $this->add_action_buttons(false, block_exalib_get_string('add'));

        }

    }

    $commentsform = new block_exalib_comment_form(null, ['item' => $item]);

    if (optional_param('action', '', PARAM_TEXT) == 'comment_add' && $allowdetail!=2) {
        require_sesskey();

        if ($formdata = $commentsform->get_data()) {
            $post = new stdClass;
            $post->itemid = $item->id;
            $post->userid = $USER->id;
            $post->time_created = $post->time_modified = time();
            $post->text = $formdata->text['text'];
            if (isset($formdata->rating)) {
                $post->rating = $formdata->rating;

                // delete older ratings for same items
                $DB->execute("
				UPDATE {block_exalib_item_comments}
				SET rating=0
				WHERE itemid=? AND userid=?
			", [$item->id, $USER->id]);
            }

            if (isset($formdata->isprivate)) {
                $post->isprivate = $formdata->isprivate;
            }

            $id = $DB->insert_record('block_exalib_item_comments', $post);

            redirect($_SERVER['REQUEST_URI']);
        }
    }
    if (optional_param('action', '', PARAM_TEXT) == 'comment_delete') {
        require_sesskey();
        $commentid = required_param('commentid', PARAM_INT);

        $conditions = array("id" => $commentid, "userid" => $USER->id, "itemid" => $itemid);
        $DB->delete_records("block_exalib_item_comments", $conditions);

        redirect($back);
    }


    $fs = get_file_storage();
    $files = $fs->get_area_files(context_system::instance()->id,
        'block_exalib',
        'item_file',
        $item->id,
        'itemid',
        '',
        false);

    if ($type == 'mine') {
        $output->set_tabs('tab_mine');
    } elseif ($type == 'admin') {
        $output->set_tabs('tab_manage_content');
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

    if (preg_match('!rtmp://!', $item->link) || preg_match('!rtmps://!', $item->link) || preg_match('!https://e-learning.ecco-ibd.eu/ECCO2019/Webcasts!', $item->link) || preg_match('!https://video.ecco-ibd.eu/ECCO2020!', $item->link)) {
        $video_url = $item->link;
        $item->link = '';
    } else {
        $video_url = null;
    }



    echo '<h2 class="head">'.$item->name;
    if ($item->year) echo ' ('.$item->year.')';
    echo '</h2>';
    echo '<span class="library-item-icon"><img src="pix/contenttypicon'.$item->maincategory.'.png" class="searchLibCheckIcon" /></span>';
    echo '<table>';
    if ($item->source) {
        echo '<tr><td>Source:</td><td>'.$item->source.'</td></tr>';
    }

    $authors = null;
    if ($item->authors) {
        $authors = $item->authors;
    } elseif ($tmpuser = $DB->get_record('user', ['id' => $item->created_by])) {
        $authors = fullname($tmpuser);
    }
    if ($authors) {
        echo '<tr><td>'.block_exalib_get_string('author').':</td><td>'.$authors;
        if ($item->affiliations) {
            echo '<i>'.$item->affiliations.'</i>';
        }

        echo '</td></tr>';
    }
    /*
    if ($item->time_created) {
        echo '<tr><td>'.block_exalib_get_string('created').':</td><td>'.
            userdate($item->time_created);

        //if ($item->created_by && $tmpuser = $DB->get_record('user', array('id' => $item->created_by))) {
        //	echo ' '.block_exalib_get_string('by_person', null, fullname($tmpuser));
        //}

        echo '</td></tr>';
    }
    if ($item->time_modified > $item->time_created) {
        echo '<tr><td>'.block_exalib_trans(['en:Last Modified', 'de:Zuletzt geändert']).':</td><td>'.
            userdate($item->time_modified);
        if ($item->modified_by && $tmpuser = $DB->get_record('user', array('id' => $item->modified_by))) {
            echo ' '.block_exalib_get_string('by_person', null, fullname($tmpuser));
        }
        echo '</td></tr>';
    }*/

    if ($item->real_fiktiv) {
        echo '<tr><td>'.block_exalib_trans('de:Typ').':</td><td>';
        echo $item->real_fiktiv;
    }

    if ($files) {
        echo '<tr>';
        $onlypdf=true;$i=1;
        foreach ($files as $file) {
            if ($file->mimetype=="application/pdf"){
                //dominik: pdf button hier machen
                echo '<td><a href="'.block_exalib_get_url_for_file($file).'" target="_blank" class="exalib-blue-cat-lib">'.
                    block_exalib_get_renderer()->pix_icon(file_file_icon($file), get_mimetype_description($file)).
                    ' '.$file->get_filename().'</a>&nbsp;&nbsp;&nbsp;';
            }else{
                if ($i==1) '<td>'.block_exalib_get_string('files').':</td><td>';$i++;

                //	print_r($file);
                $fileParams = substr(block_exalib_get_url_for_file($file), strrpos(block_exalib_get_url_for_file($file), "pluginfile.php", 0));
                $fileParams = str_replace("pluginfile.php", '', $fileParams);
                echo '<div class="text-center exalibPdfDetail"><a href="'. $CFG->wwwroot . '/blocks/exalib/pdfjs/ModifViewer/web/viewer.html?file='
                    .$fileParams.'" target="_blank" class="exalib-blue-cat-lib">'.
                    block_exalib_get_renderer()->pix_icon(file_file_icon($file), get_mimetype_description($file)).
                    'View Presentation</a></div>&nbsp;&nbsp;&nbsp;';
                /* echo '<div class="text-center exalibPdfDetail"><a href="'
                     .block_exalib_get_url_for_file($file).'" target="_blank" class="exalib-blue-cat-lib">'.
                     block_exalib_get_renderer()->pix_icon(file_file_icon($file), get_mimetype_description($file)).
                     ' '.$file->get_filename().'</a></div>&nbsp;&nbsp;&nbsp;';*/
            }
        }
        echo '</td></tr>';
    }

    if ($item->link) {
        $linkurl = block_exalib_format_url($item->link);
        $linktext = trim($item->link_titel) ? $item->link_titel : $item->link;

        echo '<tr><td>'.block_exalib_get_string('link').':</td><td>';
        echo '<a class="head" href="'.$linkurl.'" target="_blank">'.$linktext.'</a>';
    }

    echo '</table>';


    if ($item->content) {
        echo '<h2 class="head">'.block_exalib_get_string('content').'</h2>';
        echo format_text($item->content);
    }

// ecco
    if ($item->abstract) {
        if ($item->maincategory==51302) {
            echo '<h3>Keywords</h3>';
        }else{
            echo '<h3>Abstract</h3>';
        }
        echo format_text($item->abstract).'</div>';
    }
    if ($item->background) {
        echo '<h3>Background</h3>'.format_text($item->background);
    }
    if ($item->methods) {
        echo '<h3>Methods</h3><p>'.format_text($item->methods).'</p>';
    }
    if ($item->results) {
        echo '<h3>Results</h3>'.format_text($item->results).'</p>';
    }
    if ($item->conclusion) {
        echo '<h3>Conclusion</h3>'.format_text($item->conclusion);
    }

    if ($video_url) {
        block_exalib_print_jwplayer(array(
            'file'    => $video_url,
            'width'    => "100%",
            'height' => "100%",
        ));
    }

    if (@block_exalib_course_settings::allow_comments()) {
        echo '<h2 class="head">'.block_exalib_trans('de:Kommentare').'</h2>';


        $comments = $DB->get_records("block_exalib_item_comments", ["itemid" => $item->id], 'time_created ASC');
        foreach ($comments as $comment) {
            if ($comment->isprivate) {
                if (!in_array(g::$USER->id, [$item->created_by, $item->reviewer_id])) {
                    continue;
                }
            }


            $conditions = array("id" => $comment->userid);
            $user = $DB->get_record('user', $conditions);

            echo '<table cellspacing="0" class="forumpost blogpost blog" width="100%">';

            echo '<tr class="header"><td class="picture left">';
            echo $OUTPUT->user_picture($user);
            echo '</td>';

            echo '<td class="topic starter"><div class="author">';

            if ($comment->isprivate) {
                echo '['.block_exalib_trans('de:Privat').'] ';
            }

            $fullname = fullname($user, $comment->userid);
            $by = new stdClass();
            $by->name = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.
                $user->id.'&amp;course='.$COURSE->id.'">'.$fullname.'</a>';
            $by->date = userdate($comment->time_modified);
            print_string('bynameondate', 'forum', $by);
            if (block_exalib_course_settings::allow_rating() && $comment->rating) {
                echo ' - '.block_exalib_trans('de:Bewertung').': ';
                echo '<span title="'.block_exalib_trans('de:{$a->rating} von {$a->max} Sternen', ['rating' => $comment->rating, 'max' => 5]).'">';
                for ($i = 1; $i <= 5; $i++) {
                    echo ($comment->rating >= $i) ? '&#9733;' : '&#9734;';
                }
                echo '</span>';
            }

            if ($comment->userid == $USER->id) {
                echo ' '.$output->link_button(new moodle_url($PAGE->url, [
                        'commentid' => $comment->id,
                        'action' => 'comment_delete',
                        'sesskey' => sesskey(),
                        'back' => $PAGE->url->out_as_local_url(false),
                    ]), get_string('delete'), ['exa-confirm' => block_exalib_get_string('comment_delete_confirmation')]);
            }

            echo '</div></td></tr>';

            echo '<tr><td class="left side">';

            echo '</td><td class="content">'."\n";

            echo format_text($comment->text);

            echo '</td></tr></table>'."\n\n";
        }

        if (($item->allow_comments == '') // all
            || (($item->allow_comments == 'teachers_and_reviewers') && block_exalib_is_reviewer())
            || (($item->allow_comments == 'reviewers') && block_exalib_is_reviewer())
        ) {
            $commentsform->display();
        }
    }

    ?>
    <br/><br/>
    <a class="exalib-blue-cat-lib" href="<?php echo $back ?>"><?php echo block_exalib_get_string('back') ?></a>
    </div>
    <?php

    echo $output->footer();

	
}


