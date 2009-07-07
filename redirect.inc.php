<?php 

// zzbrick
// (c) Gustaf Mossakowski, <gustaf@koenige.org> 2009
// redirects, done with zz_check_url from zzform scrips


function brick_redirect($brick) {
	global $zz_conf;
	global $zz_setting;

	// import definition of zz_check_url
	require_once $zz_conf['dir'].'/inc/validate.inc.php';
	// test if it's a valid URL
	if (zz_check_url($brick['vars'][0])) {
		if (substr($brick['vars'][0], 0, 1) == '/')
			$brick['vars'][0] = $zz_setting['host_base'].$brick['vars'][0];
		header('Location: '.$brick['vars'][0]);
		exit;
	}
	// ok, it's not a URL, we do not care, return
	// todo: send error
	return $brick;
}

?>