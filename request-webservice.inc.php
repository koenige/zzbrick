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
	$data = file_get_contents($url);
//	$out = json_decode($data);			// Object
	$object = json_decode($data, true);	// Array
	return $object;
}

function brick_request_xmlout($script, $data, $params) {
	// TODO
	// header('Content-Type: application/xml; charset=utf-8;');
	// header Content-Type, Content-Length, Last-Modified
	// Output XML

	// ggf. Funktion aus reiffnet Webservice einbinden.
}

function brick_request_jsonout($script, $data, $params) {
	$out = json_encode($data);
	if ($out) {
		header('Content-Type: application/json; charset=utf-8;');
//		header("Content-Length: ".strlen($out)."; ");
		// TODO: Last-Modified
		echo $out;
		exit; // TODO: really exit here? maybe for error logging, continue
	} else {
		return false;
	}
}

?>