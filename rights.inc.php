<?php 

/**
 * zzbrick
 * Access rights depending on group membership
 *
 * Part of �Zugzwang Project�
 * http://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright � 2009 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * depending on access rights, shows the following text or hides it
 * 
 * files: zzbrick_rights/access_rights.inc.php
 * functions: This function requires a customized function called
 * brick_access_rights 
 * settings: brick_rights_translated
 * examples
 * 		%%% rights "Group 1" %%%
 * 		%%% rights "Group 1" "Group 2" Group-3 %%%
 * 		%%% rights = "Group 1" "Group 2" Group-3 %%%
 *		%%% rights group1 event:* %%% -- will send prefix and URL parameters
 *			as second parameter to brick_access_rights()
 * 		%%% rights : %%% -- if not in group, this content will be shown
 * 		%%% rights - %%% -- resume normal operations (end) 
 * @param array $brick	Array from zzbrick
 * @return array $brick
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_rights($brick) {
	// default translations, cannot be changed
	$brick['setting']['brick_rights_translated']['on'] = '=';
	$brick['setting']['brick_rights_translated']['elseif'] = '=';
	$brick['setting']['brick_rights_translated']['else'] = ':';
	$brick['setting']['brick_rights_translated']['off'] = '-';

	// @todo: what is access_rights?
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
		// is there an asterisk?
		$details = '';
		foreach ($brick['vars'] as $id => $var) {
			if (substr($var, -1) === '*' AND !empty($brick['setting']['url_parameter'])) {
				$details = str_replace('*', $brick['setting']['url_parameter'], $var);
				unset($brick['vars'][$id]);
			}
		}
		$access = brick_access_rights($brick['vars'], $details);
		if ($access) {
			if (empty($brick['content_shown'])) {
				// nothing shown so far, so show this
				$brick['content_shown'] = true;
			} else {
				// something was already shown in if clause beforehands
				// this is an elseif
				$access = false;
			}
		}
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
	
	// almost the same as in brick_language
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
		$brick['page']['text'][$brick['position']] = false;
		// ok, something is forbidden, will not be shown
		// mark it as forbidden, so if nothing will be shown, we can
		// answer with 403 forbidden
		$brick['access_forbidden'] = true; 
		// block access scripts until this script unblocks access
		$brick['access_blocked'] = 'rights';
	}
	return $brick;
}

?>