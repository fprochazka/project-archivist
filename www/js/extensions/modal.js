(function ($) {

	$.nette.ext('bs-modal', {
		init: function () {
			this.ext('snippets', true).after($.proxy(function ($el) {
				if (!$el.is('.modal')) {
					return;
				}

				$el.modal({});

			}, this));

			$('.modal[id^="snippet-"]').each(function () {
				var content = $(this).find('.modal-content');
				if (!content.length) {
					return; // ignore empty modal
				}

				$(this).modal({});
			});
		}
	});

})(jQuery);
