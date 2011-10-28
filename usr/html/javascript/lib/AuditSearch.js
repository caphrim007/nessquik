Nessquik.AuditSearch = $.klass({
	statusType: 'parked',

	search: function (statusType) {
		var baseUrl, url, params;

		baseUrl = $('#form-edit input[name=baseUrl]').val();
		url = baseUrl + '/audit/index/search';
		params = {};
		statusType = sanitizeDropMenuItem(statusType);

		$('#form-edit input[name=status]').val(statusType);
		params = $('#form-edit').serialize();

		$('#title .progress').show();

		$.get(
			Nessquik.Util.makeUrl('/audit/index/search'),
			params,
			function (data) {
				var auditIds, totalChecked, auditId, isChecked, arrId,
				    totalOnPage, allFinished;

				$('#title .progress').hide();

				$('#content').html(data.message);
				$('#content').show();
				$('#content .icons img').hide();
				$('#content input[type=checkbox]')
					.unbind('click')
					.shiftcheckbox();

				if (data.totalPages <= 1) {
					$('#pager').hide();
				} else {
					$("#pager").pager({
						pagenumber: data.currentPage,
						pagecount: data.totalPages,
						buttonClickCallback: function(pageclickednumber){
							$('#form-edit input[name=page]').val(pageclickednumber);
							searchForAudits(data.currentStatus);
						}
					}).show();
				}
			},
			'json'
		);
	}
});
