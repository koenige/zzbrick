<?php 

/**
 * zzbrick
 * switch/case
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2024 Gustaf Mossakowski
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
 * 		%%% switch end %%% 
 * @param array $brick
 * @return array $brick
 */
function brick_case($brick) {
	if (count($brick['vars']) !== 1) return $brick;

	if ($brick['position'] === '_hidden_') {
		if ($brick['vars'][0] === $brick['switch'])
			$brick['position'] = $brick['position_switch'];
	} else {
		if ($brick['vars'][0] !== $brick['switch']) {
			$brick['position'] = '_hidden_';
			// initialize text at _hidden_ position
			$brick['page']['text'][$brick['position']] = [];
		}
	}

	return $brick;
}
