<!--
# zzbrick
# Brick loopposition
#
# Part of »Zugzwang Project«
# https://www.zugzwang.org/modules/zzbrick
#
# @author Gustaf Mossakowski <gustaf@koenige.org>
# @copyright Copyright © 2026 Gustaf Mossakowski
# @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
#
# Variables
# audience = programmer
-->

# loopposition

Output text or branch on conditions depending on the **current row** in a
`%%% loop … %%%`. Position is based on the **full loop** (`loop_all` rows),
not on how many rows your template actually prints (nested `%%% if … %%%`
still advance the counter).

Outside a loop, the placeholder outputs nothing; in
`%%% if loopposition … %%%` it evaluates to false.

## Placeholder syntax

```text
%%% loopposition <positions> <text> %%% 
%%% loopposition counter %%%
```

- **`<positions>`** — one rule, or several separated by `|` (OR). See
  [Position rules](#position-rules) below.
- **`<text>`** — string to output when a rule matches. Use quotes if it
  contains spaces.

### Variants

| Form | Output |
|------|--------|
| `%%% loopposition counter %%%` | Current **1-based line number** (no second token) |
| `%%% loopposition first setting my_key %%%` | Value of setting `my_key` when the rule matches |
| `%%% loopposition first my_function "arg" %%%` | Result of PHP function `my_function('arg')` when the rule matches |

Optional local settings (same line, via `brick_local_settings`):

- **`match=<field>`** — with `first` or `last` only: match the first or
  last row where **`<field>`** is truthy on that row. Requires
  `loop_items` on the brick. Use the **exact** row key (e.g. `full_text`);
  there is no `-` → `_` normalization.

## Condition syntax

The same position rules work in **`%%% if loopposition … %%%`** and
**`%%% unless loopposition … %%%`** (condition brick). No output text —
the block is shown or hidden.

```text
%%% if loopposition first %%%
…
%%% endif %%%

%%% if loopposition first match=teaser %%%
…
%%% endif %%%
```

## Position rules {#position-rules}

| Rule | True when |
|------|-----------|
| `first` | First row of a loop with **at least two** rows |
| `single` | The **only** row in a one-row loop |
| `last` | Last row of a loop with at least two rows |
| `middle` | Neither first nor last (needs at least three rows) |
| `odd`, `uneven` | 1-based line number is odd |
| `even` | 1-based line number is even |
| `counter` | (Placeholder only.) Outputs the 1-based line number |
| `n` (numeric) | Line `n` only (1-based), e.g. `5` |
| `%n` | Line number divisible by `n`, e.g. `%5` every fifth line |

Combine rules with **`|`** for OR, e.g. `first|single`, `middle|last`.

### `match=<field>` with `first` or `last`

Without `match=`, `first` / `last` refer to the **whole** loop. With
`match=fieldname`:

- **`first`** — first row where `fieldname` is truthy
- **`last`** — last row where `fieldname` is truthy

If `loop_items` is not available, these never match.

## Examples

Separators between items (skip after last row):

```text
%%% loop items %%% 
%%% item title %%% 
%%% loopposition middle|first " · " %%% 
%%% loop end %%%
```

Horizontal rule before the first body block that has `full_text`:

```text
%%% loopposition first match=full_text "<hr>" %%% 
%%% item full_text %%%
```

Every fifth row:

```text
%%% loopposition %5 "<br class=\"clear\">" %%% 
```

## Notes

- **`first`** without **`match=`** is not “first row where something is
  filled” — use **`first match=…`** for that.
- For translated `if` / `endif` labels, see **`brick_condition_translated`**
  in zzbrick configuration.

Implementation: `zzbrick/loopposition.inc.php`; conditions delegate from
`zzbrick/condition.inc.php`.
