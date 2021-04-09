<?php 

/**
 * zzbrick
 * Include forms via zzform scripts
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2009-2021 Gustaf Mossakowski
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
		$brick['setting']['brick_default_tables'] = [];
	if (!isset($brick['setting']['zzform_colours']))
		$brick['setting']['zzform_colours'] = true;

	// directory depending on subtype
	if (empty($brick['subtype'])) $brick['subtype'] = 'forms';
	$brick['path'] = substr($brick['path'], 0, -6); // remove _forms
	$brick['tables_path'] = $brick['path'].'_tables';
	switch ($brick['subtype']) {
	case 'forms': 
		$brick['path'] .= '_forms';
		$brick['module_path'] = $brick['setting']['brick_module_dir'].'forms';
		break;
	default: 
		$brick['path'] .= '_tables';
		$brick['module_path'] = $brick['setting']['brick_module_dir'].'tables';
		break;
	}
	
	foreach ($brick['vars'] AS $index => $var) {
		if ($var === '*') {
			// replace * with full parameter list
			// (full list because we either have the scriptname directly
			// in 'vars' or we have the full script name in the url
			// mixing is not possible because it will be difficult
			// to say how many parameters are allowed)
			array_splice($brick['vars'], $index, 1, $brick['parameter']);
		} elseif ($var === '*[1]') {
			$parameter = explode('/', $brick['parameter']);
			array_splice($brick['vars'], $index, 1, $parameter[0]);
		} elseif ($var === '*[2]') {
			$parameter = explode('/', $brick['parameter']);
			array_splice($brick['vars'], $index, 1, $parameter[1]);
		} elseif ($var === '*[3]') {
			$parameter = explode('/', $brick['parameter']);
			array_splice($brick['vars'], $index, 1, $parameter[2]);
		} elseif ($var === '*[2+]') {
			$parameter = explode('/', $brick['parameter']);
			array_shift($parameter); // remove first element
			$parameter = implode('/', $parameter);
			array_splice($brick['vars'], $index, 1, $parameter);
		}
	}

	// check whether script shall be made accessible from public
	$auth = ((count($brick['vars']) > 1) AND end($brick['vars']) === 'public') ? false : true;
	if (!$auth) {
		array_pop($brick['vars']);
		$brick['public_access'] = true;
	}
	
	$brick = brick_local_settings($brick);
	
	// script path must be first variable
	$brick = brick_forms_file($brick);
	if (!$brick['form_script_path']) {
		$brick['page']['status'] = 404;
		return $brick;
	}
	
	// start zzform scripts
	if ($auth) {
		if (!empty($brick['setting']['brick_authentication_file'])) {
			require_once $brick['setting']['brick_authentication_file'];
			$brick['setting']['brick_authentication_function']();
		}
		if (!empty($_SESSION) AND empty($zz_conf['user']) AND array_key_exists($brick['setting']['brick_username_in_session'], $_SESSION))
			$zz_conf['user'] = $_SESSION[$brick['setting']['brick_username_in_session']];
	}
	require_once $zz_conf['dir'].'/zzform.php';
	// check if POST is too big, then set GET variables if possible here, so the
	// table script can react to them
	zzform_post_too_big();
	$zz = brick_forms_include($brick);
	if (!empty($brick['page']['status']) AND $brick['page']['status'] !== 200)
		return $brick;

	if (!$zz) {
		// no definitions for zzform, this will not work
		$brick['page']['error']['level'] = E_USER_ERROR;
		$brick['page']['error']['msg_text'] = 'No table definition for zzform found ($zz).';
		$brick['page']['error']['msg_vars'] = [$brick['form_script_path']];
		$brick['page']['status'] = 503;
		return $brick;
	}
	if (!empty($brick['local_settings'])) {
		$zz += $brick['local_settings'];
	}

	if (!empty($_POST) AND !empty($_POST['httpRequest']) AND substr($_POST['httpRequest'], 0, 6) === 'zzform') {
		$brick['page'] = brick_xhr($_POST, $zz);
	}

	// set allowed params
	$brick['page']['query_strings'] = [
		'mode', 'q', 'id', 'source_id', 'scope', 'filter', 'where', 'order',
		'dir', 'delete', 'insert', 'update', 'noupdate', 'zzhash', 'export',
		'add', 'group', 'nolist', 'limit', 'referer', 'file', 'thumbs',
		'field', 'zz', 'focus', 'edit', 'show', 'revise', 'zzmsg'
	];

	if (!empty($_POST) AND !empty($_POST['httpRequest']) AND substr($_POST['httpRequest'], 0, 6) === 'zzform') {
		$text = $brick['page']['text'];
		unset($brick['page']['text']);
		$brick['page']['text'][$brick['position']] = [$text];
		$brick['position'] = '_hidden_'; // hide rest of text
		$brick['page']['text'][$brick['position']] = [];
		$brick['page']['replace_db_text'] = true;
		$brick['page']['url_ending'] = 'ignore';
		$brick['page']['query_strings'][] = 'field_no';
		$brick['page']['query_strings'][] = 'subtable_no';
		$brick['page']['query_strings'][] = 'rec';
		$brick['page']['query_strings'][] = 'unrestricted';
		$brick['page']['query_strings'][] = 'zz_id_value';
		return $brick;
	}

	if (!isset($brick['page']['head'])) $brick['page']['head'] = '';
	if (!empty($zz['page'])) {
		foreach ($zz['page'] as $key => $value) {
			if (empty($brick['page'][$key])) {
				$brick['page'][$key] = $value;
			} elseif (is_array($value)) {
				$brick['page'][$key] = array_merge($brick['page'][$key], $value);
			} else {
				$brick['page'][$key] .= $value;
			}
		}
	}
	$zz_conf['show_output'] = false;
	$ops = zzform($zz);
	if (!empty($ops['html_fragment'])) {
		$brick['page']['template'] = 'empty';
		$brick['page']['url'] = $ops['redirect_url'];
		$brick['page']['send_as_json'] = true;
	}
	
	// Map? Only in list-mode and if there are records
	if (!empty($zz['geo_map_html']) AND $ops['mode'] === 'list_only' AND $ops['records_total']) {
		// set defaults
		if (empty($zz['geo_map_export'])) $zz['geo_map_export'] = 'kml';
		$head_template = !empty($zz['geo_map_head']) ? $zz['geo_map_head'] : 'map';

		// output depending on export format
		switch ($zz['geo_map_export']) {
		case 'kml':
			$brick = brick_forms_geo_map_kml($brick, $head_template);
			break;
		case 'geojson':
			$map['geojson'] = brick_forms_geo_url('geojson');
			$zz['geo_map_html'] = $brick['setting']['brick_template_function']($zz['geo_map_html'], $map);
			$brick['page']['head'] .= $brick['setting']['brick_template_function']($head_template, $map);
			break;
		}
		$ops['output'] = str_replace(
			"<div class='explanation_dynamic'></div>",
			sprintf("<div class='explanation_dynamic'>%s</div>", $zz['geo_map_html']),
			$ops['output']
		);
	}
	
	// Caching
	$uncacheable = [
		'q', 'delete', 'insert', 'update', 'noupdate', 'zzhash', 'mode',
		'thumbs', 'edit', 'show', 'revise', 'zzmsg'
	];
	foreach ($uncacheable as $query) {
		if (!empty($_GET[$query])) {
			$zz_setting['cache'] = false;
			break;
		}
	}

	// replace %%% placeholders from zzbrick just in case the whole output
	// goes through brick_format() again
	$ops['output'] = str_replace('%%%', '&#37;&#37;&#37;', $ops['output']);
	$brick['page']['text'][$brick['position']][] = $ops['output'];
	if (!empty($ops['title'])) {
		$brick['page']['title'] = $ops['title'];
		$brick['page']['dont_show_h1'] = true;
	}
	if (!empty($ops['head'])) $brick['page']['head'] .= $ops['head'];
	if (!empty($ops['meta'])) $brick['page']['meta'] = $ops['meta'];
	if (!empty($ops['status'])) $brick['page']['status'] = $ops['status'];
	if (!empty($ops['error_type'])) $brick['page']['error_type'] = $ops['error_type'];
	
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
 * get table definition filename
*
 * possible notations, table script name must be first parameter always
 * %%% tables table %%%
 * %%% tables module/table %%%
 * %%% tables table parameters %%%
 * %%% tables module/table parameters %%%
 *
 * @deprecated because table script name is second parameter
 * %%% tables subfolder table %%%
 *
 * @param array $brick
 *		array 'vars'
 *		string 'path', defaults to _inc/zzbrick_tables
 *		string 'module_path', defaults to /zzbrick_tables
 *		array 'setting' = $zz_setting
 * @global array $zz_conf
 * @return array $brick, with form_script_path set and vars modified
 */
