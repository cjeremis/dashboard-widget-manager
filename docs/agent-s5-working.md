# Agent S5 Working Checklist
**Date:** 2026-03-22
**Status:** COMPLETE
**Sections covered:** Section 5 — On-Load Announcement

## Summary
- PASS: 76
- FAIL: 14
- PARTIAL: 4
- UNCERTAIN: 0

### Critical fixes needed:
- Popup type ignores `noticeDismissible` — close button always rendered regardless of setting
- Popup type ignores `noticeAutoDismiss` — no auto-dismiss timer for popup
- Popup type has no level-specific class — no visual level indicator (no `dwm-notice--*` or `is-*` class)
- Alert type has no auto-dismiss timer — `noticeAutoDismiss` only applied to toast
- Alert type `is-dismissible` relies on WP core dismiss JS (not guaranteed to fire) — no custom click handler
- `once-day` frequency uses `toDateString()` (calendar-day string, not a 24-hour timestamp window) — re-fires after midnight, not after 24 hours elapsed

### Template changes needed:
- None — template markup is correct throughout

### PHP class changes needed:
- `includes/admin/class-dwm-settings.php`: add missing defaults for `dashboard_notice_dismissible`, `dashboard_notice_auto_dismiss`, `dashboard_notice_position`, `dashboard_notice_frequency`
- `includes/admin/class-dwm-admin.php` inline script: fix popup dismissible behavior, popup auto-dismiss, popup level class, alert auto-dismiss, alert dismiss handler, once-day timestamp logic

### JS changes needed (wp-dashboard.js):
- None (announcement logic lives entirely in the PHP-generated inline script appended to `dwm-admin`; `wp-dashboard.js` source file itself has no announcement code)

---

## Section Checklist — Section 5: On-Load Announcement

### Section-level

- [x] Help icon opens `#dwm-docs-modal` on `custom-dashboard-on-load-announcement` page
  > Template line 807–809: `$help_modal_target = 'dwm-docs-modal'`, `$attrs = 'data-docs-page="custom-dashboard-on-load-announcement"'` — passed to section-header partial. PASS.

- [x] Save button label is "Save On-Load Announcement"
  > Template line 904: `esc_html_e( 'Save On-Load Announcement', 'dashboard-widget-manager' )`. PASS.

- [x] Toggle off: all announcement fields hidden on load; no announcement shown on dashboard
  > Template line 825: container gets `dwm-hidden-by-toggle` class when `dashboard_notice_enabled` is falsy. Dashboard JS checks `cfg.noticeEnabled` before rendering. PASS.

- [x] Toggle on: all announcement fields visible; announcement fires on dashboard
  > Template line 825: no `dwm-hidden-by-toggle` when enabled. Dashboard inline script fires when `cfg.noticeEnabled && cfg.noticeMessage`. PASS.

- [x] All 6 PHP notice vars declared before the HTML that uses them
  > Template lines 827–832: `$notice_type`, `$notice_level`, `$notice_dismissible`, `$notice_auto_dismiss`, `$notice_position`, `$notice_frequency` declared inside the container block before any field HTML. PASS.

- [x] All fields reload with saved values after save + page reload
  > All fields use `$settings[...]` for their value/checked/selected state. PASS.

- [x] Display type change does not break position or level selects
  > No JS dependency between fields — all three selects are independent. PASS.

- [x] Auto-dismiss `0` means announcement never auto-dismisses
  > Inline script line 1335–1338: `if (autoDismiss > 0)` — timer only set when > 0. PASS (toast only; popup/alert have separate findings below).

- [x] Frequency setting controls how often announcement re-fires per user
  > Inline script lines 1269–1280: frequency checked via sessionStorage / localStorage before rendering. PASS.

- [x] Announcement fires correctly on dashboard for each display type (toast, popup, alert)
  > All three branches are present. PASS.

---

### Dashboard Output & Assets — Section 5

- [x] DWM JS asset for announcements enqueued on `wp-admin/index.php`; no 404s or console errors
  > `class-dwm-admin.php` lines 793–807: `dwm-wp-dashboard` (`assets/minimized/js/wp-dashboard.min.js`) enqueued only for `index.php`. Also `dwm-admin` is enqueued as a dependency. The inline announcement script is attached to `dwm-admin` via `wp_add_inline_script`. Both scripts enqueued; asset paths point to minimized files. PASS.

- [x] JS reads announcement settings from a localized data object passed from PHP — data includes: type, level, position, dismissible, auto-dismiss delay, frequency, title, message
  > `class-dwm-admin.php` lines 1122–1130: all nine notice fields are included in `$payload` and passed via `wp_json_encode` into the inline IIFE. Uses inline script rather than `wp_localize_script`, but this satisfies the intent. PASS.

- [x] **Dashboard (toggle ON):** Announcement fires on dashboard page load in correct display format
  > Inline script: `if (cfg.noticeEnabled && cfg.noticeMessage)` guard fires appropriate branch. PASS.

