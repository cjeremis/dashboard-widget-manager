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
		$dwm_admin_css_version = file_exists( DWM_PLUGIN_DIR . 'assets/minimized/css/global.min.css' )
			? (string) filemtime( DWM_PLUGIN_DIR . 'assets/minimized/css/global.min.css' )
			: DWM_VERSION;
		wp_enqueue_style(
			'dwm-admin',
			DWM_PLUGIN_URL . 'assets/minimized/css/global.min.css',
			array(),
			$dwm_admin_css_version,
			'all'
		);

		// Inject logo URL as CSS variable.
		$logo_url = DWM_PLUGIN_URL . 'assets/images/logo.png';
		wp_add_inline_style( 'dwm-admin', ':root { --dwm-logo-url: url("' . esc_url( $logo_url ) . '"); }' );

		// Page-specific styles.
		if ( strpos( $hook, 'dashboard-widget-manager' ) !== false && strpos( $hook, 'dwm-' ) === false ) {
			$ver = file_exists( DWM_PLUGIN_DIR . 'assets/minimized/css/dashboard.min.css' )
				? (string) filemtime( DWM_PLUGIN_DIR . 'assets/minimized/css/dashboard.min.css' )
				: DWM_VERSION;
			wp_enqueue_style(
				'dwm-dashboard',
				DWM_PLUGIN_URL . 'assets/minimized/css/dashboard.min.css',
				array( 'dwm-admin' ),
				$ver,
				'all'
			);

			// CodeMirror for code editing.
			wp_enqueue_code_editor( array( 'type' => 'application/x-httpd-php' ) );
			wp_enqueue_style( 'wp-codemirror' );
		}

		if ( 'widget-manager_page_dwm-settings' === $hook ) {
			$ver = file_exists( DWM_PLUGIN_DIR . 'assets/minimized/css/settings.min.css' )
				? (string) filemtime( DWM_PLUGIN_DIR . 'assets/minimized/css/settings.min.css' )
				: DWM_VERSION;
			wp_enqueue_style(
				'dwm-settings',
				DWM_PLUGIN_URL . 'assets/minimized/css/settings.min.css',
				array( 'dwm-admin' ),
				$ver,
				'all'
			);
		}

		if ( 'widget-manager_page_dwm-customize-dashboard' === $hook ) {
			$ver = file_exists( DWM_PLUGIN_DIR . 'assets/minimized/css/customize-dashboard.min.css' )
				? (string) filemtime( DWM_PLUGIN_DIR . 'assets/minimized/css/customize-dashboard.min.css' )
				: DWM_VERSION;
			wp_enqueue_style(
				'dwm-customize-dashboard',
				DWM_PLUGIN_URL . 'assets/minimized/css/customize-dashboard.min.css',
				array( 'dwm-admin' ),
				$ver,
				'all'
			);
		}

		if ( 'widget-manager_page_dwm-tools' === $hook ) {
			$ver = file_exists( DWM_PLUGIN_DIR . 'assets/minimized/css/tools.min.css' )
				? (string) filemtime( DWM_PLUGIN_DIR . 'assets/minimized/css/tools.min.css' )
				: DWM_VERSION;
			wp_enqueue_style(
				'dwm-tools',
				DWM_PLUGIN_URL . 'assets/minimized/css/tools.min.css',
				array( 'dwm-admin' ),
				$ver,
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
		if ( ! DWM_Access_Control::current_user_can_access_plugin() ) {
			return;
		}

		$data     = DWM_Data::get_instance();
		$widgets  = $data->get_widgets();
		$settings = $data->get_settings();

		$create_url   = esc_url( admin_url( 'admin.php?page=dashboard-widget-manager&action=create' ) );
		$button_label = esc_js( __( 'New Widget', 'dashboard-widget-manager' ) );

		// Detect whether any dashboard customisation is live on the dashboard page.
		$title_mode_check = sanitize_key( (string) ( $settings['dashboard_title_mode'] ?? 'default' ) );
		$title_hidden     = 'hide' === $title_mode_check;
		$customization_active = ( ! empty( $settings['dashboard_branding_enabled'] )
			|| ! empty( $settings['dashboard_background_enabled'] )
			|| ! empty( $settings['dashboard_hero_enabled'] ) )
			&& ! $title_hidden;

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

		if ( $customization_active ) {
			// Branding is live — show floating action button at bottom-right of dashboard.
			$logo_url_fab  = esc_url( DWM_PLUGIN_URL . 'assets/images/logo.png' );
			$branding_url  = esc_url( admin_url( 'admin.php?page=dwm-customize-dashboard' ) );
			$has_existing  = ! empty( $widgets );
			$manage_url    = esc_url( admin_url( 'admin.php?page=dashboard-widget-manager' ) );

			wp_add_inline_style(
				'wp-admin',
				'
				/* ── DWM Floating Action Button ── */
				#dwm-fab {
					position: fixed;
					bottom: 32px;
					right: 32px;
					z-index: 99999;
					display: flex;
					flex-direction: row-reverse;
					align-items: center;
				}
				#dwm-fab-icon {
					width: 48px;
					height: 48px;
					border-radius: 50%;
					background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
					border: none;
					cursor: pointer;
					display: flex;
					align-items: center;
					justify-content: center;
					box-shadow: 0 4px 16px rgba(102, 126, 234, 0.45);
					transition: box-shadow 0.25s ease, transform 0.25s ease;
					flex-shrink: 0;
					position: relative;
					z-index: 2;
					padding: 0;
				}
				#dwm-fab-icon:hover {
					box-shadow: 0 6px 24px rgba(102, 126, 234, 0.6);
					transform: scale(1.06);
				}
				#dwm-fab-icon img {
					width: 28px;
					height: 28px;
					border-radius: 50%;
					object-fit: contain;
					pointer-events: none;
				}
				/* Panel slides out to the LEFT of the icon */
				#dwm-fab-panel {
					position: absolute;
					right: 40px;
					bottom: 0;
					background: #fff;
					border-radius: 14px;
					box-shadow: 0 8px 32px rgba(0, 0, 0, 0.16);
					min-width: 240px;
					opacity: 0;
					transform: translateX(20px);
					pointer-events: none;
					transition: opacity 0.22s ease, transform 0.22s ease;
					overflow: hidden;
					z-index: 1;
				}
				#dwm-fab.is-open #dwm-fab-panel {
					opacity: 1;
					transform: translateX(0);
					pointer-events: auto;
				}
				#dwm-fab-panel-header {
					display: flex;
					align-items: center;
					gap: 10px;
					padding: 14px 18px 10px;
					border-bottom: 1px solid #f1f5f9;
				}
				#dwm-fab-panel-header img {
					width: 24px;
					height: 24px;
					border-radius: 50%;
					object-fit: contain;
					flex-shrink: 0;
				}
				#dwm-fab-panel-header span {
					font-size: 13px;
					font-weight: 700;
					color: #1f2937;
					white-space: nowrap;
				}
				#dwm-fab-panel-menu {
					list-style: none;
					margin: 0;
					padding: 8px 0;
				}
				#dwm-fab-panel-menu li a {
					display: flex;
					align-items: center;
					gap: 10px;
					padding: 9px 18px;
					font-size: 13px;
					font-weight: 500;
					color: #374151;
					text-decoration: none;
					transition: background 0.12s ease, color 0.12s ease;
				}
				#dwm-fab-panel-menu li a:hover {
					background: #f5f3ff;
					color: #667eea;
				}
				#dwm-fab-panel-menu li a .dashicons {
					font-size: 18px;
					width: 18px;
					height: 18px;
					color: #667eea;
					flex-shrink: 0;
				}
				/* Nudge animation on load */
				@keyframes dwmFabNudge {
					0%   { transform: translateX(0); }
					30%  { transform: translateX(-8px); }
					50%  { transform: translateX(3px); }
					70%  { transform: translateX(-2px); }
					100% { transform: translateX(0); }
				}
				#dwm-fab.dwm-fab-nudge #dwm-fab-icon {
					animation: dwmFabNudge 0.6s ease-out;
				}
			'
			);

			add_action(
				'admin_footer',
				function () use ( $logo_url_fab, $create_url, $branding_url, $has_existing, $manage_url ) {
					?>
					<div id="dwm-fab">
						<button type="button" id="dwm-fab-icon" aria-label="<?php esc_attr_e( 'Dashboard Widget Manager', 'dashboard-widget-manager' ); ?>">
							<img src="<?php echo $logo_url_fab; ?>" alt="">
						</button>
						<div id="dwm-fab-panel">
							<div id="dwm-fab-panel-header">
								<img src="<?php echo $logo_url_fab; ?>" alt="">
								<span><?php esc_html_e( 'Widget Manager', 'dashboard-widget-manager' ); ?></span>
							</div>
							<ul id="dwm-fab-panel-menu">
								<li>
									<a href="<?php echo $create_url; ?>">
										<span class="dashicons dashicons-plus-alt2"></span>
										<?php esc_html_e( 'Create New', 'dashboard-widget-manager' ); ?>
									</a>
								</li>
								<?php if ( $has_existing ) : ?>
								<li>
									<a href="<?php echo $manage_url; ?>">
										<span class="dashicons dashicons-list-view"></span>
										<?php esc_html_e( 'Manage Widgets', 'dashboard-widget-manager' ); ?>
									</a>
								</li>
								<?php endif; ?>
								<li>
									<a href="<?php echo $branding_url; ?>">
										<span class="dashicons dashicons-art"></span>
										<?php esc_html_e( 'Branding', 'dashboard-widget-manager' ); ?>
									</a>
								</li>
							</ul>
						</div>
					</div>
					<script>
					(function() {
						var fab = document.getElementById('dwm-fab');
						if (!fab) return;
						var icon = document.getElementById('dwm-fab-icon');
						var openTimeout, closeTimeout;

						function openPanel() {
							clearTimeout(closeTimeout);
							fab.classList.add('is-open');
						}
						function closePanel() {
							closeTimeout = setTimeout(function() {
								fab.classList.remove('is-open');
							}, 200);
						}

						fab.addEventListener('mouseenter', openPanel);
						fab.addEventListener('mouseleave', closePanel);
						icon.addEventListener('click', function() {
							if (fab.classList.contains('is-open')) {
								fab.classList.remove('is-open');
							} else {
								openPanel();
							}
						});

						// Nudge animation after page load.
						window.addEventListener('load', function() {
							setTimeout(function() {
								fab.classList.add('dwm-fab-nudge');
								setTimeout(function() {
									fab.classList.remove('dwm-fab-nudge');
								}, 700);
							}, 1200);
						});
					})();
					</script>
					<?php
				}
			);
		} else {
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
		}

		// Hide selected dashboard widgets and their Screen Options entries.
		$override_css = '';
		$hidden_widgets_raw = $settings['hidden_dashboard_widgets'] ?? '';
		if ( ! empty( $hidden_widgets_raw ) ) {
			$hidden_widgets_arr  = array_filter( array_map( 'trim', explode( "\n", $hidden_widgets_raw ) ) );
			$widget_screen_ids   = $this->get_widget_screen_option_ids();
			foreach ( $hidden_widgets_arr as $widget_id ) {
				if ( ! isset( $widget_screen_ids[ $widget_id ] ) ) {
					continue;
				}
				$override_css .= '#' . sanitize_key( $widget_id ) . ' { display: none !important; }';
				$screen_id     = $widget_screen_ids[ $widget_id ];
				$override_css .= 'label[for="' . esc_attr( $screen_id ) . '-hide"] { display: none !important; }';
			}
		}
		$hidden_third_party_raw = $settings['hidden_third_party_dashboard_widgets'] ?? '';
		if ( ! empty( $hidden_third_party_raw ) ) {
			$hidden_third_party_arr = array_filter( array_map( 'trim', explode( "\n", $hidden_third_party_raw ) ) );
			foreach ( $hidden_third_party_arr as $widget_id ) {
				$widget_id    = sanitize_key( $widget_id );
				$override_css .= '#' . $widget_id . ' { display: none !important; }';
				$override_css .= 'label[for="' . $widget_id . '-hide"] { display: none !important; }';
			}
		}

		if ( $override_css ) {
			wp_add_inline_style( 'wp-admin', $override_css );
		}
	}

	/**
	 * Return default WordPress dashboard widget IDs and labels.
	 *
	 * @return array<string,string>
	 */
	public function get_default_dashboard_widget_map() {
		return array(
			'welcome-panel'         => __( 'Welcome Panel', 'dashboard-widget-manager' ),
			'dashboard_activity'    => __( 'Activity', 'dashboard-widget-manager' ),
			'dashboard_right_now'   => __( 'At a Glance', 'dashboard-widget-manager' ),
			'dashboard_quick_press' => __( 'Quick Draft', 'dashboard-widget-manager' ),
			'dashboard_site_health' => __( 'Site Health Status', 'dashboard-widget-manager' ),
			'dashboard_primary'     => __( 'Events and News', 'dashboard-widget-manager' ),
		);
	}

	/**
	 * Return mapping of dashboard widget IDs to Screen Options IDs.
	 *
	 * @return array<string,string>
	 */
	private function get_widget_screen_option_ids() {
		return array(
			'welcome-panel'         => 'wp_welcome_panel',
			'dashboard_activity'    => 'dashboard_activity',
			'dashboard_right_now'   => 'dashboard_right_now',
			'dashboard_quick_press' => 'dashboard_quick_press',
			'dashboard_site_health' => 'dashboard_site_health',
			'dashboard_primary'     => 'dashboard_primary',
		);
	}

	/**
	 * Get all dashboard widgets registered by other plugins (non-core, non-DWM).
	 *
	 * @return array<string,string>
	 */
	public function get_third_party_dashboard_widgets_for_settings() {
		$registered_widgets = $this->get_registered_dashboard_widgets();
		$default_widget_ids = array_keys( $this->get_default_dashboard_widget_map() );
		$third_party        = array();

		foreach ( $registered_widgets as $widget_id => $widget_label ) {
			if ( in_array( $widget_id, $default_widget_ids, true ) ) {
				continue;
			}
			if ( 0 === strpos( $widget_id, 'dwm_' ) ) {
				continue;
			}
			$third_party[ $widget_id ] = $widget_label;
		}

		return $third_party;
	}

	/**
	 * Force-remove hidden dashboard widgets from all dashboard columns.
	 */
	public function force_remove_hidden_dashboard_widgets() {
		global $pagenow;
		if ( 'index.php' !== $pagenow ) {
			return;
		}

		$data     = DWM_Data::get_instance();
		$settings = $data->get_settings();

		$hidden_default_raw = $settings['hidden_dashboard_widgets'] ?? '';
		$hidden_default_ids = empty( $hidden_default_raw ) ? array() : array_filter( array_map( 'trim', explode( "\n", $hidden_default_raw ) ) );

		$hidden_third_party_raw = $settings['hidden_third_party_dashboard_widgets'] ?? '';
		$hidden_third_party_ids = empty( $hidden_third_party_raw ) ? array() : array_filter( array_map( 'trim', explode( "\n", $hidden_third_party_raw ) ) );

		$widget_ids = array_values( array_unique( array_merge( $hidden_default_ids, $hidden_third_party_ids ) ) );
		if ( empty( $widget_ids ) ) {
			return;
		}

		if ( in_array( 'welcome-panel', $widget_ids, true ) ) {
			remove_action( 'welcome_panel', 'wp_welcome_panel' );
		}

		$contexts = array( 'normal', 'side', 'column3', 'column4' );
		foreach ( $widget_ids as $widget_id ) {
			if ( 'welcome-panel' === $widget_id ) {
				continue;
			}
			$widget_id = sanitize_key( $widget_id );
			foreach ( $contexts as $context ) {
				remove_meta_box( $widget_id, 'dashboard', $context );
			}
		}
	}

	/**
	 * Get registered dashboard widgets as an ID => label map.
	 *
	 * @return array<string,string>
	 */
	private function get_registered_dashboard_widgets() {
		global $wp_meta_boxes;

		$screen = convert_to_screen( 'dashboard' );
		if ( ! isset( $wp_meta_boxes[ $screen->id ] ) ) {
			if ( ! function_exists( 'wp_add_dashboard_widget' ) ) {
				require_once ABSPATH . 'wp-admin/includes/dashboard.php';
			}
			// Temporarily set current screen to dashboard so plugins that call
			// get_current_screen() inside their wp_dashboard_setup callbacks register correctly.
			global $current_screen, $_wp_dashboard_control_callbacks;
			$prev_screen    = $current_screen;
			$current_screen = $screen; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
			// Ensure the dashboard key exists so third-party plugins (e.g. wp-mail-smtp)
			// that access $_wp_dashboard_control_callbacks['dashboard'] don't emit warnings.
			if ( ! isset( $_wp_dashboard_control_callbacks['dashboard'] ) ) {
				$_wp_dashboard_control_callbacks['dashboard'] = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
			}
			wp_dashboard_setup();
			$current_screen = $prev_screen; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		}

		$widget_map = array();
		$meta_boxes = $wp_meta_boxes[ $screen->id ] ?? array();

		foreach ( $meta_boxes as $contexts ) {
			if ( ! is_array( $contexts ) ) {
				continue;
			}
			foreach ( $contexts as $priorities ) {
				if ( ! is_array( $priorities ) ) {
					continue;
				}
				foreach ( $priorities as $widget_id => $meta_box ) {
					$widget_id = sanitize_key( (string) $widget_id );
					if ( '' === $widget_id ) {
						continue;
					}
					if ( isset( $widget_map[ $widget_id ] ) ) {
						continue;
					}
					$title = isset( $meta_box['title'] ) ? wp_strip_all_tags( (string) $meta_box['title'] ) : $widget_id;
					$label = trim( $title );
					if ( '' === $label ) {
						$label = $widget_id;
					}
					$widget_map[ $widget_id ] = $label;
				}
			}
		}

		asort( $widget_map, SORT_NATURAL | SORT_FLAG_CASE );
		return $widget_map;
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
				'nonce'             => wp_create_nonce( 'dwm_admin_nonce' ),
				'supportNonce'      => wp_create_nonce( 'dwm_support_nonce' ),
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'wpDebugEnabled'    => ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 1 : 0,
				'wpDebugLogEnabled' => ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) ? 1 : 0,
				'i18n'              => array(
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

		if ( 'index.php' === $hook ) {
			$this->enqueue_dashboard_customization_inline_assets();
		}

		// Page-specific scripts.
		if ( 'widget-manager_page_dwm-settings' === $hook || 'widget-manager_page_dwm-customize-dashboard' === $hook ) {
			wp_enqueue_media();
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
			'widget-manager_page_dwm-customize-dashboard',
			'widget-manager_page_dwm-tools',
			'widget-manager_page_dwm-integrations',
			'index.php',
		);

		return in_array( $hook, $plugin_pages, true );
	}

	/**
	 * Enqueue inline dashboard customization styles and scripts on wp-admin Dashboard.
	 */
	private function enqueue_dashboard_customization_inline_assets() {
		$data     = DWM_Data::get_instance();
		$settings = $data->get_settings();

		$logo_enabled = ! empty( $settings['dashboard_logo_enabled'] );
		$logo_url     = esc_url_raw( (string) ( $settings['dashboard_logo_url'] ?? '' ) );
		$logo_link_url = esc_url_raw( (string) ( $settings['dashboard_logo_link_url'] ?? '' ) );
		$logo_height      = isset( $settings['dashboard_logo_height'] ) ? absint( $settings['dashboard_logo_height'] ) : 56;
		$logo_height      = max( 1, min( 500, $logo_height ) );
		$logo_height_unit = isset( $settings['dashboard_logo_height_unit'] ) ? sanitize_key( (string) $settings['dashboard_logo_height_unit'] ) : 'px';
		if ( ! in_array( $logo_height_unit, array( 'px', '%', 'rem', 'em', 'vh' ), true ) ) {
			$logo_height_unit = 'px';
		}
		$logo_alignment = sanitize_key( (string) ( $settings['dashboard_logo_alignment'] ?? 'left' ) );
		if ( ! in_array( $logo_alignment, array( 'left', 'center', 'right' ), true ) ) {
			$logo_alignment = 'left';
		}
		$logo_bg_type             = sanitize_key( (string) ( $settings['dashboard_logo_bg_type'] ?? 'default' ) );
		$logo_bg_type             = in_array( $logo_bg_type, array( 'default', 'solid', 'gradient' ), true ) ? $logo_bg_type : 'default';
		$logo_bg_solid            = sanitize_hex_color( (string) ( $settings['dashboard_logo_bg_solid_color'] ?? '' ) );
		$logo_bg_grad_type        = sanitize_key( (string) ( $settings['dashboard_logo_bg_gradient_type'] ?? 'linear' ) );
		$logo_bg_grad_type        = in_array( $logo_bg_grad_type, array( 'linear', 'radial' ), true ) ? $logo_bg_grad_type : 'linear';
		$logo_bg_grad_angle       = max( 0, min( 360, (int) ( $settings['dashboard_logo_bg_gradient_angle'] ?? 90 ) ) );
		$logo_bg_grad_start       = sanitize_hex_color( (string) ( $settings['dashboard_logo_bg_gradient_start'] ?? '' ) ) ?: '#667eea';
		$logo_bg_grad_start_pos   = max( 0, min( 100, (int) ( $settings['dashboard_logo_bg_gradient_start_position'] ?? 0 ) ) );
		$logo_bg_grad_end         = sanitize_hex_color( (string) ( $settings['dashboard_logo_bg_gradient_end'] ?? '' ) ) ?: '#764ba2';
		$logo_bg_grad_end_pos     = max( 0, min( 100, (int) ( $settings['dashboard_logo_bg_gradient_end_position'] ?? 100 ) ) );
		$logo_padding_top        = max( 0, min( 200, (int) ( $settings['dashboard_logo_padding_top'] ?? 10 ) ) );
		$logo_padding_right      = max( 0, min( 200, (int) ( $settings['dashboard_logo_padding_right'] ?? 10 ) ) );
		$logo_padding_bottom     = max( 0, min( 200, (int) ( $settings['dashboard_logo_padding_bottom'] ?? 10 ) ) );
		$logo_padding_left       = max( 0, min( 200, (int) ( $settings['dashboard_logo_padding_left'] ?? 10 ) ) );
		$logo_padding_unit       = in_array( sanitize_key( (string) ( $settings['dashboard_logo_padding_unit'] ?? 'px' ) ), array( 'px', '%', 'rem', 'em' ), true ) ? sanitize_key( (string) $settings['dashboard_logo_padding_unit'] ) : 'px';
		$logo_margin_top         = max( -200, min( 200, (int) ( $settings['dashboard_logo_margin_top'] ?? 0 ) ) );
		$logo_margin_right       = max( -200, min( 200, (int) ( $settings['dashboard_logo_margin_right'] ?? 0 ) ) );
		$logo_margin_bottom      = max( -200, min( 200, (int) ( $settings['dashboard_logo_margin_bottom'] ?? 0 ) ) );
		$logo_margin_left        = max( -200, min( 200, (int) ( $settings['dashboard_logo_margin_left'] ?? 0 ) ) );
		$logo_margin_unit        = in_array( sanitize_key( (string) ( $settings['dashboard_logo_margin_unit'] ?? 'px' ) ), array( 'px', '%', 'rem', 'em' ), true ) ? sanitize_key( (string) $settings['dashboard_logo_margin_unit'] ) : 'px';
		$logo_border_top         = max( 0, min( 20, (int) ( $settings['dashboard_logo_border_top'] ?? 0 ) ) );
		$logo_border_right       = max( 0, min( 20, (int) ( $settings['dashboard_logo_border_right'] ?? 0 ) ) );
		$logo_border_bottom      = max( 0, min( 20, (int) ( $settings['dashboard_logo_border_bottom'] ?? 0 ) ) );
		$logo_border_left        = max( 0, min( 20, (int) ( $settings['dashboard_logo_border_left'] ?? 0 ) ) );
		$logo_border_unit        = in_array( sanitize_key( (string) ( $settings['dashboard_logo_border_unit'] ?? 'px' ) ), array( 'px', 'rem', 'em' ), true ) ? sanitize_key( (string) $settings['dashboard_logo_border_unit'] ) : 'px';
		$logo_border_style_raw   = sanitize_key( (string) ( $settings['dashboard_logo_border_style'] ?? 'none' ) );
		$logo_border_style       = in_array( $logo_border_style_raw, array( 'none', 'solid', 'dashed', 'dotted', 'double' ), true ) ? $logo_border_style_raw : 'none';
		$logo_border_color       = sanitize_hex_color( (string) ( $settings['dashboard_logo_border_color'] ?? '#dddddd' ) ) ?: '#dddddd';
		$logo_radius_tl          = max( 0, min( 200, (int) ( $settings['dashboard_logo_border_radius_tl'] ?? 0 ) ) );
		$logo_radius_tr          = max( 0, min( 200, (int) ( $settings['dashboard_logo_border_radius_tr'] ?? 0 ) ) );
		$logo_radius_br          = max( 0, min( 200, (int) ( $settings['dashboard_logo_border_radius_br'] ?? 0 ) ) );
		$logo_radius_bl          = max( 0, min( 200, (int) ( $settings['dashboard_logo_border_radius_bl'] ?? 0 ) ) );
		$logo_border_radius_unit = in_array( sanitize_key( (string) ( $settings['dashboard_logo_border_radius_unit'] ?? 'px' ) ), array( 'px', '%', 'rem', 'em' ), true ) ? sanitize_key( (string) $settings['dashboard_logo_border_radius_unit'] ) : 'px';
		$bg_enabled   = ! empty( $settings['dashboard_background_enabled'] );
		$padding_enabled = ! empty( $settings['dashboard_padding_enabled'] );
		$title_mode = sanitize_key( (string) ( $settings['dashboard_title_mode'] ?? 'default' ) );
		if ( ! in_array( $title_mode, array( 'default', 'hide', 'custom' ), true ) ) {
			$title_mode = 'default';
		}
		$hide_title = 'hide' === $title_mode;

		$bg_type = sanitize_key( (string) ( $settings['dashboard_background_type'] ?? 'default' ) );
		$bg_type = in_array( $bg_type, array( 'default', 'solid', 'gradient' ), true ) ? $bg_type : 'default';
		$solid_bg = sanitize_hex_color( $settings['dashboard_bg_solid_color'] ?? '' ) ?: '#ffffff';
		$grad_type = sanitize_key( (string) ( $settings['dashboard_bg_gradient_type'] ?? 'linear' ) );
		$grad_type = in_array( $grad_type, array( 'linear', 'radial' ), true ) ? $grad_type : 'linear';
		$grad_angle = isset( $settings['dashboard_bg_gradient_angle'] ) ? (int) $settings['dashboard_bg_gradient_angle'] : 90;
		$grad_angle = max( 0, min( 360, $grad_angle ) );
		$grad_start = sanitize_hex_color( $settings['dashboard_bg_gradient_start'] ?? '' ) ?: '#667eea';
		$grad_end   = sanitize_hex_color( $settings['dashboard_bg_gradient_end'] ?? '' ) ?: '#764ba2';
		$grad_start_pos = isset( $settings['dashboard_bg_gradient_start_position'] ) ? absint( $settings['dashboard_bg_gradient_start_position'] ) : 0;
		$grad_end_pos   = isset( $settings['dashboard_bg_gradient_end_position'] ) ? absint( $settings['dashboard_bg_gradient_end_position'] ) : 100;
		$grad_start_pos = max( 0, min( 100, $grad_start_pos ) );
		$grad_end_pos   = max( 0, min( 100, $grad_end_pos ) );

		$padding_css = '';
		if ( $padding_enabled ) {
			$valid_units = array( 'px', '%', 'rem', 'em', 'vh', 'vw' );
			$top_v = isset( $settings['dashboard_padding_top_value'] ) ? (float) $settings['dashboard_padding_top_value'] : 20;
			$right_v = isset( $settings['dashboard_padding_right_value'] ) ? (float) $settings['dashboard_padding_right_value'] : 20;
			$bottom_v = isset( $settings['dashboard_padding_bottom_value'] ) ? (float) $settings['dashboard_padding_bottom_value'] : 20;
			$left_v = isset( $settings['dashboard_padding_left_value'] ) ? (float) $settings['dashboard_padding_left_value'] : 20;
			$top_u = sanitize_key( (string) ( $settings['dashboard_padding_top_unit'] ?? 'px' ) );
			$right_u = sanitize_key( (string) ( $settings['dashboard_padding_right_unit'] ?? 'px' ) );
			$bottom_u = sanitize_key( (string) ( $settings['dashboard_padding_bottom_unit'] ?? 'px' ) );
			$left_u = sanitize_key( (string) ( $settings['dashboard_padding_left_unit'] ?? 'px' ) );
			$top_u = in_array( $top_u, $valid_units, true ) ? $top_u : 'px';
			$right_u = in_array( $right_u, $valid_units, true ) ? $right_u : 'px';
			$bottom_u = in_array( $bottom_u, $valid_units, true ) ? $bottom_u : 'px';
			$left_u = in_array( $left_u, $valid_units, true ) ? $left_u : 'px';
			$padding_css = $top_v . $top_u . ' ' . $right_v . $right_u . ' ' . $bottom_v . $bottom_u . ' ' . $left_v . $left_u;
		}

		$title_font_family = sanitize_text_field( (string) ( $settings['dashboard_title_font_family'] ?? 'inherit' ) );
		$title_font_size   = sanitize_text_field( (string) ( $settings['dashboard_title_font_size'] ?? '32px' ) );
		if ( ! preg_match( '/^\d+(?:\.\d+)?(px|rem|em)$/', $title_font_size ) ) {
			$title_font_size = '32px';
		}
		$title_font_weight = sanitize_text_field( (string) ( $settings['dashboard_title_font_weight'] ?? '700' ) );
		if ( ! in_array( $title_font_weight, array( '300', '400', '500', '600', '700' ), true ) ) {
			$title_font_weight = '700';
		}
		$title_alignment = sanitize_key( (string) ( $settings['dashboard_title_alignment'] ?? 'left' ) );
		if ( ! in_array( $title_alignment, array( 'left', 'center', 'right' ), true ) ) {
			$title_alignment = 'left';
		}
		$title_color = sanitize_text_field( (string) ( $settings['dashboard_title_color'] ?? '#1d2327' ) );

		$hero_theme = sanitize_key( (string) ( $settings['dashboard_hero_theme'] ?? 'text-left' ) );
		if ( 'classic' === $hero_theme ) {
			$hero_theme = 'text-left';
		}
		if ( ! in_array( $hero_theme, array( 'text-left', 'text-center', 'text-right', 'text-split', 'logo-left', 'logo-top', 'logo-right', 'split' ), true ) ) {
			$hero_theme = 'text-left';
		}
		$hero_bg_type = sanitize_key( (string) ( $settings['dashboard_hero_background_type'] ?? 'solid' ) );
		if ( ! in_array( $hero_bg_type, array( 'solid', 'gradient' ), true ) ) {
			$hero_bg_type = 'solid';
		}
		$hero_bg_solid = sanitize_hex_color( $settings['dashboard_hero_bg_solid_color'] ?? '' ) ?: '#667eea';
		$hero_grad_type = sanitize_key( (string) ( $settings['dashboard_hero_bg_gradient_type'] ?? 'linear' ) );
		if ( ! in_array( $hero_grad_type, array( 'linear', 'radial' ), true ) ) {
			$hero_grad_type = 'linear';
		}
		$hero_grad_angle = isset( $settings['dashboard_hero_bg_gradient_angle'] ) ? (int) $settings['dashboard_hero_bg_gradient_angle'] : 90;
		$hero_grad_angle = max( 0, min( 360, $hero_grad_angle ) );
		$hero_grad_start = sanitize_hex_color( $settings['dashboard_hero_bg_gradient_start'] ?? '' ) ?: '#667eea';
		$hero_grad_end   = sanitize_hex_color( $settings['dashboard_hero_bg_gradient_end'] ?? '' ) ?: '#764ba2';
		$hero_grad_start_pos = isset( $settings['dashboard_hero_bg_gradient_start_position'] ) ? absint( $settings['dashboard_hero_bg_gradient_start_position'] ) : 0;
		$hero_grad_end_pos   = isset( $settings['dashboard_hero_bg_gradient_end_position'] ) ? absint( $settings['dashboard_hero_bg_gradient_end_position'] ) : 100;
		$hero_grad_start_pos = max( 0, min( 100, $hero_grad_start_pos ) );
		$hero_grad_end_pos   = max( 0, min( 100, $hero_grad_end_pos ) );

		$hero_title_font_family = sanitize_text_field( (string) ( $settings['dashboard_hero_title_font_family'] ?? 'inherit' ) );
		$hero_title_font_size   = sanitize_text_field( (string) ( $settings['dashboard_hero_title_font_size'] ?? '28px' ) );
		if ( ! preg_match( '/^\d+(?:\.\d+)?(px|rem|em)$/', $hero_title_font_size ) ) {
			$hero_title_font_size = '28px';
		}
		$hero_title_font_weight = sanitize_text_field( (string) ( $settings['dashboard_hero_title_font_weight'] ?? '700' ) );
		if ( ! in_array( $hero_title_font_weight, array( '300', '400', '500', '600', '700' ), true ) ) {
			$hero_title_font_weight = '700';
		}
		$hero_title_alignment = sanitize_key( (string) ( $settings['dashboard_hero_title_alignment'] ?? 'left' ) );
		if ( ! in_array( $hero_title_alignment, array( 'left', 'center', 'right' ), true ) ) {
			$hero_title_alignment = 'left';
		}
		$hero_title_color = sanitize_text_field( (string) ( $settings['dashboard_hero_title_color'] ?? '#ffffff' ) );

		$background_css = '';
		if ( $bg_enabled && 'default' !== $bg_type ) {
			$background_css = 'gradient' === $bg_type
				? ( 'radial' === $grad_type
					? 'radial-gradient(' . $grad_start . ' ' . $grad_start_pos . '%, ' . $grad_end . ' ' . $grad_end_pos . '%)'
					: 'linear-gradient(' . $grad_angle . 'deg, ' . $grad_start . ' ' . $grad_start_pos . '%, ' . $grad_end . ' ' . $grad_end_pos . '%)' )
				: $solid_bg;
		}

		// Hero spacing.
		$hero_pad_top    = max( 0, min( 200, (int) ( $settings['dashboard_hero_padding_top'] ?? 16 ) ) );
		$hero_pad_right  = max( 0, min( 200, (int) ( $settings['dashboard_hero_padding_right'] ?? 20 ) ) );
		$hero_pad_bottom = max( 0, min( 200, (int) ( $settings['dashboard_hero_padding_bottom'] ?? 16 ) ) );
		$hero_pad_left   = max( 0, min( 200, (int) ( $settings['dashboard_hero_padding_left'] ?? 20 ) ) );
		$hero_pad_unit   = in_array( sanitize_key( (string) ( $settings['dashboard_hero_padding_unit'] ?? 'px' ) ), array( 'px', '%', 'rem', 'em' ), true ) ? sanitize_key( (string) $settings['dashboard_hero_padding_unit'] ) : 'px';
		$hero_mar_top    = max( -200, min( 200, (int) ( $settings['dashboard_hero_margin_top'] ?? 10 ) ) );
		$hero_mar_right  = max( -200, min( 200, (int) ( $settings['dashboard_hero_margin_right'] ?? 0 ) ) );
		$hero_mar_bottom = max( -200, min( 200, (int) ( $settings['dashboard_hero_margin_bottom'] ?? 16 ) ) );
		$hero_mar_left   = max( -200, min( 200, (int) ( $settings['dashboard_hero_margin_left'] ?? 0 ) ) );
		$hero_mar_unit   = in_array( sanitize_key( (string) ( $settings['dashboard_hero_margin_unit'] ?? 'px' ) ), array( 'px', '%', 'rem', 'em' ), true ) ? sanitize_key( (string) $settings['dashboard_hero_margin_unit'] ) : 'px';
		$hero_bdr_top    = max( 0, min( 20, (int) ( $settings['dashboard_hero_border_top'] ?? 0 ) ) );
		$hero_bdr_right  = max( 0, min( 20, (int) ( $settings['dashboard_hero_border_right'] ?? 0 ) ) );
		$hero_bdr_bottom = max( 0, min( 20, (int) ( $settings['dashboard_hero_border_bottom'] ?? 0 ) ) );
		$hero_bdr_left   = max( 0, min( 20, (int) ( $settings['dashboard_hero_border_left'] ?? 0 ) ) );
		$hero_bdr_unit   = in_array( sanitize_key( (string) ( $settings['dashboard_hero_border_unit'] ?? 'px' ) ), array( 'px', 'rem', 'em' ), true ) ? sanitize_key( (string) $settings['dashboard_hero_border_unit'] ) : 'px';
		$hero_bdr_style_raw = sanitize_key( (string) ( $settings['dashboard_hero_border_style'] ?? 'none' ) );
		$hero_bdr_style  = in_array( $hero_bdr_style_raw, array( 'none', 'solid', 'dashed', 'dotted', 'double' ), true ) ? $hero_bdr_style_raw : 'none';
		$hero_bdr_color  = sanitize_hex_color( (string) ( $settings['dashboard_hero_border_color'] ?? '#dddddd' ) ) ?: '#dddddd';
		$hero_rad_tl     = max( 0, min( 200, (int) ( $settings['dashboard_hero_border_radius_tl'] ?? 10 ) ) );
		$hero_rad_tr     = max( 0, min( 200, (int) ( $settings['dashboard_hero_border_radius_tr'] ?? 10 ) ) );
		$hero_rad_br     = max( 0, min( 200, (int) ( $settings['dashboard_hero_border_radius_br'] ?? 10 ) ) );
		$hero_rad_bl     = max( 0, min( 200, (int) ( $settings['dashboard_hero_border_radius_bl'] ?? 10 ) ) );
		$hero_rad_unit   = in_array( sanitize_key( (string) ( $settings['dashboard_hero_border_radius_unit'] ?? 'px' ) ), array( 'px', '%', 'rem', 'em' ), true ) ? sanitize_key( (string) $settings['dashboard_hero_border_radius_unit'] ) : 'px';
		$hero_height     = max( 0, min( 1000, (int) ( $settings['dashboard_hero_height'] ?? 0 ) ) );
		$hero_height_unit = in_array( sanitize_key( (string) ( $settings['dashboard_hero_height_unit'] ?? 'px' ) ), array( 'px', '%', 'rem', 'em', 'vh' ), true ) ? sanitize_key( (string) $settings['dashboard_hero_height_unit'] ) : 'px';
		$hero_min_height = max( 0, min( 1000, (int) ( $settings['dashboard_hero_min_height'] ?? 0 ) ) );
		$hero_min_height_unit = in_array( sanitize_key( (string) ( $settings['dashboard_hero_min_height_unit'] ?? 'px' ) ), array( 'px', '%', 'rem', 'em', 'vh' ), true ) ? sanitize_key( (string) $settings['dashboard_hero_min_height_unit'] ) : 'px';

		$hero_background_css = 'gradient' === $hero_bg_type
			? ( 'radial' === $hero_grad_type
				? 'radial-gradient(' . $hero_grad_start . ' ' . $hero_grad_start_pos . '%, ' . $hero_grad_end . ' ' . $hero_grad_end_pos . '%)'
				: 'linear-gradient(' . $hero_grad_angle . 'deg, ' . $hero_grad_start . ' ' . $hero_grad_start_pos . '%, ' . $hero_grad_end . ' ' . $hero_grad_end_pos . '%)' )
			: $hero_bg_solid;

		$css = '
			.dwm-dashboard-notice-toast {
				position: fixed;
				z-index: 100001;
				min-width: 300px;
				max-width: 460px;
				padding: 12px 14px;
				border-radius: 8px;
				color: #fff;
				box-shadow: 0 12px 28px rgba(0,0,0,0.2);
				white-space: normal;
			}
			.dwm-dashboard-notice-toast.pos-top-right { top: 52px; right: 24px; }
			.dwm-dashboard-notice-toast.pos-top-left { top: 52px; left: 24px; }
			.dwm-dashboard-notice-toast.pos-bottom-right { bottom: 24px; right: 24px; }
			.dwm-dashboard-notice-toast.pos-bottom-left { bottom: 24px; left: 24px; }
			.dwm-dashboard-notice-dismiss {
				position: absolute;
				top: 6px;
				right: 8px;
				background: transparent;
				border: 0;
				color: rgba(255,255,255,0.8);
				font-size: 18px;
				cursor: pointer;
				line-height: 1;
				padding: 0 2px;
			}
			.dwm-dashboard-notice-dismiss:hover { color: #fff; }
			.dwm-dashboard-notice-toast strong {
				display: block;
				margin-bottom: 4px;
			}
			.dwm-dashboard-notice-toast-message {
				white-space: pre-line;
			}
			.dwm-dashboard-notice-toast.is-info { background: #2271b1; }
			.dwm-dashboard-notice-toast.is-success { background: #17833f; }
			.dwm-dashboard-notice-toast.is-warning { background: #b26200; }
			.dwm-dashboard-notice-toast.is-error { background: #b32d2e; }
			.dwm-dashboard-popup-overlay {
				position: fixed;
				inset: 0;
				background: rgba(0, 0, 0, 0.55);
				z-index: 100000;
				display: flex;
				align-items: center;
				justify-content: center;
				padding: 16px;
			}
			.dwm-dashboard-popup-modal {
				width: min(560px, 100%);
				background: #fff;
				border-radius: 12px;
				box-shadow: 0 24px 50px rgba(0,0,0,0.3);
				overflow: hidden;
			}
			.dwm-dashboard-popup-header {
				padding: 14px 16px;
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
				color: #fff;
				display: flex;
				align-items: center;
				justify-content: space-between;
				font-size: 17px;
				font-weight: 700;
			}
			.dwm-dashboard-popup-close {
				border: 0;
				background: transparent;
				color: rgba(255,255,255,0.9);
				font-size: 22px;
				cursor: pointer;
				line-height: 1;
			}
			.dwm-dashboard-popup-body {
				padding: 16px;
				font-size: 14px;
				line-height: 1.5;
				color: #1f2937;
				white-space: pre-line;
			}
			.dwm-dashboard-hero {
				background: ' . $hero_background_css . ';
				color: #fff;
				padding: ' . $hero_pad_top . $hero_pad_unit . ' ' . $hero_pad_right . $hero_pad_unit . ' ' . $hero_pad_bottom . $hero_pad_unit . ' ' . $hero_pad_left . $hero_pad_unit . ';
				border-radius: ' . $hero_rad_tl . $hero_rad_unit . ' ' . $hero_rad_tr . $hero_rad_unit . ' ' . $hero_rad_br . $hero_rad_unit . ' ' . $hero_rad_bl . $hero_rad_unit . ';
				margin: ' . $hero_mar_top . $hero_mar_unit . ' ' . $hero_mar_right . $hero_mar_unit . ' ' . $hero_mar_bottom . $hero_mar_unit . ' ' . $hero_mar_left . $hero_mar_unit . ';
				' . ( 'none' !== $hero_bdr_style ? 'border: ' . $hero_bdr_top . $hero_bdr_unit . ' ' . $hero_bdr_style . ' ' . esc_attr( $hero_bdr_color ) . ';' : '' ) . '
				' . ( $hero_height > 0 ? 'height: ' . $hero_height . $hero_height_unit . ';' : '' ) . '
				' . ( $hero_min_height > 0 ? 'min-height: ' . $hero_min_height . $hero_min_height_unit . ';' : '' ) . '
				box-shadow: 0 10px 26px rgba(0, 0, 0, 0.14);
				display: grid;
				grid-template-columns: 1fr;
				gap: 10px;
			}
			.dwm-dashboard-hero.dwm-dashboard-hero-theme-logo-left {
				grid-template-columns: auto 1fr;
				align-items: center;
				column-gap: 16px;
			}
			.dwm-dashboard-hero.dwm-dashboard-hero-theme-logo-right {
				grid-template-columns: 1fr auto;
				align-items: center;
				column-gap: 16px;
			}
			.dwm-dashboard-hero.dwm-dashboard-hero-theme-logo-top {
				justify-items: center;
				text-align: center;
			}
			.dwm-dashboard-hero.dwm-dashboard-hero-theme-split {
				grid-template-columns: 1fr auto;
				align-items: center;
				column-gap: 18px;
			}
			.dwm-dashboard-hero.dwm-dashboard-hero-theme-split .dwm-dashboard-hero-content {
				order: 1;
			}
			.dwm-dashboard-hero.dwm-dashboard-hero-theme-split .dwm-dashboard-hero-logo-wrap {
				order: 2;
			}
			.dwm-dashboard-hero.dwm-dashboard-hero-theme-text-center {
				text-align: center;
				justify-items: center;
			}
			.dwm-dashboard-hero.dwm-dashboard-hero-theme-text-right {
				text-align: right;
				justify-items: end;
			}
			.dwm-dashboard-hero.dwm-dashboard-hero-theme-text-split .dwm-dashboard-hero-title {
				margin-bottom: 0;
			}
			.dwm-dashboard-hero.dwm-dashboard-hero-theme-text-split .dwm-dashboard-hero-content {
				display: grid;
				grid-template-columns: minmax(220px, auto) minmax(0, 1fr);
				align-items: start;
				column-gap: 24px;
				row-gap: 12px;
				width: 100%;
			}
			.dwm-dashboard-hero.dwm-dashboard-hero-theme-text-split .dwm-dashboard-hero-message {
				margin-top: 0;
			}
			.dwm-dashboard-hero.dwm-dashboard-hero-theme-logo-right .dwm-dashboard-hero-content {
				order: 1;
			}
			.dwm-dashboard-hero.dwm-dashboard-hero-theme-logo-right .dwm-dashboard-hero-logo-wrap {
				order: 2;
			}
			.dwm-dashboard-hero-content {
				min-width: 0;
			}
			.dwm-dashboard-hero-title {
				margin: 0 0 6px;
				color: #fff;
				font-size: 21px;
				line-height: 1.2;
			}
			.dwm-dashboard-hero-message {
				margin: 0;
				color: rgba(255, 255, 255, 0.92);
			}
			.dwm-dashboard-hero-message p {
				margin: 0 0 10px;
			}
			.dwm-dashboard-hero-message p:last-child {
				margin-bottom: 0;
			}
			.dwm-dashboard-hero-logo-wrap {
				display: flex;
				align-items: center;
				justify-content: center;
			}
			.dwm-dashboard-hero-logo-wrap .dwm-dashboard-logo-wrap {
				width: auto;
				max-width: 100%;
				display: inline-flex;
				align-items: center;
				justify-content: center;
			}
			.dwm-dashboard-logo {
				max-width: none;
				height: ' . $logo_height . $logo_height_unit . ';
				width: auto;
				display: block;
				margin: 0;
				padding: ' . $logo_padding_top . $logo_padding_unit . ' ' . $logo_padding_right . $logo_padding_unit . ' ' . $logo_padding_bottom . $logo_padding_unit . ' ' . $logo_padding_left . $logo_padding_unit . ';
			}
			.dwm-dashboard-logo-link {
				display: inline-block;
				max-width: 100%;
			}
			.dwm-dashboard-logo-wrap {
				display: inline-block;
				margin: ' . $logo_margin_top . $logo_margin_unit . ' ' . $logo_margin_right . $logo_margin_unit . ' ' . $logo_margin_bottom . $logo_margin_unit . ' ' . $logo_margin_left . $logo_margin_unit . ';
				' . ( 'gradient' === $logo_bg_type ? 'background:' . ( 'radial' === $logo_bg_grad_type ? 'radial-gradient(' : 'linear-gradient(' . $logo_bg_grad_angle . 'deg,' ) . esc_attr( $logo_bg_grad_start ) . ' ' . $logo_bg_grad_start_pos . '%,' . esc_attr( $logo_bg_grad_end ) . ' ' . $logo_bg_grad_end_pos . '%);' : ( 'solid' === $logo_bg_type && $logo_bg_solid ? 'background-color:' . esc_attr( $logo_bg_solid ) . ';' : '' ) ) . '
				' . ( 'none' !== $logo_border_style ? 'border-top:' . $logo_border_top . $logo_border_unit . ' ' . $logo_border_style . ' ' . esc_attr( $logo_border_color ) . ';border-right:' . $logo_border_right . $logo_border_unit . ' ' . $logo_border_style . ' ' . esc_attr( $logo_border_color ) . ';border-bottom:' . $logo_border_bottom . $logo_border_unit . ' ' . $logo_border_style . ' ' . esc_attr( $logo_border_color ) . ';border-left:' . $logo_border_left . $logo_border_unit . ' ' . $logo_border_style . ' ' . esc_attr( $logo_border_color ) . ';' : '' ) . '
				' . ( ( $logo_radius_tl + $logo_radius_tr + $logo_radius_br + $logo_radius_bl ) > 0 ? 'border-radius:' . $logo_radius_tl . $logo_border_radius_unit . ' ' . $logo_radius_tr . $logo_border_radius_unit . ' ' . $logo_radius_br . $logo_border_radius_unit . ' ' . $logo_radius_bl . $logo_border_radius_unit . ';' : '' ) . '
			}
			.dwm-dashboard-logo-wrap--hero {
				width: auto;
				max-width: 100%;
				display: inline-flex;
			}
			.dwm-dashboard-logo-wrap--standalone {
				display: block;
				position: absolute;
				top: 100px;
				z-index: 10;
			}
			.dwm-dashboard-logo-wrap--standalone.dwm-dashboard-logo-wrap--align-left {
				left: 40px;
			}
			.dwm-dashboard-logo-wrap--standalone.dwm-dashboard-logo-wrap--align-center {
				left: 50%;
				transform: translateX(-50%);
			}
			.dwm-dashboard-logo-wrap--standalone.dwm-dashboard-logo-wrap--align-right {
				right: 40px;
			}
			body.index-php.dwm-dashboard-title-hidden .wrap > h1 {
				display: none !important;
			}
			body.index-php.dwm-dashboard-title-hidden .dwm-dashboard-new-widget-btn {
				position: fixed;
				top: 52px;
				right: 20px;
				z-index: 100001;
				margin-left: 0;
			}
			@media (max-width: 782px) {
				.dwm-dashboard-hero.dwm-dashboard-hero-theme-logo-left,
				.dwm-dashboard-hero.dwm-dashboard-hero-theme-logo-right,
				.dwm-dashboard-hero.dwm-dashboard-hero-theme-split,
				.dwm-dashboard-hero.dwm-dashboard-hero-theme-text-split .dwm-dashboard-hero-content {
					grid-template-columns: 1fr;
				}
				.dwm-dashboard-hero.dwm-dashboard-hero-theme-logo-right .dwm-dashboard-hero-content,
				.dwm-dashboard-hero.dwm-dashboard-hero-theme-logo-right .dwm-dashboard-hero-logo-wrap,
				.dwm-dashboard-hero.dwm-dashboard-hero-theme-split .dwm-dashboard-hero-content,
				.dwm-dashboard-hero.dwm-dashboard-hero-theme-split .dwm-dashboard-hero-logo-wrap {
					order: initial;
				}
				.dwm-dashboard-notice-toast {
					left: 16px;
					right: 16px;
					min-width: 0;
					max-width: none;
				}
			}
		';

		if ( '' !== $background_css || '' !== $padding_css ) {
			$css .= ( '' !== $background_css
					? 'body.index-php{background:' . $background_css . '!important;background-attachment:fixed!important;}'
					  . 'body.index-php #wpbody-content{background:transparent!important;min-height:calc(100vh - 32px);}'
					: '' ) .
				( '' !== $padding_css ? 'body.index-php #wpbody-content{padding:' . $padding_css . ';}' : '' ) .
				'body.index-php #dashboard-widgets-wrap{margin-top:10px;}';
		}

		wp_add_inline_style( 'dwm-admin', $css );

		$payload = array(
			'logoEnabled'     => $logo_enabled,
			'hideTitle'       => $hide_title,
			'titleMode'       => $title_mode,
			'titleText'       => sanitize_text_field( (string) ( $settings['dashboard_title_text'] ?? '' ) ),
			'titleFontFamily' => $title_font_family,
			'titleFontSize'   => $title_font_size,
			'titleFontWeight' => $title_font_weight,
			'titleAlignment'  => $title_alignment,
			'titleColor'      => $title_color,
			'logoUrl'         => $logo_url,
			'logoAlignment'   => $logo_alignment,
			'logoLinkEnabled' => ! empty( $logo_link_url ),
			'logoLinkUrl'     => $logo_link_url,
			'logoLinkNewTab'  => ! empty( $settings['dashboard_logo_link_new_tab'] ),
			'heroEnabled'     => ! empty( $settings['dashboard_hero_enabled'] ),
			'heroTheme'       => $hero_theme,
			'heroTitle'       => sanitize_text_field( (string) ( $settings['dashboard_hero_title'] ?? '' ) ),
			'heroTitleFontFamily' => $hero_title_font_family,
			'heroTitleFontSize'   => $hero_title_font_size,
			'heroTitleFontWeight' => $hero_title_font_weight,
			'heroTitleAlignment'  => $hero_title_alignment,
			'heroTitleColor'      => $hero_title_color,
			'heroMessage'     => wp_kses_post( (string) ( $settings['dashboard_hero_message'] ?? '' ) ),
			'noticeEnabled'     => ! empty( $settings['dashboard_notice_enabled'] ),
			'noticeType'        => sanitize_key( (string) ( $settings['dashboard_notice_type'] ?? 'toast' ) ),
			'noticeLevel'       => sanitize_key( (string) ( $settings['dashboard_notice_level'] ?? 'info' ) ),
			'noticeTitle'       => sanitize_text_field( (string) ( $settings['dashboard_notice_title'] ?? '' ) ),
			'noticeMessage'     => sanitize_textarea_field( (string) ( $settings['dashboard_notice_message'] ?? '' ) ),
			'noticeDismissible' => ! empty( $settings['dashboard_notice_dismissible'] ),
			'noticeAutoDismiss' => max( 0, min( 60, (int) ( $settings['dashboard_notice_auto_dismiss'] ?? 6 ) ) ),
			'noticePosition'    => sanitize_key( (string) ( $settings['dashboard_notice_position'] ?? 'bottom-right' ) ),
			'noticeFrequency'   => sanitize_key( (string) ( $settings['dashboard_notice_frequency'] ?? 'always' ) ),
		);

		$script = '(function(){
			var cfg = ' . wp_json_encode( $payload ) . ';
			if (!cfg) { return; }
			function safeText(v){ return String(v || ""); }
			function buildLogoNode(contextClass) {
				if (!cfg.logoEnabled || !cfg.logoUrl) { return null; }

				var wrap = document.createElement("div");
				wrap.className = "dwm-dashboard-logo-wrap dwm-dashboard-logo-wrap--align-" + (cfg.logoAlignment || "left") + " " + (contextClass || "dwm-dashboard-logo-wrap--standalone");
				var logo = document.createElement("img");
				logo.className = "dwm-dashboard-logo";
				logo.src = cfg.logoUrl;
				logo.alt = "Dashboard Logo";

				if (cfg.logoLinkEnabled && cfg.logoLinkUrl) {
					var logoLink = document.createElement("a");
					logoLink.href = cfg.logoLinkUrl;
					logoLink.className = "dwm-dashboard-logo-link";
					if (cfg.logoLinkNewTab) {
						logoLink.target = "_blank";
						logoLink.rel = "noopener noreferrer";
					}
					logoLink.appendChild(logo);
					wrap.appendChild(logoLink);
				} else {
					wrap.appendChild(logo);
				}

				return wrap;
			}
			function appendNoticeMessage(container, message) {
				var body = document.createElement("div");
				body.className = "dwm-dashboard-notice-toast-message";
				body.textContent = safeText(message || "");
				container.appendChild(body);
			}
			function run(){
				var wrap = document.querySelector("#wpbody-content .wrap");
				if (!wrap) { return; }
				var h1 = wrap.querySelector("h1");

				if (cfg.hideTitle) { document.body.classList.add("dwm-dashboard-title-hidden"); }

				if (h1 && cfg.titleMode === "custom" && cfg.titleText) {
					h1.textContent = safeText(cfg.titleText);
					h1.style.fontFamily = cfg.titleFontFamily || "inherit";
					h1.style.fontSize = cfg.titleFontSize || "32px";
					h1.style.fontWeight = cfg.titleFontWeight || "700";
					h1.style.textAlign = cfg.titleAlignment || "left";
					h1.style.width = "100%";
					if (cfg.titleColor && cfg.titleColor.indexOf("gradient") !== -1) {
						h1.style.backgroundImage = cfg.titleColor;
						h1.style.webkitBackgroundClip = "text";
						h1.style.backgroundClip = "text";
						h1.style.color = "transparent";
						h1.style.webkitTextFillColor = "transparent";
					} else {
						h1.style.color = cfg.titleColor || "";
					}
				}

				var heroTheme = String(cfg.heroTheme || "text-left");
				if (heroTheme === "classic") { heroTheme = "text-left"; }
				var logoThemes = ["logo-left", "logo-top", "logo-right", "split"];
				var hasLogoForHero = cfg.logoEnabled && cfg.logoUrl;
				if (!hasLogoForHero && logoThemes.indexOf(heroTheme) !== -1) {
					heroTheme = "text-left";
				}

				if (cfg.logoEnabled && cfg.logoUrl && !cfg.heroEnabled && !document.getElementById("dwm-dashboard-custom-logo")) {
					var logoWrap = buildLogoNode("dwm-dashboard-logo-wrap--standalone");
					if (!logoWrap) { return; }
					var standaloneLogo = logoWrap.querySelector(".dwm-dashboard-logo");
					if (standaloneLogo) {
						standaloneLogo.id = "dwm-dashboard-custom-logo";
					}
					if (h1 && h1.nextSibling) { h1.parentNode.insertBefore(logoWrap, h1.nextSibling); }
					else if (h1 && h1.parentNode) { h1.parentNode.appendChild(logoWrap); }
					else { wrap.prepend(logoWrap); }
				}

				if (cfg.heroEnabled && !document.getElementById("dwm-dashboard-hero")) {
					var hero = document.createElement("section");
					hero.id = "dwm-dashboard-hero";
					hero.className = "dwm-dashboard-hero dwm-dashboard-hero-theme-" + heroTheme;
					var heroContent = document.createElement("div");
					heroContent.className = "dwm-dashboard-hero-content";
					var heroTitle = document.createElement("h2");
					heroTitle.className = "dwm-dashboard-hero-title";
					heroTitle.textContent = safeText(cfg.heroTitle || "Dashboard");
					heroTitle.style.fontFamily = cfg.heroTitleFontFamily || "inherit";
					heroTitle.style.fontSize = cfg.heroTitleFontSize || "28px";
					heroTitle.style.fontWeight = cfg.heroTitleFontWeight || "700";
					heroTitle.style.textAlign = cfg.heroTitleAlignment || "left";
					if (cfg.heroTitleColor && cfg.heroTitleColor.indexOf("gradient") !== -1) {
						heroTitle.style.backgroundImage = cfg.heroTitleColor;
						heroTitle.style.webkitBackgroundClip = "text";
						heroTitle.style.backgroundClip = "text";
						heroTitle.style.color = "transparent";
						heroTitle.style.webkitTextFillColor = "transparent";
					} else {
						heroTitle.style.color = cfg.heroTitleColor || "#fff";
					}
					var heroMessage = document.createElement("div");
					heroMessage.className = "dwm-dashboard-hero-message";
					heroMessage.innerHTML = String(cfg.heroMessage || "");
					heroContent.appendChild(heroTitle);
					heroContent.appendChild(heroMessage);

					if (logoThemes.indexOf(heroTheme) !== -1 && cfg.logoEnabled && cfg.logoUrl) {
						var heroLogoWrap = document.createElement("div");
						heroLogoWrap.className = "dwm-dashboard-hero-logo-wrap";
						var heroLogoNode = buildLogoNode("dwm-dashboard-logo-wrap--hero");
						if (heroLogoNode) {
							heroLogoWrap.appendChild(heroLogoNode);
						}
						hero.appendChild(heroLogoWrap);
					}
					hero.appendChild(heroContent);

					var widgetsWrap = document.getElementById("dashboard-widgets-wrap");
					if (widgetsWrap && widgetsWrap.parentNode) { widgetsWrap.parentNode.insertBefore(hero, widgetsWrap); } else { wrap.appendChild(hero); }
				}

				if (cfg.noticeEnabled && cfg.noticeMessage) {
					var freq = cfg.noticeFrequency || "always";
					var storageKey = "dwm_notice_seen";
					var shouldShow = true;
					if (freq === "once-session") {
						shouldShow = !window.sessionStorage.getItem(storageKey);
						if (shouldShow) { window.sessionStorage.setItem(storageKey, "1"); }
					} else if (freq === "once-day") {
						var lastSeen = window.localStorage.getItem(storageKey);
						var today = new Date().toDateString();
						shouldShow = lastSeen !== today;
						if (shouldShow) { window.localStorage.setItem(storageKey, today); }
					}
					if (shouldShow) {
					if (cfg.noticeType === "alert") {
						var alertBox = document.createElement("div");
						alertBox.className = "notice notice-" + (cfg.noticeLevel || "info") + (cfg.noticeDismissible ? " is-dismissible" : "");
						var p = document.createElement("p");
						p.style.whiteSpace = "pre-line";
						p.textContent = (cfg.noticeTitle ? (cfg.noticeTitle + ": ") : "") + cfg.noticeMessage;
						alertBox.appendChild(p);
						var target = document.querySelector("#wpbody-content .wrap");
						if (target) { target.prepend(alertBox); }
					} else if (cfg.noticeType === "popup") {
						var overlay = document.createElement("div");
						overlay.className = "dwm-dashboard-popup-overlay";
						var modal = document.createElement("div");
						modal.className = "dwm-dashboard-popup-modal";
						var modalHeader = document.createElement("div");
						modalHeader.className = "dwm-dashboard-popup-header";
						var modalTitle = document.createElement("span");
						modalTitle.textContent = safeText(cfg.noticeTitle || "Notice");
						var modalClose = document.createElement("button");
						modalClose.type = "button";
						modalClose.className = "dwm-dashboard-popup-close";
						modalClose.setAttribute("aria-label", "Close");
						modalClose.innerHTML = "&times;";
						var modalBody = document.createElement("div");
						modalBody.className = "dwm-dashboard-popup-body";
						modalBody.textContent = safeText(cfg.noticeMessage);
						modalHeader.appendChild(modalTitle);
						modalHeader.appendChild(modalClose);
						modal.appendChild(modalHeader);
						modal.appendChild(modalBody);
						overlay.appendChild(modal);
						document.body.appendChild(overlay);
						overlay.addEventListener("click", function(e){ if (e.target === overlay || e.target.classList.contains("dwm-dashboard-popup-close")) { overlay.remove(); }});
					} else {
						var posClass = "pos-" + (cfg.noticePosition || "bottom-right");
						var toast = document.createElement("div");
						toast.className = "dwm-dashboard-notice-toast is-" + (cfg.noticeLevel || "info") + " " + posClass;
						if (cfg.noticeDismissible) {
							var dismissBtn = document.createElement("button");
							dismissBtn.type = "button";
							dismissBtn.className = "dwm-dashboard-notice-dismiss";
							dismissBtn.setAttribute("aria-label", "Dismiss");
							dismissBtn.innerHTML = "&times;";
							dismissBtn.addEventListener("click", function(){ if (toast.parentNode) { toast.parentNode.removeChild(toast); } });
							toast.appendChild(dismissBtn);
						}
						if (cfg.noticeTitle) {
							var toastTitle = document.createElement("strong");
							toastTitle.textContent = safeText(cfg.noticeTitle);
							toast.appendChild(toastTitle);
						}
						appendNoticeMessage(toast, cfg.noticeMessage);
						document.body.appendChild(toast);
						var autoDismiss = parseInt(cfg.noticeAutoDismiss, 10);
						if (autoDismiss > 0) {
							window.setTimeout(function(){ if (toast && toast.parentNode) { toast.parentNode.removeChild(toast); } }, autoDismiss * 1000);
						}
					}
					}
				}
			}
			if (document.readyState === "loading") { document.addEventListener("DOMContentLoaded", run); } else { run(); }
		})();';

		wp_add_inline_script( 'dwm-admin', $script, 'after' );
	}
}
