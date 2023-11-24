<?php 

/**
 * zzbrick
 * classes
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2022 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * adds a class
 * 
 * files: -
 * functions: -
 * settings: -
 * examples:
 *		%%% class quotes %%%
 *		%%% class button %%%
 * @param array $brick	Array from zzbrick
 * @return array $brick
 */
function brick_class($brick) {
	if (!empty($brick['class'])) {
		$brick['page']['text'][$brick['position']][] = wrap_template('class', ['close' => 1]);
		$brick['class'] = NULL;
		return $brick;
	}
	$brick['class'] = $brick['vars'][0] ?? '';
	$brick['page']['text'][$brick['position']][] = wrap_template('class', ['class' => $brick['class']]);
	return $brick;
}
