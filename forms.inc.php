<?php 

/**
 * zzbrick
 * Include forms via zzform scripts
 *
 * Part of �Zugzwang Project�
 * http://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright � 2009-2010 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


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
	// format HTML templates
	if (!isset($brick['setting']['brick_template_function']))
		$brick['setting']['brick_template_function'] = 'wrap_template';
	// allow default tables for inclusion, on demand only
	if (!isset($brick['setting']['brick_default_tables']))
		$brick['setting']['brick_default_tables'] = array();

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
		if (!file_exists($tables) AND ($brick['setting']['brick_default_tables'] === true
			OR in_array($scriptpath, $brick['setting']['brick_default_tables']))) {
			$tables = $zz_conf['dir'].'/default_tables/database_'.$scriptpath.'.php';
			if (!file_exists($tables)) {
				$tables = $zz_conf['dir'].'/default_tables/'.$scriptpath.'.php';
				if (!file_exists($tables)) {
					$brick['page']['status'] = 404;
					return $brick;
				}
			}
		} elseif (!file_exists($tables)) {
			$brick['page']['status'] = 404;
			return $brick;
		}
	}
	
	// set allowed params
	$brick['page']['query_strings'] = array('mode', 'q', 'id', 'source_id', 
		'scope', 'filter', 'where', 'order', 'dir', 'zzaction', 'zzhash',
		'export', 'add', 'group', 'nolist', 'limit', 'referer', 'file');

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
	require $tables;
	if (!empty($brick['page']['status']) AND $brick['page']['status'] !== 200)
		return $brick;

	if (empty($zz) AND !empty($zz_sub)) {
		$zz = $zz_sub;
		unset($zz_sub);
	} elseif (empty($zz)) {
		// no defintions for zzform, this will not work
		$brick['page']['error']['level'] = E_USER_ERROR;
		$brick['page']['error']['msg_text'] = 'No table definition for zzform found ($zz).';
		$brick['page']['error']['msg_vars'] = array($tables);
		$brick['page']['status'] = 503;
		return $brick;
	}
	$zz_conf['show_output'] = false;
	$ops = zzform($zz);
	
	// Map? Only in list-mode and if there are records
	if (!empty($zz['geo_map_html']) AND $ops['mode'] === 'list_only' AND $ops['records_total']) {
		$ops['output'] = str_replace(
			"<div class='explanation_dynamic'></div>",
			sprintf("<div class='explanation_dynamic'>%s</div>", $zz['geo_map_html']),
			$ops['output']
		);
		$template = !empty($zz['geo_map_template']) ? $zz['geo_map_template'] : 'map';
		$brick = brick_forms_geo_map($brick, $template);
	}
	
	// Caching
	$uncacheable = array('q', 'zzaction', 'zzhash', 'mode');
	foreach ($uncacheable as $query) {
		if (!empty($_GET[$query])) {
			$zz_setting['cache'] = false;
			break;
		}
	}

	// Export?
	// @todo: allow caching
	if (!empty($ops['mode']) AND $ops['mode'] == 'export') {
		// in export mode, there is no html, just pdf, csv or something else
		// output it directly
		foreach ($ops['headers'] as $index) {
			foreach ($index as $bool => $header) {
				header($header, $bool);
			}
		}
		echo $ops['output'];
		exit;
	}

	// replace %%% placeholders from zzbrick just in case the whole output
	// goes through brick_format() again
	$ops['output'] = str_replace('%%%', '&#37;&#37;&#37;', $ops['output']);
	$brick['page']['text'][$brick['position']] .= $ops['output'];
	if (!empty($ops['title'])) {
		$brick['page']['title'] = $ops['title'];
		$brick['page']['dont_show_h1'] = true;
	}
	if (!empty($ops['meta'])) $brick['page']['meta'] = $ops['meta'];
	if (!empty($ops['status'])) $brick['page']['status'] = $ops['status'];
	
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

/**
 * outputs map based on a corresponding KML file to the table output
 * (e. g. an OpenLayers map)
 * HTML output goes into {$template}.template.txt
 *
 * @param array $brick
 * @param string $template name of map template, defaults to map
 * @global array $zz_conf
 * @global array $zz_setting
 *		*_maps_api_key will be made available for map template
 * @return array $brick;
 */
function brick_forms_geo_map($brick, $template) {
	global $zz_conf;
	global $zz_setting;

	// get map URL
	$url = parse_url($_SERVER['REQUEST_URI']);
	$map['kml_url'] = $url['path'];
	if (!empty($url['query'])) {
		parse_str($url['query'], $query);
		if (isset($query['limit']) AND !$query['limit']) {
			// don't set limit = 0 as this is default for export
			unset($query['limit']);
		} elseif (!isset($query['limit']))  {
			$query['limit'] = $zz_conf['limit'];
		}
	} else {
		// no limit = default limit
		$query['limit'] = $zz_conf['limit'];
	}
	$query['export'] = 'kml';
	$map['kml_url'] .= '?'.str_replace('&amp;', '&', http_build_query($query));

	// set maps API key if needed
	foreach ($zz_setting as $key => $value) {
		if (substr($key, -13) !== '_maps_api_key') continue;
		$map[$key] = $value;
	}

	if (!isset($brick['page']['head'])) $brick['page']['head'] = '';
	$brick['page']['head'] .= $brick['setting']['brick_template_function']($template, $map);
	$brick['page']['extra']['body_attributes'] = ' onload="init()"';
	return $brick;
}

?>