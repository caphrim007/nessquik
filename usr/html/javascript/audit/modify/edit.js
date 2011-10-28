/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$,searchForReports*/

var onBeforeUnloadFired = false;

var searchForPolicies = function () {
	var baseUrl, url;

	baseUrl = $('#form-edit input[name=baseUrl]').val();
	url = baseUrl + '/audit/policy/search';

	$('#page-header .progress').show();

	$.get(
		url,
		$('#policySearch, #form-edit input[name=auditId], #form-edit input[name=curPolicyId]').serialize(),
		function (data) {
			$('#form-edit').data('isSearching', false);
			$('#policy .content').html(data.message);
			$('#policy .content').show();
			$('#page-header .progress').hide();

			if (data.totalPages <= 1) {
				$('#policy .pager').hide();
			} else {
				$("#policy .pager").pager({
					pagenumber: data.currentPage,
					pagecount: data.totalPages,
					buttonClickCallback: function(pageclickednumber){
						$('#form-edit input[name=page]').val(pageclickednumber);
						searchForPolicies();
					}
				}).show();
			}
		},
		'json'
	);
}

function deleteAudit(auditId) {
	var url, params;

	url = baseUrl + '/audit/index/delete';
	params = {'auditId': auditId};

	$.ajax({
		async: false,
		type: 'POST',
		url: url,
		data: params,
		dataType: 'json',
		error: function(resp, status, error) {
			console.log(resp);
		},
		success: function (data, status, resp) {
			console.log(data);
		}
	});
}

$(document).ready(function () {
	baseUrl = $('#form-edit input[name=baseUrl]').val();

	$('.message, .input, .description, .typeSelector, .targetTypeIcons img.type').hide();
	$('.newTargetBar .targetInitiator, .newTargetBar').hide();
	$('#general, .targetTypeIcons img.HostnameTarget').show();

	$('#btn-save').click(function () {
		url = baseUrl + '/audit/modify/save';
		params = $('#form-edit, #form-submit').serialize();

		$('#page-header .progress').show();
		$('#btn-save').attr('disabled', 'disabled');

		$.post(
			url,
			params,
			function (data) {
				$('#page-header .progress').hide();

				if (data.status === true) {
					/**
					* Set the value of isNew to be empty, or false,
					* to prevent the policy from being deleted when
					* the page is navigated away from
					*/
					$('#form-edit input[name=isNew]').val("");

					window.location = baseUrl + '/audit/';
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

	$('#include .icons img.trash').live('click', function () {
		var target, type, row;

		row = $(this).parents('.row');
		target = row.find('input[name*="included"]').val();
		type = row.find('input[name=type]').val();
		row.remove();

		removeTarget(target, type, 'include');
	});

	$('#exclude .icons img.trash').live('click', function () {
		var target, type, row;

		row = $(this).parents('.row');
		target = row.find('input[name*="excluded"]').val();
		type = row.find('input[name=type]').val();
		row.remove();

		removeTarget(target, type, 'exclude');
	});

	$('.message .close').click(function () {
		$(this).parents('.message').hide();
	});

	$('#target-specific select[name=type]').trigger('change');

	$('.addTarget').click(function(){
		parentDiv = $(this).parents('div.targetList');
		parentDiv.find('div.no-results-mesg').hide();
		parentDiv.find('div.newTargetBar .targetInitiator').hide();
		parentDiv.find('div.newTargetBar .targetActions').show();
		parentDiv.find('div.newTargetBar').show();
		parentDiv.find('div.newTargetBar input[name=target]').focus();
	});

	searchForPolicies();
	searchForReports();
});

$(window).bind('beforeunload', function(){
	if (onBeforeUnloadFired === false) {
		var auditId, isNew;

		onBeforeUnloadFired = true;

		auditId = $('#form-edit input[name=auditId]').val();
		isNew = $('#form-edit input[name=isNew]').val();

		if (isNew == 1) {
			console.log('Deleting audit');
			deleteAudit(auditId);
		}
	}
});
