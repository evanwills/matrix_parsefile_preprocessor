<?php

if( !defined('MATRIX_PARSEFILE_PREPROCESSOR__XML_TAG') )
{

define('MATRIX_PARSEFILE_PREPROCESSOR__XML_TAG',true);


require(__DIR__.'/../includes/type_or_value.inc.php');

class xml_tag
{
	protected $name = '';
	protected $whole_tag = '';
	protected $id = '';
	protected $attrs = array();
	protected $line = 0;
	protected $file = '';

	const ATTR_REGEX = '`(?<=\s)([a-z_-]+)(?:=(?:"([^"]+?)"|\'([^\']+?)\'|([a-z0-9_-]+)))?(?=\s|$)`i';

	public function __construct( $whole , $element , $attrs , $file , $ln_number )
	{
		if( !is_string($whole) || trim($whole) == '' )
		{
			throw new \Exception(get_class($this).' constructor expects first parameter $whole to be a non-empty string. '.\type_or_value($whole,'string').' given');
		}
		if( !is_string($element) || trim($element) == '' )
		{
			throw new \Exception(get_class($this).' constructor expects first parameter $element to be a non-empty string. '.\type_or_value($element,'string').' given');
		}
		if( !is_string($attrs) || trim($attrs) == '' )
		{
			throw new \Exception(get_class($this).' constructor expects second parameter $attrs to be a non-empty string. '.\type_or_value($attrs,'string').' given');
		}
		if( !is_int($ln_number) || $ln_number < 1 )
		{
			throw new \Exception(get_class($this).' constructor expects second parameter $ln_number to be an integer greater than zero. '.\type_or_value($ln_number,'integer').' given');
		}
		if( !is_string($file) || trim($file) === '' )
		{
			throw new \Exception(get_class($this).' constructor expects fifth parameter $file to be a non-empty string. '.\type_or_value($file,'string').' given');
		}

		$this->whole_tag = $whole;
		$this->name = $element;
		$this->line = $ln_number;
		$this->file = $file;

		if( preg_match_all( self::ATTR_REGEX , $attrs , $matches , PREG_SET_ORDER ) )
		{
			for( $a = 0 ; $a < count($matches) ; $a += 1 )
			{
				$c = count($matches[$a]);
				$key = strtolower($matches[$a][1]);
				if( $c >= 3 )
				{
					$c -= 1;
					$value = $matches[$a][$c];
				}
				else
				{
					$value = $key;
				}

				if( $key === 'id' )
				{
					$this->id = $value;
				}
				else
				{
					$this->attrs[$key] = $value;
				}
			}
		}
	}

	public function get_name()
	{
		return $this->name;
	}

	public function get_id()
	{
		return $this->id;
	}

	public function get_whole_tag()
	{
		return $this->whole_tag;
	}

	public function get_line()
	{
		return $this->line;
	}

	public function get_file()
	{
		return $this->file;
	}

	public function get_attr( $attr )
	{
		if( !is_string($attr) && !is_numeric($attr) )
		{
			throw new \Exception(get_class($this).'::get_attr() expects only parameter to be a string or number. '.type_or_value($attrs,'string').' given.');
		}


		if( isset($this->attrs[$attr]) )
		{

			return $this->attrs[$attr];
		}
		else
		{
			return false;
		}
	}

	public function get_all()
	{
		return get_object_vars($this);
	}
}



}
