Nessquik.Audit = $.klass({
	initialize: function(){

	},

	save: function(){

	},

	delete: function(auditId){
		var baseUrl, url, params;

		baseUrl = $('#form-edit input[name=baseUrl]').val();
		url = baseUrl + '/audit/index/delete';
		params = {'auditId': auditId};

		$(document).trigger('onBeforeAsync');

		$.ajax({
			type: 'POST',
			url: url,
			data: params,
			dataType: 'json',
			error: function() {


			},
			success: function (data) {
				if (data.status === true) {
					selectedStatus = $('#form-edit input[name=status]').val();
					searchForAudits(selectedStatus);
				} else {
					$('#title .progress').hide();
					return;
				}
			}
		);
	},
});
