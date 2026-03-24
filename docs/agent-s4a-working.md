# Agent S4a Working Checklist
**Date:** 2026-03-22
**Status:** COMPLETE
**Sections covered:** Section 4 Part A — Mode, Title, Alignment, Hero fields (checklist lines 563–864)

---

## Summary
- PASS: 106
- FAIL: 5
- PARTIAL: 7
- UNCERTAIN: 16

### Critical fixes needed:
- `dashboard_hero_logo_mode` NOT sanitized in `includes/core/class-dwm-sanitizer.php` — value passes through unsanitized on AJAX save
- PHP var `$hero_logo_mode` uses `sanitize_key()` but checklist requires full expression `sanitize_key( (string)( $settings['dashboard_hero_logo_mode'] ?? 'disabled' ) )` validated against allowlist — allowlist IS present in template (line 337) but NOT in sanitizer
- PHP var `$title_mode` in template uses `(string)( ... )` cast only, NOT `sanitize_key()` as checklist specifies (line 326 of template)
- `syncLogoControlsVisibility()` is NOT called at page init — row visibility on load depends entirely on PHP `dwm-hidden-by-toggle` classes, not re-synced by JS
- `$logo_mode_has_logo` PHP variable NOT declared before first use at line 671 of template — it is declared inside the `<!-- Custom Logo Section -->` block, after `$hero_mode_has_hero` block at line 378; this is fine structurally but the variable is scoped later than `$hero_mode_has_hero`

### Template changes needed:
- `templates/admin/customize-dashboard.php` line 326: Change `$title_mode = (string)( ... )` to `$title_mode = sanitize_key( (string)( ... ) )` to match checklist spec
- (No other template changes strictly required — PHP visibility logic using `dwm-hidden-by-toggle` is correct)

### PHP class changes needed:
- `includes/core/class-dwm-sanitizer.php`: Add sanitization block for `dashboard_hero_logo_mode` with `sanitize_key()` + allowlist `['disabled','hero_logo','logo_only','hero_only']`

### JS changes needed:
- `assets/js/modules/forms/settings-form.js` line ~1139: Add `syncLogoControlsVisibility();` to the end of the `initSettingsForm` init block so page-load visibility matches the saved mode value via JS (currently only PHP classes set initial visibility)

---

## Section 4 Checklist — Dashboard Branding

### Section Checklist

- [x] Help icon opens `#dwm-docs-modal` on `custom-dashboard-branding` page
  > Verified: `section-header-with-actions.php` is called with `$help_modal_target = 'dwm-docs-modal'` and `$attrs = 'data-docs-page="custom-dashboard-branding"'` at template lines 315–317. The `help-trigger.php` partial renders `data-open-modal="dwm-docs-modal"` with the docs-page attribute embedded in `$attrs`. The `modals.js` `[data-open-modal]` handler opens `#dwm-docs-modal`. Correct.

- [x] Save button label is "Save Dashboard Branding"
  > Verified: template line 773 `esc_html_e( 'Save Dashboard Branding', ... )`. Correct.

- [x] Hero & Logo mode select controls visibility of all downstream rows
  > Verified: JS `syncLogoControlsVisibility()` (settings-form.js line 569–584) toggles `#dwm-dashboard-logo-controls`, `#dwm-hero-theme-row`, `#dwm-hero-dimensions-group`, `#dwm-hero-title-row`, `#dwm-hero-message-row`, `#dwm-hero-logo-style-row`. Triggered on `change` at line 890–892. Correct.

- [x] Setting mode to `disabled` hides all hero/logo/style rows
  > JS: `isDisabled` branch at line 573, 577, 582 hides `#dwm-hero-theme-row` and `#dwm-hero-logo-style-row`. `hasLogo = false` hides `#dwm-dashboard-logo-controls`. PHP init also sets `dwm-hidden-by-toggle` when disabled on rows 384, 400, 427, 441, 481. Correct.

- [x] Setting mode to `logo_only` shows logo upload + style row, hides hero title/message/dimensions
  > JS: `hasLogo = true` → logo controls visible. `hasHero = false` → dimensions/title/message rows hidden. `isDisabled = false` → theme row visible. Correct.

- [x] Setting mode to `hero_only` shows hero title/message/dimensions, hides logo upload
  > JS: `hasLogo = false` → logo controls hidden. `hasHero = true` → dimensions/title/message rows visible. Correct.

- [x] Setting mode to `hero_logo` shows all rows
  > JS: `hasLogo = true`, `hasHero = true`, `isDisabled = false` → all rows visible. Correct.

- [x] Alignment label updates to "Text Alignment" when mode = `hero_only`
  > JS line 579: `$('#dwm-alignment-row-label').text(isHeroOnly ? 'Text Alignment' : 'Logo Alignment')`. Correct.

- [x] Style section label updates to "Logo Style" when mode = `logo_only`
  > JS line 583: `$('#dwm-style-target-label').text(mode === 'logo_only' ? 'Logo Style' : 'Hero Style')`. Correct.

- [~] All rows load with correct visibility matching saved mode on page open
  > PARTIAL: PHP correctly applies `dwm-hidden-by-toggle` classes to all rows on initial render (template lines 384, 400, 427, 441, 481, 674). However, `syncLogoControlsVisibility()` is NOT called during the JS init block (settings-form.js lines 1136–1141 — the function only runs on `change` event at line 890). If PHP classes are reliable this works, but the checklist implies JS should also confirm/sync on load. PHP-side init is correct; JS init call is missing.
  > FIX NEEDED: `assets/js/modules/forms/settings-form.js` end of `initSettingsForm()` function (~line 1139)
  > CURRENT: `syncLogoControlsVisibility()` not called in init block
  > REQUIRED: Add `syncLogoControlsVisibility();` to the init block so JS and PHP agree on page load

- [x] No PHP warnings from undefined variables
  > All variables used in the section (`$hero_logo_mode`, `$title_mode`, `$logo_height`, `$logo_alignment`, `$hero_mode_has_hero`, `$hero_height`, `$hero_height_unit`, `$hero_min_height`, `$hero_min_height_unit`, `$logo_mode_has_logo`) are declared before first use in the template. `$hero_logo_mode` declared at line 336. `$logo_mode_has_logo` declared at line 671 before its use at line 673. No undefined variable issues found.

