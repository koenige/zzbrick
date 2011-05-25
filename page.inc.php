<?php 

// zzbrick
// (c) Gustaf Mossakowski, <gustaf@koenige.org> 2009
// page templates


/**
 * seplaces template brick for HTML page layout
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

	$page = &$brick['parameter'];
	global $zz_conf;
	if (!isset($brick['page']['text'][$brick['setting']['brick_default_position']]))
		$brick['page']['text'][$brick['setting']['brick_default_position']] = '';
	$content = false;
	$brick_var = str_replace('-', '_', array_shift($brick['vars']));
	if (file_exists($brick['path'].'/'.basename(strtolower($brick_var)).'.inc.php')) {
		// there's a custom formatting for this
		// get name of function to be called, similar to brick_request
		$request = 'page_'.strtolower($brick_var);
		$script_filename = $brick['path'].'/'.basename(strtolower($brick_var)).'.inc.php';
		if (file_exists($script_filename))
			require_once $script_filename;
		if (!function_exists($request)) {
			$brick['page']['error']['level'] = E_USER_ERROR;
			$brick['page']['error']['msg_text'] = 'The function "%s" is not supported by the CMS.';
			$brick['page']['error']['msg_vars'] = array($request);
			return $brick;
		}
		// call function
		$content = $request($brick['vars'], $page);
	}
	if (!$content AND !empty($page[$brick_var])) {
		// we have it in $page, so return this.
		if (is_array($page[$brick_var])) {
			$key = array_shift($brick['vars']);
			if (!empty($page[$brick_var][$key])) {
				$content = $page[$brick_var][$key];
			}
		} else {
			$content = $page[$brick_var];
		}
	} elseif (!$content) {
		// other special cases
		switch ($brick_var) {
			case 'charset':
				$content = $zz_conf['character_set'];
				break;
			case 'year':
				$content = date('Y');
				break;
			case 'base':
				$content = $brick['setting']['base'];
				break;
		}
	}
	if (!empty($brick['vars'][0]) AND $content) {
		// formatting to be done, there is some HTML and a value
		// check for translations in form of %{'Hello'}%
		// to translate text, you might use a translation function
		// default: use wrap_text() from core/language.inc.php from zzwrap
		if (!isset($brick['setting']['brick_translate_text_function']))
			$brick['setting']['brick_translate_text_function'] = 'wrap_text';
		if (strstr($brick['vars'][0], "%{'")) 
			$brick['vars'][0] = preg_replace_callback("~%{'(.+?)'}%~", $brick['setting']['brick_translate_text_function'], $brick['vars'][0]);
		$brick['page']['text'][$brick['setting']['brick_default_position']] .= 
			sprintf($brick['vars'][0], $content);
	} else {
		// no formatting or no value
		$brick['page']['text'][$brick['setting']['brick_default_position']] .= $content;
	}
	return $brick;
}

?>