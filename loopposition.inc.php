<?php 

// zzbrick
// (c) Gustaf Mossakowski, <gustaf@koenige.org> 2010
// string depending on position in loop


/**
 * outputs string depending on position in loop
 * 
 * files: -
 * functions: -
 * settings: -
 * examples: 
 * 		%%% loopcondition first "blubb" %%% 
 * 		%%% loopcondition middle|last "blubb" %%% 
 * 		%%% loopcondition first|middle "|" %%%
 * 		%%% loopcondition %5 "<br>" %%% (all 5 lines)
 * @param array $brick
 * @return array $brick
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_loopposition($brick) {
	// check for loops only

	if (empty($brick['loop_counter'])) return $brick;
	if (count($brick['vars']) != 2) return $brick;

	$display = false;
	$positions = explode('|', $brick['vars'][0]);
	
	$i = $brick['loop_all'] - $brick['loop_counter'] + 1;
	foreach ($positions as $position) {
		if ($position == 'first' AND $brick['loop_counter'] == $brick['loop_all']
			AND $brick['loop_all'] != 1)
			$display = true;
		elseif ($position == 'last' AND $brick['loop_counter'] == 1
			AND $brick['loop_all'] != 1)
			$display = true;
		elseif ($position == 'middle' AND $brick['loop_counter'] != 1
			AND $brick['loop_counter'] != $brick['loop_all'])
			$display = true;
		elseif ($position == 'uneven' AND ($i & 1))
			$display = true;
		elseif ($position == 'odd' AND ($i & 1))
			$display = true;
		elseif ($position == 'even' AND !($i & 1))
			$display = true;
		elseif (substr($position, 0, 1) == '%') {
			$num = intval(substr($position, 1));
			if (!($i % $num)) $display = true;
		}
	}
	if (!$display) return $brick;
	if (empty($brick['page']['text'][$brick['position']]))
		$brick['page']['text'][$brick['position']] = '';
	$brick['page']['text'][$brick['position']] .= $brick['vars'][1];
	return $brick;
}

?>