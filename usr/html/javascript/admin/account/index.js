/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

function searchForAccounts() {
	var baseUrl, url;

	baseUrl = $('#form-edit input[name=baseUrl]').val();
	url = baseUrl + '/admin/account/search';

	$('.progress').show();

	$.get(
		url,
		$('#search, #form-search').serialize(),
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
						searchForAccounts();
					}
				}).show();
			}
		},
		'json'
	);
}

function deleteAccount(accountId) {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=baseUrl]').val();
	url = baseUrl + '/admin/account/delete';
	params = { 'id': accountId };

	$('.progress').show();

	$.post(
		url,
		params,
		function (data) {
			var searchTimeout;

			$('.progress').hide();

			if (data.status === true) {
				searchForAccounts();
			} else {
				return;
			}
		},
		'json'
	);
}

$(document).ready(function () {
	var baseUrl = $('#form-edit input[name=baseUrl]').val();

	$('.select-all').click(function () {
		var block;

		block = $(this).parents('.block');
		block.find('input[type=checkbox]').attr('checked', 'checked');
		block.find('.select-every').show();
	});
	$('.select-none').click(function () {
		$(this).parents('.block')
		  .find('input[type=checkbox]')
		  .attr('checked', '');
	});

	$('.icons img.trash').live('click', function () {
		var accountId, resp, message;

		accountId = $(this).parents('.row').find('input[name=accountId]').val();
		message = 'Are you sure you want to delete this account?' +
		  ' Everything associated with the account (policies, audits, etc) will also be deleted';

		resp = confirm(message);
		if (!resp) {
			return false;
		} else {
			deleteAccount(accountId);
		}
	});

	$('.selected-delete').live('click', function () {
		var resp, message;

		message = 'Are you sure you want to delete the selected accounts?' +
		  ' Everything associated with the account (policies, audits, etc) will also be deleted';

		resp = confirm(message);
		if (!resp) {
			return false;
		} else {
			$('.selectable .row input[type=checkbox]:checked').each(function () {
				var accountId;

				accountId = $(this).val();
				deleteAccount(accountId);
			});
		}
	});

	$('.row').live('mouseover', function () {
		$(this).find('.icons img').show();
	});
	$('.row').live('mouseout', function () {
		$(this).find('.icons img').hide();
	});

	$('.show-more').live('click', $.debounce(250, true, function () {
		var page, oldPage, oldPageNum, newPage;

		page = $('#search input[name=page]');
		oldPage = $('#search input[name=old-page]');
		oldPageNum = oldPage.val();
		newPage = parseInt(oldPageNum, 10) + 1;

		oldPage.val(newPage);
		page.val(newPage);
		searchForAccounts();
	}));
	$('.show-less').live('click', $.debounce(250, true, function () {
		var page, oldPage, oldPageNum, newPage;

		page = $('#search input[name=page]');
		oldPage = $('#search input[name=old-page]');
		oldPageNum = oldPage.val();
		newPage = parseInt(oldPageNum, 10) - 1;

		oldPage.val(newPage);
		page.val(newPage);
		searchForAccounts();
	}));

	$('#form-search input[name=filter]').keyup(function (event) {
		var searchTimeout;

		if (event.which === 13) {
			event.preventDefault();
			return false;
		} else {
			if (searchTimeout !== undefined) {
				clearTimeout(searchTimeout);
			}

			$('#search input[type=hidden]').val(1);

			searchTimeout = setTimeout(function () {
				searchForAccounts();
			}, 300);
		}
	});

	$('#form-search input[name=filter]').keypress(function (event) {
		if (event.which === 13) {
			event.preventDefault();
			return false;
		}
	});

	$('#searchMenu').hide();
	$('#searchLink').click(function(){
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

	searchForAccounts();
});