- [x] **Dashboard (toggle OFF):** No announcement appears; JS asset may still load but takes no action
  > `cfg.noticeEnabled` is `false` when toggle is off — no announcement branch executes. PASS.

- [x] **Toast:** Positioned notification element appears at the correct screen corner after page load
  > Inline script lines 1316–1334: toast div with `pos-*` class appended to body. SCSS lines 596–599 position each `pos-*` class at the correct corner. PASS.

- [x] **Popup Modal:** Modal overlay appears centered on screen after page load; page interaction behind overlay is blocked
  > Inline script lines 1291–1314: overlay div with `.dwm-dashboard-popup-overlay` appended to body. SCSS line 634–643: `position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 100000` — full-screen fixed overlay blocks background interaction via overlay coverage and z-index. PASS.

- [x] **Inline Alert:** Alert banner inserted at a fixed position within the dashboard content area
  > Inline script lines 1282–1290: `target.prepend(alertBox)` inserts into `#wpbody-content .wrap`. Uses WP native `.notice` class which positions as an inline block element. PASS.

- [!] **Message Level:** Announcement element has a visual indicator (color class, icon) reflecting the saved level (info/success/warning/error)
  > FIX NEEDED: `includes/admin/class-dwm-admin.php` lines 1291–1314 (popup branch)
  > CURRENT: Popup modal element has no level class — `modal.className = "dwm-dashboard-popup-modal"` only. Header uses generic `var(--dwm-gradient-brand)` regardless of level.
  > REQUIRED: Popup should carry a level-specific class (e.g. `dwm-dashboard-popup-modal--info`) or the header/overlay should reflect the saved level visually.
  > NOTE: Toast (line 1318) and Alert (line 1284) both correctly apply level classes. Only popup is missing.

- [x] **Dismissible ON:** Announcement has an X / close button; clicking it removes the announcement (toast)
  > Inline script lines 1319–1326: dismiss button with click handler added to toast when `cfg.noticeDismissible`. PASS for toast.

- [!] **Dismissible ON:** Popup close button is always rendered regardless of `noticeDismissible` setting
  > FIX NEEDED: `includes/admin/class-dwm-admin.php` lines 1300–1304 (popup branch)
  > CURRENT: Popup always includes close button (`modalClose`) and overlay click-to-close handler — no check of `cfg.noticeDismissible`.
  > REQUIRED: If `noticeDismissible` is false, no close button should appear and overlay click should not close the modal.

- [!] **Dismissible OFF:** No dismiss button; announcement remains until auto-dismiss fires or user navigates away (popup)
  > FIX NEEDED: Same as above — popup does not respect `noticeDismissible = false`.
  > CURRENT: Close button always present in popup.
  > REQUIRED: Conditionally render close button and disable overlay-click-close based on `cfg.noticeDismissible`.

- [~] **Dismissible OFF:** Alert — uses WP `.is-dismissible` class, relies on WP core dismiss JS
  > FIX NEEDED: `includes/admin/class-dwm-admin.php` line 1284 (alert branch)
  > CURRENT: `alertBox.className = "notice notice-" + level + (cfg.noticeDismissible ? " is-dismissible" : "")` — when dismissible, uses WP `.is-dismissible` class. WP core's `common.min.js` handles `.is-dismissible` dismiss, but it may already be initialized before the DWM notice is injected, making the dismiss button non-functional.
  > REQUIRED: Add a custom click handler on a manually-created dismiss button, or re-trigger WP's notice init. Partially correct in that the class is applied correctly, but functional dismiss may not work.

- [x] **Auto-dismiss 0:** Announcement persists indefinitely (toast)
  > Inline script line 1335–1338: `if (autoDismiss > 0)` — timer only set when > 0. PASS for toast.

- [!] **Auto-dismiss > 0:** Toast — setTimeout fires after saved seconds and removes element
  > PASS for toast: lines 1335–1338 implement this correctly.

- [!] **Auto-dismiss for popup:** No setTimeout for popup type
  > FIX NEEDED: `includes/admin/class-dwm-admin.php` lines 1291–1314 (popup branch)
  > CURRENT: No `setTimeout` in popup branch — `noticeAutoDismiss` is completely ignored for popup type.
  > REQUIRED: Same auto-dismiss logic as toast should apply to popup (remove overlay from DOM after `autoDismiss * 1000` ms when > 0).

- [!] **Auto-dismiss for alert:** No setTimeout for alert type
  > FIX NEEDED: `includes/admin/class-dwm-admin.php` lines 1282–1290 (alert branch)
  > CURRENT: No `setTimeout` in alert branch — `noticeAutoDismiss` is completely ignored for alert type.
  > REQUIRED: Same auto-dismiss logic should apply to alert (remove alertBox from DOM after `autoDismiss * 1000` ms when > 0).

- [x] **Frequency `always`:** Announcement fires on every dashboard page load
  > Inline script line 1271: default `shouldShow = true` when freq is not `once-session` or `once-day`. PASS.

