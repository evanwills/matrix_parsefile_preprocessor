<?php


class matrix_parsefile_preprocessor__basic_test extends matrix_parsefile_preprocessor
{
	private $IDs = array();
	private $not_printed_IDs = array();

	const TAG_regex = '`<MySource_(AREA|PRINT)(.*?)/?>`s';
	const TAG_ATTR_regex = '`(?<=\s)(id_name|print|design_area)=("|\')([^\2]*?)(?=\2)`';

	public function __construct() { }

	public function test_parsefile( $input ) {
		if( is_string($input) ) {
			if( preg_match_all(self::TAG_regex,$input,$tags,PREG_SET_ORDER) ) {
				for( $a = 0 ; $a < count($tags) ; $a += 1 ) {
					$tags[$a][1] = strtolower($tags[$a][1]);
					if( preg_match_all(SELF::TAG_ATTR_regex,$tags[$a][2],$attrs,PREG_SET_ORDER) ){
						for( $b = 0 ; $b < count($attrs) ; $b += 1 ) {
							switch($attrs[$b][1]) {
								case 'id_name':
									$status = false;
									if( $tags[$a][1] == 'area') {
										$status = $this->existing_id($attrs[$b][3]);
									}
									elseif( $tags[$a][1] == 'print') {
										$status = $this->undefined_area($attrs[$b][3]);
										$this->remove_non_printed_ID($attrs[$b][3]);
									}
									if( $status !== false ) {debug($attrs[$b][1]);
										return array( $this->get_line_number($input,$tags[$a][0]), $tags[$a][0] , $status );
									}
									break;
								case 'print':
									if( 'no' == strtolower($attrs[$b][3]) ) {
										$printed = false;
										$this->add_non_print_ID($attrs[$b][3]);
									}
									break;
								case 'design_area':
									if($attrs[$b][3] == 'show_if') {
										$show_if_xml = simplexml_load_string(preg_replace( '`^.*?('.preg_quote($tags[$a][0]).'.*?</MySource_AREA>).*$`s','\1',$input));
										$fields = array();
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
												if( $regex_error !== false ) {
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



	private function existing_id($input) {
		if( is_string($input) ) {
			if( preg_match('`^[a-z0-9][a-z0-9_]+$`i', $input)) {
				if( !in_array($input,$this->IDs) ) {
					$this->IDs[] = $input; debug($this->IDs);
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
		if( in_array($input,$this->IDs) ) {
			return false;
		}
		else
		{
			$output = 'Matrix design area "'.$input.'" has not been defined!';
		}
	}

	private function add_non_print_ID($id)
	{
		if( !in_array($id,$this->not_printed_IDs) ) {
			$this->not_printed_IDs[] = $id;
		}
	}

	private function remove_non_printed_ID($id) {
		$key = array_search($id,$this->not_printed_IDs);
		if( $key !== false ) {
			unset($this->non_printed_IDs[$key]);
			sort($this->non_printed_IDs);
		}
	}

}