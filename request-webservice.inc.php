<?php 

// zzbrick
// (c) Gustaf Mossakowski, <gustaf@koenige.org> 2009
// requests to or from webservice for data interchange


function brick_request_getxml($script, $params, $setting) {
	// TODO
}

function brick_request_getjson($script, $params, $setting) {
	// get from URL
	$params = implode('/', $params);
	if (!empty($setting['brick_json_source_url'][$script])) {
		$url = sprintf($setting['brick_json_source_url'][$script], $params);
	} else {
		$url = sprintf($setting['brick_json_source_url_default'], $script, $params);
	}
	set_error_handler('brick_import_errors');
	$data = file_get_contents($url); // do not log error here
	restore_error_handler();

	$object = json_decode($data, true);	// Array
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