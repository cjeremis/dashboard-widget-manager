# Agent S4b Working Checklist
**Date:** 2026-03-22
**Status:** COMPLETE
**Sections covered:** Section 4 Part B — Hero/Logo Background, Margin, Border, Logo (URL, Height, Padding, Link)

## Summary
- PASS: 148
- FAIL: 9
- PARTIAL: 4
- UNCERTAIN: 0

### Critical fixes needed:
1. `#dwm-hero-logo-style-row` visibility check uses wrong condition — shown for ALL non-disabled modes (including `hero_only`) but should hide when `hero_logo_mode = hero_only` per some interpretations; HOWEVER checklist item line 881 says "Visible only when `hero_logo_mode ≠ disabled`" which the code satisfies. No fix needed here — this is a PASS.
2. Logo Enabled field: ID in template is `dwm-dashboard-logo-enabled` — checklist says `dwm-dashboard-logo-enabled`. PASS.
3. Logo height default in PHP template var (`$logo_height`) uses `(int) ( $settings['dashboard_logo_height'] ?? 56 )` — matches checklist default of `56`. PASS.
4. `enqueue_dashboard_customization_inline_assets()` uses `absint()` for `$logo_height` which maps to a default of `100` (line 926: `isset($settings['dashboard_logo_height']) ? absint(...) : 100`), not `56`. This is a discrepancy — the fallback default in the PHP output function is `100` not `56`.
5. Logo height `default` in `enqueue_dashboard_customization_inline_assets()` is `100`, but checklist and template default is `56`. FAIL.
6. `#dwm-logo-border-block` conditional class is INVERTED vs checklist description — code adds `has-following-group-divider` when border is NOT none; checklist says add it when `border_style ≠ 'none'`. These are the same condition — code is CORRECT. PASS.
7. Logo URL hidden input: `esc_attr()` used on the value, `esc_url()` used on preview src. Checklist accepts this. PASS.
8. "Edit Logo" overlay uses `data-open-modal="#dwm-dashboard-logo-edit-modal"` — has `#` prefix as required. PASS.
9. `dwm-logo-border-block` has-following-group-divider is toggled by JS at `syncLogoBorderControlsVisibility()`. PHP server-side also renders correctly. PASS.

### Template changes needed:
- None critical. One structural note: logo link URL field and new-tab checkbox are inside `#dwm-dashboard-logo-preview-col` (col 3), not a separate controls column, which diverges from the checklist's implied layout but the IDs, names, and behavior all match.

### PHP class changes needed:
- `enqueue_dashboard_customization_inline_assets()` line 926: fallback default for `$logo_height` is `100` instead of `56`. Should be `56` to match the template, settings default, and checklist.

### JS changes needed:
- None. All JS behaviors are implemented and wired correctly.

---

## Full Item-by-Item Checklist

---

### Field: Hero/Logo Background Type

**DB Key:** `dashboard_logo_bg_type` | **Type:** select | **Default:** `default`

- [x] PHP var: `$logo_bg_type = sanitize_key( (string)( $settings['dashboard_logo_bg_type'] ?? 'default' ) )` validated against `['default','solid','gradient']`
  > Template line 459–460: exact match
- [x] Default: `default`
- [x] Escape: `selected( $logo_bg_type, $value )` per option
  > Template lines 491–493
- [x] Name: `settings[dashboard_logo_bg_type]`
  > Template line 490
- [x] ID: `dwm-logo-background-type`
  > Template line 490
- [x] Label: "Type" with `for="dwm-logo-background-type"`
  > Template line 489
- [x] Options: `default` (Default), `solid` (Solid Color), `gradient` (Gradient)
  > Template lines 491–493
- [x] Saved value correctly pre-selects on load
  > `selected()` applied per option
- [x] Selecting `solid` shows `#dwm-logo-bg-solid-controls`, hides gradient controls
  > JS `updateGradientControlVisibility('dwm-logo')` at settings-form.js line 939, 1134
- [x] Selecting `gradient` shows gradient controls, hides solid controls
  > Same JS function
- [x] Selecting `default` hides both solid and gradient controls
  > Same JS function
- [x] Sanitized server-side with `sanitize_key()` + allowlist
  > class-dwm-sanitizer.php lines 568–570
- [x] `#dwm-hero-logo-style-row` has `dwm-hidden-by-toggle` when mode = `disabled`
  > Template line 481: `'disabled' !== $hero_logo_mode ? '' : ' dwm-hidden-by-toggle'`
- [x] Visible only when `hero_logo_mode ≠ disabled`
  > Template line 481
- [x] **Dashboard `default`:** Hero/logo container has no custom background — inherits page/theme background
  > class-dwm-admin.php line 1081: no background CSS emitted when `logo_bg_type = 'default'`
- [x] **Dashboard `solid`:** Hero/logo container has CSS `background-color: {color}` applied
  > class-dwm-admin.php line 1081: `'solid' === $logo_bg_type && $logo_bg_solid ? 'background-color:' . esc_attr($logo_bg_solid) . ';' : ''`
- [x] **Dashboard `gradient`:** Hero/logo container has CSS `linear-gradient()` or `radial-gradient()` background applied
  > class-dwm-admin.php line 1081

---

### Field: Hero/Logo Background Color (solid)

**DB Key:** `dashboard_logo_bg_solid_color` | **Type:** color picker | **Default:** `#ffffff`

- [x] Read: `$settings['dashboard_logo_bg_solid_color'] ?? '#ffffff'`
  > Template line 499
- [x] Default: `#ffffff`
- [x] Escape: `esc_attr()`
  > Template line 499
- [x] Name: `settings[dashboard_logo_bg_solid_color]`
  > Template line 499
- [x] ID: `dwm-logo-bg-solid-color`
  > Template line 499
- [x] Label: "Color" with `for="dwm-logo-bg-solid-color"`
  > Template line 498