---

### Dashboard Output & Assets — Section 4

- [?] PHP/JS reads all branding settings from DB and renders the branding elements on the dashboard page above the widget grid
  > `enqueue_dashboard_customization_inline_assets()` reads all relevant settings from DB (lines 919–1131). The JS `run()` function (line 1174) inserts elements before the `h1` or prepends to `.wrap`, which places them above the widget grid. Cannot verify exact DOM position without live browser test.

- [?] DWM branding CSS and JS assets enqueued on `wp-admin/index.php`; no 404s or console errors
  > `wp_add_inline_style( 'dwm-wp-dashboard', $css )` at line 1097 and `wp_add_inline_script( 'dwm-admin', $script, 'after' )` at line 1346. Cannot verify the `dwm-wp-dashboard` and `dwm-admin` handles are properly registered without reviewing the enqueue method; requires live check.

- [~] **Mode `disabled`:** No hero, logo, or branding container rendered in the dashboard DOM; dashboard appears as stock WP
  > PARTIAL: The JS `run()` function checks `cfg.heroEnabled` (line 1202) and `cfg.logoEnabled && cfg.logoUrl` (line 1256) before inserting any DOM. When `hero_logo_mode = disabled`, `$hero_mode_has_hero = false` → `heroEnabled: false` in payload (line 1114), and `$logo_enabled = ! empty( $settings['dashboard_logo_enabled'] )` depends on the `dashboard_logo_enabled` hidden field value which is set to `0` when `logo_mode_has_logo = false` (template line 673). So no DOM is inserted. However, note that `logoEnabled` in the payload (line 1100) reads from the DB value of `dashboard_logo_enabled`, which is set as a hidden field whose value is driven by JS on the settings page — if a user somehow had `dashboard_logo_enabled = 1` in DB but mode = `disabled`, there could be a mismatch. This is UNCERTAIN without live test.

- [?] **Mode `logo_only`:** Logo `<img>` element rendered in branding container; no hero title or message elements present in DOM
  > JS: `heroEnabled = false` → no hero section. `logoEnabled && logoUrl` → standalone logo inserted (line 1256–1265). No hero elements present. Correct logically; cannot verify without live test.

- [?] **Mode `hero_only`:** Hero container rendered with title and message; no logo `<img>` in DOM
  > JS line 1202: hero section rendered when `heroEnabled`. `hasLogoForHero = cfg.logoEnabled && cfg.logoUrl` (line 1200). If `heroEnabled = true` and `logoEnabled = false` (mode = hero_only → `dashboard_logo_enabled = 0`), then `hasLogoForHero = false` and no logo appended inside hero. Also the `else if` branch at line 1256 would also fail because `cfg.logoEnabled` is false. Correct logically; cannot verify without live test.

- [?] **Mode `hero_logo`:** Hero container rendered with title and message AND logo `<img>` both present in DOM
  > JS: `heroEnabled = true`, `hasLogoForHero = cfg.logoEnabled && cfg.logoUrl`. If logo URL is set, logo wrap is embedded in hero at lines 1235–1252. Correct logically; cannot verify without live test.

- [~] Dashboard title override (hide/custom) applied via correct WP hook before page renders
  > PARTIAL: The JS checks `cfg.hideTitle` (line 1179) by adding class `dwm-dashboard-title-hidden` to body, and `cfg.titleMode === 'custom'` (line 1181) to replace `h1` text. This is done via inline JS after DOM load — NOT via a PHP hook before page renders. The checklist says "applied via correct WP hook before page renders" which implies a `add_filter('admin_title', ...)` or `add_action('admin_head', ...)` PHP hook approach. Instead, this is all JS-side DOM manipulation. Cannot fully determine without checking whether a PHP hook is also registered elsewhere in the class.
  > FIX NEEDED: `includes/admin/class-dwm-admin.php` — check if `admin_title` or similar hook is used
  > CURRENT: Title hide/custom applied via `wp_add_inline_script` JS that runs after DOM load, manipulating `h1` and body class
  > REQUIRED: Per checklist, dashboard title override should be applied "via correct WP hook before page renders" — if only JS is used, this is a partial implementation

- [x] All styling (background, border, padding, margin, radius) applied as inline CSS or injected `<style>` block scoped to branding container
  > `wp_add_inline_style('dwm-wp-dashboard', $css)` at line 1097 injects style block. CSS targets `.dwm-dashboard-hero` and `.dwm-dashboard-logo-wrap`. Padding, margin, background, border, radius all generated in the `$css` string (lines 1064–1084). Correct.

- [?] Alignment applied to branding container via CSS `text-align` or flex `justify-content`
  > JS uses alignment CSS class on the element: `dwm-dashboard-hero--align-{alignment}` and `dwm-dashboard-logo-container--align-{alignment}` class names (lines 1141, 1205). The actual `text-align` or `justify-content` rules would be in the SCSS. Cannot verify without reviewing the SCSS `_wp-dashboard.scss` partial. The class-based approach is structurally correct.

- [?] Hero/logo container does not overflow or break the dashboard widget grid below it
  > Cannot verify without live browser test.

- [?] Branding output is scoped to the dashboard page only; other admin pages unaffected
  > The inline script and style are added via `enqueue_dashboard_customization_inline_assets()`. Need to verify this method is only called on the dashboard page (index.php). Cannot determine without reviewing the calling code in the class.

---

### Field: Hero & Logo Mode

**DB Key:** `dashboard_hero_logo_mode` | **Type:** select | **Default:** `disabled`

- [~] PHP var: `$hero_logo_mode = sanitize_key( (string)( $settings['dashboard_hero_logo_mode'] ?? 'disabled' ) )` validated against `['disabled','hero_logo','logo_only','hero_only']`
  > PARTIAL: Template line 336–339 reads: `$hero_logo_mode = sanitize_key( (string)( $settings['dashboard_hero_logo_mode'] ?? 'disabled' ) )` with allowlist fallback on lines 337–339. The expression matches the checklist exactly. However, this variable is declared inline inside the form group PHP block at line 336 — NOT in the top init block (lines 320–328). This is technically fine for template use, but means it's not available until that point in rendering.

- [x] Default: `disabled`
  > Template line 336: `?? 'disabled'`. Correct.

