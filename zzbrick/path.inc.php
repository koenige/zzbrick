<?php 

/**
 * zzbrick
 * path
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2023-2024, 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * read a path
 * 
 * files: -
 * functions: -
 * settings: -
 * examples:
 *		%%% path area %%%
 *		%%% path area value %%%
 *		%%% path area setting setting_key value %%%
 *		%%% path area value check_rights=0 html="<a href='%s'>" %%%
 *		%%% path area absolute=1 %%%  (absolute URL: host_base + path)
 * @param array $brick	Array from zzbrick
 * @return array $brick
 */
function brick_path($brick) {
	if (!$brick['vars']) {
		$brick['page']['text'][$brick['position']][] = '';
		return $brick;
	}
	$brick = brick_local_settings($brick);

	// first var is area
	$area = array_shift($brick['vars']);

	// get values
	$values = [];
	$i = 0;
	while ($i < count($brick['vars'])) {
		$var = $brick['vars'][$i];
		if ($var === 'setting' AND $i + 1 < count($brick['vars'])) {
			$i++;
			$values[] = wrap_setting($brick['vars'][$i]);
		} elseif (is_array($brick['loop_parameter']) AND array_key_exists($var, $brick['loop_parameter'])) {
			$values[] = $brick['loop_parameter'][$var];
		} elseif (is_array($brick['parameter']) AND array_key_exists($var, $brick['parameter'])) {
			$values[] = $brick['parameter'][$var];
		} else {
			$values[] = $var;
		}
		$i++;
	}

	$settings = [
		'testing' => (!$values and !empty($brick['parameter']['brick_condition_if'])) ? true : false,
		'check_rights' => $brick['local_settings']['check_rights'] ?? true,
		'absolute' => $brick['local_settings']['absolute'] ?? false
	];
	$text = wrap_path($area, $values, $settings);
	if (array_key_exists('html', $brick['local_settings']) AND $text)
		$text = sprintf(trim($brick['local_settings']['html'], '"'), $text);

	$brick['page']['text'][$brick['position']][] = $text;
	return $brick;
}
