$(document).ready(function(){
	var searchTimeout = undefined;

	$('.metrxRow .links, .config').hide();
	$('.metrxRow').live('click', function(){
		var urlLink = $(this).find('input[name="urlLink"]').val();
		window.location = urlLink;
	});
	$('.metrxRow').live('mouseover', function(){
		$(this).find('.links').show();
	});
	$('.metrxRow').live('mouseout', function(){
		$(this).find('.links').hide();
	});

	$('#charts').show();
});
