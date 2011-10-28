/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

function saveScanner() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/setup/scanner/save';
	params = $('#form-submit').serialize();

	$('.progress').show();

	$.post(
		url,
		params,
		function (data) {
			$('.progress').hide();

			if (data.status === true) {
				$('.update-plugins .btn-save').attr('disabled', '');
				$('.updates-mesg').show();
				$('.message').hide();
			} else {
				$('.update-plugins .btn-save').attr('disabled', 'disabled');
				$('.message .content').html(data.message);
				$('.message').show();
			}
		},
		'json'
	);
}

function deleteScanners() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/setup/scanner/delete';
	params = {};

	$('.progress').show();

	$.post(
		url,
		params,
		function (data) {
			$('.progress').hide();

			if (data.status === true) {
				window.location = baseUrl + '/setup/scanner';
			} else {
				$('.btn-save').attr('disabled', '');
				$('.message .content').html(data.message);
				$('.message').show();
			}
		},
		'json'
	);
}

function updatePlugins() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/setup/scanner/update-plugins';
	params = {};

	$('.progress').show();

	$('.update-plugins .btn-save').attr('disabled', 'disabled');
	$.post(
		url,
		params,
		function (data) {
			$('.progress').hide();

			if (data.status === true) {
				window.location = baseUrl + '/setup/authentication/';
			} else {
				$('.update-plugins .btn-save').attr('disabled', '');
				$('.message .content').html(data.message);
				$('.message').show();
			}
		},
		'json'
	);
}

function isXmlRpc() {
	var adapterChoice;

	adapterChoice = $('#adapter').val().toLowerCase();
	if (adapterChoice.match('xml') === null) {
		$('#pluginDirectory').show();
		return false;
	} else {
		$('#pluginDirectory').hide();
		return true;
	}
}

function switchPorts() {
	var dirtyAdapter;

	dirtyAdapter = $('#form-edit').data('dirtyAdapter');
	if (dirtyAdapter === false) {
		if (isXmlRpc()) {
			$('#form-submit input[name=port]').val('8834');
		}  else {
			$('#form-submit input[name=port]').val('1241');
		}
	}
}

$(document).ready(function () {
	$('#form-edit').data('dirtyAdapter', false);
	$('.message, .progress, .updates-mesg').hide();

	$('#form-submit input[type=text]').keypress(function (event) {
		if (event.which === 13) {
			event.preventDefault();
			$('.save-scanner .btn-save').trigger('click');
			return true;
		}
	});

	$('#form-submit input[name=port]').keypress(function (event) {
		if (event.which !== 13) {
			switchPorts();
			$('#form-edit').data('dirtyAdapter', true);
		}
	});

	$('.save-scanner .btn-save').click(function () {
		if ($('.save-scanner .btn-save').attr('disabled')) {
			// Already successfully created a scanner, don't let
			// user recreate
			return;
		} else {
			$('.save-scanner .btn-save').attr('disabled', 'disabled');
			saveScanner();
		}
	});

	$('.update-plugins .btn-save').click(function () {
		updatePlugins();
	});

	$('.delete-scanners .btn-save').click(function () {
		$(this).attr('disabled', 'disabled');
		deleteScanners();
	});

	$('.message .close').click(function () {
		$(this).parents('.message').hide();
	});

	$('#adapter').change(function () {
		isXmlRpc();
		switchPorts();
	});

	$('#adapter').trigger('change');
});
