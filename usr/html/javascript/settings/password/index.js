/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window, $*/
/* vim: set ts=4:sw=4:sts=4smarttab:expandtab:autoindent */

$(document).ready(function () {
	$('#message, .progress').hide();

	$('#form-submit input[type=password]').keypress(function (event) {
		if (event.which === 13) {
			event.preventDefault();
			$('#btn-save').trigger('click');
			return true;
		}
	});

	$('#btn-save').click(function () {
		var baseUrl, url, params, newPassword, repeatPassword;

		baseUrl = $('#form-edit input[name=baseUrl]').val();
		url = baseUrl + '/settings/password/save';
		params = $('#form-submit').serialize();

		newPassword = $('#form-submit input[name=newPassword]').val();
		repeatPassword = $('#form-submit input[name=repeatPassword]').val();

		if (newPassword === '' || repeatPassword === '') {
			$('#message').html('Passwords cannot be empty');
			$('#message').show();
			return;
		}

		$.post(
			url,
			params,
			function (data) {
				if (data.status === true) {
					window.location = baseUrl + '/settings/modify/edit';
				} else {
					$('#message').html(data.message);
					$('#message').show();
				}
			},
			'json'
		);
	});
});
