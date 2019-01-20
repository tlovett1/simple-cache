<?php
/**
 * Memcached drop in (Uses Memcached extension)
 *
 * Original code from http://wordpress.org/extend/plugins/memcached-redux/ by Scott
 * Taylor - uses code from Ryan Boren, Denis de Bernardy, Matt Martz, Mike Schroder, Mika Epstein
 *
 * @package simple-cache
 */

// phpcs:ignoreFile

if ( ! class_exists( 'Memcached' ) ) {
	return;
}

if ( ! defined( 'WP_CACHE_KEY_SALT' ) ) {
	define( 'WP_CACHE_KEY_SALT', '' );
}

function wp_cache_add( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;

	return $wp_object_cache->add( $key, $data, $group, $expire );
}

function wp_cache_incr( $key, $n = 1, $group = '' ) {
	global $wp_object_cache;

	return $wp_object_cache->incr( $key, $n, $group );
}

function wp_cache_decr( $key, $n = 1, $group = '' ) {
	global $wp_object_cache;

	return $wp_object_cache->decr( $key, $n, $group );
}

function wp_cache_close() {
	global $wp_object_cache;

	return $wp_object_cache->close();
}

function wp_cache_delete( $key, $group = '' ) {
	global $wp_object_cache;

	return $wp_object_cache->delete( $key, $group );
}

function wp_cache_flush() {
	global $wp_object_cache;

	return $wp_object_cache->flush();
}

function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
	global $wp_object_cache;

	return $wp_object_cache->get( $key, $group, $force, $found );
}

/**
 * $keys_and_groups = array(
 *      array( 'key', 'group' ),
 *      array( 'key', '' ),
 *      array( 'key', 'group' ),
 *      array( 'key' )
 * );
 *
 */
function wp_cache_get_multi( $key_and_groups, $bucket = 'default' ) {
	global $wp_object_cache;

	return $wp_object_cache->get_multi( $key_and_groups, $bucket );
}

/**
 * $items = array(
 *      array( 'key', 'data', 'group' ),
 *      array( 'key', 'data' )
 * );
 *
 */
function wp_cache_set_multi( $items, $expire = 0, $group = 'default' ) {
	global $wp_object_cache;

	return $wp_object_cache->set_multi( $items, $expire = 0, $group = 'default' );
}

function wp_cache_init() {
	global $wp_object_cache;

	$wp_object_cache = new WP_Object_Cache();
}

function wp_cache_replace( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;

	return $wp_object_cache->replace( $key, $data, $group, $expire );
}

function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;

	if ( defined( 'WP_INSTALLING' ) == false )
		return $wp_object_cache->set( $key, $data, $group, $expire );
	else
		return $wp_object_cache->delete( $key, $group );
}

function wp_cache_add_global_groups( $groups ) {
	global $wp_object_cache;

	$wp_object_cache->add_global_groups( $groups );
}

function wp_cache_add_non_persistent_groups( $groups ) {
	global $wp_object_cache;

	$wp_object_cache->add_non_persistent_groups( $groups );
}

class WP_Object_Cache {
	var $global_groups = array();

	var $no_mc_groups = array();

	var $cache = array();
	var $mc = array();
	var $stats = array();
	var $group_ops = array();

	var $cache_enabled = true;
	var $default_expiration = 0;

	function add( $id, $data, $group = 'default', $expire = 0 ) {
		$key = $this->key( $id, $group );

		if ( is_object( $data ) )
			$data = clone $data;

		if ( in_array( $group, $this->no_mc_groups ) ) {
			$this->cache[$key] = $data;
			return true;
		} elseif ( isset( $this->cache[$key] ) && $this->cache[$key] !== false ) {
			return false;
		}

		$mc =& $this->get_mc( $group );
		$expire = ( $expire == 0) ? $this->default_expiration : $expire;
		$result = $mc->add( $key, $data, $expire );

		if ( false !== $result ) {
			++$this->stats['add'];
			$this->group_ops[$group][] = "add $id";
			$this->cache[$key] = $data;
		}

		return $result;
	}

	function add_global_groups( $groups ) {
		if ( ! is_array( $groups ) )
			$groups = (array) $groups;

		$this->global_groups = array_merge( $this->global_groups, $groups );
		$this->global_groups = array_unique( $this->global_groups );
	}

	function add_non_persistent_groups( $groups ) {
		if ( ! is_array( $groups ) )
			$groups = (array) $groups;

		$this->no_mc_groups = array_merge( $this->no_mc_groups, $groups );
		$this->no_mc_groups = array_unique( $this->no_mc_groups );
	}

