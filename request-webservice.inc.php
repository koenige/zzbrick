<?php 

// zzbrick
// (c) Gustaf Mossakowski, <gustaf@koenige.org> 2009
// requests to or from syndication URL for data interchange


function brick_request_getxml($script, $params, $setting) {
	// TODO
}

/**
 * gets JSON data from a remote URL and converts it into an array
 *
 * @param string $script
 * @param array $params (optional)
 * @param array $setting (optional)
 * @return array
 */
function brick_request_getjson($script, $params = array(), $setting = array()) {
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
	set_error_handler('brick_import_errors');
	$data = file_get_contents($url); // do not log error here
	restore_error_handler();

	$object = json_decode($data, true);	// Array
	if (!$object) {
		// maybe this PHP version does not know how to handle strings
		// so convert it into an array
		$object = json_decode('['.$data.']', true);
		// convert it back to a string
		if (count($object) == 1 AND isset($object[0]))
			$object = $object[0];
	}
	return $object;
}

function brick_import_errors($errno, $errstr, $errfile, $errline, $errcontext) {
	// we do not care about 404 errors, they will be logged otherwise
	if (trim($errstr)
		AND substr(trim($errstr), -13) != '404 Not Found'
		AND function_exists('wrap_error'))
	{
		// you may change the error code if e. g. only pictures will be fetched
		// via JSON to E_USER_WARNING or E_USER_NOTICE
		if (empty($errcontext['setting']['brick_import_error_code']))
			$errcontext['setting']['brick_import_error_code'] = E_USER_ERROR;
		wrap_error('JSON ['.$_SERVER['SERVER_ADDR'].']: '.$errstr, $errcontext['setting']['brick_import_error_code']);
	}
}

function brick_request_xmlout($script, $data, $params) {
	// TODO
	// header('Content-Type: application/xml; charset=utf-8');
	// header Content-Type, Content-Length, Last-Modified
	// Output XML

	// ggf. Funktion aus reiffnet Webservice einbinden.
}

/**
 * output of data, JSON encoded
 *
 * @param string $script filename for download
 * @param array $data data for JSON encoding
 * @param array $params
 * @return array $brick
 */
function brick_request_jsonout($script, $data, $params) {
	$brick['text'] = json_encode($data);
	if (!$brick['text']) return false;
	header('Content-Type: application/json; charset=utf-8');
	header('Content-Length: '.strlen($brick['text']));
	header('Content-Disposition: attachment; filename='.$script.'.json');
	$brick['content_type'] = 'json';
	return $brick;
}

?>