$(document).ready(function () {
	$('#search-by-value').bind('keyup', function () {
		var searchTimeout;

		if (searchTimeout !== undefined) {
			clearTimeout(searchTimeout);
		}

		$('#plugin-search input[name=family-page]').val(1);
		$('#plugin-search input[name=plugin-page]').val(1);

		searchTimeout = setTimeout(function () {
			search();
		}, 600);
	});

	$('#search-by').change(function () {
		$('#plugin-search input[name=family-page]').val(1);
		$('#plugin-search input[name=plugin-page]').val(1);
		search();
	});

	$('#dialog').dialog({
		autoOpen: false,
		bgiframe: true,
		dialogClass: 'search-dialog',
		draggable: false,
		resizable: false,
		height: "auto",
		modal: true,
		position: ['center', 'center'],
		width: 710
	});

	$('.search-dialog-link').click(function () {
		$('#dialog').dialog('open');
	});
	$('.search-dialog-close').click(function () {
		$('#dialog').dialog('close');
	});
});
