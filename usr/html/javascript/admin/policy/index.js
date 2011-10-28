/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window, $*/
/* vim: set ts=4:sw=4:sts=4smarttab:expandtab:autoindent */

function searchForPolicies() {
	var baseUrl, url;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/admin/policy/search';

	$('.progress').show();

	$.get(
		url,
		$('#form-edit').serialize(),
		function (data) {
			$('.progress').hide();

			$('#content').html(data.message);
			$('#content').show();
			$('#content .icons img').hide();
			$('#content input[type=checkbox]')
				.unbind('click')
				.shiftcheckbox();

			if (data.totalPages <= 1) {
				$('#pager').hide();
			} else {
				$("#pager").pager({
					pagenumber: data.currentPage,
					pagecount: data.totalPages,
					buttonClickCallback: function(pageClickedNumber){
						$('#form-edit input[name=page]').val(pageClickedNumber);
						searchForPolicies();
					}
				}).show();
			}
		},
		'json'
	);
}

function deletePolicy(policyId) {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/admin/policy/delete';
	params = { 'policyId': policyId };

	$.post(
		url,
		params,
		function (data) {
			var searchTimeout;

			if (data.status === true) {
				searchTimeout = $('#form-edit').data('searchTimeout');
				if (searchTimeout !== undefined) {
					clearTimeout(searchTimeout);
				}

				searchTimeout = setTimeout(function () {
					searchForPolicies();
				}, 100);

				$('#form-edit').data('searchTimeout', searchTimeout);
			} else {
				return;
			}
		},
		'json'
	);
}

$(document).ready(function () {
	$('.icons img.trash').live('click', function () {
		var row, policyId, resp;

		row = $(this).parents('.row');
		policyId = row.find('input[name=policyId]').val();
		resp = confirm('Are you sure you want to delete this policy?');
		if (!resp) {
			return false;
		} else {
			row.hide();
		}

		deletePolicy(policyId);
	});

	$('.row').live('mouseover', function () {
		$(this).find('.icons img.trash').show();
	});
	$('.row').live('mouseout', function () {
		$(this).find('.icons img.trash').hide();
	});

	$('#searchMenu').click(function(event){
		event.stopPropagation();
	});
	$('#searchMenu').hide();
	$('#searchLink').click(function(event){
		event.stopPropagation();
		if ($('#searchMenu').is(':visible')) {
			$('#searchMenu').hide();
		} else {
			$('#searchMenu').show();
			$('#searchMenu').position({
				of: $('#title'),
				my: 'right top',
				at: 'right bottom'
			});
		}
	});

	$(document).click(function(){
		if ($('#searchMenu').is(':visible')) {
			$('#searchMenu').hide();
		}
	});

	searchForPolicies();
});
