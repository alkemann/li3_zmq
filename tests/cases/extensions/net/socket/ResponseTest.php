<?php

namespace li3_zmq\tests\cases\extensions\net\socket;

use lithium\data\Connections;
use li3_zmq\extensions\net\socket\Response;
use li3_zmq\extensions\net\socket\Router;
use li3_zmq\tests\mocks\models\Posts;

class ResponseTest extends \lithium\test\Unit {

	public function testGetOne() {
		$response = new Response(Router::parse('get/posts/1'), '\li3_zmq\tests\mocks\models\Posts');
		$expected = array('id' => 2, 'title' => 'Blue');
		$result = $response->request();
		$this->assertEqual('Container', $result['name']);
		$this->assertEqual('Entity', $result['type']);
		$this->assertEqual($expected, $result['data']);
	}

	public function testGetAll() {
		$response = new Response(Router::parse('get/posts'), '\li3_zmq\tests\mocks\models\Posts');
		$result = $response->request();
		$this->assertEqual('Container', $result['name']);
		$this->assertEqual('Collection', $result['type']);
		$this->assertEqual(3, $result['count']);
		$this->assertEqual('Mountain', $result['data'][2]['title']);
	}

	public function testPost() {
		$response = new Response(Router::parse('post/posts/{"title":"tittelen"}'), '\li3_zmq\tests\mocks\models\Posts');
		$result = $response->request();
		$expected = array('id' => 4, 'title' => 'tittelen');
		$this->assertEqual($expected, $result['data']);
	}

	public function testPostValidateFail() {
		$response = new Response(Router::parse('post/posts/{"title":"titt#elen"}'), '\li3_zmq\tests\mocks\models\Posts');
		$result = $response->request();
		$this->assertFalse($result['data']);
		$expected = array('title' => array('Only Alphanumeric'));
		$this->assertEqual($expected, $result['errors']);
	}

	public function testPut() {
		$response = new Response(Router::parse('put/posts/2/{"title":"EDIT"}'), '\li3_zmq\tests\mocks\models\Posts');
		$result = $response->request();
		$expected = array('id' => 2, 'title' => 'EDIT');
		$this->assertEqual($expected, $result['data']);
	}

	public function testDelete() {
		$response = new Response(Router::parse('delete/posts/2'), '\li3_zmq\tests\mocks\models\Posts');
		$result = $response->request();
		$expected = array('id' => 2, 'title' => 'Blue');
		$this->assertEqual($expected, $result['data']);
	}

}
