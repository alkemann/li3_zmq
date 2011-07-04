<?php
/**
 * Li3_Zmq - ZeroMQ plugin for Lithium
 *
 * @package       li3_zmq
 * @copyright     Copyright 2011, Redpill-Linpro (http://redpill-linpro.com)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */


namespace li3_zmq\extensions\data\source;

/**
 * Config object for zero mq connections
 */
class Zeromq extends \lithium\data\Source {

	private $__context = null;
	private $__defaults = array('protocol'=>'tcp','port'=>'5555','host'=>'localhost','socket'=>\ZMQ::SOCKET_REQ);
	public $connection = null;
	private $__connection = '';

	public function __construct(array $config = array()) {
		parent::__construct($config + $this->__defaults);
		$this->__context = new \ZMQContext(1);
	}

	public function connect() {
		if (is_null($this->__context)) {
			$this->__context = new \ZMQContext(1);
		}
		$this->connection = new \ZMQSocket($this->__context, $this->_config['socket']);
		$this->__connection =
				$this->_config['protocol'].
				'://'.
				$this->_config['host'].
				':'.
				$this->_config['port'];
		switch ($this->_config['socket']) {
			case \ZMQ::SOCKET_REQ:
				$this->connection->connect($this->__connection);
				break;
			case \ZMQ::SOCKET_REP:
				$this->connection->bind($this->__connection);
				break;
			default:
				die('no socket connection set');
		}
		return $this;
	}

	public function connected_to($key = null) {
		if ($key) {
			return $this->_config[$key];
		}
		return $this->connection ? $this->__connection : 'Not connected';
	}

	public function send($msgs) {
		$this->connection->send($msgs);
		return $this->connection->recv();
	}

	public function recv() {
		return $this->connection->recv();
	}

	public function disconnect() {
		//return $this->connection->close();
	}

	public function sources($class = null) {
		return $this->connection->send('meta/sources');
	}

	public function describe($entity, array $meta = array()) {
		return null;
	}

	public function relationship($class, $type, $name, array $options = array()) {
		return null;
	}

	public function create($query, array $options = array()) {

	}

	public function read($query, array $options = array()) {

	}

	public function update($query, array $options = array()) {

	}

	public function delete($query, array $options = array()) {

	}

}