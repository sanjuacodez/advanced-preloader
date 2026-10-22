<?php
/**
 * "Plugins by Sanjay Shankar" newsletter card.
 *
 * A sign-up card shown on the dashboards of plugins by Sanjay Shankar, to
 * administrators, from the first install. Closing it hides it for 30 days;
 * it stops once the site has subscribed. A permanent box with the same form
 * sits on each plugin's settings screen. Nothing is sent anywhere until the administrator
 * types an email address and clicks Subscribe; the address then goes to the
 * Brevo sign-up form for the "Plugins by Sanjay Shankar" newsletter,
 * together with the first name (optional) and the name of the plugin the
 * sign-up came from.
 *
 * One decision per site covers every plugin: subscribing hides the card in
 * all of them, and closing it is remembered per user for 30 days.
 *
 * @package SanjayShankar_Connect
 */

defined( 'ABSPATH' ) || exit;

/**
 * The shared newsletter card.
 */
class SanjayShankar_Connect {

	const VERSION = '1.0.0';

	/**
	 * Site-wide state shared by all plugins: { subscribed }.
	 */
	const OPTION = 'sanjayshankar_connect';

	/**
	 * Per-user timestamp until which the card stays hidden.
	 */
	const USER_META = 'sanjayshankar_connect_hidden_until';

	/**
	 * Brevo sign-up form for the "Plugins by Sanjay Shankar" newsletter.
	 */
	const FORM_URL = 'https://549e8c65.sibforms.com/serve/MUIFALoEB6zuzttaYpI0yN2EHk8RxT936AFqKkOw3jV11ekmrhwgYjDue8H6jqJXPqAQtYUyd--X0ZyXfkjzuaJb2LUIRW2Gd1PTXlpLNCH31CdBJE6XWlZRhIQpS8HAjzhJWaw31jmkTGuWA6k2wCZB05afCDc-y5ewNtGrLgXTlWUB2GxvXJLpJYvNbNv79h2LvZ2-kqDzlMcP';

	/**
	 * Brevo contact attribute that records which plugin the sign-up came from.
	 * It must exist in Brevo (Contacts > Settings > Contact attributes, type Text)
	 * and be added to the form, for example as a hidden field.
	 */
	const SOURCE_FIELD = 'PLUGIN';

	const PRIVACY_URL = 'https://sanjayshankar.me/privacy/';

	/**
	 * Google Apps Script web app that writes deactivation feedback to the
	 * author's Google Sheet, one tab per plugin (see bin/feedback-receiver.gs).
	 * While empty, the deactivation survey is not shown.
	 */
	const FEEDBACK_URL = 'https://script.google.com/macros/s/AKfycbzzkhPcTof2yL8b4pSPnwmps3U7zJUvCRdzAj6ZErjpv_lwo1_cfRQsxM7mQQ6Igjikng/exec';

	/**
	 * Matches TOKEN in the receiver script. Filters stray requests; not a secret.
	 */
	const FEEDBACK_TOKEN = 'ssc-feedback-1';

	/**
	 * Days the card stays hidden after it is closed ("×" or "Not now").
	 */
	const SNOOZE_DAYS = 30;

	/**
	 * Folder of the copy that is running.
	 *
	 * @var string
	 */
	private static $dir = '';

	/**
	 * Whether the card was printed on this screen.
	 *
	 * @var bool
	 */
	private static $printed = false;

	/**
	 * Hooks.
	 *
	 * @param string $dir Folder of this copy.
	 */
	public static function init( $dir ) {
		self::$dir = $dir;
		add_action( 'sanjayshankar_connect_card', array( __CLASS__, 'card' ) );
		add_action( 'sanjayshankar_connect_box', array( __CLASS__, 'box' ) );
		add_action( 'wp_ajax_sanjayshankar_connect', array( __CLASS__, 'ajax' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'plugins_screen' ) );
	}

