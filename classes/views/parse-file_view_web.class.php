<?php

namespace matrix_parsefile_preprocessor\view;


if( !defined('MATRIX_PARSEFILE_PREPROCESSOR__VIEW__WEB') )
{

define('MATRIX_PARSEFILE_PREPROCESSOR__VIEW__WEB',true);

require(__DIR__.'/parse-file_view_base.abstract.class.php');
require(__DIR__.'/../../includes/syntax_highlight.inc.php');

class web_view extends base_view
{
	const TITLE = 'Basic Squiz Matrix parse-file validator';

	private $post = [
		 '$new_parse_file' => ''
		,'old_parse_file' => ''
		,'compare' => false
	];

	public function set_post($key,$value)
	{
		if( is_string($key) && isset($this->post[$key]) && is_scalar($value) )
		{
			$this->post[$key] = $value;
		}
	}

	public function render_open( $file_name )
	{
		$this->output('
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<title>'.self::TITLE.'</title>
		<link href="style.css" rel="stylesheet" />
	</head>
	<body>
		<h1>'.self::TITLE.'</h1>

');
	}


	public function render_close()
	{
		$checked = '';
		$class = '';
		if( $this->post['compare'] === true )
		{
			$checked = ' checked="checked"';
			$class = ' class="compare"';
		}
		$get = '';
		$sep = '?';
		for( $a = 0 ; $a < count($this->types) ; $a += 1 )
		{
			$get .= $sep.'log[]='.$this->types[$a];
			$sep = '&';
		}

		$this->output('

		<form action="//'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/'.$get.'" method="post">
			<p>
				<input type="submit" value="submit" name="submit" />
				<label>
					<input type="checkbox" name="compare" id="compare" value="do"'.$checked.' />
					Check for missing design areas
				</label>
			</p>
			<ul'.$class.' id="textAreas">
				<li id="old_source">
					<label for="old_parse_file">Previously uploaded Parse-file (to be compared against for missing design areas)</label>
					<textarea name="old_parse_file" id="old_parse_file">'.htmlspecialchars($this->post['old_parse_file']).'</textarea>
				</li>
				<li id="source">
					<label for="parse_file">Parse-file to be checked</label>
					<textarea name="$new_parse_file" id="$new_parse_file">'.htmlspecialchars($this->post['$new_parse_file']).'</textarea>
				</li>
			</ul>
		</form>

		<p id="install"><a href="'.$_SERVER['REQUEST_URI'].'" rel="sidebar" title="'.self::TITLE.'">Install as Sidebar</a></p>
		<script type="text/javascript">
document.addEventListener(\'DOMContentLoaded\', function(event) {
	\'use strict\');

	var compare = document.getElementById(\'compare\'),
		ul = document.getElementById(\'textAreas\'),
		old = document.getElementById(\'old_source\');

	compare.onchange = function(e) {
		if (this.checked) {
			ul.className = \'compare\';
			old.className = \'\'
		}
		else
		{
			ul.className = \'\';
			old.className = \'hide\'
		}
	}

	old.className = \'hide\';
});
		</script>
	</body>
</html>';
	}


	public function render_report_wrap_open()
	{
		$this->output('

		<section class="report">
			<header>
				<h1>Report</h1>
			</header>');
	}
	public function render_item_wrap_open()
	{
		$this->output('
			<ol>');
	}



	public function render_item( \matrix_parsefile_preprocessor\log_item $log_item )
	{
		parent::render_item($log_item);
		if( in_array($log_item->get_type(),$this->types) )
		{
			$this->output('
				<li class="log '.$log_item->get_type().'">
					<p>
						<strong>'.ucfirst($log_item->get_type()).':</strong>
						'.$log_item->get_prop('msg').'
					</p>
');
			if( $c = $log_item->get_extra_details_count() )
			{
				$tmp = $log_item->get_extra_details();
				$this->output('
					<ul>');
				for( $a = 0 ; $a < $c ; $a += 1 )
				{
					$this->output('
						<li><code>'.$tmp[$a].'</code></li>');
				}
				$this->output('
					</ul>');
			}

			$output = '';
			if( $log_item->get_prop('sample') !== '' )
			{
				$output .= '<dt>Sample:</dt>
							<dd>'.\syntax_highlight($log_item->get_prop('sample'),'code').'</dd>';
			}
			if( $log_item->get_prop('line') > 0 )
			{
				$output .= '
						<dt>Line:</dt>
							<dd>'.$log_item->get_prop('line').'</dd>';
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
			$this->output($output.'
				</li>
');
		}
	}



	public function render_item_wrap_close()
	{
		$this->output('			</ol>
');
	}



	public function render_report_wrap_close()
	{
		$this->output('		</section>
');
	}


	public function render_report( \matrix_parsefile_preprocessor\validator $validator , $file = false )
	{

		$areas = $validator->get_areas_count();
		$non_printed_areas = $validator->get_non_printed_areas_count();

		$this->output('
				<article>
					<header>
						<h1>Overview</h1>
					</header>
');

		if( $this->partials > 0 )
		{
			$this->output("\n\t\t\t\t<p><strong>{$this->partials}</strong> files processed");
			if( $this->keywords )
			{
				$this->output('<br />');
			}
			else
			{
				$this->output('</p>');
			}
		}
		if( $this->keywords > 0 )
		{
			$this->output("\n\t\t\t\t");
			if( $this->partials === 0 )
			{
				$this->output('<p>');
			}
			$this->output("<strong>{$this->keywords}</strong> keywords found</p>");
		}
		$this->output('
					<p>
						<strong>'.$areas.'</strong> design areas found<br />
						<strong>'.$non_printed_areas.'</strong> (or ' . round($non_printed_areas/$areas,4) * 100 .'%) design areas were non-print<br />
						<strong>'.$validator->get_prints_count().'</strong> print tags found
					</p>
					<p>There were:</p>
					<ul>
						<li>'.$this->errors.' errors</li>
						<li>'.$this->warnings.' warnings</li>
						<li>'.$this->notices.' notices</li>
					</ul>
				</article>
');
	}
}



}