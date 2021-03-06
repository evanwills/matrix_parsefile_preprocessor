<?php

namespace matrix_parsefile_preprocessor;

if( !defined('MATRIX_PARSEFILE_PREPROCESSOR__TYPE_OR_VALUE') )
{

define('MATRIX_PARSEFILE_PREPROCESSOR__TYPE_OR_VALUE',true);



/**
 * works out whether the type of input matches the specified variable
 * type. If so, it returns the input value. If not, it returns the type
 * @param  mixed $input value to be checked
 * @param  string $type  data type the value should be
 * @return string either the value itself or the values type
 */
function type_or_value($input, $var_type)
{
	$tmp_type = gettype($input);
	if( $tmp_type === $var_type )
	{
		if( $var_type === 'boolean' )
		{
			if( $input === true )
			{
				return 'TRUE';
			}
			else
			{
				return 'FALSE';
			}
		}
		else
		{
			return '"'.$input.'"';
		}
	}
	else
	{
		return $var_type;
	}
}


}