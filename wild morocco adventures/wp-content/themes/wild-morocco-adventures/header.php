<?php
/** Site header. */
defined( 'ABSPATH' ) || exit;
?>
<!doctype html><html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><?php wp_head(); ?></head><body <?php body_class(); ?>><?php wp_body_open(); ?>
<a class="wma-skip-link" href="#main-content"><?php esc_html_e( 'Skip to content', 'wild-morocco-adventures' ); ?></a>
<header class="wma-site-header" data-wma-header><div class="wma-container wma-header-inner">
	<div class="wma-brand"><?php if ( has_custom_logo() ) { the_custom_logo(); } else { ?><a class="wma-wordmark" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><span class="wma-wordmark-mark" aria-hidden="true">W</span><span><strong><?php esc_html_e( 'Wild Morocco', 'wild-morocco-adventures' ); ?></strong><small><?php esc_html_e( 'Adventures', 'wild-morocco-adventures' ); ?></small></span></a><?php } ?></div>
	<button class="wma-menu-toggle" type="button" aria-expanded="false" aria-controls="wma-primary-navigation" data-wma-menu-toggle><span class="wma-menu-toggle-lines" aria-hidden="true"><i></i><i></i><i></i></span><span class="screen-reader-text"><?php esc_html_e( 'Open menu', 'wild-morocco-adventures' ); ?></span></button>
	<div class="wma-navigation-panel" id="wma-primary-navigation" data-wma-menu-panel><nav class="wma-primary-nav" aria-label="<?php esc_attr_e( 'Primary navigation', 'wild-morocco-adventures' ); ?>"><?php wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_class' => 'wma-menu', 'fallback_cb' => 'wma_fallback_menu', 'depth' => 2 ) ); ?></nav><div class="wma-header-actions"><?php wma_language_switcher(); ?><a class="wma-button wma-button--small wma-button--primary" href="<?php echo esc_url( wma_quote_url() ); ?>"><?php esc_html_e( 'Plan my journey', 'wild-morocco-adventures' ); ?></a></div></div>
</div><div class="wma-scroll-progress" aria-hidden="true"><span data-wma-scroll-progress></span></div></header><main id="main-content" class="wma-site-main">
