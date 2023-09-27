<?php 

/**
 * zzbrick
 * Text blocks, translated if applicable
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2009, 2014, 2019, 2021-2022 Gustaf Mossakowski
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
 * @param array $brick
 * @return array $brick
 */
function brick_text($brick) {
	// Translate text
	if (!isset($brick['page']['text'][$brick['position']]))
		$brick['page']['text'][$brick['position']] = [];
	$function = end($brick['vars']);
	if (in_array($function, bricksetting('brick_formatting_functions'))) {
		array_pop($brick['vars']);	
		$function = brick_format_function_prefix($function);
	} else {
		$function = false;
	}
	if (strstr($brick['vars'][0], ' ') AND count($brick['vars']) > 1) {
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
		$text = wrap_text($text, ['values' => $params]);
	} else {
		$text = wrap_text($text);
	}
	if ($function) $text = $function($text);
	$brick['page']['text'][$brick['position']][] = $text;
	unset($brick['vars']);

	return $brick;
}
