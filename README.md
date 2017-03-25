# Squiz Matrix Parse File builder and validator

This is a small tool to help with creating and maintaining [Squiz Matrix __Design Parse files__](https://matrix.squiz.net/manuals/designs).
It has a command line version which allows for building full parse files out of partials, is much the same way as something like [SASS](http://sass-lang.com/) uses to build CSS style sheets. It also has a web interface that allows basic validation of parse files

## Problem 1:
The only way to validate a [parse files](https://matrix.squiz.net/manuals/designs/chapters/design-asset#parse-file) is to upload it to Matrix. This is fine during initial development phase when the design hasn't got many customisation and hasn't been applied to too many assets. However, changing an existing parse file can take a very long time to process and, if there's an error in the code you've uploaded it can take the same amount of time again to fix the error. This tool attempts to provide a way of validating your parse-file (in seconds) before you upload it to Matrix.

## Problem 2:
When you update an existing parse file, any design areas that were in the existing parse file but not in the new version will have all their customisation setting permanently lost. This means if you inadvertently delete a design area on a production design it can have disastrous effects only fixable by doing a full restore from the most recent database backup, causing you to loose __*ALL*__ the changes made to Matrix since the last backup. This tool compares a specified old version of the file against the newly created version and identify any design areas missing from the new version.

## Problem 3:
In a single Matrix installation, you often have multiple sites. Most of these sites are likely to share significant portions of their designs and design parse files (e.g. In our main site, we have an Inside page design and a lighter weight _Home page_ design which shares all of the _Inside page_ design's header and footer sections but has a single design area for the main home page stuff). It would be good if you could break down parse files into partials then assemble the partials into different final design parse files. The command line interface for this too allows you to do this.

## Usage
### Web

The web interface only allows validation and comparison of parse files. (There are no plans to include the compiling functionality in the web interface.)

1.	Place your parse file to be validated in the "Parse-file to be checked" text area.<br />
	If you want to compare the parse file to another parse file
	1.	tick the "Check for missing design areas" checkbox
	2.	place the old parse file in the (grey) "Previously uploaded Parse-file (to be compared against for missing design areas)" text area
2.	Press the "Submit" button
3.	Fix items identified in the report and resubmit.

### Command line

The command line interface allows you to compile parse files from partials as well as validate and comparing like the web interface. It also allows you to process multiple files with a single call.

```$ php parse-file_process.cli.php parse-file.xml [runtime options] [other-files_1 other-files_2 ...] [compare] [comparison-files_1 comparison-files_2 comparison-files_3 ...]```

#### Basic CLI usage

```$ php parse-file_process.cli.php parse-file.xml```

This will compile all the partials associated with parse-file.xml and write the compiled output to the `output_dir` specified in your config file (usually `compiled`). It will also report all Errors, Warnings and Notices.

#### Runtime options

You can set default behaviour by modifying the [config file](#config) config file. You can also control behaviour at runtime.

##### Reporting

*	__`all`__ - report ALL errors, warnings and notices.
*	__`brief`__ - report only errors and warnings
*	__`compare`__ - compare newly compiled files with existing files<br />If no files are specified, newly compiled files are compared with existing versions of the same file (i.e. the old file is parsed before it is overwritten with the output of that parse is compared with the output from parsing the new version.)<br />If files are specified, the number of files to be compared must be the same as the number of files to compare against or the script will complain.<br />__NOTE:__ If there's no existing version the comparison will be silently skipped for that file.
*	__`error`__ - report only errors
*	__`l`__ *or* __`log`__ - write info from compile to log file.
*	__`notice`__ - report only notices
*	__`q`__ *or* __`quiet`__ - suppress all reporting
*	__`warning`__ - report only warnings

##### Compiled output
*	__`compact`__ - when writing the compiled output to file, convert multiple lines to single line and strip white space from the beginning and end of lines
*	__`compress`__ - in compiled parse file, convert adjacent lines, tabs & spaces into a single space.
*	__`keep-comments`__ - when writing the compiled output to file, do not delete HTML/CSS comments
*	__`no-wrap`__ - when writing the compiled output to file, do not wrap partials in comments
*	__`strip-comments`__ - remove all HTML, CSS & JavaScript comments from compiled file.
*	__`wrap`__ - wrap partials in comments to identify where the start and end of the partial is.

Most of the runtime options can be passed in any order with the exception of __`compare`__. Once __`compare`__ has been passed any files or directories passed  after it will be assumed to be comparison files.


#### Comparing parse files

```$ php parse-file_process.cli.php parse-file.xml compare```

This will compare the old version of parse-file.xml (in the `output_dir` directory/folder) with the newly created version of the same file and report any design areas that were in the old version but not the new.

#### Comparing parse file with a different file

```$ php parse-file_process.cli.php parse-file.xml compare other-parse-file.xml```

__NOTE:__ Any file names or directories/folders you pass after __`compare`__ will be assumed to be a comparison file

e.g. `$ php parse-file_process.cli.php process-me_1.xml process-me_2.xml process-me_3.xml brief process-me-too.xml`

The `process-me_1.xml`, `process-me_2.xml` & `process-me_3.xml` will be compiled and validated as normal. The `process-me-too.xml` will be considered a comparison file and will only be scanned for design area IDs.

__NOTE:__ because you've inadvertently passed a single comparison file for three input files, the script will complain and terminate because you the number of comparison files doesn't match the number of input files.

#### Processing multiple files

You can process multiple files in one of the following ways:
e.g.
*	with a wild card - `$ php parse-file_process.cli.php *.xml`
*	pointing to a directory - `$ php parse-file_process.cli.php my_dir/`
*	specifying multiple files - `$ php parse-file_process.cli.php process-me_1.xml process-me_2.xml process-me_3.xml`

If you want to compare these files you can just add `compare` after the last file/directory.
This will cause the validator to look for files with the same name in the `output_dir`.

If you want to specify specific files to compare each of the files, you'll need to have the same number of files in both the compile and the compare list e.g.

```$ php parse-file_process.cli.php process-me_1.xml process-me_2.xml process-me_3.xml compare  compare-me_1.xml compare-me_2.xml compare-me_3.xml```

doing something like

```$ php parse-file_process.cli.php process-me_1.xml process-me_2.xml process-me_3.xml compare  compare-me_1.xml```

or

```$ php parse-file_process.cli.php process-me_1.xml process-me_2.xml compare  compare-me_1.xml compare-me_2.xml nothing-to-compare.xml```

will both cause `parse-file_process.cli.php` to complain.

__NOTE:__ If you are comparing multiple files, it's probably better write the output to a log file by passing the `l` or `log` runtime option.
e.g.

```$ php parse-file_process.cli.php log process-me/*.xml compare```

## How the validator works:

1.	Finds all `<MySource_AREA>` and `<MySource_PRINT>` tags
2.	`<MySource_AREA>` tags, it finds the `id_name` attribute and records the value. If there's a `print="no"` attribute, it also records the fact that the tag will not be rendered by matrix at the location it was created.
3.	`<MySource_PRINT>` tags, it compares the `id_name` with the list of unprinted `<MySource_AREA>`s and removes the ID from the list.
4.  `<MySource_AREA>` `design_area="show_if"` tags, it checks to see if they have `keyword_regexp` condition. If so, it validates the regular expression to ensure that if has an error you know about it.
5.	`<MySource_AREA>` tags with duplicate  `id_name` values are reported as errors.
6.  Reports any `<MySource_AREA>` tags that are never printed (this is not always a problem but having design areas that are never printed can needlessly add to the processing time of the design.)

## How the old/new comparison works

1. 	It Searches through the old parse file for any `<MySource_AREA>` and records the `id_name` value for each
2. 	It Searches through the new parse file for any `<MySource_AREA>` and removes `id_name`s from the list of the ones in the old parse file
3.	Any `id_name`s left in the list from the old parse file are missing from the new parse file and displayed as warnings at the end of the validation phase.


## How the builder/compiler works:

1.	Searches the base parse file for [special keywords](#keyword-structure)
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

## Keyword structure
```
{{path/to/partial/file|find|replace|modifiers}}
```

*	"__`{{`__" or "__`{[`__" or "__`{(`__"  delimiter
	*	`{{` partial is NOT wrapped in comments
	*	`{[` partial is wrapped in HTML comments `<!-- ... -->`
	*	`{(` partial is wrapped in CSS/JS comments `/* ... */`
	<br />__NOTE:__ the keyword match will find any combination of `[]{}()` but will throw errors if the delimiters are not `{[...]}` or `{{...}}` or `{(...)}`
*	"__path/to/partial/__" relative or absolute path to partial. By default, partial paths should be relative to the location they are called from.
*	"__file__" name of partial file.
	<br />__NOTE:__ partials must be prefixed with an underscore '_' and end with the .xml file extension. e.g. `_my-partial.xml`
*	"__<code>&#96;</code>__" or "__`|`__" or "__`~`__" or "__;__" find/replace delimiter can be either backtick '&#96;', pipe '|' or tilde '~' or semicolon ';'
	<br />__NOTE:__ the delimiter you use in the keyword is also the delimiter used in the final regular expression. This may cause your regex problems.
	<br />__NOTE ALSO:__ The regex used to match the keyword is delimited by an __`@`__ symbol. If your regex needs to match __`@`__ symbols, you'll need to escape them too<br />(e.g. "`jo.smith\@company.org`")
*	"__find__"  find string or regex
*	"__replace__" replacement string or regex pattern
*	"__modifiers__" if regex is to be used, modifiers must contain '`R`' (for Regex) or any valid regex PHP PCRE modifier

## Config:

There are a few options that can be set either in a config file or at runtime these are:
*	__`output_dir`__ `{string}` *`[compiled/]`* directory to save output (either relative to source file or absolute)
*	__`log_dir`__ `{string}` *`[logs/]`* directory to save logs to (either relative to source file or absolute)
*	__`on_unprinted`__ {string} either 'show', 'fail' or 'hide':
	*	'show' [default] show the unprinted IDs, the line they were found on and the file they were found in
	*	'fail' same as show but stops processing if there are any unprinted IDs (__NOTE:__ there will be no output written to file)
	*	'hide' don't show unprinted IDs
*	__`unprinted_exceptions`__ {string} comma separated list of IDs that can be ignored if unprinted.
*	__`show_error_extended`__ {boolean} If there is an error show the whole partial/parse file where the error occured as well as the line and file name.
*	__`strip_comments`__ `{boolean}` *`[FALSE]`* strip_comments Strip HTML comments as output is being created.
*	__`white_space`__ `{string}` white_space How to handle white space during compile either 'normal', 'compact' or 'compressed':
	*	'normal' [default] do nothing (leave as is)
	*	'compact' delete spaces & tabs from start and end of lines
	*	'compressed' reduce multiple, consecutive white-spaces character to a single space character
*	__`wrap_in_comments`__ `{boolean}` *`[FALSE]`* during development, it's useful to see where each bit of parse file code comes from. setting `wrap_in_comments` to `TRUE` causes the the absolute file path of each partial to be shown (in HTML/CSS comments) to identify the begining and end of each partial.

### _To do:_
* Since normal Matrix key words (e.g. `%globals_asset_name%`) work in design parse files, It would be good if the validator checks these. Especially the modifiers.
* Go through Squiz Matrix documentation and write code to validate all design tags as per documentation.
