<?php 

/**
 * zzbrick
 * Items for page templates
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2009-2010, 2019-2025 Gustaf Mossakowski
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
 * 		%%% item pagetitle %%% 
 * 		%%% item comments "%s comments" %%% 
 * 		%%% item comments "%s comments" | "string if empty" %%% 
 * 		%%% item comments "%s comments" 0 "0 comments" 1 "1 comment" %%% 
 * @param array $brick
 * @return array $brick
 */
function brick_item($brick) {
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
	if (!empty($brick['vars'][0]) AND ($content OR $content === 0 OR $content === '0')) {
		// first variable might be formatting function
		if ($format_function = brick_format_function($brick['vars'][0])) {
			array_shift($brick['vars']);
			if (function_exists($format_function)) {
				// check against percents with space to avoid replacements in URLs
				// there, space is either + or %20
				if (!is_array($content) AND strstr($content, '%%% ') AND !wrap_setting('brick_no_format_inside')) {
					$content = brick_format($content);
					$content = $content['text'];
				}
				$content = $format_function($content);
			}
		}
		if (!empty($brick['vars'])) {
			// formatting to be done, there is some HTML and a value
			$format = array_shift($brick['vars']);
			if (!empty($brick['vars'])) {
				// there are special settings for individual values
				$key = false;
				foreach ($brick['vars'] as $var) {
					if ($key === false) $key = $var;
					else {
						$values[$key] = $var;
						$key = false;
					}
				}
				if (!empty($values) AND in_array($content, array_keys($values))) {
					$format = $values[$content];
				}
			}
			$content = sprintf($format, $content);
		}
	} else {
		// allow for (OR)
		if (count($brick['vars']) === 3 AND $brick['vars'][1] === '|') {
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
	}
	$brick['page']['text'][$pos][] = $content;
	return $brick;
}
