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

	/**
	 * Get a Route object
	 *
	 * @return \li3_zmq\extensions\net\socket\Route
	 */
	public static function get() {
		if (static::$_route == null) static::$_route = new \li3_zmq\extensions\net\socket\Route();
		return static::$_route;
	}

	/**
	 * Get a route object that has parsed $request
	 *
	 * @param string $request
	 * @return \li3_zmq\extensions\net\socket\Route
	 */
	public static function parse($request) {
		return static::get()->parse($request);
	}

	/**
	 * Reset the Route object, returning a fresh one
	 *
	 * @return \li3_zmq\extensions\net\socket\Route
	 */
	public static function reset() {
		static::$_route = new \li3_zmq\extensions\net\socket\Route();
		return static::$_route;
	}

	/**
	 * Create a Route object that maps a Query from the ZeroMQ datasource
	 *
	 * @param \lithium\data\model\Query $query
	 * @param array $options
	 * @return \li3_zmq\extensions\net\socket\Route
	 */
	public static function generate($query, array $options = array()) {
		static::reset();
		return static::get()->generate($query, $options);
	}
}
