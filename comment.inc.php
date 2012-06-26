<?php 

/**
 * zzbrick
 * Comments
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2009 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * deletes content, puts comment into 'comment'
 * 
 * files: -
 * functions: -
 * settings: -
 * examples: 
 * 		%%% comment here, there and everywhere %%% 
 * 		%%% comment "here, there and everywhere" may stretch multiple lines %%%
 * @param array $brick
 * @return array $brick
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_comment($brick) {
	// Comments won't be shown, just for internal purpose
	if (empty($brick['comment'])) $brick['comment'] = '';
	$brick['comment'] .= ' '.implode(" ", $brick['vars']);
	unset($brick['vars']);
	return $brick;
}

?>