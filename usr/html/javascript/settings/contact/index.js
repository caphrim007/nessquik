/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

$(document).ready(function () {
	var baseUrl, totalEmailRecipients, totalXmppRecipients;

	baseUrl = $('#form-edit input[name=baseUrl]').val();
	totalEmailRecipients = $('#email-block div.list .recipient').size();
	totalXmppRecipients = $('#messaging-block div.list .recipient').size();

	$('.block .scaffolding, .message, .progress').hide();

	$('#btn-save').click(function () {
		var url, params;

		url = baseUrl + '/settings/contact/save';
		params = $('#form-submit').serialize();

		$('.progress').show();
		$('#btn-save').attr('disabled', 'disabled');

		$.post(
			url,
			params,
			function (data) {
				$('.progress').show();

				if (data.status === true) {
					window.location = baseUrl + '/settings/modify/edit';
				} else {
					$('#btn-save').attr('disabled', '');
					$('.message .content').html(data.message);
					$('.message').show();
				}
			},
			'json'
		);
	});

	$('#email-block .recipient input[type=text]').watermark('email address');
	$('#email-block .add-recipient').live('click', function () {
		if (totalEmailRecipients === 3) {
			return;
		}

		$('#scaffolding .email').clone().appendTo('#email-block .list');
		$('#email-block .email input[type=text]').watermark('email address');
		totalEmailRecipients = totalEmailRecipients + 1;
	});
	$('#email-block .recipient .icons img.trash').live('click', function () {
		if (totalEmailRecipients <= 1) {
			return;
		}

		var recipient = $(this).parents('.recipient');
		recipient.hide();

		recipient.find(':input').val('');
		recipient.find(':input').trigger('blur');

		totalEmailRecipients = totalEmailRecipients - 1;
	});

	$('#messaging-block .recipient input[type=text]').watermark('messenger name');
	$('#messaging-block .add-recipient').live('click', function () {
		if (totalXmppRecipients === 3) {
			return;
		}

		$('#scaffolding .xmpp').clone().appendTo('#messaging-block .list');
		$('#messaging-block .xmpp input[type=text]').watermark('messenger name');
		totalXmppRecipients = totalXmppRecipients + 1;
	});
	$('#messaging-block .recipient .icons img.trash').live('click', function () {
		if (totalXmppRecipients <= 1) {
			return;
		}

		var recipient = $(this).parents('.recipient');
		recipient.hide();

		recipient.find(':input').val('');
		recipient.find(':input').trigger('blur');

		totalXmppRecipients = totalXmppRecipients - 1;
	});
});
