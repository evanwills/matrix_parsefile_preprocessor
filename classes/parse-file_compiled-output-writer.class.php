<?php

namespace matrix_parsefile_preprocessor;

if( !defined('MATRIX_PARSEFILE_PREPROCESSOR__COMPILED_FILE_WRITER') )
{

define('MATRIX_PARSEFILE_PREPROCESSOR__COMPILED_FILE_WRITER',true);

require(__DIR__.'/../includes/type_or_value.inc.php');

class compiled_file_writer {

	private $handle_white_space = '_white_space_normal';
	private $handle_comments = '_leave_comments';
	private $white_space_find = '';
	private $white_space_replace = '';
	private $handle_wrap = '_dont_wrap';

	private $output = null;
	private $output_file = '';

	/**
	 * @const string TRIM_LINE_REGEX for trimming lines from the
	 * beginning and end of a preparse file partial
	 */
	const TRIM_LINE_REGEX = '`(?:^(?:[\t ]*[\r\n]+)+|(?:[\r\n]+[\t ]*)+$)`';

	const STRIP_COMMENT_REGEX = '`<!--(?!=\[).*?-->|/\*.*?\*/`s';

	const STRIP_WHITE_SPACE_COMPACT = '`(?<=^|[\r\n])[\t ]+|[\t ](?=[\r\n|$])|(\r\n|\n\r|\r|\n)+`';

	/**
	 * @const string TRIM_TRAILING_WHITE_SPACE removes spaces and
	 *        tabs from the end of lines
	 */
	const TRIM_TRAILING_WHITE_SPACE = '`[\s ]+(?=[\r\n]|$)`';


	public function __construct( config $config , nested_partials $partials )
	{

		$this->output_file = $config->get_var('output_dir').$partials->get_base_file();
		$this->output = fopen( $this->output_file , 'w+' );

		$ws = $config->get_var('white_space');
		if( $ws !== 'normal' )
		{
			$this->handle_white_space = '_strip_white_space';
			if( $ws == 'compress' )
			{
				$this->white_space_find = '`\s+`';
				$this->white_space_replace = ' ';
			}
			else
			{
				$this->white_space_find = self::STRIP_WHITE_SPACE_COMPACT;
				$this->white_space_replace = '\1';
			}
		}

		if( $config->get_var('strip_comments') === true )
		{
			$this->handle_comments = '_strip_comments';
		}

		if( $config->get_var('wrap_in_comments') === true )
		{
			$this->handle_wrap = '_wrap_in_comments';
		}
	}

	public function __destruct()
	{
		fclose($this->output);
	}

	public function output($input)
	{
		if( !is_string($input) )
		{
			throw new \Exception(get_class($this).'output() expects only parameter $input to be a string. '.gettype($input).' given.');
		}
		$input = $this->{$this->handle_comments}($input);
		$input = $this->{$this->handle_white_space}($input);
		fwrite( $this->output , $input );
	}

	public function wrap( $file_name, $open = false , $wrap_type = false )
	{
		$this->{$this->handle_wrap}( $file_name, $open , $wrap_type );
	}

	public function get_file_name()
	{
		return $this->output_file;
	}


	private function _dont_wrap($file_name, $open = false , $wrap_type = false )
	{
		// don't do anything;
	}

	private function _wrap_in_comments( $file_name, $open = false , $wrap_type = false )
	{

		if( $wrap_type !== false )
		{
			if( $open === true )
			{
				$tmp = 'START:';
			}
			else
			{
				$tmp = ' END: ';
			}

			if( $wrap_type === 'html' )
			{
				$open = '<!--';
				$close = '-->';
			}
			else
			{
				$open = '/*';
				$close = '*/';
			}
			fwrite($this->output , "\n$open $tmp $file_name $close\n" );
		}
	}

	private function _strip_comments($partial_content)
	{
		return preg_replace( '`<!--(?!=\[).*?-->|/\*.*?\*/`s' , '' , $partial_content );
	}

	private function _leave_comments($partial_content)
	{
		return $partial_content;
	}

	private function _white_space_normal($input) {
		return $input;
	}

	private function _strip_white_space($input) {
		$pre = $erp = [];
		if( preg_match_all('`<pre[^>]*>.*?</pre>`is',$input,$matches) )
		{
			for( $a = 0 ; $a > count($matches[0]) ; $a += 1 )
			{
				$pre[] = $matches[0][$a];
				$erp[] = '<<{PRE'.$a.'}>>';
			}
		}

		return str_replace(
				$erp,
				$pre,
				preg_replace(
					$this->white_space_find,
					$this->white_space_replace,
					str_replace(
						$pre,
						$erp,
						$input
					)
				)
			);
	}

}

}