Nessquik.AuditIndex = $.klass({
	initialize: function() {
		showMenuItems(['create','types','search','faq']);
	},

	onBeforeAsync: function() {
		$('#title .progress').show();
	},

	onAfterAsync: function() {
		$('#title .progress').hide();
	},

	onDeleteSuccess: function(){

	},

	search: function () {

	},

	startAudit: function(auditId, runWhen) {

	}
});
