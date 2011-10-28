/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

$(document).ready(function () {
	var baseUrl;

	baseUrl = $('#form-edit input[name=base-url]').val();

	$('.message, .progress').hide();

	$('#btn-save').click(function () {
		var url, params, accountId;

		url = baseUrl + '/account/interface/save';
		params = $('#form-submit').serialize();
		accountId = $('#form-submit input[name=accountId]').val();

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
					$('.message .content').html(data.message);
					$('.message').show();
				}
			},
			'json'
		);
	});
});
