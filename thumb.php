<?php
/**
 * Pro Framework
 *
 * @category   Pro
 * @author     Pro-Web (http://pro-web.at)
 * @copyright  Copyright (c) 2008 pro-web.at (http://pro-web.at)
 * @version    $Id: thumb.php 202 2009-03-19 09:26:12Z danielpr $
 */

require dirname(__FILE__).'/inc.php';

// falls thumb.php doch in der baseUrl vorkommt, löschen
Pro::getRequest()->setBaseUrl(preg_replace("!^/thumb(\.php)?!", "", Pro::getRequest()->getBaseUrl()));

$src = isset($_GET['src']) ? $_GET['src'] : '';
$w = isset($_GET['w']) ? $_GET['w'] : '';
$h = isset($_GET['h']) ? $_GET['h'] : '';

if (strpos($src, '..') !== false) {
	// Hack attack, grrr...
	header('HTTP/1.1 404 Not Found');
	echo 'File not found #1';
	exit;
}

if (($file = Pro::basePath($src)) && is_file($file)) {
	// ok
} elseif (($file = Pro::uploadPath($src)) && is_file($file)) {
	// ok
} elseif (($file = $_SERVER['DOCUMENT_ROOT'].'/'.$src) && is_file($file)) {
	// ok
} else {
	echo 'File not found #2';
	exit;
}

if (!$size = @getImageSize($file)) {
	header('HTTP/1.1 404 Not Found');
	echo 'Not an Image';
	exit;
}

$file_modified = filemtime($file);


// init cache
$cache = Zend_Cache::factory('Output', 'File', array(
	'lifetime' => 60*60*24*30,
));


$cacheId = array(
	'src' => $file,
	'w' => (int)$w,
	'h' => (int)$h,
	'filemtime' => $file_modified
);
$strCacheId = 'thumbs_'.md5(print_r($cacheId, true));



header('Content-type: image/jpeg');

// modified?
$user_modified = @strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
if ($user_modified && $file_modified && ($file_modified === $user_modified) && $cache->test($strCacheId)) {
	if (php_sapi_name()=='cgi')
		header('Status: 304 Not Modified');
	else
		header('HTTP/1.1 304 Not Modified');
	exit;
}

header("Last-Modified: ".gmdate("D, d M Y H:i:s", $file_modified)." GMT");
ob_start('ob_gzhandler');

// we pass a unique identifier to the start() method
if(!$cache->start($strCacheId)) {
	$thumb = new Pro_Thumbnail($file);
	$thumb->resize($w, $h);
	$thumb->show();

    $cache->end();
}
