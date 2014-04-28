<?php 

/**
 * zzbrick
 *
 * 'brick' is a kind of a template language that is intended to allow easily
 * combination of html, text and placeholder blocks in content management systems.
 * 'brick' will format all text with markdown and replace the placeholder blocks
 * with real content depending on which type they are.
 *
 * syntax:
 * %%% request news 2004 %%%
 * 'query' is the type of the placeholder block
 * 	- zzbrick/request.inc.php knows what to do with this kind of placeholder
 * 'news' is the function to be called
 * 	- zzbrick_request/news.inc.php will be included with a corresponding 
 * 	function cms_news(); which will be called, other functions which are being
 * 	accessed through cms_news() must be in the same file, in files starting with
 * 	zzbrick_request/news_ or in the zzbrick_request/_common.inc.php which will 
 * 	always be included
 * '2004' and further variables, separated by spaces, are variables which are
 * being passed to the function. variables must not include ", since this sign
 * is used to allow the use of whitespace in single variables, e. g. "2004 fall"
 * 
 * $page
 * $format['position'] == here goes the formatted output, if none is there, 
 * 	position is '_hide_'
 * 
 * Variabes in $setting:
 * 	- 'brick_custom_dir': directory for the customised brick scripts, zzwrap
 * 		sets this to a default in $zz_setting['custom'], prefix zzbrick_
 * 	- 'brick_default_position': if a matrix of the content is wanted, here you 
 * 		can define a default position
 * 	- 'brick_types_translated': here you can translate the first part of the
 * 		zzbrick definition e. g. %%% abfrage ... ... %%% might be translated to
 * 		request: $setting['brick_types_translated']['abfrage'] = 'request'
 * 		this may also be used to define a certain subtype
 * 	- 'brick_request_shortcuts'	shortcuts, that is you can write %%% image blubb 
 * 		%%% instead of %%% request image blubb %%%
 * 	- 'brick_username_in_session': Name of key from $_SESSION that will be used  
 * 		as username for zzform(), default is 'username'
 * 	- 'brick_authentication_file': file that will be included if
 * 		authentication is needed for accessing the zzform scripts. might be 
 * 		false, then no file will be included. zzwrap sets this automatically
 * 	- 'brick_authentication_function': function to be called if
 * 		authentication is needed
 * 	- 'brick_translate_text_function': Name of function to translate text; 
 * 		zzwrap sets this to wrap_text
 * 	- 'brick_fulltextformat': name of function to format the complete output of
 * 		brick_format instead of formatting each paragraph separately with 
 * 		markdown
 * 	- 'brick_ipfilter_translated': similar to brick_types_translated, here
 * 		you can translate '=', ':' and '-' to different text
 * 	- 'brick_ipv4_allowed_range': standard allowed range of IP adresses if
 * 		no address is set in ipfilter
 * 	- 'brick_rights_translated': similar to brick_types_translated, here
 * 		you can translate '=', ':' and '-' to different text
 * 	- 'lang': language code for HTML lang attribute of HEAD
 * 
 * Files where customisation will take place
 * 	- zzbrick_rights/access_rights.inc.php
 * 	- zzbrick_request/{request}.inc.php
 * 	- zzbrick_request/_common.inc.php
 * 	- zzbrick_forms/{tables}.inc.php
 * 	- zzbrick_tables/{tables}.inc.php
 * 
 * Functions that contain customisations
 * 	- wrap_access_rights() - returns true if access is granted or false if no
 * 		access is granted
 * 	- cms_{request}() - returns $page-Array as defined in brick_format()
 * 
 * Available functions in this file:
 * 	- brick_format()
 * 		- brick_get_variables()
 * 		- brick_textformat()
 * 
 * Always installed modules:
 * 	- loop - will do a loop and repeat parts of the brick
 * 		%%% loop start "optional HTML if content" "optional HTML if no content" %%%
 * 		%%% loop end "optional HTML if content" %%%
 * 		»subloops«:
 * 		%%% loop subcategory %%%
 * 		%%% loop end "optional HTML if content" %%%
 *		only part of the items
 *		%%% loop start 2- %%% e. g. 1, 2-4, -5
 * 	
 * Available modules:
 * 	- comment - comment blocks, won't be displayed
 * 	- condition - if else condition basing on item
 * 	- forms - includes zzform scripts via brick_format()
 * 	- ipfilter - shows content only if client is in preset IP range
 * 	- item - gets item from array
 * 	- page - page element
 * 	- position - sets position of text block in a predefined matrix
 * 	- redirect - redirects to another URL
 * 	- request - outputs database queries in a formatted way, with URL parameters
 * 	- rights - depending on the result of a custom function, access to the
 * 		following content is allowed or forbidden (e. g. will be shown or not)
 * 	- text - translates text string
 * 
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2009-2014 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Format a textblock
 * 
 * @param string $block = text block to format
 * @param array $parameter = parameters, via URL or via function
 * @param array $zz_setting settings, might be made available in a global array
 * @return $page array
	must have:
 		'text' => (string) textbody, html formatted
 			if false, zzwrap will return a 404 page. Textblocks that are 
 			optional need to return e. g. a ' '-string
	optional:
 		'title' => (string) pagetitle
 		'breadcrumbs' => (array) breadcrumbs, each breadcrumb has it's own index
 			might be 0 => '<a href="/">Page</a>' or
 			0 => array('url_path' => '/', 'title' => 'Page')
 		'language_link' => (string) link to same page in other language (...)
 		'dont_show_h1' => (bool) zzwrap: do not repeat page title in h1
		'no_page_head' => (bool) zzwrap: do not output page head
		'no_page_foot' => (bool) zzwrap: do not output page foot
 		'authors' => (array) IDs of page authors, zzwrap will post process
 			this ID list in wrap_get_authors()
		'last_update' => (date) last update of page, might be used for 
			last-modified header
		'created' => (date) creation date of page
		'project' => (string) project title; zzwrap: this will be output in
			TITLE instead of $zz_conf['project']
 		'status' => (int) http status code
		'style' => (string) defines style of page, might be used to include
			different page heads or footers, separate css files and so on
		'head' => (string) html formatted output, that will 
			be added in the HEAD section of the HTML document (if set in the
			template) - instead you can use the more specific:
		- 'link' => (array), may be put in <link rel="" ...>
			syntax: $page['link'][REL][n][ATTR]
		- 'meta' => (array), may be put in <meta name="{$key}" content="{$value}">
		'extra' => (array), here you might use variables as you want, e. g.
			'body_attributes' =>(string) html formatted output for BODY element
			'lageplan' => (string) HTML block to be put somewhere on page
			'zeige_letze_anderung' => (bool) decision whether to show some
				block or not
			'seitenmenu' => (string) HTML block to be put somewhere on page
	undocumented or not standardised:
 		'media' => (array) page media
 			'position' => array
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_format($block, $parameter = false, $zz_setting = false) {
	if (empty($zz_setting)) global $zz_setting;
	$brick['setting'] = &$zz_setting;

	// set defaults
	if (empty($brick['setting']['brick_default_position'])) 
		$brick['setting']['brick_default_position'] = 'none';
	if (empty($brick['setting']['brick_types_translated']))
		$brick['setting']['brick_types_translated'] = array();
	if (empty($brick['setting']['brick_fulltextformat']))
		$brick['setting']['brick_fulltextformat'] = '';
	if (empty($brick['setting']['brick_custom_dir']))
		$brick['setting']['brick_custom_dir'] = $zz_setting['custom'].'/zzbrick_';
	if (empty($brick['setting']['brick_module_dir']))
		$brick['setting']['brick_module_dir'] = '/zzbrick_';

	// initialize page variables
	$brick['page']['text'] = false;				// textbody
	$brick['page']['title'] = false;			// page title and h1 element
	$brick['page']['head'] = false;				// something for the head section
	$brick['page']['breadcrumbs'] = false;		// additional breadcrumbs
	$brick['page']['authors'] = array();		// authors of page
	$brick['page']['media'] = array();			// media linked from page
	$brick['page']['language_link'] = false;	// language links to other language(s)
	$brick['page']['status'] = 200;				// everything ok

	// further variables
	$brick['access_forbidden'] = false;			// access is allowed
	$brick['access_blocked'] = '';
	$brick['position'] = 'none';
	$brick['cut_next_paragraph'] = false;		// to get markdown formatted text inline
	$brick['replace_db_text'][$brick['position']] = false;
	$brick['parameter'] = $parameter;
	// first call of brick_format(): parameters are from URL
	if (!isset($brick['setting']['url_parameter'])) 
		$brick['setting']['url_parameter'] = $parameter;

	// initialize text at given position
	$brick['page']['text'][$brick['position']] = false;

	// standardize line breaks
	$block = str_replace(array("\r\n", "\r"), "\n", $block);
	// cut content and query blocks
	$blocks = explode('%%%', $block); 
	unset($block);
	
	$i = 0;
	$loop_start = array();
	$fast_forward = 0;
	$loop_parameter = array();
	$params = array();

	while (is_numeric(key($blocks))) { // used instead of foreach because we would like to jump back
		$index = key($blocks);
		$block = $blocks[$index];
		if ($index & 1) {	// even index values: textblock
							// uneven index values: %%%-blocks
			$brick['vars'] = brick_get_variables($block);
			$brick['type'] = array_shift($brick['vars']);
			$brick['type'] = trim($brick['type']);
			if ($brick['type'] === 'loop') {
				// loop means repeat a part of the block as long as there are still
				// parameters left
				// @todo all variables will be processed! if you have a lot of variables
				// for a loop and that loop is hidden, this will take quite a long time
				// for now, you better unset these variables
				// but there should be a better solution in zzbrick
				if ($brick['vars'][0] !== 'end' AND !$fast_forward) {
					// start loop
					$i++; // there's a loop
					$loop_start[$i] = $index; // start with the next index again
					if ($brick['vars'][0] === 'start') {
						// main record, numeric indices
						$loop_parameter[$i] = $brick['parameter'];
						// parameters with non-numeric indizes are not interesting
						// for loops, so get rid of them when handling with loops
						foreach (array_keys($loop_parameter[$i]) AS $loop_index)
							if (!is_numeric($loop_index)) unset($loop_parameter[$i][$loop_index]);
					} else {
						if (!empty($params[$i-1][$brick['vars'][0]])) {
							// e. g. 2 -> categories -> 4 -> category
							// it's in the main record (-1) that is also in a loop
							$loop_parameter[$i] = $params[$i-1][$brick['vars'][0]];
						} elseif (!empty($brick['parameter'][$brick['vars'][0]])) {
							// main record is not in a loop
							$loop_parameter[$i] = $brick['parameter'][$brick['vars'][0]];
						} else {
							$loop_parameter[$i] = array();
							if (!empty($brick['vars'][2])) {
								// output no data text
								$brick['page']['text'][$brick['position']] .= $brick['vars'][2];
							}
						}
						$loop_parameter[$i] = brick_loop_range($brick['vars'], $loop_parameter[$i]);
					}
					$loop_counter[$i] = count($loop_parameter[$i]);
					$loop_all[$i] = count($loop_parameter[$i]);
					if (!$loop_parameter[$i]) {
						// ooh, no data!
						// increase fast forward by 1 to go to loop end
						$fast_forward++;
					} else {
						if (!empty($brick['vars'][1])) {
							$brick['page']['text'][$brick['position']] .= $brick['vars'][1];
						}
						// set parameters for first loop, using most recently added loop parameters
						$params[$i] = array_shift($loop_parameter[$i]);
						// remove clutter
						if (!$loop_parameter[$i]) unset($loop_parameter[$i]);
					}
				} elseif ($brick['vars'][0] !== 'end') {
					// loop inside loop with no data: ignore this one and go on!
					$fast_forward++;
				} else {
					// end loop
					$fast_forward--;
					if ($fast_forward < 0) $fast_forward = 0;
					// set parameters for next loop
					$last_block = end($loop_start);
					// test if there is something AND if it's an array to avoid endless hanging in loop
					// in case someone puts accidentally an identical loop into another loop
					if (!empty($loop_parameter[$i]) AND is_array($loop_parameter[$i])) {
						$loop_counter[$i]--;
						// there are parameters, so go on and get most recently ... see above
						$params[$i] = array_shift($loop_parameter[$i]);
						// remove clutter
						if (!$loop_parameter[$i]) unset($loop_parameter[$i]);
						while (key($blocks) != $last_block) prev($blocks); // rewind
					} else {
						$there_was_data = false;
						array_pop($loop_start); // remove last loop
						if (!empty($params[$i])) {
							unset($params[$i]); // end of loop!
							$there_was_data = true;
						}
						$i--;
						// closing HTML if there is something
						if (!empty($brick['vars'][1]) AND $there_was_data)
							$brick['page']['text'][$brick['position']] .= $brick['vars'][1];
					}
				}
			} elseif (!$fast_forward) {
				// no loop, go on and do something with the brick
				// if there are parameters from the loop, get them!
				if (!empty($params[$i])) {
					$brick['loop_parameter'] = $params[$i];
					// if it's a loop in a loop, also get parameters from main loop
					// if they are not set in this loop, just for convenience
					if (!empty($params[$i-1])) {
						foreach ($params[$i-1] as $key => $value) {
							if (is_array($value)) continue;
							if (isset($brick['loop_parameter'][$key])) continue;
							$brick['loop_parameter'][$key] = $value;
						}
					}
					$brick['loop_all'] = $loop_all[$i];
					$brick['loop_counter'] = $loop_counter[$i];
				} else {
					$brick['loop_parameter'] = false;
				}
				$brick['cut_next_paragraph'] = false;
				// check whether $blocktype needs to be translated
				if (in_array($brick['type'], array_keys($brick['setting']['brick_types_translated']))) {
					$brick['subtype'] = $brick['type'];
					$brick['type'] = $brick['setting']['brick_types_translated'][$brick['type']];
				} else {
					$brick['subtype'] = '';
				}
				$brick['type'] = basename($brick['type']); // for security, allow only filenames
				$bricktype_file = dirname(__FILE__).'/'.$brick['type'].'.inc.php';
				$brick['path'] = $brick['setting']['brick_custom_dir'].$brick['type'];
				$brick['module_path'] = $brick['setting']['brick_module_dir'].$brick['type'];
				if (!$brick['access_blocked'] OR $brick['access_blocked'] === $brick['type']) {
					// just interpret bricks if access is not blocked
					// or if it is might get unblocked
					if (file_exists($bricktype_file)) {
						require_once $bricktype_file;
						$function_name = 'brick_'.$brick['type'];
						$brick = $function_name($brick);
					} else {
						// output error
						$brick['page']['text'][$brick['position']].= '<p><strong class="error">Error: 
							 '.$brick['type'].' is not a valid parameter.</strong></p>';
					}
				}
			}
		} elseif (!$fast_forward) {
			// behind %%% -- %%% blocks, an additional newline will appear
			// remove it, because we do not want it
			if ($index AND substr($block, 0, 1) == "\n"
				AND substr($block, 0, 2) != "\n\n")
				$block = substr($block, 1);
			// format text block (default mode)
			$text_to_add = brick_textformat($block, 'pieces', $brick['setting']['brick_fulltextformat']);
			// check if there's some </p>text<p>, remove it for inline results of function
			if ($brick['cut_next_paragraph'] && substr(trim($text_to_add), 0, 3) == '<p>') {
				$text_to_add = ' '.substr(trim($text_to_add), 3);
				$brick['cut_next_paragraph'] = false;
			}
			$brick['page']['text'][$brick['position']] .= $text_to_add; // if wichtig, 
				// sonst macht markdown auch aus leerer variable etwas
		}
		next($blocks);
	}

	$page = $brick['page'];
	unset($brick['page']);

	// Standard position, remove if empty
	if (!trim($page['text']['none'])) unset($page['text']['none']);
	// Hidden text? 403 access forbidden
	if (!empty($page['text']['_hidden_']) AND trim($page['text']['_hidden_'])) {
		$brick['access_forbidden'] = true;
	}
	unset($page['text']['_hidden_']);
	
	// check if it's html or different
	if (!empty($page['content_type']) AND $page['content_type'] != 'html') {
		// no formatting, if it's not HTML!
		$brick['setting']['brick_fulltextformat'] = 'html';
		$brick['setting']['brick_default_position'] = 'none';
	}
	
	if (count($page['text']) == 1 AND !empty($page['text']['none'])) {
	// if position is not wanted, remove unnecessary complexity in array
		if ($brick['setting']['brick_default_position'] == 'none') {
			$page['text'] = brick_textformat($page['text']['none'], 'full', 
				$brick['setting']['brick_fulltextformat']);
		} else {
			$page['text'][$brick['setting']['brick_default_position']] 
				= brick_textformat($page['text']['none'], 'full', 
					$brick['setting']['brick_fulltextformat']);
			unset ($page['text']['none']);
		}
	} elseif (!count($page['text']) AND $brick['access_forbidden']) {
	// no text, access forbidden
		$page = array(); // @todo: maybe unnecessary
		$page['status'] = 403;
	} else {
	// new
		foreach ($page['text'] AS $pos => $text) {
			$page['text'][$pos] = brick_textformat($text, 'full', 
				$brick['setting']['brick_fulltextformat']);
		}
	}
	// get stuff for page head in order
	$page['head'] = brick_head_format($page, $brick['setting']);
	// get simple access to extra-Array
	if (!empty($page['extra'])) foreach ($page['extra'] as $key => $value) {
		if (!is_array($value)) $page['extra_'.$key] = $value;
		else $page['extra_'.$key] = true;
	}
	// make sure, 'text' is not an array if empty
	if (empty($page['text'])) $page['text'] = '';
	return $page;
}

/**
 * allow loop start 1, loop start 1-, loop start -2 etc.
 * to select only a subset of a loop
 *
 * @param array $vars
 * @param array $params loop parameter
 * @return $params
 */
