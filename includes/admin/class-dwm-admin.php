<?php
/**
 * Admin Class
 *
 * Handles admin asset enqueuing.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class.
 *
 * Manages admin styles and scripts.
 */
class DWM_Admin {

	use DWM_Singleton;

	/**
	 * Enqueue admin styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_styles( $hook ) {
		// Only load on plugin pages.
		if ( ! $this->is_plugin_page( $hook ) ) {
			return;
		}

		// Base admin styles.
		wp_enqueue_style(
			'dwm-admin',
			DWM_PLUGIN_URL . 'assets/minimized/css/global.min.css',
			array(),
			DWM_VERSION,
			'all'
		);

		// Inject logo URL as CSS variable.
		$logo_url = DWM_PLUGIN_URL . 'assets/images/logo.png';
		wp_add_inline_style( 'dwm-admin', ':root { --dwm-logo-url: url("' . esc_url( $logo_url ) . '"); }' );

		// Page-specific styles.
		if ( strpos( $hook, 'dashboard-widget-manager' ) !== false && strpos( $hook, 'dwm-' ) === false ) {
			wp_enqueue_style(
				'dwm-dashboard',
				DWM_PLUGIN_URL . 'assets/minimized/css/dashboard.min.css',
				array( 'dwm-admin' ),
				DWM_VERSION,
				'all'
			);

			// CodeMirror for code editing.
			wp_enqueue_code_editor( array( 'type' => 'application/x-httpd-php' ) );
			wp_enqueue_style( 'wp-codemirror' );
		}

		if ( 'widget-manager_page_dwm-settings' === $hook ) {
			wp_enqueue_style(
				'dwm-settings',
				DWM_PLUGIN_URL . 'assets/minimized/css/settings.min.css',
				array( 'dwm-admin' ),
				DWM_VERSION,
				'all'
			);
		}

		if ( 'widget-manager_page_dwm-tools' === $hook ) {
			wp_enqueue_style(
				'dwm-tools',
				DWM_PLUGIN_URL . 'assets/minimized/css/tools.min.css',
				array( 'dwm-admin' ),
				DWM_VERSION,
				'all'
			);
		}
	}

	/**
	 * Inject a "New Widget" button next to the WordPress Dashboard H1.
	 *
	 * If no widgets exist, the button links directly to the DWM dashboard.
	 * If widgets exist, the button opens a picker modal.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function inject_dashboard_button( $hook ) {
		if ( 'index.php' !== $hook ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$data    = DWM_Data::get_instance();
		$widgets = $data->get_widgets();

		$create_url   = esc_url( admin_url( 'admin.php?page=dashboard-widget-manager&action=create' ) );
		$button_label = esc_js( __( 'New Widget', 'dashboard-widget-manager' ) );

		wp_add_inline_style( 'wp-admin', '
			[id^="dwm_widget_"] .inside {
				padding: 0;
				margin: 6px;
			}
			.dwm-dashboard-new-widget-btn {
				display: inline-flex;
				align-items: center;
				gap: 4px;
				padding: 6px 14px;
				font-size: 13px;
				font-weight: 500;
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
				color: #ffffff !important;
				border: none;
				border-radius: 4px;
				cursor: pointer;
				text-decoration: none;
				vertical-align: middle;
				margin-left: 8px;
				line-height: 1.4;
				transition: box-shadow 0.2s ease, transform 0.15s ease;
			}
			.dwm-dashboard-new-widget-btn:hover {
				color: #ffffff !important;
				box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
				transform: translateY(-1px);
			}
			.dwm-dashboard-new-widget-btn .dashicons {
				font-size: 16px;
				width: 16px;
				height: 16px;
				line-height: 1;
			}
			/* Widget Picker Modal */
			#dwm-widget-picker-modal {
				display: none;
				position: fixed;
				inset: 0;
				z-index: 999999;
				align-items: center;
				justify-content: center;
			}
			#dwm-widget-picker-modal.active { display: flex; }
			#dwm-widget-picker-modal .dwm-picker-overlay {
				position: absolute;
				inset: 0;
				background: rgba(0,0,0,0.7);
				backdrop-filter: blur(4px);
			}
			#dwm-widget-picker-modal .dwm-picker-content {
				position: relative;
				background: #ffffff;
				border-radius: 16px;
				box-shadow: 0 25px 50px rgba(0,0,0,0.25);
				z-index: 10;
				width: 90%;
				max-width: 500px;
				max-height: 85vh;
				display: flex;
				flex-direction: column;
				overflow: hidden;
				animation: dwmPickerSlideIn 0.3s ease-out;
			}
			@keyframes dwmPickerSlideIn {
				from { opacity: 0; transform: translateY(-20px) scale(0.97); }
				to   { opacity: 1; transform: translateY(0) scale(1); }
			}
			#dwm-widget-picker-modal .dwm-picker-header {
				padding: 16px 24px;
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
				display: flex;
				align-items: center;
				justify-content: space-between;
				flex-shrink: 0;
			}
			#dwm-widget-picker-modal .dwm-picker-header h2 {
				color: #fff;
				font-size: 17px;
				font-weight: 700;
				margin: 0;
				display: flex;
				align-items: center;
				gap: 8px;
				line-height: 1.3;
			}
			#dwm-widget-picker-modal .dwm-picker-header .dashicons {
				font-size: 20px;
				width: 20px;
				height: 20px;
			}
			#dwm-picker-close {
				background: transparent;
				border: none;
				color: rgba(255,255,255,0.8);
				font-size: 24px;
				line-height: 1;
				cursor: pointer;
				padding: 4px;
				border-radius: 6px;
				transition: all 0.2s;
			}
			#dwm-picker-close:hover {
				color: #fff;
				background: rgba(255,255,255,0.15);
				transform: rotate(90deg);
			}
			#dwm-widget-picker-modal .dwm-picker-body {
				padding: 24px;
				overflow-y: auto;
				flex: 1;
			}
			.dwm-picker-step { display: none; }
			.dwm-picker-step.active { display: block; }
			.dwm-picker-choices {
				display: grid;
				grid-template-columns: 1fr 1fr;
				gap: 12px;
			}
			.dwm-picker-choice {
				display: flex;
				flex-direction: column;
				align-items: center;
				gap: 10px;
				padding: 20px 16px;
				border: 2px solid #e5e7eb;
				border-radius: 12px;
				background: #fff;
				cursor: pointer;
				text-align: center;
				transition: all 0.2s;
				text-decoration: none;
				color: #1f2937;
			}
			.dwm-picker-choice:hover {
				border-color: #667eea;
				background: #f5f3ff;
				color: #1f2937;
				box-shadow: 0 4px 12px rgba(102,126,234,0.15);
				transform: translateY(-2px);
			}
			.dwm-picker-choice .dashicons {
				font-size: 28px;
				width: 28px;
				height: 28px;
				color: #667eea;
			}
			.dwm-picker-choice strong {
				font-size: 14px;
				font-weight: 600;
				display: block;
			}
			.dwm-picker-choice span:not(.dashicons) {
				font-size: 12px;
				color: #6b7280;
				display: block;
				line-height: 1.4;
			}
			.dwm-picker-back {
				background: none;
				border: none;
				color: #6b7280;
				font-size: 13px;
				cursor: pointer;
				padding: 0;
				margin-bottom: 16px;
				display: flex;
				align-items: center;
				gap: 4px;
			}
			.dwm-picker-back:hover { color: #374151; }
			.dwm-widget-list {
				display: flex;
				flex-direction: column;
				gap: 8px;
				margin-top: 4px;
			}
			.dwm-widget-list-item {
				display: flex;
				align-items: center;
				justify-content: space-between;
				padding: 10px 14px;
				border: 1px solid #e5e7eb;
				border-radius: 8px;
				cursor: pointer;
				background: #fff;
				transition: all 0.15s;
				width: 100%;
				text-align: left;
			}
			.dwm-widget-list-item:hover {
				border-color: #667eea;
				background: #f9f8ff;
				box-shadow: 0 2px 6px rgba(102,126,234,0.12);
			}
			.dwm-widget-list-item .dwm-widget-item-name {
				font-size: 13px;
				font-weight: 500;
				color: #1f2937;
			}
			.dwm-widget-list-item .dwm-widget-item-desc {
				font-size: 11px;
				color: #9ca3af;
				margin-top: 2px;
			}
			.dwm-widget-status-badge {
				font-size: 11px;
				font-weight: 500;
				padding: 2px 8px;
				border-radius: 20px;
				flex-shrink: 0;
				margin-left: 8px;
			}
			.dwm-widget-status-badge.active {
				background: #d1fae5;
				color: #065f46;
			}
			.dwm-widget-status-badge.draft {
				background: #fef3c7;
				color: #92400e;
			}
			.dwm-picker-filters {
				display: flex;
				flex-direction: column;
				gap: 10px;
				margin-bottom: 12px;
			}
			.dwm-picker-status-filters {
				display: flex;
				align-items: center;
				gap: 4px;
				flex-wrap: wrap;
			}
			.dwm-picker-filter-btn {
				background: none;
				border: none;
				font-size: 12px;
				color: #6b7280;
				cursor: pointer;
				padding: 2px 4px;
				border-radius: 3px;
				transition: color 0.15s;
				line-height: 1.4;
			}
			.dwm-picker-filter-btn:hover { color: #374151; }
			.dwm-picker-filter-btn.is-active {
				color: #1f2937;
				font-weight: 600;
			}
			.dwm-picker-filter-sep {
				color: #d1d5db;
				font-size: 12px;
				user-select: none;
			}
			.dwm-picker-filter-count {
				display: inline-block;
				background: #e5e7eb;
				color: #374151;
				font-size: 10px;
				font-weight: 600;
				padding: 1px 5px;
				border-radius: 10px;
				margin-right: 2px;
			}
			.dwm-picker-filter-btn.is-active .dwm-picker-filter-count {
				background: #667eea;
				color: #fff;
			}
			.dwm-picker-search-input {
				width: 100%;
				padding: 7px 10px;
				border: 1px solid #d1d5db;
				border-radius: 6px;
				font-size: 13px;
				color: #374151;
				background: #f9fafb;
				box-sizing: border-box;
				transition: border-color 0.15s, box-shadow 0.15s;
			}
			.dwm-picker-search-input:focus {
				outline: none;
				border-color: #667eea;
				background: #fff;
				box-shadow: 0 0 0 2px rgba(102,126,234,0.15);
			}
			.dwm-picker-no-results {
				color: #9ca3af;
				font-size: 13px;
				text-align: center;
				padding: 16px 0;
				margin: 0;
			}
			.dwm-picker-confirm-text {
				font-size: 14px;
				color: #374151;
				margin-bottom: 20px;
				line-height: 1.5;
			}
			.dwm-picker-confirm-text strong { color: #1f2937; }
			.dwm-picker-footer {
				display: flex;
				justify-content: flex-end;
				gap: 8px;
				margin-top: 20px;
			}
			.dwm-picker-btn {
				padding: 8px 18px;
				font-size: 13px;
				font-weight: 500;
				border-radius: 6px;
				border: none;
				cursor: pointer;
				transition: all 0.15s;
			}
			.dwm-picker-btn-primary {
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
				color: #fff;
			}
			.dwm-picker-btn-primary:hover {
				box-shadow: 0 4px 10px rgba(102,126,234,0.4);
				transform: translateY(-1px);
				color: #fff;
			}
			.dwm-picker-btn-secondary {
				background: #f3f4f6;
				color: #374151;
				border: 1px solid #d1d5db;
			}
			.dwm-picker-btn-secondary:hover { background: #e5e7eb; }
		' );

		if ( empty( $widgets ) ) {
			// No widgets — direct link to create page.
			wp_add_inline_script( 'jquery', '
				document.addEventListener("DOMContentLoaded", function() {
					var heading = document.querySelector("#wpbody-content .wrap h1");
					if ( ! heading ) { return; }
					var btn = document.createElement("a");
					btn.href = "' . $create_url . '";
					btn.className = "dwm-dashboard-new-widget-btn";
					btn.innerHTML = \'<span class="dashicons dashicons-plus-alt2"></span>' . $button_label . '\';
					heading.appendChild(btn);
				});
			' );
		} else {
			// Widgets exist — button opens picker modal.
			add_action( 'admin_footer', array( $this, 'render_widget_picker_modal' ) );

			wp_add_inline_script( 'jquery', '
				document.addEventListener("DOMContentLoaded", function() {
					var heading = document.querySelector("#wpbody-content .wrap h1");
					if ( ! heading ) { return; }
					var btn = document.createElement("button");
					btn.type = "button";
					btn.id = "dwm-open-widget-picker";
					btn.className = "dwm-dashboard-new-widget-btn";
					btn.innerHTML = \'<span class="dashicons dashicons-plus-alt2"></span>' . $button_label . '\';
					heading.appendChild(btn);
				});
			' );
		}

		// Hide selected default WP dashboard widgets and their Screen Options entries.
		$settings           = $data->get_settings();
		$override_css       = '';
		$hidden_widgets_raw = $settings['hidden_dashboard_widgets'] ?? '';
		if ( ! empty( $hidden_widgets_raw ) ) {
			$hidden_widgets_arr  = array_filter( array_map( 'trim', explode( "\n", $hidden_widgets_raw ) ) );
			$widget_screen_ids   = array(
				'welcome-panel'         => 'wp_welcome_panel',
				'dashboard_activity'    => 'dashboard_activity',
				'dashboard_right_now'   => 'dashboard_right_now',
				'dashboard_quick_press' => 'dashboard_quick_press',
				'dashboard_site_health' => 'dashboard_site_health',
				'dashboard_primary'     => 'dashboard_primary',
			);
			foreach ( $hidden_widgets_arr as $widget_id ) {
				if ( ! isset( $widget_screen_ids[ $widget_id ] ) ) {
					continue;
				}
				$override_css .= '#' . sanitize_key( $widget_id ) . ' { display: none !important; }';
				$screen_id     = $widget_screen_ids[ $widget_id ];
				$override_css .= 'label[for="' . esc_attr( $screen_id ) . '-hide"] { display: none !important; }';
			}
		}

		if ( $override_css ) {
			wp_add_inline_style( 'wp-admin', $override_css );
		}
	}

	/**
	 * Hide Help and/or Screen Options on all admin pages when enabled in settings.
	 */
	public function hide_admin_chrome() {
		$data             = DWM_Data::get_instance();
		$settings         = $data->get_settings();
		$hide_help        = ! empty( $settings['hide_help_dropdown'] );
		$hide_screen_opts = ! empty( $settings['hide_screen_options'] );

		$css = '';
		if ( $hide_help ) {
			$css .= '#contextual-help-link-wrap { display: none !important; }';
		}
		if ( $hide_screen_opts ) {
			$css .= '#screen-options-link-wrap { display: none !important; }';
		}

		if ( $css ) {
			wp_add_inline_style( 'wp-admin', $css );
		}
	}

	/**
	 * Render the widget picker modal HTML in the admin footer.
	 * Only called when widgets exist.
	 */
	public function render_widget_picker_modal() {
		$data    = DWM_Data::get_instance();
		$widgets = $data->get_widgets();

		$create_url = esc_url( admin_url( 'admin.php?page=dashboard-widget-manager&action=create' ) );
		$ajax_url   = esc_url( admin_url( 'admin-ajax.php' ) );
		$nonce      = wp_create_nonce( 'dwm_admin_nonce' );

		// Build widget list JSON for JS.
		$widget_data      = array();
		$selectable_count = 0;
		foreach ( $widgets as $w ) {
			$status = $w['status'] ?? 'draft';
			$widget_data[] = array(
				'id'      => (int) $w['id'],
				'name'    => $w['name'],
				'desc'    => $w['description'],
				'enabled' => (bool) $w['enabled'],
				'status'  => $status,
			);
			if ( 'archived' !== $status && 'trash' !== $status ) {
				$selectable_count++;
			}
		}

		?>
		<div id="dwm-widget-picker-modal" role="dialog" aria-modal="true" aria-labelledby="dwm-picker-title">
			<div class="dwm-picker-overlay" id="dwm-picker-overlay"></div>
			<div class="dwm-picker-content">
				<div class="dwm-picker-header">
					<h2 id="dwm-picker-title">
						<span class="dashicons dashicons-layout"></span>
						<?php esc_html_e( 'Dashboard Widget', 'dashboard-widget-manager' ); ?>
					</h2>
					<button type="button" id="dwm-picker-close" aria-label="<?php esc_attr_e( 'Close', 'dashboard-widget-manager' ); ?>">&times;</button>
				</div>
				<div class="dwm-picker-body">

					<!-- Step 1: Choose action -->
					<div class="dwm-picker-step active" id="dwm-picker-step-1">
						<div class="dwm-picker-choices">
							<a href="<?php echo $create_url; ?>" class="dwm-picker-choice">
								<span class="dashicons dashicons-plus-alt2"></span>
								<strong><?php esc_html_e( 'Create New', 'dashboard-widget-manager' ); ?></strong>
								<span><?php esc_html_e( 'Build a new widget manually', 'dashboard-widget-manager' ); ?></span>
							</a>
							<?php if ( $selectable_count > 0 ) : ?>
							<button type="button" class="dwm-picker-choice" id="dwm-picker-show-list">
								<span class="dashicons dashicons-list-view"></span>
								<strong><?php esc_html_e( 'Select Existing', 'dashboard-widget-manager' ); ?></strong>
								<span><?php esc_html_e( 'Choose from your saved widgets', 'dashboard-widget-manager' ); ?></span>
							</button>
						<?php endif; ?>
						</div>
					</div>

					<!-- Step 2: Widget list -->
					<div class="dwm-picker-step" id="dwm-picker-step-2">
						<button type="button" class="dwm-picker-back" id="dwm-picker-back-1">
							&larr; <?php esc_html_e( 'Back', 'dashboard-widget-manager' ); ?>
						</button>
						<div class="dwm-picker-filters">
							<div class="dwm-picker-status-filters" id="dwm-picker-status-filters">
								<button type="button" class="dwm-picker-filter-btn" data-filter="all"><?php esc_html_e( 'All', 'dashboard-widget-manager' ); ?></button>
								<span class="dwm-picker-filter-sep">|</span>
								<button type="button" class="dwm-picker-filter-btn is-active" data-filter="active"><span class="dwm-picker-filter-count" id="dwm-picker-count-active">0</span> <?php esc_html_e( 'Active', 'dashboard-widget-manager' ); ?></button>
								<span class="dwm-picker-filter-sep">|</span>
								<button type="button" class="dwm-picker-filter-btn" data-filter="draft"><span class="dwm-picker-filter-count" id="dwm-picker-count-draft">0</span> <?php esc_html_e( 'Draft', 'dashboard-widget-manager' ); ?></button>
							</div>
							<input type="search" id="dwm-picker-search" placeholder="<?php esc_attr_e( 'Search widgets...', 'dashboard-widget-manager' ); ?>" class="dwm-picker-search-input" />
						</div>
						<div class="dwm-widget-list" id="dwm-picker-widget-list"></div>
						<p class="dwm-picker-no-results" id="dwm-picker-no-results" style="display:none;"><?php esc_html_e( 'No widgets match your search.', 'dashboard-widget-manager' ); ?></p>
					</div>

					<!-- Step 3: Confirm enable draft -->
					<div class="dwm-picker-step" id="dwm-picker-step-3">
						<button type="button" class="dwm-picker-back" id="dwm-picker-back-2">
							&larr; <?php esc_html_e( 'Back', 'dashboard-widget-manager' ); ?>
						</button>
						<p class="dwm-picker-confirm-text" id="dwm-picker-confirm-text"></p>
						<div class="dwm-picker-footer">
							<button type="button" class="dwm-picker-btn dwm-picker-btn-secondary" id="dwm-picker-cancel-enable">
								<?php esc_html_e( 'Cancel', 'dashboard-widget-manager' ); ?>
							</button>
							<button type="button" class="dwm-picker-btn dwm-picker-btn-primary" id="dwm-picker-confirm-enable">
								<?php esc_html_e( 'Activate Widget', 'dashboard-widget-manager' ); ?>
							</button>
						</div>
					</div>

				</div>
			</div>
		</div>

		<script type="text/javascript">
		(function($) {
			var widgets       = <?php echo wp_json_encode( $widget_data ); ?>;
			var ajaxUrl       = '<?php echo $ajax_url; ?>';
			var nonce         = '<?php echo esc_js( $nonce ); ?>';
			var pendingId     = null;
			var currentFilter = 'active';

			function openPicker() {
				showStep(1);
				$('#dwm-widget-picker-modal').addClass('active');
				$('body').css('overflow', 'hidden');
			}

			function closePicker() {
				$('#dwm-widget-picker-modal').removeClass('active');
				$('body').css('overflow', '');
				pendingId = null;
			}

			function showStep(n) {
				$('.dwm-picker-step').removeClass('active');
				$('#dwm-picker-step-' + n).addClass('active');
			}

			function buildWidgetList() {
				var $list = $('#dwm-picker-widget-list').empty();

				// Exclude archived and trashed widgets.
				var selectable = $.grep(widgets, function(w) {
					return w.status !== 'archived' && w.status !== 'trash';
				});

				// Update filter counts.
				var activeCount = $.grep(selectable, function(w) { return w.enabled; }).length;
				var draftCount  = $.grep(selectable, function(w) { return !w.enabled; }).length;
				$('#dwm-picker-count-active').text(activeCount);
				$('#dwm-picker-count-draft').text(draftCount);

				// Show/hide filter buttons based on counts.
				var $filters = $('#dwm-picker-status-filters');
				var $allBtn    = $filters.find('.dwm-picker-filter-btn[data-filter="all"]');
				var $activeBtn = $filters.find('.dwm-picker-filter-btn[data-filter="active"]');
				var $draftBtn  = $filters.find('.dwm-picker-filter-btn[data-filter="draft"]');
				var $seps      = $filters.find('.dwm-picker-filter-sep');

				var showAll    = activeCount > 0 && draftCount > 0;
				var showActive = activeCount > 0;
				var showDraft  = draftCount > 0;

				$allBtn.toggle(showAll);
				$seps.eq(0).toggle(showAll);
				$activeBtn.toggle(showActive);
				$seps.eq(1).toggle(showDraft);
				$draftBtn.toggle(showDraft);

				// If current filter is no longer shown, reset to visible default.
				if ( currentFilter === 'all' && !showAll ) {
					currentFilter = showActive ? 'active' : 'draft';
				} else if ( currentFilter === 'active' && !showActive ) {
					currentFilter = showDraft ? 'draft' : 'all';
				} else if ( currentFilter === 'draft' && !showDraft ) {
					currentFilter = showActive ? 'active' : 'all';
				}
				$filters.find('.dwm-picker-filter-btn').removeClass('is-active');
				$filters.find('.dwm-picker-filter-btn[data-filter="' + currentFilter + '"]').addClass('is-active');

				if ( !selectable.length ) {
					$list.html('<p style="color:#6b7280;font-size:13px;"><?php echo esc_js( __( 'No widgets found.', 'dashboard-widget-manager' ) ); ?></p>');
					return;
				}

				$.each(selectable, function(i, w) {
					var statusClass = w.enabled ? 'active' : 'draft';
					var statusLabel = w.enabled ? '<?php echo esc_js( __( 'Active', 'dashboard-widget-manager' ) ); ?>' : '<?php echo esc_js( __( 'Draft', 'dashboard-widget-manager' ) ); ?>';
					var $item = $(
						'<button type="button" class="dwm-widget-list-item" data-id="' + w.id + '" data-enabled="' + (w.enabled ? '1' : '0') + '" data-status="' + statusClass + '">' +
							'<div>' +
								'<div class="dwm-widget-item-name">' + $('<span>').text(w.name).html() + '</div>' +
								( w.desc ? '<div class="dwm-widget-item-desc">' + $('<span>').text(w.desc).html() + '</div>' : '' ) +
							'</div>' +
							'<span class="dwm-widget-status-badge ' + statusClass + '">' + statusLabel + '</span>' +
						'</button>'
					);
					$list.append($item);
				});

				applyFilters();
			}

			function applyFilters() {
				var search  = ($('#dwm-picker-search').val() || '').toLowerCase().trim();
				var visible = 0;

				$('#dwm-picker-widget-list .dwm-widget-list-item').each(function() {
					var $item       = $(this);
					var itemStatus  = $item.data('status');
					var name        = $item.find('.dwm-widget-item-name').text().toLowerCase();
					var desc        = $item.find('.dwm-widget-item-desc').text().toLowerCase();
					var matchFilter = ( currentFilter === 'all' ) || ( itemStatus === currentFilter );
					var matchSearch = !search || name.indexOf(search) !== -1 || desc.indexOf(search) !== -1;

					$item.toggle(matchFilter && matchSearch);
					if ( matchFilter && matchSearch ) { visible++; }
				});

				$('#dwm-picker-no-results').toggle(visible === 0 && $('#dwm-picker-widget-list .dwm-widget-list-item').length > 0);
			}

			// Open picker button.
			$(document).on('click', '#dwm-open-widget-picker', openPicker);

			// Close handlers.
			$(document).on('click', '#dwm-picker-close, #dwm-picker-overlay', closePicker);
			$(document).on('keydown', function(e) {
				if (e.key === 'Escape') { closePicker(); }
			});

			// Step 1 → Step 2: reset filter/search to defaults.
			$(document).on('click', '#dwm-picker-show-list', function() {
				currentFilter = 'active';
				$('#dwm-picker-status-filters .dwm-picker-filter-btn').removeClass('is-active');
				$('#dwm-picker-status-filters .dwm-picker-filter-btn[data-filter="active"]').addClass('is-active');
				$('#dwm-picker-search').val('');
				buildWidgetList();
				showStep(2);
			});

			// Filter button click.
			$(document).on('click', '.dwm-picker-filter-btn', function() {
				currentFilter = $(this).data('filter');
				$('#dwm-picker-status-filters .dwm-picker-filter-btn').removeClass('is-active');
				$(this).addClass('is-active');
				applyFilters();
			});

			// Search input.
			$(document).on('input', '#dwm-picker-search', function() {
				applyFilters();
			});

			// Step 2 → Step 1 (back).
			$(document).on('click', '#dwm-picker-back-1', function() {
				showStep(1);
			});

			// Step 3 → Step 2 (back).
			$(document).on('click', '#dwm-picker-back-2', function() {
				showStep(2);
			});

			// Widget item click.
			$(document).on('click', '.dwm-widget-list-item', function() {
				var id      = parseInt($(this).data('id'), 10);
				var enabled = $(this).data('enabled') === '1' || $(this).data('enabled') === 1;
				var name    = $(this).find('.dwm-widget-item-name').text();

				if (enabled) {
					// Already active — nothing to do, just close.
					closePicker();
				} else {
					// Draft — ask to activate.
					pendingId = id;
					$('#dwm-picker-confirm-text').html(
						'<?php echo esc_js( __( 'The widget', 'dashboard-widget-manager' ) ); ?> <strong>' + $('<span>').text(name).html() + '</strong> <?php echo esc_js( __( 'is currently a draft and not visible on the dashboard. Activate it to add it to your dashboard?', 'dashboard-widget-manager' ) ); ?>'
					);
					showStep(3);
				}
			});

			// Cancel enable.
			$(document).on('click', '#dwm-picker-cancel-enable', function() {
				pendingId = null;
				showStep(2);
			});

			// Confirm enable.
			$(document).on('click', '#dwm-picker-confirm-enable', function() {
				if ( ! pendingId ) { return; }
				var $btn = $(this);
				$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Activating...', 'dashboard-widget-manager' ) ); ?>');

				$.post(ajaxUrl, {
					action:    'dwm_toggle_widget',
					nonce:     nonce,
					widget_id: pendingId,
					status:    'publish'
				}, function(response) {
					$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Activate Widget', 'dashboard-widget-manager' ) ); ?>');
					if (response && response.success) {
						// Update local widget data so the badge refreshes if they go back.
						$.each(widgets, function(i, w) {
							if (w.id === pendingId) { w.enabled = true; }
						});
						closePicker();
						// Soft reload to show new widget on dashboard.
						window.location.reload();
					} else {
						var msg = (response && response.data && response.data.message) ? response.data.message : '<?php echo esc_js( __( 'Failed to activate widget. Please try again.', 'dashboard-widget-manager' ) ); ?>';
						alert(msg);
					}
				}).fail(function() {
					$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Activate Widget', 'dashboard-widget-manager' ) ); ?>');
					alert('<?php echo esc_js( __( 'Request failed. Please try again.', 'dashboard-widget-manager' ) ); ?>');
				});
			});

		})(jQuery);
		</script>
		<?php
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		// Only load on plugin pages.
		if ( ! $this->is_plugin_page( $hook ) ) {
			return;
		}

		// Chart widgets render on the WP Dashboard (index.php). Ensure Chart.js is available there.
		if ( 'index.php' === $hook ) {
			wp_enqueue_script(
				'dwm-chartjs',
				DWM_PLUGIN_URL . 'assets/js/components/vendors/chart.min.js',
				array(),
				DWM_VERSION,
				true
			);
			wp_enqueue_script(
				'dwm-wp-dashboard',
				DWM_PLUGIN_URL . 'assets/minimized/js/wp-dashboard.min.js',
				array( 'jquery', 'dwm-admin' ),
				DWM_VERSION,
				true
			);
		}

		// Base admin script.
		wp_enqueue_script(
			'dwm-admin',
			DWM_PLUGIN_URL . 'assets/minimized/js/admin.min.js',
			array( 'jquery' ),
			DWM_VERSION,
			true
		);

		// Localize script with AJAX data.
		wp_localize_script(
			'dwm-admin',
			'dwmAdminVars',
			array(
				'nonce'        => wp_create_nonce( 'dwm_admin_nonce' ),
				'supportNonce' => wp_create_nonce( 'dwm_support_nonce' ),
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'i18n'         => array(
					'confirmDelete'  => __( 'Are you sure you want to delete this widget?', 'dashboard-widget-manager' ),
					'saveSuccess'    => __( 'Saved successfully.', 'dashboard-widget-manager' ),
					'saveError'      => __( 'Error saving data.', 'dashboard-widget-manager' ),
					'validating'     => __( 'Validating query...', 'dashboard-widget-manager' ),
					'queryValid'     => __( 'Query is valid!', 'dashboard-widget-manager' ),
					'queryInvalid'   => __( 'Query is invalid.', 'dashboard-widget-manager' ),
					'loading'        => __( 'Loading...', 'dashboard-widget-manager' ),
				),
			)
		);

		// Inject pro menu config for JS-driven scroll/focus behavior.
		$pro_config = DWM_Admin_Menu::get_pro_menu_config();
		if ( $pro_config ) {
			wp_add_inline_script(
				'dwm-admin',
				'window.dwmProMenuLinkConfig = ' . wp_json_encode( [
					'targetUrl'   => $pro_config['target_url'],
					'type'        => $pro_config['type'],
					'initialHref' => $pro_config['menu_href'],
					'scrollTo'    => $pro_config['scroll_to'],
					'focusField'  => $pro_config['focus_field'],
				] ) . ';',
				'before'
			);
		}

		// Page-specific scripts.
		if ( 'widget-manager_page_dwm-settings' === $hook ) {
			wp_enqueue_script(
				'dwm-settings',
				DWM_PLUGIN_URL . 'assets/minimized/js/settings.min.js',
				array( 'jquery', 'dwm-admin' ),
				DWM_VERSION,
				true
			);
		}

		if ( 'widget-manager_page_dwm-tools' === $hook ) {
			wp_enqueue_script(
				'dwm-tools',
				DWM_PLUGIN_URL . 'assets/minimized/js/tools.min.js',
				array( 'jquery', 'dwm-admin' ),
				DWM_VERSION,
				true
			);
		}

		if ( strpos( $hook, 'dashboard-widget-manager' ) !== false && strpos( $hook, 'dwm-' ) === false ) {
			wp_enqueue_script(
				'dwm-dashboard',
				DWM_PLUGIN_URL . 'assets/minimized/js/dashboard.min.js',
				array( 'jquery', 'dwm-admin', 'wp-codemirror' ),
				DWM_VERSION,
				true
			);

			// jQuery UI for sortable.
			wp_enqueue_script( 'jquery-ui-sortable' );
		}
	}

	/**
	 * Check if current page is a plugin page.
	 *
	 * @param string $hook Current admin page hook.
	 * @return bool True if plugin page, false otherwise.
	 */
	private function is_plugin_page( $hook ) {
		$plugin_pages = array(
			'toplevel_page_dashboard-widget-manager',
			'widget-manager_page_dwm-settings',
			'widget-manager_page_dwm-tools',
			'index.php',
		);

		return in_array( $hook, $plugin_pages, true );
	}
}
