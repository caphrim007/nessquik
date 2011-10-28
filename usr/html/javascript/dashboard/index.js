/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window, $*/
/* vim: set ts=4:sw=4:sts=4smarttab:expandtab:autoindent */

function updateCalendarDates(events) {
	return events;
}

$(document).ready(function () {
	var baseUrl = $('#form-edit input[name=base-url]').val();
	$('#form-edit').data('searchTimeout', undefined);

	$("#week-calendar").fullCalendar({
		defaultView: 'basicWeek',
		aspectRatio: 6,
		disableDragging: true,
		disableResizing: true,
		events: function (start, end, updateCalendarDates) {
			var baseUrl, url, params;

			baseUrl = $('#form-edit input[name=base-url]').val();
			url = baseUrl + '/default/index/upcoming';
			params = {
				start: start.getTime(),
				end: end.getTime()
			};

			$.getJSON(
				url,
				params,
				function (data) {
					if (data.status === true) {
						var results, dayCount, lastDay, hasMore, days;

						results = [];
						dayCount = 0;
						lastDay = -1;
						hasMore = false;
						days = {
							'Mon': [],
							'Tue': [],
							'Wed': [],
							'Thu': [],
							'Fri': [],
							'Sat': [],
							'Sun': []
						};

						$.each(data.results, function (index, value) {
							var curDate, curDay, result, moreDate, dayEvent, dayName,
								dayLength, dayArr, theDay;

							curDate = Date.parse(data.results[index].start);
							dayName = curDate.toString('ddd');
							dayLength = days[dayName].length;
							dayArr = days[dayName];

							if (dayLength > 5) {
								return;
							}

							result = data.results[index];
							if (result.title.length > 15) {
								result.alt = result.title;
								result.title = result.title.substr(0, 15) + '...';
							}
							dayArr.push(result);
							dayLength = days[dayName].length;

							if (dayLength === 5) {
								moreDate = curDate.clone();
								moreDate.clearTime();
								moreDate.addHours(23);
								moreDate.addMinutes(59);
								moreDate.addSeconds(59);

								dayEvent = {
									'start': moreDate,
									'title': 'more ...',
									'alt': 'Show all audits that will be run today',
									'allDay': true,
									'className': 'moreEvents',
									'editable': false,
									'isMore': true
								};
								dayArr.push(dayEvent);
							}
						});

						$.each(days, function (index, value) {
							$.each(days[index], function (index2, value2) {
								results.push(days[index][index2]);
							});
						});

						updateCalendarDates(results);
					}
				}
			);
		},
		eventClick: function (calEvent, jsEvent, view) {
			if (calEvent.isMore === true) {

			} else {
				window.location = baseUrl + '/audit/modify/edit?id=' + calEvent.id;
			}
		},
		header: false
	});
});
