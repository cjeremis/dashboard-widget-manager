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

		// WP Dashboard (index.php) — static styles for FAB, picker, hero, toasts, etc.
		if ( 'index.php' === $hook ) {
			$ver = file_exists( DWM_PLUGIN_DIR . 'assets/minimized/css/wp-dashboard.min.css' )
				? (string) filemtime( DWM_PLUGIN_DIR . 'assets/minimized/css/wp-dashboard.min.css' )
				: DWM_VERSION;
			wp_enqueue_style(
				'dwm-wp-dashboard',
				DWM_PLUGIN_URL . 'assets/minimized/css/wp-dashboard.min.css',
				array( 'dwm-admin' ),
				$ver,
				'all'
			);
		}

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

		// Always show floating action button at bottom-right of dashboard.
		$logo_url_fab  = esc_url( DWM_PLUGIN_URL . 'assets/images/logo.png' );
		$branding_url  = esc_url( admin_url( 'admin.php?page=dwm-customize-dashboard' ) );
		$has_existing  = ! empty( $widgets );
		$manage_url    = esc_url( admin_url( 'admin.php?page=dashboard-widget-manager' ) );

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
									<div class="fab-panel-menu-item" id="dwm-fab-add-widget">
										<span class="dashicons dashicons-plus-alt2"></span>
										<?php esc_html_e( 'Add Widget', 'dashboard-widget-manager' ); ?>
									</div>
								</li>
								<?php if ( $has_existing ) : ?>
								<li>
									<a href="<?php echo $manage_url; ?>">
										<div class="fab-panel-menu-item">
											<span class="dashicons dashicons-list-view"></span>
											<?php esc_html_e( 'Manage Widgets', 'dashboard-widget-manager' ); ?>
										</div>
									</a>
								</li>
								<?php endif; ?>
								<li>
									<a href="<?php echo $branding_url; ?>">
										<div class="fab-panel-menu-item">
											<span class="dashicons dashicons-art"></span>
											<?php esc_html_e( 'Branding', 'dashboard-widget-manager' ); ?>
										</div>
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

						icon.addEventListener('click', function(e) {
							e.stopPropagation();
							fab.classList.toggle('is-open');
						});

						document.addEventListener('click', function(e) {
							if (fab.classList.contains('is-open') && !fab.contains(e.target)) {
								fab.classList.remove('is-open');
							}
						});

						// "Add Widget" opens the picker modal, or navigates to create page if no widgets exist.
						var addBtn = document.getElementById('dwm-fab-add-widget');
						if (addBtn) {
							addBtn.addEventListener('click', function() {
								fab.classList.remove('is-open');
								var pickerBtn = document.getElementById('dwm-open-widget-picker');
								if (pickerBtn) {
									pickerBtn.click();
								} else {
									window.location.href = '<?php echo $create_url; ?>';
								}
							});
						}
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

		// Always render the picker modal when widgets exist.
		if ( ! empty( $widgets ) ) {
			add_action( 'admin_footer', array( $this, 'render_widget_picker_modal' ) );

			// Hidden trigger button for the FAB "Add Widget" action.
			wp_add_inline_script( 'jquery', '
				document.addEventListener("DOMContentLoaded", function() {
					if (document.getElementById("dwm-open-widget-picker")) { return; }
					var btn = document.createElement("button");
					btn.type = "button";
					btn.id = "dwm-open-widget-picker";
					btn.style.display = "none";
					document.body.appendChild(btn);
				});
			' );
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
	 * Hide Help and/or Screen Options on the dashboard page when enabled in settings.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function hide_admin_chrome( $hook ) {
		if ( 'index.php' !== $hook ) {
			return;
		}

		$data             = DWM_Data::get_instance();
		$settings         = $data->get_settings();
		$hide_help        = ! empty( $settings['hide_help_dropdown'] );
		$hide_screen_opts = ! empty( $settings['hide_screen_options'] );
		$hide_notices     = ! empty( $settings['hide_inline_notices'] );

		$css = '';
		if ( $hide_help ) {
			$css .= '#contextual-help-link-wrap { display: none !important; }';
		}
		if ( $hide_screen_opts ) {
			$css .= '#screen-options-link-wrap { display: none !important; }';
		}
		if ( $hide_notices ) {
			$css .= 'body.index-php .notice, body.index-php .updated, body.index-php .error { display: none !important; }';
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
					<button type="button" class="dwm-modal-close" id="dwm-picker-close" aria-label="Close modal">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
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

		$bg_type = sanitize_key( (string) ( $settings['dashboard_background_type'] ?? 'solid' ) );
		$bg_type = in_array( $bg_type, array( 'solid', 'gradient' ), true ) ? $bg_type : 'solid';
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

		$hero_logo_mode = sanitize_key( (string) ( $settings['dashboard_hero_logo_mode'] ?? 'disabled' ) );
		if ( ! in_array( $hero_logo_mode, array( 'disabled', 'hero_logo', 'logo_only', 'hero_only' ), true ) ) {
			$hero_logo_mode = 'disabled';
		}
		$hero_mode_has_hero = in_array( $hero_logo_mode, array( 'hero_logo', 'hero_only' ), true );

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

		$hero_message_font_family = sanitize_text_field( (string) ( $settings['dashboard_hero_message_font_family'] ?? 'inherit' ) );
		$hero_message_font_size   = sanitize_text_field( (string) ( $settings['dashboard_hero_message_font_size'] ?? '24px' ) );
		if ( ! preg_match( '/^\d+(?:\.\d+)?(px|rem|em)$/', $hero_message_font_size ) ) {
			$hero_message_font_size = '24px';
		}
		$hero_message_font_weight = sanitize_text_field( (string) ( $settings['dashboard_hero_message_font_weight'] ?? '700' ) );
		if ( ! in_array( $hero_message_font_weight, array( '300', '400', '500', '600', '700' ), true ) ) {
			$hero_message_font_weight = '700';
		}
		$hero_message_alignment = sanitize_key( (string) ( $settings['dashboard_hero_message_alignment'] ?? 'left' ) );
		if ( ! in_array( $hero_message_alignment, array( 'left', 'center', 'right' ), true ) ) {
			$hero_message_alignment = 'left';
		}
		$hero_message_color = sanitize_text_field( (string) ( $settings['dashboard_hero_message_color'] ?? '#ffffff' ) );

		$background_css = '';
		if ( $bg_enabled && in_array( $bg_type, array( 'solid', 'gradient' ), true ) ) {
			$background_css = 'gradient' === $bg_type
				? ( 'radial' === $grad_type
					? 'radial-gradient(' . $grad_start . ' ' . $grad_start_pos . '%, ' . $grad_end . ' ' . $grad_end_pos . '%)'
					: 'linear-gradient(' . $grad_angle . 'deg, ' . $grad_start . ' ' . $grad_start_pos . '%, ' . $grad_end . ' ' . $grad_end_pos . '%)' )
				: $solid_bg;
		}

		$hero_height      = max( 1, min( 1000, (int) ( $settings['dashboard_hero_height'] ?? 1 ) ) );
		$hero_height_unit = in_array( sanitize_key( (string) ( $settings['dashboard_hero_height_unit'] ?? 'px' ) ), array( 'px', '%', 'rem', 'em', 'vh' ), true ) ? sanitize_key( (string) $settings['dashboard_hero_height_unit'] ) : 'px';
		$hero_min_height  = max( 1, min( 1000, (int) ( $settings['dashboard_hero_min_height'] ?? 1 ) ) );
		$hero_min_height_unit = in_array( sanitize_key( (string) ( $settings['dashboard_hero_min_height_unit'] ?? 'px' ) ), array( 'px', '%', 'rem', 'em', 'vh' ), true ) ? sanitize_key( (string) $settings['dashboard_hero_min_height_unit'] ) : 'px';

		// Dynamic inline CSS — only properties that depend on PHP settings values.
		$css = '';

		// Hero height/min-height only (structural, not styled).
		if ( $hero_mode_has_hero && ( $hero_height > 0 || $hero_min_height > 0 ) ) {
			$css .= '.dwm-dashboard-hero{'
				. ( $hero_height > 0 ? 'height:' . $hero_height . $hero_height_unit . ';' : '' )
				. ( $hero_min_height > 0 ? 'min-height:' . $hero_min_height . $hero_min_height_unit . ';' : '' )
				. '}';
		}

		// Style target: logo settings apply to the hero when hero mode is active, or to the logo wrap otherwise.
		$style_target = $hero_mode_has_hero ? '.dwm-dashboard-hero' : '.dwm-dashboard-logo-wrap';

		// Logo dynamic properties (height, padding, margin, background, border from settings).
		$css .= '.dwm-dashboard-logo{'
			. 'height:' . $logo_height . $logo_height_unit . ';'
			. '}';

		$css .= $style_target . '{'
			. 'padding:' . $logo_padding_top . $logo_padding_unit . ' ' . $logo_padding_right . $logo_padding_unit . ' ' . $logo_padding_bottom . $logo_padding_unit . ' ' . $logo_padding_left . $logo_padding_unit . ';'
			. 'margin:' . $logo_margin_top . $logo_margin_unit . ' ' . $logo_margin_right . $logo_margin_unit . ' ' . $logo_margin_bottom . $logo_margin_unit . ' ' . $logo_margin_left . $logo_margin_unit . ';'
			. ( 'gradient' === $logo_bg_type ? 'background:' . ( 'radial' === $logo_bg_grad_type ? 'radial-gradient(' : 'linear-gradient(' . $logo_bg_grad_angle . 'deg,' ) . esc_attr( $logo_bg_grad_start ) . ' ' . $logo_bg_grad_start_pos . '%,' . esc_attr( $logo_bg_grad_end ) . ' ' . $logo_bg_grad_end_pos . '%);' : ( 'solid' === $logo_bg_type && $logo_bg_solid ? 'background-color:' . esc_attr( $logo_bg_solid ) . ';' : '' ) )
			. ( 'none' !== $logo_border_style ? 'border-top:' . $logo_border_top . $logo_border_unit . ' ' . $logo_border_style . ' ' . esc_attr( $logo_border_color ) . ';border-right:' . $logo_border_right . $logo_border_unit . ' ' . $logo_border_style . ' ' . esc_attr( $logo_border_color ) . ';border-bottom:' . $logo_border_bottom . $logo_border_unit . ' ' . $logo_border_style . ' ' . esc_attr( $logo_border_color ) . ';border-left:' . $logo_border_left . $logo_border_unit . ' ' . $logo_border_style . ' ' . esc_attr( $logo_border_color ) . ';' : '' )
			. ( ( $logo_radius_tl + $logo_radius_tr + $logo_radius_br + $logo_radius_bl ) > 0 ? 'border-radius:' . $logo_radius_tl . $logo_border_radius_unit . ' ' . $logo_radius_tr . $logo_border_radius_unit . ' ' . $logo_radius_br . $logo_border_radius_unit . ' ' . $logo_radius_bl . $logo_border_radius_unit . ';' : '' )
			. '}';

		// Background and padding overrides (dynamic from settings).
		if ( '' !== $background_css || '' !== $padding_css ) {
			$css .= ( '' !== $background_css
					? '#wpbody-content{background:' . $background_css . '!important;background-attachment:fixed!important;}'
					: '' )
				. ( '' !== $padding_css ? '#wpbody-content{padding:' . $padding_css . ';box-sizing:border-box;}' : '' )
				. 'body.index-php #wpbody-content .wrap{overflow:hidden;}'
				. 'body.index-php #dashboard-widgets-wrap{margin-top:10px;}';
		}

		wp_add_inline_style( 'dwm-wp-dashboard', $css );

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
			'heroEnabled'     => $hero_mode_has_hero,
			'heroTitle'       => sanitize_text_field( (string) ( $settings['dashboard_hero_title'] ?? '' ) ),
			'heroTitleFontFamily' => $hero_title_font_family,
			'heroTitleFontSize'   => $hero_title_font_size,
			'heroTitleFontWeight' => $hero_title_font_weight,
			'heroTitleAlignment'  => $hero_title_alignment,
			'heroTitleColor'      => $hero_title_color,
			'heroMessage'            => wp_kses_post( (string) ( $settings['dashboard_hero_message'] ?? '' ) ),
			'heroMessageFontFamily'  => $hero_message_font_family,
			'heroMessageFontSize'    => $hero_message_font_size,
			'heroMessageFontWeight'  => $hero_message_font_weight,
			'heroMessageAlignment'   => $hero_message_alignment,
			'heroMessageColor'       => $hero_message_color,
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

				var container = document.createElement("div");
				container.className = "dwm-dashboard-logo-container dwm-dashboard-logo-container--align-" + (cfg.logoAlignment || "left") + " " + (contextClass || "dwm-dashboard-logo-container--standalone");

				var wrap = document.createElement("div");
				wrap.className = "dwm-dashboard-logo-wrap";

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

				container.appendChild(wrap);
				return container;
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

				var alignment = cfg.logoAlignment || "left";
				var hasLogoForHero = cfg.logoEnabled && cfg.logoUrl;

				if (cfg.heroEnabled && !document.getElementById("dwm-dashboard-hero")) {
					var hero = document.createElement("section");
					hero.id = "dwm-dashboard-hero";
					hero.className = "dwm-dashboard-hero dwm-dashboard-hero--align-" + alignment
						+ (hasLogoForHero ? " dwm-dashboard-hero--has-logo" : "");
					var heroContent = document.createElement("div");
					heroContent.className = "dwm-dashboard-hero-content";
					if (cfg.heroTitle) {
					var heroTitle = document.createElement("h2");
					heroTitle.className = "dwm-dashboard-hero-title";
					heroTitle.textContent = safeText(cfg.heroTitle);
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
					heroContent.appendChild(heroTitle);
				}
					var heroMessageText = String(cfg.heroMessage || "").trim();
					if (heroMessageText) {
						var heroMessage = document.createElement("div");
						heroMessage.className = "dwm-dashboard-hero-message";
						heroMessage.innerHTML = heroMessageText;
						heroMessage.style.fontFamily = cfg.heroMessageFontFamily || "inherit";
						heroMessage.style.fontSize = cfg.heroMessageFontSize || "24px";
						heroMessage.style.fontWeight = cfg.heroMessageFontWeight || "700";
						heroMessage.style.textAlign = cfg.heroMessageAlignment || "left";
						if (cfg.heroMessageColor && cfg.heroMessageColor.indexOf("gradient") !== -1) {
							heroMessage.style.backgroundImage = cfg.heroMessageColor;
							heroMessage.style.webkitBackgroundClip = "text";
							heroMessage.style.backgroundClip = "text";
							heroMessage.style.color = "transparent";
							heroMessage.style.webkitTextFillColor = "transparent";
						} else {
							heroMessage.style.color = cfg.heroMessageColor || "#fff";
						}
						heroContent.appendChild(heroMessage);
					}

					// Embed logo inside hero when hero_logo mode has a logo.
					if (hasLogoForHero) {
						var heroLogoWrap = document.createElement("div");
						heroLogoWrap.className = "dwm-dashboard-hero-logo-wrap";
						var heroLogoNode = buildLogoNode("dwm-dashboard-logo-container--hero");
						if (heroLogoNode) {
							heroLogoWrap.appendChild(heroLogoNode);
						}
						// Logo left/right: logo wrap precedes or follows content.
						if (alignment === "right") {
							hero.appendChild(heroContent);
							hero.appendChild(heroLogoWrap);
						} else {
							hero.appendChild(heroLogoWrap);
							hero.appendChild(heroContent);
						}
					} else {
						hero.appendChild(heroContent);
					}

					if (h1) { h1.parentNode.insertBefore(hero, h1); }
					else { wrap.prepend(hero); }
				} else if (cfg.logoEnabled && cfg.logoUrl && !document.getElementById("dwm-dashboard-custom-logo")) {
					var logoWrap = buildLogoNode("dwm-dashboard-logo-container--standalone");
					if (logoWrap) {
						var standaloneLogo = logoWrap.querySelector(".dwm-dashboard-logo");
						if (standaloneLogo) {
							standaloneLogo.id = "dwm-dashboard-custom-logo";
						}
						if (h1) { h1.parentNode.insertBefore(logoWrap, h1); }
						else { wrap.prepend(logoWrap); }
					}
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
						var lastSeenTs = lastSeen ? parseInt(lastSeen, 10) : 0;
						shouldShow = isNaN(lastSeenTs) || (Date.now() - lastSeenTs) >= 86400000;
						if (shouldShow) { window.localStorage.setItem(storageKey, String(Date.now())); }
					}
					if (shouldShow) {
					if (cfg.noticeType === "alert") {
						var alertBox = document.createElement("div");
						alertBox.className = "notice notice-" + (cfg.noticeLevel || "info") + (cfg.noticeDismissible ? " is-dismissible" : "");
						var p = document.createElement("p");
						p.style.whiteSpace = "pre-line";
						p.textContent = (cfg.noticeTitle ? (cfg.noticeTitle + ": ") : "") + cfg.noticeMessage;
						alertBox.appendChild(p);
						if (cfg.noticeDismissible) {
							var alertDismissBtn = document.createElement("button");
							alertDismissBtn.type = "button";
							alertDismissBtn.className = "notice-dismiss";
							alertDismissBtn.setAttribute("aria-label", "Dismiss this notice");
							alertDismissBtn.addEventListener("click", function(){ if (alertBox.parentNode) { alertBox.parentNode.removeChild(alertBox); } });
							alertBox.appendChild(alertDismissBtn);
						}
						var target = document.querySelector("#wpbody-content .wrap");
						if (target) { target.prepend(alertBox); }
						var alertAutoDismiss = parseInt(cfg.noticeAutoDismiss, 10);
						if (alertAutoDismiss > 0) {
							window.setTimeout(function(){ if (alertBox && alertBox.parentNode) { alertBox.parentNode.removeChild(alertBox); } }, alertAutoDismiss * 1000);
						}
					} else if (cfg.noticeType === "popup") {
						var overlay = document.createElement("div");
						overlay.className = "dwm-dashboard-popup-overlay dwm-announcement--" + (cfg.noticeLevel || "info");
						var modal = document.createElement("div");
						modal.className = "dwm-dashboard-popup-modal";
						var modalHeader = document.createElement("div");
						modalHeader.className = "dwm-dashboard-popup-header";
						var modalTitle = document.createElement("span");
						modalTitle.textContent = safeText(cfg.noticeTitle || "Notice");
						modalHeader.appendChild(modalTitle);
						if (cfg.noticeDismissible) {
							var modalClose = document.createElement("button");
							modalClose.type = "button";
							modalClose.className = "dwm-dashboard-popup-close";
							modalClose.setAttribute("aria-label", "Close");
							modalClose.innerHTML = "&times;";
							modalHeader.appendChild(modalClose);
						}
						var modalBody = document.createElement("div");
						modalBody.className = "dwm-dashboard-popup-body";
						modalBody.textContent = safeText(cfg.noticeMessage);
						modal.appendChild(modalHeader);
						modal.appendChild(modalBody);
						overlay.appendChild(modal);
						document.body.appendChild(overlay);
						overlay.addEventListener("click", function(e){ if (e.target === overlay || (cfg.noticeDismissible && e.target.classList.contains("dwm-dashboard-popup-close"))) { overlay.remove(); }});
						var popupAutoDismiss = parseInt(cfg.noticeAutoDismiss, 10);
						if (popupAutoDismiss > 0) {
							window.setTimeout(function(){ if (overlay && overlay.parentNode) { overlay.parentNode.removeChild(overlay); } }, popupAutoDismiss * 1000);
						}
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
