<?php

namespace matrix_parsefile_preprocessor\view;

if( !defined('MATRIX_PARSEFILE_PREPROCESSOR__BASE_VIEW') )
{

define('MATRIX_PARSEFILE_PREPROCESSOR__BASE_VIEW',true);

require(__DIR__.'/../../includes/type_or_value.inc.php');



abstract class base_view
{
	protected $notice_count = 0;
	protected $errors = 0;
	protected $warnings = 0;
	protected $notices = 0;
	protected $keywords = 0;
	protected $partials = 0;
	protected $config = null;
	protected $output_file = '';

	protected $types = ['error' , 'warning' , 'notice'];

	public function __construct( $types , \matrix_parsefile_preprocessor\config $config )
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
		$this->config = $config;
	}

	public function set_compile_stats( \matrix_parsefile_preprocessor\compiler $compiler )
	{
		$this->partials = $compiler->get_processed_partials_count();
		$this->keywords = $compiler->get_keyword_count();
	}

	public function set_output_file( \matrix_parsefile_preprocessor\compiled_file_writer $writer )
	{
		$this->output_file = $writer->get_file_name();
	}

	public function reset_counters( \matrix_parsefile_preprocessor\config $config )
	{
		$this->notice_count = 0;
		$this->notice_count = 0;
		$this->errors = 0;
		$this->warnings = 0;
		$this->notices = 0;
		$this->keywords = 0;
		$this->partials = 0;
		$this->config = $config;
	}
	abstract public function render_open( $file_name );
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

	abstract public function render_report( \matrix_parsefile_preprocessor\validator $validator , $file = false );

	protected function output( $input )
	{
		echo $input;
	}
}


}