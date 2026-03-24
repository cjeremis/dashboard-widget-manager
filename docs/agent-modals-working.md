# Agent Modals Working Checklist
**Date:** 2026-03-22
**Status:** COMPLETE
**Sections covered:** Shared Modal (Format Text) + Edit Logo Modal

## Summary
- PASS: 70
- FAIL: 8
- PARTIAL: 4
- UNCERTAIN: 2

### Critical fixes needed:
- RGBA preview div (`#dwm-title-rgba-preview`) never updates live when sliders move — no JS listener exists
- Preset swatch click JS is missing — swatches do not update hex input, color wheel, or RGBA preview
- Focus trap is not implemented for either modal (only `docs-modal.js` has `trapFocus`)
- Apply button does not close the modal via `dwmModalAPI.close` when the modal was opened through the generic `data-open-modal` path (no `dwmModalAPI.open` call from `.dwm-title-format-icon-btn` handler)

### Template changes needed:
- None identified — HTML structure matches spec completely

### JS changes needed:
- Add live RGBA preview update on `.dwm-rgba-slider` and `.dwm-rgba-value` input events
- Add `.dwm-preset-swatch` click handler (updates `#dwm-title-format-color`, `#dwm-title-color-wheel`)
- Add focus-trap logic for both format-text and logo-edit modals
- Fix Apply button close: use `dwmModalAPI.close` or call `closeModal('#dwm-title-format-modal')` (the existing code path for `window.dwmModalAPI.close` IS correct — but `openTitleFormatModal` never actually opens the modal via the API; the trigger click on `.dwm-title-format-icon-btn` relies on the generic `[data-open-modal]` handler in `modals.js` because `data-open-modal="dwm-title-format-modal"` (no `#`) is handled by `normalizeModalTarget` which converts bare IDs to `#id`)

---

## Files Examined

- `templates/admin/customize-dashboard.php` — lines 362–440 (trigger buttons + hidden fields), lines 779–799 (logo edit modal), lines 909–1044 (format text modal)
- `assets/js/modules/forms/settings-form.js` — lines 983–1128 (format modal JS), lines 591–878 (logo modal JS)
- `assets/js/modules/modals/modals.js` — generic modal open/close/escape handler

---

## Shared Modal — Format Text (`#dwm-title-format-modal`)

### Trigger Button Checks

- [x] Modal trigger buttons use class `dwm-title-format-icon-btn` — confirmed at lines 362 and 430 of `customize-dashboard.php`
- [x] Dashboard Title trigger: `data-field="dashboard_title"` `data-open-modal="dwm-title-format-modal"` (no `#` prefix) — confirmed line 362
- [x] Hero Title trigger: `data-field="dashboard_hero_title"` `data-open-modal="dwm-title-format-modal"` (no `#` prefix) — confirmed line 430

Note: The trigger uses class `dwm-format-icon-btn dwm-title-format-icon-btn` — the checklist says `dwm-title-format-icon-btn`, both classes are present. No issue.

### Modal Checklist

- [x] Modal opens when format button is clicked (both Dashboard Title and Hero Title buttons)
  > JS at settings-form.js line 1056: `$(document).on('click', '.dwm-title-format-icon-btn', function() { ... openTitleFormatModal(activeTitleFormatField); });` — opens modal state. However, this handler does NOT call `openModal()` — the actual DOM open is handled by `modals.js` generic `[data-open-modal]` handler (which fires first on same click). This works in practice because both fire on the same click event.

- [x] `data-field` on trigger button identifies which hidden field prefix to read/write — JS reads `$(this).data('field')` at line 1057; writes back using `activeTitleFormatField` at lines 1117–1121

- [x] Modal reads current saved values from the correct hidden fields on open (based on `data-field`) — `openTitleFormatModal` at lines 1006–1034 reads `$('#' + fieldKey + '_font_size')`, `_font_family`, `_font_weight`, `_alignment`, `_color`

