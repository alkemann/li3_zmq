<?php
/**
 * Li3_Zmq - ZeroMQ plugin for Lithium
 *
 * @package       li3_zmq
 * @copyright     Copyright 2011, Redpill-Linpro (http://redpill-linpro.com)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_zmq\extensions\net\socket;

/**
 * Static class to access Route object
 */
class Router extends \lithium\core\Object {

	protected static $_route = null;

	public static function get() {
		if (static::$_route == null) static::$_route = new \li3_zmq\extensions\net\socket\Route();
		return static::$_route;
	}

	public static function parse($request) {
		return static::get()->parse($request);
	}

	public static function reset() {
		static::$_route = new \li3_zmq\extensions\net\socket\Route();
		return static::$_route;
	}

	public static function generate($query, array $options = array()) {
		static::reset();
		return static::get()->generate($query, $options);
	}
}
