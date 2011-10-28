/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window, $*/
/* vim: set ts=4:sw=4:sts=4smarttab:expandtab:autoindent */

function searchForAudits() {
	var baseUrl, url;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/admin/audit/search';
	params = {};

	params = $('#form-audits').serialize();

	$('.progress').show();

	$.get(
		url,
		params,
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
						searchForAudits();
					}
				}).show();
			}
		},
		'json'
	);
}

$(document).ready(function () {
	var baseUrl, searchTimeout;

	baseUrl = $('#form-edit input[name=base-url]').val();
	$('#form-edit').data('searchTimeout', false);

	$('.selected-delete').click(function () {
		var block;

		block = $(this).parents('.block');
		block.find('.row input[type=checkbox]:checked')
		  .parents('tr')
		  .find('.icon img.trash')
		  .trigger('click');
	});

	$('.select-all').click(function () {
		var block;

		block = $(this).parents('.block');
		block.find('input[type=checkbox]').attr('checked', 'checked');
		block.find('.select-every').show();
	});

	$('.select-none').click(function () {
		var block;

		block = $(this).parents('.block');
		block.find('input[type=checkbox]').attr('checked', '');
	});

	$('.icons img.trash').live('click', function () {
		var url, params;

		url = baseUrl + '/admin/audit/delete';
		params = $(this).parents('div.row').find('input[name=auditId]').serialize();

		$('.progress').show();

		$.post(
			url,
			params,
			function (data) {
				$('.progress').hide();
				if (data.status === true) {
					if (searchTimeout !== undefined) {
						clearTimeout(searchTimeout);
					}

					searchTimeout = setTimeout(function () {
						searchForAudits();
					}, 300);
				} else {
					return;
				}
			},
			'json'
		);
	});

	$('.row').live('mouseover', function () {
		$(this).find('.icons img').show();
		$(this).find('.action-icons').show();
		$(this).find('.progress-icons').hide();
	});
	$('.row').live('mouseout', function () {
		$(this).find('.icons img').hide();
		$(this).find('.progress-icons').show();
		$(this).find('.action-icons').hide();
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

	$('#form-audits input[type=button]').click(function(){
		$('#searchMenu').hide();
		searchForAudits();
	});

	searchForAudits();
});
