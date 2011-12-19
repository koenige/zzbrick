<?php 

// zzbrick
// (c) Gustaf Mossakowski, <gustaf@koenige.org> 2009
// Show content depending on condition


/**
 * depending on condition, shows the following text or hides it
 * 
 * files: -
 * functions: -
 * settings: -
 * examples
 * 		%%% condition itemcontent %%%
 * 		%%% condition = itemcontent %%%
 * 		%%% condition : %%% -- if item = false, this content will be shown
 * 		%%% condition - %%% -- resume normal operations (end) 
 * @param array $brick	Array from zzbrick
 * @return array $brick
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @todo condition = AND OR ...
 */
function brick_condition($brick) {
	// if one of the other access modules already blocks access, ignore this brick
	if (!isset($brick['access_blocked'])) $brick['access_blocked'] = false;
	if ($brick['access_blocked'] AND $brick['access_blocked'] != 'condition') {
		return $brick;
	}
	// default translations, cannot be changed
	$brick['setting']['brick_condition_translated']['if'] = '=';
	$brick['setting']['brick_condition_translated']['elseif'] = '=';
	$brick['setting']['brick_condition_translated']['else'] = ':';
	$brick['setting']['brick_condition_translated']['endif'] = '-';

	if (in_array($brick['vars'][0], array_keys($brick['setting']['brick_condition_translated']))) {
		$brick['vars'][0] = $brick['setting']['brick_condition_translated'][$brick['vars'][0]];
	}

	if ($brick['vars'][0] == '-') {
		$condition = '-';
	} elseif ($brick['vars'][0] == ':') {
		$condition = ':';
	} elseif ($brick['vars'][0] == '=') {
		$condition = '=';
		array_shift($brick['vars']);
	} else {
		$condition = '=';
	}

	// check if item is empty or not
	$content = false;
	if (!empty($brick['loop_parameter'])) {
		$item = &$brick['loop_parameter'];
	} else {
		$item = &$brick['parameter'];
	}
	$brick_var = array_shift($brick['vars']);
	if ($brick_var) {
		$brick_var = str_replace('-', '_', $brick_var);
		if (isset($item[$brick_var])) {
			// we have it in $item, so return this.
			$content = $item[$brick_var];
		}
	}

	$show = true;
	switch ($condition) {
	case '=': // test with custom function
		if ($content) {
			if (empty($brick['content_shown'])) {
				$brick['content_shown'] = true;
			} else {
				// something was already shown in if clause beforehands
				// this is an elseif
				$show = false;
			}
		} else {
			$brick['content_shown'] = false;
			$show = false;
		}
		break;
	case ':': // show content else
		if (empty($brick['content_shown'])) {
			// nothing was shown yet, so show something
			$brick['content_shown'] = true;
			$show = true;
		} else {
			$show = false;
		}
		break;
	case '-':
		unset($brick['content_shown']);
		$show = true;
		break;
	}
	
	// almost the same as in brick_language
	if ($show) {
		// reset to old brick_position
		if (!empty($brick['position_old'])) {
			$brick['position'] = $brick['position_old'];
			$brick['position_old'] = '';
		}
		// unblock access
		if ($brick['access_blocked'] == 'condition') {
			$brick['access_blocked'] = false;		
		}
	} else {
		// set current position to _hidden_
		if ($brick['position'] != '_hidden_')
			$brick['position_old'] = $brick['position'];
		$brick['position'] = '_hidden_';
		// initialize text at _hidden_ position
		$brick['page']['text'][$brick['position']] = false;
		// ok, something is forbidden, will not be shown
		// mark it as forbidden, so if nothing will be shown, we can
		// answer with 403 forbidden
		$brick['access_forbidden'] = true; 
		// block access scripts until this script unblocks access
		$brick['access_blocked'] = 'condition';
	}
	return $brick;
}

?>