<?php 

// zzbrick
// (c) Gustaf Mossakowski, <gustaf@koenige.org> 2009
// positions


/** changes current position, and initalizes $text[position]
 * 
 * files: -
 * functions: -
 * settings: -
 * examples:
 *		%%% position A3 %%%
 *		%%% position A1-C3 %%%
 *		%%% position C 3 %%%
 * @param $brick(array)	Array from zzbrick
 * @return $brick
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_position($brick) {
	$brick['position'] = array_shift($brick['vars']);
	// in case someone put a space between row and column:
	if (!empty($brick['vars']))
		$brick['position'] .= array_shift($brick['vars']);
	if (empty($brick['page']['text'][$brick['position']])) {
		$brick['page']['text'][$brick['position']] = false; // initialisieren
		$brick['replace_db_text'][$brick['position']] = false;
	}
	return $brick;
}

?>