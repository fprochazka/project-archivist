(function ($, undefined) {

	$.nette.ext('voting', {
		init: function () {
			$('body').on('click', '.post-vote .vote, .post-vote .vote *', function (e) {
				var el = $(this);
				el = el.is('.vote') ? el : el.closest('.vote');

				e.preventDefault();
				console.log(el.data());

				$.nette.ajax({
					url: el.data('vote-link'),
					off: ['scrollTo']
				}, el.closest('.post-vote'), e);

				return false;
			});
		}
	});

})(jQuery);
