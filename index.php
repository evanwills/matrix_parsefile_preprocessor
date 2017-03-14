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
$title = 'Basic Squiz Matrix parse-file checker';

if( isset($_POST['input']) && trim($_POST['input']) !== '' )
{
	require_once('includes/regex_error.inc.php');
	require_once('classes/matrix-parsefile-preprocessor.class.php');
	require_once('classes/matrix-parsefile-preprocessor_basic-test.class.php');
	require_once('classes/matrix-parsefile-preprocessor_config.class.php');


	$fail_on_unprinted = true;
	$unprinted_exceptions = array();
	$input = $_POST['input'];

	$checker = matrix_parsefile_preprocessor__basic_test::get($unprinted_exceptions);

	$checker->test_parsefile( $input , 'web' );

	$errors = $checker->get_errors();

	if( count($errors) > 0 )
	{
		$report = '
		<section class="error-report">
			<header>
				<h1>Error report</h1>
			</header>
			<ul>';
		foreach( $errors as $tag )
		{
			$report .= '
				<li>
					<dl>
						<dt>Line:</dt>
							<dd>'.$tag['line'].'</dd>
						<dt>ID:</dt>
							<dd>'.$tag['id'].'</dd>
						<dt>Tag:</dt>
							<dd>'.htmlspecialchars($tag['xml']).'</dd>
					</dl>
					<p>'.$tag['msg'].'</p>
				</li>
';
		}
		$report .= '			</ul>
		</section>
';
	}
	else
	{
		$report = '
		<p class="no-errors">Yay!!! No errors found</p>';
	}
}


?>

<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<title><?php echo $title; ?></title>
		<link href="style.css" rel="stylesheet" />
	</head>
	<body>
		<h1><?php echo $title; ?></h1>
<?php echo $report; ?>
		<form action="<?php echo $root_url; ?>" method="post">
			<input type="submit" value="submit" name="submit" />
			<ul>
				<li id="source">
					<label for="input">Parse-file to be checked</label>
					<textarea name="input" id="input"><?php echo htmlspecialchars($input); ?></textarea>
				</li>
			</ul>
		</form>

		<p id="install"><a href="<?php echo $_SERVER['REQUEST_URI']; ?>" rel="sidebar" title="Basic Matrix parse-file checker">Install as Sidebar</a></p>
	</body>
</html>
