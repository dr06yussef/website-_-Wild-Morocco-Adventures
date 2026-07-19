<?php
/** Homepage. */
get_header();
$featured = new WP_Query( array( 'post_type' => 'wma_trip', 'post_status' => 'publish', 'posts_per_page' => 3, 'meta_key' => '_wma_featured', 'meta_value' => 1 ) );
if ( ! $featured->have_posts() ) { $featured = new WP_Query( array( 'post_type' => 'wma_trip', 'post_status' => 'publish', 'posts_per_page' => 3 ) ); }
$regions = get_terms( array( 'taxonomy' => 'wma_region', 'hide_empty' => false ) );
$styles = get_terms( array( 'taxonomy' => 'wma_style', 'hide_empty' => false, 'number' => 4 ) );

if ( ! is_wp_error( $regions ) ) {
	$region_order = array_flip( array( 'sahara-south', 'atlas-mountains', 'essaouira', 'atlantic-coast' ) );
	usort(
		$regions,
		static function ( WP_Term $first, WP_Term $second ) use ( $region_order ): int {
			return ( $region_order[ $first->slug ] ?? 20 ) <=> ( $region_order[ $second->slug ] ?? 20 );
		}
	);
	$regions = array_slice( $regions, 0, 4 );
}

$render_destination = static function ( string $name, string $url = '' ): void {
	$media = wma_destination_media( $name );
	$tag   = $url ? 'a' : 'div';
	?>
	<<?php echo esc_attr( $tag ); ?> class="wma-destination-card"<?php if ( $url ) : ?> href="<?php echo esc_url( $url ); ?>"<?php endif; ?> style="--destination-image:url('<?php echo esc_url( $media['poster'] ); ?>')">
		<video class="wma-destination-card__video" muted loop playsinline preload="none" poster="<?php echo esc_url( $media['poster'] ); ?>" aria-hidden="true" tabindex="-1" data-wma-destination-video>
			<source data-src="<?php echo esc_url( $media['video'] ); ?>" type="video/mp4">
		</video>
		<h3><?php echo esc_html( $name ); ?></h3>
		<i aria-hidden="true">↗</i>
	</<?php echo esc_attr( $tag ); ?>>
	<?php
};
?>
<section class="wma-home-hero"><img class="wma-home-hero-media" src="<?php echo esc_url( wma_fallback_image( 'desert' ) ); ?>" alt="" fetchpriority="high" decoding="async"><div class="wma-hero-shade"></div><div class="wma-container wma-home-hero-content"><span class="wma-eyebrow wma-eyebrow--light"><?php esc_html_e( 'Private journeys • Morocco', 'wild-morocco-adventures' ); ?></span><h1><?php esc_html_e( 'Go beyond the map. Feel the real Morocco.', 'wild-morocco-adventures' ); ?></h1><p><?php esc_html_e( 'Thoughtful journeys through mountain villages, desert horizons, storied medinas and Atlantic light—shaped around the way you want to travel.', 'wild-morocco-adventures' ); ?></p><div class="wma-button-row"><a class="wma-button wma-button--primary" href="<?php echo esc_url( wma_quote_url() ); ?>"><?php esc_html_e( 'Plan my journey', 'wild-morocco-adventures' ); ?></a><a class="wma-button wma-button--ghost" href="<?php echo esc_url( get_post_type_archive_link( 'wma_trip' ) ?: home_url( '/tours/' ) ); ?>"><?php esc_html_e( 'Explore tours', 'wild-morocco-adventures' ); ?></a></div></div><a class="wma-hero-scroll" href="#journeys"><span><?php esc_html_e( 'Begin exploring', 'wild-morocco-adventures' ); ?></span><i aria-hidden="true">↓</i></a></section>

<section class="wma-trust-strip"><div class="wma-container wma-trust-grid"><div><strong><?php esc_html_e( 'Made for you', 'wild-morocco-adventures' ); ?></strong><span><?php esc_html_e( 'Private, flexible routes', 'wild-morocco-adventures' ); ?></span></div><div><strong><?php esc_html_e( 'Rooted here', 'wild-morocco-adventures' ); ?></strong><span><?php esc_html_e( 'Local knowledge and care', 'wild-morocco-adventures' ); ?></span></div><div><strong><?php esc_html_e( 'At your rhythm', 'wild-morocco-adventures' ); ?></strong><span><?php esc_html_e( 'Space to truly experience', 'wild-morocco-adventures' ); ?></span></div><div><strong><?php esc_html_e( 'With purpose', 'wild-morocco-adventures' ); ?></strong><span><?php esc_html_e( 'Respectful travel choices', 'wild-morocco-adventures' ); ?></span></div></div></section>

