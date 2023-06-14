<?php 

/**
 * zzbrick
 * add/define contents of block
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/projects/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020, 2022 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * add/define contents of block
 * 
 * files: -
 * functions: -
 * settings: -
 * examples: 
 * 		%%% block name-of-block %%% 
 * 		%%% block definition name-of-block %%% 
 * 		%%% block definition end %%% 
 * @param array $brick
 * @return array $brick
 */
function brick_block($brick) {
	if (!array_key_exists('blocks', $brick))
		$brick['blocks'] = [];

	if (count($brick['vars']) === 2)
		return brick_block_definition($brick);
	elseif (count($brick['vars']) === 1)
		return brick_block_output($brick);

	return $brick;
}

function brick_block_output($brick) {
	if (!in_array($brick['vars'][0], $brick['blocks'])) return $brick;
	
	$block = array_shift($brick['vars']);

	if (array_key_exists('zzblock-'.$block, $brick['page']['text'])) {
		$brick['page']['text'][$brick['position']]
			 = array_merge(
			 	$brick['page']['text'][$brick['position']],
			 	$brick['page']['text']['zzblock-'.$block]
			 );
	} elseif (array_key_exists('blocks_definition', $brick)
		AND array_key_exists($block, $brick['blocks_definition'])) {
		$brick['page']['text'][$brick['position']]
		 	= array_merge(
			 	$brick['page']['text'][$brick['position']],
			 	$brick['blocks_definition'][$block]
			 );
	}
	return $brick;
}

function brick_block_definition($brick) {
	static $position_old = '';
	$type = array_shift($brick['vars']);
	if (!in_array($type, ['default', 'definition'])) {
		$brick['page']['error']['level'] = E_USER_WARNING;
		$brick['page']['error']['msg_text']
			= 'Wrong type for block: %s is not possible';
		$brick['page']['error']['msg_vars'] = [$type];
		return $brick;
	}
	$block = array_shift($brick['vars']);

	if ($block === 'end') {
		$brick['position'] = $position_old;
		$position_old = false;
	} else {
		$position_old = $brick['position'];
		if ($type === 'default' AND in_array($block, $brick['blocks']))
			$brick['position'] = '_hidden_';
		else
			$brick['position'] = 'zzblock-'.$block;
		$brick['blocks'][] = $block;
	}

	if (empty($brick['page']['text'][$brick['position']])) {
		$brick['page']['text'][$brick['position']] = []; // initialisieren
		$brick['replace_db_text'][$brick['position']] = false;
	}
	return $brick;
}
