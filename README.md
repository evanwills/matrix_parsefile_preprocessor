# Squiz Matrix Parse File builder and validator

This is a small tool to help with creating and maintaining [Squiz Matrix __Design Parse files__](https://matrix.squiz.net/manuals/designs).
It has a command line version which allows for building full parse files out of partials, is much the same way as something like [SASS](http://sass-lang.com/) uses to build CSS style sheets. It also has a web interface that allows basic validation of parse files

## Problem 1:
The only way to validate a [parse files](https://matrix.squiz.net/manuals/designs/chapters/design-asset#parse-file) is to upload it to Matrix. This is fine during initial development phase when the design hasn't got many customisation and hasn't been applied to too many assets. However, changing an existing parse file can take a very long time to process and, if there's an error in the code you've uploaded it can take the same amount of time again to fix the error. This tool attempts to provide a way of validating your parse-file (in seconds) before you upload it to Matrix.

## Problem 2:
When you update an existing parse file, any design areas that were in the existing parse file but not in the new version will have all their customisation setting permanently lost. This means if you inadvertently delete a design area on a production design it can have disastrous effects only fixable by doing a full restore from the most recent database backup, causing you to loose _**ALL**_ the changes made to Matrix since the last backup. This tool _(When I've built the code)_ will compare a specified old version of the file against the newly created version and identify any design areas missing from the new version.

## Problem 3:
In a single Matrix installation, you often have multiple sites. Most of these sites are likely to share significant portions of their designs and design parse files (e.g. In our main site, we have an Inside page design and a lighter weight _Home page_ design which shares all of the _Inside page_ design's header and footer sections but has a single design area for the main home page stuff). It would be good if you could break down parse files into partials then assemble the partials into different final design parse files. The command line interface for this too allows you to do this.

## How the validator works:

1.	Finds all `<MySource_AREA>` and `<MySource_PRINT>` tags
2.	`<MySource_AREA>` tags, it finds the `id_name` attribute and records the value. If there's a `print="no"` attribute, it also records the fact that the tag will not be rendered by matrix at the location it was created.
3.	`<MySource_PRINT>` tags, it compares the `id_name` with the list of unprinted `<MySource_AREA>`s and removes the ID from the list.
4.  `<MySource_AREA>` `design_area="show_if"` tags, it checks to see if they have `keyword_regexp` condition. If so, it validates the regular expression to ensure that if has an error you know about it.
5.	`<MySource_AREA>` tags with duplicate  `id_name` values are reported as errors.
6.  Reports any `<MySource_AREA>` tags that are never printed (this is not always a problem but having design areas that are never printed can needlessly add to the processing time of the design.)

### _To do:_
* Since normal Matrix key words (e.g. `%globals_asset_name%`) work in design parse files, It would be good if the validator checks these. Especially the modifiers.

## How the builder/compiler works:

1.	Searches the base parse file for [special keywords](#Keyword-structure)
2.	The code immediately preceding the keyword is
	1.	passed to the validator for validation.
	2.	the code is written to the output file.
3.	The keyword is analysed.
	1.	If a partial file can be found that matches the keyword then the contents of that file is pulled in.
	2.	any find/replace actions specified in the keyword are applied to the contents of the partial
4.	The partial is then (recursively) passed to the compiler in the same way the base parse file was.
5.	Once the last keyword is processed, the rest of the code is:
	1.	validated
	2.	written to the output file
6.	A report is generated containing a list (in order of occurrence) of any Errors, Warnings and Notices.

## How the old/new comparison works

1. 	It Searches through the old parse file for any `<MySource_AREA>` and records the `id_name` value for each
2. 	It Searches through the new parse file for any `<MySource_AREA>` and removes `id_name`s from the list of the ones in the old parse file
3.	Any `id_name`s left in the list from the old parse file are missing from the new parse file and displayed as warnings at the end of the validation phase.


## Keyword structure
```
{{path/to/partial/file|find|replace|modifiers}}
```

*	"__`{{`__" or "__`{[`__" or "__`{(`__"  delimiter
	*	`{{` partial is NOT wrapped in comments
	*	`{[` partial is wrapped in HTML comments `<!-- ... -->`
	*	`{(` partial is wrapped in CSS/JS comments `/* ... */`
	<br />__NOTE:__ the keyword match will find any combination of `[]{}` but will throw errors if the delimiters are not `{[...]}` or `{{...}}`
*	"__path/to/partial/__" relative or absolute path to partial by default partials should be
*	"__file__" name of partial file
	<br />__NOTE:__ partials (like in SASS) are prefixed with an underscore '_' and end with the .xml file extension
*	"__<code>&#96;</code>__" or "__`|`__" or "__`~`__" or "__;__" find/replace delimiter can be either backtick '&#96;', pipe '|' or tilda '~' or semicolon ';'
	<br />__NOTE:__ the delimiter you use in the keyword is also the delimiter used in the final regular expression. This may cause your regex problems.
	<br />__NOTE ALSO:__ The regex used to match the keyword is delimited by an __`@`__ symbol. If your regex needs to match __`@`__ symbols, you'll need to escape them too<br />(e.g. "`jo.smith\@company.org`")
*	"__find__"  find string or regex
*	"__replace__" replacement string or regex pattern
*	"__modifiers__" if regex is to be used, modifiers must contain '`R`' (for Regex) or any valid regex PHP PCRE modifier

## Config:

There are a few options that can be set either in a config file or at runtime these are:
*	__`output`__ {string} directory to save output (either relative to source file or absolute)
*	__`on_unprinted`__ {string} either 'show', 'fail' or 'hide':
	*	'show' [default] show the unprinted IDs, the line they were found on and the file they were found in
	*	'fail' same as show but stops processing if there are any unprinted IDs (__NOTE:__ there will be no output written to file)
	*	'hide' don't show unprinted IDs
*	__`unprinted_exceptions`__ {string} comma separated list of IDs that can be ignored if unprinted.
*	__`show_error_extended`__ {boolean} If there is an error show the whole partial/parse file where the error occured as well as the line and file name.
*	__`strip_comments`__ {boolean} strip_comments Strip HTML comments as output is being created.
*	__`white_space`__ {string} white_space How to handle white space during compile either 'normal', 'compact' or 'compressed':
	*	'normal' [default] do nothing (leave as is)
	*	'compact' delete spaces & tabs from start and end of lines
	*	'compressed' reduce multiple, consecutive white-spaces character to a single space character