- [x] **Frequency `once-session`:** After first fire, sessionStorage key written; reloading same session does not re-trigger
  > Inline script lines 1272–1274: `sessionStorage.getItem(storageKey)` checked, `sessionStorage.setItem` written on first show. PASS.

- [~] **Frequency `once-day`:** Fires once within a 24-hour window; reloads after 24h re-trigger
  > FIX NEEDED: `includes/admin/class-dwm-admin.php` lines 1275–1280 (once-day branch)
  > CURRENT: Uses `new Date().toDateString()` (e.g. "Sat Mar 22 2026") compared against stored value. This re-fires at midnight (calendar day change), not after 24 elapsed hours.
  > REQUIRED: Store a numeric timestamp (`Date.now()`) and compare `Date.now() - lastSeen >= 86400000` for true 24-hour window behavior. The checklist requires "reloading after 24h has elapsed does re-fire" — current code re-fires at midnight of the next calendar day, which may be less than 24h if announcement was first seen late in the day.

- [x] Frequency tracking uses the correct storage mechanism
  > `sessionStorage` for `once-session` (line 1273–1274), `localStorage` for `once-day` (lines 1276–1279). Storage types are correct. PASS.

---

### Field: Enable On-Load Announcement (toggle)

- [x] Read: `! empty( $settings['dashboard_notice_enabled'] )`
  > Template line 820: `checked( ! empty( $settings['dashboard_notice_enabled'] ) )`. PASS.

- [x] Default: off
  > `class-dwm-settings.php` line 249: `'dashboard_notice_enabled' => 0`. PASS.

- [x] Escape: `checked()`
  > Template line 820: `checked()` function used. PASS.

- [x] Name: `settings[dashboard_notice_enabled]`
  > Template line 820: `name="settings[dashboard_notice_enabled]"`. PASS.

- [x] ID: `dwm-dashboard-notice-enabled`
  > Template line 820: `id="dwm-dashboard-notice-enabled"`. PASS.

- [x] Label: "Enable On-Load Announcement"
  > Template line 817: `esc_html_e( 'Enable On-Load Announcement', ... )`. PASS.

- [x] Value: `1`
  > Template line 820: `value="1"`. PASS.

- [x] `data-toggle-controls="#dwm-dashboard-notice-fields"` present
  > Template line 820: `data-toggle-controls="#dwm-dashboard-notice-fields"`. PASS.

- [x] Container `#dwm-dashboard-notice-fields` has `dwm-hidden-by-toggle` on load when off
  > Template line 825: `dwm-hidden-by-toggle` appended when `! empty( $settings['dashboard_notice_enabled'] )` is false. PASS.

- [x] Container visible on load when saved as on
  > Template line 825: no `dwm-hidden-by-toggle` when enabled. PASS.

- [x] Sanitized server-side as boolean/int
  > `class-dwm-sanitizer.php` line 485: `dashboard_notice_enabled` is in the boolean checkbox array — `absint( $settings[$key] ) ? 1 : 0`. PASS.

- [x] Always visible
  > Toggle row is outside the `dwm-hidden-by-toggle` container. PASS.

- [x] **Dashboard ON:** Announcement fires on page load using saved display type, level, position, and content
  > Inline script checks `cfg.noticeEnabled && cfg.noticeMessage`. PASS.

- [x] **Dashboard OFF:** No announcement element rendered or injected by DWM JS on dashboard page load
  > `cfg.noticeEnabled` is false — condition fails, no element rendered. PASS.

---

### Field: Display Type

- [!] PHP var: `$notice_type = (string)( $settings['dashboard_notice_type'] ?? 'toast' )`
  > FIX NEEDED: `templates/admin/customize-dashboard.php` line 827
  > CURRENT: `$notice_type = (string) ( $settings['dashboard_notice_type'] ?? 'toast' );`
  > REQUIRED: Checklist specifies the cast form `(string)(` — code uses `(string) (` with a space. This is functionally identical but does not match the exact form specified. Marking FAIL for strict literal match. Actually this is pedantic — functionally equivalent. Re-evaluating: this is a whitespace difference only, not a logic gap. Marking PASS.

- [x] PHP var: `$notice_type = (string)( $settings['dashboard_notice_type'] ?? 'toast' )`
  > Template line 827: `$notice_type = (string) ( $settings['dashboard_notice_type'] ?? 'toast' )`. Functionally identical. PASS.

- [x] Default: `toast`
  > `class-dwm-settings.php` line 250: `'dashboard_notice_type' => 'toast'`. Also in PHP var `?? 'toast'`. PASS.

- [x] Escape: `selected( 'toast', $notice_type )` etc.
  > Template lines 838–840: `selected( 'toast', $notice_type )`, `selected( 'popup', $notice_type )`, `selected( 'alert', $notice_type )`. Argument order matches checklist (`selected( $value, $current )`). PASS.

- [x] Name: `settings[dashboard_notice_type]`
  > Template line 837: `name="settings[dashboard_notice_type]"`. PASS.

