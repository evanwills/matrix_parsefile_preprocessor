<?php

namespace matrix_parsefile_preprocessor\view;

require_once(__DIR__.'/parse-file_view_base.abstract.class.php');


class cli_view extends base_view
{
	public function render_open()
	{
	}
	public function render_close()
	{
	}
	public function render_report_wrap_open() {}
	public function render_item_wrap_open() {}


	public function render_item( \matrix_parsefile_preprocessor\log_item $log_item )
	{
		parent::render_item($log_item);

		$tmp = $log_item->get_prop();
		echo "\n\n ---------------------- ".ucfirst($log_item->get_type())." (".$this->notice_count.") ----------------------\n\n  ";
		echo $log_item->get_prop('msg')."\n\n";

		if( $log_item->get_prop('sample') !== '' )
		{
			echo "  sample: \"".$log_item->get_prop('sample')."\"\n\n";
		}
		if( $log_item->get_prop('line') > 0 )
		{
			echo "  line: ".$log_item->get_prop('line')."\n";
		}
		$file = $log_item->get_prop('file');
		if( $file !== '' && $file !== 'web' )
		{
			echo "  file: \"".$log_item->get_prop('file')."\"\n";
		}

		echo "\n";
	}
	public function render_item_wrap_close() { }
	public function render_report_wrap_close() { }

	public function render_report()
	{
		echo "\n\n==============================================";
		echo "\n All done!\n";
		echo "\n   {$this->partials} files processed.";
		echo "\n   {$this->keywords} keywords found\n";
		echo "\n   There were:";
		echo "\n\t{$this->errors} errors";
		echo "\n\t{$this->warnings} warnings";
		echo "\n\t{$this->notices} notices";
		echo "\n\n";
	}
}