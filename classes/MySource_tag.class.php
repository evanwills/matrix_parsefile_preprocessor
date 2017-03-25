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
	protected $error_msg = [];
	protected $error_count = 0;
	protected $warning_count = 0;
	protected $notice_count = 0;

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
				$this->id = trim($value);
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
		$this->error_msg[] = $error_msg;
		$this->error_count += 1;
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

	public function invalid_id(\matrix_parsefile_preprocessor\logger $logger , $c )
	{
		if( !is_int($c) && $c < 1 )
		{
			throw new \Exception(get_class($this).'::invalid_id() expects secont parameter $c to be an integer greater than 0. '.type_or_value($c, 'integer').' given.');
		}

		if( $this->id === '' )
		{
			$this->log->add(
				'error'
				,'"'.$this->get_whole_tag().'" didn\'t have an id_name attribute.'
				,$this->get_file()
				,$this->get_whole_tag()
				,''
				,$this->get_line()
			);
			$this->id = 'dudID-'.count($this->tags);
			return true;
		}
		return false;
	}
}



}