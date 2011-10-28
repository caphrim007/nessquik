/**
* jQuery dirtyFields Plugin
* Created by Brian Swartzfager (http://www.thoughtdelimited.org/thoughts)
* 
* ---------------
* OVERVIEW
* ---------------
* 
* The dirtyFields jQuery plugin applies a CSS class to a contextual DOM
* element associated with any form field that has been changed from its
* original value. For text inputs, <textarea>, and <select> elements,
* the <label> element associated with the field (via the "for" attribute)
* will be styled with the class. For <checkbox> and <radio> elements,
* you can specify the text associated with each element using
* "checkboxRadioTextTarget" plugin option (the default is "next span").
*  
* You can also use the plugin to apply a CSS class to the form itself if
* any of the form fields are dirty. There are optional callback functions
* that will execute whenever a field or a form has been changed. The initial
* value of each form field is stored within the form field itself using the
* jQuery data() function, and a list of all currently dirty fields is stored
* within the form object in the same manner.
*  
* This plugin is useful when form submissions are done via AJAX and you want
* to show the user if they have any unsaved changes to the data.
*  
* --------------
* OPTIONS
* --------------
*  
* checkboxRadioTextTarget (default value: "next span") -- Tells the plugin
* where to apply the dirty field class when used with checkbox and radio
* elements. Possible values are "next {element tag}" (the sibling element
* following the checkbox/radio field, like a span, div, or label), "previous
* {element tag}", "id matches class" (any element with a CSS class that
* matches the id of the checkbox/radio field), "id matches title", and "id
* matches for" (any element with a "for" attribute that matches the id of
* the checkbox/radio field).
*  
* denoteChangedOptions (default value: false) -- Whether or not the dirtyOptionClass
* should be applied to changed <option> elements in multi-select <select> boxes.
*  
* denoteFormDirty (default value: false) -- Whether or not the dirtyFormClass
* should be applied to the form if any form field in the form is dirty
*  
* dirtyFieldClass (default value: "dirtyField") -- The name of the CSS class
* to apply to any form field that has been changed from its starting value.
*  
* dirtyFormClass (default value: "dirtyForm") -- The name of the CSS class to
* apply to any form containing dirty fields.  Only active if denoteFormDirty
* option set to true.
*  
* dirtyOptionClass (default value: "dirtyOption") -- The name of the CSS class
* to apply to any <option> element that has been selected/un-selected. Only
* active if the <select> box accepts multiple values and the denoteChangedOptions
* option is set to true. Note that some CSS styles (such as "color") will not
* work on <option> elements ("font-style" and "font-weight", however, will work)
*
* fieldChanged (default value: "") -- A callback function that executes every
* time a form field element has been changed. It returns the updated form
* element and a Boolean value denoting if the field is now dirty or not.
*  
* formIsDirty (default value: "") -- A callback option that returns the updated form as a jQuery object and a Boolean value denoting if the form is currently dirty or not.  The callback will only execute if denoteFormDirty is set to true.
*  
* trimText (default value: false) -- If set to true, will ignore leading or trailing spaces in a text input or <textarea> when evaluating if a field value has changed or not (in other words, adding a trailing space to the value of a text input will not apply the dirtyFieldClass to the field.  Set this option to true if you use server-side code to strip out leading and trailing spaces in textual data before saving it.
*  
*  ----------------
*  FUNCTIONS
*  ----------------
*  
*  dirtyFields -- The main plugin function.  Preceded/applied to a selector containing form field elements (a single form denoted by an id value or name, a <div> containing one or more forms, "forms", etc.).  Accepts overrides to the default settings.  
*  
*  Example:  
*  	var settings= {
*  		denoteFormDirty: true,
*  		dirtyFieldClass:"makeDirty",
*  		formIsDirty: function(result) {
*  			if(result) {
*  				//Form is dirty: enable form's submit button
*  				$(this).children("input[type='submit']").attr("disabled",false);
*  			}
*  			else {
*  				//Form is clean: disable form's submit button
*  				$(this).children("input[type='submit']").attr("disabled",true);
*  			}
*  		}
*  	};
*  	$("form").dirtyFields(settings);
*  
*  $.fn.dirtyFields.resetForm -- This function is designed to be used after a form's values have been submitted.  It executes the $.fn.dirtyFields.setStartingValues to update the current value/state of each form field element, then marks all the form fields as clean.
*  
*  $.fn.dirtyFields.setStartingValues -- Called during the execution of the dirtyFields function, this function takes the current value/state of each form field element and stores it as the "startingValue" metadata value of the element using the jQuery.data() function.
*  
*  -----------------------
*  EXAMPLE CODE
*  -----------------------
*  
*  The following code illustrates how to change the behavior of the plugin via the options to enable the form's submit button when the form is dirty (using the formIsDirty callback), and then how to use the resetForm() function of the plugin to mark everything as clean once the form changes have been submitted:
*  
*  <script type="text/javascript" src="jquery-1.3.2.min.js"></script>
* <script type="text/javascript" src="jquery.dirtyFields.min.js"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			var settings= {
   				denoteFormDirty: true,
   				dirtyFieldClass:"makeDirty",
  				formIsDirty: function(result) {
   					if(result) {
		   				//Form is dirty: enable form's submit button
		   				$(this).children("input[type='submit']").attr("disabled",false);
		   			}
		   			else {
		   				//Form is clean: disable form's submit button
		   				$(this).children("input[type='submit']").attr("disabled",true);
		   			}
   				}
   			};
   	
		  $("form").dirtyFields(settings);
		
		  $("form").submit(function() {
			  //Run a function to submit the form values via AJAX, then...
			  $.fn.dirtyFields.resetForm($("form"));
		  });
		});
	</script>
*  
**/ 