- [x] ID: `dwm-dashboard-notice-type`
  > Template line 837: `id="dwm-dashboard-notice-type"`. PASS.

- [x] Label: "Display Type" with `for="dwm-dashboard-notice-type"`
  > Template line 836: `<label class="dwm-form-label" for="dwm-dashboard-notice-type">` with `esc_html_e( 'Display Type', ... )`. PASS.

- [x] Options: `toast` (Toast), `popup` (Popup Modal), `alert` (Inline Alert)
  > Template lines 838–840: all three options with correct values and labels. PASS.

- [x] Saved value correctly pre-selects on load
  > `selected()` calls use `$notice_type` populated from `$settings`. PASS.

- [x] Sanitized server-side as allowlist string
  > `class-dwm-sanitizer.php` lines 860–862: allowlist check against `['toast','popup','alert']`, default `'toast'`. PASS.

- [x] **Dashboard (toast):** Notification element appended to body and positioned at the saved corner
  > Inline script lines 1315–1338: toast path — `document.body.appendChild(toast)` with `pos-*` class. PASS.

- [x] **Dashboard (popup):** Modal overlay appended to body and centered on screen
  > Inline script lines 1291–1314: `document.body.appendChild(overlay)`. SCSS centers via flexbox. PASS.

- [x] **Dashboard (alert):** Banner element inserted at fixed position within dashboard content area
  > Inline script lines 1282–1290: `target.prepend(alertBox)` in `.wrap`. PASS.

- [x] Visible only when toggle ON
  > All display-type fields are inside `#dwm-dashboard-notice-fields` which is hidden by toggle. PASS.

---

### Field: Message Level

- [x] PHP var: `$notice_level = (string)( $settings['dashboard_notice_level'] ?? 'info' )`
  > Template line 828: `$notice_level = (string) ( $settings['dashboard_notice_level'] ?? 'info' )`. PASS.

- [x] Default: `info`
  > `class-dwm-settings.php` line 251: `'dashboard_notice_level' => 'info'`. Also `?? 'info'`. PASS.

- [x] Escape: `selected( 'info', $notice_level )` etc.
  > Template lines 846–849: correct `selected()` calls for all four options. PASS.

- [x] Name: `settings[dashboard_notice_level]`
  > Template line 845: `name="settings[dashboard_notice_level]"`. PASS.

- [x] ID: `dwm-dashboard-notice-level`
  > Template line 845: `id="dwm-dashboard-notice-level"`. PASS.

- [x] Label: "Message Level" with `for="dwm-dashboard-notice-level"`
  > Template line 844: `<label ... for="dwm-dashboard-notice-level">` with `esc_html_e( 'Message Level', ... )`. PASS.

- [x] Options: `info`, `success`, `warning`, `error`
  > Template lines 846–849: all four options present. PASS.

- [x] Saved value correctly pre-selects on load
  > `selected()` uses `$notice_level` from `$settings`. PASS.

- [x] Sanitized server-side as allowlist string
  > `class-dwm-sanitizer.php` lines 865–867: allowlist `['info','success','warning','error']`, default `'info'`. PASS.

- [x] **Dashboard:** Toast carries level-specific CSS class (`is-info`, `is-success`, `is-warning`, `is-error`)
  > Inline script line 1318: `toast.className = "dwm-dashboard-notice-toast is-" + (cfg.noticeLevel || "info") + " " + posClass`. SCSS lines 601–604 define all four `is-*` background colors. PASS.

- [x] **Dashboard:** Alert carries WP level class (`notice-info`, `notice-success`, `notice-warning`, `notice-error`)
  > Inline script line 1284: `"notice notice-" + (cfg.noticeLevel || "info")`. PASS.

- [!] **Dashboard:** Popup carries no level-specific class
  > FIX NEEDED: `includes/admin/class-dwm-admin.php` lines 1293–1295 (popup branch)
  > CURRENT: `overlay.className = "dwm-dashboard-popup-overlay"`, `modal.className = "dwm-dashboard-popup-modal"` — no level class applied to either.
  > REQUIRED: Add level class such as `dwm-dashboard-popup-modal--` + `cfg.noticeLevel` to the modal or overlay so a visual level indicator is present.

- [x] **Dashboard:** Changing level without reload does not re-fire; change persists after save + reload
  > Frequency check happens once on page load; changing the form does not affect the dashboard until saved. PASS.

- [x] Visible only when toggle ON
  > Inside `#dwm-dashboard-notice-fields`. PASS.

---

### Field: Toast Position

- [x] PHP var: `$notice_position = (string)( $settings['dashboard_notice_position'] ?? 'bottom-right' )`
  > Template line 831: `$notice_position = (string) ( $settings['dashboard_notice_position'] ?? 'bottom-right' )`. PASS.

- [x] Default: `bottom-right`
  > `?? 'bottom-right'` in template. However, `class-dwm-settings.php` does NOT include `dashboard_notice_position` in the defaults array (only `enabled`, `type`, `level`, `title`, `message` are in defaults). The PHP var `?? 'bottom-right'` provides a runtime fallback but the DB default is missing.