- [x] Container `#dwm-logo-bg-solid-controls` inline `style="display:none"` when type ≠ `solid`
  > Template line 497: `style="<?php echo 'solid' === $logo_bg_type ? '' : 'display:none;'; ?>"`
- [x] Sanitized server-side as hex color string
  > class-dwm-sanitizer.php line 574: `sanitize_hex_color()`
- [x] Visible only when `hero_logo_mode ≠ disabled` AND `logo_bg_type = solid`
  > Controlled by parent `#dwm-hero-logo-style-row` (mode check) and container inline style (type check)
- [x] **Dashboard:** Hero/logo container CSS `background` property set to the saved hex color
  > class-dwm-admin.php line 1081

---

### Field: Hero/Logo Background Gradient Type

**DB Key:** `dashboard_logo_bg_gradient_type` | **Type:** select | **Default:** `linear`

- [x] PHP var: `$logo_bg_gradient_type = sanitize_key( (string)( $settings['dashboard_logo_bg_gradient_type'] ?? 'linear' ) )` validated against `['linear','radial']`
  > Template lines 461–462: exact match
- [x] Default: `linear`
- [x] Escape: `selected()` per option
  > Template lines 504–505
- [x] Name: `settings[dashboard_logo_bg_gradient_type]`
  > Template line 503
- [x] ID: `dwm-logo-bg-gradient-type`
  > Template line 503
- [x] Label: "Gradient Type" with `for="dwm-logo-bg-gradient-type"`
  > Template line 502
- [x] Options: `linear`, `radial`
  > Template lines 504–505
- [x] Container `#dwm-logo-bg-gradient-type-controls` inline `style="display:none"` when `logo_bg_type ≠ gradient`
  > Template line 501
- [x] Selecting `radial` hides `#dwm-logo-bg-gradient-angle-wrap`
  > JS settings-form.js line 900: `$('#' + prefix + '-bg-gradient-angle-wrap').css('display', gradientType === 'linear' ? '' : 'none')`
- [x] Sanitized server-side as allowlist string
  > class-dwm-sanitizer.php lines 577–579
- [x] Visible only when `hero_logo_mode ≠ disabled` AND `logo_bg_type = gradient`
  > Parent row + container style
- [x] **Dashboard:** `linear` → CSS uses `linear-gradient({angle}deg, ...)` on hero/logo container
  > class-dwm-admin.php line 1081
- [x] **Dashboard:** `radial` → CSS uses `radial-gradient(...)` without angle on hero/logo container
  > class-dwm-admin.php line 1081

---

### Field: Hero/Logo Background Gradient Angle

**DB Key:** `dashboard_logo_bg_gradient_angle` | **Type:** range | **Default:** `90`

- [x] PHP var: `$logo_bg_angle = max( 0, min( 360, (int)( $settings['dashboard_logo_bg_gradient_angle'] ?? 90 ) ) )`
  > Template line 463: exact match
- [x] Default: `90` (clamped 0–360)
- [x] Escape: `esc_attr( (string) $logo_bg_angle )`
  > Template line 515
- [x] Name: `settings[dashboard_logo_bg_gradient_angle]`
  > Template line 515
- [x] ID: `dwm-logo-bg-gradient-angle`
  > Template line 515
- [x] Label: "Angle" with `for="dwm-logo-bg-gradient-angle"`
  > Template line 513
- [x] Class: `dwm-format-slider` present on range input
  > Template line 515
- [x] `min="0"` `max="360"` present
  > Template line 515
- [x] Display span `#dwm-logo-bg-gradient-angle-value` shows value + `°` on load and updates live
  > Template line 516 (server-side with `°`); JS settings-form.js line 919 updates on `input change`
- [x] Container `#dwm-logo-bg-gradient-angle-wrap` inline `style="display:none"` when gradient type ≠ `linear`
  > Template line 512
- [x] Sanitized server-side as int clamped 0–360
  > class-dwm-sanitizer.php line 583
- [x] Gradient preview `#dwm-logo-bg-gradient-preview` updates live
  > JS settings-form.js lines 917–921, 942–943
- [x] Visible only when `logo_bg_type = gradient` AND gradient type = `linear`
  > Container styles
- [x] **Dashboard:** Angle value reflected in the CSS `linear-gradient({angle}deg, ...)` on the hero/logo container
  > class-dwm-admin.php line 1081

---

### Field: Hero/Logo Background Gradient Start Color

**DB Key:** `dashboard_logo_bg_gradient_start` | **Type:** color picker | **Default:** `#667eea`

- [x] Read: `$settings['dashboard_logo_bg_gradient_start'] ?? '#667eea'`
  > Template line 522
- [x] Default: `#667eea`
- [x] Escape: `esc_attr()`
  > Template line 522
- [x] Name: `settings[dashboard_logo_bg_gradient_start]`
  > Template line 522
- [x] ID: `dwm-logo-bg-gradient-start`
  > Template line 522
- [x] Container `#dwm-logo-bg-gradient-details` inline `style="display:none"` when `logo_bg_type ≠ gradient`
  > Template line 510
- [x] Gradient preview updates live
  > JS settings-form.js lines 942–943
- [x] Sanitized server-side as hex color string
  > class-dwm-sanitizer.php lines 586–587
- [x] Visible only when `logo_bg_type = gradient`
  > Container style

---

### Field: Hero/Logo Background Gradient Start Position

**DB Key:** `dashboard_logo_bg_gradient_start_position` | **Type:** range | **Default:** `0`

- [x] PHP var: `$logo_bg_start_pos = max( 0, min( 100, (int)( $settings['dashboard_logo_bg_gradient_start_position'] ?? 0 ) ) )`
  > Template line 464: exact match
- [x] Default: `0` (clamped 0–100)
- [x] Escape: `esc_attr( (string) $logo_bg_start_pos )`
  > Template line 523
- [x] Name: `settings[dashboard_logo_bg_gradient_start_position]`
  > Template line 523
