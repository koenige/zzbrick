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
 * switch case construct, switch part
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
function brick_switch($brick) {
	if (count($brick['vars']) !== 1) return $brick;

	switch ($brick['vars'][0]) {
		case 'end':
		case '-':
			$brick['switch'] = NULL;
			$brick['position'] = $brick['position_switch'];
			$brick['position_switch'] = NULL;
			break;
		default:
			$brick['switch'] = $brick['parameter'][$brick['vars'][0]]
				?? $brick['loop_parameter'][$brick['vars'][0]] ?? NULL;
			$brick['position_switch'] = $brick['position'];
			break;
	}
	
	return $brick;
}
