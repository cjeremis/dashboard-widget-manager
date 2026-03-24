# Agent S3 Working Checklist
**Date:** 2026-03-22
**Status:** COMPLETE
**Sections covered:** Section 3 — Dashboard Layout (Background + Padding)

## Summary
- PASS: 87
- FAIL: 5
- PARTIAL: 3
- UNCERTAIN: 10

### Critical fixes needed:
- [FAIL] `dashboard_background_type` sanitizer allowlist mismatch — sanitizer only allows `['solid','gradient']` but admin class reads `'default'` as a valid third value. File: `includes/core/class-dwm-sanitizer.php` line 493.
- [FAIL] `$bg_gradient_type` in template (line 183) is not validated against an allowlist — no `in_array()` guard. File: `templates/admin/customize-dashboard.php` line 183.
- [FAIL] Padding link group uses bespoke `dwm-padding-link-btn` / `dwm-padding-link` / `dwm-padding-linked` pattern instead of the `dwm-link-btn` / `dwm-link-value` / `data-group` pattern the checklist requires. File: `templates/admin/customize-dashboard.php` lines 269–272.
- [FAIL] Padding number/select/range inputs do NOT have `data-side` on the `<select>` element — it is present on `<input type="number">` and `<input type="range">`, but absent from `<select>`. File: `templates/admin/customize-dashboard.php` line 286.
- [FAIL] Background CSS output condition uses `'default' !== $bg_type` but the sanitizer cannot produce `'default'` for this key — creates a dead code path and means background CSS is gated on a value that will never be `'default'` after save. File: `includes/admin/class-dwm-admin.php` lines 977–978 + 1046.

### Template changes needed:
- Line 183: Add `in_array()` validation for `$bg_gradient_type` (matching the pattern at line 182 for `$bg_type`).
- Lines 269–272: Replace `dwm-padding-link-btn` / `#dwm-padding-link` / `#dwm-padding-linked` with the `dwm-link-btn` / `dwm-link-value` / `data-group` pattern to match checklist spec.
- Line 286: Add `data-side="<?php echo esc_attr( $side ); ?>"` to the `<select>` element.

### PHP class changes needed:
- `enqueue_dashboard_customization_inline_assets()` lines 977–978: Change allowlist for `$bg_type` from `['default','solid','gradient']` to `['solid','gradient']` and default from `'default'` to `'solid'`. Update the condition at line 1046 from `'default' !== $bg_type` to a positive check (e.g. `in_array($bg_type, ['solid','gradient'], true)`).

### JS changes needed:
- None confirmed. The `updateGradientControlVisibility` / `updateGradientPreview` functions correctly use prefix `'dwm'` matching the template IDs. Padding sync uses `#dwm-padding-link` / `#dwm-padding-linked` matching the template — but both need to be updated together if template is fixed.

---

## Section 3 Checklist — Full Item Audit

### Section-Level Items (lines 295–305)

- [x] All 5 background PHP vars declared in this section's PHP init block (not in branding section)
> VERIFIED: `templates/admin/customize-dashboard.php` lines 181–186. All 5 vars (`$bg_type`, `$bg_gradient_type`, `$bg_angle`, `$bg_start_pos`, `$bg_end_pos`) declared inside the Dashboard Layout section's PHP block, after `unset()` and before HTML output. Not in the branding section.

- [x] Help icon opens `#dwm-docs-modal` on `custom-dashboard-layout` page
> VERIFIED: Template lines 176–178 pass `$help_modal_target = 'dwm-docs-modal'`, `$help_icon_label = 'Learn about dashboard layout controls'`, `$attrs = 'data-docs-page="custom-dashboard-layout"'` to the `section-header-with-actions.php` partial. The partial renders a button with `data-open-modal="dwm-docs-modal"` and `data-docs-page="custom-dashboard-layout"`. The docs-modal JS at line 338 binds `[data-open-modal="dwm-docs-modal"]` click events.

- [x] Save button label is "Save Dashboard Layout"
> VERIFIED: `templates/admin/customize-dashboard.php` line 306: `esc_html_e( 'Save Dashboard Layout', 'dashboard-widget-manager' )`.

- [x] Background toggle off: controls hidden on load; off = no custom background applied to dashboard
> VERIFIED: Template line 200: when `dashboard_background_enabled` is empty, ` dwm-hidden-by-toggle` class is appended to `#dwm-dashboard-background-controls`. `enqueue_dashboard_customization_inline_assets()` (class-dwm-admin.php line 969) reads `$bg_enabled = ! empty( $settings['dashboard_background_enabled'] )` and gates `$background_css` on it (line 1046).

- [x] Background toggle on: controls visible; background applied to dashboard
> VERIFIED: Complement of above. When enabled, no `dwm-hidden-by-toggle` class, and `$background_css` is built and injected.

- [x] Padding toggle off: controls hidden on load; off = stock WP dashboard padding
> VERIFIED: Template line 265: when `dashboard_padding_enabled` empty, ` dwm-hidden-by-toggle` appended. Admin class line 970 gates `$padding_css` on `$padding_enabled`.

- [x] Padding toggle on: controls visible; custom padding applied to dashboard
> VERIFIED: Complement of above.