<section class="wma-section wma-section--light" id="journeys"><div class="wma-container"><div class="wma-section-heading"><div><span class="wma-eyebrow"><?php esc_html_e( 'Journeys to inspire', 'wild-morocco-adventures' ); ?></span><h2><?php esc_html_e( 'Start with a possibility', 'wild-morocco-adventures' ); ?></h2></div><p><?php esc_html_e( 'Every itinerary is a starting point. We refine the pace, places and experiences until the journey feels unmistakably yours.', 'wild-morocco-adventures' ); ?></p></div><?php if ( $featured->have_posts() ) : ?><div class="wma-trip-grid"><?php while ( $featured->have_posts() ) { $featured->the_post(); get_template_part( 'template-parts/trip-card' ); } wp_reset_postdata(); ?></div><?php else : ?><div class="wma-empty-card"><h3><?php esc_html_e( 'Your first journeys are waiting to be added.', 'wild-morocco-adventures' ); ?></h3><p><?php esc_html_e( 'Use the WordPress setup tool to create removable starter journeys, or publish the client’s approved itineraries.', 'wild-morocco-adventures' ); ?></p></div><?php endif; ?><div class="wma-centered"><a class="wma-button wma-button--outline" href="<?php echo esc_url( get_post_type_archive_link( 'wma_trip' ) ?: home_url( '/tours/' ) ); ?>"><?php esc_html_e( 'See all journeys', 'wild-morocco-adventures' ); ?></a></div></div></section>

<section class="wma-section wma-section--olive">
	<div class="wma-container">
		<div class="wma-section-heading wma-section-heading--light">
			<div><span class="wma-eyebrow wma-eyebrow--light"><?php esc_html_e( 'Where Morocco opens up', 'wild-morocco-adventures' ); ?></span><h2><?php esc_html_e( 'Landscapes that change you', 'wild-morocco-adventures' ); ?></h2></div>
			<p><?php esc_html_e( 'Follow the landscape—from ochre walls to cedar valleys, rolling dunes and wind-shaped coasts.', 'wild-morocco-adventures' ); ?></p>
		</div>
		<div class="wma-destination-grid">
			<?php
			if ( ! is_wp_error( $regions ) && $regions ) {
				foreach ( $regions as $region ) {
					$term_url = get_term_link( $region );
					$render_destination( $region->name, is_wp_error( $term_url ) ? '' : $term_url );
				}
			} else {
				foreach ( array( 'Sahara & South', 'Atlas Mountains', 'Essaouira', 'Atlantic Coast' ) as $name ) {
					$render_destination( $name );
				}
			}
			?>
		</div>
	</div>
</section>

<section class="wma-section wma-section--story"><div class="wma-container wma-story-grid"><div class="wma-story-image" data-wma-reveal><img src="<?php echo esc_url( WMA_THEME_URL . '/assets/images/courtyard-tea.webp' ); ?>" alt="<?php esc_attr_e( 'A Moroccan host sharing mint tea with travelers in a sunlit courtyard', 'wild-morocco-adventures' ); ?>" loading="lazy" decoding="async"><div class="wma-story-note"><strong><?php esc_html_e( 'A slower kind of adventure', 'wild-morocco-adventures' ); ?></strong><span><?php esc_html_e( 'Time for the road, the table and the stories between.', 'wild-morocco-adventures' ); ?></span></div></div><div class="wma-story-copy" data-wma-reveal><span class="wma-eyebrow"><?php esc_html_e( 'Why travel with us', 'wild-morocco-adventures' ); ?></span><h2><?php esc_html_e( 'Planned with care. Lived with wonder.', 'wild-morocco-adventures' ); ?></h2><p><?php esc_html_e( 'The best journeys leave room for surprise without leaving anything important to chance. We pair thoughtful planning with local perspective, then stay close enough to help along the way.', 'wild-morocco-adventures' ); ?></p><ul class="wma-number-list"><li><span>01</span><div><strong><?php esc_html_e( 'Start with a conversation', 'wild-morocco-adventures' ); ?></strong><p><?php esc_html_e( 'Tell us what draws you to Morocco, how you like to travel and what should feel effortless.', 'wild-morocco-adventures' ); ?></p></div></li><li><span>02</span><div><strong><?php esc_html_e( 'Shape every detail', 'wild-morocco-adventures' ); ?></strong><p><?php esc_html_e( 'We refine route, rhythm and stays into one coherent journey.', 'wild-morocco-adventures' ); ?></p></div></li><li><span>03</span><div><strong><?php esc_html_e( 'Travel with confidence', 'wild-morocco-adventures' ); ?></strong><p><?php esc_html_e( 'Clear information and a local point of contact keep the experience flowing.', 'wild-morocco-adventures' ); ?></p></div></li></ul><a class="wma-text-link" href="<?php echo esc_url( home_url( '/why-travel-with-us/' ) ); ?>"><?php esc_html_e( 'How we work', 'wild-morocco-adventures' ); ?> <span aria-hidden="true">→</span></a></div></div></section>

