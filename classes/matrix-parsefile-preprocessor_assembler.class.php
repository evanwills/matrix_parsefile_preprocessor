<?php

require_once('includes/regex_error.inc.php');
require_once('classes/matrix-parsefile-preprocessor.class.php');
require_once('classes/matrix-parsefile-preprocessor_config.class.php');
require_once('classes/matrix-parsefile-preprocessor_basic-test.class.php');

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
			\6
			( # [8] replace string/pattern
				.*?
			)
			(?:
				(?<!\\\\)
				\6
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
	const TRIM_LINE_REGEX = '`(?:^(?:[\t ]*[\r\n]+)+|(?:[\r\n]+[\t ]*)+$)`';

	/**
	 * @const string COMMENT_REGEX checks with a preparse file
	 * partial has JS/CSS comments at the beginning of the file
	 */
	const COMMENT_REGEX = '^\s*/\*';

	const STRIP_COMMENT_REGEX = '`<!--(?!=\[).*?-->|/\*.*?\*/`s';

	const STRIP_WHITE_SPACE_COMPACT = '`(?<=^|[\r\n])[\t ]+|[\t ](?=[\r\n|$)`';
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
	 * @var string $partials the directory path to the partials
	 * directory
	 */
	static private $partials_dir = false;

	/**
	 * @var string $original the contents of the preparse file being
	 * processed (used for finding the line number of a keyword with an error)
	 */
	private $original = '';

	private $current = '';

	private $matrix_tester = false;
	private $fail_on_unprinted = false;
	static private $first_call = true;
	private $show_error_extended = false;
	private $output_dir = '';
	private $output_file = '';
	private $handle_comments = null;
	private $handle_white_space = null;
	private $handle_wrap = null;
	private $is_base = false;

// ==================================================================
// START: property validation and processing

	public function __construct( $source_path = '' , $source_file = '' , $fail_on_unprinted = false , $unprinted_exceptions = array() )
	{

		if( !is_string($source_path) )
		{
			throw new exception(get_class($this)."::__constructor() expects first paramater \$source_path to be a string! ".gettype($source_path)." given.");
		}
		if( !is_string($source_file) )
		{
			throw new exception(get_class($this)."::__constructor() expects second paramater \$source_file to be a string! ".gettype($source_file)." given.");
		}
		if( !is_bool($fail_on_unprinted) )
		{
			throw new exception(get_class($this)."::__constructor() expects third paramater \$fail_on_unprinted to be a boolean! ".gettype($source_file)." given.");
		}
		if( !is_array($unprinted_exceptions) )
		{
			throw new exception(get_class($this)."::__constructor() expects fourth paramater \$unprinted_exceptions to be an array! ".gettype($source_file)." given.");
		}

		$config = matrix_parsefile_preprocessor__config::get($source_path.$source_file);


		$this->output_dir = $config->get_var('output_dir');

		$this->show_error_extended = $config->get_var('show_error_extended');
		$this->fail_on_unprinted = $config->get_var('fail_on_unprinted');
		$this->show_error_extended = $config->get_var('show_error_extended');
		$this->unprinted_exceptions = $config->get_var('unprinted_exceptions');
		$this->matrix_tester = matrix_parsefile_preprocessor__basic_test::get( $unprinted_exceptions );

		$ws = $config->get_var('white_space');
		if( $ws == 'normal' )
		{
			$this->handle_white_space = 'white_space_normal';
		}
		else
		{
			$this->handle_white_space = 'strip_white_space';
			if( $ws == 'compress' )
			{
				$this->white_space_regex = '`\s+`';
			}
			else
			{
				$this->white_space_regex = self::STRIP_WHITE_SPACE_COMPACT;
			}
		}

		if( $config->get_var('strip_comments') === true )
		{
			$this->handle_comments = 'strip_comments';
			$this->handle_wrap = 'dont_wrap';
		}
		else
		{
			$this->handle_comments = 'leave_comments';
			$this->handle_wrap = 'wrap_in_comments';
		}


		if( self::$first_call === true ) {
			self::$first_call = false;
			$this->is_base = true;
		}


		if( $this->is_base === false )
		{
			if( is_dir(self::$partials.$source_path) )
			{
				$this->path = realpath(self::$partials.$source_path).'/';
			}
		}
		elseif( is_dir($source_path) )
		{
			$this->path = realpath($source_path).'/';
		}

		if( $this->path === '' )
		{
			throw new exception(get_class($this)."::__constructor() expects first paramater \$source_path to be a string path to a directory. Could not find directory: \"$source_path\"!");
		}

		if( $this->check_file($this->path,$source_file) )
		{
			$this->file = $source_file;
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
		/**
		 * @var array $bk an associative array of object properties to be
		 * 		backed up during the processing of this function and restored
		 *   	at the end
		 */
		$bk = $this->prop_backup( 'file' , 'original' , 'partials' , 'path' );

		/**
		 * @var string $output the content this function will return
		 */
		$output = '';

		/**
		 * @var integer $includes the number of files this (base) parse file
		 * 		inlcudes
		 */
		$includes = 0;

		debug($this->path.$this->file);
		// make the contents of this file the $this->original (for use when showing errors)
		$this->original = file_get_contents($this->path.$this->file);


		$output = preg_replace_callback( SELF::INCLUDES_REGEX , array( $this , 'ASSEMBLER_CALLBACK' ) , $this->original , -1 , $includes );


		if( $includes === 0 ) {
			echo "\n\n--------- NOTE --------\nThere were no partial patterns found in\n\t{$this->path}{$this->file}\n\n";
			$this->matrix_tester->test_parsefile( $this->original , $this->file );
		}
		else {
			echo "\n\nThere were $includes partial patterns found in\n\t{$this->path}{$this->file}\n\n";
		}

		$this->prop_restore($bk);

		if( $this->is_base )
		{
			if( $this->fail_on_unprinted )
			{
				$this->matrix_tester->fail_on_unprinted();
			}
			if( $this->output_dir !== false )
			{
				file_put_contents($this->output_dir.$this->file,$output);
				echo "\n\n------------------------------------\n\n\n All done.\n File written to \"{$this->output_dir}{$this->file}\"\n\n\n\n";
			}
		}

		return $output;
	}

	public function compile( $input_file , $output_file , $strip_comments = false , $white_space = 'normal' ) {
		// todo pull all post parsing functionality out of matrix_parsefile_preprocessor__assembler::parse()
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
		$bk = $this->prop_backup('partials','path','file');
		$partial_content = '';

		$test_result = $this->matrix_tester->test_parsefile($match[1],self::$partials_dir.$this->file);
		if( $test_result !== true ) {
			// there was a matrix parse file error in the code preceeding this keyword
			$this->display_error($test_result[1],$test_result[2],$test_result[0]);
		}
		$match[1] = $this->{$this->handle_comments}($match[1]);


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

		debug($match,$this->path, $this->path.$match[4], realpath($this->path.$match[4]),$match[5]);
		// get the partial
		if( $this->check_file($match[4],'_'.$match[5].'.xml') )
		{
			$source = $match[4].'_'.$match[5].'.xml';
			$partial_content = file_get_contents($source);
		}
		elseif( $this->check_file($match[4],$match[5].'.xml') )
		{
			$source = $match[4].$match[5].'.xml';
			debug($source);
			$partial_content = new SELF( $source );
		} else {

			$this->display_error($match[2],"Could not find\n\t".$match[4].'_'.$match[5].".xml\nor\t".$match[4].$match[5].'.xml');
		}

		$error_suffix = '';
		// do find and replace if appropriate
		// useful when using the same partial for multiple design areas.
		if( $match[6] != '' )
		{
			$match[7] = str_replace('\\'.$match[6],$match[6],$match[7]);
			$match[8] = str_replace('\\'.$match[6],$match[6],$match[8]);
			if( $match[7] != '' )
			{
				// Do regular expression find/replacce

				// 'R' is not a valid PREG modifier, it is used to identify a regex so remove it
				$match[9] = str_replace('R','',$match[9]);
				$regex_error = regex_error( $match[6].$match[7].$match[6].$match[9] );
				if( $regex_error === false ) {
					$partial_content = preg_replace( $match[6].$match[7].$match[6].$match[9] , $match[8] , $partial_content);
				}
				else
				{
					$this->prop_restore($bk);
					// regex has an error show error and terminate
					$this->display_error($match[0], $regex_error);
				}
			}
			else
			{
				// do simple find/replace
				$partial_content = str_replace( $match[7] , $match[8] , $partial_content );
			}
			$error_suffix = "\nNOTE: parsefile content has been modified by a find/replace which may have caused this issue";
		}


		$this->current = $partial_content;
		$test_result = $this->matrix_tester->test_parsefile( $partial_content , $source );
		if( $test_result !== true ) {
			// there was a matrix parse file error in the code preceeding this keyword
			$this->display_error( $test_result[1] , $test_result[2].$error_suffix, $test_result[0] , $source );
		}
		$this->current = '';


		$partial_content = preg_replace( SELF::TRIM_LINE_REGEX , '' , $partial_content );

		// wrap partial in comments (if appropriate)
		if( $no_comments === false )
		{
			$partial_content = $this->{$this->handle_wrap}($partial_content,$match[2]);
		}
		else
		{
			$partial_content = "\n".$this->{$this->handle_comments}($partial_content)."\n";
		}

		$partial_content =

		$this->prop_restore($bk);

		return $this->{$this->handle_white_space}($match[1].$partial_content);
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
				$config = matrix_parsefile_preprocessor__config::get();

				while( $tmp = $config->get_next_partial_dir() )
				{
					debug($tmp, $path, $file, $tmp.'/'.$path.'/'.$file, is_file($tmp.'/'.$path.'/'.$file));
					if( is_file($tmp.'/'.$path.'/'.$file) )
					{
						$path = realpath($tmp.'/'.$path.'/'.$file).'/';
						return true;
					}
				}
//				if( !is_dir($path) )
//				{
//					if( is_dir(self::$partials_path.$path) )
//					{
//						$path = realpath(self::$partials_path.$path).'/';
//					}
//					else
//					{
//						return false;
//					}
//				}
				if( is_file($path.$file) )
				{
					$this->file = $file;
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
		echo "\n\n-----------------------------------------\n-- ERROR --\n\n   $msg\n   $pattern\n\n   Line $line in $file";
		if( $this->show_error_extended )
		{
			echo "\n\n- - - - - - - - - - - - - - - - - - - - -\n\n{$this->current}";
		}
		echo "\n\n-----------------------------------------\n\n";
		exit;
	}

	private function dont_wrap($partial_content,$keyword)
	{
		return $this->strip_comments($partial_content);
	}

	private function wrap_in_comments($partial_content,$keyword)
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

		return "{$open}START: $keyword {$close}{$partial_content}{$open} END:  $keyword $close";
	}

	private function strip_comments($partial_content)
	{
		return preg_replace( '`<!--(?!=\[).*?-->|/\*.*?\*/`s' , '' , $partial_content );
	}

	private function leave_comments($partial_content)
	{
		return $partial_content;
	}

	private function white_space_normal($input) {
		return $input;
	}

	private function strip_white_space($input) {
		$pre = $erp = array();
		preg_match_all('`<pre[^>]*>.*?</pre>`is',$input,$matches);
		for( $a = 0 ; $a > $matches[0] ; $a += 1 )
		{
			$pre[] = $matches[$a];
			$erp[] = '<<{PRE'.$a.'}>>';
		}
		return str_replace(
				$erp,
				$pre,
				preg_replace(
					$this->white_space_regex,
					' ' ,
					str_replace(
						$pre,
						$erp,
						$input
					)
				)
			);
	}
}

