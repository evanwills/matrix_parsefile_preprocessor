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
		case 'antechinas':		// home laptop
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

require_once('includes/regex_error.inc.php');
require_once('classes/matrix-parsefile-preprocessor.class.php');
require_once('classes/matrix-parsefile-preprocessor_assembler.class.php');
require_once('classes/matrix-parsefile-preprocessor_basic-test.class.php');
require_once('classes/matrix-parsefile-preprocessor_config.class.php');


if( !isset($_SERVER['argv'][1]) || !is_file($_SERVER['argv'][1]) || !is_readable($_SERVER['argv'][1])) {
	echo "\n\nYou must specify a file to be processed\n\n";
	exit;
} elseif( substr(strtolower($_SERVER['argv'][1]), -4 ) != '.xml' ) {
	echo "\n\nI can only parse XML files.\n\n";
	exit;
}

$fail_on_unprinted = true;
$unprinted_exceptions = array();
if( isset($_SERVER['argv'][2]) )
{

	if( $_SERVER['argv'][2] == 'true' )
	{
		$fail_on_unprinted = true;
	}
	elseif( is_file($_SERVER['argv'][2]))
	{
		$unprinted_exceptions = explode("\n",file_get_contents($_SERVER['argv'][2]));
	}
	else
	{
		$unprinted_exceptions = explode(',',$_SERVER['argv'][2]);
	}
	if( !empty($unprinted_exceptions) )
	{
		for( $a = 0 ; $a < count($unprinted_exceptions) ; $a += 1 )
		{
			if( $unprinted_exceptions[$a] == '' )
			{
				unset($unprinted_exceptions[$a]);
			}
		}
		sort($unprinted_exceptions);
	}
}

$file = realpath($_SERVER['argv'][1]);
$path = dirname($file).'/';
$file = str_replace($path,'',$file);

$sample = file_get_contents( 'parse-files/partials/body/content/right_col/_right-col_promo-1.xml');
$te = new matrix_parsefile_preprocessor__assembler($path,$file,$fail_on_unprinted,$unprinted_exceptions);

$te->set_partials_dir($path.'partials/');

$te->parse();
