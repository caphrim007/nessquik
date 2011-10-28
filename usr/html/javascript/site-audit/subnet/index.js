/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$,searchForReports*/

function saveSubnet() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/site-audit/subnet/save';
	params = $('#form-submit').serialize();

	$.post(
		url,
		params,
		function (data) {
			if (data.status === true) {
				window.location = baseUrl + '/site-audit/exclude/';
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

	$('.message .close').click(function () {
		$(this).parents('.message').hide();
	});

	$('#btn-save').click(function () {
		$('#btn-save').attr('disabled', 'disabled');
		saveSubnet();
	});

	$('input[name=network]').keypress(function (event) {
		if (event.which === 47) {
			event.preventDefault();
			$('input[name=netmask]').focus();
			return false;
		} else if (event.which === 13) {
			event.preventDefault();
			$('#btn-save').trigger('click');
			return true;
		}
	});

	$('input[name=network], input[name=netmask]').keypress(function (event) {
		if (event.which === 13) {
			event.preventDefault();
			$('#btn-save').trigger('click');
			return true;
		}
	});

	$('input[name=network]').focus();
});