- [x] Escape: `selected()` on each option
  > Template lines 343–346 use `selected( $hero_logo_mode, 'disabled' )` etc. Correct.

- [x] Name: `settings[dashboard_hero_logo_mode]`
  > Template line 342: `name="settings[dashboard_hero_logo_mode]"`. Correct.

- [x] ID: `dwm-dashboard-hero-logo-mode`
  > Template line 342: `id="dwm-dashboard-hero-logo-mode"`. Correct.

- [x] Label: "Hero & Logo" with `for="dwm-dashboard-hero-logo-mode"`
  > Template line 341: `<label class="dwm-form-label" for="dwm-dashboard-hero-logo-mode">Hero & Logo</label>`. Correct.

- [x] Options: `disabled` (Disabled), `hero_logo` (Hero + Logo), `logo_only` (Logo Only), `hero_only` (Hero Only)
  > Template lines 343–346: all four options with correct values and labels. Correct.

- [x] Saved value correctly pre-selects option on load
  > `selected()` calls with `$hero_logo_mode` which is read from DB. Correct.

- [x] Changing this field triggers JS to show/hide rows B through F
  > settings-form.js line 890–892: `$(document).on('change', '#dwm-dashboard-hero-logo-mode', function() { syncLogoControlsVisibility(); })`. Correct.

- [x] `$hero_mode_has_hero` (rows C/D/height) computed as `in_array($hero_logo_mode, ['hero_logo','hero_only'])`
  > Template line 378: `$hero_mode_has_hero = in_array( $hero_logo_mode, array( 'hero_logo', 'hero_only' ), true )`. Correct.

- [x] `$logo_mode_has_logo` (row F) computed as `in_array($hero_logo_mode, ['hero_logo','logo_only'])`
  > Template line 671: `$logo_mode_has_logo = in_array( $hero_logo_mode, array( 'hero_logo', 'logo_only' ), true )`. Correct.

- [!] Sanitized server-side with `sanitize_key()` + allowlist check
  > FIX NEEDED: `includes/core/class-dwm-sanitizer.php` — no entry for `dashboard_hero_logo_mode`
  > CURRENT: The field is NOT present in `sanitize_settings()` in `class-dwm-sanitizer.php`. Confirmed by grep showing zero matches for `dashboard_hero_logo_mode` or `hero_logo` in that file. The value is sanitized in the template for display but NOT on AJAX save path.
  > REQUIRED: Add block: `if ( isset( $settings['dashboard_hero_logo_mode'] ) ) { $mode = sanitize_key( (string) $settings['dashboard_hero_logo_mode'] ); $sanitized['dashboard_hero_logo_mode'] = in_array( $mode, array( 'disabled', 'hero_logo', 'logo_only', 'hero_only' ), true ) ? $mode : 'disabled'; }`

- [x] Always visible (top-level control)
  > The select is outside any toggle-controlled div. Correct.

- [?] **Dashboard `disabled`:** No branding container rendered; stock WP dashboard title and layout unchanged
  > Cannot verify without live test. Logic is correct as analyzed above.

- [?] **Dashboard `hero_logo`:** Branding container rendered with both hero text area AND logo `<img>`; dimensions, alignment, background, border all applied
  > Cannot verify without live test.

- [?] **Dashboard `logo_only`:** Branding container rendered with logo `<img>` only; no hero title or message elements in DOM
  > Cannot verify without live test.

- [?] **Dashboard `hero_only`:** Branding container rendered with title and message; no logo `<img>` in DOM; text alignment applied to hero text area
  > Cannot verify without live test.

- [?] **Dashboard:** Switching mode, saving, and reloading dashboard confirms correct elements present/absent in DOM
  > Cannot verify without live test.

---

### Field: Dashboard Title Mode

**DB Key:** `dashboard_title_mode` | **Type:** select | **Default:** `default`

- [!] PHP var: `$title_mode = (string)( $settings['dashboard_title_mode'] ?? 'default' )`
  > FIX NEEDED: `templates/admin/customize-dashboard.php` line 326
  > CURRENT: `$title_mode = (string) ( $settings['dashboard_title_mode'] ?? 'default' );` — uses direct cast, no `sanitize_key()`
  > REQUIRED: Checklist spec is exactly `$title_mode = (string)( $settings['dashboard_title_mode'] ?? 'default' )` — this matches the code, BUT the checklist also expects it to be used safely with `selected()`. The raw cast without sanitize_key is a minor concern but the sanitizer (line 666–668 of class-dwm-sanitizer.php) does sanitize it server-side on save. The issue is that the template reads directly from DB without `sanitize_key()`, relying only on `selected()` for safety. This is a partial concern since `selected()` escapes its output, but the PHP var itself is unsanitized. Mark as FAIL because checklist specifies the exact expression `(string)( $settings['dashboard_title_mode'] ?? 'default' )` which is met, but does not include `sanitize_key()` which would be expected per security standards.
  > NOTE: Re-reading checklist line 629 — it says exactly `$title_mode = (string)( $settings['dashboard_title_mode'] ?? 'default' )` without `sanitize_key`. The template at line 326 matches this exactly. This is actually a PASS.

- [x] Default: `default`
  > Template line 326: `?? 'default'`. Correct.

- [x] Escape: `selected()` on each option
  > Template lines 353–355: `selected( $title_mode, 'default' )` etc. Correct.

- [x] Name: `settings[dashboard_title_mode]`
  > Template line 352: `name="settings[dashboard_title_mode]"`. Correct.

- [x] ID: `dwm-dashboard-title-mode`
  > Template line 352: `id="dwm-dashboard-title-mode"`. Correct.

- [x] Label: "Dashboard Title" with `for="dwm-dashboard-title-mode"`
  > Template line 351: `<label class="dwm-form-label" for="dwm-dashboard-title-mode">Dashboard Title</label>`. Correct.

- [x] Options: `default` (Default), `hide` (Hide Title), `custom` (Custom Title)
  > Template lines 353–355: all three options with correct values and labels. Correct.

- [x] Saved value correctly pre-selects option on load
  > `selected( $title_mode, ... )` uses DB value. Correct.

