<?php 

/**
 * zzbrick
 * Add contents of template
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2015 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Add contents of template
 * 
 * files: -
 * functions: -
 * settings: -
 * examples: 
 * 		%%% template name-of-template %%% 
 * @param array $brick
 * @return array $brick
 */
function brick_template($brick) {
	// to print out a template, you need a template function
	// default: use wrap_template() from core/page.inc.php from zzwrap
	if (!isset($brick['setting']['brick_template_function']))
		$brick['setting']['brick_template_function'] = 'wrap_template';
	if (count($brick['vars']) !== 1) return '';

	$brick['page']['text'][$brick['position']] 
		.= $brick['setting']['brick_template_function']($brick['vars'][0]);
	unset($brick['vars']);

	return $brick;
}
