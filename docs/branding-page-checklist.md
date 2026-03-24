# QC Audit Status
*Last QC run: 2026-03-23 (Final QA Agent)*

- **Verified correct (no change needed):** ~370 items confirmed passing across all sections by analysis agents
- **Fixed and verified:** 27 items — all Round 1 (20) and Round 2 (7) fixes confirmed correct in actual code
- **Resolved as design decisions:** 3 items (widget hiding CSS-only, dashboard title JS-only, validate_settings stub)
- **Sent to final-runthrough — still open:** 1 failed QA (popup level SCSS missing), 15 live browser verifications
- **Closed this cycle:** 7 items resolved by Round 2 fixes

See `docs/final-runthrough-checklist.md` for all remaining items.

---

# DWM Branding Page — Production Readiness Checklist

**File:** `templates/admin/customize-dashboard.php`
**Storage table:** `dwm_settings` (all keys as individual rows)
**Data source:** `DWM_Data::get_instance()->get_settings()` → `$settings[]`
**Form:** `#dwm-settings-form` — single form, all sections, AJAX submit

---

## Production Readiness Standards

These are the criteria applied under every section and every field below.

### Section-Level Standards

- [ ] Section ID is unique on page
- [ ] Section title text is correct
- [ ] Help icon present with `data-open-modal="dwm-docs-modal"` and correct `data-docs-page` slug
- [ ] Help icon `aria-label` is descriptive and accurate
- [ ] Save button present with correct label text
- [ ] Save button submits the correct form / triggers correct AJAX action
- [ ] All PHP variables used in the section are declared **before** first use in HTML
- [ ] All divs/elements are properly opened and closed (no structural leaks)
- [ ] Section visibility on page load matches expected state from saved settings
- [ ] All fields in section submit their values in the AJAX payload
- [ ] All section keys are sanitized server-side before storage
- [ ] All section keys are validated server-side before storage
- [ ] All section keys are retrievable via `get_settings()` and present in `$settings[]`
- [ ] Section renders with no PHP warnings or notices (undefined vars, etc.)

### Field-Level Standards

#### PHP / Backend
- [ ] Value read from `$settings['key']` with `?? default` null-coalescing fallback
- [ ] Value cast to correct type (`(string)`, `(int)`, `! empty()`, `sanitize_key()`, etc.)
- [ ] Restricted fields (selects, modes) validated against explicit allowlist
- [ ] PHP variable declared **before** the HTML block that uses it
- [ ] Output escaped with the correct function:
  - Text/attr → `esc_attr()`
  - HTML display → `esc_html()`
  - URLs → `esc_url()`
  - Textarea content → `esc_textarea()`
  - Raw booleans → `checked()` / `selected()`

#### HTML / Form
- [ ] `name="settings[key]"` attribute present and matches DB key exactly
- [ ] `id=` attribute present and unique on the page
- [ ] `<label>` with matching `for="id"` attribute (or wrapping label element)
- [ ] `value=` attribute populated from PHP variable (not hardcoded)
- [ ] `selected()` PHP call present on all `<option>` elements
- [ ] `checked()` PHP call present on all checkboxes
- [ ] `min` / `max` constraints present on number and range inputs
- [ ] `type` attribute is correct for the input's purpose
- [ ] `placeholder` text present where field can be left empty

#### Conditional Display
- [ ] Initial visibility on page load driven by PHP (inline `style="display:none"` or `dwm-hidden-by-toggle` class) — not JS-only
- [ ] JS toggle shows/hides the field correctly when parent value changes
- [ ] Field is hidden when its parent toggle is off or irrelevant mode is selected
- [ ] Field is revealed with correct state when condition becomes true

#### JavaScript Behavior
- [ ] Linked input groups sync all values when link button is active
- [ ] Slider ↔ number input pairs stay in sync bidirectionally
- [ ] Live preview updates when relevant fields change (color, gradient, etc.)
- [ ] Modal-driven hidden fields are populated from saved values when modal opens
- [ ] Modal writes updated values back to hidden fields when applied/closed
- [ ] `data-autosave="true"` present on fields that should auto-save immediately
- [ ] `data-toggle-controls` attribute present on toggle checkboxes

#### Save & Persist
- [ ] Field key included in the AJAX save payload (not excluded by any JS filter)
- [ ] Key sanitized server-side (correct sanitize function for data type)
- [ ] Key validated server-side (range, allowed values, type check)
- [ ] Value stored correctly in `dwm_settings` table
- [ ] Value retrieved correctly by `get_settings()` on next page load
- [ ] Previously saved value correctly repopulates the field after save + reload

#### Dashboard Output & Asset Verification
- [ ] Setting is applied to the live dashboard when enabled/set
- [ ] Setting is removed/reset to stock WP when disabled or cleared
- [ ] No residual CSS, inline styles, or markup left when feature is toggled off
- [ ] Default value produces no visible change from stock WordPress dashboard
- [ ] Output is properly escaped before being written to the page
- [ ] PHP hook (e.g. `admin_head`, `wp_dashboard_setup`, filter, action) that generates this output is registered at correct priority
- [ ] Dashboard-side PHP reads the setting from DB (via `get_settings()`) on every dashboard page load
- [ ] Feature output is scoped to the WP dashboard page (`wp-admin/index.php`) — does not bleed to other admin pages
- [ ] DWM CSS and/or JS assets responsible for this feature are enqueued on the dashboard page
- [ ] End-to-end verified: configure setting → save → reload `wp-admin/index.php` → confirm correct output in browser DOM/source
- [ ] Conditional rendering correct: output changes appropriately when a related mode/toggle changes

#### UX / UI / Accessibility
- [ ] Label text is clear, accurate, and matches what the field controls
- [ ] Default value is sensible and results in expected behavior
- [ ] Reset / clear path exists and works (removing a value, toggling off, etc.)
- [ ] `aria-label` present on icon-only buttons
- [ ] Field order is logical within its group
- [ ] Error states (invalid value, empty required field) handled gracefully

---

## Section 1 — Hide Dashboard Elements

**Section ID:** `#dwm-section-dropdown-panels`
**Title:** "Hide Dashboard Elements" + Pro badge
**Help icon:** `aria-label="Learn about hiding dropdown panels"` → `data-docs-page="custom-dashboard-dropdown-panels"` → opens `#dwm-docs-modal`
**Save button:** "Save Widget Overrides" (shared with Section 2)
**Visibility:** Always rendered

### Section Checklist
- [ ] Section title renders with Pro badge
- [ ] Help icon opens `#dwm-docs-modal` on the `custom-dashboard-dropdown-panels` page
- [x] Save button present in Section 1 — `<div class="dwm-section-actions">` with submit button added at template lines 75–79 *(Fixed: Round 2 — structural requirement met; label reads "Save Changes" rather than "Save Widget Overrides" — see final-runthrough for label note)*
- [ ] All three toggles load their saved state on page open
- [ ] `data-autosave="true"` triggers immediate save without requiring Save button click
- [ ] Toggling a field off removes the element from the WP dashboard immediately
- [ ] Toggling a field on restores the element on the WP dashboard
- [ ] No PHP warnings from undefined variables

#### Dashboard Output & Assets — Section 1
- [ ] PHP hook (e.g. `admin_head` or admin-side filter) registered to read these settings and apply element-hiding logic on the dashboard page
- [x] Hide logic is scoped to `wp-admin/index.php` only — Help dropdown/Screen Options/Notices on other admin pages are unaffected *(Fixed: `$hook` guard added to `hide_admin_chrome()`)*
- [ ] With all three toggles off: WP dashboard renders exactly as stock (Help dropdown, Screen Options tab, and inline notices all visible)
- [ ] With a toggle on and auto-saved: page reload of dashboard confirms the element is absent from the DOM — not merely hidden with CSS
- [ ] Enabling and then disabling a toggle (save each time) fully restores the element with no residual override
- [ ] DWM assets (CSS/JS) responsible for the hide behavior are enqueued on the dashboard page and no errors in browser console

---

### Field: Hide Help Dropdown

**DB Key:** `hide_help_dropdown` | **Type:** checkbox | **Default:** off (absent)

- [ ] Read: `! empty( $settings['hide_help_dropdown'] )`
- [ ] Default: absent = off (falsy check with `! empty`)
- [ ] Escape: `checked()` PHP helper
- [ ] Name: `settings[hide_help_dropdown]`
- [ ] ID: `dwm-hide-help-dropdown`
- [x] Label: "Hide Help Dropdown" with `for="dwm-hide-help-dropdown"` *(Fixed: `<span>` → `<label for="...">` in template)*
- [ ] Value: `1`
- [ ] `data-autosave="true"` present
- [ ] Saves `1` when checked, key absent when unchecked
- [ ] Sanitized server-side as boolean/int
- [ ] **Dashboard:** When `1`, the WP Help dropdown button (`#contextual-help-link-wrap`) is absent or hidden on the dashboard page *(CSS hiding only — DOM removal not implemented; see final-runthrough)*
- [ ] **Dashboard:** When unchecked/absent, WP Help dropdown button is visible and functional as stock
- [ ] **Dashboard:** No residual CSS rule or DOM override remains after unchecking
- [ ] Always visible (no conditional display on the settings page)

---

### Field: Hide Screen Options

**DB Key:** `hide_screen_options` | **Type:** checkbox | **Default:** off (absent)

- [ ] Read: `! empty( $settings['hide_screen_options'] )`
- [ ] Default: absent = off
- [ ] Escape: `checked()` PHP helper
- [ ] Name: `settings[hide_screen_options]`
- [ ] ID: `dwm-hide-screen-options`
- [x] Label: "Hide Screen Options" with `for="dwm-hide-screen-options"` *(Fixed: `<span>` → `<label for="...">` in template)*
- [ ] Value: `1`
- [ ] `data-autosave="true"` present
- [ ] Saves `1` when checked, key absent when unchecked
- [ ] Sanitized server-side as boolean/int
- [ ] **Dashboard:** When `1`, the Screen Options tab button (`#show-settings-link`) is absent or hidden on the dashboard page
- [ ] **Dashboard:** When unchecked, Screen Options tab is visible and functional as stock
- [ ] **Dashboard:** No residual override remains after unchecking
- [ ] Always visible (no conditional display on the settings page)

---

### Field: Hide Notices

**DB Key:** `hide_inline_notices` | **Type:** checkbox | **Default:** off (absent)

- [ ] Read: `! empty( $settings['hide_inline_notices'] )`
- [ ] Default: absent = off
- [ ] Escape: `checked()` PHP helper
- [ ] Name: `settings[hide_inline_notices]`
- [ ] ID: `dwm-hide-inline-notices`
- [x] Label: "Hide Notices" with `for="dwm-hide-inline-notices"` *(Fixed: `<span>` → `<label for="...">` in template)*
- [ ] Value: `1`
- [ ] `data-autosave="true"` present
- [ ] Saves `1` when checked, key absent when unchecked
- [ ] Sanitized server-side as boolean/int
- [x] **Dashboard:** When `1`, inline admin notices (`.notice`, `.updated`, `.error` etc.) are absent or hidden on the dashboard page *(Fixed: CSS selector updated to cover `.notice, .updated, .error`; scoped to `body.index-php`)*
- [ ] **Dashboard:** When unchecked, inline notices render normally for the dashboard page
- [ ] **Dashboard:** No residual CSS rule remains after unchecking; notices from other plugins/WP core reappear normally
- [ ] Always visible (no conditional display on the settings page)

---

## Section 2 — Hide Widgets

**Section ID:** `#dwm-section-hide-widgets`
**Title:** "Hide Widgets" + Pro badge
**Help icon:** `aria-label="Learn about hiding dashboard widgets"` → `data-docs-page="custom-dashboard-hide-widgets"` → opens `#dwm-docs-modal`
**Save button:** "Save Widget Overrides" (shared with Section 1)
**Visibility:** Always rendered

### Section Checklist
- [ ] Section title renders with Pro badge
- [ ] Help icon opens `#dwm-docs-modal` on `custom-dashboard-hide-widgets` page
- [ ] Save button label is "Save Widget Overrides"
- [ ] Default widgets group always renders with all 6 checkboxes
- [ ] Third-party widgets group renders only when active 3rd-party widgets are detected
- [ ] Fallback message "No 3rd-party dashboard widgets were detected." shows when none found
- [ ] "Select All" / "Deselect All" controls work per group independently
- [ ] Hidden field for each group is synced by JS from checkboxes before submit
- [ ] Checking a widget and saving hides it from the WP dashboard
- [ ] Unchecking a widget and saving restores it on the WP dashboard
- [ ] Saved hidden widget list correctly repopulates checkboxes on page reload

#### Dashboard Output & Assets — Section 2
- [ ] PHP hook registered before `wp_dashboard_setup` removes the specified widget IDs from the dashboard widget registry
- [ ] **Dashboard:** Each checked default widget (`welcome-panel`, `dashboard_activity`, `dashboard_right_now`, `dashboard_quick_press`, `dashboard_site_health`, `dashboard_primary`) is absent from `#dashboard-widgets` after save
- [ ] **Dashboard:** Unchecking a widget and saving re-registers it — widget reappears on next dashboard load
- [ ] **Dashboard:** Third-party widgets (if any) are removed by ID when checked; restored when unchecked — identified by slug stored in DB
- [ ] **Dashboard:** With all widgets unchecked, the dashboard widget grid is empty (no widget panels rendered)
- [ ] **Dashboard:** Widget removal is scoped to the dashboard page only; no effect on other admin screens
- [ ] **Dashboard:** Selecting and saving "Select All" removes all 6 default widgets from dashboard DOM
- [ ] **Dashboard:** "Deselect All" + save restores all 6 default widgets