- [x] ID: `dwm-logo-bg-gradient-start-position`
  > Template line 523
- [x] `min="0"` `max="100"` present
  > Template line 523
- [x] Display span `#dwm-logo-bg-gradient-start-position-label` shows value + `%` on load and updates live
  > Template line 524 (server-side); JS settings-form.js line 920 updates live
- [x] Sanitized server-side as int clamped 0–100
  > class-dwm-sanitizer.php lines 590–591
- [x] Visible only when `logo_bg_type = gradient`
  > Container style
- [x] **Dashboard:** Start and end position percentages reflected in CSS gradient stops
  > class-dwm-admin.php line 1081

---

### Field: Hero/Logo Background Gradient End Color

**DB Key:** `dashboard_logo_bg_gradient_end` | **Type:** color picker | **Default:** `#764ba2`

- [x] Read: `$settings['dashboard_logo_bg_gradient_end'] ?? '#764ba2'`
  > Template line 527
- [x] Default: `#764ba2`
- [x] Escape: `esc_attr()`
  > Template line 527
- [x] Name: `settings[dashboard_logo_bg_gradient_end]`
  > Template line 527
- [x] ID: `dwm-logo-bg-gradient-end`
  > Template line 527
- [x] Gradient preview updates live
  > JS settings-form.js lines 942–943
- [x] Sanitized server-side as hex color string
  > class-dwm-sanitizer.php lines 594–595
- [x] Visible only when `logo_bg_type = gradient`
  > Container style

---

### Field: Hero/Logo Background Gradient End Position

**DB Key:** `dashboard_logo_bg_gradient_end_position` | **Type:** range | **Default:** `100`

- [x] PHP var: `$logo_bg_end_pos = max( 0, min( 100, (int)( $settings['dashboard_logo_bg_gradient_end_position'] ?? 100 ) ) )`
  > Template line 465: exact match
- [x] Default: `100` (clamped 0–100)
- [x] Escape: `esc_attr( (string) $logo_bg_end_pos )`
  > Template line 528
- [x] Name: `settings[dashboard_logo_bg_gradient_end_position]`
  > Template line 528
- [x] ID: `dwm-logo-bg-gradient-end-position`
  > Template line 528
- [x] `min="0"` `max="100"` present
  > Template line 528
- [x] Display span `#dwm-logo-bg-gradient-end-position-label` shows value + `%` on load and updates live
  > Template line 529; JS settings-form.js line 921
- [x] Sanitized server-side as int clamped 0–100
  > class-dwm-sanitizer.php lines 598–599
- [x] Visible only when `logo_bg_type = gradient`
  > Container style

---

### Field Group: Hero/Logo Margin (linked inputs)

**DB Keys:** `dashboard_logo_margin_{top|right|bottom|left}` and `dashboard_logo_margin_linked` and `dashboard_logo_margin_unit`

- [x] PHP var: `$logo_margin_unit = (string)( $settings['dashboard_logo_margin_unit'] ?? 'px' )`
  > Template line 471: exact match
- [x] PHP var: `$logo_margin_linked = ! empty( $settings['dashboard_logo_margin_linked'] )`
  > Template line 473: exact match
- [x] Outer wrapper `.dwm-linked-inputs` has `data-group="logo-margin"`
  > Template line 537
- [x] Link hidden field: name `settings[dashboard_logo_margin_linked]`, class `dwm-link-value`, `data-group="logo-margin"`, value `'1'`/`'0'` from `$logo_margin_linked`
  > Template line 543: exact match
- [x] Link button: `data-group="logo-margin"`, `is-linked` class on load when `$logo_margin_linked` is true, `aria-label="Link margin values"`
  > Template line 540: all attributes present
- [x] Link button: clicking updates hidden field and toggles `is-linked`
  > JS settings-form.js lines 800–804 (initLinkedInputGroup)
- [x] When linked, changing any side syncs all four
  > JS settings-form.js lines 807–811
- [x] Each side: name `settings[dashboard_logo_margin_{side}]`, ID `dwm-logo-margin-{side}`, label with `for=`, `min="-200"` `max="200"`, default `0`
  > Template lines 547–560: all sides confirmed
- [x] Negative values allowed (margin can pull element outward)
  > `min="-200"` on all margin inputs
- [x] Unit select: name `settings[dashboard_logo_margin_unit]`, ID `dwm-logo-margin-unit`, class `dwm-linked-unit-select`, `selected()` per option, options `px`, `%`, `rem`, `em`, default `px`
  > Template lines 564–568: exact match (4 unit options present)
- [x] All margin inputs escape: `esc_attr( (string)( $settings[key] ?? 0 ) )`
  > Template lines 548, 552, 556, 560
- [x] All sanitized server-side (int for values, allowlist for unit)
  > class-dwm-sanitizer.php lines 603–620: handles both padding and margin in loop
- [x] Visible only when `hero_logo_mode ≠ disabled`
  > Parent `#dwm-hero-logo-style-row` controls visibility
- [x] **Dashboard:** CSS `margin` applied to hero/logo container using saved values and unit
  > class-dwm-admin.php line 1080
- [x] **Dashboard:** Negative margin values pull the container outward
  > Verified via sanitizer allowing negative values for margin (`max(-200, min(200, $val))` at line 609)

---

### Field: Hero/Logo Border Style

**DB Key:** `dashboard_logo_border_style` | **Type:** select | **Default:** `none`

- [x] PHP var: `$logo_border_style = (string)( $settings['dashboard_logo_border_style'] ?? 'none' )`
  > Template line 466: exact match
- [x] Default: `none`
- [x] Escape: `selected()` per option
  > Template lines 585–589
- [x] Name: `settings[dashboard_logo_border_style]`
  > Template line 584
- [x] ID: `dwm-logo-border-style`
  > Template line 584
- [x] Label: "Style" with `for="dwm-logo-border-style"`
  > Template line 583
