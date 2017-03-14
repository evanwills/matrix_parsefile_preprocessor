<?php
/**
 * extract_dot_info() takes the contents of an info file and converts
 * it to a multi dimensional array.
 *
 * @param $info_content string contents of a .info file
 * @param $file_path boolean append the full file name to the array
 * @return array multi dimensional array
 */
function extract_dot_info($info_content, $file_path = false )
{
	$info_array = array();
	if(is_file($info_content) && strtolower($info_content) != 'debug.info' )
	{
		$info_file = $info_content;
		$info_content = file_get_contents($info_content);
	};
	$info_regex = '/
(?<=^|[\n\r])
(?:
	([^;\n\r][^\[=:]+)	# $key_value[x][1]
	(?:
		\[
		([^\]]*)	# $key_value[x][2]
		(\])		# $key_value[x][3]
	)?
	(?:
		\[
		([^\]]*)	# $key_value[x][4]
		(\])		# $key_value[x][5]
	)?
	(?:
		\[
		([^\]]*)	# $key_value[x][6]
		(\])		# $key_value[x][7]
	)?
)
[\t ]*
(?:=|:)
(.*)	# $key_value[x][8]
(?:[;\n\r])
/iUx';
	preg_match_all( $info_regex , $info_content , $key_value , PREG_SET_ORDER);	

	foreach($key_value as $info_item)
	{
		$key_0 = prep_key($info_item[1]); // First dimension
		$key_1 = prep_key($info_item[2]); // Second dimension key
		$key_test_1 = $info_item[3];	// Second dimension test
		$key_2 = prep_key($info_item[4]); // Third dimension key
		$key_test_2 = $info_item[5];	// Third dimension test
		$key_3 = prep_key($info_item[6]); // Fourth dimension key
		$key_test_3 = $info_item[7];	// Fourth dimension test
		$value = trim($info_item[8]);	// item value

		if( !empty($key_0) && !empty($key_test_1) && !empty($key_test_2) && !empty($key_test_3) )
		{ // All four dimensions are set
			$info_array[$key_0][$key_1][$key_2][$key_3] = $value;
		}
		elseif( !empty($key_0) && empty($key_test_1) && !empty($key_test_2) && !empty($key_test_3) )
		{ // Three out of Four dimensions are set
			$info_array[$key_0][$key_2][$key_3] = $value;
		}
		elseif( !empty($key_0) && empty($key_test_1) && empty($key_test_2) && !empty($key_test_3) )
		{ // Two out of Four dimensions are set
			$info_array[$key_0][$key_3] = $value;
		}
		elseif( !empty($key_0) && empty($key_test_1) && empty($key_test_2) && empty($key_test_3) )
		{ // Only one dimension is set
			$info_array[$key_0] = $value;
		};
	};
	if( isset($info_file) && $file_path === true )
	{
		$info_array['info_file'] = $info_file;
	};
	return $info_array;
};


function prep_key($key)
{
	return trim(strtolower(str_replace( ']' , '' , $key )));
};

