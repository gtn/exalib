<?php

require 'inc.php';

function enroll_in_library() {
	global $DB, $USER;

	$course = $DB->get_record('course', array('id' => EXALIB_COURSE_ID));
	if (!$course) die('course not found');

	if ($USER->id == 0 || isguestuser()) {
		redirect('/enrol/index.php?id='.$course->id);
		exit;
	}

	if (!is_enrolled(context_course::instance($course->id))) {
		// enroll
		echo 'e';
		enrol_user($course, $USER->id);
	}
}

echo enroll_in_library();

echo 'xx';
exit;
