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

require_login();
block_exalib_require_use();

$forward = required_param('forward', PARAM_LOCALURL);
$accept = optional_param('accept', false, PARAM_BOOL);

if ($accept) {
	set_user_preference('block_exalib_terms_of_service', true);
	redirect(new moodle_url($forward));
	exit;
}

$PAGE->set_url('/blocks/exalib/terms_of_service.php', ['forward' => $forward]);
$PAGE->set_course($SITE);
$PAGE->set_pagelayout('login');

$output = block_exalib_get_renderer();
$output->init_js_css();

echo $OUTPUT->header();

echo \block_exalib\get_string('terms_of_use');

echo '<div style="padding: 40px; text-align: center;">';

echo $output->link_button(new moodle_url($PAGE->url, ['accept' => true]), \block_exalib\trans('de:Einverstanden'));
echo $output->back_button(new moodle_url('/'));
echo '</div>';

echo $OUTPUT->footer();
