<?php 

/**
 * zzbrick
 * Positions
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2009, 2019 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * changes current position, and initalizes $text[position]
 * 
 * files: -
 * functions: -
 * settings: -
 * examples:
 *		%%% position A3 %%%
 *		%%% position A1-C3 %%%
 *		%%% position C 3 %%%
 * @param array $brick	Array from zzbrick
 * @return array $brick
 */
function brick_position($brick) {
	$brick['position'] = array_shift($brick['vars']);
	// in case someone put a space between row and column:
	if (!empty($brick['vars']))
		$brick['position'] .= array_shift($brick['vars']);
	if (empty($brick['page']['text'][$brick['position']])) {
		$brick['page']['text'][$brick['position']] = []; // initialisieren
		$brick['replace_db_text'][$brick['position']] = false;
	}
	return $brick;
}
