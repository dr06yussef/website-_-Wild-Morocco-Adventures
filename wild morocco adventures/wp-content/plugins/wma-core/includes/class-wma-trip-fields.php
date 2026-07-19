<?php
/**
 * Native WordPress fields for structured tour content.
 */

defined( 'ABSPATH' ) || exit;

final class WMA_Trip_Fields {
	public static function init(): void {
		add_action( 'add_meta_boxes_wma_trip', array( __CLASS__, 'boxes' ) );
		add_action( 'save_post_wma_trip', array( __CLASS__, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	public static function boxes(): void {
		add_meta_box( 'wma_trip_summary', __( 'Tour summary', 'wma-core' ), array( __CLASS__, 'summary' ), 'wma_trip', 'normal', 'high' );
		add_meta_box( 'wma_trip_media', __( 'Gallery and route', 'wma-core' ), array( __CLASS__, 'media' ), 'wma_trip', 'normal' );
		add_meta_box( 'wma_trip_itinerary', __( 'Day-by-day itinerary', 'wma-core' ), array( __CLASS__, 'itinerary' ), 'wma_trip', 'normal' );
		add_meta_box( 'wma_trip_information', __( 'Trip information', 'wma-core' ), array( __CLASS__, 'information' ), 'wma_trip', 'normal' );
		add_meta_box( 'wma_trip_faqs', __( 'FAQs and related tours', 'wma-core' ), array( __CLASS__, 'faqs' ), 'wma_trip', 'normal' );
		add_meta_box( 'wma_trip_featured', __( 'Tour display', 'wma-core' ), array( __CLASS__, 'featured' ), 'wma_trip', 'side' );
	}

	public static function assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || 'wma_trip' !== $screen->post_type ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'wma-admin', WMA_CORE_URL . 'assets/admin.css', array(), WMA_CORE_VERSION );
		wp_enqueue_script( 'wma-admin', WMA_CORE_URL . 'assets/admin.js', array( 'jquery' ), WMA_CORE_VERSION, true );
		wp_localize_script( 'wma-admin', 'wmaAdmin', array( 'galleryTitle' => __( 'Choose tour images', 'wma-core' ), 'galleryButton' => __( 'Use selected images', 'wma-core' ), 'imageTitle' => __( 'Choose route image', 'wma-core' ), 'imageButton' => __( 'Use this image', 'wma-core' ) ) );
	}

	public static function summary( WP_Post $post ): void {
		wp_nonce_field( 'wma_save_trip', 'wma_trip_nonce' );
		$fields = array(
			'subtitle' => array( __( 'Subtitle', 'wma-core' ), 'text' ), 'price' => array( __( 'Starting price', 'wma-core' ), 'number' ),
			'currency' => array( __( 'Currency (EUR, MAD, USD or GBP)', 'wma-core' ), 'text' ), 'days' => array( __( 'Days', 'wma-core' ), 'number' ),
			'nights' => array( __( 'Nights', 'wma-core' ), 'number' ), 'start_location' => array( __( 'Start location', 'wma-core' ), 'text' ),
			'end_location' => array( __( 'End location', 'wma-core' ), 'text' ), 'difficulty' => array( __( 'Difficulty', 'wma-core' ), 'text' ),
			'group_size' => array( __( 'Group size', 'wma-core' ), 'text' ),
		);
		echo '<div class="wma-fields-grid">';
		foreach ( $fields as $key => $field ) {
			$value = get_post_meta( $post->ID, '_wma_' . $key, true );
			printf( '<label class="wma-field"><strong>%1$s</strong><input class="widefat" type="%2$s" name="wma_%3$s" value="%4$s"%5$s></label>', esc_html( $field[0] ), esc_attr( $field[1] ), esc_attr( $key ), esc_attr( (string) $value ), 'number' === $field[1] ? ' min="0" step="0.01"' : '' );
		}
		echo '</div>';
		self::list_field( $post->ID, 'highlights', __( 'Highlights', 'wma-core' ) );
	}

	public static function media( WP_Post $post ): void {
		$gallery = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post->ID, '_wma_gallery_ids', true ) ) ) );
		$route = absint( get_post_meta( $post->ID, '_wma_route_image_id', true ) );
		?>
		<div data-wma-gallery><input type="hidden" name="wma_gallery_ids" value="<?php echo esc_attr( implode( ',', $gallery ) ); ?>" data-wma-gallery-input><strong><?php esc_html_e( 'Tour gallery', 'wma-core' ); ?></strong><div class="wma-media-preview" data-wma-gallery-preview><?php foreach ( $gallery as $id ) { echo wp_get_attachment_image( $id, 'thumbnail' ); } ?></div><p><button class="button" type="button" data-wma-gallery-select><?php esc_html_e( 'Choose images', 'wma-core' ); ?></button> <button class="button-link-delete" type="button" data-wma-gallery-clear><?php esc_html_e( 'Clear', 'wma-core' ); ?></button></p></div>
		<hr>
		<div data-wma-image><input type="hidden" name="wma_route_image_id" value="<?php echo esc_attr( (string) $route ); ?>" data-wma-image-input><strong><?php esc_html_e( 'Route map or route image', 'wma-core' ); ?></strong><div class="wma-media-preview" data-wma-image-preview><?php if ( $route ) { echo wp_get_attachment_image( $route, 'medium' ); } ?></div><p><button class="button" type="button" data-wma-image-select><?php esc_html_e( 'Choose route image', 'wma-core' ); ?></button> <button class="button-link-delete" type="button" data-wma-image-clear><?php esc_html_e( 'Clear', 'wma-core' ); ?></button></p></div>
		<?php
	}

	public static function itinerary( WP_Post $post ): void {
		$rows = self::array_meta( $post->ID, '_wma_itinerary' );
		$rows = $rows ?: array( array() );
		?>
		<p><?php esc_html_e( 'Add each day in display order. Leave a row empty to remove it.', 'wma-core' ); ?></p>
		<div class="wma-repeater" data-wma-repeater="itinerary"><div data-wma-repeater-rows><?php foreach ( $rows as $index => $row ) { self::itinerary_row( (string) $index, is_array( $row ) ? $row : array() ); } ?></div><button class="button" type="button" data-wma-add-row="itinerary"><?php esc_html_e( 'Add itinerary day', 'wma-core' ); ?></button><template data-wma-template="itinerary"><?php self::itinerary_row( '__INDEX__', array() ); ?></template></div>
		<?php
	}

	private static function itinerary_row( string $index, array $row ): void {
		$row = wp_parse_args( $row, array( 'day' => '', 'title' => '', 'location' => '', 'description' => '', 'overnight' => '' ) );
		?>
		<div class="wma-repeater-row" data-wma-row><div class="wma-row-heading"><strong><?php esc_html_e( 'Itinerary day', 'wma-core' ); ?></strong><button class="button-link-delete" type="button" data-wma-remove-row><?php esc_html_e( 'Remove', 'wma-core' ); ?></button></div><div class="wma-fields-grid"><label class="wma-field"><span><?php esc_html_e( 'Day', 'wma-core' ); ?></span><input class="widefat" type="number" min="1" name="wma_itinerary[<?php echo esc_attr( $index ); ?>][day]" value="<?php echo esc_attr( (string) $row['day'] ); ?>"></label><label class="wma-field"><span><?php esc_html_e( 'Title', 'wma-core' ); ?></span><input class="widefat" name="wma_itinerary[<?php echo esc_attr( $index ); ?>][title]" value="<?php echo esc_attr( (string) $row['title'] ); ?>"></label><label class="wma-field"><span><?php esc_html_e( 'Location / route', 'wma-core' ); ?></span><input class="widefat" name="wma_itinerary[<?php echo esc_attr( $index ); ?>][location]" value="<?php echo esc_attr( (string) $row['location'] ); ?>"></label><label class="wma-field"><span><?php esc_html_e( 'Overnight', 'wma-core' ); ?></span><input class="widefat" name="wma_itinerary[<?php echo esc_attr( $index ); ?>][overnight]" value="<?php echo esc_attr( (string) $row['overnight'] ); ?>"></label></div><label class="wma-field"><span><?php esc_html_e( 'Description', 'wma-core' ); ?></span><textarea class="widefat" rows="5" name="wma_itinerary[<?php echo esc_attr( $index ); ?>][description]"><?php echo esc_textarea( (string) $row['description'] ); ?></textarea></label></div>
		<?php
	}

	public static function information( WP_Post $post ): void {
		echo '<div class="wma-fields-grid">';
		self::list_field( $post->ID, 'included', __( 'Included services', 'wma-core' ) );
		self::list_field( $post->ID, 'excluded', __( 'Excluded services', 'wma-core' ) );
		self::list_field( $post->ID, 'what_to_bring', __( 'What to bring', 'wma-core' ) );
		echo '</div><h3>' . esc_html__( 'Accommodation', 'wma-core' ) . '</h3>';
		wp_editor( (string) get_post_meta( $post->ID, '_wma_accommodation', true ), 'wma_accommodation', array( 'textarea_rows' => 6, 'media_buttons' => false ) );
		echo '<h3>' . esc_html__( 'Practical advice', 'wma-core' ) . '</h3>';
		wp_editor( (string) get_post_meta( $post->ID, '_wma_advice', true ), 'wma_advice', array( 'textarea_rows' => 6, 'media_buttons' => false ) );
	}

	private static function list_field( int $post_id, string $key, string $label ): void {
		printf( '<label class="wma-field"><strong>%1$s</strong><span class="description">%2$s</span><textarea class="widefat" rows="6" name="wma_%3$s">%4$s</textarea></label>', esc_html( $label ), esc_html__( 'One item per line.', 'wma-core' ), esc_attr( $key ), esc_textarea( implode( "\n", self::array_meta( $post_id, '_wma_' . $key ) ) ) );
	}

	public static function faqs( WP_Post $post ): void {
		$rows = self::array_meta( $post->ID, '_wma_faqs' );
		$rows = $rows ?: array( array() );
		$related = array_map( 'absint', self::array_meta( $post->ID, '_wma_related_trip_ids' ) );
		$tours = get_posts( array( 'post_type' => 'wma_trip', 'post_status' => array( 'publish', 'draft', 'pending' ), 'posts_per_page' => 100, 'exclude' => array( $post->ID ), 'orderby' => 'title', 'order' => 'ASC' ) );
		?>
		<div class="wma-repeater" data-wma-repeater="faq"><div data-wma-repeater-rows><?php foreach ( $rows as $index => $row ) { self::faq_row( (string) $index, is_array( $row ) ? $row : array() ); } ?></div><button class="button" type="button" data-wma-add-row="faq"><?php esc_html_e( 'Add FAQ', 'wma-core' ); ?></button><template data-wma-template="faq"><?php self::faq_row( '__INDEX__', array() ); ?></template></div>
		<hr><label class="wma-field"><strong><?php esc_html_e( 'Related tours', 'wma-core' ); ?></strong><select class="widefat" name="wma_related_trip_ids[]" multiple size="7"><?php foreach ( $tours as $tour ) : ?><option value="<?php echo esc_attr( (string) $tour->ID ); ?>" <?php selected( in_array( $tour->ID, $related, true ) ); ?>><?php echo esc_html( $tour->post_title ); ?></option><?php endforeach; ?></select></label>
		<?php
	}

	private static function faq_row( string $index, array $row ): void {
		$row = wp_parse_args( $row, array( 'question' => '', 'answer' => '' ) );
		?>
		<div class="wma-repeater-row" data-wma-row><div class="wma-row-heading"><strong><?php esc_html_e( 'Question and answer', 'wma-core' ); ?></strong><button class="button-link-delete" type="button" data-wma-remove-row><?php esc_html_e( 'Remove', 'wma-core' ); ?></button></div><label class="wma-field"><span><?php esc_html_e( 'Question', 'wma-core' ); ?></span><input class="widefat" name="wma_faqs[<?php echo esc_attr( $index ); ?>][question]" value="<?php echo esc_attr( (string) $row['question'] ); ?>"></label><label class="wma-field"><span><?php esc_html_e( 'Answer', 'wma-core' ); ?></span><textarea class="widefat" rows="4" name="wma_faqs[<?php echo esc_attr( $index ); ?>][answer]"><?php echo esc_textarea( (string) $row['answer'] ); ?></textarea></label></div>
		<?php
	}

	public static function featured( WP_Post $post ): void {
		printf( '<label><input type="checkbox" name="wma_featured" value="1" %1$s> %2$s</label>', checked( (bool) get_post_meta( $post->ID, '_wma_featured', true ), true, false ), esc_html__( 'Feature this tour on the homepage', 'wma-core' ) );
	}

	public static function save( int $post_id ): void {
		if ( ! isset( $_POST['wma_trip_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wma_trip_nonce'] ) ), 'wma_save_trip' ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_wma_trip', $post_id ) ) {
			return;
		}
		foreach ( array( 'subtitle', 'currency', 'start_location', 'end_location', 'difficulty', 'group_size' ) as $key ) {
			self::store( $post_id, '_wma_' . $key, sanitize_text_field( wp_unslash( $_POST[ 'wma_' . $key ] ?? '' ) ) );
		}
		foreach ( array( 'price', 'days', 'nights' ) as $key ) {
			$value = isset( $_POST[ 'wma_' . $key ] ) ? (float) wp_unslash( $_POST[ 'wma_' . $key ] ) : 0;
			self::store( $post_id, '_wma_' . $key, $value > 0 ? $value : '' );
		}
		foreach ( array( 'highlights', 'included', 'excluded', 'what_to_bring' ) as $key ) {
			$raw = sanitize_textarea_field( wp_unslash( $_POST[ 'wma_' . $key ] ?? '' ) );
			self::store( $post_id, '_wma_' . $key, array_values( array_filter( array_map( 'trim', preg_split( '/\R/', $raw ) ?: array() ) ) ) );
		}
		foreach ( array( 'accommodation', 'advice' ) as $key ) {
			self::store( $post_id, '_wma_' . $key, wp_kses_post( wp_unslash( $_POST[ 'wma_' . $key ] ?? '' ) ) );
		}
		$gallery = implode( ',', array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST['wma_gallery_ids'] ?? '' ) ) ) ) ) );
		self::store( $post_id, '_wma_gallery_ids', $gallery );
		self::store( $post_id, '_wma_route_image_id', absint( $_POST['wma_route_image_id'] ?? 0 ) );

		$itinerary = array();
		foreach ( (array) wp_unslash( $_POST['wma_itinerary'] ?? array() ) as $row ) {
			if ( ! is_array( $row ) ) { continue; }
			$item = array( 'day' => absint( $row['day'] ?? 0 ), 'title' => sanitize_text_field( $row['title'] ?? '' ), 'location' => sanitize_text_field( $row['location'] ?? '' ), 'description' => wp_kses_post( $row['description'] ?? '' ), 'overnight' => sanitize_text_field( $row['overnight'] ?? '' ) );
			if ( $item['title'] || $item['description'] ) { $itinerary[] = $item; }
		}
		self::store( $post_id, '_wma_itinerary', $itinerary );

		$faqs = array();
		foreach ( (array) wp_unslash( $_POST['wma_faqs'] ?? array() ) as $row ) {
			if ( ! is_array( $row ) ) { continue; }
			$item = array( 'question' => sanitize_text_field( $row['question'] ?? '' ), 'answer' => wp_kses_post( $row['answer'] ?? '' ) );
			if ( $item['question'] && $item['answer'] ) { $faqs[] = $item; }
		}
		self::store( $post_id, '_wma_faqs', $faqs );
		self::store( $post_id, '_wma_related_trip_ids', array_values( array_unique( array_filter( array_map( 'absint', (array) ( $_POST['wma_related_trip_ids'] ?? array() ) ) ) ) ) );
		self::store( $post_id, '_wma_featured', isset( $_POST['wma_featured'] ) ? 1 : '' );
	}

	private static function array_meta( int $post_id, string $key ): array {
		$value = get_post_meta( $post_id, $key, true );
		return is_array( $value ) ? $value : array();
	}

	private static function store( int $post_id, string $key, $value ): void {
		if ( '' === $value || 0 === $value || array() === $value ) { delete_post_meta( $post_id, $key ); } else { update_post_meta( $post_id, $key, $value ); }
	}
}
