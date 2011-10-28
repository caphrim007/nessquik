/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$,searchForReports*/

function getSelectedTab() {
	var tabs, tabText;

	tabs = $('#target-steps').tabs();
	tabText = $('#target-steps .ui-tabs-selected span').html();

	if (tabText === 'Specific') {
		return 0;
	} else if (tabText === 'List of targets') {
		return 1;
	} else if (tabText === 'Extract IP targets') {
		return 2;
	} else {
		return -1;
	}
}

function toggleSubmitButton() {
	var selectedScanner;

	selectedScanner = $('.scanner').val();

	if (selectedScanner === '') {
		$('#btn-save').attr('disabled', 'disabled');
	} else {
		$('#btn-save').attr('disabled', '');
	}
}

$(document).ready(function () {
	var baseUrl, url, params, row,
	    page, oldPage, oldPageNum,
	    newPage, curVal, newVal, wrapper,
	    block, isSearching;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = null;
	params = {};
	row = null;
	page = null;
	oldPage = null;
	oldPageNum = 0;
	newPage = 0;
	curVal = null;
	newVal = null;
	wrapper = null;

	$('#form-edit').data('isSearching', false);
	$('.message, .input, .description').hide();
	$('#dialog .progress, #page-header .progress').hide();
	$('#steps, #target-steps').tabs();

	$('#btn-save').click(function () {
		url = baseUrl + '/site-audit/options/save';
		params = $('#form-edit, #form-submit').serialize();

		$('#page-header .progress').show();
		$('#btn-save').attr('disabled', 'disabled');

		$.post(
			url,
			params,
			function (data) {
				$('#page-header .progress').hide();

				if (data.status === true) {
					window.location = baseUrl + '/site-audit/subnet';
				} else {
					$('#page-header .progress').hide();
					$('#btn-save').attr('disabled', '');

					$('#form-submit .message.error .content').html(data.message);
					$('#form-submit .message.error').show();
				}
			},
			'json'
		);
	});

	$('.row').live('mouseover', function () {
		$(this).find('.icons img').show();
	});
	$('.row').live('mouseout', function () {
		$(this).find('.icons img').hide();
	});

	$('.message .close').click(function () {
		$(this).parents('.message').hide();
	});

	$('#form-submit input[name=policyId]').live('click', function () {
		toggleSubmitButton();
	});

	if ($('.scanner').size() > 0) {
		$('#general select[class=scanner]').trigger('change');
	}
});
