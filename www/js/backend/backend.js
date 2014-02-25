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