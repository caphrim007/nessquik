/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

function switchAuthBlock() {
	var index;

	index = $('#auth-type').val();

	$('.authentication-block').hide();
	$('#' + index).show();
}

$(document).ready(function () {
	var useEncryption;

	useEncryption = $('.encryption-block input[name=encryption-type]').is(':checked');

	$('.message, .authentication-block').hide();

	$('#auth-type').change(function () {
		switchAuthBlock();
	});

	$('#btn-save').click(function () {
		var baseUrl, url, method, params;

		baseUrl = $('#form-edit input[name=base-url]').val();
		url = baseUrl + '/admin/authentication/save';
		method = $('#auth-type').val();
		params = $('#form-submit, #' + method + ' :input').serialize();

		$.post(
			url,
			params,
			function (data) {
				if (data.status === true) {
					window.location = baseUrl + '/admin/authentication';
				} else {
					$('.message .content').html(data.message);
					$('.message').show();
				}
			},
			'json'
		);
	});

	if (useEncryption) {
		$('input[name=use-encryption]').attr('checked', 'checked');
		$('.encryption-block').show();
	} else {
		$('.encryption-block').hide();
	}

	$('input[name=use-encryption]').click(function () {
		var block, defaultPort;

		block = $('.encryption-block');
		defaultPort = $('#Ldap input[name=port]').val();

		if (block.is(':hidden')) {
			block.find('input[value=useSsl]').attr('checked', 'checked');
			block.show();
			if (defaultPort === '' || defaultPort === '389') {
				$('#Ldap input[name=port]').val('636');
			}
		} else {
			block.hide();
			block.find('input[name=encryption-type]').attr('checked', '');
			if (defaultPort === '' || defaultPort === '636') {
				$('#Ldap input[name=port]').val('389');
			}
		}
	});

	$('.message .close').click(function () {
		$(this).parents('.message').hide();
	});

	switchAuthBlock();
});
