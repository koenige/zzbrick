<?php 

/**
 * zzbrick
 * Add contents of template
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2015, 2019, 2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Add contents of template
 * 
 * files: -
 * functions: -
 * settings: brick_template_function
 * examples: 
 * 		%%% template name-of-template %%% 
 * @param array $brick
 * @return array $brick
 */
function brick_template($brick) {
	if (count($brick['vars']) !== 1) return '';

	$brick['page']['text'][$brick['position']][]
		= bricksetting('brick_template_function')($brick['vars'][0]);
	unset($brick['vars']);

	return $brick;
}
