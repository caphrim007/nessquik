/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

function searchForRoles() {
	var baseUrl, url;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/admin/roles/search';

	$('.progress').show();

	$.get(
		url,
		$('#form-edit').serialize(),
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
						searchForRoles();
					}
				}).show();
			}
		},
		'json'
	);
}

function deleteRole(roleId) {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/admin/roles/delete';
	params = { 'roleId': roleId };

	$.post(
		url,
		params,
		function (data) {
			if (data.status === true) {
				var searchTimeout;

				searchTimeout = $('#form-edit').data('searchTimeout');
				if (searchTimeout !== null) {
					clearTimeout(searchTimeout);
				}

				searchTimeout = setTimeout(function () {
					searchForRoles();
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
	$('.role-list .progress').hide();
	$('.role-checkbox').live('click', function () {
		var count;

		count = $('.row input[type=checkbox]:checked').length;
		if (count > 0) {
			$('#with-selected').show();
		} else {
			$('#with-selected').hide();
		}
	});

	$('.icons img.trash').live('click', function () {
		var roleId, resp;

		roleId = $(this).parents('.row').find('input[name=roleId]').val();
		resp = confirm('Are you sure you want to delete this role?');
		if (!resp) {
			return false;
		}

		deleteRole(roleId);
	});

	$('.row').live('mouseover', function () {
		$(this).find('.icons img').show();
	});
	$('.row').live('mouseout', function () {
		$(this).find('.icons img').hide();
	});

	searchForRoles();
});
