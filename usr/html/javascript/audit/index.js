/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window, $*/
/* vim: set ts=4:sw=4:sts=4smarttab:expandtab:autoindent */

searchForAudits = function () {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=baseUrl]').val();
	url = baseUrl + '/audit/index/search';
	params = {};

	params = $('#form-audits').serialize();

	$('#title .progress').show();

	$.get(
		url,
		params,
		function (data) {
			var auditIds, totalChecked, auditId, isChecked, arrId,
			    totalOnPage, allFinished;

			$('#title .progress').hide();

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
					buttonClickCallback: function(pageclickednumber){
						$('#form-edit input[name=page]').val(pageclickednumber);
						searchForAudits();
					}
				}).show();
			}
		},
		'json'
	);
}

function startAudit(auditId, runWhen) {
	var baseUrl, url, params, startDate, endDate, dateScheduled;

	baseUrl = $('#form-edit input[name=baseUrl]').val();
	url = baseUrl + '/audit/index/start';
	params = { 'auditId': auditId, 'runWhen': runWhen };
	startDate = null;
	endDate = null;

	$('#startTimeDiag .progress').show();
	$('#btn-start').attr('disabled', 'disabled');

	if (runWhen === 'future') {
		dateScheduled = Date.parse($('#startOnDate').val() + ' ' + $('#startOnTime').val());
		params.dateScheduled = dateScheduled.toString('yyyy-MM-ddTHH:mm:ss');
	}

	$.post(
		url,
		params,
		function (data) {
			$('#startTimeDiag .progress').hide();
			$('#btn-start').attr('disabled', '');

			if (data.status === true) {
				$('#startTimeDiag').dialog('close');

				var searchTimeout = $('#form-edit').data('searchTimeout');

				if (searchTimeout !== undefined) {
					clearTimeout(searchTimeout);
				}

				searchTimeout = setTimeout(function () {
					var tabs, selected, block, selectedStatus;

					tabs = $('#steps').tabs();
					selected = tabs.tabs('option', 'selected');
					block = $('.ui-tabs-selected').find('a').attr('href');
					selectedStatus = $(block).find('input[name=statusText]').val();
					searchForAudits(selectedStatus);
				}, 300);

				$('#form-edit').data('searchTimeout', searchTimeout);
				$('#startTimeDiag .message.error .content').html('');
				$('#startTimeDiag .message.error').hide();
			} else {
				$('#startTimeDiag .message.error .content').html(data.message);
				$('#startTimeDiag .message.error').show();
				return;
			}
		},
		'json'
	);
}

function stopAudit(auditId) {
	var baseUrl, url, params, searchTimeout;

	baseUrl = $('#form-edit input[name=baseUrl]').val();
	url = baseUrl + '/audit/index/stop';
	params = {'auditId': auditId};
	searchTimeout = $('#form-edit').data('searchTimeout');

	$('#heading .progress').show();

	$.post(
		url,
		params,
		function (data) {
			if (data.status === true) {
				if (searchTimeout !== undefined) {
					clearTimeout(searchTimeout);
				}

				searchTimeout = setTimeout(function () {
					var tabs, selected, block, selectedStatus;

					tabs = $('#steps').tabs();
					selected = tabs.tabs('option', 'selected');
					block = $('.ui-tabs-selected').find('a').attr('href');
					selectedStatus = $(block).find('input[name=statusText]').val();
					searchForAudits(selectedStatus);
				}, 300);

				$('#form-edit').data('searchTimeout', searchTimeout);
			} else {
				$('#heading .progress').hide();
				return;
			}
		},
		'json'
	);
}

function parkAudit(auditId) {
	var baseUrl, url, params, searchTimeout;

	baseUrl = $('#form-edit input[name=baseUrl]').val();
	url = baseUrl + '/audit/index/park';
	params = {'auditId': auditId};
	searchTimeout = $('#form-edit').data('searchTimeout');

	$('#heading .progress').show();

	$.post(
		url,
		params,
		function (data) {
			if (data.status === true) {
				if (searchTimeout !== undefined) {
					clearTimeout(searchTimeout);
				}

				searchTimeout = setTimeout(function () {
					var tabs, selected, block, selectedStatus;

					tabs = $('#steps').tabs();
					selected = tabs.tabs('option', 'selected');
					block = $('.ui-tabs-selected').find('a').attr('href');
					selectedStatus = $(block).find('input[name=statusText]').val();
					searchForAudits(selectedStatus);
				}, 300);

				$('#form-edit').data('searchTimeout', searchTimeout);
			} else {
				$('#heading .progress').hide();
				return;
			}
		},
		'json'
	);
}

function deleteAudit(auditId) {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=baseUrl]').val();
	url = baseUrl + '/audit/index/delete';
	params = {'auditId': auditId};

	$('#title .progress').show();

	$.post(
		url,
		params,
		function (data) {
			if (data.status === true) {
				var searchTimeout = $('#form-edit').data('searchTimeout');

				if (searchTimeout !== undefined) {
					clearTimeout(searchTimeout);
				}

				searchTimeout = setTimeout(function () {
					var selectedStatus;

					selectedStatus = $('#form-edit input[name=status]').val();
					searchForAudits(selectedStatus);
				}, 300);

				$('#form-edit').data('searchTimeout', searchTimeout);
			} else {
				$('#title .progress').hide();
				return;
			}
		},
		'json'
	);
}

