/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

function standardLogin() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/setup/admin/standard-login';
	params = $('.has-ldap :input').serialize();

	$('.btn-save').attr('disabled', 'disabled');
	$('.yes-ldap, .no-ldap').hide();
	$('.progress').show();

	$.post(
		url,
		params,
		function (data) {
			$('.progress').hide();

			if (data.status === true) {
				window.location = baseUrl + '/setup/accounts/';
			} else {
				$('.has-ldap .message .content').html(data.message);
				$('.has-ldap .message').show();
				$('.no-ldap').show();
				$('.btn-save').attr('disabled', '');
			}
		},
		'json'
	);
}

function createAccount() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/setup/admin/create-account';
	params = $('.database-account :input').serialize();

	$('.btn-save').attr('disabled', 'disabled');
	$('.yes-ldap, .no-ldap').hide();
	$('.progress').show();

	$.post(
		url,
		params,
		function (data) {
			$('.progress').hide();

			if (data.status === true) {
				window.location = baseUrl + '/setup/accounts/';
			} else {
				$('.database-account .message .content').html(data.message);
				$('.database-account .message').show();
				$('.yes-ldap').show();
				$('.btn-save').attr('disabled', '');
			}
		},
		'json'
	);
}

$(document).ready(function () {
	$('.message, .progress').hide();

	$('.database-account input[type=text]').keypress(function (event) {
		if (event.which === 13) {
			event.preventDefault();
			$('.database-account .btn-save').trigger('click');
			return true;
		}
	});

	$('.has-ldap input[type=text]').keypress(function (event) {
		if (event.which === 13) {
			event.preventDefault();
			$('.has-ldap .btn-save').trigger('click');
			return true;
		}
	});

	$('.has-ldap .btn-save').click(function () {
		standardLogin();
	});

	$('.database-account .btn-save').click(function () {
		createAccount();
	});

	$('.message .close').click(function () {
		$(this).parents('.message').hide();
	});

	if ($('.has-ldap').length > 0) {
		$('.database-account').hide();
	}

	$('.no-ldap').click(function () {
		$('.database-account, .yes-ldap').show();
		$('.has-ldap').hide();
		$('.message .close').trigger('click');
	});
	$('.yes-ldap').click(function () {
		$('.database-account').hide();
		$('.has-ldap, .no-ldap').show();
		$('.message .close').trigger('click');
	});
});
