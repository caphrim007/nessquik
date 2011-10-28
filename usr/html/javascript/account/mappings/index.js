/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

function searchForMappings() {
	var baseUrl, url;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/account/mappings/search';

	$('#heading .progress').show();

	$.get(
		url,
		$('#search').serialize(),
		function (data) {
			$('#heading .progress').hide();

			$('#content').html(data.message);
			$('#content').show();
			$('#content .icons img').hide();
			$('#content input[type=checkbox]').unbind('click').shiftcheckbox();
		},
		'json'
	);
}

function deleteMapping(mappingId) {
	var baseUrl, url, accountId, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/account/mappings/delete';
	accountId = $('#form-edit input[name=accountId]').val();
	params = { 'accountId': accountId, 'mapId': mappingId };

	$.post(
		url,
		params,
		function (data) {
			if (data.status === true) {
				searchForMappings();
			} else {
				$('.message').hide();
				$('#notices .error').html(data.message);
				$('#notices .error').show();
			}
		},
		'json'
	);
}

function createMapping() {
	var baseUrl, url, params, mapName;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/account/mappings/save';
	params = $('#form-submit').serialize();
	mapName = $('#form-submit input[name=map-name]');
	accountId = $('#form-edit input[name=accountId]').val();

	if (mapName.val() === '') {
		return;
	}

	$('#dialog .progress').show();
	$('#dialog .create-dialog-save').attr('disabled', 'disabled');

	$.post(
		url,
		params,
		function (data) {
			$('#dialog .progress').show();

			if (data.status === true) {
				window.location = baseUrl + '/account/mappings?accountId=' + accountId;
			} else {
				$('#create-mapping-body .error').html(data.message);
				$('#create-mapping-body .error').show();
			}
		},
		'json'
	);
}

$(document).ready(function () {
	$('.role-row img, #create-mapping-body .error').hide();
	$('.message, .trash-icon img, .progress').hide();

	$('.role-row').hover(
		function () {
			$(this).find('.trash-icon img').show();
		},
		function () {
			$(this).find('.trash-icon img').hide();
		}
	);

	$('#dialog').dialog({
		autoOpen: false,
		bgiframe: true,
		dialogClass: 'mapping-dialog',
		draggable: false,
		resizable: false,
		height: "auto",
		modal: true,
		width: 500
	});

	$('.create-mapping').live('click', function () {
		$('#dialog').dialog('open');
	});
	$('.create-dialog-close').click(function () {
		$('#dialog').dialog('close');
	});

	$('.create-dialog-save').click(function () {
		createMapping();
	});

	$('.icons img.trash').live('click', function () {
		var mappingId, resp;

		mappingId = $(this).parents('.row').find('input[name=mapId]').val();
		resp = confirm('Are you sure you want to delete the selected account mappings?');
		if (!resp) {
			return false;
		}

		deleteMapping(mappingId);
	});

	$('#form-mapping input[name=map-name]').keypress(function (event) {
		if (event.which === 13) {
			event.preventDefault();
			createMapping();
			return true;
		}
	});

	$('.row').live('mouseover', function () {
		$(this).find('.icons img').show();
	});
	$('.row').live('mouseout', function () {
		$(this).find('.icons img').hide();
	});

	$('.select-all').click(function () {
		$('.selectable input[type=checkbox]').attr('checked', 'checked');
	});
	$('.select-none').click(function () {
		$('.selectable input[type=checkbox]').attr('checked', '');
	});
	$('.selected-delete').click(function () {
		var resp = confirm('Are you sure you want to delete the selected account mappings?');
		if (!resp) {
			return false;
		}

		$('.selectable .row input[type=checkbox]:checked').each(function () {
			var mappingId = $(this).val();
			deleteMapping(mappingId);
		});
	});

	searchForMappings();
});
