<?php

require_once('classes/xml_tag.class.php');

class mysource_tag extends xml_tag
{
	protected $printed = false;
	protected $called = false;

	public function __construct( $element , $attrs , $ln_number )
	{
		parent::__construct( $element, $attrs, $ln_number );

		foreach($this->attrs as $key => $value )
		{
			if( $key === 'print' )
			{
				if( $value === 'yes' )
				{
					$this->printed = true;
				}
				break;
			}
		}
	}

	public function get_printed() { return $this->printed; }
	public function set_printed() {
		$this->printed = true;
	}

	public function get_called() { return $this->called; }
	public function set_called() {
		$this->called = true;
	}
}