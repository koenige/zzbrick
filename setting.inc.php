<?php 

/**
 * zzbrick
 * Settings
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2016, 2018-2022 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * returns string value of corresponding setting
 * 
 * examples: 
 * 		%%% setting hostname %%% 
 * 		%%% setting behaviour_path %%% 
 * 		%%% setting local_access ".local" %%% 
 * @param array $brick
 * @return array $brick
 */
function brick_setting($brick) {
	global $zz_conf;
	
	if (empty($brick['vars'][0])) return $brick;
	if (count($brick['vars']) > 2) return $brick;

	$pos = $brick['position'];
	if (!isset($brick['page']['text'][$pos]))
		$brick['page']['text'][$pos] = [];

	$brick_var = str_replace('-', '_', array_shift($brick['vars']));
	$content = '';
	if ($brick_var === 'php') {
		$content = !empty(ini_get($brick['vars'][0])) ? ini_get($brick['vars'][0]) : '';
		array_shift($brick['vars']);
	} elseif (array_key_exists($brick_var, $brick['setting']) AND !is_array($brick['setting'][$brick_var])) {
		$content = $brick['setting'][$brick_var];
	} elseif (strstr($brick_var, '[')) {
		// @todo improve this code, check needs to be in separate function
		// @todo support three keys and counting
		$keys = explode('[', $brick_var);
		foreach ($keys as $index => $key) {
			$keys[$index] = rtrim($key, ']');
		}
		if (count($keys) === 2)
			if (isset($brick['setting'][$keys[0]][$keys[1]]))
				$content = $brick['setting'][$keys[0]][$keys[1]];
	} elseif (substr($brick_var, 0, 11) === 'zzform_int_') {
		$key = substr($brick_var, 11);
		if (array_key_exists($key, $zz_conf['int'])) {
			$content = $zz_conf['int'][$key];
		} elseif (strpos($key, '_')) {
			$key = explode('_', $key);
			if (count($key) === 2 AND isset($zz_conf['int'][$key[0]][$key[1]]))
				$content = $zz_conf['int'][$key[0]][$key[1]];
		}
	} elseif (array_key_exists($brick_var, $zz_conf) AND !is_array($zz_conf[$brick_var])) {
		$content = $zz_conf[$brick_var];
	} elseif (substr($brick_var, 0, 7) === 'zzform_'
		AND array_key_exists(substr($brick_var, 7) , $zz_conf) AND !is_array($zz_conf[substr($brick_var, 7)])) {
		$content = $zz_conf[substr($brick_var, 7)];
	} elseif (function_exists('wrap_get_setting') AND $s = wrap_get_setting($brick_var)) {
		$content = $s;
	} else {
		// other special cases
		switch ($brick_var) {
		case 'charset':
			$content = $brick['setting']['character_set'];
			break;
		}
	}
	if (!empty($brick['vars'][0]) AND $content) {
		// formatting to be done, there is some HTML and a value
		$brick['vars'][0] = brick_translate($brick['vars'][0], $brick['setting']);
		$brick['page']['text'][$pos][] = 
			sprintf($brick['vars'][0], $content);
	} else {
		// no formatting or no value
		$brick['page']['text'][$pos][] = $content;
	}
	
	// write value to parameter for later use with conditions in page template
	if (!is_array($brick['parameter'])) $brick['parameter'] = [$brick['parameter']];
	$brick['parameter'][$brick_var] = $content;
	return $brick;
}
