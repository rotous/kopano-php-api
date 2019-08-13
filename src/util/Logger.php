<?php

namespace Kopano\Api;

require_once(__DIR__ . '/../mapi/mapitags.php');
require_once(__DIR__ . '/../mapi/mapicode.php');

class Logger {
	/**
	 * Can be 'html', 'dump', 'error', or 'echo'
	 */
	static private $_type = 'html';

	static private $_mapiPropList;
	static private $_mapiErrorList;

	/**
	 * Outputs the passed parameters in html format.
	 * @param String|Misc $param1 If $param2 is set and this is a string, it will be output as header of the block, otherwise it
	 * will be output as the value.
	 * @param Misc|NULL $param2 If $param1 is a String and this is not NULL, it will be output as value.
	 */
	static public function logHtml($param1, $param2=null){
		if ( func_num_args() > 1 && $param1 ){
			echo '<div style="background-color: #eeeeee; display: block; padding: 1em; overflow-wrap: break-word; font-family: monospace;">';
			echo $param1;
			echo '</div>';
		}
		if ( func_num_args() > 1 ){
			echo '<pre style="background-color: #fafafa; margin: 0 0 1em; display: block; padding: 1em; overflow-wrap: break-word; font-family: monospace; white-space: pre-wrap;">';
			if ( is_array($param2) && count($param2)>0 ){
				$keys = array_keys($param2);
				if ( isset($keys[0]) && is_int($keys[0]) && ($keys[0]>1000 || $keys[0]<-1000) ){
					// Let's assume this is an array with mapi properties
					$param2 = Logger::parseProps($param2);
				} else if ( isset($param2[0]) && $param2[0] instanceof MapiObject ){
					foreach ( $param2 as $i => $mapiObj ){
						$param2[$i] = array(
							'className' => get_class($mapiObj),
							'properties' => Logger::parseProps($mapiObj->getProperties())
						);
					}
				}
			} elseif ( $param2 instanceof MapiObject ){
				$mapiObj = $param2;
				$param2 = array(
					'className' => get_class($mapiObj),
					'defaultPropertyKeys' => array_keys(Logger::parseProps(array_combine($mapiObj->getDefaultPropertyKeys(), array_keys($mapiObj->getDefaultPropertyKeys())))),
					'properties' => Logger::parseProps($mapiObj->getProperties())
				);
			}
			echo str_replace("=>\n", ' => ', var_export($param2, true));
			echo '</pre>';
		} else {
			Logger::logHtml(NULL, $param1);
		}
	}

	/**
	 * Outputs the passed parameters in html format, treating the value as restriction.
	 * @param String|Misc $param1 If $param2 is set and this is a string, it will be output as header of the block, otherwise it
	 * will be output as the value.
	 * @param Misc|NULL $param2 If $param1 is a String and this is not NULL, it will be output as value.
	 */
	static public function logRestrictionHtml($param1, $param2 = NULL){
		$restriction = func_num_args() > 1 ? $param2 : $param1;
		$restriction = Logger::_simplifyRestriction($restriction);

		if ( func_num_args() > 1 && $param !== NULL){
			Logger::logHtml($param1, $restriction);
		} else {
			Logger::logHtml($restriction);
		}
	}

	/**
	 * Alias for logHtml
	 */
	static public function log() {
		if ( Logger::$_type === 'html' ){
			call_user_func_array('\Kopano\Api\Logger::logHtml', func_get_args());
		}
		// TODO: Implement the other types
	}

