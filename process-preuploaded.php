<?php


echo '
<!DOCTYPE html>
<html lang="en">';

$ti = time();

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
		case '192.168.18.129':	// work laptop (debian)
		case 'antechinus':	// work laptop (debian)
		case 'localhost':	// home laptop
		case 'evan':		// home laptop
		case 'wombat':	$root = '/var/www/';	$inc = $root.'includes/'; $classes = $cls = $root.'classes/'; break; // home laptop

		case '192.168.1.128':	$root = '/var/www/html';	$inc = $root.'includes/'; $classes = $cls = $root.'classes/'; break; // home laptop

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

function css_safe($input, $prefix = 'a')
{
	if( !is_string($input) )
       	{
		die('css_safe() expects first parameter $input to be a string. '.gettype($input).' given.');
	}
	if( !is_string($prefix) )
       	{
		die('css_safe() expects second parameter $prefix to be a string. '.gettype($prefix).' given.');
	}
	$input = preg_replace('`[^a-z0-9_-]+`i' , '' , $input);
	if( is_numeric(substr($input,0,1)) ) {
		$input = $prefix.$input;
	}
	return $input;
}

function make_select($get, $label, $options, $default) 
{
	$tmp = $default;
	if( isset($_GET[$get]) )
	{
		$tmp = css_safe($_GET[$get]);
	}
	$output = '
			<p>
				<label for="'.$get.'">
					'.$label.'
				<label>
				<select name="'.$get.'" id="'.$get.'">';
	foreach( $options as $key => $value )
	{
		$checked = '';
		if( $tmp === $key )
		{
			$checked = ' selected="selected"';
			$_SERVER['argv'][] = $key;
		}
		$output .= '
					<option value='.$key.$checked.'>'.$value.'</option>';

	}
	$output .= '
				</select>
			</p>';
	return $output;
}

//debug('server');
$_SERVER['argv'][0]= 'parse-file_process.cli.php';

$tmp = scandir('parse-files/');
$c = 0;
$files_list = '';

for( $a = 0 ; $a < count($tmp) ; $a += 1 )
{
	if( substr(strtolower($tmp[$a]), -4, 4) === '.xml' )
	{
		$checked = '';
		$name = css_safe($tmp[$a]);
		if( isset($_GET[$name]) && $_GET[$name] === 'true' )
		{
			$_SERVER['argv'][] = 'parse-files/'.$tmp[$a];
			$c += 1;
			$checked = ' checked="checked"';
		}
		$files_list .= '
				<li>
					<label>
						<input type="checkbox" name="'.$name.'" value="true"'.$checked.' />
						'.$tmp[$a].'
					</label>
				</li>';
	}
}
//$tmp = array('mode','report','comments','compare')
//$_SERVER['argv'][1] = 'parse-files/*.xml';

$options = array(
	 'all' => 'All - show messages (errors, warnings &amp; notices)'
	,'brief' => 'Brief - errors and warnings (omit warnings)'
	,'error' => 'errors only'
	,'warning' => 'warnings only'
	,'notice' => 'notices only'
);
$mode_options = make_select('mode', 'Reporting mode', $options,'brief');

$options = array(
	 'compact' => 'compact'
	,'compress' => 'compress'
	,'normal' => 'normal'
);
$whiteSpace_options = make_select('whit-space', 'White space', $options, 'normal');

$options = array(
	 'compare' => 'Compare design areas in previous compiled version of the parse file to the newly compiled version'
	,'ignore' => 'Ignore changes between compiled parse file version'
);
$compare_options = make_select('compare', 'Compare parse file versions', $options, 'ignore');

$options = array(
	 'keepcomments' => 'Keep existing HTML/CSS comments'
	 ,'stripcomments' => 'Delete existing HTML/CSS comments'
);
$userComments_options = make_select('user-comments', 'Your HTML/CSS comments', $options, 'keepcomments');


$options = array(
	 'wrap' => 'Wrap partials in HTML/CSS comments (useful for finding bugs)'
	,'nowrap' => 'Don\'t wrap partials in comments'
);
$parserComments_options = make_select('prepocessor-comments', 'Prepocessor HTML/CSS comments', $options, 'nowrap');

//debug('server');
$_SERVER['argc'] = count($_SERVER['argv']);

ob_clean();
ob_start();

require_once('parse-file_process.cli.php');

$output = ob_get_clean();
ob_end_clean();
echo'
	<head>
		<meta charset="utf-8" />
		<title>Matrix parse-file prepocessor</title>
	</head>
	<body>
		<h1>Matrix parse-file prepocessor</h1>

		<p>Processed <strong>'.$c.'</strong> matrix XML parse files</p>
		<form action="'.$_SERVER['PHP_SELF'].'" method="GET">
			<fieldset>
				<legend>Files to be processed</legend>
		
				<ul>'.$files_list.'
				</ul>
			</fieldset>

			'.$mode_options
			 .$whiteSpace_options
			 .$compare_options
			 .$userComments_options
			 .$parserComments_options.'
			<input type="submit" name="submit" value="submit" />

		</form>

		<h2>Processing already uploaded files</h2>

		<p><strong>Started at:</strong>: '.date('Y-m-d H:i:s',$ti).'</p>
		<p><strong>Completed at:</strong> '.date('Y-m-d H:i:s').'</p>
		<p><strong>Total time:</strong> '.(time() - $ti).' seconds.</p>


		<h2>Process log:</h2>




		<pre>

'.htmlspecialchars($output).'


		</pre>

	</body>
</html>
';
