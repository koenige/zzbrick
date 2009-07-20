<?php 

// zzbrick
// (c) Gustaf Mossakowski, <gustaf@koenige.org> 2009
// Main file

/*	Available functions:
	- brick_format()
		- brick_get_variables()
		- brick_textformat()
*/

/*

Concept

'brick' is a kind of a template language that is intended to allow easily
combination of html, text and placeholder blocks in content management systems.

'brick' will format all text with markdown and replace the placeholder blocks
with real content depending on which type they are.

syntax:
%%% query news 2004 %%%
'query' is the type of the placeholder block
	- format/query.inc.php knows what to do with this kind of placeholder
'news' is the function to be called (in s
	- format/query/news.inc.php will be included with a corresponding 
	function cms_news(); which will be called, other functions which are being
	accessed through cms_news() must be in the same file, in files starting with
	zzbrick/query/news_ or in the zzbrick/query/common.inc.php which will always
	be included
'2004' and further variables, separated by spaces, are variables which are
being passed to the function. variables must not include ", since this sign
is used to allow the use of whitespace in single variables, e. g. "2004 fall"


$page
$format['position'] == here goes the formatted output, if none is there, position is '_hide_'

*/

$zz_setting['brick_types_translated']['abfrage'] = 'request';
$zz_setting['brick_types_translated']['bild'] = 'request';
$zz_setting['brick_types_translated']['image'] = 'request';

$zz_setting['brick_types_translated']['rechte'] = 'rights';

$zz_setting['brick_types_translated']['kommentar'] = 'comment';

$zz_setting['brick_types_translated']['tables'] = 'forms';
$zz_setting['brick_types_translated']['tabellen'] = 'forms';
$zz_setting['brick_types_translated']['forms'] = 'forms';
$zz_setting['brick_types_translated']['publicforms'] = 'forms';
$zz_setting['brick_types_translated']['verwaltung'] = 'forms';

