<?php

namespace matrix_parsefile_preprocessor\view;

if( !defined('MATRIX_PARSEFILE_PREPROCESSOR__VIEW__CLI') )
{

define('MATRIX_PARSEFILE_PREPROCESSOR__VIEW__CLI',true);


require(__DIR__.'/parse-file_view_base.abstract.class.php');


class cli_view extends base_view
{
	protected $base_file = '';
	protected $output_file = '';


	public function render_open( $file_name ) {
		if( !is_string($file_name) || trim($file_name) === '' )
		{
			throw new \Exception(get_class($this).'::render_open() expects only parameter $file_name to be a non-empty string. '.\type_or_value($file_name,'string').' given');
		}

		$file_prefix = pathinfo($file_name, PATHINFO_FILENAME);
		if( $file_prefix === '' )
		{
			throw new \Exception(get_class($this).'::render_open() expects only parameter $file_name to be the name of an existing file. "'.$file_name.'" is not an existing file.');
		}
		$this->base_file = $file_name;

		$this->output("\n\n ==============================================\n file: {$this->base_file}\n start: ".date('H:i:s, l \t\h\e jS \o\f F, Y ')."\n");
	}
	public function render_close() {}
	public function render_report_wrap_open() {}
	public function render_item_wrap_open() {}


	public function render_item( \matrix_parsefile_preprocessor\log_item $log_item )
	{
		parent::render_item($log_item);

		if( in_array($log_item->get_type(),$this->types) )
		{
			$tmp = $log_item->get_prop();
			$this->output("\n\n --------------------------------------------\n ".ucfirst($log_item->get_type())." (".$this->{$log_item->get_type().'s'}.') ('.$this->notice_count.") \n  ");
			$this->output($log_item->get_prop('msg')."\n\n");

			if( $log_item->get_extra_details_count() > 0 )
			{
				$tmp = $log_item->get_extra_details();
				for( $a = 0 ; $a < count($tmp) ; $a += 1 )
				{
					$this->output("\t-  {$tmp[$a]}\n");
				}
			}

			if( $log_item->get_prop('sample') !== '' )
			{
				$this->output("  sample: \"".$log_item->get_prop('sample')."\"\n\n");
			}
			if( $log_item->get_prop('line') > 0 )
			{
				$this->output("  line: ".$log_item->get_prop('line')."\n");
			}
			$file = $log_item->get_prop('file');
			if( $file !== '' && $file !== 'web' )
			{
				$this->output("  file: \"".$log_item->get_prop('file')."\"\n");
			}

			$this->output("\n");
		}
	}
	public function render_item_wrap_close() { }
	public function render_report_wrap_close() { }

	public function render_report( \matrix_parsefile_preprocessor\validator $validator , $file = false  )
	{

		$areas = $validator->get_areas_count();
		$non_printed_areas = $validator->get_non_printed_areas_count();

		$this->output("\n\n==============================================");
		$this->output("\n All done!\n");

		$this->output("\n Input:  {$this->base_file}\n");
		if( $this->output_file !== '' )
		{
			$this->output(" Output: {$this->output_file}\n");
		}

		$this->output("\n   {$this->partials} files processed.");
		$this->output("\n   {$this->keywords} keywords found");
		$this->output("\n   $areas design areas found");
		$this->output("\n   $non_printed_areas (or " . round($non_printed_areas/$areas,4)*100 . "%) design areas were non-print.");
		$this->output("\n   ".$validator->get_prints_count()." print tags found\n");
		$this->output("\n   There were:");
		$this->output("\n\t{$this->errors} errors");
		$this->output("\n\t{$this->warnings} warnings");
		$this->output("\n\t{$this->notices} notices");
		$this->output("\n\n");
	}
}



}