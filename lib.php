<?php

define('FILESYSTEMREPO_ID', 9);

function block_exalib_new_moodle_url() {
	global $CFG;
	
	$moodle_path = preg_replace('!^[^/]+//[^/]+!', '', $CFG->wwwroot);
	
	return new moodle_url(str_replace($moodle_path, '', $_SERVER['REQUEST_URI']));
}

function block_exalib_require_use() {
	if (!has_capability('block/exalib:use', context_system::instance())) {
		throw new require_login_exception('You are no allowed to view Library Content');
	}
}

function block_exalib_require_open() {
	block_exalib_require_use();
	if (!has_capability('block/exalib:use', context_system::instance())) {
		throw new require_login_exception('You are no allowed to view Library Content');
	}
}

function block_exalib_require_admin() {
	block_exalib_require_use();
	if (!has_capability('block/exalib:use', context_system::instance())) {
		throw new require_login_exception('You are no Exalib Admin');
	}
}

function print_items($ITEMS, $admin=false) {
	foreach ($ITEMS as $item) {
		
		$fs = get_file_storage();
		$areafiles = $fs->get_area_files(context_system::instance()->id, 'block_exalib', 'item_file', $item->id, 'itemid', '', false);
		$file = reset($areafiles);

		$downloadUrl = null;
		$podcast = false;
		$targetNewWindow = false;
		
		if ($file) {
			$url = "{$CFG->wwwroot}/pluginfile.php/{$file->get_contextid()}/block_exalib/item_file/".$file->get_itemid();
			$targetNewWindow = true;
		} elseif ($item->resource_id) {
			$url = '/mod/resource/view.php?id='.$item->resource_id;
		} elseif ($item->link) {
			if (strpos($item->link, 'rtmp://') === 0) {
				$url = 'detail.php?itemid='.$item->id;
			} elseif (strpos($item->link, 'filesystemrepo://') === 0) {
				$url = 'file.php?itemid='.$item->id;
			} else {
				$url = $item->link;
				if (preg_match('!library/Podcasts/(.*)!', $url, $matches)) {
					$downloadUrl = 'download.php?file='.urlencode($matches[0]);
					$podcast = true;
				} else {
					$targetNewWindow = true;
				}
			}
		} elseif ($item->content) {
			$url = 'detail.php?itemid='.$item->id;
		} else {
			$url = '';
		}

		$linkHeader = $admin || $downloadUrl;
		
		if ($linkHeader)
			echo '<div class="library-item">';
		else
			echo '<a class="library-item"'.($url?' href="'.$url.'"':'').($targetNewWindow?' target="_blank"':'').'>';

		if ($linkHeader)
			echo '<a class="head"'.($url?' href="'.$url.'"':'').($targetNewWindow?' target="_blank"':'').'>'.$item->name.'</a>';
		else
			echo '<div class="head">'.$item->name.'</div>';
		if ($item->source) echo '<div><span class="libary_author">Source:</span> '.$item->source.'</div>';
		if ($item->authors) echo '<div><span class="libary_author">Authors:</span> '.$item->authors.'</div>';
		
		echo '<span class="library-item-buttons">';
		if ($linkHeader) echo '<a href="'.$url.($targetNewWindow?'" target="_blank"':'').'"> '.($podcast?'listen':'show').'</a>';
		if ($admin) {
			echo ' | <a href="admin.php?show=edit&id='.$item->id.'">edit</a>';
			echo ' | <a href="admin.php?show=delete&id='.$item->id.'"">delete</a>';
		}
		if ($downloadUrl) {
			echo ' | <a href="'.$downloadUrl.'">download</a>';
		}
		echo '</span>';
		
		if ($linkHeader) {
			echo '</div>';
		} else {
			echo '</a>';
		}
	}
}

function block_exalib_print_jwplayer($options) {

	$options = array_merge(array(
		'flashplayer' => "jwplayer/player.swf",
		'primary' => "flash",
		'autostart' => false
	), $options);

	if (isset($options['file']) && preg_match('!^(rtmp://.*):(.*)$!i', $options['file'], $matches)) {
		$options = array_merge($options, array(
			'provider' => 'rtmp',
			'streamer' => $matches[1],
			'file' => str_replace('%20', ' ', $matches[2]),
		));
	}

	?>
	<div id='player_2834'></div>
	<script type='text/javascript'>
		var options = <?php echo json_encode($options); ?>;
		if (options.width == 'auto') options.width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
		if (options.height == 'auto') options.height = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
		
		var preview_start = false;
		if (!options.autostart) {
			preview_start = true;

			options.autostart = true;
			options.mute = true;
		}
		
		var p = jwplayer('player_2834').setup(options);
		
		if (preview_start) {
			p.onPlay(function(){
				if (preview_start) {
					this.pause();
					this.setMute(false);
					preview_start = false;
				}
			});
		}
	</script>
	<?php
}

function block_exalib_send_stored_file($itemid) {
	global $DB, $CFG;
	
	$item = $DB->get_record('exalib_item', array('id' => $itemid));
	if (!$item) {
		send_file_not_found();
		die('file not found');
	}
	
	if (preg_match('!^filesystemrepo://(.*)$!', $item->link, $matches)) {
		require_once($CFG->dirroot . '/repository/lib.php');

		$repo = repository::get_repository_by_id(FILESYSTEMREPO_ID, SYSCONTEXTID);
		$file = $repo->get_file(urldecode(trim($matches[1])));
		if (!$file || !file_exists($file['path'])) {
			send_file_not_found();
			return;
		}

		send_file($file['path'], basename($file['path']));
	} else {
		send_file_not_found();
		die('file not found #2');
	}
}

function block_exalib_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options) {
	block_exalib_require_open();

	$fs = get_file_storage();
	$areafiles = $fs->get_area_files(get_context_instance(CONTEXT_SYSTEM)->id, 'block_exalib', 'item_file', $args[0], 'itemid', '', false);
	$file = reset($areafiles);
	
	if (!$file) {
		send_file_not_found();
	}

	session_get_instance()->write_close(); // unlock session during fileserving
	send_stored_file($file, 0, 0, $forcedownload, array('preview' => $preview));
}

