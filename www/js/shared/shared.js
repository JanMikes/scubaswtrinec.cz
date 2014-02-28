// Avoid `console` errors in browsers that lack a console.
(function() {
	var method;
	var noop = function () {};
	var methods = [
		'assert', 'clear', 'count', 'debug', 'dir', 'dirxml', 'error',
		'exception', 'group', 'groupCollapsed', 'groupEnd', 'info', 'log',
		'markTimeline', 'profile', 'profileEnd', 'table', 'time', 'timeEnd',
		'timeStamp', 'trace', 'warn'
	];
	var length = methods.length;
	var console = (window.console = window.console || {});

	while (length--) {
		method = methods[length];

		// Only stub undefined methods.
		if (!console[method]) {
			console[method] = noop;
		}
	}
}());

$(function(){

	// Allow ajax only if browser allows it
	if(window.history && history.pushState && window.history.replaceState){
		$.nette.init();
	}

	$.ajaxSetup({
		cache: false,
		dataType: 'json'
	});

	// Nette ajax confirm extension
	$.nette.ext('confirm', {
		before: function (xhr, settings) {
			if (settings.nette !== undefined && settings.nette.el !== undefined) {
				var question = settings.nette.el.data('confirm');
				if (question) {
					return confirm(question);
				}
			}
		}
	});

	// Nette ajax google analytics extension
	if (typeof _gaq != 'undefined') {
		(function ($, _gaq) {
			$.nette.ext('ga', {
				success: function (payload) {
					var url = payload.url || payload.redirect;
					if (url && !$.nette.ext('redirect')) {
						_gaq.push(['_trackPageview', url]);
					}
				}
			});
		})(jQuery, _gaq);
	}

});
