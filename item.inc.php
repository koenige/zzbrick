<?php 

// zzbrick
// (c) Gustaf Mossakowski, <gustaf@koenige.org> 2009-2010
// page templates


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
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_item($brick) {
	if (empty($brick['vars'][0])) return false;
	global $zz_conf;

	if (!empty($brick['loop_parameter'])) {
		$item = &$brick['loop_parameter'];
	} else {
		$item = &$brick['parameter'];
	}
	// keep the standard position
	$pos = $brick['position'];
	if (!isset($brick['page']['text'][$pos])) $brick['page']['text'][$pos] = '';

	$content = false;
	$brick_var = str_replace('-', '_', array_shift($brick['vars']));

	if (isset($item[$brick_var])) {
		// we have it in $item, so return this.
		$content = $item[$brick_var];
	}
	if (!empty($brick['vars'][0]) AND ($content OR $content === 0 OR $content === '0')) {
		// first variable might be formatting function
		if (!empty($brick['setting']['brick_formatting_functions'])
			AND in_array($brick['vars'][0], $brick['setting']['brick_formatting_functions'])
			AND function_exists($brick['vars'][0])) {
			$format_function = array_shift($brick['vars']);
			$content = $format_function($content);
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
				if (in_array($content, array_keys($values))) {
					$format = $values[$content];
				}
			}
			$format = brick_translate($format);
			$brick['page']['text'][$pos] .= sprintf($format, $content);
		} else {
			$brick['page']['text'][$pos] .= $content;
		}
	} else {
		// allow for (OR)
		if (count($brick['vars']) == 3 AND $brick['vars'][1] == '|')
			$content = $brick['vars'][2];
		// no formatting or no value
		$brick['page']['text'][$pos] .= $content;
	}
	return $brick;
}

?>