/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

function hasAuthentication() {
	var count;

	count = $('.search-results .content .sortable .row').size();
	if (count > 0) {
		return true;
	} else {
		return false;
	}
}

function updateOrder() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/setup/authentication/order';
	params = $('.sortable .icons input[type=hidden]').serialize();

	$.post(
		url,
		params,
		function (data) {
			if (data.status === true) {
				return;
			} else {
				$('.message .content').html(data.message);
				$('.message').show();
			}
		},
		'json'
	);
}

function searchForAuthentication() {
	var baseUrl, url;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/setup/authentication/search';

	$('.progress').show();

	$.get(
		url,
		$('#search').serialize(),
		function (data) {
			var block = $('.block');

			$('.progress').hide();

			block.find('.search-results .content').html(data);
			block.find('.search-results .content').show();
			block.find('.icons img').hide();
			block.find('input[type=checkbox]').unbind('click').shiftcheckbox();

			$('.sortable').sortable({
				axis: 'y',
				handle: '.order',
				update: function (event, ui) {
					updateOrder();
				}
			});

			if (hasAuthentication()) {
				$('#form-submit-main #btn-save').attr('disabled', false);
			} else {
				$('#form-submit-main input[type=button]').attr('disabled', true);
			}
		},
		'html'
	);
}

function deleteAuthentication(authenticationId) {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/setup/authentication/delete';
	params = { 'authenticationId': authenticationId };

	$('.progress').show();

	$.post(
		url,
		params,
		function (data) {
			$('.progress').hide();

			if (data.status === true) {
				var searchTimeout = $('#form-edit').data('searchTimeout');

				if (searchTimeout !== undefined) {
					clearTimeout(searchTimeout);
				}

				searchTimeout = setTimeout(function () {
					searchForAuthentication();
				}, 300);

				$('#form-edit').data('searchTimeout', searchTimeout);
			} else {
				$('.message .content').html(data.message);
				$('.message').show();
			}
		},
		'json'
	);
}

$(document).ready(function () {
	var baseUrl;
	baseUrl = $('#form-edit input[name=base-url]').val();

	$('.message, .progress').hide();
	$('.block input[type=checkbox]').shiftcheckbox();

	$('.message .close').click(function () {
		$(this).parents('.message').hide();
	});

	$('.selected-delete').click(function () {
		var length, authenticationId, resp;

		length = $('.row input[type=checkbox]:checked').length;
		if (length > 0) {
			resp = confirm('Are you sure you want to delete these authentication types?');
			if (!resp) {
				return false;
			}

			$('.row input[type=checkbox]:checked').each(function () {
				authenticationId = $(this).val();
				deleteAuthentication(authenticationId);
			});
		}
	});

	$('.select-all').click(function () {
		$('.block input[type=checkbox]').attr('checked', 'checked');
	});

	$('.select-none').click(function () {
		$('.block input[type=checkbox]').attr('checked', '');
	});

	$('.icons img.trash').live('click', function () {
		var authenticationId, resp;

		authenticationId = $(this).parents('.row').find('input[name=authenticationId]').val();
		resp = confirm('Are you sure you want to delete this authentication type');
		if (!resp) {
			return false;
		} else {
			deleteAuthentication(authenticationId);
		}
	});

	$('#btn-save').click(function () {
		$('#btn-save').attr('disabled', 'disabled');
		window.location = baseUrl + '/setup/admin';
	});

	$('.row').live('mouseover', function () {
		$(this).find('.icons img').show();
	});
	$('.row').live('mouseout', function () {
		$(this).find('.icons img').hide();
	});

	searchForAuthentication();
});
