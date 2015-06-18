# Squiz Matrix Parse File Preprocessor

This is an attempt at making a preprocessor to allow for reusable parsefile chunks to be assembled then checked for basic syntax errors

Inspired by the Sass CSS Preprocessor, SMPFP (for want of a better name) will:

1.	read through a file finding keywords delimited by `{{...}}` or `{[...]}` and what ever HTML or Matrix code preceeds the keyword
2.	syntax check Matrix code preceeding matched keyword
3.	search for file partials identified by the keywords
4.	modify partials using basic find/replace or regex/replace as required
5.	syntax check partial
6.	and replace the keyword with the (possibly modified) contents of the partial file

## Keyword structure:
```
{{path/to/partial/file|find|replace|modifiers}}
```

*	"__`{{`__" or "__`{[`__"  delimiter
	*	`{{` partial is wrapped in comments
	*	`{[` partial is NOT wrapped in comments
	<br />__NOTE:__ the keyword match will find any combination of `[]{}` but will throw errors if the delimiters are not `{[...]}` or `{{...}}`
*	"__path/to/partial/__" relative or absolute path to partial by default partials should be
*	"__file__" name of partial file
	<br />__NOTE:__ partials (like in SASS) are prefixed with an underscore '_' and end with the .xml file extension
*	"__<code>&#96;</code>__" or "__`|`__" or "__`~`__" or "__;__" find/replace delimiter can be either backtick '&#96;', pipe '|' or tilda '~' or semicolon ';'
	<br />__NOTE:__ you can use a delimiter character as part of your regex if you escape it.
*	"__find__"  find string or regex
*	"__replace__" replacement string or regex pattern
*	"__modifiers__" if regex is to be used, modifiers must containt '`R`' (for Regex) or any valid regex modifier

## Syntax checking:

Currently, syntax checking checks for:
*	Duplicate IDs in `<MySource_AREA>` design areas;
*	Undefined IDs in `<MySource_Print>` statements; and
*	Unprinted `<MySource_AREA>` design areas  i.e. unique design areas that are defined but never printed;
	<br />__NOTE:__ This will be off by default but can be switched on and can have exceptions for intentionally unprinted design areas;
*	Bad regexes in show_if statements.

As I get a better understanding of how the Matrix Parse file is parsed, I will add features to the syntax checking.

## Config:

There are a few options that can be set either in a config file or at runtime these are:
*	__`output`__ {string} directory to save output (either relative to source file or absolute)
*	__`partials`__ {string} directory to find partial files (either relative to source file or absolute)
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