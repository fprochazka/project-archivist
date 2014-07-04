(function ($, undefined) {

	$('body').on('keydown', 'textarea', function (e) {
		if ((e.keyCode == 10 || e.keyCode == 13) && e.ctrlKey) {
			$(e.target).closest('form').submit();
		}
	});

})(jQuery);
