# Les-Tilleuls.coop — Brand foundation

On-brand design foundation extracted from the company deck template and
confirmed against the live theme CSS (les-tilleuls.coop). This is a
**foundation kit** — tokens, fonts and guidelines — not a coded component
library. Build UI with these tokens; the look comes from `styles.css`.

## How to use

- **Style with tokens.** Everything is a CSS custom property in `styles.css`'s
  `@import` closure (`tokens/colors.css`, `typography.css`, `spacing.css`).
  Reference `var(--lt-*)` — never hard-code hex.
- **Color**: one brand color, `--lt-orange` `#ee4322`, on a slate/grey/white
  base (`--lt-secondary` `#414956`, grey scale `#f0eded`→`#878d97`). No green;
  emphasis and totals are orange on neutral.
- **Type**: headings `var(--lt-font-display)` = **Neulis Alt** (500, commercial —
  license it); body `var(--lt-font-body)` = **Ubuntu** (400). Fallback stacks
  keep the geometric feel.
- **Signature shape**: the *tilleul leaf* — rounded rectangle with three large
  corners + one sharp (`--lt-leaf*`). Use for cards, photo masks, icon chips.
- **Idioms**: orange `→` arrow bullets (`.lt-bullets`), short orange title
  underline (`.lt-underline`), two-tone titles (charcoal + one orange word),
  pill CTA (`.lt-btn`).

See `guidelines/brand.md` for the full spec, layout patterns and do/don't.
Foundation preview cards live under `components/foundations/`.
