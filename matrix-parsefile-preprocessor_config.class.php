<?php

class matrix_parsefile_preprocessor__config
{
	private $config_vars = array();
	private $path = '';
	private $output_dir = '';
	private $partials_dir = '';
	private $show_error_extended = false;
	private $fail_on_unprinted = false;

	private static $me = null;

	private function __construct( $file )
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
			$config = $this->get_config($file,$matches[1]);
		}
		if( $config === false && preg_match( '`^(.*?/)(.*?)\.xml$`i' , $file , $matches) ) {
			$config = $this->get_config($matches[1].'config_'.$matches[2].'.');
		}
		if( $config === false )
		{
			$config = $this->get_config(dirname($file).'/config.');
		}
		if( $config === false )
		{
			// throw error "Could not find a valid config file."
		}

		$tmp = array('output','partials');
		for( $a = 0 ; $a < 2 ; $a += 1 )
		{
			if( isset($this->config_vars[$tmp[$a]]) )
			{
				if( !is_dir($this->config_vars[$tmp[$a]]) )
				{
					$prop = $tmp[$a].'_dir';
					if( is_dir($this->path.$this->config_vars[$tmp[$a]]) )
					{
						$this->$prop = $this->config_vars[$tmp[$a]] = realpath($this->path.$this->config_vars[$tmp[$a]]).'/';
					}
					else
					{
						// throw $output dir does not exist
					}
				}
				else
				{
						$this->$prop = $this->config_vars[$tmp[$a]] = realpath($this->config_vars[$tmp[$a]]).'/';
				}
			}
		}

		$tmp = array('show_error_extended','fail_on_unprinted');
		for( $a = 0 ; $a < 2 ; $a += 1 )
		{
			if( isset($this->config_vars[$tmp[$a]]) )
			{
				if( is_bool($this->config_vars[$tmp[$a]]) )
				{
					$this->$tmp[$a] = $this->config_vars[$tmp[$a]];
				}
				else
				{
					// throw
				}
			}
		}

	}

	public static function get( $file = '' )
	{
		if( self::$me === null)
		{
			self::$me = new matrix_parsefile_preprocessor__config($file);
		}debug(self::$me);
		return self::$me;
	}

	public function has_var($key)
	{
		if( isset($this->config_vars[$key]) )
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
				$this->$conf_build_method(file_get_contents(realpath($file.$types[$a])));
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
			$config[$a] = explode(':',trim($config[$a]));
			if( $config[$a] != '' )
			{
				$key = trim($config[$a][0]);
				$value = trim($config[$a][1]);

				if( 'true' == strtolower($value) )
				{
					$value = true;
				}
				elseif( 'false' == strtolower($value) )
				{
					$value = false;
				}
				elseif( is_numeric($value) )
				{
					$value = ( $value/1 );
				}
				elseif( substr_count($value,',') > 0 )
				{
					$value = explode(',',$value);
				}

				$this->config_vars[$key] = $value;
			}
		}
	}

	private function build_config_json($content)
	{
		$config = json_decode($content,true);
	}
}

