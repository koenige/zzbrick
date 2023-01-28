<?php 

/**
 * zzbrick
 * Links (will not link to self if link url = current url)
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2011, 2014, 2019, 2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * sets links
 * 
 * files: -
 * functions: -
 * settings:
 *		'brick_nolink_template', in case URL = current
 * examples: 
 * 		%%% link /some/internal/link "Link text" %%% 
 * 		%%% link /some/internal/link "Link text" "title='title text'" %%% 
 * @param array $brick
 * @return array $brick
 */
function brick_link($brick) {
	if (count($brick['vars']) < 2) return $brick;
	if (!isset($brick['page']['text'][$brick['position']]))
		$brick['page']['text'][$brick['position']] = [];

	$text = '';
	$link = bricksetting('base').$brick['vars'][0];
	if ($_SERVER['REQUEST_URI'] === $link) {
		$text = sprintf(bricksetting('brick_nolink_template'), $brick['vars'][1]);
	} elseif (count($brick['vars']) === 2) {
		$template = '<a href="%s">%s</a>';
		$text = sprintf($template, $link, $brick['vars'][1]);
	} elseif (count($brick['vars']) === 3) {
		$template = '<a href="%s" %s>%s</a>';
		$text = sprintf($template, $link, $brick['vars'][2], $brick['vars'][1]);
	}
	$brick['page']['text'][$brick['position']][] = $text;
	return $brick;
}