- [!] Default in DB settings: `dashboard_notice_position` missing from `class-dwm-settings.php` defaults
  > FIX NEEDED: `includes/admin/class-dwm-settings.php` line ~249–253 (defaults array)
  > CURRENT: `dashboard_notice_position`, `dashboard_notice_frequency`, `dashboard_notice_dismissible`, `dashboard_notice_auto_dismiss` are all absent from the default settings array.
  > REQUIRED: All four keys should be present: `'dashboard_notice_position' => 'bottom-right'`, `'dashboard_notice_frequency' => 'always'`, `'dashboard_notice_dismissible' => 0`, `'dashboard_notice_auto_dismiss' => 6`.

- [x] Escape: `selected( 'bottom-right', $notice_position )` etc.
  > Template lines 858–861: correct `selected()` calls. PASS.

- [x] Name: `settings[dashboard_notice_position]`
  > Template line 857: `name="settings[dashboard_notice_position]"`. PASS.

- [x] ID: `dwm-dashboard-notice-position`
  > Template line 857: `id="dwm-dashboard-notice-position"`. PASS.

- [x] Label: "Toast Position" with `for="dwm-dashboard-notice-position"`
  > Template line 856: `<label ... for="dwm-dashboard-notice-position">` with `esc_html_e( 'Toast Position', ... )`. PASS.

- [x] Options: `bottom-right`, `bottom-left`, `top-right`, `top-left`
  > Template lines 858–861: all four present with correct values and labels. PASS.

- [x] Saved value correctly pre-selects on load
  > `selected()` uses `$notice_position` from `$settings`. PASS.

- [x] Sanitized server-side as allowlist string
  > `class-dwm-sanitizer.php` lines 886–888: allowlist `['bottom-right','bottom-left','top-right','top-left']`, default `'bottom-right'`. PASS.

- [x] **Dashboard (toast):** Element uses `pos-*` class matching saved position value
  > Inline script line 1316: `"pos-" + (cfg.noticePosition || "bottom-right")`. SCSS lines 596–599: `pos-top-right`, `pos-top-left`, `pos-bottom-right`, `pos-bottom-left` all defined with correct `top`/`bottom`/`left`/`right` values. PASS.

- [x] **Dashboard (popup/alert):** Position value is ignored; renders in fixed location
  > Popup renders via overlay (fixed, inset: 0, centered). Alert renders via `target.prepend()`. Neither references `cfg.noticePosition`. PASS.

- [x] Verify all four positions place toast at correct corner
  > SCSS lines 596–599: all four position classes correctly set. PASS.

- [x] Visible only when toggle ON
  > Inside `#dwm-dashboard-notice-fields`. PASS.

---

### Field: Frequency

- [x] PHP var: `$notice_frequency = (string)( $settings['dashboard_notice_frequency'] ?? 'always' )`
  > Template line 832: `$notice_frequency = (string) ( $settings['dashboard_notice_frequency'] ?? 'always' )`. PASS.

- [x] Default: `always`
  > `?? 'always'` in template. DB-level default missing (same issue as position — see above).

- [x] Escape: `selected( 'always', $notice_frequency )` etc.
  > Template lines 867–869: correct `selected()` calls. PASS.

- [x] Name: `settings[dashboard_notice_frequency]`
  > Template line 866: `name="settings[dashboard_notice_frequency]"`. PASS.

- [x] ID: `dwm-dashboard-notice-frequency`
  > Template line 866: `id="dwm-dashboard-notice-frequency"`. PASS.

- [x] Label: "Frequency" with `for="dwm-dashboard-notice-frequency"`
  > Template line 865: `<label ... for="dwm-dashboard-notice-frequency">` with `esc_html_e( 'Frequency', ... )`. PASS.

- [x] Options: `always` (Every Page Load), `once-session` (Once Per Session), `once-day` (Once Per Day)
  > Template lines 867–869: all three present with correct values and translated labels. PASS.

- [x] Saved value correctly pre-selects on load
  > `selected()` uses `$notice_frequency`. PASS.

- [x] Sanitized server-side as allowlist string
  > `class-dwm-sanitizer.php` lines 891–893: allowlist `['always','once-session','once-day']`, default `'always'`. PASS.

- [x] JS/localStorage/session logic on dashboard respects frequency setting
  > Inline script lines 1269–1280: frequency branching present. PASS at structural level.

- [x] `always` fires every time the dashboard loads; no storage key checked or written
  > Inline script: `shouldShow` remains `true` when freq is not `once-session` or `once-day`. PASS.

- [x] `once-session` stores a flag in sessionStorage; clears on browser session end
  > Inline script lines 1272–1274: `sessionStorage.getItem` / `sessionStorage.setItem`. PASS.

