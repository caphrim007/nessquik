/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window, $*/
/* vim: set ts=4:sw=4:sts=4smarttab:expandtab:autoindent */

searchForPolicies = function () {
	var baseUrl, url;

	baseUrl = $('#form-edit input[name=baseUrl]').val();
	url = baseUrl + '/policy/index/search';

	$('.progress').show();

	$.get(
		url,
		$('#form-edit,#form-policies').serialize(),
		function (data) {
			$('.progress').hide();

			$('#content').html(data.message);
			$('#content').show();
			$('#content .icons img').hide();
			$('input[type=checkbox]')
				.unbind('click')
				.shiftcheckbox();

			if (data.totalPages <= 1) {
				$('#pager').hide();
			} else {
				$('#content tr.removed').hide();
				$("#pager").pager({
					pagenumber: data.currentPage,
					pagecount: data.totalPages,
					buttonClickCallback: function(pageClickedNumber){
						$('#form-edit input[name=page]').val(pageClickedNumber);
						searchForPolicies();
					}
				}).show();
			}
		},
		'json'
	);
}

$(document).ready(function () {
	$('.select-actions, #with-selected').hide();

	$('.selected-delete').click(function () {
		var block;

		block = $(this).parents('.block');
		block.find('.row input[type=checkbox]:checked')
			.parents('.row')
			.find('.icons .trash')
			.each(function () {
				$(this).trigger('click');
			}
		);
	});

	$('.select-all').click(function () {
		var block;

		block = $(this).parents('.block');
		block.find('input[type=checkbox]').attr('checked', 'checked');
		block.find('.select-every').show();
	});

	$('.select-none').click(function () {
		var block;

		block = $(this).parents('.block');
		block.find('input[type=checkbox]').attr('checked', '');
	});

	$('.row').live('mouseover', function () {
		$(this).find('.icons img').show();
	});
	$('.row').live('mouseout', function () {
		$(this).find('.icons img').hide();
	});

	$('.row .icons .trash').live('click', function () {
		var baseUrl, url, params;

		baseUrl = $('#form-edit input[name=baseUrl]').val();
		url = baseUrl + '/policy/index/delete';
		params = $(this).parents('.icons').find('input[name=policyId]').serialize();

		$('.progress').show();
		$(this).hide();

		$.post(
			url,
			params,
			function (data) {
				$('.progress').hide();

				if (data.status === true) {
					searchForPolicies();
				} else {
					$('.messages .error').html(data.message);
					$('.messages, .messages .error').show();
				}
			},
			'json'
		);
	});

	$('#searchMenu').click(function(event){
		event.stopPropagation();
	});
	$('#searchMenu').hide();
	$('#searchLink').click(function(event){
		event.stopPropagation();
		if ($('#searchMenu').is(':visible')) {
			$('#searchMenu').hide();
		} else {
			$('#searchMenu').show();
			$('#searchMenu').position({
				of: $('#title'),
				my: 'right top',
				at: 'right bottom'
			});
		}
	});

	$(document).click(function(){
		if ($('#searchMenu').is(':visible')) {
			$('#searchMenu').hide();
		}
	});

	$('#form-policies input[type=button]').click(function(){
		$('#searchMenu').hide();
		searchForPolicies();
	});

	searchForPolicies();
});
