/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window, $*/
/* vim: set ts=4:sw=4:sts=4smarttab:expandtab:autoindent */

function alertUser(message, actionStatus) {
	var addClass, messageObj;

	addClass = null;
	messageObj = $('#message');
	messageObj.hide();

	if (actionStatus === true) {
		addClass = 'success';
	} else {
		addClass = 'error';
	}

	messageObj.html(message)
	  .removeClass('success error')
	  .addClass(addClass)
	  .show();

	setTimeout(function () {
		messageObj.hide();
	}, 3000);

}

$(document).ready(function () {
	$('#btn-save').click(function (ev) {
		var username;

		ev.preventDefault();

		username = $('#form-account input[name=username]').val();
		if (username === '') {
			alertUser('The username cannot be empty', false);
			return false;
		}

		$("#form-account").submit(); 
	});
});
