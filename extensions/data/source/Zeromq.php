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

	/**
	 * Create a Socket connection to host specified in protocol. Auto called on construction
	 *
	 * @return Zeromq
	 */
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

	/**
	 * Get connection description or config of $key
	 *
	 * @param string $key
	 * @return string
	 */
	public function connected_to($key = null) {
		if ($key && $this->connection) {
			return $this->_config[$key];
		}
		return $this->connection ? $this->__connection : 'Not connected';
	}

	/**
	 * Get the fully namespaced model name tied to this connection
	 *
	 * @return string
	 */
	public function model() {
		return $this->_config['model'];
	}

	/**
	 * Send $msgs over socket connection
	 *
	 * @param mixed $msgs
	 */
	public function send($msgs) {
		$this->connection->send($msgs);
	}

	/**
	 * Recieve from socket connection
	 *
	 * @return string
	 */
	public function recv() {
		return $this->connection->recv();
	}

	/**
	 * Throw away socket connection
	 *
	 * @return boolean
	 */
	public function disconnect() {
		$this->connection = null;
		$this->__connection = null;
		return true;
	}

	/**
	 * Ask connection for list of entities
	 *
	 * @param null $class
	 * @return string
	 */
	public function sources($class = null) {
		$this->connection->send('entities');
		return $this->connection->recv();
	}

	/**
	 * Describe $entity
	 *
	 * @param string $entity
	 * @param array $meta
	 * @return string
	 */
	public function describe($entity, array $meta = array()) {
		$this->connection->send('describe/'.$entity);
		return $this->connection->recv();
	}

	public function relationship($class, $type, $name, array $options = array()) {
		throw new \Exception('Relationship: Not implemented for Zeromq datasource');
	}

	/**
	 * Send a CREATE to Zmq connection, returns entity or false
	 *
	 * @param \lithium\data\model\Query $query
	 * @param array $options
	 * @return type
	 */
	public function create($query, array $options = array()) {
		$request = Router::generate($query, $options);
		$this->send($request->__toString(), $request->sendOptions());
		$result = $this->recv();
		return $result;
	}

	/**
	 * Send a READ to Zmq connection, returns entity or false
	 *
	 * @param \lithium\data\model\Query $query
	 * @param array $options
	 * @return type
	 */
	public function read($query, array $options = array()) {
		$request = Router::generate($query, $options);
		$this->send($request->__toString(), $request->sendOptions());
		$result = $this->recv();
		return $result;
	}

	/**
	 * Send a UPDATE to Zmq connection, returns entity or false
	 *
	 * @param \lithium\data\model\Query $query
	 * @param array $options
	 * @return type
	 */
	public function update($query, array $options = array()) {
		$request = Router::generate($query, $options);
		$this->send($request->__toString(), $request->sendOptions());
		$result = $this->recv();
		return $result;
	}

	/**
	 * Send a DELETE to Zmq connection, returns entity or false
	 *
	 * @param \lithium\data\model\Query $query
	 * @param array $options
	 * @return type
	 */
	public function delete($query, array $options = array()) {
		$request = Router::generate($query, $options);
		$this->send($request->__toString(), $request->sendOptions());
		$result = $this->recv();
		return $result;
	}

}
