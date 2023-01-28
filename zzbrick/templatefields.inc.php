<?php 

/**
 * zzbrick
 * Show all fields available in template
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2015, 2019-2020 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * show all fields available in template
 * 
 * files: -
 * functions: -
 * settings: -
 * examples:
 *		%%% templatefields %%%
 * @param array $brick	Array from zzbrick
 * @return array $brick
 */
function brick_templatefields($brick) {
	$fields = brick_templatefields_recursive($brick['parameter']);
	$text = wrap_template('templatefields', $fields);
	echo 'This is for debugging purposes only.<br><br># List of fields:<br>';
	echo '# '.$text;
	echo '<br><br>Real content:<br>';
	echo wrap_print($brick['parameter']);
	exit;
}

function brick_templatefields_recursive($values) {
	$i = 0;
	$keys = [];
	if (!is_array($values)) return $keys;
	$first = key($values);
	if (is_numeric($first)) {
		$values = reset($values);
		$keys[$i]['subkeys'] = '';
		$keys['subkey'] = true;
	}
	foreach ($values as $key => $value) {
		$keys[$i]['key'] = $key;
		if (is_array($value)) {
			$values = reset($value);
			$keys[$i]['subkeys'] = brick_templatefields_recursive($values);
		}
		$i++;
	}
	return $keys;
}
