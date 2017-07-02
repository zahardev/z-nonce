<?php

namespace Zahardoc\ZNonce\Tests\Unit;

use Zahardoc\ZNonce\ZNonce;

/**
 * Tests for ZNonce class.
 *
 * @package Zahardoc\ZNonce\Tests\Unit
 *
 * @since   1.0.0
 */
class ZNonceTest extends \WP_UnitTestCase {
	/**
	 * @var \Zahardoc\ZNonce\ZNonce
	 * */
	private static $znonce;

	/**
	 * @var
	 */
	private static $mock;

	/**
	 *
	 */
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		self::$znonce = ZNonce::init();

		self::$mock   = array(
			'actions'  => array(
				- 1,
				'customAction',
			),
			'names'    => array(
				'_wpnonce',
				'customName',
			),
			'referers' => array(
				true,
				false,
			),
			'urls'     => array(
				'http://google.com',
				'http://google.com/?q=test',
			),
		);


		//test nonce half expiration feature
		add_filter( 'nonce_life', function () {
			return 2;
		} );
		self::$znonce->set_nonce_life(2);
	}

	/**
	 * @param bool $logged_in
	 */
	public function test_create_nonce( $logged_in = false ) {
		foreach ( self::$mock['actions'] as $action ) {
			$z_nonce  = self::$znonce->create_nonce( $action );
			$wp_nonce = self::$znonce->create_nonce( $action );
			$this->assertSame( $wp_nonce, $z_nonce, "Logged out user nonce error" );
		}

		if ( ! $logged_in ) {
			$this->login();
			$this->test_create_nonce( true );
		}
	}


	/**
	 * @param bool $logged_in
	 */
	public function test_nonce_field( $logged_in = false ) {
		foreach ( self::$mock['actions'] as $action ) {
			foreach ( self::$mock['names'] as $name ) {
				foreach ( self::$mock['referers'] as $referer ) {
					$z_nonce  = self::$znonce->nonce_field( $action, $name, $referer, false );
					$wp_nonce = wp_nonce_field( $action, $name, $referer, false );
					$this->assertSame( $wp_nonce, $z_nonce, $this->user_type( $logged_in ) . " user nonce error" );
				}
			}
		}

		if ( ! $logged_in ) {
			$this->login();
			$this->test_nonce_field( true );
		}
	}


	/**
	 * @param bool $logged_in
	 */
	public function test_nonce_url( $logged_in = false ) {
		foreach ( self::$mock['urls'] as $url ) {
			foreach ( self::$mock['actions'] as $action ) {
				foreach ( self::$mock['names'] as $name ) {
					$z_nonce  = self::$znonce->nonce_url( $url, $action, $name );
					$wp_nonce = wp_nonce_url( $url, $action, $name );
					$this->assertSame( $wp_nonce, $z_nonce, $this->user_type( $logged_in ) . " user nonce error" );
				}
			}
		}

		if ( ! $logged_in ) {
			$this->login();
			$this->test_nonce_url( true );
		}
	}


	/**
	 * @param bool $logged_in
	 */
	public function test_verify_nonce( $logged_in = false ) {
		foreach ( self::$mock['actions'] as $action ) {
			$nonce = self::$znonce->create_nonce( $action );
			$this->assertEquals( 1, self::$znonce->verify_nonce( $nonce, $action ) );

			sleep( 1 );
			$this->assertEquals( 2, self::$znonce->verify_nonce( $nonce, $action ) );

			sleep( 1 );
			$this->assertFalse( self::$znonce->verify_nonce( $nonce, $action ) );
		}

		if ( ! $logged_in ) {
			$this->login();
			$this->test_verify_nonce( true );
		}
	}


	/**
	 * @param bool $logged_in
	 */
	public function test_check_admin_referer( $logged_in = false ) {
		foreach ( self::$mock['names'] as $name ) {
			$_REQUEST[ $name ] = self::$znonce->create_nonce( 'test_action' );
			$this->assertEquals( 1, self::$znonce->check_admin_referer( 'test_action', $name ) );
			sleep( 1 );
			$this->assertEquals( 2, self::$znonce->check_admin_referer( 'test_action', $name ) );
		}

		if( ! $logged_in ){
			$this->login();
			$this->test_check_admin_referer( true );
		}
	}

	/**
	 * @param bool $logged_in
	 */
	public function test_check_ajax_referer( $logged_in = false ) {
		foreach ( self::$mock['names'] as $name ) {
			$_REQUEST[ $name ] = self::$znonce->create_nonce( 'test_action' );
			$this->assertEquals( 1, self::$znonce->check_ajax_referer( 'test_action', $name ) );

			sleep( 1 );
			$this->assertEquals( 2, self::$znonce->check_ajax_referer( 'test_action', $name ) );

			sleep( 1 );
			$this->assertFalse( self::$znonce->check_ajax_referer( 'test_action', $name, false ) );
		}

		$_REQUEST['_ajax_nonce'] = self::$znonce->create_nonce( 'test_action' );
		$this->assertEquals( 1, self::$znonce->check_ajax_referer( 'test_action', false ) );

		sleep( 1 );
		$this->assertEquals( 2, self::$znonce->check_ajax_referer( 'test_action', false ) );

		unset( $_REQUEST['_ajax_nonce'] );
		$this->assertFalse( self::$znonce->check_ajax_referer( 'test_action', false, false ) );

		$_REQUEST['_wpnonce'] = self::$znonce->create_nonce( 'test_action' );
		$this->assertEquals( 1, self::$znonce->check_ajax_referer( 'test_action', false ) );

		sleep( 1 );
		$this->assertEquals( 2, self::$znonce->check_ajax_referer( 'test_action', false ) );

		unset( $_REQUEST['_wpnonce'] );
		$this->assertFalse( self::$znonce->check_ajax_referer( 'test_action', false, false ) );

		if( ! $logged_in ){
			$this->test_check_ajax_referer( true );
		}
	}

	/**
	 * Function login
	 *
	 * Creates user and sets up it as current one.
	 */
	private function login() {
		$user_id = $this->factory->user->create();
		wp_set_current_user( $user_id );
	}


	/**
	 * @param $logged_in
	 *
	 * @return string
	 */
	private function user_type( $logged_in ) {
		return $logged_in ? 'Logged in' : 'Logged out';
	}
}
