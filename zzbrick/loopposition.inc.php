<?php 

/**
 * zzbrick
 * Content depending on position in loop
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010-2011, 2013, 2016-2017, 2019, 2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * outputs string depending on position in loop
 * 
 * files: -
 * functions: -
 * settings: -
 * examples: 
 * 		%%% loopposition single "blubb" %%% (display if only one item)
 * 		%%% loopposition first "blubb" %%% (display if first item of min. 2)
 * 		%%% loopposition middle|last "blubb" %%% 
 * 		%%% loopposition first|middle "|" %%%
 * 		%%% loopposition %5 "<br>" %%% (all 5 lines)
 * 		%%% loopposition 5 "<br>" %%% (on line 5, counting starting with 1)
 *		%%% loopposition counter %%% returns current line number
 *		%%% loopposition first setting xy %%% returns setting `xy`
 * @param array $brick
 * @return array $brick
 */
function brick_loopposition($brick) {
	// check for loops only

	if (empty($brick['loop_counter'])) return $brick;
	if (count($brick['vars']) < 1) return $brick;
	$positions = explode('|', $brick['vars'][0]);
	$function = NULL;
	if (count($positions) !== 1 OR $positions[0] !== 'counter') {
		// normally, two variables are required
		if (count($brick['vars']) === 3 and $brick['vars'][1] === 'setting')
			$brick['vars'][1] = wrap_setting(array_pop($brick['vars']));
		elseif (count($brick['vars']) === 3 and function_exists(end($brick['vars'])))
			$function = array_pop($brick['vars']);
		if (count($brick['vars']) !== 2) return $brick;
	} else {
		// counter just requires itself as variable
		if (count($brick['vars']) !== 1) return $brick;
	}

	$display = false;
	
	$i = $brick['loop_all'] - $brick['loop_counter'] + 1;
	foreach ($positions as $position) {
		if ($position === 'first' AND $brick['loop_counter'] === $brick['loop_all']
			AND $brick['loop_all'] != 1)
			$display = true;
		elseif ($position === 'single' AND $brick['loop_counter'] === $brick['loop_all']
			AND $brick['loop_all'] === 1)
			$display = true;
		elseif ($position === 'last' AND $brick['loop_counter'] === 1
			AND $brick['loop_all'] != 1)
			$display = true;
		elseif ($position === 'middle' AND $brick['loop_counter'] != 1
			AND $brick['loop_counter'] != $brick['loop_all'])
			$display = true;
		elseif ($position === 'uneven' AND ($i & 1))
			$display = true;
		elseif ($position === 'odd' AND ($i & 1))
			$display = true;
		elseif ($position === 'even' AND !($i & 1))
			$display = true;
		elseif ($position === 'counter')
			$display = $i;
		elseif (is_numeric($position) AND $position.'' === $i.'')
			$display = true;
		elseif (substr($position, 0, 1) === '%') {
			$num = intval(substr($position, 1));
			if (!($i % $num)) $display = true;
		}
	}
	if ($display === false) return $brick;
	if (empty($brick['page']['text'][$brick['position']]))
		$brick['page']['text'][$brick['position']] = [];
	if ($display === true) {
		$brick['page']['text'][$brick['position']][] = $function ? $function($brick['vars'][1]) : $brick['vars'][1];
	} else {
		$brick['page']['text'][$brick['position']][] = $display;
	}
	return $brick;
}