/** Format database content from CMS
 * 
 * @param $text(string) = text field read from database
 * @param $parameters(array) = parameters, via URL or via function
 * @return $page array
 		'title' => pagetitle (string)
 		'text' => textbody (string)
 		'breadcrumbs' => breadcrumbs (array)
 		'authors' => page authors (array)
 		'media' => page media (array)
 		'language_link' => link to same page in different language (...)
 		'status' => http status code
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_format($block, $parameter = false, $zz_setting = false) {
	if (empty($zz_setting)) global $zz_setting;
	// set defaults
	if (empty($zz_setting['default_position'])) 
		$zz_setting['default_position'] = 'none';
	if (empty($zz_setting['brick_types_translated']))
		$zz_setting['brick_types_translated'] = array();

	// initialize page variables
	$brick['page']['text'] = false;				// textbody
	$brick['page']['title'] = false;			// page title and h1 element
	$brick['page']['breadcrumbs'] = false;		// additional breadcrumbs
	$brick['page']['authors'] = array();		// authors of page
	$brick['page']['media'] = array();			// media linked from page
	$brick['page']['language_link'] = false;	// language links to other language(s)
	$brick['page']['status'] = 200;				// everything ok
	// further variables
	$brick['access_forbidden'] = false;			// access is allowed
	$brick['position'] = 'none';
	$brick['cut_next_paragraph'] = false;		// to get markdown formatted text inline
	$brick['replace_db_text'][$brick['position']] = false;
	$brick['parameter'] = $parameter;

	// initialize text at given position
	$brick['page']['text'][$brick['position']] = false;

	// standardize line breaks
	$block = str_replace(array("\r\n", "\r"), "\n", $block);
	// cut content and query blocks
	$blocks = explode('%%%', $block); 
	unset($block);

	// get include dir
	$brick_dir = $zz_setting['inc'].'/zzbrick';
	$include_dir = dirname($brick_dir);

	foreach ($blocks as $index => $block) {
		if ($index & 1) {	// even index values: textblock
							// uneven index values: %%%-blocks
			$brick['vars'] = brick_get_variables($block);
			$brick['type'] = array_shift($brick['vars']);
			$brick['cut_next_paragraph'] = false;
			// check whether $blocktype needs to be translated
			if (in_array($brick['type'], array_keys($zz_setting['brick_types_translated']))) {
				$brick['subtype'] = $brick['type'];
				$brick['type'] = $zz_setting['brick_types_translated'][$brick['type']];
			}
			$brick['type'] = basename($brick['type']); // for security, allow only filenames
			$bricktype_file = $brick_dir.'/'.$brick['type'].'.inc.php';
			$brick['path'] = $include_dir.'/'.$brick['type'];
			if (file_exists($bricktype_file)) {
				require_once $bricktype_file;
				$function_name = 'brick_'.$brick['type'];
				$brick = $function_name($brick);
			} else {
				// output error
				$brick['page']['text'][$brick['position']].= '<p><strong class="error">Error: 
					 '.$brick['type'].' is not a valid parameter.</strong></p>';
			}
		} else {
			// behind %%% -- %%% blocks, an additional newline will appear
			// remove it, because we do not want it
			if ($index AND substr($block, 0, 1) == "\n") $block = substr($block, 1);
			// format text block (default mode)
			$text_to_add = brick_textformat($block, 'pieces', $zz_setting);
			// check if there's some </p>text<p>, remove it for inline results of function
			if ($brick['cut_next_paragraph'] && substr(trim($text_to_add), 0, 3) == '<p>') {
				$text_to_add = ' '.substr(trim($text_to_add), 3);
				$brick['cut_next_paragraph'] = false;
			}
			$brick['page']['text'][$brick['position']] .= $text_to_add; // if wichtig, 
				// sonst macht markdown auch aus leerer variable etwas
		}
	}

	$page = $brick['page'];
	unset($brick['page']);
	// Standard position, remove if empty
	if (!trim($page['text']['none'])) unset($page['text']['none']);
	// Hidden text? 403 access forbidden
	if (!empty($page['text']['_hidden_'])) {
		unset($page['text']['_hidden_']);
		$brick['access_forbidden'] = true;
	}
	if (count($page['text']) == 1 AND !empty($page['text']['none'])) {
	// if position is not wanted, remove unneccessary complexity in array
		if ($zz_setting['default_position'] == 'none') {
			$page['text'] = brick_textformat($page['text']['none'], 'full', $zz_setting);
		} else {
			$page['text'][$zz_setting['default_position']] 
				= brick_textformat($page['text']['none'], 'full', $zz_setting);
			unset ($page['text']['none']);
		}
	} elseif (!count($page['text']) AND $brick['access_forbidden']) {
	// no text, access forbidden
		$page = false; // TODO: maybe unneccessary
		$page['status'] = 403;
	} else {
	// new
		foreach ($page['text'] AS $pos => $text) {
			$page['text'][$pos] = brick_textformat($text, 'full', $zz_setting);
		}
	}
	return $page;
}

/** Transforms string into array of variables
 * 
 * Example: request news "John Doe" => 'request', 'news', 'John Doe'
 * @param $block(string) original string
 * @return array variables
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_get_variables($block) {
	$block = trim($block); // allow whitespace around '%%%'
	$variables = explode("\n", $block); // separated by newline
	if (count($variables) == 1)
		$variables = explode(" ", $block); // or by space
	// put variables with spaces, but enclosed in "" back together
	$paste = false;
	foreach ($variables as $index => $var) {
		if (!$paste AND substr($var, 0, 1) == '"'
			// beginning and ending with "
			AND substr($var, -1) == '"') {
			$var = substr($var, 1, -1);
			$variables[$index] = $var;
		} elseif (!$paste AND substr($var, 0, 1) == '"') {
			// beginning with "
			$paste = substr($var, 1);
			unset($variables[$index]);
		} elseif ($paste AND substr($var, -1) == '"') {
			// ending with "
			$variables[$index] = $paste.' '.substr($variables[$index], 0, -1);
			$paste = false;
		} elseif ($paste) {
			$paste .= ' '.$var;
			unset($variables[$index]);
		}
	}
	// get keys without gaps
	$variables = array_values($variables);
	return $variables;
}

/** Transforms string into array of variables
 * 
 * Example: request news "John Doe" => 'request', 'news', 'John Doe'
 * @param $block(string) original string
 * @return array variables
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_textformat($string, $type, $zz_setting) {
	if (!empty($zz_setting['fulltextformat'])
		AND function_exists($zz_setting['fulltextformat'])) {
		if ($type == 'pieces') {
			return $string;
		} elseif ($type == 'full') {
			// this makes markdown work with </div> bla <div>,
			// in case you close your standard box and try to open it again
			if ($zz_setting['fulltextformat'] == 'markdown')
				$string = '<div markdown="1">'.$string.'</div>';
			$text = $zz_setting['fulltextformat']($string);
			if ($zz_setting['fulltextformat'] == 'markdown')
				$text = substr($text, 6, -7);
			return $text;
		}
	} else {
		// Standard formatting, each piece will be treated seperately, for backwards 
		// compatibility
		if ($type == 'pieces') {
			return markdown($string);
		} elseif ($type == 'full') {
			return $string;
		}
	}
}

?>