<?php if ( ! is_wp_error( $styles ) && $styles ) : ?><section class="wma-section wma-section--sand"><div class="wma-container"><div class="wma-section-heading"><div><span class="wma-eyebrow"><?php esc_html_e( 'Travel your way', 'wild-morocco-adventures' ); ?></span><h2><?php esc_html_e( 'What calls you?', 'wild-morocco-adventures' ); ?></h2></div></div><div class="wma-style-grid"><?php foreach ( $styles as $index => $style ) : ?><a href="<?php echo esc_url( get_term_link( $style ) ); ?>"><span>0<?php echo esc_html( (string) ( $index + 1 ) ); ?></span><h3><?php echo esc_html( $style->name ); ?></h3><i aria-hidden="true">→</i></a><?php endforeach; ?></div></div></section><?php endif; ?>

<?php $testimonials = new WP_Query( array( 'post_type' => 'wma_testimonial', 'post_status' => 'publish', 'posts_per_page' => 3, 'orderby' => 'menu_order date', 'order' => 'DESC' ) ); if ( $testimonials->have_posts() ) : ?><section class="wma-section wma-section--testimonials"><div class="wma-container"><span class="wma-eyebrow"><?php esc_html_e( 'Traveller stories', 'wild-morocco-adventures' ); ?></span><h2><?php esc_html_e( 'Words carried home', 'wild-morocco-adventures' ); ?></h2><div class="wma-testimonial-grid"><?php while ( $testimonials->have_posts() ) : $testimonials->the_post(); ?><blockquote><div class="wma-stars" aria-label="<?php echo esc_attr( sprintf( __( '%d out of 5 stars', 'wild-morocco-adventures' ), absint( get_post_meta( get_the_ID(), '_wma_rating', true ) ) ) ); ?>"><?php echo esc_html( str_repeat( '★', absint( get_post_meta( get_the_ID(), '_wma_rating', true ) ) ) ); ?></div><?php the_content(); ?><footer><strong><?php the_title(); ?></strong><span><?php echo esc_html( (string) get_post_meta( get_the_ID(), '_wma_location', true ) ); ?></span></footer></blockquote><?php endwhile; wp_reset_postdata(); ?></div></div></section><?php endif; ?>

<?php $journal = new WP_Query( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 3, 'ignore_sticky_posts' => true ) ); if ( $journal->have_posts() ) : ?><section class="wma-section wma-section--journal"><div class="wma-container"><div class="wma-section-heading"><div><span class="wma-eyebrow"><?php esc_html_e( 'From the journal', 'wild-morocco-adventures' ); ?></span><h2><?php esc_html_e( 'Stories for the road', 'wild-morocco-adventures' ); ?></h2></div><a class="wma-text-link" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/journal/' ) ); ?>"><?php esc_html_e( 'Read all stories', 'wild-morocco-adventures' ); ?> <span aria-hidden="true">→</span></a></div><div class="wma-journal-grid"><?php while ( $journal->have_posts() ) : $journal->the_post(); ?><article class="wma-journal-card"><a class="wma-journal-image" href="<?php the_permalink(); ?>"><?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'large', array( 'loading' => 'lazy' ) ); } else { echo '<img src="' . esc_url( wma_fallback_image( 'marrakech' ) ) . '" alt="" loading="lazy">'; } ?></a><div><span><?php echo esc_html( get_the_date() ); ?></span><h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3><p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24 ) ); ?></p></div></article><?php endwhile; wp_reset_postdata(); ?></div></div></section><?php endif; ?>

<section class="wma-section wma-section--responsible"><div class="wma-container wma-responsible-grid"><div><span class="wma-eyebrow wma-eyebrow--light"><?php esc_html_e( 'Travel with purpose', 'wild-morocco-adventures' ); ?></span><h2><?php esc_html_e( 'Leave with more. Leave less behind.', 'wild-morocco-adventures' ); ?></h2></div><div><p><?php esc_html_e( 'Thoughtful travel begins with time, context and clear choices. Your final proposal can favour realistic pacing, locally rooted stays and experiences selected with respect for the places visited.', 'wild-morocco-adventures' ); ?></p><a class="wma-text-link wma-text-link--light" href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'Our approach', 'wild-morocco-adventures' ); ?> <span aria-hidden="true">→</span></a></div></div></section>
<?php get_footer(); ?>
