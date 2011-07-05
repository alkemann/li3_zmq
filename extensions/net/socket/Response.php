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
		$pk = $this->_route->location;
		$post = $this->_route->post;

		$model = $this->__model();

		$conditions = array($model::key() => $pk);
		$entity = $model::find('first', compact('conditions'));

		$container = $this->container('Entity');

		if ($entity) {
			$entity->set($post);
			if ($entity->validates()) {
				$entity->save(null, array('validate' => false));
				$container['data'] = $entity->to('array');
			} else {
				$container['errors'] = $entity->errors();
			}
		}

		return $container;
	}

	/**
	 * Remove the record specified by the location param of the request string
	 *
	 * @return array
	 */
	public function delete() {
		$pk = $this->_route->location;

		$model = $this->__model();

		$conditions = array($model::key() => $pk);
		$entity = $model::find('first', compact('conditions'));

		$container = $this->container('Entity');
		if ($entity) {
			if (!$entity->delete()) {
				throw new \Exception('Delete failed!');
			}
			$container['data'] = $entity->to('array');
		}


		return $container;
	}

	/**
	 * Add $this->_route->post to resource data array
	 *
	 * @return array
	 */
	public function post() {
		$post = $this->_route->post;

		$model = $this->__model();

		$container = $this->container('Entity');

		$entity = $model::create($post);

		if ($entity->validates()) {
			$entity->save(null, array('validate' => false));
			$container['data'] = $entity->to('array');
		} else {
			$container['errors'] = $entity->errors();
		}

		return $container;
	}

	/**
	 * Retrive the specified data from $data
	 *
	 * $return array asked for data in a container
	 */
	public function get() {
		$pk = $this->_route->location;
		$query = $this->_route->query;;

		$model = $this->__model();

		if ($pk) {
			$container = $this->container('Entity');
			$conditions = $query + array($model::key() => $pk);
			$finder = 'first';
		} else {
			$container = $this->container('Collection');
			$conditions = $query;
			$finder = 'all';
		}
		$collection = $model::find($finder, compact('conditions'));
		if ($collection) {
			$container['data'] = $collection->to('array');
		}

		if ($collection && $container['type'] == 'Collection') {
			$container['count'] = $container['total'] = $collection->count();
		}

		return $container;
	}

	/**
	 * Return the name of the model for Response, load if not already loaded
	 *
	 * @return string
	 */
	private function __model() {
		$model = $this->_model;
		if (!class_exists($model)) {
			\lithium\core\Libraries::load($model);
		}
		return $model;
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
			$container['type'] = 'Entity';
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