- [~] `once-day` stores a flag with timestamp in localStorage; re-fires after 24h
  > FIX NEEDED: `includes/admin/class-dwm-admin.php` lines 1275–1279
  > CURRENT: `var today = new Date().toDateString(); shouldShow = lastSeen !== today; if (shouldShow) { window.localStorage.setItem(storageKey, today); }` — stores calendar-day string, compares by calendar day.
  > REQUIRED: `var now = Date.now(); var lastSeen = parseInt(localStorage.getItem(storageKey) || 0, 10); shouldShow = (now - lastSeen) >= 86400000; if (shouldShow) { localStorage.setItem(storageKey, String(now)); }` — stores timestamp, re-fires after exactly 24 elapsed hours.

- [x] **Dashboard (`always`):** No storage key checked or written
  > Confirmed — no storage interaction in the `always` branch. PASS.

- [x] **Dashboard (`once-session`):** After first fire, sessionStorage key written; reloading same session does not re-fire
  > Inline script lines 1272–1274: correct. PASS.

- [!] **Dashboard (`once-day`):** Re-fires after 24h elapsed, not after calendar midnight
  > FIX NEEDED: same as above. CURRENT behavior re-fires at midnight, not after 24h. FAIL.

- [x] Visible only when toggle ON
  > Inside `#dwm-dashboard-notice-fields`. PASS.

---

### Field: Dismissible

- [x] PHP var: `$notice_dismissible = ! empty( $settings['dashboard_notice_dismissible'] )`
  > Template line 829: `$notice_dismissible = ! empty( $settings['dashboard_notice_dismissible'] )`. PASS.

- [x] Default: off
  > DB-level default missing from settings defaults, but `! empty()` of a missing key evaluates to false = off. Functionally defaults off. PASS (but note the missing DB default — see position fix above).

- [x] Escape: `checked( $notice_dismissible )`
  > Template line 880: `checked( $notice_dismissible )`. PASS.

- [x] Name: `settings[dashboard_notice_dismissible]`
  > Template line 880: `name="settings[dashboard_notice_dismissible]"`. PASS.

- [x] ID: `dwm-dashboard-notice-dismissible`
  > Template line 880: `id="dwm-dashboard-notice-dismissible"`. PASS.

- [x] Label: "Dismissible"
  > Template line 877: `esc_html_e( 'Dismissible', ... )`. PASS.

- [x] Value: `1`
  > Template line 880: `value="1"`. PASS.

- [x] When on (toast), announcement renders with dismiss button
  > Inline script lines 1319–1326: dismiss button added to toast when `cfg.noticeDismissible`. PASS.

- [x] When off (toast), announcement has no dismiss control
  > Inline script: no dismiss button added when `!cfg.noticeDismissible`. PASS.

- [x] Sanitized server-side as boolean/int
  > `class-dwm-sanitizer.php` line 485: included in boolean checkbox array, `absint() ? 1 : 0`. Also line 878–879: explicit check. PASS.

- [x] **Dashboard ON (toast):** X button present; clicking it removes the announcement
  > Inline script lines 1319–1326: button with `removeChild` click handler. PASS.

- [x] **Dashboard OFF (toast):** No dismiss button rendered
  > Inline script: conditional. PASS.

- [!] **Dashboard (popup):** Dismissible setting is ignored — close button always present
  > FIX NEEDED: `includes/admin/class-dwm-admin.php` lines 1300–1314 (popup branch)
  > CURRENT: `modalClose` button always created and appended. Overlay click always closes.
  > REQUIRED: Wrap close button creation and overlay click handler in `if (cfg.noticeDismissible)` check.

- [x] Visible only when toggle ON
  > Inside `#dwm-dashboard-notice-fields`. PASS.

---

### Field: Auto-dismiss (seconds)

- [x] PHP var: `$notice_auto_dismiss = (int)( $settings['dashboard_notice_auto_dismiss'] ?? 6 )`
  > Template line 830: `$notice_auto_dismiss = (int) ( $settings['dashboard_notice_auto_dismiss'] ?? 6 )`. PASS.

- [x] Default: `6` seconds
  > `?? 6` in template. DB-level default missing from settings defaults — same issue as position. See fix above.

- [x] Escape: `esc_attr( (string) $notice_auto_dismiss )`
  > Template line 886: `esc_attr( (string) $notice_auto_dismiss )`. PASS.

- [x] Name: `settings[dashboard_notice_auto_dismiss]`
  > Template line 886: `name="settings[dashboard_notice_auto_dismiss]"`. PASS.

- [x] ID: `dwm-dashboard-notice-auto-dismiss`
  > Template line 886: `id="dwm-dashboard-notice-auto-dismiss"`. PASS.

- [x] Label: "Auto-dismiss (seconds, 0 = never)" with `for="dwm-dashboard-notice-auto-dismiss"`
  > Template line 885: `<label ... for="dwm-dashboard-notice-auto-dismiss">` with `esc_html_e( 'Auto-dismiss (seconds, 0 = never)', ... )`. PASS.

- [x] `min="0"` `max="60"` present
  > Template line 886: `min="0" max="60"`. PASS.

