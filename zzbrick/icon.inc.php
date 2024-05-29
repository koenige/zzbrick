<?php 

/**
 * zzbrick
 * Add contents of svg icon
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2015, 2019, 2023 Gustaf Mossakowski
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
	$files = wrap_collect_files($icon, 'custom/modules/themes');
	if (!$files) {
		$content = '?';
	} else {
		$file = reset($files);
		$content = file_get_contents($file);
	}

	$brick['page']['text'][$brick['position']][] = $content;
	unset($brick['vars']);

	return $brick;
}
