<?php
/**
 * Features Registry Class
 *
 * Handles feature registry definitions and feature metadata operations.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DWM_Features {

	const PLAN_FREE = 'free';
	const PLAN_PRO  = 'pro';

	private static $features_cache     = null;
	private static $integrations_cache = null;
	private static $categories_cache   = null;
	private static $data_cache         = null;

	private static function load_data_file(): array {
		if ( null !== self::$data_cache ) {
			return self::$data_cache;
		}

		$file = DWM_PLUGIN_DIR . 'data/features.php';
		if ( file_exists( $file ) ) {
			$data = include $file;
			if ( is_array( $data ) ) {
				self::$data_cache = $data;
				return self::$data_cache;
			}
		}
		return [];
	}

	public static function get_all_features(): array {
		if ( null !== self::$features_cache ) {
			return self::$features_cache;
		}

		$data = self::load_data_file();
		if ( ! empty( $data['all_features'] ) ) {
			self::$features_cache = self::normalize_features( $data['all_features'] );
			return self::$features_cache;
		}

		self::$features_cache = [];
		return self::$features_cache;
	}

	public static function get_all_integrations(): array {
		if ( null !== self::$integrations_cache ) {
			return self::$integrations_cache;
		}

		$data = self::load_data_file();
		if ( ! empty( $data['integrations'] ) ) {
			self::$integrations_cache = self::normalize_integrations( $data['integrations'] );
			return self::$integrations_cache;
		}

		self::$integrations_cache = [];
		return self::$integrations_cache;
	}

	public static function get_categories(): array {
		if ( null !== self::$categories_cache ) {
			return self::$categories_cache;
		}

		$data = self::load_data_file();
		if ( ! empty( $data['categories'] ) ) {
			self::$categories_cache = $data['categories'];
			return self::$categories_cache;
		}

		self::$categories_cache = [];
		return self::$categories_cache;
	}

	public static function get_category_icon( string $category_name ): string {
		$categories = self::get_categories();
		return $categories[ $category_name ]['icon'] ?? 'list-view';
	}

	public static function get_category_description( string $category_name ): string {
		$categories = self::get_categories();
		return $categories[ $category_name ]['description'] ?? '';
	}

	public static function get_integrations_meta(): array {
		$data = self::load_data_file();
		if ( ! empty( $data['integrations_meta'] ) ) {
			return $data['integrations_meta'];
		}

		return [
			'icon'        => 'admin-plugins',
			'label'       => __( 'Integrations', 'dashboard-widget-manager' ),
			'description' => __( 'Connect Dashboard Widget Manager with external tools and services.', 'dashboard-widget-manager' ),
		];
	}

	public static function get_labels(): array {
		$data = self::load_data_file();
		if ( ! empty( $data['labels'] ) ) {
			return $data['labels'];
		}

		return [
			'badge_pro'         => __( 'Pro', 'dashboard-widget-manager' ),
			'badge_free'        => __( 'Free', 'dashboard-widget-manager' ),
			'badge_coming_soon' => __( 'Coming Soon', 'dashboard-widget-manager' ),
			'badge_available'   => __( 'Available', 'dashboard-widget-manager' ),
		];
	}

	public static function get_label( string $label_key ): string {
		$labels = self::get_labels();
		return $labels[ $label_key ] ?? $label_key;
	}

	public static function get_features_flat(): array {
		$features = self::get_all_features();
		$flat     = [];
		foreach ( $features as $category_features ) {
			$flat = array_merge( $flat, $category_features );
		}
		return $flat;
	}

	public static function get_integrations_flat(): array {
		$integrations = self::get_all_integrations();
		$flat         = [];
		foreach ( $integrations as $category_integrations ) {
			$flat = array_merge( $flat, $category_integrations );
		}
		return $flat;
	}

	public static function clear_cache(): void {
		self::$features_cache     = null;
		self::$integrations_cache = null;
		self::$categories_cache   = null;
		self::$data_cache         = null;
	}

	private static function normalize_features( array $all_features ): array {
		foreach ( $all_features as $category => &$features ) {
			foreach ( $features as &$feature ) {
				if ( ! isset( $feature['plan'] ) ) {
					$feature['plan'] = self::PLAN_FREE;
				}
				if ( ! isset( $feature['implemented'] ) ) {
					$feature['implemented'] = true;
				}
				if ( empty( $feature['id'] ) ) {
					$feature['id'] = sanitize_title( $feature['title'] ?? 'feature' );
				}
				if ( empty( $feature['docs_page'] ) ) {
					$feature['docs_page'] = 'feature-' . $feature['id'];
				}
			}
		}
		return $all_features;
	}

	private static function normalize_integrations( array $all_integrations ): array {
		foreach ( $all_integrations as $category => &$items ) {
			foreach ( $items as &$integration ) {
				if ( ! isset( $integration['implemented'] ) ) {
					$integration['implemented'] = false;
				}
				if ( empty( $integration['id'] ) ) {
					$integration['id'] = sanitize_title( $integration['title'] ?? 'integration' );
				}
				if ( empty( $integration['docs_page'] ) ) {
					$integration['docs_page'] = 'integration-' . $integration['id'];
				}
			}
		}
		return $all_integrations;
	}
}
