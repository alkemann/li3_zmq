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
 * Mocks ZMQ to allow for testing without ZMQ binding installed
 */
class MockZeromq extends \li3_zmq\extensions\data\source\Zeromq {

	private $__custom = array(
		'delete/posts/12' => 'true',
		'delete/posts/14' => 'false',
	);
	private $msg = '';

	/**
	 * Send $msgs over socket connection
	 *
	 * @param mixed $msgs
	 */
	public function send($msg) {
		$this->msg = $msg;
		return $this;
	}

	/**
	 * Recieve from socket connection
	 *
	 * @return string
	 */
	public function recv() {
		$msg = $this->msg;
		return (isset($this->__custom[$msg]) ? $this->__custom[$msg] : $msg);
	}
}