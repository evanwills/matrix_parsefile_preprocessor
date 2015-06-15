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
	__NOTE:__ the keyword match will find any combination of `[]{}` but will throw errors if the delimiters are not `{[...]}` or `{{...}}`
*	"__path/to/partial/__" relative or absolute path to partial
*	"__file__" name of partial file
	__NOTE:__ partials (like in SASS) are prefixed with an underscore '_' and end with the .xml file extension
*	"__<code>&#96;</code>__" or "__`|`__" or "__`~`__" find/replace delimiter can be either backtick '&#96;', pipe '|' or tilda '~'
	__NOTE:__ you can use a delimiter character as part of your regex if you escape it.
*	"__find__"  find string or regex
*	"__replace__" replacement string or regex pattern
*	"__modifiers__" if regex is to be used, modifiers must containt '`R`' (for Regex) or any valid regex modifier

## Syntax checking:

Currently, syntax checking checks for:
*	Duplicate IDs in `<MySource_AREA>` design areas;
*	Undefined IDs in `<MySource_Print>` statements; and
*	Unprinted `<MySource_AREA>` design areas  i.e. unique design areas that are defined but never printed;
	__NOTE:__ This will be off by default but can be switched on and can have exceptions for intentionally unprinted design areas;
*	Bad regexes in show_if statements.

As I get a better understanding of how the Matrix Parse file is parsed, I will add features to the syntax checking.