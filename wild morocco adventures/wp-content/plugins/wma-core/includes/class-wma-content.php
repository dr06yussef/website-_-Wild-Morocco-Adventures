<?php
/**
 * Custom content types and permissions.
 */

defined( 'ABSPATH' ) || exit;

final class WMA_Content {
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'after_switch_theme', array( __CLASS__, 'install_roles' ) );
	}

	public static function register(): void {
		register_post_type(
			'wma_trip',
			array(
				'labels' => array(
					'name' => __( 'Tours', 'wma-core' ), 'singular_name' => __( 'Tour', 'wma-core' ),
					'add_new' => __( 'Add tour', 'wma-core' ), 'add_new_item' => __( 'Add new tour', 'wma-core' ),
					'edit_item' => __( 'Edit tour', 'wma-core' ), 'view_item' => __( 'View tour', 'wma-core' ),
					'all_items' => __( 'All tours', 'wma-core' ), 'menu_name' => __( 'Wild Morocco', 'wma-core' ),
				),
				'public' => true, 'show_in_rest' => true, 'has_archive' => 'tours',
				'rewrite' => array( 'slug' => 'tour', 'with_front' => false ),
				'menu_icon' => 'dashicons-location-alt', 'menu_position' => 5,
				'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author' ),
				'capability_type' => array( 'wma_trip', 'wma_trips' ), 'map_meta_cap' => true,
			)
		);

		register_post_type(
			'wma_inquiry',
			array(
				'labels' => array( 'name' => __( 'Enquiries', 'wma-core' ), 'singular_name' => __( 'Enquiry', 'wma-core' ), 'edit_item' => __( 'View enquiry', 'wma-core' ), 'all_items' => __( 'Enquiries', 'wma-core' ) ),
				'public' => false, 'publicly_queryable' => false, 'exclude_from_search' => true,
				'show_ui' => true, 'show_in_menu' => 'edit.php?post_type=wma_trip', 'show_in_rest' => false,
				'supports' => array( 'title' ),
				'capability_type' => array( 'wma_inquiry', 'wma_inquiries' ), 'map_meta_cap' => true,
				'capabilities' => array( 'create_posts' => 'do_not_allow' ),
			)
		);

		self::simple_type( 'wma_testimonial', __( 'Testimonials', 'wma-core' ), __( 'Testimonial', 'wma-core' ), array( 'title', 'editor', 'thumbnail', 'page-attributes' ) );
		self::simple_type( 'wma_faq', __( 'FAQs', 'wma-core' ), __( 'FAQ', 'wma-core' ), array( 'title', 'editor', 'page-attributes' ) );

		self::taxonomy( 'wma_region', __( 'Regions', 'wma-core' ), __( 'Region', 'wma-core' ), 'region' );
		self::taxonomy( 'wma_style', __( 'Travel styles', 'wma-core' ), __( 'Travel style', 'wma-core' ), 'travel-style' );
		self::taxonomy( 'wma_interest', __( 'Interests', 'wma-core' ), __( 'Interest', 'wma-core' ), 'interest' );
	}

	private static function simple_type( string $key, string $plural, string $single, array $supports ): void {
		register_post_type(
			$key,
			array(
				'labels' => array( 'name' => $plural, 'singular_name' => $single, 'add_new_item' => sprintf( __( 'Add %s', 'wma-core' ), $single ), 'edit_item' => sprintf( __( 'Edit %s', 'wma-core' ), $single ) ),
				'public' => false, 'show_ui' => true, 'show_in_menu' => 'edit.php?post_type=wma_trip',
				'show_in_rest' => true, 'supports' => $supports,
			)
		);
	}

	private static function taxonomy( string $key, string $plural, string $single, string $slug ): void {
		register_taxonomy(
			$key,
			array( 'wma_trip' ),
			array(
				'labels' => array( 'name' => $plural, 'singular_name' => $single, 'all_items' => sprintf( __( 'All %s', 'wma-core' ), $plural ), 'add_new_item' => sprintf( __( 'Add %s', 'wma-core' ), $single ) ),
				'public' => true, 'hierarchical' => true, 'show_admin_column' => true, 'show_in_rest' => true,
				'rewrite' => array( 'slug' => $slug, 'with_front' => false ),
			)
		);
	}

	public static function install_roles(): void {
		add_role( 'travel_manager', __( 'Travel Manager', 'wma-core' ), array( 'read' => true, 'upload_files' => true, 'edit_posts' => true, 'publish_posts' => true, 'delete_posts' => true ) );
		$trip_caps = self::caps( 'wma_trip', 'wma_trips' );
		$inquiry_caps = self::caps( 'wma_inquiry', 'wma_inquiries' );
		$inquiry_caps[] = 'wma_export_inquiries';
		foreach ( array( 'administrator', 'travel_manager' ) as $role_key ) {
			$role = get_role( $role_key );
			if ( $role ) {
				foreach ( array_merge( $trip_caps, $inquiry_caps ) as $cap ) {
					$role->add_cap( $cap );
				}
			}
		}
		$editor = get_role( 'editor' );
		if ( $editor ) {
			foreach ( $trip_caps as $cap ) {
				$editor->add_cap( $cap );
			}
		}
	}

	private static function caps( string $single, string $plural ): array {
		return array(
			"edit_{$single}", "read_{$single}", "delete_{$single}", "edit_{$plural}", "edit_others_{$plural}",
			"publish_{$plural}", "read_private_{$plural}", "delete_{$plural}", "delete_private_{$plural}",
			"delete_published_{$plural}", "delete_others_{$plural}", "edit_private_{$plural}", "edit_published_{$plural}",
		);
	}
}
