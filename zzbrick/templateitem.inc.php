<?php 

/**
 * zzbrick
 * Add content of item as a  template
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Add content of item as a  template
 * 
 * files: -
 * functions: -
 * settings: -
 * examples: 
 * 		%%% templateitem fieldkey %%% 
 * @param array $brick
 * @return array $brick
 */
function brick_templateitem($brick) {
	if (count($brick['vars']) !== 1) return $brick;
	if (empty($brick['parameter'][$brick['vars'][0]])) return $brick;

	$template = $brick['parameter'][$brick['vars'][0]];
	unset($brick['parameter'][$brick['vars'][0]]);
	if (strstr($template, '%%%'))
		$brick['page']['text'][$brick['position']][]
			= wrap_template($template, $brick['parameter']);
	else
		$brick['page']['text'][$brick['position']][] = $template;
	unset($brick['vars']);
	
	return $brick;
}
