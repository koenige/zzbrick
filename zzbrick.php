<?php 

// zzbrick
// (c) Gustaf Mossakowski, <gustaf@koenige.org> 2009
// Main file

/*

zzbrick Concept

'brick' is a kind of a template language that is intended to allow easily
combination of html, text and placeholder blocks in content management systems.

'brick' will format all text with markdown and replace the placeholder blocks
with real content depending on which type they are.

syntax:
%%% request news 2004 %%%
'query' is the type of the placeholder block
	- zzbrick/request.inc.php knows what to do with this kind of placeholder
'news' is the function to be called
	- zzbrick_request/news.inc.php will be included with a corresponding 
	function cms_news(); which will be called, other functions which are being
	accessed through cms_news() must be in the same file, in files starting with
	zzbrick_request/news_ or in the zzbrick_request/_common.inc.php which will 
	always be included
'2004' and further variables, separated by spaces, are variables which are
being passed to the function. variables must not include ", since this sign
is used to allow the use of whitespace in single variables, e. g. "2004 fall"

$page
$format['position'] == here goes the formatted output, if none is there, 
	position is '_hide_'

Variabes in $setting:
	- 'brick_custom_dir': directory for the customised brick scripts, zzwrap
		sets this to a default in $zz_setting['custom'], prefix zzbrick_
	- 'brick_default_position': if a matrix of the content is wanted, here you 
		can define a default position
	- 'brick_types_translated': here you can translate the first part of the
		zzbrick definition e. g. %%% abfrage ... ... %%% might be translated to
		request: $setting['brick_types_translated']['abfrage'] = 'request'
		this may also be used to define a certain subtype
	- 'brick_request_shortcuts'	shortcuts, that is you can write %%% image blubb 
		%%% instead of %%% request image blubb %%%
	- 'brick_username_in_session': Name of key from $_SESSION that will be used  
		as username for zzform(), default is 'username'
	- 'brick_authentification_file': file that will be included if
		authentification is needed for accessing the zzform scripts. might be 
		false, then no file will be included. zzwrap sets this automatically
	- 'brick_translate_text_function': Name of function to translate text; 
		zzwrap sets this to cms_text
	- 'brick_fulltextformat': name of function to format the complete output of
		brick_format instead of formatting each paragraph separately with 
		markdown
	- 'brick_ipfilter_translated': similar to brick_types_translated, here
		you can translate '=', ':' and '-' to different text
	- 'brick_ipv4_allowed_range': standard allowed range of IP adresses if
		no address is set in ipfilter
	- 'brick_rights_translated': similar to brick_types_translated, here
		you can translate '=', ':' and '-' to different text

Files where customisation will take place
	- zzbrick_rights/access_rights.inc.php
	- zzbrick_request/{request}.inc.php
	- zzbrick_request/_common.inc.php
	- zzbrick_forms/{tables}.inc.php
	- zzbrick_tables/{tables}.inc.php

Functions that contain customisations
	- cms_access_rights() - returns true if access is granted or false if no
		access is granted
	- cms_{request}() - returns $page-Array as defined in brick_format()

Available functions in this file:
	- brick_format()
		- brick_get_variables()
		- brick_textformat()

Available modules:
	- comment - comment blocks, won't be displayed
	- forms - includes zzform scripts via brick_format()
	- ipfilter - shows content only if client is in preset IP range
	- position - sets position of text block in a predefined matrix
	- redirect - redirects to another URL
	- request - outputs database queries in a formatted way, with URL parameters
	- rights - depending on the result of a custom function, access to the
		following content is allowed or forbidden (e. g. will be shown or not)
*/

/** Format database content from CMS
 * 
 * @param $text(string) = text field read from database
 * @param $parameters(array) = parameters, via URL or via function
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
 			this ID list in cms_get_authors()
		'last_update' => (date) last update of page, might be used for 
			last-modified header
		'created' => (date) creation date of page
		'project' => (string) project title; zzwrap: this will be output in
			TITLE instead of $zz_conf['project']
 		'status' => (int) http status code
		'head_addition' => (array) array of lines, html formatted, that will 
			be added in the HEAD section of the HTML document (if set in the
			template) - instead you can use the more specific:
		'meta' => (array), may be put in <meta name="{$key}" content="{$value}">
		'link' => (array), may be put in <link rel="{$key}" href="{$value}">
		'style' => (string) defines style of page, might be used to include
			different page heads or footers, separate css files and so on
		'extra' => (array), here you might use variables as you want, e. g.
			'headers' => (string) html formatted output for page HEAD
			'body_attributes' =>(string) html formatted output for BODY element
			'noindex' => (bool) decision whether to put a META noindex in HEAD
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

	foreach ($blocks as $index => $block) {
		if ($index & 1) {	// even index values: textblock
							// uneven index values: %%%-blocks
			$brick['vars'] = brick_get_variables($block);
			$brick['type'] = array_shift($brick['vars']);
			$brick['cut_next_paragraph'] = false;
			// check whether $blocktype needs to be translated
			if (in_array($brick['type'], array_keys($brick['setting']['brick_types_translated']))) {
				$brick['subtype'] = $brick['type'];
				$brick['type'] = $brick['setting']['brick_types_translated'][$brick['type']];
			}
			$brick['type'] = basename($brick['type']); // for security, allow only filenames
			$bricktype_file = dirname(__FILE__).'/'.$brick['type'].'.inc.php';
			$brick['path'] = $brick['setting']['brick_custom_dir'].$brick['type'];
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
			$text_to_add = brick_textformat($block, 'pieces', $brick['setting']['brick_fulltextformat']);
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
		$page = false; // TODO: maybe unneccessary
		$page['status'] = 403;
	} else {
	// new
		foreach ($page['text'] AS $pos => $text) {
			$page['text'][$pos] = brick_textformat($text, 'full', 
				$brick['setting']['brick_fulltextformat']);
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
			$text = $fulltextformat($string);
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

?>