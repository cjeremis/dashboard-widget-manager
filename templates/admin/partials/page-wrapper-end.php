<?php
/**
 * Admin Partial Template - Page Wrapper End
 *
 * Handles markup rendering for the page wrapper end admin partial template.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Close page content and include footer
include __DIR__ . '/footer.php';
?>
</div>

<?php
// Render notifications panel (content populated by AJAX)
include __DIR__ . '/notifications-panel.php';

// Render features modal (available on all DWM admin pages)
include DWM_PLUGIN_DIR . 'templates/admin/modals/features-modal.php';

// Render docs modal (available on all DWM admin pages)
include DWM_PLUGIN_DIR . 'templates/admin/modals/docs-modal.php';

// Render support ticket form modal (available on all DWM admin pages)
include DWM_PLUGIN_DIR . 'templates/admin/partials/support-ticket-form.php';

// Render pro upgrade modal (available on all DWM admin pages)
include DWM_PLUGIN_DIR . 'templates/admin/modals/pro-upgrade.php';
?>
