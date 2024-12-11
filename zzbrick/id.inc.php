<?php 

/**
 * zzbrick
 * IDs
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * returns id value for identifier
 * 
 * examples: 
 * 		%%% id categories provider/e-mail %%% 
 * @param array $brick
 * @return array $brick
 */
function brick_id($brick) {
	if (count($brick['vars']) !== 2) {
		$brick['page']['error']['level'] = E_USER_NOTICE;
		$brick['page']['error']['msg_text'] = 'brick id needs two parameters';
		return $brick;
	}
	
	$id = wrap_id($brick['vars'][0], $brick['vars'][1]);
	if (!$id) return $brick; // errors via wrap_id()

	$brick['page']['text'][$brick['position']][] = $id;
	return $brick;
}
