<?php 

/**
 * zzbrick
 * change settings
 *
 * Part of �Zugzwang Project�
 * http://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright � 2020 Gustaf Mossakowski
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
	
	$overwrite_allow = ['content_type'];
	foreach (array_keys($brick['local_settings']) as $key) {
		if (!in_array($key, $overwrite_allow)) unset ($brick['local_settings'][$key]);
	}
	
	$brick['page'] = array_merge($brick['page'], $brick['local_settings']);
	return $brick;
}
