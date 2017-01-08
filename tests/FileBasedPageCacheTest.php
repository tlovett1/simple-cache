<?php

class FileBasedPageCacheTest extends \PHPUnit_Framework_TestCase {
	/**
	 * Set up with WP_Mock
	 *
	 * @since  1.6
	 */
	public function setUp() {
		\WP_Mock::setUp();
	}

	/**
	 * Tear down with WP_Mock
	 *
	 * @since  1.6
	 */
	public function tearDown() {
		\WP_Mock::tearDown();
	}

	/**
	 * Test url exception matching
	 *
	 * @since  1.6
	 */
	public function test_url_exception_match() {
		// Test simple correct url path
		$_SERVER['REQUEST_URI'] = '/test/url';
		$exception = '/test/url';

		$this->assertTrue( sc_url_exception_match( $exception ) );
		$_SERVER = [];

		// Test simple incorrect url path

		$_SERVER['REQUEST_URI'] = '/test/url';
		$exception = '/tesat/url';

		$this->assertFalse( sc_url_exception_match( $exception ) );
		$_SERVER = [];

		// Test that trailing slash doesnt matter with path

		$_SERVER['REQUEST_URI'] = '/test/url';
		$exception = '/test/url/';

		$this->assertTrue( sc_url_exception_match( $exception ) );
		$_SERVER = [];

		// Test full url exception

		$_SERVER['REQUEST_URI'] = '/test/url';
		$exception = 'http://test.com/test/url';
		$_SERVER['HTTP_HOST'] = 'test.com';

		$this->assertTrue( sc_url_exception_match( $exception ) );
		$_SERVER = [];

		// Test SSL url counts

		$_SERVER['REQUEST_URI'] = '/test/url';
		$exception = 'https://test.com/test/url';
		$_SERVER['HTTP_HOST'] = 'test.com';

		$this->assertFalse( sc_url_exception_match( $exception ) );
		$_SERVER = [];

		// Test correct ssl url

		$_SERVER['REQUEST_URI'] = '/test/url';
		$exception = 'https://test.com/test/url';
		$_SERVER['HTTP_HOST'] = 'test.com';
		$_SERVER['HTTPS'] = true;

		$this->assertTrue( sc_url_exception_match( $exception ) );
		$_SERVER = [];

		// Test good wildcard path

		$_SERVER['REQUEST_URI'] = '/test/url';
		$exception = '/test/*';

		$this->assertTrue( sc_url_exception_match( $exception ) );
		$_SERVER = [];

		// Test good wildcard path

		$_SERVER['REQUEST_URI'] = '/test';
		$exception = '/test/*';

		$this->assertTrue( sc_url_exception_match( $exception ) );
		$_SERVER = [];

		// Test bad wildcard path

		$_SERVER['REQUEST_URI'] = '/sdf/sdfsdf';
		$exception = '/test/*';

		$this->assertFalse( sc_url_exception_match( $exception ) );
		$_SERVER = [];

		// Test bad wildcard path with required trailing slash

		$_SERVER['REQUEST_URI'] = '/tester';
		$exception = '/test/*';

		$this->assertTrue( sc_url_exception_match( $exception ) );
		$_SERVER = [];

		// Test good wildcard path

		$_SERVER['REQUEST_URI'] = '/tester/here';
		$exception = '/test*';

		$this->assertTrue( sc_url_exception_match( $exception ) );
		$_SERVER = [];

		// Test full url exception

		$_SERVER['REQUEST_URI'] = '/test/url';
		$exception = 'http://test.com/test/*';
		$_SERVER['HTTP_HOST'] = 'test.com';

		$this->assertTrue( sc_url_exception_match( $exception ) );
		$_SERVER = [];
	}

	/**
	 * Test url exception matching with regex
	 *
	 * @since  1.6
	 */
	public function test_url_exception_match_regex() {
		// Test simple correct url path
		$_SERVER['REQUEST_URI'] = '/test/url';
		$exception = '/[a-z]+/[a-z]+';

		$this->assertTrue( sc_url_exception_match( $exception, true ) );
		$_SERVER = [];

		// Test simple incorrect url path
		$_SERVER['REQUEST_URI'] = '/test/url';
		$exception = '/[a-z]+/[0-9]+';

		$this->assertFalse( sc_url_exception_match( $exception, true ) );
		$_SERVER = [];

		// Test full url exception
		$_SERVER['REQUEST_URI'] = '/test/url';
		$exception = 'https?://test\.com/[a-z]+/[a-z]+';
		$_SERVER['HTTP_HOST'] = 'test.com';

		$this->assertTrue( sc_url_exception_match( $exception, true ) );
		$_SERVER = [];
	}

}