function brick_forms_file($brick) {
	global $zz_conf;

	if (file_exists($brick['path'].'/_common.inc.php')) {
		$brick['common_script_path'] =  $brick['path'].'/_common.inc.php';
	} elseif (!empty($brick['tables_path']) AND $brick['path'] !== $brick['tables_path']
		AND file_exists($brick['tables_path'].'/_common.inc.php')) {
		$brick['common_script_path'] = $brick['tables_path'].'/_common.inc.php';
	} elseif (!empty($brick['setting']['modules'])) {
		$dir = substr($brick['path'], strrpos($brick['path'], '/'));
		$brick['common_script_path'] = '';
		foreach ($brick['setting']['modules'] as $module) {
			// just get first common script
			if (!file_exists($file = $brick['setting']['modules_dir'].'/'.$module.$dir.'/_common.inc.php')) continue;
			$brick['common_script_path'] = $file;
			break;
		}
	} else {
		$brick['common_script_path'] = '';
	}

	// @deprecated
	// %%% tables subfolder table %%%
	if (count($brick['vars']) === 2) {
		$brick['form_script_path'] = $brick['path'].'/'.implode('/', $brick['vars']).'.php';
		if (file_exists($brick['form_script_path'])) {
			$brick['page']['error']['level'] = E_USER_DEPRECATED;
			$brick['page']['error']['msg_text'] = 'Using two variables `tables %s` is deprecated';
			$brick['page']['error']['msg_vars'] = $brick['vars'];
			$found = false;
			foreach ($brick['vars'] as $var) {
				if (substr($var, 0, 1) === '.') $found = true;
			}
			if (!$found) {
				$brick['vars'] = [];
				return $brick;
			}
		}
	}

	$folder = '';
	$script = array_shift($brick['vars']);

	if (strstr($script, '/') AND substr($script, 0, 3) !== '../') {
		// allow for zzform_multi() with paths, e. g. ../zzbrick_forms/ + script name
		$script = explode('/', $script);
		$folder = array_shift($script);
		$script = implode('/', $script);
	}
	
	$brick['form_script_path'] = $brick['path'].'/'.($folder ? $folder.'/' : '').$script.'.php';
	// allow to use script without recursion
	$backtrace = debug_backtrace();
	foreach ($backtrace as $step) {
		if ($step['file'] === $brick['form_script_path']) $brick['form_script_path'] = false;
	}
	if (file_exists($brick['form_script_path'])) return $brick;
	
	foreach ($brick['setting']['modules'] as $module) {
		if ($folder AND $folder !== $module) continue;
		if ($module === 'default') {
			if (empty($brick['setting']['brick_default_tables'])) continue;
			if ($brick['setting']['brick_default_tables'] !== true AND
				!in_array($script, $brick['setting']['brick_default_tables'])) continue;
		}
		$module_path = $brick['setting']['modules_dir'].'/'.$module.$brick['module_path'];
		$brick['form_script_path'] = $module_path.'/'.$script.'.php';
		if (file_exists($brick['form_script_path'])) {
			if (empty($brick['setting']['active_module']))
				$brick['setting']['active_module'] = $module;
			return $brick;
		}
		if ($module !== 'default') continue;
		// default-module has some database_-prefixed tables
		$brick['form_script_path'] = $module_path.'/database_'.$script.'.php';	
		if (file_exists($brick['form_script_path'])) {
			if (empty($brick['setting']['active_module']))
				$brick['setting']['active_module'] = $module;
			return $brick;
		}
	}
	$brick['form_script_path'] = '';
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
function brick_forms_geo_map_kml($brick, $template) {
	global $zz_conf;
	global $zz_setting;

	// get map URL
	$map['kml_url'] = brick_forms_geo_url('kml');

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

/**
 * get own zzform URL stripped from unnecessary parts for map export
 *
 * @param string $type type of export
 * @global array $zz_conf
 * @return string $map_url
 */
function brick_forms_geo_url($type = 'kml') {
	global $zz_conf;

	$url = parse_url($_SERVER['REQUEST_URI']);
	$map_url = $url['path'];
	if (!empty($url['query'])) {
		parse_str($url['query'], $query);
		// set no limit, default for export is to show all records
		unset($query['limit']);
		unset($query['referer']);
		unset($query['delete']);
	}
	$query['export'] = $type;
	$map_url .= '?'.str_replace('&amp;', '&', http_build_query($query));
	return $map_url;
}

/**
 * include a table definition file
 * with just $zz_conf, $zz_setting and $brick available for reading, $zz for
 * writing
 *
 * $brick is referenced because in common_script and form_script, $brick
 * might be changed
 *
 * @param array $brick
 * @return array $zz (or false, if no definitions available)
 */
function brick_forms_include(&$brick) {
	global $zz_conf;
	global $zz_setting;

	if ($brick['common_script_path']) {
		require_once $brick['common_script_path'];
	}
	require $brick['form_script_path'];
	if (empty($zz) AND !empty($zz_sub))
		return $zz_sub;
	if (!empty($zz))
		return $zz;

	// no definitions for zzform, this will not work
	return false;
}