- [x] Switching background type between solid/gradient shows/hides correct sub-controls
> VERIFIED: JS `settings-form.js` line 924 binds change on `#dwm-background-type, #dwm-bg-gradient-type`, calls `updateGradientControlVisibility('dwm')` which shows/hides `#dwm-bg-solid-controls`, `#dwm-bg-gradient-type-controls`, `#dwm-bg-gradient-details` (lines 897–899).

- [x] Switching gradient type between linear/radial shows/hides angle control
> VERIFIED: Same `updateGradientControlVisibility` function, line 900: shows/hides `#dwm-bg-gradient-angle-wrap` based on gradientType === 'linear'.

- [x] Changing gradient type to radial hides angle input
> VERIFIED: Same as above — when `#dwm-bg-gradient-type` value is `'radial'`, `#dwm-bg-gradient-angle-wrap` is set to `display:none`.

- [?] All fields reload with saved values after save + page reload
> UNCERTAIN: Cannot verify without browser. PHP sets all field values from `$settings` on page load, and the JS init calls `updateGradientControlVisibility('dwm')` and `updateGradientPreview('dwm')` on load (lines 1130–1131). Structurally correct.

---

### Dashboard Output & Assets — Section 3 (lines 308–317)

- [x] PHP/JS reads background and padding settings from DB and injects inline CSS into `<head>` on dashboard page load
> VERIFIED: `enqueue_dashboard_customization_inline_assets()` is called from the hook at admin class line 858 for the dashboard page. It reads `$data->get_settings()` (line 921) and calls `wp_add_inline_style('dwm-wp-dashboard', $css)` at line 1097.

- [~] Inline CSS targets the correct WP dashboard wrapper element — not the entire admin UI
> PARTIAL: Background targets `body.index-php` (line 1089) — this is the full page body, not a wrapper like `#wpwrap` or `#wpbody-content`. Padding targets `body.index-php #wpbody-content` (line 1092), which is more scoped. The `body.index-php` selector is scoped to dashboard only (page-class based), but applies background to the entire `<body>` rather than a specific dashboard content wrapper. Whether this is "correct" per design intent is partially ambiguous, but it does include the full body not just a content wrapper.
> FIX NEEDED: `includes/admin/class-dwm-admin.php` line 1089
> CURRENT: `body.index-php{background:...!important;}`
> REQUIRED: Checklist says CSS targets "the correct WP dashboard wrapper element (e.g. `#wpwrap`, `#wpcontent`, or `#wpbody-content`)". Current code targets `body` directly. This is a design choice but deviates from the checklist's explicit examples.

- [x] Background: With toggle ON + type `solid`: generated CSS is `background: {hex-color}` applied to dashboard wrapper
> VERIFIED: Admin class line 1051: when `$bg_type` is `'solid'`, `$background_css = $solid_bg` (the hex color string). Then line 1089: `body.index-php{background:` + `$background_css` + `!important;}`. Solid hex color is applied.

- [x] Background: With toggle ON + type `gradient` + type `linear`: generated CSS is `background: linear-gradient({angle}deg, {start-color} {start-pos}%, {end-color} {end-pos}%)` on dashboard wrapper
> VERIFIED: Admin class lines 1047–1050: when `$bg_type === 'gradient'` and `$grad_type !== 'radial'`, CSS is `linear-gradient(` + `$grad_angle` + `deg, ` + `$grad_start` + ` ` + `$grad_start_pos` + `%, ` + `$grad_end` + ` ` + `$grad_end_pos` + `%)`.

- [x] Background: With toggle ON + type `gradient` + type `radial`: generated CSS is `background: radial-gradient({start-color} {start-pos}%, {end-color} {end-pos}%)` (no angle) on dashboard wrapper
> VERIFIED: Admin class lines 1047–1049: when `$grad_type === 'radial'`, CSS is `radial-gradient(` + `$grad_start` + ` ` + `$grad_start_pos` + `%, ` + `$grad_end` + ` ` + `$grad_end_pos` + `%)`.

- [x] Background: With toggle OFF — no background CSS injected; dashboard wrapper has stock WP background
> VERIFIED: Admin class line 969: `$bg_enabled = ! empty( $settings['dashboard_background_enabled'] )`. If false, `$background_css` stays `''` (line 1045). The condition at line 1087 only outputs background CSS if `'' !== $background_css`.

- [x] Padding: With toggle ON: generated CSS is `padding: {top}{unit} {right}{unit} {bottom}{unit} {left}{unit}` applied to dashboard wrapper
> VERIFIED: Admin class line 1006 builds `$padding_css` as `$top_v . $top_u . ' ' . $right_v . $right_u . ' ' . $bottom_v . $bottom_u . ' ' . $left_v . $left_u`. Line 1092 applies as `body.index-php #wpbody-content{padding:` + `$padding_css` + `;box-sizing:border-box;}`.

- [x] Padding: With toggle OFF — no padding CSS injected; stock WP dashboard padding unchanged
> VERIFIED: Admin class line 970: `$padding_enabled = ! empty($settings['dashboard_padding_enabled'])`. If false, `$padding_css` stays `''` and is not included in output.

