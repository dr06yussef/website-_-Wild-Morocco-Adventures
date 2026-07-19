<?php
/**
 * Wild Morocco Adventures theme functions.
 */

defined( 'ABSPATH' ) || exit;

define( 'WMA_THEME_VERSION', '1.4.2' );
define( 'WMA_THEME_URL', get_template_directory_uri() );

function wma_theme_setup(): void {
	load_theme_textdomain( 'wild-morocco-adventures', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' ); add_theme_support( 'post-thumbnails' ); add_theme_support( 'responsive-embeds' ); add_theme_support( 'align-wide' ); add_theme_support( 'editor-styles' );
	add_theme_support( 'custom-logo', array( 'height' => 96, 'width' => 280, 'flex-height' => true, 'flex-width' => true ) );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_editor_style( 'assets/css/editor.css' );
	register_nav_menus( array( 'primary' => __( 'Primary navigation', 'wild-morocco-adventures' ), 'footer' => __( 'Footer navigation', 'wild-morocco-adventures' ), 'legal' => __( 'Legal navigation', 'wild-morocco-adventures' ) ) );
	add_image_size( 'wma-card', 760, 540, true ); add_image_size( 'wma-hero', 1920, 1100, true ); add_image_size( 'wma-gallery', 1200, 840, true );
}
add_action( 'after_setup_theme', 'wma_theme_setup' );

function wma_assets(): void {
	wp_enqueue_style( 'wma-main', WMA_THEME_URL . '/assets/css/main.css', array(), WMA_THEME_VERSION );
	wp_enqueue_script( 'wma-main', WMA_THEME_URL . '/assets/js/main.js', array(), WMA_THEME_VERSION, true );
	wp_localize_script( 'wma-main', 'wmaTheme', array( 'menuOpen' => __( 'Open menu', 'wild-morocco-adventures' ), 'menuClose' => __( 'Close menu', 'wild-morocco-adventures' ) ) );
}
add_action( 'wp_enqueue_scripts', 'wma_assets' );

function wma_dependency_notice(): void {
	if ( is_admin() && current_user_can( 'activate_plugins' ) && ! defined( 'WMA_CORE_VERSION' ) ) { echo '<div class="notice notice-warning"><p>' . esc_html__( 'Wild Morocco Adventures requires the WMA Core plugin for tours and quotations.', 'wild-morocco-adventures' ) . '</p></div>'; }
}
add_action( 'admin_notices', 'wma_dependency_notice' );

function wma_trip_meta( string $key, int $id = 0 ) { return get_post_meta( $id ?: get_the_ID(), '_wma_' . $key, true ); }
function wma_trip_list( string $key, int $id = 0 ): array { $value = wma_trip_meta( $key, $id ); return is_array( $value ) ? $value : array(); }

function wma_trip_price( int $id = 0 ): string {
	$price = (float) wma_trip_meta( 'price', $id ); $currency = (string) ( wma_trip_meta( 'currency', $id ) ?: 'EUR' );
	if ( $price <= 0 ) { return __( 'Tailored quote', 'wild-morocco-adventures' ); }
	$symbols = array( 'EUR' => '€', 'MAD' => 'MAD ', 'USD' => '$', 'GBP' => '£' );
	return ( $symbols[ $currency ] ?? $currency . ' ' ) . number_format_i18n( $price, floor( $price ) === $price ? 0 : 2 );
}

function wma_quote_url( int $trip_id = 0 ): string {
	$page = get_page_by_path( 'request-a-quote' ); $id = $page ? $page->ID : 0;
	if ( $id && function_exists( 'pll_get_post' ) ) { $id = pll_get_post( $id ) ?: $id; }
	$url = $id ? get_permalink( $id ) : home_url( '/request-a-quote/' );
	return $trip_id ? add_query_arg( 'trip_id', $trip_id, $url ) : $url;
}

function wma_fallback_image( string $name = 'desert' ): string {
	$name = in_array( $name, array( 'desert', 'atlas', 'coast', 'marrakech' ), true ) ? $name : 'desert';
	return WMA_THEME_URL . '/assets/images/' . $name . '.webp';
}

/**
 * Return the locally hosted video and poster for a destination card.
 *
 * @return array{video:string,poster:string}
 */
function wma_destination_media( string $destination ): array {
	$destination = strtolower( remove_accents( $destination ) );
	$video       = 'desert';
	$poster      = 'desert';

	if ( str_contains( $destination, 'essaouira' ) ) {
		$video  = 'essaouira';
		$poster = 'coast';
	} elseif ( str_contains( $destination, 'atlantic' ) || str_contains( $destination, 'coast' ) ) {
		$video  = 'coast';
		$poster = 'coast';
	} elseif ( str_contains( $destination, 'atlas' ) || str_contains( $destination, 'mountain' ) ) {
		$video  = 'atlas';
		$poster = 'atlas';
	} elseif ( str_contains( $destination, 'imperial' ) || str_contains( $destination, 'city' ) || str_contains( $destination, 'medina' ) || str_contains( $destination, 'marrakech' ) ) {
		$video  = 'city';
		$poster = 'marrakech';
	}

	return array(
		'video'  => WMA_THEME_URL . '/assets/videos/' . $video . '.mp4',
		'poster' => wma_fallback_image( $poster ),
	);
}

function wma_trip_fallback_image( int $id ): string {
	$slugs = wp_get_post_terms( $id, 'wma_region', array( 'fields' => 'slugs' ) ); $joined = is_wp_error( $slugs ) ? '' : implode( ' ', $slugs );
	if ( str_contains( $joined, 'atlas' ) ) { return wma_fallback_image( 'atlas' ); }
	if ( str_contains( $joined, 'atlantic' ) || str_contains( $joined, 'coast' ) ) { return wma_fallback_image( 'coast' ); }
	if ( str_contains( $joined, 'marrakech' ) ) { return wma_fallback_image( 'marrakech' ); }
	return wma_fallback_image( 'desert' );
}

function wma_image_url( int $id, string $size = 'wma-card' ): string { return has_post_thumbnail( $id ) ? (string) get_the_post_thumbnail_url( $id, $size ) : wma_trip_fallback_image( $id ); }

function wma_item_list( array $items, string $class = 'wma-check-list' ): void {
	if ( ! $items ) { return; } echo '<ul class="' . esc_attr( $class ) . '">'; foreach ( $items as $item ) { echo '<li>' . esc_html( is_scalar( $item ) ? (string) $item : '' ) . '</li>'; } echo '</ul>';
}

function wma_language_switcher(): void {
	if ( ! function_exists( 'pll_the_languages' ) ) { return; }
	$languages = pll_the_languages( array( 'raw' => 1, 'hide_if_empty' => 0 ) ); if ( ! is_array( $languages ) || count( $languages ) < 2 ) { return; }
	echo '<nav class="wma-languages" aria-label="' . esc_attr__( 'Languages', 'wild-morocco-adventures' ) . '">'; foreach ( $languages as $language ) { printf( '<a href="%1$s" lang="%2$s" hreflang="%3$s"%4$s>%5$s</a>', esc_url( $language['url'] ), esc_attr( $language['locale'] ), esc_attr( $language['slug'] ), $language['current_lang'] ? ' aria-current="page"' : '', esc_html( strtoupper( $language['slug'] ) ) ); } echo '</nav>';
}

function wma_fallback_menu(): void {
	echo '<ul class="wma-menu"><li><a href="' . esc_url( get_post_type_archive_link( 'wma_trip' ) ?: home_url( '/tours/' ) ) . '">' . esc_html__( 'Tours', 'wild-morocco-adventures' ) . '</a></li><li><a href="' . esc_url( home_url( '/destinations/' ) ) . '">' . esc_html__( 'Destinations', 'wild-morocco-adventures' ) . '</a></li><li><a href="' . esc_url( home_url( '/about/' ) ) . '">' . esc_html__( 'About', 'wild-morocco-adventures' ) . '</a></li><li><a href="' . esc_url( home_url( '/travel-guide/' ) ) . '">' . esc_html__( 'Travel guide', 'wild-morocco-adventures' ) . '</a></li></ul>';
}

function wma_breadcrumbs(): void {
	if ( is_front_page() ) { return; }
	$items = array( array( __( 'Home', 'wild-morocco-adventures' ), home_url( '/' ) ) );
	if ( is_singular( 'wma_trip' ) ) { $items[] = array( __( 'Tours', 'wild-morocco-adventures' ), get_post_type_archive_link( 'wma_trip' ) ); $items[] = array( get_the_title(), '' ); }
	elseif ( is_post_type_archive( 'wma_trip' ) ) { $items[] = array( __( 'Tours', 'wild-morocco-adventures' ), '' ); }
	elseif ( is_tax() ) { $items[] = array( single_term_title( '', false ), '' ); }
	elseif ( is_home() ) { $items[] = array( __( 'Travel guide', 'wild-morocco-adventures' ), '' ); }
	else { $items[] = array( get_the_title(), '' ); }
	echo '<nav class="wma-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'wild-morocco-adventures' ) . '"><ol>'; foreach ( $items as $index => $item ) { echo '<li>'; echo $item[1] ? '<a href="' . esc_url( $item[1] ) . '">' . esc_html( $item[0] ) . '</a>' : '<span aria-current="page">' . esc_html( $item[0] ) . '</span>'; if ( $index < count( $items ) - 1 ) { echo '<i aria-hidden="true">/</i>'; } echo '</li>'; } echo '</ol></nav>';
}

function wma_trip_archive_filters( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() || ! ( $query->is_post_type_archive( 'wma_trip' ) || $query->is_tax( array( 'wma_region', 'wma_style', 'wma_interest' ) ) ) ) { return; }
	$query->set( 'posts_per_page', 9 ); $tax_query = (array) $query->get( 'tax_query' ); $meta_query = (array) $query->get( 'meta_query' );
	foreach ( array( 'region' => 'wma_region', 'style' => 'wma_style' ) as $parameter => $taxonomy ) { $value = sanitize_title( wp_unslash( $_GET[ $parameter ] ?? '' ) ); if ( $value ) { $tax_query[] = array( 'taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => $value ); } }
	$duration = absint( $_GET['duration_max'] ?? 0 ); if ( $duration ) { $meta_query[] = array( 'key' => '_wma_days', 'value' => $duration, 'type' => 'NUMERIC', 'compare' => '<=' ); }
	$budget = absint( $_GET['budget_max'] ?? 0 ); if ( $budget ) { $meta_query[] = array( 'key' => '_wma_price', 'value' => $budget, 'type' => 'NUMERIC', 'compare' => '<=' ); }
	if ( count( $tax_query ) > 1 ) { $tax_query['relation'] = 'AND'; } if ( count( $meta_query ) > 1 ) { $meta_query['relation'] = 'AND'; }
	$query->set( 'tax_query', $tax_query ); $query->set( 'meta_query', $meta_query );
	$sort = sanitize_key( wp_unslash( $_GET['sort'] ?? '' ) ); if ( in_array( $sort, array( 'price_asc', 'price_desc' ), true ) ) { $query->set( 'meta_key', '_wma_price' ); $query->set( 'orderby', 'meta_value_num' ); $query->set( 'order', 'price_desc' === $sort ? 'DESC' : 'ASC' ); } elseif ( 'duration' === $sort ) { $query->set( 'meta_key', '_wma_days' ); $query->set( 'orderby', 'meta_value_num' ); $query->set( 'order', 'ASC' ); }
}
add_action( 'pre_get_posts', 'wma_trip_archive_filters' );

function wma_customize( WP_Customize_Manager $manager ): void {
	$manager->add_section( 'wma_brand', array( 'title' => __( 'Wild Morocco brand', 'wild-morocco-adventures' ), 'priority' => 30 ) );
	foreach ( array( 'accent' => array( __( 'Clay accent', 'wild-morocco-adventures' ), '#B85C3B' ), 'olive' => array( __( 'Atlas olive', 'wild-morocco-adventures' ), '#263C32' ) ) as $key => $item ) { $setting = 'wma_' . $key . '_color'; $manager->add_setting( $setting, array( 'default' => $item[1], 'sanitize_callback' => 'sanitize_hex_color' ) ); $manager->add_control( new WP_Customize_Color_Control( $manager, $setting, array( 'label' => $item[0], 'section' => 'wma_brand' ) ) ); }
}
add_action( 'customize_register', 'wma_customize' );
function wma_custom_colors(): void { $accent = sanitize_hex_color( get_theme_mod( 'wma_accent_color', '#B85C3B' ) ) ?: '#B85C3B'; $olive = sanitize_hex_color( get_theme_mod( 'wma_olive_color', '#263C32' ) ) ?: '#263C32'; wp_add_inline_style( 'wma-main', ':root{--wma-clay:' . $accent . ';--wma-olive:' . $olive . ';}' ); }
add_action( 'wp_enqueue_scripts', 'wma_custom_colors', 20 );

function wma_body_class( array $classes ): array { if ( is_singular( 'wma_trip' ) ) { $classes[] = 'wma-single-trip'; } if ( function_exists( 'pll_current_language' ) ) { $classes[] = 'wma-lang-' . sanitize_html_class( pll_current_language( 'slug' ) ); } return $classes; }
add_filter( 'body_class', 'wma_body_class' );

function wma_seo_plugin_active(): bool { return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ); }
function wma_head_metadata(): void {
	if ( wma_seo_plugin_active() || is_404() ) { return; }
	$current_url = home_url( '/' );
	if ( is_singular() ) { $current_url = get_permalink(); }
	elseif ( is_post_type_archive( 'wma_trip' ) ) { $current_url = get_post_type_archive_link( 'wma_trip' ); }
	elseif ( is_tax() ) { $term_url = get_term_link( get_queried_object() ); if ( ! is_wp_error( $term_url ) ) { $current_url = $term_url; } }
	elseif ( is_home() && get_option( 'page_for_posts' ) ) { $current_url = get_permalink( (int) get_option( 'page_for_posts' ) ); }
	$allowed_query = array(); foreach ( array( 'region', 'style', 'duration_max', 'budget_max', 'sort', 'paged' ) as $key ) { if ( isset( $_GET[ $key ] ) && '' !== $_GET[ $key ] ) { $allowed_query[ $key ] = sanitize_text_field( wp_unslash( $_GET[ $key ] ) ); } } if ( $allowed_query ) { $current_url = add_query_arg( $allowed_query, $current_url ); }
	$title = wp_get_document_title(); $description = is_singular() ? get_the_excerpt() : get_bloginfo( 'description' ); $image = is_singular() && has_post_thumbnail() ? get_the_post_thumbnail_url( get_the_ID(), 'large' ) : wma_fallback_image();
	if ( ! is_singular() ) { echo '<link rel="canonical" href="' . esc_url( $current_url ) . '">'; }
	printf( '<meta property="og:site_name" content="%1$s"><meta property="og:type" content="%2$s"><meta property="og:title" content="%3$s"><meta property="og:description" content="%4$s"><meta property="og:url" content="%5$s"><meta property="og:image" content="%6$s"><meta name="twitter:card" content="summary_large_image">', esc_attr( get_bloginfo( 'name' ) ), is_singular( array( 'post', 'wma_trip' ) ) ? 'article' : 'website', esc_attr( $title ), esc_attr( wp_strip_all_tags( (string) $description ) ), esc_url( $current_url ), esc_url( (string) $image ) );
	$graph = array( array( '@type' => 'TravelAgency', '@id' => home_url( '/#organization' ), 'name' => function_exists( 'wma_get_setting' ) ? wma_get_setting( 'business_name', get_bloginfo( 'name' ) ) : get_bloginfo( 'name' ), 'url' => home_url( '/' ) ) );
	if ( is_singular( 'wma_trip' ) ) { $trip = array( '@type' => 'TouristTrip', '@id' => get_permalink() . '#trip', 'name' => get_the_title(), 'description' => wp_strip_all_tags( get_the_excerpt() ), 'url' => get_permalink(), 'image' => wma_image_url( get_the_ID(), 'full' ), 'provider' => array( '@id' => home_url( '/#organization' ) ) ); $graph[] = $trip; $faqs = wma_trip_list( 'faqs' ); $entities = array(); foreach ( $faqs as $faq ) { if ( is_array( $faq ) && ! empty( $faq['question'] ) && ! empty( $faq['answer'] ) ) { $entities[] = array( '@type' => 'Question', 'name' => $faq['question'], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => wp_strip_all_tags( $faq['answer'] ) ) ); } } if ( $entities ) { $graph[] = array( '@type' => 'FAQPage', 'mainEntity' => $entities ); } }
	if ( ! is_front_page() ) {
		$crumbs = array( array( 'name' => __( 'Home', 'wild-morocco-adventures' ), 'url' => home_url( '/' ) ) );
		if ( is_singular( 'wma_trip' ) ) { $crumbs[] = array( 'name' => __( 'Tours', 'wild-morocco-adventures' ), 'url' => get_post_type_archive_link( 'wma_trip' ) ); $crumbs[] = array( 'name' => get_the_title(), 'url' => get_permalink() ); }
		elseif ( is_post_type_archive( 'wma_trip' ) ) { $crumbs[] = array( 'name' => __( 'Tours', 'wild-morocco-adventures' ), 'url' => get_post_type_archive_link( 'wma_trip' ) ); }
		elseif ( is_tax() ) { $crumbs[] = array( 'name' => single_term_title( '', false ), 'url' => get_term_link( get_queried_object() ) ); }
		elseif ( is_singular() ) { $crumbs[] = array( 'name' => get_the_title(), 'url' => get_permalink() ); }
		if ( count( $crumbs ) > 1 ) { $items = array(); foreach ( $crumbs as $position => $crumb ) { $items[] = array( '@type' => 'ListItem', 'position' => $position + 1, 'name' => $crumb['name'], 'item' => $crumb['url'] ); } $graph[] = array( '@type' => 'BreadcrumbList', 'itemListElement' => $items ); }
	}
	echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
}
add_action( 'wp_head', 'wma_head_metadata', 20 );
