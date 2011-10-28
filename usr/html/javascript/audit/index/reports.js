/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

$(document).ready(function () {
	var baseUrl;

	baseUrl = $('#form-edit input[name=base-url]').val();

	$('#form-edit').data('isSearching', false);
	$('#form-edit').data('searchTimeout', undefined);
	$('#form-edit').data('selectedAuditIds', []);
	$('#form-edit').data('generatedAudit', false);

	$('#reports .progress, .format-example, #recipientContainer, #reports-dialog .content-block').hide();
	$('#download-format .generating, #download-format .download').hide();

	$('#reports-download .format').click(function () {
		var format;

		$('#download-format .generating, #download-format .download').hide();
		$('#download-format .generate').show();

		format = $(this).find('input[name=format]').val();

		$('#reports-download .format-example').hide();
		$('#reports-download .format').removeClass('selected-format');
		$('#' + format).show();

		$(this).addClass('selected-format');
		$('#form-edit').data('format', format);
	});

	$('#reports-download .generate').click(function () {
		var format, params, url, auditIds, fileFormat,
		    downloadUrl, tmpParams, allFinished;

		url = baseUrl + '/audit/report/generate';
		auditIds = $('#form-edit').data('selectedAuditIds');
		format = $('#form-edit').data('format');
		fileFormat = $('#' + format + ' input[name=format]').val();

		tmpParams = $('#' + format + ' input[name=format]').serialize();
		downloadUrl = baseUrl + '/audit/report/download-selected?' + tmpParams;

		params = [];

		allFinished = $('#form-edit').data('all-finished');

		if (allFinished === true) {
			params.push({name: 'auditIds', value: 'all'});
		} else {
			params.push({name: 'auditIds', value: auditIds});
		}

		params.push({name: 'format', value: fileFormat});

		$('#download-format .generate, #download-format .generating, #download-format .download').hide();
		$('#' + format + ' .generating').show();

		$.post(
			url,
			params,
			function (data) {
				$('#form-edit').data('generatedAudit', true);
				$('#download-format .generating, #download-format .download, #download-format .generate').hide();

				$('#' + format + ' .download').attr('href', downloadUrl);
				$(this).addClass('selected-format');

				$('#download-format .download').show();
			},
			'json'
		);
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

	$('.selected-download').live('click', function () {
		$('#reports-download .selected-format').trigger('click');
		$('#reports-download').dialog('open');

		$('#download-format .generating, #download-format .download').hide();
		$('#download-format .generate').show();
	});

	$('.reports-dialog-close').click(function () {
		$(this).parents('.dialog-block').dialog('close');
	});

	$('#audit-finished .finished input[type=checkbox]').live('click', function () {
		var auditIds, auditId, isChecked, arrId;

		auditIds = $('#form-edit').data('selectedAuditIds');
		auditId = $(this).val();
		isChecked = $(this).is(':checked');
		arrId = $.inArray(auditId, auditIds);

		if (isChecked) {
			auditIds.push(auditId);
		} else {
			$('.select-mesg, .select-all-mesg, .select-all-finished-mesg').hide();
			$('#form-edit').data('all-finished', false);
			$('#audit-finished .selectable .row').removeClass('highlighted');
			auditIds.splice(arrId, 1);
		}

		$('#form-edit').data('selectedAuditIds', auditIds);
	});

	$('#reports-download .format:first').trigger('click');
});

$(window).unload(function () {
	var baseUrl, generatedAudit, reqUrl;

	generatedAudit = $('#form-edit').data('generatedAudit');

	if (generatedAudit === true) {
		baseUrl = $('#form-edit input[name=base-url]').val();
		reqUrl = baseUrl + '/audit/report/delete-generated';
		$.ajax({url: reqUrl, cache: false, async: false});
	}
});