	/**
	 * Will convert keys to their string representation if found, and some binary values to hex values when possible.
	 * @param Array $param An associative array with MAPI properties
	 *
	 * @return Array MAPI properties array with converted keys and binary values
	 */
	static public function parseProps($param){
		Logger::_createMapiPropList();

		foreach ( $param as $key=>$value ){
			$prop = Logger::getPropertyString($key);
			if ( $prop ){
				$newKey = "0x".str_pad(strtoupper(dechex($key)),8, '0', STR_PAD_LEFT).' '.$prop;

				if ( ($key & 0xFFFF) === PT_ERROR ){
					$value = 'PT_ERROR: ' . Logger::getMapiErrorString($value);
				} else {
					if ( strpos($prop, 'ENTRYID') === strlen($prop)-strlen('ENTRYID') ||		// Let's assume this is an entryid and convert it to its hex representation
					strpos($prop, 'RECORD_KEY') === strlen($prop)-strlen('RECORD_KEY') ||		// Let's assume this is an record key and convert it to its hex representation
					strpos($prop, 'INSTANCE_KEY') === strlen($prop)-strlen('INSTANCE_KEY') ){	// Let's assume this is an instance key and convert it to its hex representation
						 $value = bin2hex($value);
					}
				}
				$param[$newKey] = $value;
			}else{
				$newKey = "0x".str_pad(strtoupper(dechex($key)),8, '0', STR_PAD_LEFT);
				$param[$newKey] = $value;
			}
			unset($param[$key]);
		}

		return $param;
	}

	static public function getPropertyString($propertyKeyInt){
		Logger::_createMapiPropList();

		foreach (Logger::$_mapiPropList as $key=>$val){
			if ( ($propertyKeyInt & 0xFFFF0000) === ($val & 0xFFFF0000) ){
				return $key;
			}
		}

		return '';
	}

	static public function getMapiErrorString($propertyKeyInt){
		Logger::_createMapiErrorList();

		foreach (Logger::$_mapiErrorList as $key=>$val){
			if ( ($propertyKeyInt & 0xFFFFFFFF) === ($val & 0xFFFFFFFF) ){
				return $key;
			}
		}

		return 'unknown error (0x'.dechex($propertyKeyInt).')';
	}


