/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

function emailReport() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/audit/report/email';
	params = $('#form-edit input[name=auditId], #form-edit input[name=reportId], #reports-email form').serialize();

	$('#reports-email .progress').show();

	$.post(
		url,
		params,
		function (data) {
			$('#reports-email .progress').hide();

			if (data.status === true) {
				$('#reports-email').dialog('close');
				$('#reports-email .btn-save').attr('disabled', '');
				$('#reports-email form')[0].reset();

				$('#reports-email .message.success .content').html(data.message);
				$('#reports-email .message.success').show();

				setTimeout(function () {
					$('#reports-email .message.success').fadeOut('slow');
				}, 2500);
			} else {
				$('#reports-email .recipient input[type=text]').each(function (item) {
					var currentVal = $(this).val();
					if (currentVal === '') {
						$(this).trigger('blur');
					}
				});

				$('#reports-email .btn-save').attr('disabled', '');
				$('#reports-email .message.error .content').html(data.message);
				$('#reports-email .message.error').show();
				return;
			}
		},
		'json'
	);
}

$(document).ready(function () {
	var baseUrl, auditName, reportId, totalRecipients;

	baseUrl = $('#form-edit input[name=base-url]').val();
	totalRecipients = 0;

	$('#form-edit').data('searchTimeout', undefined);

	$('#reports-email .format-example, #reports-email .send-to-others').hide();
	$('#reports-email .format-example.nessus').show();
	$('#reports-email .recipient').hide();

	$('#reports-email').dialog({
		autoOpen: false,
		bgiframe: true,
		dialogClass: 'email-dialog',
		draggable: false,
		resizable: false,
		height: "auto",
		modal: true,
		position: ['center', 'center'],
		width: 710
	});

	$('#reports .icons img.email').live('click', function () {
		reportId = $(this).parents('.row').find('input[name=reportId]').val();
		auditName = $(this).parents('.row').find('.name span').html();
		$('#form-edit input[name=reportId]').val(reportId);
		$('#reports-email .audit-report-name').html(auditName);
		$('#reports-email input[name=send-to-others]').trigger('click');
		$('#reports-email').dialog('open');
	});

	$('#reports-email .reports-email-close').click(function () {
		$('#reports-email').dialog('close');
	});

	$('#reports-email .report-format').change(function () {
		var format = $(this).val();

		$('#reports-email .format-example').hide();
		$('#reports-email .' + format).show();
	});

	$('#reports-email input[name=send-to-others]').click(function () {
		var value = $(this).val();
		if (value === 'yes') {
			$('#reports-email .send-to-others').show();
		} else {
			$('#reports-email .send-to-others').hide();
		}
	});

	$('#reports-email .recipient input[type=text]').watermark('email address');

	$('#reports-email .add-recipient').click(function () {
		if (totalRecipients === 3) {
			return;
		}

		$('.recipient:hidden:first').fadeIn('slow');
		totalRecipients = totalRecipients + 1;
	});
	$('#reports-email .add-recipient').trigger('click');

	$('#reports-email .recipient .icons img.trash').click(function () {
		if (totalRecipients <= 1) {
			return;
		}

		var recipient = $(this).parents('.recipient');
		recipient.hide();

		recipient.find(':input').val('');
		recipient.find(':input').trigger('blur');

		totalRecipients = totalRecipients - 1;
	});

	$('#reports-email .btn-save').click(function () {
		$(this).attr('disabled', 'disabled');

		$('#reports-email .recipient input[type=text]').each(function (item) {
			var currentVal = $(this).val();
			if (currentVal === 'email address') {
				$(this).val('');
			}
		});

		emailReport();
	});

	$('#reports-email .report-format').trigger('change');
});
