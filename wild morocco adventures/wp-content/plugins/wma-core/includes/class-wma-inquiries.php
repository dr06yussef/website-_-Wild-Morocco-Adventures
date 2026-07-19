<?php
/**
 * Secure quotation form, private lead storage and workflow.
 */

defined( 'ABSPATH' ) || exit;

final class WMA_Inquiries {
	private const STATUSES = array( 'new' => 'New', 'contacted' => 'Contacted', 'quote_sent' => 'Quote sent', 'won' => 'Won', 'closed' => 'Closed' );

	public static function init(): void {
		add_shortcode( 'wma_quote_form', array( __CLASS__, 'form' ) );
		add_action( 'admin_post_wma_submit_inquiry', array( __CLASS__, 'submit' ) );
		add_action( 'admin_post_nopriv_wma_submit_inquiry', array( __CLASS__, 'submit' ) );
		add_action( 'add_meta_boxes_wma_inquiry', array( __CLASS__, 'boxes' ) );
		add_action( 'save_post_wma_inquiry', array( __CLASS__, 'save' ) );
		add_filter( 'manage_wma_inquiry_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_wma_inquiry_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'filter' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'filter_query' ) );
		add_action( 'admin_menu', array( __CLASS__, 'export_page' ) );
		add_action( 'admin_post_wma_export_inquiries', array( __CLASS__, 'export' ) );
		add_action( 'wma_daily_privacy_cleanup', array( __CLASS__, 'cleanup' ) );
	}

	public static function form( array $attributes = array() ): string {
		$attributes = shortcode_atts( array( 'tour_id' => 0 ), $attributes, 'wma_quote_form' );
		$tour_id = absint( $attributes['tour_id'] );
		if ( ! $tour_id && isset( $_GET['trip_id'] ) ) { $tour_id = absint( $_GET['trip_id'] ); }
		$status = isset( $_GET['wma_quote'] ) ? sanitize_key( wp_unslash( $_GET['wma_quote'] ) ) : '';
		$tours = get_posts( array( 'post_type' => 'wma_trip', 'post_status' => 'publish', 'posts_per_page' => 100, 'orderby' => 'title', 'order' => 'ASC' ) );
		$interests = get_terms( array( 'taxonomy' => 'wma_interest', 'hide_empty' => false ) );
		ob_start();
		?>
		<div class="wma-quote-form-wrap" id="quote-form">
			<?php self::notice( $status ); ?>
			<form class="wma-quote-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="wma_submit_inquiry"><input type="hidden" name="redirect_to" value="<?php echo esc_url( self::current_url() ); ?>"><?php wp_nonce_field( 'wma_submit_inquiry', 'wma_inquiry_nonce' ); ?>
				<div class="wma-honeypot" aria-hidden="true"><label><?php esc_html_e( 'Leave empty', 'wma-core' ); ?><input name="company_website" tabindex="-1" autocomplete="off"></label></div>
				<div class="wma-form-grid">
					<label class="wma-form-field wma-form-field--wide"><span><?php esc_html_e( 'Tour', 'wma-core' ); ?></span><select name="tour_id"><option value="0"><?php esc_html_e( 'Custom trip / not sure yet', 'wma-core' ); ?></option><?php foreach ( $tours as $tour ) : ?><option value="<?php echo esc_attr( (string) $tour->ID ); ?>" <?php selected( $tour_id, $tour->ID ); ?>><?php echo esc_html( $tour->post_title ); ?></option><?php endforeach; ?></select></label>
					<?php self::input( 'name', __( 'Full name', 'wma-core' ), 'text', true, 'name' ); ?>
					<?php self::input( 'email', __( 'Email address', 'wma-core' ), 'email', true, 'email' ); ?>
					<?php self::input( 'phone', __( 'Telephone / WhatsApp', 'wma-core' ), 'tel', false, 'tel' ); ?>
					<?php self::input( 'country', __( 'Country', 'wma-core' ), 'text', false, 'country-name' ); ?>
					<?php self::input( 'preferred_date', __( 'Preferred start date', 'wma-core' ), 'date' ); ?>
					<label class="wma-form-field wma-form-field--check"><input type="checkbox" name="flexible_dates" value="1"><span><?php esc_html_e( 'My dates are flexible', 'wma-core' ); ?></span></label>
					<label class="wma-form-field"><span><?php esc_html_e( 'Adults', 'wma-core' ); ?></span><input type="number" name="adults" min="1" max="50" value="2"></label>
					<label class="wma-form-field"><span><?php esc_html_e( 'Children', 'wma-core' ); ?></span><input type="number" name="children" min="0" max="30" value="0"></label>
					<?php self::input( 'budget', __( 'Approximate budget', 'wma-core' ) ); ?>
					<label class="wma-form-field"><span><?php esc_html_e( 'Preferred language', 'wma-core' ); ?></span><select name="language"><option value="fr" <?php selected( 'fr', self::language() ); ?>>Français</option><option value="en" <?php selected( 'en', self::language() ); ?>>English</option><option value="ar" <?php selected( 'ar', self::language() ); ?>>العربية</option></select></label>
				</div>
				<?php if ( ! is_wp_error( $interests ) && $interests ) : ?><fieldset class="wma-form-field wma-form-field--wide"><legend><?php esc_html_e( 'What interests you?', 'wma-core' ); ?></legend><div class="wma-choice-grid"><?php foreach ( $interests as $interest ) : ?><label><input type="checkbox" name="interests[]" value="<?php echo esc_attr( (string) $interest->term_id ); ?>"> <span><?php echo esc_html( $interest->name ); ?></span></label><?php endforeach; ?></div></fieldset><?php endif; ?>
				<label class="wma-form-field wma-form-field--wide"><span><?php esc_html_e( 'Tell us about your ideal trip', 'wma-core' ); ?> <abbr title="<?php esc_attr_e( 'required', 'wma-core' ); ?>">*</abbr></span><textarea name="message" rows="6" required></textarea></label>
				<label class="wma-form-field wma-form-field--check wma-form-field--wide"><input type="checkbox" name="consent" value="1" required><span><?php esc_html_e( 'I agree that Wild Morocco Adventures may use this information to respond to my request.', 'wma-core' ); ?> *</span></label>
				<button class="wma-button wma-button--primary" type="submit"><?php esc_html_e( 'Send my request', 'wma-core' ); ?></button>
			</form>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private static function input( string $name, string $label, string $type = 'text', bool $required = false, string $autocomplete = '' ): void {
		printf( '<label class="wma-form-field"><span>%1$s%2$s</span><input type="%3$s" name="%4$s"%5$s%6$s></label>', esc_html( $label ), $required ? ' <abbr title="' . esc_attr__( 'required', 'wma-core' ) . '">*</abbr>' : '', esc_attr( $type ), esc_attr( $name ), $required ? ' required' : '', $autocomplete ? ' autocomplete="' . esc_attr( $autocomplete ) . '"' : '' );
	}

	private static function notice( string $status ): void {
		$messages = array( 'success' => array( 'success', __( 'Thank you. Your request has been received and our team will contact you shortly.', 'wma-core' ) ), 'error' => array( 'error', __( 'Please check the required fields and try again.', 'wma-core' ) ), 'rate' => array( 'error', __( 'Too many requests were sent. Please wait before trying again.', 'wma-core' ) ) );
		if ( isset( $messages[ $status ] ) ) { printf( '<div class="wma-form-notice wma-form-notice--%1$s" role="status" tabindex="-1">%2$s</div>', esc_attr( $messages[ $status ][0] ), esc_html( $messages[ $status ][1] ) ); }
	}

	public static function submit(): void {
		$redirect = wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['redirect_to'] ?? '' ) ), home_url( '/' ) );
		if ( ! isset( $_POST['wma_inquiry_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wma_inquiry_nonce'] ) ), 'wma_submit_inquiry' ) ) { self::go( $redirect, 'error' ); }
		if ( ! empty( $_POST['company_website'] ) ) { self::go( $redirect, 'success' ); }
		$name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$message = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
		if ( ! $name || ! is_email( $email ) || ! $message || empty( $_POST['consent'] ) ) { self::go( $redirect, 'error' ); }

		$ip_hash = hash_hmac( 'sha256', sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ), wp_salt( 'auth' ) );
		$ip_rate_key = 'wma_rate_ip_' . md5( $ip_hash );
		$ip_rate = absint( get_transient( $ip_rate_key ) );
		if ( $ip_rate >= 10 ) { self::go( $redirect, 'rate' ); }
		set_transient( $ip_rate_key, $ip_rate + 1, HOUR_IN_SECONDS );
		$rate_key = 'wma_rate_' . md5( $ip_hash . strtolower( $email ) );
		$rate = absint( get_transient( $rate_key ) );
		if ( $rate >= 3 ) { self::go( $redirect, 'rate' ); }
		set_transient( $rate_key, $rate + 1, HOUR_IN_SECONDS );

		$tour_id = absint( $_POST['tour_id'] ?? 0 );
		$language = sanitize_key( wp_unslash( $_POST['language'] ?? self::language() ) );
		if ( ! in_array( $language, array( 'fr', 'en', 'ar' ), true ) ) { $language = 'en'; }
		$fingerprint = hash_hmac( 'sha256', strtolower( $email ) . '|' . $tour_id . '|' . $message, wp_salt( 'nonce' ) );
		$duplicate = get_posts( array( 'post_type' => 'wma_inquiry', 'post_status' => 'private', 'posts_per_page' => 1, 'fields' => 'ids', 'date_query' => array( array( 'after' => '1 hour ago' ) ), 'meta_key' => '_wma_fingerprint', 'meta_value' => $fingerprint ) );
		if ( $duplicate ) { self::go( $redirect, 'success' ); }

		$id = wp_insert_post( array( 'post_type' => 'wma_inquiry', 'post_status' => 'private', 'post_title' => $name . ' — ' . wp_date( 'Y-m-d H:i' ) ), true );
		if ( is_wp_error( $id ) ) { self::go( $redirect, 'error' ); }
		$data = array(
			'status' => 'new', 'name' => $name, 'email' => $email,
			'phone' => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ), 'country' => sanitize_text_field( wp_unslash( $_POST['country'] ?? '' ) ),
			'preferred_date' => sanitize_text_field( wp_unslash( $_POST['preferred_date'] ?? '' ) ), 'flexible_dates' => empty( $_POST['flexible_dates'] ) ? 0 : 1,
			'adults' => max( 1, min( 50, absint( $_POST['adults'] ?? 1 ) ) ), 'children' => min( 30, absint( $_POST['children'] ?? 0 ) ),
			'budget' => sanitize_text_field( wp_unslash( $_POST['budget'] ?? '' ) ), 'interests' => array_values( array_filter( array_map( 'absint', (array) ( $_POST['interests'] ?? array() ) ) ) ),
			'language' => $language, 'message' => $message, 'tour_id' => $tour_id, 'source_url' => $redirect,
			'ip_hash' => $ip_hash, 'fingerprint' => $fingerprint, 'consent' => 1,
		);
		foreach ( $data as $key => $value ) { update_post_meta( (int) $id, '_wma_' . $key, $value ); }
		self::email( (int) $id, $data );
		self::go( $redirect, 'success' );
	}

	private static function email( int $id, array $data ): void {
		$tour = $data['tour_id'] ? get_the_title( $data['tour_id'] ) : __( 'Custom trip', 'wma-core' );
		$subject = sprintf( __( 'New quotation request from %1$s — %2$s', 'wma-core' ), $data['name'], $tour );
		$body = implode( "\n", array( $subject, '', 'Email: ' . $data['email'], 'Phone: ' . $data['phone'], 'Country: ' . $data['country'], 'Date: ' . $data['preferred_date'], 'Travellers: ' . $data['adults'] . ' adults, ' . $data['children'] . ' children', 'Budget: ' . $data['budget'], '', $data['message'], '', admin_url( 'post.php?post=' . $id . '&action=edit' ) ) );
		wp_mail( wma_get_setting( 'quote_email', (string) get_option( 'admin_email' ) ), $subject, $body, array( 'Reply-To: ' . $data['name'] . ' <' . $data['email'] . '>' ) );
		$copies = array(
			'en' => array( 'We received your Wild Morocco Adventures request', "Hello %s,\n\nThank you for sharing your travel plans. Our local team has received your request and will contact you shortly.\n\nWild Morocco Adventures" ),
			'fr' => array( 'Nous avons reçu votre demande Wild Morocco Adventures', "Bonjour %s,\n\nMerci de nous avoir confié votre projet de voyage. Notre équipe locale a reçu votre demande et vous contactera prochainement.\n\nWild Morocco Adventures" ),
			'ar' => array( 'توصلنا بطلبكم لدى Wild Morocco Adventures', "مرحباً %s،\n\nشكراً لمشاركتنا خطط رحلتكم. توصل فريقنا المحلي بطلبكم وسيتواصل معكم قريباً.\n\nWild Morocco Adventures" ),
		);
		$copy = $copies[ $data['language'] ] ?? $copies['en'];
		wp_mail( $data['email'], $copy[0], sprintf( $copy[1], $data['name'] ) );
	}

	public static function boxes(): void {
		add_meta_box( 'wma_inquiry_details', __( 'Enquiry details', 'wma-core' ), array( __CLASS__, 'details' ), 'wma_inquiry', 'normal', 'high' );
		add_meta_box( 'wma_inquiry_workflow', __( 'Workflow', 'wma-core' ), array( __CLASS__, 'workflow' ), 'wma_inquiry', 'side', 'high' );
	}

	public static function details( WP_Post $post ): void {
		$fields = array( 'name' => 'Name', 'email' => 'Email', 'phone' => 'Phone / WhatsApp', 'country' => 'Country', 'preferred_date' => 'Preferred date', 'budget' => 'Budget', 'language' => 'Language', 'source_url' => 'Source page' );
		echo '<table class="widefat striped"><tbody>';
		foreach ( $fields as $key => $label ) { $value = get_post_meta( $post->ID, '_wma_' . $key, true ); printf( '<tr><th style="width:180px">%1$s</th><td>%2$s</td></tr>', esc_html( $label ), 'source_url' === $key ? '<a href="' . esc_url( (string) $value ) . '" target="_blank" rel="noopener">' . esc_html( (string) $value ) . '</a>' : esc_html( (string) $value ) ); }
		$tour_id = absint( get_post_meta( $post->ID, '_wma_tour_id', true ) );
		printf( '<tr><th>Tour</th><td>%1$s</td></tr><tr><th>Travellers</th><td>%2$s adults, %3$s children</td></tr><tr><th>Message</th><td>%4$s</td></tr>', esc_html( $tour_id ? get_the_title( $tour_id ) : 'Custom trip' ), esc_html( get_post_meta( $post->ID, '_wma_adults', true ) ), esc_html( get_post_meta( $post->ID, '_wma_children', true ) ), nl2br( esc_html( (string) get_post_meta( $post->ID, '_wma_message', true ) ) ) );
		echo '</tbody></table>';
	}

	public static function workflow( WP_Post $post ): void {
		wp_nonce_field( 'wma_save_inquiry', 'wma_inquiry_admin_nonce' );
		$status = get_post_meta( $post->ID, '_wma_status', true ) ?: 'new';
		echo '<label for="wma_status"><strong>' . esc_html__( 'Status', 'wma-core' ) . '</strong></label><select class="widefat" id="wma_status" name="wma_status">';
		foreach ( self::STATUSES as $key => $label ) { echo '<option value="' . esc_attr( $key ) . '" ' . selected( $status, $key, false ) . '>' . esc_html( $label ) . '</option>'; }
		echo '</select><p><label for="wma_notes"><strong>' . esc_html__( 'Internal notes', 'wma-core' ) . '</strong></label></p><textarea class="widefat" rows="8" id="wma_notes" name="wma_notes">' . esc_textarea( (string) get_post_meta( $post->ID, '_wma_notes', true ) ) . '</textarea>';
	}

	public static function save( int $post_id ): void {
		if ( ! isset( $_POST['wma_inquiry_admin_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wma_inquiry_admin_nonce'] ) ), 'wma_save_inquiry' ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_wma_inquiry', $post_id ) ) { return; }
		$status = sanitize_key( wp_unslash( $_POST['wma_status'] ?? 'new' ) );
		update_post_meta( $post_id, '_wma_status', isset( self::STATUSES[ $status ] ) ? $status : 'new' );
		update_post_meta( $post_id, '_wma_notes', sanitize_textarea_field( wp_unslash( $_POST['wma_notes'] ?? '' ) ) );
	}

	public static function columns(): array { return array( 'cb' => '<input type="checkbox">', 'title' => __( 'Enquiry', 'wma-core' ), 'wma_status' => __( 'Status', 'wma-core' ), 'wma_tour' => __( 'Tour', 'wma-core' ), 'wma_contact' => __( 'Contact', 'wma-core' ), 'date' => __( 'Received', 'wma-core' ) ); }
	public static function column( string $column, int $id ): void {
		if ( 'wma_status' === $column ) { $status = get_post_meta( $id, '_wma_status', true ) ?: 'new'; echo esc_html( self::STATUSES[ $status ] ?? $status ); }
		if ( 'wma_tour' === $column ) { $tour = absint( get_post_meta( $id, '_wma_tour_id', true ) ); echo esc_html( $tour ? get_the_title( $tour ) : __( 'Custom trip', 'wma-core' ) ); }
		if ( 'wma_contact' === $column ) { echo esc_html( (string) get_post_meta( $id, '_wma_email', true ) ); }
	}

	public static function filter(): void {
		global $typenow; if ( 'wma_inquiry' !== $typenow ) { return; }
		$current = sanitize_key( wp_unslash( $_GET['wma_status'] ?? '' ) ); echo '<select name="wma_status"><option value="">' . esc_html__( 'All workflow statuses', 'wma-core' ) . '</option>'; foreach ( self::STATUSES as $key => $label ) { echo '<option value="' . esc_attr( $key ) . '" ' . selected( $current, $key, false ) . '>' . esc_html( $label ) . '</option>'; } echo '</select>';
	}
	public static function filter_query( WP_Query $query ): void { if ( is_admin() && $query->is_main_query() && 'wma_inquiry' === $query->get( 'post_type' ) ) { $status = sanitize_key( wp_unslash( $_GET['wma_status'] ?? '' ) ); if ( isset( self::STATUSES[ $status ] ) ) { $query->set( 'meta_key', '_wma_status' ); $query->set( 'meta_value', $status ); } } }

	public static function export_page(): void { add_submenu_page( 'edit.php?post_type=wma_trip', __( 'Export enquiries', 'wma-core' ), __( 'Export enquiries', 'wma-core' ), 'wma_export_inquiries', 'wma-export', array( __CLASS__, 'export_screen' ) ); }
	public static function export_screen(): void { if ( ! current_user_can( 'wma_export_inquiries' ) ) { return; } echo '<div class="wrap"><h1>' . esc_html__( 'Export enquiries', 'wma-core' ) . '</h1><p>' . esc_html__( 'This file contains personal data. Store it securely.', 'wma-core' ) . '</p><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="wma_export_inquiries">'; wp_nonce_field( 'wma_export_inquiries' ); submit_button( __( 'Download CSV', 'wma-core' ) ); echo '</form></div>'; }

	public static function export(): void {
		if ( ! current_user_can( 'wma_export_inquiries' ) ) { wp_die( esc_html__( 'Not allowed.', 'wma-core' ) ); }
		check_admin_referer( 'wma_export_inquiries' ); nocache_headers(); header( 'Content-Type: text/csv; charset=utf-8' ); header( 'Content-Disposition: attachment; filename=wma-enquiries-' . gmdate( 'Y-m-d' ) . '.csv' ); $out = fopen( 'php://output', 'w' );
		if ( false === $out ) { wp_die( esc_html__( 'Export failed.', 'wma-core' ) ); }
		fputcsv( $out, array( 'Date', 'Status', 'Name', 'Email', 'Phone', 'Country', 'Tour', 'Preferred date', 'Adults', 'Children', 'Budget', 'Language', 'Message', 'Source' ) );
		foreach ( get_posts( array( 'post_type' => 'wma_inquiry', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC' ) ) as $post ) {
			$tour = absint( get_post_meta( $post->ID, '_wma_tour_id', true ) ); $row = array( get_the_date( 'c', $post ), get_post_meta( $post->ID, '_wma_status', true ), get_post_meta( $post->ID, '_wma_name', true ), get_post_meta( $post->ID, '_wma_email', true ), get_post_meta( $post->ID, '_wma_phone', true ), get_post_meta( $post->ID, '_wma_country', true ), $tour ? get_the_title( $tour ) : 'Custom trip', get_post_meta( $post->ID, '_wma_preferred_date', true ), get_post_meta( $post->ID, '_wma_adults', true ), get_post_meta( $post->ID, '_wma_children', true ), get_post_meta( $post->ID, '_wma_budget', true ), get_post_meta( $post->ID, '_wma_language', true ), get_post_meta( $post->ID, '_wma_message', true ), get_post_meta( $post->ID, '_wma_source_url', true ) ); fputcsv( $out, array_map( array( __CLASS__, 'csv_safe' ), $row ) );
		}
		fclose( $out ); exit;
	}
	private static function csv_safe( $value ): string { $value = is_scalar( $value ) ? (string) $value : ''; return preg_match( '/^[=+\-@]/', $value ) ? "'" . $value : $value; }

	public static function cleanup(): void { $days = max( 30, min( 3650, absint( wma_get_setting( 'retention_days', '365' ) ) ) ); $ids = get_posts( array( 'post_type' => 'wma_inquiry', 'post_status' => 'publish', 'posts_per_page' => 100, 'fields' => 'ids', 'date_query' => array( array( 'before' => $days . ' days ago' ) ), 'meta_key' => '_wma_status', 'meta_value' => 'closed' ) ); foreach ( $ids as $id ) { wp_delete_post( (int) $id, true ); } }
	private static function language(): string { $lang = function_exists( 'pll_current_language' ) ? pll_current_language( 'slug' ) : substr( determine_locale(), 0, 2 ); return in_array( $lang, array( 'fr', 'en', 'ar' ), true ) ? $lang : 'en'; }
	private static function current_url(): string { return home_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) ) ); }
	private static function go( string $url, string $status ): void { wp_safe_redirect( add_query_arg( 'wma_quote', $status, $url ) . '#quote-form' ); exit; }
}
