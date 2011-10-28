/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

function loadCountryExposure() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/admin/charts/country-exposure';
	params = {};

	$('#countryExposure .progress').show();

	$.get(
		url,
		params,
		function (data) {
			$('#countryExposure .progress').hide();

			if (data.status === true) {
				$('#countryExposureChart').gchart(
					$.gchart.map('world', data.message)
				);
			} else {
				$('#countryExposure .message .content').html(data.message);
				$('#countryExposure .message').show();
			}
		},
		'json'
	);
}

function loadUrlActivityLastWeekScatter() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/admin/charts/url-activity-last-week-scatter';
	params = {};

	$('#urlActivityLastWeekScatter .progress').show();

	$.get(
		url,
		params,
		function (data) {
			var series, values, chart, xaxis, yaxis, i;

			$('#urlActivityLastWeekScatter .progress').hide();

			if (data.status === true) {
				series = [[], [], []];
				values = data.message.data;
				chart = {};
				xaxis = ['', '12am', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12pm',
					'1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11'];
				yaxis = ['', 'Sun', 'Mon', 'Tue', 'Wed', 'Thr', 'Fri', 'Sat'];

				for (i = 0; i < values.length; i = i + 1) {
					series[0][i] = values[i][0];
					series[1][i] = values[i][1];
					series[2][i] = values[i][2];
				}

				chart = {
					type: 'scatter', 
					series: [$.gchart.series('', series[0], '', '', 0, 24),
						$.gchart.series('', series[1], '', '', 0, 6),
						$.gchart.series('', series[2])],
					axes: [ $.gchart.axis('bottom', xaxis),
						$.gchart.axis('left', yaxis)],
					markers: [$.gchart.marker('circle', '', 1, 1.0, 30.0)]
				};

				$('#urlActivityLastWeekScatterChart').gchart(chart);
			} else {
				$('#urlActivityLastWeekScatter .message .content').html(data.message);
				$('#urlActivityLastWeekScatter .message').show();
			}
		},
		'json'
	);
}

function loadUrlActivityLastMonthBar() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/admin/charts/url-activity-last-month-bar';
	params = {};

	$('#urlActivityLastMonthBar .progress').show();

	$.get(
		url,
		params,
		function (data) {
			var values, chart, dataPoints, labels, newValues, i;

			$('#urlActivityLastMonthBar .progress').hide();

			if (data.status === true) {
				values = data.message;
				chart = {};
				dataPoints = [];
				labels = [];
				newValues = [];
				labels = data.label;

				for (i in values) {
					newValues.push(values[i]);
					if (values[i] === 0) {
						continue;
					} else {
						dataPoints.push($.gchart.marker('flag', '0000FF', 0, i - 1, 10, 'above', data.label[i]));
					}
				}

				chart = $.extend({}, {
					margins: [0,0,30,0],
					type: 'barVert',
					dataLabels: labels,
					encoding: 'scaled',
					series: [
						$.gchart.series('', newValues, '76A4FB')
					],
					markers: dataPoints
				});

				$('#urlActivityLastMonthBarChart').gchart(chart);
			} else {
				$('#urlActivityLastMonthBar .message .content').html(data.message);
				$('#urlActivityLastMonthBar .message').show();
			}
		},
		'json'
	);
}

function loadUrlActivityLastDayBar() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/admin/charts/url-activity-last-day-bar';
	params = {};

	$('#urlActivityLastDayBar .progress').show();

	$.get(
		url,
		params,
		function (data) {
			var values, chart, dataPoints, labels;

			$('#urlActivityLastDayBar .progress').hide();

			if (data.status === true) {
				values = data.message;
				chart = {};
				dataPoints = [];
				labels = ['12am', '1', '2', '3', '4', '5', '6', '7', '8',
				          '9', '10', '11', '12pm', '1', '2', '3', '4', '5',
				          '6', '7', '8', '9', '10', '11'];

				for (var i = 0; i < values.length; i++) {
					if (values[i] == 0) {
						continue;
					} else {
						dataPoints.push($.gchart.marker('flag', '0000FF', 0, i, 10, 'above', data.label[i]));
					}
				}

				chart = $.extend({}, {
					margins: [0,0,30,0],
					type: 'barVert',
					dataLabels: labels,
					encoding: 'scaled',
					series: [
						$.gchart.series('', values, '76A4FB')
					],
					markers: dataPoints
				});

				$('#urlActivityLastDayBarChart').gchart(chart);
			} else {
				$('#urlActivityLastDayBar .message .content').html(data.message);
				$('#urlActivityLastDayBar .message').show();
			}
		},
		'json'
	);
}

function loadUrlActivityByAccountLastWeekStacked() {
	var baseUrl, url, params;

	baseUrl = $('#form-edit input[name=base-url]').val();
	url = baseUrl + '/admin/charts/url-activity-by-account-last-week-stacked';
	params = {};

	$('#urlActivityByAccountLastWeekStacked .progress').show();

	$.get(
		url,
		params,
		function (data) {
			var values, chart, dataPoints, labels, color;

			$('#urlActivityByAccountLastWeekStacked .progress').hide();

			if (data.status === true) {
				values = data.message.result;
				chart = {};
				dataPoints = [];
				labels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
				color = null;

				for (var i = 0; i < values.length; i++) {
					color = ('00000'+(Math.random()*16777216<<0).toString(16)).substr(-6);
					dataPoints.push($.gchart.series(values[i]['label'], values[i]['points'], color));
				}

				chart = $.extend({}, {
					legend: 'right',
					type: 'barVert',
					dataLabels: labels,
					encoding: 'scaled',
					series: dataPoints,
				});

				$('#urlActivityByAccountLastWeekStackedChart').gchart(chart);
			} else {
				$('#urlActivityByAccountLastWeekStacked .message .content').html(data.message);
				$('#urlActivityByAccountLastWeekStacked .message').show();
			}
		},
		'json'
	);
}

$(document).ready(function () {
	$('.progress, .message').hide();

	loadCountryExposure();
	loadUrlActivityLastWeekScatter();
	loadUrlActivityLastDayBar();
	loadUrlActivityLastMonthBar();
	loadUrlActivityByAccountLastWeekStacked();
});
