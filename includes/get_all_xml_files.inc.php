<?php

namespace matrix_parsefile_preprocessor;


function get_all_xml_files($input , $include_syblings = false )
{
	if( !is_string($input) || trim($input) === '' )
	{
		return false;
	}
	$input = realpath($input);

	if( $input === false )
	{
		return false;
	}

	if( $tmp = check_is_good_xml($input) && $include_syblings !== true )
	{
		return [$input];
	}

	if( !is_dir($input) )
	{
		$input = dirname($input).'/';
	}

	$output = [];
	$contents = scandir($input);

	for( $a = 0 ; $a < count($contents) ; $a += 1 )
	{
		if( $tmp = check_is_good_xml($input.$contents[$a]) )
		{
			$output[] = $tmp;
		}
	}

	if( count($output) === 0 )
	{
		return false;
	}

	return $output;
}

function check_is_good_xml( $item )
{
	$info = pathinfo($item);
	if( isset($info['extension']) )
	{
		if( strtolower($info['extension']) === 'xml' && substr( $info['filename'] , 0 , 1 ) !== '_' && is_readable($item) )
		{
			return $item;
		}
	}
	return false;
}