<?php 

// zzbrick
// (c) Gustaf Mossakowski, <gustaf@koenige.org> 2011
// links (will not link to self if link url = current url)


/**
 * sets links
 * 
 * files: -
 * functions: -
 * settings: -
 * examples: 
 * 		%%% link /some/internal/link "Link text" %%% 
 * @param array $brick
 * @return array $brick
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 */
function brick_link($brick) {
	if (count($brick['vars']) !== 2) return $brick;
	if (!isset($brick['page']['text'][$brick['position']]))
		$brick['page']['text'][$brick['position']] = '';

	if ($_SERVER['REQUEST_URI'] === $brick['setting']['base'].$brick['vars'][0]) {
		$template = '%s';
		$text = sprintf($template, $brick['vars'][1]);
	} else {
		$template = '<a href="%s">%s</a>';
		$text = sprintf($template, $brick['vars'][0], $brick['vars'][1]);
	}
	$brick['page']['text'][$brick['position']] .= $text;
	return $brick;
}

?>