<?php

require_once($cls.'regex/regex_error.inc.php');

class matrix_parsefile_preprocessor
{

	protected function get_line_number($haystack,$needle) {
		if( is_string($haystack) && is_string($needle) ) {
			return count(
							preg_split(
									 '`(?:\r\n|\n\r|\r|\n)`'
									,substr_replace(
											 $haystack
											,''
											,(
												strpos($haystack,$needle)
												+
												strlen($needle)
											)
									)
							)
						);
		}
	}
}

