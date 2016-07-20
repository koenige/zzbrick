<?php 

/**
 * zzbrick
 * Show content depending on condition
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2009-2016 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * depending on condition, shows the following text or hides it
 * 
 * files: -
 * functions: -
 * settings: -
 * examples
 * 		%%% condition itemcontent %%%
 * 		%%% condition = itemcontent %%%
 * 		%%% condition : %%% -- if !item =, this content will be shown
 * 		%%% condition - %%% -- resume normal operations (end) 
 * 		%%% condition ! %%% -- if !item , this will be shown
 * 		%%% condition itemcontent | itemcontent2 | itemconten2 %%% -- OR
 * 		%%% condition itemcontent & itemcontent2 & itemconten2 %%% -- AND
 * @param array $brick	Array from zzbrick
 * @return array $brick
 */
function brick_condition($brick) {
	static $i;
	if (!$i) $i = 0;

	$if = false;
	if (count($brick['vars']) === 3 AND in_array($brick['vars'][1], array('page', 'setting'))) {
		$if = $brick['vars'][1];
	}
	
	// default translations, cannot be changed
	$brick['setting']['brick_condition_translated']['if'] = '=';
	$brick['setting']['brick_condition_translated']['elseif'] = ':=';
	$brick['setting']['brick_condition_translated']['else'] = ':';
	$brick['setting']['brick_condition_translated']['endif'] = '-';
	$brick['setting']['brick_condition_translated']['unless'] = '!';

	if (in_array($brick['vars'][0], array_keys($brick['setting']['brick_condition_translated']))) {
		$brick['vars'][0] = $brick['setting']['brick_condition_translated'][$brick['vars'][0]];
	}

	$condition = array_shift($brick['vars']);
	if (!in_array($condition, $brick['setting']['brick_condition_translated'])) {
		$condition = '=';
	}

	// check if item is empty or not
	$content = false;
	if ($if) {
		array_shift($brick['vars']);
		$req = brick_format('%%% '.$if.' '.$brick['vars'][0].' %%%', $brick['parameter']);
		$item[$brick['vars'][0]] = $req['text'];
	} elseif (!empty($brick['loop_parameter'])) {
		$item = &$brick['loop_parameter'];
	} else {
		$item = &$brick['parameter'];
	}
	$operator = '';
	if (count($brick['vars']) > 1) {
		// possible: uneven number of brick vars, separated by | = or
		// or & = and, currently no combination possible
		$operator = $brick['vars'][1];
		$brick_vars = array();
		for ($j = 0; $j < count($brick['vars']); $j++) {
			if ($j & 1) continue;
			$brick_vars[] = $brick['vars'][$j];
		}
		$brick['vars'] = array();
	} else {
		$brick_vars[0] = array_shift($brick['vars']);
	}
	foreach ($brick_vars as $brick_var) {
		$brick_var = str_replace('-', '_', $brick_var);
		if (is_array($item) AND isset($item[$brick_var])) {
			// we have it in $item, so return this.
			$content = $item[$brick_var];
			if ($content AND $operator === '|') break;
		} elseif ($operator === '&') {
			$content = '';
			break;
		}
	}

	// check if there it's a nested if and if there is a parent condition
	// if so and it is false, do not show content for all clauses
	if ($condition === '=' OR $condition === '!') {
		$i++; // increase level
		$brick['condition_content_shown'][$i] = false;
	}
	if (isset($brick['condition_show'][$i-1]) AND !$brick['condition_show'][$i-1]) {
		$show = false;
	} else {
		$show = true;
	}
	if ($i === 0) {
		// this means the template is somehow wrong
		$brick['page']['error']['level'] = E_USER_NOTICE;
		$brick['page']['error']['msg_text'] = 'There’s an error in the nesting of conditions in the template `%s`: There are more endifs than ifs.';
		if (!empty($brick['setting']['current_template'])) {
			$brick['page']['error']['msg_vars'] = $brick['setting']['current_template'];
		}
	}

	switch ($condition) {
	case '=': // if
		if (!$show) break;
		if ($content OR is_float($content) OR is_int($content)) {
			$brick['condition_content_shown'][$i] = true;
		} else {
			$brick['condition_content_shown'][$i] = false;
			$show = false;
		}
		break;
	case ':=': // elseif
		if (!$show) break;
		if ($content OR is_float($content) OR is_int($content)) {
			if (empty($brick['condition_content_shown'][$i])) {
				$brick['condition_content_shown'][$i] = true;
			} else {
				// something was already shown in if clause beforehands
				// this is an elseif
				$show = false;
			}
		} else {
			$show = false;
		}
		break;
	case ':': // show content else
		if (!$show) break;
		if (empty($brick['condition_content_shown'][$i])) {
			// nothing was shown yet, so show something
			$brick['condition_content_shown'][$i] = true;
		} else {
			$show = false;
		}
		break;
	case '-':
		unset($brick['condition_content_shown'][$i]);
		unset($brick['condition_show'][$i]);
		// reset show state to last condition if there is one
		$i--;
		break;
	case '!':
		if (!$show) break;
		if (!$content AND !is_float($content) AND !is_int($content)) {
			$brick['condition_content_shown'][$i] = true;
		} else {
			$brick['condition_content_shown'][$i] = false;
			$show = false;
		}
		break;
	}
	
	// almost the same as in brick_language
	if ($show) {
		// save that this branch of condition is to be evaluated
		$brick['condition_show'][$i] = true;
		// reset to old brick_position
		if (!empty($brick['position_old'])) {
			$brick['position'] = $brick['position_old'];
			$brick['position_old'] = '';
		}
		// unblock access
		$brick['access_blocked'] = false;		
	} else {
		// this branch of condition is to be neglected
		$brick['condition_show'][$i] = false;
		// set current position to _hidden_
		if ($brick['position'] != '_hidden_')
			$brick['position_old'] = $brick['position'];
		$brick['position'] = '_hidden_';
		// initialize text at _hidden_ position
		$brick['page']['text'][$brick['position']] = false;
		// block access scripts until this script unblocks access
		$brick['access_blocked'] = 'condition';
	}
	return $brick;
}
