<?php

/**
 * syntax_highlight() takes HTML input and highlights all aspects of
 * the code
 *
 * @param input string HTML code
 * @param wrapper string what to wrap the output code in.
 *	options: css -  wrap the output in <code class="hl"></code>
 * 			tags and prepend the CSS in Style tags
 *		 code - wrap the output in <code class="hl"></code>
 * 			tags only
 * 		[default] - don't wrap the output in anything
 * @param $anything_else
 *
 * @return string HTML code with the markup highlighted.
 */
function syntax_highlight( $input , $wrapper = '' , $anthing_else = false )
{
	$style = '<style type="text/css">'.SYNTAX_HIGHLIGHT_CSS.'</style>';


	$tag_match = '/<(\/?(?:[a-z_-]+))(.*?)(\/?)>/isx';

	if($anthing_else === true && !defined('SYNTAX_HIGHLIGHT_ANTHING_ELSE'))
	{
		define('SYNTAX_HIGHLIGHT_ANTHING_ELSE',TRUE);
	};
	$throughput = preg_replace_callback($tag_match,'SYNTAX_HIGHLIGHT_CALLBACK_EL',matrix_url($input));
	$output = preg_replace(
			 array(
				 '/<(!(?:\[endif\]--|--\[[a-z0-9 ]+\]|--.*?--))>/is'
				,'/<!\[CDATA\[(.*?)\]\]>/is'
			 )
			,array(
				 '<span class="co">&lt;\1&gt;</span>'
				,'<span class="cd">&lt;&#33;&#91;CDATA&#91;</span><span class="cdc">\1</span><span class="cd">&#93;&#93;&gt;</span>'
			 )
			,$throughput
		  );

	switch($wrapper)
	{
		case 'css':
			$output = $style.'<pre><code class="hl">'.$output.'</code></pre>';
			break;
		case 'code':
			$output = '<code class="hl">'.$output.'</code>';
			break;
		case 'pre':
			$output = '<pre class="hl">'.$output.'</pre>';
			break;
	};
	return $output;
};

function SYNTAX_HIGHLIGHT_CALLBACK_EL($matches)
{
	$attr_match = '/([a-z_-]+)=([\'"])([^\2]*?)(\2)/is';

	$open = '<span class="el">&lt;'.$matches[1].'</span>';
	if(isset($matches[3]))
	{
		$attributes = preg_replace($attr_match,'<span class="at">\1=\2</span><span class="va">\3</span><span class="at">\2</span>',$matches[2]);
		if(defined('SYNTAX_HIGHLIGHT_ANYTHING_ELSE'))
		{
			$attributes = str_ireplace( 'anything else' , '<span class="ae">anything else</span>' , $attributes );
		}
		$close = $matches[3];
	}
	else
	{
		$attributes = '';
		$close = $matches[2];
	};
	return $open.$attributes.'<span class="el">'.$close.'&gt;</span>';
};

function matrix_url($input)
{
	return preg_replace(
		 '/((?:href|src)=[\'"]?\.\/)\?(a=)/i'
		,'\1&#63;\2'
		,$input);
};

define('SYNTAX_HIGHLIGHT_CSS','
/* ----------------------------------------------
   START: Syntax Highlight CSS */

pre { border:0.1em solid #666; padding:2em; margin:2em; background-color:#eee; color: }
code.hl,pre.hl { font-size:100%; padding:0.7em 0%; }
.hl .el { color:#808; }
.hl .at { color:#088; }
.hl .va { color:#aa0; }
.hl .imp { font-weight:bold; }
.hl .co { color:#888;  background-color:#f5f5f5; }
.hl .co .el { color:#e9e; }
.hl .co .at { color:#6cc; }
.hl .co .va { color:#dd5; }
.hl .cd { color:#5dd; }
.hl .cdc { color:#55d; }

/* END: Syntax Highlight CSS
---------------------------------------------- */
');
