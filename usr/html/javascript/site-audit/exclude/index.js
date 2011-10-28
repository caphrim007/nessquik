/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$,searchForReports*/

function saveExcludeList() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/site-audit/exclude/save';
	params = $('#form-submit').serialize();

	$.post(
		url,
		params,
		function (data) {
			if (data.status === true) {
				window.location = baseUrl + '/site-audit/finalize/';
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
		$('.progress, #pleaseBePatient').show();
		$('#btn-save').attr('disabled', 'disabled');
		saveExcludeList();
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
