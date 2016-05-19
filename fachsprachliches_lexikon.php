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

block_exalib_require_global_cap(\block_exalib\CAP_USE);

$output = block_exalib_get_renderer();
$output->set_tabs('tab_fachsprachliches_lexikon');

$items = block_exalib_get_fachsprachliches_lexikon_items();

echo $output->header();

if (!$items) {
	echo get_string('noitemsfound', 'block_exalib');
} else {
	$lastLetter = '';

	?>
	<style>
		table.fachsprachliches_lexikon td {
			border: 1px solid #aaa;
			padding: 10px;
			vertical-align: top;
		}
		table.fachsprachliches_lexikon .spacer td {
			border: none;
		}
	</style>
	<?php
	echo '<table class="fachsprachliches_lexikon">';
	foreach ($items as $item) {
		$letter = substr($item->{'fach'}, 0, 1);
		if ($letter != $lastLetter) {
			if ($lastLetter) {
				echo '<tr class="spacer"><td>&nbsp;</td></tr>';
			}
			$lastLetter = $letter;
			echo '<tr style="background: lightblue">';
			echo '<td colspan="3">'.$letter.'</td>';
			echo '</tr>';

			echo '<tr style="font-weight: bold; background: #ddd">';
			echo '<td>'.'Fachsprache'.'</td>';
			echo '<td>'.'Bedeutung'.'</td>';
			echo '<td>'.'Theorien/Experten/Quellen'.'</td>';
			echo '</tr>';
		}

		echo '<tr>';
		echo '<td>'.$item->{'fach'}.'</td>';
		echo '<td>'.$item->{'bede'}.'</td>';
		echo '<td>'.$item->{'theo'}.'</td>';
		echo '</tr>';
	}
	echo '</table>';
}

echo $output->footer();
