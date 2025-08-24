<?php 

/**
 * zzbrick
 * display help text(s)
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * display help text(s)
 * 
 * examples: 
 * 		%%% help Name of the help text %%%
 * @param array $brick
 * @return array $brick
 */
function brick_helptext($brick) {
	$brick = brick_local_settings($brick);
	$filename = implode('-', $brick['vars']);
	$filename = strtolower($filename);

	wrap_include('request', 'zzbrick');
	$data = brick_request_data('helptexts', [$filename]);
	if (!$data) return $brick;
	
	$data += $brick['local_settings'];

	$brick['page']['text'][$brick['position']][] = wrap_template('helptext', $data);
	return $brick;
}
