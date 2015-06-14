Squiz Matrix Parse File Preprocessor

This is an attempt at making a preprocessor to allow for reusable parsefile chunks to be assembled then checked for basic symantic errors

Inspired by the Sass CSS Preprocessor, SMPFP (for want of a better name) will:

1.	read through a file finding keywords delimited by {{...}} or {[...]}
2.	search for file partials identified by the keywords
3.	modify partials using basic find/replace or regex/replace as required
4.	and replace the keyword with the partial
5.	syntax check assembled file for duplicate IDs and undefined print statements

keyword structure: `{{path/to/partial/file|find|replace|modifiers}}`

*	"__`{{`__" or "__`{[`__"  delimiter
	*	`{{` partial is wrapped in comments
	*	`{[` partial is NOT wrapped in comments
*	"__path/to/partial/__" relative or absolute path to partial
*	"__file__" name of partial file (not partials (like in SASS) are prefixed with an underscore '_' and end with the .xml file extension
*	"__<code>&#96;</code>`__" or "__`|`__" or "__`~`__" find/replace delimiter can be either backtick '&#96;', pipe '|' or tilda '~'
*	"__find__"  find string or regex
*	"__replace__" replacement string or regex pattern
*	"__modifiers__" if regex is to be used, modifiers must containt '`R`' (for Regex) or any valid regex modifier