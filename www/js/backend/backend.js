$(function(){

	$("a[href$='.jpg'],a[href$='.png'],a[href$='.gif'], .fancy a, a.fancy").fancybox();

	$("input[maxlength], textarea[maxlength]").each(function(){
		$(this).after("<span class=\"maxlength-info\"></span>");
		maxlengthInfo($(this));
	}).on("change blur keydown keypress keyup", function() {
		maxlengthInfo($(this));
	});

	$("*[data-confirm]").click(function(e) {
		var msg = $(this).data("confirm");
		return confirm(msg);
	});

	var dtpicker = $('.dtpicker').datepicker({
		format: 'dd.mm.yyyy',
		weekStart: 1,
		autoclose: true
	}).on('changeDate', function(ev) {
		$(this).click().blur();
		$(this).datepicker('hide');
	});

	$("textarea.ckeditor").ckeditor({
		on: {
			instanceReady: function( ev ) {
				var writer = this.dataProcessor.writer;

				var blockElement = {
					indent: true,
					breakBeforeOpen: true,
					breakAfterOpen: true,
					breakBeforeClose: true,
					breakAfterClose: true
				};

				var inlineElement = {
					indent: false,
					breakBeforeOpen: false,
					breakAfterOpen: false,
					breakBeforeClose: false,
					breakAfterClose: false
				};

				var inlineBlockElement = {
					indent: false,
					breakBeforeOpen: false,
					breakAfterOpen: false,
					breakBeforeClose: false,
					breakAfterClose: true
				};

				writer.indentationChars = '\t';
				writer.selfClosingEnd = '>';
				writer.lineBreakChars = '\n';

				writer.setRules('p', inlineBlockElement);
				writer.setRules('br', inlineElement);
				writer.setRules('ul', blockElement);
				writer.setRules('ol', blockElement);
				writer.setRules('li', inlineBlockElement);
				writer.setRules('table', blockElement);
				writer.setRules('tbody', blockElement);
				writer.setRules('thead', blockElement);
				writer.setRules('tfoot', blockElement);
				writer.setRules('tr', blockElement);
				writer.setRules('th', inlineBlockElement);
				writer.setRules('td', inlineBlockElement);
				writer.setRules('a', inlineElement);
				writer.setRules('h2', inlineBlockElement);
				writer.setRules('h3', inlineBlockElement);
				writer.setRules('h4', inlineBlockElement);
				writer.setRules('h5', inlineBlockElement);
			}
    	}
	});

});

function maxlengthInfo ($el) {
	var max = $el.attr("maxlength");
	var act = $el.val().length;

	var $span = $el.parent().find(".maxlength-info");

	$span.html("<span>Zbývá " + (max-act) + " znaků z " + max + "</span>");

	if (act >= max) {
		$span.addClass("form-warning-message");
		$span.removeClass("form-success-message");
	} else {
		$span.removeClass("form-warning-message");
		$span.addClass("form-success-message");
	}

}
