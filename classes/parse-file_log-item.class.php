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
	protected $extra = [];

	/**
	 * Factory Method returns the correct object based on the type parameter.
	 * @param  string   $type   either: "Error", "Warning" or "Notice"
	 * @param  integer  $msg    Details of the log entry
	 * @param  string   $sample Code that triggered the need for this entry
	 * @param  string   $line   The line number the sample can be found on
	 * @param  string   $file   The name of the file the sample came from
	 * @return object   either a log_item_error, log_item_warning or log_item_notice object
	 */
	static public function get( $type , $msg , $sample , $line , $file )
	{
		$dud_msg = self::invalid_type($type);
		if( $dud_msg !== false )
		{
			throw new \Exception('log_item::get() expects first parameter $type'.$dud_msg);
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
			return ' to be a string matching one of the following: "error", "warning", "notice". '.$tail.' given.';
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

	public function set_extra_detail($input)
	{
		$ok = false;
		if(is_numeric($input) || ( is_string($input) && trim($input) !== '' ) )
		{
			$ok = true;
			$this->extra[] = $input;
		}
		if( is_array($input) )
		{
			foreach( $input as $value )
			{
				if(is_numeric($input) || ( is_string($input) && trim($input) !== '' ) )
				{
					$ok = true;
					$this->extra[] = $value;
				}
			}
		}
	}

	public function get_type()
	{
		return 'base';
	}

	public function get_extra_details()
	{
		return $this->extra;
	}

	public function get_extra_details_count()
	{
		return count($this->extra);
	}
}

class log_item_error extends log_item
{
	protected $type = 'error';

	public function get_type()
	{
		return 'error';
	}
}

class log_item_warning extends log_item
{
	protected $type = 'warning';

	public function get_type()
	{
		return 'warning';
	}
}

class log_item_notice extends log_item
{
	protected $type = 'notice';

	public function get_type()
	{
		return 'notice';
	}
}