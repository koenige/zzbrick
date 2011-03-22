<?php 

// zzbrick
// (c) Gustaf Mossakowski, <gustaf@koenige.org> 2009
// text blocks, translated if applicable


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

	// Translate text
	if (!isset($brick['page']['text'][$brick['position']]))
		$brick['page']['text'][$brick['position']] = '';
	$brick['page']['text'][$brick['position']] 
		.= $brick['setting']['brick_translate_text_function'](implode(' ', $brick['vars']));
	unset ($brick['vars']);

	return $brick;
}

?>