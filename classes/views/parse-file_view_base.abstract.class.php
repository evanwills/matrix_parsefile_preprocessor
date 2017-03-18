<?php

namespace matrix_parsefile_preprocessor\view;

require_once($pwd.'/includes/type_or_value.inc.php');

abstract class base_view
{
	protected $notice_count = 0;
	protected $errors = 0;
	protected $warnings = 0;
	protected $notices = 0;
	protected $keywords = 0;
	protected $partials = 0;

	protected $types = ['error' , 'warning' , 'notice'];

	public function __construct( $partials_count , $keyword_count , $types )
	{
		if( is_string($types) )
		{
			if( in_array($types, $this->types) )
			{
				$this->types = [$types];
			}
		}
		elseif( is_array($types) )
		{
			$tmp = [];
			for( $a = 0 ; $a < count($types) ; $a += 1 )
			{
				$types[$a] = strtolower(trim($types[$a]));
				if( in_array($types[$a], $this->types) )
				{
					$tmp[] = $types[$a];
				}
			}
			$this->types = $tmp;
		}
		if( !is_int($partials_count) || $partials_count < 0 )
		{
			throw new \Exception(get_class($this).'constructor expects first parameter $partials_count to be an integer, zero or higher. '.\type_or_value($partials_count,'integer').' given.');
		}
		if( !is_int($keyword_count) || $keyword_count < 0 )
		{
			throw new \Exception(get_class($this).'constructor expects first parameter $keyword_count to be an integer, zero or higher. '.\type_or_value($keyword_count,'integer').' given.');
		}
		$this->partials = $partials_count;
		$this->keywords = $keyword_count;
	}

	abstract public function render_open();
	abstract public function render_close();
	abstract public function render_report_wrap_open();
	abstract public function render_item_wrap_open();
	public function render_item( \matrix_parsefile_preprocessor\log_item $log_item )
	{
		$this->notice_count += 1;
		$this->{$log_item->get_type().'s'} += 1;
	}
	abstract public function render_item_wrap_close();
	abstract public function render_report_wrap_close();
	abstract public function render_report();
}