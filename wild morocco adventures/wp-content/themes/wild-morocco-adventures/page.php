<?php
/** Default page. */
get_header(); while ( have_posts() ) : the_post(); get_template_part( 'template-parts/page-hero' ); ?><article class="wma-section"><div class="wma-container wma-prose"><?php the_content(); wp_link_pages(); ?></div></article><?php endwhile; get_footer();
