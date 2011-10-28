/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$,searchForAuthentication*/

function switchAuthBlock() {
	var index;

	index = $('#auth-type').val();
	$('.authentication-block').hide();
	$('#' + index).show();
	$('.encryption-block').hide();
}

function getAuthentication(authenticationId) {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/setup/authentication/edit';
	params = { 'id': authenticationId };

	$.get(
		url,
		params,
		function (data) {
			$('#dialog .content').html(data);
			$('#dialog .message, .authentication-block').hide();
			switchAuthBlock();
		},
		'html'
	);
}

$(document).ready(function () {
	$('#form-edit').data('useEncryption', false);

	$('#auth-type').live('change', function () {
		switchAuthBlock();
	});

	$('#dialog').dialog({
		autoOpen: false,
		bgiframe: true,
		dialogClass: 'authentication-dialog',
		draggable: false,
		resizable: false,
		height: "auto",
		modal: true,
		position: ['center', 'center'],
		width: 830
	});

	$('.dialog-link').click(function () {
		getAuthentication('_new');
		$('#form-edit').data('useEncryption', false);
		$('#dialog').dialog('open');
	});
	$('.dialog-close').click(function () {
		$('#dialog').dialog('close');
	});

	$('#dialog .btn-save').click(function () {
		var baseUrl, url, method, params;

		baseUrl = $('#form-edit input[name=base-url]').val();
		url = baseUrl + '/setup/authentication/save';
		method = $('#auth-type').val();
		params = $('#form-submit, #' + method + ' :input').serialize();

		$.post(
			url,
			params,
			function (data) {
				if (data.status === true) {
					$('#dialog').dialog('close');
					searchForAuthentication();
				} else {
					$('#dialog .message .content').html(data.message);
					$('#dialog .message').show();
				}
			},
			'json'
		);
	});

	$('input[name=use-encryption]').live('click', function () {
		var block, defaultPort, useEncryption, authBlock;

		useEncryption = $('#form-edit').data('useEncryption');

		block = $('.encryption-block');
		authBlock = $(this).parents('.authentication-block');
		defaultPort = authBlock.find('input[name=port]').val();

		if (useEncryption === true) {
			block.hide();
			block.find('input[name=encryption-type]').attr('checked', '');
			if (defaultPort === '' || defaultPort === '636') {
				authBlock.find('input[name=port]').val('389');
			}
			useEncryption = $('#form-edit').data('useEncryption', false);
		} else {
			block.find('input[value=useSsl]').attr('checked', 'checked');
			block.show();
			if (defaultPort === '' || defaultPort === '389') {
				authBlock.find('input[name=port]').val('636');
			}
			useEncryption = $('#form-edit').data('useEncryption', true);
		}
	});

	$('.message .close').live('click', function () {
		$(this).parents('.message').hide();
	});
});
