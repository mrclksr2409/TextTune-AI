<?php
/**
 * WP-Backend UI — screen registration, asset loading and markup helpers.
 *
 * @package WP_Backend_UI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared admin UI for all plugins.
 *
 * Only loaded through wp-backend-ui.php, never directly.
 */
final class WPB_Admin_UI {

	/**
	 * Library version (also used as asset version).
	 */
	const VERSION = '1.0.2';

	/**
	 * Asset handle (shared by every plugin bundling the library).
	 */
	const HANDLE = 'wpb-admin-ui';

	/**
	 * Body class that activates the core layer of the stylesheet.
	 */
	const BODY_CLASS = 'wpb-admin';

	/**
	 * Absolute path of the loaded loader file.
	 *
	 * @var string
	 */
	private static $file = '';

	/**
	 * Registered ?page= slugs.
	 *
	 * @var string[]
	 */
	private static $pages = array();

	/**
	 * Registered screen IDs.
	 *
	 * @var string[]
	 */
	private static $screens = array();

	/**
	 * Hook everything up. Called once by wpb_admin_ui_boot().
	 *
	 * @param string $file Loader file of the copy that won the version check.
	 * @return void
	 */
	public static function init( $file ) {
		self::$file = $file;

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'maybe_enqueue' ) );
		add_filter( 'admin_body_class', array( __CLASS__, 'body_class' ) );
	}

	/**
	 * Register screens that get the shared UI.
	 *
	 * @param array $args {
	 *     @type string[] $pages   Admin page slugs as used in ?page= (e.g. 'auto-quill-settings').
	 *     @type string[] $screens Screen IDs (e.g. 'settings_page_pixel-diet', 'edit-post').
	 * }
	 * @return void
	 */
	public static function register( array $args ) {
		if ( ! empty( $args['pages'] ) ) {
			self::$pages = array_unique( array_merge( self::$pages, array_map( 'sanitize_key', (array) $args['pages'] ) ) );
		}
		if ( ! empty( $args['screens'] ) ) {
			self::$screens = array_unique( array_merge( self::$screens, array_map( 'sanitize_key', (array) $args['screens'] ) ) );
		}
	}

	/**
	 * Whether the current admin request is a registered screen.
	 *
	 * @return bool
	 */
	public static function is_active_screen() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing check.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( '' !== $page && in_array( $page, self::$pages, true ) ) {
			return true;
		}

		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( $screen && in_array( $screen->id, self::$screens, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add the scope class on registered screens.
	 *
	 * @param string $classes Space-separated body classes.
	 * @return string
	 */
	public static function body_class( $classes ) {
		if ( self::is_active_screen() ) {
			$classes .= ' ' . self::BODY_CLASS;
		}
		return $classes;
	}

	/**
	 * Enqueue assets on registered screens.
	 *
	 * @return void
	 */
	public static function maybe_enqueue() {
		if ( self::is_active_screen() ) {
			self::enqueue_assets();
		}
	}

	/**
	 * Enqueue the stylesheet and script.
	 *
	 * Call this directly to use the .wpb-* components on screens that are not
	 * registered (e.g. a meta box in the post editor). The core layer only
	 * applies to registered screens.
	 *
	 * @return void
	 */
	public static function enqueue_assets() {
		if ( wp_style_is( self::HANDLE, 'enqueued' ) ) {
			return;
		}

		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( self::HANDLE, self::asset_url( 'assets/css/wpb-admin.css' ), array(), self::VERSION );
		wp_enqueue_script( self::HANDLE, self::asset_url( 'assets/js/wpb-admin.js' ), array(), self::VERSION, true );
		wp_localize_script(
			self::HANDLE,
			'wpbAdminUIL10n',
			array(
				'copied'     => __( 'Copied to clipboard.', 'wp-backend-ui' ),
				'copyFailed' => __( 'Copy failed.', 'wp-backend-ui' ),
				'show'       => __( 'Show', 'wp-backend-ui' ),
				'hide'       => __( 'Hide', 'wp-backend-ui' ),
				'close'      => __( 'Close', 'wp-backend-ui' ),
			)
		);
	}

	/**
	 * URL of a file inside the library.
	 *
	 * @param string $path Relative path.
	 * @return string
	 */
	public static function asset_url( $path ) {
		return plugins_url( ltrim( $path, '/' ), self::$file );
	}

	/**
	 * Render the page header. Use it as the first element inside .wrap.
	 *
	 * Also prints <hr class="wp-header-end">, so WordPress moves admin notices
	 * below the header instead of into it.
	 *
	 * @param array $args {
	 *     @type string $title    Page title. Default: get_admin_page_title().
	 *     @type string $subtitle Optional one-line description.
	 *     @type string $icon     Dashicon slug (e.g. 'dashicons-email-alt') or image URL.
	 *     @type string $version  Optional version shown as badge.
	 *     @type array  $actions  List of array( 'label', 'url', 'primary' => bool, 'target' => '_blank' ).
	 * }
	 * @return void
	 */
	public static function header( array $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'title'    => get_admin_page_title(),
				'subtitle' => '',
				'icon'     => '',
				'version'  => '',
				'actions'  => array(),
			)
		);
		?>
		<div class="wpb-header">
			<div class="wpb-header__brand">
				<?php if ( '' !== $args['icon'] ) : ?>
					<?php if ( 0 === strpos( $args['icon'], 'dashicons-' ) ) : ?>
						<span class="wpb-header__icon dashicons <?php echo esc_attr( $args['icon'] ); ?>" aria-hidden="true"></span>
					<?php else : ?>
						<span class="wpb-header__icon" aria-hidden="true"><img src="<?php echo esc_url( $args['icon'] ); ?>" alt="" /></span>
					<?php endif; ?>
				<?php endif; ?>
				<div>
					<h1 class="wpb-header__title">
						<?php echo esc_html( $args['title'] ); ?>
						<?php if ( '' !== $args['version'] ) : ?>
							<span class="wpb-badge"><?php echo esc_html( 'v' . ltrim( $args['version'], 'v' ) ); ?></span>
						<?php endif; ?>
					</h1>
					<?php if ( '' !== $args['subtitle'] ) : ?>
						<p class="wpb-header__subtitle"><?php echo esc_html( $args['subtitle'] ); ?></p>
					<?php endif; ?>
				</div>
			</div>
			<?php if ( ! empty( $args['actions'] ) ) : ?>
				<div class="wpb-header__actions">
					<?php foreach ( $args['actions'] as $action ) : ?>
						<?php
						$action = wp_parse_args(
							$action,
							array(
								'label'   => '',
								'url'     => '#',
								'primary' => false,
								'target'  => '',
							)
						);
						?>
						<a class="button<?php echo $action['primary'] ? ' button-primary' : ''; ?>" href="<?php echo esc_url( $action['url'] ); ?>"<?php echo '' !== $action['target'] ? ' target="' . esc_attr( $action['target'] ) . '" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $action['label'] ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<hr class="wp-header-end" />
		<?php
	}

	/**
	 * Render server-side tabs (one URL per tab).
	 *
	 * @param array  $tabs   Map of tab key => label.
	 * @param string $active Active tab key.
	 * @param array  $args {
	 *     @type string $base_url  URL the tab argument is added to. Default: current admin page.
	 *     @type string $query_arg Query argument name. Default 'tab'.
	 *     @type string $label     Accessible label for the nav element.
	 * }
	 * @return void
	 */
	public static function tabs( array $tabs, $active, array $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'base_url'  => self::current_page_url(),
				'query_arg' => 'tab',
				'label'     => __( 'Secondary menu', 'wp-backend-ui' ),
			)
		);

		echo '<nav class="nav-tab-wrapper wpb-tabs" aria-label="' . esc_attr( $args['label'] ) . '">';
		foreach ( $tabs as $key => $label ) {
			$is_active = (string) $key === (string) $active;
			printf(
				'<a href="%1$s" class="nav-tab%2$s"%3$s>%4$s</a>',
				esc_url( add_query_arg( $args['query_arg'], $key, $args['base_url'] ) ),
				$is_active ? ' nav-tab-active' : '',
				$is_active ? ' aria-current="page"' : '',
				esc_html( $label )
			);
		}
		echo '</nav>';
	}

	/**
	 * Open a card. Close it with card_end().
	 *
	 * @param array $args {
	 *     @type string $title       Card title.
	 *     @type string $description Text below the title.
	 *     @type string $actions     Trusted HTML for the header's right side (escape before passing).
	 *     @type string $class       Extra CSS classes for the card.
	 *     @type bool   $flush       Remove body padding (for tables). Default false.
	 * }
	 * @return void
	 */
	public static function card_start( array $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'title'       => '',
				'description' => '',
				'actions'     => '',
				'class'       => '',
				'flush'       => false,
			)
		);

		echo '<section class="' . esc_attr( trim( 'wpb-card ' . $args['class'] ) ) . '">';

		if ( '' !== $args['title'] || '' !== $args['actions'] ) {
			echo '<header class="wpb-card__header"><div>';
			if ( '' !== $args['title'] ) {
				echo '<h2 class="wpb-card__title">' . esc_html( $args['title'] ) . '</h2>';
			}
			if ( '' !== $args['description'] ) {
				echo '<p class="wpb-card__description">' . esc_html( $args['description'] ) . '</p>';
			}
			echo '</div>';
			if ( '' !== $args['actions'] ) {
				echo '<div class="wpb-row">' . wp_kses_post( $args['actions'] ) . '</div>';
			}
			echo '</header>';
		}

		echo '<div class="wpb-card__body' . ( $args['flush'] ? ' wpb-card__body--flush' : '' ) . '">';
	}

	/**
	 * Close a card opened with card_start().
	 *
	 * @param string $footer Optional trusted footer HTML (escape before passing).
	 * @return void
	 */
	public static function card_end( $footer = '' ) {
		echo '</div>';
		if ( '' !== $footer ) {
			echo '<footer class="wpb-card__footer">' . $footer . '</footer>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- documented as trusted, may contain form controls.
		}
		echo '</section>';
	}

	/**
	 * Badge markup.
	 *
	 * @param string $label   Text.
	 * @param string $variant neutral|success|warning|error|info|accent.
	 * @param bool   $dot     Show a status dot.
	 * @return string Escaped HTML.
	 */
	public static function badge( $label, $variant = 'neutral', $dot = false ) {
		$classes = 'wpb-badge';
		if ( 'neutral' !== $variant ) {
			$classes .= ' wpb-badge--' . sanitize_html_class( $variant );
		}
		if ( $dot ) {
			$classes .= ' wpb-badge--dot';
		}
		return '<span class="' . esc_attr( $classes ) . '">' . esc_html( $label ) . '</span>';
	}

	/**
	 * Toggle switch markup (a styled checkbox, works with the Settings API).
	 *
	 * @param string $name    Input name.
	 * @param bool   $checked Current state.
	 * @param string $label   Visible label.
	 * @param array  $args {
	 *     @type string $id    Input ID.
	 *     @type string $value Submitted value. Default '1'.
	 *     @type bool   $disabled
	 * }
	 * @return string Escaped HTML.
	 */
	public static function toggle( $name, $checked, $label, array $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'id'       => '',
				'value'    => '1',
				'disabled' => false,
			)
		);

		return sprintf(
			'<label class="wpb-toggle"><input type="checkbox" name="%1$s" value="%2$s"%3$s%4$s%5$s /><span class="wpb-toggle__track" aria-hidden="true"></span><span>%6$s</span></label>',
			esc_attr( $name ),
			esc_attr( $args['value'] ),
			'' !== $args['id'] ? ' id="' . esc_attr( $args['id'] ) . '"' : '',
			checked( $checked, true, false ),
			disabled( $args['disabled'], true, false ),
			esc_html( $label )
		);
	}

	/**
	 * Inline alert markup (stays where it is printed, unlike .notice).
	 *
	 * @param string $message Text.
	 * @param string $variant info|success|warning|error.
	 * @param string $title   Optional bold title.
	 * @return string Escaped HTML.
	 */
	public static function alert( $message, $variant = 'info', $title = '' ) {
		$icons = array(
			'info'    => 'dashicons-info-outline',
			'success' => 'dashicons-yes-alt',
			'warning' => 'dashicons-warning',
			'error'   => 'dashicons-dismiss',
		);
		$icon  = isset( $icons[ $variant ] ) ? $icons[ $variant ] : $icons['info'];
		$role  = 'error' === $variant ? 'alert' : 'status';

		return sprintf(
			'<div class="wpb-alert wpb-alert--%1$s" role="%2$s"><span class="dashicons %3$s" aria-hidden="true"></span><div class="wpb-alert__body">%4$s<p>%5$s</p></div></div>',
			esc_attr( sanitize_html_class( $variant ) ),
			esc_attr( $role ),
			esc_attr( $icon ),
			'' !== $title ? '<strong class="wpb-alert__title">' . esc_html( $title ) . '</strong>' : '',
			esc_html( $message )
		);
	}

	/**
	 * Current admin page URL without volatile arguments.
	 *
	 * @return string
	 */
	private static function current_page_url() {
		global $pagenow;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing check.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$base = admin_url( $pagenow ? $pagenow : 'admin.php' );

		return '' !== $page ? add_query_arg( 'page', $page, $base ) : $base;
	}
}
