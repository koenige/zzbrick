<?php 

/**
 * zzbrick
 * switch/case
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2024-2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * switch case construct, case part
 * 
 * files: -
 * functions: -
 * settings: -
 * examples: 
 * 		%%% switch field %%%
 *		%%% case value1 %%% content shown if field = value1
 *		%%% case value2 %%% content shown if field = value2
 *		%%% case : %%% content shown in other cases or
 *		%%% default %%% content shown in other cases
 * 		%%% switch end %%% 
 * @param array $brick
 * @return array $brick
 */
function brick_case($brick) {
	if (count($brick['vars']) !== 1) return $brick;

	if ($brick['vars'][0] === $brick['switch']) {
		$brick['position'] = $brick['position_switch'];
		$brick['switch_used'][] = brick_case_id($brick);
	} elseif ($brick['vars'][0] === ':' OR $brick['vars'][0] === 'default') {
		if (in_array(brick_case_id($brick), $brick['switch_used'])) {
			$brick['position'] = '_hidden_';
			// initialize text at _hidden_ position
			$brick['page']['text'][$brick['position']] = [];
		} else {
			$brick['position'] = $brick['position_switch'];
		}
	} else {
		$brick['position'] = '_hidden_';
		// initialize text at _hidden_ position
		$brick['page']['text'][$brick['position']] = [];
	}

	return $brick;
}

function brick_case_id($brick) {
	return sprintf('%s:%s', $brick['switch_field'], $brick['switch']);
}