
function matrixParseFileAssembler(sourcePath, sourceFile, partials) {
	'use strict';

	var fs = require('fs'),
		incRegex = new RegExp('(\\{[\\[\\{].*?([\\]\\}])\\})', 'g'),
		incPartsRegex = new RegExp('^\\{[\\[\\{]((?:[a-zA-Z0-9 _-]+/)+)?([a-zA-Z0-9 _-]+)(?:\\|([^|]+)\\|([^\\]\\}\\|]+)(?:\\|([Rgimy]{1,4}))?)?([\\]\\}])\\}$', 'i'),
		trimLineRegex = new RegExp('(?:^(?:[\\t ]*[\\r\\n]+)+|(?:[\\r\\n]+[\\t ]*)+$)'),
		commentRegex = new RegExp('^\\s*/\\*'),
		source = '',
		finds = [],
		i = 0,
		j = 0;

	source = fs.readFileSync(sourcePath + '/' + sourceFile, encoding);

	if (source === undefined) {
		// write out error
		exit;
	}

	if (partials === undefined) {
		// use the default partials directory
		sourcePath += 'partials/';
	} else if (partials !== false) {
		if (fs.readFileSync(sourcePath + partials + '/')) {
			sourcePath += partials + '/';
		} else {
			// supplied partials directory doesn't exist
			// write out errro
			exit;
		}
	} // console.log(sourcePath);
	sourcePath = fs.realpathSync(sourcePath); // console.log(sourcePath);

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
			inc,

			/**
			 * @var output {string} the compiled result of parsing the input file
			 */
			output = '',

			/**
			 * @ver parentPath {string} the value of the sourcePath at the start of the function call (used to return the sourcePath value to it's original state and the end of the function call);
			 */
			parentpath = sourcePath;

		inc = incPartsRegex.exec(keyword);
		sourcePath += inc[1];

		child = fs.readFileSync(sourcePath + '_' + inc[2] + '.xml', encoding);

		if (child !== undefined) {
			child = child.replace(trimLineRegex, '');

			if (inc[3] !== '') {
				// do a standard string replace on the partial
				child = child.replace(inc[3], inc[4]);
			}

			if (inc[6] === ']') {
				// don't wrap this partial in comments
				output = "\n" + child + "\n";
			} else {
				if (commentRegex.test(child)) {
					// this partial uses CSS/JS comments instead of HTML comments
					commentOpen = '/*';
					commentClose = '*/';
				}
				commentOpen = "\n" + commentOpen + '|| ';
				commentClose = ' ||' + commentClose + "\n";

				// wrap the partial in comments.
				output = commentOpen + 'START: ' + inc[1] + inc[2] + commentClose + output + commentOpen + ' END:  ' + inc[1] + inc[2] + commentClose;
			}

		} else {
			// check if the partial needs to be parsed like its parent
			child = fs.readFileSync(sourcePath + inc[2] + '.xml', encoding);
			if (child !== undefined) {
				output = matrixParseFileAssembler(sourcePath, inc[2] + '.xml', false);
			} else {
				// partial could not be found.
				// write out error
				exit;
			}
		}

		// return sourcePath to its original value.
		sourcePath = parentpath;
		return output;
	}

	// find all the includes
	finds = incRegex.exec(source);
	j = finds.length;

	// loop through each include
	for (i = 0; i < j; i += 1) {
		// updated the source, replacing the include string with the contents of the include
		source = source.replace(incRegex, addPart('$1'));
	}

	return source;
}

