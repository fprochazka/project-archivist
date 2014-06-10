(function ($) {

	$.nette.ext('bs-modal', {
		init: function () {
			var self = this;

			this.ext('snippets', true).after($.proxy(function ($el) {
				if (!$el.is('.modal')) {
					return;
				}

				self.open($el);
			}, this));

			$('.modal[id^="snippet-"]').each(function () {
				self.open($(this));
			});
		}
	}, {
		open: function (el) {
			var content = el.find('.modal-content');
			if (!content.length) {
				return; // ignore empty modal
			}

			el.modal({});
		}
	});

})(jQuery);