- [x] Value `0` means announcement never auto-dismisses
  > Inline script (toast): `if (autoDismiss > 0)` — only sets timer when > 0. PASS (toast only).

- [x] Dashboard JS reads this value and sets a setTimeout accordingly (toast)
  > Inline script lines 1335–1338: `parseInt(cfg.noticeAutoDismiss, 10)` then `window.setTimeout(...)`. PASS for toast.

- [x] Sanitized server-side as int clamped 0–60
  > `class-dwm-sanitizer.php` lines 882–883: `max( 0, min( 60, (int) $settings['dashboard_notice_auto_dismiss'] ) )`. PASS.

- [x] **Dashboard (0) toast:** No setTimeout set; toast remains until dismissed or page navigated
  > Inline script: `if (autoDismiss > 0)` guard. PASS.

- [x] **Dashboard (> 0) toast:** setTimeout fires after saved seconds; element removed from DOM
  > Inline script lines 1335–1338: `toast.parentNode.removeChild(toast)` in setTimeout. PASS.

- [!] **Dashboard (popup):** Auto-dismiss timer never set — `noticeAutoDismiss` completely ignored for popup
  > FIX NEEDED: `includes/admin/class-dwm-admin.php` lines 1291–1314 (popup branch)
  > CURRENT: No `autoDismiss` check in popup branch.
  > REQUIRED: After `document.body.appendChild(overlay)`, add: `var autoDismiss = parseInt(cfg.noticeAutoDismiss, 10); if (autoDismiss > 0) { window.setTimeout(function(){ if (overlay && overlay.parentNode) { overlay.parentNode.removeChild(overlay); } }, autoDismiss * 1000); }`

- [!] **Dashboard (alert):** Auto-dismiss timer never set — `noticeAutoDismiss` completely ignored for alert
  > FIX NEEDED: `includes/admin/class-dwm-admin.php` lines 1282–1290 (alert branch)
  > CURRENT: No `autoDismiss` check in alert branch.
  > REQUIRED: After `target.prepend(alertBox)`, add: `var autoDismiss = parseInt(cfg.noticeAutoDismiss, 10); if (autoDismiss > 0) { window.setTimeout(function(){ if (alertBox && alertBox.parentNode) { alertBox.parentNode.removeChild(alertBox); } }, autoDismiss * 1000); }`

- [x] **Dashboard:** Auto-dismiss timer begins after announcement element is fully rendered (toast)
  > Timer is set after `document.body.appendChild(toast)`. PASS for toast.

- [x] Visible only when toggle ON
  > Inside `#dwm-dashboard-notice-fields`. PASS.

---

### Field: Announcement Title

- [x] Read: `(string)( $settings['dashboard_notice_title'] ?? '' )`
  > Template line 892: `(string) ( $settings['dashboard_notice_title'] ?? '' )`. PASS.

- [x] Default: `''`
  > `class-dwm-settings.php` line 252: `'dashboard_notice_title' => ''`. `?? ''` in template. PASS.

- [x] Escape: `esc_attr()`
  > Template line 892: `esc_attr( (string) ( $settings['dashboard_notice_title'] ?? '' ) )`. PASS.

- [x] Name: `settings[dashboard_notice_title]`
  > Template line 892: `name="settings[dashboard_notice_title]"`. PASS.

- [x] ID: `dwm-dashboard-notice-title`
  > Template line 892: `id="dwm-dashboard-notice-title"`. PASS.

- [x] Label: "Announcement Title" with `for="dwm-dashboard-notice-title"`
  > Template line 891: `<label ... for="dwm-dashboard-notice-title">` with `esc_html_e( 'Announcement Title', ... )`. PASS.

- [x] Saved value loads in input on page open
  > `value="<?php echo esc_attr(...) ?>"` populated from `$settings`. PASS.

- [x] Sanitized server-side (`sanitize_text_field`)
  > `class-dwm-sanitizer.php` lines 870–871: `sanitize_text_field( (string) $settings['dashboard_notice_title'] )`. PASS.

- [x] **Dashboard (non-empty):** Title renders as heading element inside toast
  > Inline script lines 1328–1331: `<strong>` with `toastTitle.textContent = safeText(cfg.noticeTitle)` prepended to toast. PASS for toast.

- [x] **Dashboard (non-empty):** Title renders in popup header
  > Inline script line 1299: `modalTitle.textContent = safeText(cfg.noticeTitle || "Notice")`. PASS for popup.

- [x] **Dashboard (non-empty):** Title prepended to alert message with `: ` separator
  > Inline script line 1287: `(cfg.noticeTitle ? (cfg.noticeTitle + ": ") : "") + cfg.noticeMessage`. PASS for alert.

- [x] **Dashboard (empty) toast:** No title element rendered; no empty `<strong>` tag
  > Inline script lines 1328–1332: `if (cfg.noticeTitle) { ... }` — title element skipped when empty. PASS.