- [x] Font family select `#dwm-title-format-font-family` pre-populated from saved `{prefix}_font_family` — line 1011

- [x] Font size value and unit split from saved `{prefix}_font_size` (e.g. `28px` → `28` + `px`) — regex split at line 1007; `match[1]` → number input, `match[2]` → unit select

- [x] Font weight select `#dwm-title-format-font-weight` pre-populated from saved `{prefix}_font_weight` — line 1012

- [x] Alignment button group (`.dwm-alignment-buttons`) shows correct `active`/selected state from saved `{prefix}_alignment` — lines 1013–1014

- [x] Color tab pre-populated from saved `{prefix}_color` (hex → Solid tab, `rgba()` → RGBA tab, `gradient` → Gradient tab) — lines 1017–1034 handle all three branches

- [x] Clicking Apply (`#dwm-title-format-apply`) writes current modal values back to all 5 hidden fields — lines 1117–1121

- [x] Applied values submitted with main form and persisted to DB on next save — hidden fields have `name="settings[...]"` and are inside `#dwm-settings-form`

- [x] RGBA tab: range sliders and number inputs stay in sync bidirectionally per channel — lines 1083–1089

- [x] Gradient tab: `#dwm-title-gradient-preview` updates live when type/angle/stops change — line 1080–1082 triggers `updateTitleGradientPreview()`

- [!] Color presets: clicking a preset swatch updates hex input, color wheel, and RGBA preview — **NO JS handler for `.dwm-preset-swatch` click exists anywhere in settings-form.js or any other source JS file**
  > FIX NEEDED: `assets/js/modules/forms/settings-form.js`
  > CURRENT: No click handler for `.dwm-preset-swatch`
  > REQUIRED: Handler that reads `$(this).data('color')`, sets `#dwm-title-format-color` and `#dwm-title-color-wheel` to that value

- [x] Modal accessible: `role="dialog"`, `aria-modal="true"`, `aria-labelledby` present — confirmed at line 909 of template: `role="dialog" aria-modal="true" aria-labelledby="dwm-title-format-modal-title"`

- [x] Close button has `aria-label="Close modal"` — line 914: `aria-label="<?php esc_attr_e( 'Close modal', ... ); ?>"`

- [!] Focus trapped inside modal while open — **No focus trap implemented** in `settings-form.js` or `modals.js` for this modal. `docs-modal.js` has `trapFocus` but it is not shared/reused.
  > FIX NEEDED: `assets/js/modules/forms/settings-form.js` or `assets/js/modules/modals/modals.js`
  > CURRENT: No Tab key focus-trap logic for `#dwm-title-format-modal`
  > REQUIRED: When modal is open, Tab/Shift+Tab cycles within focusable elements inside the modal

- [x] Escape key closes modal — `modals.js` ESC handler at lines 223–240 closes the topmost active modal; this covers `#dwm-title-format-modal`

- [~] Modal closes after apply — Apply handler at lines 1123–1127 correctly calls `window.dwmModalAPI.close('#dwm-title-format-modal')` if the API is present, with fallback to `$('#dwm-title-format-modal').hide()`. The `dwmModalAPI` is set in `modals.js` line 163–166 which runs on admin pages. However `modals.js` is imported from `admin.js` component, not from `settings.js` — this is PARTIAL risk.
  > FIX NEEDED: Verify `modals.js` / `dwmModalAPI` is available on the Branding page (loaded via `admin.js`). If not, the `.hide()` fallback is used which does not remove the `active` class — the modal would be visually hidden but not truly closed.
  > CURRENT: Fallback uses `.hide()` instead of removing `.active` class
  > REQUIRED: Fallback should be `$('#dwm-title-format-modal').removeClass('active')`

---

### Format Modal Controls Detail

#### Font Family (`#dwm-title-format-font-family`)

