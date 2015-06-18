<?php

class matrix_parsefile_preprocessor__config
{
	private $config_vars = array();
	private $path = '';
	private $output_dir = '';
	private $partials_dir = '';
	private $show_error_extended = false;
	private $on_unprinted = 'show';
	private $white_space = 'normal';
	private $strip_comments = true;
	private $unprinted_exceptions = array('__global__');

	private static $me = null;

	private function __construct( $file , $runtime )
	{
		$config = false;
		$conf_type = 'info';
		$conf_types = array('info','json');



		$file = trim($file);

		if( $file == '' || !is_file($file) || !is_dir($file) )
		{
			// throw error "$file must be either the pre-parsefile (.xml) or a config file (.info or .json) or the path to a directory in which a config file can be found";
		}

		if( preg_match( '`^.*?/config.*?\.(info|json)$`i' , $file , $matches ) ) {
			$config = $this->get_config( $file , $matches[1] );
		}
		if( $config === false && preg_match( '`^(.*?/)(.*?)\.xml$`i' , $file , $matches) ) {
			$config = $this->get_config( $matches[1].'config_'.$matches[2].'.' );
		}
		if( $config === false )
		{
			$config = $this->get_config( dirname($file).'/config.' );
		}
		if( is_array($runtime) && !empty($runtime) )
		{
			foreach( $runtime as $key => $value )
			{
				$this->try_to_set($key,$value);
			}
		}
	}

	public static function get( $file = '' , $runtime = array() )
	{
		if( self::$me === null)
		{
			self::$me = new matrix_parsefile_preprocessor__config( $file , $runtime );
		}
		return self::$me;
	}

	public function has_var($key)
	{
		if( property_exists($this,$key) || isset($this->config_vars[$key]) )
		{
			return true;
		}
		return false;
	}

	public function get_var($key)
	{
		if( property_exists($this,$key) )
		{
			return $this->$key;
		}
		if( isset($this->config_vars[$key]) )
		{
			return $this->config_vars[$key];
		}
	}

	public function check_type($var_type,$key) {
		if( is_string($var_type) && isset($this->config[$key]) ) {
			$var_type = strtolower($var_type);
			switch($var_type) {
				case 'boolean':
				case 'integer':
				case 'float':
				case 'string':
				case 'array':
				case 'object':
				case 'null':
					if( gettype($this->config[$key]) == $var_type ) {
						return true;
					}
					break;
				case 'dir':
					if( is_dir($this->config[$key]) ) {
						return true;
					}
					break;
				case 'file':
					if( is_file($this->config[$key]) ) {
						return true;
					}
					break;
			}
		}
		return false;
	}

	private function get_config($file, $type = false)
	{
		$types = array('info','json');
		$b = 0;
		$c = 2;

		if( $type == 'json')
		{
			$b = 1;
		}
		elseif( $type == 'info' )
		{
			$c = 1;
		}

		for( $a = $b ; $a < $c ; $a += 1 )
		{
			if( is_file($file.$types[$a]) )
			{
				$this->path = realpath(dirname($file)).'/';
				$conf_build_method = "build_config_{$types[$a]}";
				$this->$conf_build_method( file_get_contents( realpath($file.$types[$a]) ) );
				return true;
			}
		}
		return false;
	}

	private function build_config_info($config)
	{
		$config = explode("\n",preg_replace('`[\t ]*(?:#|;|//).*$`m','',$config));
		$b = count($config);
		for( $a = 0 ; $a < $b ; $a += 1 )
		{
			if( $config[$a] != ''  )
			{
				$config[$a] = preg_split('`\s*(\:|=)\s*`',trim($config[$a]),2);

				$this->try_to_set( trim($config[$a][0]) , trim($config[$a][1]) );
			}
		}
	}


	private function try_to_set($key,$value)
	{
		if( !$this->set_dir($key,$value) &&
			!$this->set_str($key,$value) &&
			!$this->set_bool($key,$value) &&
			!$this->set_array($key,$value)
		)
		{
			if( !property_exists($this,$key) )
			{
				$this->config_vars[$key] = $value;
			}
			else
			{
				// throw Config variable $key is not valid.
			}
		}
	}

	private function build_config_json($config)
	{
		$config = json_decode( $config , true );
	}

	private function set_str($prop,$input)
	{
		$options = array(
			'on_unprinted' => array('show','fail','hide'),
			'white_space' => array('normal','compact','compress')
		);
		if( isset($options[$prop]) && is_string($input) )
		{
			$input = strtolower($input);
			if( in_array($input,$options[$prop]))
			{
				$this->$prop = $this->config_vars[$prop] = $input;
				return true;
			}
		}
		return false;
	}

	private function set_bool($prop,$input)
	{
		$props = array( 'show_error_extended' , 'strip_comments');
		if( is_string($prop) && in_array($prop,$props) ) {
			$input = strtolower($input);
			if( $input == 'true' || $input == 1 )
			{
				$input = true;
			}
			elseif( $input == 'false' || $input == 0 )
			{
				$input = false;
			}
			else
			{
				return false;
			}
			$this->$prop = $this->config_vars[$prop] = $input;
		}
	}

	private function set_dir($prop,$input)
	{
		$props = array( 'output' , 'partials' );
		if( is_string($prop) && in_array($prop,$props) )
		{
			if( !is_dir($input) )
			{
				$prop_ = $prop.'_dir';
				if( is_dir($this->path.$input) )
				{
					$input = $this->path.$input;
				}
				else
				{
					// throw $output dir does not exist
					return false;
				}
			}
			$this->$prop_ = $this->config_vars[$prop] = realpath($input).'/';
			return true;
		}
		return false;
	}

	private function set_array($prop,$input)
	{
		$props = array( 'unprinted_exceptions' );
		if( is_string($prop) && in_array($prop,$props) )
		{
			if( !is_array($input) )
			{
				if( is_string($input) )
				{
					if(preg_match_all(
						'`(?<=^|,)\s*("|\')?(?(1)(.*?)(?<!\\\\)\1|([^,]+))(?=\s*(?:,|$))`',
						$input,
						$matches,
						PREG_SET_ORDER
					))
					{
						$tmp = array();
						for($a = 0 ; $a < count($matches) ; $a += 1 )
						{
							$str = trim($mathces[$a][2].$mathces[$a][3]);
							if( $str != '')
							{
								$str = str_replace("\\{$matches[$a][1]}",$matches[$a][1],$str);
							}
							if( is_numeric($str) && substr($str,0,1) != '0' )
							{
								$str = ($str/1);
							}
							$tmp[] = $str;
						}
						$input = $tmp;
					}
					else
					{
						$input = array($input);
					}
				}
				else
				{
					// throw $output dir does not exist
					return false;
				}
			}
			if( !empty($input) )
			{
				for( $a = 0 ; $a < count($input) ; $a += 1 )
				{
					if( in_array($input[$a],$this->$prop) )
					{
						array_push($this->$prop,$input[$a]);
					}
					if( !in_array($input[$a],$this->config_vars[$prop]) )
					{
						$this->config_vars[$prop][] = $input[$a];
					}
				}
				return true;
			}
		}
		return false;
	}
}

