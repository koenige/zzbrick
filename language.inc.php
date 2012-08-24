<?php 

/**
 * zzbrick
 * Language dependent content
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2009 Gustaf Mossakowski
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
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_language($brick) {
	// if one of the other access modules already blocks access, ignore this brick
	if (!isset($brick['access_blocked'])) $brick['access_blocked'] = false;
	if ($brick['access_blocked'] AND $brick['access_blocked'] != 'language') {
		return $brick;
	}
	global $zz_conf;
	if (empty($brick['vars'][0])) {
		// no language was defined
		$access = true;
	} else {
		if ($brick['vars'][0] == '-') {
			// stop negotiating languages
			$access = true;
		} elseif ($brick['vars'][0] == $brick['setting']['lang']) {
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
		if ($brick['access_blocked'] == 'language') {
			$brick['access_blocked'] = false;		
		}
	} else {
		// set current position to _hidden_
		if ($brick['position'] != '_hidden_')
			$brick['position_old'] = $brick['position'];
		$brick['position'] = '_hidden_';
		// initialize text at _hidden_ position
		$brick['page']['text'][$brick['position']] = false;
		// block access scripts until this script unblocks access
		$brick['access_blocked'] = 'language';
	}
	return $brick;
}

?>