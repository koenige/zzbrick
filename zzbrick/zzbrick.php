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
 * 	zzbrick_request/news_
 * '2004' and further variables, separated by spaces, are variables which are
 * being passed to the function. variables must not include ", since this sign
 * is used to allow the use of whitespace in single variables, e. g. "2004 fall"
 * 
 * $page
 * $format['position'] => here goes the formatted output, if none is there, 
 * 	position is '_hide_'
 * 
 * Files where customisation will take place
 * 	- zzbrick_rights/access_rights.inc.php
 * 	- zzbrick_request/{request}.inc.php
 * 	- zzbrick_forms/{tables}.php
 * 	- zzbrick_tables/{tables}.php
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
 * https://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2009-2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Format a textblock
 * 
 * @param string $block = text block to format
 * @param mixed $parameter = parameters, via URL or via function, string or array
 * @return $page array
	must have:
 		'text' => (string) textbody, html formatted
 			if false, zzwrap will return a 404 page. Textblocks that are 
 			optional need to return e. g. a ' '-string
	optional:
 		'title' => (string) pagetitle
 		'breadcrumbs' => (array) breadcrumbs, each breadcrumb has it's own index
 			might be 0 => '<a href="/">Page</a>' or
 			0 => ['url_path' => '/', 'title' => 'Page']
 		'language_link' => (string) link to same page in other language (...)
 		'dont_show_h1' => (bool) zzwrap: do not repeat page title in h1
 		'authors' => (array) IDs of page authors, zzwrap will post process
 			this ID list in wrap_get_authors()
		'last_update' => (date) last update of page, might be used for 
			last-modified header
		'created' => (date) creation date of page
		'project' => (string) project title; zzwrap: this will be output in
			TITLE instead of config variable 'project'
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
 */
