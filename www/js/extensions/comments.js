(function ($, undefined) {

	$.nette.ext({
		init: function () {
			this.ext('snippets').after(function ($el) {
				$el.find('.ajax-comments-input').each(function () {
					$(this).focus();
				});
			});
		}
	});

})(jQuery);
