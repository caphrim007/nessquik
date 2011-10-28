/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

function standardLogin() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/setup/account/standard-login';
	params = $('.has-ldap :input').serialize();

	$.post(
		url,
		params,
		function (data) {
			if (data.status === true) {
				window.location = baseUrl + '/setup/finalize/';
			} else {
				$('.has-ldap .message .content').html(data.message);
				$('.has-ldap .message').show();
			}
		},
		'json'
	);
}

function createAccount() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/setup/account/create-account';
	params = $('.database-account :input').serialize();

	$.post(
		url,
		params,
		function (data) {
			if (data.status === true) {
				window.location = baseUrl + '/setup/finalize/';
			} else {
				$('.database-account .message .content').html(data.message);
				$('.database-account .message').show();
			}
		},
		'json'
	);
}

$(document).ready(function () {
	$('.message').hide();

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
		$('.database-account').show();
		$('.has-ldap').hide();
	});
	$('.yes-ldap').click(function () {
		$('.has-ldap').show();
		$('.database-account').hide();
	});
});
