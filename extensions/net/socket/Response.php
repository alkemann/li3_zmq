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

	protected $_route;
	protected $_model;

	/**
	 * Classes used by Response.
	 *
	 * @var array
	 */
	protected $_classes = array(
		'router' => 'li3_zmq\net\socket\Router'
	);

	public function __construct(Route $route, $model = 'lithium\data\Model') {
		$this->_route = $route;
		$this->_model = $model;
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
	}

	/**
	 * Remove the record specified by the location param of the request string
	 *
	 * @return array
	 */
	public function delete() {
	}

	/**
	 * Add $this->_route->post to resource data array
	 *
	 * @return array
	 */
	public function post() {
	}

	/**
	 * Retrive the specified data from $data
	 *
	 * $return array asked for data in a container
	 */
	public function get() {
		$pk = $this->_route->location;
		$query = $this->_route->query;;


		$model = $this->_model;
		\lithium\core\Libraries::load($model);

		if ($pk) {
			$container = $this->container('Entity');
			$conditions = $query + array($model::meta('name') . '.id' => $pk); // @todo . $model->key());
			$finder = 'first';
		} else {
			$container = $this->container('Collection');
			$conditions = $query;
			$finder = 'all';
		}
		$collection = $model::find($finder, compact('conditions'));
		$container['data'] = $collection->to('array');

		if ($container['type'] == 'Collection') {
			$container['count'] = $container['total'] = $collection->count();
		}

		return $container;
	}

	/**
	 * Create a json container for data
	 *
	 * @param mixed $data
	 * @return array
	 */
	public function container($type = 'Collection') {
		$container = array(
			'name' => 'Container',
			'version' => 1,
			'resource' => $this->_route->resource,
			'data' => false
		);
		if ($type === 'Entity') {
			$container['type'] = 'entity';
		} else {
			$container += array(
				'count' => 0,
				'total' => 0,
				'type' => 'Collection',
				'data' => false
			);
		}
		return $container;
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
