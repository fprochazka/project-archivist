(function ($) {

	$.nette.ext('overlay', {
		before: function (xhr, settings) {
			if (!settings.nette || !settings.nette.el) {
				return;
			}

			var tar = $(settings.nette.el);
			var container = tar.closest('.ajax-overlay');

			var text = container.data('overlay-text');
			if (typeof text == 'undefined') {
				text = '<p>Loading, please wait...</p>';
			}

			if (container.length == 0) {
				return;
			}

			var otherTarget = $(container.data('overlay-el'));
			if (otherTarget.length) {
				container = otherTarget.css({'position': 'relative'});
			}

			this.overlay = $('<div class="overlay ajaxOverlay">' + text + '</div>');
			this.container = container;
			this.overlay.appendTo(this.container);
			this.overlay.show();
		},
		success: function (payload) {
			if (!this.overlay) {
				return;
			}

			this.overlay.remove();
			this.overlay = null;
		},
		error: function (xhr, status) {
			if (!this.overlay) {
				return;
			}

			this.overlay.find('p').html("We're sorry, but something broke.<br>Please try again later.");

			var overlay = this.overlay;
			setTimeout(function () {
				overlay.remove();
			}, 3000);
			this.overlay = null;
		}
	}, { container: null, overlay: null });

})(jQuery);
