<?php 

/**
 * zzbrick
 * sections
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2022, 2024-2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * adds a section
 * 
 * files: -
 * functions: -
 * settings: -
 * examples:
 *		%%% section gallery %%%
 *		%%% section downloads %%%
 *		%%% section downloads main %%%
 * @param array $brick	Array from zzbrick
 * @return array $brick
 */
function brick_section($brick) {
	$brick = brick_local_settings($brick);

	$data = $brick['local_settings'];
	$data['section'] = implode(' ', $brick['vars'] ?? []);
	if (!empty($brick['section_close'])) {
		$brick['section_close'] = '';
		$brick['page']['text'][$brick['position']][] = wrap_template('section', ['close' => 1]);
		return $brick;
	} elseif (!empty($brick['section']))
		$brick['page']['text'][$brick['position']][] = wrap_template('section', ['close' => 1]);
	$brick['section'] = $data['section'];
	if ($data['section'] === '-') {
		$brick['section'] = '';
		return $brick;
	}
	if ($data['section'])
		$brick['page']['text'][$brick['position']][] = wrap_template('section', $data);
	else
		$brick['page']['text'][$brick['position']][] = wrap_template('section', ['close' => 1]);
	return $brick;
}
