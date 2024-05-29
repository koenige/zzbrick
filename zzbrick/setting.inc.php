<?php 

/**
 * zzbrick
 * Settings
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2016, 2018-2024 Gustaf Mossakowski
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
	} elseif (substr($brick_var, 0, 11) === 'zzform_int_') {
		$key = substr($brick_var, 11);
		if (array_key_exists($key, $zz_conf['int'])) {
			$content = $zz_conf['int'][$key];
		} elseif (strpos($key, '_')) {
			$key = explode('_', $key);
			if (count($key) === 2 AND isset($zz_conf['int'][$key[0]][$key[1]]))
				$content = $zz_conf['int'][$key[0]][$key[1]];
		}
	} elseif (is_array($zz_conf) AND array_key_exists($brick_var, $zz_conf) AND !is_array($zz_conf[$brick_var])) {
		$content = $zz_conf[$brick_var];
	} elseif (is_array($zz_conf) AND substr($brick_var, 0, 7) === 'zzform_'
		AND array_key_exists(substr($brick_var, 7) , $zz_conf) AND !is_array($zz_conf[substr($brick_var, 7)])) {
		$content = $zz_conf[substr($brick_var, 7)];
	} elseif ($s = wrap_setting($brick_var)) {
		$content = $s;
	} else {
		// other special cases
		switch ($brick_var) {
		case 'charset':
			$content = wrap_setting('character_set');
			break;
		}
	}
	if (!empty($brick['vars'][0]) AND $content) {
		// formatting to be done, there is some HTML and a value
		$brick['vars'][0] = brick_translate($brick['vars'][0]);
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