$(document).ready(function () {
	var baseUrl, dateScheduled, tabs, selected;

	baseUrl = $('#form-edit input[name=baseUrl]').val();
	dateScheduled = new Date();

	$('.select-actions, .message, .progress').hide();

	$('.selected-start').live('click', function () {
		$('.selectable .row input[type=checkbox]:checked').each(function () {
			var auditId = $(this).val();
			startAudit(auditId);
		});
	});
	$('.selected-stop').live('click', function () {
		$('.selectable .row input[type=checkbox]:checked').each(function () {
			var auditId = $(this).val();
			stopAudit(auditId);
		});
	});

	$('.select-all').click(function () {
		var block, auditId, auditIds, tabs;

		block = $(this).parents('.block');
		block.find('input[type=checkbox]').attr('checked', 'checked');
		block.find('.select-every').show();

		tabs = $('#steps').tabs("option", "selected");
		if (tabs === 4) {
			auditIds = $('#form-edit').data('selectedAuditIds');
			block.find('.row').addClass('highlighted');
			block.find('input[type=checkbox]:checked').each(function () {
				auditId = $(this).val();
				auditIds.push(auditId);
			});
			$('#form-edit').data('selectedAuditIds', auditIds);

			$('.select-mesg, .select-all-mesg').show();
			$('.select-all-finished-mesg').hide();
		}
	});

	$('.select-none').live('click', function () {
		var block;

		block = $(this).parents('.block');
		block.find('input[type=checkbox]').attr('checked', '');
		block.find('.row').removeClass('highlighted');

		block.find('.row').removeClass('highlighted');
		$('.select-mesg, .select-all-mesg, .select-all-finished-mesg').hide();
		$('#form-edit').data('all-finished', false);
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

	$('.icons img.trash').live('click', function () {
		var auditId, resp;

		auditId = $(this).parents('.row').find('input[name=auditId]').val();
		resp = confirm('Are you sure you want to delete this audit? The audit and associated results will be deleted');
		if (!resp) {
			return false;
		}

		$(this).parents('tr')
		deleteAudit(auditId);
	});

	$('.icons img.start').live('click', function () {
		var auditId = $(this).parents('.row').find('input[name=auditId]').val();
		$('#form-edit').data('auditId', auditId);
		$('#startTimeDiag input[name=startDateSwitch]:checked').trigger('click');
		$('#startTimeDiag').dialog('open');
	});

	$('.icons img.stop').live('click', function () {
		var auditId = $(this).parents('.row').find('input[name=auditId]').val();
		stopAudit(auditId);
	});

	$('.icons img.park').live('click', function () {
		var auditId = $(this).parents('.row').find('input[name=auditId]').val();
		parkAudit(auditId);
	});

	$('.message .close').click(function () {
		$(this).parents('.message').hide();
	});

	$('#startTimeDiag').dialog({
		autoOpen: false,
		bgiframe: true,
		dialogClass: 'starttime-dialog',
		draggable: false,
		resizable: false,
		height: "auto",
		modal: true,
		position: ['center', 'center'],
		width: 710
	});

	$('.starttime-dialog-close').click(function () {
		$('#startTimeDiag .message .close').trigger('click');
		$('#startTimeDiag').dialog('close');
	});

	$('#startOnDate').dateEntry({
		spinnerImage: 'usr/images/spinnerUpDown.png',
		spinnerSize: [15, 16, 0],
		spinnerIncDecOnly: true,
		defaultDate: dateScheduled
	});

	$('#startOnTime').timeEntry({
		spinnerImage: 'usr/images/spinnerUpDown.png',
		spinnerSize: [15, 16, 0],
		spinnerIncDecOnly: true,
		defaultDate: dateScheduled
	});

	$('#startOnDate').dateEntry('setDate', null);
	$('#startOnTime').timeEntry('setTime', null);

	$('#startTimeDiag input[name=startDateSwitch]').click(function () {
		var currentBlock = $(this).val();
		if (currentBlock === 'future') {
			/**
			* I'm doing this loop here because Google Chrome doesnt
			* Change the color of the input box when you undisable the
			* element. You need to do a focus on the element before it
			* appears to be undisabled.
			*/
			$('#startTimeDiag .future input[type=text]').each(function () {
				$(this).attr('disabled', '');
				$(this).focus();
			});
			// Then I do this to re-focus on the first input box
			$('#startTimeDiag .future input[type=text]')[0].focus();
		} else {
			$('#startTimeDiag .future input[type=text]').attr('disabled', 'disabled');
		}
	});

	$('#btn-start').click(function () {
		var scheduleType, auditId;

		scheduleType = $('#startTimeDiag input[name=startDateSwitch]:checked').val();
		auditId = $('#form-edit').data('auditId');
		startAudit(auditId, scheduleType);
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
