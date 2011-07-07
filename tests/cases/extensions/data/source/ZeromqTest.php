<?php
/**
 * Li3_Zmq - ZeroMQ plugin for Lithium
 *
 * @package       li3_zmq
 * @copyright     Copyright 2011, Redpill-Linpro (http://redpill-linpro.com)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_zmq\tests\cases\extensions\data\source;

use li3_zmq\extensions\data\source\Zeromq;
use lithium\data\Connections;
use li3_zmq\tests\mocks\zmq\MockZMQ;
use lithium\data\model\Query;

/**
 * Test li3_zmq\extensions\data\source\Zeromq
 */
class ZeromqTest extends \lithium\test\Unit {

	private $__classes = array(
		'result' => 'li3_zmq\tests\mocks\zmq\MockResult',
		'context' => 'li3_zmq\tests\mocks\zmq\MockContext',
		'socket' =>  'li3_zmq\tests\mocks\zmq\MockSocket',
		'zmq' => 'li3_zmq\tests\mocks\zmq\MockZMQ'
	);

	public function skip() {
		Connections::add('zmq-test', array(
			'type' => 'Zeromq',
			'model' => '\li3_zmq\tests\mocks\models\Posts',
			'classes' => $this->__classes
		));
		Connections::add('zmq-test-entity', array(
			'type' => 'Zeromq',
			'model' => '\li3_zmq\tests\mocks\models\Posts',
			'classes' => array(
				'result' => 'li3_zmq\tests\mocks\zmq\MockEntityResult',
			) + $this->__classes
		));
	}

	public function setUp() {
		Connections::get('zmq-test')->connect();
	}

	public function tearDown() {
		Connections::get('zmq-test')->disconnect();
	}

	public function testConnection() {
		$connection = Connections::get('zmq-test');
		$this->assertTrue($connection instanceof Zeromq);
		$socket = $connection->connection;
		$this->assertTrue($socket instanceof \li3_zmq\tests\mocks\zmq\MockSocket);

		$connection->disconnect();

		$this->assertNull($connection->connection);

		$connection->connect();

		$this->assertTrue($connection instanceof Zeromq);

		Connections::add('zmq-test-config', array(
			'type' => 'Zeromq',
			'socket' => MockZMQ::SOCKET_REP,
			'model' => '\li3_zmq\tests\mocks\models\Bananas',
			'protocol' => 'igm',
			'port' => '5959',
			'host' => 'example.org',
			'classes' => $this->__classes
		));
		$con2 = Connections::get('zmq-test-config');
		$expected = 'igm://example.org:5959';
		$result = $con2->connection->connected_to;
		$this->assertEqual($expected, $result);
	}

	public function testModel() {
		$con = Connections::get('zmq-test');

		$expected = '\li3_zmq\tests\mocks\models\Posts';
		$result = $con->model();
		$this->assertEqual($expected, $result);
	}

	public function testConfigConnectedTo() {
		$con = Connections::get('zmq-test');

		$expected = '5555';
		$result = $con->connected_to('port');
		$this->assertEqual($expected, $result);

		$expected = 'tcp://localhost:5555';
		$result = $con->connected_to();
		$this->assertEqual($expected, $result);
	}

	public function testConnectedToDisconnected() {
		$con = Connections::get('zmq-test');
		$con->disconnect();

		$expected = 'Not connected';
		$result = $con->connected_to('port');
		$this->assertEqual($expected, $result);
		$result = $con->connected_to();
		$this->assertEqual($expected, $result);
	}

	public function testSend() {
		$con = Connections::get('zmq-test');

		$expected ='PRESIDENT';
		$con->send($expected);
		$result = $con->connection->msg;
		$this->assertEqual($expected, $result);
	}

	public function testRecv() {
		$con = Connections::get('zmq-test');

		$con->connection->msg = $expected ='PRESIDENT';
		$result = $con->recv();
		$this->assertEqual($expected, $result);
	}

	public function testSendRecv() {
		$con = Connections::get('zmq-test');

		$expected ='PRESIDENT';
		$con->send($expected);
		$result = $con->recv();
		$this->assertEqual($expected, $result);
	}

	public function testSources() {
		$con = Connections::get('zmq-test');

		$expected = 'entities';
		$result = $con->sources();
		$this->assertEqual($expected, $result);
	}

	public function testDescribe() {
		$con = Connections::get('zmq-test');

		$expected = 'describe/users';
		$result = $con->describe('users');
		$this->assertEqual($expected, $result);
	}

	public function testReadFirst() {
		$con = Connections::get('zmq-test-entity');

		$query = new Query(array(
			'type'       => 'read',
			'conditions' => array('_id' => 2),
			'model'      => '\li3_zmq\tests\mocks\models\Posts',
			'limit'      => 1
		));
		$options = array(
			'limit' => 1,
			'conditions' => array('_id' => 2),
			'model' => '\li3_zmq\tests\mocks\models\Posts'
		);
		$result = $con->read($query, $options);
		$this->assertTrue($result instanceof \lithium\data\Entity);
		$expected = 'get/posts/2';
		$this->assertEqual($expected, $result->request);
	}

	public function testReadAll() {
		$con = Connections::get('zmq-test');

		$query = new Query(array(
			'type'       => 'read',
			'model'      => '\li3_zmq\tests\mocks\models\Posts',
		));
		$result = $con->read($query);
		$this->assertTrue($result instanceof \lithium\data\Collection);
		$expected = 'get/posts';
		$this->assertEqual($expected, $result->stats('request'));
	}

	public function testCreate() {
		$con = Connections::get('zmq-test-entity');

		$query = new Query(array(
			'type'       => 'create',
			'model'      => '\li3_zmq\tests\mocks\models\Posts',
			'data'      => array('title' => 'Go there', 'public' => 0)
		));

		$result = $con->create($query);
		$this->assertTrue($result instanceof \lithium\data\Entity);
		$this->assertTrue($result->exists());
		$expected = 'post/posts/{"title":"Go there","public":0}';
		$this->assertEqual($expected, $result->request);
	}

	public function testDelete() {
		$con = Connections::get('zmq-test-entity');

		$query = new Query(array(
			'type'       => 'delete',
			'model'      => '\li3_zmq\tests\mocks\models\Posts',
			'conditions' => array('_id' => 12)
		));
		$options = array('conditions' => array('_id' => 12));

		$result = $con->delete($query, $options);
		$this->assertIdentical(true, $result);
	}

	public function testDeleteFail() {
		$con = Connections::get('zmq-test-entity');

		$query = new Query(array(
			'type'       => 'delete',
			'model'      => '\li3_zmq\tests\mocks\models\Posts',
			'conditions' => array('_id' => 14)
		));
		$options = array('conditions' => array('_id' => 14));

		$result = $con->delete($query, $options);
		$this->assertIdentical(false, $result);
	}

}
