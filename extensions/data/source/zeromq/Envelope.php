<?php
/**
 * guic! hub proof of concept
 *
 * @copyright Redpill-Linpro AS
 */

namespace li3_zmq\extensions\data\source\zeromq;

/**
 * Package for zmq messages that contain identity and
 * return address
 */
class Envelope {

	public $address;
	public $return = null;
	public $content;

	/**
	 * Stringify object as a debug aid
	 *
	 * @return type
	 */
	public function __toString() {
		return 'adr['. substr(hash('sha256', $this->address), 10, 20).
			'] return['. $this->return.
			'] content['. $this->content.']'.\PHP_EOL;
	}

	/**
	 * Send the envelope, with address, content
	 * and, if present, return identification
	 *
	 * @param ZMQSocket $socket
	 */
	public function send($socket) {
		$socket->send($this->address, \ZMQ::MODE_SNDMORE);
		$socket->send('', \ZMQ::MODE_SNDMORE);
		if (!empty ($this->return)) {
			$socket->send($this->return, \ZMQ::MODE_SNDMORE);
		}
		$socket->send($this->content);
	}

	private function recvit($socket) {
		$parts = array();
		while (true) {
			$parts[] = $socket->recv();
			if (!$socket->getSockOpt(\ZMQ::SOCKOPT_RCVMORE)) {
				break;
			}
		}
		return $parts;
	}

	/**
	 * Recieve multiparts on the supplied socket,
	 * the parts will be unwrapped into the envelope,
	 * populating content, address, and if present,
	 * return identification.
	 *
	 * @param ZMQSocket $socket
	 */
	public function recv($socket) {
		$message_parts = $this->recvit($socket); //$socket->recvMulti();
		$this->address = \array_shift($message_parts);
		$this->content = \array_pop($message_parts);
		if (!empty ($message_parts)) {
			$this->return = \array_pop($message_parts);
			if ($this->return === '') {
				unset($this->return);
			}
		}
	}
}
