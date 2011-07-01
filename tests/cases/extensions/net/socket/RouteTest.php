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

	ds('echo', true);

class RouteTest extends \lithium\test\Unit {

	public function testGet() {
		$route = new Route();
		$expected = 'get';
		$result = $route->type;
		$this->assertEqual($expected, $result);
	}

	public function testExport() {
		$route = new Route();
		$expected = array('type' => 'get');
		$result = $route->export();
		$this->assertEqual($expected, $result);
	}

	public function testParse() {
		$route = new Route();
		$route->parse('get/posts');
		$expected = 'get';
		$result = $route->type;
		$this->assertEqual($expected, $result);
		$expected = 'posts';
		$result = $route->resource;
		$this->assertEqual($expected, $result);

		$expected = array('type' => 'get', 'resource' => 'posts', 'location' => '11');
		$result = $route->parse('get/posts/11')->export();
		$this->assertEqual($expected, $result);

		$expected = array('type' => 'get', 'resource' => 'posts', 'query' => array('admin' => '1'));
		$result = $route->parse('get/posts/?admin=1')->export();
		$this->assertEqual($expected, $result);
		
		$expected = array('type' => 'post', 'resource' => 'posts', 'post' => array('name' => 'ui'));
		$result = $route->parse('post/posts/{"name":"ui"}')->export();
		$this->assertEqual($expected, $result);

		$expected = array('type' => 'put', 'resource' => 'posts', 'location' => '11', 'post' => array('name' => 'ui'));
		$result = $route->parse('put/posts/11/{"name":"ui"}')->export();
		$this->assertEqual($expected, $result);
	}

	public function testToString() {
		$route = new Route();
		
		foreach (array(
				'get/posts/11',
				'delete/users/11',
				'post/users/{"username":"alkemann","email":"alek@example.org"}',
				'put/users/11/{"username":"alkemann","email":"alek@example.org"}',
				'get/comments',
				'register/1234',
				'ping'
			) as $s) {
			$result = $route->parse($s) . '';
			$this->assertEqual($s, $result);
		}
	}

}
