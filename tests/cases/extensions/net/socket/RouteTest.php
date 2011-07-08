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

/**
 * Test li3_zmq\extensions\net\socket\Route
 */
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
		$result = $route->parse('get/posts?admin=1')->export();
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
				'get/posts?admin=1',
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

	public function testGenerateGetAll() {
		$route = new Route();

		$query = new \lithium\data\model\Query(array(
			'type'	=> 'read',
			'model' => '\li3_zmq\tests\mocks\models\Posts',
		));
		$expected = 'get/posts';
		$result = $route->generate($query)->__toString();
		$this->assertEqual($expected, $result);
	}

	public function testGenerateGetAllWithQuery() {
		$route = new Route();

		$query = new \lithium\data\model\Query(array(
			'type'       => 'read',
			'conditions' => array('public' => 1),
			'model'      => '\li3_zmq\tests\mocks\models\Posts',
		));
		$expected = 'get/posts?public=1';
		$result = $route->generate($query)->__toString();
		$this->assertEqual($expected, $result);
	}

	public function testGenerateGetOne() {
		$route = new Route();

		$query = new \lithium\data\model\Query(array(
			'type'       => 'read',
			'conditions' => array('_id' => 2),
			'model'      => '\li3_zmq\tests\mocks\models\Posts'
		));
		$expected = 'get/posts/2';
		$result = $route->generate($query)->__toString();
		$this->assertEqual($expected, $result);
	}

	public function testGenerateGetOneWithQuery() {
		$route = new Route();

		$query = new \lithium\data\model\Query(array(
			'type'       => 'read',
			'model'      => '\li3_zmq\tests\mocks\models\Posts'
		));
		$options = array(
			'conditions' => array('_id' => 2, 'public' => 0)
		);

		$expected = 'get/posts/2?public=0';
		$result = $route->generate($query, $options)->__toString();
		$this->assertEqual($expected, $result);
	}

	public function testGeneratePost() {
		$route = new Route();

		$query = new \lithium\data\model\Query(array(
			'type'       => 'create',
			'model'      => '\li3_zmq\tests\mocks\models\Posts',
			'data'      => array('title' => 'Go there', 'public' => 0)
		));
		$expected = 'post/posts/{"title":"Go there","public":0}';
		$result = $route->generate($query)->__toString();
		$this->assertEqual($expected, $result);
	}

	public function testGeneratePut() {
		$route = new Route();

		$query = new \lithium\data\model\Query(array(
			'type'       => 'update',
			'conditions' => array('_id' => 2),
			'limit'      => 1,
			'model'      => '\li3_zmq\tests\mocks\models\Posts',
			'data'      => array('title' => 'Go updated', 'public' => 1)
		));
		$expected = 'put/posts/2/{"title":"Go updated","public":1}';
		$result = $route->generate($query)->__toString();
		$this->assertEqual($expected, $result);
	}

	public function testGenerateDelete() {
		$route = new Route();

		$query = new \lithium\data\model\Query(array(
			'type'       => 'delete',
			'conditions' => array('_id' => 2),
			'model'      => '\li3_zmq\tests\mocks\models\Posts'
		));

		$expected = 'delete/posts/2';
		$result = $route->generate($query)->__toString();
		$this->assertEqual($expected, $result);
	}

	public function testGenerateDeleteWithQuery() {
		$route = new Route();

		$query = new \lithium\data\model\Query(array(
			'type'       => 'delete',
			'conditions' => array('_id' => 2, 'public' => 0),
			'model'      => '\li3_zmq\tests\mocks\models\Posts'
		));

		$expected = 'delete/posts/2?public=0';
		$result = $route->generate($query)->__toString();
		$this->assertEqual($expected, $result);
	}

	public function testQueries() {
		$route = new Route();

		$route->parse('get/users?username=alek');
		$expected = array('username' => 'alek');
		$result = $route->query;
		$this->assertEqual($expected, $result);

		$route->parse('get/users?username=alek&password=abd008dda1e0b78125d11f0ed054710f');
		$expected = array('username' => 'alek', 'password' => 'abd008dda1e0b78125d11f0ed054710f');
		$result = $route->query;
		$this->assertEqual($expected, $result);
	}
}
