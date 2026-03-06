<?php
/**
 * Access Control Class
 *
 * Handles role and user-based access checks for DWM admin features.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DWM_Access_Control {

	/**
	 * Get all registered role keys.
	 *
	 * @return array<int,string>
	 */
	public static function get_all_role_keys(): array {
		if ( ! function_exists( 'wp_roles' ) ) {
			return array();
		}

		$roles = wp_roles();
		if ( ! $roles || empty( $roles->roles ) || ! is_array( $roles->roles ) ) {
			return array();
		}

		return array_values( array_map( 'sanitize_key', array_keys( $roles->roles ) ) );
	}

	/**
	 * Get the allowed role keys from plugin settings.
	 *
	 * @param array|null $settings Optional settings array to avoid duplicate reads.
	 * @return array<int,string>
	 */
	public static function get_allowed_role_keys( ?array $settings = null ): array {
		$all_roles = self::get_all_role_keys();
		if ( empty( $all_roles ) ) {
			return array();
		}

		if ( null === $settings ) {
			$settings = DWM_Data::get_instance()->get_settings();
		}

		$raw = isset( $settings['access_allowed_roles'] ) ? (string) $settings['access_allowed_roles'] : '';
		if ( '' === trim( $raw ) ) {
			return array();
		}

		$selected = explode( "\n", $raw );
		$selected = array_map( 'trim', $selected );
		$selected = array_map( 'sanitize_key', $selected );
		$selected = array_values( array_filter( $selected ) );

		return array_values( array_intersect( $selected, $all_roles ) );
	}

	/**
	 * Get explicitly restricted user IDs from plugin settings.
	 *
	 * @param array|null $settings Optional settings array to avoid duplicate reads.
	 * @return array<int,int>
	 */
	public static function get_restricted_user_ids( ?array $settings = null ): array {
		if ( null === $settings ) {
			$settings = DWM_Data::get_instance()->get_settings();
		}

		$raw = isset( $settings['restricted_user_ids'] ) ? (string) $settings['restricted_user_ids'] : '';
		if ( '' === trim( $raw ) ) {
			return array();
		}

		$ids = explode( "\n", $raw );
		$ids = array_map( 'trim', $ids );
		$ids = array_map( 'absint', $ids );
		$ids = array_values( array_unique( array_filter( $ids ) ) );

		return $ids;
	}

	/**
	 * Check if a user is explicitly blocked.
	 *
	 * @param int|null   $user_id  Optional user ID.
	 * @param array|null $settings Optional settings array.
	 * @return bool
	 */
	public static function is_user_explicitly_restricted( ?int $user_id = null, ?array $settings = null ): bool {
		$user_id = $user_id ?: get_current_user_id();
		if ( ! $user_id ) {
			return true;
		}

		$restricted_ids = self::get_restricted_user_ids( $settings );
		return in_array( $user_id, $restricted_ids, true );
	}

	/**
	 * Check if at least one of the user's roles is allowed.
	 *
	 * @param int|null   $user_id  Optional user ID.
	 * @param array|null $settings Optional settings array.
	 * @return bool
	 */
	public static function is_user_role_allowed( ?int $user_id = null, ?array $settings = null ): bool {
		$user_id = $user_id ?: get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		$user = get_userdata( $user_id );
		if ( ! $user || ! is_array( $user->roles ) ) {
			return false;
		}

		$allowed_roles = self::get_allowed_role_keys( $settings );
		if ( empty( $allowed_roles ) ) {
			return false;
		}

		foreach ( $user->roles as $role ) {
			if ( in_array( sanitize_key( $role ), $allowed_roles, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if a user passes DWM role/user access restrictions.
	 *
	 * @param int|null $user_id Optional user ID.
	 * @return bool
	 */
	public static function current_user_can_access_plugin( ?int $user_id = null ): bool {
		$user_id = $user_id ?: get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		$settings = DWM_Data::get_instance()->get_settings();
		if ( self::is_user_explicitly_restricted( $user_id, $settings ) ) {
			return false;
		}

		return self::is_user_role_allowed( $user_id, $settings );
	}
}

