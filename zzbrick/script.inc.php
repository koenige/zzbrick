<?php 

/**
 * zzbrick
 * script tag
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * adds a script tag
 * 
 * examples:
 *		%%% script /absolute/path/test.js %%%
 *		%%% script library/test.js %%%
 *		%%% script library/test.js defer %%%
 * @param array $brick	Array from zzbrick
 * @return array $brick
 */
function brick_script($brick) {
	static $scripts = [];

	if (!$brick['vars']) return $brick;
	$data['file'] = array_shift($brick['vars']);
	// include each script only once
	if (in_array($data['file'], $scripts)) return $brick;
	$scripts[] = $data['file'];
	if (str_starts_with($data['file'], '/')) $data['absolute_path'] = true;
	$data['attributes'] = implode(' ', $brick['vars']);

	$brick['page']['text'][$brick['position']][] = wrap_template('script', $data);
	return $brick;
}