---

### Field: Hidden Default Widgets (hidden input)

**DB Key:** `hidden_dashboard_widgets` | **Type:** hidden text (newline-separated widget IDs) | **Default:** `''`

- [ ] Read: `$settings['hidden_dashboard_widgets'] ?? ''`
- [ ] Default: `''` (empty string = no widgets hidden)
- [ ] Escape: `esc_attr()`
- [ ] Name: `settings[hidden_dashboard_widgets]`
- [ ] ID: `dwm-hidden-widgets-value`
- [ ] Parsed into array: `array_filter( array_map( 'trim', explode( "\n", $hidden_widgets_raw ) ) )`
- [ ] JS syncs this field from checkbox state before form submit
- [ ] Sanitized server-side (each widget ID sanitized as a key/slug)
- [ ] Stored as newline-separated string in `dwm_settings`
- [ ] Retrieved and split correctly by `get_settings()` → used to check checkboxes on load
- [ ] **Dashboard:** Each widget ID in the stored list is absent from the dashboard widget area after page reload

---

### Field Group: Default Widget Checkboxes

**Widget IDs (values):** `welcome-panel`, `dashboard_activity`, `dashboard_right_now`, `dashboard_quick_press`, `dashboard_site_health`, `dashboard_primary`
**Checked via:** `in_array( $widget_id, $hidden_widgets_arr, true )`

- [ ] All 6 widget checkboxes render
- [ ] Each checkbox `value=` is the correct widget ID slug
- [ ] `checked()` correctly marks widget as hidden when its ID is in saved list
- [ ] Each label text matches the WP widget label it controls
- [ ] Checkboxes update the hidden field value on change (JS)
- [ ] "Select All" checks all 6 + updates hidden field
- [ ] "Deselect All" unchecks all 6 + updates hidden field
- [ ] **Dashboard (per widget):** After checking and saving each widget below, confirm it is absent from the WP admin dashboard:
  - `welcome-panel` → Welcome Panel box absent
  - `dashboard_activity` → Activity box absent
  - `dashboard_right_now` → At a Glance box absent
  - `dashboard_quick_press` → Quick Draft box absent
  - `dashboard_site_health` → Site Health Status box absent
  - `dashboard_primary` → Events and News box absent

---

### Field: Hidden Third-Party Widgets (hidden input)

**DB Key:** `hidden_third_party_dashboard_widgets` | **Type:** hidden text (newline-separated) | **Default:** `''`

- [ ] Read: `$settings['hidden_third_party_dashboard_widgets'] ?? ''`
- [ ] Default: `''`
- [ ] Escape: `esc_attr()`
- [ ] Name: `settings[hidden_third_party_dashboard_widgets]`
- [ ] ID: `dwm-hidden-third-party-widgets-value`
- [ ] JS syncs from checkbox state before submit
- [ ] Sanitized server-side (each ID sanitized as slug)
- [ ] Stored as newline-separated string
- [ ] Correctly repopulates checkboxes on page reload
- [ ] Entire group skipped (not rendered) when `get_third_party_dashboard_widgets_for_settings()` returns empty
- [ ] Fallback message displayed when no third-party widgets detected
- [ ] **Dashboard:** Each checked third-party widget ID is absent from `#dashboard-widgets` after save; restored when unchecked

---

## Section 3 — Dashboard Layout

**Section ID:** `#dwm-section-dashboard-layout`
**Title:** "Dashboard Layout"
**Help icon:** `aria-label="Learn about dashboard layout controls"` → `data-docs-page="custom-dashboard-layout"` → opens `#dwm-docs-modal`
**Save button:** "Save Dashboard Layout"
**Visibility:** Always rendered
**PHP init block (lines 181–186):** `$bg_type`, `$bg_gradient_type`, `$bg_angle`, `$bg_start_pos`, `$bg_end_pos` — all declared before any HTML in this section

### Section Checklist
- [ ] All 5 background PHP vars declared in this section's PHP init block (not in branding section)
- [ ] Help icon opens `#dwm-docs-modal` on `custom-dashboard-layout` page
- [ ] Save button label is "Save Dashboard Layout"
- [ ] Background toggle off: controls hidden on load; off = no custom background applied to dashboard
- [ ] Background toggle on: controls visible; background applied to dashboard
- [ ] Padding toggle off: controls hidden on load; off = stock WP dashboard padding
- [ ] Padding toggle on: controls visible; custom padding applied to dashboard
- [ ] Switching background type between solid/gradient shows/hides correct sub-controls
- [ ] Switching gradient type between linear/radial shows/hides angle control
- [ ] Changing gradient type to radial hides angle input
- [ ] All fields reload with saved values after save + page reload

#### Dashboard Output & Assets — Section 3
- [ ] PHP/JS reads background and padding settings from DB and injects inline CSS into `<head>` on dashboard page load
- [x] Inline CSS targets the correct WP dashboard wrapper element (`#wpbody-content`) — not the entire admin UI *(Fixed: background CSS target changed from `body.index-php` to `#wpbody-content`)*
- [ ] **Background:** With toggle ON + type `solid`: generated CSS is `background: {hex-color}` applied to dashboard wrapper
- [ ] **Background:** With toggle ON + type `gradient` + type `linear`: generated CSS is `background: linear-gradient({angle}deg, {start-color} {start-pos}%, {end-color} {end-pos}%)` on dashboard wrapper
- [ ] **Background:** With toggle ON + type `gradient` + type `radial`: generated CSS is `background: radial-gradient({start-color} {start-pos}%, {end-color} {end-pos}%)` (no angle) on dashboard wrapper
- [ ] **Background:** With toggle OFF: no background CSS injected; dashboard wrapper has stock WP background
- [ ] **Padding:** With toggle ON: generated CSS is `padding: {top}{unit} {right}{unit} {bottom}{unit} {left}{unit}` (or per-side properties) applied to dashboard wrapper
- [ ] **Padding:** With toggle OFF: no padding CSS injected; stock WP dashboard padding unchanged
- [ ] CSS output is scoped to dashboard page only — other admin pages (`/wp-admin/edit.php`, etc.) unaffected
- [ ] DWM dashboard CSS/JS asset files enqueued on dashboard page; no 404s or console errors

---

### Field: Custom Background (enable toggle)

**DB Key:** `dashboard_background_enabled` | **Type:** checkbox | **Default:** off

- [ ] Read: `! empty( $settings['dashboard_background_enabled'] )`
- [ ] Default: off (falsy)
- [ ] Escape: `checked()`
- [ ] Name: `settings[dashboard_background_enabled]`
- [ ] ID: `dwm-dashboard-background-enabled`
- [ ] Label: "Custom Background"
- [ ] Value: `1`
- [ ] `data-toggle-controls="#dwm-dashboard-background-controls"` present
- [ ] Container `#dwm-dashboard-background-controls` has `dwm-hidden-by-toggle` on load when off
- [ ] Container visible on load when saved as on
- [ ] Sanitized server-side as boolean/int
- [ ] **Dashboard:** When ON — background CSS is present in `<head>` on dashboard page
- [ ] **Dashboard:** When OFF — no background CSS injected; dashboard wrapper shows stock WP background color

---

### Field: Background Type

**DB Key:** `dashboard_background_type` | **Type:** select | **Default:** `solid`

- [x] PHP var: `$bg_type = (string)( $settings['dashboard_background_type'] ?? 'solid' )` — validated against `['solid','gradient']` *(Fixed: admin class allowlist changed from `['default','solid','gradient']` to `['solid','gradient']`; default changed from `'default'` to `'solid'`; condition changed to `in_array()` positive check)*
- [ ] Default: `solid`
- [ ] Escape: `selected()` on each option
- [ ] Name: `settings[dashboard_background_type]`
- [ ] ID: `dwm-background-type`
- [ ] Label: "Background Type" with `for="dwm-background-type"`
- [ ] Options: `solid` (Solid Color), `gradient` (Gradient)
- [ ] Saved value correctly pre-selects option on load
- [ ] Selecting `solid` shows `#dwm-bg-solid-controls`, hides gradient controls
- [ ] Selecting `gradient` shows gradient controls, hides solid controls
- [ ] Sanitized server-side as allowlist string
- [ ] Visible only when toggle ON
- [ ] **Dashboard:** `solid` → generated CSS uses a plain `background-color` or `background` hex value; no gradient function
- [ ] **Dashboard:** `gradient` → generated CSS uses `linear-gradient()` or `radial-gradient()` depending on sub-type

---

### Field: Background Color (solid)

**DB Key:** `dashboard_bg_solid_color` | **Type:** color picker | **Default:** `#ffffff`

- [ ] Read: `$settings['dashboard_bg_solid_color'] ?? '#ffffff'`
- [ ] Default: `#ffffff`
- [ ] Escape: `esc_attr()`
- [ ] Name: `settings[dashboard_bg_solid_color]`
- [ ] ID: `dwm-bg-solid-color`
- [ ] Label: "Background Color" with `for="dwm-bg-solid-color"`
- [ ] Container `#dwm-bg-solid-controls` uses inline `style="display:none"` on load when type ≠ `solid`
- [ ] Shown on load when `$bg_type === 'solid'`
- [ ] Sanitized server-side as hex color string
- [ ] Visible only when toggle ON AND type = `solid`
- [ ] **Dashboard:** Saved hex color is applied as CSS `background` (or `background-color`) on the dashboard wrapper element
- [ ] **Dashboard:** Changing the color, saving, and reloading dashboard shows the new color on the wrapper

---

### Field: Gradient Type

**DB Key:** `dashboard_bg_gradient_type` | **Type:** select | **Default:** `linear`

- [x] PHP var: `$bg_gradient_type = (string)( $settings['dashboard_bg_gradient_type'] ?? 'linear' )` — validated against `['linear','radial']` *(Fixed: `in_array()` validation added at template line 184)*
- [ ] Default: `linear`
- [ ] Escape: `selected()` on each option
- [ ] Name: `settings[dashboard_bg_gradient_type]`
- [ ] ID: `dwm-bg-gradient-type`
- [ ] Label: "Gradient Type" with `for="dwm-bg-gradient-type"`
- [ ] Options: `linear` (Linear), `radial` (Radial)
- [ ] Container `#dwm-bg-gradient-type-controls` uses inline `style="display:none"` on load when type ≠ `gradient`
- [ ] Saved value correctly pre-selects option on load
- [ ] Selecting `radial` hides `#dwm-bg-gradient-angle-wrap`
- [ ] Selecting `linear` shows `#dwm-bg-gradient-angle-wrap`
- [ ] Sanitized server-side as allowlist string
- [ ] Visible only when toggle ON AND type = `gradient`
- [ ] **Dashboard:** `linear` → generated CSS uses `linear-gradient({angle}deg, ...)` on dashboard wrapper
- [ ] **Dashboard:** `radial` → generated CSS uses `radial-gradient(...)` without an angle on dashboard wrapper

---

### Field: Gradient Angle

**DB Key:** `dashboard_bg_gradient_angle` | **Type:** range | **Default:** `90`

- [ ] PHP var: `$bg_angle = (int)( $settings['dashboard_bg_gradient_angle'] ?? 90 )`
- [ ] Default: `90`
- [ ] Escape: `esc_attr( (string) $bg_angle )`
- [ ] Name: `settings[dashboard_bg_gradient_angle]`
- [ ] ID: `dwm-bg-gradient-angle`
- [ ] Label: "Angle" with `for="dwm-bg-gradient-angle"`
- [ ] Class: `dwm-format-slider` present on range input
- [ ] `min="0"` `max="360"` present
- [ ] Display span `#dwm-bg-gradient-angle-value` shows current value + `°` on load and updates live
- [ ] Container `#dwm-bg-gradient-angle-wrap` uses inline `style="display:none"` on load when gradient type ≠ `linear`
- [ ] Sanitized server-side as integer 0–360
- [ ] Gradient preview `#dwm-bg-gradient-preview` updates live when angle changes
- [ ] Visible only when toggle ON AND type = `gradient` AND gradient type = `linear`
- [ ] **Dashboard:** Saved angle value reflected in the `linear-gradient({angle}deg, ...)` CSS on the dashboard wrapper (inspect element to verify degree value)

---

### Field: Gradient Start Color

**DB Key:** `dashboard_bg_gradient_start` | **Type:** color picker | **Default:** `#667eea`

- [ ] Read: `$settings['dashboard_bg_gradient_start'] ?? '#667eea'`
- [ ] Default: `#667eea`
- [ ] Escape: `esc_attr()`
- [ ] Name: `settings[dashboard_bg_gradient_start]`
- [ ] ID: `dwm-bg-gradient-start`
- [ ] Label: "Start Color" (no `for` — label wraps or adjacent)
- [ ] Gradient preview updates live when color changes
- [ ] Sanitized server-side as hex color string
- [ ] Visible only when toggle ON AND type = `gradient`

---

### Field: Gradient Start Position

**DB Key:** `dashboard_bg_gradient_start_position` | **Type:** range | **Default:** `0`

