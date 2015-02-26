<?php 

/**
 * zzbrick
 * Page templates
 *
 * Part of �Zugzwang Project�
 * http://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright � 2009-2015 Gustaf Mossakowski
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
 * 		%%% page last_update "<p>Page last updated at %s.</p>' %%% 
 * @param array $brick
 * @return array $brick
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_page($brick) {
	if (empty($brick['vars'][0])) return false;
	global $zz_page;
	global $zz_conf;

	$page = &$brick['parameter'];
	$pos = $brick['position'];
	if (!isset($brick['page']['text'][$pos]))
		$brick['page']['text'][$pos] = '';
	$content = false;
	$brick_var = str_replace('-', '_', array_shift($brick['vars']));

	// is there a custom formatting for this?
	// get name of function to be called, similar to brick_request
	$request = false;
	// first check own page-directory
	$paths[] = $brick['path'];
	foreach ($brick['setting']['modules'] as $module) {
		// also check modules in alphabetical order
		$paths[] = $brick['setting']['modules_dir'].'/'.$module.'/'.$brick['module_path'];
	}
	$filename = '/'.basename(strtolower($brick_var)).'.inc.php';
	foreach ($paths as $path) {
		if (!file_exists($script_filename = $path.$filename)) continue;
		require_once $script_filename;
		$request = 'page_'.strtolower($brick_var);
		if (!function_exists($request)) {
			$brick['page']['error']['level'] = E_USER_ERROR;
			$brick['page']['error']['msg_text'] = 'The function "%s" is not supported by the CMS.';
			$brick['page']['error']['msg_vars'] = array($request);
			return $brick;
		}
		break;
	}

	if ($request) {
		// call function
		$content = $request($brick['vars'], $page);
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
		case 'charset':
			$content = $zz_conf['character_set'];
			break;
		case 'year':
			$content = date('Y');
			break;
		case 'base':
		case 'hostname':
		case 'host_base':
			$content = $brick['setting'][$brick_var];
			break;
		case 'url_path':
			$content = $zz_page['url']['full']['path'];
			break;
		case 'project':
			$content = $zz_conf['project'];
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
		$brick['page']['text'][$pos] .= 
			sprintf($brick['vars'][0], $content);
	} else {
		// no formatting or no value
		$brick['page']['text'][$pos] .= $content;
	}
	// write value to parameter for later use with conditions in page template
	if (!is_array($brick['parameter'])) $brick['parameter'] = array($brick['parameter']);
	$brick['parameter'][$brick_var] = $content;
	return $brick;
}
