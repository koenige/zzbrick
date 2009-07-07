<?php 

// zzbrick
// (c) Gustaf Mossakowski, <gustaf@koenige.org> 2009
// comments


function brick_comment($brick) {
	// Comments won't be shown, just for internal purpose
	if (empty($brick['comment'])) $brick['comment'] = '';
	$brick['comment'] .= ' '.implode(" ", $brick['vars']);
	return $brick;
}

?>