function brick_format($block, $parameter = false) {
	if (!$block) return ['text' => ''];

	// initialize page variables
	$brick['page']['text'] = [];				// textbody
	$brick['page']['title'] = false;			// page title and h1 element
	$brick['page']['head'] = false;				// something for the head section
	$brick['page']['breadcrumbs'] = [];			// additional breadcrumbs
	$brick['page']['authors'] = [];				// authors of page
	$brick['page']['media'] = [];				// media linked from page
	$brick['page']['language_link'] = false;	// language links to other language(s)
	$brick['page']['status'] = 200;				// everything ok
	$brick['page']['url'] = '';					// own URL for JS redirects
	$brick['page']['content_type'] = NULL;

	// further variables
	$brick['access_forbidden'] = false;			// access is allowed
	$brick['access_blocked'] = '';
	$brick['position'] = 'none';
	$brick['cut_next_paragraph'] = false;		// to get markdown formatted text inline
	$brick['replace_db_text'][$brick['position']] = false;
	$check = brick_check_parameters($parameter);
	if (!$check) {
		$brick['page']['status'] = 404;
		return $brick['page'];
	}
	$brick['parameter'] = $parameter;
	// first call of brick_format(): parameters are from URL
	if (bricksetting('brick_url_parameter') === NULL)
		bricksetting('brick_url_parameter', $parameter);

	// initialize text at given position
	$brick['page']['text'][$brick['position']] = [];

	// standardize line breaks
	$block = str_replace(["\r\n", "\r"], "\n", $block);
	// cut content and query blocks
	$blocks = explode('%%%', $block); 
	unset($block);
	
	$i = 0;
	$loop_start = [];
	$fast_forward = 0;
	$loop_parameter = [];
	$params = [];

	// check for includes
	list($brick, $blocks) = brick_include($brick, $blocks);

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
						} elseif (strstr($brick['vars'][0], '/')) {
							$loop_vars = explode('/', $brick['vars'][0]);
							if (count($loop_vars) === 2 AND !empty($brick['parameter'][$loop_vars[0]][$loop_vars[1]])) {
								$loop_parameter[$i] = $brick['parameter'][$loop_vars[0]][$loop_vars[1]];
							} else {
								$loop_parameter[$i] = [];
							}
						} else {
							$loop_parameter[$i] = [];
							if (!empty($brick['vars'][2])) {
								// output no data text
								$brick['page']['text'][$brick['position']][] = $brick['vars'][2];
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
							$brick['page']['text'][$brick['position']][] = $brick['vars'][1];
						}
						// set parameters for first loop, using most recently added loop parameters
						$params[$i] = array_shift($loop_parameter[$i]);
						// remove clutter
						if (!$loop_parameter[$i]) unset($loop_parameter[$i]);
					}
				} elseif ($brick['vars'][0] !== 'end') {
					// loop inside loop with no data: ignore this one and go on!
					$fast_forward++;
				} elseif ($fast_forward > 1) {
					// end loop, but do not prepare for next loop
					// as this is still hidden
					$fast_forward--;
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
							$brick['page']['text'][$brick['position']][] = $brick['vars'][1];
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
							if (!is_array($brick['loop_parameter'])) continue; // @todo
							$brick['loop_parameter']['main__'.$key] = $value;
							// @deprecated: do not use main__ prefix
							if (array_key_exists($key, $brick['loop_parameter'])) continue;
							$brick['loop_parameter'][$key] = $value;
						}
					}
					$brick['loop_all'] = $loop_all[$i];
					$brick['loop_counter'] = $loop_counter[$i];
				} else {
					$brick['loop_parameter'] = [];
				}
				$brick = brick_format_placeholderblock($brick);
			}
		} elseif (!$fast_forward) {
			$brick = brick_format_textblock($brick, $block, $index);
		}
		next($blocks);
	}
	
	// closing section?
	if (!empty($brick['section'])) {
		$brick['section_close'] = true;
		$brick = brick_section($brick);
	}
	$brick = brick_blocks_cleanup($brick);

	foreach ($brick['page']['text'] as $index => $text) {
		$last_line = '';
		foreach ($text as $lineindex => $line) {
			if (!$line) continue; // ignore empty lines without whitespace
			if (substr($last_line, -2) === "\n\n" AND substr($line, 0, 2) === "\n\n")
				$line = $text[$lineindex] = substr($line, 2);
			elseif (substr($last_line, -1) === "\n" AND substr($line, 0, 1) === "\n")
				$line = $text[$lineindex] = substr($line, 1);
			$last_line = $line;
		}
		$brick['page']['text'][$index] = implode('', $text);
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
	
	$fulltextformat = bricksetting('brick_fulltextformat');
	
	// check if it's JSON, HTML or different
	if (bricksetting('send_as_json')) {
		if (!in_array($page['content_type'], ['json', 'geojson'])) {
			$page['content_type_original'] = $page['content_type'] ?? 'html';
			$page['content_type'] = 'json';
		}
	}
	if ($page['content_type'] AND $page['content_type'] != 'html') {
		// no formatting, if it's not HTML!
		$fulltextformat = 'html';
		bricksetting('brick_default_position', 'none');
	}
	
	if (count($page['text']) === 1 AND !empty($page['text']['none'])) {
	// if position is not wanted, remove unnecessary complexity in array
		if (bricksetting('brick_default_position') === 'none') {
			$page['text'] = brick_textformat($page['text']['none'], 'full', 
				$fulltextformat);
		} else {
			$page['text'][bricksetting('brick_default_position')] 
				= brick_textformat($page['text']['none'], 'full', $fulltextformat);
			unset ($page['text']['none']);
		}
	} elseif (!count($page['text']) AND $brick['access_forbidden']) {
	// no text, access forbidden
		$page = []; // @todo: maybe unnecessary
		$page['status'] = 403;
	} else {
	// new
		foreach ($page['text'] AS $pos => $text) {
			$page['text'][$pos] = brick_textformat($text, 'full', $fulltextformat);
		}
	}
	// get stuff for page head in order
	$page = brick_head_format($page);
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
 * @return array
 */
function brick_loop_range(&$vars, $params) {
	if (!$params) return [];
	if (empty($vars[1])) return $params;
	if (!preg_match('/^[0-9-]+$/', $vars[1])) return $params;
	if (strstr('-', $vars[1]) > 1) return $params;
	$range = explode('-', $vars[1]);
	unset($vars[1]);
	if (count($range) === 1)
		$params = array_slice($params, $range[0] - 1, 1);
	elseif (!$range[0])
		$params = array_slice($params, 0, $range[1] - 1);
	elseif (!$range[1])
		$params = array_slice($params, $range[0] - 1);
	else
		$params = array_slice($params, $range[0] - 1, $range[1] - $range[0] - 1);
	return $params;
}

/**
 * Transforms string into array of variables
 * 
 * Example: request news "John Doe" => 'request', 'news', 'John Doe'
 * @param string $block original string
 * @return array variables
 */
function brick_get_variables($block) {
	$block = trim($block); // allow whitespace around '%%%'
	$block = str_replace('  ', ' ', $block); // allow lax definition
	$variables = explode("\n", $block); // separated by newline
	if (count($variables) === 1) {
		$variables = explode(" ", $block); // or by space
		// put variables with spaces, but enclosed in "" back together
		unset($paste);
		foreach ($variables as $index => $var) {
			if (!isset($paste) AND substr($var, 0, 1) === '"'
				AND substr($var, -1) === '"'
				AND strlen($var) > 1) {
				// beginning and ending with "
				$var = substr($var, 1, -1);
				$variables[$index] = $var;
			} elseif (!isset($paste) AND substr($var, 0, 1) === '"') {
				// beginning with "
				$paste = substr($var, 1);
				unset($variables[$index]);
			} elseif (isset($paste) AND substr($var, -1) === '"') {
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
 * @param string $type 'pieces' or 'full'
 * @param string $fulltextformat
 * @return array variables
 */
function brick_textformat($string, $type, $fulltextformat) {
	if ($fulltextformat
		AND function_exists($fulltextformat)) {
		if ($type === 'pieces') {
			return $string;
		} elseif ($type === 'full') {
			// this makes markdown work with </div> bla <div>,
			// in case you close your standard box and try to open it again
			if ($fulltextformat === 'markdown')
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
			if ($fulltextformat === 'markdown')
				$text = substr($text, 6, -7);
			return $text;
		}
	} else {
		// Standard formatting, each piece will be treated seperately, for  
		// backwards compatibility
		if ($type === 'pieces') {
			if (!$string) return '';
			return markdown($string);
		} elseif ($type === 'full') {
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
 */
function brick_textformat_html($string) {
	return $string;
}

/**
 * Formats values in $page that should go into the HTML head section
 * 
 * @param array $page $page-Array from zzbrick()
 * @param bool $build build final HTML
 * @return array $page, modified
 */
function brick_head_format($page, $build = false) {
	static $tags = [];
	$keys = ['head', 'link', 'meta', 'opengraph'];
	foreach ($keys as $key) {
		if (empty($tags[$key])) $tags[$key] = [];
		if (empty($page[$key])) continue;
		if (is_array($page[$key])) {
			if (empty($tags[$key])) $tags[$key] = [];
			$tags[$key] = array_merge($tags[$key], $page[$key]);
		} else {
			// head
			$tags[$key][] = $page[$key];
		}
		unset($page[$key]); // avoid duplication
	}

	// head via %%% position head %%%
	if (!empty($page['text']) AND is_array($page['text']) AND in_array('head', array_keys($page['text']))) {
		$tags['head'][] = $page['text']['head'];
		unset($page['text']['head']);
		if (count($page['text']) === 1 AND !empty($page['text']['text'])) {
			$page['text'] = $page['text']['text'];
		}
	}

	if (!$build) return $page;
	return brick_head_format_html($page, $tags);
}

/**
 * build HTML output for HEAD
 *
 * @param array $page
 * @param array $tags
 * @return array
 */
function brick_head_format_html($page, $tags) {
	$head = [];
	$i = 0;
	
	// @todo: insert bricksetting('page_base'); ? or do this in functions 
	foreach ($tags['link'] AS $rel => $link) {
		if (!in_array(ucfirst($rel), bricksetting('brick_html_link_types'))) continue;
		foreach ($link as $index) {
			if (!is_array($index)) continue;
			$head[$i] = '<link rel="'.$rel.'"';
			foreach ($index as $attribute => $value) {
				$head[$i] .= ' '.$attribute.'="'.$value.'"';
			}
			if (!empty(bricksetting('xml_close_empty_tags'))) $head[$i] .= ' /';
			$head[$i] .= '>';
			$i++;
		}
	}
	if ($tags['opengraph'] OR !empty(bricksetting('opengraph'))) {
		$tags['meta'] = array_merge($tags['meta'], brick_head_opengraph($tags['opengraph'], $page));
		$meta_description = false;
		foreach ($tags['meta'] as $index => $meta_tag) {
			if (empty($meta_tag['property'])) continue;
			switch ($meta_tag['property']) {
			case 'description':
				$meta_description = true;
				break 2;
			case 'og:description':
				$meta_description = $meta_tag['content'];
				break;
			}
		}
		if ($meta_description AND $meta_description !== true) {
			$tags['meta'][] = ['name' => 'description', 'content' => $meta_description];
		}
	}
	foreach ($tags['meta'] as $index) {
		if (!is_array($index)) continue;
		$head[$i] = '<meta';
		foreach ($index as $attribute => $value)
			$head[$i] .= ' '.$attribute.'="'.$value.'"';
		if (!empty(bricksetting('xml_close_empty_tags'))) $head[$i] .= ' /';
		$head[$i] .= '>';
		$i++;
	}
	
	$page['head'] = '';
	if ($tags['head']) {
		$page['head'] = trim(implode("\n\t", $tags['head']));
		$page['head'] = sprintf("\t%s\n", $page['head']);
	}
	if ($head) {
		$page['head'] .= "\t".implode("\n\t", $head)."\n";
	}
	return $page;
}

/**
 * put OpenGraph tags on page
 *
 * @param array $tags
 * @param array $page
 * @return array
 */
function brick_head_opengraph($tags, $page) {
	// defaults
	if ($page['status'] !== 200) return [];
	$tags['og:title'] = $tags['og:title'] ?? (bricksetting('request_uri') === '/' ? bricksetting('project') : $page['title']);
	$tags['og:type'] = $tags['og:type'] ?? 'website';
	$tags['og:url'] = $tags['og:url'] ?? bricksetting('host_base').bricksetting('request_uri');
	$tags['og:site_name'] = $tags['og:site_name'] ?? bricksetting('project');
	$tags['og:locale'] = $tags['og:locale'] ?? bricksetting('lang');
	$tags['og:description'] = $tags['og:description'] ?? $page['description'];

	if (empty($tags['og:image'])) {
		$processed = false;
		if (function_exists('mf_media_opengraph_image')) {
			if (!empty($tags['image'])) {
				$tags += mf_media_opengraph_image($tags['image']);
				$processed = true;
			} elseif (bricksetting('opengraph') AND !empty($page['media']['images'])) {
				$tags += mf_media_opengraph_image(reset($page['media']['images']));
				$processed = true;
			}
		}
		if (!$processed AND bricksetting('active_theme')) {
		// default image: opengraph.png from theme folder
			$filename = sprintf('%s/%s/opengraph.png', bricksetting('themes_dir'), bricksetting('active_theme'));
			if (file_exists($filename)) {
				$size = getimagesize($filename);
				$tags['og:image'] = bricksetting('host_base').'/opengraph.png';
				$tags['og:image:width'] = $size[0];
				$tags['og:image:height'] = $size[1];
				$tags['og:image:alt'] = bricksetting('project').' '.wrap_text('Logo');
			}
		}
	}
	unset($tags['image']);

	// image is required
	if (empty($tags['og:image'])) return [];
	if (bricksetting('opengraph_properties')) {
		$global = explode(' ', bricksetting('opengraph_properties'));
		foreach ($global as $property) {
			list($property, $content) = explode('=', $property);
			if (!$content) continue;
			$tags[$property] = $tags[$property] ?? $content;
		}
	}

	$meta = [];
	foreach ($tags as $property => $list) {
		// there might be more than one property of the same name
		if (!is_array($list)) $list = [$list];
		foreach ($list as $content) {
			if (!$content) continue;
			$meta[] = [
				'property' => $property,
				'content' => $content
			];
		}
	}
	return $meta;
}

/** 
 * check for translations in form of %{'Hello'}%
 * 
 * @param string $string
 * @return string translated string
 */
function brick_translate($string) {
	if (!strstr($string, "%{'")) return $string;
	$string = preg_replace_callback(
		"~%{'(.+?)'}%~",
		function ($string) {
			return wrap_text($string[1]);
		},
		$string
	);
	return $string;
}

/**
 * get filename from brick type and function
 * look in custom folder first, then in module folders
 *
 * @param string $type
 * @param string $function
 * @return string name of function
 */
function brick_file($type, $function) {
	$function_name = str_replace('-', '_', $function);
	$file = sprintf(
		'%s%s/%s.inc.php', bricksetting('brick_custom_dir'), $type, $function
	);
	if (file_exists($file)) {
		require_once $file;
		return sprintf('cms_%s_%s', $type, $function_name);
	}
	foreach (bricksetting('modules') as $module) {
		$file = sprintf(
			'%s/%s%s%s/%s.inc.php', bricksetting('modules_dir'), $module
			, bricksetting('brick_module_dir'), $type, $function
		);
		if (!file_exists($file)) continue;
		require_once $file;
		if (function_exists('wrap_package_activate'))
			wrap_package_activate($module);
		else
			bricksetting('active_module', $module);
		return sprintf('mod_%s_%s_%s', $module, $type, $function_name);
	}
	return '';
}

/**
 * send response to an xmlHTTPrequest
 *
 * @param string $xmlHttpRequest
 * @param string $parameter
 * @return array $page
 */
function brick_xhr($xmlHttpRequest, $parameter) {
	$function = $xmlHttpRequest['httpRequest'];
	$file = bricksetting('custom').'/zzbrick_xhr/'.$function.'.inc.php';
	if (file_exists($file)) {
		require_once $file;
		$function = 'cms_xhr_'.str_replace('-', '_', $function);
	} else {
		$chosen_module = '';
		if (strstr($function, '-')) {
			$chosen_module = explode('-', $function);
			$function = $chosen_module[1];
			$chosen_module = $chosen_module[0];
		}
		foreach (bricksetting('modules') as $module) {
			if ($chosen_module AND $module !== $chosen_module) continue;
			if (file_exists($file = bricksetting('modules_dir').'/'.$module.'/zzbrick_xhr/'.$function.'.inc.php')) {
				require_once $file;
				bricksetting('active_module', $module);
				$function = 'mod_'.$module.'_xhr_'.$function;
				break;
			}
		}
	}
	if (!function_exists($function)) {
		$page['status'] = 503;
	} else {
		$return = $function($xmlHttpRequest, $parameter);
		if (!empty($return['_query_strings'])) {
			$page['query_strings'] = $return['_query_strings'];
			unset($return['_query_strings']);
		}
		$page['text'] = json_encode($return);
		$page['status'] = 200;
		$page['content_type'] = 'json';
	}
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
	$brick['local_settings'] = [];
	$url_settings = array_reverse($brick['vars']);
	$append = [];
	foreach ($url_settings as $setting) {
		// allow settings in quotation marks, but don’t do anything if there is
		// no =" finally
		if ($append) {
			$append[] = $setting;
			if (!strstr($setting, '="')) continue;
			$append = array_reverse($append);
			$setting = implode('%20', $append);
			// ok, we found something, so now we can remove the variables
			for ($i = 0; $i < count($append) - 1; $i++) {
				array_pop($brick['vars']);
			}
			$append = [];
		} elseif (str_ends_with($setting, '"')) {
			$append[] = $setting;
			continue;
		}
		// '=' is not an allowed symbol for a folder identifier
		if (!strstr($setting, '=')) continue;
		parse_str($setting, $new_settings);
		foreach ($new_settings as $index => $new_setting) {
			if (is_array($new_setting)) {
				foreach ($new_setting as $nindex => $nnew_setting) {
					if (substr($nnew_setting, 0, 1) === '[' AND substr($nnew_setting, -1) === ']') {
						$new_settings[$index][$nindex] = explode(',', substr($nnew_setting, 1, -1));
					}
				}
			} else {
				if (substr($new_setting, 0, 1) === '[' AND substr($new_setting, -1) === ']') {
					$new_settings[$index] = explode(',', substr($new_setting, 1, -1));
				} elseif (str_starts_with($new_setting, '"')) {
					$new_settings[$index] = trim($new_setting, '"');
				}
			}
		}
		if ($new_settings) {
			$brick['local_settings'] = array_merge_recursive($brick['local_settings'], $new_settings);
		}
		array_pop($brick['vars']);
	}
	return $brick;
}

/**
 * call placeholder script for parameter inside zzbrick_placeholder folder
 * activated with local parameter *=script, where 'script' is the script to call
 *
 * @param array $brick
 * @return array
 */
function brick_placeholder_script($brick) {
	if (empty($brick['local_settings']['*'])) return $brick;
	$placeholders = explode(',', $brick['local_settings']['*']);
	$brick['placeholders'] = !empty($brick['parameter']) ? explode('/', $brick['parameter']) : [];
	foreach ($placeholders as $index => $placeholder) {
		$placeholder = trim($placeholder);
		$function = brick_file('placeholder', $placeholder);
		if (!function_exists($function)) continue;
		if (array_key_exists($index, $brick['placeholders']))
			$brick['placeholder'] = $brick['placeholders'][$index];
		$brick = $function($brick);
	}
	return $brick;
}

/**
 * Call a placeholder function
 *
 * @param array $brick
 * @return array
 */
function brick_format_placeholderblock($brick) {
	// don't cut text after placeholders
	$brick['cut_next_paragraph'] = false;

	// check whether $blocktype needs to be translated
	$brick_types_translated = bricksetting('brick_types_translated');
	if (array_key_exists($brick['type'], $brick_types_translated)) {
		$brick['subtype'] = $brick['type'];
		$brick['type'] = $brick_types_translated[$brick['type']];
	} else {
		$brick['subtype'] = '';
	}

	// for security, allow only filenames
	$brick['type'] = basename($brick['type']);

	// just interpret bricks if access is not blocked
	// or if it is might get unblocked
	if ($brick['access_blocked'] AND $brick['access_blocked'] !== $brick['type'])
		return $brick;

	// include file
	$bricktype_file = __DIR__.'/'.$brick['type'].'.inc.php';
	$brick['path'] = bricksetting('brick_custom_dir').$brick['type'];
	$brick['module_path'] = bricksetting('brick_module_dir').$brick['type'];
	$function_name = 'brick_'.$brick['type'];
	if (!function_exists($function_name) AND file_exists($bricktype_file)) {
		require_once $bricktype_file;
	}

	// call function
	if (function_exists($function_name)) {
		$brick = $function_name($brick);
	} else {
		// output error
		$brick['page']['text'][$brick['position']][] = '<p><strong class="error">Error: 
			 '.$brick['type'].' is not a valid parameter.</strong></p>';
	}

	return $brick;
}

/**
 * Just format text block (default mode)
 *
 * @param array $brick
 * @param array $block
 * @param int $index
 * @return array
 */
function brick_format_textblock($brick, $block, $index) {
	// behind %%% -- %%% blocks, an additional newline will appear
	// remove it, because we do not want it
	if ($index AND substr($block, 0, 1) === "\n"
		AND substr($block, 0, 2) != "\n\n")
		$block = substr($block, 1);
	if ($block) {
		$text_to_add = brick_textformat($block, 'pieces', bricksetting('brick_fulltextformat'));
		// check if there's some </p>text<p>, remove it for inline results of function
		if ($brick['cut_next_paragraph'] && substr(trim($text_to_add), 0, 3) === '<p>') {
			$text_to_add = ' '.substr(trim($text_to_add), 3);
			$brick['cut_next_paragraph'] = false;
		}
	} else {
		$text_to_add = '';
	}
	$brick['page']['text'][$brick['position']][] = $text_to_add;
	return $brick;
}

/**
 * include other templates
 *
 * @param array $brick
 * @param array $blocks
 * @return array
 */
function brick_include($brick, $blocks = []) {
	static $includes = [];

	// not replaced include because of error? has no blocks
	// @see brick_format_placeholderblock()
	if (empty($blocks)) return $brick;

	if (count($blocks) === 1)
		return [$brick, $blocks];

	$pos = -1;
	foreach ($blocks as $index => $block) {
		$pos++;
		if ($index & 1) {
			$block = explode(' ', trim($block));
			if ($block[0] !== 'include') continue;
			$block[1] = trim($block[1]);
			if (in_array($block[1], $includes)) {
				$brick['page']['error']['level'] = E_USER_ERROR;
				$brick['page']['error']['msg_text']
					= 'Template %s includes itself';
				$brick['page']['error']['msg_vars'] = [$block[1]];
				return [$brick, $blocks];
			}
			$includes[] = $block[1];
			$tpl = wrap_template($block[1], [], 'error');
			$new_blocks = explode('%%%', $tpl);
			list($brick, $new_blocks) = brick_include($brick, $new_blocks);
			// there now are two or three text blocks adjacent, glue them together
			if (count($new_blocks) > 1) {
				if (isset($blocks[$pos - 1])) {
					$first_new_block = array_shift($new_blocks);
					$blocks[$pos - 1] .= $first_new_block;
				}
				if (isset($blocks[$pos + 1])) {
					$last_new_block = array_pop($new_blocks);
					if ($new_blocks) {
						$blocks[$pos + 1] = $last_new_block.$blocks[$pos + 1];
					} else {
						$blocks[$pos - 1] .= $last_new_block.$blocks[$pos + 1];
						unset($blocks[$pos + 1]);
					}
				}
				array_splice($blocks, $pos, 1, $new_blocks);
			} else {
				$new_block = array_shift($new_blocks);
				if (array_key_exists($pos - 1, $blocks) AND array_key_exists($pos + 1, $blocks)) {
					// in the middle
					$blocks[$pos - 1] .= $new_block.$blocks[$pos + 1];
					unset($blocks[$pos]);
					unset($blocks[$pos + 1]);
				} elseif (array_key_exists($pos - 1, $blocks)) {
					// at the beginning
					$blocks[$pos - 1] .= $new_block;
					unset($blocks[$pos]);
				} elseif (array_key_exists($pos + 1, $blocks)) {
					// at the end
					$blocks[$pos + 1] = $new_block.$blocks[$pos + 1];
					unset($blocks[$pos]);
				} else {
					// only block
					$blocks[$pos] = $new_block;
				}
			}
			$pos += count($new_blocks);
		}
	}
	return [$brick, $blocks];
}

/**
 * move blocks from page text to blocks_definition
 *
 * @param array $blocks
 * @return array
 */
function brick_blocks_cleanup($brick) {
	if (empty($brick['blocks'])) return $brick;

	foreach ($brick['blocks'] as $block) {
		if (!array_key_exists('zzblock-'.$block, $brick['page']['text'])) continue;
		$brick['blocks_definition'][$block] = $brick['page']['text']['zzblock-'.$block];
		unset($brick['page']['text']['zzblock-'.$block]);
	}
	return $brick;
}

/**
 * check blocks if they match
 *
 * @param string $block block part between %%% markings
 * @param string $match block check if matches
 * @return bool
 */
function brick_blocks_match($block, $match) {
	$block_brick = brick_block_split($block);
	$match_brick = brick_block_split($match);
	if ($block_brick === $match_brick) return true;

	// check vars	
	foreach ($match_brick['vars'] as $index => $value) {
		if (empty($block_brick['vars'][$index])) return false;
		if ($block_brick['vars'][$index] !== $value) return false;
	}

	// check local settings
	if (!$block_brick['local_settings']) return true; // no settings = match
	if (!$match_brick['local_settings']) return true; // no settings = match
	if ($block_brick['local_settings'] === $match_brick['local_settings']) return true;

	
	foreach ($block_brick['local_settings'] as $key => $values) {
		if (!array_key_exists($key, $match_brick['local_settings'])) return false;
		if (!is_array($match_brick['local_settings'][$key]))
			$match_brick['local_settings'][$key] = [$match_brick['local_settings'][$key]];
		foreach ($match_brick['local_settings'][$key] as $value)
			if (!in_array($value, $values)) return false;
	}
	return true;
}

/**
 * split block in brick vars and local_settings
 *
 * @param string $block
 * @param array $brick (optional)
 * @return array
 */
function brick_block_split($block, $brick = []) {
	$brick['vars'] = brick_get_variables($block);
	$brick = brick_local_settings($brick);
	return $brick;
}

/**
 * merge existing $brick['page'] array with one returned from function
 *
 * @param array $page = $brick['page'] or similar
 * @param array $content
 * @return array merged $brick
 */
function brick_merge_page_bricks($page, $content) {
	static $status = false;

	// get some content from the function and overwrite existing values
	$overwrite_bricks = [
		'title', 'dont_show_h1', 'language_link', 'error_type',
		'last_update', 'style', 'project', 'dont_show_title_as_breadcrumb',
		'created', 'headers', 'url_ending', 'no_output', 'template',
		'content_type', 'redirect', 'send_as_json', 'url', 'h1',
		'description'
	];
	foreach ($overwrite_bricks as $part) {
		if (!empty($content[$part]))
			$page[$part] = $content[$part];	
	}
	
	// status is not overwritten if new status is 404
	if (!empty($content['status'])) {
		// status is 200 per default, but if it was set once explicitly to 200
		// keep it that way
		if ($status !== 200) {
			$page['status'] = $content['status'];
			$status = $content['status'];
		}
	}

	// get even more content from the function and merge with existing values
	// extra: for all individual needs, not standardized
	$merge_bricks = [
		'authors', 'media', 'head', 'extra', 'meta', 'link', 'error',
		'query_strings', 'breadcrumbs', 'opengraph'
	];
	foreach ($merge_bricks as $part) {
		if (!empty($content[$part]) AND is_array($content[$part])) {
			if (empty($page[$part])) $page[$part] = [];
			$page[$part] = array_merge($page[$part], $content[$part]);
		} elseif (!empty($content[$part])) {
			if (empty($page[$part])) $page[$part] = '';
			// check if part of that string is already on page, then don't repeat it!
			if (stripos($page[$part], $content[$part]) === false)
				$page[$part] .= $content[$part];
		}
	}
	return $page;
}

/**
 * add function prefix for brick_formatting_functions
 *
 * @param string $function
 * @return string
 */
function brick_format_function_prefix($function) {
	$formatting_functions_prefix = bricksetting('brick_formatting_functions_prefix');
	if ($formatting_functions_prefix AND array_key_exists($function, $formatting_functions_prefix))
		$function = $formatting_functions_prefix[$function].'_'.$function;
	return $function;
}

/**
 * check if parameters from URL are normalised
 * if not, throw a 404
 *
 * @param mixed $parameters
 * @return bool false: error, throw 404
 */
function brick_check_parameters($parameters) {
	$return = true;
	if (!is_array($parameters))
		$parameters = explode('/', $parameters);
	foreach ($parameters as $index => $parameter) {
		if (!is_int($index)) continue;
		if (is_array($parameter)) continue;
		if (!$parameter) continue;
		if ($parameter === '*') continue;
		if (strstr($parameter, '%')) {
			// valid sequence?
			if (strtolower(wrap_detect_encoding(urldecode($parameter))) === bricksetting('character_set')) continue;
		} else {
			if (wrap_filename($parameter, '', [
				'.' => '.', '+' => '+', '_' => '_', '(' => '(', ')' => ')', '-' => '-'
			]) === $parameter.'') continue;
		}
		$return = false;
	}
	return $return;
}

/**
 * read or write settings
 *
 * @param string $key
 * @param mixed $value (if set, assign value, if not read value)
 * @return mixed
 */
function bricksetting($key, $value = NULL) {
	return wrap_setting($key, $value);
}