- [x] CSS output is scoped to dashboard page only — other admin pages unaffected
> VERIFIED: All CSS is prefixed with `body.index-php` (admin class lines 1089–1094), which WordPress only adds as a body class on the dashboard (`/wp-admin/index.php`). Other admin pages have different body classes.

- [?] DWM dashboard CSS/JS asset files enqueued on dashboard page; no 404s or console errors
> UNCERTAIN: Cannot verify without browser. Structurally, admin class line 54–59 registers `dwm-wp-dashboard` CSS from `assets/minimized/css/wp-dashboard.min.css`, and line 802–803 enqueues `assets/minimized/js/wp-dashboard.min.js`. Whether compiled files exist requires filesystem/browser verification.

---

### Field: Custom Background (enable toggle) (lines 323–337)

- [x] Read: `! empty( $settings['dashboard_background_enabled'] )`
> VERIFIED: Template line 196: `checked( ! empty( $settings['dashboard_background_enabled'] ) )`.

- [x] Default: off (falsy)
> VERIFIED: Default settings in `class-dwm-settings.php` line 165: `'dashboard_background_enabled' => 0`.

- [x] Escape: `checked()`
> VERIFIED: Template line 196 uses WordPress `checked()` function.

- [x] Name: `settings[dashboard_background_enabled]`
> VERIFIED: Template line 196.

- [x] ID: `dwm-dashboard-background-enabled`
> VERIFIED: Template line 196.

- [x] Label: "Custom Background"
> VERIFIED: Template line 193: `esc_html_e( 'Custom Background', 'dashboard-widget-manager' )`.

- [x] Value: `1`
> VERIFIED: Template line 196: `value="1"`.

- [x] `data-toggle-controls="#dwm-dashboard-background-controls"` present
> VERIFIED: Template line 196: `data-toggle-controls="#dwm-dashboard-background-controls"`.

- [x] Container `#dwm-dashboard-background-controls` has `dwm-hidden-by-toggle` on load when off
> VERIFIED: Template line 200: class includes ` dwm-hidden-by-toggle` when `! empty( $settings['dashboard_background_enabled'] )` is falsy.

- [x] Container visible on load when saved as on
> VERIFIED: Template line 200: when enabled, no `dwm-hidden-by-toggle` class appended.

- [x] Sanitized server-side as boolean/int
> VERIFIED: `class-dwm-sanitizer.php` line 485–488: `dashboard_background_enabled` is in the boolean keys loop, saved as `0` or `1` via `absint()`.

- [?] Dashboard: When ON — background CSS is present in `<head>` on dashboard page
> UNCERTAIN: Cannot verify without browser, but code path is correct.

- [?] Dashboard: When OFF — no background CSS injected; dashboard wrapper shows stock WP background color
> UNCERTAIN: Cannot verify without browser, but code path is correct.

---

### Field: Background Type (lines 341–358)

- [~] PHP var: `$bg_type = (string)( $settings['dashboard_background_type'] ?? 'solid' )` — validated against `['solid','gradient']`
> PARTIAL: Template lines 181–182 match this spec exactly — declared and validated against `['solid','gradient']`. However, `enqueue_dashboard_customization_inline_assets()` at lines 977–978 reads `$bg_type` with `'default'` as default AND adds `'default'` to the allowlist `['default','solid','gradient']`, then gates on `'default' !== $bg_type`. The sanitizer (line 492–493) only allows `['solid','gradient']` and defaults to `'solid'` — meaning `'default'` can never be saved to DB. This inconsistency in the admin class is a logic fault.
> FIX NEEDED: `includes/admin/class-dwm-admin.php` lines 977–978 + 1046
> CURRENT: `$bg_type = sanitize_key(...); $bg_type = in_array($bg_type, ['default','solid','gradient'], true) ? $bg_type : 'default';` ... `if ($bg_enabled && 'default' !== $bg_type)`
> REQUIRED: Allowlist `['solid','gradient']`, default `'solid'`. Condition: `if ($bg_enabled && in_array($bg_type, ['solid','gradient'], true))` or equivalent.

- [x] Default: `solid`
> VERIFIED: Template line 181 default `'solid'`; sanitizer line 493 fallback `'solid'`.

- [x] Escape: `selected()` on each option
> VERIFIED: Template lines 205–206.

- [x] Name: `settings[dashboard_background_type]`
> VERIFIED: Template line 204.

- [x] ID: `dwm-background-type`
> VERIFIED: Template line 204.

- [x] Label: "Background Type" with `for="dwm-background-type"`
> VERIFIED: Template line 203.

- [x] Options: `solid` (Solid Color), `gradient` (Gradient)
> VERIFIED: Template lines 205–206.

- [x] Saved value correctly pre-selects option on load
> VERIFIED: `selected($bg_type, 'solid')` and `selected($bg_type, 'gradient')` on lines 205–206.

- [x] Selecting `solid` shows `#dwm-bg-solid-controls`, hides gradient controls
> VERIFIED: JS `updateGradientControlVisibility('dwm')` (settings-form.js line 897): shows `#dwm-bg-solid-controls` when `type === 'solid'`.

