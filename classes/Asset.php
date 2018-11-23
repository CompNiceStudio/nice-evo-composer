<?php
namespace NiceStudio;

class Asset {
	
	public static $css = array();
	public static $js = array();
	public static $basePath = '';
	
	private static function initAssets()
	{
		global $modx;
		if(!defined('NICESTUDIO_UTF'))
			define('PSTOOLS_UTF', $modx->config['modx_charset']);
	}
	
	/*
	* Объединение CSS файлов.
	* Относительные пути ресурсов в выходном css файле перезапишутся
	*/
	public static function addCss(\DocumentParser $modx, array $list, string $out_type="content", string $attr = "")
	{
		if(!defined('NICESTUDIO_UTF'))
			self::initAssets();
		if(is_array($list)):
			$content = "";
			$vars = $list;
			foreach($vars as $key=>$val):
				$path_base = MODX_BASE_PATH . str_replace(MODX_BASE_PATH, '', $val);
				$time = 0;
				if(is_file($path_base))
					$time = filemtime($path_base);
				$vars[$key] = $path_base . '?' . $time;
			endforeach;
			
			$cache = 'assets/cache/css/style.'.md5(print_r($vars, true)).'.css';
			
			if(is_file(MODX_BASE_PATH . $cache)):
				$content = file_get_contents(MODX_BASE_PATH . $cache);
			else:
				@mkdir( MODX_BASE_PATH . 'assets/cache/css/', 0777, true );
				foreach($list as $key=>$val):
					$content .= self::fixCssIncludes(self::getContent($val), $val);
				endforeach;
				$content = trim(self::minifyCss($content));
				file_put_contents(MODX_BASE_PATH . $cache, $content);
			endif;
			if($out_type=="content"):
				$out = '<style';
				if($attr){
					if(strlen(trim($attr)) > 0){
						$out .= ' ' . $attr . '>';
					} else {
						$out .= '>';
					}
				}else{
					$out .= '>';
				}
				$out .= $content . "</style>";
			else:
				$out = "<link rel=\"stylesheet\" href=\"/${cache}\" ${attr}>";
			endif;
			return $out;
		endif;
		return '';
	}
	
