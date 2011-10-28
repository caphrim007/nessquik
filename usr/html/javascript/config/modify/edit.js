$(document).ready(function(){
	var baseUrl;

	baseUrl = $('#form-edit input[name=base-url]').val();

	$('.message, .progress').hide();

	$('input[type=text]').keypress(function (event) { 
		if (event.which === 13) {
			event.preventDefault();
			$('#btn-save').trigger('click');
			return true;
		}
	});

	$('#btn-save').click(function () {
		var url, params;

		url = baseUrl + '/config/modify/save';
		params = $('.form-submit').serialize();

		$(this).attr('disabled', 'disabled');

		$.post(
			url,
			params,
			function (data) {
				if (data.status === true) {
					window.location = baseUrl + '/admin/';
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

	$("ul.sub-menu li:first").addClass("active").show();
	$(".menu-content:first").show();
	$('#content .config').hide();

	$("ul.sub-menu li").click(function() {
		$('#content .config').hide();
		$("ul.sub-menu li").removeClass("active");
		$(this).addClass("active");

		var activeTab = $(this).find("a").attr("href");
		var activeText = $(this).find("a").html();

		$(activeTab).show();

		$(this).parents('.sub-menu').hide();
		$(this).parents('.trigger').find('.activeText').html(activeText);
		return false;
	});
	$('#menu .sub-menu li:first').trigger('click');

	showMenuItems(['settings']);
});