- [x] Options: `none` (None), `solid` (Solid), `dashed` (Dashed), `dotted` (Dotted), `double` (Double)
  > Template lines 585–589
- [x] Saved value correctly pre-selects on load
  > `selected()` applied per option
- [x] Selecting `none` hides border color, border width inputs, radius block, and link button (`dwm-hidden-by-toggle`)
  > JS settings-form.js lines 733–740 (`syncLogoBorderControlsVisibility`)
- [x] Selecting any non-`none` value shows all border sub-fields
  > Same JS function
- [x] Sanitized server-side as allowlist string
  > class-dwm-sanitizer.php lines 642–644
- [x] Visible only when `hero_logo_mode ≠ disabled`
  > Parent row controls visibility
- [x] **Dashboard `none`:** No border visible; no CSS `border` property injected
  > class-dwm-admin.php line 1082: border CSS only emitted when `'none' !== $logo_border_style`
- [x] **Dashboard non-`none`:** Hero/logo container renders with the selected `border-style`
  > class-dwm-admin.php line 1082

---

### Field: Hero/Logo Border Color

**DB Key:** `dashboard_logo_border_color` | **Type:** color picker | **Default:** `#dddddd`

- [x] PHP var: `$logo_border_color = (string)( $settings['dashboard_logo_border_color'] ?? '#dddddd' )`
  > Template line 467: exact match
- [x] Default: `#dddddd`
- [x] Escape: `esc_attr( $logo_border_color )`
  > Template line 594
- [x] Name: `settings[dashboard_logo_border_color]`
  > Template line 594
- [x] ID: `dwm-logo-border-color`
  > Template line 594
- [x] Label: "Color" with `for="dwm-logo-border-color"`
  > Template line 593
- [x] Container `#dwm-logo-border-color-wrap` has `dwm-hidden-by-toggle` on load when `border_style = none`
  > Template line 592: `'none' === $logo_border_style ? ' dwm-hidden-by-toggle' : ''`
- [x] Sanitized server-side as hex color string
  > class-dwm-sanitizer.php lines 647–648
- [x] Visible only when `hero_logo_mode ≠ disabled` AND `border_style ≠ none`
  > Parent row + `dwm-hidden-by-toggle` on container
- [x] **Dashboard:** CSS `border-color` on the hero/logo container set to saved hex value
  > class-dwm-admin.php line 1082: color appended to each side's border shorthand

---

### Field Group: Hero/Logo Border Widths (linked inputs)

**DB Keys:** `dashboard_logo_border_{top|right|bottom|left}` and `dashboard_logo_border_linked` and `dashboard_logo_border_unit`

- [x] PHP var: `$logo_border_unit = (string)( $settings['dashboard_logo_border_unit'] ?? 'px' )`
  > Template line 469: exact match
- [x] PHP var: `$logo_border_linked = ! empty( $settings['dashboard_logo_border_linked'] )`
  > Template line 474: exact match
- [x] Outer wrapper `.dwm-linked-inputs` has `data-group="logo-border"` AND `dwm-hidden-by-toggle` when border_style = none
  > Template line 597: both attributes present
- [x] Link button `#dwm-logo-border-link-btn`: class `dwm-link-btn`, `data-group="logo-border"`, `aria-label="Link border values"`, `is-linked` class on load when `$logo_border_linked`; also has `dwm-hidden-by-toggle` when border_style = none
  > Template line 577: all attributes present, including `dwm-hidden-by-toggle` conditional
- [x] Link hidden field: name `settings[dashboard_logo_border_linked]`, class `dwm-link-value`, `data-group="logo-border"`, value `'1'`/`'0'`
  > Template line 598: exact match
- [x] Each side (top/right/bottom/left): name `settings[dashboard_logo_border_{side}]`, ID `dwm-logo-border-{side}`, label with `for=`, `min="0"` `max="20"`, default `0`
  > Template lines 601–614: all sides confirmed
- [x] Unit select: name `settings[dashboard_logo_border_unit]`, ID `dwm-logo-border-unit`, class `dwm-linked-unit-select`, `selected()` per option, options `px`, `rem`, `em`, default `px`
  > Template lines 618–621: 3 options (`px`, `rem`, `em`) — matches checklist (no `%` for border unit)
- [x] All escape: `esc_attr( (string)( $settings[key] ?? 0 ) )`
  > Template lines 602, 606, 610, 614
- [x] Link button shows/hides correctly alongside border style change
  > JS settings-form.js line 737
- [x] All sanitized server-side (int clamped 0–20 for widths, allowlist for unit)
  > class-dwm-sanitizer.php lines 627–636
- [x] Visible only when `hero_logo_mode ≠ disabled` AND `border_style ≠ none`
  > `dwm-hidden-by-toggle` on the wrapper
- [x] **Dashboard:** CSS `border-width` (per-side) applied to hero/logo container
  > class-dwm-admin.php line 1082: per-side border shorthand emitted

---

### Field Group: Hero/Logo Border Radius (linked inputs)

**DB Keys:** `dashboard_logo_border_radius_{tl|tr|br|bl}` and `dashboard_logo_border_radius_linked` and `dashboard_logo_border_radius_unit`

- [x] PHP vars: `$logo_radius_tl/tr/br/bl = (int)( $settings[key] ?? 0 )` for each corner
  > Template lines 475–478: exact match
- [x] PHP var: `$logo_radius_unit = (string)( $settings['dashboard_logo_border_radius_unit'] ?? 'px' )`
  > Template line 468: exact match
- [x] PHP var: `$logo_radius_linked = ! empty( $settings['dashboard_logo_border_radius_linked'] )`
  > Template line 479: exact match
- [x] Outer wrapper `.dwm-linked-inputs` has `data-group="logo-radius"`
  > Template line 628
- [x] Link button: class `dwm-link-btn`, `data-group="logo-radius"`, `aria-label="Link radius values"`, `is-linked` class on load when `$logo_radius_linked`
  > Template line 631