- [ ] PHP var: `$bg_start_pos = (int)( $settings['dashboard_bg_gradient_start_position'] ?? 0 )`
- [ ] Default: `0`
- [ ] Escape: `esc_attr( (string) $bg_start_pos )`
- [ ] Name: `settings[dashboard_bg_gradient_start_position]`
- [ ] ID: `dwm-bg-gradient-start-position`
- [ ] `min="0"` `max="100"` present
- [ ] Display span `#dwm-bg-gradient-start-position-label` shows current value + `%` on load and updates live
- [ ] Sanitized server-side as integer 0–100
- [ ] Gradient preview updates live when position changes
- [ ] Visible only when toggle ON AND type = `gradient`

---

### Field: Gradient End Color

**DB Key:** `dashboard_bg_gradient_end` | **Type:** color picker | **Default:** `#764ba2`

- [ ] Read: `$settings['dashboard_bg_gradient_end'] ?? '#764ba2'`
- [ ] Default: `#764ba2`
- [ ] Escape: `esc_attr()`
- [ ] Name: `settings[dashboard_bg_gradient_end]`
- [ ] ID: `dwm-bg-gradient-end`
- [ ] Label: "End Color"
- [ ] Gradient preview updates live when color changes
- [ ] Sanitized server-side as hex color string
- [ ] Visible only when toggle ON AND type = `gradient`
- [ ] **Dashboard:** Start color value present as first stop color in the CSS gradient on the dashboard wrapper

---

### Field: Gradient End Position

**DB Key:** `dashboard_bg_gradient_end_position` | **Type:** range | **Default:** `100`

- [ ] PHP var: `$bg_end_pos = (int)( $settings['dashboard_bg_gradient_end_position'] ?? 100 )`
- [ ] Default: `100`
- [ ] Escape: `esc_attr( (string) $bg_end_pos )`
- [ ] Name: `settings[dashboard_bg_gradient_end_position]`
- [ ] ID: `dwm-bg-gradient-end-position`
- [ ] `min="0"` `max="100"` present
- [ ] Display span `#dwm-bg-gradient-end-position-label` shows current value + `%` on load and updates live
- [ ] Sanitized server-side as integer 0–100
- [ ] Gradient preview updates live when position changes
- [ ] Visible only when toggle ON AND type = `gradient`
- [ ] **Dashboard:** Start position (`dashboard_bg_gradient_start_position`) and end position values reflected as stop percentages in the CSS gradient string (e.g. `linear-gradient(90deg, #667eea 0%, #764ba2 100%)`) on the dashboard wrapper

---

### Field: Custom Padding (enable toggle)

**DB Key:** `dashboard_padding_enabled` | **Type:** checkbox | **Default:** off

- [ ] Read: `! empty( $settings['dashboard_padding_enabled'] )`
- [ ] Default: off
- [ ] Escape: `checked()`
- [ ] Name: `settings[dashboard_padding_enabled]`
- [ ] ID: `dwm-dashboard-padding-enabled`
- [ ] Label: "Custom Padding"
- [ ] Value: `1`
- [ ] `data-toggle-controls="#dwm-dashboard-padding-controls"` present
- [ ] Container `#dwm-dashboard-padding-controls` has `dwm-hidden-by-toggle` on load when off
- [ ] Container visible on load when saved as on
- [ ] Sanitized server-side as boolean/int
- [ ] **Dashboard:** When ON — padding CSS applied to dashboard wrapper; default `20px` on all sides until customized
- [ ] **Dashboard:** When OFF — no padding CSS injected; dashboard wrapper uses stock WP padding

---

### Field: Padding Link State

**DB Key:** `dashboard_padding_linked` | **Type:** hidden `1`/`0` | **Default:** `0`

- [x] Read: `! empty( $settings['dashboard_padding_linked'] )`
- [ ] Default: `0` (unlinked)
- [x] Escape: ternary → `'1'` or `'0'`
- [x] Name: `settings[dashboard_padding_linked]` — hidden input `class="dwm-link-value" data-group="dashboard-padding"` *(Fixed: template updated to generic link pattern)*
- [x] ID removed — hidden input now uses class + data-group only *(Fixed: Round 2 — JS updated to match; no longer uses removed `#dwm-padding-linked` ID)*
- [x] Link button has `is-linked` class on load when value is `1` and uses `class="dwm-link-btn" data-group="dashboard-padding"` *(Fixed: class changed from `dwm-padding-link-btn`; bespoke `id="dwm-padding-link"` removed)*
- [x] Clicking link button toggles `is-linked` class and updates hidden field value *(Fixed: Round 2 — JS click handler now delegates to `.dwm-link-btn[data-group="dashboard-padding"]`)*
- [x] When linked, changing any side value syncs all four sides *(Fixed: Round 2 — `syncPaddingSide()` confirmed functional; `isPaddingLinked()` reads correct selector)*
- [ ] Sanitized server-side as `0`/`1`

---

### Field Group: Padding Sides (Top / Right / Bottom / Left)

**DB Keys:** `dashboard_padding_{side}_value` and `dashboard_padding_{side}_unit`
**PHP:** Dynamic loop over `['top','right','bottom','left']`
**Defaults:** value `20`, unit `px`

For each of the 4 sides (top, right, bottom, left):

- [ ] Value read: `isset($settings[$value_key]) ? (string)$settings[$value_key] : '20'`
- [ ] Unit read: `isset($settings[$unit_key]) ? (string)$settings[$unit_key] : 'px'`
- [ ] Default value: `20`
- [ ] Default unit: `px`
- [ ] Value escape: `esc_attr( $side_val )`
- [ ] Unit escape: `selected( $side_unit, $u )` on each option
- [ ] Number input name: `settings[dashboard_padding_{side}_value]`
- [ ] Select name: `settings[dashboard_padding_{side}_unit]`
- [ ] Number input ID: `dwm-padding-{side}-value`
- [ ] Select ID: `dwm-padding-{side}-unit`
- [ ] Range slider ID: `dwm-padding-{side}-slider`
- [ ] Label: capitalized side name with correct `for=` attribute
- [ ] Number input: `min="0"` `max="300"` present
- [ ] Range slider: `min="0"` `max="300"` present, `value=` matches number input
- [ ] Slider and number input stay in sync bidirectionally (JS)
- [ ] Unit options: `px`, `%`, `rem`, `em`, `vh`, `vw`
- [ ] Saved unit correctly pre-selected on load
- [x] `data-side="{side}"` on number, select, and range (for JS link group) *(confirmed present; select also has `dwm-linked-unit-select` + `data-group="dashboard-padding"`)*
- [ ] When link active, changing this side updates all other sides
- [ ] Value and unit sanitized server-side (int + allowlist string)
- [ ] Visible only when padding toggle ON
- [ ] **Dashboard:** Each side's `{value}{unit}` is reflected in the CSS `padding-{side}` (or shorthand) applied to the dashboard wrapper element
- [ ] **Dashboard:** Changing a side value, saving, and reloading dashboard shows the new padding dimension in the element's computed style
- [ ] **Dashboard:** Linked sides: when link active, all four sides use the same value and unit in the generated CSS

---

## Section 4 — Dashboard Branding

