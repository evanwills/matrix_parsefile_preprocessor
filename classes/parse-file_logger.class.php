<?php

namespace matrix_parsefile_preprocessor;

require_once(dirname(__FILE__).'/parse-file_log-item.class.php');


class logger
{
	static private $me = null;

	private $log = [ 'all' => [] , 'error' => [] , 'warning' => [] , 'notice' => [] ];

	static public function get()
	{
		if( self::$me === null )
		{
			self::$me = new logger();
		}
		return self::$me;
	}

	public function add( $type , $msg , $file = '' , $pattern = '' , $whole_content = '' )
	{
		if( $dud_msg = log_item::invalid_type($type) )
		{
			throw new \exception($dud_msg);
		}
		if( !is_string($msg) || trim($msg) === '' )
		{
			throw new \exception(get_class($this).'::add() expects second parameter $msg to be a non-empty string. '.gettype($msg).' given.');
		}
		if( !is_string($file) )
		{
			throw new \exception(get_class($this).'::add() expects third parameter $msg to be a string. '.gettype($file).' given.');
		}
		if( !is_string($pattern) )
		{
			throw new \exception(get_class($this).'::add() expects fourth parameter $pattern to be a string. '.gettype($pattern).' given.');
		}
		if( !is_string($whole_content) )
		{
			throw new \exception(get_class($this).'::add() expects fifth parameter $whole_content to be a string. '.gettype($whole_content).' given.');
		}

		if( $pattern !== '' && $whole_content !== '' )
		{
			$line = $this->_get_error_line( $pattern, $whole_content );
		}
		else
		{
			$line = 0;
		}

		$tmp = log_item::get($type , $msg , $pattern , $line , $file );
		$this->log['all'][] = $tmp;
		$this->log[$type][] = $tmp;
	}


	public function get_all()
	{
		return $this->log['all'];
	}
	public function get_errors()
	{
		return $this->log['error'];
	}
	public function get_warnings()
	{
		return $this->log['warning'];
	}
	public function get_notices()
	{
		return $this->log['notice'];
	}


	/**
	 * @function _get_error_line() returns the line the current
	 * keyword is on in the preparse file being processed
	 * @param  string $pattern (equivelent to $inc[0]) the full
	 *                keyword string where an error has occured
	 * @return integer the line number of the current keyword
	 */
	private function _get_error_line( $pattern , $content ) {
		$arr =	preg_split(
						 '`(\r\n|\n\r|\r|\n)`'
						,preg_replace(
							 '`(?<='.str_replace('`','\\`',preg_quote($pattern)).').*$`s'
							,''
							,$content
						)
				);
		return count($arr);
	}



}