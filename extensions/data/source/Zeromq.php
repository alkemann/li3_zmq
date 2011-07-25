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
		'result' => 'li3_zmq\extensions\data\source\zeromq\Result',
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
		'model' => null
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

	protected function __class($name) { return '\\'.$this->_classes[$name]; }

	/**
	 * Change the port of the connection
	 * Use this to have multiple services on the same connection config
	 *
	 * @param type $port 
	 */
	public function change_port($port) { $this->_config['port'] = $port; }

	/**
	 * Create a Socket connection to host specified in protocol. Auto called on construction
	 *
	 * @return Zeromq
	 */
	public function connect() {
		$zmqClass = $this->__class('zmq');
		$socketClass = $this->__class('socket');
		$this->connection = new $socketClass($this->__context, $this->_config['socket']);
		try {
			switch ($this->_config['socket']) {
				case $zmqClass::SOCKET_REQ:
					$this->__connection =
							$this->_config['protocol'].
							'://'.
							$this->_config['host'].
							':'.
							$this->_config['port'];
					$this->connection->connect($this->__connection);
					break;
				case $zmqClass::SOCKET_REP:
					$this->__connection =
							$this->_config['protocol'].
							'://*:'.
							$this->_config['port'];
					$this->connection->bind($this->__connection);
					break;
				default:
					die('no socket connection set');
			}
		} catch(\ZMQSocketException $e) {
			throw new \ZMQSocketException($this->__connection . ' already in use', 98);
			die();
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
	public function model($resource) {
		if (!isset($this->_config['model'][$resource])) {
			throw new \Exception('Missing connection for resource : ' . $resource);
		}
		return $this->_config['model'][$resource];
	}

	/**
	 * Send $msgs over socket connection
	 *
	 * @param mixed $msgs
	 */
	public function send($msgs) {
		$this->connection->send($msgs);
		return $this;
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
		return array();
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
		$options += array('model' => $query->model());
		$params = compact('query', 'options');
		$config = $this->_config;
		return $this->_filter(__METHOD__, $params, function($self, $params) use ($config) {
			$query = $params['query'];
			$options = $params['options'];

			// Generate a Route object based on the query
			$request = Router::generate($query, $options);

			// Send 0MQ request
			$self->send($request->__toString(), $request->sendOptions());

			// Recieve 0MQ response
			$response = $self->recv();

			$resultClass = $self->invokeMethod('__class', array('result'));
			$result = new $resultClass(array('resource' => $response));

			// Get the data array out of the container result
			$data = $result->data();
			$errors = $result->errors();

			if (empty($data) && empty($errors)) {
				return false;
			}
			$opts = array('class' => 'entity', 'exists' => !empty($data));
			$res = $self->item($query->model(), $data, $opts);
			$res->errors($errors);
			return $res;
		});
	}

	/**
	 * Send a READ to Zmq connection, returns entity or false
	 *
	 * @param \lithium\data\model\Query $query
	 * @param array $options
	 * @return mixed
	 * @filter
	 */
	public function read($query, array $options = array()) {
		$options += array('model' => $query->model());
		$params = compact('query', 'options');
		$config = $this->_config;
		return $this->_filter(__METHOD__, $params, function($self, $params) use ($config) {
			$query = $params['query'];
			$options = $params['options'];

			// Generate a Route object based on the query
			$request = Router::generate($query, $options);

			// Send 0MQ request
			$self->send($request->__toString(), $request->sendOptions());

			// Recieve 0MQ response
			$response = $self->recv();

			$resultClass = $self->invokeMethod('__class', array('result'));
			$result = new $resultClass(array('resource' => $response));

			// Get the data array out of the container result
			$data = $result->data();

			if ($result->type() == 'Entity') {
				if (empty($data)) {
					return null;
				}
				$opts = array('class' => 'entity', 'exists' => true);
				return $self->item($query->model(), $data, $opts);
			}

			// Grab any meta data from container result
			$stats = $result->stats();

			$opts = compact('stats') + array('class' => 'set', 'exists' => true);
			return $self->item($query->model(), $data, $opts);
		});
	}

	/**
	 * Send a UPDATE to Zmq connection, returns entity or false
	 *
	 * @param \lithium\data\model\Query $query
	 * @param array $options
	 * @return type
	 */
	public function update($query, array $options = array()) {
		$options += array('model' => $query->model());
		$params = compact('query', 'options');
		$config = $this->_config;
		return $this->_filter(__METHOD__, $params, function($self, $params) use ($config) {
			$query = $params['query'];
			$options = $params['options'];

			// Generate a request and send it to 0mq
			$request = Router::generate($query, $options);
			$self->send($request->__toString(), $request->sendOptions());

			// Recieve response from 0MQ
			$response = $self->recv();

			// Generate result from response
			$resultClass = $self->invokeMethod('__class', array('result'));
			$result = new $resultClass(array('resource' => $response));

			$data = $result->data();
			$errors = $result->errors();

			if (empty($data) && empty($errors)) {
				return false;
			}

			$opts = array('class' => 'entity', 'exists' => true, 'errors' => $errors);
			return $self->item($query->model(), $data, $opts);
		});
	}

	/**
	 * Send a DELETE to Zmq connection, returns entity or false
	 *
	 * @param \lithium\data\model\Query $query
	 * @param array $options
	 * @return type
	 */
	public function delete($query, array $options = array()) {
		$options += array('model' => $query->model());
		$params = compact('query', 'options');
		$config = $this->_config;
		return $this->_filter(__METHOD__, $params, function($self, $params) use ($config) {
			$query = $params['query'];
			$options = $params['options'];

			$request = Router::generate($query, $options);

			// Send delete request over 0MQ
			$self->send($request->__toString(), $request->sendOptions());

			// Return response from 0MQ - expecting 'true' or 'false'
			$response = $self->recv();

			return json_decode($response);
		});
	}

}