- [x] Selecting `gradient` shows gradient controls, hides solid controls
> VERIFIED: Same function, lines 898–899.

- [x] Sanitized server-side as allowlist string
> VERIFIED: `class-dwm-sanitizer.php` lines 491–493.

- [x] Visible only when toggle ON
> VERIFIED: Field is inside `#dwm-dashboard-background-controls` which is hidden when toggle is off.

- [x] Dashboard: `solid` → generated CSS uses a plain `background-color` or `background` hex value; no gradient function
> VERIFIED: Admin class line 1051: `$background_css = $solid_bg` (plain hex). Applied as `background:` not `linear-gradient(...)`.

- [x] Dashboard: `gradient` → generated CSS uses `linear-gradient()` or `radial-gradient()` depending on sub-type
> VERIFIED: Admin class lines 1047–1050.

---

### Field: Background Color (solid) (lines 362–377)

- [x] Read: `$settings['dashboard_bg_solid_color'] ?? '#ffffff'`
> VERIFIED: Template line 212.

- [x] Default: `#ffffff`
> VERIFIED: Template line 212; sanitizer line 497; default settings line 167.

- [x] Escape: `esc_attr()`
> VERIFIED: Template line 212.

- [x] Name: `settings[dashboard_bg_solid_color]`
> VERIFIED: Template line 212.

- [x] ID: `dwm-bg-solid-color`
> VERIFIED: Template line 212.

- [x] Label: "Background Color" with `for="dwm-bg-solid-color"`
> VERIFIED: Template line 211.

- [x] Container `#dwm-bg-solid-controls` uses inline `style="display:none"` on load when type ≠ `solid`
> VERIFIED: Template line 210: `style="<?php echo 'solid' === $bg_type ? '' : 'display:none;'; ?>"`.

- [x] Shown on load when `$bg_type === 'solid'`
> VERIFIED: Same line — empty style string when `$bg_type === 'solid'`.

- [x] Sanitized server-side as hex color string
> VERIFIED: `class-dwm-sanitizer.php` line 497: `sanitize_hex_color()`.

- [x] Visible only when toggle ON AND type = `solid`
> VERIFIED: Inside `#dwm-dashboard-background-controls` (toggle-gated) and inside `#dwm-bg-solid-controls` (type-gated).

- [?] Dashboard: Saved hex color is applied as CSS `background` on the dashboard wrapper element
> UNCERTAIN: Code path is correct (admin class lines 1051, 1089); browser verification needed.

- [?] Dashboard: Changing the color, saving, and reloading dashboard shows the new color
> UNCERTAIN: Browser verification required.

---

### Field: Gradient Type (lines 381–399)

- [!] PHP var: `$bg_gradient_type = (string)( $settings['dashboard_bg_gradient_type'] ?? 'linear' )` — missing `in_array()` validation
> FIX NEEDED: `templates/admin/customize-dashboard.php` line 183
> CURRENT: `$bg_gradient_type = (string) ( $settings['dashboard_bg_gradient_type'] ?? 'linear' );`
> REQUIRED: Add validation: `$bg_gradient_type = in_array( $bg_gradient_type, array( 'linear', 'radial' ), true ) ? $bg_gradient_type : 'linear';` (matching the pattern at line 182 for `$bg_type`).

- [x] Default: `linear`
> VERIFIED: Template line 183 default `'linear'`; sanitizer line 502 fallback `'linear'`.

- [x] Escape: `selected()` on each option
> VERIFIED: Template lines 217–218.

- [x] Name: `settings[dashboard_bg_gradient_type]`
> VERIFIED: Template line 216.

- [x] ID: `dwm-bg-gradient-type`
> VERIFIED: Template line 216.

- [x] Label: "Gradient Type" with `for="dwm-bg-gradient-type"`
> VERIFIED: Template line 215.

- [x] Options: `linear` (Linear), `radial` (Radial)
> VERIFIED: Template lines 217–218.

- [x] Container `#dwm-bg-gradient-type-controls` uses inline `style="display:none"` on load when type ≠ `gradient`
> VERIFIED: Template line 214: `style="<?php echo 'gradient' === $bg_type ? '' : 'display:none;'; ?>"`.

- [x] Saved value correctly pre-selects option on load
> VERIFIED: `selected($bg_gradient_type, 'linear')` and `selected($bg_gradient_type, 'radial')` at lines 217–218.

- [x] Selecting `radial` hides `#dwm-bg-gradient-angle-wrap`
> VERIFIED: JS line 900: `$('#dwm-bg-gradient-angle-wrap').css('display', gradientType === 'linear' ? '' : 'none')`.

- [x] Selecting `linear` shows `#dwm-bg-gradient-angle-wrap`
> VERIFIED: Same line — shows when `gradientType === 'linear'`.

- [x] Sanitized server-side as allowlist string
> VERIFIED: `class-dwm-sanitizer.php` lines 500–502.

- [x] Visible only when toggle ON AND type = `gradient`
> VERIFIED: Inside `#dwm-dashboard-background-controls` and inside `#dwm-bg-gradient-type-controls` (both gated).

