/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

function setNotification() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/audit/modify/set-notification';
	params = $('#form-notification, #form-edit input[name=auditId]').serialize();

	$.post(
		url,
		params,
		function (data) {
			if (data.status === false) {
				$('#steps').tabs('select', '#notifications');
				$('#notifications .message.error .content').html(data.message);
				$('#notifications .message.error').show();
			}
		},
		'json'
	);
}

$(document).ready(function () {
	var searchTimeout, baseUrl, totalRecipients;

	searchTimeout = undefined;
	baseUrl = $('#form-edit input[name=base-url]').val();
	totalRecipients = 0;

	$('#form-edit').data('searchTimeout', undefined);

	$('#notifications .format-example, #notifications .send-to-others').hide();
	$('#notifications .format-example .nessus').show();
	totalRecipients = $('#recipientList div.recipient').size();

	$('#notifications .report-format').change(function () {
		var format = $(this).val();

		$('#notifications .format-example').hide();
		$('#notifications .' + format).show();
	});

	$('#notifications input[name=sendToOthers]').click(function () {
		var value = $(this).val();
		if (value === 'yes') {
			$('#notifications .send-to-others').show();
		} else {
			$('#notifications .send-to-others').hide();
		}
	});

	$('#notifications .recipient input[type=text]').watermark('email address');

	$('#notifications .add-recipient').click(function () {
		if (totalRecipients === 3) {
			return;
		}

		$('#scaffolding .recipient').clone().appendTo('#recipientList');
		$('#notifications .recipient input[type=text]').watermark('email address');
		totalRecipients = totalRecipients + 1;
	});

	$('#notifications .recipient .icons img.trash').live('click', function () {
		if (totalRecipients <= 1) {
			return;
		}

		var recipient = $(this).parents('.recipient');
		recipient.hide();

		recipient.find(':input').val('');
		recipient.find(':input').trigger('blur');

		totalRecipients = totalRecipients - 1;
	});

	$('#notifications .report-format').trigger('change');
	$('#notifications input[name=sendToOthers]:checked').trigger('click');
});