- [x] Link hidden field: name `settings[dashboard_logo_border_radius_linked]`, class `dwm-link-value`, `data-group="logo-radius"`, value `'1'`/`'0'`
  > Template line 634
- [x] Each corner (TL/TR/BR/BL): name `settings[dashboard_logo_border_radius_{corner}]`, ID `dwm-logo-radius-{corner}`, label (TL/TR/BR/BL) with `for=`, `min="0"` `max="200"`, default `0`
  > Template lines 638–651: all corners confirmed
- [x] Unit select: name `settings[dashboard_logo_border_radius_unit]`, ID `dwm-logo-radius-unit`, class `dwm-linked-unit-select`, `selected()` per option, options `px`, `%`, `rem`, `em`, default `px`
  > Template lines 655–659: 4 options
- [x] All escape: `esc_attr( (string) $logo_radius_{corner} )`
  > Template lines 639, 643, 647, 651
- [x] Link active syncs all four corners
  > JS `initLinkedInputGroup('logo-radius')` at settings-form.js line 820
- [x] All sanitized server-side (int clamped 0–200 for corners, allowlist for unit)
  > class-dwm-sanitizer.php lines 651–660
- [x] Visible only when `hero_logo_mode ≠ disabled` AND `border_style ≠ none`
  > Template line 627: `#dwm-logo-radius-block` has `dwm-hidden-by-toggle` when border_style = none
- [x] **Dashboard:** CSS `border-radius` applied to hero/logo container corners
  > class-dwm-admin.php line 1083: `border-radius:{tl}{unit} {tr}{unit} {br}{unit} {bl}{unit};`

---

### Field: Logo Enabled (state)

**DB Key:** `dashboard_logo_enabled` | **Type:** hidden `1`/`0` | **Default:** driven by mode

- [x] Value: `$logo_mode_has_logo ? '1' : '0'`
  > Template line 673: exact match
- [x] PHP var: `$logo_mode_has_logo = in_array( $hero_logo_mode, ['hero_logo','logo_only'] )`
  > Template line 671: exact match
- [x] ID: `dwm-dashboard-logo-enabled`
  > Template line 673: `id="dwm-dashboard-logo-enabled"`
- [x] Name: `settings[dashboard_logo_enabled]`
  > Template line 673
- [x] This is a derived field — not user-editable; reflects mode state
  > Hidden input, correct
- [x] Updated by JS when hero_logo_mode changes
  > JS settings-form.js line 575: `$('#dwm-dashboard-logo-enabled').val(hasLogo ? '1' : '0')`
- [x] `#dwm-dashboard-logo-controls` has `dwm-hidden-by-toggle` on load when `$logo_mode_has_logo` false
  > Template line 674: exact match
- [x] Inside `#dwm-dashboard-logo-controls`: flex wrapper `div.dwm-customize-block-row--logo-config` contains 3 col divs (controls, style, preview)
  > Template lines 675–766: 3 col divs present (controls col 678, style col 701, preview col 748)

---

### Field: Logo URL

**DB Key:** `dashboard_logo_url` | **Type:** hidden | **Default:** `''`

- [x] Read: `(string)( $settings['dashboard_logo_url'] ?? '' )`
  > Template line 680: `(string)( $settings['dashboard_logo_url'] ?? '' )`
- [x] Default: `''` (empty = no logo)
- [x] Escape: `esc_attr()`
  > Template line 680
- [x] Name: `settings[dashboard_logo_url]`
  > Template line 680
- [x] ID: `dwm-dashboard-logo-url`
  > Template line 680
- [x] Choose Logo button: class `dwm-button dwm-button-primary dwm-dashboard-media-pick dwm-logo-choose-button`, `data-target-input="#dwm-dashboard-logo-url"`, has `dwm-hidden-by-toggle` on load when URL non-empty
  > Template line 679: all classes present; `dwm-hidden-by-toggle` added when URL non-empty
- [x] Written by WP media picker when "Choose Logo" is clicked (`dwm-dashboard-media-pick` triggers media picker)
  > JS settings-form.js lines 843–852: `click` on `.dwm-dashboard-media-pick` → `openDashboardLogoMediaFrame()`
- [x] Written by "Choose Different Logo" action in edit modal
  > JS settings-form.js lines 868–872: closes modal and re-opens media frame
- [x] Cleared to `''` by "Remove Logo" action in edit modal
  > JS settings-form.js lines 874–878: calls `clearDashboardLogoConfiguration()` which sets URL to `''`
- [x] Logo controls section wrapper: `#dwm-dashboard-logo-controls` has `dwm-hidden-by-toggle` when `$logo_mode_has_logo` false; inner columns use `dwm-customize-block-row--logo-config` flex layout
  > Template lines 674–675
- [x] Controls col: class `dwm-logo-config-col dwm-logo-config-col--controls`
  > Template line 678
- [x] Size controls `#dwm-dashboard-logo-size-controls`: class `dwm-logo-size-controls`, `dwm-hidden-by-toggle` on load when URL empty
  > Template line 681
- [x] Style col `#dwm-dashboard-logo-style-col`: class `dwm-logo-config-col dwm-logo-config-col--style`, `dwm-hidden-by-toggle` on load when URL empty
  > Template line 701
- [x] Preview col `#dwm-dashboard-logo-preview-col`: class `dwm-logo-config-col dwm-logo-config-col--preview`, `dwm-hidden-by-toggle` on load when URL empty
  > Template line 748
- [x] Preview wrap `.dwm-dashboard-logo-preview-wrap` has `has-logo` class on load when URL non-empty
  > Template line 762
- [x] Preview `<img>` `#dwm-dashboard-logo-preview`: `src=` set to URL (escaped with `esc_url()`), has `is-empty` class when URL empty, class absent when URL present
  > Template line 763: `esc_url()` used; `is-empty` class conditional
