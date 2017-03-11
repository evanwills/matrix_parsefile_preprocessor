<?php

require_once('classes/matrix-parsefile-preprocessor.class.php');
require_once('classes/matrix-parsefile-preprocessor_config.class.php');

class matrix_parsefile_preprocessor__basic_test extends matrix_parsefile_preprocessor
{
	private $IDs = array('__global__');
	private $not_printed_IDs = array();
	private $fail_on_unprinted = true;
	private $unprinted_exceptions = array('__global__');

	static private $me = null;

	const TAG_regex = '`<MySource_(AREA|PRINT)(.*?)/?>`s';
	const TAG_ATTR_regex = '`(?<=\s)(id_name|print|design_area)=("|\')([^\2]*?)(?=\2)`';
	const SHOWIF_START_REGEX = '`^.*?(';
	const SHOWIF_END_REGEX = '.*?)(?=\s*<MySource_(?:THEN|ELSE>)).*$`s';

	private function __construct( $unprinted_exceptions )
	{
		$config = matrix_parsefile_preprocessor__config::get();

		if( $config->has_var('unprinted_exceptions') ) {
			$unprinted_exceptions = $config->get_var('unprinted_exceptions');
			if( is_string($unprinted_exceptions) ) {
				$unprinted_exceptions = array($unprinted_exceptions);
			}
		}

		if( $config->check_type('bool','fail_on_unprinted') ) {
			$this->fail_on_unprinted = $config->get_var('fail_on_unprinted');
		}

		foreach( $unprinted_exceptions as $exception ) {
			if( is_string($exception) && $exception != '' && !in_array($exception, $this->unprinted_exceptions ) )
			{
				$this->unprinted_exceptions[] = $exception;
			}
		}
	}

	public static function get( $unprinted_exceptions = array() ) {
		if( self::$me === null )
		{
			if( !is_array($unprinted_exceptions) )
			{
				// throw
			}
			self::$me = new matrix_parsefile_preprocessor__basic_test($unprinted_exceptions);
		}
		return self::$me;
	}

	public function test_parsefile( $input , $source )
	{
		if( is_string($input) ) {
			if( preg_match_all(self::TAG_regex,$input,$tags,PREG_SET_ORDER) )
			{
				for( $a = 0 ; $a < count($tags) ; $a += 1 )
				{
					$tags[$a][1] = strtolower($tags[$a][1]);
					if( preg_match_all(SELF::TAG_ATTR_regex,$tags[$a][2],$attrs,PREG_SET_ORDER) )
					{
						$id = '';
						for( $b = 0 ; $b < count($attrs) ; $b += 1 )
						{
							switch($attrs[$b][1]) {
								case 'id_name':
									$id = $attrs[$b][3];
									$status = false;
									if( $tags[$a][1] == 'area')
									{
										$status = $this->existing_id($attrs[$b][3]);
									}
									elseif( $tags[$a][1] == 'print')
									{
										$status = $this->undefined_area($attrs[$b][3]);
										$this->remove_non_printed_ID($attrs[$b][3]);
									}
									if( $status !== false )
									{
										return array( $this->get_line_number($input,$tags[$a][0]), $tags[$a][0] , $status );
									}
									break;

								case 'print':
									if( 'no' == strtolower($attrs[$b][3]) )
									{
										$printed = false;
										$this->add_non_print_ID( $id , $this->get_line_number($input,$tags[$a][0]) , $source);
									}
									break;

								case 'design_area':
									if($attrs[$b][3] == 'show_if') {
										$show_if = preg_replace( SELF::SHOWIF_START_REGEX.preg_quote($tags[$a][0]).SELF::SHOWIF_END_REGEX,'\1</MySource_AREA>',$input);
										$show_if = preg_replace_callback('`(?<=value=")(.*?)(?=")`',function($matches){return htmlspecialchars($matches[1]);},$show_if);
										$show_if_xml = simplexml_load_string($show_if);
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
													$regex = '/'.$fields['condition_keyword_match'].'/';
													$regex_error = regex_error( $regex );
													if( $regex_error !== false )
													{
														// regex has an error show error and terminate
														return array($this->get_line_number($input,$tags[$a][0]), $tags[$a][0], "Regular expression \"$regex\" has an error: ".$regex_error);
													}

												}
											}
										}
									}
									break;
							}
						}
					}
				}
			}
		}
		return true;
	}

	public function fail_on_unprinted()
	{
		$config = matrix_parsefile_preprocessor__config::get();
		$partials = $config->get_var('partials_dir');

		if( !empty($this->not_printed_IDs) )
		{
			$c = count($this->not_printed_IDs);
			if( $c > 1 )
			{
				$areas = "$c design areas were";
			}
			else
			{
				$areas = 'design area was';
			}

			echo "\n\n-----------------------------------------\n-- WARNING --\n\nThe following $areas unprinted:";

			foreach( $this->not_printed_IDs as $ID => $where )
			{
				$short_file = str_replace($partials,'',$where['file']);
				echo "\n    \"{$ID}\"\n\ton line {$where['line']}\n\tof $short_file\n\t{$where['file']}\n";
			}
			if( $c > 1 )
			{
				$it_they = 'They';
				$it_them = 'them';
			}
			else
			{
				$it_they = 'It';
				$it_them = 'it';
			}
			echo "\n$it_they may not be needed. If so, you should delete $it_them from the parse file.\n\n";
			//exit;
		}
		return false;
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
	}
}
