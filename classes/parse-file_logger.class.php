<?php

namespace matrix_parsefile_preprocessor;

require_once(dirname(__FILE__).'/parse-file_log-item.class.php');
require_once($_SERVER['PWD'].'/includes/get_line_number.inc.php');


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
			throw new \Exception($dud_msg);
		}
		if( !is_string($msg) || trim($msg) === '' )
		{
			throw new \Exception(get_class($this).'::add() expects second parameter $msg to be a non-empty string. '.gettype($msg).' given.');
		}
		if( !is_string($file) )
		{
			throw new \Exception(get_class($this).'::add() expects third parameter $msg to be a string. '.gettype($file).' given.');
		}
		if( !is_string($pattern) )
		{
			throw new \Exception(get_class($this).'::add() expects fourth parameter $pattern to be a string. '.gettype($pattern).' given.');
		}
		if( !is_string($whole_content) )
		{
			throw new \Exception(get_class($this).'::add() expects fifth parameter $whole_content to be a string. '.gettype($whole_content).' given.');
		}

		if( $pattern !== '' && $whole_content !== '' )
		{
			$line = get_line_number( $pattern, $whole_content );
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
}