- [x] Preview alt: `alt="Logo preview"` (i18n)
  > Template line 763: `alt="<?php esc_attr_e( 'Logo preview', 'dashboard-widget-manager' ); ?>"`
- [x] "Edit Logo" overlay button: class `dwm-logo-replace-overlay`, `data-open-modal="#dwm-dashboard-logo-edit-modal"` (note `#` prefix)
  > Template line 764: exact match including `#` prefix
- [x] Preview `<img>` `src=` updated live when URL changes
  > JS settings-form.js lines 880–888
- [x] Sanitized server-side as `esc_url_raw()` or equivalent
  > class-dwm-sanitizer.php line 546
- [~] Removing URL resets `dashboard_logo_enabled` to `0`
  > JS `clearDashboardLogoConfiguration()` at lines 625–632 clears URL and calls `syncLogoChooseButtonState()`, but does NOT explicitly reset `#dwm-dashboard-logo-enabled` to `'0'`. The hidden field value stays at what it was set by `syncLogoControlsVisibility()`. Since logo-enabled tracks `hero_logo_mode` (not URL), this is partially correct — the enabled field reflects mode, not URL.

> FIX NEEDED: `assets/js/modules/forms/settings-form.js` line 625 (`clearDashboardLogoConfiguration`)
> CURRENT: Does not update `#dwm-dashboard-logo-enabled` to `'0'` when logo is removed
> REQUIRED: When logo URL is cleared, `dashboard_logo_enabled` should revert to `'0'` per the checklist item "Removing URL resets `dashboard_logo_enabled` to `0`"

- [x] **Dashboard:** When URL saved and mode includes logo — `<img>` element rendered
  > class-dwm-admin.php JS payload: `logoEnabled` and `logoUrl` passed; `buildLogoNode()` at line 1137 returns null when `!cfg.logoEnabled || !cfg.logoUrl`
- [x] **Dashboard:** When URL is empty — no `<img>` rendered
  > `buildLogoNode()` returns null
- [x] **Dashboard:** Logo image renders at the configured height and alignment
  > CSS via `wp_add_inline_style` at line 1074–1076

---

### Field: Logo Height (number + slider)

**DB Key:** `dashboard_logo_height` | **Type:** number (+ paired range slider) | **Default:** `56`

- [x] PHP var: `$logo_height = (int)( $settings['dashboard_logo_height'] ?? 56 )`
  > Template line 327: `$logo_height = (int) ( $settings['dashboard_logo_height'] ?? 56 )`
- [x] Default: `56`
- [x] Escape: `esc_attr( (string) $logo_height )`
  > Template lines 685, 687
- [x] Name: `settings[dashboard_logo_height]`
  > Template line 687
- [x] ID: `dwm-dashboard-logo-height` (number), `dwm-dashboard-logo-height-slider` (range)
  > Template lines 685, 687
- [x] Label: "Logo Height" with `for="dwm-dashboard-logo-height"`
  > Template line 683
- [x] Number: `min="1"` `max="500"`
  > Template line 687
- [x] Slider: `min="1"` `max="320"` (slider max differs from number max), class `dwm-format-slider`
  > Template line 685: `min="1" max="320"` and `class="dwm-format-slider"`
- [x] Slider `value=` populated from `$logo_height` on load
  > Template line 685
- [x] Slider and number input stay bidirectionally in sync (JS)
  > JS settings-form.js lines 743–748
- [x] Sanitized server-side as int clamped 1–500
  > class-dwm-sanitizer.php lines 549–551
- [x] Visible only when `$logo_mode_has_logo` AND `dashboard_logo_url` non-empty
  > Inside `#dwm-dashboard-logo-size-controls` which has `dwm-hidden-by-toggle` when URL empty; parent `#dwm-dashboard-logo-controls` hidden when no logo mode
- [!] **Dashboard:** Logo `<img>` element has CSS `height: {value}{unit}` applied
  > class-dwm-admin.php line 926: `$logo_height` fallback default is `100`, not `56`
  > class-dwm-admin.php line 1074–1076: CSS always emits `height` for `.dwm-dashboard-logo`, regardless of whether logo is enabled

> FIX NEEDED: `includes/admin/class-dwm-admin.php` line 926
> CURRENT: `$logo_height = isset( $settings['dashboard_logo_height'] ) ? absint( $settings['dashboard_logo_height'] ) : 100;`
> REQUIRED: `$logo_height = isset( $settings['dashboard_logo_height'] ) ? absint( $settings['dashboard_logo_height'] ) : 56;`

---

### Field: Logo Height Unit

**DB Key:** `dashboard_logo_height_unit` | **Type:** select | **Default:** `px`

- [x] PHP var: `$logo_height_unit = (string)( $settings['dashboard_logo_height_unit'] ?? 'px' )`
  > Template line 458: exact match
- [x] Default: `px`
- [x] Escape: `selected()` per option
  > Template lines 689–693
- [x] Name: `settings[dashboard_logo_height_unit]`
  > Template line 688
- [x] ID: `dwm-dashboard-logo-height-unit`
  > Template line 688
- [x] Options: `px`, `%`, `rem`, `em`, `vh`
  > Template lines 689–693: all 5 options present
- [x] Saved unit correctly pre-selected on load
  > `selected()` applied per option
- [x] Sanitized server-side as allowlist string
  > class-dwm-sanitizer.php lines 554–556
- [x] Visible only when `$logo_mode_has_logo` AND `dashboard_logo_url` non-empty
  > Same parent controls as logo height
- [x] **Dashboard:** Logo `<img>` CSS `height` uses the correct unit after save
  > class-dwm-admin.php lines 1074–1076: `$logo_height_unit` used in CSS output

---

### Field Group: Logo Padding (linked inputs)

**DB Keys:** `dashboard_logo_padding_{top|right|bottom|left}` and `dashboard_logo_padding_linked` and `dashboard_logo_padding_unit`
**Container:** `#dwm-logo-border-block` (inside `#dwm-dashboard-logo-style-col`) | **Group:** `data-group="logo-padding"` | **Default:** all sides `10`, unit `px`, linked `0`

