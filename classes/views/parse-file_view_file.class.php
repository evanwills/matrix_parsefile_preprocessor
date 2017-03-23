<?php

namespace matrix_parsefile_preprocessor\view;

if( !defined('MATRIX_PARSEFILE_PREPROCESSOR__VIEW__FILE') )
{

define('MATRIX_PARSEFILE_PREPROCESSOR__VIEW__FILE',true);


require(__DIR__.'/parse-file_view_cli.class.php');


class file_view extends cli_view
{
	private $log_file = null;
	private $start = '';

	public function render_open($file_name)
	{
		if( !is_string($file_name) || trim($file_name) === '' )
		{
			throw new \Exception(get_class($this).'::render_open() expects only parameter $file_name to be a non-empty string. '.\type_or_value($file_name,'string').' given');
		}

		$file_prefix = pathinfo($file_name, PATHINFO_FILENAME);
		if( $file_prefix === '' )
		{
			throw new \Exception(get_class($this).'::render_open() expects only parameter $file_name to be the name of an existing file. "'.$file_name.'" is not an existing file.');
		}

		$output_dir = $this->config->get_var('log_dir');
		if( !is_dir($output_dir) || !is_writable($output_dir) )
		{
			throw new \Exception(get_class($this).'::render_open() expects '.$output_dir.' to be a writable directory.');
		}

		if( $this->log_file === null )
		{
			$this->start = '_'.date('Y-m-d_H-i-s').'.log.txt';
		}
		else
		{
			fclose($this->log_file);
		}

		$tmp = $this->config->get_var('log_dir').$file_prefix.$this->start;
		if( file_exists($tmp) && !is_writable($tmp) )
		{
			throw new \Exception(get_class($this).' expects "'.$this->config->get_var('log_dir').'" to either not exist or to be writable. "'.$tmp.'" is not writable.');
		}

		$this->log_file = fopen( $tmp , 'w+' );

		parent::render_open($file_name);
	}


	protected function output($input)
	{
		fwrite( $this->log_file , $input );
	}

	public function __destruct()
	{
		fclose($this->log_file);
	}
}



}