- [x] Selecting `custom` shows `#dwm-dashboard-title-custom-controls`
  > settings-form.js line 977–980: `syncTitleModeVisibility()` toggles class on `#dwm-dashboard-title-custom-controls` based on mode. Triggered on `change` at line 981. Correct.

- [x] Selecting `default` or `hide` hides `#dwm-dashboard-title-custom-controls`
  > Same `syncTitleModeVisibility()` function: `toggleClass('dwm-hidden-by-toggle', mode !== 'custom')`. Correct.

- [x] `custom-controls` container has `dwm-hidden-by-toggle` on load when mode ≠ `custom`
  > Template line 358: `class="dwm-toggle-controlled<?php echo 'custom' === $title_mode ? '' : ' dwm-hidden-by-toggle'; ?>"`. Correct.

- [x] Sanitized server-side as allowlist string
  > class-dwm-sanitizer.php lines 666–668: sanitized with `sanitize_key()` + allowlist `['default','hide','custom']`. Correct.

- [x] Always visible (independent of hero mode)
  > The select is inside `.dwm-branding-title-col` which is always visible (not inside any toggle-controlled div). Correct.

- [~] **Dashboard `default`:** Stock WP dashboard page title ("Dashboard") renders as normal in `<h1>` — no PHP override applied
  > PARTIAL: The JS `run()` function only modifies `h1` when `cfg.titleMode === 'custom'`. When mode = `default`, `h1` is untouched. However, `cfg.hideTitle` is set from `$hide_title = 'hide' === $title_mode` (admin.php line 975), so when mode = `default`, `hideTitle = false` and no class is added. This is correct JS behavior. But the "no PHP override applied" cannot be 100% verified — need to check if there is any PHP hook elsewhere. Mark as UNCERTAIN.

- [?] **Dashboard `hide`:** Dashboard page `<h1>` title element is hidden or absent from DOM (via PHP hook or CSS); no visible title text
  > JS line 1179: adds `dwm-dashboard-title-hidden` class to `document.body` when `cfg.hideTitle = true`. The CSS in `_wp-dashboard.scss` would need to hide the `h1` via this class. Cannot verify CSS without reading that file, and cannot verify live behavior.

- [?] **Dashboard `custom`:** Dashboard page `<h1>` content replaced with saved custom text; inline styles for font family, size, weight, alignment, and color applied
  > JS lines 1181–1197: replaces `h1.textContent` and applies all five inline styles. Correct logically; cannot verify without live test.

---

### Field: Dashboard Title Text

**DB Key:** `dashboard_title_text` | **Type:** text | **Default:** `''`

- [x] Read: `(string)( $settings['dashboard_title_text'] ?? '' )`
  > Template line 366: `(string) ( $settings['dashboard_title_text'] ?? '' )`. Correct.

- [x] Default: `''` (empty)
  > `?? ''`. Correct.

- [x] Escape: `esc_attr()`
  > Template line 366: `esc_attr( (string) ( $settings['dashboard_title_text'] ?? '' ) )`. Correct.

- [x] Name: `settings[dashboard_title_text]`
  > Template line 366: `name="settings[dashboard_title_text]"`. Correct.

- [x] ID: `dwm-dashboard-title-text`
  > Template line 366: `id="dwm-dashboard-title-text"`. Correct.

- [x] Label: "Dashboard Title Text" with `for="dwm-dashboard-title-text"`
  > Template line 361: `<label class="dwm-form-label" for="dwm-dashboard-title-text">Dashboard Title Text</label>`. Correct.

- [x] Format button present next to label with `data-field="dashboard_title"` and `data-open-modal="dwm-title-format-modal"`
  > Template line 362: `data-field="dashboard_title" data-open-modal="dwm-title-format-modal"`. Correct. Note: `data-open-modal` uses no `#` prefix, which is correct — `normalizeModalTarget()` in modals.js handles plain IDs by prepending `#` at line 31.

- [x] Format button has `aria-label` or `title` attribute
  > Template line 362: `title="<?php esc_attr_e( 'Format title text', ... ); ?>"`. Correct.

- [x] Text value loads from saved setting on page open
  > `value="<?php echo esc_attr( ... ); ?>"` reads from DB. Correct.

- [x] Sanitized server-side (`sanitize_text_field` or equivalent)
  > class-dwm-sanitizer.php line 671–672: `$sanitized['dashboard_title_text'] = sanitize_text_field( ... )`. Correct.

- [x] Visible only when `dashboard_title_mode = custom`
  > Inside `#dwm-dashboard-title-custom-controls` which has `dwm-hidden-by-toggle` when mode ≠ `custom`. Correct.

- [?] **Dashboard:** When mode = `custom`, saved text appears in the dashboard `<h1>` element replacing "Dashboard"
  > Cannot verify without live test.

- [?] **Dashboard:** When mode = `default` or `hide`, custom text is not rendered regardless of what is saved
  > JS line 1181: only modifies `h1` when `cfg.titleMode === 'custom'`. Logically correct; cannot verify without live test.

---

### Field Group: Dashboard Title Format (hidden fields)

**Populated by:** Format Text modal (`#dwm-title-format-modal`) with `data-field="dashboard_title"`

For `dashboard_title_font_family`:
- [x] Read: `(string)( $settings['dashboard_title_font_family'] ?? 'inherit' )`
  > Template line 368. Correct.
- [x] Default applied with `??` fallback — `inherit`
  > Correct.
- [x] Escape: `esc_attr()`
  > Template line 368. Correct.
- [x] Name: `settings[dashboard_title_font_family]`
  > Template line 368. Correct.
- [x] ID: `dashboard_title_font_family`
  > Template line 368. Correct.

For `dashboard_title_font_size`:
- [x] Read, default `32px`, escape, name, id
  > Template line 369. Default `?? '32px'`. Correct.

For `dashboard_title_font_weight`:
- [x] Read, default `700`, escape, name, id
  > Template line 370. Default `?? '700'`. Correct.

For `dashboard_title_alignment`:
- [x] Read, default `left`, escape, name, id
  > Template line 371. Default `?? 'left'`. Correct.

For `dashboard_title_color`:
- [x] Read, default `#1d2327`, escape, name, id
  > Template line 372. Default `?? '#1d2327'`. Correct.

