<?php
/**
 * @function regex_error() outputs PHP error message (if any) for a particular regex
 *
 * Takes a supplied regular expression and runs it through
 * the appropriate PHP core function trapping any errror
 * message generated and returns it.
 *
 * @param string $regex Regular expression to be tested
 * @param boolean $pcre True if PCRE False if Posix
 *
 * @return string if the supplied regular expression generated an error
 * @return boolean false if the supplied regular expression didn't generate an error
 */
function regex_error( $regex , $pcre = true )
{

	if($old_track_errors = ini_get('track_errors'))
	{
		$old_php_errormsg = isset($php_errormsg)?$php_errormsg:false;
	}
	else
	{
		ini_set('track_errors' , 1);
	};

	unset($php_errormsg);

	if( $pcre === true )
	{
		@preg_match($regex , '');
		$flav = 'PCRE';
	}
	else
	{
		@ereg($regex , '');
		$flav = 'POSIX';
	};

	$output = isset($php_errormsg)?$php_errormsg:false;

	if($old_track_errors)
	{
		$php_errormsg = isset($old_php_errormsg)?$old_php_errormsg:false;
	}
	else
	{
		ini_set('track_errors' , 0);
	};
	return $output;
};

