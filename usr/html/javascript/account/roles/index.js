/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

$(document).ready(function () {
	var baseUrl = $('#form-edit input[name=base-url]').val();

	$('.message, .progress').hide();

	$('.available li').click(function () {
		var itemClone, topDiv, origImg, origImgSrc, origImgNewSrc,
		    origInputName, origInputNewName, selected, existing,
		    img, newImgSrc;

		itemClone = $(this).clone();
		topDiv = $(this).parents('div.roles');

		origImg = $(this).find('img');
		origImgSrc = origImg.attr('src');
		origImgNewSrc = origImgSrc.replace('forward.png', 'forward-selected.png');

		origInputName = itemClone.find('input[type=hidden]').attr('name');
		origInputNewName = origInputName.replace('available', 'selected');

		selected = $(this).attr('class');
		existing = topDiv.find('.selected li[class=' + selected + ']');

		if (existing.size() > 0) {
			return;
		}

		itemClone.find('input[type=hidden]').attr('name', origInputNewName);
		origImg.attr('src', origImgNewSrc);
		img = $(itemClone).find('img');
		newImgSrc = img.attr('src').replace('forward.png', 'back.png');

		img.attr('src', newImgSrc);

		topDiv.find('.selected').append(itemClone);
	});

	$('.add-all').click(function () {
		var topDiv = $(this).parents('div.permission-block');
		topDiv.find('ol.available li').each(function () {
			$(this).trigger('click');
		});
	});

	$('.clear-all').click(function () {
		var topDiv = $(this).parents('div.permission-block');
		topDiv.find('ol.selected li').each(function () {
			$(this).trigger('click');
		});
	});

	$(".selected li").live("click", function () {
		var topDiv, selected, existing, origImg, origImgSrc, origImgNewSrc;

		topDiv = $(this).parents('div.roles');
		selected = $(this).attr('class');
		existing = topDiv.find('.selected li[class=' + selected + ']');

		if (existing.size() > 0) {
			$(this).remove();

			origImg = topDiv.find('.available li[class=' + selected + '] img');
			origImgSrc = origImg.attr('src');
			origImgNewSrc = origImgSrc.replace('forward-selected.png', 'forward.png');
			origImg.attr('src', origImgNewSrc);
		}
	});

	$('#btn-save').click(function () {
		var url, params, accountId;

		url = baseUrl + '/account/roles/save';
		params = $('#form-submit').serialize();
		accountId = $('#form-submit input[name=accountId]').val();

		$('.progress').show();
		$('#btn-save').attr('disabled', 'disabled');

		$.post(
			url,
			params,
			function (data) {
				$('.progress').hide();

				if (data.status === true) {
					window.location = baseUrl + '/account/modify/edit?id=' + accountId;
				} else {
					$('#btn-save').attr('disabled', '');
					$('.message .content').html(data.message);
					$('.message').show();
				}
			},
			'json'
		);
	});
});
