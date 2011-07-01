<?php
/**
 * Li3_Zmq - ZeroMQ plugin for Lithium
 *
 * @package       li3_zmq
 * @copyright     Copyright 2011, Redpill-Linpro (http://redpill-linpro.com)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_zmq\tests\cases\extensions\net\socket;

use li3_zmq\extensions\net\socket\Route;

class RouteTest extends \lithium\test\Unit {

	public function testParse() {
		$route = new Route();
		$route->parse('get/posts');
		$expected = 'get';
		$result = $route->type;
		$this->assertEqual($expected, $result);
		$expected = 'posts';
		$result = $route->resource;
		$this->assertEqual($expected, $result);
	}

}
