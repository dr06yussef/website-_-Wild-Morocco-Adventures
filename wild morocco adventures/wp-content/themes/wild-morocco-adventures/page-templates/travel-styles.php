<?php
/**
 * Template Name: Travel Styles
 */
get_header(); the_post(); get_template_part( 'template-parts/page-hero' ); $terms = get_terms( array( 'taxonomy' => 'wma_style', 'hide_empty' => false ) ); ?>
<section class="wma-section"><div class="wma-container"><div class="wma-prose wma-prose--intro"><?php the_content(); ?></div><div class="wma-style-list"><?php if ( ! is_wp_error( $terms ) ) : foreach ( $terms as $index => $term ) : ?><a href="<?php echo esc_url( get_term_link( $term ) ); ?>"><span><?php echo esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span><div><h2><?php echo esc_html( $term->name ); ?></h2><?php if ( $term->description ) : ?><p><?php echo esc_html( $term->description ); ?></p><?php endif; ?></div><i aria-hidden="true">↗</i></a><?php endforeach; endif; ?></div></div></section><?php get_footer(); ?>
