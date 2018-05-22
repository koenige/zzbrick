<?php 

/**
 * zzbrick
 * Settings
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2016, 2018 Gustaf Mossakowski
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
	global $zz_page;
	global $zz_conf;
	
	if (empty($brick['vars'][0])) return $brick;
	if (count($brick['vars']) > 2) return $brick;

	$pos = $brick['position'];
	if (!isset($brick['page']['text'][$pos]))
		$brick['page']['text'][$pos] = '';

	$brick_var = str_replace('-', '_', array_shift($brick['vars']));
	$content = '';
	if (array_key_exists($brick_var, $brick['setting']) AND !is_array($brick['setting'][$brick_var])) {
		$content = $brick['setting'][$brick_var];
	} elseif (array_key_exists($brick_var, $zz_conf) AND !is_array($zz_conf[$brick_var])) {
		$content = $zz_conf[$brick_var];
	} elseif (function_exists('wrap_get_setting') AND $s = wrap_get_setting($brick_var)) {
		$content = $s;
	} else {
		// other special cases
		switch ($brick_var) {
		case 'charset':
			$content = $zz_conf['character_set'];
			break;
		}
	}
	if (!empty($brick['vars'][0]) AND $content) {
		// formatting to be done, there is some HTML and a value
		$brick['vars'][0] = brick_translate($brick['vars'][0], $brick['setting']);
		$brick['page']['text'][$pos] .= 
			sprintf($brick['vars'][0], $content);
	} else {
		// no formatting or no value
		$brick['page']['text'][$pos] .= $content;
	}
	
	// write value to parameter for later use with conditions in page template
	if (!is_array($brick['parameter'])) $brick['parameter'] = array($brick['parameter']);
	$brick['parameter'][$brick_var] = $content;
	return $brick;
}
