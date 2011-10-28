/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window, $*/
/* vim: set ts=4:sw=4:sts=4smarttab:expandtab:autoindent */

function searchForQueues() {
	var baseUrl, url;

	baseUrl = $('#form-edit input[name=baseUrl]').val();
	url = baseUrl + '/admin/queues/search';
	params = {};

	params = $('#form-edit').serialize();

	$('.progress').show();

	$.get(
		url,
		params,
		function (data) {
			$('.progress').hide();

			$('#content').html(data.message);
			$('#content').show();
			$('#content .icons img').hide();

			if (data.totalPages <= 1) {
				$('#pager').hide();
			} else {
				$("#pager").pager({
					pagenumber: data.currentPage,
					pagecount: data.totalPages,
					buttonClickCallback: function(pageClickedNumber){
						$('#form-edit input[name=page]').val(pageClickedNumber);
						searchForQueues();
					}
				}).show();
			}
		},
		'json'
	);
}

function deleteQueue(queueId) {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=baseUrl]').val();
	url = baseUrl + '/admin/queues/delete';
	params = { 'id': queueId };

	$('.progress').show();

	$.post(
		url,
		params,
		function (data) {
			$('.progress').hide();

			if (data.status === true) {
				searchForQueues();
			} else {
				return;
			}
		},
		'json'
	);
}

function flushQueue(queueId) {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=baseUrl]').val();
	url = baseUrl + '/queues/messages/flush';
	params = { 'queueId': queueId };

	$.post(
		url,
		params,
		function (data) {
			if (data.status === true) {
				searchForQueues();
			} else {
				return;
			}
		},
		'json'
	);
}

$(document).ready(function () {
	var baseUrl;

	baseUrl = $('#form-edit input[name=baseUrl]').val();

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
		var queueId, resp;

		queueId = $(this).parents('.row').find('input[name=queueId]').val();
		resp = confirm('Are you sure you want to delete this queue? All messages in the queue will also be deleted');
		if (!resp) {
			return false;
		} else {
			deleteQueue(queueId);
		}
	});

	$('.selected-empty').live('click', function () {
		var resp;

		resp = confirm('Are you sure you want to empty the selected queues? All messages in the queues will be deleted');
		if (!resp) {
			return false;
		} else {
			$('.selectable .row input[type=checkbox]:checked').each(function () {
				var queueId;

				queueId = $(this).val();
				flushQueue(queueId);
			});
		}
	});

	$('.row').live('mouseover', function () {
		$(this).find('.icons img').show();
	});
	$('.row').live('mouseout', function () {
		$(this).find('.icons img').hide();
	});

	searchForQueues();
});
