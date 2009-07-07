<?php 

// zzbrick
// (c) Gustaf Mossakowski, <gustaf@koenige.org> 2009
// Domain 'tables' for zzform scripts


function brick_forms($brick) {
	global $zz_conf;		// zzform configuration
	global $zz_setting;		// common settings (?)
	global $zz_access;		// access parameters (?)

	// directory depending on subtype
	if (empty($brick['subtype'])) $brick['subtype'] = '';
	switch ($brick['subtype']) {
		case 'verwaltung': $brick['path'] = dirname($brick['path']).'/verwaltung'; break;
		case 'forms': break;
		case 'publicforms': $brick['path'] = dirname($brick['path']).'/publicforms'; break;
		default: $brick['path'] = dirname($brick['path']).'/db';
	}

	// scriptpath depending on subtype
	if ($brick['subtype'] == 'forms' OR !$brick['parameter']) {
		$scriptpath = array_shift($brick['vars']);
	} elseif (substr($brick['parameter'], -1) == '*') {
		$scriptpath = substr($brick['parameter'], 0, -1);
	} else {
		$scriptpath = $brick['parameter'];
	}

	// start zzform scripts
	if (file_exists($tables = $brick['path'].'/'.$scriptpath.'.php')) {
		// TODO: generalize this part if needed
		// check whether script shall be made accessible from public
		if (empty($brick['vars']) OR array_shift($brick['vars']) != 'public') {
			require_once $zz_setting['core'].'/auth.inc.php';
			if (!empty($_SESSION)) $zz_conf['user'] = $_SESSION['username'];
		}
		require_once $zz_conf['dir'].'/inc/edit.inc.php';
		// TODO: end generalize this part
		require_once $tables;
		$zz_conf['show_output'] = false;
		zzform();
		$brick['page']['text'][$brick['position']] = $zz['output'];
		$brick['page']['title'] = ((!empty($zz_conf['title'])) ? $zz_conf['title'] : cms_text('Error'));
		$brick['page']['breadcrumbs'][] = $brick['page']['title'];
		$brick['page']['dont_show_h1'] = true;
		if ($zz['mode'] == 'export') {
			// in export mode, there is no html, just pdf, csv or something else
			// output it directly
			foreach ($zz['headers'] as $index) {
				foreach ($index as $bool => $header) {
					header($header, $bool);
				}
			}
			echo $zz['output'];			// Output der Funktion ausgeben
			exit;
		}
	} else {
		$brick['page']['status'] = 404;
	}
	return $brick;
}

?>