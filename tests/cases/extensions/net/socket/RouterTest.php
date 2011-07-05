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
use li3_zmq\extensions\net\socket\Router;

	ds('echo', true);

class RouterTest extends \lithium\test\Unit {

	public function testMethods() {
		$this->assertTrue(Router::get() instanceof Route);

		$expected = 'get/posts/1?admin=1';
		$result = Router::parse($expected)->__toString();
		$this->assertEqual($expected, $result);

		$expected = array('type' => 'get');
		$result = Router::reset()->export();
		$this->assertEqual($expected, $result);

		$expected = array('type' => 'get', 'resource' => 'posts', 'location' => '11');
		$result = Router::parse('get/posts/11')->export();
		$this->assertEqual($expected, $result);

		Router::reset();
		$query = new \lithium\data\model\Query(array(
			'type'	=> 'read',
			'model' => '\li3_zmq\tests\mocks\models\Post',
		));
		$options = array(
			'model' => '\li3_zmq\tests\mocks\models\Post'
		);

		$expected = 'get/posts';
		$result = Router::generate($query, $options)->__toString();
		$this->assertEqual($expected, $result);
	}

}
