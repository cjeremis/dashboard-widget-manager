=== Dashboard Widget Manager ===
Contributors: topdevamerica
Tags: dashboard, admin, widgets, sql, charts
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build custom WordPress dashboard widgets with SQL queries, visual builder controls, table/chart display modes, and scoped template assets.

== Description ==

Dashboard Widget Manager lets administrators create custom WP Admin dashboard widgets powered by SQL queries.

Key capabilities:
- Visual query builder (tables, columns, joins, filters, sorting, limits)
- Direct SQL editor with query validation
- Table, bar, line, pie, and doughnut display modes
- Template rendering with scoped CSS/JS per widget
- Caching and query execution controls

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install via **Plugins > Add New > Upload Plugin**.
2. Activate the plugin.
3. Open **Widget Manager** in wp-admin to create your first widget.

== Frequently Asked Questions ==

= Who can manage widgets? =
Only users with `manage_options` capability can create, edit, or delete widgets.

= Does this plugin send data to external servers by default? =
No. By default, this plugin makes no remote requests. Remote requests only occur after explicit user action: enabling the Support Data Sharing opt-in setting, activating a pro license key, or submitting a support ticket with explicit consent.

= Does this plugin write to my database tables? =
No. Widget queries are restricted to read-only behavior for dashboard display.

== External Services ==

This plugin connects to external TopDevAmerica services for support and license validation.

= Support API =

Service base URL: `https://topdevamerica.com/wp-json/support-manager/v1`

Triggered when:
* A support ticket is submitted from the plugin support modal (requires explicit consent checkbox).
* The "Support Data Sharing" opt-in setting is enabled and support reply notifications are synced.

Data sent when submitting a ticket (only after explicit consent at submission time):
* Customer email address
* Ticket subject and description
* Priority and status
* Site URL
* WordPress version
* PHP version
* Active plugins list
* Active theme name and version
* Dashboard Widget Manager version
* Client IP address
* Browser user agent

Data sent during notification sync (only when the Support Data Sharing opt-in is enabled):
* Your account email address
* Site URL

Service terms and privacy: https://topdevamerica.com/terms | https://topdevamerica.com/privacy-policy

= License Validation API =

Service endpoint (site-local REST proxy): `/wp-json/dwm/v1/license/activate` and `/wp-json/dwm/v1/license/status`

Triggered when:
* Activating a Pro license key.
* Validating cached license status (after the 24-hour cache TTL expires).

Data sent:
* License key
* Site domain
* Plugin version

Service terms and privacy: https://topdevamerica.com/terms | https://topdevamerica.com/privacy-policy

== Privacy ==

By default, this plugin does not make any remote requests. Remote requests are only made when:

1. The "Support Data Sharing" opt-in is enabled in plugin settings (Settings > Support & Privacy). When enabled, the plugin contacts TopDevAmerica servers to sync support reply notifications. Data transmitted: your account email address and site URL. This setting is disabled by default and must be explicitly enabled.
2. A pro license key is activated (for license verification). Data transmitted: license key, site domain, and plugin version.
3. A support ticket is submitted. The user must check a required consent checkbox before submitting. The consent checkbox explicitly lists all diagnostic data that will be transmitted: site URL, active plugins, WordPress version, PHP version, active theme, IP address, and browser user agent. Submission is blocked both client-side and server-side if consent is not given.

== Changelog ==

= 1.0.0 =
* Initial public release.
