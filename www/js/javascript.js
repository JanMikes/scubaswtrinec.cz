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

	$.nette.init();

		$.ajaxSetup({
		cache: false,
		dataType: 'json'
	});

	$('<div id="ajax-spinner" style="position: fixed;left: 50%;top: 50%;display: none;">Loading<br /><i class="fa fa-spinner fa-spin fa-3x"></i></div>').appendTo("body");
	$.nette.ext('spinner', {
		start: function () {
			this.spinner.show(this.speed);
		},
		complete: function () {
			this.spinner.hide(this.speed);
		}
	}, {
		spinner: $('#ajax-spinner'),
		speed: undefined
	});

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

	// Reinitialization after ajax request
	$.nette.ext('reinitialization', {
		complete: function() {
			reinit();
		}
	});
	
	reinit();

});

function reinit()
{

}