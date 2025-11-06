<?php 

/**
 * zzbrick
 * server variables
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * show server variables
 * 
 * examples: 
 * 		%%% server HTTP_REFERER %%% 
 * @param array $brick
 * @return array $brick
 */
function brick_server($brick) {
	if (!array_key_exists($brick['vars'][0], $_SERVER)) return $brick;
	
	$brick['page']['text'][$brick['position']][] = $_SERVER[$brick['vars'][0]];
	return $brick;
}
