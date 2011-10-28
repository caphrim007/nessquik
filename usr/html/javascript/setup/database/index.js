/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

function testConnection() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/setup/database/test';
	params = $('#form-submit').serialize();

	$.post(
		url,
		params,
		function (data) {
			if (data.status === true) {
				$(document).dequeue("ajaxRequests");
			} else {
				$(document).queue("ajaxRequests", []);
				$('#btn-save').attr('disabled', '');
				$('.message .content').html(data.message);
				$('.message').show();
				$('.progress').hide();
			}
		},
		'json'
	);
}

function saveCredentials() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/setup/database/save';
	params = $('#form-submit').serialize();

	$.post(
		url,
		params,
		function (data) {
			if (data.status === true) {
				window.location = baseUrl + '/setup/docdb/';
			} else {
				$('#btn-save').attr('disabled', '');
				$('.message .content').html(data.message);
				$('.message').show();
				$('.progress').hide();
			}
		},
		'json'
	);
}

$(document).ready(function () {
	$('.message, .progress').hide();

	$('#form-submit input[type=text], #form-submit textarea').keypress(function (event) {
		if (event.which === 13) {
			event.preventDefault();
			$('#btn-save').trigger('click');
			return true;
		}
	});

	$('#btn-save').click(function () {
		$('.progress').show();
		$('#btn-save').attr('disabled', 'disabled');

		$(document).queue("ajaxRequests", function () {
			testConnection();
		});
		$(document).queue("ajaxRequests", function () {
			saveCredentials();
		});

		$(document).dequeue("ajaxRequests");
	});

	$('.message .close').click(function () {
		$(this).parents('.message').hide();
	});
});
