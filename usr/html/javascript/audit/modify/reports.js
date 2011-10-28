/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$,stopAudit*/

function fetchReport(reportId) {
	var baseUrl, url, params, tab;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/audit/report/fetch';
	params = { 'reportId': reportId };
	tab = $('#report-detail');

	$('#reports .progress').show();
	tab.find('.loading-block').show();
	tab.find('.content-block').hide();

	$.get(
		url,
		params,
		function (data) {
			$('#reports .progress').hide();
			if (data.status === true) {
				console.debug('Successfully received the report from the server');
				var tab = $('#report-detail');
				tab.find('.loading-block').hide();
				tab.find('.content-block').html(data.message);
				tab.find('.content-block').show();
			} else {
				console.debug('Failed to receive the report from the server');
			}
		},
		'json'
	);
}

function countReports() {
	var totalCount;

	totalCount = 0;
	totalCount = $('#reports .report-list .row').size();

	return totalCount;
}

function searchForReports() {
	var baseUrl, url;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/audit/modify/search-reports';

	$('#page-header .progress').show();

	$.get(
		url,
		$('#reportSearch, #form-edit input[name=auditId]').serialize(),
		function (data) {
			var reportCount;

			reportCount = 0;

			$('#form-edit').data('isSearching', false);
			$('#reports .search-results .content').html(data);
			$('#reports .search-results .content').show();
			$('#page-header .progress').hide();
			$('#reports .icons img').hide();
			$('.block input[type=checkbox]').unbind('click').shiftcheckbox();

			reportCount = countReports();
			if (reportCount === 0) {
				$('#steps').tabs('remove', 4);
				$('#steps').tabs('select', 0);
			}
		},
		'html'
	);
}

function deleteReport(reportId) {
	var baseUrl, url, auditId, params, searchTimeout;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/audit/report/delete';
	auditId = $('#form-edit input[name=auditId]').val();
	params = { 'auditId': auditId, 'reportId': reportId };
	searchTimeout = $('#form-edit').data('searchTimeout');

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
					var isSearching = $('#form-edit').data('isSearching');

					if (isSearching !== false) {
						return;
					} else {
						$('#form-edit').data('isSearching', true);
					}

					searchForReports();
				}, 300);

				$('#form-edit').data('searchTimeout', searchTimeout);
			} else {
				return;
			}
		},
		'json'
	);
}

