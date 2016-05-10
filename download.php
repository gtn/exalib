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
block_exalib_require_open();

$file = $_REQUEST['file'];
if (!preg_match('!^library/Podcasts/(.*)!', $file, $matches)) {
    die ('not library file');
};

if ($file[0] == '/') {
    die('not allowed 1');
};
if (strpos($file, '..') !== false) {
    die('not allowed 2');
}

$filepath = dirname(__FILE__).'/../../'.$file;
if (!file_exists($filepath)) {
    die('not found');
};

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
