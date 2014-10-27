(function($){
	$(function(){
		// un/check all
		var $all_cat = $('#search-all-categories');
		var $category_ids = $('input[name="category_ids[]"]:visible'); // :visible, because other form has hidden category_ids[] fields!

		$all_cat.click(function(){
			var checked = $(this).prop('checked');
			$category_ids.each(function(){
				$(this).prop('checked', checked);
			});
		});

		$category_ids.click(function(){
			if ($category_ids.not(':checked').length == 0) {
				$all_cat.prop('checked', true);
			}
			else if ($category_ids.not(':checked').length > 0) {
				$all_cat.prop('checked', false);
			}
		});
		
		if ($all_cat.prop('checked')) {
			$all_cat.triggerHandler('click');
		} else {
			$($category_ids[0]).triggerHandler('click');
		}
	});
})(jQueryLibrary);