	public static function clearFolder(string $path = "assets/cache/css")
	{
		$dir = MODX_BASE_PATH . str_replace(MODX_BASE_PATH, "", $path);
		if(is_dir($dir) && is_writable($dir)):
			$directory = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
			$iteartion = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::CHILD_FIRST);
			foreach ( $iteartion as $file ) {
				$file->isDir() ?  rmdir($file) : unlink($file);
			}
			return true;
		endif;
		return false;
	}
	
	public static function setHtaccess(string $path = "")
	{
		$dir = rtrim(MODX_BASE_PATH . str_replace(MODX_BASE_PATH, "", $path), "/");
		if($dir !== MODX_BASE_PATH):
			$content = "order deny,allow".PHP_EOL;
			$content .= "allow from all".PHP_EOL;
			$content .= "Options -Indexes".PHP_EOL;
			@file_put_contents($dir . "/.htaccess", $content);
		endif;
	}
	
	public static function replaceUrlCss(string $url, string $quote, string $path)
	{
		if(strpos($url, "://") !== false || strpos($url, "data:") !== false)
		{
			return $quote.$url.$quote;
		}
		$url = trim(stripslashes($url), "'\" \r\n\t");
		if(substr($url, 0, 1) == "/")
		{
			return $quote.$url.$quote;
		}
		return $quote.$path.'/'.$url.$quote;
	}
	
	private static function fixCssIncludes(string $content, string $path)
	{
		$path = self::getDirectory($path);
		$content = preg_replace_callback(
			'#([;\s:]*(?:url|@import)\s*\(\s*)(\'|"|)(.+?)(\2)\s*\)#si',
			create_function('$matches', 'return $matches[1] . NiceStudio\Asset::replaceUrlCSS($matches[3], $matches[2], "'.addslashes($path).'").")";'),
			$content
		);
		$content = preg_replace_callback(
			'#(\s*@import\s*)([\'"])([^\'"]+)(\2)#si',
			create_function('$matches', 'return $matches[1] . NiceStudio\Asset::replaceUrlCSS($matches[3], $matches[2],"'.addslashes($path).'");'),
			$content
		);
		return $content;
	}
	
	private static function getName(string $path)
	{
		$p = self::getLastPosition($path, "/");
		if ($p !== false)
			return substr($path, $p + 1);
		return $path;
	}
	
	private static function getDirectory(string $path)
	{
		return '/' . substr($path, 0, -strlen(self::getName($path)) - 1);
	}
	
	private static function getLastPosition(string $haystack, string $needle)
	{
		if(!defined(NICESTUDIO_UTF))
			self::initAssets();
		if(NICESTUDIO_UTF):
			$ln = strlen($needle);
			for ($i = strlen($haystack) - $ln; $i >= 0; $i--)
			{
				if (substr($haystack, $i, $ln) == $needle)
				{
					return $i;
				}
			}
			return false;
		endif;
		return strrpos($haystack, $needle);
	}
	
	public static function getContent(string $path)
	{
		$path_base = MODX_BASE_PATH . str_replace(MODX_BASE_PATH, '', $path);
		if(is_file($path_base)):
			$content = @file_get_contents($path_base);
			if($content){
				return $content;
			}
		endif;
		return "";
	}
	
	private static function minifyCss(string $content)
	{
		$content = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content );
		$content = preg_replace( '/(\s\s+|\t|\n)/', ' ', $content );
		$content = preg_replace( array('(( )+{)','({( )+)'), '{', $content );
		$content = preg_replace( array('(( )+})','(}( )+)','(;( )*})'), '}', $content );
		$content = preg_replace( array('(;( )+)','(( )+;)'), ';', $content );
		$content = str_replace(array(', ', ': ', '; ', ' > ', ' }', '} ', ';}', '{ ', ' {'), array(',', ':', ';', '>', '}', '}', '}', '{', '{'), $content);
		return $content;
	}
	
	/*
	* OpenGraph Image
	*/
	public function setOgImage(\DocumentParser $modx, string $image)
	{
		$output = "";
		if(is_file(MODX_BASE_PATH.$image)):
			$one_input = $modx->runSnippet('#thumb',
				array(
					'input'=>$image,
					'options'=>'w=537,h=240,zc=C,bg=ffffff,f=jpg'
				)
			);
			$two_input = $modx->runSnippet('#thumb',
				array(
					'input'=>$image,
					'options'=>'w=400,h=400,zc=C,bg=ffffff,f=jpg'
				)
			);
			$one_input_size = @getimagesize(MODX_BASE_PATH . $one_input);
			$two_input_size = @getimagesize(MODX_BASE_PATH . $two_input);
			$output .= "		<meta property=\"image\" content=\"" . MODX_SITE_URL . $image . "\" />";
			if($one_input_size):
				$output .= "\n";
				$output .= "		<meta property=\"og:image\" content=\"" . MODX_SITE_URL . preg_replace("/(?<!:)\/+/", "/", $one_input) . "\" />\n";
				$output .= "		<meta property=\"og:image:width\" content=\"" . $one_input_size[0] . "\" />\n";
				$output .= "		<meta property=\"og:image:height\" content=\"" . $one_input_size[1] . "\" />\n";
				$output .= "		<meta property=\"og:image:type\" content=\"" . $one_input_size['mime'] . "\" />";
			endif;
			if($two_input_size):
				$output .= "\n";
				$output .= "		<meta property=\"og:image\" content=\"" . MODX_SITE_URL . preg_replace("/(?<!:)\/+/", "/", $two_input) . "\" />\n";
				$output .= "		<meta property=\"og:image:width\" content=\"" . $two_input_size[0] . "\" />\n";
				$output .= "		<meta property=\"og:image:height\" content=\"" . $two_input_size[1] . "\" />\n";
				$output .= "		<meta property=\"og:image:type\" content=\"" . $two_input_size['mime'] . "\" />";
			endif;
		endif;
		return $output;
	}
	
	/*
	* Создание дирректории по id документа c учЄтом родителей
	*/
	public static function createDocIdFolder(\DocumentParser $modx, $id, $parentid)
	{
		$permsFolder = octdec($modx->config['new_folder_permissions']);
		$assetsPath = $modx->config['rb_base_dir'];
		
		$lists = array(str_pad($id, 4, "0", STR_PAD_LEFT));
		
		if($parentid){
			$lists[] = str_pad($parentid, 4, "0", STR_PAD_LEFT);
			self::getParent($modx, $parentid, $lists);
		}
		
		$dir = implode('/', array_reverse($lists));
		
		if(!is_dir($assetsPath."images/".$dir)):
			@mkdir($assetsPath."images/".$dir, $permsFolder, true);
		endif;
		if(!is_dir($assetsPath."files/".$dir)):
			@mkdir($assetsPath."files/".$dir, $permsFolder, true);
		endif;
	}
	
	private static function getParent(\DocumentParser $modx, $id, &$lists)
	{
		$res = $modx->db->select('id,parent', $modx->getFullTableName('site_content'), "id=" . $id);
		while ($row = $modx->db->getRow($res)) {
			if($row['parent']):
				$lists[] = str_pad($row['parent'], 4, "0", STR_PAD_LEFT);
				self::getParent($modx, $row['parent'], $lists);
			endif;
		}
	}
	
	public static function setWaterMark(\DocumentParser $modx, array $params)
	{
		/*
		Array
		(
			[filepath] => /home/a0231929/domains/site.ru/public_html/assets/images/old
			[filename] => about.jpg
		)
		*/
		if (!class_exists('phpthumb'))
			include_once(MODX_BASE_PATH.'assets/snippets/phpthumb/phpthumb.class.php');
		
		$path = $params['filepath'] . '/' . $params['filename'];
		$path_parts = pathinfo($path);
		$params['f'] = strtolower($path_parts['extension']);
		$validate = array('jpg', 'jpeg', 'png', 'bmp');
		$arr = array_map('strtolower', explode(',', $modx->config['upload_images']));
		$assetsPath = str_replace($modx->config['rb_base_dir'], '', $params['filepath']);
		self::debug($params, 'upload.json');
        if (in_array($params['f'], explode(',',$modx->config['upload_images'])) && in_array($params['f'], $validate) && preg_match( '/images\/\d{1,}(\/.+)?/', $assetsPath)) {
			// Наносим знак
			//'fltr[]=wmi|assets/img/water.png|BR|50|5|5'
			//$params['fltr']='wmi|/'.$wmpath.'|'.$x.'x'.$y.'|'.$transparency.'|'.floor($size[0]*0.8).'|'.floor($size[1]*0.8);
			$paramsthumb = array(
				'f'			=> strtolower($path_parts['extension']),
				'filepath'	=> $params['filepath'],
				'filename'	=> $params['filename'],
				'fltr'		=> 'wmi|/' . $params['watermark'] . '|BR|50|20|20'
			);
			$phpThumb = new \phpthumb();
			foreach ($paramsthumb as $key => $value) {
				$phpThumb->setParameter($key, $value);
			}
			$phpThumb->setSourceFilename($path);
			if ($phpThumb->GenerateThumbnail()) {
				$phpThumb->RenderToFile($path);
				$modx->invokeEvent('OnGenerateThumbnail', array('thumbnail' => $path));
			}else {
				$modx->logEvent(0, 3, implode('<br />', $phpThumb->debugmessages), 'phpthumb');
			}
		}
	}
	
	/*
	* Дебаг вывод
	*/
	public static function print_r($args)
	{
		return '<code style="white-space: pre-wrap; display: block;">' . print_r($args, true) . '</code>';
	}
	
	/*
	* Дебаг запись в файл
	*/
	public function debug($args, string $debug = 'debug.json')
	{
		$h = @fopen(MODX_BASE_PATH . $debug, 'a+');
		@fwrite($h, print_r($args, true) . PHP_EOL);
		@fclose($h);
	}
}