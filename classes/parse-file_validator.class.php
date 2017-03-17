<?php

namespace matrix_parsefile_preprocessor;


require_once(__DIR__.'/xml_tag.class.php');
require_once(__DIR__.'/MySource_tag.class.php');
require_once(__DIR__.'/parse-file_config.class.php');
require_once(__DIR__.'/parse-file_logger.class.php');
require_once(__DIR__.'/parse-file_nested-partials.class.php');

require_once($pwd.'/includes/regex_error.inc.php');
require_once($pwd.'/includes/get_line_number.inc.php');
require_once($pwd.'/includes/type_or_value.inc.php');


class validator {

	private $config = null;
	private $log = null;
	private $nested_partials = null;

	private $IDs = ['__global__'];
	private $unprinted_IDs = [];

	private $tags = [];

	const TAG_REGEX = '`<MySource_(AREA|PRINT)(.*?)/?>`s';
	const SHOWIF_START_REGEX = '`^.*?(';
	const SHOWIF_END_REGEX = '.*?)(?=\s*<MySource_(?:THEN|ELSE>)).*$`s';
	const SHOWIF_CALLBACK_REGEX = '`(?<=value=")(.*?)(?=")`';


	public function __construct( $file = 'web' )
	{
		$this->config = config::get($file);
		$this->log = logger::get();
		$this->nested_partials = nested_partials::get('web');
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
				$this->tags[$id] = $tag;

				if( $element === 'print' )
				{
					if( $id !== '' )
					{
						$this->remove_non_printed_ID($id);
						if( $msg = $this->undefined_area($id) )
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
					if( $msg = $this->existing_id($id) )
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
						$this->add_non_print_ID( $id , $tag->get_line() , $source);
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
									,$input
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
									if( $msg = \regex_error( '/'.$fields['condition_keyword_match'].'/' ) )
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


	private function undefined_area($input) {
		if( in_array($input,$this->IDs) )
		{
			return false;
		}
		else
		{
			return 'Matrix design area "'.$input.'" has not yet been defined!';
		}
	}




	private function add_non_print_ID( $id, $line , $file )
	{
		if( !isset($this->not_printed_IDs[$id]) && !in_array($id,$this->unprinted_exceptions) )
		{
			$this->not_printed_IDs[$id] = array(
				 'line' => $line
				,'file' => $file
			);
		}
	}




	private function remove_non_printed_ID($id)
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


	private function existing_id($input)
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

}