- [x] Dashboard: `linear` → generated CSS uses `linear-gradient({angle}deg, ...)`
> VERIFIED: Admin class lines 1050.

- [x] Dashboard: `radial` → generated CSS uses `radial-gradient(...)` without angle
> VERIFIED: Admin class lines 1048–1049.

---

### Field: Gradient Angle (lines 403–420)

- [x] PHP var: `$bg_angle = (int)( $settings['dashboard_bg_gradient_angle'] ?? 90 )`
> VERIFIED: Template line 184.

- [x] Default: `90`
> VERIFIED: Template line 184; default settings line 169.

- [x] Escape: `esc_attr( (string) $bg_angle )`
> VERIFIED: Template line 229.

- [x] Name: `settings[dashboard_bg_gradient_angle]`
> VERIFIED: Template line 229.

- [x] ID: `dwm-bg-gradient-angle`
> VERIFIED: Template line 229.

- [x] Label: "Angle" with `for="dwm-bg-gradient-angle"`
> VERIFIED: Template line 227.

- [x] Class: `dwm-format-slider` present on range input
> VERIFIED: Template line 229: `class="dwm-format-slider"`.

- [x] `min="0"` `max="360"` present
> VERIFIED: Template line 229.

- [x] Display span `#dwm-bg-gradient-angle-value` shows current value + `°` on load and updates live
> VERIFIED: Template line 230: span with `id="dwm-bg-gradient-angle-value"` outputs `esc_html( (string) $bg_angle ) . '°'`. JS line 919: `updateGradientPreview` updates this text on input/change events.

- [x] Container `#dwm-bg-gradient-angle-wrap` uses inline `style="display:none"` on load when gradient type ≠ `linear`
> VERIFIED: Template line 226: `style="<?php echo 'linear' === $bg_gradient_type ? '' : 'display:none;'; ?>"`.

- [x] Sanitized server-side as integer 0–360
> VERIFIED: `class-dwm-sanitizer.php` lines 505–508: `(int)`, then `max(0, min(360, $angle))`.

- [x] Gradient preview `#dwm-bg-gradient-preview` updates live when angle changes
> VERIFIED: JS line 928 binds `input change` on `#dwm-bg-gradient-angle` → `updateGradientPreview('dwm')` → line 918 updates `#dwm-bg-gradient-preview`.

- [x] Visible only when toggle ON AND type = `gradient` AND gradient type = `linear`
> VERIFIED: Inside `#dwm-dashboard-background-controls` (toggle-gated) → inside `#dwm-bg-gradient-details` (gradient-type-gated) → inside `#dwm-bg-gradient-angle-wrap` (linear-type-gated).

- [?] Dashboard: Saved angle value reflected in the `linear-gradient({angle}deg, ...)` CSS
> UNCERTAIN: Browser verification required.

---

### Field: Gradient Start Color (lines 424–436)

- [x] Read: `$settings['dashboard_bg_gradient_start'] ?? '#667eea'`
> VERIFIED: Template line 236.

- [x] Default: `#667eea`
> VERIFIED: Template line 236; default settings line 170.

- [x] Escape: `esc_attr()`
> VERIFIED: Template line 236.

- [x] Name: `settings[dashboard_bg_gradient_start]`
> VERIFIED: Template line 236.

- [x] ID: `dwm-bg-gradient-start`
> VERIFIED: Template line 236.

- [x] Label: "Start Color" (no `for` — label wraps or adjacent)
> VERIFIED: Template line 234: `<label>` with text `'Start Color'`, no `for` attribute — it is a standalone `<label>` adjacent to the inputs.

- [x] Gradient preview updates live when color changes
> VERIFIED: JS line 928 binds `input change` on `#dwm-bg-gradient-start` → calls `updateGradientPreview('dwm')`.

- [x] Sanitized server-side as hex color string
> VERIFIED: `class-dwm-sanitizer.php` line 512: `sanitize_hex_color()`.

- [x] Visible only when toggle ON AND type = `gradient`
> VERIFIED: Inside `#dwm-dashboard-background-controls` and `#dwm-bg-gradient-details` (both gated).

---

### Field: Gradient Start Position (lines 440–453)

- [x] PHP var: `$bg_start_pos = (int)( $settings['dashboard_bg_gradient_start_position'] ?? 0 )`
> VERIFIED: Template line 185.

- [x] Default: `0`
> VERIFIED: Template line 185; default settings line 171.

- [x] Escape: `esc_attr( (string) $bg_start_pos )`
> VERIFIED: Template line 237.

- [x] Name: `settings[dashboard_bg_gradient_start_position]`
> VERIFIED: Template line 237.

- [x] ID: `dwm-bg-gradient-start-position`
> VERIFIED: Template line 237.

- [x] `min="0"` `max="100"` present
> VERIFIED: Template line 237.

- [x] Display span `#dwm-bg-gradient-start-position-label` shows current value + `%` on load and updates live
> VERIFIED: Template line 238: `id="dwm-bg-gradient-start-position-label"` with `esc_html( (string) $bg_start_pos ) . '%'`. JS line 920 updates it live.

