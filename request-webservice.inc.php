<?php 

// zzbrick
// (c) Gustaf Mossakowski, <gustaf@koenige.org> 2009
// requests to or from webservice for data interchange


function brick_request_getxml($script, $params, $setting) {
	// TODO
}

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

function brick_import_errors($errno, $errstr) {
	// we do not care about 404 errors, they will be logged otherwise
	if (trim($errstr)
		AND substr(trim($errstr), -13) != '404 Not Found'
		AND function_exists('wrap_error'))
	{
		wrap_error('JSON ['.$_SERVER['SERVER_ADDR'].']: '.$errstr, E_USER_ERROR);
	}
}

function brick_request_xmlout($script, $data, $params) {
	// TODO
	// header('Content-Type: application/xml; charset=utf-8');
	// header Content-Type, Content-Length, Last-Modified
	// Output XML

	// ggf. Funktion aus reiffnet Webservice einbinden.
}

function brick_request_jsonout($script, $data, $params) {
	$out = json_encode($data);
	if ($out) {
		header('Content-Type: application/json; charset=utf-8');
		header('Content-Length: '.strlen($out));
	 	header('Accept-Ranges: bytes');
		header('Content-Disposition: attachment; filename='.$script.'.json');
		// TODO: Last-Modified
		echo $out;
		exit; // TODO: really exit here? maybe for error logging, continue
	} else {
		return false;
	}
}

?>