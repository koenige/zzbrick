<?php 

/**
 * zzbrick
 * Settings
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2016 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * returns string value of corresponding setting
 * 
 * examples: 
 * 		%%% setting hostname %%% 
 * 		%%% setting behaviour_path %%% 
 * @param array $brick
 * @return array $brick
 */
function brick_setting($brick) {
	global $zz_page;
	global $zz_conf;
	
	if (empty($brick['vars'][0])) return $brick;
	if (count($brick['vars']) !== 1) return $brick;

	$pos = $brick['position'];
	if (!isset($brick['page']['text'][$pos]))
		$brick['page']['text'][$pos] = '';

	$brick_var = str_replace('-', '_', array_shift($brick['vars']));
	$content = '';
	if (array_key_exists($brick_var, $brick['setting']) AND !is_array($brick['setting'][$brick_var])) {
		$content = $brick['setting'][$brick_var];
	} elseif (array_key_exists($brick_var, $zz_conf) AND !is_array($zz_conf[$brick_var])) {
		$content = $zz_conf[$brick_var];
	} else {
		// other special cases
		switch ($brick_var) {
		case 'charset':
			$content = $zz_conf['character_set'];
			break;
		case 'year':
			$content = date('Y');
			break;
		case 'url_path':
			$content = $zz_page['url']['full']['path'];
			break;
		case 'project':
			$content = $zz_conf['project'];
			break;
		case 'logged_in':
			if (empty($_SESSION['logged_in'])) $content = false;
			else $content = true;
			break;
		default:
			if (empty($zz_page['db'][$brick_var])) break;
			$content = $zz_page['db'][$brick_var];
			break;
		}
	}
	$brick['page']['text'][$pos] .= $content;
	
	// write value to parameter for later use with conditions in page template
	if (!is_array($brick['parameter'])) $brick['parameter'] = array($brick['parameter']);
	$brick['parameter'][$brick_var] = $content;
	return $brick;
}