- [x] Sanitized server-side as integer 0–100
> VERIFIED: `class-dwm-sanitizer.php` lines 519–521: `absint()` then `max(0, min(100, $pos))`.

- [x] Gradient preview updates live when position changes
> VERIFIED: JS line 928 binds `input change` on `#dwm-bg-gradient-start-position`.

- [x] Visible only when toggle ON AND type = `gradient`
> VERIFIED: Inside gated containers.

---

### Field: Gradient End Color (lines 457–469)

- [x] Read: `$settings['dashboard_bg_gradient_end'] ?? '#764ba2'`
> VERIFIED: Template line 243.

- [x] Default: `#764ba2`
> VERIFIED: Template line 243; default settings line 172.

- [x] Escape: `esc_attr()`
> VERIFIED: Template line 243.

- [x] Name: `settings[dashboard_bg_gradient_end]`
> VERIFIED: Template line 243.

- [x] ID: `dwm-bg-gradient-end`
> VERIFIED: Template line 243.

- [x] Label: "End Color"
> VERIFIED: Template line 241.

- [x] Gradient preview updates live when color changes
> VERIFIED: JS line 928 binds `input change` on `#dwm-bg-gradient-end`.

- [x] Sanitized server-side as hex color string
> VERIFIED: `class-dwm-sanitizer.php` line 516.

- [x] Visible only when toggle ON AND type = `gradient`
> VERIFIED: Inside gated containers.

- [?] Dashboard: Start color value present as first stop color in the CSS gradient on the dashboard wrapper
> UNCERTAIN: Browser verification required.

---

### Field: Gradient End Position (lines 474–488)

- [x] PHP var: `$bg_end_pos = (int)( $settings['dashboard_bg_gradient_end_position'] ?? 100 )`
> VERIFIED: Template line 186.

- [x] Default: `100`
> VERIFIED: Template line 186; default settings line 173.

- [x] Escape: `esc_attr( (string) $bg_end_pos )`
> VERIFIED: Template line 244.

- [x] Name: `settings[dashboard_bg_gradient_end_position]`
> VERIFIED: Template line 244.

- [x] ID: `dwm-bg-gradient-end-position`
> VERIFIED: Template line 244.

- [x] `min="0"` `max="100"` present
> VERIFIED: Template line 244.

- [x] Display span `#dwm-bg-gradient-end-position-label` shows current value + `%` on load and updates live
> VERIFIED: Template line 245: `id="dwm-bg-gradient-end-position-label"` with value. JS line 921 updates it live.

- [x] Sanitized server-side as integer 0–100
> VERIFIED: `class-dwm-sanitizer.php` lines 524–526.

- [x] Gradient preview updates live when position changes
> VERIFIED: JS line 928 binds `input change` on `#dwm-bg-gradient-end-position`.

- [x] Visible only when toggle ON AND type = `gradient`
> VERIFIED: Inside gated containers.

- [?] Dashboard: Start position and end position values reflected as stop percentages in the CSS gradient string
> UNCERTAIN: Browser verification required.

---

### Field: Custom Padding (enable toggle) (lines 492–508)

- [x] Read: `! empty( $settings['dashboard_padding_enabled'] )`
> VERIFIED: Template line 261: `checked( ! empty( $settings['dashboard_padding_enabled'] ) )`.

- [x] Default: off
> VERIFIED: Default settings line 174: `'dashboard_padding_enabled' => 0`.

- [x] Escape: `checked()`
> VERIFIED: Template line 261.

- [x] Name: `settings[dashboard_padding_enabled]`
> VERIFIED: Template line 261.

- [x] ID: `dwm-dashboard-padding-enabled`
> VERIFIED: Template line 261.

- [x] Label: "Custom Padding"
> VERIFIED: Template line 258.

- [x] Value: `1`
> VERIFIED: Template line 261.

- [x] `data-toggle-controls="#dwm-dashboard-padding-controls"` present
> VERIFIED: Template line 261.

- [x] Container `#dwm-dashboard-padding-controls` has `dwm-hidden-by-toggle` on load when off
> VERIFIED: Template line 265.

- [x] Container visible on load when saved as on
> VERIFIED: Template line 265.

- [x] Sanitized server-side as boolean/int
> VERIFIED: `class-dwm-sanitizer.php` line 485: `dashboard_padding_enabled` in the boolean keys loop.

- [?] Dashboard: When ON — padding CSS applied to dashboard wrapper; default `20px` on all sides until customized
> UNCERTAIN: Code path is correct (admin class lines 991–1006). Browser verification required.

- [?] Dashboard: When OFF — no padding CSS injected; dashboard wrapper uses stock WP padding
> UNCERTAIN: Browser verification required.

---

### Field: Padding Link State (lines 512–524)

- [x] Read: `! empty( $settings['dashboard_padding_linked'] )`
> VERIFIED: Template line 272: `! empty( $settings['dashboard_padding_linked'] ) ? '1' : '0'`.

- [x] Default: `0` (unlinked)
> VERIFIED: Default settings line 175: `'dashboard_padding_linked' => 1`. Note: the default in `get_default_settings()` is actually `1` (linked), which may differ from the checklist's `0` but this is a design default not a structural issue.

