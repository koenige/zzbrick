<?php 

/**
 * zzbrick
 * Requests
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2009-2012, 2014-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * requests function which returns database content in a $page-Array
 * 
 * files: zzbrick_request/{request}.inc.php
 * functions: cms_{$request}()
 * settings: brick_request_shortcuts
 * example: 
 *		%%% request news %%%
 *		%%% request news * %%% -- URL-parameters take place of asterisk
 *		%%% request news 2004 %%%
 * @param array $brick	Array from zzbrick
 * @return array $brick
 */
function brick_request($brick) {
	// shortcuts
	if (empty($brick['subtype'])) 
		$brick['subtype'] = NULL;
	if (in_array($brick['subtype'], bricksetting('brick_request_shortcuts'))) {
		array_unshift($brick['vars'], $brick['subtype']);
		// to transport additional variables which are needed
		// so %%% image 23 %%% may be as small as possible
		if (in_array($brick['subtype'], bricksetting('brick_request_url_params'))) {
			$brick['vars'][] = '*';
		}
	}

	$brick = brick_local_settings($brick);
	if (!empty($brick['local_settings']['brick_request_cms']))
		bricksetting('brick_request_cms', true);
	
	// get parameter for function
	$filetype = '';
	if (bricksetting('brick_request_cms')) {
		if (preg_match('/(.+)\.([a-z0-9]+)/i', bricksetting('brick_url_parameter'), $matches)) {
			// use last part behind dot as file extension
			if (count($matches) === 3 AND in_array($matches[2], bricksetting('brick_export_formats'))) {
				bricksetting('brick_url_parameter', $matches[1]);
				$filetype = $matches[2];
			}
		} else {
			$path = pathinfo(bricksetting('request_uri'));
			if (!empty($path['extension'])) {
				$filetype = $path['extension'];
				if ($pos = strpos($filetype, '?')) $filetype = substr($filetype, 0, $pos);
			}
		}
	}
	$brick['vars'] = brick_request_params($brick['vars'], bricksetting('brick_url_parameter'));
	$brick = brick_placeholder_script($brick);
	$script = array_shift($brick['vars']);

	if (bricksetting('brick_request_cms')) {
		// call function
		$content = brick_request_cms($script, $brick, $filetype);
	} else {
		if ($brick['subtype'] === 'make') {
			$brick = brick_request_file($script, $brick, 'make');
		} else {
			$brick = brick_request_file($script, $brick);
		}

		if (!function_exists($brick['request_function'])) {
			$brick['page']['error']['level'] = E_USER_ERROR;
			$brick['page']['error']['msg_text'] = 'The function "%s" is not supported by the CMS.';
			$brick['page']['error']['msg_vars'] = [$brick['request_function']];
			$brick['text'] = false;
			return $brick;
		}
		// call function
		$job = wrap_job_check($brick['subtype']);
		$content = $brick['request_function']($brick['vars'], $brick['local_settings'], $brick['data'] ?? []);
		wrap_job_finish($job, $brick['subtype'], $content);
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
			$last_block = array_pop($brick['page']['text'][$brick['position']]);
			if (substr(trim($last_block), -4) === '</p>') {
				$last_block = substr(trim($last_block), 0, -4).' ';
				$brick['cut_next_paragraph'] = true;
			}
			$brick['page']['text'][$brick['position']][] = $last_block;
		}
	}

	if (!empty($content['replace_db_text'])) {
		// hide previous textblocks
		$brick['replace_db_text'][$brick['position']] = true;
		$brick['page']['text'][$brick['position']] = [];
	}

	if (!empty($content['text']) AND is_array($content['text'])) {
		foreach (array_keys($content['text']) AS $pos) {
			if (empty($brick['page']['text'][$pos])) 
				$brick['page']['text'][$pos] = [];
			$brick['page']['text'][$pos][] = $content['text'][$pos];
		}
	} elseif (!empty($content['text']))
		$brick['page']['text'][$brick['position']][] = $content['text'];

	if (!empty($content['replace_db_text'])) {
		// hide next textblocks
		$brick['position'] = '_hidden_';
		$brick['page']['text'][$brick['position']] = [];
	}

	$brick['page'] = brick_merge_page_bricks($brick['page'], $content);
	return $brick;
}

