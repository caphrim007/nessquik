/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window, $*/
/* vim: set ts=4:sw=4:sts=4smarttab:expandtab:autoindent */

/**
* This file contains code that is executable that is run
* at "the end" of all other script inclusion.
*
* Where the init.js should include event handlers and
* pre-op code, this script should be running after all
* the triggers have been registered
*/

$(document).ready(function () {
	$('.progress').hide();
});