$(document).ready(function () {
	var baseUrl, url, params, row, remaining, auditId, items,
	    page, oldPage, oldPageNum, newPage, isNew, curVal,
	    newVal, name, totalRecipients, auditName, reportId,
	    resp;

	baseUrl = $('#form-edit input[name=base-url]').val();

	$('#form-edit').data('isSearching', false);
	$('#form-edit').data('searchTimeout', undefined);

	url = undefined;
	params = {};
	row = undefined;
	remaining = 0;
	auditId = undefined;
	items = {};
	page = undefined;
	oldPage = undefined;
	oldPageNum = 0;
	newPage = 0;
	isNew = false;
	curVal = undefined;
	newVal = undefined;
	name = undefined;
	totalRecipients = 0;

	$('#reports .progress, .format-example, #recipientContainer, #reports-dialog .content-block').hide();

	$('#reports-download .format').click(function () {
		var format, params, downloadUrl;

		format = $(this).find('input[name=format]').val();
		params = $('#form-edit input[name=auditId], #form-edit input[name=reportId], #' + format + ' input[name=format]').serialize();
		downloadUrl = baseUrl + '/audit/report/download?' + params;

		$('#reports-download .format-example').hide();
		$('#reports-download .format').removeClass('selected-format');

		$('#' + format + ' .download').attr('href', downloadUrl);
		$('#' + format).show();
		$(this).addClass('selected-format');
	});

	$('#reports-dialog').dialog({
		autoOpen: false,
		bgiframe: true,
		dialogClass: 'reports-dialog',
		draggable: false,
		resizable: false,
		height: "auto",
		modal: true,
		position: ['center', 'center'],
		width: 710
	});

	$('#reports-download').dialog({
		autoOpen: false,
		bgiframe: true,
		dialogClass: 'download-dialog',
		draggable: false,
		resizable: false,
		height: "auto",
		modal: true,
		position: ['center', 'center'],
		width: 710
	});

	$('.reports-dialog-close').click(function () {
		$(this).parents('.dialog-block').dialog('close');
	});

	$('#reports .show-more').live('click', function () {
		var isSearching, wrapper;

		isSearching = $('#form-edit').data('isSearching');
		if (isSearching !== false) {
			return;
		} else {
			$('#form-edit').data('isSearching', true);
		}

		wrapper = $('#reportSearch');
		page = wrapper.find('input[name=page]');
		oldPage = wrapper.find('input[name=old-page]');
		oldPageNum = oldPage.val();
		newPage = parseInt(oldPageNum, 10) + 1;

		oldPage.val(newPage);
		page.val(newPage);
		searchForReports();
	});
	$('#reports .show-less').live('click', function () {
		var isSearching, wrapper;

		isSearching = $('#form-edit').data('isSearching');
		if (isSearching !== false) {
			return;
		} else {
			$('#form-edit').data('isSearching', true);
		}

		wrapper = $('#reportSearch');
		page = wrapper.find('input[name=page]');
		oldPage = wrapper.find('input[name=old-page]');
		oldPageNum = oldPage.val();
		newPage = parseInt(oldPageNum, 10) - 1;

		oldPage.val(newPage);
		page.val(newPage);
		searchForReports();
	});

	$('#reports .selected-compare').live('click', function () {
		$('#reports .selectable .row input[type=checkbox]:checked').each(function () {
			var auditId = $(this).val();
			stopAudit(auditId);
		});
	});

	$('#reports .select-all').live('click', function () {
		var block = $(this).parents('.block');
		block.find('input[type=checkbox]').attr('checked', 'checked');
		block.find('.select-every').show();
	});
	$('#reports .select-none').live('click', function () {
		var block = $(this).parents('.block');
		block.find('input[type=checkbox]').attr('checked', '');
	});

	$('#reports .selectable .name span').live('click', function () {
		reportId = $(this).parents('.row').find('input[name=reportId]').val();
		auditName = $(this).parents('.row').find('.name span').html();
		$('#reports-dialog .audit-report-name').html(auditName);
		$('#form-edit input[name=reportId]').val(reportId);

		$('#reports-dialog').dialog('open');
		fetchReport(reportId);
	});

	$('#reports .selected-delete').live('click', function () {
		$('.selectable .row input[type=checkbox]:checked').each(function () {
			var reportId = $(this).val();
			deleteReport(reportId);
		});
	});

	$('#reports .icons img.trash').live('click', function () {
		row = $(this).parents('.row');
		reportId = row.find('input[name=reportId]').val();
		resp = confirm('Are you sure you want to delete this report?');
		if (!resp) {
			return false;
		}

		deleteReport(reportId);
	});

	$('#reports .icons img.download').live('click', function () {
		reportId = $(this).parents('.row').find('input[name=reportId]').val();
		auditName = $(this).parents('.row').find('.name span').html();
		$('#form-edit input[name=reportId]').val(reportId);
		$('#reports-download .audit-report-name').html(auditName);
		$('#reports-download .selected-format').trigger('click');
		$('#reports-download').dialog('open');
	});

	$('#reports-download .format:first').trigger('click');

	$('#reports-email .recipient input[type=text]').watermark('email address');
	$('#reports-email .add-recipient').click(function () {
		if (totalRecipients === 3) {
			return;
		}

		$('#scaffolding .recipient').clone().appendTo('#emailRecipientList');
		$('#reports-email .recipient input[type=text]').watermark('email address');
		totalRecipients = totalRecipients + 1;
	});
	$('#reports-email .recipient .icons img.trash').live('click', function () {
		if (totalRecipients <= 1) {
			return;
		}

		var recipient = $(this).parents('.recipient');
		recipient.hide();

		recipient.find(':input').val('');
		recipient.find(':input').trigger('blur');

		totalRecipients = totalRecipients - 1;
	});
	$('#reports-email input[name=sendToOthers]').click(function () {
		if ($(this).val() === 'yes') {
			$('#recipientContainer').show();
		} else {
			$('#recipientContainer').hide();
		}
	});
});
