/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

function createAccounts() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/setup/accounts/create';
	params = {};

	$('.progress').show();

	$.post(
		url,
		params,
		function (data) {
			$('.progress').hide();

			$('#accounts').html(data);
			$("#acct-table").tablesorter({
				sortList: [[0, 0]],
				widgets: ['zebra']
				//debug: true
			});
			$('#accounts, #next-step').show();
		},
		'html'
	);
}

$(document).ready(function () {
	$('.message, .progress, #next-step').hide();

	$('.message .close').click(function () {
		$(this).parents('.message').hide();
	});

	createAccounts();
});
