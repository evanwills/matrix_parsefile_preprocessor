<?php

require_once($cls.'regex/regex_error.inc.php');

class matrix_assembler
{
	/**
	 * @const string INCLUDES_REGEX a regular expression for
	 * matching preparse file keywords
	 *		[0] the full match of the keyword string
	 *		[1] the directory path to find the preparse
	 *			block or sub-preparse file (relative to the
	 *			current preparse file)
	 *		[2] the name of the file to be included
	 *		[3] (optional) find string/regex to do find and
	 *			replace on the praparse block/sub-preparse
	 *			file
	 *		[4] (optional) replace string to be used in
	 *			conjuction with find string/regex
	 *		[5] (optional) regex modifiers/regex identifier
	 *			"R" (if no modifiers)
	 *		[6] "}" - wrap output in comments or
	 *			"]" do NOt wrap output in comments
	 */
	const INCLUDES_REGEX = '\{(?:(\{)|(\[))((?:[a-zA-Z0-9 _-]+/)+)?([a-zA-Z0-9 _-]+)(?:\|([^|]+)\|([^\]\}\|]+)(?:\|([RimsxeADSUXJu]{1,11))?)?((?(1)\})(?(2)\]))\}';
	const BAD_INCLUDES_REGEX = '\{(?:(\{)|(\[)).*?(?:(?(1)\])(?(2)\}))\}';

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
	static private $partials = '';

	/**
	 * @var string $original the contents of the preparse file being
	 * processed (used for finding the line number of a keyword with an error)
	 */
	private $original = '';


// ==================================================================
// START: property validation and processing

	public function __construct( $source_path , $source_file ){
		if( !is_string($source_path) )
		{
			// throw
		}
		elseif( !is_dir($source_path) )
		{
			// throw
		}
		else
		{
			$this-path = realpath($source_path);
		}

		if( $this->check_path($this->path,$source_file) )
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
		$bk = $this->prop_backup( 'file' , 'original' , 'partials' , 'path' );
		$output = '';
		$includes = 0;

		if( $this->check_file($path,$file) === true )
		{
			$this->file = $file;
			$this->path = $path;

			$this->original = file_get_contents($path.$file);
			$output = preg_replace_callback( SELF::INCLUDES_REGEX , array( $this , ASSEMBLER_CALLBACK ) , $this->original , -1 , $includes );


			if( $includes === 0 ) {
				echo "\n\n--------- ERROR --------\nThere were no partial patterns found in\n\t{$this->path}{$this->file}\n\n";
				exit;
			}
			else {
				echo "\n\nThere were $includes partial patterns found in\n\t{$this->path}{$this->file}\n\n";
			}
		} else {
			echo "\n\n--------- ERROR --------\nCould not find\n\t{$path}{$file}\n\n";
			exit;
		}

		$this->prop_restore($bk);

		return $output;
	}

	public function set_partials_dir($partials_dir) {
		if( is_string($partials_dir) )
		{
			if( is_dir($partials_dir) )
			{
				self::$partials = realpath($partials_dir);
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
	 *				 [0] the full match of the keyword string
	 *     			 [1] the directory path to find the preparse
	 *         			 block or sub-preparse file (relative to the
	 *             		 current preparse file)
	 *               [2] the name of the file to be included
	 *               [3] (optional) find string/regex to do find and
	 *               	 replace on the praparse block/sub-preparse
	 *                   file
	 *               [4] (optional) replace string to be used in
	 *                   conjuction with find string/regex
	 *               [5] (optional) regex modifiers/regex identifier
	 *                   "R" (if no modifiers)
	 *               [6] "}" - wrap output in comments or
	 *                   "]" do NOt wrap output in comments
	 * @return string preparse file block or parsed wub-preparse file
	 *         [either] with modifications
	 */
	private function ASSEMBLER_CALLBACK($inc) {
		$bk = $this->prop_backup('partials','path');
		$partial_content = '';

		// get the partial
		if( $this->check_file($inc[1],'_'.$inc[2].'xml') )
		{
			$this->partials = $inc[1];
			$partial_content = file_get_contents($inc[1].'_'.$inc[2].'xml');
		}
		elseif( this->check_file($inc[1],$inc[2].'xml') )
		{
			$this->partials = $inc[1];
			$partial_content = new SELF( $inc[1] , $inc[2].'xml' );
		} else {
			$this->display_error($inc[0],"Could not find\n\t".$this->partials.'_'.$inc[2]."xml\nor\t".$this->partials.$inc[2].'xml');
		}

		// do find and replace if appropriate
		// useful when using the same partial for multiple design areas.
		if( $inc[3] != '' ) {
			if( $inc[5] != '' ) {
				// Do regular expression find/replacce

				// 'R' is not a valid PREG modifier, it is used to identify a regex so remove it
				$inc[5] = str_replace('R','',$inc[5]);
				$regex_error = regex_error( '`'.$inc[3].'`'.$inc[5] );
				if( $regex_error === false ) {
					$partial_content = preg_replace( "`{$inc[3]}`{$inc[5]}" , $inc[4] , $partial_content);
				}
				else
				{
					// regex has an error show error and terminate
					$this->display_error($inc[0], $regex_error);
				}
			}
			else
			{
				// do simple find/replace
				$partial_content = str_replace( $inc[3] , $inc[4] , $partial_content );
			}
		}

		$partial_content = preg_replace( SELF::TRIM_LINE_REGEX , '' , $partial_content );

		// wrap partial in comments (if appropriate)
		if( $inc[6] === '}')
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

			$partial_content = "{$open}START: {$inc[1]}{$inc[2]} {$close}{$partial_content}{$open} END:  {$inc[1]}{$inc[2]} $close"
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
						$path = realpath($this->partials.$path);
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
	private function display_error( $pattern , $msg ) {
		echo "\n\n--------- ERROR ---------\n$msg\n$pattern\nLine ".$this->get_error_line($pattern)." in {$this->path}{$this->file}\n\n";
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

		for( a = 0 ; $a < $b ; $a += 1 )
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