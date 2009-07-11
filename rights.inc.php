<?php 

// zzbrick
// (c) Gustaf Mossakowski, <gustaf@koenige.org> 2009
// Access rights


function brick_rights($brick) {
	// TODO: what is access_rights?
	require_once $brick['path'].'/access_rights.inc.php';
	if (!brick_access_rights($brick['vars'])) {
		// set current position to _hidden_
		$brick['position_old'] = $brick['position'];
		$brick['position'] = '_hidden_';
		// initialize text at _hidden_ position
		$brick['page']['text'][$brick['position']] = false;
		// ok, something is forbidden, will not be shown
		// mark it as forbidden, so if nothing will be shown, we can
		// answer with 403 forbidden
		$brick['access_forbidden'] = true; 
	} else {
		// reset to old brick_position
		if (!empty($brick['position_old'])) {
			$brick['position'] = $brick['position_old'];
			$brick['position_old'] = '';
		}
	}
	return $brick;
}

?>