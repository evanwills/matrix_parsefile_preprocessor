
function matrixParseFileAssembler() {
	'use strict';

	var //fs = require('fs'),
		incRegex = new RegExp('(\\{[\\[\\{].*?([\\]\\}])\\})', 'g'),
		incPartsRegex = new RegExp('^\\{[\\[\\{]((?:[a-zA-Z0-9 _-]+/)+)?([a-zA-Z0-9 _-]+)(?:\\|([^|]+)\\|([^\\]\\}\\|]+)(?:\\|([Rgimy]{1,4}))?)?([\\]\\}])\\}$', 'i'),
		trimLineRegex = new RegExp('(?:^(?:[\\t ]*[\\r\\n]+)+|(?:[\\r\\n]+[\\t ]*)+$)'),
		commentRegex = new RegExp('^\\s*/\\*'),
		source = '',
		finds = [],
		i = 0,
		j = 0;

	source = document.getElementById('preparse').innerHTML;


	/**
	 * find the partial, extracts its contents, parses it (if neccessary),
	 * wraps it in comments (if appropriate) and returns it as a string
	 * @param   {string} keyword preparse file keyword found by incRegex
	 * @returns {string} the contents of a partial to be included in the parent
	 */
	function addPart(keyword) {
		/**
		 * @var {string} child the contents of the partial to be inserted into the parent file
		 */
		var child = '',
			/**
			 * @var commentOpen {string} the opening of a partial's wrapping commnet
			 */
			commentOpen = '<!--',
			/**
			 * @var closeOpen {string} the close of a partial's wrapping commnet
			 */
			commentClose = '-->',

			/**
			 * @var {array} inc array of keyword parts
			 */
			inc = [],

			/**
			 * @var output {string} the compiled result of parsing the input file
			 */
			output = '',

			kwdReplace = function(input) { return input; },
			path = '',
			file = '',
			kwdUsrRegex = null,


			/**
			 * @ver parentPath {string} the value of the sourcePath at the start of the function call (used to return the sourcePath value to it's original state and the end of the function call);
			 */
			parentPath;// = sourcePath;

		inc = incPartsRegex.exec(keyword);
		if (inc[1] !== undefined) {
			path = inc[1];
		}
		file = inc[2];
		if (inc[3] !== undefined) {

			if (inc[4] === undefined) {
				inc[4] = '';
			}

			if (inc[5] !== undefined) {
				inc[5] = inc[5].replace('R', '');
				try {
					kwdUsrRegex = new RegExp(inc[3], inc[5]);
					kwdReplace = function (input) { return input.replace(kwdUsrRegex, inc[4]); };
				} catch (e) {
					console.error(e);
				}

			} else {
				kwdReplace = function (input) { return input.replace(inc[3], inc[4]); };
			}
		}
		console.log(keyword);
		console.log(inc);
	}

	// updated the source, replacing the include string with the contents of the include
	source = source.replace(incRegex, addPart);
}

matrixParseFileAssembler();