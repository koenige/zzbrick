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
 * switch case construct, switch part
 * 
 * files: -
 * functions: -
 * settings: -
 * examples: 
 * 		%%% switch field %%%
 *		%%% case value1 %%% content shown if field = value1
 *		%%% case value2 %%% content shown if field = value2
 *		%%% default %%% content shown in other cases
 * 		%%% switch end %%% 
 * @param array $brick
 * @return array $brick
 */
function brick_switch($brick) {
	if (count($brick['vars']) !== 1) return $brick;

	switch ($brick['vars'][0]) {
		case 'end':
		case '-':
			$brick['switch'] = NULL;
			$brick['position'] = $brick['position_switch'];
			$brick['position_switch'] = NULL;
			$brick['switch_field'] = NULL;
			break;
		default:
			$brick['switch_used'] = [];
			$brick['switch'] = $brick['parameter'][$brick['vars'][0]]
				?? $brick['loop_parameter'][$brick['vars'][0]] ?? NULL;
			$brick['position_switch'] = $brick['position'];
			$brick['switch_field'] = $brick['vars'][0];
			break;
	}
	
	return $brick;
}