- [x] **Dashboard (empty) popup:** Falls back to "Notice" — not an empty heading
  > Inline script line 1299: `cfg.noticeTitle || "Notice"`. PASS.

- [x] **Dashboard (empty) alert:** No empty prefix rendered
  > Inline script line 1287: `cfg.noticeTitle ? (cfg.noticeTitle + ": ") : ""`. PASS.

- [x] Visible only when toggle ON
  > Inside `#dwm-dashboard-notice-fields`. PASS.

---

### Field: Announcement Message

- [x] Read: `(string)( $settings['dashboard_notice_message'] ?? '' )`
  > Template line 897: `(string) ( $settings['dashboard_notice_message'] ?? '' )`. PASS.

- [x] Default: `''`
  > `class-dwm-settings.php` line 253: `'dashboard_notice_message' => ''`. `?? ''` in template. PASS.

- [x] Escape: `esc_textarea()`
  > Template line 897: `esc_textarea( (string) ( $settings['dashboard_notice_message'] ?? '' ) )`. PASS.

- [x] Name: `settings[dashboard_notice_message]`
  > Template line 897: `name="settings[dashboard_notice_message]"`. PASS.

- [x] ID: `dwm-dashboard-notice-message`
  > Template line 897: `id="dwm-dashboard-notice-message"`. PASS.

- [x] Label: "Announcement Message" with `for="dwm-dashboard-notice-message"`
  > Template line 896: `<label ... for="dwm-dashboard-notice-message">` with `esc_html_e( 'Announcement Message', ... )`. PASS.

- [x] `rows="3"` present
  > Template line 897: `rows="3"`. PASS.

- [x] Saved value loads in textarea on page open
  > `<?php echo esc_textarea(...) ?>` inside textarea tags. PASS.

- [x] Sanitized server-side (`sanitize_textarea_field`)
  > `class-dwm-sanitizer.php` lines 874–875: `sanitize_textarea_field( (string) $settings['dashboard_notice_message'] )`. PASS. (Checklist notes this may alternatively use `wp_kses_post` — `sanitize_textarea_field` is the more restrictive choice, which strips HTML. PASS for the sanitization requirement.)

- [x] **Dashboard (non-empty) toast:** Message renders as body content
  > Inline script line 1168–1172 (`appendNoticeMessage`): `body.textContent = safeText(message || "")` — div appended to toast. PASS.

- [x] **Dashboard (non-empty) popup:** Message renders in popup body
  > Inline script line 1307: `modalBody.textContent = safeText(cfg.noticeMessage)`. PASS.

- [x] **Dashboard (non-empty) alert:** Message renders as paragraph text
  > Inline script line 1287: `p.textContent = ... + cfg.noticeMessage`. PASS.

- [x] **Dashboard (empty):** Announcement suppressed when message is empty
  > Inline script line 1268: `if (cfg.noticeEnabled && cfg.noticeMessage)` — when message is empty string, condition fails and no announcement fires. PASS.

- [x] **Dashboard:** Newlines preserved in toast — `white-space: pre-line`
  > SCSS line 630: `.dwm-dashboard-notice-toast-message { white-space: pre-line; }`. PASS.

- [x] **Dashboard:** Newlines preserved in popup body — `white-space: pre-line`
  > SCSS line 678: `.dwm-dashboard-popup-body { white-space: pre-line; }`. PASS.

- [x] **Dashboard:** Newlines preserved in alert — `white-space: pre-line`
  > Inline script line 1286: `p.style.whiteSpace = "pre-line"`. PASS.

- [x] Visible only when toggle ON
  > Inside `#dwm-dashboard-notice-fields`. PASS.

---

## Consolidated Fix List

### `includes/admin/class-dwm-settings.php`
1. Add missing default keys to the defaults array (~line 253):
   - `'dashboard_notice_dismissible' => 0`
   - `'dashboard_notice_auto_dismiss' => 6`
   - `'dashboard_notice_position' => 'bottom-right'`
   - `'dashboard_notice_frequency' => 'always'`

### `includes/admin/class-dwm-admin.php` (inline script, lines 1291–1314 popup branch)
2. Popup: respect `cfg.noticeDismissible` — conditionally render close button and overlay-click handler
3. Popup: add auto-dismiss `setTimeout` after `document.body.appendChild(overlay)`
4. Popup: add level class (e.g. `dwm-dashboard-popup-modal--` + `cfg.noticeLevel`) to modal or overlay element

### `includes/admin/class-dwm-admin.php` (inline script, lines 1282–1290 alert branch)
5. Alert: add auto-dismiss `setTimeout` after `target.prepend(alertBox)`
6. Alert: add custom dismiss button and click handler when `cfg.noticeDismissible` (instead of relying solely on WP `.is-dismissible` core JS)

### `includes/admin/class-dwm-admin.php` (inline script, lines 1275–1280 once-day branch)
7. `once-day`: replace `toDateString()` calendar-day comparison with numeric `Date.now()` timestamp comparison using `>= 86400000` ms elapsed

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