/**
 * Merges parameter from brick and form URI
 * 
 * @param array $variables = parameter from %%%-brick
 * @param string $parameter = parameter from URL
 * @return array $parameter_for_function
 */
function brick_request_params($variables, $parameter) {
	$parameter_for_function = [];
	$var_safe = [];

	foreach ($variables as $var) {
		if ($var === '*' OR substr($var, -1) === '*') {
			if (!$parameter AND $parameter !== '0' AND $parameter !== 0) {
				// return * as parameter, better than false, so you can
				// catch this error and return with a 404
				$parameter_for_function[] = '*';
				continue;
			}
			$url_parameters = explode('/', $parameter);
			if (substr($var, -1) === '*' AND count($url_parameters)) {
				$url_parameters[0] = substr($var, 0, -1).$url_parameters[0];
			}
			if ($parameter_for_function AND count($url_parameters)) {
				$parameter_for_function = array_merge(
					$parameter_for_function, $url_parameters
				);
			// parameters transmitted via URL
			// Attention: if there is more than one *-variable
			// parameters will be inserted several times
			} else {
				$parameter_for_function = $url_parameters;
			}
		} else {
			if (strlen($var) > 1 AND substr($var, 0, 1) === '"' && substr($var, -1) === '"')
				$parameter_for_function[] = substr($var, 1, -1);
			elseif ((strlen($var) > 1 OR empty($var_safe)) AND substr($var, 0, 1) === '"')
				$var_safe[] = substr($var, 1);
			elseif ($var_safe && substr($var, -1) !== '"') 
				$var_safe[] = $var;
			elseif ($var_safe && substr($var, -1) === '"') {
				$var_safe[] = substr($var, 0, -1);
				$parameter_for_function[] = implode(" ", $var_safe);
				$var_safe = [];
			} elseif ($var OR $var === '0' OR $var === 0) {
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
 * @param array $brick - settings for brick-scripts, here:
 *	- brick_cms_input = db, xml, json
 * @return mixed output of function (html: $page; other cases: direct output, headers
 */
function brick_request_cms($script, $brick, $filetype = '') {
	if (in_array($brick['subtype'], bricksetting('brick_export_formats')))
		$output_format = $brick['subtype'];
	elseif ($filetype AND in_array($filetype, bricksetting('brick_export_formats')))
		$output_format = $filetype;
	else
		$output_format = false;
	
	// get data for input, depending on settings
	$brick = brick_request_file($script, $brick, 'get');
	if (function_exists($brick['request_function'])) {
		$data = $brick['request_function']($brick['vars'], $brick['local_settings'], $brick['data'] ?? []);
	} else {
		// function does not exist, probably no database data is needed
		switch (bricksetting('brick_cms_input')) {
		case 'xml':
		case 'json':
			$data = brick_request_external($script, $brick['vars']);
			break;
		case 'jsonl':
			// trigger JSON Lines download
			$data = brick_request_external($script, $brick['vars']);
			$data = true;
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

	if ($data === true AND !empty($brick['request_function'])) {
		switch ($output_format) {
		case 'xml':
		case 'json':
		case 'jsonl':
		case 'csv':
			$content['error']['level'] = E_USER_NOTICE;
			$content['error']['msg_text'] = 'No input data for %s was found. Probably function `%s` is missing.';
			$content['error']['msg_vars'] = [$script, $brick['request_function']];
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
	if (is_array($data) AND array_key_exists('_query_strings', $data)) {
		$brick['query_strings'] = $data['_query_strings'];
		unset($data['_query_strings']);
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
	case 'jsonl':
		$brick['text'] = '';
		foreach ($data as $key => $line) {
			$brick['text'] .= json_encode([$key => $line])."\r\n";
		}
		if (!$brick['text']) return false;
		$brick['content_type'] = 'jsonl';
		$brick['headers']['filename'] = $filename;
		return $brick;
	case 'csv':
		$brick['text'] = brick_csv_encode($data);
		if (!$brick['text']) return false;
		$brick['content_type'] = 'csv';
		$brick['headers']['filename'] = $filename;
		if (bricksetting('export_csv_excel_compatible')) {
			$brick['headers']['character_set'] = 'utf-16le';
		}
		return $brick;
	case 'html':
	default:
		$brick = brick_request_file($script, $brick, $brick['subtype'] ?? 'htmlout');
		if (!function_exists($brick['request_function'])) {
			$content['error']['level'] = E_USER_ERROR;
			$content['error']['msg_text'] = 'The function "%s" is not supported by the CMS.';
			$content['error']['msg_vars'] = [$brick['request_function']];
			return $content;
		}
		return $brick['request_function']($data, $brick['vars'], $brick['local_settings'], $brick['data'] ?? []);
	}
}

/**
 * get filename of request script from request-folder or modules
 * and include file
 *
 * @param string $script
 * @param array $brick
 * @param string $type (optional: false, 'make', 'get' or 'htmlout')
 * @return array $brick
 *		'request_function' => function name if script was found, or false
 *		'active_module' => name of module, if applicable
 */
function brick_request_file($script, $brick, $type = false) {
	$script = strtolower($script);

	// check if script is in subdirectory
	if (strstr($script, '/')) {
		$script = explode('/', $script);
		$folder = array_shift($script);
		$script = implode('/', $script);
	} else {
		$folder = '';
	}

	// get path
	$path = substr(bricksetting('brick_module_dir'), 1);
	switch ($type) {
		case 'get': $path .= 'request_get'; break;
		case 'make': $path .= $type; break;
		default: $path .= 'request'; break;
	}

	// check for alternatives
	$filenames[1] = str_replace('_', '-', $script);
	if ($pos = strpos($filenames[1], '-'))
		$filenames[0] = substr($filenames[1], 0, $pos);
	ksort($filenames);
	
	// custom?
	$function = [];
	foreach ($filenames as $filename) {
		$filename = sprintf('%s%s/%s', $path, ($folder ? '/'.$folder : ''), $filename);
		$files = wrap_collect_files($filename, 'custom');
		if ($files) {
			$function[] = 'cms';
			$filenames = [];
			break;
		}
	}

	// modules?
	foreach ($filenames as $filename) {
		$filename = sprintf('%s/%s', $path, $filename);
		$files = wrap_collect_files($filename, ($folder ? $folder : 'modules'));
		if ($files) {
			// more than one match? do not take first match, remove default module first
			if (count($files) > 1 AND key($files) === 'default')
				unset($files['default']);
			$module = key($files);
			wrap_package_activate($module);
			$function[] = 'mod';
			$function[] = $module;
			break;
		}
	}
	
	if (!$function) {
		// prefix cms_ is needed, e. g. for centrally defined functions like cms_login()
		$brick['request_function'] = 'cms_'.($folder ? $folder.'/' : '').$script;
		return $brick;
	}
	require_once reset($files);

	// get name of function to call
	if ($type) $function[] = $type;
	$function[] = str_replace('-', '_', str_replace('/', '_', $script));
	$brick['request_function'] = implode('_', $function);

	return $brick;
}

/**
 * request data from get script, either from custom folder or module
 * use from inside of request or make script
 *
 * @param string $script
 * @param array $params (optional)
 * @param array $settings (optional)
 * @return array
 */
function brick_request_data($script, $params = [], $settings = []) {
	$brick = brick_request_file($script, [], 'get');
	$data = $brick['request_function']($params, $settings);
	return $data;
}

/**
 * requests external data, first create URL, then run syndication script
 * from own library
 *
 * @param string $script
 * @param array $params (optional)
 */
function brick_request_external($script, $params = []) {
	$url = brick_request_url($script, $params);
	if ($url === true) return true;
	if (!$url) return [];

	if ($file = bricksetting('brick_syndication_file')) require_once $file;
	$data = bricksetting('brick_syndication_function')($url, bricksetting('brick_cms_input'));
	return $data;
}

/**
 * gets URL for retrieving data from a foreign source
 *
 * @param string $script
 * @param array $params (optional)
 * @return array
 */
function brick_request_url($script, $params = []) {
	// get from URL
	$params = implode('/', $params);
	$json_source_url = bricksetting('brick_json_source_url');
	if (isset($json_source_url[$script])) {
		// set to: we don't need a JSON import
		if (!$json_source_url[$script]) return true;
		$url = sprintf($json_source_url[$script], $params);
	} elseif ($url = bricksetting('brick_json_source_url_default')) {
		$url = sprintf($url, $script, $params);
	} else {
		if (!parse_url($script, PHP_URL_SCHEME)) return false;
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
 * @return string
 */
function brick_csv_encode($data) {
	if (bricksetting('export_csv_excel_compatible'))
		bricksetting('export_csv_delimiter', "\t");
	$enc = bricksetting('export_csv_enclosure');
	$lim = bricksetting('export_csv_delimiter');

	$text = '';
	$newline = true;
	if (bricksetting('export_csv_heading')) {
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
			} elseif (!$field AND bricksetting('export_csv_show_empty_cells')) {
				$text .= $enc.$enc;
			}
		}
		$text .= "\r\n";
		$newline = true;
	}
	if (bricksetting('export_csv_excel_compatible')) {
		// @todo check with mb_list_encodings() if available
		$text = mb_convert_encoding($text, 'UTF-16LE', bricksetting('character_set'));
	}
	return $text;
}

/**
 * replaces all placeholders in text with files
 *
 * @param string $text (will change)
 * @param array $media (will change)
 * @param string $field_name
 * @return void
 */
function brick_request_links(&$text, &$media, $field_name) {
	if (!$text) return;
	$parts = explode('%%%', $text);
	$formatted = '';
	foreach ($parts as $index => $part) {
		if ($index & 1) {
			$part = trim($part);
			$medium = explode(' ', $part);
			$medium = brick_request_params($medium, '');
			$formatted .= brick_request_link($media, $medium, $field_name);
		} else {
			$formatted .= ltrim($part);
		}
	}
	$text = $formatted;
}

/**
 * replaces single placeholder in text with file
 *
 * @param array $media (will change)
 * @param array $placeholder
 *		[0]: area = typ
 *		[1]: medium no
 *		[2]: (optional, unless last) position
 *		[last]: (optional) size
 * @param string $field_name
 * @return string
 */
function brick_request_link(&$media, $placeholder, $field_name) {
	$area = array_shift($placeholder);
	switch ($area) {
	case 'bild':
	case 'image':
		$mediakeys = ['images'];
		$template = 'image'; // inline image
		$sizes = false;
		break;
	case 'video':
		$mediakeys = ['videos'];
		$template = 'video'; // inline video
		$sizes = false;
		break;
	case 'link':
		$mediakeys = ['links'];
		$template = 'link'; // just plain link
		$sizes = false;
		break;
	case 'doc':
		$mediakeys = ['links'];
		$template = 'doc'; // link with anchor
		$sizes = false;
		break;
	case 'download':
		$mediakeys = ['images', 'links'];
		$template = 'download'; // download links
		$sizes = true;
		break;
	default:
		// not supported, return unchanged
		foreach ($placeholder as $index => $p) {
			if (strstr($p, ' ')) $placeholder[$index] = sprintf('"%s"', $p);
		}
		return '%%% '.$area.' '.implode(' ', $placeholder).' %%%';
	}
	$no = array_shift($placeholder);
	$media_sizes = bricksetting('media_sizes');
	foreach ($mediakeys as $mediakey) {
		if (empty($media[$mediakey])) continue;
		foreach ($media[$mediakey] as $medium_id => $medium) {
			if ($medium[$field_name] != $no) continue;
			unset($media[$mediakey][$medium_id]);
			// last parameter = size
			if ($mediakey === 'images') {
				if (count($placeholder) === 3)
					$medium['custom_title'] = array_pop($placeholder);
				$size = array_pop($placeholder);
				if ($size AND !in_array($size, array_keys($media_sizes))) {
					// do not care about order of parameters position, size
					$medium['position'] = $size;
					$size = array_pop($placeholder);
				}
				if ($size AND in_array($size, array_keys($media_sizes))) {
					$medium['size'] = $size;
					$medium['path'] = $media_sizes[$size]['path'];
				} elseif (count($placeholder) > 1) {
					$medium['size'] = 'invalid';
				}
				// first parameter if there's still one = position
				if (count($placeholder) AND empty($medium['position'])) {
					$medium['position'] = $placeholder[0];
				}
			} else {
				$medium['custom_title'] = array_pop($placeholder);
			}
			// default path?
			$media_standard_image_size_used = false;
			if (empty($medium['path'])) {
				if (bricksetting('default_media_size')) {
					$medium['path'] = $media_sizes[bricksetting('default_media_size')]['path'];
				} elseif (bricksetting('media_standard_image_size')) {
					$media_standard_image_size_used = true;
					$medium['path'] = bricksetting('media_standard_image_size');
				}
			}
			if (empty($medium['path_x2'])) {
				if ($media_standard_image_size_used AND bricksetting('media_standard_image_size_x2')) {
					foreach ($media_sizes as $size => $medium_size) {
						if ($medium_size['path'].'' !== bricksetting('media_standard_image_size_x2').'') continue;
						$medium['path_x2'] = $medium_size['path'];
					}
				} elseif (!empty($medium['path'])) {
					foreach ($media_sizes as $medium_size) {
						if (is_numeric($medium['path']) AND $medium['path'] * 2 == $medium_size['path'])
							$medium['path_x2'] = $medium_size['path'];
					}
				}
			}
			if (!empty($medium['position']) AND $medium['position'] === 'hidden') continue;
			if (empty($medium['size']) AND !empty($medium['path'])) {
				foreach ($media_sizes as $size => $medium_size) {
					if ($medium_size['path'].'' !== $medium['path'].'') continue;
					$medium['size'] = $size;
				}
			}
			if (!empty($medium['size'])) {
				$medium['max_width'] = $media_sizes[$medium['size']]['width'];
				$medium['max_height'] = $media_sizes[$medium['size']]['height'];
				foreach ($media_sizes as $medium_size) {
					if ($medium_size['width'] > $medium['max_width']) {
						$medium['bigger_size_available'] = true;
					}
				}
			}
			if (!empty($medium['custom_title']) AND $medium['custom_title'] === '-') {
				$medium['custom_title'] = '';
				$medium['title'] = '';
			}
			if ($sizes) {
				array_multisort($media_sizes, SORT_DESC, SORT_REGULAR, array_column($media_sizes, 'width'));
				foreach ($media_sizes as $size) {
					if ($size['action'] === 'crop') continue; // no cropped images
					$file = sprintf('%s/%s.%s.%s', wrap_setting('media_folder'), $medium['filename'], $size['path'], $medium['thumb_extension']);
					if (!file_exists($file)) continue;
					$image = getimagesize($file);
					$medium['files'][] = array_merge($medium, [
						'path' => $size['path'],
						'width' => $image[0],
						'height' => $image[1],
						'filesize' => filesize($file)
					]);
				}
			}
			return wrap_template($template, $medium);
		}
	}
	return '';
}