- [x] `<select>` element, no `name` (modal-only) — confirmed at line 922 of template: `<select id="dwm-title-format-font-family">` — no `name` attribute
- [x] Options: `inherit` (Default/Inherit) — line 923
- [x] Options: `Arial, sans-serif` (Arial) — line 924
- [x] Options: `'Helvetica Neue', Helvetica, sans-serif` (Helvetica) — line 925
- [x] Options: `Georgia, serif` (Georgia) — line 926
- [x] Options: `'Times New Roman', Times, serif` (Times New Roman) — line 927
- [x] Options: `'Courier New', Courier, monospace` (Courier) — line 928
- [x] Options: `-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif` (System Font) — line 929
- [x] Pre-populated from `{prefix}_font_family` hidden field on modal open — settings-form.js line 1011

#### Text Alignment (`.dwm-alignment-buttons`)

- [x] Three buttons `.dwm-alignment-btn` with `data-align="left|center|right"` — lines 935–937 of template
- [x] Dashicons icons on alignment buttons — confirmed: `dashicons-editor-alignleft`, `dashicons-editor-aligncenter`, `dashicons-editor-alignright`
- [x] Active alignment button reflects saved `{prefix}_alignment` value on modal open — settings-form.js line 1014
- [x] Clicking button updates `active` state — settings-form.js lines 1090–1093

#### Font Size

- [x] Number input: `id="dwm-title-format-font-size-value"`, `min="8"` `max="72"`, default `32` — template line 943
- [x] Unit select: `id="dwm-title-format-font-size-unit"`, options `px`, `rem`, `em` — template lines 944–948
- [x] Slider: `id="dwm-title-format-font-size-slider"`, class `dwm-format-slider`, `min="8"` `max="72"`, default `32` — template line 950
- [x] Slider and number input stay bidirectionally in sync — settings-form.js lines 1068–1073 (number → slider and slider → number)
- [x] Value combined with unit as `{value}{unit}` string written to `{prefix}_font_size` on Apply — settings-form.js line 1098

#### Font Weight (`#dwm-title-format-font-weight`)

- [x] `<select>` element, no `name` — template line 954: `<select id="dwm-title-format-font-weight">` no name attr
- [x] Options: `300` (Light) — line 955
- [x] Options: `400` (Normal) — line 956
- [x] Options: `500` (Medium) — line 957
- [x] Options: `600` (Semi-Bold) — line 958
- [x] Options: `700` (Bold) — line 959
- [x] Pre-populated from `{prefix}_font_weight` hidden field on modal open — settings-form.js line 1012
- [x] Default stored as `700` (Bold) — `getFormatFieldDefaults` returns `fontWeight: '700'` at line 998/991

#### Text Color — Tab System (`.dwm-color-tabs`)

- [x] Three tab buttons `.dwm-color-tab-btn` with `data-tab="hex|rgba|gradient"` — template lines 967–969
- [x] First tab (`data-tab="hex"`) has `active` class on initial open — template line 967: `class="dwm-color-tab-btn active"`
- [x] Three tab content panels `.dwm-color-tab-content` with `data-tab="hex|rgba|gradient"` — template lines 972, 990, 1002
- [x] First panel (`data-tab="hex"`) has `active` class on initial open — template line 972: `class="dwm-color-tab-content active"`
- [x] Clicking a tab button switches active class and shows/hides corresponding panel — settings-form.js lines 1060–1067

**Solid/Hex tab (`data-tab="hex"`):**

