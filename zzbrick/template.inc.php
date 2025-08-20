<?php 

/**
 * zzbrick
 * Add contents of template
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2015, 2019, 2023, 2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Add contents of template
 * 
 * files: -
 * functions: -
 * settings: -
 * examples: 
 * 		%%% template name-of-template %%% 
 * 		%%% template name-of-template key=value %%% 
 * @param array $brick
 * @return array $brick
 */
function brick_template($brick) {
	$brick = brick_local_settings($brick);

	if (count($brick['vars']) !== 1) {
		unset($brick['vars']);
		return $brick;
	}

	$brick['page']['text'][$brick['position']][]
		= wrap_template($brick['vars'][0], $brick['local_settings'] ?? []);
	unset($brick['vars']);

	return $brick;
}
