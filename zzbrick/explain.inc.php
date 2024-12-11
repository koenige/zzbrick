<?php 

/**
 * zzbrick
 * Explanation of zzbrick syntax
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2015, 2018-2019 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * allows for explanation of zzbrick syntax
 * 
 * files: -
 * functions: -
 * settings: -
 * examples: 
 * 		%%% explain request test %%% 
 * 		will output %%% request test %%% in HTML
 * @param array $brick
 * @return array $brick
 */
function brick_explain($brick) {
	$pos = $brick['position'];
	if (!isset($brick['page']['text'][$pos])) $brick['page']['text'][$pos] = [];
	foreach ($brick['vars'] as $index => $var) {
		if (strstr($var, ' ')) $brick['vars'][$index] = sprintf('"%s"', $var);
	}
	$brick['page']['text'][$pos][] = '%\%\% '.implode(' ', $brick['vars']).' %\%\%';
	return $brick;
}
