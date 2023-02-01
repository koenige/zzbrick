<?php 

/**
 * zzbrick
 * path
 *
 * Part of ÈZugzwang ProjectÇ
 * http://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * read a path
 * 
 * files: -
 * functions: -
 * settings: -
 * examples:
 *		%%% path area %%%
 *		%%% path area value %%%
 * @param array $brick	Array from zzbrick
 * @return array $brick
 */
function brick_path($brick) {
	$text = '';
	if (count($brick['vars']) > 1)
		if (is_array($brick['loop_parameter']) AND array_key_exists($brick['vars'][1], $brick['loop_parameter']))
			$parameter = $brick['loop_parameter'][$brick['vars'][1]];
		elseif (is_array($brick['parameter']) AND array_key_exists($brick['vars'][1], $brick['parameter']))
			$parameter = $brick['parameter'][$brick['vars'][1]];
		else
			$parameter = '';

	switch (count($brick['vars'])) {
		case 1:
			$text = wrap_path($brick['vars'][0]);
			break;
		case 2:
			$text = wrap_path($brick['vars'][0], $parameter);
			break;
		case 3:
			if ($brick['vars'][3] === 'check_rights=0')
				$text = wrap_path($brick['vars'][0], $parameter, false);
			elseif ($brick['vars'][3] === 'check_rights=1')
				$text = wrap_path($brick['vars'][0], $parameter, true);
			else
				$text = wrap_path($brick['vars'][0], $parameter);
			break;
	}
	$brick['page']['text'][$brick['position']][] = $text;
	return $brick;
}
