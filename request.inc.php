<?php 

/**
 * zzbrick
 * Requests
 *
 * Part of �Zugzwang Project�
 * http://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright � 2009-2012, 2014 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


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
 *	- brick_export_formats = html, xml, json
 * @return array $brick
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_request($brick) {
	// shortcuts
	if (empty($brick['subtype'])) 
		$brick['subtype'] = '';
	if (empty($brick['setting']['brick_request_shortcuts'])) 
		$brick['setting']['brick_request_shortcuts'] = array();
	if (empty($brick['setting']['brick_request_url_params'])) 
		$brick['setting']['brick_request_url_params'] = array();
	if (in_array($brick['subtype'], $brick['setting']['brick_request_shortcuts'])) {
		array_unshift($brick['vars'], $brick['subtype']);
		// to transport additional variables which are needed
		// so %%% image 23 %%% may be as small as possible
		if (in_array($brick['subtype'], $brick['setting']['brick_request_url_params'])) {
			$brick['vars'][] = '*';
		}
	}
	// supported export formats
	if (empty($brick['setting']['brick_export_formats'])) {
		$brick['setting']['brick_export_formats'] = array(
			'html', 'xml', 'json', 'csv'
		);
	}
	if (!is_array($brick['setting']['brick_export_formats'])) {
		$brick['setting']['brick_export_formats'] = array($brick['setting']['brick_export_formats']);
	}
	if (file_exists($brick['path'].'/_common.inc.php'))
		require $brick['path'].'/_common.inc.php';

	// get parameter for function
	$filetype = '';
	if (!empty($brick['setting']['brick_request_cms'])
		AND preg_match('/(.+)\.([a-z0-9]+)/i', $brick['setting']['url_parameter'], $matches)) {
		// use last part behind dot as file extension
		if (count($matches) === 3 AND in_array($matches[2], $brick['setting']['brick_export_formats'])) {
			$brick['setting']['url_parameter'] = $matches[1];
			$filetype = $matches[2];
		}
	}
	$function_params = brick_request_params($brick['vars'], $brick['setting']['url_parameter']);
	$script = array_shift($function_params);

	if (!empty($brick['setting']['brick_request_cms'])) {
		// call function
		$content = brick_request_cms($script, $function_params, $brick, $filetype);
	} else {
		$request = brick_request_file($script, $brick);

		if (!function_exists($request)) {
			$brick['page']['error']['level'] = E_USER_ERROR;
			$brick['page']['error']['msg_text'] = 'The function "%s" is not supported by the CMS.';
			$brick['page']['error']['msg_vars'] = array($request);
			$brick['text'] = false;
			return $brick;
		}
		// call function
		$content = $request($function_params);
	}

	if (empty($content)) {
		$brick['text'] = false;
		$brick['page']['status'] = 404;
		return $brick;
	} elseif (!is_array($content)) {
		// a space or so might have been returned to show, no, we do not
		// want a 404 here, it's just a secondary block
		$brick['text'] = false;
		return $brick;
	}

	// check if there's some </p>text<p>, remove it for inline results of function
	if (!empty($content['text']) AND !is_array($content['text'])) {
		if (substr($content['text'], 0, 1) != '<' AND substr($content['text'], -1) != '>') {
			if (substr(trim($brick['page']['text'][$brick['position']]), -4) == '</p>') {
				$brick['page']['text'][$brick['position']] 
					= substr(trim($brick['page']['text'][$brick['position']]), 0, -4).' ';
				$brick['cut_next_paragraph'] = true;
			}
		}
	}

	if (!empty($content['replace_db_text'])) {
		// hide previous textblocks
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

	if (!empty($content['replace_db_text'])) {
		// hide next textblocks
		$brick['position'] = '_hidden_';
		$brick['page']['text'][$brick['position']] = '';
	}

	// get some content from the function and overwrite existing values
	$overwrite_bricks = array('title', 'dont_show_h1', 'language_link',
		'no_page_head', 'no_page_foot', 'last_update',
		'style', 'breadcrumbs', 'project', 'created', 'headers',
		'url_ending', 'no_output', 'template', 'content_type', 'status');
	// extra: for all individual needs, not standardized
	foreach ($overwrite_bricks as $part) {
		if (!empty($content[$part]))
			$brick['page'][$part] = $content[$part];	
	}

	// get even more content from the function and merge with existing values
	$merge_bricks = array('authors', 'media', 'head', 'extra', 'meta', 'link',
		'error', 'query_strings');
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
 * Replacement for compound cms_-functions, lets you use content syndication
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
 * @return mixed output of function (html: $page; other cases: direct output, headers
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_request_cms($script, $params, $brick, $filetype = '') {
	// brick_cms_input is variable to check where input comes from
	if (empty($brick['setting']['brick_cms_input'])) 
		$brick['setting']['brick_cms_input'] = '';

	if (empty($brick['setting']['syndication_function'])) {
		$brick['setting']['syndication_library'] = '/zzwrap/syndication.inc.php';
		$brick['setting']['syndication_function'] = 'wrap_syndication_get';
	}

	if (in_array($brick['subtype'], $brick['setting']['brick_export_formats'])) {
		$output_format = $brick['subtype'];
	} elseif ($filetype AND in_array($filetype, $brick['setting']['brick_export_formats'])) {
		$output_format = $filetype;
	} else {
		$output_format = false;
	}
	
	// get data for input, depending on settings
	$request = brick_request_file($script, $brick, 'get');
	if (function_exists($request)) {
		$data = $request($params);
	} else {
		// function does not exist, probably no database data is needed
		switch ($brick['setting']['brick_cms_input']) {
		case 'xml':
		case 'json':
			$data = brick_request_external($script, $brick['setting'], $params);
			break;
		case 'db':
			$data = true; // do not return a 404
			break;
		default:
			break;
		}
	}

	// return false, if there's no input
	if (empty($data)) return false;

	if ($data === true AND !empty($request)) {
		switch ($output_format) {
		case 'xml':
		case 'json':
		case 'csv':
			$content['error']['level'] = E_USER_WARNING;
			$content['error']['msg_text'] = 'No input data for %s was found. Probably function `%s` is missing.';
			$content['error']['msg_vars'] = array($script, $request);
			$content['status'] = 404;
			return $content;
			break;
		}
	}

	if (is_array($data) AND array_key_exists('_filename', $data)) {
		if (array_key_exists('_extension', $data)) {
			$extension = $data['_extension'];
			unset($data['_extension']);
		} else {
			$extension = $output_format;
		}
		$filename = $data['_filename'].'.'.$extension;
		unset($data['_filename']);
	} else {
		if (strstr($script, '/')) {
			$script = explode('/', $script);
			$script = array_pop($script);
		}
		$filename = $script.'.'.$output_format;
	}
	// just use/change settings for this single request
	$setting = $brick['setting'];
	if (is_array($data) AND array_key_exists('_setting', $data)) {
		$setting = array_merge($setting, $data['_setting']);
		unset($data['_setting']);
	}
	
	// output data, depending on parameter
	switch ($output_format) {
	case 'xml':
		// @todo: SimpleXML or use some generic function
		// $brick['content_type'] = 'xml';
		$brick['text'] = 'XML export currently not supported';
		return $brick;
	case 'json':
		$brick['text'] = json_encode($data);
		if (!$brick['text']) return false;
		$brick['content_type'] = 'json';
		$brick['headers']['filename'] = $filename;
		return $brick;
	case 'csv':
		$brick['text'] = brick_csv_encode($data, $setting);
		if (!$brick['text']) return false;
		$brick['content_type'] = 'csv';
		$brick['headers']['filename'] = $filename;
		return $brick;
	case 'html':
	default:
		$request = brick_request_file($script, $brick, 'htmlout');
		if (!function_exists($request)) {
			$content['error']['level'] = E_USER_ERROR;
			$content['error']['msg_text'] = 'The function "%s" is not supported by the CMS.';
			$content['error']['msg_vars'] = array($request);
			return $content;
		}
		return $request($data, $params);
	}
}

/**
 * get filename of request script from request-folder or modules
 * and include file
 *
 * @param string $script
 * @param array $brick
 * @param string $type (optional: false, 'get' or 'htmlout')
 * @param string function name if script was found, or false
 */