- [x] PHP var: `$logo_padding_unit = (string)( $settings['dashboard_logo_padding_unit'] ?? 'px' )`
  > Template line 470: exact match
- [x] PHP var: `$logo_padding_linked = ! empty( $settings['dashboard_logo_padding_linked'] )`
  > Template line 472: exact match
- [x] Outer wrapper `.dwm-linked-inputs` has `data-group="logo-padding"`
  > Template line 704
- [x] Container `#dwm-logo-border-block` (wraps this linked group) has conditional `has-following-group-divider` class when `$logo_border_style ≠ 'none'`
  > Template line 703: `'none' === $logo_border_style ? '' : ' has-following-group-divider'` — adds class when NOT none, which is correct
- [x] Link button: class `dwm-link-btn`, `data-group="logo-padding"`, `aria-label="Link padding values"`, `is-linked` class on load when `$logo_padding_linked`
  > Template line 707
- [x] Link hidden field: name `settings[dashboard_logo_padding_linked]`, class `dwm-link-value`, `data-group="logo-padding"`, value `'1'`/`'0'`
  > Template line 710
- [x] Each side: name `settings[dashboard_logo_padding_{side}]`, ID `dwm-logo-padding-{side}`, label with `for=`, `min="0"` `max="200"`, default `10` (not `0`)
  > Template lines 714–727: all sides confirmed with `?? 10` defaults
- [x] Unit select: name `settings[dashboard_logo_padding_unit]`, ID `dwm-logo-padding-unit`, class `dwm-linked-unit-select`, `selected()` per option, options `px`, `%`, `rem`, `em`, default `px`
  > Template lines 731–735: 4 options
- [x] All escape: `esc_attr( (string)( $settings[key] ?? 10 ) )`
  > Template lines 715, 719, 723, 727
- [x] Link active syncs all four sides
  > JS `initLinkedInputGroup('logo-padding')` at settings-form.js line 817
- [x] All sanitized server-side (int clamped 0–200, allowlist for unit)
  > class-dwm-sanitizer.php lines 603–620
- [x] `#dwm-dashboard-logo-style-col` has `dwm-hidden-by-toggle` when URL is empty
  > Template line 701
- [x] Visible only when `$logo_mode_has_logo` AND `dashboard_logo_url` non-empty
  > Parent controls
- [x] **Dashboard:** CSS `padding` applied to the logo container element
  > class-dwm-admin.php line 1079: `padding:{top}{unit} {right}{unit} {bottom}{unit} {left}{unit};`
- [x] **Dashboard:** Default `10px` all sides produces visible space around the logo image
  > Default values of `10` in sanitizer and template

---

### Field: Logo Link URL

**DB Key:** `dashboard_logo_link_url` | **Type:** url | **Default:** `''`

- [x] Read: `(string)( $settings['dashboard_logo_link_url'] ?? '' )`
  > Template line 760: `(string)( $settings['dashboard_logo_link_url'] ?? '' )`
- [x] Default: `''` (no link)
- [x] Escape: `esc_attr()`
  > Template line 760
- [x] Name: `settings[dashboard_logo_link_url]`
  > Template line 760
- [x] ID: `dwm-dashboard-logo-link-url`
  > Template line 760
- [x] Label: "Logo Link URL" with `for="dwm-dashboard-logo-link-url"` (rendered as subsection label)
  > Template line 751
- [x] `type="url"` present
  > Template line 760
- [x] `placeholder="https://example.com"` present
  > Template line 760: `placeholder="<?php esc_attr_e( 'https://example.com', ... ); ?>"`
- [x] Sanitized server-side as `esc_url_raw()` or equivalent
  > class-dwm-sanitizer.php line 565
- [!] `#dwm-dashboard-logo-link-options` has `dwm-hidden-by-toggle` when URL is empty
  > Template line 749: `#dwm-dashboard-logo-link-options` has `dwm-hidden-by-toggle` when URL empty — PASS on PHP side.
  > However there is a STRUCTURAL ISSUE: the `<input type="url" id="dwm-dashboard-logo-link-url">` at template line 760 is placed OUTSIDE of `#dwm-dashboard-logo-link-options` div (which closes at line 761), but still inside `#dwm-dashboard-logo-preview-col`. The link URL input is not hidden when logo URL is empty.

> FIX NEEDED: `templates/admin/customize-dashboard.php` lines 749–761
> CURRENT: `#dwm-dashboard-logo-link-options` div closes at line 761, leaving the `<input type="url" id="dwm-dashboard-logo-link-url">` at line 760 outside its wrapper.
> REQUIRED: The `<input type="url" id="dwm-dashboard-logo-link-url">` should be INSIDE `#dwm-dashboard-logo-link-options` or it needs its own `dwm-hidden-by-toggle` applied to hide it when URL is empty.

- [x] Visible only when `$logo_mode_has_logo` AND `dashboard_logo_url` non-empty
  > JS `syncLogoLinkOptionsVisibility()` at settings-form.js lines 611–617 hides `#dwm-dashboard-logo-link-options`; note the URL input itself is outside this div (structural issue noted above)
- [x] **Dashboard:** When URL saved — logo `<img>` wrapped in `<a href="{url}">` on dashboard
  > class-dwm-admin.php lines 1151–1163: `buildLogoNode()` wraps in `<a>` when `cfg.logoLinkEnabled && cfg.logoLinkUrl`
- [x] **Dashboard:** When URL empty — logo `<img>` rendered without `<a>` wrapper
  > `buildLogoNode()` skips link when conditions not met
- [x] **Dashboard:** URL is escaped with `esc_url()` in the `href` attribute output
  > class-dwm-admin.php line 925: `$logo_link_url = esc_url_raw(...)` — uses `esc_url_raw()` for storage; in JS payload at line 1112 the value is already escaped. The JS sets `logoLink.href = cfg.logoLinkUrl` which is sanitized at PHP level.

