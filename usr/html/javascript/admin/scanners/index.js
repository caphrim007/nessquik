/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

function searchForScanners() {
	var baseUrl, url;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/admin/scanners/search';

	$('.progress').show();

	$.get(
		url,
		$('#form-edit').serialize(),
		function (data) {
			$('.progress').hide();

			$('#content').html(data.message);
			$('#content').show();
			$('#content .icons img').hide();

			$('#content .block input[type=checkbox]')
				.unbind('click')
				.shiftcheckbox();

			if (data.totalPages <= 1) {
				$('#pager').hide();
			} else {
				$("#pager").pager({
					pagenumber: data.currentPage,
					pagecount: data.totalPages,
					buttonClickCallback: function(pageclickednumber){
						$('#form-edit input[name=page]').val(pageclickednumber);
						searchForScanners();
					}
				}).show();
			}
		},
		'json'
	);
}

function deleteScanner(scannerId) {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/admin/scanners/delete';
	params = { 'scannerId': scannerId };

	$('.progress').show();

	$.post(
		url,
		params,
		function (data) {
			var searchTimeout;

			searchTimeout = $('#form-edit').data('searchTimeout');
			$('.progress').hide();

			if (data.status === true) {
				if (searchTimeout !== undefined) {
					clearTimeout(searchTimeout);
				}

				searchTimeout = setTimeout(function () {
					searchForScanners();
				}, 200);
			} else {
				return;
			}

			$('#form-edit').data('searchTimeout', searchTimeout);
		},
		'json'
	);
}

$(document).ready(function () {
	$('.icons img.trash').live('click', function () {
		var scannerId, resp;

		scannerId = $(this).parents('.row').find('input[name=scannerId]').val();
		resp = confirm('Are you sure you want to delete this scanner?');
		if (!resp) {
			return false;
		}

		deleteScanner(scannerId);
	});

	$('.row').live('mouseover', function () {
		$(this).find('.icons img').show();
	});
	$('.row').live('mouseout', function () {
		$(this).find('.icons img').hide();
	});

	searchForScanners();
});
