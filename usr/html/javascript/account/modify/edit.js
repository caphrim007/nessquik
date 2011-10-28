/*jslint browser: true, devel: true, cap: false, maxerr: 65535*/
/*global window, $*/
/* vim: set ts=4:sw=4:sts=4smarttab:expandtab:autoindent */

$(document).ready(function () {
	var baseUrl;

	baseUrl = $('#form-edit input[name=base-url]').val();

	$('.message').hide();
	$('.message .close').click(function () {
		$(this).parents('.message').hide();
	});

	$('#form-submit input[name=password]').keypress(function (event) { 
		if (event.which === 13) {
			event.preventDefault();
			$('#btn-save').trigger('click');
			return true;
		}
	});

	$('#btn-save').click(function () {
		var url, params, username;

		url = baseUrl + '/account/modify/save';
		params = $("#form-submit").serialize(); 
		username = $('#form-submit input[name=username]').val();

		if (username === '') {
			$('.message .content').html('The username cannot be empty');
			$('.message').show();
			return false;
		}

		$('#btn-save').attr('disabled', 'disabled');

		$.post(
			url,
			params,
			function (data) {
				if (data.status === true) {
					window.location = baseUrl + '/admin/account';
				} else {
					$('#btn-save').attr('disabled', '');
					$('.message .content').html(data.message);
					$('.message').show();
				}
			},
			'json'
		);
	});

	$('#generate').click(function(){
		var password;

		password = RandomPassword.generate(13);
		$('#form-submit input[name=password]').val(password);
	});
});

var RandomPassword = {
	_characters: [],
	_noSim: 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrsuvwxyz23456789~!@#$%^&*_+=-?:',
	_similar: '0olt01',

	generate: function(length) {
		this._includeLetters();
		this._includeMixedCase();
		this._includeNumbers();
		this._includePunctuation();

		chars = this.array_unique(this._characters);
		chars = this.array_values(chars);
		max = chars.length - 1;

		i = 0;
		j = 0;

		pass = '' ;

		while (i <= length) {
			num = this.mt_rand(0, max);
			pass = pass + chars[num];
			i++;
		}

		return pass;
	},

	array_unique: function(inputArr) {
		var key = '', tmp_arr2 = {}, val = ''; 
		var __array_search = function (needle, haystack) {
			var fkey = '';
			for (fkey in haystack) {
				if (haystack.hasOwnProperty(fkey)) {
					if ((haystack[fkey] + '') === (needle + '')) {
						return fkey;
					}
				}
			}
			return false;
		};
 
		for (key in inputArr) {
			if (inputArr.hasOwnProperty(key)) {
				val = inputArr[key];
				if (false === __array_search(val, tmp_arr2)) {
					tmp_arr2[key] = val;
				}
			}
		}

		return tmp_arr2;
	},

	array_values: function(input) {
		var tmp_arr = [], cnt = 0;
		var key = ''; 
		for ( key in input ){
			tmp_arr[cnt] = input[key];
			cnt++;
		} 

		return tmp_arr;
	},

	mt_rand: function(min, max) {
		var argc = arguments.length;
		if (argc === 0) {
			min = 0;
			max = 2147483647;
		} else if (argc === 1) {
			throw new Error('Warning: mt_rand() expects exactly 2 parameters, 1 given');
		}

		return Math.floor(Math.random() * (max - min + 1)) + min;
	},

	array_merge: function() {
		var args = Array.prototype.slice.call(arguments), retObj = {}, k, j = 0, i = 0, retArr = true;

		for (i=0; i < args.length; i++) {
			if (!(args[i] instanceof Array)) {
				retArr=false;
				break;
			}
		}
    
		if (retArr) {
			retArr = [];
			for (i=0; i < args.length; i++) {
				retArr = retArr.concat(args[i]);
			}
			return retArr;
		}

		var ct = 0;
    
		for (i=0, ct=0; i < args.length; i++) {
			if (args[i] instanceof Array) {
				for (j=0; j < args[i].length; j++) {
					retObj[ct++] = args[i][j];
				}
			} else {
				for (k in args[i]) {
					if (args[i].hasOwnProperty(k)) {
						if (parseInt(k, 10)+'' === k) {
							retObj[ct++] = args[i][k];
						} else {
							retObj[k] = args[i][k];
						}
					}
				}
			}
		}

		return retObj;
	},

	str_split: function(string, split_length) {
		if (split_length === null) {
			split_length = 1;
		}

		if (string === null || split_length < 1) {
			return false;
		}

		string += '';
		var chunks = [], pos = 0, len = string.length;
		while (pos < len) {
			chunks.push(string.slice(pos, pos += split_length));
		}

		return chunks;
	},

	in_array: function(needle, haystack, argStrict) {
		var key = '', strict = !!argStrict; 
		if (strict) {
			for (key in haystack) {
				if (haystack[key] === needle) {
					return true;
				}
			}
		} else {
			for (key in haystack) {
				if (haystack[key] == needle) {
					return true;
				}
			}
		}

		return false;
	},

	_includeLetters: function() {
		chars = 'abcdefghijklmnopqrstuvwxyz';
		this._characters = this.array_merge(this._characters, this.str_split(chars, 1));
	},

	_includeMixedCase: function() {
		chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
		this._characters = this.array_merge(this._characters, this.str_split(chars, 1));
	},

	_includeNumbers: function() {
		chars = '0123456789';
		this._characters = this.array_merge(this._characters, this.str_split(chars, 1));
	},

	_includePunctuation: function() {
		// I'm deliberately not including all possible punctuation here
		chars = '~!@#$%^&*_+=-?:';
		this._characters = this.array_merge(this._characters, this.str_split(chars, 1));
	}
};
