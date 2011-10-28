/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

function saveCredentials() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/site-audit/account/save';
	params = $('#form-submit').serialize();

	$.post(
		url,
		params,
		function (data) {
			if (data.status === true) {
				window.location = baseUrl + '/site-audit/options';
			} else {
				$('#btn-save').attr('disabled', '');
				$('.message .content').html(data.message);
				$('.message').show();
				$('.progress').hide();
			}
		},
		'json'
	);
}

function revealDocStatus() {
	var docStatus, mesg;

	docStatus = $('input[name=status]').val();
	if (docStatus === 'exists') {
		mesg = 'An existing site audit was found to be in progress. Do you want to ' +
			'<span id="resumeSiteAudit" class="hypertext">resume</span> ' +
			'or <span id="cancelSiteAudit" class="hypertext">cancel</span> ' +
			' that audit.';

		$('.notice .content').html(mesg);
		$('.notice').show();
		$('#formContent, #btn-save').hide();
	} else {
		$('.notice .content').html('');
		$('.notice').hide();
	}
}

function resumeSiteAudit() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();

	window.location = baseUrl + '/site-audit/options';
}

function cancelSiteAudit() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/site-audit/index/cancel';
	params = $('#form-edit').serialize();

	$.post(
		url,
		params,
		function (data) {
			if (data.status === true) {
				window.location = baseUrl + '/site-audit';
			} else {
				$('.error .content').html(data.message);
				$('.error').show();
			}
		},
		'json'
	);
}

$(document).ready(function () {
	$('.message, .progress').hide();

	$('#form-submit input[type=text], #form-submit textarea').keypress(function (event) {
		if (event.which === 13) {
			event.preventDefault();
			$('#btn-save').trigger('click');
			return true;
		}
	});

	$('#btn-save').click(function () {
		$('.progress').show();
		$('#btn-save').attr('disabled', 'disabled');

		saveCredentials();
	});

	$('.message .close').click(function () {
		$(this).parents('.message').hide();
	});

	$('#resumeSiteAudit').live('click', function () {
		resumeSiteAudit();
	});

	$('#cancelSiteAudit').live('click', function () {
		cancelSiteAudit();
	});

	$('input[name=username]').focus();
	revealDocStatus();
});
