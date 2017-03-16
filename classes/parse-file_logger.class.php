<?php

namespace matrix_parsefile_preprocessor;

require_once(__DIR__.'/parse-file_log-item.class.php');
require_once($pwd.'/includes/get_line_number.inc.php');
require_once($pwd.'/includes/type_or_value.inc.php');


class logger
{
	static private $me = null;

	private $log = [ 'all' => [] , 'error' => [] , 'warning' => [] , 'notice' => [] ];
	private $itterator = null;

	static public function get()
	{
		if( self::$me === null )
		{
			self::$me = new logger();
		}
		return self::$me;
	}

	public function add( $type , $msg , $file = '' , $pattern = '' , $whole_content = '' , $line = 0 )
	{
		if( $dud_msg = log_item::invalid_type($type) )
		{
			throw new \Exception($dud_msg);
		}
		if( !is_string($msg) || trim($msg) === '' )
		{
			throw new \Exception(get_class($this).'::add() expects second parameter $msg to be a non-empty string. '.\type_or_value($msg,'string').' given.');
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
		elseif( !is_int($line) || $line < 0 )
		{
			throw new \Exception(get_class($this).'::add() expects sixth parameter $line to be a an integer zero or greater. '.\type_or_value($whole_content,'integer').' given.');
		}

		$tmp = log_item::get($type , $msg , $pattern , $line , $file );
		$this->log['all'][] = $tmp;
		$this->log[$type][] = $tmp;
	}

	public function get_next_item()
	{
		if( $this->itterator === null )
		{

			if( func_num_args() === 0 )
			{
				$this->itterator = $this->log['all'];
			}
			elseif( func_num_args() === 1 )
			{
				$arg0 = func_get_arg(1);
				if( isset($this->log[$arg0]))
				{
					$this->itterator = $this->log[$arg0];
				}
			}
			else
			{
				$args = func_get_args();
				for( $a = 0 ; $a < count($this->log['all']) ; $a += 1 )
				{
					if( in_array($this->log['all'][$a]->get_type(), $args) )
					{
						$this->itterator[] = $this->log['all'][$a];
					}
				}
			}
		}
		if( count($this->itterator) > 0 )
		{
			return array_shift($this->itterator);
		}
		else
		{
			$this->itterator = null;
			return false;
		}
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