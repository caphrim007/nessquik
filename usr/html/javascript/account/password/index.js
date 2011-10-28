/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

$(document).ready(function () {
	var baseUrl = $('#form-edit input[name=base-url]').val();

	$('.message, .progress').hide();

	$('#form-submit input[type=password]').keypress(function (event) { 
		if (event.which === 13) {
			event.preventDefault();
			$('#btn-save').trigger('click');
			return true;
		}
	});

	$('#btn-save').click(function () {
		var url, params, accountId, newPassword, repeatPassword;

		url = baseUrl + '/account/password/save';
		params = $('#form-submit').serialize();
		accountId = $('#form-submit input[name=accountId]').val();

		newPassword = $('#form-submit input[name=newPassword]').val();
		repeatPassword = $('#form-submit input[name=repeatPassword]').val();

		if (newPassword === '' || repeatPassword === '') {
			$('.message').html('Passwords cannot be empty');
			$('.message').show();
			return;
		}

		$('.progress').show();
		$('#btn-save').attr('disabled', 'disabled');

		$.post(
			url,
			params,
			function (data) {
				$('.progress').hide();

				if (data.status === true) {
					window.location = baseUrl + '/account/modify/edit?id=' + accountId;
				} else {
					$('#btn-save').attr('disabled', '');
					$('.message').html(data.message);
					$('.message').show();
				}
			},
			'json'
		);
	});

	$('.message .close').click(function () {
		$(this).parents('.message').hide();
	});

	$('#form-submit input[name=newPassword]').focus();
});
