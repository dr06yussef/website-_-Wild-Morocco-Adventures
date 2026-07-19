<?php
/** Shared interior page hero. */
defined( 'ABSPATH' ) || exit;
$image = get_the_post_thumbnail_url( get_the_ID(), 'wma-hero' ) ?: wma_fallback_image( 'marrakech' );
?>
<header class="wma-page-hero" style="--page-image:url('<?php echo esc_url( $image ); ?>')"><div class="wma-container"><?php wma_breadcrumbs(); ?><span class="wma-eyebrow wma-eyebrow--light"><?php esc_html_e( 'Wild Morocco Adventures', 'wild-morocco-adventures' ); ?></span><h1><?php the_title(); ?></h1><?php if ( has_excerpt() ) : ?><p><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?></div></header>
