/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$,searchForReports*/

function finalizeSiteAudit() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/site-audit/finalize/save';
	params = $('#form-submit').serialize();

	$('#pleaseBePatient').show();

	$.post(
		url,
		params,
		function (data) {
			$('#pleaseBePatient').hide();
			$('#btn-save, #backLink, .progress').hide();

			if (data.status === true) {
				$('#finishedSaving').show();
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
	$('.message, .progress, #pleaseBePatient, #finishedSaving').hide();

	$('.message .close').click(function () {
		$(this).parents('.message').hide();
	});

	$('#btn-save').click(function () {
		$('#btn-save').attr('disabled', 'disabled');
		$('.progress').show();
		finalizeSiteAudit();
	});
});
