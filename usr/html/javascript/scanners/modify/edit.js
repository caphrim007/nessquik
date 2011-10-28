/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

function isXmlRpc() {
	var adapterChoice = $('#adapter').val().toLowerCase();

	if (adapterChoice.match('xml') === null) {
		$('#pluginDirectory').show();
		return false;
	} else {
		$('#pluginDirectory').hide();
		return true;
	}
}

function switchPorts() {
	var dirtyAdapter = $('#form-edit').data('dirtyAdapter');

	if (dirtyAdapter === false) {
		if (isXmlRpc()) {
			$('#form-submit input[name=port]').val('8834');
		}  else {
			$('#form-submit input[name=port]').val('1241');
		}
	}
}

$(document).ready(function () {
	var baseUrl, searchTimeout;

	baseUrl = $('#form-edit input[name=base-url]').val();
	searchTimeout = undefined;
	
	$('.message, .progress').hide();

	$('#form-submit input[type=text], #form-submit textarea').keypress(function (event) { 
		if (event.which === 13) {
			event.preventDefault();
			$('#btn-save').trigger('click');
			return true;
		}
	});

	$('#btn-save').click(function () {
		var url, params;

		url = baseUrl + '/scanners/modify/save';
		params = $('#form-submit').serialize();

		$('.progress').show();
		$('#btn-save').attr('disabled', 'disabled');

		$.post(
			url,
			params,
			function (data) {
				$('.progress').hide();

				if (data.status === true) {
					window.location = baseUrl + '/admin/scanners';
				} else {
					$('#btn-save').attr('disabled', '');
					$('.message .content').html(data.message);
					$('.message').show();
				}
			},
			'json'
		);
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