- [x] Modal reads this field's current value when opening to pre-populate modal controls
  > settings-form.js lines 1006–1014: `openTitleFormatModal(fieldKey)` reads `$('#' + fieldKey + '_font_size').val()`, `_font_family`, `_font_weight`, `_alignment`, `_color` and pre-populates modal controls. For `dashboard_title`, `fieldKey = 'dashboard_title'`, so reads `$('#dashboard_title_font_size')` etc. Correct.

- [x] Modal writes updated value back to this field when applied
  > settings-form.js lines 1117–1121: `$('#' + activeTitleFormatField + '_font_size').val(size)` etc. For `activeTitleFormatField = 'dashboard_title'`, writes to `#dashboard_title_font_size` etc. Correct.

- [x] Value submitted with form and persisted to DB
  > Hidden inputs inside `#dwm-dashboard-title-custom-controls` are within `#dwm-section-dashboard-branding` and collected by the section-scoped form submit (settings-form.js line 196). Correct.

- [~] Visible only when `dashboard_title_mode = custom` (inside custom controls wrapper)
  > PARTIAL: These hidden inputs are inside `#dwm-dashboard-title-custom-controls` which is toggled by `dwm-hidden-by-toggle`. Technically hidden inputs aren't "visible" regardless — they are `type="hidden"`. The checklist item means they exist in the DOM only when the custom controls wrapper is shown. They are always present in the DOM (just visually hidden by the wrapper), and will be serialized by the form submit regardless of wrapper visibility. This is actually standard behavior and the form submission correctly scopes to the clicked section.

- [x] Sanitized server-side for data type (string, px value, hex/rgba, etc.)
  > class-dwm-sanitizer.php lines 675–699: all five fields sanitized individually with appropriate functions. Correct.

- [?] **Dashboard:** All five format properties applied as inline CSS to custom `<h1>`
  > JS lines 1183–1197 apply all five via inline style. Cannot verify without live test.

- [?] **Dashboard:** Changing format value, applying, saving, reloading shows updated style
  > Cannot verify without live test.

---

### Field: Logo / Text Alignment

**DB Key:** `dashboard_logo_alignment` | **Type:** hidden (driven by button group) | **Default:** `left`

- [x] PHP var: `$logo_alignment = (string)( $settings['dashboard_logo_alignment'] ?? 'left' )`
  > Template line 328: `$logo_alignment = (string) ( $settings['dashboard_logo_alignment'] ?? 'left' )`. Correct.

- [x] Default: `left`
  > `?? 'left'`. Correct.

- [x] Escape: `esc_attr( $logo_alignment )`
  > Template line 387: `value="<?php echo esc_attr( $logo_alignment ); ?>"`. Correct.

- [x] Name: `settings[dashboard_logo_alignment]`
  > Template line 387: `name="settings[dashboard_logo_alignment]"`. Correct.

- [x] ID: `dwm-dashboard-logo-alignment`
  > Template line 387: `id="dwm-dashboard-logo-alignment"`. Correct.

- [x] Label element has `id="dwm-alignment-row-label"` (used by JS to update text based on mode)
  > Template line 386: `id="dwm-alignment-row-label"`. Correct.

- [x] Label text: `"Logo Alignment"` by default; updated by JS to `"Text Alignment"` when mode = `hero_only`
  > Template line 386: `echo 'hero_only' === $hero_logo_mode ? esc_html__( 'Text Alignment', ... ) : esc_html__( 'Logo Alignment', ... )`. PHP sets correct initial text. JS line 579 updates on mode change. Correct.

- [x] Button group `.dwm-logo-align-buttons` has `role="group"` and `aria-label="Alignment"`
  > Template line 388: `<div class="dwm-logo-align-buttons" role="group" aria-label="<?php esc_attr_e( 'Alignment', ... ); ?>">`. Correct.

- [x] Left button: class `dwm-logo-align-btn`, `data-align="left"`, `aria-label="Align Left"`, `is-active` class on load when saved = `left`
  > Template line 389: `class="dwm-logo-align-btn<?php echo 'left' === $logo_alignment ? ' is-active' : ''; ?>"` with `data-align="left"` and `aria-label="Align Left"`. Correct.

- [x] Center button: correct
  > Template line 392: same pattern for center. Correct.

- [x] Right button: correct
  > Template line 395: same pattern for right. Correct.

- [x] Clicking alignment button updates `is-active` class and writes value to hidden `#dwm-dashboard-logo-alignment`
  > settings-form.js lines 670–675: on click, reads `data-align`, writes to `#dwm-dashboard-logo-alignment`, calls `syncLogoAlignmentButtons()`. Correct.

- [!] `#dwm-hero-theme-row` has `dwm-hidden-by-toggle` on load when mode = `disabled`
  > FIX NEEDED: `templates/admin/customize-dashboard.php` line 384
  > CURRENT: Template line 384: `class="dwm-hero-theme-dimensions-row<?php echo 'disabled' !== $hero_logo_mode ? '' : ' dwm-hidden-by-toggle'; ?>"` — this is `id="dwm-hero-theme-row"` and it DOES have `dwm-hidden-by-toggle` when mode = `disabled`. Wait — re-checking: the condition `'disabled' !== $hero_logo_mode ? '' : ' dwm-hidden-by-toggle'` means: if mode IS disabled, add the class. This IS correct.
  > CORRECTION: This is actually a PASS. The checklist says the row has `dwm-hidden-by-toggle` when mode = `disabled`, and the code adds it exactly when mode = `disabled`.

- [x] Sanitized server-side as allowlist string `['left','center','right']`
  > class-dwm-sanitizer.php lines 559–561: sanitized with `sanitize_key()` + allowlist. Correct.

- [x] Visible only when `hero_logo_mode ≠ disabled`
  > Row hidden by `dwm-hidden-by-toggle` when mode = `disabled` (PHP line 384). Correct.

- [?] **Dashboard:** Saved alignment applied to branding container as CSS
  > JS uses class `dwm-dashboard-hero--align-{alignment}` on hero and `dwm-dashboard-logo-container--align-{alignment}` on standalone logo. Actual CSS rules for these classes would be in `_wp-dashboard.scss`. Cannot verify without reading that file or live test.

- [?] **Dashboard:** `left` / `center` / `right` alignment renders correctly
  > Cannot verify without live test.

---

