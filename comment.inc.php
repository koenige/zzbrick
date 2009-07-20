<?php 

// zzbrick
// (c) Gustaf Mossakowski, <gustaf@koenige.org> 2009
// comments


/** deletes content, puts comment into 'comment'
 * 
 * files: -
 * functions: -
 * settings: -
 * examples: 
 * 		%%% comment here, there and everywhere %%% 
 * 		%%% comment "here, there and everywhere" may stretch multiple lines %%%
 * @return $brick
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