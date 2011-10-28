/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

function searchForMessages() {
	var baseUrl, url;

	baseUrl = $('#form-edit input[name=baseUrl]').val();
	url = baseUrl + '/queues/messages/search';

	$('.progress').show();

	$.get(
		url,
		$('#search,#form-edit input[name=queueId]').serialize(),
		function (data) {
			$('.search-results .content').html(data);
			$('.search-results .content').show();
			$('.progress').hide();
			$('.icons img').hide();
			$('.block input[type=checkbox]').unbind('click').shiftcheckbox();
			$('.select-mesg, .select-all-mesg, .select-all-in-queue-mesg').hide();
			$('.select-all-in-queue').data('flush-queue', false);
		},
		'html'
	);
}

function deleteMessage(messageId) {
	var baseUrl, url, queueId, params;

	baseUrl = $('#form-edit input[name=baseUrl]').val();
	url = baseUrl + '/queues/messages/delete';
	queueId = $('#form-edit input[name=queueId]').val();
	params = { 'queueId': queueId, 'messageId': messageId };

	$.post(
		url,
		params,
		function (data) {
			if (data.status === true) {
				var searchTimeout = $('#form-edit').data('searchTimeout');
				if (searchTimeout !== undefined) {
					clearTimeout(searchTimeout);
				}

				searchTimeout = setTimeout(function () {
					searchForMessages();
				}, 300);

				$('#form-edit').data('searchTimeout', searchTimeout);
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
				var searchTimeout = $('#form-edit').data('searchTimeout');
				if (searchTimeout !== undefined) {
					clearTimeout(searchTimeout);
				}

				searchTimeout = setTimeout(function () {
					searchForMessages();
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
	var baseUrl, searchTimeout;

	baseUrl = $('#form-edit input[name=baseUrl]').val();
	searchTimeout = undefined;

	$('.error, .success, .progress').hide();
	$('.select-mesg, .select-all-mesg, .select-all-in-queue-mesg').hide();

	$('.message, .config').hide();
	$('.message .close').click(function () {
		$(this).parents('.message').hide();
	});

	$('#btn-save').click(function () {
		var url, params;

		url = baseUrl + '/queues/modify/save';
		params = $('#form-settings').serialize();

		$('.progress').show();
		$('#btn-save').attr('disabled', 'disabled');

		$.post(
			url,
			params,
			function (data) {
				$('.progress').hide();

				if (data.status === true) {
					window.location = baseUrl + '/admin/queues';
				} else {
					$('#btn-save').attr('disabled', '');
					$('.message .content').html(data.message);
					$('.message').show();
				}
			},
			'json'
		);
	});

	$('.select-all').click(function () {
		var block;

		block = $(this).parents('.block');
		block.find('input[type=checkbox]').attr('checked', 'checked');
		block.find('.row').addClass('highlighted');
		block.find('.select-every').show();

		$('.select-mesg, .select-all-mesg').show();
	});
	$('.select-none').live('click', function () {
		var block;

		block = $(this).parents('.block');
		block.find('input[type=checkbox]').attr('checked', '');
		block.find('.row').removeClass('highlighted');
		$('.select-mesg, .select-all-mesg, .select-all-in-queue-mesg').hide();
		$('.select-all-in-queue').data('flush-queue', false);
	});

	$('.icons img.trash').live('click', function (ev) {
		var messageId, resp;

		messageId = $(this).parents('.row').find('input[name=messageId]').val();
		resp = confirm('Are you sure you want to delete this message?');
		if (!resp) {
			return false;
		} else {
			deleteMessage(messageId);
		}
	});

	$('.selected-delete').live('click', function () {
		var resp, shouldFlushQueue, queueId, messageId;

		shouldFlushQueue = $('.select-all-in-queue').data('flush-queue');
		if (shouldFlushQueue === true) {
			resp = confirm('Are you sure you want to delete all the messages in the queue?');
			if (!resp) {
				return false;
			} else {
				queueId = $('#form-edit input[name=queueId]').val();
				flushQueue(queueId);
			}
		} else {
			resp = confirm('Are you sure you want to delete the selected messages?');
			if (!resp) {
				return false;
			} else {
				$('.selectable .row input[type=checkbox]:checked').each(function () {
					messageId = $(this).val();
					deleteMessage(messageId);
				});
			}
		}
	});

	$('.row').live('mouseover', function () {
		$(this).find('.icons img').show();
	});
	$('.row').live('mouseout', function () {
		$(this).find('.icons img').hide();
	});

	$('.selectable .row input[type=checkbox]').live('click', function () {
		var totalCheckbox, totalChecked;

		totalCheckbox = $('.selectable .row input[type=checkbox]').size();
		totalChecked = $('.selectable .row input[type=checkbox]:checked').size();

		if (totalChecked < totalCheckbox) {
			$('.select-mesg, .select-all-mesg, .select-all-in-queue-mesg').hide();
			$('.select-all-in-queue').data('flush-queue', false);
		}

		$(this).parents('.row').toggleClass('highlighted');
	});

	if ($('#form-edit input[name=queueId]').val() !== '_new') {
		searchForMessages();
	}

	$('.select-all-in-queue').live('click', function () {
		$('.select-all-mesg').hide();
		$('.select-all-in-queue-mesg').show();
		$('.select-all-in-queue').data('flush-queue', true);
	});

	$('#settings').show();
});