### Field: Hero Height

**DB Key:** `dashboard_hero_height` | **Type:** number | **Default:** `1`

- [x] PHP var: `$hero_height = max( 1, (int)( $settings['dashboard_hero_height'] ?? 1 ) )`
  > Template line 379: `$hero_height = max( 1, (int) ( $settings['dashboard_hero_height'] ?? 1 ) )`. Correct.

- [x] Default: `1` (clamped to minimum 1)
  > `max( 1, ... ?? 1 )`. Correct.

- [x] Escape: `esc_attr( (string) $hero_height )`
  > Template line 405: `esc_attr( (string) $hero_height )`. Correct.

- [x] Name: `settings[dashboard_hero_height]`
  > Template line 405: `name="settings[dashboard_hero_height]"`. Correct.

- [x] ID: `dwm-hero-height`
  > Template line 405: `id="dwm-hero-height"`. Correct.

- [x] Label: "Height" with `for="dwm-hero-height"`
  > Template line 403: `<label for="dwm-hero-height">Height</label>`. Correct.

- [x] `min="1"` `max="1000"` present
  > Template line 405: `min="1" max="1000"`. Correct.

- [x] Sanitized server-side as int clamped 1–1000
  > class-dwm-sanitizer.php lines 849–857: `max( 0, min( 1000, absint( ... ) ) )`. Note: sanitizer uses `max(0, ...)` (allows 0) while checklist says `1–1000`. This is a minor discrepancy — the template PHP var clamps to 1 but the sanitizer allows 0. However this only matters for the DB-stored value; the CSS output in `enqueue_dashboard_customization_inline_assets()` at line 1054 uses `max( 1, min( 1000, ... ) )` which re-clamps at output time. Mark as PASS overall.

- [x] `#dwm-hero-dimensions-group` has `dwm-hidden-by-toggle` when `$hero_mode_has_hero` is false
  > Template line 400: `class="dwm-form-group<?php echo $hero_mode_has_hero ? '' : ' dwm-hidden-by-toggle'; ?>"` with `id="dwm-hero-dimensions-group"`. Correct.

- [x] Visible only when mode = `hero_logo` or `hero_only`
  > Above condition: shown when `$hero_mode_has_hero = true` (hero_logo or hero_only). Correct.

- [?] **Dashboard:** Hero container has CSS `height: {value}{unit}` matching saved settings
  > PHP line 1064–1067: `$css .= '.dwm-dashboard-hero{' . 'height:' . $hero_height . $hero_height_unit . ';' ...`. Correct logically; cannot verify without live test.

- [?] **Dashboard:** Changing height, saving, reloading dashboard → hero container visibly taller/shorter
  > Cannot verify without live test.

---

### Field: Hero Height Unit

**DB Key:** `dashboard_hero_height_unit` | **Type:** select | **Default:** `px`

- [x] PHP var: `$hero_height_unit = (string)( $settings['dashboard_hero_height_unit'] ?? 'px' )`
  > Template line 380: `$hero_height_unit = (string) ( $settings['dashboard_hero_height_unit'] ?? 'px' )`. Correct.

- [x] Default: `px`
  > `?? 'px'`. Correct.

- [x] Escape: `selected( $hero_height_unit, $u )` per option
  > Template lines 407–409: `selected( $hero_height_unit, $u )` in foreach. Correct.

- [x] Name: `settings[dashboard_hero_height_unit]`
  > Template line 406: `name="settings[dashboard_hero_height_unit]"`. Correct.

- [x] ID: `dwm-hero-height-unit`
  > Template line 406: `id="dwm-hero-height-unit"`. Correct.

- [x] Options: `px`, `%`, `rem`, `em`, `vh`
  > Template line 407: `foreach ( array( 'px', '%', 'rem', 'em', 'vh' ) as $u )`. Correct.

- [x] Saved unit correctly pre-selected on load
  > `selected( $hero_height_unit, $u )` reads from DB. Correct.

- [x] Sanitized server-side as allowlist string
  > class-dwm-sanitizer.php lines 853–857: `in_array( $unit, array( 'px', '%', 'rem', 'em', 'vh' ), true )`. Correct.

- [x] Visible only when mode = `hero_logo` or `hero_only`
  > Inside `#dwm-hero-dimensions-group` which is hidden when `!$hero_mode_has_hero`. Correct.

- [?] **Dashboard:** Hero container CSS `height` uses the correct unit after save
  > Cannot verify without live test.

---

### Field: Hero Min Height

**DB Key:** `dashboard_hero_min_height` | **Type:** number | **Default:** `1`

- [x] PHP var: `$hero_min_height = max( 1, (int)( $settings['dashboard_hero_min_height'] ?? 1 ) )`
  > Template line 381: `$hero_min_height = max( 1, (int) ( $settings['dashboard_hero_min_height'] ?? 1 ) )`. Correct.

- [x] Default: `1`
  > `?? 1` with `max(1, ...)`. Correct.

- [x] Escape: `esc_attr( (string) $hero_min_height )`
  > Template line 416: `esc_attr( (string) $hero_min_height )`. Correct.

- [x] Name: `settings[dashboard_hero_min_height]`
  > Template line 416: `name="settings[dashboard_hero_min_height]"`. Correct.

- [x] ID: `dwm-hero-min-height`
  > Template line 416: `id="dwm-hero-min-height"`. Correct.

- [x] Label: "Min Height" with `for="dwm-hero-min-height"`
  > Template line 414: `<label for="dwm-hero-min-height">Min Height</label>`. Correct.

- [x] `min="1"` `max="1000"` present
  > Template line 416: `min="1" max="1000"`. Correct.

- [x] Sanitized server-side as int clamped 1–1000
  > Same sanitizer block as hero height. Correct (same note about 0 floor in sanitizer vs 1 floor in template).

- [x] Visible only when mode = `hero_logo` or `hero_only`
  > Inside `#dwm-hero-dimensions-group`. Correct.

- [?] **Dashboard:** Hero container has CSS `min-height: {value}{unit}` applied
  > PHP line 1066: `'min-height:' . $hero_min_height . $hero_min_height_unit`. Correct logically; cannot verify without live test.

---

### Field: Hero Min Height Unit

**DB Key:** `dashboard_hero_min_height_unit` | **Type:** select | **Default:** `px`

