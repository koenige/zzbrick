<?php 

/**
 * zzbrick
 * Redirect to another URL
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2009-2013, 2016, 2019, 2023-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * redirects to another URL after checking if it's syntactically valid
 * 
 * files: -
 * functions: -
 * settings: -
 * example: 
 *		%%% redirect http://www.example.org/ %%%
 *		%%% redirect /path/to/local.html %%%
 *		%%% redirect 307 /path/to/local.html %%%
 * @param array $brick	Array from zzbrick, in $brick['vars'][0] or [1] we need
 *      the new URL, [0] might contain redirection code
 *		wrap_setting('host_base') will be used if set, must be something
 *		like http://www.example.org
 * @return array $brick['page']['error'] if false; this function exits if URL is correct
 */
function brick_redirect($brick) {
	// test if field is hidden
	if (!empty($brick['position']) AND $brick['position'] == '_hidden_')
		return $brick;

	// test if it's a valid URL
	if (in_array($brick['vars'][0], [301, 302, 303, 307])) {
		$statuscode = array_shift($brick['vars']);	
	} else {
		$statuscode = 302;
	}
	if (brick_check_url($brick['vars'][0])) {
		if (substr($brick['vars'][0], 0, 1) == '/') {
			// Location needs an absolute URI
			// HTTP_HOST must be canonical, best to do this via the webserver
			if (!wrap_setting('host_base')) {
				$host = $_SERVER['HTTP_HOST'];
				// hostname may only contain ASCII letters, - and .
				if (!preg_match('/^[a-zA-Z0-9-\.]+$/', $host)) $host = '';
				if (!$host) $host = $_SERVER['SERVER_NAME'];
				$base = (!empty($_SERVER['HTTPS']) ? 'https' : 'http').'://'.$host;
			} else {
				$base = wrap_setting('host_base');
			}
			$base .= wrap_setting('base');
			$brick['vars'][0] = $base.$brick['vars'][0];
		}
		if (function_exists('wrap_redirect')) {
			wrap_redirect($brick['vars'][0], $statuscode);
			exit;
		}
		header('Location: '.$brick['vars'][0]);
		exit;
	}
	// ok, it's not a URL, we do not care, send an error and return
	$brick['page']['error']['level'] = E_USER_NOTICE;
	$brick['page']['error']['msg_text'] = '"%s" is not a valid URI.';
	$brick['page']['error']['msg_vars'] = [$brick['vars'][0]];
	return $brick;
}

/**
 * checks whether an input is a URL
 * 
 * This function is part of zzform, there it is called zz_check_url()
 * @param string $url	URL to be tested, may be a relative URL as well (starting with ../, /)
 *		might add http:// in front of it if this generates a valid URL
 * @return string url if correct, or false
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_check_url($url) {
	$url = trim($url); // remove invalid white space at the beginning and end of URL
	$url = str_replace("\\", "/", $url); // not sure: is \ a legal part of a URL?
	if (substr($url, 0, 1) == "/")
		if (brick_is_url('http://example.com'.$url)) return $url;
		else return false;
	elseif (substr($url, 0, 2) == "./") 
		if (brick_is_url('http://example.com'.substr($url,1))) return $url;
		else return false;
	elseif (substr($url, 0, 3) == "../") 
		if (brick_is_url('http://example.com'.substr($url,2))) return $url;
		else return false;
	else
		if (!brick_is_url($url))  {
			$url = "http://" . $url;
			if (!brick_is_url($url))	return false;
			else						return $url;
		} else return $url;
}

/**
 * checks whether an input is a URL
 * 
 * This function is part of zzform, there it is called zz_is_url()
 * @param string $url	URL to be tested, only absolute URLs
 * @return string url if correct, or false
 */
function brick_is_url($url) {
	// @todo give back which part of URL is incorrect
	$possible_schemes = ['http', 'https', 'ftp', 'gopher'];
	if (!$url) return false;
	$parts = parse_url($url);
	if (!$parts) return false;
	if (empty($parts['scheme']) OR !in_array($parts['scheme'], $possible_schemes))
		return false;
	elseif (empty($parts['host']) 
		OR (!preg_match("/^[0-9a-z]([-.]?[0-9a-z])*\.[a-z]{2,6}$/i", $parts['host'])
		AND !preg_match('/[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}/', $parts['host'])))
		return false;
	elseif (!empty($parts['user']) 
		AND !preg_match("/^([0-9a-z-]|[\_])*$/i", $parts['user']))
		return false;
	elseif (!empty($parts['pass']) 
		AND !preg_match("/^([0-9a-z-]|[\_])*$/i", $parts['pass']))
		return false;
	elseif (!empty($parts['path']) 
		AND !preg_match("/^[0-9a-z\/_\.@~\-,=%]*$/i", $parts['path']))
		return false;
	elseif (!empty($parts['query'])
		AND !preg_match("/^[A-Za-z0-9\-\._~!$&'\(\)\*+,;=:@?\/%]*$/", $parts['query']))
		// not 100% correct: % may only appear in front of HEXDIG, e. g. %2F
		// here it may appear in front of any other sign
		// see 
		// http://www.ietf.org/rfc/rfc3986.txt and 
		// http://www.ietf.org/rfc/rfc2234.txt
		return false;
	return true;
}
