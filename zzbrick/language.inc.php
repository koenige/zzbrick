<?php 

/**
 * zzbrick
 * Language dependent content
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2009, 2019, 2023-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * shows content dependent on language
 * 
 * files: -
 * functions: -
 * settings: -
 * examples:
 *		%%% language de %%%
 *		%%% language en %%%
 *		%%% language - %%%
 * @param array $brick	Array from zzbrick
 * @return array $brick
 */
function brick_language($brick) {
	if (empty($brick['vars'][0])) {
		// no language was defined
		$access = true;
	} else {
		if ($brick['vars'][0] === '-') {
			// stop negotiating languages
			$access = true;
		} elseif ($brick['vars'][0] === wrap_setting('lang')) {
			// language block is in correct language
			$access = true;
		} else {
			// language block is in different language
			$access = false;
		}
	}
	// almost the same as in brick_rights
	if ($access) {
		// reset to old brick_position
		if (!empty($brick['position_old'])) {
			$brick['position'] = $brick['position_old'];
			$brick['position_old'] = '';
		}
		// unblock access
		$brick['access_blocked'] = false;		
	} else {
		// set current position to _hidden_
		if ($brick['position'] != '_hidden_')
			$brick['position_old'] = $brick['position'];
		$brick['position'] = '_hidden_';
		// initialize text at _hidden_ position
		$brick['page']['text'][$brick['position']] = [];
		// block access scripts until this script unblocks access
		$brick['access_blocked'] = 'language';
	}
	return $brick;
}
