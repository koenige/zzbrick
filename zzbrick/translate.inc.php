<?php 

/**
 * zzbrick
 * translate items
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * translate items
 * 
 * files: -
 * functions: -
 * settings: -
 * examples: 
 * 		%%% translate item unit %%% 
 * 		%%% translate item unit context=msgctxt %%%
 * 		%%% translate setting project %%% 
 * @param array $brick
 * @return array $brick
 */
function brick_translate($brick) {
	$brick = brick_local_settings($brick);
	if (!isset($brick['page']['text'][$brick['position']]))
		$brick['page']['text'][$brick['position']] = [];

	if (!$brick['vars']) return $brick;
	if (count($brick['vars']) === 1) $brick['vars'] = array_unshift($brick['vars'], 'item');

	$text_params = [];
	if (!empty($brick['local_settings']['context']))
		$text_params['context'] = $brick['local_settings']['context']; 

	switch ($brick['vars'][0]) {
	case 'item':
		if (!empty($brick['loop_parameter'])) {
			$item = &$brick['loop_parameter'];
		} else {
			$item = &$brick['parameter'];
		}
		$text = $item[$brick['vars'][1]] ?? '';
		if ($text) $text = wrap_text($text, $text_params);
		break;
	case 'setting':
		$text = wrap_setting($brick['vars'][1]);
		if (is_array($text)) $text = '';
		else $text = wrap_text($text, $text_params);
		break;
	}

	$brick['page']['text'][$brick['position']][] = $text;
	unset($brick['vars']);

	return $brick;
}
