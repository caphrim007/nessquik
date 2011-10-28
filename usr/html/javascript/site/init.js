/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window, $*/
/* vim: set ts=4:sw=4:sts=4smarttab:expandtab:autoindent */

Nessquik = {};

$(document).ready(function () {
	$('#scaffolding').hide();

	if ($('#menu')) {
		$('#content .config').hide();

		$("#menu li").click(function(event) {
			var activeTab, activeText;

			activeTab = $(this).find("a").attr("href");
			if (activeTab === undefined) {
				return;
			}

			$('.config').hide();
			$(activeTab).show();

			event.stopPropagation();
		});
	}
});
