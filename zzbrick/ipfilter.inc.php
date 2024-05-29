<?php 

/**
 * zzbrick
 * Access rights depending on user IPv4 address
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2009, 2019, 2023-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * displays content only if user ip address is in allowed range
 * 
 * files: -
 * functions: -
 * settings: brick_ipv4_allowed_range, brick_ipfilter_translated
 * examples: 
 * 		%%% ipfilter %%% -- access allowed for IPs in brick_ipv4_allowed_range
 * 		%%% ipfilter = %%% -- access allowed for IPs in brick_ipv4_allowed_range
 * 		%%% ipfilter {IP from}-{IP to} %%%
 * 		%%% ipfilter = {IP from}-{IP to} %%%
 * 		%%% ipfilter {IP from}-{IP to} {IP from}-{IP to} %%%
 * 		%%% ipfilter = {IP from}-{IP to} {IP from}-{IP to} %%%
 * 		%%% ipfilter : %%% -- if not in range(s), this content will be shown
 * 		%%% ipfilter - %%% -- resume normal operations (end)
 * @param array $brick	Array from zzbrick
 * @return array $brick
 */
function brick_ipfilter($brick) {
	$ipfilter_translated = wrap_setting('brick_ipfilter_translated');
	// default translations, cannot be changed
	$ipfilter_translated['on'] = '=';
	$ipfilter_translated['elseif'] = '=';
	$ipfilter_translated['else'] = ':';
	$ipfilter_translated['off'] = '-';
	// get IP
	$remote_ip = (!empty($_SERVER['REMOTE_ADDR']) ? ip2long($_SERVER['REMOTE_ADDR']) : '');

	if (empty($brick['vars'])) {
		$brick['vars'][0] = '=';
	} elseif (in_array($brick['vars'][0], array_keys($ipfilter_translated))) {
		$brick['vars'][0] = $ipfilter_translated[$brick['vars'][0]];
	}
	
	if ($brick['vars'][0] == '-') {
		$ipfilter = '-';
	} elseif ($brick['vars'][0] == ':') {
		$ipfilter = ':';
	} elseif ($brick['vars'][0] == '=') {
		$ipfilter = '=';
		array_shift($brick['vars']);
	} else {
		$ipfilter = '=';
	}

	$access = true;
	switch ($ipfilter) {
	case '=': // test for ip in range
		$ranges = $brick['vars'];
		if (!$ranges)
			$ranges = wrap_setting('brick_ipv4_allowed_range');
		if (!$ranges) {
			$brick['page']['error']['level'] = E_USER_ERROR;
			$brick['page']['error']['msg_text'] = 'No IP range defined';
			$access = false;
		} else {
			$access = brick_check_if_in_ip_range($remote_ip, $ranges);
			if ($access) $brick['content_shown'] = true;
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
	
	// this part is the same as in rights
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
		// ok, something is forbidden, will not be shown
		// mark it as forbidden, so if nothing will be shown, we can
		// answer with 403 forbidden
		$brick['access_forbidden'] = true;
		// block access scripts until this script unblocks access
		$brick['access_blocked'] = 'ipfilter';
	}
	return $brick;
}

/**
 * checks if given IP is in a range of other IPs
 * 
 * @param string $ip	IPv4 address, separated with .
 * @param array $range	IPv4 addresses
 *		'127.0.0.1-127.0.0.255'
 *		array('127.0.0.1', '127.0.0.255')
 * @return bool true if in range, false if out of range or no ip address given
 */
function brick_check_if_in_ip_range($ip, $ranges) {
	if (!$ip) return false;
	foreach ($ranges as $range) {
		if (is_array($range)) {
			$begin = array_shift($range);
			$end = array_shift($range);
		} else {
			$range = explode('-', $range);
			$begin = $range[0];
			$end = $range[1];
		}
		if ($ip >= ip2long($begin) AND $ip <= ip2long($end)) {
			return true;
		}
	}
	return false;
}
