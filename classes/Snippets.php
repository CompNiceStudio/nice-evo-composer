<?php
namespace NiceStudio;

class Snippets {
	
	public function initSnippets(\DocumentParser $modx)
	{
		$modx->addSnippet('ogUrl', '\NiceStudio\Snippets::ogUrl');
		$modx->addSnippet('ogImage', '\NiceStudio\Snippets::ogImage');
		$modx->addSnippet('addCss', '\NiceStudio\Snippets::addCss');
		$modx->addSnippet('thumb', '\NiceStudio\Thumb::thumb');
	}
	
	public static function ogUrl(){
		global $modx;
		return $modx->makeUrl($modx->documentIdentifier, '', '', 'full');
	}
	
	public static function addCss($options = array('list', 'type', 'attr'))
	{
		global $modx;
		$list = isset($options['list']) ? explode(',', $options['list']) : array();
		$attr = isset($options['attr']) ? $options['attr'] : '';
		$type = isset($options['type']) ? $options['type'] : 'content';
		return \NiceStudio\Asset::addCss($modx, $list, $type, $attr);
	}
	
	public static function ogImage()
	{
		global $modx;
		$imageOg = $modx->documentObject['ogimage'][1];
		$output = "";
		if(is_file(MODX_BASE_PATH.$imageOg)):
			$one_input = $modx->runSnippet('#thumb',
				array(
					'input'=>$imageOg,
					'options'=>'w=537,h=240,zc=C,bg=ffffff,f=jpg'
				)
			);
			$two_input = $modx->runSnippet('#thumb',
				array(
					'input'=>$imageOg,
					'options'=>'w=400,h=400,zc=C,bg=ffffff,f=jpg'
				)
			);
			$one_input_size = getimagesize(MODX_BASE_PATH.$one_input);
			$two_input_size = getimagesize(MODX_BASE_PATH.$two_input);
			$output = "		<meta name=\"image\" content=\"[(site_url)]".$imageOg."\" />\n";
			$output .= "		<meta property=\"og:image\" content=\"[(site_url)]".$one_input."\" />\n";
			if($one_input_size):
				$output .= "		<meta property=\"og:image:width\" content=\"".$one_input_size[0]."\" />\n";
				$output .= "		<meta property=\"og:image:height\" content=\"".$one_input_size[1]."\" />\n";
				$output .= "		<meta property=\"og:image:type\" content=\"".$one_input_size['mime']."\" />\n";
			endif;
				$output .= "		<meta property=\"og:image\" content=\"[(site_url)]".$two_input."\" />\n";
			if($one_input_size):
				$output .= "		<meta property=\"og:image:width\" content=\"".$two_input_size[0]."\" />\n";
				$output .= "		<meta property=\"og:image:height\" content=\"".$two_input_size[1]."\" />\n";
				$output .= "		<meta property=\"og:image:type\" content=\"".$two_input_size['mime']."\" />\n";
			endif;
		endif;
		return $output;
	}
}