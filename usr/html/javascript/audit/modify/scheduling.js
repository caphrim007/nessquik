/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

function updateRepeatEvery() {
	var type, value, typeText;

	type = $('#enableScheduling').val();
	value = $('#repeatEvery select[name=repeatEvery]').val();
	typeText = $('#repeatEveryText');

	switch (type) {
	case 'daily':
		if (value === 1) {
			typeText.html('day');
		} else {
			typeText.html('days');
		}
		break;
	case 'weekly':
		if (value === 1) {
			typeText.html('week');
		} else {
			typeText.html('weeks');
		}
		break;
	case 'monthly':
		if (value === 1) {
			typeText.html('month');
		} else {
			typeText.html('months');
		}
		break;
	case 'yearly':
		if (value === 1) {
			typeText.html('year');
		} else {
			typeText.html('years');
		}
		break;
	}
}

function updateSchedulingText() {
	var type, value, textSchedule, textString, startOn, d1, d2,
	    found, until, day, occurrence, occurStr, repeatOn, weekday,
	    weekdaySelect, startOnTime;

	type = $('#enableScheduling').val();
	value = $('#repeatEvery select[name=repeatEvery]').val();
	textSchedule = $('#textScheduling');
	textString = '';
	startOn = null;
	d1 = null;
	d2 = null;
	found = false;
	until = null;
	day = 0;
	occurrence = 0;
	occurStr = '';
	repeatOn = null;
	weekday = 0;
	weekdaySelect = [];

	switch (type) {
	case 'daily':
		if (value === 1) {
			textString = 'Daily';
		} else {
			textString = 'Every ' + value + ' days';
		}
		break;
	case 'everyWeekday':
		textString = 'Weekly on weekdays';
		break;
	case 'everyMonWedFri':
		textString = 'Weekly on Monday, Wednesday, Friday';
		break;
	case 'everyTueThu':
		textString = 'Weekly on Tuesday, Thursday';
		break;
	case 'weekly':
		if (value === 1) {
			textString = 'Weekly';
		} else {
			textString = 'Every ' + value + ' weeks';
		}

		if ($('#repeatOn input[type=checkbox]:checked').size() > 0) {
			textString = textString + ' on ';

			$('#repeatOn input[type=checkbox]:checked').each(function () {
				weekday = $(this).val();

				if (weekday === 0) {
					weekdaySelect.push('Sunday');
				}
				if (weekday === 1) {
					weekdaySelect.push('Monday');
				}
				if (weekday === 2) {
					weekdaySelect.push('Tuesday');
				}
				if (weekday === 3) {
					weekdaySelect.push('Wednesday');
				}
				if (weekday === 4) {
					weekdaySelect.push('Thursday');
				}
				if (weekday === 5) {
					weekdaySelect.push('Friday');
				}
				if (weekday === 6) {
					weekdaySelect.push('Saturday');
				}
			});

			if (weekdaySelect.length === 7) {
				textString = textString + 'all days';
			} else {
				textString = textString + weekdaySelect.join(', ');
			}
		}
		break;
	case 'monthly':
		startOn = $('#rangeStart input[name=rangeStart]').val();
		d1 = Date.parse(startOn);
		d2 = d1.clone();
		found = false;

		if (value === 1) {
			textString = 'Monthly';
		} else {
			textString = 'Every ' + value + ' months';
		}

		for (day = 0; day <= 6; day = day + 1) {
			for (occurrence = -1; occurrence <= 4; occurrence = occurrence + 1) {
				if (occurrence === 0) {
					continue;
				} else {
					d2.moveToNthOccurrence(day, occurrence);
					if (d1.equals(d2)) {
						if (occurrence === -1) {
							occurStr = 'last';
						} else if (occurrence === 1) {
							occurStr = 'first';
						} else if (occurrence === 2) {
							occurStr = 'second';
						} else if (occurrence === 3) {
							occurStr = 'third';
						} else if (occurrence === 4) {
							occurStr = 'fourth';
						} else if (occurrence === 5) {
							occurStr = 'fifth';
						}
							
						found = true;
						break;
					}
				}

				if (found === true) {
					break;
				}
			}

			if (found === true) {
				break;
			}
		}

		if ($('#repeatBy input[type=radio][value=byMonthDay]').is(':checked')) {
			textString = textString + ' on day ' + d1.toString('d ');
		} else {
			textString = textString + ' on the ' + occurStr + d1.toString(' dddd');
		}
		break;
	case 'yearly':
		startOn = $('#rangeStart input[name=rangeStart]').val();
		d1 = Date.parse(startOn);

		if (value === 1) {
			textString = 'Annually';
		} else {
			textString = 'Every ' + value + ' years';
		}

		textString = textString + ' on ' + d1.toString('MMMM d, yyyy');
		break;
	}

	if (type === 'doesNotRepeat') {
		$('#atTime').hide();
	} else {
		$('#atTime').show();
	}

	startOnTime = $('#startOnTime').val();
	textString = textString + ' at ' + startOnTime;

	if ($('#rangeEnd input[type=radio][value=until]').is(':checked')) {
		until = $('#rangeUntil').val();
		d1 = Date.parse(until);
		textString = textString + ', until ' + d1.toString('MMMM d, yyyy');
	}

	textSchedule.html(textString);
}

