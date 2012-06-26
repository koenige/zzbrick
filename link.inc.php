<?php 

/**
 * zzbrick
 * Links (will not link to self if link url = current url)
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2011 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * sets links
 * 
 * files: -
 * functions: -
 * settings:
 *		'nolink_template', default '<strong>%s</strong>', in case URL = current
 * examples: 
 * 		%%% link /some/internal/link "Link text" %%% 
 * 		%%% link /some/internal/link "Link text" "title='title text'" %%% 
 * @param array $brick
 * @return array $brick
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_link($brick) {
	if (count($brick['vars']) < 2) return $brick;
	if (!isset($brick['setting']['nolink_template']))
		$brick['setting']['nolink_template'] = '<strong>%s</strong>';
	if (!isset($brick['page']['text'][$brick['position']]))
		$brick['page']['text'][$brick['position']] = '';

	$text = '';
	if ($_SERVER['REQUEST_URI'] === $brick['setting']['base'].$brick['vars'][0]) {
		$text = sprintf($brick['setting']['nolink_template'], $brick['vars'][1]);
	} elseif (count($brick['vars']) === 2) {
		$template = '<a href="%s">%s</a>';
		$text = sprintf($template, $brick['vars'][0], $brick['vars'][1]);
	} elseif (count($brick['vars']) === 3) {
		$template = '<a href="%s" %s>%s</a>';
		$text = sprintf($template, $brick['vars'][0], $brick['vars'][2], $brick['vars'][1]);
	}
	$brick['page']['text'][$brick['position']] .= $text;
	return $brick;
}

?>