	function incr( $id, $n = 1, $group = 'default' ) {
		$key = $this->key( $id, $group );
		$mc =& $this->get_mc( $group );
		$this->cache[ $key ] = $mc->increment( $key, $n );
		return $this->cache[ $key ];
	}

	function decr( $id, $n = 1, $group = 'default' ) {
		$key = $this->key( $id, $group );
		$mc =& $this->get_mc( $group );
		$this->cache[ $key ] = $mc->decrement( $key, $n );
		return $this->cache[ $key ];
	}

	function close() {
		// Silence is Golden.
	}

	function delete( $id, $group = 'default' ) {
		$key = $this->key( $id, $group );

		if ( in_array( $group, $this->no_mc_groups ) ) {
			unset( $this->cache[$key] );
			return true;
		}

		$mc =& $this->get_mc( $group );

		$result = $mc->delete( $key );

		if ( false !== $result ) {
			++$this->stats['delete'];
			$this->group_ops[$group][] = "delete $id";
			unset( $this->cache[$key] );
		}

		return $result;
	}

	function flush() {
		// Don't flush if multi-blog.
		if ( function_exists( 'is_site_admin' ) || defined( 'CUSTOM_USER_TABLE' ) && defined( 'CUSTOM_USER_META_TABLE' ) )
			return true;

		$ret = true;
		foreach ( array_keys( $this->mc ) as $group )
			$ret &= $this->mc[$group]->flush();
		return $ret;
	}

	function get( $id, $group = 'default', $force = false, &$found = null ) {
		$key = $this->key( $id, $group );
		$mc =& $this->get_mc( $group );
		$found = false;

		if ( isset( $this->cache[$key] ) && ( !$force || in_array( $group, $this->no_mc_groups ) ) ) {
			$found = true;
			if ( is_object( $this->cache[$key] ) )
				$value = clone $this->cache[$key];
			else
				$value = $this->cache[$key];
		} else if ( in_array( $group, $this->no_mc_groups ) ) {
			$this->cache[$key] = $value = false;
		} else {
			$value = $mc->get( $key );
			if ( empty( $value ) || ( is_integer( $value ) && -1 == $value ) ){
				$value = false;
				$found = $mc->getResultCode() !== Memcached::RES_NOTFOUND;
			} else {
				$found = true;
			}
			$this->cache[$key] = $value;
		}

		if ( $found ) {
			++$this->stats['get'];
			$this->group_ops[$group][] = "get $id";
		} else {
			++$this->stats['miss'];
		}

		if ( 'checkthedatabaseplease' === $value ) {
			unset( $this->cache[$key] );
			$value = false;
		}

		return $value;
	}

	function get_multi( $keys, $group = 'default' ) {
		$return = array();
		$gets = array();
		foreach ( $keys as $i => $values ) {
			$mc =& $this->get_mc( $group );
			$values = (array) $values;
			if ( empty( $values[1] ) )
				$values[1] = 'default';

			list( $id, $group ) = (array) $values;
			$key = $this->key( $id, $group );

			if ( isset( $this->cache[$key] ) ) {

				if ( is_object( $this->cache[$key] ) )
					$return[$key] = clone $this->cache[$key];
				else
					$return[$key] = $this->cache[$key];

			} else if ( in_array( $group, $this->no_mc_groups ) ) {
				$return[$key] = false;

			} else {
				$gets[$key] = $key;
			}
		}

		if ( !empty( $gets ) ) {
			$results = $mc->getMulti( $gets, $null, Memcached::GET_PRESERVE_ORDER );
			$joined = array_combine( array_keys( $gets ), array_values( $results ) );
			$return = array_merge( $return, $joined );
		}

		++$this->stats['get_multi'];
		$this->group_ops[$group][] = "get_multi $id";
		$this->cache = array_merge( $this->cache, $return );
		return array_values( $return );
	}

	function key( $key, $group ) {
		if ( empty( $group ) )
			$group = 'default';

		if ( false !== array_search( $group, $this->global_groups ) )
			$prefix = $this->global_prefix;
		else
			$prefix = $this->blog_prefix;

		return preg_replace( '/\s+/', '', WP_CACHE_KEY_SALT . "$prefix$group:$key" );
	}

	function replace( $id, $data, $group = 'default', $expire = 0 ) {
		$key = $this->key( $id, $group );
		$expire = ( $expire == 0) ? $this->default_expiration : $expire;
		$mc =& $this->get_mc( $group );

		if ( is_object( $data ) )
			$data = clone $data;

		$result = $mc->replace( $key, $data, $expire );
		if ( false !== $result )
			$this->cache[$key] = $data;
		return $result;
	}

