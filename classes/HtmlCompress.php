<?php
namespace NiceStudio;

class HtmlCompress {
	
	public static function compress(string $content){
		$str = $content;
		$re = '/((?:content=)|(?:"description":\s+))(?:"|\')([A-я\S\s\d\D\X\W\w]+)(?:"|\')/mUi';
		
		$str = preg_replace_callback($re, '\NiceStudio\HtmlCompress::replace', $str);
			
		$str = preg_replace("/<!(--)?(\s+)?(?!\[).*-->/", '', $str);
		$str = preg_replace("/(\s+)?\n(\s+)?/", '', $str);
		$str = preg_filter("/\s+/u", ' ', $str);
		
		$str = preg_replace("/(\xD6\xD6\xD6\xD6)/", "\n", $str);
		return $str;
	}
	
	public static function replace($matches){
		$res = preg_replace('(\r(?:\n)?)', "\xD6\xD6\xD6\xD6", $matches[2]);
		return $matches[1].'"'.$res.'"';
	}
}