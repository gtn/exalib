<?php

require_once 'lib.php';
require_login(EXALIB_COURSE_ID);

$file = $_REQUEST['file'];
if (!preg_match('!^library/Podcasts/(.*)!', $file, $matches)) die ('not library file');

if ($file[0] == '/') die('not allowed 1');
if (strpos($file, '..') !== false) die('not allowed 2');

$filepath = dirname(__FILE__).'/../../'.$file;
if (!file_exists($filepath)) die('not found');

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename='.basename($file));
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filepath));
ob_clean();
flush();
readfile($filepath);
