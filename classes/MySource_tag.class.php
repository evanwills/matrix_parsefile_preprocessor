<?php

if( !defined('MATRIX_PARSEFILE_PREPROCESSOR__MYSOURCE_TAG') )
{

define('MATRIX_PARSEFILE_PREPROCESSOR__MYSOURCE_TAG',true);


require(__DIR__.'/xml_tag.class.php');
require(__DIR__.'/../includes/type_or_value.inc.php');

class mysource_tag extends xml_tag
{
	protected $printed = true;
	protected $called = false;
	protected $error = false;
	protected $error_msg = '';

	public function __construct( $whole , $element , $attrs , $file , $ln_number )
	{
		parent::__construct( $whole , $element , $attrs , $file , $ln_number );

		foreach($this->attrs as $key => $value )
		{
			if( $key === 'print' )
			{
				$value = strtolower($value);
				$this->attrs[$key] = $value;
				if( $value === 'no' )
				{
					$this->printed = false;
				}
				else
				{
					$printed = false;
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
		if( !is_string($error_msg) && trim($error_msg) !== '' )
		{
			throw new \Exception('mysource_tag::set_error() expects only parameter $error_msg to be a non-empty string. '.type_or_value($error_msg,'string').' given.');
		}
		$this->error = true;
		$this->error_msg = $error_msg;
	}

	public function get_error()
	{
		return $this->error_msg;
	}

	public function has_error()
	{
		return $this->error;
	}
	public function get_all()
	{
		return get_object_vars($this);
	}
}



}