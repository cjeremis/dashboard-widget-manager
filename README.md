<div align="center">

<img src="assets/images/logo.png" alt="Dashboard Widget Manager" width="80" height="80">

# Dashboard Widget Manager

**Build powerful custom WordPress dashboard widgets with SQL, charts, and templates — no coding required.**

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue?logo=wordpress&logoColor=white)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL--2.0-green)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.0.0-orange)](https://github.com/topdevamerica/dashboard-widget-manager/releases)

[Features](#-features) · [Getting Started](#-getting-started) · [Display Modes](#-display-modes) · [Pro](#-pro-features) · [Security](#-security) · [Tools](#-tools)

</div>

---

## Overview

Dashboard Widget Manager lets you create unlimited custom widgets for the WordPress admin dashboard — powered by real SQL queries against your own database. No plugin bloat, no shortcodes on the front end, no CSV uploads. Just a clean widget editor, a point-and-click query builder, and your data exactly where you need it.

Each widget is a standalone unit with its own:
- **SQL query** — built visually or written by hand
- **Display mode** — table, bar, line, pie, or doughnut chart
- **HTML/PHP template** — auto-generated or fully custom
- **Scoped CSS & JavaScript** — styles and scripts that only load with that widget
- **Performance settings** — caching, execution limits, query logging

---

## ✨ Features

### 🧩 Widget Management
| Feature | Description |
|---|---|
| Unlimited widgets | Create as many custom widgets as your dashboard needs |
| Enable / disable | Toggle any widget on or off without deleting it |
| Named & described | Keep your list organized with names and descriptions |
| Native WP integration | Widgets appear as standard meta boxes — drag to reorder, hide/show with Screen Options |

### 🖥️ Display Modes
| Mode | Best for |
|---|---|
| **Table** | Lists, records, logs — auto-headers from SQL aliases |
| **Bar Chart** | Comparing values across categories |
| **Line Chart** | Trends and time-series data |
| **Pie Chart** | Proportional breakdowns (parts of a whole) |
| **Doughnut Chart** | Same as pie, easier to read when proportions are similar |

> All chart modes are powered by **Chart.js**, loaded automatically — no manual enqueue needed.

### 🧱 Visual Query Builder
Build complete SQL queries **without writing a single line of code** using the point-and-click Data tab:

- **Primary Table** — pick any WordPress-prefix table in your database
- **Column Selection** — check the columns you want; unchecked = `SELECT *`
- **Joins** — LEFT, INNER, or RIGHT JOIN with any table, configured via dropdowns
- **Filters (WHERE)** — 12 operators: `=`, `!=`, `<`, `>`, `<=`, `>=`, `LIKE`, `NOT LIKE`, `IN`, `NOT IN`, `IS NULL`, `IS NOT NULL`
- **Sorting** — multiple `ORDER BY` rules with ASC/DESC, priority follows list order
- **Row Limit** — optional cap of 1–100 rows
- **Chart Config** — label column, data column(s), optional title and legend toggle

> The builder auto-generates SQL in real time. Switch to the Query tab at any time to review or refine it.

### 🗄️ SQL Query Editor
For advanced users who prefer writing SQL directly:

```sql
SELECT
    post_status,
    COUNT(*) AS total
FROM {{posts}}
WHERE post_type = 'post'
GROUP BY post_status
ORDER BY total DESC
```

**Built-in query variables:**

| Variable | Resolves to |
|---|---|
| `{{table_prefix}}` | Your WP table prefix (e.g. `wp_`) |
| `{{posts}}` | `wp_posts` |
| `{{users}}` | `wp_users` |
| `{{comments}}` | `wp_comments` |
| `{{options}}` | `wp_options` |
| `{{postmeta}}` | `wp_postmeta` |
| `{{usermeta}}` | `wp_usermeta` |
| `{{current_user_id}}` | Currently logged-in user's ID |
| `{{site_url}}` | Your site's base URL |

- **Validate Query** — catches syntax errors before you save
- **Preview Results** — live paginated + searchable data preview inside the editor

### 📄 Template System
| Feature | Description |
|---|---|
| Auto-generated | Mode-aware starter templates generated automatically |
| Theme presets | 6 table themes, 6 chart palettes — applied with one click |
| HTML/PHP support | Full PHP with `$query_results` and `$widget_data` in scope |
| Variable tokens | `{{column_name}}`, `{{esc_html:col}}`, `{{esc_url:col}}`, `{{esc_attr:col}}` |
| Allow Editing toggle | Lock/unlock each tab independently; disabling reverts to auto-generated |

**Example template:**
```php
<ul class="my-widget-list">
    <?php foreach ( $query_results as $row ) : ?>
        <li>
            <strong><?php echo esc_html( $row['post_title'] ); ?></strong>
            <span><?php echo esc_html( $row['post_status'] ); ?></span>
        </li>
    <?php endforeach; ?>
</ul>
```

### 🎨 Custom CSS & JavaScript
- CSS is **automatically scoped** to the widget container — write plain selectors, no wrappers needed
- JavaScript runs with **jQuery available**, injected after widget HTML
- Each widget's script is **isolated in a function wrapper** — no variable collisions between widgets
- Auto-generated **theme-aware starter styles and scripts** on each new widget

### ⚡ Performance
| Setting | Description |
|---|---|
| Caching | WordPress transient-based cache per widget |
| Cache TTL | Configurable 0–3600 seconds |
| Auto-refresh | Polls for data changes at TTL interval; renders live without page reload |
| Manual refresh | Button shown when auto-refresh is off |
| Execution time limit | Abort slow queries automatically (1–60s per widget) |
| Query logging | Write execution times to `debug.log` for profiling |

---

## 🚀 Getting Started

### Requirements
- WordPress **6.0** or higher
- PHP **8.0** or higher
- MySQL 5.7+ or MariaDB 10.3+

### Installation

1. Download the latest release `.zip` from the [Releases page](https://github.com/topdevamerica/dashboard-widget-manager/releases)
2. In your WordPress admin, go to **Plugins → Add New → Upload Plugin**
3. Upload the zip and click **Install Now**, then **Activate**

### Create Your First Widget

```
Dashboard Widget Manager → Widget Manager → New Widget
```

1. **Name your widget** — give it a descriptive name (e.g. *Recent Posts by Status*)
2. **Build your query** — use the **Data tab** to pick a table, select columns, add filters
3. **Choose a display mode** — select Table, Bar, Line, Pie, or Doughnut on the **Display tab**
4. **Pick a theme preset** — open the **Template tab** and choose a theme; CSS and JS populate automatically
5. **Save** — your widget appears on the WordPress dashboard immediately

> **Tip:** Click **Preview Results** on the Query tab to see live data before saving.

---

## 📊 Display Modes

<table>
<tr>
<th>Mode</th>
<th>When to use</th>
<th>Config required</th>
</tr>
<tr>
<td>📋 <strong>Table</strong></td>
<td>Any list of records — posts, orders, users, logs</td>
<td>None — renders automatically from SQL aliases</td>
</tr>
<tr>
<td>📊 <strong>Bar Chart</strong></td>
<td>Comparing values across categories (e.g. posts per month)</td>
<td>Label column + 1–n data columns</td>
</tr>
<tr>
<td>📈 <strong>Line Chart</strong></td>
<td>Trends over time, sequential data</td>
<td>Label column (date/sequence) + 1–n data columns</td>
</tr>
<tr>
<td>🥧 <strong>Pie Chart</strong></td>
<td>Parts of a whole (e.g. post status distribution)</td>
<td>Label column + 1 data column</td>
</tr>
<tr>
<td>🍩 <strong>Doughnut</strong></td>
<td>Same as pie — better readability when proportions are close</td>
<td>Label column + 1 data column</td>
</tr>
</table>

**Example — Posts by status as a pie chart:**
```sql
SELECT post_status AS Status, COUNT(*) AS Count
FROM {{posts}}
WHERE post_type = 'post'
GROUP BY post_status
ORDER BY Count DESC
```
> Set **Label Column** → `Status`, **Data Column** → `Count`

---

## 🔒 Security

Security is built in at every layer — not bolted on.

| Protection | How it works |
|---|---|
| **SELECT-only enforcement** | Any query containing `INSERT`, `UPDATE`, `DELETE`, `DROP`, `TRUNCATE`, `ALTER`, `EXEC`, `GRANT`, or `REVOKE` is rejected at save time and blocked at runtime — always active, cannot be disabled |
| **Table Allowlist** | Configure which database tables widget queries can reference; sensitive tables can be excluded entirely (Settings → Security) |
| **Capability checks** | Every admin action requires `manage_options` — editors and authors cannot create or modify widgets |
| **Nonce verification** | Every AJAX request is verified server-side; invalid nonces return a 403 |
| **Prepared statements** | Query variables are passed through `$wpdb->prepare()` — no injection risk |

---

## 🛠️ Tools

All data tools live at **Dashboard Widget Manager → Tools**.

| Tool | Description |
|---|---|
| **Export** | Download all widgets and/or settings as a timestamped JSON file |
| **Import** | Drag-and-drop a JSON file to restore or migrate widgets; preview before importing |
| **Demo Data** | Load a curated set of example widgets to explore the plugin without writing queries |
| **Reset** | Permanently delete all widgets and/or reset settings to defaults — two-step confirmation required |

---

## ⭐ Pro Features

> **Dashboard Widget Manager Pro** is coming soon. The following features are planned for the Pro tier.

### 👥 Role-Based Visibility
Restrict any widget to specific user roles so only authorized users see sensitive data. Administrators always see all widgets regardless of role rules.

- Per-widget role selection (any registered WordPress role)
- Multi-role support
- Admin override — admins always see everything

### 🔗 Integrations *(Pro — Coming Soon)*

Pull data from external services directly into your dashboard widgets:

<table>
<tr><th>Category</th><th>Integration</th><th>What you can surface</th></tr>
<tr><td rowspan="2">CRM</td><td>Salesforce</td><td>Leads, contacts, opportunities</td></tr>
<tr><td>HubSpot</td><td>Contacts, deals, pipeline stages, revenue</td></tr>
<tr><td rowspan="3">Developer Tools</td><td>GitHub</td><td>Open issues, PRs, recent commits, CI status</td></tr>
<tr><td>Jira</td><td>Sprint progress, issue counts, assignee workload</td></tr>
<tr><td>Redis</td><td>Cache hit rates, memory usage, key statistics</td></tr>
</table>

---

## Requirements

| Requirement | Minimum |
|---|---|
| WordPress | 6.0 |
| PHP | 8.0 |
| MySQL | 5.7 |
| MariaDB | 10.3 |

---

## License

Dashboard Widget Manager is licensed under the [GNU General Public License v2.0](https://www.gnu.org/licenses/gpl-2.0.html).

---

<div align="center">

Built by [TopDevAmerica](https://topdevamerica.com)

</div>
