<?php
namespace NiceStudio;

class Plugin {
	
	public static function plugin(\DocumentParser $modx)
	{
		$e = &$modx->event;
		$params = &$modx->event->params;
		
		switch($e->name){
			case 'OnWebPageInit':
			case 'OnManagerPageInit':
			case 'OnPageNotFound':
			case 'OnPageUnauthorized':
				\NiceStudio\Snippets::initSnippets($modx);
				break;
			case 'OnFileBrowserUpload':
			case 'OnFileManagerUpload':
				$params['filepath'] = str_replace("\\", "/", $params['filepath']);
				$params['filename'] = str_replace("\\", "/", $params['filename']);
				$params['filename'] = str_replace($params['filepath'] . '/', "", $params['filename']);
				$path = $params['filepath'] . '/' . $params['filename'];
				\NiceStudio\Thumb::optimized($path);
				break;
			case "OnGenerateThumbnail":
				$path = $params['thumbnail'];
				\NiceStudio\Thumb::optimized($path);
				break;
			case 'OnWebPagePrerender':
				$content = $params['documentOutput'];
				$content = \NiceStudio\Obfuscate::obfuscate($content);
				$content = \NiceStudio\HtmlCompress::compress($content);
				$e->output($content);
				break;
			case 'OnEvoFileBrowser':
			case "OnDocFormRender":
			case "OnUserFormRender":
			case "OnWUsrFormRender":
				$browser = $modx->getConfig('which_browser');
				$media_browser = MODX_MANAGER_URL . 'media/browser/' . $browser . '/browse.php';
				$base_dir = str_replace('\\', '/', dirname(__FILE__));
				$dir = str_replace(MODX_BASE_PATH, '/', $dir);
				$js = $dir . "/js/main.js" . (is_file($base_dir . "/js/main.js") ? "?" . filemtime($base_dir . "/js/main.js") : "");
				$css = $dir . "/css/main.css" . (is_file($base_dir . "/css/main.css") ? "?" . filemtime($base_dir . "/css/main.css") : "");
				$out = "<link rel=\"stylesheet\" type=\"text/css\" href=\"/{$css}\">
				<script type=\"text/javascript\">window.filemanageropen_url = \"{$media_browser}\";</script>
				<script type=\"text/javascript\" src=\"/{$js}\"></script>";
				$modx->event->output($out);
				break;
			case 'OnDocFormSave':
			case "OnDocDuplicate":
				$paramsEv = $modx->event->params;
				
				$did = $paramsEv['new_id'] ? (int)$paramsEv['new_id'] : (int)$paramsEv['id'];
				$hid = $paramsEv['new_id'] ? (int)$paramsEv['id'] : $did;
				
				$parent = $modx->getParent($hid, 0);
				$parentId = $parent["id"];
				\NiceStudio\Asset::createDocIdFolder($modx, $did, $parentId);
				break;
		}
	}
	
}