function brick_loop_range(&$vars, $params) {
	if (!$params) return array();
	if (empty($vars[1])) return $params;
	if (!preg_match('/^[0-9-]+$/', $vars[1])) return $params;
	if (strstr('-', $vars[1]) > 1) return $params;
	$range = explode('-', $vars[1]);
	unset($vars[1]);
	if (count($range) === 1) {
		$params = array_slice($params, $range[0] - 1, 1);
	} elseif (!$range[0]) {
		$params = array_slice($params, 0, $range[1] - 1);
	} elseif (!$range[1]) {
		$params = array_slice($params, $range[0] - 1);
	} else {
		$params = array_slice($params, $range[0] - 1, $range[1] - $range[0] - 1);
	}
	return $params;
}

/**
 * Transforms string into array of variables
 * 
 * Example: request news "John Doe" => 'request', 'news', 'John Doe'
 * @param string $block original string
 * @return array variables
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_get_variables($block) {
	$block = trim($block); // allow whitespace around '%%%'
	$variables = explode("\n", $block); // separated by newline
	if (count($variables) == 1) {
		$variables = explode(" ", $block); // or by space
		// put variables with spaces, but enclosed in "" back together
		unset($paste);
		foreach ($variables as $index => $var) {
			if (!isset($paste) AND substr($var, 0, 1) == '"'
				AND substr($var, -1) == '"'
				AND strlen($var) > 1) {
				// beginning and ending with "
				$var = substr($var, 1, -1);
				$variables[$index] = $var;
			} elseif (!isset($paste) AND substr($var, 0, 1) == '"') {
				// beginning with "
				$paste = substr($var, 1);
				unset($variables[$index]);
			} elseif (isset($paste) AND substr($var, -1) == '"') {
				// ending with "
				$variables[$index] = $paste.' '.substr($variables[$index], 0, -1);
				unset($paste);
			} elseif (isset($paste)) {
				$paste .= ' '.$var;
				unset($variables[$index]);
			}
		}
	}
	// get keys without gaps
	$variables = array_values($variables);
	return $variables;
}

/**
 * Transforms string into array of variables
 * 
 * Example: request news "John Doe" => 'request', 'news', 'John Doe'
 * @param string $block original string
 * @return array variables
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_textformat($string, $type, $fulltextformat) {
	if ($fulltextformat
		AND function_exists($fulltextformat)) {
		if ($type == 'pieces') {
			return $string;
		} elseif ($type == 'full') {
			// this makes markdown work with </div> bla <div>,
			// in case you close your standard box and try to open it again
			if ($fulltextformat == 'markdown')
				$string = '<div markdown="1">'.$string.'</div>';
			// preserve forms, do not apply any formatting to them!
			// this is useful for form elements because we do not want them to 
			// be formatted in any way at all
			$hash = 'someneverappearingsequenceofcharacters923skfjkdlxb';
			preg_match_all('~(<form.+</form>)~sUi', $string, $forms);
			foreach ($forms[0] as $index => $form) {
				$string = str_replace($form, $hash.sprintf("%04d", $index), $string);
			}
			$text = $fulltextformat($string);
			foreach ($forms[0] as $index => $form) {
				$text = str_replace($hash.sprintf("%04d", $index), $form, $text);
			}
			if ($fulltextformat == 'markdown')
				$text = substr($text, 6, -7);
			return $text;
		}
	} else {
		// Standard formatting, each piece will be treated seperately, for  
		// backwards compatibility
		if ($type == 'pieces') {
			return markdown($string);
		} elseif ($type == 'full') {
			return $string;
		}
	}
}

/**
 * Returns HTML as unchanged HTML
 * 
 * this function seems to be pretty useless but it is not. ;-)
 * @param string $string original string
 * @return string unmodified string
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_textformat_html($string) {
	return $string;
}

/**
 * Formats values in $page that should go into the HTML head section
 * 
 * @param array $page $page-Array from zzbrick()
 * @param array $setting $brick['setting']-Array from zzbrick()
 * @return array modified $page['head']
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_head_format($page, $setting) {
	$head = array();
	$i = 0;
	
	if (empty($page['head'])) $page['head'] = '';
	// @todo: insert $setting['page_base']; ? or do this in functions 
	if (!empty($page['link'])) foreach ($page['link'] AS $rel => $link) {
		if (!in_array(ucfirst($rel), $setting['html_link_types'])) continue;
		foreach ($link as $index) {
			if (!is_array($index)) continue;
			$head[$i] = '<link rel="'.$rel.'"';
			foreach ($index as $attribute => $value) {
				$head[$i] .= ' '.$attribute.'="'.$value.'"';
			}
			if (!empty($zz_setting['xml_close_empty_tags'])) $head[$i] .= ' /';
			$head[$i] .= '>';
			$i++;
		}
	}
	if (!empty($page['meta'])) {
		foreach ($page['meta'] as $index) {
			if (!is_array($index)) continue;
			$head[$i] = '<meta';
			foreach ($index as $attribute => $value)
				$head[$i] .= ' '.$attribute.'="'.$value.'"';
			if (!empty($zz_setting['xml_close_empty_tags'])) $head[$i] .= ' /';
			$head[$i] .= '>';
			$i++;
		}
	}
	if ($head) {
		// add new lines to already defined head elements in $page['head']
		$head = $page['head']."\t".implode("\n\t", $head)."\n";
	} else {
		$head = $page['head'];
	}
	return $head;
}

/** 
 * check for translations in form of %{'Hello'}%
 * to translate text, you might use a translation function
 * default: use wrap_text() from core/language.inc.php from zzwrap
 * 
 * @param string $string
 * @param array $settings = $brick['setting']
 * @return string translated string
 */
