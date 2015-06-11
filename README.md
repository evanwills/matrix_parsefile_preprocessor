Squiz Matrix Parse File Preprocessor

This is an attempt at making a preprocessor to allow for reusable parsefile chunks to be assembled then checked for basic symantic errors

Inspired by the Sass CSS Preprocessor, SMPFP (for want of a better name) will:

1	read through a file finding keywords delimited by {{...}} or {[...]}
2	search for file partials identified by the keywords
3	modify partials using basic find/replace or regex/replace as required
4	and replace the keyword with the partial
5	syntax check assembled file for duplicate IDs and undefined print statements

keyword structure: {{path/to/partial/\_file.xml|find|replace|modifiers}}

{{ or {[  delimiter
	{{ partial is wrapped in comments
	{[ partial is NOT wrapped in comments
path	
