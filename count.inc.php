<?php 

/**
 * zzbrick
 * Count number of items
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2013 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * counts number of items that can be used for a loop
 * 
 * files: -
 * functions: -
 * settings: -
 * example: 
 *		%%% count media %%%
 * @param array $brick	Array from zzbrick
 * @return array
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_count($brick) {
	if (count($brick['vars']) !== 1) return $brick;
	if (empty($brick['parameter'][$brick['vars'][0]])) return $brick;

	$number = count($brick['parameter'][$brick['vars'][0]]);

	$pos = $brick['position'];
	if (!isset($brick['page']['text'][$pos])) $brick['page']['text'][$pos] = '';
	$brick['page']['text'][$pos] .= $number;
	return $brick;
}

?>