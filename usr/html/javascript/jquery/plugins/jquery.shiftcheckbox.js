/**
 * JQuery shiftcheckbox plugin
 *
 * shiftcheckbox provides a simpler and faster way to select/unselect multiple checkboxes within a given range with just two clicks.
 * Inspired from GMail checkbox functionality
 *
 * Just call $('.<class-name>').shiftcheckbox() in $(document).ready
 *
 * @name shiftcheckbox
 * @type jquery
 * @cat Plugin/Form
 * @return JQuery
 *
 * Copyright (c) 2009 Aditya Mooley <adityamooley@sanisoft.com>
 * Dual licensed under the MIT (MIT-LICENSE.txt) and GPL (GPL-LICENSE.txt) licenses
 */

(function($) {
	$.fn.shiftcheckbox = function() {
		var prevChecked = null;
		var selectorStr = this;

		$(selectorStr).bind("click", function(event){
			var val = this.value;
			var checkStatus = this.checked;

			//check whether user has pressed shift
			if (event.shiftKey) {
				if (prevChecked != 'null') {
					//get the current checkbox number
					var ind = 0
					var found = 0
					var currentChecked = {};
					currentChecked = getSelected(val);

					ind = 0;
					if (currentChecked < prevChecked) {
						$(selectorStr).each(function(i) {
							if (ind >= currentChecked && ind <= prevChecked) {
								this.checked = checkStatus;
							}
							ind += 1;
						});
					} else {
						$(selectorStr).each(function(i) {
							if (ind >= prevChecked && ind <= currentChecked) {
								this.checked = checkStatus;
							}
							ind += 1;
						});
					}
	
					prevChecked = currentChecked;
				}
			} else {
				if (checkStatus) {
					prevChecked = getSelected(val);
				}
			}
		});

		function getSelected(val) {
			var ind = 0, found = 0, checkedIndex;

			$(selectorStr).each(function(i) {
				if (val == this.value && found != 1) {
					checkedIndex = ind;
					found = 1;
				}
				ind += 1;
			});

			return checkedIndex;
		};
	}
})(jQuery);
