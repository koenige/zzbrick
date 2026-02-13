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
 *		%%% path area value check_rights=0 html="<a href='%s'>" %%%
 * @param array $brick	Array from zzbrick
 * @return array $brick
 */
function brick_path($brick) {
	if (!$brick['vars']) {
		$brick['page']['text'][$brick['position']][] = '';
		return $brick;
	}

	// first var is area
	$area = array_shift($brick['vars']);

	// check for parameters
	$path_params = [];
	while (strstr(end($brick['vars']), '=')) {
		parse_str(array_pop($brick['vars']), $my_params);
		$path_params += $my_params;
	}

	// get values
	$values = [];
	if (count($brick['vars']) === 2 AND $brick['vars'][0] === 'setting') {
		$values[] = wrap_setting($brick['vars'][1]);
	} else {
		foreach ($brick['vars'] as $var)
			if (is_array($brick['loop_parameter']) AND array_key_exists($var, $brick['loop_parameter']))
				$values[] = $brick['loop_parameter'][$var];
			elseif (is_array($brick['parameter']) AND array_key_exists($var, $brick['parameter']))
				$values[] = $brick['parameter'][$var];
			else
				$values[] = $var;
	}

	$testing = (!$values and !empty($brick['parameter']['brick_condition_if'])) ? true : false;
	$text = wrap_path($area, $values, $path_params['check_rights'] ?? true, $testing);
	if (array_key_exists('html', $path_params) AND $text)
		$text = sprintf(trim($path_params['html'], '"'), $text);

	$brick['page']['text'][$brick['position']][] = $text;
	return $brick;
}