function customRange(input) {
	return {minDate: (input.name === 'rangeUntil' ? $('#rangeStart input[name=rangeStart]').dateEntry('getDate') : null),
		maxDate: (input.name === 'rangeStart' ? $('#rangeUntil').dateEntry('getDate') : null)}; 
}

$(document).ready(function () {
	var searchTimeout, dateScheduled;

	searchTimeout = undefined;
	dateScheduled = Date.parse($('#date-scheduled').val());

	$('.scheduling').hide();

	$('#rangeUntil, #rangeStart input[name=rangeStart]').dateEntry({
		spinnerImage: 'usr/images/spinnerUpDown.png',
		spinnerSize: [15, 16, 0],
		spinnerIncDecOnly: true,
		beforeShow: customRange
	});

	$('#rangeEnd input[type=radio][value=never]').click(function () {
		$('#rangeUntil').val(null);
		$('#rangeUntil').dateEntry('disable');
	});
	$('#rangeEnd input[type=radio][value=until]').click(function () {
		$('#rangeUntil').dateEntry('enable');
		if ($('#rangeUntil').val() === '') {
			var startDate = $('#rangeStart input[name=rangeStart]').dateEntry('getDate');
			startDate.setDate(startDate.getDate() + 7);
			$('#rangeUntil').dateEntry('setDate', startDate);
		}
	});

	if ($('#rangeUntil').val() === '') {
		$('#rangeUntil').dateEntry('disable');
	}
	if ($('#rangeStart input[name=rangeStart]').val() === '') {
		$('#rangeStart input[name=rangeStart]').dateEntry('setDate', null);
	}

	$('#enableScheduling').change(function () {
		var index = $('#enableScheduling').val();

		$('.scheduling').hide();

		if (index === 'doesNotRepeat') {
			$('#form-submit input[name=scheduling]').val(0);
			return;
		} else {
			$('#form-submit input[name=scheduling]').val(1);
		}

		switch (index) {
		case 'daily':
			$('#repeatEvery').show();
			break;
		case 'everyWeekday':
		case 'everyMonWedFri':
		case 'everyTueThu':
			break;
		case 'weekly':
			$('#repeatEvery, #repeatOn').show();
			break;
		case 'monthly':
			$('#repeatEvery, #repeatBy').show();
			break;
		case 'yearly':
			$('#repeatEvery').show();
			break;
		}

		$('#range, #textScheduling').show();
		updateRepeatEvery();
	});

	$('#repeatEvery select[name=repeatEvery]').change(function () {
		updateRepeatEvery();
	});

	$('#scheduling input').click(function (event) {
		updateSchedulingText();
	});

	$('#scheduling select').change(function () {
		updateSchedulingText();
	});

	$('#startOnTime').change(function () {
		var startOnTime = $(this).val();
		$('#date-scheduled').val(startOnTime);
		updateSchedulingText();
	});

	if ($('#enableScheduling').val() !== 'doesNotRepeat') {
		$('#enableScheduling').trigger('change');
	}

	$('#startOnTime').timeEntry({
		spinnerImage: 'usr/images/spinnerUpDown.png',
		spinnerSize: [15, 16, 0],
		spinnerIncDecOnly: true,
		defaultDate: dateScheduled
	});
	$('#startOnTime').timeEntry('setTime', dateScheduled);

	$('#rangeStart .dateEntry_control, #rangeEnd .dateEntry_control').live('click onChange', function () {
		updateSchedulingText();
	});

	$('#rangeStart .hasDateEntry, #rangeEnd .hasDateEntry').live('change', function () {
		updateSchedulingText();
	});
});
