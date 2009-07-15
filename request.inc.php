<?php 

// zzbrick
// (c) Gustaf Mossakowski, <gustaf@koenige.org> 2009
// requests

/*	Available functions:
	- brick_request()
		- brick_request_params()
*/

function brick_request($brick) {
	// shortcuts
	if (empty($brick['subtype'])) $brick['subtype'] = '';
	switch ($brick['subtype']) {
		case 'bild': 
		case 'image':
			array_unshift($brick['vars'], $brick['subtype']);
			$brick['vars'][] = '*';
			// to transport additional variables which are needed
			// so %%% image 23 %%% may be as small as possible
			break;
		case 'partien': // TODO: put this into settings 
		case 'diagramm': // TODO: put this into settings 
		case 'swiss_eins': // TODO: put this into settings 
		case 'swiss_tabellen': // TODO: put this into settings 
			array_unshift($brick['vars'], $brick['subtype']);
	}
	if (file_exists($brick['path'].'/common.inc.php'))
		require_once $brick['path'].'/common.inc.php';

	// get name of function to be called
	$func = array_shift($brick['vars']);
	$request = 'cms_'.strtolower($func);

	// include function file and check if function exists
	$script_filename = 'cms-'.substr(strtolower($func), 0, strpos($func.'_', '_')).'.inc.php';
	if (file_exists($brick['path'].'/'.$script_filename))
		require_once $brick['path'].'/'.$script_filename;
	if (!function_exists($request)) {
		$brick['error']['level'] = E_USER_WARNING;
		$brick['error']['msg_text'] = 'The function "%s" is not supported by the CMS.';
		$brick['error']['msg_vars'] = array($request);
		return $brick;
	}

	// get parameter for function
	$function_params = brick_request_params($brick['vars'], $brick['parameter']);

	// call function
	$content = $request($function_params);
	if (empty($content)) {
		$brick['text'] = false;
		$brick['page']['status'] = 404;
		return $brick;
	}

	// check if there's some </p>text<p>, remove it for inline results of function
	if (!is_array($content['text'])) if (substr($content['text'], 0, 1) != '<' 
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

	if (is_array($content['text'])) {
		foreach (array_keys($content['text']) AS $pos) {
			if (empty($brick['page']['text'][$pos])) 
				$brick['page']['text'][$pos] = '';
			$brick['page']['text'][$pos] .= $content['text'][$pos];
		}
	} else
		$brick['page']['text'][$brick['position']] .= $content['text'];

	// get some content from the function and overwrite existing values
	$overwrite_bricks = array('title', 'dont_show_h1', 'language_link',
		'extra', 'no_page_head', 'no_page_foot', 'last_update',
		'head_addition', 'style', 'breadcrumbs', 'meta', 'project', 'created', 'link');
	// extra: for all individual needs, not standardized
	foreach ($overwrite_bricks as $part) {
		if (!empty($content[$part]))
			$brick['page'][$part] = $content[$part];	
	}

	// get even more content from the function and merge with existing values
	$merge_bricks = array('authors', 'media');
	foreach ($merge_bricks as $part) {
		if (!empty($content[$part]) AND is_array($content[$part]))
			$brick['page'][$part] = array_merge($brick['page'][$part], $content[$part]);
	}
	return $brick;
}

/** Merges parameter from brick and form URI
 * 
 * @param $variables(array) = parameter from %%%-brick
 * @param $parameter(string) = parameter from URL
 * @return $parameter_for_function array
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_request_params($variables, $parameter) {
	$parameter_for_function = false;
	$var_safe = false;
	foreach ($variables as $var) {
		if ($var == '*') {
			$url_parameters = explode('/', $parameter);
			if ($parameter_for_function)
				$parameter_for_function = array_merge($parameter_for_function, 
				$url_parameters); // parameters transmitted via URL
			// Attention: if there are more than one *-variables
			// parameters will be inserted several times
			else
				$parameter_for_function = $url_parameters;
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
			} else 
				// parameter like given to function but newly indexed
				$parameter_for_function[] = $var;
		}
	}
	return $parameter_for_function;
}

?>