<?php 

/**
 * zzbrick
 * define an item
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * define an item for later use
 * 
 * files: -
 * functions: -
 * settings: -
 * examples: 
 * 		%%% define mail %%% 
 * 		<a href="mailto:%%% setting own_e_mail %%%">%%% setting own_e_mail %%%</a>
 * 		%%% define end %%% 
 * @param array $brick
 * @return array $brick
 */
function brick_define($brick) {
	if (count($brick['vars']) !== 1) return $brick;

	switch ($brick['vars'][0]) {
		case 'end':
			$brick['parameter'][$brick['definition_key']]
				= implode('', $brick['page']['text']['_definition_']);
			unset($brick['definition_key']);
			unset($brick['page']['text']['_definition_']);
			$brick['position'] = $brick['position_old'];
			break;
		default:
			$brick['definition_key'] = $brick['vars'][0];
			$brick['position_old'] = $brick['position'];
			$brick['position'] = '_definition_';
			break;
	}
	
	return $brick;
}
