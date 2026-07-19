<?php
/**
 * Template Name: Request a Quote
 */
get_header(); the_post(); get_template_part( 'template-parts/page-hero' ); ?>
<section class="wma-section"><div class="wma-container wma-quote-layout"><aside><span class="wma-eyebrow"><?php esc_html_e( 'A journey made around you', 'wild-morocco-adventures' ); ?></span><h2><?php esc_html_e( 'Share the first pieces.', 'wild-morocco-adventures' ); ?></h2><div class="wma-rich-content"><?php the_content(); ?></div><ol class="wma-quote-steps"><li><span>1</span><?php esc_html_e( 'Tell us your ideas and dates.', 'wild-morocco-adventures' ); ?></li><li><span>2</span><?php esc_html_e( 'A local travel designer reviews your request.', 'wild-morocco-adventures' ); ?></li><li><span>3</span><?php esc_html_e( 'Receive a personal proposal to refine together.', 'wild-morocco-adventures' ); ?></li></ol></aside><div class="wma-form-card"><?php echo do_shortcode( '[wma_quote_form]' ); ?></div></div></section><?php get_footer(); ?>
