<?php
/**
 * Support AJAX Handler
 *
 * Handles AJAX requests for support tickets and support API operations.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DWM_Support_AJAX {

	use DWM_Singleton;

	const RATE_LIMIT_PREFIX = 'dwm_support_rate_limit_';

	private $api_base_url;

	private const USER_REPLY_META_KEY = 'dwm_support_last_reply_ids';

	/**
	 * Initialize API base URL
	 */
	private function initialize(): void {
		$this->api_base_url = apply_filters(
			'dwm_support_api_base_url',
			'https://topdevamerica.com/wp-json/support-manager/v1'
		);
	}

	/**
	 * Get API base URL (lazy init)
	 *
	 * @return string
	 */
	private function get_api_base_url(): string {
		if ( ! $this->api_base_url ) {
			$this->initialize();
		}
		return $this->api_base_url;
	}

	/**
	 * Check whether live support sync is enabled.
	 *
	 * Disabled by default. Users must explicitly opt in via the Support Data Sharing setting.
	 *
	 * @return bool
	 */
	private function is_live_sync_enabled(): bool {
		$settings = DWM_Data::get_instance()->get_settings();

		return ! empty( $settings['support_data_sharing_opt_in'] );
	}

	/**
	 * Sync remote support replies into local notifications for a user.
	 *
	 * @param int $user_id
	 * @return void
	 */
	public function sync_notifications_for_user( int $user_id ): void {
		if ( ! $this->is_live_sync_enabled() ) {
			return;
		}

		if ( ! class_exists( 'DWM_Notifications' ) ) {
			return;
		}

		$user = get_user_by( 'id', $user_id );
		if ( ! $user || ! $user->user_email ) {
			return;
		}

		$response = $this->api_request( 'GET', '/tickets', [
			'customer_email' => $user->user_email,
			'limit'          => 50,
			'offset'         => 0,
		] );

		if ( is_wp_error( $response ) || empty( $response['tickets'] ) ) {
			return;
		}

		$notifications = DWM_Notifications::get_instance();
		$last_meta     = get_user_meta( $user_id, self::USER_REPLY_META_KEY, true );
		$last_meta     = is_array( $last_meta ) ? $last_meta : [];

		foreach ( $response['tickets'] as $ticket ) {
			$ticket_id    = (int) ( $ticket['id'] ?? 0 );
			$ticket_num   = $ticket['ticket_number'] ?? $ticket_id;
			$ticket_title = $ticket['subject'] ?? __( 'Support Ticket', 'dashboard-widget-manager' );

			if ( ! $ticket_id ) {
				continue;
			}

			$replies = $this->api_request( 'GET', "/tickets/{$ticket_id}/replies", [
				'customer_email' => $user->user_email,
			] );

			if ( is_wp_error( $replies ) || empty( $replies['replies'] ) ) {
				continue;
			}

			$last_seen_reply = isset( $last_meta[ $ticket_id ] ) ? (int) $last_meta[ $ticket_id ] : 0;
			$new_last_seen   = $last_seen_reply;

			foreach ( $replies['replies'] as $reply ) {
				$reply_id = (int) ( $reply['id'] ?? 0 );
				if ( ! $reply_id || $reply_id <= $last_seen_reply ) {
					continue;
				}

				$author_email   = isset( $reply['author_email'] ) ? strtolower( $reply['author_email'] ) : '';
				$is_admin_reply = $author_email !== strtolower( $user->user_email );
				$display_name   = $is_admin_reply ? 'TopDevAmerica' : ( $reply['author_name'] ?? 'You' );

				$notifications->add_notification(
					'support_reply_' . $ticket_id . '_' . $reply_id,
					sprintf( __( 'New Reply on Ticket #%s', 'dashboard-widget-manager' ), $ticket_num ),
					$display_name . ': ' . mb_substr( $reply['message'] ?? '', 0, 140 ),
					'email-alt',
					[
						[
							'label' => __( 'View Ticket', 'dashboard-widget-manager' ),
							'url'   => admin_url( 'admin.php?page=dwm-settings' ),
						],
					],
					$user_id
				);

				if ( $reply_id > $new_last_seen ) {
					$new_last_seen = $reply_id;
				}
			}

			if ( $new_last_seen > $last_seen_reply ) {
				$last_meta[ $ticket_id ] = $new_last_seen;
			}
		}

		update_user_meta( $user_id, self::USER_REPLY_META_KEY, $last_meta );
	}

	/**
	 * Submit a new support ticket
	 *
	 * @return void
	 */
	public function submit_ticket(): void {
		check_ajax_referer( 'dwm_support_nonce', 'nonce' );

		if ( ! current_user_can( DWM_Admin_Menu::REQUIRED_CAP ) ) {
			wp_send_json_error(
				[ 'message' => __( 'You do not have permission to submit support tickets.', 'dashboard-widget-manager' ) ],
				403
			);
		}

		$system_info = DWM_System_Info::get_instance();
		$user_email  = $system_info->get_current_user_email();

		if ( ! $this->check_rate_limit( 'ticket_submit_' . md5( $user_email ), 5, HOUR_IN_SECONDS ) ) {
			wp_send_json_error(
				[ 'message' => __( 'You have submitted too many tickets recently. Please try again later.', 'dashboard-widget-manager' ) ],
				429
			);
		}

		$subject     = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
		$description = isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '';
		$priority    = isset( $_POST['priority'] ) ? sanitize_text_field( wp_unslash( $_POST['priority'] ) ) : 'normal';
		$consent_raw = isset( $_POST['support_data_consent'] ) ? sanitize_text_field( wp_unslash( $_POST['support_data_consent'] ) ) : '';
		$has_consent = in_array( $consent_raw, [ '1', 'true', 'on', 'yes' ], true );

		if ( ! $has_consent ) {
			wp_send_json_error(
				[ 'message' => __( 'Consent is required to submit a support ticket with diagnostic data.', 'dashboard-widget-manager' ) ],
				400
			);
		}

		if ( empty( $subject ) || strlen( $subject ) < 5 ) {
			wp_send_json_error(
				[ 'message' => __( 'Subject must be at least 5 characters long.', 'dashboard-widget-manager' ) ],
				400
			);
		}

		if ( empty( $description ) || strlen( $description ) < 10 ) {
			wp_send_json_error(
				[ 'message' => __( 'Description must be at least 10 characters long.', 'dashboard-widget-manager' ) ],
				400
			);
		}

		$ticket_number = $this->generate_ticket_number();

		$ticket_data = [
			'ticket_number'  => $ticket_number,
			'customer_email' => $user_email,
			'subject'        => $subject,
			'description'    => $description,
			'priority'       => $priority,
			'status'         => 'new',
		];

		if ( $has_consent ) {
			$system_data = $system_info->get_all_info();

			$ticket_data = array_merge(
				$ticket_data,
				[
					'customer_site_url'   => home_url(),
					'wp_version'          => $system_data['wp_version'] ?? '',
					'php_version'         => $system_data['php_version'] ?? '',
					'theme_name'          => $system_data['theme_name'] ?? '',
					'theme_version'       => $system_data['theme_version'] ?? '',
					'active_plugins'      => $system_data['active_plugins'] ?? [],
					'dwm_version'         => $system_data['dwm_version'] ?? '',
					'customer_ip'         => $system_info->get_client_ip(),
					'customer_user_agent' => $system_info->get_user_agent(),
				]
			);
		}

		$response = $this->api_request( 'POST', '/tickets', $ticket_data );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error(
				[ 'message' => $response->get_error_message() ],
				500
			);
		}

		if ( ! isset( $response['success'] ) || ! $response['success'] ) {
			wp_send_json_error(
				[ 'message' => $response['message'] ?? __( 'Failed to create ticket.', 'dashboard-widget-manager' ) ],
				500
			);
		}

		wp_send_json_success( [
			'message'       => __( 'Support ticket submitted successfully.', 'dashboard-widget-manager' ),
			'ticket_number' => $response['ticket_number'] ?? $ticket_number,
			'ticket_id'     => $response['ticket_id'] ?? null,
		] );
	}

	/**
	 * Get support tickets for current user
	 *
	 * @return void
	 */
	public function get_tickets(): void {
		check_ajax_referer( 'dwm_support_nonce', 'nonce' );

		if ( ! current_user_can( DWM_Admin_Menu::REQUIRED_CAP ) ) {
			wp_send_json_error(
				[ 'message' => __( 'You do not have permission to view support tickets.', 'dashboard-widget-manager' ) ],
				403
			);
		}

		$system_info = DWM_System_Info::get_instance();
		$user_email  = $system_info->get_current_user_email();

		if ( ! $user_email ) {
			wp_send_json_error(
				[ 'message' => __( 'User email not found.', 'dashboard-widget-manager' ) ],
				400
			);
		}

		$limit  = isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 20;
		$offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;

		$response = $this->api_request( 'GET', '/tickets', [
			'customer_email' => $user_email,
			'limit'          => $limit,
			'offset'         => $offset,
		] );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error(
				[ 'message' => $response->get_error_message() ],
				500
			);
		}

		wp_send_json_success( [
			'tickets' => $response['tickets'] ?? [],
			'total'   => $response['total'] ?? 0,
			'count'   => count( $response['tickets'] ?? [] ),
		] );
	}

	/**
	 * Get ticket detail with replies
	 *
	 * @return void
	 */
	public function get_ticket_detail(): void {
		check_ajax_referer( 'dwm_support_nonce', 'nonce' );

		if ( ! current_user_can( DWM_Admin_Menu::REQUIRED_CAP ) ) {
			wp_send_json_error(
				[ 'message' => __( 'You do not have permission to view ticket details.', 'dashboard-widget-manager' ) ],
				403
			);
		}

		$ticket_id = isset( $_POST['ticket_id'] ) ? absint( $_POST['ticket_id'] ) : 0;

		if ( ! $ticket_id ) {
			wp_send_json_error(
				[ 'message' => __( 'Invalid ticket ID.', 'dashboard-widget-manager' ) ],
				400
			);
		}

		$system_info = DWM_System_Info::get_instance();
		$user_email  = $system_info->get_current_user_email();

		$response = $this->api_request( 'GET', "/tickets/{$ticket_id}", [
			'customer_email' => $user_email,
		] );

		if ( is_wp_error( $response ) ) {
			$error_data = $response->get_error_data();
			$status     = is_array( $error_data ) && isset( $error_data['status'] ) ? $error_data['status'] : 500;
			wp_send_json_error(
				[ 'message' => $response->get_error_message() ],
				$status
			);
		}

		$replies = $response['replies'] ?? [];

		foreach ( $replies as &$reply ) {
			$author_email        = isset( $reply['author_email'] ) ? strtolower( $reply['author_email'] ) : '';
			$reply['author_type'] = ( $author_email === strtolower( $user_email ) ) ? 'user' : 'admin';
			$reply['author_name'] = ( $reply['author_type'] === 'admin' ) ? 'TopDevAmerica' : ( $reply['author_name'] ?? 'You' );
		}
		unset( $reply );

		wp_send_json_success( [
			'ticket'  => $response['ticket'] ?? null,
			'replies' => $replies,
		] );
	}

	/**
	 * Submit a reply to a ticket
	 *
	 * @return void
	 */
	public function submit_reply(): void {
		check_ajax_referer( 'dwm_support_nonce', 'nonce' );

		if ( ! current_user_can( DWM_Admin_Menu::REQUIRED_CAP ) ) {
			wp_send_json_error(
				[ 'message' => __( 'You do not have permission to submit replies.', 'dashboard-widget-manager' ) ],
				403
			);
		}

		$system_info = DWM_System_Info::get_instance();
		$user_email  = $system_info->get_current_user_email();

		if ( ! $this->check_rate_limit( 'reply_submit_' . md5( $user_email ), 10, HOUR_IN_SECONDS ) ) {
			wp_send_json_error(
				[ 'message' => __( 'You have submitted too many replies recently. Please try again later.', 'dashboard-widget-manager' ) ],
				429
			);
		}

		$ticket_id = isset( $_POST['ticket_id'] ) ? absint( $_POST['ticket_id'] ) : 0;
		$message   = isset( $_POST['message'] ) ? wp_kses_post( wp_unslash( $_POST['message'] ) ) : '';

		if ( ! $ticket_id ) {
			wp_send_json_error(
				[ 'message' => __( 'Invalid ticket ID.', 'dashboard-widget-manager' ) ],
				400
			);
		}

		if ( empty( $message ) || strlen( $message ) < 5 ) {
			wp_send_json_error(
				[ 'message' => __( 'Message must be at least 5 characters long.', 'dashboard-widget-manager' ) ],
				400
			);
		}

		$reply_data = [
			'author_name'  => $system_info->get_current_user_name(),
			'author_email' => $user_email,
			'message'      => $message,
			'ip_address'   => $system_info->get_client_ip(),
			'user_agent'   => $system_info->get_user_agent(),
		];

		$response = $this->api_request( 'POST', "/tickets/{$ticket_id}/replies", $reply_data );

		if ( is_wp_error( $response ) ) {
			$error_data = $response->get_error_data();
			$status     = is_array( $error_data ) && isset( $error_data['status'] ) ? $error_data['status'] : 500;
			wp_send_json_error(
				[ 'message' => $response->get_error_message() ],
				$status
			);
		}

		wp_send_json_success( [
			'message'  => __( 'Reply submitted successfully.', 'dashboard-widget-manager' ),
			'reply_id' => $response['reply_id'] ?? null,
		] );
	}

	/**
	 * Make an API request to TopDevAmerica
	 *
	 * @param string $method   HTTP method
	 * @param string $endpoint API endpoint
	 * @param array  $data     Request data
	 * @return array|WP_Error
	 */
	private function api_request( string $method, string $endpoint, array $data = [] ) {
		$base_url = $this->get_api_base_url();
		$url      = $base_url . $endpoint;

		if ( $method === 'GET' && ! empty( $data ) ) {
			$url = add_query_arg( $data, $url );
		}

		$timestamp = time();
		$site_url  = home_url();
		$signature = $this->generate_signature( $data, $timestamp );

		$args = [
			'method'  => $method,
			'headers' => [
				'Content-Type' => 'application/json',
				'X-Site-URL'   => $site_url,
				'X-Timestamp'  => $timestamp,
				'X-Signature'  => $signature,
			],
			'timeout' => 30,
		];

		if ( $method === 'POST' ) {
			$args['body'] = wp_json_encode( $data );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'api_error',
				__( 'Failed to connect to support server. Please try again later.', 'dashboard-widget-manager' )
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$decoded     = json_decode( $body, true );

		if ( $status_code >= 400 ) {
			$message = $decoded['message'] ?? __( 'An error occurred.', 'dashboard-widget-manager' );
			return new WP_Error( 'api_error', $message, [ 'status' => $status_code ] );
		}

		return $decoded ?: [];
	}

	/**
	 * Generate ticket number
	 * Format: DWMSUP-YYYYMMDD-####
	 *
	 * @return string
	 */
	private function generate_ticket_number(): string {
		$date_prefix   = 'DWMSUP-' . gmdate( 'Ymd' ) . '-';
		$random_suffix = str_pad( wp_rand( 1, 9999 ), 4, '0', STR_PAD_LEFT );
		return $date_prefix . $random_suffix;
	}

	/**
	 * Check rate limit
	 *
	 * @param string $key    Rate limit key
	 * @param int    $limit  Maximum requests
	 * @param int    $window Time window in seconds
	 * @return bool True if within limit
	 */
	private function check_rate_limit( string $key, int $limit, int $window ): bool {
		$transient_key = self::RATE_LIMIT_PREFIX . $key;
		$count         = get_transient( $transient_key );

		if ( false === $count ) {
			set_transient( $transient_key, 1, $window );
			return true;
		}

		if ( $count >= $limit ) {
			return false;
		}

		set_transient( $transient_key, $count + 1, $window );
		return true;
	}

	/**
	 * Generate HMAC signature for API request
	 *
	 * @param array $payload   Request payload
	 * @param int   $timestamp Unix timestamp
	 * @return string HMAC signature
	 */
	private function generate_signature( array $payload, int $timestamp ): string {
		$secret = home_url();
		$data   = wp_json_encode( $payload ) . $timestamp;
		return hash_hmac( 'sha256', $data, $secret );
	}
}
