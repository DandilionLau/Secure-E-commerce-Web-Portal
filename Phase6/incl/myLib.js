/* 
 * IERG4210 Web Programming and Security  - myLib.js
 * This file serves as a javascript library that is useful across a website. You're not 
 * expected to change this file. Instead, you should create your own Javascript file.
 */

// To extend the String prototype
String.prototype.escapeHTML = function() {
	return this.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
};
String.prototype.escapeQuotes = function() {
	return this.replace(/"/g,'&quot;').replace(/'/g,'&#39;');
};

// To add a global function el as a shortcut to getElementById()
window.el = function(A) {
	A = document.getElementById(A);
	if (A) {
		A.hide = function(){this.className = 'hide'};
		A.show = function(){this.className = ''};
	}
	return A;
};


(function(){
	
	var myLib = window.myLib = (window.myLib || {});

	// ##########################################
	//         PRIVATE FUNCTIONS of myLib
	// ##########################################
	
	// To prompt an error message and focus on the field concerned
	function alertFieldError(el, msg) {
		alert('FieldError: ' + msg);
		el.focus();
		return false
	};
	
	// To generate GET parameters based on properties of an object
	var encodeParam = function(obj) {
		var data = [];
		for (var key in obj)
			data.push(encodeURIComponent(key) + '=' + encodeURIComponent(obj[key]));
		return data.join('&');
	}
	
	// To generate POST parameters based on input controls of a form object
	var formData = function(form) {
		// private variable for storing parameters
		this.data = [];
		for (var i = 0, j = 0, name, el, els = form.elements; el = els[i]; i++) {
			// skip those useless elements
			if (el.disabled || el.name == '' 
				|| ((el.type == 'radio' || el.type == 'checkbox') && !el.checked))
				continue;
			// add those useful elements to the data array
			this.append(el.name, el.value);
		}
	};
	// To output the required final POST parameters, e.g. a=1&b=2&c=3
	formData.prototype.toString = function(){
		return this.data.join('&');
	};
	// To encode the data with the built-in function encodeURIComponent
	formData.prototype.append = function(key, val){
		this.data.push(encodeURIComponent(key) + '=' + encodeURIComponent(val));
	};


	// ##########################################
	//         PUBLIC FUNCTIONS of myLib
	// ##########################################
	
	// The base class of AJAX/XMLHttpRequest feature, you can use others library
	myLib.ajax = function(opt) {
		opt = opt || {};
		var xhr = (window.XMLHttpRequest) 
				? new XMLHttpRequest()                     // IE7+, Firefox1+, Chrome1+, etc
				: new ActiveXObject("Microsoft.XMLHTTP"),  // IE 6
			async = opt.async !== false,
			success = opt.success || null, 
			error = opt.error || function(){alert('AJAX Error: ' + this.status)};
			
		// pass three parameters, otherwise the default ones, to xhr.open()
		xhr.open(opt.method || 'GET', opt.url || '', async);
		
		if (opt.method == 'POST') 
			xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		
		// Asyhronous Call requires a callback function listening on readystatechange
		if (async)
			xhr.onreadystatechange = function(){
				if (xhr.readyState == 4) {
					var status = xhr.status, response = xhr.responseText;
					if ((status >= 200 && status < 300) || status == 304 || status == 1223) {
						success && success.call(xhr, (response.substr(0,9) == 'while(1);') ? response.substring(9) : response);
					} 
					else if (status >= 500)
					{
						error.call(xhr);
					}
				}
			};
		xhr.onerror = function(){error.call(xhr)};
		
		// POST parameters encoded as opt.data is passed here to xhr.send()
		xhr.send(opt.data || null);
		// Synchronous Call blocks UI and returns result immediately after xhr.send()
		!async && callback && callback.call(xhr, xhr.responseText);
	};
	
	// To get some content in JSON format with AJAX
	myLib.processJSON = function(url, param, successCallback, opt) {
		opt = opt || {};
		opt.url = url || 'admin-process.php';
		opt.method = opt.method || 'GET';
		if (param)
			opt.data = encodeParam(param);
			opt.success = function(json){
			json = JSON.parse(json);
			if (json.success)
				successCallback && successCallback.call(this, json.success);
			else 
				alert('Error: ' + json.failed);
		};
		myLib.ajax(opt);
	};
	// To get some content in JSON format with AJAX from the default admin-process.php?rnd=<currentTime>
	myLib.get = function(param, successCallback) {
		param = param || {};
		param.rnd =  new Date().getTime(); // to avoid caching in IE
		myLib.processJSON('admin-process.php?' + encodeParam(param), null, successCallback);
	};
	// To send an action to the admin-process.php over AJAX
	myLib.post = function(param, successCallback) {
		myLib.processJSON('admin-process.php?rnd=' + new Date().getTime(), param, successCallback, {method:'POST'});
	};

	// To validate if a form passes the client-side restrictions
	myLib.validate = function(form) {
		// Looping over every form control incl <input>, <textarea>, and <select>
		for (var i = 0, p, el, els = form.elements; el = els[i]; i++) {
			// bypass any disabled controls
			if (el.disabled) continue;
			// validate empty field, radio and checkboxes
			if (el.hasAttribute('required')) {
				if (el.type == 'radio') {
					if (lastEl && lastEl == el.name) 
						continue;
					for (var j = 0, chk = false, lastEl = el.name, choices = form[lastEl],
						choice; choice = choices[j]; j++)
							if (choice.checked) {chk = true; break;}
					if (!chk)
						return alertFieldError(el, 'choose a ' + el.title);
					continue;
				} else if ((el.type == 'checkbox' && !el.checked) || el.value == '') 
					return alertFieldError(el, el.title + ' is required');
			}
			if ((p = el.getAttribute('pattern')) && !new RegExp(p).test(el.value))
			return alertFieldError(el, 'in' + el.title);
		}
		return true;
	};
	
	// Given a form that passed the client-side restrictions, 
	//   submit the parameters based on input controls of a form object over AJAX,
	//     and calls the successCallback upon server response
	myLib.submit = function(form, successCallback) {
		myLib.validate(form) && myLib.ajax({
			method: 'POST',
			url: form.getAttribute('action') || 'admin-process.php',
			data: new formData(form).toString(),
			success: function(json){
				json = JSON.parse(json);
				if (json.success)
					successCallback && successCallback.call(this, json.success);
				else 
					alert('Error: ' + json.failed);
			}
		});
		return false;
	};

})();