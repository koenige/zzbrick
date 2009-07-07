<?php 

// zzbrick
// (c) Gustaf Mossakowski, <gustaf@koenige.org> 2009
// positions


function brick_position($brick) {
	$brick['position'] = array_shift($brick['vars']);
	// in case someone put a space between row and column:
	$brick['position'] .= array_shift($brick['vars']);
	if (empty($brick['page']['text'][$brick['position']])) {
		$brick['page']['text'][$brick['position']] = false; // initialisieren
		$brick['replace_db_text'][$brick['position']] = false;
	}
	return $brick;
}

?>