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
			$msgid = mf_zzbrick_extract_template($chunk);
			if ($msgid === null) continue;
			wrap_extract_add($entries, $msgid, $reference, $pot);
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
 * Build msgid from a %%% text … %%% template chunk
 *
 * @param string $chunk inner part of the template text block
 * @return string|null
 */
function mf_zzbrick_extract_template($chunk) {
	$parsed = brick_get_variables($chunk);
	if (!$parsed['vars']) return null;

	if (count($parsed['vars']) > 1
		AND (str_contains($parsed['vars'][0], ' ')
			OR !empty($parsed['in_quotes'])
			OR !empty($parsed['quoted_indices'][0]))) {
		return $parsed['vars'][0];
	}
	return implode(' ', $parsed['vars']);
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
