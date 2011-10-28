/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

function createAutomations() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/setup/automation/create';
	params = {};

	$('.progress').show();

	$.post(
		url,
		params,
		function (data) {
			$('.progress').hide();

			$('#automations').html(data);
			$("#auto-table").tablesorter({
				sortList: [[0, 0]],
				widgets: ['zebra']
				//debug: true
			});
			$('#automations, #next-step').show();
		},
		'html'
	);
}

$(document).ready(function () {
	$('.message, .progress, #next-step').hide();

	$('.message .close').click(function () {
		$(this).parents('.message').hide();
	});

	createAutomations();
});
