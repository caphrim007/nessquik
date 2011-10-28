/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

var baseUrl, onBeforeUnloadFired;

onBeforeUnloadFired = false;
baseUrl = null;

function revealElevatePrivs() {
	var index;

	index = $('#elevate-privs').val();
	if (index === 'Nothing') {
		$('.elevate-password').hide();
	} else {
		$('.elevate-password').show();
	}
}

function revealWebAppTest(element) {
	var block;

	block = $(element).parents('.web-app-test-block');

	if ($(element).attr('checked')) {
		block.find('.test-value').show();
	} else {
		block.find('.test-value').hide();
	}
}

function listPlugins(family) {
	var url, params;

	$('#form-plugin input[name=family]').val(family);

	url = baseUrl + '/policy/plugin/list';
	params = $('#form-plugin').serializeArray();

	$('.progress').show();

	$.get(
		url,
		params,
		function (data) {
			$('.progress').hide();

			$('#individualPluginContent').html(data.message);
			$('#individualPluginContent').show();
			$('#individualPluginContent img.disable').hide();

			$('#individualPluginContent input[name="state"][value="disabled"]').each(function(){
				pluginId = $(this).prev().val();
				showPluginDisabled(pluginId);
			});

			if (data.totalPages <= 1) {
				$('#pager').hide();
			} else {
				$("#pager").pager({
					pagenumber: data.currentPage,
					pagecount: data.totalPages,
					buttonClickCallback: function(pageClickedNumber){
						$('#form-plugin input[name=page]').val(pageClickedNumber);
						listPlugins(data.family);
					}
				}).show();
			}
		},
		'json'
	);
}

function listFamilies() {
	var url, params;

	url = baseUrl + '/policy/family/list';
	params = $('#form-plugin').serializeArray();

	$('.progress').show();

	$.get(
		url,
		params,
		function (data) {
			$('.progress').hide();

			$('#pluginFamilyList').html(data.message);
			$('#pluginFamilyList img.disable, #pluginFamilyList img.mixed').hide();

			$('#plugins input[name="state"][value="disabled"]').each(function(){
				family = $(this).prev().val();
				showFamilyDisabled(family);
			});
			$('#plugins input[name="state"][value="mixed"]').each(function(){
				family = $(this).prev().val();
				showFamilyMixed(family);
			});
		},
		'json'
	);
}

function revealDbAuthType() {
	var index;

	$('.auth-type').hide();

	index = $('#db-type').val();
	if (index === 'Oracle') {
		$('#oracle-auth-type').show();
	} else if (index === 'SQL Server') {
		$('#sqlserver-auth-type').show();
	}
}

