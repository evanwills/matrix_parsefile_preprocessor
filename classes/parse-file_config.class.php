<?php

namespace matrix_parsefile_preprocessor;

require_once($pwd.'/includes/type_or_value.inc.php');

/**
 * config is a singlton object used to store shared info between
 * validator and compiler classes
 */
class config
{

	/**
	 * @private
	 * @var matrix_parsefile_preprocessor\config $me the single instance of this object
	 */
	static private $me = null;

	/**
	 * @private
	 * @var array $config_vars list of random extra key/value pairs stored for later use.
	 */
	private $config_vars = [];

	/**
	 * @private
	 * @var string $input_dir absolute path to directory where
	 *             base parse file is stored
	 */
	private $input_dir = '';

	/**
	 * @private
	 * @var string $output_dir absolute path to directory where
	 *             the output file is to be written to.
	 */
	private $output_dir = '';

	private $show_error_extended = false;

	private $on_unprinted = 'show';

	private $white_space = 'normal';

	private $strip_comments = false;
	private $wrap_in_comments = false;


//  END:  properties
// ========================================================
// START: public methods


	/**
	 * get() is the singleton access method to get the one
	 * instance of this object
	 * @param string $file path to base parse file or specific config
	 *               file (can be .info or JSON format)
	 */
	static public function get( $file = '' , $runtime = array() )
	{
		if( self::$me === null ){
			self::$me = new self( $file , $runtime );
		}
		return self::$me;
	}


	/**
	 * check whether the object has a particular property
	 * @param  string $key name of property to be checked
	 * @return boolean  true if the property exists
	 */
	public function has_var($key)
	{
		if( !is_string($key) && trim($key) !== '' )
		{
			throw new \Exception(get_class($this).'::has_var() expects only param $key to be a non-empty string. '.\type_or_value($key,'string').' given.');
		}

		if( property_exists($this,$key) || isset($this->config_vars[$key]) )
		{
			return true;
		}
		return false;
	}

	public function get_var($key)
	{
		if( !is_string($key) && trim($key) !== '' )
		{
			throw new \Exception(get_class($this).'::get_var() expects only param $key to be a non-empty string. '.\type_or_value($key,'string').' given.');
		}

		if( property_exists($this,$key) )
		{
			return $this->$key;
		}
		elseif( isset($this->config_vars[$key]) )
		{
			return $this->config_vars[$key];
		}
		else
		{
			throw new \Exception(get_class($this).'::get_var() expects only param $key to be a valid config property. Use config->has_var() to check the property exists before you try getting it.');
		}
	}

	public function get_all()
	{
		return get_object_vars($this);
	}

//  END:  public methods
// ========================================================
// START: private methods


	/**
	 * [[Description]]
	 * @private
	 * @param string $file full path to location of config file or
	 *               location of base parse file
	 */
	private function __construct($file)
	{
		if( !is_string($file) || trim($file) === '' )
		{
			throw new \Exception(get_class($this).'::__construct() expects only parameter $file to be a non-empty string. '.type_or_value($file,'string')." given.\n");
		}

		if( isset($_SERVER['SCRIPT_FILENAME']) )
		{
			$pwd = dirname(realpath($_SERVER['SCRIPT_FILENAME'])).'/';
		}
		elseif( isset($_SERVER['SCRIPT_NAME']) )
		{
			$pwd = dirname(realpath($_SERVER['SCRIPT_NAME'])).'/';
		}
		else
		{
			$pwd = realpath($_SERVER['PWD']).'/';
		}

		$file = realpath($file);
		if( $file === false )
		{
			// use default config file.
			$file = realpath($pwd.'config.info');
		}

		$path = pathinfo($file);

		$type = false;

		if( isset($path['extension']) )
		{
			if( $path['extension'] === 'info' )
			{
				$type = 'info';
				$input = $path['dirname'].'/'.$path['filename'].'.';
			}
			elseif( $path['extension'] === 'json' )
			{
				$type = 'json';
				$input = $path['dirname'].'/'.$path['filename'].'.';
			}
			elseif( $path['extension'] === 'xml' )
			{
				$tmp = $path['dirname'].'/'.$path['filename'].'.';
				$tmp_type = ['info', 'json'];
				for( $a = 0 ; $a < 2 ; $a += 1 )
				{
					if( file_exists($tmp.$tmp_type[$a]) )
					{
						$type = $tmp_type[$a];
						$input = $tmp;
					}
					elseif( file_exists($tmp.'config.'.$tmp_type[$a]) )
					{
						$type = $tmp_type[$a];
						$input = $tmp.'config.';
					}
					else
					{
						// no custom config could be found
						// lets try generic ones
						$tmp = $path['dirname'].'/config.';
						if( file_exists($tmp.$tmp_type[$a]) )
						{
							// found parse-file generic config
							$type = $tmp_type[$a];
							$input = $tmp;
						}
						else
						{
							if( file_exists($pwd.'/config.'.$tmp_type[$a]))
							{
								// found system generic config
								$type = $tmp_type[$a];
								$input = $tmp;
							}
						}
					}
				}
				unset($tmp);
			}
		}
		if( $type === false )
		{
			throw new \Exception(get_class($this).'::__construct() expects only parameter $file to point to a .info or .json file. "'.$file."\" given.\n");
		}
		elseif( $type === 'info' )
		{
			require_once($pwd.'includes/extract_dot_info.inc.php');
			$info = extract_dot_info(file_get_contents($input.$type));
		}
		else
		{
			$info = json_parse(file_get_contents($input.$type), true);
		}

		$this->input_dir = $path['dirname'].'/';

		foreach( $info as $key => $value )
		{
			if( property_exists($this,$key) )
			{
				if( gettype($this->$key) !== gettype($value) )
				{
					settype( $value , gettype($this->$key) );
				}
				$this->$key = $value;
			}
			else
			{
				$this->config_vars[$key] = $value;
			}
		}
	}
}