- [x] Text input `#dwm-title-format-color` class `dwm-color-hex-input`, default value `#1d2327` — template line 975
- [x] Native color picker `#dwm-title-color-wheel` class `dwm-native-color-picker`, default `#1d2327` — template line 976
- [x] Hex text and color wheel stay in sync bidirectionally — settings-form.js lines 1074–1079
- [x] 6 color preset swatches (`.dwm-preset-swatch` with `data-color`): `#1e1e1e`, `#333333`, `#666666`, `#999999`, `#ffffff`, `#0073aa` — template lines 981–986
- [!] Clicking a swatch updates hex input and color wheel — **NO JS handler for `.dwm-preset-swatch` click**
  > FIX NEEDED: `assets/js/modules/forms/settings-form.js`
  > CURRENT: No handler
  > REQUIRED: `$(document).on('click', '.dwm-preset-swatch', function(e) { e.preventDefault(); const color = $(this).data('color'); $('#dwm-title-format-color').val(color); $('#dwm-title-color-wheel').val(color); });`

**RGBA tab (`data-tab="rgba"`):**

- [x] PHP loop generates 4 channels: `r=29`, `g=35`, `b=39`, `a=100` — template line 992: `array( 'r' => 29, 'g' => 35, 'b' => 39, 'a' => 100 )`
- [x] Range: `id="dwm-title-rgba-{key}"` (r/g/b/a), class `dwm-rgba-slider`, `min="0"`, `max="255"` (or `max="100"` for alpha) — template line 995
- [x] Number input: class `dwm-rgba-value`, `min="0"`, `max="255"` (or `max="100"` for alpha) — template line 996
- [x] Range and number stay in sync per channel — settings-form.js lines 1083–1089
- [!] Preview div `#dwm-title-rgba-preview` updates live as sliders move — **NO JS that updates the preview div's background-color**
  > FIX NEEDED: `assets/js/modules/forms/settings-form.js`
  > CURRENT: The `.dwm-rgba-slider` and `.dwm-rgba-value` handlers only sync slider↔number; no `#dwm-title-rgba-preview` update
  > REQUIRED: Add preview update logic inside the rgba slider/value input handlers that reads all 4 channels and sets `$('#dwm-title-rgba-preview').css('background-color', 'rgba(r, g, b, a)')`
- [x] Preview div `#dwm-title-rgba-preview` default inline style `background-color: rgba(29, 35, 39, 1);` — template line 1000: `style="background-color: rgba(29, 35, 39, 1);"` ✓

**Gradient tab (`data-tab="gradient"`):**

- [x] Gradient type select `#dwm-title-gradient-type`, options `linear` / `radial` — template lines 1007–1010
- [x] Angle range `#dwm-title-gradient-angle`, class `dwm-format-slider`, `min="0"` `max="360"`, default `90` — template line 1015
- [x] Angle display span `#dwm-title-gradient-angle-value`, default `90°`, updates live — template line 1016; JS updates at settings-form.js line 1050
- [x] Start color picker `#dwm-title-gradient-start`, class `dwm-stop-color`, default `#667eea` — template line 1023
- [x] Start position range `#dwm-title-gradient-start-pos`, class `dwm-stop-position`, `min="0"` `max="100"`, default `0` — template line 1024
- [x] Start position label `#dwm-title-gradient-start-label`, default `0%`, updates live — template line 1025; JS updates at settings-form.js line 1051
- [x] End color picker `#dwm-title-gradient-end`, class `dwm-stop-color`, default `#764ba2` — template line 1028
- [x] End position range `#dwm-title-gradient-end-pos`, class `dwm-stop-position`, `min="0"` `max="100"`, default `100` — template line 1029
- [x] End position label `#dwm-title-gradient-end-label`, default `100%`, updates live — template line 1030; JS updates at settings-form.js line 1052
- [x] Preview div `#dwm-title-gradient-preview`, class `dwm-gradient-preview`, default inline style `background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);` — template line 1034 ✓
- [x] Preview updates live — settings-form.js line 1080–1082

#### Apply Button

