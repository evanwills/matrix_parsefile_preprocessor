<?php

namespace matrix_parsefile_preprocessor;

if( !defined('MATRIX_PARSEFILE_PREPROCESSOR__COMPILER') )
{

define('MATRIX_PARSEFILE_PREPROCESSOR__COMPILER',true);

require(__DIR__.'/parse-file_config.class.php');
require(__DIR__.'/parse-file_validator.class.php');
require(__DIR__.'/parse-file_nested-partials.class.php');
require(__DIR__.'/parse-file_logger.class.php');

require(__DIR__.'/../includes/regex_error.inc.php');
require(__DIR__.'/../includes/type_or_value.inc.php');

class compiler {

	private $config = null;

	private $validator = null;

	private $log = null;

	private $nested_partials = null;
	private $file_writer = null;


	/**
	 * @var array list of absolute file paths of the
	 *		parse-file/partial currently being processed
	 */
	private $current_file = [];

	/**
	 * @var array list of full contents of the parse-file/partial
	 *		currently being processed being processed
	 */
	private $current_content = [];

	/**
	 * @var string $output_file name of file to be used as output of
	 *		compiled Squiz Matrix parse file XML
	 */
	private $output_file = '';

	/**
	 * @var resource $output file resource generated by fopen
	 */
	private $output = null;

	/**
	 * @var string last_match the complete string matched by the
	 * 		compiler regex to be used at the end of parsing this
	 *   	parse-file/partial
	 */
	private $last_match = '';

	private $fail_on_unprinted = false;
	private $show_error_extended = false;
	private $handle_comments = null;
	private $handle_white_space = null;
	private $handle_wrap = null;

	private $is_initialised = false;

	private $partials_processed = 0;
	private $keywords = 0;
	private $wrap_type = 'html';

	/**
	 * @const string INCLUDES_REGEX a regular expression for
	 * matching preparse file keywords
	 *		[0] the full match of the keyword string
	 *  	[1] preceeding content
	 *  	[2] whole keyword
	 *  	[3] opening wrapper
	 *		[4] the directory path to find the preparse
	 *			block or sub-preparse file (relative to the
	 *			current preparse file)
	 *		[5] the name of the file to be included
	 *  	[6] (optional) find/replace delimiter [`~|;]
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
		\{[{\[(]
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
			  # can be either "`" (back-tick), "|" (pipe), "~" (tilda) or ";" (semi-colon)
			  # NOTE: if your replace is a regex, the regex that matches the keywords
			  #       is an "\@" so you will need to escape any "\@" symbols in your regex
			  # NOTE ALSO: the delimiter you use to identify your find/replace will be
			  #       used to delimit your regex
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
		(?:(?<!\\\\)[)\]}]?\})
	)
)
@xs';

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




	public function __construct( config $config , logger $logger , nested_partials $partials , validator $validator , compiled_file_writer $file_writer )
	{
		$this->config = $config;
		$this->log = $logger;
		$this->nested_partials = $partials;
		$this->validator = $validator;
		$this->file_writer = $file_writer;
	}

	/**
	 * recursively parses parse-file/partials to build final
	 * SquizMatrix parse-file
	 * @param string $file_name
	 * @param [[Type]] [$modifiers = false] [[Description]]
	 */
	public function parse( $file_name , $modifiers = false , $wrap_type = false )
	{
		if( !is_string($file_name) || trim($file_name) === '' )
		{
			throw new \Exception(get_class($this).'::parse() expects first parameter $file_name to be a non-empty string. '.\type_or_value($file_name,'string').' given.');
		}
		if( $wrap_type !== false && (!is_string($wrap_type) || ($wrap_type !== 'html' && $wrap_type !== 'css' )))
		{
			throw new \Exception(get_class($this).'::parse() expects third parameter $wrap_type to be either FALSE, "html" or "css". '.\type_or_value($wrap_type,'string').' given.');
		}
		$this->_validate_modifiers($modifiers);

		if( substr(strtolower($file_name),-4,4) !== '.xml' )
		{
			$file_name .= '.xml';
		}

		$file_parts = pathinfo($file_name);

		$ok = false;
		try
		{
			$path = $this->nested_partials->add( $file_parts['dirname'].'/' , $file_parts['basename'] );
			$ok = true;
        }
		catch (\Exception $e)
		{
			$this->log->add(
				 'error'
				,"Could not find partial: \"$file_name\""
				,$this->_get_current('file')
				,$this->_get_nested_files()
				,$this->_get_current('content')
			);
		}

		if( $ok === true )
		{
			$file = $this->nested_partials->get_inner_most_file_whole();

			if( file_exists($file) )
			{
				$content = $this->_get_parse_file_contents($file,$modifiers);

				$wrap_type = $this->wrap_type;
				if( $wrap_type !== false )
				{
					$this->file_writer->wrap($file,true,$wrap_type);
				}

				$this->current_file[] = $file;
				$this->current_content[] = $content;

				$matches = 0;
				$count = 0;

				$content = preg_replace_callback( self::INCLUDES_REGEX , [ $this , '_PARSE_KEYWORDS_CALLBACK' ] , $content , -1 , $count );

				if( $count < 1 )
				{
					$this->validator->parse( $content , $file , $content );
					$this->log->add(
						'notice'
						,"no keywords were found in {$file_parts['dirname']}/{$file_parts['basename']}"
						,$file
					);
				}
				else
				{
					$this->keywords += $count;
					if( $count === 1 )
					{
						$tmp = "$count keyword was";
					}
					else
					{
						$tmp = "$count keywords were";
					}
					$this->log->add(
						'notice'
						,"$tmp found in {$file_parts['dirname']}/{$file_parts['basename']}"
						,$file
					);
				}
				$this->file_writer->output( $content );
				if( $wrap_type !== false )
				{
					$this->file_writer->wrap($file,false,$wrap_type);

				}

				$this->partials_processed += 1;

				$this->nested_partials->remove();
				array_pop($this->current_file);
				array_pop($this->current_content);
			}
		}
	}


	/**
	 * a pass through method to get errors from the validator
	 * @return array a list in order of all the tags that have
	 *               errors or warnings.
	 */
	public function get_logs()
	{
		return $this->log;
	}

	/**
	 * a pass through method to log any unprinted tags
	 * @return void
	 */
	public function log_unprinted()
	{
		$this->validator->log_unprinted();
	}

	/**
	 * Returns the number of partials processed.
	 * @return integer The number of partials processed
	 */
	public function get_processed_partials_count()
	{
		return $this->partials_processed;
	}

	/**
	 * Returns the number of keywords found in base parse file and all the partials.
	 * @return integer The number of keywords
	 */
	public function get_keyword_count()
	{
		return $this->keywords;
	}

	public function get_deleted_IDs()
	{
		return $this->validator->get_deleted_IDs();
	}

	public function check_deleted_areas()
	{
		$this->validator->check_deleted_areas();
	}


	public function get_validator()
	{
		return $this->validator;
	}

	private function _validate_modifiers($modifiers)
	{
		if(
			$modifiers !== false && (
				!is_array($modifiers) ||
				empty($modifiers) ||
				!isset($modifiers['find']) || !is_string($modifiers['find']) || trim($modifiers['find']) === '' ||
				!isset($modifiers['replace']) || !is_string($modifiers['replace']) ||
				!isset($modifiers['is_regex']) || !is_bool($modifiers['is_regex'])
			)
		)
		{
			throw new \Exception(get_class($this).'::parse() expects second parameter $modifiers to be either false or a non-empty array containing the following keys: \'find\', \'replace\', \'regex\'.');
		}
	}

	private function _get_parse_file_contents($file,$modifiers)
	{
		$content = $this->_fix_MySource_case(file_get_contents($file));
		if( $modifiers !== false )
		{
			if( $modifiers['is_regex'] === true )
			{
				if( $msg = regex_error($modifiers['find']) )
				{
					throw new \Exception(get_class($this).'::parse() expects second parameter $modifiers to contain a valid regex when $modifiers[is_regex] is TRUE. Regex error: "'.$msg.'"');
				}
				else
				{
					$content = preg_replace( $modifiers['find'] , $modifiers['replace'] , $content );
				}
			}
			else
			{
				$content = str_replace( $modifiers['find'] , $modifiers['replace'] , $content );
			}
		}
		return $content;
	}


	/**
	 * @function PARSE_KEYWORDS_CALLBACK() uses the match array of a
	 * regular expression on a single preparse file keyword and
	 * returns the defined contents after doing some stuff with it.
	 * @param  array $inc an array of seven items:
	 *		[0] the full match of the keyword string
	 *  	[1] preceeding content
	 *  	[2] whole keyword
	 *  	[3] opening wrapper
	 *		[4] the directory path to find the preparse
	 *			block or sub-preparse file (relative to the
	 *			current preparse file)
	 *		[5] the name of the file to be included
	 *  	[6] (optional) find/replace delimiter [`~|;]
	 *		[7] (optional) find string/regex to do find and
	 *			replace on the praparse block/sub-preparse
	 *			file
	 *		[8] (optional) replace string to be used in
	 *			conjuction with find string/regex
	 *		[9] (optional) regex modifiers/regex identifier
	 *			"R" (if no modifiers)
	 *		[10] closing wrapper
	 */
	private function _PARSE_KEYWORDS_CALLBACK($match)
	{
		$this->validator->parse( $match[1] , $this->_get_current('file') , $this->_get_current('content') );
		$this->last_match = $match[0];

		$this->file_writer->output($this->_fix_MySource_case($match[1]));

		$ok = false;
		$no_comments = false;
		if( $match[3] == '{{' && $match[10] == '}}' ) {
			$ok = true;
			$this->wrap_type = false;
		} elseif( $match[3] == '{[' && $match[10] == ']}'  ) {
			$ok = true;
			$this->wrap_type = 'html';
		} elseif( $match[3] == '{(' && $match[10] == ')}'  ) {
			$ok = true;
			$this->wrap_type = 'css';
		} else {
			// keyword dlimiters
			$this->log->add(
				 'error'
				,"Keyword delimiters '{$match[3]}' and '{$match[10]}' are not valid"
				,$match[2]
				,$this->_get_current('file')
				,$this->_get_current('content')
			);
		}


		if( $match[7] !== '' )
		{
			$modifiers = [
				'find' => $match[7],
				'replace' => stripslashes($match[8]),
				'is_regex' => false
			];
			if( $match[9] !== '' )
			{
				$modifiers['is_regex'] = true;
				$modifiers['find'] = $match[6].stripslashes($match[7]).$match[6];
				if( $match[9] !== 'R' )
				{
					$modifiers['find'] .= $match[9];
				}
				if( $msg = regex_error($modifiers['find']) )
				{
					$this->log->add(
						 'error'
						,"keyword find/replace regex is not valid\n\tError message: \"$msg\"\n\tDelimiter: \"{$match[6]}\"\n\tRaw pattern: \"{$match[7]}\"\n\tModifiers: \"{$match[9]}\"\n\tCompiled pattern: \"{$modifiers['find']}\"\n\n"
						,$match[2]
						,$this->_get_nested_files()
						,$this->_get_current()
					);
					$modifiers = false;
				}
			}
		}
		else
		{
			$modifiers = false;
		}

//
		try {
			$this->parse($match[4].$match[5],$modifiers);
		}
		catch( \Exception $e ) {

			$this->log->add(
				'error'
				,$e->getMessage()
				,$match[2]
				,$this->_get_nested_files()
				,$this->_get_current() );
		}
		return '';
	}


	private function _get_current( $type = 'content' )
	{
		if( $type !== 'file' && $type !== 'content' )
		{
			throw new \Exception(get_class($this).'::_get_current() expects only parameter to be string with a value of either "file" OR "content". '.\type_or_value($type,'string').' given.');
		}
		$tmp = 'current_'.$type;
		$c = count($this->{$tmp}) - 1;
		return $this->{$tmp}[$c];
	}

	private function _get_nested_files()
	{
		return implode(
			"\n      "
			,array_reverse($this->current_file)
		);
	}

	private function _fix_MySource_case($input)
	{
		return preg_replace_callback('`mysource_([a-z_]+)`i', array($this,'_FIX_MySource_CASE_CALLBACK'),$input);
	}

	private function _FIX_MySource_CASE_CALLBACK($matches)
	{
		return 'MySource_'.strtoupper($matches[1]);
	}

}



}