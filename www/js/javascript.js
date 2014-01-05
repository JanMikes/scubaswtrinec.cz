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