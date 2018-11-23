<?php
namespace NiceStudio;

class Obfuscate {
	
	public static function ordutf8($string, &$offset_obfus) {
		$code = ord(substr($string, $offset_obfus,1)); 
		if ($code >= 128) {        //otherwise 0xxxxxxx
			if ($code < 224) $bytesnumber = 2;                //110xxxxx
			else if ($code < 240) $bytesnumber = 3;        //1110xxxx
			else if ($code < 248) $bytesnumber = 4;    //11110xxx
			$codetemp = $code - 192 - ($bytesnumber > 2 ? 32 : 0) - ($bytesnumber > 3 ? 16 : 0);
			for ($i = 2; $i <= $bytesnumber; $i++) {
				$offset_obfus ++;
				$code2 = ord(substr($string, $offset_obfus, 1)) - 128;        //10xxxxxx
				$codetemp = $codetemp*64 + $code2;
			}
			$code = $codetemp;
		}
		$offset_obfus += 1;
		if ($offset_obfus >= strlen($string)) $offset_obfus = -1;
		return $code;
	}
	
	public static function obfuscate_replacer(&$matches){
		//-------------------------------
		$str = trim($matches[2]);
		$offset_obfus = 0;
		//-------------------------------
		$str = preg_replace('|&nbsp;|', ' ',$str);
		$arr = explode("<br />", $str);
		$out = array();
		$offset_obfus = 0;
		foreach($arr as $key=>$value){
			$offset_obfus = 0;
			$obfus = "";
			while ($offset_obfus >= 0) {
				$obfus .= "&#".self::ordutf8($value, $offset_obfus).";";
			}
			$out[] = $obfus;
		}
		$html = implode("<br />", $out);
		return $html;
	}
	
	public static function obfuscate($content)
	{
		$regex = "#(\{obfuscate\}(.+)\{\/obfuscate})#Usi";
		return preg_replace_callback($regex, '\NiceStudio\Obfuscate::obfuscate_replacer', $content);
	}
}