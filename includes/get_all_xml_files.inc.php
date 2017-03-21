<?php

namespace matrix_parsefile_preprocessor;


function get_all_xml_files($input)
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

	$output = [];
	if( !is_dir($input) )
	{
		$input = dirname($input).'/';
	}

	$contents = scandir($input);

	for( $a = 0 ; $a < count($contents) ; $a += 1 )
	{
		$info = pathinfo($contents[$a]);
		if( isset($info['extension']) )
		{
			$tmp = $input.$info['basename'];
			if( strtolower($info['extension']) === 'xml' && substr( $info['filename'] , 0 , 1 ) !== '_' && is_readable($tmp) )
			{
				$output[] = $tmp;
			}
		}
	}

	if( count($output) === 0 )
	{
		return false;
	}

	return $output;
}