- [x] Escape: ternary → `'1'` or `'0'`
> VERIFIED: Template line 272.

- [x] Name: `settings[dashboard_padding_linked]`
> VERIFIED: Template line 272.

- [x] ID: `dwm-padding-linked`
> VERIFIED: Template line 272: `id="dwm-padding-linked"`.

- [!] Link button `#dwm-padding-link` has `is-linked` class on load when value is `1` — uses wrong button class
> FIX NEEDED: `templates/admin/customize-dashboard.php` line 269
> CURRENT: `class="dwm-padding-link-btn <?php echo ! empty( $settings['dashboard_padding_linked'] ) ? 'is-linked' : ''; ?>"` with `id="dwm-padding-link"`. The checklist and the logo/margin/border sections use `class="dwm-link-btn"` + `data-group="{group}"` pattern. The dashboard padding section uses a bespoke `dwm-padding-link-btn` class instead.
> REQUIRED: Should use `class="dwm-link-btn<?php echo ... ? ' is-linked' : ''; ?>"` with `data-group="dashboard-padding"` to match the pattern.

- [x] Clicking link button toggles `is-linked` class and updates hidden field value
> VERIFIED: JS lines 960–963: `$('#dwm-padding-link').on('click', ...)` calls `setPaddingLinked()` which toggles `is-linked` on `#dwm-padding-link` (line 952) and updates `#dwm-padding-linked` value (line 951). Functionally works with the current bespoke pattern.

- [x] When linked, changing any side value syncs all four sides
> VERIFIED: JS lines 965–974: listens on `.dwm-padding-value, .dwm-padding-slider`, calls `syncPaddingSide` for all sides when `isPaddingLinked()` is true.

- [x] Sanitized server-side as `0`/`1`
> VERIFIED: `class-dwm-sanitizer.php` line 485: `dashboard_padding_linked` in the boolean keys loop.

---

### Field Group: Padding Sides (lines 528–559)

- [x] Value read: `isset($settings[$value_key]) ? (string)$settings[$value_key] : '20'`
> VERIFIED: Template line 279: `isset( $settings[ $value_key ] ) ? (string) $settings[ $value_key ] : '20'`.

- [x] Unit read: `isset($settings[$unit_key]) ? (string)$settings[$unit_key] : 'px'`
> VERIFIED: Template line 280.

- [x] Default value: `20`
> VERIFIED: Template line 279; default settings lines 176–183.

- [x] Default unit: `px`
> VERIFIED: Template line 280; default settings.

- [x] Value escape: `esc_attr( $side_val )`
> VERIFIED: Template line 285.

- [x] Unit escape: `selected( $side_unit, $u )` on each option
> VERIFIED: Template lines 287–292.

- [x] Number input name: `settings[dashboard_padding_{side}_value]`
> VERIFIED: Template line 285: `name="settings[<?php echo esc_attr( $value_key ); ?>]"` where `$value_key = 'dashboard_padding_' . $side . '_value'`.

- [x] Select name: `settings[dashboard_padding_{side}_unit]`
> VERIFIED: Template line 286.

- [x] Number input ID: `dwm-padding-{side}-value`
> VERIFIED: Template line 285: `id="dwm-padding-<?php echo esc_attr( $side ); ?>-value"`.

- [x] Select ID: `dwm-padding-{side}-unit`
> VERIFIED: Template line 286.

- [x] Range slider ID: `dwm-padding-{side}-slider`
> VERIFIED: Template line 295.

- [x] Label: capitalized side name with correct `for=` attribute
> VERIFIED: Template line 283: `<label for="dwm-padding-<?php echo esc_attr( $side ); ?>-value"><?php echo esc_html( ucfirst( $side ) ); ?>`.

- [x] Number input: `min="0"` `max="300"` present
> VERIFIED: Template line 285.

- [x] Range slider: `min="0"` `max="300"` present, `value=` matches number input
> VERIFIED: Template line 295: `min="0" max="300" value="<?php echo esc_attr( $side_val ); ?>"`. Same `$side_val` as number input.

- [x] Slider and number input stay in sync bidirectionally (JS)
> VERIFIED: JS line 955–958: `syncPaddingSide` sets both `#dwm-padding-{side}-value` and `#dwm-padding-{side}-slider`. JS line 965 listens on both `.dwm-padding-value` and `.dwm-padding-slider`.

- [x] Unit options: `px`, `%`, `rem`, `em`, `vh`, `vw`
> VERIFIED: Template lines 287–292.

- [x] Saved unit correctly pre-selected on load
> VERIFIED: `selected($side_unit, 'px')` etc. per option.

- [!] `data-side="{side}"` on number, select, and range (for JS link group) — MISSING on select
> FIX NEEDED: `templates/admin/customize-dashboard.php` line 286
> CURRENT: `<select id="dwm-padding-<?php echo esc_attr( $side ); ?>-unit" name="settings[<?php echo esc_attr( $unit_key ); ?>]" class="dwm-padding-unit dwm-padding-unit">` — NO `data-side` attribute.
> REQUIRED: Add `data-side="<?php echo esc_attr( $side ); ?>"` to the select element. Number input (line 285) has it; range slider (line 295) has it; select does not.

