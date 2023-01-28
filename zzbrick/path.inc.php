<?php 

/**
 * zzbrick
 * path
 *
 * Part of »Zugzwang Project«
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
	switch (count($brick['vars'])) {
		case 1:
			$text = wrap_path($brick['vars'][0]);
			break;
		case 2:
			$text = wrap_path($brick['vars'][0], $brick['vars'][1]);
			break;
		case 3:
			if ($brick['vars'][3] === 'check_rights=0')
				$text = wrap_path($brick['vars'][0], $brick['vars'][1], false);
			elseif ($brick['vars'][3] === 'check_rights=1')
				$text = wrap_path($brick['vars'][0], $brick['vars'][1], true);
			else
				$text = wrap_path($brick['vars'][0], $brick['vars'][1]);
			break;
	}
	$brick['page']['text'][$brick['position']][] = $text;
	return $brick;
}