	/**
	 * Site-wide state.
	 *
	 * @return array
	 */
	private static function state() {
		$state = get_option( self::OPTION, array() );
		return wp_parse_args(
			is_array( $state ) ? $state : array(),
			array(
				'subscribed' => 0,
			)
		);
	}

	/**
	 * Whether the card should show to the current user.
	 *
	 * @return bool
	 */
	public static function should_show() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		$state = self::state();
		if ( $state['subscribed'] ) {
			return false;
		}
		$hidden_until = (int) get_user_meta( get_current_user_id(), self::USER_META, true );

		/**
		 * Filters whether the "Plugins by Sanjay Shankar" card shows.
		 *
		 * @param bool $show Show the card.
		 */
		return (bool) apply_filters( 'sanjayshankar_connect_show', $hidden_until < time() );
	}

	/**
	 * Names of the plugins by Sanjay Shankar active on this site.
	 *
	 * @return string[]
	 */
	private static function plugin_names() {
		return array_values( array_filter( wp_list_pluck( self::plugins(), 'name' ) ) );
	}

	/**
	 * Plugins by Sanjay Shankar active on this site.
	 *
	 * @return array Slug => { name, file, version }.
	 */
	private static function plugins() {
		/**
		 * Plugins by Sanjay Shankar register themselves here:
		 * slug => name, or slug => array( 'name' => …, 'file' => plugin_basename( __FILE__ ), 'version' => … ).
		 * The file is needed for the deactivation survey.
		 *
		 * @param array $plugins Plugins.
		 */
		$out = array();
		foreach ( (array) apply_filters( 'sanjayshankar_connect_plugins', array() ) as $slug => $plugin ) {
			$plugin             = is_array( $plugin ) ? $plugin : array( 'name' => $plugin );
			$out[ (string) $slug ] = array(
				'name'    => isset( $plugin['name'] ) ? (string) $plugin['name'] : (string) $slug,
				'file'    => isset( $plugin['file'] ) ? (string) $plugin['file'] : '',
				'version' => isset( $plugin['version'] ) ? (string) $plugin['version'] : '',
			);
		}
		return $out;
	}

	/**
	 * Prints the dismissible card (plugin dashboards), once per screen.
	 *
	 * @param string $source Slug of the plugin whose screen this is.
	 */
	public static function card( $source = '' ) {
		if ( self::$printed || ! self::should_show() ) {
			return;
		}
		self::$printed = true;
		self::render( $source, 'card' );
	}

	/**
	 * Prints the permanent sign-up box (plugin settings screens), so admins who
	 * closed the card can still subscribe. Always shown to administrators.
	 *
	 * @param string $source Slug of the plugin whose screen this is.
	 */
	public static function box( $source = '' ) {
		// On a screen that already shows the card (settings-only plugins), one form is enough.
		if ( ! self::$printed && current_user_can( 'manage_options' ) ) {
			self::render( $source, 'box' );
		}
	}

	/**
	 * Loads the card's stylesheet and script.
	 */
	private static function enqueue() {
		$main = self::$dir . '/connect.php';
		wp_enqueue_style( 'sanjayshankar-connect', plugins_url( 'connect.css', $main ), array(), self::VERSION );
		wp_enqueue_script( 'sanjayshankar-connect', plugins_url( 'connect.js', $main ), array(), self::VERSION, true );
		wp_localize_script(
			'sanjayshankar-connect',
			'sanjayshankarConnect',
			array(
				'ajax'  => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'sanjayshankar_connect' ),
				'i18n'  => array(
					'sending' => __( 'Subscribing…', 'advanced-preloader' ),
					'invalid' => __( 'Please enter a valid email address.', 'advanced-preloader' ),
					'error'   => __( 'Something went wrong. Please try again later.', 'advanced-preloader' ),
				),
			)
		);
	}

	/**
	 * The card or box markup.
	 *
	 * @param string $source Slug of the plugin whose screen this is.
	 * @param string $mode   'card' (dismissible) or 'box' (permanent).
	 */
	private static function render( $source, $mode ) {
		static $count = 0;
		++$count;
		$id    = 'sanjayshankar-connect-' . $count;
		$card  = 'card' === $mode;
		$state = self::state();

		self::enqueue();

		// A different pastel panel on each page load, from the sanjayshankar.me palette.
		$themes = array( 'green', 'blue', 'purple', 'yellow', 'orange' );
		$theme  = $themes[ wp_rand( 0, count( $themes ) - 1 ) ];

		// Name the plugin whose screen this is; count the others.
		$all     = self::plugins();
		$current = isset( $all[ $source ] ) ? $all[ $source ]['name'] : '';
		$others  = array_values( array_diff( self::plugin_names(), array( $current ) ) );
		if ( '' === $current ) {
			$current = array_shift( $others );
		}
		if ( ! $current ) {
			$names = '';
		} elseif ( ! $others ) {
			$names = $current;
		} elseif ( 1 === count( $others ) ) {
			/* translators: 1: this plugin's name, 2: another plugin's name */
			$names = sprintf( __( '%1$s and %2$s', 'advanced-preloader' ), $current, $others[0] );
		} else {
			/* translators: 1: this plugin's name, 2: number of the author's other plugins */
			$names = sprintf( _n( '%1$s and %2$d of my other plugins', '%1$s and %2$d of my other plugins', count( $others ), 'advanced-preloader' ), $current, count( $others ) );
		}
		// Pre-filled from the admin's own profile so they don't retype it. Nothing is
		// sent until they click Subscribe, and they can change both fields first.
		$user  = wp_get_current_user();
		$first = $user->first_name ? $user->first_name : '';
		$email = is_email( $user->user_email ) ? $user->user_email : (string) get_option( 'admin_email' );
		?>
		<section class="sanjayshankar-connect sanjayshankar-connect--<?php echo esc_attr( $mode ); ?> is-theme-<?php echo esc_attr( $theme ); ?>" data-source="<?php echo esc_attr( sanitize_key( $source ) ); ?>" aria-labelledby="<?php echo esc_attr( $id ); ?>-title">
			<?php if ( $card ) : ?>
				<button type="button" class="sanjayshankar-connect__close" data-connect-action="snooze" aria-label="<?php esc_attr_e( 'Hide for 30 days', 'advanced-preloader' ); ?>">
					<span aria-hidden="true">&times;</span>
				</button>
			<?php endif; ?>
			<div class="sanjayshankar-connect__inner">
			<div class="sanjayshankar-connect__intro">
				<div class="sanjayshankar-connect__byline">
					<img class="sanjayshankar-connect__avatar" src="<?php echo esc_url( plugins_url( 'avatar.jpg', self::$dir . '/connect.php' ) ); ?>" width="44" height="44" alt="" loading="lazy">
					<span>
						<strong class="sanjayshankar-connect__name"><?php esc_html_e( 'Hi, I’m Sanjay', 'advanced-preloader' ); ?></strong>
						<span class="sanjayshankar-connect__eyebrow"><?php esc_html_e( 'Plugins by Sanjay Shankar', 'advanced-preloader' ); ?></span>
					</span>
				</div>
				<h2 id="<?php echo esc_attr( $id ); ?>-title"><?php echo esc_html( $card ? __( 'Be the first to try my new plugins', 'advanced-preloader' ) : __( 'Stay in the loop', 'advanced-preloader' ) ); ?></h2>
				<p class="sanjayshankar-connect__lead">
					<?php
					if ( $names ) {
						/* translators: %s: names of the author's plugins installed on this site */
						echo esc_html( sprintf( __( 'Thanks for using %s! I build free WordPress plugins, and subscribers hear about the new ones first.', 'advanced-preloader' ), $names ) );
					} else {
						esc_html_e( 'I build free WordPress plugins, and subscribers hear about the new ones first.', 'advanced-preloader' );
					}
					?>
				</p>
				<ul class="sanjayshankar-connect__perks">
					<li class="is-yellow"><?php esc_html_e( 'Early access to new plugins', 'advanced-preloader' ); ?></li>
					<li class="is-blue"><?php esc_html_e( 'Beta invites', 'advanced-preloader' ); ?></li>
					<li class="is-green"><?php esc_html_e( 'Release notes and tips', 'advanced-preloader' ); ?></li>
					<li class="is-purple"><?php esc_html_e( 'News from sanjayshankar.me', 'advanced-preloader' ); ?></li>
				</ul>
			</div>
			<?php if ( ! $card && $state['subscribed'] ) : ?>
				<p class="sanjayshankar-connect__done" role="status">
					<?php
					/* translators: %s: date */
					echo esc_html( sprintf( __( 'This site subscribed on %s. Thank you!', 'advanced-preloader' ), date_i18n( get_option( 'date_format' ), (int) $state['subscribed'] ) ) );
					?>
				</p>
			<?php else : ?>
				<form class="sanjayshankar-connect__form" novalidate>
					<p class="sanjayshankar-connect__field">
						<label for="<?php echo esc_attr( $id ); ?>-name"><?php esc_html_e( 'First name', 'advanced-preloader' ); ?> <span class="sanjayshankar-connect__optional"><?php esc_html_e( '(optional)', 'advanced-preloader' ); ?></span></label>
						<input type="text" id="<?php echo esc_attr( $id ); ?>-name" name="first_name" maxlength="60" autocomplete="given-name" value="<?php echo esc_attr( $first ); ?>">
					</p>
					<p class="sanjayshankar-connect__field">
						<label for="<?php echo esc_attr( $id ); ?>-email"><?php esc_html_e( 'Email', 'advanced-preloader' ); ?></label>
						<input type="email" id="<?php echo esc_attr( $id ); ?>-email" name="email" required autocomplete="email" value="<?php echo esc_attr( $email ); ?>" placeholder="<?php esc_attr_e( 'you@example.com', 'advanced-preloader' ); ?>">
					</p>
					<p class="sanjayshankar-connect__submit">
						<button type="submit" class="sanjayshankar-connect__button"><?php esc_html_e( 'Subscribe', 'advanced-preloader' ); ?> <span aria-hidden="true">&rarr;</span></button>
						<?php if ( $card ) : ?>
							<button type="button" class="sanjayshankar-connect__later" data-connect-action="snooze"><?php esc_html_e( 'Not now', 'advanced-preloader' ); ?></button>
						<?php endif; ?>
					</p>
					<p class="sanjayshankar-connect__fine">
						<?php
						printf(
							/* translators: %s: privacy policy link */
							esc_html__( 'About one email a month, only about my plugins and sanjayshankar.me. Never sold or shared. Unsubscribe anytime. Sent via Brevo when you click Subscribe. %s', 'advanced-preloader' ),
							'<a href="' . esc_url( self::PRIVACY_URL ) . '" target="_blank" rel="noopener">' . esc_html__( 'Privacy policy', 'advanced-preloader' ) . '</a>'
						);
						?>
					</p>
					<p class="sanjayshankar-connect__message" role="status" aria-live="polite"></p>
				</form>
			<?php endif; ?>
			</div>
		</section>
		<?php
	}

	/**
	 * Handles Subscribe, "Not now" and "Don't show again".
	 */
	public static function ajax() {
		check_ajax_referer( 'sanjayshankar_connect', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'advanced-preloader' ) ), 403 );
		}

		$task = isset( $_POST['task'] ) ? sanitize_key( wp_unslash( $_POST['task'] ) ) : '';

		if ( 'snooze' === $task ) {
			update_user_meta( get_current_user_id(), self::USER_META, time() + self::SNOOZE_DAYS * DAY_IN_SECONDS );
			wp_send_json_success();
		}

		if ( 'feedback' === $task ) {
			self::feedback();
		}

		if ( 'subscribe' !== $task ) {
			wp_send_json_error( array( 'message' => __( 'Unknown request.', 'advanced-preloader' ) ), 400 );
		}

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$name  = isset( $_POST['first_name'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['first_name'] ) ), 0, 60 ) : '';
		$slug  = isset( $_POST['source'] ) ? sanitize_key( wp_unslash( $_POST['source'] ) ) : '';
		$all   = self::plugins();
		// The plugin's display name when it registered one, so Brevo shows "Shopnook" rather than "shopnook".
		$from  = isset( $all[ $slug ] ) ? $all[ $slug ]['name'] : $slug;
		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'advanced-preloader' ) ), 400 );
		}

		$response = wp_remote_post(
			add_query_arg( 'isAjax', '1', self::FORM_URL ),
			array(
				'timeout' => 15,
				'body'    => array(
					'EMAIL'               => $email,
					'FIRSTNAME'           => $name,
					self::SOURCE_FIELD    => $from,
					'email_address_check' => '',
					'locale'              => 'en',
					'html_type'           => 'simple',
				),
			)
		);
		$body = is_wp_error( $response ) ? null : json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) || empty( $body['success'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not reach the newsletter service. Please try again later.', 'advanced-preloader' ) ), 502 );
		}

		$state               = self::state();
		$state['subscribed'] = time();
		update_option( self::OPTION, $state, false );

		wp_send_json_success( array( 'message' => __( 'Thanks! Check your inbox to confirm your subscription.', 'advanced-preloader' ) ) );
	}

	/**
	 * Deactivation reasons. Keys are sent; the English text goes to the sheet.
	 *
	 * @return array Key => [ label, prompt for the details box ].
	 */
	private static function reasons() {
		return array(
			'not_working'     => array( __( 'I couldn’t get it to work', 'advanced-preloader' ), __( 'What went wrong?', 'advanced-preloader' ) ),
			'better_plugin'   => array( __( 'I found a better plugin', 'advanced-preloader' ), __( 'Which plugin? What does it do better?', 'advanced-preloader' ) ),
			'missing_feature' => array( __( 'It’s missing a feature I need', 'advanced-preloader' ), __( 'Which feature would you like?', 'advanced-preloader' ) ),
			'broke_site'      => array( __( 'It broke my site or conflicts with something', 'advanced-preloader' ), __( 'What happened, and with which theme or plugin?', 'advanced-preloader' ) ),
			'temporary'       => array( __( 'I only needed it for a short time', 'advanced-preloader' ), __( 'Anything you’d like to add? (optional)', 'advanced-preloader' ) ),
			'other'           => array( __( 'Other', 'advanced-preloader' ), __( 'Please tell me more', 'advanced-preloader' ) ),
		);
	}

	/**
	 * English reason text for the sheet, whatever the admin's language.
	 *
	 * @param string $key Reason key.
	 * @return string
	 */
	private static function reason_text( $key ) {
		$english = array(
			'not_working'     => 'I couldn’t get it to work',
			'better_plugin'   => 'I found a better plugin',
			'missing_feature' => 'It’s missing a feature I need',
			'broke_site'      => 'It broke my site or conflicts with something',
			'temporary'       => 'I only needed it for a short time',
			'other'           => 'Other',
		);
		return isset( $english[ $key ] ) ? $english[ $key ] : '';
	}

	/**
	 * Where deactivation feedback is sent.
	 *
	 * @return string
	 */
	private static function feedback_url() {
		/**
		 * Filters the deactivation feedback endpoint. Empty turns the survey off.
		 *
		 * @param string $url Endpoint.
		 */
		return (string) apply_filters( 'sanjayshankar_connect_feedback_url', self::FEEDBACK_URL );
	}

	/**
	 * Loads the deactivation survey on the Plugins screen.
	 *
	 * @param string $hook Screen hook.
	 */
	public static function plugins_screen( $hook ) {
		if ( 'plugins.php' !== $hook || '' === self::feedback_url() || is_network_admin() || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		$plugins = array();
		foreach ( self::plugins() as $slug => $plugin ) {
			if ( $plugin['file'] ) {
				$plugins[ $slug ] = array(
					'name' => $plugin['name'],
					'file' => $plugin['file'],
				);
			}
		}
		if ( ! $plugins ) {
			return;
		}

		$main = self::$dir . '/connect.php';
		wp_enqueue_style( 'sanjayshankar-connect', plugins_url( 'connect.css', $main ), array(), self::VERSION );
		wp_enqueue_script( 'sanjayshankar-feedback', plugins_url( 'feedback.js', $main ), array(), self::VERSION, true );
		$prompts = array();
		foreach ( self::reasons() as $key => $reason ) {
			$prompts[ $key ] = $reason[1];
		}
		wp_localize_script(
			'sanjayshankar-feedback',
			'sanjayshankarFeedback',
			array(
				'ajax'    => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'sanjayshankar_connect' ),
				'plugins' => $plugins,
				'prompts' => $prompts,
				'i18n'    => array(
					/* translators: %s: plugin name */
					'title'   => __( 'Quick question before you deactivate %s', 'advanced-preloader' ),
					'sending' => __( 'Sending…', 'advanced-preloader' ),
				),
			)
		);
		add_action( 'admin_footer', array( __CLASS__, 'survey' ) );
	}

	/**
	 * The deactivation survey dialog, shared by all registered plugins.
	 */
	public static function survey() {
		$user  = wp_get_current_user();
		$name  = trim( $user->first_name . ' ' . $user->last_name );
		$name  = '' !== $name ? $name : $user->display_name;
		$email = is_email( $user->user_email ) ? $user->user_email : (string) get_option( 'admin_email' );
		?>
		<dialog class="sanjayshankar-survey is-theme-<?php echo esc_attr( array( 'green', 'blue', 'purple', 'yellow', 'orange' )[ wp_rand( 0, 4 ) ] ); ?>" aria-labelledby="sanjayshankar-survey-title">
			<form method="dialog" class="sanjayshankar-survey__form">
				<div class="sanjayshankar-survey__head">
					<img class="sanjayshankar-connect__avatar" src="<?php echo esc_url( plugins_url( 'avatar.jpg', self::$dir . '/connect.php' ) ); ?>" width="40" height="40" alt="">
					<div>
						<h2 id="sanjayshankar-survey-title"></h2>
						<p><?php esc_html_e( 'If you have a moment, tell me why. I read every answer and use it to make the plugin better.', 'advanced-preloader' ); ?></p>
					</div>
				</div>
				<fieldset class="sanjayshankar-survey__reasons">
					<legend class="screen-reader-text"><?php esc_html_e( 'Reason for deactivating', 'advanced-preloader' ); ?></legend>
					<?php foreach ( self::reasons() as $key => $reason ) : ?>
						<label class="sanjayshankar-survey__reason">
							<input type="radio" name="reason" value="<?php echo esc_attr( $key ); ?>">
							<span><?php echo esc_html( $reason[0] ); ?></span>
						</label>
					<?php endforeach; ?>
				</fieldset>
				<div class="sanjayshankar-survey__details" hidden>
					<label for="sanjayshankar-survey-details" class="sanjayshankar-survey__prompt"></label>
					<textarea id="sanjayshankar-survey-details" name="details" rows="3" maxlength="2000"></textarea>
				</div>
				<div class="sanjayshankar-survey__contact">
					<label class="sanjayshankar-survey__check">
						<input type="checkbox" name="contact" value="1" checked>
						<span><?php esc_html_e( 'You can contact me about this', 'advanced-preloader' ); ?></span>
					</label>
					<div class="sanjayshankar-survey__who">
						<input type="text" name="name" value="<?php echo esc_attr( $name ); ?>" aria-label="<?php esc_attr_e( 'Your name', 'advanced-preloader' ); ?>" maxlength="60">
						<input type="email" name="email" value="<?php echo esc_attr( $email ); ?>" aria-label="<?php esc_attr_e( 'Your email', 'advanced-preloader' ); ?>" maxlength="120">
					</div>
				</div>
				<p class="sanjayshankar-survey__fine">
					<?php
					printf(
						/* translators: %s: privacy policy link */
						esc_html__( 'Sent to the plugin author only if you click Send: your answer, the plugin version and your WordPress, WooCommerce and PHP versions. Your name and email above are included while the box is ticked; untick it to send your answer anonymously. %s', 'advanced-preloader' ),
						'<a href="' . esc_url( self::PRIVACY_URL ) . '" target="_blank" rel="noopener">' . esc_html__( 'Privacy policy', 'advanced-preloader' ) . '</a>'
					);
					?>
				</p>
				<div class="sanjayshankar-survey__actions">
					<button type="button" class="sanjayshankar-connect__button" data-survey="send" disabled><?php esc_html_e( 'Send & deactivate', 'advanced-preloader' ); ?></button>
					<button type="button" class="sanjayshankar-survey__skip" data-survey="skip"><?php esc_html_e( 'Skip & deactivate', 'advanced-preloader' ); ?></button>
					<button type="button" class="sanjayshankar-survey__cancel" data-survey="cancel"><?php esc_html_e( 'Cancel', 'advanced-preloader' ); ?></button>
				</div>
			</form>
		</dialog>
		<?php
	}

	/**
	 * Sends one deactivation answer to the feedback sheet.
	 */
	private static function feedback() {
		if ( ! current_user_can( 'activate_plugins' ) || '' === self::feedback_url() ) {
			wp_send_json_error( null, 403 );
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Checked in ajax().
		$slug    = isset( $_POST['source'] ) ? sanitize_key( wp_unslash( $_POST['source'] ) ) : '';
		$reason  = isset( $_POST['reason'] ) ? sanitize_key( wp_unslash( $_POST['reason'] ) ) : '';
		$details = isset( $_POST['details'] ) ? mb_substr( sanitize_textarea_field( wp_unslash( $_POST['details'] ) ), 0, 2000 ) : '';
		$contact = ! empty( $_POST['contact'] );
		$name    = $contact && isset( $_POST['name'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['name'] ) ), 0, 60 ) : '';
		$email   = $contact && isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		// phpcs:enable

		$plugins = self::plugins();
		if ( ! isset( $plugins[ $slug ] ) || '' === self::reason_text( $reason ) ) {
			wp_send_json_error( null, 400 );
		}

		// Apps Script runs the request, then answers with a redirect to its result.
		// Following it would re-send the POST, which Google rejects, so fetch it with GET.
		$response = wp_remote_post(
			self::feedback_url(),
			array(
				'timeout'     => 8,
				'redirection' => 0,
				'headers'     => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode(
					array(
						'token'   => self::FEEDBACK_TOKEN,
						'slug'    => $slug,
						'plugin'  => $plugins[ $slug ]['name'],
						'version' => $plugins[ $slug ]['version'],
						'reason'  => self::reason_text( $reason ),
						'details' => $details,
						'wp'      => get_bloginfo( 'version' ),
						'wc'      => defined( 'WC_VERSION' ) ? WC_VERSION : '',
						'php'     => PHP_VERSION,
						'locale'  => get_user_locale(),
						'name'    => $name,
						'email'   => is_email( $email ) ? $email : '',
					)
				),
			)
		);
		$location = is_wp_error( $response ) ? '' : (string) wp_remote_retrieve_header( $response, 'location' );
		if ( $location && 0 === strpos( $location, 'https://' ) ) {
			$response = wp_remote_get( $location, array( 'timeout' => 8 ) );
		}
		$result = is_wp_error( $response ) ? null : json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $result['ok'] ) ) {
			wp_send_json_error( null, 502 );
		}
		wp_send_json_success();
	}
}
