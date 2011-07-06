<?php
/**
 * Li3_Zmq - ZeroMQ plugin for Lithium
 *
 * @package       li3_zmq
 * @copyright     Copyright 2011, Redpill-Linpro (http://redpill-linpro.com)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_zmq\tests\mocks\zmq;

/**
 * Mocks ZMQContext to allow for testing without ZMQ binding installed
 */
class MockContext {

	public function getSocket($type, $persistent_id = null, $on_new_socket = null) {
		return new MockSocket($type);
	}

}
