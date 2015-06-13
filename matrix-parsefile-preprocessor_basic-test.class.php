<?php


class matrix_parsefile_preprocessor__basic_test extends matrix_parsefile_preprocessor
{
	private $IDs = array();

	const TAG_regex = '`<MySource_(AREA|PRINT)(.*?)/?>`s';
	const TAG_ATTR_regex = '`(?<=\s)(id_name|print|design_area)=("|\')([^\2]*)(?=\2)`'

	public function __construct() { }

	public function existing_id($input) {
		if( is_string($input) ) {
			if( preg_match('`^[a-z0-9][a-z0-9_]+$`i', $input)) {
				if( !in_array($input) ) {
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

	public function undefined_area($input) {
		if( in_array($input) ) {
			return false;
		}
		else
		{
			$output = '"'.$input.'" has not been defined!';
		}
	}

	public function test_parsefile_regex( $input ) {
		if( is_string($input) ) {
			if( preg_match_all(self::TAG_regex,$input,$tags,PREG_SET_ORDER) ) {
				for( $a = 0 ; $a < count($tags) ; $a += 1 ) {
					if( preg_match_all(SELF::TAG_ATTR_regex,$tags[$a][2],$attrs,PREG_SET_ORDER) ){
						for( $b = 0 ; $b < count($attrs) ; $b += 1 ) {
							switch($attrs[$b][1]) {
								case 'id_name':
									if( $tags[$a][1] == 'area') {
										$status = $this->existing_id($attrs[$b][3]);
									}
									elseif( $tags[$a][1] == 'print') {
										$status = $this->undefined_id($attrs[$b][3]);
									}
									if( $status !== false ) {
										return array( $this->get_line_number($input,$tags[$a][0]) , $status );
									}
									break;
								case 'print':
									break;
								case 'design_area'
									if($attrs[$b][3] == 'show_if')
									break;

							}
						}
					}
				}
			}
		}
	}

	public function test_parsefile_XML( $input ) {
		if( is_string($input) ) {
			$xml = simplexml_load_string($input);
			debug($xml);
		}
	}
}