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

require_once('classes/parse-file_compiler.class.php');
require_once('classes/views/parse-file_view_cli.class.php');
require_once($pwd.'includes/get_all_xml_files.inc.php');


if( !isset($_SERVER['argv'][1]) ) {
	echo "\n\nYou must specify a file to be processed\n\n";
	exit;
}

$file = realpath($_SERVER['argv']['1']);

$files = [];
$compare_files = [];
$compare = false;
$reporting = 'all';

$get_compare_files = false;

for( $a = 2 ; $a < $_SERVER['argc'] ; $a += 1 )
{
	switch(strtolower($_SERVER['argv'][$a]))
	{
		case 'all':
		case 'error':
		case 'notice':
		case 'warning':
			$mode = $_SERVER['argv'][2];
			$get_compare_files = true;
			break;
		case 'brief':
			$mode = ['error','warning'];
			$get_compare_files = true;
			break;
		case 'compare':
			$compare = true;
			$get_compare_files = true;
			break;
		case 'q':
		case 'quiet':
		case 's':
		case 'silent':
			$mode = 'silent';
			$get_compare_files = true;
			break;
		default:
			if( $get_compare_files === false )
			{
				$tmp_var = &$files;
			}
			else
			{
				$tmp_var = &$compare_files;
			}
			$tmp = matrix_parsefile_preprocessor\get_all_xml_files($_SERVER['argv'][$a]);
			if( $tmp !== false )
			{
				for( $a = 0 ; $a < count($tmp) ; $a += 1 )
				{
					if( !in_array( $tmp[$a] , $tmp_var ) )
					{
						$tmp_var[] = $tmp[$a];
					}
				}
			}
			unset($tmp_var);
	}
}

$config = new matrix_parsefile_preprocessor\config($pwd,$files[0]);
$c_new = count($files);
$c_old = count($compare_files);
if( $compare === true )
{
	if( $c_new === $c_old || $c_old === 0 )
	{
		$output_dir = $config->get_var('output_dir');
		for( $a = 0 ; $a < $c_new ; $a += 1 )
		{
			$tmp = pathinfo($files[$a]);
			if( !isset($compare_files[$a]) || $compare_files[$a] === true )
			{
				$tmp_compare = $output_dir.$tmp['basename'];
				if( is_file($tmp_compare) && is_readable($tmp_compare) )
				{
					$compare_files[$a] = $tmp_compare;
				}
				else
				{
					$compare_files[$a] = false;
				}
			}
			elseif( !is_file($compare_files[$a]) )
			{
				if( is_file($output_dir.$compare_files[$a]) )
				{
					$compare_files[$a] = $output_dir.$compare_files[$a];
				}
				else
				{
					$compare_files[$a] = false;
				}
			}
		}
	}
	else
	{
		echo "\n\n You've specified some comparison files but not the same number as new files.\n Rather than do the wrong thing, I'm stopping here.\n Better luck next time!\nFiles to compile:\n\t".implode("\n\t",$files)."\n Compare files:\n\t".implode("\n\t",$compare_files)."\n\n";
		exit;
	}
}
else
{
	$compare_files = array_fill( 0 , $c_new , false );
}


for( $a = 0 ; $a < $c_new ; $a += 1 )
{
	$config = new matrix_parsefile_preprocessor\config($pwd,$files[$a]);
	$logger = new matrix_parsefile_preprocessor\logger();
	$partials = new matrix_parsefile_preprocessor\nested_partials( $logger , $files[$a] );
	$validator = new matrix_parsefile_preprocessor\validator($config,$logger,$partials);


	if($compare_files[$a] !== false)
	{
		$validator->process_old_parse_file($compare_files[$a]);
	}

	$builder = new matrix_parsefile_preprocessor\compiler($config,$logger,$partials,$validator);
	$builder->parse($files[$a]);
	$builder->log_unprinted();
	if($compare_files[$a] !== false)
	{
		$validator->check_deleted_areas();
	}

	$mode = 'all';


	$view = new matrix_parsefile_preprocessor\view\cli_view( $builder->get_processed_partials_count() , $builder->get_keyword_count() , $mode );

	$logs = $builder->get_logs();

	while( $log_item = $logs->get_next_item() )
	{
		$view->render_item($log_item);
	}

	$validator = $builder->get_validator();
	$view->render_report( $validator, $files[$a] );
}