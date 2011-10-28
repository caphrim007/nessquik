/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

function checkWritable() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/admin/logging/writable';
	params = $('#form-submit').serialize();

	$.get(
		url,
		params,
		function (data) {
			if (data.status === true) {
				window.location = baseUrl + '/admin/';
			} else {
				$('.message .content').html(data.message);
				$('.message').show();
			}
		},
		'json'
	);
}

$(document).ready(function () {
	var baseUrl;

	baseUrl = $('#form-edit input[name=base-url]').val();

	$('.message').hide();

	$('.input-txt').keypress(function (event) { 
		if (event.which === 13) {
			event.preventDefault();
			$('#btn-save').trigger('click');
			return true;
		}
	});

	$('#btn-save').click(function () {
		var url, params;

		url = baseUrl + '/admin/logging/save';
		params = $('#form-submit').serialize();

		$.post(
			url,
			params,
			function (data) {
				if (data.status === true) {
					window.location = baseUrl + '/admin/';
				} else {
					$('.message .content').html(data.message);
					$('.message').show();
				}
			},
			'json'
		);
	});

	$('.message .close').click(function () {
		$(this).parents('.message').hide();
	});
});
