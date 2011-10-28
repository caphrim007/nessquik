/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

$(document).ready(function () {
	var removeRegister, removeUnregister, totalSize, baseUrl;

	removeRegister = $('#include .list .remove');
	removeUnregister = $('#exclude .list .remove');
	totalSize = $('#include .list .empty, #exclude .list .empty').size();
	baseUrl = $('#form-edit input[name=base-url]').val();

	$('.block .skeleton, .messages').hide();

	$('.add-new').click(function () {
		var block, skel;

		block = $(this).parents('.block');
		skel = block.find('.skeleton .row').clone();
		skel.removeClass('skeleton');
		skel.show();

		block.find('.list').append(skel);
		block.find('.list .remove').show();
	});

	$('.remove').live('click', function () {
		var block, input, remaining, removeInput;

		block = $(this).parents('.block');
		input = $(this).parents('.row').find('input[type=hidden]').val();
		remaining = block.find('.list .remove').size() - 1;

		if (input !== '') {
			removeInput = $('#delete .skeleton').clone();
			removeInput.val(input);
			removeInput.removeClass('skeleton');
			$('#delete').append(removeInput);
		}

		$(this).parents('.row').remove();

		if (remaining === 1) {
			block.find('.list .remove').hide();
		}
	});

	if (totalSize === 2) {
		$('#single-form, #activate-single .deactivate-toggle').hide();
		$('#form-submit input[name=choose-specific]').val(0);
	} else {
		$('#activate-single .activate-toggle').hide();
		$('#form-submit input[name=choose-specific]').val(1);
	}

	if (removeRegister.size() === 1) {
		removeRegister.hide();
	}

	if (removeUnregister.size() === 1) {
		removeUnregister.hide();
	}

	$('#btn-save').click(function () {
		var url, params;

		url = baseUrl + '/admin/maintenance/save';
		params = $('#form-submit').serialize();

		$.post(
			url,
			params,
			function (data) {
				if (data.status === true) {
					window.location = baseUrl + '/admin';
				} else {
					$('.message .error').html(data.message);
				}
			},
			'json'
		);
	});

	$('#activate-single .hypertext').click(function () {
		var result;

		$('#activate-single .hypertext, #single-form').toggle();

		result = $('#single-form').is(':visible');
		if (result === true) {
			$('#form-submit input[name=choose-specific]').val(1);
		} else {
			$('#form-submit input[name=choose-specific]').val(0);
		}
	});
});
