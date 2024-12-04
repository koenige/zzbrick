<?php 

/**
 * zzbrick
 * Add contents of svg icon
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Add contents of svg icon in folder /icons
 * 
 * files: -
 * functions: -
 * settings: -
 * examples: 
 * 		%%% icon name-of-icon %%% 
 * @param array $brick
 * @return array $brick
 */
function brick_icon($brick) {
	if (count($brick['vars']) !== 1) return '';
	
	$icon = sprintf('icons/%s.svg', $brick['vars'][0]);
	$files = wrap_collect_files($icon, 'files/custom/modules/themes');
	if (!$files) {
		$content = '?';
	} else {
		$file = reset($files);
		$content = file_get_contents($file);
		if (strstr($content, '<?xml version="1.0" encoding="UTF-8"?>'))
			$content = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $content);
		$title = basename($file);
		if (strstr($title, '.'))
			$title = substr($title, 0, strpos($title, '.'));
		if (!strstr('role="img"', $content))
			$content = str_replace('<svg', '<svg role="img" aria-label="'.$title.'"', $content);
		$content = '<span class="svg-title">'.$title.'</span>'.$content;
	}

	$brick['page']['text'][$brick['position']][] = $content;
	unset($brick['vars']);

	return $brick;
}
