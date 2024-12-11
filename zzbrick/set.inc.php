<?php 

/**
 * zzbrick
 * change settings
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020, 2023-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * change settings for page
 * 
 * examples: 
 * 		%%% set content_type=txt %%% 
 * @param array $brick
 * @return array $brick
 */
function brick_set($brick) {
	if (empty($brick['vars'][0])) return $brick;
	$brick = brick_local_settings($brick);
	if (empty($brick['local_settings'])) return $brick;
	
	$overwrite_page = ['content_type', 'dont_show_h1', 'template'];
	$overwrite_setting = ['brick_fulltextformat'];
	foreach ($brick['local_settings'] as $key => $value) {
		if (in_array($key, $overwrite_page)) $brick['page'][$key] = $value;
		if (in_array($key, $overwrite_setting)) wrap_setting($key, $value);
	}
	return $brick;
}
