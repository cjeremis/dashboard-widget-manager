<?php
/**
 * Admin Partial Template - Support Ticket Form
 *
 * Handles markup rendering for the support ticket form admin partial template.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$privacy_page_id = (int) get_option( 'tda_shared_privacy_page_id' );
$terms_page_id   = (int) get_option( 'tda_shared_terms_page_id' );
$privacy_url     = $privacy_page_id ? get_permalink( $privacy_page_id ) : 'https://topdevamerica.com/privacy-policy';
$terms_url       = $terms_page_id ? get_permalink( $terms_page_id ) : 'https://topdevamerica.com/terms';
?>

<div id="dwm-new-ticket-modal" class="dwm-modal dwm-support-ticket-modal">
	<div class="dwm-modal-overlay"></div>
	<div class="dwm-modal-content">
		<div class="dwm-modal-header">
			<div class="dwm-modal-title-wrap">
				<span class="dwm-modal-title-icon">
					<span class="dashicons dashicons-phone"></span>
				</span>
				<h2><?php esc_html_e( 'Submit New Support Ticket', 'dashboard-widget-manager' ); ?></h2>
			</div>
			<button type="button" class="dwm-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>

		<div class="dwm-modal-body">
			<form id="dwm-support-ticket-form" class="dwm-form">
				<?php wp_nonce_field( 'dwm_support_nonce', 'dwm_support_nonce' ); ?>

				<div class="dwm-form-group">
					<label for="dwm-ticket-subject"><?php esc_html_e( 'Subject', 'dashboard-widget-manager' ); ?> <span class="dwm-required">*</span></label>
					<input
						type="text"
						id="dwm-ticket-subject"
						name="subject"
						required
						minlength="5"
						maxlength="500"
						placeholder="<?php esc_attr_e( 'Brief description of your issue', 'dashboard-widget-manager' ); ?>"
					/>
				</div>

				<div class="dwm-form-group">
					<label for="dwm-ticket-priority"><?php esc_html_e( 'Priority', 'dashboard-widget-manager' ); ?></label>
					<select id="dwm-ticket-priority" name="priority">
						<option value="normal"><?php esc_html_e( 'Normal', 'dashboard-widget-manager' ); ?></option>
						<option value="low"><?php esc_html_e( 'Low', 'dashboard-widget-manager' ); ?></option>
						<option value="high"><?php esc_html_e( 'High', 'dashboard-widget-manager' ); ?></option>
						<option value="critical"><?php esc_html_e( 'Critical', 'dashboard-widget-manager' ); ?></option>
					</select>
				</div>

				<div class="dwm-form-group">
					<label for="dwm-ticket-description"><?php esc_html_e( 'Description', 'dashboard-widget-manager' ); ?> <span class="dwm-required">*</span></label>
					<textarea
						id="dwm-ticket-description"
						name="description"
						rows="5"
						required
						minlength="10"
						placeholder="<?php esc_attr_e( 'Provide detailed information about your issue, including steps to reproduce if applicable...', 'dashboard-widget-manager' ); ?>"
					></textarea>
				</div>

				<div class="dwm-form-group">
					<label class="dwm-checkbox-label" for="dwm-ticket-data-consent" style="display:flex;align-items:flex-start;gap:8px;cursor:pointer;">
						<input
							type="checkbox"
							id="dwm-ticket-data-consent"
							name="support_data_consent"
							value="1"
							required
							style="margin-top:3px;flex-shrink:0;"
						/>
						<span>
							<?php
							esc_html_e(
								'I consent to transmitting diagnostic information — site URL, active plugins, WordPress version, PHP version, active theme, IP address, and browser user agent — to TopDevAmerica support to help resolve my ticket.',
								'dashboard-widget-manager'
							);
							?>
							<span class="dwm-required">*</span>
						</span>
					</label>
					<p class="description" style="margin-top:4px;margin-left:24px;">
						<a href="<?php echo esc_url( $privacy_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Privacy Policy', 'dashboard-widget-manager' ); ?></a>
						|
						<a href="<?php echo esc_url( $terms_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Terms of Service', 'dashboard-widget-manager' ); ?></a>
					</p>
				</div>

				<div id="dwm-ticket-form-message" class="dwm-message" style="display: none;"></div>
			</form>
		</div>

		<div class="dwm-modal-footer">
			<button type="submit" form="dwm-support-ticket-form" class="dwm-button-primary" id="dwm-submit-ticket-btn">
				<?php esc_html_e( 'Submit Ticket', 'dashboard-widget-manager' ); ?>
			</button>
		</div>
	</div>
</div>
