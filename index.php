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

$input = '';
$report = '';
$root_url = '//'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/';


require_once($pwd.'classes/parse-file_validator.class.php');
require_once($pwd.'classes/views/parse-file_view_web.class.php');

if( isset($_POST['input']) && trim($_POST['input']) !== '' )
{
	require_once('classes/parse-file_validator.class.php');


	$fail_on_unprinted = true;
	$unprinted_exceptions = array();
	$input = $_POST['input'];

	$validator = new matrix_parsefile_preprocessor\validator();

	$validator->parse( $input , 'web' );
	$validator->log_unprinted();

	debug('blah;');


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


	$view = new matrix_parsefile_preprocessor\view\web_view($builder->get_processed_partials_count() , $builder->get_keyword_count() , $mode );

	$view->render_open();
	$logs = $validator->get_logs();

	$view->render_report_wrap_open();
	$view->render_report();
	$view->render_item_wrap_open();
	while( $log_item = $log->get_next_item() )
	{
		$view->render_item($log_item);
	}
	$view->render_item_wrap_close();
	$view->render_report_wrap_close();
	$view->render_close();
}
else
{

	$view = new matrix_parsefile_preprocessor\view\web_view(0 , 0 , 'all' );
	$view->render_open();
	$view->render_close();
}