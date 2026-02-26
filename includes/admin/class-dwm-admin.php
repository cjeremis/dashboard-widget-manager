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
			DWM_PLUGIN_URL . 'assets/css/minimized/admin/global.min.css',
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
				DWM_PLUGIN_URL . 'assets/css/minimized/admin/dashboard.min.css',
				array( 'dwm-admin' ),
				DWM_VERSION,
				'all'
			);

			// CodeMirror for code editing.
			wp_enqueue_code_editor( array( 'type' => 'application/x-httpd-php' ) );
			wp_enqueue_style( 'wp-codemirror' );
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

		$data    = DWM_Data::get_instance();
		$widgets = $data->get_widgets();

		$create_url   = esc_url( admin_url( 'admin.php?page=dashboard-widget-manager' ) );
		$button_label = esc_js( __( 'New Widget', 'dashboard-widget-manager' ) );

		wp_add_inline_style( 'wp-admin', '
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
	}

	/**
	 * Render the widget picker modal HTML in the admin footer.
	 * Only called when widgets exist.
	 */
	public function render_widget_picker_modal() {
		$data    = DWM_Data::get_instance();
		$widgets = $data->get_widgets();

		$create_url = esc_url( admin_url( 'admin.php?page=dashboard-widget-manager' ) );
		$ajax_url   = esc_url( admin_url( 'admin-ajax.php' ) );
		$nonce      = wp_create_nonce( 'dwm_admin_nonce' );

		// Build widget list JSON for JS.
		$widget_data = array();
		foreach ( $widgets as $w ) {
			$widget_data[] = array(
				'id'      => (int) $w['id'],
				'name'    => $w['name'],
				'desc'    => $w['description'],
				'enabled' => (bool) $w['enabled'],
			);
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
								<span><?php esc_html_e( 'Build a new widget from scratch', 'dashboard-widget-manager' ); ?></span>
							</a>
							<button type="button" class="dwm-picker-choice" id="dwm-picker-show-list">
								<span class="dashicons dashicons-list-view"></span>
								<strong><?php esc_html_e( 'Select Existing', 'dashboard-widget-manager' ); ?></strong>
								<span><?php esc_html_e( 'Choose from your saved widgets', 'dashboard-widget-manager' ); ?></span>
							</button>
						</div>
					</div>

					<!-- Step 2: Widget list -->
					<div class="dwm-picker-step" id="dwm-picker-step-2">
						<button type="button" class="dwm-picker-back" id="dwm-picker-back-1">
							&larr; <?php esc_html_e( 'Back', 'dashboard-widget-manager' ); ?>
						</button>
						<div class="dwm-widget-list" id="dwm-picker-widget-list"></div>
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
			var widgets    = <?php echo wp_json_encode( $widget_data ); ?>;
			var ajaxUrl    = '<?php echo $ajax_url; ?>';
			var nonce      = '<?php echo esc_js( $nonce ); ?>';
			var pendingId  = null;

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
				if ( ! widgets.length ) {
					$list.html('<p style="color:#6b7280;font-size:13px;"><?php echo esc_js( __( 'No widgets found.', 'dashboard-widget-manager' ) ); ?></p>');
					return;
				}
				$.each(widgets, function(i, w) {
					var statusClass  = w.enabled ? 'active' : 'draft';
					var statusLabel  = w.enabled ? '<?php echo esc_js( __( 'Active', 'dashboard-widget-manager' ) ); ?>' : '<?php echo esc_js( __( 'Draft', 'dashboard-widget-manager' ) ); ?>';
					var $item = $(
						'<button type="button" class="dwm-widget-list-item" data-id="' + w.id + '" data-enabled="' + (w.enabled ? '1' : '0') + '">' +
							'<div>' +
								'<div class="dwm-widget-item-name">' + $('<span>').text(w.name).html() + '</div>' +
								( w.desc ? '<div class="dwm-widget-item-desc">' + $('<span>').text(w.desc).html() + '</div>' : '' ) +
							'</div>' +
							'<span class="dwm-widget-status-badge ' + statusClass + '">' + statusLabel + '</span>' +
						'</button>'
					);
					$list.append($item);
				});
			}

			// Open picker button.
			$(document).on('click', '#dwm-open-widget-picker', openPicker);

			// Close handlers.
			$(document).on('click', '#dwm-picker-close, #dwm-picker-overlay', closePicker);
			$(document).on('keydown', function(e) {
				if (e.key === 'Escape') { closePicker(); }
			});

			// Step 1 → Step 2 (select existing).
			$(document).on('click', '#dwm-picker-show-list', function() {
				buildWidgetList();
				showStep(2);
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
	 * Enqueue Chart.js on the WordPress dashboard page.
	 *
	 * Hooked to admin_enqueue_scripts so Chart.js is available
	 * for any widgets that use chart rendering.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_chartjs( $hook ) {
		if ( 'index.php' !== $hook && ! $this->is_plugin_page( $hook ) ) {
			return;
		}

		wp_enqueue_script(
			'dwm-chartjs',
			DWM_PLUGIN_URL . 'assets/js/minimized/vendors/chart.min.js',
			array(),
			DWM_VERSION,
			true
		);
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

		// Base admin script.
		wp_enqueue_script(
			'dwm-admin',
			DWM_PLUGIN_URL . 'assets/js/minimized/admin/admin.min.js',
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
		if ( strpos( $hook, 'dashboard-widget-manager' ) !== false && strpos( $hook, 'dwm-' ) === false ) {
			wp_enqueue_script(
				'dwm-widget-editor',
				DWM_PLUGIN_URL . 'assets/js/minimized/admin/widget-editor.min.js',
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
		);

		return in_array( $hook, $plugin_pages, true );
	}
}