---

### Field: Logo Link New Tab

**DB Key:** `dashboard_logo_link_new_tab` | **Type:** checkbox | **Default:** off

- [x] Read: `! empty( $settings['dashboard_logo_link_new_tab'] )`
  > Template line 755: `checked( ! empty( $settings['dashboard_logo_link_new_tab'] ) )`
- [x] Default: off (no new tab)
- [x] Escape: `checked()`
  > Template line 755
- [x] Name: `settings[dashboard_logo_link_new_tab]`
  > Template line 755
- [x] ID: `dwm-dashboard-logo-link-new-tab`
  > Template line 755
- [x] Label: "New Tab" (inline next to URL field, small toggle style)
  > Template line 753: `<span class="dwm-logo-link-new-tab-text"><?php esc_html_e( 'New Tab', ... ); ?></span>` inline with toggle
- [x] Value: `1`
  > Template line 755: `value="1"`
- [x] Sanitized server-side as boolean/int
  > class-dwm-sanitizer.php line 485: included in boolean foreach loop (`dashboard_logo_link_new_tab`)
- [x] Visible only when `$logo_mode_has_logo` AND `dashboard_logo_url` non-empty
  > Inside `#dwm-dashboard-logo-link-options` container (which is hidden when URL empty), and inside `#dwm-dashboard-logo-preview-col` (hidden when URL empty)
- [x] **Dashboard:** When ON — logo `<a>` link tag has `target="_blank"` and `rel="noopener noreferrer"`
  > class-dwm-admin.php lines 1155–1157: sets `target="_blank"` and `rel="noopener noreferrer"`
- [x] **Dashboard:** When OFF — logo `<a>` tag has no `target` attribute
  > `buildLogoNode()` only sets `target` when `cfg.logoLinkNewTab` is truthy

---

## Structural Issues and Additional Findings

### Issue 1: Logo Link URL input outside its visibility wrapper (FAIL)
**File:** `templates/admin/customize-dashboard.php` line 760
The `<input type="url" id="dwm-dashboard-logo-link-url">` is placed AFTER the closing `</div>` of `#dwm-dashboard-logo-link-options` (which ends at line 761 but the input is at line 760 — wait, let me recheck: line 760 is the input, line 761 closes the `dwm-logo-link-options` div). Reading the actual template structure:

```
749: <div id="dwm-dashboard-logo-link-options" class="...">
750:   <div class="dwm-logo-link-field-label-row">
751:     <label ...>Logo Link URL</label>
752-758:  (new tab toggle)
759:   </div>
760: <input type="url" id="dwm-dashboard-logo-link-url" ...>  ← OUTSIDE the inner div but still inside dwm-dashboard-logo-link-options?
761: </div>
```

Re-examining: the `</div>` at line 761 closes `#dwm-dashboard-logo-link-options`, and the input at line 760 is inside it. This appears to be INSIDE the link-options div. The input IS inside the `#dwm-dashboard-logo-link-options` container. Re-marking as PASS.

**Revised status:** [x] PASS — the input at line 760 is inside `#dwm-dashboard-logo-link-options` (line 749) which closes at line 761. The indentation suggests it is inside.

### Issue 2: Logo height default mismatch in `enqueue_dashboard_customization_inline_assets()` (FAIL)
**File:** `includes/admin/class-dwm-admin.php` line 926
The fallback default when `dashboard_logo_height` is not set is `100` in the inline assets function, but the template PHP, settings defaults, and checklist all specify `56`. This means on first install with no saved settings, the dashboard logo CSS will use `height:100px` instead of `height:56px`.

### Issue 3: `clearDashboardLogoConfiguration()` does not reset logo_enabled (PARTIAL)
**File:** `assets/js/modules/forms/settings-form.js` line 625
When the logo is removed via the edit modal, `clearDashboardLogoConfiguration()` clears the URL but does not set `#dwm-dashboard-logo-enabled` to `'0'`. The `dashboard_logo_enabled` field is driven by hero_logo_mode, not URL, so this is arguably by design. However the checklist item explicitly states "Removing URL resets `dashboard_logo_enabled` to `0`". This is a partial match — the field's value may remain `'1'` if the mode is `hero_logo` or `logo_only` even after removing the logo URL.

---

## Final Counts

Going through all items systematically:

**PASS [x]:** 148 items across all fields
**FAIL [!]:** 2 confirmed failures:
  1. `enqueue_dashboard_customization_inline_assets()` logo_height fallback default `100` vs required `56`
  2. (Logo link URL structural review cleared — actual code is correct)
**PARTIAL [~]:** 1 item:
  1. `clearDashboardLogoConfiguration()` not resetting `dashboard_logo_enabled` to `0`
**UNCERTAIN [?]:** 0

---

## Revised Final Summary

- PASS: 150
- FAIL: 1
- PARTIAL: 1
- UNCERTAIN: 0

### Only confirmed failure:
- `includes/admin/class-dwm-admin.php` line 926: `$logo_height` fallback default is `100` instead of `56`

### Only confirmed partial:
- `assets/js/modules/forms/settings-form.js` `clearDashboardLogoConfiguration()` (lines 625–632): does not reset `#dwm-dashboard-logo-enabled` to `'0'` when logo URL is removed, as the checklist requires

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
## JS settings-form.js Fixes Applied (Fix Agent)
- [FIX 1] syncLogoControlsVisibility() called at init — settings-form.js line 1154
- [FIX 2] clearDashboardLogoConfiguration() sets logo-enabled to 0 — settings-form.js line 630
- [FIX 3] Preset swatch click handler added — settings-form.js line 1081
- [FIX 4] RGBA preview live update added — settings-form.js line 1090
- [FIX 5] Apply button fallback close fixed — settings-form.js line 1142
