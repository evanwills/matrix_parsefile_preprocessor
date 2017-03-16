<?php

namespace matrix_parsefile_preprocessor\view;

require_once(__DIR__.'/parse-file_view_base.abstract.class.php');

class web_view extends base_view
{
	private $title = 'Basic Squiz Matrix parse-file checker';

	public function render_open()
	{
		echo '
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<title>'.$this->title.'</title>
		<link href="style.css" rel="stylesheet" />
	</head>
	<body>
		<h1>'.$this->title.'</h1>

';
	}


	public function render_close()
	{
		if( func_num_args() > 0 )
		{
			$input = func_get_arg[0];
		}
		else
		{
			$input = '';
		}
		echo '

		<form action="//'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/" method="post">
			<input type="submit" value="submit" name="submit" />
			<ul>
				<li id="source">
					<label for="input">Parse-file to be checked</label>
					<textarea name="input" id="input">'.htmlspecialchars($input).'</textarea>
				</li>
			</ul>
		</form>

		<p id="install"><a href="'.$_SERVER['REQUEST_URI'].'" rel="sidebar" title="'.$this->title.'">Install as Sidebar</a></p>
	</body>
</html>';
	}


	public function render_report_wrap_open()
	{
		echo '

		<section class="error-report">
			<header>
				<h1>Report</h1>
			</header>';
	}
	public function render_item_wrap_open()
	{
		echo '
			<ol>';
	}



	public function render_item( \matrix_parsefile_preprocessor\log_item $log_item )
	{
		echo '
				<li class="'.$log_item->get_type().'">
					<p>
						<strong>'.ucfirst($log_item->get_type()).':</strong>
						'.$log_item->get_prop('msg').'
					</p>
';

		$output = '';
		if( $log_item->get_prop('sample') !== '' )
		{
			$output .= '<dt>Sample:</dt>
							<dd><code><pre>'.htmlspecialchars($log_item->get_prop('sample')).'</pre></code></dd>';
		}
		if( $log_item->get_prop('line') > 0 )
		{
			$output .= '
						<dt>Line:</dt>
							<dd>'.$log_item->get_prop('file').'</dd>';
		}
		$file = $log_item->get_prop('file');
		if( $file !== '' && $file !== 'web' )
		{
			$output .= '
						<dt>file:/dt>
							<dd>'.$log_item->get_prop('file').'</dd>';
		}

		if( $output !== '' )
		{
			$output = '
					<dl>'.$output.'
					</dl>
';
		}
		echo $output.'
				</li>
';
	}



	public function render_item_wrap_close()
	{
		echo '			</ol>
';
	}



	public function render_report_wrap_close()
	{
		echo '		</section>
';
	}


	public function render_report()
	{
		echo '
				<article>
					<header>
						<h1>Overview</h1>
					</header>

					<p>'.$this->partials.' files processed</p>
					<p>'.$this->keywords.' keywords found</p>
					<p>There were:</p>
					<ul>
						<li>'.$this->errors.' errors</li>
						<li>'.$this->warnings.' warnings</li>
						<li>'.$this->notices.' notices</li>
					</ul>
				</article>
';
	}
}