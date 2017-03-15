<?php

namespace matrix_parsefile_preprocessor;

/**
 * log_item is a factory class for different log item types.
 */
class log_item
{
	protected $type = 'base';
	protected $msg = '';
	protected $sample = '';
	protected $line = 0;
	protected $file = '';

	static public function get( $type , $msg , $sample , $line , $file )
	{
		$dud_msg = self::invalid_type($type);
		if( $dud_msg !== false )
		{
			throw new \exception($dud_msg);
		}

		$tmp = 'matrix_parsefile_preprocessor\\log_item_'.$type;
		return new $tmp( $msg, $sample, $line , $file );
	}

	static public function invalid_type($type)
	{
		$ok = true;
		if( !is_string($type) )
		{
			$ok = false;
			$tail = gettype($type);
		}
		else
		{
			$tmp = 'log_item_'.$type;

			if( !class_exists('matrix_parsefile_preprocessor\\'.$tmp) )
			{
				$ok = false;
				$tail = '"'.$type.'"';
			}
		}

		if( $ok === false )
		{
			return 'log_item::get() expects first parameter $type to be a string matching one of the following: "error", "warning", "notice". '.$tail.' given.';
		}
		return false;
	}

	public function get_prop( $which = 'all' )
	{
		if( property_exists($this,$which) )
		{
			return $this->$which;
		}
		else
		{
			return [
				 'type' => $this->type
				,'msg' => $this->msg
				,'sample' => $this->sample
				,'line' => $this->line
				,'file' => $this->file
			];
		}
	}

	protected function __construct( $msg , $sample , $line , $file )
	{
		$this->msg = $msg;
		$this->sample = $sample;
		$this->line = $line;
		$this->file = $file;
	}
}

class log_item_error extends log_item
{
	protected $type = 'error';
}

class log_item_warning extends log_item
{
	protected $type = 'warning';
}

class log_item_notice extends log_item
{
	protected $type = 'notice';
}