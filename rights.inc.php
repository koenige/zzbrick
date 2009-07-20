<?php 

// zzbrick
// (c) Gustaf Mossakowski, <gustaf@koenige.org> 2009
// Access rights depending on group membership


/** depending on access rights, shows the following text or hides it
 * 
 * files: zzbrick_rights/access_rights.inc.php
 * functions: This function requires a customized function called
 * brick_access_rights 
 * settings: brick_rights_translated
 * examples
 * 		%%% rights "Group 1" %%%
 * 		%%% rights "Group 1" "Group 2" Group-3 %%%
 * 		%%% rights = "Group 1" "Group 2" Group-3 %%%
 * 		%%% rights : %%% -- if not in group, this content will be shown
 * 		%%% rights - %%% -- presume normal operations (end) 
 * @param $brick(array)	Array from zzbrick
 * @return $brick
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_rights($brick) {
	// default translations, cannot be changed
	$brick['setting']['brick_rights_translated']['on'] = '=';
	$brick['setting']['brick_rights_translated']['elseif'] = '=';
	$brick['setting']['brick_rights_translated']['else'] = ':';
	$brick['setting']['brick_rights_translated']['off'] = '-';

	// TODO: what is access_rights?
	require_once $brick['path'].'/access_rights.inc.php';

	if (in_array($brick['vars'][0], array_keys($brick['setting']['brick_rights_translated']))) {
		$brick['vars'][0] = $brick['setting']['brick_rights_translated'][$brick['vars'][0]];
	}

	if ($brick['vars'][0] == '-') {
		$rights = '-';
	} elseif ($brick['vars'][0] == ':') {
		$rights = ':';
	} elseif ($brick['vars'][0] == '=') {
		$rights = '=';
		array_shift($brick['vars']);
	} else {
		$rights = '=';
	}

	$access = true;
	switch ($rights) {
	case '=': // test with custom function
		$access = brick_access_rights($brick['vars']);
		if ($access) $brick['content_shown'] = true;
		break;
	case ':': // show content else
		if (empty($brick['content_shown'])) {
			// nothing was shown yet, so show something
			$brick['content_shown'] = true;
			$access = true;
		} else {
			$access = false;
		}
		break;
	case '-':
		unset($brick['content_shown']);
		$access = true;
		break;
	}
	
	if ($access) {
		// reset to old brick_position
		if (!empty($brick['position_old'])) {
			$brick['position'] = $brick['position_old'];
			$brick['position_old'] = '';
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
	}
	return $brick;
}

?>