<?php 

/**
 * zzbrick
 * Page templates
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2009-2016, 2019, 2022 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Replaces template brick for HTML page layout
 * 
 * the second parameter might be used in case value for first parameter
 * returns something to format the output only if a parameter is returned.
 * files: zzbrick_page/{request}.inc.php
 * functions: page_{$request}()
 * settings: -
 * examples: 
 * 		%%% page output %%% 
 * 		%%% page pagetitle %%% 
 * 		%%% page lang %%% 
 * 		%%% page nav %%% 
 * 		%%% page year %%% 
 * 		%%% page last_update "<p>Page last updated at %s.</p>" %%% 
 * @param array $brick
 * @return array $brick
 */
function brick_page($brick) {
	if (empty($brick['vars'][0])) return false;
	global $zz_page;
	
	if (empty($brick['setting']['brick_page_shortcuts']))
		$brick['setting']['brick_page_shortcuts'] = [];
	if (empty($brick['subtype'])) 
		$brick['subtype'] = '';
	if (in_array($brick['subtype'], $brick['setting']['brick_page_shortcuts'])) {
		array_unshift($brick['vars'], $brick['subtype']);
	}

	$page = &$brick['parameter'];
	$pos = $brick['position'];
	if (!isset($brick['page']['text'][$pos]))
		$brick['page']['text'][$pos] = [];
	$content = false;
	$brick_var = str_replace('-', '_', array_shift($brick['vars']));

	// is there a custom formatting for this?
	// get name of function to be called, similar to brick_request
	$request = false;
	// first check own page-directory
	$paths[] = $brick['path'];
	$default_module_present = false;
	foreach ($brick['setting']['modules'] as $module) {
		// also check modules in alphabetical order
		if ($module === 'default') {
			$default_module_present = true;
			continue;
		}
		$paths[] = $brick['setting']['modules_dir'].'/'.$module.'/'.$brick['module_path'];
	}
	if ($default_module_present)
		$paths[] = $brick['setting']['modules_dir'].'/default/'.$brick['module_path'];
	$filename = '/'.basename(strtolower($brick_var)).'.inc.php';
	foreach ($paths as $path) {
		if (!file_exists($script_filename = $path.$filename)) continue;
		require_once $script_filename;
		$request = 'page_'.strtolower($brick_var);
		if (!function_exists($request)) {
			$brick['page']['error']['level'] = E_USER_ERROR;
			$brick['page']['error']['msg_text'] = 'The function "%s" is not supported by the CMS.';
			$brick['page']['error']['msg_vars'] = [$request];
			return $brick;
		}
		break;
	}

	if ($request) {
		// call function
		$content = $request($brick['vars'], $page);
		if (isset($page['media']) AND $page['media'] !== []) {
			$brick['page']['media'] = $page['media'];
		}
	} elseif (!empty($page[$brick_var])) {
		// we have it in $page, so return this.
		if (is_array($page[$brick_var])) {
			$key = array_shift($brick['vars']);
			if (!empty($page[$brick_var][$key])) {
				$content = $page[$brick_var][$key];
			}
		} else {
			$content = $page[$brick_var];
		}
	} else {
		// other special cases
		switch ($brick_var) {
		case 'year':
			$content = date('Y');
			break;
		case 'url_path':
			$content = $zz_page['url']['full']['path'];
			break;
		case 'logged_in':
			if (empty($_SESSION['logged_in'])) $content = false;
			else $content = true;
			break;
		default:
			if (empty($zz_page['db'][$brick_var])) break;
			$content = $zz_page['db'][$brick_var];
			break;
		}
	}
	if (!empty($brick['vars'][0]) AND $content) {
		// formatting to be done, there is some HTML and a value
		$brick['vars'][0] = brick_translate($brick['vars'][0], $brick['setting']);
		$brick['page']['text'][$pos][] = 
			sprintf($brick['vars'][0], $content);
	} else {
		// no formatting or no value
		$brick['page']['text'][$pos][] = $content;
	}
	// write value to parameter for later use with conditions in page template
	if (!is_array($brick['parameter'])) $brick['parameter'] = [$brick['parameter']];
	$brick['parameter'][$brick_var] = $content;
	return $brick;
}
