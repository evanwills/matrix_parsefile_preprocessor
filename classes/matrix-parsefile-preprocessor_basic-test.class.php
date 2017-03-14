<?php

namespace matrix_parsefile_preprocessor;


require_once(dirname(__FILE__).'/xml_tag.class.php');
require_once(dirname(__FILE__).'/MySource_tag.class.php');
require_once(dirname(__FILE__).'/parse-file_config.class.php');


class validator {

	private $config = null;

	private $IDs = ['__global__'];
	private $unprinted_IDs = [];

	private $tags = [];

	const TAG_REGEX = '`<MySource_(AREA|PRINT)(.*?)/?>`s';
	const SHOWIF_START_REGEX = '`^.*?(';
	const SHOWIF_END_REGEX = '.*?)(?=\s*<MySource_(?:THEN|ELSE>)).*$`s';
	const SHOWIF_CALLBACK_REGEX = '`(?<=value=")(.*?)(?=")`';


	public function __construct( )
	{
		$this->config = config::get();
	}


	public function parse( $code , $file_name , $file_content = '' )
	{
		if( $file_content === '' )
		{
			$file_content = $code;
		}
	}

	public function get_errors()
	{
		$output = [];
		foreach( $this->tags as $ID => $tags )
		{
			if( $tag->has_error() )
			{
				$output[] = $tag;
			}
		}
		if( count($output) === 0 )
		{
			return false;
		}
		else
		{
			return $output;
		}
	}
}