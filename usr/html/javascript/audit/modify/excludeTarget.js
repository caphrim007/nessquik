/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

function addTargetExclude(target) {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=baseUrl]').val();
	url = baseUrl + '/audit/targets/add'

	$('#form-targets-exclude input[name=target]').val(target);

	params = $('#form-targets-exclude').serialize();
	$('.progress').show();

	$.post(
		url,
		params,
		function (data) {
			$('.progress').hide();

			if (data.status === true) {
				$('#targetExcludeList input[name=target]').val('');
				searchForExcludeTargets();
			} else {
				// Show error message
			}
		},
		'json'
	);
}

function searchForExcludeTargets() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=baseUrl]').val();

	url = baseUrl + '/audit/targets/search';
	params = $('#form-targets-exclude-search').serializeArray();

	$('.progress').show();

	$.get(
		url,
		params,
		function (data) {
			$('.progress').hide();

			$('#targetExcludeList .content').html(data.message);
			$('#targetExcludeList .content').show();
			$('#targetExcludeList .content .icons img').hide();

			if (data.totalPages < 1) {
				$('#targetExcludeList .no-results-mesg').show();
				$('#targetExcludeList .newTargetBar .targetInitiator').hide();
				$('#targetExcludeList .newTargetBar .targetActions').show();
				$('#targetExcludeList .newTargetBar').hide();
			} else if (data.totalPages <= 1) {
				$('#targetExcludeList .pager').hide();
			} else {
				$("#targetExcludeList .pager").pager({
					pagenumber: data.currentPage,
					pagecount: data.totalPages,
					buttonClickCallback: function(pageclickednumber){
						$('#form-targets-exclude-search input[name=page]').val(pageclickednumber);
						searchForExcludeTargets();
					}
				}).show();
			}
		},
		'json'
	);
}

function removeTargetExclude(target, type, targetStatus) {
	var baseUrl, url, params, searchTimeout;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/audit/targets/remove'
	params = {
		'status': targetStatus,
		'target': target,
		'type': type
	};

	$('.progress').show();

	$.post(
		url,
		params,
		function (data) {
			$('.progress').hide();
			searchForTargets(targetStatus);
		},
		'json'
	);
}

$(document).ready(function () {
	$('#targetExcludeList .targetTypeIcons').click(function(event){
		var parentTable ;

		parentTable = $(this).parents('table');
		parentType = $(this).parents('.targetList').find('input[name=type]').val();
		$('#form-targets-exclude input[name=type]').val(parentType);

		$('#targetTypeSelectorExclude')
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

	$('#targetTypeSelectorExclude tr').click(function(){
		var type;

		parentType = $('#form-targets-exclude input[name=type]').val();

		typeIcons = $('#targetExcludeList .targetTypeIcons');
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

		$('#form-targets-exclude input[name=type]').val(type);
	});

	/**
	* This will cause the submenus to be hidden automatically
	* when a user clicks outside of the submenu
	*/
	$(document).click(function(){
		if ($('#targetTypeSelectorExclude').is(':visible')) {
			$('#targetTypeSelectorExclude').hide();

			$('#targetExcludeList .targetTypeIcons').each(function(){
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

	$('#targetExcludeList input[name=target]').keypress(function(event) {
		var target;

		if (event.which == 13) {
			event.preventDefault();

			target = $('#targetExcludeList input[name=target]').val();
			addTargetExclude(target);
		}
	});

	$('#targetExcludeList img.accept').click(function(){
		target = $('#targetExcludeList input[name=target]').val();
		addTargetExclude(target);
	});

	$('#targetExcludeList img.cancel').click(function(){
		var numberOfTargets, parentDiv;

		numberOfTargets = $('#targetExcludeList .targetList tr').size();

		$('#form-targets-exclude input[name=type]').val('HostnameTarget');

		$('#targetExcludeList input[name=target]').val('');
		$('#targetExcludeList .targetTypeIcons img.type').hide();
		$('#targetExcludeList .targetTypeIcons img.HostnameTarget').show();

		if (numberOfTargets == 0) {
			$('#targetExcludeList .no-results-mesg').show();
			$('#targetExcludeList .newTargetBar').hide();
		} else {
			$('#targetExcludeList .newTargetBar .targetInitiator').show();
			$('#targetExcludeList .newTargetBar .targetActions').hide();
			$('#targetExcludeList .newTargetBar').show();
		}
	});

	$('#targetExcludeList .targetInitiatorLink').click(function(){
		$('#targetExcludeList .newTargetBar .targetInitiator').hide();
		$('#targetExcludeList .newTargetBar .targetActions').show();
		$('#targetExcludeList .newTargetBar').show();
	});

	$('#targetExcludeList img.trash').live('click', function(){
		var targetId;

		targetId = $(this).parents('.row').find('input[name=targetId]').val();
		removeTargetExclude(targetId);
	});
});
