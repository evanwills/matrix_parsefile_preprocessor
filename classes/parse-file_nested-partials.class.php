<?php

namespace matrix_parsefile_preprocessor;

class nested_partials
{
	/**
	 * @private
	 * @var nested_partials the single instance object of this class/
	 */
	static private $me = null;

	/**
	 * full/absolute path to directory of initial parse file
	 * being proccessed.
	 * @private
	 */
	private $path = '';

	/**
	 * file name of initial parse file being proccessed.
	 * @private
	 */
	private $file = '';

	/**
	 * @private
	 * @var array $partials_dirs list of nested partials directories.
	 */
	private $partials_dirs = [];

	/**
	 * @private
	 * @var array $tmp_partials_dirs used for itterator function
	 *            (is reset each time a new partials dir is added
	 *             or removed)
	 */
	private $tmp_partials_dirs = false;


//  END: object properties
// ========================================================
// START: public Singleton method



	/**
	 * Get the single instance instance of this object
	 * @return nested_partials the single instance object of this class
	 */
	static public function get( $file = '' )
	{
		if( self::$me === null )
		{
			self::$me = new self($file);
		}
		return self::$me;
	}


//  END: public Singleton method
// ========================================================
// START: private methods



	/**
	 * add another nested partial to the stack of partials currently
	 * being processed.
	 * @param  string $path file path to the partial being processed
	 * @param  string $file name of the partial being processed
	 * @return string full/absolute path to partial being processed
	 */
	public function add( $path , $file )
	{
		if( $path === $this->path && $file === $this->file )
		{
			return ;
		}
		if( !is_string($path) || trim($path) === '' )
		{
			throw new \exception(get_class($this).'::add() expects first parameter $path to be a non-empty string. '.gettype($path).' given.');
		}


		$c = count($this->partials_dirs) - 1;

		if( substr($path,0,1) === '/' || $c < 0 ) // path is relative to partials directory
		{
			if( $path !== $this->path)
			{
				$path = $this->path.$path;
			}
			if( !is_file($path.$file) )
			{
				if( is_file($path.'_'.$file) )
				{
					$file = '_'.$file;
				}
				else
				{
					throw new \exception(get_class($this).'::add() expects second parameter $file to be an existing file. "'.$file.'" cannot be found.');
				}
			}
		}
		elseif( is_dir($this->get_inner_most_path().$path) ) // path is local to current parse file partial
		{
			$path = $this->get_inner_most_path().$path;
			if( !is_file($path.$file) )
			{
				if( is_file($path.'_'.$file) )
				{
					$file = '_'.$file;
				}
				else
				{
					throw new \exception(get_class($this).'::add() expects second parameter $file to be an existing file. "'.$file.'" cannot be found.');
				}
			}
		}
		else
		{
			throw new \exception(get_class($this).'::add() expects first parameter $path to be a valid path to a directory. "'.$path.'" cannot be found.');
		}

		$this->partials_dirs[] = [ 'path' => $path , 'file' => $file ];
		$this->tmp_partials_dirs = $this->partials_dirs;
	}

	/**
	 * When a partial has been processed, it needs to be removed from
	 * the stack of partials
	 */
	public function remove()
	{
		array_pop($this->partials_dirs);
		$this->tmp_partials_dirs = $this->partials_dirs;
	}

	/**
	 * itterator method to retrieve the next (parent) partials
	 * directory in the stack of nested partials.
	 * @return string absolute path to a partials directory
	 * @return boolean FALSE if we have reached the bottom of the stack
	 */
	public function get_next()
	{
		if( $this->tmp_partials_dirs === false )
		{
			$this->tmp_partials_dirs = $this->partials_dirs;
		}

		if( count($this->tmp_partials_dirs) > 0 )
		{
			return array_pop($this->tmp_partials_dirs);
		}
		else
		{
			$this->tmp_partials_dirs = false;
			return false;
		}
	}

	/**
	 * manually reset the get_partials iterator array
	 */
	public function reset_itterator()
	{
		$this->tmp_partials_dirs = $this->partials_dirs;
	}

	/**
	 * get the most recent partial to be added to the stack
	 * @return array (associative) [[Description]]
	 */
	public function get_inner_most()
	{
		$c = count($this->partials_dirs) - 1;
		if( isset($this->partials_dirs[$c]) )
		{
			return $this->partials_dirs[$c];
		}
		else
		{
			return [ 'path' => $this->path , 'file' => $this->file ];
		}
	}

	/**
	 * get the parth of the most recent partial to be added to the stack
	 * @return string the file system path to the directory/folder
	 *                containing the most recent partial added to the stack
	 */
	public function get_inner_most_path()
	{
		$output = $this->get_inner_most();
		return $output['path'];
	}

	/**
	 * get the parth of the most recent partial to be added to the stack
	 * @return string the file system path to the directory/folder
	 *                containing the most recent partial added to the stack
	 */
	public function get_inner_most_file()
	{
		$output = $this->get_inner_most();
		return $output['file'];
	}

	/**
	 * get the parth of the most recent partial to be added to the stack
	 * @return string the file system path to the directory/folder
	 *                containing the most recent partial added to the stack
	 */
	public function get_inner_most_file_whole()
	{
		$output = $this->get_inner_most();
		return $output['path'] . $output['file'];
	}

	public function get_base()
	{
		return $this->path.$this->file;
	}

	public function get_base_path()
	{
		return $this->path;
	}

	public function get_base_file()
	{
		return $this->file;
	}

	public function get_all()
	{
		return get_object_vars($this);
	}

//  END: public methods
// ========================================================
// START: private methods


	private function __construct($base_file)
	{
		if( !is_string($base_file) )
		{
			throw new \exception(get_class($this)."::__constructor() expects first paramater \$base_file to be a string! ".gettype($base_file)." given.");
		}

		$base_file = realpath($base_file);
		if( $base_file === false )
		{
			throw new \exception(get_class($this)."::__constructor() expects first paramater \$base_file to be the path to a file! \"$base_file\" cannot be found.");
		}

		$file_parts = pathinfo($base_file);
		if( !isset($file_parts['extension']) || strtolower($file_parts['extension']) !== 'xml' )
		{
			throw new \exception(get_class($this)."::__constructor() expects first paramater \$base_file to be an XML file (with the .xml extension)! \"$base_file\" cannot be found.");
		}

		$this->path = $file_parts['dirname'].'/';
		$this->file = $file_parts['basename'];
	}
}