<?php 

/**
 * zzbrick
 * Links (will not link to self if link url = current url)
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2011, 2014, 2019, 2023-2024 Gustaf Mossakowski
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
 * 		%%% link start /some/internal/link %%%
 * 		%%% link start /some/internal/link "title='title text'" %%%
 * 		%%% link end %%% 
 * @param array $brick
 * @return array $brick
 */
function brick_link($brick) {
	if (count($brick['vars']) < 1) return $brick;
	if (!isset($brick['page']['text'][$brick['position']]))
		$brick['page']['text'][$brick['position']] = [];

	$vars = $brick['vars'];
	switch ($brick['vars'][0]) {
	case 'start':
		$brick['link_open'] = $link = $vars[1];
		array_shift($vars);
		array_shift($vars);
		break;
	case 'end':
		$link = $brick['link_open'];
		$brick['link_open'] = false;
		array_shift($vars);
		break;
	default:
		$link = $vars[0];
		break;
	}
	
	$text = '';
	$link = wrap_setting('base').$link;
	if (wrap_setting('request_uri') === $link) {
		$template = wrap_setting('brick_nolink_template');
	} else {
		array_unshift($vars, $link);
		if (count($brick['vars']) === 3)
			$template = '<a href="%s" %s>%s</a>';
		else
			$template = '<a href="%s">%s</a>';
	}

	switch ($brick['vars'][0]) {
	case 'end':
		$text = substr($template, strrpos($template, '%s') + 2);
		break;
	case 'start':
		$template = substr($template, 0, strrpos($template, '%s'));
	default:
		$text = vsprintf($template, $vars);
		break;
	}
	
	$brick['page']['text'][$brick['position']][] = $text;
	return $brick;
}