- [x] `id="dwm-title-format-apply"`, class `dwm-button dwm-button-primary`, label "Apply" — template line 1041
- [x] Clicking writes all current modal control values to the correct `{prefix}_*` hidden fields — settings-form.js lines 1096–1121
- [~] Modal closes after apply — settings-form.js lines 1123–1127 call `window.dwmModalAPI.close('#dwm-title-format-modal')` if available, else `$('#dwm-title-format-modal').hide()`. The `.hide()` fallback is incorrect — it suppresses visibility but does not remove `.active` class, meaning the modal remains "open" in state.
  > FIX NEEDED: `assets/js/modules/forms/settings-form.js` line 1126
  > CURRENT: `$('#dwm-title-format-modal').hide();`
  > REQUIRED: `$('#dwm-title-format-modal').removeClass('active'); $('body').removeClass('dwm-modal-open');`

---

### Format Modal Hidden Field Group

**`dashboard_title` prefix (Dashboard Title):**

- [x] `dashboard_title_font_family` — `id="dashboard_title_font_family"` `name="settings[dashboard_title_font_family]"` default `inherit` via `?? 'inherit'` escaped with `esc_attr()` — template line 368
- [x] `dashboard_title_font_size` — `id="dashboard_title_font_size"` `name="settings[dashboard_title_font_size]"` default `32px` via `?? '32px'` escaped with `esc_attr()` — template line 369
- [x] `dashboard_title_font_weight` — `id="dashboard_title_font_weight"` `name="settings[dashboard_title_font_weight]"` default `700` via `?? '700'` escaped — template line 370
- [x] `dashboard_title_alignment` — `id="dashboard_title_alignment"` `name="settings[dashboard_title_alignment]"` default `left` via `?? 'left'` escaped — template line 371
- [x] `dashboard_title_color` — `id="dashboard_title_color"` `name="settings[dashboard_title_color]"` default `#1d2327` via `?? '#1d2327'` escaped — template line 372
- [x] All IDs match what JS uses (no `#dwm-` prefix — just raw key name) — JS accesses `$('#' + fieldKey + '_font_size')` etc., matching IDs like `dashboard_title_font_size`
- [x] All names `settings[key]` match DB key exactly
- [x] All defaults applied via PHP `??` fallback
- [x] All values escaped with `esc_attr()`
- [x] Modal reads these on open — `openTitleFormatModal('dashboard_title')` reads all 5
- [x] Modal writes back on Apply — lines 1117–1121
- [x] Values submitted with form — inputs are inside `#dwm-settings-form` with `name` attributes

**`dashboard_hero_title` prefix (Hero Title):**

- [x] `dashboard_hero_title_font_family` — `id="dashboard_hero_title_font_family"` `name="settings[dashboard_hero_title_font_family]"` default `inherit` via `?? 'inherit'` escaped — template line 435
- [x] `dashboard_hero_title_font_size` — `id="dashboard_hero_title_font_size"` `name="settings[dashboard_hero_title_font_size]"` default `28px` via `?? '28px'` escaped — template line 436
- [x] `dashboard_hero_title_font_weight` — `id="dashboard_hero_title_font_weight"` `name="settings[dashboard_hero_title_font_weight]"` default `700` via `?? '700'` escaped — template line 437
- [x] `dashboard_hero_title_alignment` — `id="dashboard_hero_title_alignment"` `name="settings[dashboard_hero_title_alignment]"` default `left` via `?? 'left'` escaped — template line 438
- [x] `dashboard_hero_title_color` — `id="dashboard_hero_title_color"` `name="settings[dashboard_hero_title_color]"` default `#ffffff` via `?? '#ffffff'` escaped — template line 439
- [x] All IDs match JS access pattern
- [x] All names `settings[key]` match DB key exactly
- [x] All defaults applied via PHP `??` fallback
- [x] All values escaped with `esc_attr()`
- [x] Modal reads these on open — `openTitleFormatModal('dashboard_hero_title')` reads all 5
- [x] Modal writes back on Apply — lines 1117–1121
- [x] Values submitted with form

**Server-side sanitization and application:**

