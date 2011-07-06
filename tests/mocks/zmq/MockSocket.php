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
 * Mocks ZMQSocket to allow for testing without ZMQ binding installed
 */
class MockSocket {

	public function connect($adr) {
		$this->connected_to = $adr;
		return true;
	}

	public function bind($adr) {
		$this->connected_to = $adr;
		return true;
	}

	public function send($msg, $options = null) {
		$this->msg = $msg;
	}

	public function recv() {
		$msg = $this->msg;
		unset($this->msg);
		return $msg;
	}
}
