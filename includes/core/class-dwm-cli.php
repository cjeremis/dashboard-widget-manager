<?php
/**
 * WP-CLI Commands
 *
 * Defines WP-CLI commands for Dashboard Widget Manager operations.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Dashboard Widget Manager CLI commands.
 */
class DWM_CLI {

	/**
	 * Import demo data into the database.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Force import even if widgets with the same name exist.
	 *
	 * ## EXAMPLES
	 *
	 *     wp dwm import-demo-data
	 *     wp dwm import-demo-data --force
	 *
	 * @when after_wp_load
	 */
	public function import_demo_data( $args, $assoc_args ) {
		$force = \WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );

		$demo_file = DWM_PLUGIN_DIR . 'includes/admin/data/demo-data.json';

		if ( ! file_exists( $demo_file ) ) {
			\WP_CLI::error( sprintf( 'Demo data file not found at %s', $demo_file ) );
		}

		// Load demo data file.
		$contents  = file_get_contents( $demo_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$demo_data = json_decode( $contents, true );

		if ( null === $demo_data ) {
			\WP_CLI::error( 'Invalid JSON in demo-data.json file' );
		}

		if ( ! isset( $demo_data['widgets'] ) || ! is_array( $demo_data['widgets'] ) ) {
			\WP_CLI::error( 'No widgets found in demo data' );
		}

		$data  = DWM_Data::get_instance();
		$count = 0;

		\WP_CLI::log( sprintf( 'Found %d widgets in demo data', count( $demo_data['widgets'] ) ) );

		foreach ( $demo_data['widgets'] as $widget ) {
			if ( ! is_array( $widget ) || empty( $widget['name'] ) ) {
				continue;
			}

			// Check for existing widget with same name.
			$existing = $data->get_widget_by_name( sanitize_text_field( $widget['name'] ) );
			if ( $existing && ! $force ) {
				\WP_CLI::log( sprintf( 'Skipping "%s" - widget already exists (use --force to override)', $widget['name'] ) );
				continue;
			}

			// Strip any existing ID fields.
			unset( $widget['id'], $widget['created_at'], $widget['updated_at'], $widget['created_by'] );

			// Mark as demo.
			$widget['is_demo'] = 1;

			// Sanitize key fields.
			$widget['name']        = sanitize_text_field( $widget['name'] );
			$widget['description'] = isset( $widget['description'] ) ? sanitize_text_field( $widget['description'] ) : '';
			$widget['enabled']     = isset( $widget['enabled'] ) ? (int) $widget['enabled'] : 1;
			$widget['widget_order'] = isset( $widget['widget_order'] ) ? (int) $widget['widget_order'] : 0;

			$new_id = $data->create_widget( $widget );

			if ( $new_id ) {
				\WP_CLI::log( sprintf( 'Imported widget "%s" (ID: %d)', $widget['name'], $new_id ) );
				$count++;
			} else {
				\WP_CLI::warning( sprintf( 'Failed to import widget "%s"', $widget['name'] ) );
			}
		}

		if ( $count > 0 ) {
			\WP_CLI::success( sprintf( '%d widget(s) imported successfully', $count ) );
		} else {
			\WP_CLI::warning( 'No widgets were imported' );
		}
	}

	/**
	 * Delete all demo data from the database.
	 *
	 * ## EXAMPLES
	 *
	 *     wp dwm delete-demo-data
	 *
	 * @when after_wp_load
	 */
	public function delete_demo_data( $args, $assoc_args ) {
		$data    = DWM_Data::get_instance();
		$deleted = $data->delete_demo_widgets();

		if ( $deleted > 0 ) {
			\WP_CLI::success( sprintf( '%d demo widget(s) deleted successfully', $deleted ) );
		} else {
			\WP_CLI::log( 'No demo widgets found to delete' );
		}
	}
}

// Register the commands.
if ( class_exists( 'WP_CLI' ) ) {
	\WP_CLI::add_command( 'dwm', 'DWM_CLI' );
}
