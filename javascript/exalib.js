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

$(function () {
	if ($('#exalib-categories').length) {
		var easytree = $('#exalib-categories').easytree();
	}
});

$(document).on('click', '.library-item', function(event){
	if ($(event.target).closest('a, input').length) {
		// a link or button inside was pressed
		return;
	}

	// click the first link (header link)
	$(this).find('> a')[0].click();
	return;
});