function brick_translate($string, $settings) {
	if (!strstr($string, "%{'")) return $string;
	if (!isset($settings['brick_translate_text_function']))
		$settings['brick_translate_text_function'] = 'wrap_text';
	$string = preg_replace_callback("~%{'(.+?)'}%~", $settings['brick_translate_text_function'], $string);
	return $string;
}

/**
 * send response to an xmlHTTPrequest
 *
 * @param string $xmlHttpRequest
 * @param string $block
 * @param string $parameter
 * @return array $page
 */
function brick_xhr($xmlHttpRequest, $parameter, $zz_setting = array()) {
	if (empty($zz_setting)) global $zz_setting;

	$xmlHttpRequest = json_decode($xmlHttpRequest, 1);
	$function = $xmlHttpRequest['httpRequest'];
	require_once $zz_setting['custom'].'/zzbrick_xhr/'.$function.'.inc.php';
	$function = 'cms_xhr_'.$function;
	$page['text'] = json_encode($function($xmlHttpRequest, $parameter));
	$page['status'] = 200;
	$page['content_type'] = 'json';
	return $page;
}

/**
 * read settings from vars, if they have an equal sign
 *
 * example: %%% forms table media * title=Files publish=0 %%%
 *
 * @param array $brick
 * @return array $brick
 */
function brick_local_settings($brick) {
	// get settings out of parameters
	$brick['local_settings'] = array();
	$url_settings = array_reverse($brick['vars']);
	foreach ($url_settings as $setting) {
		// '=' is not an allowed symbol for a folder identifier
		if (!strstr($setting, '=')) continue;
		parse_str($setting, $new_settings);
		if ($new_settings) {
			$brick['local_settings'] = array_merge($brick['local_settings'], $new_settings);
		}
		array_pop($brick['vars']);
	}
	return $brick;
}
