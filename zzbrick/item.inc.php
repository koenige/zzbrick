<?php 

/**
 * zzbrick
 * Items for page templates
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2009-2010, 2019-2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * replaces template brick for HTML page items
 * 
 * the second parameter might be used in case value for first parameter
 * returns something to format the output only if a parameter is returned.
 * the following parameters might be used as pairs of values and formats
 * specifically applied if the values match
 * files: zzbrick_page/{request}.inc.php
 * settings: -
 * examples: 
 * 		%%% item output %%% 
 * 		%%% item output format=markdown %%% 
 * 		%%% item pagetitle %%% 
 * 		%%% item comments "%s comments" %%% 
 * 		%%% item comments "%s comments" | "string if empty" %%% 
 * 		%%% item comments "%s comments" 0 "0 comments" 1 "1 comment" %%% 
 * @param array $brick
 * @return array $brick
 */
function brick_item($brick) {
	$brick = brick_local_settings($brick);
	if (empty($brick['vars'][0])) return false;

	if (!empty($brick['loop_parameter'])) {
		$item = &$brick['loop_parameter'];
	} else {
		$item = &$brick['parameter'];
	}
	// keep the standard position
	$pos = $brick['position'];
	if (!isset($brick['page']['text'][$pos])) $brick['page']['text'][$pos] = [];

	$content = false;
	$brick_var = str_replace('-', '_', array_shift($brick['vars']));

	if (isset($item[$brick_var])) {
		// we have it in $item, so return this.
		$content = $item[$brick_var];
	}
	if ((!empty($brick['vars'][0]) OR !empty($brick['local_settings']['format']))
		AND ($content OR $content === 0 OR $content === '0')) {
		$content = brick_item_format($brick, $content);
		if (!empty($brick['vars'])) {
			// formatting to be done, there is some HTML and a value
			$template = array_shift($brick['vars']);
			$content = brick_item_template($template, $brick['vars'], $content);
		}
	} elseif (count($brick['vars']) === 3 AND $brick['vars'][1] === '|') {
		// allow for (OR)
		if ($content) {
			// condition is true, choose first value (= nothing)
			$content = '';
		} else {
			// condition is false, choose last value
			$content = $brick['vars'][2];
		}
	} else {
		// no formatting or no value
		// just check: can be empty array if array functions are used but there is no value
		if (is_array($content)) $content = implode('', $content);
	}
	$brick['page']['text'][$pos][] = $content;
	return $brick;
}

/**
 * applies format function to content
 *
 * @param array $brick brick array (passed by reference to modify vars)
 * @param string|array $content content to format
 * @return string formatted content
 */
function brick_item_format(&$brick, $content) {
	$format_functions = [];
	if (!empty($brick['local_settings']['format'])) {
		if (!is_array($brick['local_settings']['format']))
			$brick['local_settings']['format'] = [$brick['local_settings']['format']];
		foreach ($brick['local_settings']['format'] as $function) {
			$function = explode(':', $function);
			$function[0] = brick_format_function($function[0]);
			if (!$function[0]) continue;
			$format_functions[] = [
				'function' => array_shift($function),
				'parameter' => $function
			];
		}
	} else {
		// @deprecated first variable might be formatting function
		$format_function = brick_format_function($brick['vars'][0]);
		if ($format_function) {
			array_shift($brick['vars']);
			$format_functions[]['function'] = $format_function;
		}
	}
	if (!$format_functions) return $content;

	// check against percents with space to avoid replacements in URLs
	// there, space is either + or %20
	if (!is_array($content) AND strstr($content, '%%% ') AND !wrap_setting('brick_no_format_inside')) {
		$content = brick_format($content);
		$content = $content['text'];
	}
	foreach ($format_functions as $format_function) {
		if (array_key_exists('parameter', $format_function) AND $format_function['parameter'])
			$content = $format_function['function']($content, ...$format_function['parameter']);
		else
			$content = $format_function['function']($content);
	}
	return $content;
}

/**
 * applies template with value-specific mappings
 *
 * @param string $template sprintf format string
 * @param array $vars remaining variables with key-value pairs
 * @param string|int $content content to format
 * @return string formatted content
 */
function brick_item_template($template, $vars, $content) {
	if (!empty($vars)) {
		// there are special settings for individual values
		$values = [];
		$key = false;
		foreach ($vars as $var) {
			if ($key === false) {
				$key = $var;
			} else {
				$values[$key] = $var;
				$key = false;
			}
		}
		if (!empty($values) AND in_array($content, array_keys($values))) {
			$template = $values[$content];
		}
	}
	return sprintf($template, $content);
}
