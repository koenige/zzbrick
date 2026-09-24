<!--
# zzbrick
# Template include
#
# Part of »Zugzwang Project«
# https://www.zugzwang.org/modules/zzbrick
#
# @author Gustaf Mossakowski <gustaf@koenige.org>
# @copyright Copyright © 2026 Gustaf Mossakowski
# @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
#
# Variables
# audience = editor, programmer
-->

# include

Inserts another **brick template** at this point. The included file is
loaded with `wrap_template()` and its `%%% … %%%` blocks are processed
like the rest of the page. Includes run **before** other bricks in that
template (including `if` / `loop`).

## Syntax

```text
%%% include template-name %%%
%%% include value=parameter_key %%%
```

- **`template-name`** — name of a `.template.txt` file (no extension).
  Package paths are allowed, e.g. `zzform/pagedown-head`.
- **`value=…`** — use the template name from `$brick['parameter'][key]`
  (top-level keys on the data passed to `wrap_template()`, not fields on the
  current loop row). If that value is empty, the include is skipped.

## Examples

```text
%%% include logo %%%
%%% include zzform-actions %%%
%%% include searchform %%%
```

## Notes

- **Nested includes** are allowed; a template must not include itself
  (directly or in a cycle) — that triggers an error.
- A **missing** template adds nothing at that spot.
- Put includes on their own placeholder line when possible; the engine
  merges adjacent text blocks around the included content.

