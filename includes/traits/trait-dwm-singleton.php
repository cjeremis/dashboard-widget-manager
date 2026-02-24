<?php
/**
 * Singleton Trait
 *
 * Provides singleton pattern implementation for classes.
 * Prevents direct instantiation, cloning, and unserialization.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singleton trait.
 *
 * Enforces singleton pattern for classes that use this trait.
 */
trait DWM_Singleton {

	/**
	 * Instance of the class.
	 *
	 * @var object
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return object Instance of the class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct instantiation.
	 */
	private function __construct() {
		// Constructor logic in the class using this trait.
	}

	/**
	 * Prevent cloning of the instance.
	 */
	private function __clone() {
		// Prevent cloning.
	}

	/**
	 * Prevent unserialization of the instance.
	 */
	public function __wakeup() {
		// Prevent unserialization.
	}
}
