<?php 

/**
 * zzbrick
 * Include forms via zzform scripts
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2009-2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * includes zzform forms
 * 
 * files: zzbrick_forms/{tables}.php, zzbrick_tables/{tables}.php
 * 		it is recommended to put the raw table definitions in zzbrick_tables
 * 		and the more sophisticated table scripts in zzbrick_forms
 * functions: -
 * settings: brick_authentication_file, brick_authentication_function,
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

	// directory depending on subtype
	if (empty($brick['subtype'])) $brick['subtype'] = 'forms';
	$brick['path'] = substr($brick['path'], 0, -6); // remove _forms
	$brick['tables_path'] = $brick['path'].'_tables';
	switch ($brick['subtype']) {
	case 'forms': 
		$brick['path'] .= '_forms';
		$brick['module_path'] = bricksetting('brick_module_dir').'forms';
		break;
	default: 
		$brick['path'] .= '_tables';
		$brick['module_path'] = bricksetting('brick_module_dir').'tables';
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
	// @deprecated, use public=1 instead, see below
	$auth = ((count($brick['vars']) > 1) AND end($brick['vars']) === 'public') ? false : true;
	if (!$auth) {
		array_pop($brick['vars']);
		$brick['public_access'] = true;
	} elseif ($authentication_file = bricksetting('brick_authentication_file')) {
		require_once $authentication_file;
		bricksetting('brick_authentication_function')();
	}
	
	$brick = brick_local_settings($brick);
	if (!empty($brick['local_settings']['public']))
		$brick['public_access'] = $brick['local_settings']['public'];
	$brick = brick_placeholder_script($brick);
	
	// start zzform scripts
	wrap_include_files('zzform.php', 'zzform');
	// check if POST is too big, then set GET variables if possible here, so the
	// table script can react to them
	zzform_post_too_big();
	$script = array_shift($brick['vars']);
	$zz = zzform_include($script, [], $brick['subtype'], $brick);
	if (!$zz)
		$brick['page']['status'] = 404;
	if (!empty($brick['page']['status']) AND $brick['page']['status'] !== 200)
		return $brick;

	if (!empty($brick['local_settings']))
		$zz += $brick['local_settings'];

	if (!empty($_POST) AND !empty($_POST['httpRequest']) AND substr($_POST['httpRequest'], 0, 6) === 'zzform')
		$brick['page'] = brick_xhr($_POST, $zz);

	// set allowed params
	$brick['page']['query_strings'] = [
		'mode', 'q', 'id', 'source_id', 'scope', 'filter', 'where', 'order',
		'dir', 'delete', 'insert', 'update', 'noupdate', 'zzhash', 'export',
		'add', 'group', 'nolist', 'limit', 'referer', 'file', 'thumbs',
		'field', 'zz', 'focus', 'edit', 'show', 'revise', 'merge'
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

	$ops = zzform($zz);
	$ops = brick_forms_request($brick, $ops, $zz);
	$brick['page'] = brick_merge_page_bricks($brick['page'], $ops['page']);
	
	// Caching
	$uncacheable = [
		'q', 'delete', 'insert', 'update', 'noupdate', 'zzhash', 'mode',
		'thumbs', 'edit', 'show', 'revise', 'merge'
	];
	foreach ($uncacheable as $query) {
		if (!empty($_GET[$query])) {
			bricksetting('cache', false);
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
	
	if (!empty($zz_conf['breadcrumbs'])) {
		foreach ($zz_conf['breadcrumbs'] as $breadcrumb) {
			$brick['page']['breadcrumbs'][] = 
				(!empty($breadcrumb['url']) ? '<a href="'.$breadcrumb['url'].'"'
				.(!empty($breadcrumb['title']) ? ' title="'.$breadcrumb['title'].'"' : '')
				.'>' : '')
				.$breadcrumb['linktext'].(!empty($breadcrumb['url']) ? '</a>' : '');
		}
	}
	if (empty($brick['page']['dont_show_title_as_breadcrumb'])
		AND (!empty($ops['breadcrumb'])))
		$brick['page']['breadcrumbs'][]['title'] = $ops['breadcrumb'];
	return $brick;
}

/**
 * include request functions depending on form
 *
 * @param array $ops
 * @param array $zz
 * @return array
 */
function brick_forms_request($brick, $ops, $zz) {
	// settings are from zzform
	$settings = array_merge($zz, $ops);
	$settings['zz_title'] = $zz['title'];

	// path ends with _request, not _forms
	$brick['path'] = substr($brick['path'], 0, strrpos($brick['path'], '_'));
	$brick['path'] .= '_request';
	$brick['module_path'] = substr($brick['module_path'], 0, strrpos($brick['module_path'], '_'));
	$brick['module_path'] .= '_request';

	// init list of scripts, always add module request scripts	
	if (empty($zz['request'])) $zz['request'] = [];
	array_unshift($zz['request'], 'zzformmap');

	// request data from all scripts
	require_once __DIR__.'/request.inc.php';
	$pages = [];
	foreach ($zz['request'] as $function) {
		$brick = brick_request_file($function, $brick);
		if (empty($brick['request_function'])) continue;
		if (!function_exists($brick['request_function'])) continue;
		$pages[] = $brick['request_function']($brick['vars'], $settings, $zz);
	}
	$text = [];
	foreach ($pages as $page) {
		if (!$page) continue;
		$ops['page'] = brick_merge_page_bricks($ops['page'], $page);
		if (!empty($page['text'])) $text[] = $page['text'];
	}
	if ($text) {
		$ops['output'] = str_replace(
			"<div class='explanation_dynamic'></div>",
			sprintf("<div class='explanation_dynamic'>%s</div>", implode("\n", $text)),
			$ops['output']
		);
	}
	if (!empty($ops['page']['title'])) {
		$ops['output'] = preg_replace('/<h1>(.+?)<\/h1>/'
			, sprintf('<h1>%s</h1>', $ops['page']['title'])
			, $ops['output']
		);
		$ops['title'] = $ops['page']['title'];
	}
	if (!empty($ops['page']['h1'])) {
		$ops['output'] = preg_replace('/<h1>(.+?)<\/h1>/'
			, sprintf('<h1>%s</h1>', $ops['page']['h1'])
			, $ops['output']
		);
	}
	return $ops;
}
