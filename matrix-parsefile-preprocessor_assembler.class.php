<?php

require_once($cls.'regex/regex_error.inc.php');

class matrix_parsefile_preprocessor__assembler extends matrix_parsefile_preprocessor
{
	/**
	 * @const string INCLUDES_REGEX a regular expression for
	 * matching preparse file keywords
	 *		[0] the full match of the keyword string
	 *  	[1] whole keyword
	 *  	[2] preceeding content
	 *  	[3] opening wrapper
	 *		[4] the directory path to find the preparse
	 *			block or sub-preparse file (relative to the
	 *			current preparse file)
	 *		[5] the name of the file to be included
	 *  	[6] (optional) find/replace delimiter [`~|]
	 *		[7] (optional) find string/regex to do find and
	 *			replace on the praparse block/sub-preparse
	 *			file
	 *		[8] (optional) replace string to be used in
	 *			conjuction with find string/regex
	 *		[9] (optional) regex modifiers/regex identifier
	 *			"R" (if no modifiers)
	 *		[10] closing wrapper
	 */
	const INCLUDES_REGEX = '@
( # [1] preceeding content
	.*?
)
( # [2] whole keyword
	( # [3] opening wrapper
		[\{\}\[\]]{2}
	)
	(?:
		( # [4] path
			(?:[a-zA-Z0-9_-]+/)*
		)
		( # [5] file
			[a-zA-Z0-9_-]+
		)
		(?:
			( # [6] find/replace delimiter
				[\`\|\~\;]
			)
			( # [7] find string/pattern
				.*?
			)
			(?<!\\\\)
			\4
			( # [8] replace string/pattern
				.*?
			)
			(?:
				(?<!\\\\)
				\4
				( # [9] regex identifier/modifiers
					[RimsxeADSUXJu]{1,11}
				)
			)?
		)?
	)
	( # [10] closing wrapper
		[\{\}\[\]]{2}
	)
)
@x';

	/**
	 * @const string TRIM_LINE_REGEX for trimming lines from the
	 * beginning and end of a preparse file partial
	 */
	const TRIM_LINE_REGEX = '(?:^(?:[\t ]*[\r\n]+)+|(?:[\r\n]+[\t ]*)+$)';

	/**
	 * @const string COMMENT_REGEX checks with a preparse file
	 * partial has JS/CSS comments at the beginning of the file
	 */
	const COMMENT_REGEX = '^\s*/\*';

	/**
	 * @var string $path the directory path of the current file being
	 * processed
	 */
	private $path = '';

	/**
	 * @var string $file name of the file currently being processed
	 */
	private $file = '';

	/**
	 * @var string $partials the directory parth to the partials
	 * directory
	 */
	static private $partials_dir = false;
	private $partials = '';

	/**
	 * @var string $original the contents of the preparse file being
	 * processed (used for finding the line number of a keyword with an error)
	 */
	private $original = '';

	static private $matrix_test = false;
	private $matrix_tester = false;

// ==================================================================
// START: property validation and processing

	public function __construct( $source_path , $source_file ){
		if( !is_string($source_path) )
		{
			echo "\n\n--------- ERROR --------\nmatrix_parsefile_preprocessor__assembler::__constructor() expects first paramater to be a string!\n".gettype($source_path)." given\n\n";
			exit;
		}
		elseif( !is_dir($source_path) )
		{
			echo "\n\n--------- ERROR --------\nmatrix_parsefile_preprocessor__assembler::__constructor() expects first paramater to be a string path to a directory\nCould not find directory: \"$source_path\"!\n\n";
			exit;
		}
		else
		{
			$this->path = realpath($source_path).'/';
		}

		if( $this->check_file($this->path,$source_file) )
		{
			$this->file = $source_file;
		}

		if( self::$matrix_test === false ) {
			self::$matrix_test = new matrix_parsefile_preprocessor__basic_test();
		}
		$this->matrix_tester = self::$matrix_test;

		if( self::$partials_dir === false ) {
			$this->set_partials_dir($source_path.'partials/');
		}
	}

//  END: property validation and processing
// ==================================================================
// START: public functions

	/**
	 * @function parse() processes the contents of a preparse file
	 * including all of its child parse file blocks
	 * @param string file system path to the directory where the
	 *               preparse file is to be found
	 * @param string the name of the file to be parsed
	 *@return string fully assembled parse file block.
	 */
	public function parse() {
		$bk = $this->prop_backup( 'file' , 'original' , 'partials' , 'path' );
		$output = '';
		$includes = 0;

		$this->original = file_get_contents($this->path.$this->file);
		$output = preg_replace_callback( SELF::INCLUDES_REGEX , array( $this , 'ASSEMBLER_CALLBACK' ) , $this->original , -1 , $includes );


		if( $includes === 0 ) {
			echo "\n\n--------- ERROR --------\nThere were no partial patterns found in\n\t{$this->path}{$this->file}\n\n";
			exit;
		}
		else {
			echo "\n\nThere were $includes partial patterns found in\n\t{$this->path}{$this->file}\n\n";
		}

		$this->prop_restore($bk);

		return $output;
	}

	public function set_partials_dir($partials_dir) {
		if( is_string($partials_dir) )
		{
			if( is_dir($partials_dir) )
			{
				self::$partials_dir = realpath($partials_dir).'/';
				$this->partials = self::$partials_dir;
			}
			else
			{
				// throw set_partials_dir() expects paramater 1 to be path to a valid directory
			}
		}
		else
		{
			// throw set_partials_dir() expects paramater 1 to be a string
		}
	}

//  END:  public functions
// ==================================================================
// START: private functions

	/**
	 * @function ASSEMBLER_CALLBACK() uses the match array of a
	 * regular expression on a single preparse file keyword and
	 * returns the defined contents after doing some stuff with it.
	 * @param  array $inc an array of seven items:
	 *		[0] the full match of the keyword string
	 *  	[1] whole keyword
	 *  	[2] preceeding content
	 *  	[3] opening wrapper
	 *		[4] the directory path to find the preparse
	 *			block or sub-preparse file (relative to the
	 *			current preparse file)
	 *		[5] the name of the file to be included
	 *  	[6] (optional) find/replace delimiter [`~|]
	 *		[7] (optional) find string/regex to do find and
	 *			replace on the praparse block/sub-preparse
	 *			file
	 *		[8] (optional) replace string to be used in
	 *			conjuction with find string/regex
	 *		[9] (optional) regex modifiers/regex identifier
	 *			"R" (if no modifiers)
	 *		[10] closing wrapper
	 */
	private function ASSEMBLER_CALLBACK($match) {
		$bk = $this->prop_backup('partials','path');
		$partial_content = '';



		$test_result = $this->matrix_tester->test_parsefile($match[1]);
		if( $test_result !== true ) {
			// there was a matrix parse file error in the code preceeding this keyword
			$this->display_error($test_result[1],$test_result[2],$test_result[0]);
		}



		$ok = false;
		$no_comments = false;
		if( $match[3] == '{{' && $match[10] == '}}' ) {
			$ok = true;
		} elseif( $match[3] == '{[' && $match[10] == ']}'  ) {
			$ok = true;
			$no_comments = true;
		} else {
			// keyword dlimiters
			$this->display_error($match[2], "Keyword delimiters '{$match[3]}' and '{$match[10]}' are not valid");
		}
debug($match,$this->partials);

		// get the partial
		if( $this->check_file($match[4],'_'.$match[5].'.xml') )
		{

			$partial_content = file_get_contents($match[4].'_'.$match[5].'.xml');
		}
		elseif( $this->check_file($match[4],$match[5].'.xml') )
		{
			$partial_content = new SELF( $match[4] , $match[5].'.xml' );
		} else {

			$this->display_error($match[2],"Could not find\n\t".$match[4].'_'.$match[5].".xml\nor\t".$match[4].$match[5].'.xml');
		}

		$error_suffix = '';
		// do find and replace if appropriate
		// useful when using the same partial for multiple design areas.
		if( $match[6] != '' ) {
			$match[7] = str_replace('\\'.$match[6],$match[6],$match[7]);
			$match[8] = str_replace('\\'.$match[6],$match[6],$match[8]);
			if( $match[7] != '' ) {
				// Do regular expression find/replacce

				// 'R' is not a valid PREG modifier, it is used to identify a regex so remove it
				$match[7] = str_replace('R','',$match[7]);
				$regex_error = regex_error( $match[6].$match[7].$match[6].$match[9] );
				if( $regex_error === false ) {
					$partial_content = preg_replace( $match[4].$match[5].$match[4].$match[7] , $match[6] , $partial_content);
				}
				else
				{
					// regex has an error show error and terminate
					$this->display_error($match[0], $regex_error);
				}
			}
			else
			{
				// do simple find/replace
				$partial_content = str_replace( $match[5] , $match[6] , $partial_content );
			}
			$error_suffix = "\nNOTE: parsefile content has been modified by a find/replace which may have caused this issue";
		}

		$test_result = $this->matrix_tester->test_parsefile($partial_content);
		if( $test_result !== true ) {
			// there was a matrix parse file error in the code preceeding this keyword
			$this->display_error( $test_result[1] , $test_result[2].$error_suffix, $test_result[0]);
		}

		debug(self::TRIM_LINE_REGEX,$partial_content);
		$partial_content = preg_replace( SELF::TRIM_LINE_REGEX , '' , $partial_content );

		// wrap partial in comments (if appropriate)
		if( $no_comments === false )
		{
			$open = '<!--';
			$close = '-->';
			if( preg_match( '`^\s*/\*`' , $partial_content ) )
			{
				$open = '/*';
				$close = '*/';
			}
			$open = "\n$open|| ";
			$close = "||$close\n";

			$partial_content = "{$open}START: {$match[2]}{$match[3]} {$close}{$partial_content}{$open} END:  {$match[2]}{$match[3]} $close";
		}
		else
		{
			$partial_content = "\n".$partial_content."\n";
		}

		$this->prop_restore($bk);

		return $partial_content;
	}

	/**
	 * @function check_file() checks if a given file exists
	 * @param  string &$path string file system path to directory
	 *                       where file should be found
	 * @param  string $file  name of file to be checked
	 * @return boolean  true if file is found, false otherwise
	 */
	private function check_file( &$path , $file ) {
		if( is_string($path) )
		{
			if( is_string($file) )
			{
				if( !is_dir($path) )
				{
					if( is_dir($this->partials.$path) )
					{
						$path = realpath($this->partials.$path).'/';
					}
					else
					{
						return false;
					}
				}
				if( is_file($path.$file) ){
					return true;
				}
			}
		}
		return false;
	}

	/**@function get_file() uses check_file to validate a file's
	 * existance then returnse it if found or false if not
	 * @param  string &$path string file system path to directory
	 *                       where file should be found
	 * @param  string $file  name of file to be checked
	 * @return string boolean if file is found then it's contents is
	 *                returned otherwise, false is returned.
	 */
	private function get_file( &$path , $file ) {
		if( $this->check_file($path,$file) ) {
			return file_get_contents($path.$file);
		}
		return false;
	}

	/**
	 * @function get_error_line() returns the line the current
	 * keyword is on in the preparse file being processed
	 * @param  string $pattern (equivelent to $inc[0]) the full
	 *                keyword string where an error has occured
	 * @return integer the line number of the current keyword
	 */
	private function get_error_line($pattern) {
		$arr =	preg_split(
						 '`(\r\n|\n\r|\r|\n)`'
						,preg_replace(
							 '`(?<='.preg_quote($pattern).').*$`s'
							,''
							,$this->original
						)
				);
		return count($arr);
	}

	/**
	 * @function display_error() renders an error message
	 * @param string $pattern the keyword where the error has occured
	 * @param string $msg     the error message to be displayed
	 */
	private function display_error( $pattern , $msg , $line = false , $file = false ) {
		if( !is_int($line) ) {
			$line = $this->get_line_number($pattern,$this->original);
		}
		if( !is_string($file) || !is_file($file) ) {
			$file = $this->path.$this->file;
		}
		echo "\n\n--------- ERROR ---------\n$msg\n$pattern\nLine $line in $file\n\n";
		exit;
	}

	/**
	 * @function prop_backup() each param is a class property name.
	 * The function builds an associative array of the values of
	 * those properties and returns that array.
	 * @param string class property name to be backed up/preserved
	 * @return array an associative array of key value pairs.
	 */
	private function prop_backup() {
		$b = func_num_args();
		$output = array();

		for( $a = 0 ; $a < $b ; $a += 1 )
		{
			$key = func_get_arg($a);

			if( property_exists($this,$key) )
			{
				$output[$key] = $this->$key;
			}
			else
			{
				// throw
			}
		}
		return $output;
	}

	/**
	 * @function prop_restor() restores the values of class
	 * properties based on the contents of an associative array
	 * (generated by $this->prop_backup())
	 * @param array an associative array of key value pairs, where
	 *        the key is a class property name and the value is the
	 *        value to be assigned to that property.
	 * @return true if the properties are assigned script it
	 *         terminated if any properties are not valid.
	 */
	private function prop_restore( $arr ) {
		if( !is_array($arr) ) {
			//throw
		}
		foreach( $arr as $key => $value ) {
			if( property_exists($this,$key) ) {
				$this->$key = $value;
			} else {
				// throw
			}
		}
		return true;
	}
}