	/**
	 * This function is used to covert all constants of restriction into a human readable strings
	 */
	static private function _simplifyRestriction($restriction) {
		if (!is_array($restriction)){
			return $restriction;
		}

		switch($restriction[0]){
			case RES_AND:
				$restriction[0] = "RES_AND";
				if(isset($restriction[1][0]) && is_array($restriction[1][0])) {
					foreach($restriction[1] as &$res) {
						$res = Logger::_simplifyRestriction($res);
					}
					unset($res);
				} else if(isset($restriction[1]) && $restriction[1]) {
					$restriction[1] = Logger::_simplifyRestriction($restriction[1]);
				}
				break;
			case RES_OR:
				$restriction[0] = "RES_OR";
				if(isset($restriction[1][0]) && is_array($restriction[1][0])) {
					foreach($restriction[1] as &$res) {
						$res = Logger::_simplifyRestriction($res);
					}
					unset($res);
				} else if(isset($restriction[1]) && $restriction[1]) {
					$restriction[1] = Logger::_simplifyRestriction($restriction[1]);
				}
				break;
			case RES_NOT:
				$restriction[0] = "RES_NOT";
				$restriction[1][0] = Logger::_simplifyRestriction($restriction[1][0]);
				break;
			case RES_COMMENT:
				$restriction[0] = "RES_COMMENT";
				$res = Logger::_simplifyRestriction($restriction[1][RESTRICTION]);
				$props = $restriction[1][PROPS];

				foreach($props as &$prop) {
					$propTag = $prop[ULPROPTAG];
					$propValue = $prop[VALUE];

					unset($prop);

					$prop["ULPROPTAG"] = is_string($propTag) ? $propTag : Logger::_property2json($propTag);
					$prop["VALUE"] = is_array($propValue) ? $propValue[$propTag] : $propValue;
				}
				unset($prop);

				unset($restriction[1]);

				$restriction[1]["RESTRICTION"] = $res;
				$restriction[1]["PROPS"] = $props;
				break;
			case RES_PROPERTY:
				$restriction[0] = "RES_PROPERTY";
				$propTag = $restriction[1][ULPROPTAG];
				$propValue = $restriction[1][VALUE];
				$relOp = $restriction[1][RELOP];

				unset($restriction[1]);

				// relop flags
				$relOpFlags = "";
				if($relOp == RELOP_LT) {
					$relOpFlags = "RELOP_LT";
				} else if($relOp == RELOP_LE) {
					$relOpFlags = "RELOP_LE";
				} else if($relOp == RELOP_GT) {
					$relOpFlags = "RELOP_GT";
				} else if($relOp == RELOP_GE) {
					$relOpFlags = "RELOP_GE";
				} else if($relOp == RELOP_EQ) {
					$relOpFlags = "RELOP_EQ";
				} else if($relOp == RELOP_NE) {
					$relOpFlags = "RELOP_NE";
				} else if($relOp == RELOP_RE) {
					$relOpFlags = "RELOP_RE";
				}

				$restriction[1]["RELOP"] = $relOpFlags;
				$restriction[1]["ULPROPTAG"] = is_string($propTag) ? $propTag : Logger::_property2json($propTag);
				$restriction[1]["VALUE"] = is_array($propValue) ? $propValue[$propTag] : $propValue;
				if ( is_string($restriction[1]["ULPROPTAG"]) && substr($restriction[1]["ULPROPTAG"], strlen($restriction[1]["ULPROPTAG"])-strlen('ENTRYID'))=='ENTRYID' ){
					if ( is_string($restriction[1]["VALUE"]) ){
						$restriction[1]["VALUE"] = bin2hex($restriction[1]["VALUE"]);
					}
				}
				break;
			case RES_CONTENT:
				$restriction[0] = "RES_CONTENT";
				$propTag = $restriction[1][ULPROPTAG];
				$propValue = $restriction[1][VALUE];
				$fuzzyLevel = $restriction[1][FUZZYLEVEL];

				unset($restriction[1]);

				// fuzzy level flags
				$levels = array();

				if (($fuzzyLevel & FL_SUBSTRING) == FL_SUBSTRING)
					$levels[] = "FL_SUBSTRING";
				elseif (($fuzzyLevel & FL_PREFIX) == FL_PREFIX)
					$levels[] = "FL_PREFIX";
				else
					$levels[] = "FL_FULLSTRING";

				if (($fuzzyLevel & FL_IGNORECASE) == FL_IGNORECASE)
					$levels[] = "FL_IGNORECASE";

				if (($fuzzyLevel & FL_IGNORENONSPACE) == FL_IGNORENONSPACE)
					$levels[] = "FL_IGNORENONSPACE";

				if (($fuzzyLevel & FL_LOOSE) == FL_LOOSE)
					$levels[] = "FL_LOOSE";

				$fuzzyLevelFlags = implode(" | ", $levels);

				$restriction[1]["FUZZYLEVEL"] = $fuzzyLevelFlags;
				$restriction[1]["ULPROPTAG"] = is_string($propTag) ? $propTag : Logger::_property2json($propTag);
				$restriction[1]["VALUE"] = is_array($propValue) ? $propValue[$propTag] : $propValue;
				break;
			case RES_COMPAREPROPS:
				$propTag1 = $restriction[1][ULPROPTAG1];
				$propTag2 = $restriction[1][ULPROPTAG2];

				unset($restriction[1]);

				$restriction[1]["ULPROPTAG1"] = is_string($propTag1) ? $proptag1 : Logger::_property2json($proptag1);
				$restriction[1]["ULPROPTAG2"] = is_string($propTag2) ? $propTag2 : Logger::_property2json($propTag2);
				break;
			case RES_BITMASK:
				$restriction[0] = "RES_BITMASK";
				$propTag = $restriction[1][ULPROPTAG];
				$maskType = $restriction[1][ULTYPE];
				$maskValue = $restriction[1][ULMASK];

				unset($restriction[1]);

				// relop flags
				$maskTypeFlags = "";
				if($maskType == BMR_EQZ) {
					$maskTypeFlags = "BMR_EQZ";
				} else if($maskType == BMR_NEZ) {
					$maskTypeFlags = "BMR_NEZ";
				}

				$restriction[1]["ULPROPTAG"] = is_string($propTag) ? $propTag : Logger::_property2json($propTag);
				$restriction[1]["ULTYPE"] = $maskTypeFlags;
				$restriction[1]["ULMASK"] = $maskValue;
				break;
			case RES_SIZE:
				$restriction[0] = "RES_SIZE";
				$propTag = $restriction[1][ULPROPTAG];
				$propValue = $restriction[1][CB];
				$relOp = $restriction[1][RELOP];

				unset($restriction[1]);

				// relop flags
				$relOpFlags = "";
				if($relOp == RELOP_LT) {
					$relOpFlags = "RELOP_LT";
				} else if($relOp == RELOP_LE) {
					$relOpFlags = "RELOP_LE";
				} else if($relOp == RELOP_GT) {
					$relOpFlags = "RELOP_GT";
				} else if($relOp == RELOP_GE) {
					$relOpFlags = "RELOP_GE";
				} else if($relOp == RELOP_EQ) {
					$relOpFlags = "RELOP_EQ";
				} else if($relOp == RELOP_NE) {
					$relOpFlags = "RELOP_NE";
				} else if($relOp == RELOP_RE) {
					$relOpFlags = "RELOP_RE";
				}

				$restriction[1]["ULPROPTAG"] = is_string($propTag) ? $propTag : Logger::_property2json($propTag);
				$restriction[1]["RELOP"] = $relOpFlags;
				$restriction[1]["CB"] = $propValue;
				break;
			case RES_EXIST:
				$restriction[0] = "RES_EXIST";
				$propTag = $restriction[1][ULPROPTAG];

				unset($restriction[1]);

				$restriction[1]["ULPROPTAG"] = is_string($propTag) ? $propTag : Logger::_property2json($propTag);
				break;
			case RES_SUBRESTRICTION:
				$restriction[0] = "RES_SUBRESTRICTION";
				$propTag = $restriction[1][ULPROPTAG];
				$res = Logger::_simplifyRestriction($restriction[1][RESTRICTION]);

				unset($restriction[1]);

				$restriction[1]["ULPROPTAG"] = is_string($propTag) ? $propTag : Logger::_property2json($propTag);
				$restriction[1]["RESTRICTION"] = $res;
				break;
		}

		return $restriction;
	}

