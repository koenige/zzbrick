<?php 

/**
 * zzbrick
 * Text blocks, translated if applicable
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2009, 2014, 2019, 2021-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * translates text
 * 
 * files: -
 * functions: -
 * settings: -
 * examples: 
 * 		%%% text hello %%% 
 * 		%%% text We like to use our CMS! %%%
 * 		%%% text "We found %d items" item_count %%%
 * 		%%% text "We found %d items" context=msgctxt %%%
 * @param array $brick
 * @return array $brick
 */
function brick_text($brick) {
	$brick = brick_local_settings($brick);
	if (!isset($brick['page']['text'][$brick['position']]))
		$brick['page']['text'][$brick['position']] = [];
	$function = end($brick['vars']);
	if (in_array($function, bricksetting('brick_formatting_functions'))) {
		array_pop($brick['vars']);	
		$function = brick_format_function_prefix($function);
	} else {
		$function = false;
	}
	if (count($brick['vars']) > 1 AND (strstr($brick['vars'][0], ' ') OR !empty($brick['in_quotes']))) {
		$text = array_shift($brick['vars']);
		$sprintf_params = $brick['vars'];
	} else {
		$text = implode(' ', $brick['vars']);
		$sprintf_params = [];
	}
	$matches = [];
	if (strstr($text, '{')) {
		preg_match_all('/(%[a-z0-9$]+){([^}]+)}/', $text, $matches);
		if ($matches) {
			$text = str_replace($matches[0], $matches[1], $text);
			$sprintf_params += $matches[2];
		}
	}
	$text_params = [];
	if (!empty($brick['local_settings']['context']))
		$text_params['context'] = $brick['local_settings']['context']; 

	if ($sprintf_params) {
		if (!empty($brick['loop_parameter'])) {
			$item = &$brick['loop_parameter'];
		} else {
			$item = &$brick['parameter'];
		}
		$params = [];
		$is_setting = false;
		foreach ($sprintf_params as $key) {
			if ($key === 'setting') {
				$is_setting = true;
			} elseif ($is_setting) {
				$params[] = wrap_setting($key);
				$is_setting = false;
			} else {
				if (!isset($item[$key])) continue;
				$params[] = $item[$key];
			}
		}
		if ($function)
			foreach ($params as $index => $param)
				$params[$index] = $function($param);
		$text_params['values'] = $params;
		$text = wrap_text($text, $text_params);
	} else {
		$text = wrap_text($text, $text_params);
		if ($function) $text = $function($text);
	}
	
	$brick['page']['text'][$brick['position']][] = $text;
	unset($brick['vars']);

	return $brick;
}