function brick_request_file($script, $brick, $type = false) {

	// check if script is in subdirectory
	if (strstr($script, '/')) {
		$script = explode('/', $script);
		$folder = array_shift($script);
		$script = implode('/', $script);
	} else {
		$folder = '';
	}
	// get name of function to be called
	$script = strtolower(str_replace('-', '_', $script));
	switch ($type) {
	case 'get':
		$request = 'cms_get_'.$script;
		$path = $brick['path'].'_get/';
		$brick['module_path'] .= '_get';
		$function_name = 'mod_%s_get_%s';
		break;
	case 'htmlout':
		$request = 'cms_htmlout_'.$script;
		$path = $brick['path'];
		$function_name = 'mod_%s_htmlout_%s';
		break;
	default:
		$request = 'cms_'.$script;
		$path = $brick['path'];
		$function_name = 'mod_%s_%s';
		break;
	}

	// include function file and check if function exists
	$exists = brick_request_script($folder.$script, $path);
	if (!$exists) {
		foreach ($brick['setting']['modules'] as $module) {
			if ($folder AND $folder !== $module) continue;
			$module_path = $brick['setting']['modules_dir'].'/'.$module.$brick['module_path'];
			$exists = brick_request_script($script, $module_path);
			if ($exists) $request = sprintf($function_name, $module, $script);
		}
	}
	return $request;
}

