<?php defined( 'ABSPATH' ) || die();

// Avoid recursively registering this function after bootstrap.
if ( defined( 'ABSPATH' ) && ! class_exists( 'Rename_WP_Login' ) ) {

	/**
	 * This class renames the WordPress login URL to a custom route.
	 *
	 * @package utexas-eid-auth
	 */
	class Rename_WP_Login {

		/**
		 * Defines the replacement login path, overriding wp-login.php.
		 *
		 * @var string
		 */
		private static $new_login_slug = 'saml/login/';

		/**
		 * Whether the current request is to the WordPress default login path.
		 *
		 * @var bool
		 */
		private static $wp_login_php;

		/**
		 * Registers our functions within WP hooks.
		 */
		public static function register() {
			register_uninstall_hook( plugin_basename( __FILE__ ), 'Rename_WP_Login::uninstall' );
			// Hook into bootstrap stage where plugins have been loaded.
			add_action( 'plugins_loaded', 'Rename_WP_Login::plugins_loaded', 1 );
			// Hook into processed after WordPress has been bootstrapped.
			add_action( 'wp_loaded', 'Rename_WP_Login::wp_loaded' );
			// Modify the site url.
			add_filter( 'site_url', 'Rename_WP_Login::site_url', 10, 4 );
			// Hook into WordPress redirection process.
			add_filter( 'wp_redirect', 'Rename_WP_Login::wp_redirect', 10, 2 );
			// Modify the new user welcome email.
			add_filter( 'site_option_welcome_email', 'Rename_WP_Login::welcome_email' );
			// Do not use WordPress core's template logic for admin actions.
			// Instead, we use a modified version in self::wp_template_loader().
			remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );

			// Accommodations for network/multisites.
			add_filter( 'network_site_url', 'Rename_WP_Login::network_site_url', 10, 3 );
			if ( is_multisite() && ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}
		}

		/**
		 * Overrides WordPress's base template loader to exclude wp-login-php.
		 */
		private static function wp_template_loader() {
			global $pagenow;
			$pagenow = 'index.php';
			if ( ! defined( 'WP_USE_THEMES' ) ) {
				define( 'WP_USE_THEMES', true );
			}
			wp();

			// This completes the core logic in wp-blog-header.php.
			require_once ABSPATH . WPINC . '/template-loader.php';
			die;
		}

		/**
		 * Returns the site's new login URL.
		 *
		 * @return string
		 */
		public static function new_login_url() {
			// Using home_url() here returns the site URL, rather than the network
			// site, in the context of a multisite.
			return home_url( '/', 'https' ) . trailingslashit( self::$new_login_slug );
		}

		/**
		 * Plugins are loaded. Evaluate whether the current page is login.
		 */
		public static function plugins_loaded() {

			global $pagenow;
			$request = rawurldecode( $_SERVER['REQUEST_URI'] );
			$uri     = wp_parse_url( $request );
			$path    = $uri['path'] ?? untrailingslashit( $uri['path'] );

			// Disallow requests for /wp-signup and /wp-activate.
			if ( ! is_multisite() ) {
				if ( strpos( $request , 'wp-signup' ) !== false
					|| strpos( $request , 'wp-activate' ) !== false ) {
						wp_die( __( 'This feature is not enabled.', 'rename-wp-admin-login' ) );
				}
			}

			if ( ( strpos( $request, 'wp-login.php' ) !== false
				|| ( isset( $path ) && $path === site_url( 'wp-login', 'relative' ) ) )
				&& ! is_admin() ) {
					// Scenario 1: the *literal* request is to `wp-login.php` or `/wp-login`.
					// Classify this as a non-login request by changing the internal
					// route from 'wp-login.php' to 'index.php. This will result in a 404.
					self::$wp_login_php     = true;
					$pagenow                = 'index.php';
			} elseif ( ( isset( $uri['path'] )
				&& trailingslashit( $uri['path'] ) === home_url( self::$new_login_slug, 'relative' ) ) ) {
					// Scenario 2: the *literal request is to /saml/login/
					// Classify this as a request to the WordPress sign-in page
					// and add a trailing slash to match the iDP Assertion Consumer Service.
					$_SERVER['REQUEST_URI'] = trailingslashit( $_SERVER['REQUEST_URI'] );
					$pagenow                = 'wp-login.php';
			} elseif ( ( strpos( $request, 'wp-register.php' ) !== false
					|| ( isset( $path ) && $path === site_url( 'wp-register', 'relative' ) ) )
				&& ! is_admin() ) {
					// Scenario 3: the *literal* request is to `wp-register.php` or `/wp-register`.
					// Classify this as a non-login request by changing the internal
					// route from 'wp-login.php' to 'index.php. This will result in a 404.
				self::$wp_login_php     = true;
				$pagenow                = 'index.php';
			}
		}

		/**
		 * WordPress has bootstrapped. If the request is for a login page, redirect.
		 */
		public static function wp_loaded() {
			global $pagenow;

			// Redirect unauthenticated requests for admin pages to the homepage.
			if ( is_admin() && ! is_user_logged_in() && ! defined( 'DOING_AJAX' ) ) {
				// Using home_url() here instead of simply '/' accommodates multisites.
				wp_safe_redirect( home_url() );
				die();
			}
			$request = rawurldecode( $_SERVER['REQUEST_URI'] );
			$uri     = wp_parse_url( $request );

			//if ( ! is_user_logged_in() && str_ends_with( $uri['path'], '/saml/login' ) !== FALSE ) {
			//	print_r($pagenow);
			//	print_r($request);
			//	die();
			//	$_SERVER['REQUEST_URI'] = untrailingslashit($_SERVER['REQUEST_URI']);
			//}

			//// Provide a legacy redirect for /saml_login to the new path.
			//if ( ! is_user_logged_in() && str_ends_with( $uri['path'], '/saml/login/' ) !== FALSE ) {
			//	print_r($pagenow);
			//	print_r($request);
			//	die();
			//	$_SERVER['REQUEST_URI'] = untrailingslashit($_SERVER['REQUEST_URI']);
			//}
			//print_r($uri['path']);
			//if ( ! is_user_logged_in() && str$uri['path'] )
			//die();

			// Provide a legacy redirect for /saml_login to the new path.
			if ( strpos( $uri['path'], '/saml_login' ) === 0 ) {
				wp_safe_redirect( self::new_login_url() );
				die();
			}

			// Redirect bare *authenticated* requests for /saml/login to the homepage.
			// See "Scenario 2" in plugins_loaded().
			// The check for $uri['query'] avoids this redirect for requests like
			// /saml/login/?action=logout
			if ( is_user_logged_in() && 'wp-login.php' === $pagenow && empty( $uri['query'] ) ) {
					// Using home_url() here instead of simply '/' accommodates multisites.
					wp_safe_redirect( home_url() );
					die();
			}

			// If the request has been identified as "this is a login request"...
			// @see self::wp_loaded()
			if ( self::$wp_login_php ) {
				// If the path is for wp-activate.php and a query param is present...
				$referer = wp_get_referer();
				$uri     = wp_parse_url( $referer );
				if (
					( $referer ) &&
					strpos( $referer, 'wp-activate.php' ) !== false &&
					( $uri ) &&
					! empty( $uri['query'] )
				) {
					// The request has been passed from wp-activate.php.
					parse_str( $uri['query'], $referer );

					$result = wpmu_activate_signup( $uri['key'] );
					// If the activation request is already active/taken...
					if (
						! empty( $uri['key'] ) &&
						( $result ) &&
						is_wp_error( $result ) && (
							$result->get_error_code() === 'already_active' ||
							$result->get_error_code() === 'blog_taken'
						)
					) {
						wp_safe_redirect( self::new_login_url() . ( ! empty( $_SERVER['QUERY_STRING'] ) ? '?' . $_SERVER['QUERY_STRING'] : '' ) );
						die;
					}
				}
				self::wp_template_loader();
			} elseif ( 'wp-login.php' === $pagenow ) {
				// Fallback method to ensure wp-login.php isn't accessed.
				global $error, $interim_login, $action, $user_login;
				@require_once ABSPATH . 'wp-login.php';
				die;
			}
		}

		/**
		 * Provide the new URL for the login path including parameters.
		 *
		 * This is used by:
		 *   self::site_url()
		 *   self::network_site_url()
		 *
		 * @param string $url The old login URL.
		 * @return string The new login URL.
		 */
		public static function filter_wp_login_php( $url ) {
			if ( strpos( $url, 'wp-login.php' ) !== false ) {
				$args = explode( '?', $url );
				if ( isset( $args[1] ) ) {
					parse_str( $args[1], $args );
					$url = add_query_arg( $args, self::new_login_url() );
				} else {
					$url = self::new_login_url();
				}
			}
			return $url;
		}

		/**
		 * Hooks into network_site_url to rewrite login URLs.
		 *
		 * @param string $url The current base URL.
		 * @param string $path The current path.
		 * @param string $scheme An HTTP scheme.
		 * @param string $blog_id The (multisite) site blog id.
		 * @return string The prepared URL.
		 */
		public static function site_url( $url, $path, $scheme, $blog_id ) {
			return self::filter_wp_login_php( $url );
		}

		/**
		 * Hooks into network_site_url to rewrite login URLs.
		 *
		 * @param string $url The current base URL.
		 * @param string $path The current path.
		 * @param string $scheme An HTTP scheme.
		 * @return string The prepared URL.
		 */
		public static function network_site_url( $url, $path, $scheme ) {
			return self::filter_wp_login_php( $url );
		}

		/**
		 * Hooks into wp_redirect to rewrite login URLs.
		 *
		 * @param string $location A URL.
		 * @param string $status An HTTP status code.
		 * @return string The prepared URL.
		 */
		public static function wp_redirect( $location, $status ) {
			return self::filter_wp_login_php( $location );
		}

		/**
		 * Replace "wp-login.php" in our welcome email with the new path.
		 *
		 * @param string $value The original welcome email text.
		 * @return string The new welcome email text.
		 */
		public static function welcome_email( $value ) {
			return str_replace( 'wp-login.php', trailingslashit( self::$new_login_slug ), $value );
		}

		/**
		 * Update the list of forbidden paths.
		 *
		 * @return [] The new list of forbidden paths.
		 */
		public static function forbidden_slugs() {
			$wp = new WP();
			return array_merge( $wp->public_query_vars, $wp->private_query_vars );
		}

		/**
		 * Callback for uninstallation. Ensures redirections are flushed.
		 */
		public static function uninstall() {
			global $wpdb;
			if ( is_multisite() ) {
				// Handle multisites.
				flush_rewrite_rules();
				$blogs = $wpdb->get_results( "SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A );
				if ( $blogs ) {
					foreach ( $blogs as $blog ) {
						switch_to_blog( $blog['blog_id'] );
						flush_rewrite_rules();
						restore_current_blog();
					}
				}
			} else {
				// Handle single sites.
				flush_rewrite_rules();
			}
		}
	}

	Rename_WP_Login::register();
}
