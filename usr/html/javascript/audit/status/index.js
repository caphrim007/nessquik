/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

function searchForScanners() {
	var baseUrl, url, params, block;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/audit/status/scanner-jobs';
	params = {};

	$('.progress').show();

	$.get(
		url,
		params,
		function (data) {
			$('.progress').hide();
			if (data.totalResults == 0) {

			} else {
				$('#scannerResultsTable tbody').html(data.content);
				$('#scannerResultsTable').trigger('update');
				var sorting = [[0,0]];
				// sort on the first column 
				$('#scannerResultsTable').trigger('sorton',[sorting]); 
			}
		},
		'json'
	);
}

function searchForLastAudits() {

}

$(document).ready(function () {
	var baseUrl;

	baseUrl = $('#form-edit input[name=base-url]').val();

	$('#form-edit').data('searchTimeout', undefined);
	$('#scannerResultsTable').tablesorter({
		widgets: ['zebra'],
		headers: {
			1: { sorter: false }
		},
		debug: true
	});

	searchForScanners();
});