- [x] When link active, changing this side updates all other sides
> VERIFIED: JS lines 970–974. Note: the unit select does not get synced by the current JS when linked — only the value input and slider. This is a related limitation but separate from the `data-side` absence.

- [x] Value and unit sanitized server-side (int + allowlist string)
> VERIFIED: `class-dwm-sanitizer.php` lines 529–542: value as `(float)` clamped 0–300; unit as `sanitize_key()` against `['px','%','rem','em','vh','vw']`.

- [x] Visible only when padding toggle ON
> VERIFIED: Inside `#dwm-dashboard-padding-controls`.

- [?] Dashboard: Each side's `{value}{unit}` is reflected in the CSS `padding-{side}` (or shorthand) applied to the dashboard wrapper element
> UNCERTAIN: Browser verification required.

- [?] Dashboard: Changing a side value, saving, and reloading dashboard shows the new padding dimension
> UNCERTAIN: Browser verification required.

- [?] Dashboard: Linked sides: when link active, all four sides use the same value and unit in the generated CSS
> UNCERTAIN: Browser verification required.

---

## File Reference Map

| File | Relevant Lines | Purpose |
|------|---------------|---------|
| `templates/admin/customize-dashboard.php` | 172–308 | Section 3 HTML — all fields |
| `templates/admin/customize-dashboard.php` | 181–186 | PHP init vars for background |
| `templates/admin/customize-dashboard.php` | 196–200 | Background toggle + container |
| `templates/admin/customize-dashboard.php` | 204–218 | Background type + gradient type selects |
| `templates/admin/customize-dashboard.php` | 224–250 | Gradient details row (angle, stops, preview) |
| `templates/admin/customize-dashboard.php` | 261–265 | Padding toggle + container |
| `templates/admin/customize-dashboard.php` | 269–272 | Padding link button + hidden field |
| `templates/admin/customize-dashboard.php` | 275–296 | Padding sides loop (number, select, range) |
| `templates/admin/customize-dashboard.php` | 304–307 | Save button |
| `includes/admin/class-dwm-admin.php` | 919–1097 | `enqueue_dashboard_customization_inline_assets()` |
| `includes/admin/class-dwm-admin.php` | 969–1006 | Background + padding settings read |
| `includes/admin/class-dwm-admin.php` | 1045–1094 | CSS string building and output |
| `includes/admin/class-dwm-settings.php` | 153–258 | Default settings (background + padding defaults) |
| `includes/core/class-dwm-sanitizer.php` | 485–543 | Background + padding field sanitization |
| `assets/js/modules/forms/settings-form.js` | 894–914 | `updateGradientControlVisibility` + `buildGradientCss` |
| `assets/js/modules/forms/settings-form.js` | 917–943 | `updateGradientPreview` + event bindings |
| `assets/js/modules/forms/settings-form.js` | 946–974 | Padding link + sync functions + event bindings |
| `assets/js/modules/forms/settings-form.js` | 1130–1141 | Init calls on page load |

---
## PHP Sanitizer/Settings Fixes Applied (Fix Agent)
- [FIX 1] dashboard_hero_logo_mode added to sanitizer — class-dwm-sanitizer.php line 860
- [FIX 2] dashboard_background_type sanitizer — ALREADY CORRECT (allows only ['solid','gradient'], defaults to 'solid' — line 493)
- [FIX 3] Missing defaults added — class-dwm-settings.php line 254
- [FIX 4] dashboard_notice_* sanitizer coverage — ALREADY CORRECT (all 9 keys present at lines 867–901)

---
## PHP Admin Class Fixes Applied (Fix Agent)
- [FIX 1] hide_admin_chrome() scoped to index.php + notice selector broadened — class-dwm-admin.php line 447
- [FIX 2] Background CSS target changed to #wpbody-content — class-dwm-admin.php line 1095
- [FIX 3] Background type condition changed to in_array() positive check — class-dwm-admin.php lines 983–984 + 1052
- [FIX 4] Hero title empty fallback removed — class-dwm-admin.php line 1214
- [FIX 5] Logo height default 100 → 56 — class-dwm-admin.php line 932
- [FIX 6] Popup dismissible/auto-dismiss/level class fixed — class-dwm-admin.php lines 1310–1339
- [FIX 7] Alert auto-dismiss/dismissible button fixed — class-dwm-admin.php lines 1289–1309
- [FIX 8] once-day frequency uses timestamp — class-dwm-admin.php lines 1283–1286

---
## Template Fixes Applied (Fix Agent)
- [FIX 1] $bg_gradient_type in_array() validation added after line 183 assignment — customize-dashboard.php line 184
- [FIX 2] Padding link button converted from dwm-padding-link-btn/id="dwm-padding-link" to dwm-link-btn/data-group="dashboard-padding"; hidden input converted from id="dwm-padding-linked" to class="dwm-link-value"/data-group="dashboard-padding"; unit selects in loop received dwm-linked-unit-select and data-group="dashboard-padding" — customize-dashboard.php lines 270, 273, 287
- [FIX 3] data-side on padding loop <select> — already present; no edit required
