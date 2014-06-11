(function($, undefined) {

/**
 * Depends on 'snippets' extension
 */
$.nette.ext('scrollTo', {
	init: function () {
		this.ext('snippets', true).before($.proxy(function ($el, settings) {
			if ($.inArray('scrollTo', settings.off) !== -1) {
				this.shouldTry = false;
			}

			if (this.shouldTry && !$el.is('title')) {
				var offset = $el.offset();
				scrollTo(offset.left, offset.top);
				this.shouldTry = false;
			}
		}, this));
	},
	success: function (payload, status, xhr, settings) {
		this.shouldTry = true;
	}
}, {
	shouldTry: true
});

})(jQuery);
