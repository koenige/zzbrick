<?php 

/**
 * zzbrick
 * extract functions for zzwrap
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/zzbrick
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Register zzbrick extract handlers
 *
 * @return array list of handler definitions
 */
function mf_zzbrick_extract_register() {
	return [
		[
			'match' => ['*.template.txt', '*.css', '*.js'],
			'scan' => 'mf_zzbrick_extract_scan_template',
		],
		[
			'match' => '*.php',
			'scan' => 'mf_zzbrick_extract_scan_php',
		],
	];
}

/**
 * Scan a template/CSS/JS file for %%% text … %%% blocks
 *
 * @param string $content file contents with Unix line endings
 * @param string $relative_path path relative to package folder
 * @param array $entries collected entries (by reference)
 * @return void
 */
function mf_zzbrick_extract_scan_template($content, $relative_path, &$entries) {
	$pot = wrap_extract_translate_pot($content);
	$lines = explode("\n", $content);

	foreach ($lines as $line_number => $line) {
		if (!preg_match_all('/%%% text (.+?) %%%/', $line, $matches)) continue;
		$reference = sprintf('%s:%d', $relative_path, $line_number + 1);
		foreach ($matches[1] as $chunk) {
			$entry = mf_zzbrick_extract_template($chunk);
			if ($entry === null) continue;
			wrap_extract_add($entries, $entry['msgid'], $reference, $pot, $entry['context']);
		}
	}
}

/**
 * Scan a PHP file for brick_xhr_error() message literals
 *
 * @param string $content file contents with Unix line endings
 * @param string $relative_path path relative to package folder
 * @param array $entries collected entries (by reference)
 * @return void
 */
function mf_zzbrick_extract_scan_php($content, $relative_path, &$entries) {
	if (!preg_match_all(
		'/brick_xhr_error\s*\(\s*[^,]+,\s*(\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"\\\\]|\\\\.)*")(?=[\s,\)])/',
		$content, $matches, PREG_OFFSET_CAPTURE
	)) return;

	$pot = wrap_extract_translate_pot($content);
	foreach ($matches[1] as $match) {
		$msgid = mf_zzbrick_brick_xhr_error($match[0]);
		if ($msgid === null) continue;
		$reference = sprintf(
			'%s:%d', $relative_path,
			wrap_extract_line_number($content, $match[1])
		);
		wrap_extract_add($entries, $msgid, $reference, $pot);
	}
}

/**
 * Build msgid and msgctxt from a %%% text … %%% template chunk
 *
 * Local settings (e.g. context=club) are stripped via brick_local_settings().
 *
 * @param string $chunk inner part of the template text block
 * @return array|null keys msgid, context; null if empty
 */
function mf_zzbrick_extract_template($chunk) {
	$brick = brick_local_settings(brick_get_variables($chunk));
	if (!$brick['vars']) return null;

	if (count($brick['vars']) > 1
		AND (str_contains($brick['vars'][0], ' ')
			OR !empty($brick['in_quotes'])
			OR !empty($brick['quoted_indices'][0]))) {
		$msgid = $brick['vars'][0];
	} else {
		$msgid = implode(' ', $brick['vars']);
	}

	return [
		'msgid' => $msgid,
		'context' => $brick['local_settings']['context'] ?? '',
	];
}

/**
 * Build msgid from a brick_xhr_error() message string literal (2nd argument)
 *
 * @param string $chunk quoted string including delimiters
 * @return string|null
 */
function mf_zzbrick_brick_xhr_error($chunk) {
	return wrap_extract_msg_literal($chunk);
}
