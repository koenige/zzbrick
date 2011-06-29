<?php 

// zzbrick
// (c) Gustaf Mossakowski, <gustaf@koenige.org> 2009-2010
// Domain 'tables' for zzform scripts


/**
 * includes zzform forms
 * 
 * files: zzbrick_forms/{tables}.php, zzbrick_tables/{tables}.php
 * 		it is recommended to put the raw table definitions in zzbrick_tables
 * 		and the more sophisticated table scripts in zzbrick_forms
 * functions: -
 * settings: brick_username_in_session, brick_authentication_file, 
 *		brick_authentication_function, brick_translate_text_function, 
 *		$zz_conf['dir']
 * examples:
 *		%%% tables * %%% -- URL parameters take place of asterisk
 *		%%% tables *[2] %%% -- only 2nd URL parameter take place of asterisk
 *			(this is useful if 1st parameter is language); 
 *			*[2+] second and further parameters
 *		%%% forms webpages %%%
 *		%%% forms webpages public %%% - script does not require authentication
 *		%%% forms * public %%% - script does not require authentication
 * This function needs the table definitions in a custom directory
 * either zzbrick_forms or zzbrick_tables
 * For the syntax of the table definition file, read the zzform documentation
 * @param array $brick	Array from zzbrick
 * @return array $brick
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_forms($brick) {
	global $zz_conf;		// zzform configuration
	global $zz_setting;		// common settings, just for ease of use global definition goes here

	// get username for zzform, logging and errors
	if (empty($brick['setting']['brick_username_in_session']))
		$brick['setting']['brick_username_in_session'] = 'username';
	// for webpages that have not always authentication, require it
	// default: use core/auth.inc.php from zzwrap
	if (!isset($brick['setting']['brick_authentication_file']))
		$brick['setting']['brick_authentication_file'] = $brick['setting']['core'].'/auth.inc.php';
	if (!isset($brick['setting']['brick_authentication_function']))
		$brick['setting']['brick_authentication_function'] = 'wrap_auth';
	// to translate error messages, you might use a translation function
	// default: use wrap_text() from core/language.inc.php from zzwrap
	if (!isset($brick['setting']['brick_translate_text_function']))
		$brick['setting']['brick_translate_text_function'] = 'wrap_text';

	// directory depending on subtype
	if (empty($brick['subtype'])) $brick['subtype'] = 'forms';
	$brick['path'] = substr($brick['path'], 0, -6); // remove _forms
	switch ($brick['subtype']) {
		case 'forms': 
			$brick['path'] .= '_forms'; break;
		default: 
			$brick['path'] .= '_tables'; break;
	}
	
	foreach ($brick['vars'] AS $index => $var) {
		if ($var == '*') {
			// replace * with full parameter list
			// (full list because we either have the scriptname directly
			// in 'vars' or we have the full script name in the url
			// mixing is not possible because it will be difficult
			// to say how many parameters are allowed)
			array_splice($brick['vars'], $index, 1, $brick['parameter']);
		} elseif ($var == '*[1]') {
			$parameter = explode('/', $brick['parameter']);
			array_splice($brick['vars'], $index, 1, $parameter[0]);
		} elseif ($var == '*[2]') {
			$parameter = explode('/', $brick['parameter']);
			array_splice($brick['vars'], $index, 1, $parameter[1]);
		} elseif ($var == '*[3]') {
			$parameter = explode('/', $brick['parameter']);
			array_splice($brick['vars'], $index, 1, $parameter[2]);
		} elseif ($var == '*[2+]') {
			$parameter = explode('/', $brick['parameter']);
			array_shift($parameter); // remove first element
			$parameter = implode('/', $parameter);
			array_splice($brick['vars'], $index, 1, $parameter);
		}
	}

	// check whether script shall be made accessible from public
	$auth = ((count($brick['vars']) > 1) AND end($brick['vars']) == 'public') ? false : true;
	if (!$auth) {
		array_pop($brick['vars']);
		$brick['public_access'] = true;
	}
	
	if (file_exists($brick['path'].'/_common.inc.php'))
		require_once $brick['path'].'/_common.inc.php';

	// script path must be first variable
	$scriptpath = implode('/', $brick['vars']);
	$tables = $brick['path'].'/'.$scriptpath.'.php';
	if (!file_exists($tables)) {
		$tables = $brick['path'].'/'.array_shift($brick['vars']).'.php';
		if (!file_exists($tables)) {
			$tables = $zz_conf['dir'].'/default_tables/database_'.$scriptpath.'.php';
			if (!file_exists($tables)) {
				$tables = $zz_conf['dir'].'/default_tables/'.$scriptpath.'.php';
				if (!file_exists($tables)) {
					$brick['page']['status'] = 404;
					return $brick;
				}
			}
		}
	}

	// start zzform scripts
	if ($auth) {
		if (!empty($brick['setting']['brick_authentication_file'])) {
			require_once $brick['setting']['brick_authentication_file'];
			$brick['setting']['brick_authentication_function']();
		}
		if (!empty($_SESSION) AND empty($zz_conf['user']))
			$zz_conf['user'] = $_SESSION[$brick['setting']['brick_username_in_session']];
	}
	require_once $zz_conf['dir'].'/zzform.php';
	// check if POST is too big, then set GET variables if possible here, so the
	// table script can react to them
	zzform_post_too_big(); 
	require_once $tables;
	if (empty($zz)) {
		// no defintions for zzform, this will not work
		$brick['page']['error']['level'] = E_USER_ERROR;
		$brick['page']['error']['msg_text'] = 'No table definition for zzform found ($zz).';
		$brick['page']['error']['msg_vars'] = array($tables);
		return $brick;
	}
	$zz_conf['show_output'] = false;
	$ops = zzform($zz);

	// Export?
	if (!empty($ops['mode']) AND $ops['mode'] == 'export') {
		// in export mode, there is no html, just pdf, csv or something else
		// output it directly
		foreach ($ops['headers'] as $index) {
			foreach ($index as $bool => $header) {
				header($header, $bool);
			}
		}
		echo $ops['output'];			// Output der Funktion ausgeben
		exit;
	}

	// replace %%% placeholders from zzbrick just in case the whole output
	// goes through brick_format() again
	$ops['output'] = str_replace('%%%', '&#37;&#37;&#37;', $ops['output']);
	$brick['page']['text'][$brick['position']] .= $ops['output'];
	if (!empty($zz_conf['title'])) {
		$brick['page']['title'] = $zz_conf['title'];
		$brick['page']['dont_show_h1'] = true;
	}
	if (!empty($ops['meta'])) $brick['page']['meta'] = $ops['meta'];
	if (!empty($zz_conf['breadcrumbs'])) {
		foreach ($zz_conf['breadcrumbs'] as $breadcrumb) {
			$brick['page']['breadcrumbs'][] = 
				(!empty($breadcrumb['url']) ? '<a href="'.$breadcrumb['url'].'"'
				.(!empty($breadcrumb['title']) ? ' title="'.$breadcrumb['title'].'"' : '')
				.'>' : '')
				.$breadcrumb['linktext'].(!empty($breadcrumb['url']) ? '</a>' : '');
		}
	}
	if (empty($zz_conf['dont_show_title_as_breadcrumb'])
		AND (!empty($brick['page']['title'])))
		$brick['page']['breadcrumbs'][] = $brick['page']['title'];
	return $brick;
}

?>