function deletePolicy(policyId) {
	var url, params;

	url = baseUrl + '/policy/index/delete';
	params = {'policyId': policyId};

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

function showPluginDisabled(pluginId) {
	$('#individualPluginContent input[name="plugin"][value="' + pluginId + '"]')
	  .parents('.row')
	  .find('img')
	  .hide()
	  .parents('.row')
	  .find('img.disable')
	  .show();
}

function setPluginState(policyId, pluginId, state) {
	var url, params;

	url = baseUrl + '/policy/plugin/state';
	params = {
		'policyId': policyId,
		'pluginId': pluginId,
		'state': state
	};

	$.ajax({
		async: true,
		type: 'POST',
		url: url,
		data: params,
		dataType: 'json',
		error: function(resp, status, error) {
			console.log(resp);
		}
	});
}

function setFamilyState(policyId, family, state) {
	var url, params;

	url = baseUrl + '/policy/family/state';
	params = {
		'policyId': policyId,
		'family': family,
		'state': state
	};

	$.ajax({
		async: true,
		type: 'POST',
		url: url,
		data: params,
		dataType: 'json',
		error: function(resp, status, error) {
			console.log(resp);
		}
	});
}

function showFamilyDisabled(family) {
	$('#plugins input[name="family"][value="' + family + '"]')
	  .parents('div.pluginFamilyTable')
	  .find('img')
	  .hide()
	  .parents('div.pluginFamilyTable')
	  .find('img.disable')
	  .show();
}

function showFamilyMixed(family) {
	$('#plugins input[name="family"][value="' + family + '"]')
	  .parents('div.pluginFamilyTable')
	  .find('img')
	  .hide()
	  .parents('div.pluginFamilyTable')
	  .find('img.mixed')
	  .show();
}

function getTotalPlugins() {
	return $('#form-edit input[name=totalPlugins]').val();
}

$(document).ready(function () {
	var totalSmbCredentials, defaultPolicyView, totalPlugins,
	  isNew;

	totalSmbCredentials = 0;
	isNew = $('#form-edit input[name=isNew]').val();

	baseUrl = $('#form-edit input[name=base-url]').val();

	$('.icons img, .message, .progress').hide();

	$('.smb-credential, .auth-type, .credential, .advanced').hide();
	$('.first-credential, .first-advanced, #general').show();

	$('#selectCredentials').change(function () {
		var index;

		index = $(this).val();
		$('.credential').hide();
		$('#' + index).show();
	});

	$('#selectAdvanced').change(function () {
		index = $(this).val();

		$('#advanced .advanced').hide();
		$('#' + index).show();
	});

	$('#btn-save').click(function () {
		var baseUrl, url, params;

		baseUrl = $('#form-edit input[name=base-url]').val();
		url = baseUrl + '/policy/modify/save';
		params = $('#form-edit input[name=policyId], #form-submit').serialize();

		$('#main .progress').show();
		$('#btn-save').attr('disabled', 'disabled');

		$.post(
			url,
			params,
			function (data) {
				$('.progress').hide();

				if (data.status === true) {
					/**
					* Set the value of isNew to be empty, or false,
					* to prevent the policy from being deleted when
					* the page is navigated away from
					*/
					$('#form-edit input[name=isNew]').val("");

					window.location = baseUrl + '/policy';
				} else {
					$('#main .progress').hide();
					$('#btn-save').attr('disabled', '');
					$('#save-failure').html(data.message);
					$('#save-failure').show();
				}
			},
			'json'
		);
	});

	$('.smb-credential').each(function () {
		var account, totalSmbCredentials;

		account = $(this).find('.smb-account');
		if (account.val() !== '') {
			$(this).show();
			totalSmbCredentials = totalSmbCredentials + 1;
		}
	});

	$('.add-windows-credential').click(function () {
		if (totalSmbCredentials === 3) {
			return;
		}

		$('.smb-credential:hidden:first').show();
		totalSmbCredentials = totalSmbCredentials + 1;
	});

	$('.remove-windows-credential').click(function () {
		if (totalSmbCredentials === 0) {
			return;
		}

		$(this)
		  .parents('.smb-credential')
		  .hide()
		  .find(':input')
		  .val('');

		totalSmbCredentials = totalSmbCredentials - 1;
	});

	$('.web-app-test-block input[type=checkbox]').click(function () {
		revealWebAppTest(this);
	});

	$('.web-app-test-block input[type=checkbox]').each(function () {
		revealWebAppTest(this);
	});

	$('#db-type').change(function () {
		revealDbAuthType();
	});

	$('#elevate-privs').change(function () {
		revealElevatePrivs();
	});

	$('.row').live('mouseover', function () {
		$(this).find('.icons img').show();
	});
	$('.row').live('mouseout', function () {
		$(this).find('.icons img').hide();
	});

	$('.icons .trash').live('click', function () {
		var row;

		row = $(this).parents('.row');
		row.remove();
	});

	revealDbAuthType();
	revealElevatePrivs();

	/**
	* Handles switching amongst the various lights for the
	* plugin families
	*/
	$('#plugins img.disable, #plugins img.mixed').hide();
	$('#plugins img').live('click', function(){
		var td;

		td = $(this).parents('td');
		policyId = $('#form-plugin input[name=policyId]').val();
		family = $(this).parents('.pluginFamilyTable').find('input[name=family]').val();

		$(this).hide();

		if ($(this).hasClass('enable')) {
			td.find('.disable').show();
			setFamilyState(policyId, family, 'disable');
		} else {
			td.find('.enable').show();
			setFamilyState(policyId, family, 'enable');
		}
	});
	$('#plugins .familyName').live('click', function(){
		var family;

		family = $(this).html();
		isDisabled = $(this).parents('.pluginFamilyTable').find('img.disable').is(':visible');
		if (isDisabled) {
			return;
		}

		listPlugins(family);
		$('#pluginsIndividual .pluginFamilyName').html(family);
		$('#plugins, #pluginsIndividual').toggle();
	});


	$('#pluginsIndividual').hide();
	$('#pluginsIndividual .pluginFamilyNameWrapper').click(function(){
		listFamilies();
		$('#plugins, #pluginsIndividual').toggle();
	});
	$('#individualPluginContent img').live('click', function(){
		var family, td;

		td = $(this).parents('td');
		pluginId = $(this).parents('td').find('input[name=plugin]').val();
		policyId = $('#form-plugin input[name=policyId]').val();

		$(this).hide();

		if ($(this).hasClass('enable')) {
			td.find('.disable').show();
			setPluginState(policyId, pluginId, 'disable');
		} else {
			td.find('.enable').show();
			setPluginState(policyId, pluginId, 'enable');
		}
	});

	if (isNew != 1) {
		$('#plugins input[name="state"][value="disabled"]').each(function(){
			family = $(this).prev().val();
			showFamilyDisabled(family);
		});
		$('#plugins input[name="state"][value="mixed"]').each(function(){
			family = $(this).prev().val();
			showFamilyMixed(family);
		});
	}
});

$(window).bind('beforeunload', function(){
	if (onBeforeUnloadFired === false) {
		var policyId, isNew;

		onBeforeUnloadFired = true;

		policyId = $('#form-edit input[name=policyId]').val();
		isNew = $('#form-edit input[name=isNew]').val();

		if (isNew == 1) {
			console.log('Deleting policy');
			deletePolicy(policyId);
		}
	}
});
