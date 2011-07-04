<?php
/**
 * Li3_Zmq - ZeroMQ plugin for Lithium
 *
 * @package       li3_zmq
 * @copyright     Copyright 2011, Redpill-Linpro (http://redpill-linpro.com)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_zmq\extensions\net\socket;

/**
 * Hold and prepare response
 */
class Response extends \lithium\action\Response {

	protected $_route = null;

	//tmp
	protected $data = array(
		'users' => array(
			13 => array(
				'id' => 13,
				'username' => 'viggo',
				'email' => 'viggo@example.org',
				'admin' => "true"
			),
			14 => array(
				'id' => 14,
				'username' => 'strind',
				'email' => 'strind@example.org',
				'admin' => "false"
			),
		)
	);

	/**
	 * Classes used by Response.
	 *
	 * @var array
	 */
	protected $_classes = array(
		'router' => 'li3_zmq\net\socket\Router',
	//	'media' => 'lithium\net\http\Media'
	);

	public function __construct(Route $route) {
		$this->_route = $route;
	}

	/**
	 * Run the request and return the result
	 *
	 * @return mixed
	 */
	public function request() {
		switch ($this->_route->type) {
			case 'put':
				return $this->put();
				break;
			case 'delete':
				return $this->delete();
				break;
			case 'post':
				return $this->post();
				break;
			case 'get':
				return $this->get();
				break;
			default:
				throw new \Exception('Method doesnt exist');
		}
	}

	/**
	 * Update entity record with the posted data
	 * changing the fields specified, keeping rest
	 *
	 * @return array
	 */
	public function put() {
		if (!isset($this->data[$this->_route->resource][$this->_route->location])) return null;
		$data = & $this->data[$this->_route->resource][$this->_route->location];
		foreach ($this->_route->post as $field => $value) {
			$data[$field] = $value;
		}
		return $data;
	}

	/**
	 * Remove the record specified by the location param of the request string
	 *
	 * @return array
	 */
	public function delete() {
		if (!isset($this->data[$this->_route->resource][$this->_route->location])) return null;
		$data = $this->data[$this->_route->resource][$this->_route->location];
		unset($this->data[$this->_route->resource][$this->_route->location]);
		return $data;
	}

	/**
	 * Add $this->_route->post to resource data array
	 *
	 * @return array
	 */
	public function post() {
		$this->data[$this->_route->resource][$this->_route->post['id']] = $this->_route->post;
		return $this->_route->post;
	}

	/**
	 * Retrive the specified data from $data
	 *
	 * $return array asked for data in a container
	 */
	public function get() {
		$container = $this->container();
		$result = null;
		if (!empty($this->_route->location)) {
			if (isset($this->data[$this->_route->resource][$this->_route->location])) {
				$result['type'] = 'Entity';
				$row = $this->data[$this->_route->resource][$this->_route->location];
				$match = true;
				foreach ($this->_route->query as $field => $value) {
					$match = ($row[$field] === $value) && $match;
				}
				if ($match) {
					$result['data'] = $row;
				}
			}
		} else {
			if (isset($this->data[$this->_route->resource])) {
				$result['total'] = count($this->data[$this->_route->resource]);
				$result['type'] = 'Collection';
				if (empty($this->_route->query)) {
					$result['data'] = $this->data[$this->_route->resource];
				} else {
					$result['data'] = array();
					foreach ($this->data[$this->_route->resource] as $key => $row) {
						$match = true;
						foreach ($this->_route->query as $field => $value) {
							$match = ($row[$field] === $value) && $match;
						}
						if ($match) {
							$result['data'][$key] = $row;
						}
					}
				}
				$result['count'] = count($result['data']);
			}
		}
		return $result;
	}

	/**
	 * Create a json container for data
	 *
	 * @param mixed $data
	 * @return array
	 */
	public function container($data = false) {
		return array(
			'name' => 'Container',
			'version' => 1,
			'resource' => $this->_route->resource,
			'count' => 0,
			'total' => 0,
			'type' => 'Collection',
			'data' => $data
		);
	}

	/**
	 * Merge two containers together
	 *
	 * @param array $one
	 * @param array $two
	 * @return array
	 */
	public function merge(array $one, array $two) {
		$ret = $one;
		$ret['count'] += $two['count'];
		$ret['total'] += $two['total'];
		$ret['data'] += $two['data'];
		return $ret;
	}

}
