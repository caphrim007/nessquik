/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

function searchForCapabilities() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/admin/roles/search-permission';
	params = $('#permission-capability .search').serialize();

	$('#dialog .progress').show();

	$.get(
		url,
		params,
		function (data) {
			var tab;

			$('#dialog .progress').hide();
			tab = $('#permission-capability');
			tab.find('.search-results .content').html(data);
			tab.find('.search-results .content').show();
			tab.find('.icons img').hide();
			tab.find('.block input[type=checkbox]').unbind('click');
			tab.find('.block input[type=checkbox]').shiftcheckbox();
		},
		'html'
	);
}

function searchForNetworks() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/admin/roles/search-permission';
	params = $('#permission-network .search').serialize();

	$('#dialog .progress').show();

	$.get(
		url,
		params,
		function (data) {
			var tab;

			$('#dialog .progress').hide();
			tab = $('#permission-network');
			tab.find('.search-results .content').html(data);
			tab.find('.search-results .content').show();
			tab.find('.icons img').hide();
			tab.find('.block input[type=checkbox]').unbind('click');
			tab.find('.block input[type=checkbox]').shiftcheckbox();
		},
		'html'
	);
}

function searchForHostnames() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/admin/roles/search-permission';
	params = $('#permission-hostname .search').serialize();

	$('#dialog .progress').show();

	$.get(
		url,
		params,
		function (data) {
			var tab;

			$('#dialog .progress').hide();
			tab = $('#permission-hostname');
			tab.find('.search-results .content').html(data);
			tab.find('.search-results .content').show();
			tab.find('.icons img').hide();
			tab.find('.block input[type=checkbox]').unbind('click');
			tab.find('.block input[type=checkbox]').shiftcheckbox();
		},
		'html'
	);
}

function deletePermission(type, permissionId) {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/admin/roles/delete-permission';
	params = { 'type': type, 'permissionId': permissionId };

	$.post(
		url,
		params,
		function (data) {
			var searchTimeout, type, permissionId;

			searchTimeout = $('#form-edit').data('searchTimeout');

			if (data.status === true) {
				if (searchTimeout !== undefined) {
					clearTimeout(searchTimeout);
				}

				type = data.type;
				permissionId = data.permissionId;

				searchTimeout = setTimeout(function () {
					if (type === 'capability') {
						searchForCapabilities();
					} else if (type === 'network') {
						searchForNetworks();
					} else if (type === 'hostname') {
						searchForHostnames();
					}

					$('ol.selectable li.' + permissionId).remove();
				}, 600);

				$('#form-edit').data('searchTimeout', searchTimeout);
			} else {
				return;
			}
		},
		'json'
	);
}