**Section ID:** `#dwm-section-dashboard-branding`
**Title:** "Dashboard Branding"
**Help icon:** `aria-label="Learn about dashboard branding controls"` → `data-docs-page="custom-dashboard-branding"` → opens `#dwm-docs-modal`
**Save button:** "Save Dashboard Branding"
**Visibility:** Always rendered
**PHP init block (lines 320–339):** Re-declares `$bg_type`, `$bg_gradient_type`, `$bg_angle`, `$bg_start_pos`, `$bg_end_pos` (redundant — these were already declared in Section 3's block at lines 181–186); also declares `$title_mode`, `$logo_height`, `$logo_alignment` (unique to this section). All three previously missing `in_array()` guards (`$bg_gradient_type`, `$title_mode`, `$logo_alignment`) were added in Round 2 and verified correct at template lines 330, 335, 338. *(Fixed: Round 2)*

### Section Checklist
- [ ] Help icon opens `#dwm-docs-modal` on `custom-dashboard-branding` page
- [ ] Save button label is "Save Dashboard Branding"
- [ ] Hero & Logo mode select controls visibility of all downstream rows
- [ ] Setting mode to `disabled` hides all hero/logo/style rows
- [ ] Setting mode to `logo_only` shows logo upload + style row, hides hero title/message/dimensions
- [ ] Setting mode to `hero_only` shows hero title/message/dimensions, hides logo upload
- [ ] Setting mode to `hero_logo` shows all rows
- [ ] Alignment label updates to "Text Alignment" when mode = `hero_only`
- [ ] Style section label updates to "Logo Style" when mode = `logo_only`
- [x] All rows load with correct visibility matching saved mode on page open *(Fixed: `syncLogoControlsVisibility()` now called at JS init)*
- [ ] No PHP warnings from undefined variables

#### Dashboard Output & Assets — Section 4
- [ ] PHP/JS reads all branding settings from DB and renders the branding elements on the dashboard page above the widget grid
- [ ] DWM branding CSS and JS assets enqueued on `wp-admin/index.php`; no 404s or console errors
- [ ] **Mode `disabled`:** No hero, logo, or branding container rendered in the dashboard DOM; dashboard appears as stock WP
- [ ] **Mode `logo_only`:** Logo `<img>` element rendered in branding container; no hero title or message elements present in DOM
- [ ] **Mode `hero_only`:** Hero container rendered with title and message; no logo `<img>` in DOM
- [ ] **Mode `hero_logo`:** Hero container rendered with title and message AND logo `<img>` both present in DOM
- [ ] Dashboard title override (hide/custom) applied via correct WP hook before page renders
- [ ] All styling (background, border, padding, margin, radius) applied as inline CSS or injected `<style>` block scoped to branding container
- [ ] Alignment applied to branding container via CSS `text-align` or flex `justify-content`
- [ ] Hero/logo container does not overflow or break the dashboard widget grid below it
- [ ] Branding output is scoped to the dashboard page only; other admin pages unaffected

---

### Field: Hero & Logo Mode

**DB Key:** `dashboard_hero_logo_mode` | **Type:** select | **Default:** `disabled`

- [ ] PHP var: `$hero_logo_mode = sanitize_key( (string)( $settings['dashboard_hero_logo_mode'] ?? 'disabled' ) )` validated against `['disabled','hero_logo','logo_only','hero_only']`
- [ ] Default: `disabled`
- [ ] Escape: `selected()` on each option
- [ ] Name: `settings[dashboard_hero_logo_mode]`
- [ ] ID: `dwm-dashboard-hero-logo-mode`
- [ ] Label: "Hero & Logo" with `for="dwm-dashboard-hero-logo-mode"`
- [ ] Options: `disabled` (Disabled), `hero_logo` (Hero + Logo), `logo_only` (Logo Only), `hero_only` (Hero Only)
- [ ] Saved value correctly pre-selects option on load
- [ ] Changing this field triggers JS to show/hide rows B through F
- [ ] `$hero_mode_has_hero` (rows C/D/height) computed as `in_array($hero_logo_mode, ['hero_logo','hero_only'])`
- [ ] `$logo_mode_has_logo` (row F) computed as `in_array($hero_logo_mode, ['hero_logo','logo_only'])`
- [x] Sanitized server-side with `sanitize_key()` + allowlist check *(Fixed: `dashboard_hero_logo_mode` sanitization block added to `class-dwm-sanitizer.php`)*
- [ ] Always visible (top-level control)
- [ ] **Dashboard `disabled`:** No branding container rendered; stock WP dashboard title and layout unchanged
- [ ] **Dashboard `hero_logo`:** Branding container rendered with both hero text area AND logo `<img>`; dimensions, alignment, background, border all applied
- [ ] **Dashboard `logo_only`:** Branding container rendered with logo `<img>` only; no hero title or message elements in DOM
- [ ] **Dashboard `hero_only`:** Branding container rendered with title and message; no logo `<img>` in DOM; text alignment applied to hero text area
- [ ] **Dashboard:** Switching mode, saving, and reloading dashboard confirms correct elements present/absent in DOM

---

### Field: Dashboard Title Mode

**DB Key:** `dashboard_title_mode` | **Type:** select | **Default:** `default`

- [x] PHP var: `$title_mode = (string)( $settings['dashboard_title_mode'] ?? 'default' )` validated against `['default','hide','custom']` *(Fixed: Round 2 — `in_array()` guard added at template line 335)*
- [ ] Default: `default`
- [ ] Escape: `selected()` on each option
- [ ] Name: `settings[dashboard_title_mode]`
- [ ] ID: `dwm-dashboard-title-mode`
- [ ] Label: "Dashboard Title" with `for="dwm-dashboard-title-mode"`
- [ ] Options: `default` (Default), `hide` (Hide Title), `custom` (Custom Title)
- [ ] Saved value correctly pre-selects option on load
- [ ] Selecting `custom` shows `#dwm-dashboard-title-custom-controls`
- [ ] Selecting `default` or `hide` hides `#dwm-dashboard-title-custom-controls`
- [ ] `custom-controls` container has `dwm-hidden-by-toggle` on load when mode ≠ `custom`
- [ ] Sanitized server-side as allowlist string
- [ ] Always visible (independent of hero mode)
- [ ] **Dashboard `default`:** Stock WP dashboard page title ("Dashboard") renders as normal in `<h1>` — no PHP override applied
- [ ] **Dashboard `hide`:** Dashboard page `<h1>` title element is hidden or absent from DOM (via PHP hook or CSS); no visible title text
- [ ] **Dashboard `custom`:** Dashboard page `<h1>` content replaced with saved custom text; inline styles for font family, size, weight, alignment, and color applied

---

### Field: Dashboard Title Text

**DB Key:** `dashboard_title_text` | **Type:** text | **Default:** `''`

- [ ] Read: `(string)( $settings['dashboard_title_text'] ?? '' )`
- [ ] Default: `''` (empty)
- [ ] Escape: `esc_attr()`
- [ ] Name: `settings[dashboard_title_text]`
- [ ] ID: `dwm-dashboard-title-text`
- [ ] Label: "Dashboard Title Text" with `for="dwm-dashboard-title-text"`
- [ ] Format button present next to label with `data-field="dashboard_title"` and `data-open-modal="dwm-title-format-modal"`
- [ ] Format button has `aria-label` or `title` attribute
- [ ] Text value loads from saved setting on page open
- [ ] Sanitized server-side (`sanitize_text_field` or equivalent)
- [ ] Visible only when `dashboard_title_mode = custom`
- [ ] **Dashboard:** When mode = `custom`, saved text appears in the dashboard `<h1>` element replacing "Dashboard"
- [ ] **Dashboard:** When mode = `default` or `hide`, custom text is not rendered regardless of what is saved

---

### Field Group: Dashboard Title Format (hidden fields)

**Populated by:** Format Text modal (`#dwm-title-format-modal`) with `data-field="dashboard_title"`
All written to hidden inputs; read back by JS when modal opens.

| Label | DB Key | Default |
|---|---|---|
| Font Family | `dashboard_title_font_family` | `inherit` |
| Font Size | `dashboard_title_font_size` | `32px` |
| Font Weight | `dashboard_title_font_weight` | `700` |
| Alignment | `dashboard_title_alignment` | `left` |
| Color | `dashboard_title_color` | `#1d2327` |

For each hidden field:
- [ ] Read: `(string)( $settings['key'] ?? 'default' )`
- [ ] Default applied with `??` fallback
- [ ] Escape: `esc_attr()`
- [ ] Name: `settings[key]`
- [ ] ID: `{key}` (matches what JS targets)
- [ ] Modal reads this field's current value when opening to pre-populate modal controls
- [ ] Modal writes updated value back to this field when applied
- [ ] Value submitted with form and persisted to DB
- [ ] Visible only when `dashboard_title_mode = custom` (inside custom controls wrapper)
- [ ] Sanitized server-side for data type (string, px value, hex/rgba, etc.)
- [ ] **Dashboard:** All five format properties (font-family, font-size, font-weight, text-align, color) applied as inline CSS or injected styles to the custom `<h1>` on the dashboard page
- [ ] **Dashboard:** Changing any format value in the modal, applying, saving, and reloading dashboard shows the updated style on the `<h1>`

---

### Field: Logo / Text Alignment

**DB Key:** `dashboard_logo_alignment` | **Type:** hidden (driven by button group) | **Default:** `left`

- [x] PHP var: `$logo_alignment = (string)( $settings['dashboard_logo_alignment'] ?? 'left' )` validated against `['left','center','right']` *(Fixed: Round 2 — `in_array()` guard added at template line 338)*
- [ ] Default: `left`
- [ ] Escape: `esc_attr( $logo_alignment )`
- [ ] Name: `settings[dashboard_logo_alignment]`
- [ ] ID: `dwm-dashboard-logo-alignment`
- [ ] Label element has `id="dwm-alignment-row-label"` (used by JS to update text based on mode)
- [ ] Label text: `"Logo Alignment"` by default; updated by JS to `"Text Alignment"` when mode = `hero_only`
- [ ] Button group `.dwm-logo-align-buttons` has `role="group"` and `aria-label="Alignment"`
- [ ] Left button: class `dwm-logo-align-btn`, `data-align="left"`, `aria-label="Align Left"`, `is-active` class on load when saved = `left`
- [ ] Center button: class `dwm-logo-align-btn`, `data-align="center"`, `aria-label="Align Center"`, `is-active` class on load when saved = `center`
- [ ] Right button: class `dwm-logo-align-btn`, `data-align="right"`, `aria-label="Align Right"`, `is-active` class on load when saved = `right`
- [ ] Clicking alignment button updates `is-active` class and writes value to hidden `#dwm-dashboard-logo-alignment`
- [ ] `#dwm-hero-theme-row` has `dwm-hidden-by-toggle` on load when mode = `disabled`
- [ ] Sanitized server-side as allowlist string `['left','center','right']`
- [ ] Visible only when `hero_logo_mode ≠ disabled`
- [ ] **Dashboard:** Saved alignment applied to branding container as CSS (`text-align`, `justify-content`, or equivalent) — verify by reloading dashboard and inspecting the hero/logo wrapper element
- [ ] **Dashboard:** `left` → content aligned to the left edge of the branding container
- [ ] **Dashboard:** `center` → content centered within the branding container
- [ ] **Dashboard:** `right` → content aligned to the right edge of the branding container

---

### Field: Hero Height

**DB Key:** `dashboard_hero_height` | **Type:** number | **Default:** `1`

- [ ] PHP var: `$hero_height = max( 1, (int)( $settings['dashboard_hero_height'] ?? 1 ) )`
- [ ] Default: `1` (clamped to minimum 1)
- [ ] Escape: `esc_attr( (string) $hero_height )`
- [ ] Name: `settings[dashboard_hero_height]`
- [ ] ID: `dwm-hero-height`
- [ ] Label: "Height" with `for="dwm-hero-height"`
- [ ] `min="1"` `max="1000"` present
- [ ] Sanitized server-side as int clamped 1–1000
- [ ] `#dwm-hero-dimensions-group` has `dwm-hidden-by-toggle` when `$hero_mode_has_hero` is false
- [ ] Visible only when mode = `hero_logo` or `hero_only`
- [ ] **Dashboard:** Hero container element has CSS `height: {value}{unit}` matching saved settings
- [ ] **Dashboard:** Changing height, saving, reloading dashboard → hero container visibly taller/shorter

---

### Field: Hero Height Unit

**DB Key:** `dashboard_hero_height_unit` | **Type:** select | **Default:** `px`

- [ ] PHP var: `$hero_height_unit = (string)( $settings['dashboard_hero_height_unit'] ?? 'px' )`
- [ ] Default: `px`
- [ ] Escape: `selected( $hero_height_unit, $u )` per option
- [ ] Name: `settings[dashboard_hero_height_unit]`
- [ ] ID: `dwm-hero-height-unit`
- [ ] Options: `px`, `%`, `rem`, `em`, `vh`
- [ ] Saved unit correctly pre-selected on load
- [ ] Sanitized server-side as allowlist string
- [ ] Visible only when mode = `hero_logo` or `hero_only`
- [ ] **Dashboard:** Hero container CSS `height` uses the correct unit (e.g. `200px`, `50vh`, `10rem`) after save

---

### Field: Hero Min Height

**DB Key:** `dashboard_hero_min_height` | **Type:** number | **Default:** `1`

- [ ] PHP var: `$hero_min_height = max( 1, (int)( $settings['dashboard_hero_min_height'] ?? 1 ) )`
- [ ] Default: `1` (clamped to minimum 1)
- [ ] Escape: `esc_attr( (string) $hero_min_height )`
- [ ] Name: `settings[dashboard_hero_min_height]`
- [ ] ID: `dwm-hero-min-height`
- [ ] Label: "Min Height" with `for="dwm-hero-min-height"`
- [ ] `min="1"` `max="1000"` present
- [ ] Sanitized server-side as int clamped 1–1000
- [ ] Visible only when mode = `hero_logo` or `hero_only`
- [ ] **Dashboard:** Hero container has CSS `min-height: {value}{unit}` applied; container does not collapse below that threshold even with minimal content

---

### Field: Hero Min Height Unit

**DB Key:** `dashboard_hero_min_height_unit` | **Type:** select | **Default:** `px`

- [ ] PHP var: `$hero_min_height_unit = (string)( $settings['dashboard_hero_min_height_unit'] ?? 'px' )`
- [ ] Default: `px`
- [ ] Escape: `selected( $hero_min_height_unit, $u )` per option
- [ ] Name: `settings[dashboard_hero_min_height_unit]`
- [ ] ID: `dwm-hero-min-height-unit`
- [ ] Options: `px`, `%`, `rem`, `em`, `vh`
- [ ] Saved unit correctly pre-selected on load
- [ ] Sanitized server-side as allowlist string
- [ ] Visible only when mode = `hero_logo` or `hero_only`
- [ ] **Dashboard:** Hero container CSS `min-height` uses the correct unit after save

---

### Field: Hero Title Text

**DB Key:** `dashboard_hero_title` | **Type:** text | **Default:** `''`

- [ ] Read: `(string)( $settings['dashboard_hero_title'] ?? '' )`
- [ ] Default: `''` (empty)
- [ ] Escape: `esc_attr()`
- [ ] Name: `settings[dashboard_hero_title]`
- [ ] ID: `dwm-dashboard-hero-title`
- [ ] Label: "Hero Title" with `for="dwm-dashboard-hero-title"`
- [ ] Format button present with `data-field="dashboard_hero_title"` and `data-open-modal="dwm-title-format-modal"`
- [ ] Format button has `title` attribute
- [ ] `#dwm-hero-title-row` has `dwm-hidden-by-toggle` on load when `$hero_mode_has_hero` is false
- [ ] Saved text loads in input on page open
- [ ] Sanitized server-side (`sanitize_text_field`)
- [ ] Visible only when mode = `hero_logo` or `hero_only`
- [ ] **Dashboard:** Saved title text rendered as a heading element inside the hero container on the dashboard page
- [ ] **Dashboard:** When text is empty, hero title element is absent or empty (no orphan heading tags)
- [ ] **Dashboard:** Only present when mode = `hero_logo` or `hero_only`; absent when `logo_only` or `disabled`

---

### Field Group: Hero Title Format (hidden fields)

**Populated by:** Format Text modal with `data-field="dashboard_hero_title"`

| Label | DB Key | Default |
|---|---|---|
| Font Family | `dashboard_hero_title_font_family` | `inherit` |
| Font Size | `dashboard_hero_title_font_size` | `28px` |
| Font Weight | `dashboard_hero_title_font_weight` | `700` |
| Alignment | `dashboard_hero_title_alignment` | `left` |
| Color | `dashboard_hero_title_color` | `#ffffff` (white — differs from dashboard title's `#1d2327`) |

For each hidden field:
- [ ] Read: `(string)( $settings['key'] ?? 'default' )`
- [ ] Default applied with `??` fallback
- [ ] Escape: `esc_attr()`
- [ ] Name: `settings[key]`
- [ ] ID: `{key}`
- [ ] Modal reads this value when opening to pre-populate modal controls
- [ ] Modal writes updated value back when applied
- [ ] Value submitted with form and persisted to DB
- [ ] Visible/hidden matches parent `#dwm-hero-title-row` state
- [ ] Sanitized server-side for data type
- [ ] **Dashboard:** All five format properties (font-family, font-size, font-weight, text-align, color) applied as inline CSS to the hero title element on the dashboard
- [ ] **Dashboard:** Default color `#ffffff` (white) renders correctly against typical hero background colors

---

### Field: Hero Message

**DB Key:** `dashboard_hero_message` | **Type:** `wp_editor` | **Default:** `''`

- [ ] Read: `(string)( $settings['dashboard_hero_message'] ?? '' )`
- [ ] Default: `''` (empty)
- [ ] Escape: passed directly to `wp_editor()` first argument
- [ ] Textarea name: `settings[dashboard_hero_message]`
- [ ] Editor ID: `dwm-dashboard-hero-message` (no hyphens restriction — WP editor allows this)
- [ ] Label: "Hero Message" renders before `wp_editor()` with `for="dwm-dashboard-hero-message"`
- [ ] `wp_editor` config: `teeny=true`, `media_buttons=false`, `textarea_rows=5`, `quicktags=true`
- [ ] `#dwm-hero-message-row` has `dwm-hidden-by-toggle` when `$hero_mode_has_hero` is false
- [ ] Saved content loads in editor on page open
- [ ] Sanitized server-side (`wp_kses_post` or equivalent)
- [ ] Visible only when mode = `hero_logo` or `hero_only`
- [ ] **Dashboard:** Saved HTML content rendered inside the hero body area below the hero title
- [ ] **Dashboard:** Allowed HTML tags (links, bold, italic, etc.) render correctly; disallowed tags stripped
- [ ] **Dashboard:** Empty message produces no orphan container element in DOM
- [ ] **Dashboard:** Only present when mode = `hero_logo` or `hero_only`

---

### Field: Hero/Logo Background Type

**DB Key:** `dashboard_logo_bg_type` | **Type:** select | **Default:** `default`

- [ ] PHP var: `$logo_bg_type = sanitize_key( (string)( $settings['dashboard_logo_bg_type'] ?? 'default' ) )` validated against `['default','solid','gradient']`
- [ ] Default: `default`
- [ ] Escape: `selected( $logo_bg_type, $value )` per option
- [ ] Name: `settings[dashboard_logo_bg_type]`
- [ ] ID: `dwm-logo-background-type`
- [ ] Label: "Type" with `for="dwm-logo-background-type"`
- [ ] Options: `default` (Default), `solid` (Solid Color), `gradient` (Gradient)
- [ ] Saved value correctly pre-selects on load
- [ ] Selecting `solid` shows `#dwm-logo-bg-solid-controls`, hides gradient controls
- [ ] Selecting `gradient` shows gradient controls, hides solid controls
- [ ] Selecting `default` hides both solid and gradient controls
- [ ] Sanitized server-side with `sanitize_key()` + allowlist
- [ ] `#dwm-hero-logo-style-row` has `dwm-hidden-by-toggle` when mode = `disabled`
- [ ] Visible only when `hero_logo_mode ≠ disabled`
- [ ] **Dashboard `default`:** Hero/logo container has no custom background — inherits page/theme background
- [ ] **Dashboard `solid`:** Hero/logo container has CSS `background: {color}` applied
- [ ] **Dashboard `gradient`:** Hero/logo container has CSS `linear-gradient()` or `radial-gradient()` background applied

---

### Field: Hero/Logo Background Color (solid)

**DB Key:** `dashboard_logo_bg_solid_color` | **Type:** color picker | **Default:** `#ffffff`

- [ ] Read: `$settings['dashboard_logo_bg_solid_color'] ?? '#ffffff'`
- [ ] Default: `#ffffff`
- [ ] Escape: `esc_attr()`
- [ ] Name: `settings[dashboard_logo_bg_solid_color]`
- [ ] ID: `dwm-logo-bg-solid-color`
- [ ] Label: "Color" with `for="dwm-logo-bg-solid-color"`
- [ ] Container `#dwm-logo-bg-solid-controls` inline `style="display:none"` when type ≠ `solid`
- [ ] Sanitized server-side as hex color string
- [ ] Visible only when `hero_logo_mode ≠ disabled` AND `logo_bg_type = solid`
- [ ] **Dashboard:** Hero/logo container CSS `background` property set to the saved hex color

---

### Field: Hero/Logo Background Gradient Type

**DB Key:** `dashboard_logo_bg_gradient_type` | **Type:** select | **Default:** `linear`

- [ ] PHP var: `$logo_bg_gradient_type = sanitize_key( (string)( $settings['dashboard_logo_bg_gradient_type'] ?? 'linear' ) )` validated against `['linear','radial']`
- [ ] Default: `linear`
- [ ] Escape: `selected()` per option
- [ ] Name: `settings[dashboard_logo_bg_gradient_type]`
- [ ] ID: `dwm-logo-bg-gradient-type`
- [ ] Label: "Gradient Type" with `for="dwm-logo-bg-gradient-type"`
- [ ] Options: `linear`, `radial`
- [ ] Container `#dwm-logo-bg-gradient-type-controls` inline `style="display:none"` when `logo_bg_type ≠ gradient`
- [ ] Selecting `radial` hides `#dwm-logo-bg-gradient-angle-wrap`
- [ ] Sanitized server-side as allowlist string
- [ ] Visible only when `hero_logo_mode ≠ disabled` AND `logo_bg_type = gradient`
- [ ] **Dashboard:** `linear` → CSS uses `linear-gradient({angle}deg, ...)` on hero/logo container
- [ ] **Dashboard:** `radial` → CSS uses `radial-gradient(...)` without angle on hero/logo container

---

### Field: Hero/Logo Background Gradient Angle

**DB Key:** `dashboard_logo_bg_gradient_angle` | **Type:** range | **Default:** `90`

- [ ] PHP var: `$logo_bg_angle = max( 0, min( 360, (int)( $settings['dashboard_logo_bg_gradient_angle'] ?? 90 ) ) )`
- [ ] Default: `90` (clamped 0–360)
- [ ] Escape: `esc_attr( (string) $logo_bg_angle )`
- [ ] Name: `settings[dashboard_logo_bg_gradient_angle]`
- [ ] ID: `dwm-logo-bg-gradient-angle`
- [ ] Label: "Angle" with `for="dwm-logo-bg-gradient-angle"`
- [ ] Class: `dwm-format-slider` present on range input
- [ ] `min="0"` `max="360"` present
- [ ] Display span `#dwm-logo-bg-gradient-angle-value` shows value + `°` on load and updates live
- [ ] Container `#dwm-logo-bg-gradient-angle-wrap` inline `style="display:none"` when gradient type ≠ `linear`
- [ ] Sanitized server-side as int clamped 0–360
- [ ] Gradient preview `#dwm-logo-bg-gradient-preview` updates live
- [ ] Visible only when `logo_bg_type = gradient` AND gradient type = `linear`
- [ ] **Dashboard:** Angle value reflected in the CSS `linear-gradient({angle}deg, ...)` on the hero/logo container

---

### Field: Hero/Logo Background Gradient Start Color

**DB Key:** `dashboard_logo_bg_gradient_start` | **Type:** color picker | **Default:** `#667eea`

- [ ] Read: `$settings['dashboard_logo_bg_gradient_start'] ?? '#667eea'`
- [ ] Default: `#667eea`
- [ ] Escape: `esc_attr()`
- [ ] Name: `settings[dashboard_logo_bg_gradient_start]`
- [ ] ID: `dwm-logo-bg-gradient-start`
- [ ] Container `#dwm-logo-bg-gradient-details` inline `style="display:none"` when `logo_bg_type ≠ gradient`
- [ ] Gradient preview updates live
- [ ] Sanitized server-side as hex color string
- [ ] Visible only when `logo_bg_type = gradient`

---

### Field: Hero/Logo Background Gradient Start Position

**DB Key:** `dashboard_logo_bg_gradient_start_position` | **Type:** range | **Default:** `0`

- [ ] PHP var: `$logo_bg_start_pos = max( 0, min( 100, (int)( $settings['dashboard_logo_bg_gradient_start_position'] ?? 0 ) ) )`
- [ ] Default: `0` (clamped 0–100)
- [ ] Escape: `esc_attr( (string) $logo_bg_start_pos )`
- [ ] Name: `settings[dashboard_logo_bg_gradient_start_position]`
- [ ] ID: `dwm-logo-bg-gradient-start-position`
- [ ] `min="0"` `max="100"` present
- [ ] Display span `#dwm-logo-bg-gradient-start-position-label` shows value + `%` on load and updates live
- [ ] Sanitized server-side as int clamped 0–100
- [ ] Visible only when `logo_bg_type = gradient`
- [ ] **Dashboard:** Start and end position percentages reflected in CSS gradient stops on the hero/logo container (e.g. `linear-gradient(90deg, #667eea 0%, #764ba2 100%)`)

---

### Field: Hero/Logo Background Gradient End Color

**DB Key:** `dashboard_logo_bg_gradient_end` | **Type:** color picker | **Default:** `#764ba2`

- [ ] Read: `$settings['dashboard_logo_bg_gradient_end'] ?? '#764ba2'`
- [ ] Default: `#764ba2`
- [ ] Escape: `esc_attr()`
- [ ] Name: `settings[dashboard_logo_bg_gradient_end]`
- [ ] ID: `dwm-logo-bg-gradient-end`
- [ ] Gradient preview updates live
- [ ] Sanitized server-side as hex color string
- [ ] Visible only when `logo_bg_type = gradient`

---

### Field: Hero/Logo Background Gradient End Position

**DB Key:** `dashboard_logo_bg_gradient_end_position` | **Type:** range | **Default:** `100`

- [ ] PHP var: `$logo_bg_end_pos = max( 0, min( 100, (int)( $settings['dashboard_logo_bg_gradient_end_position'] ?? 100 ) ) )`
- [ ] Default: `100` (clamped 0–100)
- [ ] Escape: `esc_attr( (string) $logo_bg_end_pos )`
- [ ] Name: `settings[dashboard_logo_bg_gradient_end_position]`
- [ ] ID: `dwm-logo-bg-gradient-end-position`
- [ ] `min="0"` `max="100"` present
- [ ] Display span `#dwm-logo-bg-gradient-end-position-label` shows value + `%` on load and updates live
- [ ] Sanitized server-side as int clamped 0–100
- [ ] Visible only when `logo_bg_type = gradient`

---

### Field Group: Hero/Logo Margin (linked inputs)

**DB Keys:** `dashboard_logo_margin_{top|right|bottom|left}` and `dashboard_logo_margin_linked` and `dashboard_logo_margin_unit`
**Group attribute:** `data-group="logo-margin"` | **Default values:** all sides `0`, unit `px`, linked `0`

- [ ] PHP var: `$logo_margin_unit = (string)( $settings['dashboard_logo_margin_unit'] ?? 'px' )`
- [ ] PHP var: `$logo_margin_linked = ! empty( $settings['dashboard_logo_margin_linked'] )`
- [ ] Outer wrapper `.dwm-linked-inputs` has `data-group="logo-margin"`
- [ ] Link hidden field: name `settings[dashboard_logo_margin_linked]`, class `dwm-link-value`, `data-group="logo-margin"`, value `'1'`/`'0'` from `$logo_margin_linked`
- [ ] Link button: `data-group="logo-margin"`, `is-linked` class on load when `$logo_margin_linked` is true, `aria-label="Link margin values"`
- [ ] Link button: clicking updates hidden field and toggles `is-linked`
- [ ] When linked, changing any side syncs all four
- [ ] Each side: name `settings[dashboard_logo_margin_{side}]`, ID `dwm-logo-margin-{side}`, label with `for=`, `min="-200"` `max="200"`, default `0`
- [ ] Negative values allowed (margin can pull element outward)
- [ ] Unit select: name `settings[dashboard_logo_margin_unit]`, ID `dwm-logo-margin-unit`, class `dwm-linked-unit-select`, `selected()` per option, options `px`, `%`, `rem`, `em`, default `px`
- [ ] All margin inputs escape: `esc_attr( (string)( $settings[key] ?? 0 ) )`
- [ ] All sanitized server-side (int for values, allowlist for unit)
- [ ] Visible only when `hero_logo_mode ≠ disabled`
- [ ] **Dashboard:** CSS `margin` applied to hero/logo container using saved values and unit (e.g. `margin: 0px 10px 20px 0px`)
- [ ] **Dashboard:** Negative margin values pull the container outward (overlap adjacent elements); verify this works as expected

---

### Field: Hero/Logo Border Style

**DB Key:** `dashboard_logo_border_style` | **Type:** select | **Default:** `none`

- [ ] PHP var: `$logo_border_style = (string)( $settings['dashboard_logo_border_style'] ?? 'none' )`
- [ ] Default: `none`
- [ ] Escape: `selected()` per option
- [ ] Name: `settings[dashboard_logo_border_style]`
- [ ] ID: `dwm-logo-border-style`
- [ ] Label: "Style" with `for="dwm-logo-border-style"`
- [ ] Options: `none` (None), `solid` (Solid), `dashed` (Dashed), `dotted` (Dotted), `double` (Double)
- [ ] Saved value correctly pre-selects on load
- [ ] Selecting `none` hides border color, border width inputs, radius block, and link button (`dwm-hidden-by-toggle`)
- [ ] Selecting any non-`none` value shows all border sub-fields
- [ ] Sanitized server-side as allowlist string
- [ ] Visible only when `hero_logo_mode ≠ disabled`
- [ ] **Dashboard `none`:** No border visible on hero/logo container; no CSS `border` property injected
- [ ] **Dashboard non-`none`:** Hero/logo container renders with the selected `border-style` (solid line, dashed, dotted, double lines) visible in browser

---

### Field: Hero/Logo Border Color

**DB Key:** `dashboard_logo_border_color` | **Type:** color picker | **Default:** `#dddddd`

- [ ] PHP var: `$logo_border_color = (string)( $settings['dashboard_logo_border_color'] ?? '#dddddd' )`
- [ ] Default: `#dddddd`
- [ ] Escape: `esc_attr( $logo_border_color )`
- [ ] Name: `settings[dashboard_logo_border_color]`
- [ ] ID: `dwm-logo-border-color`
- [ ] Label: "Color" with `for="dwm-logo-border-color"`
- [ ] Container `#dwm-logo-border-color-wrap` has `dwm-hidden-by-toggle` on load when `border_style = none`
- [ ] Sanitized server-side as hex color string
- [ ] Visible only when `hero_logo_mode ≠ disabled` AND `border_style ≠ none`
- [ ] **Dashboard:** CSS `border-color` on the hero/logo container set to saved hex value

---

### Field Group: Hero/Logo Border Widths (linked inputs)

**DB Keys:** `dashboard_logo_border_{top|right|bottom|left}` and `dashboard_logo_border_linked` and `dashboard_logo_border_unit`
**Group attribute:** `data-group="logo-border"` | **Default values:** all sides `0`, unit `px`, linked `0`
**Entire block** (`dwm-linked-inputs`) has `dwm-hidden-by-toggle` when `border_style = none`

- [ ] PHP var: `$logo_border_unit = (string)( $settings['dashboard_logo_border_unit'] ?? 'px' )`
- [ ] PHP var: `$logo_border_linked = ! empty( $settings['dashboard_logo_border_linked'] )`
- [ ] Outer wrapper `.dwm-linked-inputs` has `data-group="logo-border"` AND `dwm-hidden-by-toggle` when border_style = none
- [ ] Link button `#dwm-logo-border-link-btn`: class `dwm-link-btn`, `data-group="logo-border"`, `aria-label="Link border values"`, `is-linked` class on load when `$logo_border_linked`; also has `dwm-hidden-by-toggle` when border_style = none
- [ ] Link hidden field: name `settings[dashboard_logo_border_linked]`, class `dwm-link-value`, `data-group="logo-border"`, value `'1'`/`'0'`
- [ ] Each side (top/right/bottom/left): name `settings[dashboard_logo_border_{side}]`, ID `dwm-logo-border-{side}`, label with `for=`, `min="0"` `max="20"`, default `0`
- [ ] Unit select: name `settings[dashboard_logo_border_unit]`, ID `dwm-logo-border-unit`, class `dwm-linked-unit-select`, `selected()` per option, options `px`, `rem`, `em`, default `px`
- [ ] All escape: `esc_attr( (string)( $settings[key] ?? 0 ) )`
- [ ] Link button shows/hides correctly alongside border style change
- [ ] All sanitized server-side (int clamped 0–20 for widths, allowlist for unit)
- [ ] Visible only when `hero_logo_mode ≠ disabled` AND `border_style ≠ none`
- [ ] **Dashboard:** CSS `border-width` (per-side) applied to hero/logo container; inspect computed style to confirm each side matches saved values

---

### Field Group: Hero/Logo Border Radius (linked inputs)

**DB Keys:** `dashboard_logo_border_radius_{tl|tr|br|bl}` and `dashboard_logo_border_radius_linked` and `dashboard_logo_border_radius_unit`
**Container:** `#dwm-logo-radius-block` | **Group:** `data-group="logo-radius"` | **Default:** all `0`, unit `px`, linked `0`
**Visibility:** `#dwm-logo-radius-block` has `dwm-hidden-by-toggle` when `border_style = none`

- [ ] PHP vars: `$logo_radius_tl/tr/br/bl = (int)( $settings[key] ?? 0 )` for each corner
- [ ] PHP var: `$logo_radius_unit = (string)( $settings['dashboard_logo_border_radius_unit'] ?? 'px' )`
- [ ] PHP var: `$logo_radius_linked = ! empty( $settings['dashboard_logo_border_radius_linked'] )`
- [ ] Outer wrapper `.dwm-linked-inputs` has `data-group="logo-radius"`
- [ ] Link button: class `dwm-link-btn`, `data-group="logo-radius"`, `aria-label="Link radius values"`, `is-linked` class on load when `$logo_radius_linked`
- [ ] Link hidden field: name `settings[dashboard_logo_border_radius_linked]`, class `dwm-link-value`, `data-group="logo-radius"`, value `'1'`/`'0'`
- [ ] Each corner (TL/TR/BR/BL): name `settings[dashboard_logo_border_radius_{corner}]`, ID `dwm-logo-radius-{corner}`, label (TL/TR/BR/BL) with `for=`, `min="0"` `max="200"`, default `0`
- [ ] Unit select: name `settings[dashboard_logo_border_radius_unit]`, ID `dwm-logo-radius-unit`, class `dwm-linked-unit-select`, `selected()` per option, options `px`, `%`, `rem`, `em`, default `px`
- [ ] All escape: `esc_attr( (string) $logo_radius_{corner} )`
- [ ] Link active syncs all four corners
- [ ] All sanitized server-side (int clamped 0–200 for corners, allowlist for unit)
- [ ] Visible only when `hero_logo_mode ≠ disabled` AND `border_style ≠ none`
- [ ] **Dashboard:** CSS `border-radius` applied to hero/logo container corners as `{tl}{unit} {tr}{unit} {br}{unit} {bl}{unit}`; rounded corners visible in browser

---

### Field: Logo Enabled (state)

**DB Key:** `dashboard_logo_enabled` | **Type:** hidden `1`/`0` | **Default:** driven by mode

- [ ] Value: `$logo_mode_has_logo ? '1' : '0'`
- [ ] PHP var: `$logo_mode_has_logo = in_array( $hero_logo_mode, ['hero_logo','logo_only'] )`
- [ ] ID: `dwm-dashboard-logo-enabled`
- [ ] Name: `settings[dashboard_logo_enabled]`
- [ ] This is a derived field — not user-editable; reflects mode state
- [ ] Updated by JS when hero_logo_mode changes
- [ ] `#dwm-dashboard-logo-controls` has `dwm-hidden-by-toggle` on load when `$logo_mode_has_logo` false
- [ ] Inside `#dwm-dashboard-logo-controls`: flex wrapper `div.dwm-customize-block-row--logo-config` contains 3 col divs (controls, style, preview)

---

### Field: Logo URL

**DB Key:** `dashboard_logo_url` | **Type:** hidden | **Default:** `''`

- [ ] Read: `(string)( $settings['dashboard_logo_url'] ?? '' )`
- [ ] Default: `''` (empty = no logo)
- [ ] Escape: `esc_attr()`
- [ ] Name: `settings[dashboard_logo_url]`
- [ ] ID: `dwm-dashboard-logo-url`
- [ ] Choose Logo button: class `dwm-button dwm-button-primary dwm-dashboard-media-pick dwm-logo-choose-button`, `data-target-input="#dwm-dashboard-logo-url"`, has `dwm-hidden-by-toggle` on load when URL non-empty
- [ ] Written by WP media picker when "Choose Logo" is clicked (`dwm-dashboard-media-pick` triggers media picker)
- [ ] Written by "Choose Different Logo" action in edit modal
- [ ] Cleared to `''` by "Remove Logo" action in edit modal
- [ ] Logo controls section wrapper: `#dwm-dashboard-logo-controls` has `dwm-hidden-by-toggle` when `$logo_mode_has_logo` false; inner columns use `dwm-customize-block-row--logo-config` flex layout
- [ ] Controls col: class `dwm-logo-config-col dwm-logo-config-col--controls`
- [ ] Size controls `#dwm-dashboard-logo-size-controls`: class `dwm-logo-size-controls`, `dwm-hidden-by-toggle` on load when URL empty
- [ ] Style col `#dwm-dashboard-logo-style-col`: class `dwm-logo-config-col dwm-logo-config-col--style`, `dwm-hidden-by-toggle` on load when URL empty
- [ ] Preview col `#dwm-dashboard-logo-preview-col`: class `dwm-logo-config-col dwm-logo-config-col--preview`, `dwm-hidden-by-toggle` on load when URL empty
- [ ] Preview wrap `.dwm-dashboard-logo-preview-wrap` has `has-logo` class on load when URL non-empty
- [ ] Preview `<img>` `#dwm-dashboard-logo-preview`: `src=` set to URL (escaped with `esc_url()`), has `is-empty` class when URL empty, class absent when URL present
- [ ] Preview alt: `alt="Logo preview"` (i18n)
- [ ] "Edit Logo" overlay button: class `dwm-logo-replace-overlay`, `data-open-modal="#dwm-dashboard-logo-edit-modal"` (note `#` prefix — unique to this trigger)
- [ ] Preview `<img>` `src=` updated live when URL changes
- [ ] Sanitized server-side as `esc_url_raw()` or equivalent
- [ ] Removing URL resets `dashboard_logo_enabled` to `0`
- [ ] **Dashboard:** When URL saved and mode includes logo — `<img>` element rendered in the branding container with `src=` set to the saved URL
- [ ] **Dashboard:** When URL is empty — no `<img>` rendered; logo-specific controls (size, style, link) absent from dashboard output
- [ ] **Dashboard:** Logo image renders at the configured height and alignment within the branding container

---

### Field: Logo Height (number + slider)

**DB Key:** `dashboard_logo_height` | **Type:** number (+ paired range slider) | **Default:** `56`

- [ ] PHP var: `$logo_height = (int)( $settings['dashboard_logo_height'] ?? 56 )`
- [ ] Default: `56`
- [ ] Escape: `esc_attr( (string) $logo_height )`
- [ ] Name: `settings[dashboard_logo_height]`
- [ ] ID: `dwm-dashboard-logo-height` (number), `dwm-dashboard-logo-height-slider` (range)
- [ ] Label: "Logo Height" with `for="dwm-dashboard-logo-height"`
- [ ] Number: `min="1"` `max="500"`
- [ ] Slider: `min="1"` `max="320"` (slider max differs from number max), class `dwm-format-slider`
- [ ] Slider `value=` populated from `$logo_height` on load
- [ ] Slider and number input stay bidirectionally in sync (JS)
- [ ] Sanitized server-side as int clamped 1–500
- [ ] Visible only when `$logo_mode_has_logo` AND `dashboard_logo_url` non-empty
- [x] **Dashboard:** Logo `<img>` element has CSS `height: {value}{unit}` applied; image renders at that height on the dashboard page *(Fixed: fallback default in admin class changed from `100` to `56`)*

---

### Field: Logo Height Unit

**DB Key:** `dashboard_logo_height_unit` | **Type:** select | **Default:** `px`

- [ ] PHP var: `$logo_height_unit = (string)( $settings['dashboard_logo_height_unit'] ?? 'px' )`
- [ ] Default: `px`
- [ ] Escape: `selected()` per option
- [ ] Name: `settings[dashboard_logo_height_unit]`
- [ ] ID: `dwm-dashboard-logo-height-unit`
- [ ] Options: `px`, `%`, `rem`, `em`, `vh`
- [ ] Saved unit correctly pre-selected on load
- [ ] Sanitized server-side as allowlist string
- [ ] Visible only when `$logo_mode_has_logo` AND `dashboard_logo_url` non-empty
- [ ] **Dashboard:** Logo `<img>` CSS `height` uses the correct unit after save (e.g. `56px`, `10vh`)

---

### Field Group: Logo Padding (linked inputs)

**DB Keys:** `dashboard_logo_padding_{top|right|bottom|left}` and `dashboard_logo_padding_linked` and `dashboard_logo_padding_unit`
**Container:** `#dwm-logo-border-block` (inside `#dwm-dashboard-logo-style-col`) | **Group:** `data-group="logo-padding"` | **Default:** all sides `10`, unit `px`, linked `0`
**Note:** Default is `10` not `0` — intentional padding default

- [ ] PHP var: `$logo_padding_unit = (string)( $settings['dashboard_logo_padding_unit'] ?? 'px' )`
- [ ] PHP var: `$logo_padding_linked = ! empty( $settings['dashboard_logo_padding_linked'] )`
- [ ] Outer wrapper `.dwm-linked-inputs` has `data-group="logo-padding"`
- [ ] Container `#dwm-logo-border-block` (wraps this linked group) has conditional `has-following-group-divider` class when `$logo_border_style ≠ 'none'` (adds visual divider between padding and subsequent border controls)
- [ ] Link button: class `dwm-link-btn`, `data-group="logo-padding"`, `aria-label="Link padding values"`, `is-linked` class on load when `$logo_padding_linked`
- [ ] Link hidden field: name `settings[dashboard_logo_padding_linked]`, class `dwm-link-value`, `data-group="logo-padding"`, value `'1'`/`'0'`
- [ ] Each side: name `settings[dashboard_logo_padding_{side}]`, ID `dwm-logo-padding-{side}`, label with `for=`, `min="0"` `max="200"`, default `10` (not `0`)
- [ ] Unit select: name `settings[dashboard_logo_padding_unit]`, ID `dwm-logo-padding-unit`, class `dwm-linked-unit-select`, `selected()` per option, options `px`, `%`, `rem`, `em`, default `px`
- [ ] All escape: `esc_attr( (string)( $settings[key] ?? 10 ) )`
- [ ] Link active syncs all four sides
- [ ] All sanitized server-side (int clamped 0–200, allowlist for unit)
- [ ] `#dwm-dashboard-logo-style-col` has `dwm-hidden-by-toggle` when URL is empty
- [ ] Visible only when `$logo_mode_has_logo` AND `dashboard_logo_url` non-empty
- [ ] **Dashboard:** CSS `padding` applied to the logo container element; logo `<img>` has visible inner spacing
- [ ] **Dashboard:** Default `10px` all sides produces visible space around the logo image when no custom padding is set

---

### Field: Logo Link URL

**DB Key:** `dashboard_logo_link_url` | **Type:** url | **Default:** `''`

- [ ] Read: `(string)( $settings['dashboard_logo_link_url'] ?? '' )`
- [ ] Default: `''` (no link)
- [ ] Escape: `esc_attr()`
- [ ] Name: `settings[dashboard_logo_link_url]`
- [ ] ID: `dwm-dashboard-logo-link-url`
- [ ] Label: "Logo Link URL" with `for="dwm-dashboard-logo-link-url"` (rendered as subsection label)
- [ ] `type="url"` present
- [ ] `placeholder="https://example.com"` present
- [ ] Sanitized server-side as `esc_url_raw()` or equivalent
- [ ] `#dwm-dashboard-logo-link-options` has `dwm-hidden-by-toggle` when URL is empty
- [ ] Visible only when `$logo_mode_has_logo` AND `dashboard_logo_url` non-empty
- [ ] **Dashboard:** When URL saved — logo `<img>` wrapped in `<a href="{url}">` on dashboard; clicking logo navigates to the URL
- [ ] **Dashboard:** When URL empty — logo `<img>` rendered without `<a>` wrapper; clicking logo does nothing
- [ ] **Dashboard:** URL is escaped with `esc_url()` in the `href` attribute output

---

### Field: Logo Link New Tab

**DB Key:** `dashboard_logo_link_new_tab` | **Type:** checkbox | **Default:** off

- [ ] Read: `! empty( $settings['dashboard_logo_link_new_tab'] )`
- [ ] Default: off (no new tab)
- [ ] Escape: `checked()`
- [ ] Name: `settings[dashboard_logo_link_new_tab]`
- [ ] ID: `dwm-dashboard-logo-link-new-tab`
- [ ] Label: "New Tab" (inline next to URL field, small toggle style)
- [ ] Value: `1`
- [ ] Sanitized server-side as boolean/int
- [ ] Visible only when `$logo_mode_has_logo` AND `dashboard_logo_url` non-empty
- [ ] **Dashboard:** When ON — logo `<a>` link tag has `target="_blank"` (and ideally `rel="noopener noreferrer"`) — verify clicking logo opens URL in a new browser tab
- [ ] **Dashboard:** When OFF — logo `<a>` tag has no `target` attribute; URL opens in same tab

---

## Section 5 — On-Load Announcement

**Section ID:** `#dwm-section-onload-announcement`
**Title:** "On-Load Announcement"
**Help icon:** `aria-label="Learn about dashboard on-load announcements"` → `data-docs-page="custom-dashboard-on-load-announcement"` → opens `#dwm-docs-modal`
**Save button:** "Save On-Load Announcement"
**Visibility:** Always rendered (outside `#dwm-section-dashboard-branding` but inside `#dwm-settings-form`)
**PHP init vars** declared inside `#dwm-dashboard-notice-fields` (lines 827–832): `$notice_type`, `$notice_level`, `$notice_dismissible`, `$notice_auto_dismiss`, `$notice_position`, `$notice_frequency`

### Section Checklist
- [ ] Help icon opens `#dwm-docs-modal` on `custom-dashboard-on-load-announcement` page
- [ ] Save button label is "Save On-Load Announcement"
- [ ] Toggle off: all announcement fields hidden on load; no announcement shown on dashboard
- [ ] Toggle on: all announcement fields visible; announcement fires on dashboard
- [ ] All 6 PHP notice vars declared before the HTML that uses them
- [ ] All fields reload with saved values after save + page reload
- [ ] Display type change does not break position or level selects
- [ ] Auto-dismiss `0` means announcement never auto-dismisses
- [ ] Frequency setting controls how often announcement re-fires per user
- [ ] Announcement fires correctly on dashboard for each display type (toast, popup, alert)

#### Dashboard Output & Assets — Section 5
- [ ] DWM JS asset for announcements enqueued on `wp-admin/index.php`; no 404s or console errors
- [ ] JS reads announcement settings from a localized data object (e.g. `wp_localize_script`) passed from PHP — data includes: type, level, position, dismissible, auto-dismiss delay, frequency, title, message
- [ ] **Dashboard (toggle ON):** Announcement fires on dashboard page load in correct display format
- [ ] **Dashboard (toggle OFF):** No announcement appears; JS asset may still load but takes no action
- [ ] **Toast:** Positioned notification element appears at the correct screen corner after page load
- [ ] **Popup Modal:** Modal overlay appears centered on screen after page load; page interaction behind overlay is blocked
- [ ] **Inline Alert:** Alert banner inserted at a fixed position within the dashboard content area
- [x] **Message Level:** Announcement element has a visual indicator (color class, icon) reflecting the saved level *(Fixed: popup overlay now carries `dwm-announcement--{level}` class; toast and alert were already correct)*
- [x] **Dismissible ON:** Announcement has an X / close button; clicking it removes the announcement *(Fixed: popup close button now conditional on `noticeDismissible`; alert dismiss handler added)*
- [x] **Dismissible OFF:** No dismiss button; announcement remains until auto-dismiss fires or user navigates away *(Fixed: popup close button and overlay-click-close now guarded by `cfg.noticeDismissible`)*
- [ ] **Auto-dismiss 0:** Announcement persists indefinitely (until dismissed or page navigated); no auto-dismiss timer
- [x] **Auto-dismiss > 0:** Announcement disappears after the saved number of seconds *(Fixed: auto-dismiss setTimeout added for popup and alert types)*
- [ ] **Frequency `always`:** Announcement fires on every dashboard page load regardless of prior visits
- [ ] **Frequency `once-session`:** Fires once per browser session; reloading dashboard same session does not re-trigger; new browser session does re-trigger
- [x] **Frequency `once-day`:** Fires once within a 24-hour window; reloading dashboard same day does not re-trigger; reloading after 24h does re-trigger *(Fixed: now uses `Date.now()` timestamp comparison `>= 86400000` instead of calendar-day string)*
- [x] Frequency tracking uses the correct storage mechanism (sessionStorage for `once-session`, localStorage with timestamp for `once-day`) *(Fixed: `once-day` now stores numeric timestamp)*

---

### Field: Enable On-Load Announcement (toggle)

**DB Key:** `dashboard_notice_enabled` | **Type:** checkbox | **Default:** off

- [ ] Read: `! empty( $settings['dashboard_notice_enabled'] )`
- [ ] Default: off
- [ ] Escape: `checked()`
- [ ] Name: `settings[dashboard_notice_enabled]`
- [ ] ID: `dwm-dashboard-notice-enabled`
- [ ] Label: "Enable On-Load Announcement"
- [ ] Value: `1`
- [ ] `data-toggle-controls="#dwm-dashboard-notice-fields"` present
- [ ] Container `#dwm-dashboard-notice-fields` has `dwm-hidden-by-toggle` on load when off
- [ ] Container visible on load when saved as on
- [ ] Sanitized server-side as boolean/int
- [ ] Always visible
- [ ] **Dashboard ON:** Announcement fires on page load using saved display type, level, position, and content
- [ ] **Dashboard OFF:** No announcement element rendered or injected by DWM JS on dashboard page load

---

### Field: Display Type

**DB Key:** `dashboard_notice_type` | **Type:** select | **Default:** `toast`

- [ ] PHP var: `$notice_type = (string)( $settings['dashboard_notice_type'] ?? 'toast' )`
- [ ] Default: `toast`
- [ ] Escape: `selected( 'toast', $notice_type )` etc. (note: argument order is `selected( $value, $current )`)
- [ ] Name: `settings[dashboard_notice_type]`
- [ ] ID: `dwm-dashboard-notice-type`
- [ ] Label: "Display Type" with `for="dwm-dashboard-notice-type"`
- [ ] Options: `toast` (Toast), `popup` (Popup Modal), `alert` (Inline Alert)
- [ ] Saved value correctly pre-selects on load
- [ ] Sanitized server-side as allowlist string
- [ ] **Dashboard (toast):** Notification element appended to body and positioned at the saved corner; does not interrupt page layout
- [ ] **Dashboard (popup):** Modal overlay element appended to body and centered on screen; background interaction blocked while open
- [ ] **Dashboard (alert):** Banner element inserted at a fixed position within the dashboard content area (not floating)
- [ ] Visible only when toggle ON

---

### Field: Message Level

**DB Key:** `dashboard_notice_level` | **Type:** select | **Default:** `info`

- [ ] PHP var: `$notice_level = (string)( $settings['dashboard_notice_level'] ?? 'info' )`
- [ ] Default: `info`
- [ ] Escape: `selected( 'info', $notice_level )` etc.
- [ ] Name: `settings[dashboard_notice_level]`
- [ ] ID: `dwm-dashboard-notice-level`
- [ ] Label: "Message Level" with `for="dwm-dashboard-notice-level"`
- [ ] Options: `info`, `success`, `warning`, `error`
- [ ] Saved value correctly pre-selects on load
- [ ] Sanitized server-side as allowlist string
- [ ] **Dashboard:** Announcement element carries a level-specific CSS class (e.g. `dwm-notice--info`, `dwm-notice--success`, `dwm-notice--warning`, `dwm-notice--error`) that drives the background color and icon displayed
- [ ] **Dashboard:** Changing level without reloading the page does not re-fire the announcement; change persists after save + reload
- [ ] Visible only when toggle ON

---

### Field: Toast Position

**DB Key:** `dashboard_notice_position` | **Type:** select | **Default:** `bottom-right`

- [ ] PHP var: `$notice_position = (string)( $settings['dashboard_notice_position'] ?? 'bottom-right' )`
- [x] Default: `bottom-right` in DB *(Fixed: `dashboard_notice_position => 'bottom-right'` added to `class-dwm-settings.php` defaults)*
- [ ] Escape: `selected( 'bottom-right', $notice_position )` etc.
- [ ] Name: `settings[dashboard_notice_position]`
- [ ] ID: `dwm-dashboard-notice-position`
- [ ] Label: "Toast Position" with `for="dwm-dashboard-notice-position"`
- [ ] Options: `bottom-right` (Bottom Right), `bottom-left` (Bottom Left), `top-right` (Top Right), `top-left` (Top Left)
- [ ] Saved value correctly pre-selects on load
- [ ] Sanitized server-side as allowlist string
- [ ] **Dashboard (toast):** Element uses inline or class-based CSS (`bottom`/`top`, `left`/`right`) matching the saved position value
- [ ] **Dashboard (popup/alert):** Position value is ignored; element renders in its fixed location regardless of this setting
- [ ] Verify all four positions: `bottom-right`, `bottom-left`, `top-right`, `top-left` — each places toast at the correct corner
- [ ] Visible only when toggle ON

---

### Field: Frequency

**DB Key:** `dashboard_notice_frequency` | **Type:** select | **Default:** `always`

- [ ] PHP var: `$notice_frequency = (string)( $settings['dashboard_notice_frequency'] ?? 'always' )`
- [x] Default: `always` in DB *(Fixed: `dashboard_notice_frequency => 'always'` added to `class-dwm-settings.php` defaults)*
- [ ] Escape: `selected( 'always', $notice_frequency )` etc.
- [ ] Name: `settings[dashboard_notice_frequency]`
- [ ] ID: `dwm-dashboard-notice-frequency`
- [ ] Label: "Frequency" with `for="dwm-dashboard-notice-frequency"`
- [ ] Options: `always` (Every Page Load), `once-session` (Once Per Session), `once-day` (Once Per Day)
- [ ] Saved value correctly pre-selects on load
- [ ] Sanitized server-side as allowlist string
- [ ] JS/localStorage/session logic on dashboard respects frequency setting
- [ ] `always` fires every time the dashboard loads
- [ ] `once-session` stores a flag in sessionStorage; clears on browser session end
- [x] `once-day` stores a flag with timestamp in localStorage; re-fires after 24h *(Fixed: now uses `Date.now()` numeric timestamp comparison)*
- [ ] **Dashboard (`always`):** Announcement fires on every dashboard page load; no storage key checked or written
- [ ] **Dashboard (`once-session`):** After first fire, a sessionStorage key is written; reloading the dashboard in the same browser session does not re-fire; opening a new browser session clears sessionStorage and re-fires
- [x] **Dashboard (`once-day`):** After first fire, a localStorage key with a timestamp is written; reloading within 24h does not re-fire; reloading after 24h has elapsed does re-fire and resets the timestamp *(Fixed: `Date.now()` timestamp logic now used)*
- [ ] Visible only when toggle ON

---

### Field: Dismissible

**DB Key:** `dashboard_notice_dismissible` | **Type:** checkbox | **Default:** off

- [ ] PHP var: `$notice_dismissible = ! empty( $settings['dashboard_notice_dismissible'] )`
- [x] Default: off — `dashboard_notice_dismissible => 0` now present in DB defaults *(Fixed: added to `class-dwm-settings.php`)*
- [ ] Escape: `checked( $notice_dismissible )`
- [ ] Name: `settings[dashboard_notice_dismissible]`
- [ ] ID: `dwm-dashboard-notice-dismissible`
- [ ] Label: "Dismissible"
- [ ] Value: `1`
- [x] When on, announcement renders with an X / dismiss button on dashboard *(Fixed: popup close button and alert dismiss button now conditional on `noticeDismissible`)*
- [x] When off, announcement has no dismiss control (user must wait for auto-dismiss) *(Fixed: popup dismiss guard added)*
- [ ] Sanitized server-side as boolean/int
- [x] **Dashboard ON:** Close/X button is present inside the announcement element; clicking it removes the announcement immediately *(Fixed: popup close button conditional; alert dismiss click handler added)*
- [x] **Dashboard OFF:** No dismiss button rendered inside the announcement element *(Fixed: popup close button and overlay-click-close now guarded by `cfg.noticeDismissible`)*
- [ ] Visible only when toggle ON

---

### Field: Auto-dismiss (seconds)

**DB Key:** `dashboard_notice_auto_dismiss` | **Type:** number | **Default:** `6`

- [ ] PHP var: `$notice_auto_dismiss = (int)( $settings['dashboard_notice_auto_dismiss'] ?? 6 )`
- [x] Default: `6` seconds in DB *(Fixed: `dashboard_notice_auto_dismiss => 6` added to `class-dwm-settings.php` defaults)*
- [ ] Escape: `esc_attr( (string) $notice_auto_dismiss )`
- [ ] Name: `settings[dashboard_notice_auto_dismiss]`
- [ ] ID: `dwm-dashboard-notice-auto-dismiss`
- [ ] Label: "Auto-dismiss (seconds, 0 = never)" with `for="dwm-dashboard-notice-auto-dismiss"`
- [ ] `min="0"` `max="60"` present
- [ ] Value `0` means announcement never auto-dismisses (persists until user dismisses or navigates away)
- [x] Dashboard JS reads this value and sets a `setTimeout` accordingly *(Fixed: setTimeout added for popup and alert types, in addition to existing toast handler)*
- [ ] Sanitized server-side as int clamped 0–60
- [ ] **Dashboard (0):** No `setTimeout` is set; announcement remains visible until dismissed or page navigated
- [x] **Dashboard (> 0):** `setTimeout` fires after the saved number of seconds; announcement element is removed from the DOM automatically *(Fixed: popup and alert setTimeout added)*
- [ ] **Dashboard:** Auto-dismiss timer begins after the announcement element is fully rendered/visible, not before
- [ ] Visible only when toggle ON

---

### Field: Announcement Title

**DB Key:** `dashboard_notice_title` | **Type:** text | **Default:** `''`

- [ ] Read: `(string)( $settings['dashboard_notice_title'] ?? '' )`
- [ ] Default: `''` (empty — title is optional)
- [ ] Escape: `esc_attr()`
- [ ] Name: `settings[dashboard_notice_title]`
- [ ] ID: `dwm-dashboard-notice-title`
- [ ] Label: "Announcement Title" with `for="dwm-dashboard-notice-title"`
- [ ] Saved value loads in input on page open
- [ ] Sanitized server-side (`sanitize_text_field`)
- [ ] **Dashboard (non-empty):** Title renders as a heading element inside the announcement (e.g. `<strong>`, `<h3>`, or equivalent) above the message body
- [ ] **Dashboard (empty):** Title heading element is omitted; no empty tag rendered; message-only announcement displays without a gap or blank heading
- [ ] Visible only when toggle ON

---

### Field: Announcement Message

**DB Key:** `dashboard_notice_message` | **Type:** textarea (3 rows) | **Default:** `''`

- [ ] Read: `(string)( $settings['dashboard_notice_message'] ?? '' )`
- [ ] Default: `''` (empty)
- [ ] Escape: `esc_textarea()`
- [ ] Name: `settings[dashboard_notice_message]`
- [ ] ID: `dwm-dashboard-notice-message`
- [ ] Label: "Announcement Message" with `for="dwm-dashboard-notice-message"`
- [ ] `rows="3"` present
- [ ] Saved value loads in textarea on page open
- [ ] Sanitized server-side (`wp_kses_post` or `sanitize_textarea_field` depending on allowed HTML)
- [ ] **Dashboard (non-empty):** Message renders as the body content of the announcement element (paragraph or div); text is escaped/safe output
- [ ] **Dashboard (empty):** Body content element omitted or announcement suppressed entirely; no empty paragraph rendered
- [ ] **Dashboard:** Newlines in message preserved or converted to `<br>` appropriately; no raw `\n` characters visible in rendered announcement
- [ ] Visible only when toggle ON

---

## Shared Modal — Format Text (`#dwm-title-format-modal`)

**Trigger buttons:** `.dwm-title-format-icon-btn` with `data-field` attribute and `data-open-modal="dwm-title-format-modal"` (no `#` prefix)
**Two target field prefixes:** `dashboard_title` (Dashboard Title) and `dashboard_hero_title` (Hero Title)
**Modal attrs:** `role="dialog"` `aria-modal="true"` `aria-labelledby="dwm-title-format-modal-title"`
**Header:** `id="dwm-title-format-modal-title"`, dashicons-editor-textcolor icon + "Format Text"
**Close:** `button.dwm-modal-close` with `aria-label="Close modal"`, overlay click, Escape key

### Modal Checklist
- [ ] Modal opens when format button is clicked (both Dashboard Title and Hero Title buttons)
- [ ] `data-field` on trigger button identifies which hidden field prefix to read/write
- [ ] Modal reads current saved values from the correct hidden fields on open (based on `data-field`)
- [ ] Font family select `#dwm-title-format-font-family` pre-populated from saved `{prefix}_font_family`
- [ ] Font size value and unit split from saved `{prefix}_font_size` (e.g. `28px` → `28` + `px`)
- [ ] Font weight select `#dwm-title-format-font-weight` pre-populated from saved `{prefix}_font_weight`
- [ ] Alignment button group (`.dwm-alignment-buttons`) shows correct `active`/selected state from saved `{prefix}_alignment`
- [ ] Color tab pre-populated from saved `{prefix}_color` (hex → Solid tab, `rgba()` → RGBA tab, `gradient` → Gradient tab)
- [ ] Clicking Apply (`#dwm-title-format-apply`) writes current modal values back to all 5 hidden fields
- [ ] Applied values submitted with main form and persisted to DB on next save
- [ ] RGBA tab: range sliders and number inputs stay in sync bidirectionally per channel
- [ ] Gradient tab: `#dwm-title-gradient-preview` updates live when type/angle/stops change
- [x] Color presets: clicking a preset swatch updates hex input, color wheel, and RGBA preview *(Fixed: `.dwm-preset-swatch` click handler added to `settings-form.js`)*
- [ ] Modal accessible: `role="dialog"`, `aria-modal="true"`, `aria-labelledby` present
- [ ] Close button has `aria-label="Close modal"`
- [x] Focus trapped inside modal while open *(Fixed: Round 2 — `trapFocus()` added to `openModal()` in `modals.js`; all modals using `openModal()` now receive focus trap automatically)*
- [ ] Escape key closes modal

### Format Modal Controls Detail

#### Font Family (`#dwm-title-format-font-family`)
- [ ] `<select>` element, no `name` (modal-only; value written to hidden field on Apply)
- [ ] Options: `inherit` (Default/Inherit), `Arial, sans-serif` (Arial), `'Helvetica Neue', Helvetica, sans-serif` (Helvetica), `Georgia, serif` (Georgia), `'Times New Roman', Times, serif` (Times New Roman), `'Courier New', Courier, monospace` (Courier), `-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif` (System Font)
- [ ] Pre-populated from `{prefix}_font_family` hidden field on modal open

#### Text Alignment (`.dwm-alignment-buttons`)
- [ ] Three buttons `.dwm-alignment-btn` with `data-align="left|center|right"` and dashicons icons
- [ ] Active alignment button reflects saved `{prefix}_alignment` value on modal open
- [ ] Clicking button updates `active` state and stores selection (written to hidden field on Apply)

#### Font Size
- [ ] Number input: `id="dwm-title-format-font-size-value"`, `min="8"` `max="72"`, default `32`
- [ ] Unit select: `id="dwm-title-format-font-size-unit"`, options `px`, `rem`, `em`
- [ ] Slider: `id="dwm-title-format-font-size-slider"`, class `dwm-format-slider`, `min="8"` `max="72"`, default `32`
- [ ] Slider and number input stay bidirectionally in sync (JS)
- [ ] Value combined with unit as `{value}{unit}` string written to `{prefix}_font_size` on Apply

#### Font Weight (`#dwm-title-format-font-weight`)
- [ ] `<select>` element, no `name`
- [ ] Options: `300` (Light), `400` (Normal), `500` (Medium), `600` (Semi-Bold), `700` (Bold)
- [ ] Pre-populated from `{prefix}_font_weight` hidden field on modal open
- [ ] Default stored as `700` (Bold)

#### Text Color — Tab System (`.dwm-color-tabs`)
- [ ] Three tab buttons `.dwm-color-tab-btn` with `data-tab="hex|rgba|gradient"`
- [ ] First tab (`data-tab="hex"`) has `active` class on initial open
- [ ] Three tab content panels `.dwm-color-tab-content` with `data-tab="hex|rgba|gradient"`
- [ ] First panel (`data-tab="hex"`) has `active` class on initial open
- [ ] Clicking a tab button switches active class and shows/hides corresponding panel

**Solid/Hex tab (`data-tab="hex"`):**
- [ ] Text input `#dwm-title-format-color` class `dwm-color-hex-input`, default value `#1d2327`
- [ ] Native color picker `#dwm-title-color-wheel` class `dwm-native-color-picker`, default `#1d2327`
- [ ] Hex text and color wheel stay in sync bidirectionally
- [ ] 6 color preset swatches (`.dwm-preset-swatch` with `data-color`): `#1e1e1e` (Black), `#333333` (Dark Gray), `#666666` (Gray), `#999999` (Light Gray), `#ffffff` (White), `#0073aa` (WordPress Blue)
- [x] Clicking a swatch updates hex input and color wheel *(Fixed: `.dwm-preset-swatch` click handler added)*

**RGBA tab (`data-tab="rgba"`):**
- [ ] PHP loop generates 4 channels: `r=29`, `g=35`, `b=39`, `a=100`
- [ ] Range: `id="dwm-title-rgba-{key}"` (r/g/b/a), class `dwm-rgba-slider`, `min="0"`, `max="255"` (or `max="100"` for alpha)
- [ ] Number input: class `dwm-rgba-value`, `min="0"`, `max="255"` (or `max="100"` for alpha)
- [ ] Range and number stay in sync per channel
- [x] Preview div `#dwm-title-rgba-preview` default inline style `background-color: rgba(29, 35, 39, 1);` — updates live as sliders move *(Fixed: `updateRgbaPreview()` function added and called from slider/number input handlers)*

**Gradient tab (`data-tab="gradient"`):**
- [ ] Gradient type select `#dwm-title-gradient-type`, options `linear` / `radial`
- [ ] Angle range `#dwm-title-gradient-angle`, class `dwm-format-slider`, `min="0"` `max="360"`, default `90`
- [ ] Angle display span `#dwm-title-gradient-angle-value`, default `90°`, updates live
- [ ] Start color picker `#dwm-title-gradient-start`, class `dwm-stop-color`, default `#667eea`
- [ ] Start position range `#dwm-title-gradient-start-pos`, class `dwm-stop-position`, `min="0"` `max="100"`, default `0`
- [ ] Start position label `#dwm-title-gradient-start-label`, default `0%`, updates live
- [ ] End color picker `#dwm-title-gradient-end`, class `dwm-stop-color`, default `#764ba2`
- [ ] End position range `#dwm-title-gradient-end-pos`, class `dwm-stop-position`, `min="0"` `max="100"`, default `100`
- [ ] End position label `#dwm-title-gradient-end-label`, default `100%`, updates live
- [ ] Preview div `#dwm-title-gradient-preview`, class `dwm-gradient-preview`, default inline style `background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);` — updates live

#### Apply Button
- [ ] `id="dwm-title-format-apply"`, class `dwm-button dwm-button-primary`, label "Apply"
- [ ] Clicking writes all current modal control values to the correct `{prefix}_*` hidden fields
- [x] Modal closes after apply *(Fixed: fallback `.hide()` replaced with `.removeClass('active')` + body class removal)*

### Format Modal Hidden Field Group

For each of the two prefixes (`dashboard_title` and `dashboard_hero_title`):

| Hidden Field | DB Key | ID | Default |
|---|---|---|---|
| Font family | `{prefix}_font_family` | `{prefix}_font_family` | `inherit` |
| Font size | `{prefix}_font_size` | `{prefix}_font_size` | `32px` (title) / `28px` (hero title) |
| Font weight | `{prefix}_font_weight` | `{prefix}_font_weight` | `700` |
| Alignment | `{prefix}_alignment` | `{prefix}_alignment` | `left` |
| Color | `{prefix}_color` | `{prefix}_color` | `#1d2327` (title) / `#ffffff` (hero title) |

For each hidden field:
- [ ] ID matches what JS uses to read/write the field (no `#dwm-` prefix — just the raw key name)
- [ ] Name `settings[key]` matches DB key exactly
- [ ] Default applied via PHP `??` fallback
- [ ] Value escaped with `esc_attr()`
- [ ] Modal reads this on open and pre-fills corresponding modal control
- [ ] Modal writes back to this on Apply button click
- [ ] Value submitted with form
- [ ] Sanitized server-side for data type (string, px/rem/em value, hex/rgba/gradient string)
- [ ] Applied to the correct element's inline style on dashboard

---

## Edit Logo Modal (`#dwm-dashboard-logo-edit-modal`)

**Opens from:** "Edit Logo" overlay button (`class="dwm-logo-replace-overlay"`, `data-open-modal="#dwm-dashboard-logo-edit-modal"` — note the `#` prefix, unique to this trigger)
**Modal attrs:** `role="dialog"` `aria-modal="true"` `aria-labelledby="dwm-dashboard-logo-edit-title"`
**Header:** `id="dwm-dashboard-logo-edit-title"`, dashicons-format-image icon + "Edit Dashboard Logo"
**Close:** `button.dwm-modal-close` with `aria-label="Close modal"`, overlay click, Escape key

### Modal Checklist
- [ ] Modal opens when "Edit Logo" overlay button is clicked
- [ ] "Choose Different Logo" button: class `dwm-button dwm-button-secondary dwm-dashboard-logo-replace-action` — re-triggers WP media picker and replaces `dashboard_logo_url`
- [ ] "Remove Logo" button: class `dwm-button dwm-button-danger dwm-dashboard-logo-remove-action` — clears `dashboard_logo_url` to `''`, hides size/style/preview cols, shows Choose Logo button
- [x] After removing logo, `dashboard_logo_enabled` set to `0`, preview img gets `is-empty` class, preview wrap loses `has-logo` class *(Fixed: `clearDashboardLogoConfiguration()` now sets `#dwm-dashboard-logo-enabled` to `'0'`)*
- [ ] After replacing logo, new URL updates hidden `#dwm-dashboard-logo-url`, preview `<img>` `src=` updates live, `has-logo` and `is-empty` classes updated accordingly
- [ ] Modal body text: "Choose a different logo or remove the current one. Removing it will hide all logo-specific controls until a new logo is added."
- [ ] Modal accessible: `role="dialog"`, `aria-modal="true"`, `aria-labelledby="dwm-dashboard-logo-edit-title"` present
- [ ] Close button (`dwm-modal-close`) has `aria-label="Close modal"` and dashicons-no-alt icon
- [x] Focus trapped inside modal while open *(Fixed: Round 2 — `trapFocus()` in `openModal()` covers this modal)*
- [ ] Escape key closes modal
- [ ] Clicking overlay closes modal
- [ ] Closing without action makes no changes
