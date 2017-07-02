<?php

namespace Zdev\ZNonce;

/**
 * Class ZNonce
 * Main plugin class.
 *
 * @package Zdev\ZNonce
 * @since   1.0.0
 */
class ZNonce {

	/**
	 * @var ZNonce
	 */
	private static $instance;

	/**
	 * @return ZNonce
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Retrieve URL with nonce added to URL query.
	 *
	 * @param string $url
	 * @param int $action
	 * @param string $name
	 *
	 * @return string
	 */
	public function nonce_url( $url, $action = - 1, $name = '_wpnonce' ) {
		$url = str_replace( '&amp;', '&', $url );

		return esc_html( add_query_arg( $name, $this->create_nonce( $action ), $url ) );
	}

	/**
	 * Retrieve or display nonce hidden field for forms.
	 *
	 * @param int $action
	 * @param string $name
	 * @param bool $referer
	 * @param bool $echo
	 *
	 * @return string
	 */
	public function nonce_field( $action = - 1, $name = '_wpnonce', $referer = true, $echo = true ) {
		$name        = esc_attr( $name );
		$nonce_field = '<input type="hidden" id="' . $name . '" name="' . $name . '" value="' . $this->create_nonce( $action ) . '" />';

		if ( $referer ) {
			$nonce_field .= $this->referer_field();
		}

		if ( $echo ) {
			echo $nonce_field;
		}

		return $nonce_field;
	}

	/**
	 * Creates a cryptographic token tied to a specific action, user, user session,
	 * and window of time.
	 *
	 * @param int $action
	 *
	 * @return bool|string
	 */
	public function create_nonce( $action = - 1 ) {
		$user = wp_get_current_user();
		$uid  = (int) $user->ID;
		if ( ! $uid ) {
			$uid = apply_filters( 'nonce_user_logged_out', $uid, $action );
		}

		$token = $this->get_session_token();
		$i     = $this->nonce_tick();

		return substr( $this->get_hash( $i . '|' . $action . '|' . $uid . '|' . $token, 'nonce' ), - 12, 10 );
	}

	/**
	 * Makes sure that a user was referred from another admin page.
	 *
	 * @param int|string $action Action nonce.
	 * @param string $query_arg Optional. Key to check for nonce in `$_REQUEST`. Default '_wpnonce'.
	 *
	 * @return false|int False if the nonce is invalid, 1 if the nonce is valid and generated between
	 *                   0-12 hours ago, 2 if the nonce is valid and generated between 12-24 hours ago.
	 */
	public function check_admin_referer( $action = - 1, $query_arg = '_wpnonce' ) {
		try {
			if ( - 1 == $action ) {
				throw new \Exception( __( 'You should specify a nonce action to be verified by using the first parameter.' ) );
			}

			$adminurl = strtolower( admin_url() );
			$referer  = strtolower( wp_get_referer() );
			$result   = isset( $_REQUEST[ $query_arg ] ) ? $this->verify_nonce( $_REQUEST[ $query_arg ], $action ) : false;

			do_action( 'check_admin_referer', $action, $result );

			if ( ! $result && ! ( - 1 == $action && strpos( $referer, $adminurl ) === 0 ) ) {
				wp_nonce_ays( $action );
				die();
			}

			return $result;

		} catch ( \Exception $e ) {
			trigger_error( __METHOD__ . ': ' . $e->getMessage() );

			return false;
		}
	}

	/**
	 * Verifies the Ajax request to prevent processing requests external of the blog.
	 *
	 * @param int $action
	 * @param bool $query_arg
	 * @param bool $die
	 *
	 * @return false|int
	 */
	public function check_ajax_referer( $action = - 1, $query_arg = false, $die = true ) {
		try {
			if ( - 1 == $action ) {
				throw new \Exception( __( 'You should specify a nonce action to be verified by using the first parameter.' ) );
			}

			$nonce = '';

			if ( $query_arg && isset( $_REQUEST[ $query_arg ] ) ) {
				$nonce = $_REQUEST[ $query_arg ];
			} elseif ( isset( $_REQUEST['_ajax_nonce'] ) ) {
				$nonce = $_REQUEST['_ajax_nonce'];
			} elseif ( isset( $_REQUEST['_wpnonce'] ) ) {
				$nonce = $_REQUEST['_wpnonce'];
			}

			$result = $this->verify_nonce( $nonce, $action );

			do_action( 'check_ajax_referer', $action, $result );

			if ( $die && false === $result ) {
				if ( wp_doing_ajax() ) {
					wp_die( - 1, 403 );
				} else {
					die( '-1' );
				}
			}

			return $result;
		} catch ( \Exception $e ) {
			trigger_error( __METHOD__ . ': ' . $e->getMessage() );

			return false;
		}
	}

	/**
	 * Verify that correct nonce was used with time limit.
	 *
	 * @param     $nonce
	 * @param int $action
	 *
	 * @return bool|int
	 */
	public function verify_nonce( $nonce, $action = - 1 ) {
		$nonce = (string) $nonce;
		$user  = wp_get_current_user();
		$uid   = (int) $user->ID;
		if ( ! $uid ) {
			$uid = apply_filters( 'nonce_user_logged_out', $uid, $action );
		}

		if ( empty( $nonce ) ) {
			return false;
		}

		$token = $this->get_session_token();
		$i     = $this->nonce_tick();

		// Nonce generated 0-12 hours ago
		$expected = substr( $this->get_hash( $i . '|' . $action . '|' . $uid . '|' . $token, 'nonce' ), - 12, 10 );
		if ( hash_equals( $expected, $nonce ) ) {
			return 1;
		}

		// Nonce generated 12-24 hours ago
		$expected = substr( $this->get_hash( ( $i - 1 ) . '|' . $action . '|' . $uid . '|' . $token, 'nonce' ), - 12, 10 );
		if ( hash_equals( $expected, $nonce ) ) {
			return 2;
		}

		do_action( 'wp_verify_nonce_failed', $nonce, $action, $user, $token );

		// Invalid nonce
		return false;
	}

	/**
	 * ZNonce constructor.
	 */
	private function __construct() {
	}

	/**
	 * Retrieve or display referer hidden field for forms.
	 *
	 * @return string
	 */
	private function referer_field() {
		return '<input type="hidden" name="_wp_http_referer" value="' . esc_attr( wp_unslash( $_SERVER['REQUEST_URI'] ) ) . '" />';
	}

	/**
	 * Retrieve the current session token from the logged_in cookie.
	 *
	 * @return string Token.
	 */
	private function get_session_token() {
		$cookie = wp_parse_auth_cookie( '', 'logged_in' );

		return ! empty( $cookie['token'] ) ? $cookie['token'] : '';
	}

	/**
	 * Get the time-dependent variable for nonce creation.
	 *
	 * @return float
	 */
	private function nonce_tick() {
		$nonce_life = apply_filters( 'nonce_life', DAY_IN_SECONDS );

		return ceil( time() / ( $nonce_life / 2 ) );
	}

	/**
	 * Get hash of given string.
	 *
	 * @param        $data
	 *
	 * @return false|string
	 */
	private function get_hash( $data ) {
		$salt = wp_salt('nonce');

		if (strlen($salt) > 64) {
			$salt = pack('H32', md5($salt));
		}

		$salt = str_pad($salt, 64, chr(0));

		$ipad = (substr($salt, 0, 64) ^ str_repeat(chr(0x36), 64));
		$opad = (substr($salt, 0, 64) ^ str_repeat(chr(0x5C), 64));

		return md5($opad . pack('H32', md5($ipad . $data)));
	}

}
