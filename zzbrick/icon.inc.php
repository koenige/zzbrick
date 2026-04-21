<?php 

/**
 * zzbrick
 * Add contents of svg icon
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2024-2026 Gustaf Mossakowski
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
 * 		%%% icon field=some_field %%% (value of some_field will be used as filename)
 * @param array $brick
 * @return array $brick
 */
function brick_icon($brick) {
	$brick = brick_local_settings($brick);
	
	if (!$brick['vars'] AND !empty($brick['local_settings']['field'])) {
		if (empty($brick['loop_parameter'][$brick['local_settings']['field']]))
			return $brick;
		$filename = $brick['loop_parameter'][$brick['local_settings']['field']];
	} elseif (count($brick['vars']) !== 1) {
		return $brick;
	} else {
		$filename = $brick['vars'][0];
	}
	
	$icon = sprintf('icons/%s.svg', $filename);
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
		if (!strstr($content, 'role="img"'))
			$content = str_replace('<svg', '<svg role="img" aria-label="'.$title.'"', $content);
		$content = '<span class="svg-title">'.$title.'</span>'.$content;
	}

	$brick['page']['text'][$brick['position']][] = $content;
	unset($brick['vars']);

	return $brick;
}
