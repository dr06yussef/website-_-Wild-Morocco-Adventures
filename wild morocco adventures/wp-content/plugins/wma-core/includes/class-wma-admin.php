<?php
/**
 * Business settings, testimonial fields and removable starter content.
 */

defined( 'ABSPATH' ) || exit;

final class WMA_Admin {
	private const STARTER_CONTENT_VERSION = '1.1.0';

	private const SETTINGS = array(
		'business_name' => 'Business name', 'phone' => 'Public phone number', 'whatsapp' => 'WhatsApp number',
		'email' => 'Public email', 'quote_email' => 'Quotation notification email', 'address' => 'Business address',
		'instagram' => 'Instagram URL', 'facebook' => 'Facebook URL', 'youtube' => 'YouTube URL', 'retention_days' => 'Closed-enquiry retention (days)',
	);

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'upgrade_starter_content' ), 30 );
		add_action( 'init', array( __CLASS__, 'remove_default_sample_post' ), 31 );
		add_action( 'admin_menu', array( __CLASS__, 'menus' ) );
		add_action( 'admin_init', array( __CLASS__, 'settings' ) );
		add_action( 'add_meta_boxes_wma_testimonial', array( __CLASS__, 'testimonial_box' ) );
		add_action( 'save_post_wma_testimonial', array( __CLASS__, 'save_testimonial' ) );
		add_action( 'admin_post_wma_create_demo', array( __CLASS__, 'create_demo' ) );
		add_action( 'admin_post_wma_remove_demo', array( __CLASS__, 'remove_demo' ) );
	}

	/**
	 * Remove only WordPress's untouched English sample post.
	 */
	public static function remove_default_sample_post(): void {
		if ( get_option( 'wma_default_sample_post_checked' ) ) {
			return;
		}

		$sample = get_page_by_path( 'hello-world', OBJECT, 'post' );
		if ( $sample instanceof WP_Post && 'Hello world!' === $sample->post_title && str_contains( $sample->post_content, 'Welcome to WordPress. This is your first post.' ) ) {
			wp_delete_post( (int) $sample->ID, true );
		}

		update_option( 'wma_default_sample_post_checked', 1, false );
	}

	/**
	 * Keep previously installed starter destinations aligned with theme updates.
	 */
	public static function upgrade_starter_content(): void {
		if ( ! get_option( 'wma_demo_installed' ) || version_compare( (string) get_option( 'wma_starter_content_version', '1.0.0' ), self::STARTER_CONTENT_VERSION, '>=' ) ) {
			return;
		}

		$marrakech = get_term_by( 'slug', 'marrakech', 'wma_region' );
		$tracked   = (array) get_option( 'wma_demo_term_ids', array() );
		$tracked_ids = array();

		foreach ( $tracked as $item ) {
			if ( is_array( $item ) && 'wma_region' === ( $item['taxonomy'] ?? '' ) ) {
				$tracked_ids[] = absint( $item['term_id'] ?? 0 );
			}
		}

		if ( $marrakech instanceof WP_Term && in_array( (int) $marrakech->term_id, $tracked_ids, true ) && ! get_term_by( 'slug', 'essaouira', 'wma_region' ) ) {
			wp_update_term(
				(int) $marrakech->term_id,
				'wma_region',
				array(
					'name'        => 'Essaouira',
					'slug'        => 'essaouira',
					'description' => 'Atlantic ramparts, a working harbour, wide beaches and the relaxed rhythm of Morocco’s windswept coast.',
				)
			);
		}

		update_option( 'wma_starter_content_version', self::STARTER_CONTENT_VERSION, false );
	}

	public static function menus(): void {
		add_submenu_page( 'edit.php?post_type=wma_trip', __( 'Business settings', 'wma-core' ), __( 'Business settings', 'wma-core' ), 'manage_options', 'wma-settings', array( __CLASS__, 'settings_page' ) );
		add_management_page( __( 'Wild Morocco setup', 'wma-core' ), __( 'Wild Morocco setup', 'wma-core' ), 'manage_options', 'wma-setup', array( __CLASS__, 'setup_page' ) );
	}

	public static function settings(): void {
		foreach ( self::SETTINGS as $key => $label ) {
			$sanitize = in_array( $key, array( 'email', 'quote_email' ), true ) ? 'sanitize_email' : ( in_array( $key, array( 'instagram', 'facebook', 'youtube' ), true ) ? 'esc_url_raw' : 'sanitize_text_field' );
			if ( 'address' === $key ) { $sanitize = 'sanitize_textarea_field'; }
			if ( 'retention_days' === $key ) { $sanitize = array( __CLASS__, 'retention' ); }
			register_setting( 'wma_settings', 'wma_' . $key, array( 'sanitize_callback' => $sanitize, 'default' => 'retention_days' === $key ? 365 : '' ) );
		}
	}

	public static function retention( $value ): int { return max( 30, min( 3650, absint( $value ) ) ); }

	public static function settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		?>
		<div class="wrap"><h1><?php esc_html_e( 'Wild Morocco Adventures settings', 'wma-core' ); ?></h1><p><?php esc_html_e( 'These verified details are used throughout the theme and quotation workflow.', 'wma-core' ); ?></p><form method="post" action="options.php"><?php settings_fields( 'wma_settings' ); ?><table class="form-table" role="presentation"><tbody>
		<?php foreach ( self::SETTINGS as $key => $label ) : $value = get_option( 'wma_' . $key, 'retention_days' === $key ? 365 : '' ); ?><tr><th scope="row"><label for="wma_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th><td><?php if ( 'address' === $key ) : ?><textarea class="large-text" rows="4" id="wma_<?php echo esc_attr( $key ); ?>" name="wma_<?php echo esc_attr( $key ); ?>"><?php echo esc_textarea( (string) $value ); ?></textarea><?php else : ?><input class="regular-text" type="<?php echo esc_attr( 'retention_days' === $key ? 'number' : ( str_contains( $key, 'email' ) ? 'email' : ( in_array( $key, array( 'instagram', 'facebook', 'youtube' ), true ) ? 'url' : 'text' ) ) ); ?>" id="wma_<?php echo esc_attr( $key ); ?>" name="wma_<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( (string) $value ); ?>"><?php endif; ?></td></tr><?php endforeach; ?>
		</tbody></table><?php submit_button(); ?></form></div>
		<?php
	}

	public static function testimonial_box(): void {
		add_meta_box( 'wma_testimonial_details', __( 'Testimonial details', 'wma-core' ), array( __CLASS__, 'testimonial_fields' ), 'wma_testimonial', 'side' );
	}

	public static function testimonial_fields( WP_Post $post ): void {
		wp_nonce_field( 'wma_save_testimonial', 'wma_testimonial_nonce' );
		foreach ( array( 'location' => __( 'Traveller location', 'wma-core' ), 'travel_date' => __( 'Travel date', 'wma-core' ), 'rating' => __( 'Rating (1–5)', 'wma-core' ) ) as $key => $label ) {
			printf( '<p><label for="wma_%1$s"><strong>%2$s</strong></label><input class="widefat" id="wma_%1$s" name="wma_%1$s" value="%3$s"%4$s></p>', esc_attr( $key ), esc_html( $label ), esc_attr( (string) get_post_meta( $post->ID, '_wma_' . $key, true ) ), 'rating' === $key ? ' type="number" min="1" max="5"' : '' );
		}
	}

	public static function save_testimonial( int $post_id ): void {
		if ( ! isset( $_POST['wma_testimonial_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wma_testimonial_nonce'] ) ), 'wma_save_testimonial' ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) { return; }
		update_post_meta( $post_id, '_wma_location', sanitize_text_field( wp_unslash( $_POST['wma_location'] ?? '' ) ) );
		update_post_meta( $post_id, '_wma_travel_date', sanitize_text_field( wp_unslash( $_POST['wma_travel_date'] ?? '' ) ) );
		update_post_meta( $post_id, '_wma_rating', max( 0, min( 5, absint( $_POST['wma_rating'] ?? 0 ) ) ) );
	}

	public static function setup_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$installed = (bool) get_option( 'wma_demo_installed' );
		?>
		<div class="wrap"><h1><?php esc_html_e( 'Wild Morocco Adventures setup', 'wma-core' ); ?></h1><p><?php esc_html_e( 'Create original, removable starter pages, menus, taxonomies, FAQs, articles and sample journeys. No reference-site content is imported.', 'wma-core' ); ?></p><?php if ( isset( $_GET['wma_setup'] ) ) : ?><div class="notice notice-success"><p><?php esc_html_e( 'Setup completed.', 'wma-core' ); ?></p></div><?php endif; ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="<?php echo $installed ? 'wma_remove_demo' : 'wma_create_demo'; ?>"><?php wp_nonce_field( $installed ? 'wma_remove_demo' : 'wma_create_demo' ); ?><?php submit_button( $installed ? __( 'Remove starter content', 'wma-core' ) : __( 'Create starter content', 'wma-core' ), $installed ? 'delete' : 'primary' ); ?></form><p><strong><?php esc_html_e( 'Before launch:', 'wma-core' ); ?></strong> <?php esc_html_e( 'review the sample routes and service wording, configure verified business details, add approved testimonials and prices where appropriate, and create translations with Polylang.', 'wma-core' ); ?></p></div>
		<?php
	}

	public static function create_demo(): void {
		self::guard( 'wma_create_demo' );
		if ( get_option( 'wma_demo_installed' ) ) { self::back( 'created' ); }
		delete_option( 'wma_demo_term_ids' );
		$pages = array(
			'home' => array( 'Home', 'default', '<p>A starting point for private journeys across Morocco, shaped around each traveller’s pace, interests and preferred level of comfort.</p>' ),
			'destinations' => array( 'Destinations', 'page-templates/destinations.php', '<p>From Atlantic ramparts to High Atlas valleys and the open horizons of the south, explore the regions that can shape a personal Morocco itinerary.</p>' ),
			'travel-styles' => array( 'Travel Styles', 'page-templates/travel-styles.php', '<p>Choose a way of travelling that feels natural to you. Every sample journey can be adjusted for pace, interests, comfort and season.</p>' ),
			'about' => array( 'About', 'page-templates/about.php', '<p>Wild Morocco Adventures is built around one simple idea: begin with the traveller, then shape a coherent journey through Morocco around what matters most to them.</p><p>The planning conversation connects pace, interests, practical needs and each region’s character so the finished proposal feels considered from beginning to end.</p>' ),
			'why-travel-with-us' => array( 'Why Travel With Us', 'page-templates/why-us.php', '<p>Personal travel planning should feel clear and collaborative. The sample itineraries show what is possible; the final proposal confirms the route, stays, services and support selected for each journey.</p>' ),
			'testimonials' => array( 'Testimonials', 'page-templates/testimonials.php', '<p>Traveller stories are published only after the client has confirmed their authenticity and permission to use them.</p>' ),
			'travel-guide' => array( 'Travel Guide', 'default', '' ),
			'faq' => array( 'FAQ', 'page-templates/faq.php', '<p>Practical starting points for planning a personalised journey. The final proposal and booking conditions always take precedence.</p>' ),
			'contact' => array( 'Contact', 'page-templates/contact.php', '<p>Share your preferred dates, interests and travel rhythm. The quotation form collects the details needed to begin a personal proposal.</p>' ),
			'request-a-quote' => array( 'Request a Quote', 'page-templates/quote.php', '<p>Tell us what you imagine, and our local team will prepare a personalised proposal.</p>' ),
			'privacy-policy' => array( 'Privacy Policy', 'default', '<h2>Draft for legal review</h2><p>This page must be completed with the verified data controller, purposes, legal bases, retention periods, processors, international transfers and traveller rights before launch.</p>' ),
			'cookie-policy' => array( 'Cookie Policy', 'default', '<h2>Draft for consent configuration</h2><p>Document only the cookies and third-party services that are enabled on the production website, together with their purpose and duration.</p>' ),
			'terms-and-conditions' => array( 'Terms and Conditions', 'default', '<h2>Draft for professional review</h2><p>Add the client-approved booking, payment, amendment, cancellation, responsibility and dispute terms before accepting enquiries commercially.</p>' ),
		);
		$page_ids = array(); $created_page_ids = array();
		foreach ( $pages as $slug => $page ) {
			$existing = get_page_by_path( $slug );
			if ( $existing ) { $page_ids[ $slug ] = (int) $existing->ID; continue; }
			$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $page[0], 'post_name' => $slug, 'post_content' => $page[2] ) );
			if ( $id && ! is_wp_error( $id ) ) { $page_ids[ $slug ] = (int) $id; $created_page_ids[ $slug ] = (int) $id; update_post_meta( (int) $id, '_wma_demo', 1 ); if ( 'default' !== $page[1] ) { update_post_meta( (int) $id, '_wp_page_template', $page[1] ); } }
		}
		if ( isset( $page_ids['home'] ) ) { update_option( 'show_on_front', 'page' ); update_option( 'page_on_front', $page_ids['home'] ); }
		if ( isset( $page_ids['travel-guide'] ) ) { update_option( 'page_for_posts', $page_ids['travel-guide'] ); }

		$regions = self::terms( 'wma_region', array(
			'Sahara & South' => 'Draa Valley oases, earthen kasbahs, desert plateaux and the dune landscapes of the south-east.',
			'Atlas Mountains' => 'High passes, cultivated valleys, walking trails and villages shaped by mountain life.',
			'Essaouira' => 'Atlantic ramparts, a working harbour, wide beaches and the relaxed rhythm of Morocco’s windswept coast.',
			'Atlantic Coast' => 'Whitewashed towns, working harbours, wide beaches and a gentler coastal rhythm.',
			'Imperial Cities' => 'Historic medinas, monumental architecture and layers of Moroccan craft and culture.',
		) );
		$styles = self::terms( 'wma_style', array(
			'Private Journeys' => 'Flexible routes shaped around one party, with the exact services confirmed in a personal proposal.',
			'Desert Adventures' => 'Journeys that make time for southern valleys, kasbahs, desert settlements and dune landscapes.',
			'Trekking' => 'Walking-led itineraries adapted to the group, season and preferred level of challenge.',
			'Culture & Heritage' => 'Medinas, architecture, craftsmanship, food and encounters that give context to each place.',
			'Family Travel' => 'Thoughtful pacing, varied activities and practical routing for adults and younger travellers.',
		) );
		$interests = self::terms( 'wma_interest', array(
			'Nature' => 'Landscapes, wildlife and open-air experiences.', 'Culture' => 'History, architecture and everyday traditions.',
			'Food' => 'Markets, local cooking and regional flavours.', 'Photography' => 'Time and light for considered image-making.',
			'Walking' => 'Gentle walks, day hikes and longer trails.', 'Desert' => 'Oases, plateaux, dune landscapes and desert settlements.',
		) );
		$media = self::demo_media();
		$tours = array(
			array(
				'title' => 'Sahara, Kasbahs & the Draa Valley', 'slug' => 'sahara-kasbahs-draa-valley', 'image' => 'desert',
				'subtitle' => 'An eight-day private journey across the High Atlas to the valleys, kasbahs and desert horizons of southern Morocco.',
				'days' => 8, 'nights' => 7, 'start' => 'Marrakech', 'end' => 'Marrakech', 'region' => 'Sahara & South', 'style' => 'Desert Adventures', 'interests' => array( 'Nature', 'Culture', 'Photography', 'Desert' ),
				'difficulty' => 'Easy to moderate', 'group' => 'Private journey',
				'excerpt' => 'Cross the High Atlas, follow palm-filled valleys and slow down beside the dunes on a flexible private route.',
				'overview' => '<p>This sample route links Marrakech with the varied landscapes of Morocco’s south: mountain passes, earthen villages, cultivated valleys and the edge of the Sahara. It is designed as a private journey, with time to pause rather than simply move from one landmark to the next.</p><p>Every service, driving arrangement, accommodation and activity remains subject to the final personalised proposal.</p>',
				'highlights' => array( 'Cross the High Atlas on a landscape-led route', 'Explore kasbah country and the Dades and Draa valleys', 'Allow a full day for the atmosphere and changing light of the dunes', 'Return to Marrakech by a different southern route' ),
				'itinerary' => array(
					self::day( 1, 'Arrive in Marrakech', 'Marrakech', 'Arrival and time to settle into the city. Depending on timing, begin with a gentle orientation walk or a quiet evening at the riad.', 'Marrakech' ),
					self::day( 2, 'Across the High Atlas', 'Marrakech to Ouarzazate', 'Travel over the mountains with time for viewpoints and a stop in kasbah country. Continue to Ouarzazate at an unhurried pace.', 'Ouarzazate' ),
					self::day( 3, 'Oases and the Dades Valley', 'Ouarzazate to Dades', 'Follow the valley east through palm groves and earthen villages, with the afternoon reserved for the landscapes around the Dades.', 'Dades Valley' ),
					self::day( 4, 'Todra and the desert road', 'Dades to Merzouga', 'Continue through the south-east, pausing near the Todra landscapes before the road opens toward the desert settlements around Merzouga.', 'Merzouga area' ),
					self::day( 5, 'A slower day by the dunes', 'Erg Chebbi', 'Keep this day flexible for a walk, a village visit, quiet time at the lodge or an optional desert activity confirmed in the proposal.', 'Merzouga area' ),
					self::day( 6, 'From the Tafilalet to the Draa', 'Merzouga to Agdz', 'Turn west across open country and reconnect with the long ribbon of palms and settlements that defines the Draa Valley.', 'Agdz' ),
					self::day( 7, 'The southern road back', 'Agdz to Marrakech', 'Return toward Marrakech through the changing geology of the south, with stops chosen around time, interests and road conditions.', 'Marrakech' ),
					self::day( 8, 'Departure or extend your stay', 'Marrakech', 'A final breakfast and onward arrangements. The journey can also be extended with additional time in Marrakech, the Atlas or on the coast.', '' ),
				),
			),
			array(
				'title' => 'High Atlas Villages & Valleys', 'slug' => 'high-atlas-villages-valleys', 'image' => 'atlas',
				'subtitle' => 'A six-day mountain journey combining scenic roads, adaptable walks and time in the valleys beyond Marrakech.',
				'days' => 6, 'nights' => 5, 'start' => 'Marrakech', 'end' => 'Marrakech', 'region' => 'Atlas Mountains', 'style' => 'Trekking', 'interests' => array( 'Nature', 'Culture', 'Walking' ),
				'difficulty' => 'Adaptable walking', 'group' => 'Private journey',
				'excerpt' => 'Trade city pace for mountain air, valley paths and village landscapes on a flexible High Atlas escape.',
				'overview' => '<p>This sample journey creates space for the High Atlas rather than treating the mountains as a day trip. Walks can be kept gentle or made more active, while the route and stays are adjusted to the season and the group.</p><p>Exact trail conditions, guide services and accommodation are confirmed in the final proposal.</p>',
				'highlights' => array( 'Spend several nights within the mountain landscape', 'Adapt walking time to the group and season', 'Balance guided exploration with quiet time at the stay', 'Finish with the colour and energy of Marrakech' ),
				'itinerary' => array(
					self::day( 1, 'Marrakech welcome', 'Marrakech', 'Arrive, settle in and discuss the rhythm of the mountain days ahead.', 'Marrakech' ),
					self::day( 2, 'Into the High Atlas', 'Marrakech to the mountain valleys', 'Leave the city behind and travel into the valleys, stopping for viewpoints and a first short walk if timing allows.', 'High Atlas' ),
					self::day( 3, 'Village paths and cultivated terraces', 'High Atlas', 'A guided walking day tailored to ability, weather and local conditions, with time to understand how the valley landscape is used.', 'High Atlas' ),
					self::day( 4, 'Choose your mountain rhythm', 'High Atlas', 'Select a longer walk, a gentler valley route or a quieter day around the accommodation.', 'High Atlas' ),
					self::day( 5, 'Return to Marrakech', 'High Atlas to Marrakech', 'Descend from the mountains and return to the city for a contrasting final evening.', 'Marrakech' ),
					self::day( 6, 'Departure or continue', 'Marrakech', 'Continue home or add another Morocco chapter to the journey.', '' ),
				),
			),
			array(
				'title' => 'Marrakech to the Atlantic', 'slug' => 'marrakech-to-the-atlantic', 'image' => 'coast',
				'subtitle' => 'Five days of medina energy, Atlantic light and a relaxed coastal finish in Essaouira.',
				'days' => 5, 'nights' => 4, 'start' => 'Marrakech', 'end' => 'Essaouira', 'region' => 'Atlantic Coast', 'style' => 'Culture & Heritage', 'interests' => array( 'Culture', 'Food', 'Photography' ),
				'difficulty' => 'Easy', 'group' => 'Private journey',
				'excerpt' => 'Pair Marrakech’s colour and craftsmanship with the sea air and slower rhythm of Essaouira.',
				'overview' => '<p>This compact sample route combines two distinct Moroccan atmospheres without rushing between them. Begin amid Marrakech’s gardens, architecture and workshops, then travel west for whitewashed walls, harbour life and Atlantic sunsets.</p><p>The balance of guided visits, independent time and transfers is set in the personalised proposal.</p>',
				'highlights' => array( 'Experience two contrasting city rhythms', 'Make space for craft, food and architecture', 'Enjoy a relaxed Atlantic finish', 'Add extra nights or continue along the coast' ),
				'itinerary' => array(
					self::day( 1, 'Arrive in Marrakech', 'Marrakech', 'Settle into the city and begin at a pace that suits the arrival time.', 'Marrakech' ),
					self::day( 2, 'Marrakech in context', 'Marrakech', 'Use a guided or self-led day to connect architecture, gardens, craftsmanship and food rather than racing through a checklist.', 'Marrakech' ),
					self::day( 3, 'West to the Atlantic', 'Marrakech to Essaouira', 'Travel toward the coast and arrive with time for a first walk along the ramparts or harbour.', 'Essaouira' ),
					self::day( 4, 'Essaouira at your pace', 'Essaouira', 'Keep the day open for the medina, beach, food, photography or a nearby experience selected in advance.', 'Essaouira' ),
					self::day( 5, 'Departure or coastal extension', 'Essaouira', 'Continue home, return to Marrakech or extend the journey farther along the Atlantic.', '' ),
				),
			),
		);
		foreach ( $tours as $tour ) {
			$id = wp_insert_post( array( 'post_type' => 'wma_trip', 'post_status' => 'publish', 'post_title' => $tour['title'], 'post_name' => $tour['slug'], 'post_excerpt' => $tour['excerpt'], 'post_content' => $tour['overview'] ) );
			if ( ! $id || is_wp_error( $id ) ) { continue; }
			foreach ( array( 'demo' => 1, 'featured' => 1, 'subtitle' => $tour['subtitle'], 'days' => $tour['days'], 'nights' => $tour['nights'], 'start_location' => $tour['start'], 'end_location' => $tour['end'], 'difficulty' => $tour['difficulty'], 'group_size' => $tour['group'] ) as $key => $value ) { update_post_meta( (int) $id, '_wma_' . $key, $value ); }
			update_post_meta( (int) $id, '_wma_highlights', $tour['highlights'] );
			update_post_meta( (int) $id, '_wma_itinerary', $tour['itinerary'] );
			update_post_meta( (int) $id, '_wma_accommodation', '<p>Suggested stays may combine riad-style hotels, small guesthouses and locally appropriate lodges. Exact properties, room categories, meal plans and accessibility details are confirmed in the final quotation and remain subject to availability.</p>' );
			update_post_meta( (int) $id, '_wma_included', array( 'Personal itinerary design and pre-departure information', 'Accommodation and meals specifically listed in the final proposal', 'Private transport, vehicle hire or guiding only where confirmed in the final proposal', 'Local assistance arrangements described in the final travel documents' ) );
			update_post_meta( (int) $id, '_wma_excluded', array( 'International flights unless explicitly stated', 'Travel insurance', 'Personal expenses, tips and services not listed as included', 'Meals, activities and transport not confirmed in the final proposal' ) );
			update_post_meta( (int) $id, '_wma_advice', '<p>Driving times, weather, road conditions and access can change by season. The final route should be reviewed close to departure, and travellers should allow flexibility for local conditions.</p><p>Health, entry and insurance requirements must be checked against current official guidance for each traveller’s nationality and circumstances.</p>' );
			update_post_meta( (int) $id, '_wma_what_to_bring', array( 'Comfortable walking shoes', 'Layers for changing temperatures', 'Sun protection and a refillable water bottle', 'Personal medication and travel documents', 'A small day bag for walks and transfers' ) );
			update_post_meta( (int) $id, '_wma_faqs', array(
				array( 'question' => 'Can this sample journey be changed?', 'answer' => 'Yes. The route, pace, stays and services are confirmed only after the traveller approves a personalised proposal.' ),
				array( 'question' => 'Is the starting price shown?', 'answer' => 'No unverified price is published. The quotation depends on dates, traveller numbers, availability and the exact services selected.' ),
				array( 'question' => 'Are flights included?', 'answer' => 'Flights are not assumed to be included. The final proposal will state clearly what is and is not part of the arrangement.' ),
			) );
			if ( isset( $media[ $tour['image'] ] ) ) { set_post_thumbnail( (int) $id, $media[ $tour['image'] ] ); }
			$gallery = array_values( array_filter( array( $media[ $tour['image'] ] ?? 0, $media['marrakech'] ?? 0, $media['atlas'] ?? 0, $media['coast'] ?? 0, $media['desert'] ?? 0 ) ) ); update_post_meta( (int) $id, '_wma_gallery_ids', implode( ',', array_values( array_unique( $gallery ) ) ) );
			if ( isset( $regions[ $tour['region'] ] ) ) { wp_set_object_terms( (int) $id, array( $regions[ $tour['region'] ] ), 'wma_region' ); }
			if ( isset( $styles[ $tour['style'] ] ) ) { wp_set_object_terms( (int) $id, array( $styles[ $tour['style'] ] ), 'wma_style' ); }
			$interest_ids = array(); foreach ( $tour['interests'] as $interest ) { if ( isset( $interests[ $interest ] ) ) { $interest_ids[] = $interests[ $interest ]; } } if ( $interest_ids ) { wp_set_object_terms( (int) $id, $interest_ids, 'wma_interest' ); }
		}
		$faqs = array(
			array( 'Can every itinerary be personalised?', 'Yes. Published journeys are inspiration. The final route, dates, stays, transport and activities are confirmed in a personal proposal.' ),
			array( 'How far in advance should I enquire?', 'Earlier enquiries generally allow more choice, especially around popular travel periods. Availability is checked only when the proposal is prepared.' ),
			array( 'Do you publish fixed prices?', 'No unverified rate is used in the starter content. A final quotation reflects the selected dates, traveller numbers, service level and current availability.' ),
			array( 'Can you plan for families or private groups?', 'The enquiry form records adults, children and interests so the proposed pace and services can be adapted to the party.' ),
			array( 'What happens after I request a quote?', 'The request is reviewed, any missing details are clarified, and a proposal can then be refined before the traveller decides whether to proceed.' ),
			array( 'Is travel insurance included?', 'Travel insurance is not assumed to be included. Travellers should arrange appropriate cover and review the final booking conditions.' ),
			array( 'How are dietary or accessibility needs handled?', 'Add all relevant needs to the enquiry. They must be checked against the exact route and suppliers before any arrangement is confirmed.' ),
			array( 'Which language should I use?', 'The website supports English, French and Arabic. Use whichever language is most comfortable when submitting the enquiry.' ),
		);
		foreach ( $faqs as $index => $faq ) { $id = wp_insert_post( array( 'post_type' => 'wma_faq', 'post_status' => 'publish', 'post_title' => $faq[0], 'post_content' => $faq[1], 'menu_order' => $index ) ); if ( $id && ! is_wp_error( $id ) ) { update_post_meta( (int) $id, '_wma_demo', 1 ); } }
		$posts = array(
			array( 'Choosing the right season and rhythm for Morocco', '<p>Morocco changes considerably between coast, mountains, cities and the south. Instead of choosing dates from a single nationwide rule, begin with the landscapes and activities that matter most, then check likely temperatures, daylight and local conditions for that route. A slower itinerary can also make changing weather easier to accommodate.</p><h2>Plan by region, not by headline</h2><p>Conditions in Essaouira, Marrakech, the High Atlas and the desert edge can feel very different on the same day. The final planning conversation should consider every overnight stop.</p><h2>Leave room in the schedule</h2><p>Long transfer days can reduce the time available for walks, meals and spontaneous stops. A balanced route protects the experiences that made you choose Morocco in the first place.</p>', 'atlas' ),
			array( 'What a personalised Morocco proposal should make clear', '<p>A useful travel proposal does more than list place names. It should explain the rhythm of the route, the type of stay, the services included and the decisions still open to the traveller.</p><h2>Look for clarity</h2><p>Dates, traveller numbers, accommodation category, transport, meals, guiding and support should be easy to identify. Anything not included should be equally visible.</p><h2>Refine before confirming</h2><p>A sample itinerary is a conversation starter. Ask what can change and which elements depend on availability before treating it as final.</p>', 'marrakech' ),
			array( 'Packing for city, mountains, desert and coast', '<p>A varied Morocco journey often crosses several climates and styles of travel. Light layers, comfortable footwear and sun protection are more useful than packing for only one landscape.</p><h2>Keep one flexible day bag</h2><p>Carry water, sun protection, personal medication and a warm layer during transfers and walks.</p><h2>Check the final route</h2><p>Altitude, season and planned activities affect what you need. Use the confirmed itinerary and current official guidance as the final packing reference.</p>', 'coast' ),
		);
		foreach ( $posts as $post ) { $id = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => $post[0], 'post_content' => $post[1] ) ); if ( $id && ! is_wp_error( $id ) ) { update_post_meta( (int) $id, '_wma_demo', 1 ); if ( isset( $media[ $post[2] ] ) ) { set_post_thumbnail( (int) $id, $media[ $post[2] ] ); } } }
		self::menu( $page_ids ); update_option( 'wma_demo_installed', 1 ); update_option( 'wma_demo_page_ids', $created_page_ids ); update_option( 'wma_starter_content_version', self::STARTER_CONTENT_VERSION, false ); flush_rewrite_rules(); self::back( 'created' );
	}

	public static function remove_demo(): void {
		self::guard( 'wma_remove_demo' );
		$page_ids = (array) get_option( 'wma_demo_page_ids', array() );
		if ( in_array( (int) get_option( 'page_on_front' ), array_map( 'intval', $page_ids ), true ) ) { update_option( 'page_on_front', 0 ); update_option( 'show_on_front', 'posts' ); }
		if ( in_array( (int) get_option( 'page_for_posts' ), array_map( 'intval', $page_ids ), true ) ) { update_option( 'page_for_posts', 0 ); }
		$ids = get_posts( array( 'post_type' => array( 'page', 'wma_trip', 'wma_faq', 'wma_testimonial', 'post' ), 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_wma_demo', 'meta_value' => 1 ) );
		foreach ( $ids as $id ) { wp_delete_post( (int) $id, true ); }
		$attachment_ids = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_wma_demo', 'meta_value' => 1 ) );
		foreach ( $attachment_ids as $attachment_id ) { wp_delete_attachment( (int) $attachment_id, true ); }
		foreach ( (array) get_option( 'wma_demo_term_ids', array() ) as $item ) { if ( ! is_array( $item ) || empty( $item['taxonomy'] ) || empty( $item['term_id'] ) ) { continue; } $objects = get_objects_in_term( (int) $item['term_id'], sanitize_key( $item['taxonomy'] ) ); if ( ! is_wp_error( $objects ) && ! $objects ) { wp_delete_term( (int) $item['term_id'], sanitize_key( $item['taxonomy'] ) ); } }
		$menu_ids = array_filter( array( absint( get_option( 'wma_demo_menu_id' ) ), absint( get_option( 'wma_demo_legal_menu_id' ) ) ) );
		if ( $menu_ids ) { $locations = get_theme_mod( 'nav_menu_locations', array() ); foreach ( $locations as $location => $assigned ) { if ( in_array( (int) $assigned, $menu_ids, true ) ) { unset( $locations[ $location ] ); } } set_theme_mod( 'nav_menu_locations', $locations ); foreach ( $menu_ids as $menu_id ) { wp_delete_nav_menu( $menu_id ); } }
		delete_option( 'wma_demo_installed' ); delete_option( 'wma_demo_page_ids' ); delete_option( 'wma_demo_term_ids' ); delete_option( 'wma_demo_menu_id' ); delete_option( 'wma_demo_legal_menu_id' ); delete_option( 'wma_starter_content_version' ); flush_rewrite_rules(); self::back( 'removed' );
	}

	private static function terms( string $taxonomy, array $terms ): array {
		$ids = array();
		foreach ( $terms as $key => $value ) {
			$name        = is_int( $key ) ? (string) $value : (string) $key;
			$description = is_int( $key ) ? '' : (string) $value;
			$existing    = term_exists( $name, $taxonomy );
			$created     = false;
			$term        = $existing;
			if ( ! $existing ) {
				$term    = wp_insert_term( $name, $taxonomy, array( 'description' => $description ) );
				$created = true;
			}
			if ( is_wp_error( $term ) ) { continue; }
			$term_id      = (int) ( is_array( $term ) ? $term['term_id'] : $term );
			$ids[ $name ] = $term_id;
			if ( $created ) {
				$tracked   = (array) get_option( 'wma_demo_term_ids', array() );
				$tracked[] = array( 'taxonomy' => $taxonomy, 'term_id' => $term_id );
				update_option( 'wma_demo_term_ids', $tracked, false );
			}
		}
		return $ids;
	}

	private static function day( int $day, string $title, string $location, string $description, string $overnight ): array {
		return compact( 'day', 'title', 'location', 'description', 'overnight' );
	}
	private static function demo_media(): array {
		$media  = array();
		$images = array(
			'desert'    => array( 'desert.webp', 'Sahara landscape', 'Morocco desert landscape at warm light' ),
			'atlas'     => array( 'atlas.webp', 'High Atlas landscape', 'High Atlas mountain landscape in Morocco' ),
			'coast'     => array( 'coast.webp', 'Atlantic coast', 'Atlantic coast and historic walls in Morocco' ),
			'marrakech' => array( 'marrakech.webp', 'Marrakech architecture', 'Warm architectural details in Marrakech' ),
		);

		foreach ( $images as $key => $image ) {
			$existing = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_wma_demo_source', 'meta_value' => $image[0] ) );
			if ( $existing ) { $media[ $key ] = (int) $existing[0]; continue; }

			$source = trailingslashit( get_template_directory() ) . 'assets/images/' . $image[0];
			if ( ! is_readable( $source ) ) { continue; }
			$contents = file_get_contents( $source );
			if ( false === $contents ) { continue; }
			$upload = wp_upload_bits( 'wma-' . $image[0], null, $contents );
			if ( ! empty( $upload['error'] ) ) { continue; }

			$filetype = wp_check_filetype( basename( $upload['file'] ), null );
			$id       = wp_insert_attachment(
				array(
					'post_mime_type' => $filetype['type'] ?: 'image/webp',
					'post_title'     => $image[1],
					'post_status'    => 'inherit',
				),
				$upload['file']
			);
			if ( ! $id || is_wp_error( $id ) ) { continue; }
			require_once ABSPATH . 'wp-admin/includes/image.php';
			wp_update_attachment_metadata( (int) $id, wp_generate_attachment_metadata( (int) $id, $upload['file'] ) );
			update_post_meta( (int) $id, '_wp_attachment_image_alt', $image[2] );
			update_post_meta( (int) $id, '_wma_demo', 1 );
			update_post_meta( (int) $id, '_wma_demo_source', $image[0] );
			$media[ $key ] = (int) $id;
		}
		return $media;
	}

	private static function menu( array $pages ): void {
		$menu    = wp_get_nav_menu_object( 'Wild Morocco Primary' );
		$created = false;
		$id      = $menu ? (int) $menu->term_id : wp_create_nav_menu( 'Wild Morocco Primary' );
		if ( is_wp_error( $id ) ) { return; }
		if ( ! $menu ) { $created = true; update_option( 'wma_demo_menu_id', (int) $id, false ); }
		if ( ! wp_get_nav_menu_items( $id ) ) {
			wp_update_nav_menu_item( $id, 0, array( 'menu-item-title' => 'Tours', 'menu-item-url' => get_post_type_archive_link( 'wma_trip' ), 'menu-item-type' => 'custom', 'menu-item-status' => 'publish' ) );
			foreach ( array( 'destinations', 'travel-styles', 'about', 'travel-guide', 'contact' ) as $slug ) { if ( isset( $pages[ $slug ] ) ) { wp_update_nav_menu_item( $id, 0, array( 'menu-item-object-id' => $pages[ $slug ], 'menu-item-object' => 'page', 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) ); } }
		}

		$legal         = wp_get_nav_menu_object( 'Wild Morocco Legal' );
		$legal_created = false;
		$legal_id      = $legal ? (int) $legal->term_id : wp_create_nav_menu( 'Wild Morocco Legal' );
		if ( ! is_wp_error( $legal_id ) ) {
			if ( ! $legal ) { $legal_created = true; update_option( 'wma_demo_legal_menu_id', (int) $legal_id, false ); }
			if ( ! wp_get_nav_menu_items( $legal_id ) ) {
				foreach ( array( 'privacy-policy', 'cookie-policy', 'terms-and-conditions' ) as $slug ) { if ( isset( $pages[ $slug ] ) ) { wp_update_nav_menu_item( $legal_id, 0, array( 'menu-item-object-id' => $pages[ $slug ], 'menu-item-object' => 'page', 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) ); } }
			}
		}

		$locations = get_theme_mod( 'nav_menu_locations', array() );
		if ( $created || empty( $locations['primary'] ) ) { $locations['primary'] = (int) $id; }
		if ( $created || empty( $locations['footer'] ) ) { $locations['footer'] = (int) $id; }
		if ( ! is_wp_error( $legal_id ) && ( $legal_created || empty( $locations['legal'] ) ) ) { $locations['legal'] = (int) $legal_id; }
		set_theme_mod( 'nav_menu_locations', $locations );
	}
	private static function guard( string $action ): void { if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Not allowed.', 'wma-core' ) ); } check_admin_referer( $action ); }
	private static function back( string $status ): void { wp_safe_redirect( add_query_arg( array( 'page' => 'wma-setup', 'wma_setup' => $status ), admin_url( 'tools.php' ) ) ); exit; }
}
