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

require_once('classes/parse-file_compiler.class.php');



if( !isset($_SERVER['argv'][1]) || !is_file($_SERVER['argv'][1]) || !is_readable($_SERVER['argv'][1])) {
	echo "\n\nYou must specify a file to be processed\n\n";
	exit;
} elseif( substr(strtolower($_SERVER['argv'][1]), -4 ) != '.xml' ) {
	echo "\n\nI can only parse XML files.\n\n";
	exit;
}




function render_to_cli( $log_item , $b )
{
	$b += 1;
	$tmp = $log_item->get_prop();
	echo "\n\n ------------------------- ".ucfirst($tmp['type'])." (".$b.") -------------------------\n\n  ";
	echo $tmp['msg']."\n\n";
	$sep = false;
	if( $tmp['sample'] !== '' )
	{
		echo "  sample: \"{$tmp['sample']}\"\n\n";
	}
	if( $tmp['line'] > 0 )
	{
		echo "  line: {$tmp['line']}\n";
	}
	if( $tmp['file'] !== '' )
	{
		echo "  file: {$tmp['file']}\n";
		$sep = true;
	}
	echo "\n";
}


$file = realpath($_SERVER['argv']['1']);


$builder = new matrix_parsefile_preprocessor\compiler($file);
$builder->parse($file);

if( isset($_SERVER['argv'][2]) && $_SERVER['argv'][2] === 'brief' )
{
	$errors = $builder->get_logs('error','warning');
}
else
{
	$errors = $builder->get_logs();
}
$e = 0;
$w = 0;
$n = 0;

for( $a = 0 ; $a < count($errors) ; $a += 1 )
{
	switch($errors[$a]->get_type())
	{
		case 'error': $e += 1; break;
		case 'warning': $w += 1; break;
		case 'notice': $n += 1; break;
	}
	render_to_cli( $errors[$a] , $a );
}

echo "\n\n==============================================\n All done!\n\n   ".$builder->get_processed_partials_count()." files processed.\n   ".$builder->get_keyword_count()." keywords found\n\n   There were:\n\t$e errors\n\t$w warnings\n\t$n notices\n\n";