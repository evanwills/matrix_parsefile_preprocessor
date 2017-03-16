<?php

// ==================================================================
// START: debug include

if(!function_exists('debug'))
{
	if(isset($_SERVER['HTTP_HOST'])){ $path = $_SERVER['HTTP_HOST']; $pwd = realpath($_SERVER['SCRIPT_FILENAME']).'/'; }
	else { $path = $_SERVER['USER']; $pwd = realpath($_SERVER['PWD']).'/'; };
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

debug('server',$pwd);
require_once('classes/parse-file_compiler.class.php');
require_once('classes/views/parse-file_view_cli.class.php');


if( !isset($_SERVER['argv'][1]) || !is_file($_SERVER['argv'][1]) || !is_readable($_SERVER['argv'][1])) {
	echo "\n\nYou must specify a file to be processed\n\n";
	exit;
} elseif( substr(strtolower($_SERVER['argv'][1]), -4 ) != '.xml' ) {
	echo "\n\nI can only parse XML files.\n\n";
	exit;
}




$file = realpath($_SERVER['argv']['1']);


$builder = new matrix_parsefile_preprocessor\compiler($file);
$builder->parse($file);

$mode = 'all';
if( isset($_SERVER['argv'][2]) && $_SERVER['argv'][2] === 'brief' )
{
	switch($_SERVER['argv'][2])
	{
		case 'brief':
			$mode = ['error','warning'];
			break;
		case 'error':
		case 'warning':
		case 'notice':
			$mode = $_SERVER['argv'][2];
			break;
		case 'q':
		case 'quiet':
		case 's':
		case 'silent':
			exit;
			break;
		default:
			$mode = 'all';

	}
}


$view = new matrix_parsefile_preprocessor\view\cli_view( $builder->get_processed_partials_count() , $builder->get_keyword_count() , $mode );

$logs = $builder->get_logs();

while( $log_item = $logs->get_next_item() )
{
	$view->render_item($log_item);
}

$view->render_report();