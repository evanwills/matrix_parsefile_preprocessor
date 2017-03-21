<?php

namespace matrix_parsefile_preprocessor;


if( !defined('MATRIX_PARSEFILE_PREPROCESSOR__GET_LINE_NUMBER') )
{

define('MATRIX_PARSEFILE_PREPROCESSOR__GET_LINE_NUMBER',true);



/**
 * @function get_line_number() returns the line the current
 * keyword is on in the preparse file being processed
 * @param  string  $pattern (equivelent to $inc[0]) the full
 *                         keyword string where an error has occured
 * @return integer the line number of the current keyword
 */
function get_line_number( $pattern , $content ) {
	$arr =	preg_split(
					 '`(\r\n|\n\r|\r|\n)`'
					,preg_replace(
						 '`(?<='.str_replace('`','\\`',preg_quote($pattern)).').*$`s'
						,''
						,$content
					)
			);
	return count($arr);
}



}