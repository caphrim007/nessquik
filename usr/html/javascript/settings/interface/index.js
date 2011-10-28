/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

$(document).ready(function () {
	var baseUrl, limits;

	baseUrl = $('#form-edit input[name=base-url]').val();

	$('.error, .progress').hide();

	$('#btn-save').click(function () {
		var url, params;

		url = baseUrl + '/settings/interface/save';
		params = $('#form-submit').serialize();

		$('.progress').show();
		$('#btn-save').attr('disabled', 'disabled');

		$.post(
			url,
			params,
			function (data) {
				$('.progress').show();

				if (data.status === true) {
					window.location = baseUrl + '/settings/modify/edit';
				} else {
					$('#btn-save').attr('disabled', '');
					$('.error').show();
				}
			},
			'json'
		);
	});

	$(".limit").slider({
		range: "min",
		value: 15,
		min: 1,
		max: 30,
		slide: function (event, ui) {
			var row;
			row = $(ui.handle).parents('.limitRow');
			row.find('.limitVal').val(ui.value);
			row.find('.limitDisp').html(ui.value);
		}
	});
	limits = $('.limit');
	$.each(limits, function (index, value) {
		var slider, currentVal;

		slider = $(limits[index]);
		currentVal = slider.parents('.limitRow').find('.limitVal').val();
		slider.slider('value', currentVal);
	});
});
