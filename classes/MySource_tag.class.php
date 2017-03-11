<?php

require_once('classes/xml_tag.class.php');

class mysource_tag extends xml_tag
{
	protected $printed = false;
	protected $called = false;
	protected $error = false;
	protected $error_msg = '';

	public function __construct( $element , $attrs , $ln_number )
	{
		parent::__construct( $element, $attrs, $ln_number );

		foreach($this->attrs as $key => $value )
		{
			if( $key === 'print' )
			{
				$value = strtolower($value);
				$this->attr[$key] = $value;
				if( $value === 'yes' )
				{
					$this->printed = true;
				}
			}
			if( $key === 'id_name' )
			{
				$this->id = $value;
				unset($this->attrs[$key]);
			}
		}
	}

	public function get_printed()
	{
		return $this->printed;
	}
	public function set_printed()
	{
		$this->printed = true;
	}

	public function get_called()
	{
		return $this->called;
	}
	public function set_called()
	{
		$this->called = true;
	}

	public function set_error( $error_msg )
	{
		if( !is_string($error_msg) || trim($error_msg) !== '' )
		{
			throw new exception('mysource_tag::set_error() expects only parameter $error_msg to be a non-empty string');
		}
		$this->error = true;
		$this->error_msg = $error_msg;
	}

	public function get_error()
	{
		return $error_msg;
	}

	protected function has_error()
	{
		return $this->error;
	}
}