/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

$(document).ready(function () {
	var baseUrl;

	baseUrl = $('#form-edit input[name=base-url]').val();
	
	$('.message').hide();
	$('.message .close').click(function () {
		$(this).parents('.message').hide();
	});
});
