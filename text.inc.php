<?php 

/**
 * zzbrick
 * Text blocks, translated if applicable
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2009 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * translates text
 * 
 * files: -
 * functions: -
 * settings: -
 * examples: 
 * 		%%% text hello %%% 
 * 		%%% text We like to use our CMS! %%%
 * @param array $brick
 * @return array $brick
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_text($brick) {
	// to translate text, you need to use a translation function
	// default: use wrap_text() from core/language.inc.php from zzwrap
	if (!isset($brick['setting']['brick_translate_text_function']))
		$brick['setting']['brick_translate_text_function'] = 'wrap_text';
	if (!isset($brick['setting']['brick_formatting_functions']))
		$brick['setting']['brick_formatting_functions'] = array();

	// Translate text
	if (!isset($brick['page']['text'][$brick['position']]))
		$brick['page']['text'][$brick['position']] = '';
	$function = end($brick['vars']);
	if (in_array($function, $brick['setting']['brick_formatting_functions'])) {
		array_pop($brick['vars']);	
	} else {
		$function = false;
	}
	$text = $brick['setting']['brick_translate_text_function'](implode(' ', $brick['vars'])); 
	if ($function) $text = $function($text);
	$brick['page']['text'][$brick['position']] .= $text;
	unset($brick['vars']);

	return $brick;
}

?>