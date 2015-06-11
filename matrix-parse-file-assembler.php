<?php

// ==================================================================
// START: debug include

if(!function_exists('debug'))
{
	if(isset($_SERVER['HTTP_HOST'])){ $path = $_SERVER['HTTP_HOST']; $pwd = dirname($_SERVER['SCRIPT_FILENAME']).'/'; }
	else { $path = $_SERVER['USER']; $pwd = $_SERVER['PWD'].'/'; };
	if( substr_compare( $path , '192.168.' , 0 , 8 ) == 0 ) { $path = 'localhost'; }
	switch($path)
	{
		case '192.168.18.128':	// work laptop (debian)
		case 'antechinus':	// work laptop (debian)
		case 'localhost':	// home laptop
		case 'evan':		// home laptop
		case 'wombat':	$root = '/var/www/';	$inc = $root.'includes/'; $classes = $cls = $root.'classes/'; break; // home laptop

		case 'burrawangcoop.net.au':	// DreamHost
		case 'adra.net.au':		// DreamHost
		case 'canc.org.au':		// DreamHost
		case 'ewills':	$root = '/home/ewills/evan/'; $inc = $root.'includes/'; $classes = $cls = $root.'classes/'; break; // DreamHost

		case 'apps.acu.edu.au':		// ACU
		case 'testapps.acu.edu.au':	// ACU
		case 'dev1.acu.edu.au':		// ACU
		case 'blogs.acu.edu.au':	// ACU
		case 'studentblogs.acu.edu.au':	// ACU
		case 'dev-blogs.acu.edu.au':	// ACU
		case 'evanw':	$root = '/home/evanw/';	$inc = $root.'includes/'; $classes = $cls = $root.'classes/'; break; // ACU

		case 'webapps.acu.edu.au':	   // ACU
		case 'panvpuwebapps01.acu.edu.au': // ACU
		case 'test-webapps.acu.edu.au':	   // ACU
		case 'panvtuwebapps01.acu.edu.au': // ACU
		case 'dev-webapps.acu.edu.au':	   // ACU
		case 'panvduwebapps01.acu.edu.au': // ACU
		case 'evwills':
			if( isset($_SERVER['HOSTNAME']) && $_SERVER['HOSTNAME'] = 'panvtuwebapps01.acu.edu.au' ) {
				$root = '/home/evwills/'; $inc = $root.'includes/'; $classes = $cls = $root.'classes/'; break; // ACU
			} else {
				$root = '/var/www/html/mini-apps/'; $inc = $root.'includes_ev/'; $classes = $cls = $root.'classes_ev/'; break; // ACU
			}
	};

	set_include_path( get_include_path().PATH_SEPARATOR.$inc.PATH_SEPARATOR.$cls.PATH_SEPARATOR.$pwd);

	if(file_exists($inc.'debug.inc.php'))
	{
		if(!file_exists($pwd.'debug.info') && is_writable($pwd) && file_exists($inc.'template.debug.info'))
		{ copy( $inc.'template.debug.info' , $pwd.'debug.info' ); };
		include($inc.'debug.inc.php');
	}
	else { function debug(){}; };

	class emergency_log { public function write( $msg , $level = 0 , $die = false ){ echo $msg; if( $die === true ) { exit; } } }
};

// END: debug include
// ==================================================================


function display_error($msg, $value = false , $line = false)
{
	echo "\n\n===========================================\n ERROR: $msg";
	if( $value !== false )
	{
		echo "\n $value";
	}
	if( $line !== false )
	{
		echo "\n $line";
	}
	echo "\n\n";
	exit;
}




if( !isset($_SERVER['argv']) || $_SERVER['argc'] < 2 || !is_file($_SERVER['argv'][1]) )
{
	display_error('You must supply a source file');
}

$source_path = realpath($_SERVER['argv'][1]);
$source = $original = file_get_contents($source_path);
$path = dirname(realpath($source_path)).'/';

/**
 * @function inc_not_found() writes an error message with details about which file couldn't be found and where to find it.
 *
 * @param {string} $pattern the include string that was couldn't be linked to a file
 * @param {string} $inc_path the file path derived from the pattern
 *
 * @use {string} $original the contents of the original source file that everything is pulled into
 * @use {string} $source_path the absolute path to the source file.
 */
$inc_not_found = function( $pattern , $inc_path ) use ($original , $source_path)
{
	$needle = preg_quote($pattern);
	$arr = preg_split( '`(\r\n|\n\r|\r|\n)`' , preg_replace( '`(?<='.$needle.').*$`s' , '' , $original) );
	echo "\n\n===========================================================\n ERROR partial not found:\n $pattern -> $inc_path\n At line ".count($arr)." in $source_path\n\n";
	exit;
};



if( isset($_SERVER['argv'][2]) )
{
	$output_path = realpath($_SERVER['argv'][2]);
	if( !file_exists($output_path) && !is_writable(dirname($output_path)) )
	{
			display_error('File not be found & cannot be created' , $output_path);
	}
}
elseif( is_writable(dirname($path)) )
{
	$output_path = preg_replace( '`^.*/(.*)$`', $path.'parse-\1' , $source_path );
}

$inc_finder = '`
\{[\[\{]				# opening delimiter
(
	(?:[a-z0-9 _-]+/)+	# [1] path
)?
(
	[a-z0-9 _-]+		# [2]file_name
)
(?:
	\|
	([^|]+)				# [3] find pattern
	\|
	([^\]\}]+)			# [4] replace pattern
)?
(
	[\]\}]				# [5] output type
)
\}`x'

function ASSEMBLER_CALLBACK($inc)
{
	$path = $inc[1];
	$file = $inc[2];
	$find = $inc[3];
	$replace = $inc[4];
	$type = $inc[5];
}

if( preg_match_all('`\{[\[\{]((?:.+/)+)?([^\]\}\|?)(?:\|([^\|]+)\|([^\]\}]*))?([\]\}])\}`',$source,$includes,PREG_SET_ORDER) )
{
	for( $a = 0 ; $a < count($includes) ; $a += 1 )
	{
		$inc_str = $includes[$a][0];
		$inc_path = 'partials/'.$includes[$a][1].'_'.$includes[$a][2].'.xml';
		if( !is_file($path.$inc_path) )
		{
			$inc_not_found( $inc_str , $inc_path );
		}

		$inc_content = preg_replace('`(?:^(?:[\t ]*[\r\n]+)+|(?:[\r\n]+[\t ]*)+$)`','',file_get_contents($path.$inc_path));
		$open = '<!--';
		$close = '-->';

		if( preg_match('`^\s*(?:/\*|<!--)`',$inc_content,$comment) )
		{
			if( $comment[0] === '/*' )
			{
				$open = '/*';
				$close = '*/';
			}
		}
		$open = "\n$open|| ";
		$close = "||$close\n";

	 	if( $includes[$a][3] === '}' )
		{
			$source = str_replace($includes[$a][0], "{$open}START: $inc_path $close".$inc_content."$open END:  $inc_path $close" , $source);
		}
		else
		{
			$source = str_replace( $includes[$a][0] , $inc_content , $source );
		}
	}

	file_put_contents( $output_path , $source );
	echo "\n\n =========================================\n All done!\n ".count($includes)." files merged in\n and save to $output_path\n";

}
else
{
	display_error( 'No partials were found in' , $path.$source_path );
}


