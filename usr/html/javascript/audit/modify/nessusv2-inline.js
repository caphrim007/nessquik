/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window,$*/

function toggle(divId) {
	var divObj, displayType;

	divObj = document.getElementById(divId);
 
	if (divObj) {
		displayType = divObj.style.display;
		if (displayType === "" || displayType === "block") {
			divObj.style.display = "none";
		} else {
			divObj.style.display = "block";
		}	
	}
}
