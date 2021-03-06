<?php

namespace matrix_parsefile_preprocessor;

if( !defined('MATRIX_PARSEFILE_PREPROCESSOR__VALIDATOR') )
{

define('MATRIX_PARSEFILE_PREPROCESSOR__VALIDATOR',true);


require(__DIR__.'/xml_tag.class.php');
require(__DIR__.'/MySource_tag.class.php');
require(__DIR__.'/parse-file_config.class.php');
require(__DIR__.'/parse-file_logger.class.php');
require(__DIR__.'/parse-file_nested-partials.class.php');

require(__DIR__.'/../includes/regex_error.inc.php');
require(__DIR__.'/../includes/get_line_number.inc.php');
require(__DIR__.'/../includes/type_or_value.inc.php');


class validator {

	private $config = null;
	private $log = null;
	private $nested_partials = null;

	private $unprinted_exceptions = [ '__global__'];

	private $areas = 0;
	private $non_printed_areas = 0;
	private $prints = 0;

	private $IDs = ['__global__'];
	private $unprinted_IDs = [];
	private $old_IDs = [];

	private $tags = [];
	private $noID = 0;

	const TAG_REGEX = '`<MySource_(AREA|PRINT)(.*?)/?>`is';
	const SHOWIF_START_REGEX = '`^.*?(';
	const SHOWIF_END_REGEX = '.*?)(?=\s*<MySource_(?:THEN|ELSE>)).*$`is';
	const SHOWIF_CALLBACK_REGEX = '`(?<=value=")(.*?)(?=")`';


	public function __construct( config $config , logger $logger , nested_partials $partials )
	{
		$this->config = $config;
		$this->log = $logger;
		$this->nested_partials = $partials;
	}


	public function parse( $code , $file_name , $file_content = '' )
	{

		if( !is_string($code) )
		{
			throw new \Exception(get_class($this).'::parse() expects first parameter $code to be a string. '.\type_or_value($code,'string').' given.');
		}
		if( !is_string($file_name) || trim($file_name) === '' )
		{
			throw new \Exception(get_class($this).'::parse() expects second parameter $file_name to be a non-empty string. '.\type_or_value($file_name,'string').' given.');
		}
		if( !is_string($file_content) )
		{
			throw new \Exception(get_class($this).'::parse() expects third parameter $file_content to be a string. '.gettype($file_content).' given.');
		}
		if( $file_content === '' )
		{
			$file_content = $code;
		}



		if( preg_match_all( self::TAG_REGEX , $code , $tags , PREG_SET_ORDER ) )
		{
			for( $a = 0 ; $a < count($tags) ; $a += 1 )
			{
				$status = false;
				$element = strtolower($tags[$a][1]);
				$line_number = get_line_number($tags[$a][0] , $file_content );
				$tag = new \mysource_tag( $tags[$a][0] , $element , $tags[$a][2] , $file_name , $line_number );
				$id = $tag->get_id();

				if( $element === 'print' )
				{
					$this->prints += 1;
					if( $id !== '' )
					{
						$this->_remove_non_printed_ID($id);
//						$this->tags[$id]->set_printed();
						if( $msg = $this->_undefined_area($id) )
						{
							$this->log->add(
								'error'
								,$msg
								,$file_name
								,$tags[$a][0]
								,$file_content
							);
						}
					}
				}
				else
				{
					if( trim($id) === '' )
					{
						$this->log->add(
							'error'
							,'No ID (id_name) speicified!'
							,$file_name
							,$tags[$a][0]
							,$file_content
						);
						$this->noID += 1;
						$id = 'noID_'.$this->noID;
					}
					$this->tags[$id] = $tag;
					$this->areas += 1;
					if( $msg = $this->_existing_id($id) )
					{
						$this->log->add(
							 'error'
							,$msg
							,$file_name
							,$tags[$a][0]
							,$file_content
						);
					}

					if( $tag->get_attr('print') === 'no' )
					{

						$printed = false;
						$this->_add_non_print_ID( $id , $tag->get_line() , $file_name);
						$this->non_printed_areas += 1;
					}

					if( $tag->get_attr('design_area') === 'show_if' )
					{
						$show_if_regex = self::SHOWIF_START_REGEX.preg_quote($tags[$a][0]).SELF::SHOWIF_END_REGEX;

						$show_if_xml = simplexml_load_string(
							preg_replace_callback(
								 '`(?<=value=")(.*?)(?=")`'
								,array( $this , 'SHOW_IF_CALLBACK' )
								,preg_replace(
									 $show_if_regex
									,'\1</MySource_AREA>'
									,$code
								 )
							 )
						);

						$fields = array();
						if( $show_if_xml !== false )
						{
							// todo work out why XML sometimes breaks;
							foreach( $show_if_xml->MySource_SET as $area_set )
							{
								$name = '';
								$value = '';
								foreach ($area_set->attributes() as $key => $VALUE ) {
									settype($key,'string');
									settype($VALUE,'string');
									$$key = $VALUE;
								}
								$fields[$name] = $value;
							}
							if( isset($fields['condition']) && $fields['condition'] == 'keyword_regexp' )
							{
								if( isset($fields['condition_keyword_match']) )
								{
									if( $msg = regex_error( '/'.$fields['condition_keyword_match'].'/' ) )
									{
										$this->log->add(
											 'error'
											,"Regular expression \"$regex\" has an error: ".$msg
											,$file_name
											,$tags[$a][0]
											,$file_content
										);
									}

								}
							}
						}

					}
				}
			}
		}
	}



	public function process_old_parse_file( $parse_file_contents , $file_name = 'web' )
	{
		if( !is_string($parse_file_contents) || trim($parse_file_contents) === '' )
		{
			throw new \Exception(get_class($this).'::process_old_parse_file() expects first parameter $parse_file_contents to be a non-empty string. '.type_or_value($parse_file_contents,'string').' given.');
		}
		elseif( is_file($parse_file_contents) && is_readable($parse_file_contents) && substr(strtolower($parse_file_contents),-4,4) === '.xml' )
		{
			$file_name = $parse_file_contents;
			$parse_file_contents = file_get_contents($parse_file_contents);
		}

		if( !is_string($file_name) || trim($file_name) === '' )
		{
			throw new \Exception(get_class($this).'::process_old_parse_file() expects second parameter $file_name to be a non-empty string. '.type_or_value($file_name,'string').' given.');
		}
		elseif( $file_name !== 'web' && !is_file($file_name) )
		{
			throw new \Exception(get_class($this).'::process_old_parse_file() expects second parameter $file_name to be either "web" or a path to an existing file. "'.$file_name.'" given.');
		}



		if( preg_match_all( self::TAG_REGEX , $parse_file_contents , $tags , PREG_SET_ORDER ) )
		{
			for( $a = 0 ; $a < count($tags) ; $a += 1 )
			{
				$element = strtolower($tags[$a][1]);
				if( $element === 'area' )
				{
					$tag = new \mysource_tag( $tags[$a][0] , $element , $tags[$a][2] , $file_name , 1 );
					$this->old_IDs[] = $tag->get_id();
					unset($tag);
				}
			}
		}
		else
		{
			$this->log->add(
				'error'
				,"$file_name contained no <MySource_AREA> tags."
				,$file_name
			);
		}
	}



	public function get_deleted_IDs()
	{
		$c = count($this->old_IDs);
		if( $c > 0 )
		{
			$tmp = $this->old_IDs;
			for( $a = 0 ; $a += $c ; $a += 1 )
			{
				if( $key = array_search( $this->IDs[$a] , $tmp ) )
				{
					unset($tmp[$key]);
				}
			}
			return $tmp;
		}
		else
		{
			$this->log->add(
				 'warning'
				,'There were no IDs collected from an old/existing parse file. It was not possible to work out if any design areas were deleted.'
			);
		}
	}
	public function check_deleted_areas()
	{
		$c = count($this->old_IDs);
		if( $c > 0 )
		{
			$tmp = $this->old_IDs;
			sort($tmp);
			foreach( $this->tags as $id => $tag )
			{
				if( $key = array_search( $id , $tmp ) )
				{
					unset($tmp[$key]);
				}
			}
			sort($tmp);
			$b = count($tmp);
			if( $b > 0 )
			{
				if( $b === 1 )
				{
					$area = 'design area was';
				}
				else
				{
					$area = $b.' design areas were';
				}

				$this->log->add(
					 'warning'
					,'The following '.$area.' in the old parse file but not in the new one:'
				);
				$log_item = $this->log->get_last_item();
				for( $a = 0 ; $a < $b ; $a += 1 )
				{
					$log_item->set_extra_detail( $tmp[$a] );
				}
			}
		}
		else
		{
			$this->log->add(
				 'error'
				,'There were no IDs collected from an old/existing parse file. It was not possible to work out if any design areas were deleted.'
			);
		}
	}

	public function get_errors()
	{
		$output = [];
		foreach( $this->tags as $tag )
		{
			if( $tag->has_error() )
			{
				$output[] = [
					 'line' => $tag->get_line()
					,'file' => $tag->get_file()
					,'id' => $tag->get_id()
					,'xml' => $tag->get_whole_tag()
					,'msg' => $tag->get_error()
				];
			}
		}
		if( count($output) === 0 )
		{
			return false;
		}
		else
		{
			return $output;
		}
	}

	public function get_logs()
	{
		return $this->log;
	}

	public function get_bad_tags()
	{
		$output = [];
		foreach( $this->tags as $ID => $tags )
		{
			if( $tag->has_error() )
			{
				$output[] = $tag;
			}
		}
		return $output;
	}


	public function log_unprinted()
	{
		$output = [];
		foreach( $this->tags as $tag )
		{
			if( $tag->get_printed() === false && $tag->get_called() === false )
			{
				$this->log->add(
					'warning'
					,'"'.$tag->get_id().'" was never printed'
					,$tag->get_file()
					,$tag->get_whole_tag()
					,''
					,$tag->get_line()
				);
			}
		}
	}


	public function get_areas_count()
	{
		return $this->areas;
	}

	public function get_non_printed_areas_count()
	{

		return $this->non_printed_areas;
	}

	public function get_prints_count()
	{
		return $this->prints;
	}

	public function get_old_IDs()
	{
		return $this->old_IDs;
	}

	public function get_new_IDs()
	{
		return array_keys($this->tags);
	}



//  END:  public methods
// ===============================================================
// START: private methods



	private function _undefined_area($input) {
		if( in_array($input,$this->IDs) )
		{
			return false;
		}
		else
		{
			return 'Matrix design area "'.$input.'" has not yet been defined!';
		}
	}




	private function _add_non_print_ID( $id, $line , $file )
	{
		if( !isset($this->not_printed_IDs[$id]) && !in_array($id,$this->unprinted_exceptions) )
		{
			$this->not_printed_IDs[$id] = array(
				 'line' => $line
				,'file' => $file
			);
		}
	}




	private function _remove_non_printed_ID($id)
	{
		if( isset($this->not_printed_IDs[$id]) )
		{
			unset($this->not_printed_IDs[$id]);
		}
		if( isset($this->tags[$id]) )
		{
			$this->tags[$id]->set_called();
		}
	}


	private function _existing_id($input)
	{
		if( is_string($input) )
		{
			if( preg_match('`^[a-z0-9][a-z0-9_]+$`i', $input))
			{
				if( !in_array($input,$this->IDs) )
				{
					$this->IDs[] = $input;
					return false;
				}
				else
				{
					$output = 'has already been defined';
				}
			}
			else
			{
				$output = 'is not a valid matrix ID';
			}
		}
		else
		{
			$output = 'is not a string';
		}
		return '"'.$input.'" '.$output.'!';
	}


	private function SHOW_IF_CALLBACK($matches)
	{
		return htmlspecialchars($matches[1]);
	}
}


}