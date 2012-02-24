(function($) {
JLSinlineEditURL = {

	init : function(){	
  		
		$('.jlsinlineshow').click(function() {
			myReset();
			var id = $(this).closest('tr').attr("id").match(/[\d]+$/);
			$('#edit-'+id+', #url-'+id ).toggle();
		});
		
		$('.jlsinline').click(function() {
			var id = $(this).closest('tr').attr("id").match(/[\d]+$/);
			$('#edit-'+id+', #url-'+id ).toggle();
		});
		
		var myReset = function() {
			$('tr[id^="edit"]').hide();
			$('tr[id^="url"]').show();
     	};
	}
};

$(document).ready(function(){JLSinlineEditURL.init();});
})(jQuery);
