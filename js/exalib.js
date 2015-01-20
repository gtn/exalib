window.jQueryExalib = jQuery.noConflict(true);

(function($){
	$(function(){
		if ($('#exalib-categories').length) {
			var easytree = $('#exalib-categories').easytree();
		}
	});
})(jQueryExalib);