/**
 * create filename from script name
 *
 * @param string $script
 * @param string $path = $brick['path']
 * @return string $filename
 */
function brick_request_script($script, $path) {
	$file = substr(strtolower($script), 0, strpos($script.'_', '_')).'.inc.php';
	if (!file_exists($path.'/'.$file)) return false;
	require_once $path.'/'.$file;
	return true;
}

/**
 * requests external data, first create URL, then run syndication script
 * from own library
 *
 * @param string $script
 * @param array $setting
 * @param array $params (optional)
 */
function brick_request_external($script, $setting, $params = array()) {
	$url = brick_request_url($script, $params, $setting);
	if ($url === true) return true;
	if (!$url) return array();

	if (empty($setting['syndication_function'])) {
		$setting['syndication_library'] = '/zzwrap/syndication.inc.php';
		$setting['syndication_function'] = 'wrap_syndication_get';
	}

	require_once $setting['lib'].$setting['syndication_library'];
	$data = $setting['syndication_function']($url, $setting['brick_cms_input']);
	return $data;
}

/**
 * gets URL for retrieving data from a foreign source
 *
 * @param string $script
 * @param array $params (optional)
 * @param array $setting (optional)
 * @return array
 */
function brick_request_url($script, $params = array(), $setting = array()) {
	// get from URL
	$params = implode('/', $params);
	if (isset($setting['brick_json_source_url'][$script])) {
		// set to: we don't need a JSON import
		if (!$setting['brick_json_source_url'][$script]) return true;
		$url = sprintf($setting['brick_json_source_url'][$script], $params);
	} elseif (!empty($setting['brick_json_source_url_default'])) {
		$url = sprintf($setting['brick_json_source_url_default'], $script, $params);
	} else {
		$url = $script;
	}
	// rare occurence, but we might not have a URL
	if (!$url) return false;
	return $url;
}

/**
 * Convert array into CSV format
 *
 * @param array $data
 * @param array $setting
 * @return string
 */
function brick_csv_encode($data, $setting) {
	if (!isset($setting['export_csv_enclosure']))
		$setting['export_csv_enclosure'] = '"';
	if (!isset($setting['export_csv_delimiter']))
		$setting['export_csv_delimiter'] = ";";
	if (!isset($setting['export_csv_show_empty_cells']))
		$setting['export_csv_show_empty_cells'] = false;
	if (!isset($setting['export_csv_heading']))
		$setting['export_csv_heading'] = true;
	$enc = $setting['export_csv_enclosure'];
	$lim = $setting['export_csv_delimiter'];

	$text = '';
	$newline = true;
	if ($setting['export_csv_heading']) {
		$head = reset($data);
		foreach (array_keys($head) as $field) {
			if ($text) $text .= $lim;
			$text .= $enc.str_replace($enc, $enc.$enc, $field).$enc;
		}
		$text .= "\r\n";
	}
	foreach ($data as $line) {
		foreach ($line as $field) {
			if ($newline) $newline = false;
			else $text .= $lim;
			if ($field AND !is_array($field)) {
				$text .= $enc.str_replace($enc, $enc.$enc, $field).$enc;
			} elseif ($field AND is_array($field)) {
				// @todo: allow arrays in arrays
				$text .= $enc.str_replace($enc, $enc.$enc, implode(',', $field)).$enc;
			} elseif (!$field AND $setting['export_csv_show_empty_cells']) {
				$text .= $enc.$enc;
			}
		}
		$text .= "\r\n";
		$newline = true;
	}
	return $text;
}

?>