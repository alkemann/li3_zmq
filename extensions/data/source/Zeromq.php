<?php
/**
 * Li3_Zmq - ZeroMQ plugin for Lithium
 *
 * @package       li3_zmq
 * @copyright     Copyright 2011, Redpill-Linpro (http://redpill-linpro.com)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_zmq\extensions\data\source;

use li3_zmq\extensions\net\socket\Router;

/**
 * Config object for zero mq connections
 */
class Zeromq extends \lithium\data\Source {

	protected $_classes = array(
		'entity' => 'lithium\data\entity\Document',
		'set' => 'lithium\data\collection\DocumentSet',
		'context' => 'ZMQContext',
		'socket' => 'ZMQSocket',
		'zmq' => 'ZMQ'
	);

	private $__context = null;
	private $__defaults = array(
		'protocol' => 'tcp',
		'port' => '5555',
		'host' => 'localhost',
		'socket' => 3, // === \ZMQ::SOCKET_REQ
		'model' => '\lithium\data\Model'
	);
	public $connection = null;
	private $__connection = '';

	public function __construct(array $config = array()) {
		$zmqClass = $this->__class('zmq');
		$contextClass = $this->__class('context');
		$default_socket = $zmqClass::SOCKET_REQ;
		$this->__context = new $contextClass(1);
		parent::__construct($config + $this->__defaults + array('socket' => $default_socket));
	}

	private function __class($name) { return '\\'.$this->_classes[$name]; }

	public function connect() {
		$zmqClass = $this->__class('zmq');
		$socketClass = $this->__class('socket');
		$this->connection = new $socketClass($this->__context, $this->_config['socket']);
		$this->__connection =
				$this->_config['protocol'].
				'://'.
				$this->_config['host'].
				':'.
				$this->_config['port'];
		switch ($this->_config['socket']) {
			case $zmqClass::SOCKET_REQ:
				$this->connection->connect($this->__connection);
				break;
			case $zmqClass::SOCKET_REP:
				$this->connection->bind($this->__connection);
				break;
			default:
				die('no socket connection set');
		}
		return $this;
	}

	public function connected_to($key = null) {
		if ($key && $this->connection) {
			return $this->_config[$key];
		}
		return $this->connection ? $this->__connection : 'Not connected';
	}

	public function model() {
		return $this->_config['model'];
	}

	public function send($msgs) {
		$this->connection->send($msgs);
	}

	public function recv() {
		return $this->connection->recv();
	}

	public function disconnect() {
		$this->connection = null;
		$this->__connection = null;
		return true;
	}

	public function sources($class = null) {
		$this->connection->send('entities');
		return $this->connection->recv();
	}

	public function describe($entity, array $meta = array()) {
		$this->connection->send('describe/'.$entity);
		return $this->connection->recv();
	}

	public function relationship($class, $type, $name, array $options = array()) {
		throw new \Exception('Relationship: Not implemented for Zeromq datasource');
	}

	public function create($query, array $options = array()) {
		$request = Router::generate($query, $options);
		$this->send($request->__toString(), $request->sendOptions());
		$result = $this->recv();
		return $result;
	}

	public function read($query, array $options = array()) {
		$request = Router::generate($query, $options);
		$this->send($request->__toString(), $request->sendOptions());
		$result = $this->recv();
		return $result;
	}

	public function update($query, array $options = array()) {
		$request = Router::generate($query, $options);
		$this->send($request->__toString(), $request->sendOptions());
		$result = $this->recv();
		return $result;

	}

	public function delete($query, array $options = array()) {
		$request = Router::generate($query, $options);
		$this->send($request->__toString(), $request->sendOptions());
		$result = $this->recv();
		return $result;

	}

}