	/**
	 * Creates a hashmap of all defined mapi properties if it hasn't been created yet.
	 */
	static private function _createMapiPropList()
	{
		if ( isset(Logger::$_mapiPropList) ){
			return;
		}

		Logger::$_mapiPropList = array();
		$constants = get_defined_constants(true);
		foreach ( $constants['user'] as $key=>$value ){
			if ( substr($key,0,3) == 'PR_' ){
				Logger::$_mapiPropList[$key] = $value;
			}
		}
	}

	/**
	 * Creates a hashmap of all defined mapi errors if it hasn't been created yet.
	 */
	static private function _createMapiErrorList()
	{
		if ( isset(Logger::$_mapiErrorList) ){
			return;
		}

		Logger::$_mapiErrorList = array();
		$constants = get_defined_constants(true);
		foreach ( $constants['user'] as $key=>$value ){
			if ( substr($key,0,7) == 'MAPI_E_' ){
				Logger::$_mapiErrorList[$key] = $value;
			}
		}
	}

	/**
	 * Convert a MAPI property tag into a JSON reference
	 *
	 * Note that this depends on the definition of the property tag constants
	 * in mapitags.php
	 *
	 * @example property2json(0x0037001e) => 'PR_SUBJECT'
	 * @param int The property tag
	 * @return string the symbolic name of the property tag
	 */
	static private function _property2json($property)
	{
		if (is_integer($property)) {
			Logger::_createMapiPropList();

			$propertyName = array_search($property, Logger::$_mapiPropList);
			if ( $propertyName ){
				return $propertyName;
			}

			return '0x' . strtoupper(str_pad(Logger::_dechex_32($property), 8, '0', STR_PAD_LEFT));
		}

		return $property;
	}

	static private function _dechex_32($dec){
		// Because on 64bit systems PHP handles integers as 64bit,
		// we need to convert these 64bit integers to 32bit when we
		// want the hex value
		$result = unpack("H*",pack("N", $dec));
		return $result[1];
	}

}
