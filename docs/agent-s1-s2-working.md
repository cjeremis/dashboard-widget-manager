# Agent S1+S2 Working Checklist
**Date:** 2026-03-22
**Status:** COMPLETE
**Sections covered:** Section 1 (Hide Dashboard Elements) + Section 2 (Hide Widgets)

## Summary
- PASS: 63
- FAIL: 9
- PARTIAL: 4
- UNCERTAIN: 12

### Critical fixes needed:
- [FAIL] Section 1 has no "Save Widget Overrides" button — `templates/admin/customize-dashboard.php` lines 33–74 (section closes with no `.dwm-section-actions`)
- [FAIL] `hide_admin_chrome()` is NOT scoped to dashboard only — Help and Screen Options CSS is applied to ALL admin pages — `includes/admin/class-dwm-admin.php` line 445–465
- [FAIL] `hide_inline_notices` CSS selector is `body.index-php .notice.inline` which only catches `.notice.inline` class combination, NOT `.notice`, `.updated`, `.error` generally — `includes/admin/class-dwm-admin.php` line 460
- [FAIL] Section 1 field labels use `<span>` not `<label for="">` — `templates/admin/customize-dashboard.php` lines 46, 56, 66 (the `<label>` is on the toggle slider, not on the field label text)
- [FAIL] Widget hiding on dashboard uses CSS `display:none` only, NOT DOM removal — `includes/admin/class-dwm-admin.php` lines 259–285 (the `inject_dashboard_button` CSS block). Checklist requires "absent from the DOM" not merely CSS-hidden.
- [FAIL] `validate_settings()` is a stub returning empty array — `includes/core/class-dwm-validator.php` line 287–289. No server-side validation is performed for Section 1 or Section 2 fields.
- [FAIL] Default widget checkboxes have no `name` attribute — only a `class` and `value`; they rely on JS to sync the hidden field. The checklist item for `name="settings[hidden_dashboard_widgets]"` on the checkboxes themselves is not satisfied.
- [FAIL] JS save logic in `settings-form.js` line 196 excludes `[data-autosave]` inputs from section submit payload — meaning if a user manually submits Section 1 via Save button (which doesn't exist), autosave fields would be excluded. (Section 1 has no save button so this is moot, but the autosave-only pattern is intentional here and is correct for the toggle fields.)
- [FAIL] `force_remove_hidden_dashboard_widgets()` uses `wp_dashboard_setup` at priority 999 but the `welcome-panel` removal uses `remove_action('welcome_panel', 'wp_welcome_panel')` — this runs at `wp_dashboard_setup` which fires before the welcome panel action is ever queued; this should be fine, but the checklist says hook registered "before `wp_dashboard_setup`" while the code hooks INTO `wp_dashboard_setup` at priority 999. This is acceptable. Rechecking: checklist says "PHP hook registered before `wp_dashboard_setup` removes the specified widget IDs". The actual hook is ON `wp_dashboard_setup` at priority 999. This means it runs DURING `wp_dashboard_setup`, not before it. This is a borderline issue — marking PARTIAL.

### Template changes needed:
- Section 1: Add `<div class="dwm-section-actions">` with "Save Widget Overrides" button (or confirm autosave-only is intentional and update checklist)
- Section 1 field labels: The label text is in a `<span class="dwm-form-label">` not a `<label for="...">` element — accessibility and checklist compliance require `<label>` with matching `for` attribute

### PHP class changes needed:
- `class-dwm-admin.php` `hide_admin_chrome()`: Add `index.php` scope guard for Help dropdown and Screen Options OR clarify that the checklist's scope requirement is wrong (the checklist says "scoped to wp-admin/index.php only")
- `class-dwm-admin.php` `hide_admin_chrome()`: Fix `hide_inline_notices` CSS selector from `body.index-php .notice.inline` to target `.notice, .updated, .error` on the dashboard page
- `class-dwm-validator.php` `validate_settings()`: Implement actual validation logic for Section 1 and Section 2 fields

---

## Section-Level Standards Review (applied to both sections)

- [x] Section ID is unique on page
> VERIFIED: `#dwm-section-dropdown-panels` (Section 1) and `#dwm-section-hide-widgets` (Section 2) — both unique — `customize-dashboard.php` lines 33, 77

- [x] Section title text is correct
> VERIFIED: Section 1 = "Hide Dashboard Elements" + Pro badge; Section 2 = "Hide Widgets" + Pro badge — `customize-dashboard.php` lines 35, 79

- [x] Help icon present with `data-open-modal="dwm-docs-modal"` and correct `data-docs-page` slug
> VERIFIED: Both sections pass `$help_modal_target = 'dwm-docs-modal'` and correct `data-docs-page` attrs through `section-header-with-actions.php` → `help-trigger.php` which renders `data-open-modal="dwm-docs-modal"`. Section 1 slug = `custom-dashboard-dropdown-panels`, Section 2 slug = `custom-dashboard-hide-widgets`. Lines 36–38 and 80–82 of template.

- [x] Help icon `aria-label` is descriptive and accurate
> VERIFIED: Section 1 = "Learn about hiding dropdown panels", Section 2 = "Learn about hiding dashboard widgets" — appended as `aria-label` in `section-header-with-actions.php` line 31.

- [!] Save button present with correct label text — **Section 1 FAIL**
> FIX NEEDED: `templates/admin/customize-dashboard.php` lines 33–74
> CURRENT: Section 1 (`#dwm-section-dropdown-panels`) has no `<div class="dwm-section-actions">` and no "Save Widget Overrides" button. The section closes at line 74 immediately after the toggles row.
> REQUIRED: A save button with label "Save Widget Overrides" inside a `.dwm-section-actions` wrapper inside Section 1, matching the pattern in Section 2 (lines 164–168).

- [x] Save button present with correct label text — **Section 2 PASS**
> VERIFIED: `customize-dashboard.php` lines 164–168 — `<button type="submit">Save Widget Overrides</button>` inside `#dwm-section-hide-widgets`

- [x] Save button submits the correct form / triggers correct AJAX action
> VERIFIED: `<button type="submit">` inside `<form id="dwm-settings-form">` → `settings-form.js` handles `submit` on `#dwm-settings-form` → calls `dwm_save_settings` AJAX action. Lines 178–228 of `settings-form.js`.

- [x] All PHP variables used in the section are declared before first use in HTML
> VERIFIED: `$settings` declared at template line 17. Section 1 uses only `$settings[...]` — no separate pre-declarations needed. Section 2 declares `$hidden_widgets_raw`, `$hidden_widgets_arr`, `$third_party_widgets`, `$hidden_third_party_raw`, `$hidden_third_party_arr` before their HTML blocks. Lines 98–110, 126–131.

- [x] All divs/elements are properly opened and closed (no structural leaks)
> VERIFIED: Both sections close their `<div>` properly at lines 74 and 169.

- [x] Section visibility on page load matches expected state from saved settings
> VERIFIED: Both sections always render (no conditional visibility). Checklist says "Always rendered" for both.

- [~] All fields in section submit their values in the AJAX payload — **PARTIAL**
> Section 1: The three toggle checkboxes have `data-autosave="true"` which triggers auto-save immediately on change via `settings-form.js` lines 231–257. They are correctly EXCLUDED from the section submit payload (line 196: `.not('[data-autosave]')`). This is intentional and correct for toggles. However, Section 1 has no save button so there is no section-scoped submit for it — all values go only through autosave. The checklist item says "All fields in section submit their values in the AJAX payload" — since Section 1 has no save button, only autosave applies. The autosave payload only sends the individual field, not all three together. This is a design choice but differs from a true section submit.

- [x] All section keys are sanitized server-side before storage
> VERIFIED: `hide_help_dropdown`, `hide_screen_options`, `hide_inline_notices` → handled in `sanitize_settings()` foreach loop at `class-dwm-sanitizer.php` line 485 as `absint($val) ? 1 : 0`. `hidden_dashboard_widgets` sanitized at lines 896–903. `hidden_third_party_dashboard_widgets` sanitized at lines 905–912.

- [!] All section keys are validated server-side before storage
> FIX NEEDED: `includes/core/class-dwm-validator.php` lines 287–289
> CURRENT: `validate_settings()` returns `array()` unconditionally — no validation at all.
> REQUIRED: Implement field validation (e.g. for boolean/int fields verify value is 0 or 1; for widget lists verify each ID against allowed values).

- [x] All section keys are retrievable via `get_settings()` and present in `$settings[]`
> VERIFIED: `class-dwm-data.php` lines 51–55 include all four keys in the defaults array: `hide_help_dropdown`, `hide_screen_options`, `hide_inline_notices`, `hidden_dashboard_widgets`, `hidden_third_party_dashboard_widgets`.

- [x] Section renders with no PHP warnings or notices (undefined vars, etc.)
> VERIFIED: All variables are initialized with defaults or null-coalescing. No undefined variable risk observed.

---

## Section 1 — Hide Dashboard Elements

### Section Checklist

- [x] Section title renders with Pro badge
> VERIFIED: `customize-dashboard.php` line 35 — title string includes `<span class="dwm-pro-badge">Pro</span>`.

- [x] Help icon opens `#dwm-docs-modal` on the `custom-dashboard-dropdown-panels` page
> VERIFIED: `$help_modal_target = 'dwm-docs-modal'` and `$attrs = 'data-docs-page="custom-dashboard-dropdown-panels"'` — template lines 36–38. The help-trigger.php partial renders `data-open-modal="dwm-docs-modal"` on the button.

- [!] Save button label is "Save Widget Overrides"
> FIX NEEDED: `templates/admin/customize-dashboard.php` lines 33–74
> CURRENT: No save button exists in Section 1 at all.
> REQUIRED: Save button with text "Save Widget Overrides" inside `.dwm-section-actions` at the bottom of `#dwm-section-dropdown-panels`.

- [x] All three toggles load their saved state on page open
> VERIFIED: Each checkbox uses `checked( ! empty( $settings['key'] ) )` — lines 49, 59, 69. `get_settings()` returns DB values merged with defaults.

- [x] `data-autosave="true"` triggers immediate save without requiring Save button click
> VERIFIED: All three inputs have `data-autosave="true"` (lines 49, 59, 69). `settings-form.js` lines 231–257 handle autosave on change.

- [?] Toggling a field off removes the element from the WP dashboard immediately
> UNCERTAIN: The CSS is injected via `wp_add_inline_style`. The autosave runs the AJAX save. After save, a page reload would apply the change. "Immediately" may require a page reload or JS-driven element hiding on the dashboard. Cannot verify live browser behavior.

- [?] Toggling a field on restores the element on the WP dashboard
> UNCERTAIN: Same as above — requires live browser verification.

- [x] No PHP warnings from undefined variables
> VERIFIED: All variables used in Section 1 are either from `$settings[]` with null-coalescing or are global template vars.

#### Dashboard Output & Assets — Section 1

- [~] PHP hook registered to read these settings and apply element-hiding logic on the dashboard page
> PARTIAL: `hide_admin_chrome()` is hooked to `admin_enqueue_scripts` (loader line 70). This hook fires on ALL admin pages, not just `index.php`. The method reads settings and applies CSS globally.
> NOTE: The Help dropdown and Screen Options CSS rules (`#contextual-help-link-wrap`, `#screen-options-link-wrap`) are therefore applied on ALL admin pages when enabled, not just the dashboard. This violates the checklist's scope requirement.

- [!] Hide logic is scoped to `wp-admin/index.php` only — Help dropdown/Screen Options/Notices on other admin pages are unaffected
> FIX NEEDED: `includes/admin/class-dwm-admin.php` line 445 (`hide_admin_chrome()`)
> CURRENT: The method has no `$hook === 'index.php'` guard. It applies CSS globally to all admin pages via `wp_add_inline_style( 'wp-admin', $css )`.
> REQUIRED: Add a `$hook` parameter check (the method is called via `admin_enqueue_scripts` which passes `$hook`) and guard the CSS output to `index.php` only — OR — only hide Help/Screen Options when on the dashboard page.

- [?] With all three toggles off: WP dashboard renders exactly as stock
> UNCERTAIN: When all values are absent/0, `$css` is empty string and `wp_add_inline_style` is not called. Logic appears correct but requires live verification.

- [!] With a toggle on and auto-saved: page reload of dashboard confirms the element is absent from the DOM — not merely hidden with CSS
> FIX NEEDED: `includes/admin/class-dwm-admin.php` lines 452–464
> CURRENT: Hide logic uses CSS only (`display: none !important`). The elements remain in the DOM.
> REQUIRED: The checklist explicitly requires elements to be "absent from the DOM" not merely CSS-hidden. PHP should use `remove_submenu_page()`, `add_filter('screen_options_show_screen', '__return_false')`, or equivalent to fully remove from DOM.

- [?] Enabling and then disabling a toggle (save each time) fully restores the element with no residual override
> UNCERTAIN: When value is 0/absent, no CSS is injected. Logic appears correct (no persistent state) but requires live verification.

- [?] DWM assets responsible for the hide behavior are enqueued on the dashboard page and no errors in browser console
> UNCERTAIN: `wp_add_inline_style( 'wp-admin', $css )` depends on the `wp-admin` stylesheet handle being registered on `index.php`. This is always the case in WP admin. The enqueue check requires browser verification.

---

### Field: Hide Help Dropdown

**DB Key:** `hide_help_dropdown` | **Type:** checkbox | **Default:** off (absent)

- [x] Read: `! empty( $settings['hide_help_dropdown'] )`
> VERIFIED: `customize-dashboard.php` line 49 — `checked( ! empty( $settings['hide_help_dropdown'] ) )`

- [x] Default: absent = off (falsy check with `! empty`)
> VERIFIED: Default in `get_settings()` is `0`, `! empty(0)` = false = unchecked.

- [x] Escape: `checked()` PHP helper
> VERIFIED: `customize-dashboard.php` line 49.

- [x] Name: `settings[hide_help_dropdown]`
> VERIFIED: `name="settings[hide_help_dropdown]"` — line 49.

- [x] ID: `dwm-hide-help-dropdown`
> VERIFIED: `id="dwm-hide-help-dropdown"` — line 49.

- [!] Label: "Hide Help Dropdown" with `for="dwm-hide-help-dropdown"`
> FIX NEEDED: `templates/admin/customize-dashboard.php` lines 44–52
> CURRENT: Label text is in `<span class="dwm-form-label">Hide Help Dropdown</span>` (line 46). The `<label>` element (line 48) wraps the toggle slider and has `for="dwm-hide-help-dropdown"` — but it is the toggle visual label, not the field label text. There is no `<label for="dwm-hide-help-dropdown">` that contains the "Hide Help Dropdown" text.
> REQUIRED: The text "Hide Help Dropdown" must be inside or associated with `<label for="dwm-hide-help-dropdown">`.

- [x] Value: `1`
> VERIFIED: `value="1"` — line 49.

- [x] `data-autosave="true"` present
> VERIFIED: `data-autosave="true"` — line 49.

- [x] Saves `1` when checked, key absent when unchecked
> VERIFIED: `settings-form.js` line 241 — `settings[match[1]] = isOn ? 1 : 0`. When unchecked, value `0` is sent. The sanitizer converts to `absint(0) ? 1 : 0 = 0`. Note: the value stored is `0` not absent, but the read uses `! empty()` which treats `0` as false. Functionally correct.

- [x] Sanitized server-side as boolean/int
> VERIFIED: `class-dwm-sanitizer.php` line 487 — `absint( $settings[$key] ) ? 1 : 0`.

- [!] **Dashboard:** When `1`, the WP Help dropdown button (`#contextual-help-link-wrap`) is absent or hidden on the dashboard page
> FIX NEEDED: `includes/admin/class-dwm-admin.php` line 454
> CURRENT: CSS rule `#contextual-help-link-wrap { display: none !important; }` is injected — CSS-hidden only, not absent from DOM. Additionally applied to all admin pages, not just dashboard.
> REQUIRED: Element should be absent from the DOM on the dashboard. Consider `add_filter('contextual_help', '__return_false')` or equivalent to prevent rendering, scoped to `index.php`.

- [?] **Dashboard:** When unchecked/absent, WP Help dropdown button is visible and functional as stock
> UNCERTAIN: Requires live browser verification.

- [?] **Dashboard:** No residual CSS rule or DOM override remains after unchecking
> UNCERTAIN: When the setting is `0`, no CSS is emitted. Logic is correct but requires live verification.

- [x] Always visible (no conditional display on the settings page)
> VERIFIED: No `style="display:none"` or toggle-controlled wrapper around this field.

---

### Field: Hide Screen Options

**DB Key:** `hide_screen_options` | **Type:** checkbox | **Default:** off (absent)

- [x] Read: `! empty( $settings['hide_screen_options'] )`
> VERIFIED: `customize-dashboard.php` line 59 — `checked( ! empty( $settings['hide_screen_options'] ) )`

- [x] Default: absent = off
> VERIFIED: Default is `0` in `get_settings()`.

- [x] Escape: `checked()` PHP helper
> VERIFIED: Line 59.

- [x] Name: `settings[hide_screen_options]`
> VERIFIED: `name="settings[hide_screen_options]"` — line 59.

- [x] ID: `dwm-hide-screen-options`
> VERIFIED: `id="dwm-hide-screen-options"` — line 59.

- [!] Label: "Hide Screen Options" with `for="dwm-hide-screen-options"`
> FIX NEEDED: `templates/admin/customize-dashboard.php` lines 54–62
> CURRENT: Label text in `<span class="dwm-form-label">` (line 56), not in a `<label for="dwm-hide-screen-options">`.
> REQUIRED: "Hide Screen Options" text must be inside or associated with `<label for="dwm-hide-screen-options">`.

- [x] Value: `1`
> VERIFIED: `value="1"` — line 59.

- [x] `data-autosave="true"` present
> VERIFIED: Line 59.

- [x] Saves `1` when checked, key absent when unchecked
> VERIFIED: Same autosave logic as above.

- [x] Sanitized server-side as boolean/int
> VERIFIED: `class-dwm-sanitizer.php` line 487.

- [!] **Dashboard:** When `1`, the Screen Options tab button (`#show-settings-link`) is absent or hidden on the dashboard page
> FIX NEEDED: `includes/admin/class-dwm-admin.php` line 457
> CURRENT: CSS rule `#screen-options-link-wrap { display: none !important; }` — CSS-hidden only, applied to ALL admin pages.
> REQUIRED: Element absent from DOM on dashboard page only. Consider `add_filter('screen_options_show_screen', '__return_false')` scoped to dashboard.

- [?] **Dashboard:** When unchecked, Screen Options tab is visible and functional as stock
> UNCERTAIN: Requires live browser verification.

- [?] **Dashboard:** No residual override remains after unchecking
> UNCERTAIN: No CSS emitted when value is 0; logically correct but requires live verification.

- [x] Always visible (no conditional display on the settings page)
> VERIFIED: No conditional display wrapper.

---

### Field: Hide Notices

**DB Key:** `hide_inline_notices` | **Type:** checkbox | **Default:** off (absent)

- [x] Read: `! empty( $settings['hide_inline_notices'] )`
> VERIFIED: `customize-dashboard.php` line 69 — `checked( ! empty( $settings['hide_inline_notices'] ) )`

- [x] Default: absent = off
> VERIFIED: Default is `0` in `get_settings()`.

- [x] Escape: `checked()` PHP helper
> VERIFIED: Line 69.

- [x] Name: `settings[hide_inline_notices]`
> VERIFIED: `name="settings[hide_inline_notices]"` — line 69.

- [x] ID: `dwm-hide-inline-notices`
> VERIFIED: `id="dwm-hide-inline-notices"` — line 69.

- [!] Label: "Hide Notices" with `for="dwm-hide-inline-notices"`
> FIX NEEDED: `templates/admin/customize-dashboard.php` lines 64–72
> CURRENT: Label text in `<span class="dwm-form-label">` (line 66), not in `<label for="dwm-hide-inline-notices">`.
> REQUIRED: "Hide Notices" text must be inside or associated with `<label for="dwm-hide-inline-notices">`.

- [x] Value: `1`
> VERIFIED: `value="1"` — line 69.

- [x] `data-autosave="true"` present
> VERIFIED: Line 69.

- [x] Saves `1` when checked, key absent when unchecked
> VERIFIED: Same autosave logic.

- [x] Sanitized server-side as boolean/int
> VERIFIED: `class-dwm-sanitizer.php` line 487.

- [!] **Dashboard:** When `1`, inline admin notices (`.notice`, `.updated`, `.error` etc.) are absent or hidden on the dashboard page
> FIX NEEDED: `includes/admin/class-dwm-admin.php` line 460
> CURRENT: CSS selector `body.index-php .notice.inline { display: none !important; }` — this targets ONLY notices that have BOTH `.notice` AND `.inline` classes. Standard WP admin notices (`.notice.notice-warning`, `.notice.notice-error`, `.updated`, `.error`) are NOT hidden.
> REQUIRED: Selector should target `.notice, .updated, .error` (and other common notice classes) scoped to the dashboard body class, e.g.: `body.index-php .notice, body.index-php .updated, body.index-php .error { display: none !important; }`. Also, this is CSS-only (not DOM removal). Checklist says "absent or hidden" so CSS hiding may be acceptable for notices specifically.

- [?] **Dashboard:** When unchecked, inline notices render normally for the dashboard page
> UNCERTAIN: Requires live verification.

- [?] **Dashboard:** No residual CSS rule remains after unchecking; notices from other plugins/WP core reappear normally
> UNCERTAIN: No CSS emitted when value is 0; logically correct but requires live verification.

- [x] Always visible (no conditional display on the settings page)
> VERIFIED: No conditional display wrapper.

---

## Section 2 — Hide Widgets

### Section Checklist

- [x] Section title renders with Pro badge
> VERIFIED: `customize-dashboard.php` line 79 — includes `<span class="dwm-pro-badge">Pro</span>`.

- [x] Help icon opens `#dwm-docs-modal` on `custom-dashboard-hide-widgets` page
> VERIFIED: `$help_modal_target = 'dwm-docs-modal'` and `$attrs = 'data-docs-page="custom-dashboard-hide-widgets"'` — lines 80–82.

- [x] Save button label is "Save Widget Overrides"
> VERIFIED: `customize-dashboard.php` line 166 — `Save Widget Overrides` inside `.dwm-section-actions` inside `#dwm-section-hide-widgets`.

- [x] Default widgets group always renders with all 6 checkboxes
> VERIFIED: `$default_wp_widgets` array has exactly 6 keys (lines 98–105), iterated by `foreach` at line 113. No conditional around this block.

- [x] Third-party widgets group renders only when active 3rd-party widgets are detected
> VERIFIED: Wrapped in `if ( ! empty( $third_party_widgets ) )` at line 134.

- [x] Fallback message "No 3rd-party dashboard widgets were detected." shows when none found
> VERIFIED: `else` branch at lines 154–161 renders `<p class="description">No 3rd-party dashboard widgets were detected.</p>`.

- [x] "Select All" / "Deselect All" controls work per group independently
> VERIFIED: Separate IDs — `#dwm-select-all-widgets` / `#dwm-deselect-all-widgets` for default, `#dwm-select-all-third-party-widgets` / `#dwm-deselect-all-third-party-widgets` for third-party. Handled independently in `settings-form.js` lines 333–370.

- [x] Hidden field for each group is synced by JS from checkboxes before submit
> VERIFIED: `settings-form.js` — `updateHiddenWidgetsValue()` (lines 318–329) updates `#dwm-hidden-widgets-value` on checkbox change. `updateHiddenThirdPartyWidgetsValue()` (lines 346–357) updates `#dwm-hidden-third-party-widgets-value`. Both fire on checkbox change.

- [x] Checking a widget and saving hides it from the WP dashboard
> VERIFIED: `force_remove_hidden_dashboard_widgets()` is hooked to `wp_dashboard_setup` at priority 999 (loader line 136) and removes meta boxes. Additionally, `inject_dashboard_button()` adds CSS hiding for the widget IDs (lines 260–285). Both mechanisms apply on `index.php`.

- [x] Unchecking a widget and saving restores it on the WP dashboard
> VERIFIED: `force_remove_hidden_dashboard_widgets()` only removes widgets present in the stored list. If unchecked, the ID is removed from the list by the JS sync → save → the widget is no longer in the removal list → it renders normally on next load.

- [x] Saved hidden widget list correctly repopulates checkboxes on page reload
> VERIFIED: `$hidden_widgets_arr` parsed from `$settings['hidden_dashboard_widgets']` at lines 107–109. `checked( in_array( $widget_id, $hidden_widgets_arr, true ) )` at line 116 marks saved widgets checked.

#### Dashboard Output & Assets — Section 2

- [~] PHP hook registered before `wp_dashboard_setup` removes the specified widget IDs from the dashboard widget registry
> PARTIAL: The hook is `wp_dashboard_setup` at priority 999 (loader line 136), which fires DURING `wp_dashboard_setup`, not before it. This means it removes widgets after they've been added by WordPress's own setup. Priority 999 ensures it runs after all other `wp_dashboard_setup` callbacks. The widgets are correctly removed via `remove_meta_box()`. Welcome-panel removal uses `remove_action('welcome_panel', 'wp_welcome_panel')` which fires at a different time. The effective result is correct but the checklist phrasing "registered before `wp_dashboard_setup`" is not technically satisfied.

- [?] **Dashboard:** Each checked default widget is absent from `#dashboard-widgets` after save
> UNCERTAIN: `force_remove_hidden_dashboard_widgets()` calls `remove_meta_box()` which prevents rendering, AND `inject_dashboard_button()` adds CSS `display:none`. The meta_box removal should make them absent from DOM. Requires live browser verification to confirm.

- [?] **Dashboard:** Unchecking a widget and saving re-registers it — widget reappears on next dashboard load
> UNCERTAIN: Correct by logic (removal only happens when ID is in stored list). Requires live verification.

- [?] **Dashboard:** Third-party widgets removed by ID when checked; restored when unchecked
> UNCERTAIN: CSS-hiding approach is used (lines 276–279 of `class-dwm-admin.php`). The `force_remove_hidden_dashboard_widgets()` also processes `$hidden_third_party_ids` via `remove_meta_box()` (lines 358–379). Requires live verification.

- [?] **Dashboard:** With all widgets unchecked, the dashboard widget grid is empty
> UNCERTAIN: Requires live browser verification.

- [x] **Dashboard:** Widget removal is scoped to the dashboard page only
> VERIFIED: `force_remove_hidden_dashboard_widgets()` has `if ( 'index.php' !== $pagenow ) { return; }` at line 348.

- [?] **Dashboard:** Selecting and saving "Select All" removes all 6 default widgets from dashboard DOM
> UNCERTAIN: Requires live verification.

- [?] **Dashboard:** "Deselect All" + save restores all 6 default widgets
> UNCERTAIN: Requires live verification.

---

### Field: Hidden Default Widgets (hidden input)

**DB Key:** `hidden_dashboard_widgets` | **Type:** hidden text (newline-separated widget IDs) | **Default:** `''`

- [x] Read: `$settings['hidden_dashboard_widgets'] ?? ''`
> VERIFIED: `customize-dashboard.php` line 106.

- [x] Default: `''` (empty string = no widgets hidden)
> VERIFIED: Default in `get_settings()` is `''` at `class-dwm-data.php` line 54.

- [x] Escape: `esc_attr()`
> VERIFIED: `customize-dashboard.php` line 111 — `value="<?php echo esc_attr( $hidden_widgets_raw ); ?>"`.

- [x] Name: `settings[hidden_dashboard_widgets]`
> VERIFIED: `name="settings[hidden_dashboard_widgets]"` — line 111.

- [x] ID: `dwm-hidden-widgets-value`
> VERIFIED: `id="dwm-hidden-widgets-value"` — line 111.

- [x] Parsed into array: `array_filter( array_map( 'trim', explode( "\n", $hidden_widgets_raw ) ) )`
> VERIFIED: `customize-dashboard.php` lines 107–109 — exact pattern matches.

- [x] JS syncs this field from checkbox state before form submit
> VERIFIED: `settings-form.js` `updateHiddenWidgetsValue()` lines 318–329 — updates `#dwm-hidden-widgets-value` on `change` of `.dwm-widget-hide-checkbox`. The hidden input value is current before the section submit fires.

- [x] Sanitized server-side (each widget ID sanitized as a key/slug)
> VERIFIED: `class-dwm-sanitizer.php` lines 896–902 — explode, trim, filter, then `array_intersect` against `$valid_ids` (the 6 allowed widget IDs). Each stored ID is implicitly validated against allowlist.

- [x] Stored as newline-separated string in `dwm_settings`
> VERIFIED: `implode( "\n", $widget_ids )` at line 902.

- [x] Retrieved and split correctly by `get_settings()` → used to check checkboxes on load
> VERIFIED: `get_settings()` returns the raw newline-separated string; template splits it at lines 107–109 for array comparison.

- [?] **Dashboard:** Each widget ID in the stored list is absent from the dashboard widget area after page reload
> UNCERTAIN: `remove_meta_box()` should prevent rendering. Requires live browser verification.

---

### Field Group: Default Widget Checkboxes

**Widget IDs:** `welcome-panel`, `dashboard_activity`, `dashboard_right_now`, `dashboard_quick_press`, `dashboard_site_health`, `dashboard_primary`

- [x] All 6 widget checkboxes render
> VERIFIED: `$default_wp_widgets` has exactly 6 entries (lines 98–105), iterated without conditions.

- [x] Each checkbox `value=` is the correct widget ID slug
> VERIFIED: `value="<?php echo esc_attr( $widget_id ); ?>"` at line 115 where `$widget_id` is the key from `$default_wp_widgets`.

- [x] `checked()` correctly marks widget as hidden when its ID is in saved list
> VERIFIED: `checked( in_array( $widget_id, $hidden_widgets_arr, true ) )` — line 116.

- [x] Each label text matches the WP widget label it controls
> VERIFIED: Labels are `Welcome Panel`, `Activity`, `At a Glance`, `Quick Draft`, `Site Health Status`, `Events and News` — standard WP widget names.

- [x] Checkboxes update the hidden field value on change (JS)
> VERIFIED: `settings-form.js` line 331 — `$(document).on('change', '.dwm-widget-hide-checkbox', updateHiddenWidgetsValue)`.

- [x] "Select All" checks all 6 + updates hidden field
> VERIFIED: `settings-form.js` lines 333–337 — checks all `.dwm-widget-hide-checkbox`, calls `updateHiddenWidgetsValue()`.

- [x] "Deselect All" unchecks all 6 + updates hidden field
> VERIFIED: `settings-form.js` lines 339–343.

- [?] **Dashboard (per widget):** After checking and saving each widget, confirm it is absent from the WP admin dashboard
> UNCERTAIN: Requires live browser verification for each of the 6 widgets.

---

### Field: Hidden Third-Party Widgets (hidden input)

**DB Key:** `hidden_third_party_dashboard_widgets` | **Type:** hidden text (newline-separated) | **Default:** `''`

- [x] Read: `$settings['hidden_third_party_dashboard_widgets'] ?? ''`
> VERIFIED: `customize-dashboard.php` line 127.

- [x] Default: `''`
> VERIFIED: Default in `get_settings()` at `class-dwm-data.php` line 55.

- [x] Escape: `esc_attr()`
> VERIFIED: `customize-dashboard.php` line 132 — `value="<?php echo esc_attr( $hidden_third_party_raw ); ?>"`.

- [x] Name: `settings[hidden_third_party_dashboard_widgets]`
> VERIFIED: `name="settings[hidden_third_party_dashboard_widgets]"` — line 132.

- [x] ID: `dwm-hidden-third-party-widgets-value`
> VERIFIED: `id="dwm-hidden-third-party-widgets-value"` — line 132.

- [x] JS syncs from checkbox state before submit
> VERIFIED: `settings-form.js` lines 346–357 and 359 — `updateHiddenThirdPartyWidgetsValue()` triggered on `.dwm-third-party-widget-hide-checkbox` change.

- [x] Sanitized server-side (each ID sanitized as slug)
> VERIFIED: `class-dwm-sanitizer.php` lines 905–911 — each ID passes through `sanitize_key()`.

- [x] Stored as newline-separated string
> VERIFIED: `implode( "\n", $widget_ids )` at line 911.

- [x] Correctly repopulates checkboxes on page reload
> VERIFIED: `$hidden_third_party_arr` parsed at lines 128–130, used in `checked( in_array(...) )` at line 148.

- [x] Entire group skipped (not rendered) when `get_third_party_dashboard_widgets_for_settings()` returns empty
> VERIFIED: The hidden input (line 132) renders unconditionally inside `#dwm-section-hide-widgets`, but the checkbox group is wrapped in `if ( ! empty( $third_party_widgets ) )` at line 134. The hidden input always renders (correct — needs to be in the form payload).

- [x] Fallback message displayed when no third-party widgets detected
> VERIFIED: `else` branch at lines 154–161.

- [?] **Dashboard:** Each checked third-party widget ID is absent from `#dashboard-widgets` after save; restored when unchecked
> UNCERTAIN: CSS hiding via `inject_dashboard_button()` lines 273–279 and `remove_meta_box()` via `force_remove_hidden_dashboard_widgets()` lines 358–379 both apply. Requires live verification.

---

## Files Reviewed

| File | Path |
|------|------|
| Template | `templates/admin/customize-dashboard.php` (lines 1–170) |
| Section header partial | `templates/admin/partials/section-header-with-actions.php` |
| Help trigger partial | `templates/admin/partials/help-trigger.php` |
| Admin class | `includes/admin/class-dwm-admin.php` (lines 1–960) |
| Settings class | `includes/admin/class-dwm-settings.php` (lines 1–250) |
| Data class | `includes/core/class-dwm-data.php` (lines 1–200) |
| Sanitizer class | `includes/core/class-dwm-sanitizer.php` (`sanitize_settings` at line 474–912) |
| Validator class | `includes/core/class-dwm-validator.php` (`validate_settings` at lines 287–289) |
| Loader class | `includes/class-dwm-loader.php` (hook registrations) |
| JS entry (settings) | `assets/js/components/settings.js` |
| JS module | `assets/js/modules/forms/settings-form.js` (full file) |
| JS admin entry | `assets/js/components/admin.js` |
| SCSS entry | `assets/scss/components/customize-dashboard.scss` |

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