- [?] Sanitized server-side for data type (string, px/rem/em value, hex/rgba/gradient string) — not verified (PHP sanitizer class not audited in this session)
- [?] Applied to the correct element's inline style on dashboard — not verified (public/dashboard rendering not audited in this session)

---

## Edit Logo Modal (`#dwm-dashboard-logo-edit-modal`)

### Modal Location

Found inline in `templates/admin/customize-dashboard.php`, lines 779–799.

### Trigger

- [x] "Edit Logo" overlay button: class `dwm-logo-replace-overlay`, `data-open-modal="#dwm-dashboard-logo-edit-modal"` (# prefix) — template line 764 ✓
- [x] JS handler intercepts this trigger specifically: `$(document).on('click', '[data-open-modal="#dwm-dashboard-logo-edit-modal"]', ...)` at settings-form.js line 855 — prevents open if no logo configured (`hasDashboardLogoConfigured()`)

### Modal Attributes

- [x] `role="dialog"` — template line 779 ✓
- [x] `aria-modal="true"` — template line 779 ✓
- [x] `aria-labelledby="dwm-dashboard-logo-edit-title"` — template line 779 ✓

### Header

- [x] `id="dwm-dashboard-logo-edit-title"` on the `<h2>` — template lines 783 ✓
- [x] dashicons-format-image icon — template line 784: `<span class="dashicons dashicons-format-image"></span>` ✓
- [x] "Edit Dashboard Logo" text — template line 785 ✓

### Close Button

- [x] `.dwm-modal-close` class — template line 787 ✓
- [x] `aria-label="Close modal"` — template line 787 via `esc_attr_e('Close modal', ...)` ✓
- [x] dashicons-no-alt icon — template line 788 ✓

### Modal Checklist

- [x] Modal opens when "Edit Logo" overlay button is clicked — settings-form.js line 855–861 handles the `data-open-modal="#dwm-dashboard-logo-edit-modal"` click, guards with `hasDashboardLogoConfigured()`, then calls `openModal('#dwm-dashboard-logo-edit-modal')`

- [x] "Choose Different Logo" button: class `dwm-button dwm-button-secondary dwm-dashboard-logo-replace-action` — template line 795 ✓

- [x] "Choose Different Logo" re-triggers WP media picker — settings-form.js line 868–872: `.dwm-dashboard-logo-replace-action` click closes modal and calls `openDashboardLogoMediaFrame()`

- [x] "Remove Logo" button: class `dwm-button dwm-button-danger dwm-dashboard-logo-remove-action` — template line 796 ✓

- [x] "Remove Logo" clears `dashboard_logo_url` to `''` — settings-form.js line 874–878 calls `clearDashboardLogoConfiguration()` which sets `$('#dwm-dashboard-logo-url').val('')`

- [~] After removing logo, `dashboard_logo_enabled` set to `0`, preview img gets `is-empty` class, preview wrap loses `has-logo` class — PARTIAL:
  - `is-empty` class IS added: `clearDashboardLogoConfiguration()` at line 627 ✓
  - `has-logo` class IS removed via `syncLogoChooseButtonState()` at line 622: `.toggleClass('has-logo', hasLogo)` — when `hasLogo` is false (no URL), `has-logo` is removed ✓
  - `dashboard_logo_enabled` set to `0`: `syncLogoControlsVisibility()` sets `$('#dwm-dashboard-logo-enabled').val(hasLogo ? '1' : '0')` — but `clearDashboardLogoConfiguration()` does NOT call `syncLogoControlsVisibility()`. It calls `syncLogoLinkOptionsVisibility()` and `syncLogoChooseButtonState()` only. `dashboard_logo_enabled` is not updated on logo remove.
  > FIX NEEDED: `assets/js/modules/forms/settings-form.js` in `clearDashboardLogoConfiguration()`
  > CURRENT: Does not update `#dwm-dashboard-logo-enabled` to `0`
  > REQUIRED: Add `$('#dwm-dashboard-logo-enabled').val('0');` or call `syncLogoControlsVisibility()` after clearing URL

- [x] After replacing logo, new URL updates hidden `#dwm-dashboard-logo-url` — media frame `select` handler at lines 658–664 sets `$target.val(attachment.url)` ✓

- [x] After replacing logo, preview `<img>` `src=` updates live — line 662: `$('#dwm-dashboard-logo-preview').attr('src', attachment.url)` ✓

- [x] After replacing logo, `is-empty` class removed — line 662: `.removeClass('is-empty')` ✓

- [x] After replacing logo, `has-logo` class updated via `syncLogoChooseButtonState()` — line 663 ✓

- [x] Modal body text: "Choose a different logo or remove the current one. Removing it will hide all logo-specific controls until a new logo is added." — template line 792 ✓

- [x] Modal accessible: `role="dialog"`, `aria-modal="true"`, `aria-labelledby="dwm-dashboard-logo-edit-title"` present — template line 779 ✓

- [x] Close button (`dwm-modal-close`) has `aria-label="Close modal"` — template line 787 ✓

- [x] Close button has dashicons-no-alt icon — template line 788 ✓

- [x] Escape key closes modal — `modals.js` ESC handler at lines 223–240 closes topmost active modal; covers this modal

- [x] Clicking overlay closes modal — `modals.js` overlay click handler at lines 190–204 closes modal ✓; additionally settings-form.js line 863 has a specific handler for this modal's overlay

- [x] Closing without action makes no changes — `clearDashboardLogoConfiguration()` is only called from `.dwm-dashboard-logo-remove-action` click, not from close/overlay/escape

- [!] Focus trapped inside modal while open — **No focus trap implemented** for this modal
  > FIX NEEDED: `assets/js/modules/forms/settings-form.js` or `assets/js/modules/modals/modals.js`
  > CURRENT: No Tab key focus-trap logic for `#dwm-dashboard-logo-edit-modal`
  > REQUIRED: Tab/Shift+Tab cycles within focusable elements inside the modal when open

---

## Full Issue Log

| # | Status | Item | File | Line |
|---|--------|------|------|------|
| 1 | [!] | Preset swatch click handler missing — no JS updates hex/color-wheel on swatch click | `assets/js/modules/forms/settings-form.js` | none |
| 2 | [!] | RGBA preview `#dwm-title-rgba-preview` not updated live on slider/number input changes | `assets/js/modules/forms/settings-form.js` | 1083–1089 |
| 3 | [!] | Focus trap missing for `#dwm-title-format-modal` | `assets/js/modules/forms/settings-form.js` or `modals.js` | none |
| 4 | [!] | Focus trap missing for `#dwm-dashboard-logo-edit-modal` | `assets/js/modules/forms/settings-form.js` or `modals.js` | none |
| 5 | [~] | Apply button `.hide()` fallback does not remove `.active` class (incorrect close) | `assets/js/modules/forms/settings-form.js` | 1126 |
| 6 | [~] | Remove Logo does not set `dashboard_logo_enabled` to `0` | `assets/js/modules/forms/settings-form.js` | 625–632 |
| 7 | [!] | Preset swatch click — not updating RGBA preview (same missing handler) | `assets/js/modules/forms/settings-form.js` | none |
| 8 | [!] | Preset swatch click — checklist says updates RGBA preview; RGBA preview itself never updates live | `assets/js/modules/forms/settings-form.js` | none |

---
## JS settings-form.js Fixes Applied (Fix Agent)
- [FIX 1] syncLogoControlsVisibility() called at init — settings-form.js line 1154
- [FIX 2] clearDashboardLogoConfiguration() sets logo-enabled to 0 — settings-form.js line 630
- [FIX 3] Preset swatch click handler added — settings-form.js line 1081
- [FIX 4] RGBA preview live update added — settings-form.js line 1090
- [FIX 5] Apply button fallback close fixed — settings-form.js line 1142
