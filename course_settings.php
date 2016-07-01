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

block_exalib_init_page();
block_exalib_require_cap(\block_exalib\CAP_COURSE_SETTINGS);

$action = optional_param('action', "", PARAM_ALPHAEXT);

$output = block_exalib_get_renderer();

$out = '';

if ($action == 'save_coursesettings') {
	$settings = block_exalib_course_settings::get_course();
	$settings->allow_comments = optional_param('allow_comments', 0, PARAM_BOOL);
	$settings->use_review = optional_param('use_review', 0, PARAM_BOOL);
	$settings->use_terms_of_service = optional_param('use_terms_of_service', 0, PARAM_BOOL);
	$settings->alternative_wording = optional_param('alternative_wording', 0, PARAM_BOOL);
	$settings->root_category_id = optional_param('root_category_id', 0, PARAM_INT);
	$settings->save();

	$out .= '<h3>'.\block_exalib\trans('de:Änderungen wurden gespeichert!').'</h3>';
}

$mgr = new block_exalib_category_manager(true);

$categories = $mgr->getChildren(0);
$categories = array_map(function($c) {
	return $c->name;
}, $categories);

echo $output->header('tab_course_settings');
echo $out;
echo html_writer::tag('form',
	html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'action', 'value' => 'save_coursesettings')).

	html_writer::checkbox('allow_comments', 1, block_exalib_course_settings::allow_comments(), \block_exalib\trans('de:Kommentarfunktion verwenden')).'<br />'.
	html_writer::checkbox('use_review', 1, block_exalib_course_settings::use_review(), \block_exalib\trans('de:Reviewing verwenden')).'<br />'.
	html_writer::checkbox('use_terms_of_service', 1, block_exalib_course_settings::use_terms_of_service(), \block_exalib\trans('de:Nutzungsbedingungen vor dem Erstellen von Inhalten/Fällen bestätigen')).'<br />'.
	html_writer::checkbox('alternative_wording', 1, block_exalib_course_settings::alternative_wording(), \block_exalib\trans('de:Wording für Fallarchiv verwenden')).'<br />'.

	html_writer::select($categories, 'root_category_id', block_exalib_course_settings::root_category_id(), \block_exalib\trans('de:-- keine --')).' '.\block_exalib\trans('de:Root Kategorie festlegen').'<br />'.

	html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('save', 'admin')))
	, ['action' => $_SERVER['REQUEST_URI'], 'method' => 'post']);


/* END CONTENT REGION */
echo $output->footer();