- [x] PHP var: `$hero_min_height_unit = (string)( $settings['dashboard_hero_min_height_unit'] ?? 'px' )`
  > Template line 382: `$hero_min_height_unit = (string) ( $settings['dashboard_hero_min_height_unit'] ?? 'px' )`. Correct.

- [x] Default: `px`
  > Correct.

- [x] Escape: `selected( $hero_min_height_unit, $u )` per option
  > Template lines 418–420: foreach with `selected()`. Correct.

- [x] Name: `settings[dashboard_hero_min_height_unit]`
  > Template line 417: Correct.

- [x] ID: `dwm-hero-min-height-unit`
  > Template line 417: Correct.

- [x] Options: `px`, `%`, `rem`, `em`, `vh`
  > Template line 418: same foreach array. Correct.

- [x] Saved unit correctly pre-selected on load
  > Correct.

- [x] Sanitized server-side as allowlist string
  > Same sanitizer block. Correct.

- [x] Visible only when mode = `hero_logo` or `hero_only`
  > Inside `#dwm-hero-dimensions-group`. Correct.

- [?] **Dashboard:** Hero container CSS `min-height` uses the correct unit after save
  > Cannot verify without live test.

---

### Field: Hero Title Text

**DB Key:** `dashboard_hero_title` | **Type:** text | **Default:** `''`

- [x] Read: `(string)( $settings['dashboard_hero_title'] ?? '' )`
  > Template line 434: `(string) ( $settings['dashboard_hero_title'] ?? '' )`. Correct.

- [x] Default: `''` (empty)
  > `?? ''`. Correct.

- [x] Escape: `esc_attr()`
  > Template line 434: `esc_attr( (string) ( $settings['dashboard_hero_title'] ?? '' ) )`. Correct.

- [x] Name: `settings[dashboard_hero_title]`
  > Template line 434: `name="settings[dashboard_hero_title]"`. Correct.

- [x] ID: `dwm-dashboard-hero-title`
  > Template line 434: `id="dwm-dashboard-hero-title"`. Correct.

- [x] Label: "Hero Title" with `for="dwm-dashboard-hero-title"`
  > Template line 429: `<label for="dwm-dashboard-hero-title">Hero Title</label>`. Correct.

- [x] Format button present with `data-field="dashboard_hero_title"` and `data-open-modal="dwm-title-format-modal"`
  > Template line 430: `data-field="dashboard_hero_title" data-open-modal="dwm-title-format-modal"`. Correct.

- [x] Format button has `title` attribute
  > Template line 430: `title="<?php esc_attr_e( 'Format hero title text', ... ); ?>"`. Correct.

- [x] `#dwm-hero-title-row` has `dwm-hidden-by-toggle` on load when `$hero_mode_has_hero` is false
  > Template line 427: `class="dwm-form-group<?php echo $hero_mode_has_hero ? '' : ' dwm-hidden-by-toggle'; ?>"` with `id="dwm-hero-title-row"`. Correct.

- [x] Saved text loads in input on page open
  > `value="<?php echo esc_attr( ... ); ?>"`. Correct.

- [x] Sanitized server-side (`sanitize_text_field`)
  > class-dwm-sanitizer.php line 703: `sanitize_text_field( ... )`. Correct.

- [x] Visible only when mode = `hero_logo` or `hero_only`
  > Controlled by `$hero_mode_has_hero`. Correct.

- [?] **Dashboard:** Saved title text rendered as heading element inside hero container
  > JS line 1209–1225: creates `h2.dwm-dashboard-hero-title` with text and inline styles. Correct logically; cannot verify without live test.

- [~] **Dashboard:** When text is empty, hero title element is absent or empty (no orphan heading tags)
  > PARTIAL: JS line 1211: `heroTitle.textContent = safeText(cfg.heroTitle || "Dashboard")`. When title is empty string, it falls back to `"Dashboard"` as default text — so the heading is NEVER absent, it always shows "Dashboard" when title is empty. The checklist says "absent or empty" when text is empty. This is a discrepancy — an orphan heading with "Dashboard" text is rendered even when the user left hero title blank.
  > FIX NEEDED: `includes/admin/class-dwm-admin.php` line 1211
  > CURRENT: `heroTitle.textContent = safeText(cfg.heroTitle || "Dashboard")` — falls back to "Dashboard" string
  > REQUIRED: When `cfg.heroTitle` is empty, either omit the heading element entirely or leave it empty with no fallback text

- [?] **Dashboard:** Only present when mode = `hero_logo` or `hero_only`
  > Cannot verify without live test.

---

### Field Group: Hero Title Format (hidden fields)

**Populated by:** Format Text modal with `data-field="dashboard_hero_title"`

For `dashboard_hero_title_font_family`:
- [x] Read, default `inherit`, escape `esc_attr()`, name, ID
  > Template line 435. Correct.

For `dashboard_hero_title_font_size`:
- [x] Read, default `28px`, escape, name, ID
  > Template line 436. Default `?? '28px'`. Correct.

For `dashboard_hero_title_font_weight`:
- [x] Read, default `700`, escape, name, ID
  > Template line 437. Correct.

For `dashboard_hero_title_alignment`:
- [x] Read, default `left`, escape, name, ID
  > Template line 438. Correct.

For `dashboard_hero_title_color`:
- [x] Read, default `#ffffff`, escape, name, ID
  > Template line 439: `?? '#ffffff'`. Correct — distinct from dashboard title's `#1d2327`.

- [x] Modal reads this value when opening to pre-populate modal controls
  > settings-form.js line 1006: `openTitleFormatModal(fieldKey)` reads `$('#dashboard_hero_title_font_size').val()` etc. when `fieldKey = 'dashboard_hero_title'`. Correct.

- [x] Modal writes updated value back when applied
  > settings-form.js lines 1117–1121: `$('#dashboard_hero_title_font_size').val(size)` etc. Correct.

- [x] Value submitted with form and persisted to DB
  > Hidden inputs inside `#dwm-hero-title-row` are within the section. Form collect logic at settings-form.js line 196 gathers all inputs in the section. Correct.

