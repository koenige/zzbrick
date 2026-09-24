<?php 

/**
 * zzbrick
 * Links (will not link to self if link url = current url)
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2011, 2014, 2019, 2023-2024, 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * sets links
 * 
 * files: -
 * functions: -
 * settings:
 *		'brick_nolink_template', in case URL = current
 *		'absolute_urls', if true href uses host_base for internal paths
 * examples: 
 * 		%%% link / %%% 
 * 		%%% link / brick_nolink_template="<h1>" %%% 
 * 		%%% link /some/internal/link "Link text" %%% 
 * 		%%% link /some/internal/link "Link text" title="title text" %%% 
 * 		%%% link start /some/internal/link %%%
 * 		%%% link start /some/internal/link title="title text" %%%
 * 		%%% link end %%% 
 * 		%%% link end brick_nolink_template="</h1>" %%% 
 * @param array $brick
 * @return array $brick
 */
function brick_link($brick) {
	if (count($brick['vars']) < 1) return $brick;
	if (!isset($brick['page']['text'][$brick['position']]))
		$brick['page']['text'][$brick['position']] = [];
	$brick = brick_local_settings($brick);

	$vars = $brick['vars'];
	switch ($brick['vars'][0]) {
	case 'start':
		array_shift($vars);
		$brick['link_open'] = $link = array_shift($vars);
		break;
	case 'end':
		$link = $brick['link_open'];
		$brick['link_open'] = false;
		array_shift($vars);
		break;
	default:
		$link = array_shift($vars);
		break;
	}
	
	$text = '';
	$link = wrap_setting('base').$link;
	if (wrap_setting('request_uri') === $link) {
		$template = $brick['local_settings']['brick_nolink_template'] ?? wrap_setting('brick_nolink_template');
	} else {
		$link = wrap_path_add_absolute($link, wrap_setting('absolute_urls'));
		array_unshift($vars, $link);
		if (!empty($brick['local_settings']['title'])) {
			$vars[] = $brick['local_settings']['title'];
			$template = '<a href="%s" title="%3$s">%2$s</a>';
		} else {
			$template = '<a href="%s">%s</a>';
		}
	}

	if (strstr($template, '%s')) {
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
	} else {
		$text = $template;
	}
	
	$brick['page']['text'][$brick['position']][] = $text;
	return $brick;
}
