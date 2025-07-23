<?php 

/**
 * zzbrick
 * link to help text
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * link to help text
 * 
 * examples: 
 * 		%%% helplink Name of the help text %%% 
 * @param array $brick
 * @return array $brick
 */
function brick_helplink($brick) {
	if (!wrap_path('default_helptext', [], true, true)) return $brick;

	$filename = implode('-', $brick['vars']);
	$filename = strtolower($filename);

	wrap_include('request', 'zzbrick');
	$data = brick_request_data('helptexts', [$filename]);
	if (!$data) return $brick;

	$brick['page']['text'][$brick['position']][] = wrap_template('helplink', $data);
	return $brick;
}
