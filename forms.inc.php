<?php 

// zzbrick
// (c) Gustaf Mossakowski, <gustaf@koenige.org> 2009
// Domain 'tables' for zzform scripts


/** includes zzform forms
 * 
 * files: zzbrick_forms/{tables}.php, zzbrick_tables/{tables}.php
 * 		it is recommended to put the raw table definitions in zzbrick_tables
 * 		and the more sophisticated table scripts in zzbrick_forms
 * functions: -
 * settings: brick_username_in_session, brick_authentification_file, 
 *		brick_translate_text_function, $zz_conf['dir']
 * examples:
 *		%%% tables * %%% -- URL parameters take place of asterisk
 *		%%% forms webpages %%%
 * This function needs the table definitions in a custom directory
 * either zzbrick_forms or zzbrick_tables
 * For the syntax of the table definition file, read the zzform documentation
 * @param $brick(array)	Array from zzbrick
 * @return $brick
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_forms($brick) {
	global $zz_conf;		// zzform configuration
	global $zz_setting;		// common settings, just for ease of use global definition goes here
	global $zz_access;		// access parameters, just for ease of use global definition goes here
	
	// get username for zzform, logging and errors
	if (empty($brick['setting']['brick_username_in_session']))
		$brick['setting']['brick_username_in_session'] = 'username';
	// for webpages that have not always authentification, require it
	// default: use core/auth.inc.php from zzwrap
	if (!isset($brick['setting']['brick_authentification_file']))
		$brick['setting']['brick_authentification_file'] = $brick['setting']['core'].'/auth.inc.php';
	// to translate error messages, you might use a translation function
	// default: use cms_text() from core/language.inc.php from zzwrap
	if (!isset($brick['setting']['brick_translate_text_function']))
		$brick['setting']['brick_translate_text_function'] = 'cms_text';

	// directory depending on subtype
	if (empty($brick['subtype'])) $brick['subtype'] = 'forms';
	$brick['path'] = substr($brick['path'], 0, -6); // remove _forms
	switch ($brick['subtype']) {
		case 'forms': 
			$brick['path'] .= '_forms'; break;
		default: 
			$brick['path'] .= '_tables'; break;
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
			if (!empty($brick['setting']['brick_authentification_file']))
				require_once $brick['setting']['brick_authentification_file'];
			if (!empty($_SESSION)) $zz_conf['user'] = $_SESSION[$brick['setting']['brick_username_in_session']];
		}
		require_once $zz_conf['dir'].'/zzform.php';
		// TODO: end generalize this part
		require_once $tables;
		$zz_conf['show_output'] = false;
		zzform();
		$brick['page']['text'][$brick['position']] = $zz['output'];
		$brick['page']['title'] = ((!empty($zz_conf['title'])) ? $zz_conf['title'] 
			: ($brick['setting']['brick_translate_text_function'] ? $brick['setting']['brick_translate_text_function']('Error') : 'Error'));
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