<?php 

// zzbrick
// (c) Gustaf Mossakowski, <gustaf@koenige.org> 2009
// requests


/**
 * requests function which returns database content in a $page-Array
 * 
 * files: zzbrick_request/_common.inc.php, zzbrick_request/{request}.inc.php
 * functions: cms_{$request}()
 * settings: brick_request_shortcuts
 * example: 
 *		%%% request news %%%
 *		%%% request news * %%% -- URL-parameters take place of asterisk
 *		%%% request news 2004 %%%
 * @param array $brick	Array from zzbrick
 * @return array $brick
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_request($brick) {
	// shortcuts
	if (empty($brick['subtype'])) 
		$brick['subtype'] = '';
	if (empty($brick['setting']['brick_request_shortcuts'])) 
		$brick['setting']['brick_request_shortcuts'] = array();
	if (in_array($brick['subtype'], $brick['setting']['brick_request_shortcuts'])) {
		array_unshift($brick['vars'], $brick['subtype']);
		// to transport additional variables which are needed
		// so %%% image 23 %%% may be as small as possible
		$brick['vars'][] = '*';
	}
	if (file_exists($brick['path'].'/_common.inc.php'))
		require_once $brick['path'].'/_common.inc.php';

	// get parameter for function
	$function_params = brick_request_params($brick['vars'], $brick['parameter']);
	// get name of function to be called
	$script = strtolower(str_replace('-', '_', array_shift($function_params)));

	if (!empty($brick['setting']['brick_request_cms'])) {
		// call function
		$content = brick_request_cms($script, $function_params, $brick);
	} else {
		$request = 'cms_'.$script;

		// include function file and check if function exists
		$script_filename = substr(strtolower($script), 0, strpos($script.'_', '_')).'.inc.php';
		if (file_exists($brick['path'].'/'.$script_filename))
			require_once $brick['path'].'/'.$script_filename;
		if (!function_exists($request)) {
			$brick['error']['level'] = E_USER_WARNING;
			$brick['error']['msg_text'] = 'The function "%s" is not supported by the CMS.';
			$brick['error']['msg_vars'] = array($request);
			return $brick;
		}
		// call function
		$content = $request($function_params);
	}

	if (empty($content)) {
		$brick['text'] = false;
		$brick['page']['status'] = 404;
		return $brick;
	}

	// check if there's some </p>text<p>, remove it for inline results of function
	if (!empty($content['text']) AND !is_array($content['text'])) if (substr($content['text'], 0, 1) != '<' 
		AND substr($content['text'], -1) != '>') {
		///echo substr(trim($brick['text'][$position]), -4);
		if (substr(trim($brick['page']['text'][$brick['position']]), -4) == '</p>') {
			$brick['page']['text'][$brick['position']] 
				= substr(trim($brick['page']['text'][$brick['position']]), 0, -4).' ';
			$brick['cut_next_paragraph'] = true;
		}
	}

	if (!empty($content['replace_db_text'])) {
		$brick['replace_db_text'][$brick['position']] = true;
		$brick['page']['text'][$brick['position']] = '';
	}

	if (!empty($content['text']) AND is_array($content['text'])) {
		foreach (array_keys($content['text']) AS $pos) {
			if (empty($brick['page']['text'][$pos])) 
				$brick['page']['text'][$pos] = '';
			$brick['page']['text'][$pos] .= $content['text'][$pos];
		}
	} elseif (!empty($content['text']))
		$brick['page']['text'][$brick['position']] .= $content['text'];

	// get some content from the function and overwrite existing values
	$overwrite_bricks = array('title', 'dont_show_h1', 'language_link',
		'no_page_head', 'no_page_foot', 'last_update',
		'style', 'breadcrumbs', 'project', 'created', 
		'url_ending', 'no_output', 'template');
	// extra: for all individual needs, not standardized
	foreach ($overwrite_bricks as $part) {
		if (!empty($content[$part]))
			$brick['page'][$part] = $content[$part];	
	}

	// get even more content from the function and merge with existing values
	$merge_bricks = array('authors', 'media', 'head', 'extra', 'meta', 'link');
	foreach ($merge_bricks as $part) {
		if (!empty($content[$part]) AND is_array($content[$part])) {
			if (empty($brick['page'][$part])) $brick['page'][$part] = array();
			$brick['page'][$part] = array_merge($brick['page'][$part], $content[$part]);
		} elseif (!empty($content[$part])) {
			if (empty($brick['page'][$part])) $brick['page'][$part] = '';
			// check if part of that string is already on page, then don't repeat it!
			if (stripos($brick['page'][$part], $content[$part]) === false)
				$brick['page'][$part] .= $content[$part];
		}
	}
	return $brick;
}

/**
 * Merges parameter from brick and form URI
 * 
 * @param array $variables = parameter from %%%-brick
 * @param string $parameter = parameter from URL
 * @return array $parameter_for_function
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_request_params($variables, $parameter) {
	$parameter_for_function = false;
	$var_safe = false;

	foreach ($variables as $var) {
		if ($var == '*') {
			if (!$parameter) continue; // no URL parameters, ignore *
			$url_parameters = explode('/', $parameter);
			if ($parameter_for_function AND count($url_parameters)) {
				$parameter_for_function = array_merge($parameter_for_function, 
				$url_parameters); // parameters transmitted via URL
			// Attention: if there are more than one *-variables
			// parameters will be inserted several times
			} else {
				$parameter_for_function = $url_parameters;
			}
		} else {
			if (substr($var, 0, 1) == '"' && substr($var, -1) == '"')
				$parameter_for_function[] = substr($var, 1, -1);
			elseif (substr($var, 0, 1) == '"')
				$var_safe[] = substr($var, 1);
			elseif ($var_safe && substr($var, -1) != '"') 
				$var_safe[] = $var;
			elseif ($var_safe && substr($var, -1) == '"') {
				$var_safe[] = substr($var, 0, -1);
				$parameter_for_function[] = implode(" ", $var_safe);
				$var_safe = false;
			} elseif ($var) {
				// parameter like given to function but newly indexed
				// ignore empty parameters
				$parameter_for_function[] = $var;
			}
		}
	}

	return $parameter_for_function;
}

/**
 * Replacement for compound cms_-functions, lets you use a webservice
 * 
 * e. g. instead of cms_calendar($params) request is sent to 
 * $data = cms_get_calendar($params) and cms_htmlout_calendar($data, $params)
 * the return value of cms_get-functions might as well be output to xml or json
 * the $data-input array of cms_htmlout-functions might as well be output of
 * getxml- or getjson-functions
 *
 * examples:
 * 		%%% xml news 2004 %%% (xml is alias of 'request')
 * 		%%% json news 2004 %%% (json is alias of 'request')
 * 		%%% request news 2004 %%% (no alias needed)
 *
 * @param string $script - script name ('func') for brick_request
 * @param array $params - parameter from URL
 * @param array $brick - settings for brick-scripts, here:
 *	- brick_cms_input = db, xml, json (defaults to db)
 *	- brick_export_formats = html, xml, json (set via first parameter)
 * @return mixed output of function (html: $page; other cases: direct output, headers
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_request_cms($script, $params, $brick) {
	// brick_cms_input is variable to check where input comes from
	if (empty($brick['setting']['brick_cms_input'])) 
		$brick['setting']['brick_cms_input'] = '';

	// supported export formats
	if (empty($brick['setting']['brick_export_formats']))
		$brick['setting']['brick_export_formats'] = array('html', 'xml', 'json');
	if (!is_array($brick['setting']['brick_export_formats']))
		$brick['setting']['brick_export_formats'] = array($brick['setting']['brick_export_formats']);

	if (in_array($brick['subtype'], $brick['setting']['brick_export_formats'])) {
		$output_format = $brick['subtype'];
	} else {
		$output_format = false;
	}
	$webservice_functions_file = dirname(__FILE__).'/'.$brick['type'].'-webservice.inc.php';
	
	// get data for input, depending on settings
	switch ($brick['setting']['brick_cms_input']) {
	case 'xml':
		require_once $webservice_functions_file;
		$data = brick_request_getxml($script, $params, $brick['setting']);
		break;
	case 'json':
		require_once $webservice_functions_file;
		$data = brick_request_getjson($script, $params, $brick['setting']);
		break;
	case 'db':
	default:
		$request = 'cms_get_'.$script;
		// include function file and check if function exists
		$script_filename = substr(strtolower($script), 0, strpos($script.'_', '_')).'.inc.php';
		if (file_exists($brick['path'].'_get/'.$script_filename))
			require_once $brick['path'].'_get/'.$script_filename;
		if (function_exists($request)) {
			$data = $request($params);
		} else {
			// function does not exist, probably no database data is needed
			$data = true; // do not return a 404
		}
		break;
	}

	// return false, if there's no input
	if (!$data) return false;
	
	// output data, depending on parameter
	switch ($output_format) {
	case 'xml':
		require_once $webservice_functions_file;
		return brick_request_xmlout($script, $data, $params);
	case 'json':
		require_once $webservice_functions_file;
		return brick_request_jsonout($script, $data, $params);
	case 'html':
	default:
		$request = 'cms_htmlout_'.$script;
		// include function file and check if function exists
		$script_filename = substr(strtolower($script), 0, strpos($script.'_', '_')).'.inc.php';
		if (file_exists($brick['path'].'/'.$script_filename))
			require_once $brick['path'].'/'.$script_filename;
		if (!function_exists($request)) {
			$brick['error']['level'] = E_USER_WARNING;
			$brick['error']['msg_text'] = 'The function "%s" is not supported by the CMS.';
			$brick['error']['msg_vars'] = array($request);
			return $brick;
		}
		return $request($data, $params);
	}
}

?>