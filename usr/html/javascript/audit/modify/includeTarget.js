/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$,searchForReports*/

function addTargetInclude(target) {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=baseUrl]').val();
	url = baseUrl + '/audit/targets/add'

	$('#form-targets-include input[name=target]').val(target);

	params = $('#form-targets-include').serialize();
	$('.progress').show();

	$.post(
		url,
		params,
		function (data) {
			$('.progress').hide();

			if (data.status === true) {
				$('#targetIncludeList input[name=target]').val('');
				searchForIncludeTargets();
			} else {
				// Show error message
			}
		},
		'json'
	);
}

function searchForIncludeTargets() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=baseUrl]').val();

	url = baseUrl + '/audit/targets/search';
	params = $('#form-targets-include-search').serializeArray();

	$('.progress').show();

	$.get(
		url,
		params,
		function (data) {
			$('.progress').hide();

			$('#targetIncludeList .content').html(data.message);
			$('#targetIncludeList .content').show();
			$('#targetIncludeList .content .icons img').hide();

			if (data.totalPages < 1) {
				$('#targetIncludeList .no-results-mesg').show();
				$('#targetIncludeList .newTargetBar .targetInitiator').hide();
				$('#targetIncludeList .newTargetBar .targetActions').show();
				$('#targetIncludeList .newTargetBar').hide();
			} else if (data.totalPages <= 1) {
				$('#targetIncludeList .pager').hide();
			} else {
				$("#targetIncludeList .pager").pager({
					pagenumber: data.currentPage,
					pagecount: data.totalPages,
					buttonClickCallback: function(pageclickednumber){
						$('#form-targets-include-search input[name=page]').val(pageclickednumber);
						searchForIncludeTargets();
					}
				}).show();
			}
		},
		'json'
	);
}

function removeTargetInclude(target) {
	var baseUrl, url, params, searchTimeout;

	baseUrl = $('#form-edit input[name=baseUrl]').val();
	auditId = $('#form-edit input[name=auditId]').val();

	url = baseUrl + '/audit/targets/remove'

	params = {
		'auditId': auditId,
		'targetId': target
	};

	$('.progress').show();

	$.post(
		url,
		params,
		function (data) {
			$('.progress').hide();
			searchForIncludeTargets();
		},
		'json'
	);
}

$(document).ready(function () {
	$('#targetIncludeList .targetTypeIcons').click(function(event){
		var parentTable ;

		parentTable = $(this).parents('table');
		parentType = $(this).parents('.targetList').find('input[name=type]').val();
		$('#form-targets-include input[name=type]').val(parentType);

		$('#targetTypeSelectorInclude')
		  .toggle()
		  .position({
			of: parentTable,
			my: 'left top',
			at: 'left bottom'
		});
		if (parentTable.hasClass('hideTypes')) {
			parentTable.removeClass('hideTypes');
			parentTable.addClass('showTypes');
		} else {
			parentTable.removeClass('showTypes');
			parentTable.addClass('hideTypes');
		}

		event.stopPropagation();
	});

	$('#targetTypeSelectorInclude tr').click(function(){
		var type;

		parentType = $('#form-targets-include input[name=type]').val();

		typeIcons = $('#targetIncludeList .targetTypeIcons');
		typeIcons.find('img.type').hide();
		typeIcons.trigger('click');

		if ($(this).hasClass('HostnameTarget')) {
			type = 'HostnameTarget';
			typeIcons.find('img.HostnameTarget').show();
		} else if ($(this).hasClass('NetworkTarget')) {
			type = 'NetworkTarget';
			typeIcons.find('img.NetworkTarget').show();
		} else if ($(this).hasClass('RangeTarget')) {
			type = 'RangeTarget';
			typeIcons.find('img.RangeTarget').show();
		} else if ($(this).hasClass('ClusterTarget')) {
			type = 'ClusterTarget';
			typeIcons.find('img.ClusterTarget').show();
		}

		$('#form-targets-include input[name=type]').val(type);
	});

	/**
	* This will cause the submenus to be hidden automatically
	* when a user clicks outside of the submenu
	*/
	$(document).click(function(){
		if ($('#targetTypeSelectorInclude').is(':visible')) {
			$('#targetTypeSelectorInclude').hide();

			$('#targetIncludeList .targetTypeIcons').each(function(){
				var parentTable;

				parentTable = $(this).parents('table');
				if (parentTable.hasClass('hideTypes')) {
					parentTable.removeClass('hideTypes');
					parentTable.addClass('showTypes');
				} else {
					parentTable.removeClass('showTypes');
					parentTable.addClass('hideTypes');
				}
			});
		}
	});
	$('#targetIncludeList input[name=target]').keypress(function(event) {
		var target;

		if (event.which == 13) {
			event.preventDefault();

			target = $('#targetIncludeList input[name=target]').val();
			addTargetInclude(target);
		}
	});

	$('#targetIncludeList img.accept').click(function(){
		target = $('#targetIncludeList input[name=target]').val();
		addTargetInclude(target);
	});

	$('#targetIncludeList img.cancel').click(function(){
		var numberOfTargets, parentDiv;

		numberOfTargets = $('#targetIncludeList .targetList tr').size();

		$('#form-targets-include input[name=type]').val('HostnameTarget');

		$('#targetIncludeList input[name=target]').val('');
		$('#targetIncludeList .targetTypeIcons img.type').hide();
		$('#targetIncludeList .targetTypeIcons img.HostnameTarget').show();

		if (numberOfTargets == 0) {
			$('#targetIncludeList .no-results-mesg').show();
			$('#targetIncludeList .newTargetBar .targetInitiator').hide();
			$('#targetIncludeList .newTargetBar .targetActions').show();
			$('#targetIncludeList .newTargetBar').hide();
		} else {
			$('#targetIncludeList .newTargetBar .targetInitiator').show();
			$('#targetIncludeList .newTargetBar .targetActions').hide();
			$('#targetIncludeList .newTargetBar').show();
		}
	});

	$('#targetIncludeList .targetInitiatorLink').click(function(){
		$('#targetIncludeList .newTargetBar .targetInitiator').hide();
		$('#targetIncludeList .newTargetBar .targetActions').show();
		$('#targetIncludeList .newTargetBar').show();
	});

	$('#targetIncludeList img.trash').live('click', function(){
		var targetId;

		targetId = $(this).parents('.row').find('input[name=targetId]').val();
		removeTargetInclude(targetId);
	});
});
