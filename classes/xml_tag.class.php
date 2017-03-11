<?php

class xml_tag
{
	protected $name = '';
	protected $id = '';
	protected $attrs = array();
	protected $line = 0;

	const ATTR_REGEX = '`(?<=\s)([a-z_-]+)(?:=(?:"([^"]+?)"|\'([^\']+?\'|([a-z0-9_-]+)))?(?=\s|>)`i';

	public function __construct( $element , $attrs , $ln_number )
	{
		if( !is_string($element) || trim($element) == '' )
		{
			throw new exception(get_class($this).' constructor expects first parameter $element to be a non-empty string');
		}
		if( !is_string($attrs) || trim($attrs) == '' )
		{
			throw new exception(get_class($this).' constructor expects second parameter $attrs to be a non-empty string');
		}
		if( !is_int($ln_number) || $ln_number < 1 )
		{
			throw new exception(get_class($this).' constructor expects second parameter $ln_number to be an integer greater than 1');
		}

		$this->name = $element;
		$this->line = $ln_number;


		if( preg_match_all( self::ATTR_REGEX , $attrs , $matches , PREG_SET_ORDER ) )
		{
			for( $a = 0 ; $a < count($matches) ; $a += 1 )
			{
				$c = count($matches[$a]);
				$key = strtolower($matches[$a][1]);
				if( $c > 3 )
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

	public function get_line()
	{
		return $this->line;
	}

	public function get_attr( $attr )
	{
		if( !is_str($attr) && !is_numeric($attr) )
		{
			throw new exception(get_class($this).'::get_attr() expects only parameter to be a string or number');
		}

		if( isset($this->attr[$attr]) )
		{
			return $this->attr[$attr];
		}
		else
		{
			return false;
		}
	}
}