(function($) {
	$.fn.dirtyFields = function(parameters) {
		var opts = $.extend({}, $.fn.dirtyFields.defaults, parameters);

		return this.each(function() {
			var $targetElement = $(this);

			for (var dataName in opts) {
				fieldArray = new Array();
				$targetElement.data(dataName,opts[dataName]);
				$targetElement.data("dirtyFields",fieldArray);
			}

			$("input[type='text'],textarea",$targetElement).change(function() {
				var $elem = $(this);
				var $form = $elem.parents("form");

				var elemName = $elem.attr("name");
				var elemDirty = false;

				if(opts.trimText) {
					var elemValue = jQuery.trim($elem.val());
				} else {
					var elemValue = $elem.val();
				}

				if (elemValue != $elem.data("startingValue")) {
					$("label[for='" + elemName + "']",$form).addClass(opts.dirtyFieldClass);
					updateDirtyFieldsArray(elemName,$form,"dirty");
					elemDirty = true;
				} else {
					$("label[for='" + elemName + "']",$form).removeClass(opts.dirtyFieldClass);
					updateDirtyFieldsArray(elemName,$form,"clean");
				}

				if($.isFunction(opts.fieldChanged)) {
					opts.fieldChanged.call($elem,elemDirty);
				}

				if(opts.denoteFormDirty) {
					updateFormStatus($elem.parents("form"),opts);
				}
			});

			$("select",$targetElement).change(function() {
				var $elem = $(this);
				var $form = $elem.parents("form");

				var elemName = $elem.attr("name");
				var elemDirty = false;

				if($elem.attr("multiple") && opts.denoteChangedOptions) {
						var optionsDirty = false;
						$elem.children("option").each(function(o) {
							var $option = $(this);
							var isSelected = $option.attr("selected");
							if(isSelected != $option.data("startingValue")) {
								$option.addClass(opts.dirtyOptionClass);
								optionsDirty = true;
							} else {
								$option.removeClass(opts.dirtyOptionClass);
							}
						});

						if(optionsDirty) {
							$("label[for='" + elemName + "']",$form).addClass(opts.dirtyFieldClass);
							updateDirtyFieldsArray(elemName,$form,"dirty");
							elemDirty = true;
						} else {
							$("label[for='" + elemName + "']",$form).removeClass(opts.dirtyFieldClass);
							updateDirtyFieldsArray(elemName,$form,"clean");
						}
				} else {
					if ($elem.val() != $elem.data("startingValue")) {
						$("label[for='" + elemName + "']",$form).addClass(opts.dirtyFieldClass);
						updateDirtyFieldsArray(elemName,$form,"dirty");
						elemDirty = true;
					} else {
						$("label[for='" + elemName + "']",$form).removeClass(opts.dirtyFieldClass);
						updateDirtyFieldsArray(elemName,$form,"clean");
					}
				}

				if($.isFunction(opts.fieldChanged)) {
					opts.fieldChanged.call($elem,elemDirty);
				}

				if(opts.denoteFormDirty) {
					updateFormStatus($elem.parents("form"),opts);
				}
			});

			$(":checkbox,:radio",$targetElement).change(function() {
				var $elem = $(this);
				var $form = $elem.parents("form");
				var elemName = $elem.attr("name");
				var elemDirty = false;
				
				var isChecked = $elem.attr("checked");
				if(isChecked != $elem.data("startingValue")) {
					updateCheckboxRadioElement($elem,"changed",$form,opts);
					elemDirty = true;
				} else {
					updateCheckboxRadioElement($elem,"unchanged",$form,opts);
				}

				if($elem.attr("type")== 'radio') {
					$(":radio[name='" + elemName + "']",$form).each(function(r) {
						var $thisRadio = $(this);
						var thisIsChecked = $thisRadio.attr("checked");
						if(thisIsChecked != $thisRadio.data("startingValue")) {
							updateCheckboxRadioElement($thisRadio,"changed",$form,opts);
							elemDirty = true;
						} else {
							updateCheckboxRadioElement($thisRadio,"unchanged",$form,opts);
						}
					});
				}

				if(elemDirty) {
					updateDirtyFieldsArray(elemName,$form,"dirty");
				} else {
					updateDirtyFieldsArray(elemName,$form,"clean");
				}

				if($.isFunction(opts.fieldChanged)) {
					opts.fieldChanged.call($elem,elemDirty);
				}

				if(opts.denoteFormDirty) {
					updateFormStatus($elem.parents("form"),opts);
				}
			});

			$.fn.dirtyFields.setStartingValues($targetElement);
		});
	};

	$.fn.dirtyFields.defaults = {
		   dirtyFieldClass: "dirtyField",
		   checkboxRadioTextTarget: "next span",
		   denoteFormDirty: false,
		   dirtyFormClass: "dirtyForm",
		   denoteChangedOptions: false,
		   dirtyOptionClass: "dirtyOption",
		   trimText: false,
		   formIsDirty: "",
		   fieldChanged: ""
	};

	$.fn.dirtyFields.resetForm = function($targetElement) {
		var fieldArray = new Array();

		$.fn.dirtyFields.setStartingValues($targetElement);

		$targetElement.data("dirtyFields",fieldArray);
		
		$("." + $targetElement.data("dirtyFieldClass"),$targetElement).removeClass($targetElement.data("dirtyFieldClass"));
		if($targetElement.data("denoteChangedOptions")) {
			$("." + $targetElement.data("dirtyOptionClass"),$targetElement).removeClass($targetElement.data("dirtyOptionClass"));
		}

		if($targetElement.data("denoteFormDirty")) {
			$targetElement.removeClass($targetElement.data("dirtyFormClass"));
		}
	};

	$.fn.dirtyFields.setStartingValues= function($targetElement) {
		$("input[type='text'],select,:checkbox,:radio,textarea",$targetElement).each(function(i) {
			$elem = $(this);
			if($elem.attr("type") == "radio" || $elem.attr("type") == "checkbox") {
				if($elem.attr("checked")) {
					$elem.data("startingValue",true);
				} else {
					$elem.data("startingValue",false);
				}
			} else {
				$elem.data("startingValue",$elem.val());
			}

			if($elem.attr("multiple") && $targetElement.data("denoteChangedOptions")) {
				$elem.children("option").each(function(o) {
					var $option = $(this);
					if($option.attr("selected")) {
						$option.data("startingValue",true);
					} else {
						$option.data("startingValue",false);
					}
				});
			}
		});
	};  

	function updateDirtyFieldsArray(elemName,$form,status) {
		var dirtyFieldsArray = $form.data("dirtyFields");
		var index = $.inArray(elemName,dirtyFieldsArray);
		if(status == "dirty" && index == -1) {
			dirtyFieldsArray.push(elemName);
			$form.data("dirtyFields",dirtyFieldsArray);
		} else if(status == "clean" && index > -1) {
			dirtyFieldsArray.splice(index,1);
			$form.data("dirtyFields",dirtyFieldsArray);
		}
	};

	function updateFormStatus($form,opts) {
		if($("." + opts.dirtyFieldClass,$form).length > 0) {
			$form.addClass(opts.dirtyFormClass);
			if($.isFunction(opts.formIsDirty)) {
				opts.formIsDirty.call($form,true,$form.data("dirtyFields"));
			}
		} else {
			$form.removeClass(opts.dirtyFormClass);
			if($.isFunction(opts.formIsDirty)) {
				opts.formIsDirty.call($form,false,$form.data("dirtyFields"));
			}
		}
	};

	function updateCheckboxRadioElement($elem,status,$form,opts) {
		var updateSettings= opts.checkboxRadioTextTarget;
		var updateSettingsArray= updateSettings.split(" ");
		
		switch (updateSettingsArray[0]) {
			case "next":
				if(status == "changed") {
					$elem.next(updateSettingsArray[1]).addClass(opts.dirtyFieldClass);
				} else {
					$elem.next(updateSettingsArray[1]).removeClass(opts.dirtyFieldClass);
				}
				break;
			case "previous":
				if(status == "changed") {
					$elem.prev(updateSettingsArray[1]).addClass(opts.dirtyFieldClass);
				} else {
					$elem.prev(updateSettingsArray[1]).removeClass(opts.dirtyFieldClass);
				}
				break;
			case "id":
				switch (updateSettingsArray[2]) {
					case "class":
						if(status == "changed") {
							$("." + $elem.attr("id"),$form).addClass(opts.dirtyFieldClass);
						} else {
							$("." + $elem.attr("id"),$form).removeClass(opts.dirtyFieldClass);
						}
						break;
					case "title":
						if(status == "changed") {
							$("*[title='" + $elem.attr("id") + "']",$form).addClass(opts.dirtyFieldClass);
						} else {
							$("*[title='" + $elem.attr("id") + "']",$form).removeClass(opts.dirtyFieldClass);
						}
						break;
					case "for":
						if(status== "changed") {
							$("*[for='" + $elem.attr("id") + "']",$form).addClass(opts.dirtyFieldClass);
						} else {
							$("*[for='" + $elem.attr("id") + "']",$form).removeClass(opts.dirtyFieldClass);
						}
						break;
				}
				break;
		}
	};
})(jQuery);