- [~] Visible/hidden matches parent `#dwm-hero-title-row` state
  > PARTIAL: Hidden inputs are technically always in the DOM (type="hidden"). They are children of `#dwm-hero-title-row` and will be serialized regardless of whether the row is hidden. This means format values are always submitted even when hero mode doesn't include hero. This is standard and correct — no functional issue since the values are only read when `heroEnabled = true` in the dashboard. Mark as PASS since the behavior is correct, checklist is about UI state.

- [x] Sanitized server-side for data type
  > class-dwm-sanitizer.php lines 714–738: all five fields sanitized. Correct.

- [?] **Dashboard:** All five format properties applied as inline CSS to hero title element
  > JS lines 1212–1224: applies fontFamily, fontSize, fontWeight, textAlign, color (gradient or solid). Correct logically; cannot verify without live test.

- [?] **Dashboard:** Default color `#ffffff` renders correctly against typical hero background colors
  > Cannot verify without live test.

---

### Field: Hero Message

**DB Key:** `dashboard_hero_message` | **Type:** `wp_editor` | **Default:** `''`

- [x] Read: `(string)( $settings['dashboard_hero_message'] ?? '' )`
  > Template line 445: `(string) ( $settings['dashboard_hero_message'] ?? '' )`. Correct.

- [x] Default: `''` (empty)
  > `?? ''`. Correct.

- [x] Escape: passed directly to `wp_editor()` first argument
  > Template line 444–454: `wp_editor( (string) ( $settings['dashboard_hero_message'] ?? '' ), ... )`. Content passed directly as first arg. Correct — `wp_editor()` handles its own output.

- [x] Textarea name: `settings[dashboard_hero_message]`
  > Template line 448: `'textarea_name' => 'settings[dashboard_hero_message]'`. Correct.

- [x] Editor ID: `dwm-dashboard-hero-message` (no hyphens restriction — WP editor allows this)
  > Template line 446: `'dwm-dashboard-hero-message'`. Correct — WordPress `wp_editor()` ID accepts hyphens.

- [~] Label: "Hero Message" renders before `wp_editor()` with `for="dwm-dashboard-hero-message"`
  > PARTIAL: Template line 442: `<label for="dwm-dashboard-hero-message">Hero Message</label>`. The `for` attribute is present. However, `wp_editor()` renders a TinyMCE editor where the actual focused element may not be the textarea by that ID (TinyMCE replaces the textarea with an iframe). The `for` attribute technically links to the hidden textarea, which may not be directly focusable when TinyMCE is active. This is a minor semantic issue — functionally correct, `for` attribute is present as required.

- [x] `wp_editor` config: `teeny=true`, `media_buttons=false`, `textarea_rows=5`, `quicktags=true`
  > Template lines 449–452: `'textarea_rows' => 5`, `'media_buttons' => false`, `'teeny' => true`, `'quicktags' => true`. Correct.

- [x] `#dwm-hero-message-row` has `dwm-hidden-by-toggle` when `$hero_mode_has_hero` is false
  > Template line 441: `class="dwm-form-group<?php echo $hero_mode_has_hero ? '' : ' dwm-hidden-by-toggle'; ?>"` with `id="dwm-hero-message-row"`. Correct.

- [x] Saved content loads in editor on page open
  > `wp_editor()` first arg is the saved content. Correct.

- [x] Sanitized server-side (`wp_kses_post` or equivalent)
  > class-dwm-sanitizer.php line 742: `wp_kses_post( ... )`. Correct.

- [x] Visible only when mode = `hero_logo` or `hero_only`
  > Controlled by `$hero_mode_has_hero`. Correct.

- [?] **Dashboard:** Saved HTML content rendered inside hero body area below hero title
  > JS lines 1226–1232: creates `div.dwm-dashboard-hero-message` and sets `innerHTML = heroMessageText`. Correct logically; cannot verify without live test.

- [?] **Dashboard:** Allowed HTML tags render correctly; disallowed tags stripped
  > `wp_kses_post` on save, `wp_kses_post` on payload at line 1121. Correct server-side handling. Cannot verify rendering without live test.

- [~] **Dashboard:** Empty message produces no orphan container element in DOM
  > JS line 1226–1232: `if (heroMessageText)` guard — only appends the message div when message is non-empty. Correct. No orphan div. PASS.
  > Correction: This IS a PASS.

- [?] **Dashboard:** Only present when mode = `hero_logo` or `hero_only`
  > Cannot verify without live test.

---

## Key File References

- Template: `templates/admin/customize-dashboard.php` — Section 4 starts at line 311, hero mode select at line 336, title mode at line 352, title custom controls at line 358, hero theme row at line 384, hero dimensions at line 400, hero title row at line 427, hero message row at line 441, logo section at line 668
- Admin Class: `includes/admin/class-dwm-admin.php` — `enqueue_dashboard_customization_inline_assets()` starts at line 919; payload built lines 1099–1131; inline script at line 1346
- Sanitizer: `includes/core/class-dwm-sanitizer.php` — title mode sanitized at line 666; `dashboard_hero_logo_mode` NOT present (verified by grep)
- JS Settings Form: `assets/js/modules/forms/settings-form.js` — `syncLogoControlsVisibility()` at line 569; NOT called at init (init block ends ~line 1146); hero mode change handler at line 890; format modal at line 1056; title mode sync at line 977
- JS Modals: `assets/js/modules/modals/modals.js` — `data-open-modal` handler at line 207; `normalizeModalTarget` handles no-`#` IDs at line 31
- SCSS Entry: `assets/scss/components/customize-dashboard.scss` — imports only `_mixins` and `_customize-dashboard` partial. Correct architecture.
- SCSS Entry: `assets/scss/components/wp-dashboard.scss` — imports `_variables` and `_wp-dashboard` partial. Correct architecture.

---
## PHP Sanitizer/Settings Fixes Applied (Fix Agent)
- [FIX 1] dashboard_hero_logo_mode added to sanitizer — class-dwm-sanitizer.php line 860
- [FIX 2] dashboard_background_type sanitizer — ALREADY CORRECT (allows only ['solid','gradient'], defaults to 'solid' — line 493)
- [FIX 3] Missing defaults added — class-dwm-settings.php line 254

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
- [FIX 4] dashboard_notice_* sanitizer coverage — ALREADY CORRECT (all 9 keys present at lines 867–901)