$(document).ready(function () {
	var baseUrl;

	baseUrl = $('#form-edit input[name=base-url]').val();

	$('.error, .success, .message, .progress').hide();
	$('#steps').tabs();

//
// TODO, add infinite scrolling for hostname, network, capability, and cluster fields
//
//	$('.network-target div.permissions ol').scroll(function(){
//		block = $('.network-target div.permissions ol');
//
//		console.log(block.scrollTop());
//		console.log(block.height());
//
//		if  ($('div.permissions ol').scrollTop() == $('div.permissions ol').height() - $('div.permissions ol').height()){
//			console.log("ASDSAD");
//		}
//	}); 

	$('.available li').click(function () {
		var itemClone, topDiv, origImg, origImgSrc, origImgNewSrc,
		    origInputName, origInputNewName, selected, existing, img,
		    newImgSrc;

		itemClone = $(this).clone();
		topDiv = $(this).parents('div.permissions');

		origImg = $(this).find('img');
		origImgSrc = origImg.attr('src');
		origImgNewSrc = origImgSrc.replace('forward.png', 'forward-selected.png');

		origInputName = itemClone.find('input[type=hidden]').attr('name');
		origInputNewName = origInputName.replace('available', 'selected');

		selected = $(this).attr('class');
		existing = topDiv.find('.selected li[class=' + selected + ']');

		if (existing.size() > 0) {
			return;
		}

		itemClone.find('input[type=hidden]').attr('name', origInputNewName);
		origImg.attr('src', origImgNewSrc);
		img = $(itemClone).find('img');
		newImgSrc = img.attr('src').replace('forward.png', 'back.png');

		img.attr('src', newImgSrc);

		topDiv.find('.selected').append(itemClone);
	});

	$('.add-all').click(function () {
		var topDiv;

		topDiv = $(this).parents('div.permission-block');
		topDiv.find('ol.available li').trigger('click');
	});

	$('.clear-all').click(function () {
		var topDiv;

		topDiv = $(this).parents('div.permission-block');
		topDiv.find('ol.selected li').trigger('click');
	});

	$('.selected li').live("click", function () {
		var topDiv, selected, existing, origImg, origImgSrc, origImgNewSrc;

		topDiv = $(this).parents('div.permissions');
		selected = $(this).attr('class');
		existing = topDiv.find('.selected li[class=' + selected + ']');

		if (existing.size() > 0) {
			$(this).remove();

			origImg = topDiv.find('.available li[class=' + selected + '] img');
			origImgSrc = origImg.attr('src');
			origImgNewSrc = origImgSrc.replace('forward-selected.png', 'forward.png');
			origImg.attr('src', origImgNewSrc);
		}
	});

	$('#dialog').dialog({
		autoOpen: false,
		bgiframe: true,
		dialogClass: 'permissions-dialog',
		draggable: false,
		resizable: false,
		height: "auto",
		modal: true,
		position: ['center', 'center'],
		width: 830
	});

	$('.search-dialog-link').click(function () {
		$('#dialog').dialog('open');

		searchForCapabilities();
		searchForHostnames();
		searchForNetworks();
	});
	$('.search-dialog-close').click(function () {
		$('#dialog').dialog('close');
	});

	$('#btn-save').click(function () {
		var url, params;

		url = baseUrl + '/admin/roles/save';
		params = $('#form-submit').serialize();

		$('#form-ops .progress').show();
		$('#btn-save').attr('disabled', 'disabled');

		$.post(
			url,
			params,
			function (data) {
				$('#form-ops .progress').hide();

				if (data.status === true) {
					window.location = baseUrl + '/admin/roles';
				} else {
					$('#btn-save').attr('disabled', '');
					$('.error').show();
				}
			},
			'json'
		);
	});

	$('#permission-capability input[type=button][name=add]').click(function () {
		var permission, url, params, button;

		permission = $('#permission-capability input[name=permission]').val();
		url = baseUrl + '/admin/roles/add-permission';
		params = { 'permission': permission, 'type': 'capability' };

		button = $(this);
		button.attr('disabled', 'disabled');
		$('#dialog .progress').show();

		$.post(
			url,
			params,
			function (data) {
				$('#dialog .progress').hide();
				button.attr('disabled', '');

				if (data.status === true) {
					searchForCapabilities();
				} else {
					$('#permission-capability .message .content').html(data.message);
					$('#permission-capability .message').show();
				}
			},
			'json'
		);
	});
	$('#permission-capability .selected-delete').click(function () {
		var resp;

		resp = confirm('Are you sure you want to delete the selected capabilities?');
		if (!resp) {
			return false;
		} else {
			$('#permission-capability .row input[type=checkbox]:checked').each(function () {
				var permissionId;

				permissionId = $(this).val();
				deletePermission('capability', permissionId);
			});
		}
	});

	$('#permission-network input[type=button][name=add]').click(function () {
		var permission, url, params, button;

		permission = $('#permission-network input[name=permission]').val();
		url = baseUrl + '/admin/roles/add-permission';
		params = { 'permission': permission, 'type': 'network' };

		button = $(this);
		button.attr('disabled', 'disabled');
		$('#dialog .progress').show();

		$.post(
			url,
			params,
			function (data) {
				$('#dialog .progress').hide();
				button.attr('disabled', '');

				if (data.status === true) {
					searchForNetworks();
				} else {
					$('#permission-network .message .content').html(data.message);
					$('#permission-network .message').show();
				}
			},
			'json'
		);
	});
	$('#permission-network .selected-delete').click(function () {
		var resp;

		resp = confirm('Are you sure you want to delete the selected networks?');
		if (!resp) {
			return false;
		} else {
			$('#permission-network .row input[type=checkbox]:checked').each(function () {
				var permissionId;

				permissionId = $(this).val();
				deletePermission('network', permissionId);
			});
		}
	});

	$('#permission-hostname input[type=button][name=add]').click(function () {
		var permission, url, params, button;

		permission = $('#permission-hostname input[name=permission]').val();
		url = baseUrl + '/admin/roles/add-permission';
		params = { 'permission': permission, 'type': 'hostname' };

		button = $(this);
		button.attr('disabled', 'disabled');
		$('#dialog .progress').show();

		$.post(
			url,
			params,
			function (data) {
				$('#dialog .progress').hide();
				button.attr('disabled', '');

				if (data.status === true) {
					searchForHostnames();
				} else {
					$('#permission-hostname .message .content').html(data.message);
					$('#permission-hostname .message').show();
				}
			},
			'json'
		);
	});
	$('#permission-hostname .selected-delete').click(function () {
		var resp;

		resp = confirm('Are you sure you want to delete the selected hostnames?');
		if (!resp) {
			return false;
		} else {
			$('#permission-hostname .row input[type=checkbox]:checked').each(function () {
				var permissionId;

				permissionId = $(this).val();
				deletePermission('hostname', permissionId);
			});
		}
	});

	$('.row').live('mouseover', function () {
		$(this).find('.icons img').show();
	});
	$('.row').live('mouseout', function () {
		$(this).find('.icons img').hide();
	});

	$('#permission-capability .show-more').live('click', function () {
		var page, oldPage, oldPageNum, newPage;

		page = $('#permission-capability .search input[name=page]');
		oldPage = $('#permission-capability .search input[name=old-page]');
		oldPageNum = oldPage.val();
		newPage = parseInt(oldPageNum, 10) + 1;

		oldPage.val(newPage);
		page.val(newPage);
		searchForCapabilities();
	});
	$('#permission-capability .show-less').live('click', function () {
		var page, oldPage, oldPageNum, newPage;

		page = $('#permission-capability .search input[name=page]');
		oldPage = $('#permission-capability .search input[name=old-page]');
		oldPageNum = oldPage.val();
		newPage = parseInt(oldPageNum, 10) - 1;

		oldPage.val(newPage);
		page.val(newPage);
		searchForCapabilities();
	});
	$('#permission-hostname .show-more').live('click', function () {
		var page, oldPage, oldPageNum, newPage;

		page = $('#permission-hostname .search input[name=page]');
		oldPage = $('#permission-hostname .search input[name=old-page]');
		oldPageNum = oldPage.val();
		newPage = parseInt(oldPageNum, 10) + 1;

		oldPage.val(newPage);
		page.val(newPage);
		searchForHostnames();
	});
	$('#permission-hostname .show-less').live('click', function () {
		var page, oldPage, oldPageNum, newPage;

		page = $('#permission-hostname .search input[name=page]');
		oldPage = $('#permission-hostname .search input[name=old-page]');
		oldPageNum = oldPage.val();
		newPage = parseInt(oldPageNum, 10) - 1;

		oldPage.val(newPage);
		page.val(newPage);
		searchForHostnames();
	});
	$('#permission-network .show-more').live('click', function () {
		var page, oldPage, oldPageNum, newPage;

		page = $('#permission-network .search input[name=page]');
		oldPage = $('#permission-network .search input[name=old-page]');
		oldPageNum = oldPage.val();
		newPage = parseInt(oldPageNum, 10) + 1;

		oldPage.val(newPage);
		page.val(newPage);
		searchForNetworks();
	});
	$('#permission-network .show-less').live('click', function () {
		var page, oldPage, oldPageNum, newPage;

		page = $('#permission-network .search input[name=page]');
		oldPage = $('#permission-network .search input[name=old-page]');
		oldPageNum = oldPage.val();
		newPage = parseInt(oldPageNum, 10) - 1;

		oldPage.val(newPage);
		page.val(newPage);
		searchForNetworks();
	});

	$('.select-all').click(function () {
		$(this).parents('.tab-block')
		  .find('input[type=checkbox]')
		  .attr('checked', 'checked');
	});

	$('.select-none').click(function () {
		$(this).parents('.tab-block')
		  .find('input[type=checkbox]')
		  .attr('checked', '');
	});

	$('.icons img.trash').live('click', function () {
		var type, permissionId, resp;

		type = $(this).parents('.icons').find('input[name=type]').val();
		permissionId = $(this).parents('.icons').find('input[name=permissionId]').val();

		resp = confirm('Are you sure you want to delete this permission?');
		if (!resp) {
			return false;
		} else {
			deletePermission(type, permissionId);
		}
	});

	$('.message .close').click(function () {
		$(this).parents('.message').hide();
	});
});