	function set( $id, $data, $group = 'default', $expire = 0 ) {
		$key = $this->key( $id, $group );
		if ( isset( $this->cache[$key] ) && ( 'checkthedatabaseplease' === $this->cache[$key] ) )
			return false;

		if ( is_object( $data) )
			$data = clone $data;

		$this->cache[$key] = $data;

		if ( in_array( $group, $this->no_mc_groups ) )
			return true;

		$expire = ( $expire == 0 ) ? $this->default_expiration : $expire;
		$mc =& $this->get_mc( $group );
		$result = $mc->set( $key, $data, $expire );

		return $result;
	}

	function set_multi( $items, $expire = 0, $group = 'default' ) {
		$sets = array();
		$mc =& $this->get_mc( $group );
		$expire = ( $expire == 0 ) ? $this->default_expiration : $expire;

		foreach ( $items as $i => $item ) {
			if ( empty( $item[2] ) )
				$item[2] = 'default';

			list( $id, $data, $group ) = $item;

			$key = $this->key( $id, $group );
			if ( isset( $this->cache[$key] ) && ( 'checkthedatabaseplease' === $this->cache[$key] ) )
				continue;

			if ( is_object( $data) )
				$data = clone $data;

			$this->cache[$key] = $data;

			if ( in_array( $group, $this->no_mc_groups ) )
				continue;

			$sets[$key] = $data;
		}

		if ( !empty( $sets ) )
			$mc->setMulti( $sets, $expire );
	}

	function colorize_debug_line( $line ) {
		$colors = array(
			'get'   => 'green',
			'set'   => 'purple',
			'add'   => 'blue',
			'delete'=> 'red'
		);

		$cmd = substr( $line, 0, strpos( $line, ' ' ) );

		$cmd2 = "<span style='color:{$colors[$cmd]}'>$cmd</span>";

		return $cmd2 . substr( $line, strlen( $cmd ) ) . "\n";
	}

	function stats() {
		echo "<p>\n";
		foreach ( $this->stats as $stat => $n ) {
			echo "<strong>$stat</strong> $n";
			echo "<br/>\n";
		}
		echo "</p>\n";
		echo "<h3>Memcached:</h3>";
		foreach ( $this->group_ops as $group => $ops ) {
			if ( !isset( $_GET['debug_queries'] ) && 500 < count( $ops ) ) {
				$ops = array_slice( $ops, 0, 500 );
				echo "<big>Too many to show! <a href='" . add_query_arg( 'debug_queries', 'true' ) . "'>Show them anyway</a>.</big>\n";
			}
			echo "<h4>$group commands</h4>";
			echo "<pre>\n";
			$lines = array();
			foreach ( $ops as $op ) {
				$lines[] = $this->colorize_debug_line( $op );
			}
			print_r( $lines );
			echo "</pre>\n";
		}

		if ( !empty( $this->debug ) && $this->debug )
			var_dump( $this->memcache_debug );
	}

	function &get_mc( $group ) {
		if ( isset( $this->mc[$group] ) )
			return $this->mc[$group];
		return $this->mc['default'];
	}

	function __construct() {

		$this->stats = array(
			'get'        => 0,
			'get_multi'  => 0,
			'add'        => 0,
			'set'        => 0,
			'delete'     => 0,
			'miss'       => 0,
		);

		global $memcached_servers;

		if ( isset( $memcached_servers ) )
			$buckets = $memcached_servers;
		else
			$buckets = array( '127.0.0.1' );

		reset( $buckets );
		if ( is_int( key( $buckets ) ) )
			$buckets = array( 'default' => $buckets );

		foreach ( $buckets as $bucket => $servers ) {
			$this->mc[$bucket] = new Memcached();

			$instances = array();
			foreach ( $servers as $server ) {
				@list( $node, $port ) = explode( ':', $server );
				if ( empty( $port ) )
					$port = ini_get( 'memcache.default_port' );
				$port = intval( $port );
				if ( !$port )
					$port = 11211;

				$instances[] = array( $node, $port, 1 );
			}
			$this->mc[$bucket]->addServers( $instances );
		}

		global $blog_id, $table_prefix;
		$this->global_prefix = '';
		$this->blog_prefix = '';
		if ( function_exists( 'is_multisite' ) ) {
			$this->global_prefix = ( is_multisite() || defined( 'CUSTOM_USER_TABLE' ) && defined( 'CUSTOM_USER_META_TABLE' ) ) ? '' : $table_prefix;
			$this->blog_prefix = ( is_multisite() ? $blog_id : $table_prefix ) . ':';
		}

		$this->cache_hits =& $this->stats['get'];
		$this->cache_misses =& $this->stats['miss'];
	}
}
